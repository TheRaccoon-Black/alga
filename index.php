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
<title>Sistem Pakar Diagnosis Penyakit Kulit</title>
<style>
  * { box-sizing: border-box; margin: 0; font-family: 'Segoe UI', Arial, sans-serif; }
  body { background:#f0f4f8; color:#2d3748; }
  .wrap { max-width: 900px; margin: 30px auto; padding: 0 15px; }
  header { background:#1a56db; color:#fff; padding:28px; border-radius:12px; }
  header h1 { font-size:22px; } header p { opacity:.85; margin-top:6px; font-size:14px; }
  .badge { display:inline-block; margin-top:12px; padding:5px 14px; border-radius:20px;
           font-size:13px; font-weight:600; }
  .badge.on  { background:#c6f6d5; color:#22543d; }
  .badge.off { background:#fed7d7; color:#742a2a; }
  .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr));
          gap:12px; margin:22px 0; }
  .card { background:#fff; border-radius:10px; padding:16px; text-align:center;
          box-shadow:0 1px 3px rgba(0,0,0,.08); }
  .card .angka { font-size:26px; font-weight:700; color:#1a56db; }
  .card .label { font-size:12px; color:#718096; margin-top:4px; }
  h2 { font-size:16px; margin:26px 0 12px; color:#2d3748; }
  .menu { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:12px; }
  .menu a { display:flex; gap:12px; align-items:center; background:#fff; padding:16px;
            border-radius:10px; text-decoration:none; color:#2d3748;
            box-shadow:0 1px 3px rgba(0,0,0,.08); transition:.15s; }
  .menu a:hover { transform:translateY(-2px); box-shadow:0 4px 10px rgba(0,0,0,.12); }
  .menu .ikon { font-size:26px; }
  .menu b { display:block; font-size:14px; }
  .menu small { color:#718096; font-size:12px; }
  .utama { background:#1a56db !important; color:#fff !important; }
  .utama small { color:#c3d4f7 !important; }
  footer { text-align:center; font-size:12px; color:#a0aec0; margin:30px 0; }
</style>
</head>
<body>
<div class="wrap">

<header>
  <h1>🩺 Sistem Pakar Diagnosis Penyakit Kulit</h1>
  <p>Metode <i>Naïve Bayes</i> — Studi Kasus RSUD Hasanuddin Damrah Bengkulu Selatan</p>
  <?php if ($siap): ?>
    <span class="badge on">✔ Model siap (<?= $stat['model'] ?> parameter likelihood)</span>
  <?php else: ?>
    <span class="badge off">✖ Model belum dilatih — jalankan Latih Model dulu</span>
  <?php endif; ?>
</header>

<div class="grid">
  <div class="card"><div class="angka"><?= $stat['penyakit'] ?></div><div class="label">Penyakit</div></div>
  <div class="card"><div class="angka"><?= $stat['gejala'] ?></div><div class="label">Gejala</div></div>
  <div class="card"><div class="angka"><?= $stat['kasus'] ?></div><div class="label">Total Kasus</div></div>
  <div class="card"><div class="angka"><?= $stat['latih'] ?></div><div class="label">Data Latih</div></div>
  <div class="card"><div class="angka"><?= $stat['uji'] ?></div><div class="label">Data Uji</div></div>
  <div class="card"><div class="angka"><?= $stat['konsul'] ?></div><div class="label">Konsultasi</div></div>
</div>

<h2>Untuk Pengguna</h2>
<div class="menu">
  <a class="utama" href="konsultasi.php">
    <span class="ikon">💬</span>
    <span><b>Mulai Konsultasi</b><small>Pilih gejala yang dialami, dapatkan diagnosis awal</small></span>
  </a>
</div>

<h2>Panel Admin</h2>
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