<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$integracao = App\Models\Integracao::where('marketplace', 'mercadolivre')->where('ativo', true)->first();
if (!$integracao) {
    echo "Nenhuma integracao ativa encontrada.\n";
    exit;
}

$service = new App\Services\MeliService();
if ($integracao->isExpired()) {
    $service->refreshToken($integracao);
    $integracao->refresh();
}

$response = Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $integracao->access_token,
    'version' => 'v2',
])->get('https://api.mercadolibre.com/seller-promotions/promotions', [
    'user_id' => $integracao->external_user_id,
    'status' => 'active',
]);

echo "PROMOCOES ATIVAS:\n";
echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";

$response2 = Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $integracao->access_token,
    'version' => 'v2',
])->get('https://api.mercadolibre.com/seller-promotions/promotions', [
    'user_id' => $integracao->external_user_id,
    'status' => 'candidate',
]);

echo "PROMOCOES CANDIDATE:\n";
echo json_encode($response2->json(), JSON_PRETTY_PRINT) . "\n";

