<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$integracao = App\Models\Integracao::where('marketplace', 'mercadolivre')->where('ativo', true)->first();

$response = Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $integracao->access_token,
])->get('https://api.mercadolibre.com/seller-promotions/promotions', [
    'user_id' => $integracao->external_user_id,
]);

echo "STATUS: " . $response->status() . "\n";
echo "BODY: " . $response->body() . "\n";
