<?php

namespace App\Services;

use App\Models\Integracao;
use App\Models\MarketplaceAnuncio;
use SellingPartnerApi\Enums\Endpoint;
use SellingPartnerApi\Enums\Marketplace;
use SellingPartnerApi\Seller\SellerConnector;
use SellingPartnerApi\Seller\OrdersV0\Api as OrdersApi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AmazonSpApiService
{
    private ?SellerConnector $connector = null;
    private int $empresaId;

    public function __construct(int $empresaId)
    {
        $this->empresaId = $empresaId;
    }

    private function getConnector(): ?SellerConnector
    {
        if ($this->connector) {
            return $this->connector;
        }

        $integracao = Integracao::where('empresa_id', $this->empresaId)
            ->where('marketplace', 'amazon')
            ->where('ativo', true)
            ->first();

        if (!$integracao) {
            Log::error("Amazon: Integração não encontrada para empresa {$this->empresaId}");
            return null;
        }

        $config = $integracao->configuracoes;

        if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['refresh_token'])) {
            Log::error("Amazon: Credenciais incompletas para empresa {$this->empresaId}");
            return null;
        }

        try {
            $this->connector = SellerConnector::seller(
                $config['client_id'],
                $config['client_secret'],
                $config['refresh_token'],
                Endpoint::NA
            );

            return $this->connector;
        } catch (\Exception $e) {
            Log::error("Amazon: Erro ao criar connector - " . $e->getMessage());
            return null;
        }
    }

    public function testConnection(): array
    {
        $connector = $this->getConnector();
        
        if (!$connector) {
            return ['success' => false, 'message' => 'Credenciais não configuradas'];
        }

        try {
            $ordersApi = new OrdersApi($connector);
            
            $response = $ordersApi->getOrders(
                marketplaceIds: [Marketplace::BR->value],
                orderStatuses: ['Unshipped', 'Shipped'],
                maxResultsPerPage: 1,
                createdAfter: Carbon::now()->subDays(7)->toIso8601String()
            );
            
            return [
                'success' => true,
                'message' => 'Conexão OK!',
                'data' => json_decode($response->body(), true)
            ];
        } catch (\Exception $e) {
            Log::error("Amazon: Erro na conexão - " . $e->getMessage());
            return [
                'success' => false, 
                'message' => $e->getMessage()
            ];
        }
    }

    public function getOrders(array $params = []): array
    {
        $connector = $this->getConnector();
        
        if (!$connector) {
            return ['error' => 'Credenciais não configuradas'];
        }

        try {
            $ordersApi = new OrdersApi($connector);
            
            $marketplaceIds = [Marketplace::BR->value];
            $orderStatuses = ['Unshipped', 'PartiallyShipped', 'Shipped'];
            $maxResults = $params['maxResults'] ?? 50;
            $createdAfter = $params['createdAfter'] ?? Carbon::now()->subDays(30);
            
            $response = $ordersApi->getOrders(
                marketplaceIds: $marketplaceIds,
                orderStatuses: $orderStatuses,
                maxResultsPerPage: $maxResults,
                createdAfter: $createdAfter instanceof Carbon ? $createdAfter->toIso8601String() : $createdAfter
            );
            
            return json_decode($response->body(), true);
        } catch (\Exception $e) {
            Log::error("Amazon: Erro ao buscar pedidos - " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function getOrder(string $amazonOrderId): array
    {
        $connector = $this->getConnector();
        
        if (!$connector) {
            return ['error' => 'Credenciais não configuradas'];
        }

        try {
            $ordersApi = new OrdersApi($connector);
            $response = $ordersApi->getOrder($amazonOrderId, [Marketplace::BR->value]);
            
            return json_decode($response->body(), true);
        } catch (\Exception $e) {
            Log::error("Amazon: Erro ao buscar pedido {$amazonOrderId} - " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function getOrderItems(string $amazonOrderId): array
    {
        $connector = $this->getConnector();
        
        if (!$connector) {
            return ['error' => 'Credenciais não configuradas'];
        }

        try {
            $ordersApi = new OrdersApi($connector);
            $response = $ordersApi->getOrderItems($amazonOrderId);
            
            return json_decode($response->body(), true);
        } catch (\Exception $e) {
            Log::error("Amazon: Erro ao buscar itens do pedido {$amazonOrderId} - " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Sync Amazon listings from orders (extracts SKUs from recent orders)
     */
    public function syncListingsFromOrders(int $days = 30): array
    {
        $connector = $this->getConnector();
        
        if (!$connector) {
            return ['success' => false, 'message' => 'Credenciais não configuradas'];
        }

        try {
            $ordersApi = new OrdersApi($connector);
            
            $response = $ordersApi->getOrders(
                marketplaceIds: [Marketplace::BR->value],
                orderStatuses: ['Unshipped', 'PartiallyShipped', 'Shipped'],
                maxResultsPerPage: 100,
                createdAfter: Carbon::now()->subDays($days)->toIso8601String()
            );
            
            $data = json_decode($response->body(), true);
            
            if (!isset($data['payload']['Orders'])) {
                return ['success' => false, 'message' => 'Nenhum pedido encontrado'];
            }

            $integracao = Integracao::where('empresa_id', $this->empresaId)
                ->where('marketplace', 'amazon')
                ->first();

            if (!$integracao) {
                return ['success' => false, 'message' => 'Integração Amazon não encontrada'];
            }

            $count = 0;
            $seenSkus = [];

            foreach ($data['payload']['Orders'] as $order) {
                $orderId = $order['AmazonOrderId'];
                
                // Get order items
                $itemsResponse = $ordersApi->getOrderItems($orderId);
                $itemsData = json_decode($itemsResponse->body(), true);
                
                if (isset($itemsData['payload']['OrderItems'])) {
                    foreach ($itemsData['payload']['OrderItems'] as $item) {
                        $sku = $item['SellerSKU'] ?? null;
                        
                        if (!$sku || isset($seenSkus[$sku])) {
                            continue;
                        }
                        
                        $seenSkus[$sku] = true;
                        
                        // Use SKU as primary key for uniqueness
                        $existing = MarketplaceAnuncio::where('empresa_id', $this->empresaId)
                            ->where('marketplace', 'amazon')
                            ->where('sku', $sku)
                            ->first();
                        
                        if ($existing) {
                            // Update existing
                            $existing->update([
                                'external_id' => $item['ASIN'] ?? $sku,
                                'titulo' => $item['Title'] ?? $sku,
                                'status' => 'ACTIVE',
                                'json_data' => $item,
                            ]);
                        } else {
                            // Create new
                            MarketplaceAnuncio::create([
                                'empresa_id' => $this->empresaId,
                                'marketplace' => 'amazon',
                                'integracao_id' => $integracao->id,
                                'sku' => $sku,
                                'external_id' => $item['ASIN'] ?? $sku,
                                'titulo' => $item['Title'] ?? $sku,
                                'status' => 'ACTIVE',
                                'json_data' => $item,
                            ]);
                        }
                        $count++;
                    }
                }
            }

            return ['success' => true, 'message' => "{$count} anúncios sincronizados dos pedidos"];
            
        } catch (\Exception $e) {
            Log::error("Amazon: Erro ao sincronizar anúncios - " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sync Amazon listings to database
     */
    public function syncListings(int $limit = 100): array
    {
        // Use orders-based sync as primary method
        return $this->syncListingsFromOrders(30);
    }
}
