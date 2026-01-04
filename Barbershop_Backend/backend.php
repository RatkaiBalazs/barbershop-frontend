<?php


// 1. Adatbázis paraméterek
$serverName = 'LAPTOP-DTKB74P5\SQLEXPRESS'; // A SQL Server címe (pl. 'localhost', 'SERVER\SQLEXPRESS', vagy IP-cím)
$dbName     = 'fodraszat_db'; 
$user       = 'db_user';   
$pass       = 'titkos_jelszo'; 

// A DSN (Data Source Name) string MSSQL-hez
// A PDO_SQLSRV illesztőprogram használata:
$dsn = "sqlsrv:Server=$serverName;Database=$dbName"; 

// Kapcsolódási opciók
$options = [
    // Az alábbi opciók segítenek a hibakezelésben és az eredmények kezelésében
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     
];

try {
     // PDO objektum létrehozása: a kapcsolat felépítése
     $pdo = new PDO($dsn, $user, $pass, $options);
     
     // Fontos a UTF-8 karakterkódolás beállítása
     $pdo->exec("SET NAMES 'UTF8'"); 
     
} catch (\PDOException $e) {
     // Kapcsolódási hiba kezelése
     // \PDOException::getCode() 
     // $e->getMessage()
     die("Hiba történt a kapcsolódáskor: " . $e->getMessage());
}

// A $pdo objektum készen áll a használatra!


// A $pdo objektum használatra kész!


// Tegyük fel, hogy ez a $pdo az adatbázis kapcsolatunk (PDO objektum)

// 1. Szolgáltatás időtartamának lekérése
$service_id = $_POST['service_id']; 

$stmt_duration = $pdo->prepare("SELECT duration_minutes FROM services WHERE id = ?");
$stmt_duration->execute([$service_id]);
$service_data = $stmt_duration->fetch(PDO::FETCH_ASSOC);

if (!$service_data) {
    // Kezeljük a hibát, ha nem találjuk a szolgáltatást
    die(json_encode(['success' => false, 'message' => 'Szolgáltatás nem található.']));
}

$duration_minutes = $service_data['duration_minutes'];

// Időpontok kiszámítása
$start_datetime = new DateTime($_POST['date'] . ' ' . $_POST['time']);
$end_datetime = clone $start_datetime;
$end_datetime->modify('+' . $duration_minutes . ' minutes');

$start_timestamp = $start_datetime->format('Y-m-d H:i:s');
$end_timestamp = $end_datetime->format('Y-m-d H:i:s');

$employee_id = $_POST['employee_id'];

$sql_check = "
    SELECT id 
    FROM appointments 
    WHERE employee_id = ?
      AND date(start_timestamp) = date(?) -- Csak az adott napot nézzük
      AND (
            (end_timestamp > ? AND start_timestamp < ?) -- Az átfedési logika
          )
    LIMIT 1;
";

$stmt_check = $pdo->prepare($sql_check);
$stmt_check->execute([$employee_id, $_POST['date'], $start_timestamp, $end_timestamp]);
$existing_appointment = $stmt_check->fetch(PDO::FETCH_ASSOC);

if ($existing_appointment) {
    // Ütközés van, nem lehet foglalni!
    die(json_encode(['success' => false, 'message' => 'Ez az időpont már foglalt!']));
}

// Tegyük fel, hogy az 'user_id' a bejelentkezett felhasználó azonosítója
$user_id = $_SESSION['user_id'] ?? null; 

if (!$user_id) {
    die(json_encode(['success' => false, 'message' => 'Kérem, jelentkezzen be a foglaláshoz.']));
}

$sql_insert = "
    INSERT INTO appointments (user_id, employee_id, service_id, start_timestamp, end_timestamp, status)
    VALUES (?, ?, ?, ?, ?, 'reserved')
";

$stmt_insert = $pdo->prepare($sql_insert);
$success = $stmt_insert->execute([$user_id, $employee_id, $service_id, $start_timestamp, $end_timestamp]);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Sikeres foglalás!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Hiba történt a foglalás rögzítésekor.']);
}

?>
