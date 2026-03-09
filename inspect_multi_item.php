<?php

use App\Models\MarketplacePedido;
use App\Models\Empresa;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$searchId = '2000011914801283';
$order = MarketplacePedido::where('pedido_id', 'like', "%$searchId%")
    ->orWhere('external_id', 'like', "%$searchId%")
    ->orWhere('json_data', 'like', "%$searchId%")
    ->first();

if (!$order) {
    echo "Order not found!\n";
    exit;
}

echo "Order found! ID: {$order->id}, Pedido ID: {$order->pedido_id}\n";
echo "Valor Produtos: {$order->valor_produtos}\n";
echo "Valor Líquido (DB): {$order->valor_liquido}\n";
echo "Lucro (DB): {$order->lucro}\n";

$jsonData = $order->json_data;
$items = $jsonData['order_items'] ?? [];

echo "\nOrder Items in JSON:\n";
foreach ($items as $idx => $item) {
    $title = $item['item']['title'] ?? 'N/A';
    $qty = $item['quantity'] ?? 0;
    $price = $item['unit_price'] ?? 0;
    $sku = $item['item']['seller_sku'] ?? 'N/A';
    echo "[$idx] Title: $title | Qty: $qty | Unit Price: $price | SKU: $sku\n";
}

$profitService = new \App\Services\OrderProfitService();
$empresa = Empresa::find($order->empresa_id);
$result = $profitService->calculateOrderProfit($order, $empresa);

echo "\nCalculation Result from Service:\n";
print_r($result);
