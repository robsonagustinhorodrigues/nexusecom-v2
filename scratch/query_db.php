<?php
$env = parse_ini_file(__DIR__ . '/../.env');
$pdo = new PDO("pgsql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}", $env['DB_USERNAME'], $env['DB_PASSWORD']);

$stmt = $pdo->query("SELECT id, tipo_fiscal, chave, data_emissao FROM nfe_emitidas WHERE data_emissao >= '2026-04-01' AND data_emissao <= '2026-04-30' LIMIT 10");
echo "NFE EMITIDAS:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['id']} - {$row['tipo_fiscal']} - {$row['data_emissao']} - {$row['chave']}\n";
}

$stmt2 = $pdo->query("SELECT id, tipo_fiscal, chave, data_emissao FROM nfe_recebidas WHERE data_emissao >= '2026-04-01' AND data_emissao <= '2026-04-30' LIMIT 10");
echo "NFE RECEBIDAS:\n";
while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['id']} - {$row['tipo_fiscal']} - {$row['data_emissao']} - {$row['chave']}\n";
}
