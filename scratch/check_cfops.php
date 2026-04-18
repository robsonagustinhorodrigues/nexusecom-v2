<?php
$dsn = "pgsql:host=177.170.10.220;port=8281;dbname=nexusecom";
$user = "producao";
$pass = "nFxRuWN3P5IHWRn";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $query = "SELECT i.cfop, count(*) as total, SUM(i.valor_total) as valor 
              FROM nfe_items i 
              JOIN nfe_emitidas e ON i.nfe_emitida_id = e.id 
              WHERE e.data_emissao >= '2026-04-01' AND e.data_emissao <= '2026-04-30' 
              GROUP BY i.cfop 
              ORDER BY total DESC";
    $stmt = $pdo->query($query);
    echo "CFOPs in Emitidas (April 2026):\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "CFOP: {$row['cfop']} | Count: {$row['total']} | Valor: {$row['valor']}\n";
    }

    $query2 = "SELECT i.cfop, count(*) as total, SUM(i.valor_total) as valor 
               FROM nfe_items i 
               JOIN nfe_recebidas r ON i.nfe_recebida_id = r.id 
               WHERE r.data_emissao >= '2026-04-01' AND r.data_emissao <= '2026-04-30' 
               GROUP BY i.cfop 
               ORDER BY total DESC";
    $stmt2 = $pdo->query($query2);
    echo "\nCFOPs in Recebidas (April 2026):\n";
    while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        echo "CFOP: {$row['cfop']} | Count: {$row['total']} | Valor: {$row['valor']}\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
