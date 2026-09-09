<?php
include 'config/db.php';
include 'fungsi_nb.php';

 $model = ambilModel($conn);
 $kelas = array_column($conn->query("SELECT id_penyakit FROM penyakit ORDER BY id_penyakit")
                      ->fetch_all(MYSQLI_ASSOC), 'id_penyakit');

// Nama penyakit untuk tampilan yang lebih mudah dibaca
 $nama = [];
 $q = $conn->query("SELECT id_penyakit, nama_penyakit FROM penyakit");
while ($r = $q->fetch_assoc()) $nama[$r['id_penyakit']] = $r['nama_penyakit'];

// ================= 1. CONFUSION MATRIX (DATA UJI) =================
 $uji = $conn->query("SELECT id_kasus, id_penyakit FROM data_kasus WHERE is_uji=1")
            ->fetch_all(MYSQLI_ASSOC);

 $C = [];
foreach ($kelas as $a) foreach ($kelas as $p) $C[$a][$p] = 0;
foreach ($uji as $k) {
    $post = hitungPosterior($model, gejalaAktifKasus($conn, (int)$k['id_kasus']));
    $C[$k['id_penyakit']][ array_key_first($post) ]++;
}

// ================= 2. AKURASI DATA UJI =================
 $total = count($uji);
 $benar = 0; foreach ($kelas as $k) $benar += $C[$k][$k];
echo "<h3>Akurasi Data Uji: " . round($benar / $total * 100, 2) . "% ($benar/$total)</h3>";

// ================= 3. AKURASI DATA LATIH (tes baru!) =================
 $ujiLatih = $conn->query("SELECT id_kasus, id_penyakit FROM data_kasus WHERE is_uji=0")
                 ->fetch_all(MYSQLI_ASSOC);
 $benarL = 0;
foreach ($ujiLatih as $k) {
    $post = hitungPosterior($model, gejalaAktifKasus($conn, (int)$k['id_kasus']));
    if (array_key_first($post) === $k['id_penyakit']) $benarL++;
}
echo "<h3>Akurasi Data Latih: " . round($benarL / count($ujiLatih) * 100, 2)
   . "% ($benarL/" . count($ujiLatih) . ")</h3>";

// ================= 4. PRECISION / RECALL / F1 PER KELAS =================
 $M = [];
foreach ($kelas as $k) {
    $TP = $C[$k][$k];
    $FP = 0; foreach ($kelas as $a) if ($a !== $k) $FP += $C[$a][$k];
    $FN = array_sum($C[$k]) - $TP;
    $P = ($TP + $FP) ? $TP / ($TP + $FP) : 0;
    $R = ($TP + $FN) ? $TP / ($TP + $FN) : 0;
    $M[$k] = ['P' => $P, 'R' => $R, 'F1' => ($P + $R) ? 2 * $P * $R / ($P + $R) : 0];
}
 $macro = fn($i) => array_sum(array_column($M, $i)) / count($kelas);

echo "<h4>Metrik per kelas</h4><table border=1 cellpadding=4>
      <tr><th>Penyakit</th><th>Precision</th><th>Recall</th><th>F1</th></tr>";
foreach ($M as $k => $v)
    echo "<tr><td>{$nama[$k]}</td><td>" . round($v['P'] * 100, 1) . "%</td><td>"
       . round($v['R'] * 100, 1) . "%</td><td>" . round($v['F1'] * 100, 1) . "%</td></tr>";
echo "<tr><th>Macro avg</th><th>" . round($macro('P') * 100, 1) . "%</th><th>"
   . round($macro('R') * 100, 1) . "%</th><th>" . round($macro('F1') * 100, 1) . "%</th></tr></table>";

// ================= 5. TAMPILKAN CONFUSION MATRIX =================
echo "<h4>Confusion Matrix (baris = aktual, kolom = prediksi)</h4>
      <table border=1 cellpadding=4 style='border-collapse:collapse;font-size:12px'>
      <tr><th>Aktual \\ Prediksi</th>";
foreach ($kelas as $p) echo "<th>{$nama[$p]}</th>";
echo "<th>Total</th></tr>";

foreach ($kelas as $a) {
    echo "<tr><th style='text-align:left'>{$nama[$a]}</th>";
    foreach ($kelas as $p) {
        $v = $C[$a][$p];
        // sel diagonal (benar) diberi warna hijau, sel salah besar diberi warna merah muda
        $style = ($a === $p) ? "background:#c6f6d5"
               : (($v > 0) ? "background:#fed7d7" : "");
        echo "<td style='$style;text-align:center'>$v</td>";
    }
    echo "<td style='text-align:center'><b>" . array_sum($C[$a]) . "</b></td></tr>";
}
echo "</table>";