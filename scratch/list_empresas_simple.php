<?php
$dsn = 'pgsql:host=127.0.0.1;port=5432;dbname=nexusecom';
$user = 'producao';
$pass = 'nFxRuWN3P5IHWRn';

try {
    $pdo = new PDO($dsn, $user, $pass);
    $stmt = $pdo->query('SELECT id, nome, cnpj FROM empresas');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Nome: {$row['nome']} | CNPJ: {$row['cnpj']}\n";
    }
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
