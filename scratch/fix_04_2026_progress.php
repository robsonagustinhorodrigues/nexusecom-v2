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
$logFile = __DIR__.'/fix_progress.txt';

file_put_contents($logFile, "Iniciando...\n");

$emitidas = NfeEmitida::whereBetween('data_emissao', ['2026-04-01 00:00:00', '2026-04-30 23:59:59'])->get();
$countEmitidas = 0;
file_put_contents($logFile, "Total Emitidas para verificar: " . $emitidas->count() . "\n", FILE_APPEND);

foreach ($emitidas as $nota) {
    if ($nota->xml_path && Storage::exists($nota->xml_path)) {
        $content = Storage::get($nota->xml_path);
        try {
            $service->importXml($content, $nota->empresa_id, basename($nota->xml_path));
            $countEmitidas++;
            if ($countEmitidas % 10 == 0) {
                file_put_contents($logFile, "Emitidas processadas: $countEmitidas\n", FILE_APPEND);
            }
        } catch (\Exception $e) {
            file_put_contents($logFile, "Erro emitidas {$nota->chave}: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

$recebidas = NfeRecebida::whereBetween('data_emissao', ['2026-04-01 00:00:00', '2026-04-30 23:59:59'])->get();
$countRecebidas = 0;
file_put_contents($logFile, "Total Recebidas para verificar: " . $recebidas->count() . "\n", FILE_APPEND);

foreach ($recebidas as $nota) {
    if ($nota->xml_path && Storage::exists($nota->xml_path)) {
        $content = Storage::get($nota->xml_path);
        try {
            $service->importXml($content, $nota->empresa_id, basename($nota->xml_path));
            $countRecebidas++;
            if ($countRecebidas % 10 == 0) {
                file_put_contents($logFile, "Recebidas processadas: $countRecebidas\n", FILE_APPEND);
            }
        } catch (\Exception $e) {
            file_put_contents($logFile, "Erro recebidas {$nota->chave}: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

file_put_contents($logFile, "Concluído. Emitidas: $countEmitidas, Recebidas: $countRecebidas\n", FILE_APPEND);
echo "OK\n";
