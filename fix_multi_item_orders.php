<?php

use App\Models\MarketplacePedido;
use App\Services\MeliIntegrationService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting bulk recalculation of Mercado Livre orders for multi-item fixes...\n";

$orders = MarketplacePedido::where('marketplace', 'mercadolivre')
    ->orderBy('id', 'desc')
    ->limit(500)
    ->get();

$count = 0;
foreach ($orders as $order) {
    $items = $order->json_data['order_items'] ?? $order->order_json['order_items'] ?? [];
    $hasMulti = false;
    foreach ($items as $item) {
        if (($item['quantity'] ?? 1) > 1) {
            $hasMulti = true;
            break;
        }
    }

    if ($hasMulti || count($items) > 1) {
        $oldTaxa = $order->valor_taxa_platform;
        $service = new MeliIntegrationService($order->empresa_id);
        
        if ($service->recalculateOrderFinancials($order)) {
            $order->refresh();
            if (abs($oldTaxa - $order->valor_taxa_platform) > 0.01) {
                echo "Pedido {$order->pedido_id} (ID: {$order->id}): Taxa corrigida de $oldTaxa para {$order->valor_taxa_platform}. Novo lucro: {$order->lucro}\n";
                $count++;
            }
        }
    }
}

echo "\nFinished! $count orders were corrected.\n";
