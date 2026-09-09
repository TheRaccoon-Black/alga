<?php
include 'config/db.php';

// Urutan 20 gejala = urutan kolom ke-5 s/d ke-24 di CSV
 $urutanGejala = ['G01','G02','G03','G04','G05','G06','G07','G08','G09','G10',
                 'G11','G12','G13','G14','G15','G16','G17','G18','G19','G20'];

// ---- Tolak jika sudah ada data (cegah import ganda) ----
 $sudahAda = (int) $conn->query("SELECT COUNT(*) c FROM data_kasus")->fetch_assoc()['c'];
if ($sudahAda > 0) die("data_kasus sudah berisi $sudahAda baris. Bersihkan dulu sebelum import ulang.");

 $h = fopen('data_mentah.csv', 'r');
if (!$h) die("File data_mentah.csv tidak ditemukan.");

// ---- Deteksi delimiter (; atau ,) ----
 $cek = (string) fgets($h);
 $delimiter = (substr_count($cek, ';') > substr_count($cek, ',')) ? ';' : ',';
rewind($h);

// ---- Baca semua baris ----
 $rows = [];
 $baris1 = array_map(fn($v) => trim((string)$v), fgetcsv($h, 0, $delimiter) ?: []);
 $baris1[0] = preg_replace('/^\xEF\xBB\xBF/', '', $baris1[0] ?? ''); // hapus BOM

if (preg_match('/^P\d+$/i', $baris1[0] ?? '')) {
    $rows[] = $baris1;                       // baris pertama = data
} else {
    echo "Baris pertama terdeteksi sebagai header → dilewati.<br>";
}
while (($r = fgetcsv($h, 0, $delimiter)) !== false) $rows[] = $r;
fclose($h);

// ---- Proses tiap baris ----
 $berhasil = 0; $dilewati = 0; $penyakitBaru = [];

foreach ($rows as $row) {
    $row = array_map(fn($v) => trim((string)$v), $row);
    if (array_filter($row) === []) continue;

    // Struktur wajib: 4 kolom identitas + 20 gejala + 1 diagnosa = 25
    if (count($row) !== 25) {
        $dilewati++;
        echo "Dilewati (jumlah kolom " . count($row) . ", bukan 25): "
           . htmlspecialchars($row[0] ?? '') . "<br>";
        continue;
    }

    $kodePasien = $row[0];
    $namaP      = $row[24];
    if ($kodePasien === '' || $namaP === '') { $dilewati++; continue; }

    // ---- Cari penyakit (tidak peduli huruf besar/kecil) ----
    $q = $conn->prepare("SELECT id_penyakit FROM penyakit WHERE LOWER(nama_penyakit)=LOWER(?)");
    $q->bind_param("s", $namaP); $q->execute();
    if ($ada = $q->get_result()->fetch_assoc()) {
        $idP = $ada['id_penyakit'];
    } else {
        // Nama tidak cocok dengan master → daftarkan otomatis + beri tahu
        $c = (int) $conn->query("SELECT COUNT(*) c FROM penyakit")->fetch_assoc()['c'] + 1;
        $idP = 'PK' . str_pad($c, 2, '0', STR_PAD_LEFT);
        $ins = $conn->prepare("INSERT INTO penyakit VALUES (?,?)");
        $ins->bind_param("ss", $idP, $namaP); $ins->execute();
        $penyakitBaru[] = $namaP;
    }

    // ---- Simpan kasus ----
    $ins = $conn->prepare("INSERT INTO data_kasus (kode_pasien, id_penyakit) VALUES (?,?)");
    $ins->bind_param("ss", $kodePasien, $idP); $ins->execute();
    $idK = $conn->insert_id;

    // ---- Simpan 20 nilai gejala (0 dan 1 sama-sama disimpan) ----
    $ins = $conn->prepare("INSERT INTO detail_kasus VALUES (?,?,?)");
    for ($i = 0; $i < 20; $i++) {
        $idG = $urutanGejala[$i];
        $nilai = (int) $row[4 + $i];
        $ins->bind_param("isi", $idK, $idG, $nilai); $ins->execute();
    }
    $berhasil++;
}

// ---- Ringkasan ----
echo "<hr><b>Import selesai:</b> $berhasil kasus masuk, $dilewati baris dilewati.<br>";
if ($penyakitBaru) {
    echo "<b style='color:#b00'>⚠ Nama diagnosa berikut TIDAK cocok dengan master, jadi dibuat sebagai penyakit baru: "
       . htmlspecialchars(implode(', ', $penyakitBaru))
       . " → samakan penulisannya dengan master atau perbaiki CSV!</b><br>";
}
 $t = $conn->query("SELECT COUNT(*) c FROM penyakit")->fetch_assoc()['c'];
echo "Total penyakit di database sekarang: $t (harusnya 12).";