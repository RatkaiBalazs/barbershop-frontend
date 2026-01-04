<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// ITT használjuk fel a közös kapcsolatot, amit az előbb csináltál:
require 'db.php';

$data = json_decode(file_get_contents("php://input"));

if(!isset($data->nev) || !isset($data->email) || !isset($data->telszam) || !isset($data->jelszo)) {
    echo json_encode(["success" => false, "message" => "Hiányzó adatok!"]);
    exit();
}

$nev = $conn->real_escape_string($data->nev);
$email = $conn->real_escape_string($data->email);
$telszam = $conn->real_escape_string($data->telszam);
$jelszo = $conn->real_escape_string($data->jelszo);

// Ellenőrizzük, van-e már ilyen email
$check = $conn->query("SELECT * FROM Ugyfel WHERE Ugyfel_Email = '$email'");
if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Ez az email már foglalt!"]);
    exit();
}

// Mentés
$sql = "INSERT INTO Ugyfel (Ugyfel_Nev, Ugyfel_Email, Ugyfel_Telszam, Ugyfel_jelszo1, Ugyfel_jelszo2) 
        VALUES ('$nev', '$email', '$telszam', '$jelszo', '$jelszo')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "message" => "Sikeres regisztráció!"]);
} else {
    echo json_encode(["success" => false, "message" => "Hiba: " . $conn->error]);
}

$conn->close();
?>