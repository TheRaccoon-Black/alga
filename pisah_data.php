<?php // pisah_data.php
include 'config/db.php';

$totalKasus = (int) $conn->query("SELECT COUNT(*) c FROM data_kasus")->fetch_assoc()['c'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi'])) {
    $conn->query("UPDATE data_kasus SET is_uji = 0");

    $ps = $conn->query("SELECT DISTINCT id_penyakit FROM data_kasus");
    while ($row = $ps->fetch_assoc()) {
        $idP = $row['id_penyakit'];
        $q = $conn->prepare("SELECT id_kasus FROM data_kasus WHERE id_penyakit=? ORDER BY RAND()");
        $q->bind_param("s", $idP); $q->execute();
        $ids = array_column($q->get_result()->fetch_all(MYSQLI_ASSOC), 'id_kasus');

        $nUji = (int) floor(count($ids) * 0.2);
        foreach (array_slice($ids, 0, $nUji) as $id) {
            $u = $conn->prepare("UPDATE data_kasus SET is_uji=1 WHERE id_kasus=?");
            $u->bind_param("i", $id); $u->execute();
        }
    }

    $s = $conn->query("SELECT is_uji, COUNT(*) n FROM data_kasus GROUP BY is_uji");
    $hasil = [];
    while ($r = $s->fetch_assoc()) $hasil[$r['is_uji']] = $r['n'];
    $latih = $hasil[0] ?? 0;
    $uji   = $hasil[1] ?? 0;
    $sukses = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pisah Data — Sispak Kulit</title>
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
.section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); margin-bottom: 12px; padding-left: 2px; }

.card {
  background: var(--surface); border-radius: var(--radius); padding: 28px;
  box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 20px;
}
.card h2 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
.card .sub { font-size: 13px; color: var(--text-muted); margin-bottom: 20px; }

.alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
.alert-info  { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.alert-success { background: var(--success-light); color: #166534; border: 1px solid #bbf7d0; }
.alert-warn  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.alert-error { background: var(--danger-light); color: #991b1b; border: 1px solid #fca5a5; }

/* Split visualization */
.split-visual { display: flex; gap: 0; border-radius: 10px; overflow: hidden; border: 1px solid var(--border); margin: 20px 0; }
.split-part { flex: 1; padding: 20px; text-align: center; }
.split-part.latih { background: #eff6ff; }
.split-part.uji   { background: #fef3c7; }
.split-part .pct { font-size: 28px; font-weight: 800; }
.split-part.latih .pct { color: var(--primary); }
.split-part.uji .pct  { color: #92400e; }
.split-part .lbl { font-size: 13px; font-weight: 600; margin-top: 4px; }
.split-part .count { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
.split-divider { width: 2px; background: var(--border); }

.actions { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
.btn {
  display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px;
  border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none;
  border: none; cursor: pointer; transition: .15s;
}
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: var(--primary-dark); }
.btn-outline { background: transparent; color: var(--primary); border: 1px solid var(--border); }
.btn-outline:hover { background: #eff6ff; border-color: var(--primary-light); }
.btn-warning { background: #f59e0b; color: #fff; }
.btn-warning:hover { background: #d97706; }

footer { text-align: center; font-size: 12px; color: var(--text-muted); padding: 20px 0 0; border-top: 1px solid var(--border); }

@media (max-width: 640px) {
  .nav { padding: 0 16px; }
  .nav-links a { padding: 6px 10px; font-size: 13px; }
  .wrap { padding: 16px 14px 48px; }
  .card { padding: 18px; }
  .split-part .pct { font-size: 22px; }
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

<h2 class="section-title">Panel Admin — Pisah Data</h2>

<?php if (isset($sukses) && $sukses): ?>
  <div class="alert alert-success">✅ Data berhasil dipisah!</div>
  <div class="split-visual">
    <div class="split-part latih">
      <div class="pct">80%</div>
      <div class="lbl">Data Latih</div>
      <div class="count"><?= $latih ?> kasus</div>
    </div>
    <div class="split-divider"></div>
    <div class="split-part uji">
      <div class="pct">20%</div>
      <div class="lbl">Data Uji</div>
      <div class="count"><?= $uji ?> kasus</div>
    </div>
  </div>
  <div class="actions">
    <a href="latih.php" class="btn btn-primary">⚙️ Latih Model</a>
    <a href="index.php" class="btn btn-outline">Ke Beranda</a>
  </div>
<?php else: ?>
  <?php if ($totalKasus === 0): ?>
    <div class="alert alert-error">⚠ Belum ada data. Silakan <a href="import_csv.php">Import Data CSV</a> terlebih dahulu.</div>
  <?php else: ?>
    <div class="card">
      <h2>✂️ Pisah Data Latih & Uji</h2>
      <p class="sub">Membagi dataset menjadi 80% data latih dan 20% data uji secara acak per jenis penyakit (stratified).</p>

      <div class="split-visual">
        <div class="split-part latih">
          <div class="pct">80%</div>
          <div class="lbl">Data Latih</div>
          <div class="count">Akan dibentuk</div>
        </div>
        <div class="split-divider"></div>
        <div class="split-part uji">
          <div class="pct">20%</div>
          <div class="lbl">Data Uji</div>
          <div class="count">Akan dibentuk</div>
        </div>
      </div>

      <div class="alert alert-info">
        📊 Total <b><?= $totalKasus ?></b> kasus akan dibagi. Pengacakan dilakukan secara <i>stratified</i> — setiap jenis penyakit tetap proporsional.
      </div>

      <div class="actions">
        <form method="post" onsubmit="return confirm('Yakin ingin memisah data? Pengaturan is_uji lama akan ditimpa.')">
          <input type="hidden" name="konfirmasi" value="1">
          <button type="submit" class="btn btn-primary">✂️ Pisah Data Sekarang</button>
        </form>
        <a href="index.php" class="btn btn-outline">Kembali</a>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<footer>Prototype skripsi — Algalin Zakawali (G1A021077) — Informatika, Universitas Bengkulu</footer>
</div>
</body>
</html>
