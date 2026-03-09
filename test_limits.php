<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

$baseUrl = 'http://localhost:8000'; // Assuming local dev server
$empresaId = 6; 

echo "Testing 31-day limit for recalculate API...\n";

// Test 32 days (should fail)
$from = Carbon::now()->subDays(32)->format('Y-m-d');
$to = Carbon::now()->format('Y-m-d');

// Manual call mock or direct controller test
$request = new \Illuminate\Http\Request([
    'from' => $from,
    'to' => $to,
    'empresa_id' => $empresaId
]);

$controller = new \App\Http\Controllers\Api\OrderController();
$response = $controller->recalculate($request);

echo "Response for 32 days: " . $response->getContent() . "\n";

// Test 5 days (should succeed)
$from = Carbon::now()->subDays(5)->format('Y-m-d');
$to = Carbon::now()->format('Y-m-d');

$request = new \Illuminate\Http\Request([
    'from' => $from,
    'to' => $to,
    'empresa_id' => $empresaId
]);

$response = $controller->recalculate($request);
echo "Response for 5 days: " . $response->getContent() . "\n";
