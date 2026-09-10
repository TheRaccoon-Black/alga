<?php // latih.php
include 'config/db.php';
include 'fungsi_nb.php';

$statModel = (int) $conn->query("SELECT COUNT(*) c FROM model_likelihood")->fetch_assoc()['c'];
$statKasus = (int) $conn->query("SELECT COUNT(*) c FROM data_kasus WHERE is_uji=0")->fetch_assoc()['c'];
$sukses    = false;
$pesan     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi'])) {
    // ---- RESET MODEL LAMA ----
    $conn->query("TRUNCATE model_prior");
    $conn->query("TRUNCATE model_likelihood");

    // ---- 1. PRIOR: P(K) = X / A ----
    $totalLatih = $statKasus;
    $res = $conn->query("SELECT id_penyakit, COUNT(*) x FROM data_kasus
                         WHERE is_uji=0 GROUP BY id_penyakit");
    $insP = $conn->prepare("INSERT INTO model_prior VALUES (?,?,?)");
    while ($r = $res->fetch_assoc()) {
        $x = (int)$r['x'];
        $prior = $x / $totalLatih;
        $insP->bind_param("sid", $r['id_penyakit'], $x, $prior);
        $insP->execute();
    }

    // ---- 2. LIKELIHOOD: P(G|K) = (F+1)/(X+2) — Laplace smoothing ----
    $res = $conn->query("SELECT k.id_penyakit, d.id_gejala, SUM(d.nilai) f, COUNT(*) x
                         FROM detail_kasus d
                         JOIN data_kasus k ON k.id_kasus = d.id_kasus
                         WHERE k.is_uji = 0
                         GROUP BY k.id_penyakit, d.id_gejala");
    $insL = $conn->prepare("INSERT INTO model_likelihood VALUES (?,?,?,?,?)");
    $jmlLikelihood = 0;
    while ($r = $res->fetch_assoc()) {
        $f = (int)$r['f']; $x = (int)$r['x'];
        $pAda = ($f + 1) / ($x + 2);
        $insL->bind_param("ssiid", $r['id_penyakit'], $r['id_gejala'], $f, $x, $pAda);
        $insL->execute();
        $jmlLikelihood++;
    }

    $sukses = true;
    $pesan  = "Pelatihan selesai. Model tersimpan: {$statKasus} data latih, {$jmlLikelihood} parameter likelihood.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Latih Model — Sispak Kulit</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --primary: #1e40af; --primary-light: #3b82f6; --primary-dark: #1e3a8a;
  --success: #16a34a; --success-light: #dcfce7;
  --danger: #dc2626; --danger-light: #fee2e2;
  --bg: #f1f5f9; --surface: #ffffff; --text: #1e293b;
  --text-muted: #64748b; --border: #e2e8f0; --radius: 12px;
  --shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.04);
}
body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; line-height: 1.6; }
.nav {
  background: var(--surface); border-bottom: 1px solid var(--border);
  padding: 0 24px; display: flex; align-items: center; justify-content: space-between;
  height: 60px; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 0 rgba(0,0,0,.06);
}
.nav-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 15px; color: var(--primary); text-decoration: none; }
.nav-brand span { font-size: 22px; }
.nav-links { display: flex; gap: 4px; }
.nav-links a { text-decoration: none; color: var(--text-muted); font-size: 14px; font-weight: 500; padding: 6px 14px; border-radius: 8px; transition: .15s; }
.nav-links a:hover, .nav-links a.active { color: var(--primary); background: #eff6ff; }

.wrap { max-width: 720px; margin: 0 auto; padding: 24px 20px 60px; }

.card {
  background: var(--surface); border-radius: var(--radius); padding: 28px;
  box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 20px;
}
.card h2 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
.card .sub { font-size: 13px; color: var(--text-muted); margin-bottom: 20px; }

.info-row { display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.info-item { flex: 1; min-width: 140px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 14px; text-align: center; }
.info-item .val { font-size: 24px; font-weight: 800; color: var(--primary); }
.info-item .lbl { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

.step { display: flex; gap: 14px; margin-bottom: 16px; }
.step-num {
  width: 28px; height: 28px; border-radius: 50%; background: var(--primary); color: #fff;
  display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0;
}
.step-text { font-size: 14px; color: var(--text); padding-top: 3px; }
.step-text code { background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-size: 13px; color: var(--primary-dark); }

.actions { display: flex; gap: 10px; margin-top: 24px; flex-wrap: wrap; }
.btn {
  display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px;
  border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none;
  border: none; cursor: pointer; transition: .15s;
}
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: var(--primary-dark); }
.btn-outline { background: transparent; color: var(--primary); border: 1px solid var(--border); }
.btn-outline:hover { background: #eff6ff; border-color: var(--primary-light); }
.btn-danger  { background: var(--danger); color: #fff; }
.btn-danger:hover  { background: #b91c1c; }

.alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
.alert-info  { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.alert-success { background: var(--success-light); color: #166534; border: 1px solid #bbf7d0; }
.alert-warn  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.alert-error { background: var(--danger-light); color: #991b1b; border: 1px solid #fca5a5; }

footer { text-align: center; font-size: 12px; color: var(--text-muted); padding: 20px 0 0; border-top: 1px solid var(--border); }

@media (max-width: 640px) {
  .nav { padding: 0 16px; }
  .nav-links a { padding: 6px 10px; font-size: 13px; }
  .wrap { padding: 16px 14px 48px; }
  .card { padding: 18px; }
}
</style>
</head>
<body>

<nav class="nav">
  <a class="nav-brand" href="index.php"><span>🩺</span> Sispak Kulit</a>
  <div class="nav-links">
    <a href="index.php">Beranda</a>
    <a href="konsultasi.php">Konsultasi</a>
    <a href="evaluasi.php">Evaluasi</a>
  </div>
</nav>

<div class="wrap">

<h2 class="section-title" style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:12px;padding-left:2px;">Panel Admin — Latih Model</h2>

<?php if (isset($sukses) && $sukses): ?>
  <div class="alert alert-success">✅ <?= htmlspecialchars($pesan) ?></div>
  <div class="actions">
    <a href="evaluasi.php" class="btn btn-primary">📊 Evaluasi Model</a>
    <a href="index.php"    class="btn btn-outline">Ke Beranda</a>
  </div>
<?php else: ?>

  <?php if ($statKasus === 0): ?>
    <div class="alert alert-error">
      ⚠ Belum ada data latih. Silakan <a href="import_csv.php">Import Data CSV</a> dan <a href="pisah_data.php">Pisah Data</a> terlebih dahulu.
    </div>
  <?php elseif ($statModel > 0): ?>
    <div class="alert alert-warn">
      ⚠ Model sudah ada (<?= $statModel ?> parameter). Pelatihan ulang akan <b>menghapus model lama</b>.
    </div>
  <?php endif; ?>

  <div class="card">
    <h2>⚙️ Pelatihan Model Naïve Bayes</h2>
    <p class="sub">Menghitung prior P(K) dan likelihood P(G|K) dari data latih.</p>

    <div class="info-row">
      <div class="info-item"><div class="val"><?= $statKasus ?></div><div class="lbl">Data Latih</div></div>
      <div class="info-item"><div class="val"><?= $conn->query("SELECT COUNT(*) c FROM penyakit")->fetch_assoc()['c'] ?></div><div class="lbl">Jenis Penyakit</div></div>
      <div class="info-item"><div class="val"><?= $conn->query("SELECT COUNT(*) c FROM gejala")->fetch_assoc()['c'] ?></div><div class="lbl">Jumlah Gejala</div></div>
    </div>

    <div class="step">
      <div class="step-num">1</div>
      <div class="step-text"><b>Hitung Prior</b> — P(K) = jumlah kasus penyakit K / total data latih</div>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <div class="step-text"><b>Hitung Likelihood</b> — P(G|K) = (frekuensi + 1) / (jumlah kasus K + 2) <i>(Laplace smoothing)</i></div>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <div class="step-text"><b>Simpan</b> — hasil disimpan ke tabel <code>model_prior</code> dan <code>model_likelihood</code></div>
    </div>

    <div class="actions">
      <form method="post" onsubmit="return confirm('Yakin ingin melatih ulang model? Data model lama akan dihapus.')">
        <input type="hidden" name="konfirmasi" value="1">
        <button type="submit" class="btn btn-primary">🚀 Latih Model Sekarang</button>
      </form>
      <a href="index.php" class="btn btn-outline">Kembali</a>
    </div>
  </div>

<?php endif; ?>

<footer>Prototype skripsi — Algalin Zakawali (G1A021077) — Informatika, Universitas Bengkulu</footer>
</div>
</body>
</html>
