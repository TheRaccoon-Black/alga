<?php
include 'config/db.php';
 $conn->query("TRUNCATE model_prior");
 $conn->query("TRUNCATE model_likelihood");

// ---- 1. PRIOR: P(K) = X / A ----
 $totalLatih = (int) $conn->query("SELECT COUNT(*) t FROM data_kasus WHERE is_uji=0")
                  ->fetch_assoc()['t'];
 $res = $conn->query("SELECT id_penyakit, COUNT(*) x FROM data_kasus
                     WHERE is_uji=0 GROUP BY id_penyakit");
 $insP = $conn->prepare("INSERT INTO model_prior VALUES (?,?,?)");
while ($r = $res->fetch_assoc()) {
    $x = (int)$r['x'];
    $prior = $x / $totalLatih;                       // P(K) = X/A  (rumus Bab 2.2)
    $insP->bind_param("sid", $r['id_penyakit'], $x, $prior);
    $insP->execute();
}

// ---- 2. LIKELIHOOD: P(G|K) = (F+1)/(X+2) — Laplace smoothing ----
 $res = $conn->query("SELECT k.id_penyakit, d.id_gejala, SUM(d.nilai) f, COUNT(*) x
                     FROM detail_kasus d
                     JOIN data_kasus k ON k.id_kasus = d.id_kasus
                     WHERE k.is_uji = 0
                     GROUP BY k.id_penyakit, d.id_gejala");
 $insL = $conn->prepare("INSERT INTO model_likelihood VALUES (?,?,?,?,?)");
while ($r = $res->fetch_assoc()) {
    $f = (int)$r['f']; $x = (int)$r['x'];
    $pAda = ($f + 1) / ($x + 2);                     // anti-likelihood-nol
    $insL->bind_param("ssiid", $r['id_penyakit'], $r['id_gejala'], $f, $x, $pAda);
    $insL->execute();
}
echo "Pelatihan selesai. Model tersimpan.";