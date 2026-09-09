<?php // konsultasi.php
include 'config/db.php'; ?>
<form method="post" action="proses_diagnosa.php">
<?php
 $q = $conn->query("SELECT * FROM gejala ORDER BY id_gejala");
while ($g = $q->fetch_assoc())
    echo "<label><input type='checkbox' name='gejala[]' value='{$g['id_gejala']}'>
          {$g['nama_gejala']}</label><br>";
?>
<button type="submit">Diagnosa</button>
</form>