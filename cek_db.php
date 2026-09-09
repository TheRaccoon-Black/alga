<?php
include 'config/db.php';
echo "index.php ini terhubung ke database: <b>"
   . $conn->query("SELECT DATABASE() d")->fetch_assoc()['d'] . "</b><br><br>";
foreach (['penyakit','gejala','data_kasus','model_prior','model_likelihood','konsultasi'] as $t) {
    echo "$t : " . $conn->query("SELECT COUNT(*) c FROM $t")->fetch_assoc()['c'] . "<br>";
}