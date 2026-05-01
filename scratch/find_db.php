<?php
$hosts = ['127.0.0.1', 'localhost', '172.17.0.1', '192.168.10.124', '192.168.10.119'];
$ports = ['5432', '5433'];
$user = 'producao';
$pass = 'nFxRuWN3P5IHWRn';
$dbname = 'nexusecom';

foreach ($hosts as $host) {
    foreach ($ports as $port) {
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        echo "Tentando {$dsn}...\n";
        try {
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 2]);
            echo "✅ CONECTADO! Host: {$host}, Port: {$port}\n";
            $stmt = $pdo->query("SELECT id, nome FROM empresas");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "ID: {$row['id']} | Nome: {$row['nome']}\n";
            }
            exit(0);
        } catch (Exception $e) {
            echo "❌ Falha: " . substr($e->getMessage(), 0, 50) . "...\n";
        }
    }
}
echo "Fim da tentativa.\n";
