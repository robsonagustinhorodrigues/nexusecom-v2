<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Empresa;
use App\Services\SefazEngine;
use Illuminate\Support\Facades\Log;

$companyIds = [4, 5, 6];
$empresas = Empresa::whereIn('id', $companyIds)->get();

if ($empresas->isEmpty()) {
    echo "Nenhuma das empresas encontradas com IDs: " . implode(', ', $companyIds) . "\n";
    exit(1);
}

$sefaz = app(SefazEngine::class);

foreach ($empresas as $empresa) {
    echo "Processando {$empresa->nome} (ID: {$empresa->id}, NSU Atual: {$empresa->last_nsu})...\n";
    try {
        $result = $sefaz->buscarNovasNotas($empresa);
        echo "✅ Sucesso para {$empresa->nome}: {$result['count']} documentos processados em {$result['batchCount']} lotes. Novo NSU: {$result['lastNsu']}\n";
    } catch (\Exception $e) {
        echo "❌ Erro para {$empresa->nome}: " . $e->getMessage() . "\n";
    }
}
