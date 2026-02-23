<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$empresaId = 4;
$service = new \App\Services\MeliIntegrationService($empresaId);

$anuncio = \App\Models\MarketplaceAnuncio::find(347);

echo "Anúncio ID: " . $anuncio->id . "\n";
echo "Título: " . $anuncio->titulo . "\n";
echo "json_data: " . print_r($anuncio->json_data, true) . "\n";

$catalogId = $anuncio->json_data['catalog_product_id'] ?? null;
echo "Catalog Product ID: " . ($catalogId ?? 'NÃO ENCONTRADO') . "\n";

$token = $service->getAccessToken();
echo "Token: " . ($token ? "DISPONÍVEL" : "NÃO DISPONÍVEL") . "\n";

if ($catalogId && $token) {
    echo "Chamando getCatalogOffers...\n";
    $offers = $service->getCatalogOffers($catalogId);
    echo "Resultado: " . print_r($offers, true) . "\n";
}
