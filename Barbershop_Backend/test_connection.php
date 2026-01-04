<?php

require_once 'adatbazis.php';

// Egyszerű lekérdezés a kapcsolat teszteléséhez
try {
    $query = $db->query("SELECT 1 AS test");
    $result = $query->fetch();
    
    if ($result) {
        echo "<h2 style='color: green;'>✓ Adatbázis kapcsolat SIKERES!</h2>";
        echo "<p>A kapcsolat működik és képes adatokat lekérdezni.</p>";
    }
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>✗ Adatbázis kapcsolat HIBÁS!</h2>";
    echo "<p><strong>Hiba:</strong> " . $e->getMessage() . "</p>";
}

?>
