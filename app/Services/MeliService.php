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

        try {
            // 1. Busca IDs de todos os anúncios (paginado)
            $searchResponse = Http::withToken($accessToken)
                ->get("https://api.mercadolibre.com/users/{$userId}/items/search");

            if ($searchResponse->failed()) {
                throw new \Exception('Erro ao buscar itens ML: '.$searchResponse->body());
            }

            $itemIds = $searchResponse->json('results') ?? [];

            foreach ($itemIds as $itemId) {
                // 2. Busca detalhes de cada anúncio com todos os atributos
                $itemResponse = Http::withToken($accessToken)
                    ->get("https://api.mercadolibre.com/items/{$itemId}?include_attributes=all");

                if ($itemResponse->successful()) {
                    $data = $itemResponse->json();

                    // Buscar descrição separada
                    try {
                        $descResponse = Http::withToken($accessToken)
                            ->get("https://api.mercadolibre.com/items/{$itemId}/description");
                        if ($descResponse->successful()) {
                            $descData = $descResponse->json();
                            $data['description'] = $descData['plain_text'] ?? $descData['text'] ?? null;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Erro ao buscar descrição: '.$e->getMessage());
                    }

                    // Enriquecer variações com URLs de imagens
                    if (! empty($data['variations'])) {
                        foreach ($data['variations'] as &$variation) {
                            if (! empty($variation['picture_ids'])) {
                                $pictureUrls = [];
                                foreach ($variation['picture_ids'] as $picId) {
                                    $picResponse = Http::withToken($accessToken)
                                        ->get("https://api.mercadolibre.com/pictures/{$picId}/fetch", [
                                            'size' => '500x500',
                                        ]);
                                    if ($picResponse->successful()) {
                                        $pictureUrls[] = $picResponse->json('url') ?? "https://http2.mlstatic.com/D_{$picId}-I.jpg";
                                    }
                                }
                                $variation['picture_urls'] = $pictureUrls;
                            }
                        }
                    }

                    // Buscar informações do produto via user_product_id
                    if (! empty($data['user_product_id'])) {
                        try {
                            $upResponse = Http::withToken($accessToken)
                                ->get("https://api.mercadolibre.com/user-products/{$data['user_product_id']}");
                            if ($upResponse->successful()) {
                                $data['user_product_info'] = $upResponse->json();
                            }
                        } catch (\Exception $e) {
                            Log::warning('Erro ao buscar user_product: '.$e->getMessage());
                        }
                    }

                    // Buscar informações do produto/categoria
                    if (! empty($data['catalog_product_id'])) {
                        $productResponse = Http::withToken($accessToken)
                            ->get("https://api.mercadolibre.com/products/{$data['catalog_product_id']}");
                        if ($productResponse->successful()) {
                            $data['product_info'] = $productResponse->json();
                        }
                    }

                    // Buscar SKU nos atributos (SELLER_SKU)
                    $skuPrincipal = null;

                    // Primeiro tenta nos atributos do item (SELLER_SKU)
                    if (! empty($data['attributes'])) {
                        foreach ($data['attributes'] as $attr) {
                            if ($attr['id'] === 'SELLER_SKU' || $attr['id'] === '-seller_sku') {
                                $skuPrincipal = $attr['value_name'] ?? null;
                                break;
                            }
                            if ($attr['id'] === 'CODE' || $attr['id'] === 'PRODUCT_CODE') {
                                $skuPrincipal = $attr['value_name'] ?? null;
                            }
                        }
                    }

                    // Se não encontrou, tenta no campo direto
                    if (! $skuPrincipal) {
                        $skuPrincipal = $data['seller_sku'] ?? $data['sku'] ?? null;
                    }

                    // Se ainda não encontrou, tenta nas variações
                    if (! $skuPrincipal && ! empty($data['variations'])) {
                        foreach ($data['variations'] as $variation) {
                            if (! empty($variation['seller_custom_field'])) {
                                $skuPrincipal = $variation['seller_custom_field'];
                                break;
                            }
                            if (! empty($variation['sku'])) {
                                $skuPrincipal = $variation['sku'];
                                break;
                            }
                            // Tentar encontrar SELLER_SKU nos atributos da variação
                            if (! empty($variation['attributes'])) {
                                foreach ($variation['attributes'] as $attr) {
                                    if ($attr['id'] === 'SELLER_SKU' || $attr['id'] === '-seller_sku') {
                                        $skuPrincipal = $attr['value_name'] ?? null;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }

                    MarketplaceAnuncio::updateOrCreate(
                        [
                            'integracao_id' => $integracao->id,
                            'external_id' => $itemId,
                        ],
                        [
                            'empresa_id' => $integracao->empresa_id,
                            'marketplace' => 'mercadolivre',
                            'titulo' => $data['title'],
                            'sku' => $skuPrincipal,
                            'preco' => $data['price'],
                            'estoque' => $data['available_quantity'],
                            'status' => $data['status'],
                            'url_anuncio' => $data['permalink'],
                            'json_data' => $data,
                        ]
                    );
                }
            }

            return count($itemIds);

        } catch (\Exception $e) {
            Log::error('Erro Sync ML Anuncios: '.$e->getMessage());
            throw $e;
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
    public function getPromocoesAtivas(Integracao $integracao): array
    {
        $accessToken = $integracao->access_token;
        $userId = $integracao->external_user_id;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'version' => 'v2',
            ])->get('https://api.mercadolibre.com/seller-promotions/promotions', [
                'user_id' => $userId,
                'status' => 'active',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['results'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Erro ao buscar promoções ativas: '.$e->getMessage());

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
     */
    public function syncPromocoes(Integracao $integracao): array
    {
        $promocoes = $this->getPromocoesAtivas($integracao);

        $itensPromocao = [];

        foreach ($promocoes as $promocao) {
            $tipo = $promocao['promotion_type'] ?? '';
            $promocaoId = $promocao['id'] ?? '';

            if ($promocaoId) {
                $itens = $this->getItensPromocao($integracao, $promocaoId, $tipo);
                foreach ($itens as $item) {
                    $itensPromocao[$item['item_id']] = [
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

        return $itensPromocao;
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
