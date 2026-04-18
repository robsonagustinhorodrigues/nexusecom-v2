<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Pega um anúncio para testar
$anuncio = \App\Models\MarketplaceAnuncio::find(347);

$permalink = $anuncio->json_data['permalink'] ?? null;
$catalogId = $anuncio->json_data['catalog_product_id'] ?? null;

echo "Anúncio: " . $anuncio->titulo . "\n";
echo "Permalink: " . $permalink . "\n";
echo "Catalog ID: " . $catalogId . "\n\n";

if (!$permalink) {
    echo "ERRO: Sem permalink\n";
    exit(1);
}

// Extrai o slug do permalink
if (preg_match('/MLB-\d+-(.+?)(?:\?|_JM|$)/', $permalink, $matches)) {
    $slug = rtrim($matches[1], '-');
    $url = "https://www.mercadolivre.com.br/{$slug}/p/{$catalogId}/s?";
    
    echo "URL dos concorrentes: " . $url . "\n\n";
    
    // Usa Chrome headless para renderizar a página
    $command = sprintf(
        'google-chrome --headless --disable-gpu --no-sandbox --dump-dom %s 2>/dev/null',
        escapeshellarg($url)
    );
    
    echo "Executando Chrome...\n";
    $html = shell_exec($command);
    
    if (!$html) {
        echo "ERRO: Chrome não retornou conteúdo\n";
        exit(1);
    }
    
    echo "HTML recebido: " . strlen($html) . " bytes\n\n";
    
    // Procura por preços no HTML
    preg_match_all('/R\$\s*([0-9.,]+)/', $html, $matches);
    
    echo "Preços encontrados:\n";
    if (!empty($matches[1])) {
        foreach (array_unique($matches[1]) as $price) {
            echo "  - R$ " . $price . "\n";
        }
    } else {
        echo "  Nenhum preço encontrado no formato esperado\n";
    }
    
    // Salva o HTML para debug
    file_put_contents('/tmp/meli_concorrentes.html', $html);
    echo "\nHTML salvo em /tmp/meli_concorrentes.html\n";
    
} else {
    echo "ERRO: Não foi possível extrair slug do permalink\n";
}
