<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$empresaId = 6;

$service = new \App\Services\MeliIntegrationService($empresaId);
$integracao = $service->getIntegracao();

if (! $integracao) {
    echo "Sem integracao para empresa {$empresaId}.\n";
    exit;
}

// Busca anuncios existentes
$ad = \App\Models\MarketplaceAnuncio::where('marketplace', 'mercadolivre')
    ->where('empresa_id', $empresaId)
    ->whereNotNull('external_id')
    ->first();

if (! $ad) {
    echo "Sem anuncio. Execute o sync de anuncios primeiro.\n";
    exit;
}

echo "=== Testando getFreteCusto ===\n";
echo "Item: {$ad->external_id}\n";

$meliService = new \App\Services\MeliService;
$freteData = $meliService->getFreteCusto($integracao, $ad->external_id);
echo 'Frete Cost: R$ '.number_format($freteData['cost'], 2, ',', '.')."\n";
echo 'Is Free Shipping: '.($freteData['is_free_shipping'] ? 'Yes' : 'No')."\n";
echo "Source: {$freteData['source']}\n\n";

// Atualizar o anuncio comfrete
$ad->update([
    'frete_custo_seller' => $freteData['cost'],
    'frete_source' => $freteData['source'],
    'frete_updated_at' => now(),
]);

$ad->refresh();

echo "=== Calculando Lucratividade ===\n";
$component = new \App\Livewire\Integrations\Anuncios;
$lucro = $component->calcularLucratividade($ad);

echo 'Preco: R$ '.number_format($lucro['preco'], 2, ',', '.')."\n";
echo 'Custo: R$ '.number_format($lucro['custo'], 2, ',', '.')."\n";
echo 'Taxas: R$ '.number_format($lucro['taxas'], 2, ',', '.')."\n";
echo 'Frete: R$ '.number_format($lucro['frete'], 2, ',', '.')." ({$lucro['frete_source']})\n";
echo 'Imposto: R$ '.number_format($lucro['imposto'], 2, ',', '.')."\n";
echo 'Lucro: R$ '.number_format($lucro['lucro_bruto'], 2, ',', '.')."\n";
echo 'Margem: '.number_format($lucro['margem'], 1)."%\n";
