<?php
echo "NFE EMITIDAS:\n";
$emitidas = \DB::table('nfe_emitidas')->whereBetween('data_emissao', ['2026-04-01', '2026-04-30'])->get();
foreach ($emitidas as $e) {
    echo "{$e->id} - {$e->chave} - {$e->tipo_fiscal} - {$e->status} - {$e->data_emissao}\n";
}

echo "NFE RECEBIDAS:\n";
$recebidas = \DB::table('nfe_recebidas')->whereBetween('data_emissao', ['2026-04-01', '2026-04-30'])->get();
foreach ($recebidas as $e) {
    echo "{$e->id} - {$e->chave} - {$e->tipo_fiscal} - {$e->status_nfe} - {$e->data_emissao}\n";
}
