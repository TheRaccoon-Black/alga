<?php // cek_db.php
include 'config/db.php';
$db = $conn->query("SELECT DATABASE() d")->fetch_assoc()['d'];
$tables = ['penyakit','gejala','data_kasus','model_prior','model_likelihood','konsultasi'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cek Database — Sispak Kulit</title>
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
  background: var(--surface); border-radius: var(--radius); padding: 24px;
  box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 20px;
}
.card h2 { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
.card .db-name { font-size: 13px; color: var(--text-muted); }

.table-wrap { overflow-x: auto; border-radius: var(--radius); border: 1px solid var(--border); background: var(--surface); }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { padding: 10px 16px; text-align: left; border-bottom: 1px solid var(--border); }
th { background: #f8fafc; font-weight: 600; color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
td:last-child, th:last-child { text-align: right; font-weight: 700; color: var(--primary); }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #f8fafc; }

.alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
.alert-success { background: var(--success-light); color: #166534; border: 1px solid #bbf7d0; }
.alert-error  { background: var(--danger-light); color: #991b1b; border: 1px solid #fca5a5; }

.actions { display: flex; gap: 10px; margin-top: 16px; }
.btn {
  display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px;
  border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none;
  border: none; cursor: pointer; transition: .15s;
}
.btn-outline { background: transparent; color: var(--primary); border: 1px solid var(--border); }
.btn-outline:hover { background: #eff6ff; border-color: var(--primary-light); }

footer { text-align: center; font-size: 12px; color: var(--text-muted); padding: 20px 0 0; border-top: 1px solid var(--border); }

@media (max-width: 640px) {
  .nav { padding: 0 16px; }
  .nav-links a { padding: 6px 10px; font-size: 13px; }
  .wrap { padding: 16px 14px 48px; }
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

<div class="card">
  <h2>🔍 Cek Database</h2>
  <p class="db-name">Database: <b><?= htmlspecialchars($db) ?></b> — terhubung ke <b>localhost</b></p>
</div>

<?php
$err = false;
try {
    $connected = true;
} catch (\Exception $e) {
    $connected = false;
    $err = $e->getMessage();
}
?>

<?php if (!$connected): ?>
  <div class="alert alert-error">❌ Gagal terhubung ke database: <?= htmlspecialchars($err) ?></div>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Tabel</th><th>Jumlah Baris</th></tr>
      </thead>
      <tbody>
        <?php foreach ($tables as $t):
          $c = (int) $conn->query("SELECT COUNT(*) c FROM $t")->fetch_assoc()['c'];
        ?>
        <tr><td><code><?= htmlspecialchars($t) ?></code></td><td><?= number_format($c) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="actions">
    <a href="index.php" class="btn btn-outline">← Kembali ke Beranda</a>
  </div>
<?php endif; ?>

<footer>Prototype skripsi — Algalin Zakawali (G1A021077) — Informatika, Universitas Bengkulu</footer>
</div>
</body>
</html>
