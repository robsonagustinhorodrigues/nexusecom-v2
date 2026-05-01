<?php
$dsn = 'sqlite:' . __DIR__ . '/../database/database.sqlite';

try {
    $pdo = new PDO($dsn);
    echo "Conectado ao SQLite!\n";
    $stmt = $pdo->query('SELECT id, nome, cnpj FROM empresas');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Nome: {$row['nome']} | CNPJ: {$row['cnpj']}\n";
    }
} catch (PDOException $e) {
    echo 'Falha: ' . $e->getMessage() . "\n";
}
