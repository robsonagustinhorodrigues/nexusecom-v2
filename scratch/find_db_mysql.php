<?php
$hosts = ['127.0.0.1', 'localhost', '172.17.0.1'];
$ports = ['3306'];
$user = 'producao';
$pass = 'nFxRuWN3P5IHWRn';
$dbname = 'nexusecom';

foreach ($hosts as $host) {
    foreach ($ports as $port) {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname}";
        echo "Tentando {$dsn}...\n";
        try {
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 2]);
            echo "✅ CONECTADO! Host: {$host}, Port: {$port}\n";
            exit(0);
        } catch (Exception $e) {
            echo "❌ Falha: " . substr($e->getMessage(), 0, 50) . "...\n";
        }
    }
}
echo "Fim da tentativa.\n";
