<?php // import_csv.php
include 'config/db.php';

$berhasil = 0; $dilewati = 0; $penyakitBaru = []; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Gagal upload: kode error {$file['error']}.";
    } else {
        $tmp = $file['tmp_name'];
        $h = fopen($tmp, 'r');
        if (!$h) {
            $error = "Tidak bisa membuka file sementara.";
        } else {
            $sudahAda = (int) $conn->query("SELECT COUNT(*) c FROM data_kasus")->fetch_assoc()['c'];
            // not blocking — allow re-import with truncation option
            $urutanGejala = ['G01','G02','G03','G04','G05','G06','G07','G08','G09','G10',
                             'G11','G12','G13','G14','G15','G16','G17','G18','G19','G20'];

            $cek = (string) fgets($h);
            $delimiter = (substr_count($cek, ';') > substr_count($cek, ',')) ? ';' : ',';
            rewind($h);

            $baris1 = array_map(fn($v) => trim((string)$v), fgetcsv($h, 0, $delimiter) ?: []);
            $baris1[0] = preg_replace('/^\xEF\xBB\xBF/', '', $baris1[0] ?? '');

            $rows = [];
            if (preg_match('/^P\d+$/i', $baris1[0] ?? '')) {
                $rows[] = $baris1;
            } else {
                echo ""; // header dilewati
            }
            while (($r = fgetcsv($h, 0, $delimiter)) !== false) $rows[] = $r;
            fclose($h);

            foreach ($rows as $row) {
                $row = array_map(fn($v) => trim((string)$v), $row);
                if (array_filter($row) === []) continue;
                if (count($row) !== 25) { $dilewati++; continue; }

                $kodePasien = $row[0];
                $namaP      = $row[24];
                if ($kodePasien === '' || $namaP === '') { $dilewati++; continue; }

                $q = $conn->prepare("SELECT id_penyakit FROM penyakit WHERE LOWER(nama_penyakit)=LOWER(?)");
                $q->bind_param("s", $namaP); $q->execute();
                if ($ada = $q->get_result()->fetch_assoc()) {
                    $idP = $ada['id_penyakit'];
                } else {
                    $c = (int) $conn->query("SELECT COUNT(*) c FROM penyakit")->fetch_assoc()['c'] + 1;
                    $idP = 'PK' . str_pad($c, 2, '0', STR_PAD_LEFT);
                    $ins = $conn->prepare("INSERT INTO penyakit VALUES (?,?)");
                    $ins->bind_param("ss", $idP, $namaP); $ins->execute();
                    $penyakitBaru[] = $namaP;
                }

                $ins = $conn->prepare("INSERT INTO data_kasus (kode_pasien, id_penyakit) VALUES (?,?)");
                $ins->bind_param("ss", $kodePasien, $idP); $ins->execute();
                $idK = $conn->insert_id;

                $insD = $conn->prepare("INSERT INTO detail_kasus VALUES (?,?,?)");
                for ($i = 0; $i < 20; $i++) {
                    $idG = $urutanGejala[$i];
                    $nilai = (int) $row[4 + $i];
                    $insD->bind_param("isi", $idK, $idG, $nilai);
                    $insD->execute();
                }
                $berhasil++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Import CSV — Sispak Kulit</title>
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

/* Upload area */
.upload-area {
  border: 2px dashed var(--border); border-radius: var(--radius); padding: 40px 20px;
  text-align: center; cursor: pointer; transition: .15s; background: var(--bg);
}
.upload-area:hover { border-color: var(--primary-light); background: #eff6ff; }
.upload-area .ikon { font-size: 40px; margin-bottom: 10px; }
.upload-area .title { font-size: 15px; font-weight: 600; color: var(--text); }
.upload-area .hint { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

.form-group { margin-top: 16px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
input[type="file"] { font-size: 14px; }

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

.spec { margin-top: 20px; }
.spec h4 { font-size: 14px; font-weight: 700; margin-bottom: 8px; }
.spec ul { font-size: 13px; color: var(--text-muted); padding-left: 18px; line-height: 1.8; }
.spec code { background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-size: 12px; color: var(--primary-dark); }

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

<h2 class="section-title">Panel Admin — Import Data</h2>

<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && $berhasil > 0): ?>
  <div class="alert alert-success">
    ✅ <b>Import berhasil:</b> <?= $berhasil ?> kasus masuk, <?= $dilewati ?> baris dilewati.
    <?php if ($penyakitBaru): ?>
      <br><span style="color:#92400e">⚠ Penyakit baru otomatis dibuat: <?= htmlspecialchars(implode(', ', $penyakitBaru)) ?></span>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="card">
  <h2>📥 Import Data CSV</h2>
  <p class="sub">Unggah file CSV berisi data kasus penyakit kulit.</p>

  <form method="post" enctype="multipart/form-data">
    <div class="upload-area" id="uploadArea">
      <div class="ikon">📁</div>
      <div class="title">Klik untuk pilih file CSV</div>
      <div class="hint">Format: 25 kolom (4 identitas + 20 gejala + 1 diagnosa)</div>
    </div>
    <input type="hidden" id="fileName" value="">
    <div class="form-group" style="margin-top:12px">
      <label>Pilih file: <span id="fileLabel" style="font-weight:400;color:var(--text-muted)">Belum ada file</span></label>
      <input type="file" name="csv_file" id="csvFile" accept=".csv,.txt" style="font-size:14px;">
    </div>
    <div class="actions">
      <button type="submit" class="btn btn-primary">📤 Unggah & Import</button>
      <a href="index.php" class="btn btn-outline">Kembali</a>
    </div>
  </form>

  <div class="spec">
    <h4>📋 Spesifikasi File CSV</h4>
    <ul>
      <li>Delimiter: <code>;</code> atau <code>,</code> (otomatis terdeteksi)</li>
      <li>Jumlah kolom: <b>25</b> — kolom 1-4: identitas, kolom 5-24: gejala (0/1), kolom 25: nama penyakit</li>
      <li>Baris pertama boleh header (akan otomatis dilewati)</li>
      <li>Kode pasien unik, nama penyakit sesuai master (atau akan dibuat baru)</li>
    </ul>
  </div>
</div>

<footer>Prototype skripsi — Algalin Zakawali (G1A021077) — Informatika, Universitas Bengkulu</footer>
</div>

<script>
const csvFile = document.getElementById('csvFile');
const fileLabel = document.getElementById('fileLabel');
const uploadArea = document.getElementById('uploadArea');

csvFile.addEventListener('change', () => {
  const f = csvFile.files[0];
  fileLabel.textContent = f ? f.name : 'Belum ada file';
  if (uploadArea) uploadArea.querySelector('.title').textContent = f ? f.name : 'Klik untuk pilih file CSV';
});
if (uploadArea) {
  uploadArea.addEventListener('click', () => csvFile.click());
}
</script>
</body>
</html>
