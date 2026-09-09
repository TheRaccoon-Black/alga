<?php // config/db.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
 $conn = new mysqli("localhost", "root", "", "db_sispak"); // default Laragon
 $conn->set_charset("utf8mb4");