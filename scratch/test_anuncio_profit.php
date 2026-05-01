<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app()->make(\App\Http\Controllers\Api\AnuncioController::class);

$anuncio = \App\Models\MarketplaceAnuncio::where('marketplace', 'mercadolivre')->first();
if ($anuncio) {
    $anuncio->preco = 100;
    $anuncio->promocao_valor = 80;
    $anuncio->promocao_desconto = 20;
    
    // Simulate formatting
    $method = new ReflectionMethod($controller, 'formatAnuncio');
    $method->setAccessible(true);
    $result = $method->invoke($controller, $anuncio);
    
    echo json_encode([
        'preco' => $result['preco'],
        'promocao_valor' => $result['promocao_valor'],
        'lucro' => $result['lucro'],
        'margem' => $result['margem'],
        'lucro_promocao' => $result['lucro_promocao'],
        'margem_promocao' => $result['margem_promocao'],
    ], JSON_PRETTY_PRINT);
} else {
    echo "Nenhum anuncio encontrado.\n";
}
