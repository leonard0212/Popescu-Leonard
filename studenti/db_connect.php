<?php
// db_connect.php
$servername = "localhost";
$username   = "root";
$password   = ""; // Implicit în XAMPP este gol
$dbname     = "service_flow_db";

// Creare conexiune
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificare conexiune
if ($conn->connect_error) {
    die("Conexiunea a eșuat: " . $conn->connect_error);
}

// Setare charset pentru diacritice
$conn->set_charset("utf8mb4");
?>