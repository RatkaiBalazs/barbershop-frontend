<?php
// ==========================================
// 1. BEÁLLÍTÁSOK ÉS KAPCSOLÓDÁS
// ==========================================

// Engedélyezzük, hogy a React (másik portról) elérje ezt a fájlt
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Ha csak előzetes ellenőrzés (OPTIONS kérés) jön, azonnal válaszolunk OK-t
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Adatbázis adatok (XAMPP alapértelmezett)
$host = 'localhost';
$db   = 'barbershop';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Adatbázis hiba: ' . $e->getMessage()]);
    exit();
}

// ==========================================
// 2. ADATOK FOGADÁSA REACTBŐL
// ==========================================

// A React JSON-t küld, ezt olvassuk ki
$input = json_decode(file_get_contents("php://input"), true);

// Ellenőrizzük, hogy minden kötelező adat megjött-e
if (!isset($input['service_id']) || !isset($input['date']) || !isset($input['time']) || !isset($input['employee_id']) || !isset($input['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Hiányzó adatok a foglaláshoz!']);
    exit();
}

// Változókba mentjük a bejövő adatokat
$service_id = $input['service_id'];
$employee_id = $input['employee_id'];
$user_id = $input['user_id'];
$date_str = $input['date'];
$time_str = $input['time'];

// ==========================================
// 3. LOGIKA ÉS ELLENŐRZÉSEK
// ==========================================

// A) Megnézzük, mennyi ideig tart a választott szolgáltatás
// Tábla: Szolgaltatas, Oszlop: Szolgaltatas_Ido
$stmt_duration = $pdo->prepare("SELECT Szolgaltatas_Ido FROM Szolgaltatas WHERE Szolgaltatas_ID = ?");
$stmt_duration->execute([$service_id]);
$service_data = $stmt_duration->fetch();

if (!$service_data) {
    echo json_encode(['success' => false, 'message' => 'A kiválasztott szolgáltatás nem található.']);
    exit();
}

$duration_minutes = $service_data['Szolgaltatas_Ido'];

// B) Kiszámoljuk a kezdést és a befejezést
try {
    $start_datetime = new DateTime($date_str . ' ' . $time_str);
    $end_datetime = clone $start_datetime;
    $end_datetime->modify('+' . $duration_minutes . ' minutes');

    $start_timestamp = $start_datetime->format('Y-m-d H:i:s');
    $end_timestamp = $end_datetime->format('Y-m-d H:i:s');
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hibás dátum formátum.']);
    exit();
}

// C) Megnézzük, ráér-e a fodrász (Ütközésvizsgálat)
// Tábla: Idopont
$sql_check = "
    SELECT Idopont_ID 
    FROM Idopont 
    WHERE Fodrasz_ID = ?
      AND (
            (Kezdes < ? AND Befejezes > ?) -- Ha az idősávok metszik egymást
          )
    LIMIT 1;
";

$stmt_check = $pdo->prepare($sql_check);
$stmt_check->execute([$employee_id, $end_timestamp, $start_timestamp]);
$existing_appointment = $stmt_check->fetch();

if ($existing_appointment) {
    echo json_encode(['success' => false, 'message' => 'Ez az időpont sajnos már foglalt!']);
    exit();
}

// ==========================================
// 4. MENTÉS AZ ADATBÁZISBA (A LÉNYEG)
// ==========================================

try {
    // Tranzakció indítása (innentől vagy minden sikerül, vagy semmi)
    $pdo->beginTransaction();

    // 1. LÉPÉS: Beszúrás az 'Idopont' táblába
    // A Statusz mezőbe 'F'-et írunk (mint Foglalt)
    $sql_insert = "
        INSERT INTO Idopont (Ugyfel_ID, Fodrasz_ID, Kezdes, Befejezes, Statusz)
        VALUES (?, ?, ?, ?, 'F')
    ";
    
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([$user_id, $employee_id, $start_timestamp, $end_timestamp]);
    
    // Lekérjük a most létrejött foglalás ID-ját!
    $new_appointment_id = $pdo->lastInsertId();

    // 2. LÉPÉS: Beszúrás a 'KapcsoloTabla'-ba (ITT A TRÜKK!)
    // Összekötjük az új időpontot a szolgáltatással
    $sql_link = "INSERT INTO KapcsoloTabla (Idopont_ID, Szolgaltatas_ID) VALUES (?, ?)";
    $stmt_link = $pdo->prepare($sql_link);
    $stmt_link->execute([$new_appointment_id, $service_id]);

    // Ha idáig eljutottunk hiba nélkül, véglegesítjük a mentést
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Sikeres foglalás!']);

} catch (Exception $e) {
    // Ha bármi hiba volt, visszacsináljuk az egészet (nem jön létre félkész foglalás)
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Hiba történt a mentéskor: ' . $e->getMessage()]);
}
?>