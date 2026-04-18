<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use App\Services\FiscalService;
use Illuminate\Support\Facades\Storage;

$service = new FiscalService();

$emitidas = NfeEmitida::whereBetween('data_emissao', ['2026-04-01 00:00:00', '2026-04-30 23:59:59'])->get();
$countEmitidas = 0;
foreach ($emitidas as $nota) {
    if ($nota->xml_path && Storage::exists($nota->xml_path)) {
        $content = Storage::get($nota->xml_path);
        try {
            $service->importXml($content, $nota->empresa_id, basename($nota->xml_path));
            $countEmitidas++;
        } catch (\Exception $e) {
            echo "Erro emitidas {$nota->chave}: " . $e->getMessage() . "\n";
        }
    }
}

$recebidas = NfeRecebida::whereBetween('data_emissao', ['2026-04-01 00:00:00', '2026-04-30 23:59:59'])->get();
$countRecebidas = 0;
foreach ($recebidas as $nota) {
    if ($nota->xml_path && Storage::exists($nota->xml_path)) {
        $content = Storage::get($nota->xml_path);
        try {
            $service->importXml($content, $nota->empresa_id, basename($nota->xml_path));
            $countRecebidas++;
        } catch (\Exception $e) {
            echo "Erro recebidas {$nota->chave}: " . $e->getMessage() . "\n";
        }
    }
}

file_put_contents(__DIR__.'/fix_result.txt', "Re-importadas com sucesso: Emitidas: $countEmitidas, Recebidas: $countRecebidas\n");
echo "OK\n";
