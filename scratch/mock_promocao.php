<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$anuncio = \App\Models\MarketplaceAnuncio::where('marketplace', 'mercadolivre')->where('status', 'active')->first();
if ($anuncio) {
    $anuncio->preco = 100;
    $anuncio->promocao_valor = 80;
    $anuncio->promocao_desconto = 20;
    $anuncio->promocao_id = 'P-12345';
    $anuncio->promocao_tipo = 'DEAL_OF_THE_DAY';
    $anuncio->save();
    echo "Anúncio {$anuncio->id} atualizado com promoção mockada.\n";
} else {
    echo "Nenhum anuncio ativo encontrado.\n";
}
