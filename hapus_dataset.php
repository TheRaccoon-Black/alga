<?php
include 'config/db.php';

 $pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nKasus = (int) $conn->query("SELECT COUNT(*) c FROM data_kasus")->fetch_assoc()['c'];

    // Catat penyakit yang TIDAK terpakai (0 kasus) SEBELUM penghapusan
    $tidakTerpakai = array_column($conn->query(
        "SELECT p.id_penyakit FROM penyakit p
         LEFT JOIN data_kasus k ON k.id_penyakit = p.id_penyakit
         GROUP BY p.id_penyakit HAVING COUNT(k.id_kasus) = 0"
    )->fetch_all(MYSQLI_ASSOC), 'id_penyakit');

    try {
        // Kunci perbaikan: matikan pengecekan FK hanya untuk sesi koneksi ini
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");

        $conn->query("TRUNCATE detail_kasus");
        $conn->query("TRUNCATE data_kasus");
        $pesan = "<b>$nKasus kasus</b> beserta detail gejalanya telah dihapus.";

        if (isset($_POST['hapus_model'])) {
            $conn->query("TRUNCATE model_prior");
            $conn->query("TRUNCATE model_likelihood");
            $pesan .= " Model terlatih ikut dihapus (wajib latih ulang).";
        }
        if (isset($_POST['hapus_tak_terpakai']) && $tidakTerpakai) {
            $in = implode("','", $tidakTerpakai);
            $conn->query("DELETE FROM penyakit WHERE id_penyakit IN ('$in')");
            $pesan .= " " . count($tidakTerpakai) . " penyakit master tanpa kasus ikut dihapus.";
        }
        if (isset($_POST['hapus_log'])) {
            $conn->query("TRUNCATE konsultasi");
            $pesan .= " Log konsultasi dibersihkan.";
        }
    } catch (Throwable $e) {
        $pesan = "Gagal menghapus: " . htmlspecialchars($e->getMessage());
    } finally {
        // WAJIB dikembalikan agar integritas data tetap terjaga
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    }
}
 $stat = [
    'kasus' => $conn->query("SELECT COUNT(*) c FROM data_kasus")->fetch_assoc()['c'],
    'model' => $conn->query("SELECT COUNT(*) c FROM model_likelihood")->fetch_assoc()['c'],
    'log'   => $conn->query("SELECT COUNT(*) c FROM konsultasi")->fetch_assoc()['c'],
];
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Hapus Dataset</title>
<style>
 *{box-sizing:border-box;font-family:'Segoe UI',Arial,sans-serif}
 body{background:#f0f4f8;color:#2d3748;margin:0}
 .wrap{max-width:640px;margin:30px auto;padding:0 15px}
 .box{background:#fff;border-radius:10px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
 h1{font-size:19px} h1 span{color:#e53e3e}
 .stat{background:#edf2f7;border-radius:8px;padding:12px 16px;margin:14px 0;font-size:14px}
 label.opt{display:block;margin:8px 0;font-size:14px}
 .bahaya{background:#fff5f5;border:1px solid #fed7d7;color:#742a2a;border-radius:8px;
         padding:12px 16px;font-size:13px;margin:14px 0}
 button.hapus{background:#e53e3e;color:#fff;border:0;padding:11px 22px;border-radius:8px;
              font-size:14px;font-weight:600;cursor:pointer}
 .pesan{padding:12px 16px;border-radius:8px;margin-bottom:14px;font-size:14px}
 .pesan.ok{background:#c6f6d5;color:#22543d} .pesan.err{background:#fed7d7;color:#742a2a}
 a.back{display:inline-block;margin-top:14px;color:#1a56db;text-decoration:none;font-size:13px}
</style></head><body><div class="wrap"><div class="box">
<h1>🗑️ <span>Hapus Dataset</span></h1>

<?php if ($pesan): ?><div class="pesan ok"><?= $pesan ?></div><?php endif; ?>

<div class="stat">
  Data kasus saat ini: <b><?= $stat['kasus'] ?></b> ·
  Parameter model: <b><?= $stat['model'] ?></b> ·
  Log konsultasi: <b><?= $stat['log'] ?></b>
</div>

<div class="bahaya">⚠️ Tindakan ini <b>tidak dapat dibatalkan</b>. Master penyakit &amp; gejala
<b>tidak ikut dihapus</b>, jadi import ulang CSV akan langsung cocok dengan master yang ada.</div>

<form method="post" onsubmit="return confirm('Yakin menghapus seluruh dataset? Tindakan ini permanen!')">
  <label class="opt"><input type="checkbox" name="hapus_model" checked>
    Hapus juga model terlatih (disarankan — data berubah, model harus dilatih ulang)</label>
  <label class="opt"><input type="checkbox" name="hapus_tak_terpakai" checked>
    Hapus penyakit master yang tidak memiliki kasus (membersihkan sisa duplikat)</label>
  <label class="opt"><input type="checkbox" name="hapus_log">
    Bersihkan juga log konsultasi</label>
  <br><button class="hapus" type="submit">Hapus Semua Data Kasus</button>
</form>

<a class="back" href="index.php">← Kembali ke dashboard</a>
</div></div></body></html>