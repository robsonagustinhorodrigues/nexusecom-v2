<?php
$sockets = [
    '/var/run/postgresql/.s.PGSQL.5432',
    '/tmp/.s.PGSQL.5432'
];

$user = 'producao';
$pass = 'nFxRuWN3P5IHWRn';
$dbname = 'nexusecom';

foreach ($sockets as $socket) {
    echo "Tentando socket: {$socket}...\n";
    $dsn = "pgsql:host=".dirname($socket).";dbname={$dbname}";
    try {
        $pdo = new PDO($dsn, $user, $pass);
        echo "Conectado via socket!\n";
        $stmt = $pdo->query('SELECT id, nome, cnpj FROM empresas');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "ID: {$row['id']} | Nome: {$row['nome']} | CNPJ: {$row['cnpj']}\n";
        }
        exit(0);
    } catch (PDOException $e) {
        echo 'Falha: ' . $e->getMessage() . "\n";
    }
}
