<?php
$host = 'sql113.infinityfree.com'; // Baddel b-Host mte3ak
$dbname = 'if0_42847852_ugtehec';   // Baddel b-Database Name mte3ak
$username = 'if0_42847852';        // Baddel b-Username mte3ak
$password = 'ziedugte';       // Baddel b-Password mte3ak

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
