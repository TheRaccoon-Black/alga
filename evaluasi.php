<?php
include 'config/db.php';
include 'fungsi_nb.php';

 $model = ambilModel($conn);
 $kelas = array_column($conn->query("SELECT id_penyakit FROM penyakit ORDER BY id_penyakit")
                      ->fetch_all(MYSQLI_ASSOC), 'id_penyakit');
 $uji = $conn->query("SELECT id_kasus, id_penyakit FROM data_kasus WHERE is_uji=1")
            ->fetch_all(MYSQLI_ASSOC);

// ---- 1. Confusion matrix C[aktual][prediksi] ----
 $C = [];
foreach ($kelas as $a) foreach ($kelas as $p) $C[$a][$p] = 0;
foreach ($uji as $k) {
    $post = hitungPosterior($model, gejalaAktifKasus($conn, (int)$k['id_kasus']));
    $C[$k['id_penyakit']][ array_key_first($post) ]++;
}

 $total = count($uji);
 $benar = 0; foreach ($kelas as $k) $benar += $C[$k][$k];
echo "<h3>Akurasi: " . round($benar / $total * 100, 2) . "% ($benar/$total)</h3>";

// ---- 2. Precision, Recall, F1 per kelas ----
 $M = [];
foreach ($kelas as $k) {
    $TP = $C[$k][$k];
    $FP = 0; foreach ($kelas as $a) if ($a !== $k) $FP += $C[$a][$k];
    $FN = array_sum($C[$k]) - $TP;
    $P = ($TP+$FP) ? $TP/($TP+$FP) : 0;
    $R = ($TP+$FN) ? $TP/($TP+$FN) : 0;
    $M[$k] = ['P'=>$P, 'R'=>$R, 'F1'=> ($P+$R) ? 2*$P*$R/($P+$R) : 0];
}
 $macro = fn($i) => array_sum(array_column($M, $i)) / count($kelas);

echo "<h4>Metrik per kelas</h4><table border=1 cellpadding=4>
      <tr><th>Penyakit</th><th>Precision</th><th>Recall</th><th>F1</th></tr>";
foreach ($M as $k => $v)
    echo "<tr><td>$k</td><td>" . round($v['P']*100,1) . "%</td><td>"
       . round($v['R']*100,1) . "%</td><td>" . round($v['F1']*100,1) . "%</td></tr>";
echo "<tr><th>Macro avg</th><th>" . round($macro('P')*100,1) . "%</th><th>"
   . round($macro('R')*100,1) . "%</th><th>" . round($macro('F1')*100,1) . "%</th></tr></table>";

// Tabel confusion matrix (baris=aktual, kolom=prediksi) — render serupa jika perlu