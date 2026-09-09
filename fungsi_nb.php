<?php // fungsi_nb.php — dipakai bersama konsultasi & evaluasi

function ambilModel(mysqli $conn): array {
    $m = ['gejala'=>[], 'prior'=>[], 'p'=>[]];
    $q = $conn->query("SELECT id_gejala FROM gejala ORDER BY id_gejala");
    while ($r = $q->fetch_assoc()) $m['gejala'][] = $r['id_gejala'];
    $q = $conn->query("SELECT id_penyakit, prior FROM model_prior");
    while ($r = $q->fetch_assoc()) $m['prior'][$r['id_penyakit']] = (float)$r['prior'];
    $q = $conn->query("SELECT id_penyakit, id_gejala, p_ada FROM model_likelihood");
    while ($r = $q->fetch_assoc())
        $m['p'][$r['id_penyakit']][$r['id_gejala']] = (float)$r['p_ada'];
    return $m;
}

/* $aktif = daftar id_gejala bernilai 1.
   Return: [id_penyakit => probabilitas 0..1], terurut menurun */
function hitungPosterior(array $m, array $aktif): array {
    $post = [];
    foreach ($m['prior'] as $idP => $pK) {
        $p = $pK;
        foreach ($m['gejala'] as $idG) {
            $pG = $m['p'][$idP][$idG] ?? 0.5;
            $p *= in_array($idG, $aktif, true) ? $pG : (1 - $pG);
        }
        $post[$idP] = $p;
    }
    $total = array_sum($post) ?: 1;
    foreach ($post as &$v) $v /= $total;
    arsort($post);
    return $post;
}

function gejalaAktifKasus(mysqli $conn, int $idKasus): array {
    $q = $conn->prepare("SELECT id_gejala FROM detail_kasus WHERE id_kasus=? AND nilai=1");
    $q->bind_param("i", $idKasus);
    $q->execute();
    return array_column($q->get_result()->fetch_all(MYSQLI_ASSOC), 'id_gejala');
}