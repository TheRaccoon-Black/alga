<?php // konsultasi.php
session_start();
include 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Konsultasi — Sispak Kulit</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --primary: #1e40af; --primary-light: #3b82f6; --primary-dark: #1e3a8a;
  --accent: #0ea5e9; --success: #16a34a; --success-light: #dcfce7;
  --danger: #dc2626; --danger-light: #fee2e2;
  --bg: #f1f5f9; --surface: #ffffff; --text: #1e293b;
  --text-muted: #64748b; --border: #e2e8f0; --radius: 12px;
  --shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.04);
  --shadow-lg: 0 4px 24px rgba(0,0,0,.10);
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

.wrap { max-width: 800px; margin: 0 auto; padding: 24px 20px 60px; }

.card {
  background: var(--surface); border-radius: var(--radius); padding: 28px;
  box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 20px;
}
.card h2 { font-size: 18px; font-weight: 700; margin-bottom: 4px; color: var(--text); }
.card .sub { font-size: 13px; color: var(--text-muted); margin-bottom: 20px; }

.checkbox-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 8px;
}
.checkbox-grid label {
  display: flex; align-items: center; gap: 10px; padding: 10px 14px;
  background: var(--bg); border: 1px solid var(--border); border-radius: 8px;
  cursor: pointer; font-size: 14px; transition: .15s;
}
.checkbox-grid label:hover { border-color: var(--primary-light); background: #f8faff; }
.checkbox-grid input[type=checkbox] { width: 16px; height: 16px; accent-color: var(--primary); }

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

.alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
.alert-info  { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.alert-warn  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.alert-error { background: var(--danger-light); color: #991b1b; border: 1px solid #fca5a5; }

.result-list { display: flex; flex-direction: column; gap: 8px; margin: 16px 0; }
.result-item {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 18px; background: var(--surface); border: 1px solid var(--border);
  border-radius: 10px; transition: .15s;
}
.result-item:first-child { border-color: var(--primary-light); background: #eff6ff; }
.result-item .rank { font-weight: 700; color: var(--text-muted); font-size: 13px; width: 24px; }
.result-item .name { font-weight: 600; font-size: 14px; flex: 1; }
.result-item .pct { font-weight: 700; color: var(--primary); font-size: 15px; }
.result-item.top .name::after { content: ' ← hasil utama'; font-size: 12px; color: var(--success); font-weight: 600; }

.progress-row { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
.progress-bar { flex: 1; height: 8px; background: var(--border); border-radius: 99px; overflow: hidden; }
.progress-bar .fill { height: 100%; background: var(--primary); border-radius: 99px; transition: width .4s; }
.result-item.top .progress-bar .fill { background: var(--success); }

footer { text-align: center; font-size: 12px; color: var(--text-muted); padding: 20px 0 0; border-top: 1px solid var(--border); }

@media (max-width: 640px) {
  .nav { padding: 0 16px; }
  .nav-links a { padding: 6px 10px; font-size: 13px; }
  .wrap { padding: 16px 14px 48px; }
  .card { padding: 20px; }
}
</style>
</head>
<body>

<nav class="nav">
  <a class="nav-brand" href="index.php"><span>🩺</span> Sispak Kulit</a>
  <div class="nav-links">
    <a href="index.php">Beranda</a>
    <a href="konsultasi.php" class="active">Konsultasi</a>
    <a href="evaluasi.php">Evaluasi</a>
  </div>
</nav>

<div class="wrap">

<?php
// Hapus session jika diminta (tombol "Konsultasi Baru")
if (isset($_GET['baru'])) { unset($_SESSION['hasil_terakhir']); }

// Hasil bisa datang dari POST langsung atau dari session (setelah redirect)
$sumberHasil = !empty($_SESSION['hasil_terakhir']) ? $_SESSION['hasil_terakhir'] : null;
$displayPost = !empty($_POST['gejala']) ? $_POST['gejala'] : null;

if (!empty($displayPost) || !empty($sumberHasil)):
  include 'fungsi_nb.php';

  if (!empty($sumberHasil)) {
      $dipilih  = $sumberHasil['gejala'];
      $post     = $sumberHasil['post'];
      $nama     = $sumberHasil['nama'];
  } else {
      $dipilih  = $displayPost;
      $model    = ambilModel($conn);
      $post     = hitungPosterior($model, $dipilih);
      $nama     = [];
      $q        = $conn->query("SELECT id_penyakit, nama_penyakit FROM penyakit");
      while ($r = $q->fetch_assoc()) $nama[$r['id_penyakit']] = $r['nama_penyakit'];

      $ins = $conn->prepare("INSERT INTO konsultasi (gejala_input, hasil_utama, prob_utama) VALUES (?,?,?)");
      $json  = json_encode($dipilih);
      $idTop = array_key_first($post);
      $top   = $post[$idTop];
      $ins->bind_param("ssd", $json, $idTop, $top);
      $ins->execute();
      $_SESSION['hasil_terakhir'] = ['gejala' => $dipilih, 'post' => $post, 'nama' => $nama];
  }
  ?>
  <div class="card">
    <h2>📋 Hasil Diagnosis</h2>
    <p class="sub">Berdasarkan <?= count($dipilih) ?> gejala yang dipilih:</p>
    <div class="result-list">
      <?php $no = 0; foreach ($post as $idP => $p): $no++; ?>
      <div class="result-item<?= $no === 1 ? ' top' : '' ?>">
        <span class="rank">#<?= $no ?></span>
        <div class="progress-row">
          <span class="name"><?= htmlspecialchars($nama[$idP] ?? $idP) ?></span>
          <div class="progress-bar"><div class="fill" style="width:<?= round($p*100) ?>%"></div></div>
        </div>
        <span class="pct"><?= round($p*100, 2) ?>%</span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="actions">
      <a href="konsultasi.php?baru=1" class="btn btn-outline">← Konsultasi Baru</a>
      <a href="index.php"      class="btn btn-outline">Ke Beranda</a>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <h2>💬 Mulai Konsultasi</h2>
    <p class="sub">Centang gejala-gejala yang Anda rasakan, lalu klik tombol Diagnosa untuk mendapatkan prediksi penyakit kulit.</p>
    <form method="post" action="proses_diagnosa.php" onsubmit="return checkGejala()">
    <?php
    $q = $conn->query("SELECT * FROM gejala ORDER BY id_gejala");
    while ($g = $q->fetch_assoc()): ?>
    <div class="checkbox-grid">
      <label>
        <input type="checkbox" name="gejala[]" value="<?= $g['id_gejala'] ?>">
        <?= htmlspecialchars($g['nama_gejala']) ?>
      </label>
    </div>
    <?php endwhile; ?>
      <div class="actions">
        <button type="button" id="btnPilihSemua" class="btn btn-outline btn-sm">Pilih Semua</button>
        <button type="button" id="btnHapusSemua"  class="btn btn-outline btn-sm">Hapus Semua</button>
        <button type="submit" class="btn btn-primary">🔍 Diagnosa</button>
      </div>
      <p style="font-size:12px; color:var(--text-muted); margin-top:12px;">
        ⚠️ <i>Hasil ini hanya bersifat praduga. Konsultasikan dengan dokter untuk diagnosis yang pasti.</i>
      </p>
    </form>
  </div>
<?php endif; ?>

<footer>Prototype skripsi — Algalin Zakawali (G1A021077) — Informatika, Universitas Bengkulu</footer>
</div>

<script>
function checkGejala() {
  const checked = document.querySelectorAll('input[name="gejala[]"]:checked');
  if (checked.length === 0) { alert('Pilih minimal satu gejala!'); return false; }
}
document.getElementById('btnPilihSemua')?.addEventListener('click', () => {
  document.querySelectorAll('input[name="gejala[]"]').forEach(cb => cb.checked = true);
});
document.getElementById('btnHapusSemua')?.addEventListener('click', () => {
  document.querySelectorAll('input[name="gejala[]"]').forEach(cb => cb.checked = false);
});
</script>
</body>
</html>
