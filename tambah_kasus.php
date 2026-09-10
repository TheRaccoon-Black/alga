<?php
include 'config/db.php';

 $pesan = ''; $tipePesan = 'ok';

// ---------- Hapus satu kasus ----------
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $del = $conn->prepare("DELETE FROM data_kasus WHERE id_kasus=?");
    $del->bind_param("i", $id); $del->execute();
    $pesan = "Kasus #$id dihapus beserta detail gejalanya.";
}

// ---------- Tambah kasus baru ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $kode    = trim($_POST['kode_pasien'] ?? '');
    $idP     = trim($_POST['id_penyakit'] ?? '');
    $dipilih = $_POST['gejala'] ?? [];

    if ($kode === '' || $idP === '') {
        $pesan = "Kode pasien dan penyakit wajib diisi."; $tipePesan = 'err';
    } elseif (count($dipilih) < 1) {
        $pesan = "Pilih minimal satu gejala."; $tipePesan = 'err';
    } else {
        $q = $conn->prepare("SELECT COUNT(*) c FROM data_kasus WHERE kode_pasien=?");
        $q->bind_param("s", $kode); $q->execute();
        if ($q->get_result()->fetch_assoc()['c'] > 0) {
            $pesan = "Kode pasien <b>'$kode'</b> sudah ada di dataset — gunakan kode lain.";
            $tipePesan = 'err';
        } else {
            $ins = $conn->prepare("INSERT INTO data_kasus (kode_pasien, id_penyakit) VALUES (?,?)");
            $ins->bind_param("ss", $kode, $idP); $ins->execute();
            $idK = $conn->insert_id;

            // Simpan SEMUA gejala: 1 untuk yang dicentang, 0 untuk yang tidak
            $ins = $conn->prepare("INSERT INTO detail_kasus VALUES (?,?,?)");
            $semuaGejala = $conn->query("SELECT id_gejala FROM gejala")->fetch_all(MYSQLI_ASSOC);
            foreach ($semuaGejala as $g) {
                $v = in_array($g['id_gejala'], $dipilih, true) ? 1 : 0;
                $ins->bind_param("isi", $idK, $g['id_gejala'], $v); $ins->execute();
            }
            $pesan = "✅ Kasus <b>$kode</b> berhasil ditambahkan ("
                   . count($dipilih) . " gejala aktif). Jangan lupa <b>Latih Model</b> ulang!";
        }
    }
}

// Saran kode pasien otomatis (P001, P002, ...)
 $next = (int) $conn->query(
    "SELECT COALESCE(MAX(CAST(SUBSTRING(kode_pasien,2) AS UNSIGNED)),0)+1 s
     FROM data_kasus WHERE kode_pasien REGEXP '^P[0-9]+$'")->fetch_assoc()['s'];
 $saranKode = 'P' . str_pad($next, 3, '0', STR_PAD_LEFT);

 $penyakit = $conn->query("SELECT * FROM penyakit ORDER BY id_penyakit")->fetch_all(MYSQLI_ASSOC);
 $gejala   = $conn->query("SELECT * FROM gejala ORDER BY id_gejala")->fetch_all(MYSQLI_ASSOC);

 $riwayat = $conn->query(
    "SELECT k.id_kasus, k.kode_pasien, k.is_uji, p.nama_penyakit,
       (SELECT COUNT(*) FROM detail_kasus d WHERE d.id_kasus=k.id_kasus AND d.nilai=1) jml
     FROM data_kasus k JOIN penyakit p ON p.id_penyakit=k.id_penyakit
     ORDER BY k.id_kasus DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Tambah Kasus Manual</title>
<style>
 *{box-sizing:border-box;font-family:'Segoe UI',Arial,sans-serif}
 body{background:#f0f4f8;color:#2d3748;margin:0}
 .wrap{max-width:760px;margin:30px auto;padding:0 15px}
 .box{background:#fff;border-radius:10px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.08);margin-bottom:16px}
 h1{font-size:19px;margin-top:0} h2{font-size:15px}
 input[type=text],select{padding:9px;border:1px solid #cbd5e0;border-radius:7px;font-size:14px;width:100%}
 .grid2{display:grid;grid-template-columns:1fr 2fr;gap:12px;margin-bottom:14px}
 .gejala{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:5px;font-size:13.5px}
 .gejala label{background:#f7fafc;border:1px solid #e2e8f0;border-radius:6px;padding:6px 9px}
 button{background:#1a56db;color:#fff;border:0;padding:11px 24px;border-radius:8px;
        font-size:14px;font-weight:600;cursor:pointer}
 .info{background:#fefcbf;border-radius:7px;padding:9px 14px;font-size:12.5px;margin-top:12px}
 .pesan{padding:11px 15px;border-radius:8px;margin-bottom:14px;font-size:14px}
 .pesan.ok{background:#c6f6d5;color:#22543d} .pesan.err{background:#fed7d7;color:#742a2a}
 table{width:100%;border-collapse:collapse;font-size:13px}
 th,td{border-bottom:1px solid #e2e8f0;padding:7px 8px;text-align:left}
 th{background:#f7fafc}
 .badge-uji{background:#bee3f8;color:#2a4365;border-radius:10px;padding:1px 8px;font-size:11px}
 a.hapus{color:#e53e3e;text-decoration:none;font-size:12.5px}
 a.back{display:inline-block;color:#1a56db;text-decoration:none;font-size:13px}
</style></head><body><div class="wrap">

<div class="box">
<h1>➕ Tambah Kasus Manual</h1>
<?php if ($pesan): ?><div class="pesan <?= $tipePesan ?>"><?= $pesan ?></div><?php endif; ?>

<form method="post">
  <input type="hidden" name="tambah" value="1">
  <div class="grid2">
    <div><b style="font-size:13px">Kode Pasien</b>
      <input type="text" name="kode_pasien" value="<?= $saranKode ?>" required>
    </div>
    <div><b style="font-size:13px">Diagnosis (dari pakar)</b>
      <select name="id_penyakit" required>
        <option value="">— pilih penyakit —</option>
        <?php foreach ($penyakit as $p)
            echo "<option value='{$p['id_penyakit']}'>{$p['nama_penyakit']}</option>"; ?>
      </select>
    </div>
  </div>
  <b style="font-size:13px">Gejala yang dialami:</b><br><br>
  <div class="gejala">
    <?php foreach ($gejala as $g)
        echo "<label><input type='checkbox' name='gejala[]' value='{$g['id_gejala']}'> {$g['nama_gejala']}</label>"; ?>
  </div>
  <br><button type="submit">Simpan Kasus</button>
  <div class="info">℗ Kasus baru otomatis masuk <b>data latih</b>. Untuk memasukkannya ke data uji,
  jalankan ulang <b>Pisah Data</b> (akan mengacak ulang seluruh pembagian), lalu <b>Latih Model</b> &amp; <b>Evaluasi</b>.</div>
</form>
</div>

<div class="box">
<h2>20 Kasus Terakhir</h2>
<table>
  <tr><th>Kode</th><th>Diagnosis</th><th>Gejala aktif</th><th>Set</th><th></th></tr>
  <?php foreach ($riwayat as $r): ?>
  <tr>
    <td><b><?= htmlspecialchars($r['kode_pasien']) ?></b></td>
    <td><?= htmlspecialchars($r['nama_penyakit']) ?></td>
    <td><?= $r['jml'] ?> gejala</td>
    <td><?= $r['is_uji'] ? '<span class="badge-uji">uji</span>' : 'latih' ?></td>
    <td><a class="hapus" href="?hapus=<?= $r['id_kasus'] ?>"
           onclick="return confirm('Hapus kasus <?= htmlspecialchars($r['kode_pasien']) ?>?')">hapus</a></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$riwayat): ?><tr><td colspan="5" style="text-align:center;color:#a0aec0">Belum ada data kasus.</td></tr><?php endif; ?>
</table>
</div>

<a class="back" href="index.php">← Kembali ke dashboard</a>
</div></body></html>