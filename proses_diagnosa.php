<?php // proses_diagnosa.php
include 'config/db.php';
include 'fungsi_nb.php';

 $dipilih = $_POST['gejala'] ?? [];
if (!$dipilih) die("Pilih minimal satu gejala.");

 $model = ambilModel($conn);
 $post  = hitungPosterior($model, $dipilih);          // <-- inti Naive Bayes

 $nama = [];
 $q = $conn->query("SELECT id_penyakit, nama_penyakit FROM penyakit");
while ($r = $q->fetch_assoc()) $nama[$r['id_penyakit']] = $r['nama_penyakit'];

 $no = 0;
foreach ($post as $idP => $p) {
    $no++;
    echo "$no. {$nama[$idP]} — " . round($p*100, 2) . "%"
       . (($no === 1) ? " <b>← hasil utama</b>" : "") . "<br>";
}

// fasilitas penjelasan: tampilkan gejala pembeda dari likelihood tertinggi (kembangkan)
// simpan log
 $ins = $conn->prepare("INSERT INTO konsultasi (gejala_input, hasil_utama, prob_utama) VALUES (?,?,?)");
 $json = json_encode($dipilih);
 $idTop = array_key_first($post);
 $top = $post[$idTop];
 $ins->bind_param("ssd", $json, $idTop, $top);
 $ins->execute();