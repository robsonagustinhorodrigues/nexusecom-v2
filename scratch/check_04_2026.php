<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$emitidas = \DB::table('nfe_emitidas')->whereBetween('data_emissao', ['2026-04-01', '2026-04-30'])->get();
$recebidas = \DB::table('nfe_recebidas')->whereBetween('data_emissao', ['2026-04-01', '2026-04-30'])->get();

$sem_itens_emitidas = 0;
$sem_cfop_emitidas = 0;
foreach ($emitidas as $nfe) {
    $items = \DB::table('nfe_items')->where('nfe_emitida_id', $nfe->id)->get();
    if ($items->isEmpty()) $sem_itens_emitidas++;
    foreach ($items as $item) {
        if (empty($item->cfop)) $sem_cfop_emitidas++;
    }
}

$sem_itens_recebidas = 0;
$sem_cfop_recebidas = 0;
foreach ($recebidas as $nfe) {
    $items = \DB::table('nfe_items')->where('nfe_recebida_id', $nfe->id)->get();
    if ($items->isEmpty()) $sem_itens_recebidas++;
    foreach ($items as $item) {
        if (empty($item->cfop)) $sem_cfop_recebidas++;
    }
}

file_put_contents(__DIR__.'/result_04_2026.txt', "Emitidas: " . $emitidas->count() . " | Sem itens: $sem_itens_emitidas | Itens sem CFOP: $sem_cfop_emitidas\n" . "Recebidas: " . $recebidas->count() . " | Sem itens: $sem_itens_recebidas | Itens sem CFOP: $sem_cfop_recebidas\n");
