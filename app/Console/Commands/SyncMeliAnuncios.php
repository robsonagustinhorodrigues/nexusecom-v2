<?php

namespace App\Console\Commands;

use App\Models\Integracao;
use App\Models\MarketplaceAnuncio;
use Illuminate\Console\Command;

class SyncMeliAnuncios extends Command
{
    protected $signature = 'sync:meli-anuncios {empresaId? : ID da empresa}';
    protected $description = 'Sincroniza anúncios do Mercado Livre';

    public function handle()
    {
        $empresaId = $this->argument('empresaId');

        $query = Integracao::where('marketplace', 'mercadolivre')->where('ativo', true);

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $integracoes = $query->get();

        if ($integracoes->isEmpty()) {
            $this->error('Nenhuma integração do Mercado Livre encontrada.');
            return;
        }

        foreach ($integracoes as $integracao) {
            $this->syncEmpresa($integracao);
        }
    }

    private function syncEmpresa(Integracao $integracao)
    {
        $empresaId = $integracao->empresa_id;
        $userId = $integracao->external_user_id;
        $token = $integracao->access_token;

        $this->info("Sincronizando empresa {$empresaId} (user: {$userId})...");

        // Get all item IDs
        $itemIds = [];
        $offset = 0;
        $limit = 100;

        do {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get("https://api.mercadolibre.com/users/{$userId}/items/search", [
                'limit' => $limit,
                'offset' => $offset,
            ]);

            if (!$response->successful()) {
                $this->error("Erro ao buscar itens: " . $response->status());
                break;
            }

            $data = $response->json();
            $items = $data['results'] ?? [];
            $itemIds = array_merge($itemIds, $items);

            $offset += $limit;
            $this->line("  Encontrados {$offset} itens...");

        } while (count($items) === $limit);

        $this->info("  Total de itens: " . count($itemIds));

        // Save each item
        $saved = 0;
        foreach ($itemIds as $itemId) {
            // Get item details
            $itemResponse = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get("https://api.mercadolibre.com/items/{$itemId}");

            if ($itemResponse->successful()) {
                $item = $itemResponse->json();

                MarketplaceAnuncio::updateOrCreate(
                    [
                        'integracao_id' => $integracao->id,
                        'external_id' => $itemId,
                    ],
                    [
                        'empresa_id' => $empresaId,
                        'marketplace' => 'mercadolivre',
                        'sku' => $item['seller_custom_field'] ?? null,
                        'titulo' => $item['title'] ?? $itemId,
                        'preco' => floatval($item['price'] ?? 0),
                        'estoque' => intval($item['available_quantity'] ?? 0),
                        'status' => $item['status'] ?? 'active',
                        'json_data' => $item,
                    ]
                );
                $saved++;
            }

            if ($saved % 10 === 0) {
                $this->line("  Processados {$saved}...");
            }
        }

        $this->info("  Salvos {$saved} anúncios!");
    }
}
