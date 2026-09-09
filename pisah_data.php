<?php
include 'config/db.php';
 $conn->query("UPDATE data_kasus SET is_uji = 0");

 $ps = $conn->query("SELECT DISTINCT id_penyakit FROM data_kasus");
while ($row = $ps->fetch_assoc()) {
    $idP = $row['id_penyakit'];
    $q = $conn->prepare("SELECT id_kasus FROM data_kasus WHERE id_penyakit=? ORDER BY RAND()");
    $q->bind_param("s", $idP); $q->execute();
    $ids = array_column($q->get_result()->fetch_all(MYSQLI_ASSOC), 'id_kasus');

    $nUji = (int) floor(count($ids) * 0.2);          // 20% jadi data uji
    foreach (array_slice($ids, 0, $nUji) as $id) {
        $u = $conn->prepare("UPDATE data_kasus SET is_uji=1 WHERE id_kasus=?");
        $u->bind_param("i", $id); $u->execute();
    }
}
 $s = $conn->query("SELECT is_uji, COUNT(*) n FROM data_kasus GROUP BY is_uji");
while ($r = $s->fetch_assoc())
    echo ($r['is_uji'] ? "Data uji" : "Data latih") . ": {$r['n']} kasus<br>";