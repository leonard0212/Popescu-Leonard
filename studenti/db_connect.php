<?php
// db_connect.php

// 1. Configurația fixă pentru Docker
// În Docker, numele serverului este numele serviciului din docker-compose.yml ("mysql")
// NU folosi "localhost" aici, deoarece localhost se referă la containerul de Apache, nu la cel de MySQL.
$servername = "mysql";
$username   = "user";       // Userul definit în docker-compose.yml
$password   = "password";   // Parola definită în docker-compose.yml
$dbname     = "service_flow_db";

// 2. Activăm raportarea erorilor (foarte util pentru debugging)
// Asta transformă erorile MySQL în excepții PHP pe care le putem prinde
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 3. Crearea conexiunii
    $conn = new mysqli($servername, $username, $password, $dbname);

    // 4. Setăm charset-ul la UTF-8 (esențial pentru diacritice românești)
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    // 5. Dacă ceva nu merge, afișăm un mesaj clar și oprim execuția
    // În producție nu ai afișa $e->getMessage() utilizatorilor, dar pentru proiect e perfect.
    die("<h3>Eroare critică de conectare la baza de date!</h3><br>" . 
        "Mesaj eroare: " . $e->getMessage() . "<br><br>" .
        "Verifică dacă containerul MySQL rulează (docker ps) și dacă datele din docker-compose.yml coincid.");
}
?>