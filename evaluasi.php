<?php // evaluasi.php
session_start();
include 'config/db.php';
include 'fungsi_nb.php';

$model  = ambilModel($conn);
$kelas  = array_column($conn->query("SELECT id_penyakit FROM penyakit ORDER BY id_penyakit")->fetch_all(MYSQLI_ASSOC), 'id_penyakit');

$nama = [];
$q = $conn->query("SELECT id_penyakit, nama_penyakit FROM penyakit");
while ($r = $q->fetch_assoc()) $nama[$r['id_penyakit']] = $r['nama_penyakit'];

// 1. CONFUSION MATRIX (DATA UJI)
$uji  = $conn->query("SELECT id_kasus, id_penyakit FROM data_kasus WHERE is_uji=1")->fetch_all(MYSQLI_ASSOC);
$C    = [];
foreach ($kelas as $a) foreach ($kelas as $p) $C[$a][$p] = 0;
foreach ($uji as $k) {
    $post  = hitungPosterior($model, gejalaAktifKasus($conn, (int)$k['id_kasus']));
    $C[$k['id_penyakit']][array_key_first($post)]++;
}

$total = count($uji);
$benar = 0; foreach ($kelas as $k) $benar += $C[$k][$k];
$akurasiUji = $total ? round($benar / $total * 100, 2) : 0;

// 2. AKURASI DATA LATIH
$ujiLatih = $conn->query("SELECT id_kasus, id_penyakit FROM data_kasus WHERE is_uji=0")->fetch_all(MYSQLI_ASSOC);
$benarL   = 0;
foreach ($ujiLatih as $k) {
    $post  = hitungPosterior($model, gejalaAktifKasus($conn, (int)$k['id_kasus']));
    if (array_key_first($post) === $k['id_penyakit']) $benarL++;
}
$akurasiLatih = count($ujiLatih) ? round($benarL / count($ujiLatih) * 100, 2) : 0;

// 3. METRIK PER KELAS
$M = [];
foreach ($kelas as $k) {
    $TP = $C[$k][$k];
    $FP = 0; foreach ($kelas as $a) if ($a !== $k) $FP += $C[$a][$k];
    $FN = array_sum($C[$k]) - $TP;
    $P  = ($TP + $FP) ? $TP / ($TP + $FP) : 0;
    $R  = ($TP + $FN) ? $TP / ($TP + $FN) : 0;
    $M[$k] = ['P' => $P, 'R' => $R, 'F1' => ($P + $R) ? 2 * $P * $R / ($P + $R) : 0];
}
$macro = fn($i) => count($kelas) ? array_sum(array_column($M, $i)) / count($kelas) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Evaluasi Model — Sispak Kulit</title>
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

.wrap { max-width: 1100px; margin: 0 auto; padding: 24px 20px 60px; }

.section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); margin-bottom: 12px; padding-left: 2px; }

.card {
  background: var(--surface); border-radius: var(--radius); padding: 24px;
  box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 20px;
}
.card h3 { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
.card .sub { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; }

/* ── AKURASI CARDS ── */
.akurasi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 24px; }
.akurasi-card {
  background: var(--surface); border-radius: var(--radius); padding: 20px; text-align: center;
  box-shadow: var(--shadow); border: 1px solid var(--border);
}
.akurasi-card .nilai { font-size: 32px; font-weight: 800; color: var(--primary); }
.akurasi-card .label { font-size: 13px; color: var(--text-muted); margin-top: 4px; font-weight: 500; }
.akurasi-card .detail { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

/* ── TABLE ── */
.table-wrap { overflow-x: auto; border-radius: var(--radius); border: 1px solid var(--border); background: var(--surface); }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { padding: 10px 14px; text-align: center; border-bottom: 1px solid var(--border); white-space: nowrap; }
th { background: #f8fafc; font-weight: 600; color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
th:first-child, td:first-child { text-align: left; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #f8fafc; }
tfoot td { font-weight: 700; background: #f8fafc; }

/* ── CONFUSION MATRIX ── */
.cm-cell { padding: 8px 12px; border-radius: 4px; font-weight: 600; font-size: 13px; }
.cm-diag { background: var(--success-light); color: #166534; }
.cm-salah { background: var(--danger-light); color: #991b1b; }
.cm-kosong { background: #f8fafc; color: var(--text-muted); }

/* ── ALERT ── */
.alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
.alert-info  { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.alert-error { background: var(--danger-light); color: #991b1b; border: 1px solid #fca5a5; }

/* ── FOOTER ── */
footer { text-align: center; font-size: 12px; color: var(--text-muted); padding: 20px 0 0; border-top: 1px solid var(--border); }

@media (max-width: 640px) {
  .nav { padding: 0 16px; }
  .nav-links a { padding: 6px 10px; font-size: 13px; }
  .wrap { padding: 16px 14px 48px; }
  .card { padding: 16px; }
}
</style>
</head>
<body>

<nav class="nav">
  <a class="nav-brand" href="index.php"><span>🩺</span> Sispak Kulit</a>
  <div class="nav-links">
    <a href="index.php">Beranda</a>
    <a href="konsultasi.php">Konsultasi</a>
    <a href="evaluasi.php" class="active">Evaluasi</a>
  </div>
</nav>

<div class="wrap">

<h2 class="section-title">Evaluasi Model Naïve Bayes</h2>

<?php if (empty($model['prior'])): ?>
  <div class="alert alert-error">
    ⚠ Model belum dilatih. Silakan lakukan <a href="latih.php">Latih Model</a> terlebih dahulu.
  </div>
<?php else: ?>

<!-- AKURASI -->
<div class="akurasi-grid">
  <div class="akurasi-card">
    <div class="nilai"><?= $akurasiUji ?>%</div>
    <div class="label">Akurasi Data Uji</div>
    <div class="detail"><?= $benar ?> benar dari <?= $total ?> kasus</div>
  </div>
  <div class="akurasi-card">
    <div class="nilai"><?= $akurasiLatih ?>%</div>
    <div class="label">Akurasi Data Latih</div>
    <div class="detail"><?= $benarL ?> benar dari <?= count($ujiLatih) ?> kasus</div>
  </div>
</div>

<!-- PRECISION / RECALL / F1 -->
<div class="card">
  <h3>Metrik per Kelas</h3>
  <p class="sub">Precision, Recall, dan F1-Score untuk setiap penyakit</p>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Penyakit</th>
          <th>Precision</th>
          <th>Recall</th>
          <th>F1-Score</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($M as $k => $v): ?>
        <tr>
          <td style="text-align:left; font-weight:600"><?= htmlspecialchars($nama[$k] ?? $k) ?></td>
          <td><?= round($v['P'] * 100, 1) ?>%</td>
          <td><?= round($v['R'] * 100, 1) ?>%</td>
          <td><strong><?= round($v['F1'] * 100, 1) ?>%</strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td style="text-align:left">Macro Average</td>
          <td><?= round($macro('P') * 100, 1) ?>%</td>
          <td><?= round($macro('R') * 100, 1) ?>%</td>
          <td><?= round($macro('F1') * 100, 1) ?>%</td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<!-- CONFUSION MATRIX -->
<div class="card">
  <h3>Confusion Matrix</h3>
  <p class="sub">Baris = aktual, Kolom = prediksi. Sel hijau = benar, merah = salah.</p>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Aktual \ Prediksi</th>
          <?php foreach ($kelas as $p): ?><th><?= htmlspecialchars($nama[$p] ?? $p) ?></th><?php endforeach; ?>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($kelas as $a): ?>
        <tr>
          <th style="text-align:left"><?= htmlspecialchars($nama[$a] ?? $a) ?></th>
          <?php foreach ($kelas as $p):
            $v = $C[$a][$p];
            $cls = ($a === $p) ? 'cm-diag' : (($v > 0) ? 'cm-salah' : 'cm-kosong');
          ?>
          <td class="cm-cell <?= $cls ?>"><?= $v ?></td>
          <?php endforeach; ?>
          <td style="font-weight:700"><?= array_sum($C[$a]) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<footer>Prototype skripsi — Algalin Zakawali (G1A021077) — Informatika, Universitas Bengkulu</footer>
</div>
</body>
</html>
