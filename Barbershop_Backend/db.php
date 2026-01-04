<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "barbershop";

// Kapcsolat létrehozása
$conn = new mysqli($servername, $username, $password, $dbname);

// Hibaellenőrzés
if ($conn->connect_error) {
    die("Kapcsolódási hiba: " . $conn->connect_error);
}
?>