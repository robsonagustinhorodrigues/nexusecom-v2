<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\MeliIntegrationService(4);
$token = $service->getPublicAccessToken();

echo "Token: " . ($token ? substr($token, 0, 30) . '...' : 'NULL') . "\n";

if (!$token) {
    echo "ERRO: Token não disponível\n";
    exit(1);
}

// Testa a API de search
$searchUrl = 'https://api.mercadolibre.com/sites/MLB/search?q=etiqueta%20adesiva%20a4%20100%20folhas&limit=5';

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
])->get($searchUrl);

echo "Status: " . $response->status() . "\n";

$data = $response->json();
if ($data && isset($data['results'])) {
    echo "Resultados encontrados: " . count($data['results']) . "\n\n";
    foreach (array_slice($data['results'], 0, 3) as $item) {
        echo "- " . substr($item['title'] ?? 'N/A', 0, 60) . "\n";
        echo "  Preço: R$ " . number_format($item['price'] ?? 0, 2, ',', '.') . "\n";
        echo "  Vendedor: " . ($item['seller']['id'] ?? $item['seller_id'] ?? 'N/A') . "\n";
        echo "  Tipo envio: " . ($item['shipping']['logistic_type'] ?? 'N/A') . "\n\n";
    }
} else {
    echo "Body: " . substr($response->body(), 0, 500) . "\n";
}
