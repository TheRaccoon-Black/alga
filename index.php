<?php
include 'config/db.php';

$stat = [];
$stat['penyakit'] = $conn->query("SELECT COUNT(*) c FROM penyakit")->fetch_assoc()['c'];
$stat['gejala']   = $conn->query("SELECT COUNT(*) c FROM gejala")->fetch_assoc()['c'];
$stat['kasus']    = $conn->query("SELECT COUNT(*) c FROM data_kasus")->fetch_assoc()['c'];
$stat['latih']    = $conn->query("SELECT COUNT(*) c FROM data_kasus WHERE is_uji=0")->fetch_assoc()['c'];
$stat['uji']      = $conn->query("SELECT COUNT(*) c FROM data_kasus WHERE is_uji=1")->fetch_assoc()['c'];
$stat['model']    = $conn->query("SELECT COUNT(*) c FROM model_likelihood")->fetch_assoc()['c'];
$stat['konsul']   = $conn->query("SELECT COUNT(*) c FROM konsultasi")->fetch_assoc()['c'];
$siap = $stat['model'] > 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistem Pakar Diagnosis Penyakit Kulit</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --primary: #1e40af;
  --primary-light: #3b82f6;
  --primary-dark: #1e3a8a;
  --accent: #0ea5e9;
  --success: #16a34a;
  --success-light: #dcfce7;
  --danger: #dc2626;
  --danger-light: #fee2e2;
  --warning: #f59e0b;
  --bg: #f1f5f9;
  --surface: #ffffff;
  --text: #1e293b;
  --text-muted: #64748b;
  --border: #e2e8f0;
  --radius: 12px;
  --shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.04);
  --shadow-lg: 0 4px 24px rgba(0,0,0,.10);
}

body {
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  line-height: 1.6;
}

/* ── NAV ── */
.nav {
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 60px;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 1px 0 rgba(0,0,0,.06);
}
.nav-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  font-size: 15px;
  color: var(--primary);
  text-decoration: none;
}
.nav-brand span { font-size: 22px; }
.nav-links { display: flex; gap: 4px; }
.nav-links a {
  text-decoration: none;
  color: var(--text-muted);
  font-size: 14px;
  font-weight: 500;
  padding: 6px 14px;
  border-radius: 8px;
  transition: .15s;
}
.nav-links a:hover, .nav-links a.active {
  color: var(--primary);
  background: #eff6ff;
}

/* ── WRAP ── */
.wrap { max-width: 1080px; margin: 0 auto; padding: 24px 20px 60px; }

/* ── HERO ── */
.hero {
  background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
  color: #fff;
  border-radius: var(--radius);
  padding: 32px 36px;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
}
.hero::after {
  content: '';
  position: absolute;
  right: -40px; top: -40px;
  width: 200px; height: 200px;
  background: rgba(255,255,255,.06);
  border-radius: 50%;
}
.hero h1 { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
.hero p { font-size: 14px; opacity: .85; margin-bottom: 14px; }
.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
}
.badge.on  { background: var(--success-light); color: #166534; }
.badge.off { background: var(--danger-light); color: #991b1b; }

/* ── STAT GRID ── */
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 28px;
}
.stat-card {
  background: var(--surface);
  border-radius: var(--radius);
  padding: 18px 16px;
  text-align: center;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  transition: transform .15s, box-shadow .15s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
.stat-card .angka { font-size: 28px; font-weight: 800; color: var(--primary); line-height: 1.2; }
.stat-card .label { font-size: 12px; color: var(--text-muted); margin-top: 4px; font-weight: 500; }

/* ── SECTION ── */
.section-title {
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--text-muted);
  margin-bottom: 12px;
  padding-left: 2px;
}

/* ── MENU GRID ── */
.menu {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 12px;
  margin-bottom: 32px;
}
.menu a {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  background: var(--surface);
  padding: 18px 20px;
  border-radius: var(--radius);
  text-decoration: none;
  color: var(--text);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  transition: transform .15s, box-shadow .15s, border-color .15s;
}
.menu a:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
  border-color: var(--primary-light);
}
.menu .ikon { font-size: 24px; line-height: 1; flex-shrink: 0; margin-top: 2px; }
.menu b { display: block; font-size: 14px; font-weight: 600; }
.menu small { display: block; font-size: 12px; color: var(--text-muted); margin-top: 2px; line-height: 1.4; }

.menu a.utama {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
  border-color: transparent;
  color: #fff;
}
.menu a.utama b { color: #fff; }
.menu a.utama small { color: rgba(255,255,255,.8); }
.menu a.utama:hover { border-color: transparent; }

/* ── FOOTER ── */
footer {
  text-align: center;
  font-size: 12px;
  color: var(--text-muted);
  padding: 20px 0 0;
  border-top: 1px solid var(--border);
}

/* ── TABLE ── */
.table-wrap { overflow-x: auto; border-radius: var(--radius); border: 1px solid var(--border); background: var(--surface); }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border); white-space: nowrap; }
th { background: #f8fafc; font-weight: 600; color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #f8fafc; }

/* ── BUTTONS ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: .15s;
}
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: var(--primary-dark); }
.btn-outline { background: transparent; color: var(--primary); border: 1px solid var(--border); }
.btn-outline:hover { background: #eff6ff; border-color: var(--primary-light); }
.btn-sm { padding: 6px 12px; font-size: 13px; }

/* ── ALERT ── */
.alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  margin-bottom: 16px;
}
.alert-info  { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.alert-success { background: var(--success-light); color: #166534; border: 1px solid #bbf7d0; }
.alert-warn  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.alert-error { background: var(--danger-light); color: #991b1b; border: 1px solid #fca5a5; }

/* ── FORM ── */
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 4px; }
.form-control {
  width: 100%; padding: 9px 12px; border: 1px solid var(--border);
  border-radius: 8px; font-size: 14px; background: var(--surface); color: var(--text);
  transition: border-color .15s;
}
.form-control:focus { outline: none; border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(59,130,246,.15); }

/* ── CHECKBOX LIST ── */
.checkbox-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 8px;
  margin: 16px 0;
}
.checkbox-grid label {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  transition: .15s;
}
.checkbox-grid label:hover { border-color: var(--primary-light); background: #f8faff; }
.checkbox-grid input[type=checkbox] { width: 16px; height: 16px; accent-color: var(--primary); }

/* ── RESULT CARD ── */
.result-list { display: flex; flex-direction: column; gap: 8px; margin: 16px 0; }
.result-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  transition: .15s;
}
.result-item:first-child { border-color: var(--primary-light); background: #eff6ff; }
.result-item .rank { font-weight: 700; color: var(--text-muted); font-size: 13px; width: 24px; }
.result-item .name { font-weight: 600; font-size: 14px; flex: 1; }
.result-item .pct { font-weight: 700; color: var(--primary); font-size: 15px; }
.result-item.top .name::after { content: ' ← hasil utama'; font-size: 12px; color: var(--success); font-weight: 600; }

/* ── PROGRESS BAR ── */
.progress-row { display: flex; align-items: center; gap: 10px; }
.progress-bar {
  flex: 1; height: 8px; background: var(--border); border-radius: 99px; overflow: hidden;
}
.progress-bar .fill { height: 100%; background: var(--primary); border-radius: 99px; transition: width .4s; }
.result-item.top .progress-bar .fill { background: var(--success); }

/* ── RESPONSIVE ── */
@media (max-width: 640px) {
  .nav { padding: 0 16px; }
  .nav-links { gap: 0; }
  .nav-links a { padding: 6px 10px; font-size: 13px; }
  .hero { padding: 24px 20px; }
  .hero h1 { font-size: 18px; }
  .wrap { padding: 16px 14px 48px; }
}
</style>
</head>
<body>

<nav class="nav">
  <a class="nav-brand" href="index.php">
    <span>🩺</span> Sispak Kulit
  </a>
  <div class="nav-links">
    <a href="index.php"         <?php echo basename($_SERVER['PHP_SELF'])==='index.php'?'class="active"':'' ?>>Beranda</a>
    <a href="konsultasi.php"    <?php echo basename($_SERVER['PHP_SELF'])==='konsultasi.php'?'class="active"':'' ?>>Konsultasi</a>
    <a href="evaluasi.php"      <?php echo basename($_SERVER['PHP_SELF'])==='evaluasi.php'?'class="active"':'' ?>>Evaluasi</a>
  </div>
</nav>

<div class="wrap">

<div class="hero">
  <h1>Sistem Pakar Diagnosis Penyakit Kulit</h1>
  <p>Metode <i>Naïve Bayes</i> — Studi Kasus RSUD Hasanuddin Damrah Bengkulu Selatan</p>
  <?php if ($siap): ?>
    <span class="badge on">✔ Model siap (<?= $stat['model'] ?> parameter likelihood)</span>
  <?php else: ?>
    <span class="badge off">✖ Model belum dilatih — jalankan Latih Model dulu</span>
  <?php endif; ?>
</div>

<div class="stats">
  <div class="stat-card"><div class="angka"><?= $stat['penyakit'] ?></div><div class="label">Penyakit</div></div>
  <div class="stat-card"><div class="angka"><?= $stat['gejala'] ?></div><div class="label">Gejala</div></div>
  <div class="stat-card"><div class="angka"><?= $stat['kasus'] ?></div><div class="label">Total Kasus</div></div>
  <div class="stat-card"><div class="angka"><?= $stat['latih'] ?></div><div class="label">Data Latih</div></div>
  <div class="stat-card"><div class="angka"><?= $stat['uji'] ?></div><div class="label">Data Uji</div></div>
  <div class="stat-card"><div class="angka"><?= $stat['konsul'] ?></div><div class="label">Konsultasi</div></div>
</div>

<h2 class="section-title">Untuk Pengguna</h2>
<div class="menu">
  <a class="utama" href="konsultasi.php">
    <span class="ikon">💬</span>
    <span>
      <b>Mulai Konsultasi</b>
      <small>Pilih gejala yang dialami, dapatkan diagnosis awal secara otomatis</small>
    </span>
  </a>
</div>

<h2 class="section-title">Panel Admin</h2>
<div class="menu">
  <a href="import_csv.php">
    <span class="ikon">📥</span>
    <span><b>Import Data CSV</b><small>Masukkan data kasus dari file Excel/CSV</small></span>
  </a>
  <a href="pisah_data.php">
    <span class="ikon">✂️</span>
    <span><b>Pisah Data</b><small>Bagi dataset 80% latih : 20% uji</small></span>
  </a>
  <a href="latih.php">
    <span class="ikon">⚙️</span>
    <span><b>Latih Model</b><small>Hitung nilai prior &amp; likelihood (Naive Bayes)</small></span>
  </a>
  <a href="evaluasi.php">
    <span class="ikon">📊</span>
    <span><b>Evaluasi Model</b><small>Akurasi, precision, recall, F1-score</small></span>
  </a>
</div>

<footer>Prototype skripsi — Algalin Zakawali (G1A021077) — Informatika, Universitas Bengkulu</footer>
</div>
</body>
</html>
