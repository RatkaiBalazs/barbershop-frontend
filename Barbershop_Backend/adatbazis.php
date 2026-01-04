<?php

// SQL Server adatbázis kapcsolódási paraméterek
$server_name = "LAPTOP-DQPHQUE2\\SQLEXPRESS";
$database_name = "projekt";
$user_name = "LAPTOP-DQPHQUE2\\vatka";
$password = "";

// Kapcsolódási szöveg (connection string)
$connection_string = "sqlsrv:Server=$server_name;Database=$database_name";

try {
    // PDO kapcsolat létrehozása SQL Serverhez
    $db = new PDO($connection_string, $user_name, $password);
    
    // Hibakezelés beállítása
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Sikeres adatbázis kapcsolat!";
} catch (PDOException $e) {
    echo "Kapcsolódási hiba: " . $e->getMessage();
    die();
}
?>