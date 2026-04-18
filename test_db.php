<?php
$host = '177.170.10.220';
$port = 8281;
$dbname = 'nexusecom';
$user = 'producao';
$password = 'nFxRuWN3P5IHWRn';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Connected successfully to the database!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
