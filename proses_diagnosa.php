<?php // proses_diagnosa.php — redirect ke konsultasi.php yang menangani tampilan
session_start();
include 'config/db.php';
include 'fungsi_nb.php';

$dipilih = $_POST['gejala'] ?? [];
if (!$dipilih) die("<div class='alert alert-error'>Pilih minimal satu gejala.</div>");

$model = ambilModel($conn);
$post  = hitungPosterior($model, $dipilih);

$nama = [];
$q = $conn->query("SELECT id_penyakit, nama_penyakit FROM penyakit");
while ($r = $q->fetch_assoc()) $nama[$r['id_penyakit']] = $r['nama_penyakit'];

// simpan ke session agar konsultasi.php bisa menampilkannya
$_SESSION['hasil_terakhir'] = [
  'gejala' => $dipilih,
  'post'   => $post,
  'nama'   => $nama
];

// simpan log
$ins = $conn->prepare("INSERT INTO konsultasi (gejala_input, hasil_utama, prob_utama) VALUES (?,?,?)");
$json = json_encode($dipilih);
$idTop = array_key_first($post);
$top   = $post[$idTop];
$ins->bind_param("ssd", $json, $idTop, $top);
$ins->execute();

header('Location: konsultasi.php');
exit;
