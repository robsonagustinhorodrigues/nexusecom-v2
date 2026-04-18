<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;

$empresas = Empresa::all()->keyBy('id');

$emitidasErradas = [];
$totalEmitidas = 0;

NfeEmitida::chunk(2000, function($notas) use (&$emitidasErradas, &$totalEmitidas, $empresas) {
    foreach($notas as $nota) {
        $totalEmitidas++;
        $empresa = $empresas->get($nota->empresa_id);
        if (!$empresa) continue;

        $cnpjEmpresa = ltrim(preg_replace('/[^0-9]/', '', $empresa->cnpj), '0');
        $rawChave = preg_replace('/[^0-9]/', '', $nota->chave);
        $cnpjFromChave = strlen($rawChave) === 44 ? substr($rawChave, 6, 14) : '';
        $cnpjEmitente = ltrim(preg_replace('/[^0-9]/', '', $nota->emitente_cnpj ?: $cnpjFromChave), '0');

        if (!empty($cnpjEmitente) && !empty($cnpjEmpresa) && $cnpjEmitente !== $cnpjEmpresa) {
            $emitidasErradas[] = [
                'id' => $nota->id,
                'chave' => $nota->chave,
                'emitente_cnpj' => $cnpjEmitente,
                'empresa_cnpj' => $cnpjEmpresa,
                'empresa_id' => $nota->empresa_id
            ];
        }
    }
});

echo "Total NfeEmitida processadas: {$totalEmitidas}\n";
echo "NfeEmitida classificadas INCORRETAMENTE (Ocultas como recebidas): " . count($emitidasErradas) . "\n";
if (count($emitidasErradas) > 0) {
    echo "Exemplos (ate 5):\n";
    print_r(array_slice($emitidasErradas, 0, 5));
}

$recebidasErradas = [];
$totalRecebidas = 0;

NfeRecebida::chunk(2000, function($notas) use (&$recebidasErradas, &$totalRecebidas, $empresas) {
    foreach($notas as $nota) {
        $totalRecebidas++;
        $empresa = $empresas->get($nota->empresa_id);
        if (!$empresa) continue;

        $cnpjEmpresa = ltrim(preg_replace('/[^0-9]/', '', $empresa->cnpj), '0');
        $rawChave = preg_replace('/[^0-9]/', '', $nota->chave);
        $cnpjFromChave = strlen($rawChave) === 44 ? substr($rawChave, 6, 14) : '';
        $cnpjEmitente = ltrim(preg_replace('/[^0-9]/', '', $nota->emitente_cnpj ?: $cnpjFromChave), '0');

        if (!empty($cnpjEmitente) && !empty($cnpjEmpresa) && $cnpjEmitente === $cnpjEmpresa) {
            $recebidasErradas[] = [
                'id' => $nota->id,
                'chave' => $nota->chave,
                'emitente_cnpj' => $cnpjEmitente,
                'empresa_cnpj' => $cnpjEmpresa,
                'empresa_id' => $nota->empresa_id
            ];
        }
    }
});

echo "\nTotal NfeRecebida processadas: {$totalRecebidas}\n";
echo "NfeRecebida classificadas INCORRETAMENTE (Que deveriam ser Emitidas): " . count($recebidasErradas) . "\n";
if (count($recebidasErradas) > 0) {
    echo "Exemplos (ate 5):\n";
    print_r(array_slice($recebidasErradas, 0, 5));
}

echo "\nConcluido.\n";
