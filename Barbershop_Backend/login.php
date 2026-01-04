<?php
// 1. Engedélyezzük, hogy a React elérje ezt a fájlt (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// 2. Adatbázis kapcsolat betöltése
require 'db.php';

// 3. Adatok fogadása a Reactból (email és jelszó)
$data = json_decode(file_get_contents("php://input"));

// Ellenőrzés: Megjött-e minden adat?
if(!isset($data->email) || !isset($data->password)) {
    echo json_encode(["success" => false, "message" => "Hiányzó adatok!"]);
    exit();
}

// Biztonsági tisztítás
$email = $conn->real_escape_string($data->email);
$password = $conn->real_escape_string($data->password);

// 4. Megkeressük a felhasználót az email címe alapján
$sql = "SELECT * FROM Ugyfel WHERE Ugyfel_Email = '$email'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Van ilyen email cím, nézzük meg az adatait
    $user = $result->fetch_assoc();
    
    // 5. Jelszó ellenőrzése
    // Mivel a regisztrációnál simán mentettük el, itt is simán hasonlítjuk össze.
    // (Az adatbázisodban az 'Ugyfel_jelszo1' mezőben van a jelszó)
    if ($password === $user['Ugyfel_jelszo1']) {
        
        // SIKER! Visszaküldjük, hogy oké, és a nevet/ID-t (jelszót SOHA nem küldünk vissza)
        echo json_encode([
            "success" => true, 
            "message" => "Sikeres bejelentkezés!",
            "user" => [
                "id" => $user['Ugyfel_ID'],
                "name" => $user['Ugyfel_Nev'],
                "email" => $user['Ugyfel_Email']
            ]
        ]);
    } else {
        // Rossz jelszó
        echo json_encode(["success" => false, "message" => "Hibás jelszó!"]);
    }
} else {
    // Nincs ilyen email cím a rendszerben
    echo json_encode(["success" => false, "message" => "Nincs ilyen felhasználó ezzel az emaillel!"]);
}

$conn->close();
?>