<?php

namespace App\Services;

use App\Models\Integracao;
use App\Models\MarketplaceAnuncio;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeliService
{
    /**
     * Sincroniza os anúncios vinculados a uma integração específica
     */
    public function syncAnuncios(Integracao $integracao)
    {
        if ($integracao->isExpired()) {
            $this->refreshToken($integracao);
            $integracao->refresh();
        }

        $userId = $integracao->external_user_id;
        $accessToken = $integracao->access_token;

        $allItemIds = [];
        $limit = 50;
        $offset = 0;
        $total = 0;

        try {
            // 1. Busca IDs de todos os anúncios (paginado para tirar limitações)
            do {
                $searchResponse = Http::withToken($accessToken)
                    ->get("https://api.mercadolibre.com/users/{$userId}/items/search", [
                        'offset' => $offset,
                        'limit' => $limit
                    ]);

                if ($searchResponse->failed()) {
                    throw new \Exception('Erro ao buscar itens ML: '.$searchResponse->body());
                }

                $data = $searchResponse->json();
                $itemIds = $data['results'] ?? [];
                $allItemIds = array_merge($allItemIds, $itemIds);
                
                $total = $data['paging']['total'] ?? 0;
                $offset += $limit;

            } while ($offset < $total && !empty($itemIds));

            foreach ($allItemIds as $itemId) {
                // 2. Busca detalhes de cada anúncio com todos os atributos
                $itemResponse = Http::withToken($accessToken)
                    ->get("https://api.mercadolibre.com/items/{$itemId}?include_attributes=all");

                if ($itemResponse->successful()) {
                    $itemData = $itemResponse->json();

                    // Buscar descrição separada
                    try {
                        $descResponse = Http::withToken($accessToken)
                            ->get("https://api.mercadolibre.com/items/{$itemId}/description");
                        if ($descResponse->successful()) {
                            $descData = $descResponse->json();
                            $itemData['description'] = $descData['plain_text'] ?? $descData['text'] ?? null;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Erro ao buscar descrição: '.$e->getMessage());
                    }

                    // Se o item tem variações, sincronizamos cada uma
                    if (!empty($itemData['variations'])) {
                        foreach ($itemData['variations'] as $variation) {
                            $this->upsertAnuncioVariation($integracao, $itemData, $variation, $accessToken);
                        }
                    } else {
                        // Sem variações, sincroniza o item principal
                        $this->upsertAnuncioSimple($integracao, $itemData, $accessToken);
                    }
                }
            }

            // 3. Marcar como fechados os anúncios que não existem mais no ML
            // Importante: Aqui precisamos considerar external_id E variation_id se quisermos ser precisos,
            // mas o item_id excluido remove todas as variações.
            $fechados = MarketplaceAnuncio::where('integracao_id', $integracao->id)
                ->whereNotIn('external_id', $allItemIds)
                ->whereNull('closed_at')
                ->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                    'closed_reason' => 'ml_excluded',
                ]);

            if ($fechados > 0) {
                Log::info("MeliService: {$fechados} anúncios marcados como fechados (não existem mais no ML)");
            }

            // 4. Batch sync Visits API
            if (!empty($allItemIds)) {
                $this->syncVisits($integracao, $allItemIds);
            }

            return count($allItemIds);

        } catch (\Exception $e) {
            Log::error('Erro Sync ML Anuncios: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Sincroniza um anúncio simples (sem variações)
     */
    private function upsertAnuncioSimple(Integracao $integracao, array $data, string $accessToken)
    {
        $itemId = $data['id'];
        $sku = $this->extractSku($data);

        MarketplaceAnuncio::updateOrCreate(
            [
                'integracao_id' => $integracao->id,
                'external_id' => $itemId,
                'variation_id' => null,
            ],
            [
                'empresa_id' => $integracao->empresa_id,
                'marketplace' => 'mercadolivre',
                'titulo' => $data['title'],
                'sku' => $sku,
                'preco' => $data['price'],
                'estoque' => $data['available_quantity'],
                'status' => $data['status'],
                'url_anuncio' => $data['permalink'],
                'json_data' => $data,
                'thumbnail' => $data['thumbnail'] ?? null,
                'closed_at' => $data['status'] === 'active' ? null : ($data['status'] === 'closed' ? now() : null),
                'closed_reason' => $data['status'] === 'active' ? null : ($data['status'] === 'closed' ? 'ml_closed' : null),
            ]
        );
    }

    /**
     * Sincroniza uma variação específica de um anúncio
     */
    private function upsertAnuncioVariation(Integracao $integracao, array $itemData, array $variation, string $accessToken)
    {
        $itemId = $itemData['id'];
        $variationId = $variation['id'];
        
        // SKU da variação
        $sku = $variation['seller_custom_field'] ?? null;
        if (!$sku && !empty($variation['attributes'])) {
            foreach ($variation['attributes'] as $attr) {
                if ($attr['id'] === 'SELLER_SKU' || $attr['id'] === '-seller_sku') {
                    $sku = $attr['value_name'] ?? null;
                    break;
                }
            }
        }

        // Título formatado com variação para facilitar visualização no ERP
        $titulo = $itemData['title'];
        $labels = [];
        if (!empty($variation['attribute_combinations'])) {
            foreach ($variation['attribute_combinations'] as $comb) {
                $labels[] = $comb['value_name'] ?? '';
            }
        }
        if (!empty($labels)) {
            $titulo .= " - " . implode(' / ', array_filter($labels));
        }

        // Thumbnail específica da variação se houver
        $thumbnail = $itemData['thumbnail'];
        if (!empty($variation['picture_ids'])) {
            $picId = $variation['picture_ids'][0];
            $thumbnail = "https://http2.mlstatic.com/D_{$picId}-I.jpg";
        }

        // Mesclar dados da variação no JSON do item para referência
        $mergedData = $itemData;
        $mergedData['variation_id'] = $variationId;
        $mergedData['variation_data'] = $variation;

        MarketplaceAnuncio::updateOrCreate(
            [
                'integracao_id' => $integracao->id,
                'external_id' => $itemId,
                'variation_id' => $variationId,
            ],
            [
                'empresa_id' => $integracao->empresa_id,
                'marketplace' => 'mercadolivre',
                'titulo' => $titulo,
                'sku' => $sku,
                'preco' => $variation['price'] ?? $itemData['price'],
                'estoque' => $variation['available_quantity'] ?? 0,
                'status' => $itemData['status'],
                'url_anuncio' => $itemData['permalink'],
                'json_data' => $mergedData,
                'thumbnail' => $thumbnail,
                'closed_at' => $itemData['status'] === 'active' ? null : ($itemData['status'] === 'closed' ? now() : null),
                'closed_reason' => $itemData['status'] === 'active' ? null : ($itemData['status'] === 'closed' ? 'ml_closed' : null),
            ]
        );
    }

    private function extractSku(array $data)
    {
        if (! empty($data['attributes'])) {
            foreach ($data['attributes'] as $attr) {
                if ($attr['id'] === 'SELLER_SKU' || $attr['id'] === '-seller_sku') {
                    return $attr['value_name'] ?? null;
                }
            }
        }
        return $data['seller_sku'] ?? $data['sku'] ?? null;
    }

    /**
     * Sincroniza as visitas de um lote de anúncios (máximo 50 itens)
     */
    public function syncVisits(Integracao $integracao, array $itemIds)
    {
        $accessToken = $integracao->access_token;
        $chunks = array_chunk($itemIds, 50);

        foreach ($chunks as $chunk) {
            $idsParam = implode(',', $chunk);
            try {
                $response = Http::withToken($accessToken)
                    ->get("https://api.mercadolibre.com/visits/items?ids={$idsParam}&window=LAST_30_DAYS");

                if ($response->successful()) {
                    $visitsData = $response->json();
                    
                    foreach ($visitsData as $itemId => $visitsCount) {
                        $anuncio = MarketplaceAnuncio::where('integracao_id', $integracao->id)
                            ->where('external_id', $itemId)
                            ->first();
                            
                        if ($anuncio) {
                            $jsonData = $anuncio->json_data ?? [];
                            $jsonData['visits'] = $visitsCount;
                            
                            $anuncio->update([
                                'json_data' => $jsonData
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Erro ao sincronizar visitas ML: '.$e->getMessage());
            }
        }
    }

    /**
     * Busca o custo dofrete de um item no Mercado Livre
     * Retorna o valor que o seller paga pelofrete (grátis ou não)
     */
    public function getFreteCusto(Integracao $integracao, string $itemId): array
    {
        $userId = $integracao->external_user_id;
        $accessToken = $integracao->access_token;

        $cost = 0;
        $source = 'none';
        $isFreeShipping = false;

        try {
            // 1. Tentar obter o custo dofrete grátis via API de shipping_options
            $response = Http::withToken($accessToken)
                ->get("https://api.mercadolibre.com/users/{$userId}/shipping_options/free", [
                    'item_id' => $itemId,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Verificar se o ML está subsidiando ofrete (free_shipping_by_meli)
                $isMeliSubsidied = isset($data['coverage']['all_country']['free_shipping_by_meli'])
                    || (isset($data['coverage']) && collect($data['coverage'])->contains('free_shipping_by_meli', 1));

                if ($isMeliSubsidied) {
                    // ML está pagando ofrete - custo é 0 para o vendedor
                    $cost = 0;
                    $source = 'meli_subsidized';
                } elseif (isset($data['coverage']['all_country']['cost'])) {
                    // Custo explícito retornado
                    $cost = floatval($data['coverage']['all_country']['cost']);
                    $source = 'shipping_options';
                } elseif (isset($data['coverage']['all_country']['list_cost'])) {
                    // list_cost é o custo original (pode ser maior que 0 mesmo comfrete grátis)
                    $cost = floatval($data['coverage']['all_country']['list_cost']);
                    $source = 'shipping_options_list';
                } elseif (isset($data['coverage'])) {
                    // Tentar em coverage sem all_country
                    foreach ($data['coverage'] as $coverage) {
                        if (isset($coverage['cost']) && $coverage['cost'] > 0) {
                            $cost = floatval($coverage['cost']);
                            $source = 'shipping_options';
                            break;
                        }
                        if (! isset($coverage['cost']) && isset($coverage['list_cost']) && $coverage['list_cost'] > 0) {
                            $cost = floatval($coverage['list_cost']);
                            $source = 'shipping_options_list';
                            break;
                        }
                    }
                }
            }

            // 2. Verificar se éfrete grátis no item
            $itemResponse = Http::withToken($accessToken)
                ->get("https://api.mercadolibre.com/items/{$itemId}");

            if ($itemResponse->successful()) {
                $itemData = $itemResponse->json();
                $isFreeShipping = $itemData['shipping']['free_shipping'] ?? false;
            }

            // Se não encontrou custo dofrete grátis, pode ser que o seller paga unitário
            // Nesse caso, o custo é coberto pelofrete do buyer ou é zero
            // Para anúncios semfrete grátis, o ML cobra do buyer

            return [
                'cost' => $cost,
                'is_free_shipping' => $isFreeShipping,
                'source' => $source,
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar custo dofrete: '.$e->getMessage());

            return ['cost' => 0, 'source' => 'error', 'is_free_shipping' => false];
        }
    }

    /**
     * Atualiza o custo dofrete para todos os anúncios de uma integração
     * Processa em lotes para não sobrecarregar a API
     */
    public function syncFreteAnuncios(Integracao $integracao, int $batchSize = 50): int
    {
        $anuncios = MarketplaceAnuncio::where('integracao_id', $integracao->id)
            ->whereNotNull('external_id')
            ->limit($batchSize)
            ->get();

        $atualizados = 0;

        foreach ($anuncios as $anuncio) {
            $freteData = $this->getFreteCusto($integracao, $anuncio->external_id);

            $anuncio->update([
                'frete_custo_seller' => $freteData['cost'],
                'frete_source' => $freteData['source'],
                'frete_updated_at' => now(),
            ]);

            $atualizados++;
        }

        return $atualizados;
    }

    /**
     * Busca promoções ativas de um vendedor
     */
    public function getPromocoes(Integracao $integracao, string $status = 'active'): array
    {
        $accessToken = $integracao->access_token;
        $userId = $integracao->external_user_id;

        try {
            // ML API v2 for promotions
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'version' => 'v2', // Some docs use this
                'X-Version' => '2', // Others use this
            ])->get('https://api.mercadolibre.com/seller-promotions/promotions', [
                'user_id' => $userId,
                'status' => $status,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('MeliService getPromocoes success', [
                    'status' => $status, 
                    'count' => count($data['results'] ?? []),
                    'user_id' => $userId
                ]);
                return $data['results'] ?? [];
            }

            Log::error('MeliService getPromocoes error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'user_id' => $userId,
                'req_status' => $status
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Erro ao buscar promoções: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Busca itens de uma promoção específica
     */
    public function getItensPromocao(Integracao $integracao, string $promocaoId, string $tipo): array
    {
        $accessToken = $integracao->access_token;
        $userId = $integracao->external_user_id;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'version' => 'v2',
            ])->get("https://api.mercadolibre.com/seller-promotions/promotions/{$promocaoId}/items", [
                'user_id' => $userId,
                'promotion_type' => $tipo,
            ]);

            if ($response->successful()) {
                return $response->json()['results'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Erro ao buscar itens da promoção: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Sincroniza promoções para todos os anúncios de uma integração
     * e salva no MarketplaceAnuncio.
     */
    public function syncPromocoes(Integracao $integracao): array
    {
        $promocoesAtivas = $this->getPromocoes($integracao, 'active');
        $itensPromocao = [];
        $idsAtivos = [];

        foreach ($promocoesAtivas as $promocao) {
            $tipo = $promocao['promotion_type'] ?? '';
            $promocaoId = $promocao['id'] ?? '';

            if ($promocaoId) {
                $itens = $this->getItensPromocao($integracao, $promocaoId, $tipo);
                foreach ($itens as $item) {
                    $itemId = $item['item_id'];
                    $idsAtivos[] = $itemId;
                    $itensPromocao[$itemId] = [
                        'tipo' => $tipo,
                        'id' => $promocaoId,
                        'deal_price' => floatval($item['deal_price'] ?? 0),
                        'original_price' => floatval($item['original_price'] ?? 0),
                        'discount_percent' => floatval($item['discount_percent'] ?? 0),
                        'start_date' => $item['start_date'] ?? null,
                        'finish_date' => $item['finish_date'] ?? null,
                    ];
                }
            }
        }

        // Atualizar banco de dados para os anúncios da integração
        if (!empty($idsAtivos)) {
            $idsChunks = array_chunk($idsAtivos, 100);
            foreach ($idsChunks as $chunk) {
                $anuncios = MarketplaceAnuncio::where('integracao_id', $integracao->id)
                    ->whereIn('external_id', $chunk)
                    ->get();

                foreach ($anuncios as $anuncio) {
                    $dadosPromo = $itensPromocao[$anuncio->external_id] ?? null;
                    if ($dadosPromo) {
                        $anuncio->update([
                            'promocao_id' => $dadosPromo['id'],
                            'promocao_tipo' => $dadosPromo['tipo'],
                            'preco_original' => $dadosPromo['original_price'],
                            'promocao_valor' => $dadosPromo['deal_price'],
                            'promocao_desconto' => $dadosPromo['discount_percent'],
                            'promocao_inicio' => $dadosPromo['start_date'],
                            'promocao_fim' => $dadosPromo['finish_date'],
                        ]);
                    }
                }
            }
        }

        // Limpar promoções que não estão mais ativas
        if (!empty($idsAtivos)) {
            MarketplaceAnuncio::where('integracao_id', $integracao->id)
                ->whereNotNull('promocao_id')
                ->whereNotIn('external_id', $idsAtivos)
                ->update([
                    'promocao_id' => null,
                    'promocao_tipo' => null,
                    'preco_original' => null,
                    'promocao_valor' => null,
                    'promocao_desconto' => null,
                    'promocao_inicio' => null,
                    'promocao_fim' => null,
                ]);
        } else {
            MarketplaceAnuncio::where('integracao_id', $integracao->id)
                ->whereNotNull('promocao_id')
                ->update([
                    'promocao_id' => null,
                    'promocao_tipo' => null,
                    'preco_original' => null,
                    'promocao_valor' => null,
                    'promocao_desconto' => null,
                    'promocao_inicio' => null,
                    'promocao_fim' => null,
                ]);
        }

        return [
            'success' => true,
            'count' => count($itensPromocao),
            'items' => $itensPromocao
        ];
    }

    /**
     * Atualiza o access_token usando o refresh_token
     */
    public function refreshToken(Integracao $integracao)
    {
        try {
            $response = Http::asForm()->post('https://api.mercadolibre.com/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => config('services.meli.app_id'),
                'client_secret' => config('services.meli.app_secret'),
                'refresh_token' => $integracao->refresh_token,
            ]);

            if ($response->failed()) {
                throw new \Exception('Erro Refresh Token ML: '.$response->body());
            }

            $data = $response->json();

            $integracao->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at' => now()->addSeconds($data['expires_in']),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Falha Refresh ML: '.$e->getMessage());

            return false;
        }
    }
}
