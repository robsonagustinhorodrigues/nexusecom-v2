<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$items = \App\Models\NfeItem::where('cfop', 'like', '%152%')->limit(10)->get();
foreach ($items as $item) {
    echo "CFOP: {$item->cfop}, Valor: {$item->valor_total}\n";
    if ($item->nfe_emitida_id) {
        $nfe = \App\Models\NfeEmitida::find($item->nfe_emitida_id);
        echo "Emitida: {$nfe->numero}, Devolvida: {$nfe->devolvida}, Tipo: {$nfe->tipo_fiscal}, FinNFe: {$nfe->tp_nf}\n";
    }
    if ($item->nfe_recebida_id) {
        $nfe = \App\Models\NfeRecebida::find($item->nfe_recebida_id);
        echo "Recebida: {$nfe->numero}, Devolucao: {$nfe->devolucao}, Tipo: {$nfe->tipo_fiscal}, FinNFe: {$nfe->tp_nf}\n";
    }
}
