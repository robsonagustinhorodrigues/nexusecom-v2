<?php

namespace App\Services;

use App\Models\Integracao;
use App\Models\Empresa;
use App\Models\Cliente;
use App\Models\MarketplacePedido;
use App\Models\MarketplaceAnuncio;
use App\Services\OrderProfitService;
use SellingPartnerApi\Enums\Endpoint;
use SellingPartnerApi\Enums\Marketplace;
use SellingPartnerApi\Seller\SellerConnector;
use SellingPartnerApi\Seller\OrdersV0\Api as OrdersApi;
use SellingPartnerApi\Seller\ReportsV20210630\Api as ReportsApi;
use SellingPartnerApi\Seller\ReportsV20210630\Dto\CreateReportSpecification;
use SellingPartnerApi\Seller\CatalogItemsV20220401\Api as CatalogApi;
use SellingPartnerApi\Seller\FinancesV0\Api as FinancesApi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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
        
        // Handle configuracoes as string or array
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

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
            $response = $ordersApi->getOrder($amazonOrderId);
            
            return json_decode($response->body(), true);
        } catch (\Exception $e) {
            Log::error("Amazon: Erro ao buscar pedido {$amazonOrderId} - " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function syncOrders(?string $dateFrom = null, ?string $dateTo = null, ?string $nextToken = null): array
    {
        $connector = $this->getConnector();
        
        if (!$connector) {
            return ['success' => false, 'message' => 'Amazon não conectado'];
        }

        try {
            $ordersApi = new OrdersApi($connector);
            $marketplaceIds = [Marketplace::BR->value];
            $orderStatuses = ['Unshipped', 'PartiallyShipped', 'Shipped', 'InvoiceUnconfirmed'];
            
            // Se tiver nextToken, usamos apenas ele
            $response = null;
            if ($nextToken) {
                $response = $ordersApi->getOrders(
                    marketplaceIds: $marketplaceIds,
                    nextToken: $nextToken
                );
            } else {
                $createdAfter = $dateFrom ? Carbon::parse($dateFrom)->startOfDay()->toIso8601String() : Carbon::now()->subDays(7)->toIso8601String();
                
                // For createdBefore, we must ensure it's at least 2 minutes in the past
                $maxAllowed = Carbon::now()->subMinutes(2);
                
                if ($dateTo) {
                    $targetDate = Carbon::parse($dateTo);
                    // If the provided date is today, we only use endOfDay if it's in the past relative to maxAllowed
                    $createdBefore = $targetDate->isToday() 
                        ? $maxAllowed->toIso8601String() 
                        : $targetDate->endOfDay()->toIso8601String();
                } else {
                    $createdBefore = $maxAllowed->toIso8601String();
                }

                $response = $ordersApi->getOrders(
                    marketplaceIds: $marketplaceIds,
                    orderStatuses: $orderStatuses,
                    createdAfter: $createdAfter,
                    createdBefore: $createdBefore,
                    maxResultsPerPage: 50
                );
            }

            $data = json_decode($response->body(), true);
            $orders = $data['payload']['Orders'] ?? [];
            $hasNextToken = $data['payload']['NextToken'] ?? null;

            $imported = 0;
            foreach ($orders as $orderData) {
                $this->importOrder($orderData);
                $imported++;
            }

            return [
                'success' => true,
                'message' => "{$imported} pedidos sincronizados",
                'imported' => $imported,
                'has_more' => !empty($hasNextToken),
                'next_token' => $hasNextToken
            ];
        } catch (\Exception $e) {
            Log::error("Amazon: Erro no syncOrders - " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function refreshOrder(int $id): array
    {
        $pedido = MarketplacePedido::where('id', $id)
            ->where('empresa_id', $this->empresaId)
            ->where('marketplace', 'amazon')
            ->first();

        if (!$pedido) {
            return ['success' => false, 'message' => 'Pedido não encontrado'];
        }

        try {
            $amazonOrderId = $pedido->pedido_id;
            Log::info("Amazon: Atualizando pedido {$amazonOrderId}");

            // 1. Buscar dados atualizados do pedido
            $orderDataResult = $this->getOrder($amazonOrderId);
            $orderData = $orderDataResult['payload'] ?? null;

            if (!$orderData) {
                return ['success' => false, 'message' => 'Não foi possível buscar dados do pedido na Amazon'];
            }

            // 2. Buscar itens atualizados
            $itemsResult = $this->getOrderItems($amazonOrderId);
            $orderItems = $itemsResult['payload']['OrderItems'] ?? [];

            // 3. Extrair financeiros (inclui consulta à Finances API)
            $financials = $this->extractFinancials($orderData, $orderItems);

            // 4. Status Mapping
            $statusMap = [
                'Pending' => 'pendente',
                'Unshipped' => 'em_aberto',
                'PartiallyShipped' => 'em_processamento',
                'Shipped' => 'enviado',
                'Canceled' => 'cancelado',
                'Unfulfillable' => 'erro',
                'InvoiceUnconfirmed' => 'em_aberto',
            ];
            $newStatus = $statusMap[$orderData['OrderStatus']] ?? $pedido->status;

            // Se mudou para cancelado e não tinha sido enviado, repor estoque
            if ($newStatus === 'cancelado' && $pedido->status !== 'cancelado' && ! in_array($pedido->status, ['enviado', 'entregue', 'Shipped'])) {
                $this->reporEstoqueAutomatico($pedido, $orderItems);
            }

            // 5. Atualizar o pedido
            $pedido->update([
                'status' => $newStatus,
                'valor_total' => $financials['valor_total'],
                'valor_produtos' => $financials['valor_produtos'],
                'valor_frete' => $financials['valor_frete'],
                'valor_taxa_platform' => $financials['valor_taxa_platform'],
                'json_data' => $orderData,
                'order_json' => $orderData,
                'cart_json' => $orderItems,
            ]);

            // 6. Persistir campos de lucro (OrderProfitService)
            $profitService = new OrderProfitService();
            $profitService->persistFinancialFields($pedido, Empresa::find($this->empresaId));

            return [
                'success' => true,
                'message' => 'Pedido atualizado com sucesso',
                'order' => $pedido
            ];

        } catch (\Exception $e) {
            Log::error("Amazon: Erro ao atualizar pedido - " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function importOrder(array $orderData): ?MarketplacePedido
    {
        try {
            $amazonOrderId = $orderData['AmazonOrderId'];
            $importHash = md5(json_encode($orderData));

            // Verificar se o pedido já existe
            $pedido = MarketplacePedido::where('pedido_id', $amazonOrderId)
                ->where('marketplace', 'amazon')
                ->where('empresa_id', $this->empresaId)
                ->first();

            if ($pedido && $pedido->import_hash === $importHash) {
                return $pedido;
            }

            return $this->processImport($orderData, $importHash);
        } catch (\Exception $e) {
            Log::error("Amazon: Erro ao importar pedido - " . $e->getMessage());
            return null;
        }
    }

    protected function processImport(array $orderData, string $importHash): MarketplacePedido
    {
        $amazonOrderId = $orderData['AmazonOrderId'];
        Log::info("Amazon: Processando importação do pedido {$amazonOrderId}");

        $integracao = Integracao::where('empresa_id', $this->empresaId)
            ->where('marketplace', 'amazon')
            ->first();

        // Buscar itens do pedido para ter o SKU e financeiros
        $itemsResult = $this->getOrderItems($amazonOrderId);
        $orderItems = $itemsResult['payload']['OrderItems'] ?? [];
        
        // Dados do comprador
        $buyerName = $orderData['BuyerInfo']['BuyerName'] ?? 'Cliente Amazon';
        $buyerEmail = $orderData['BuyerInfo']['BuyerEmail'] ?? null;
        
        $shippingAddress = $orderData['ShippingAddress'] ?? [];
        
        // Status Mapping
        $statusMap = [
            'Pending' => 'pendente',
            'Unshipped' => 'em_aberto',
            'PartiallyShipped' => 'em_processamento',
            'Shipped' => 'enviado',
            'Canceled' => 'cancelado',
            'Unfulfillable' => 'erro',
            'InvoiceUnconfirmed' => 'em_aberto',
        ];

        $status = $statusMap[$orderData['OrderStatus']] ?? 'em_aberto';

        // Financeiros
        $financials = $this->extractFinancials($orderData, $orderItems);

        $pedido = MarketplacePedido::updateOrCreate(
            [
                'pedido_id' => $amazonOrderId,
                'marketplace' => 'amazon',
                'empresa_id' => $this->empresaId,
            ],
            [
                'integracao_id' => $integracao->id ?? null,
                'pedido_hub_id' => $amazonOrderId,
                'status' => $status,
                'data_compra' => Carbon::parse($orderData['PurchaseDate']),
                'valor_total' => $financials['valor_total'],
                'valor_produtos' => $financials['valor_produtos'],
                'valor_frete' => $financials['valor_frete'],
                'valor_taxa_platform' => $financials['valor_taxa_platform'],
                'valor_taxa_pagamento' => 0,
                'valor_imposto' => 0,
                'valor_outros' => 0,
                'currency' => $orderData['OrderTotal']['CurrencyCode'] ?? 'BRL',
                'comprador' => $buyerName,
                'comprador_nome' => $buyerName,
                'comprador_email' => $buyerEmail,
                'endereco' => trim(($shippingAddress['AddressLine1'] ?? '') . ' ' . ($shippingAddress['AddressLine2'] ?? '')),
                'cidade' => $shippingAddress['City'] ?? null,
                'estado' => $shippingAddress['StateOrRegion'] ?? null,
                'cep' => $shippingAddress['PostalCode'] ?? null,
                'telefone' => $shippingAddress['Phone'] ?? null,
                'envio_tipo' => $orderData['FulfillmentChannel'] ?? 'MFN',
                'envio_id' => $orderData['AmazonOrderId'],
                'json_data' => $orderData,
                'order_json' => $orderData,
                'cart_json' => $orderItems,
                'import_hash' => $importHash,
            ]
        );

        // Cadastrar cliente
        $this->cadastrarCliente($pedido, $orderData, $shippingAddress);

        return $pedido;
    }

    public function extractFinancials(array $orderData, array $orderItems): array
    {
        $valorTotal = floatval($orderData['OrderTotal']['Amount'] ?? 0);
        $valorProdutos = 0;
        $valorFrete = 0;
        $taxaPlataforma = 0;

        foreach ($orderItems as $item) {
            $valorProdutos += floatval($item['ItemPrice']['Amount'] ?? 0);
            $valorFrete += floatval($item['ShippingPrice']['Amount'] ?? 0);
        }

        // Try to fetch detailed fees from the Finances API
        $amazonOrderId = $orderData['AmazonOrderId'] ?? null;
        if ($amazonOrderId) {
            try {
                $financialData = $this->getFinancialEvents($amazonOrderId);
                if (!isset($financialData['error'])) {
                    $events = $financialData['payload']['FinancialEvents'] ?? [];
                    $shipmentEvents = $events['ShipmentEventList'] ?? [];

                    $totalFees = 0;
                    $totalShipping = 0;
                    $totalPromotion = 0;

                    foreach ($shipmentEvents as $event) {
                        $itemList = $event['ShipmentItemList'] ?? [];
                        foreach ($itemList as $shipItem) {
                            // Sum all fees (Commission, FBA, Closing, etc.)
                            $feeList = $shipItem['ItemFeeList'] ?? [];
                            foreach ($feeList as $fee) {
                                $feeAmount = floatval($fee['FeeAmount']['CurrencyAmount'] ?? 0);
                                $totalFees += abs($feeAmount);
                            }

                            // Extract shipping from charges
                            $chargeList = $shipItem['ItemChargeList'] ?? [];
                            foreach ($chargeList as $charge) {
                                $chargeType = $charge['ChargeType'] ?? '';
                                $chargeAmount = floatval($charge['ChargeAmount']['CurrencyAmount'] ?? 0);
                                if (in_array($chargeType, ['ShippingCharge', 'ShippingTax'])) {
                                    $totalShipping += $chargeAmount;
                                }
                            }

                            // Promotions (discounts)
                            $promoList = $shipItem['PromotionList'] ?? [];
                            foreach ($promoList as $promo) {
                                $promoAmount = floatval($promo['PromotionAmount']['CurrencyAmount'] ?? 0);
                                $totalPromotion += $promoAmount;
                            }
                        }
                    }

                    // Only use Finances data if we actually got data
                    if ($totalFees > 0) {
                        $taxaPlataforma = $totalFees;
                    }
                    if ($totalShipping > 0) {
                        $valorFrete = $totalShipping;
                    }

                    // Also parse ServiceFeeEventList for MFN shipping fees
                    // These appear before ShipmentEventList (which only populates after shipment)
                    $serviceFeeEvents = $events['ServiceFeeEventList'] ?? [];
                    foreach ($serviceFeeEvents as $svcEvent) {
                        $feeList = $svcEvent['FeeList'] ?? [];
                        foreach ($feeList as $fee) {
                            $feeType = $fee['FeeType'] ?? '';
                            $feeAmount = floatval($fee['FeeAmount']['CurrencyAmount'] ?? 0);
                            
                            if (in_array($feeType, ['MFNPostageFee', 'FBAPerUnitFulfillmentFee'])) {
                                // Shipping/fulfillment cost charged by Amazon
                                $valorFrete = max($valorFrete, abs($feeAmount));
                            } elseif ($feeType !== 'MFNShippingChargeback' && abs($feeAmount) > 0) {
                                // Other service fees (add to platform fees)
                                $taxaPlataforma += abs($feeAmount);
                            }
                        }
                    }

                    // Also parse RefundEventList for returns/cancellations
                    $refundEvents = $events['RefundEventList'] ?? [];
                    foreach ($refundEvents as $refundEvent) {
                        $itemRefundList = $refundEvent['ShipmentItemRefundList'] ?? [];
                        foreach ($itemRefundList as $refundItem) {
                            $chargeRefundList = $refundItem['ItemChargeRefundList'] ?? [];
                            foreach ($chargeRefundList as $charge) {
                                $chargeType = $charge['ChargeType'] ?? '';
                                $chargeAmount = floatval($charge['ChargeAmount']['CurrencyAmount'] ?? 0);
                                if (in_array($chargeType, ['Principal', 'Tax'])) {
                                    $valorProdutos += $chargeAmount; // Usually negative in Amazon API
                                } elseif (in_array($chargeType, ['ShippingCharge', 'ShippingTax'])) {
                                    $valorFrete += $chargeAmount;
                                }
                            }

                            $feeRefundList = $refundItem['ItemFeeRefundList'] ?? [];
                            foreach ($feeRefundList as $fee) {
                                $feeAmount = floatval($fee['FeeAmount']['CurrencyAmount'] ?? 0);
                                // Fees are expenses (positive in our $taxaPlataforma). 
                                // Refunds return fees, so we subtract from the expense.
                                $taxaPlataforma -= abs($feeAmount);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Amazon: Finances API falhou para pedido {$amazonOrderId}: " . $e->getMessage());
                // Fallback: keep values from getOrderItems
            }
        }

        // --- FALLBACK LOGIC ---
        // If the order is Shipped but we have no fees or shipping cost from Finance API,
        // we apply a fallback estimation (12% commission by default)
        $status = $orderData['OrderStatus'] ?? '';
        if (in_array($status, ['Shipped', 'InvoiceUnconfirmed']) && $taxaPlataforma <= 0) {
            // Apply 12% fallback for Amazon.com.br standard commission
            $taxaPlataforma = $valorProdutos * 0.12; 
            Log::info("Amazon: Aplicando fallback de taxa (12%) para pedido {$amazonOrderId}");
        }

        // If it is EasyShip (Std EZ National) and we have no shipping charge, 
        // we might need to estimate or look at MFN fees.
        // For now, if it is Shipped and valor_frete is 0, we check if there's a reason.
        
        // --- END FALLBACK LOGIC ---

        // Se o valorTotal for 0 (pedidos pendentes), usamos a soma dos itens
        if ($valorTotal <= 0) {
            $valorTotal = $valorProdutos + $valorFrete;
        }

        return [
            'valor_total' => $valorTotal,
            'valor_produtos' => $valorProdutos,
            'valor_frete' => $valorFrete,
            'valor_taxa_platform' => $taxaPlataforma,
        ];
    }

    protected function cadastrarCliente(MarketplacePedido $pedido, array $orderData, array $shippingAddress)
    {
        $nome = $orderData['BuyerInfo']['BuyerName'] ?? $shippingAddress['Name'] ?? 'Cliente Amazon';
        
        $cliente = Cliente::updateOrCreate(
            [
                'empresa_id' => $this->empresaId,
                'documento' => $orderData['AmazonOrderId'], // Amazon não envia CPF fácil
            ],
            [
                'nome' => $nome,
                'email' => $orderData['BuyerInfo']['BuyerEmail'] ?? null,
                'telefone' => $shippingAddress['Phone'] ?? null,
                'cidade' => $shippingAddress['City'] ?? null,
                'estado' => $shippingAddress['StateOrRegion'] ?? null,
                'cep' => $shippingAddress['PostalCode'] ?? null,
                'endereco' => ($shippingAddress['AddressLine1'] ?? '') . ' ' . ($shippingAddress['AddressLine2'] ?? ''),
                'numero' => 'S/N',
            ]
        );

        $pedido->update(['cliente_id' => $cliente->id]);
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
     * Fetch financial events for a specific order via the Finances API.
     * Returns detailed fee breakdowns (commission, FBA fees, shipping charges, etc.)
     * Note: Financial data may take up to 48h to become available after order creation.
     */
    public function getFinancialEvents(string $amazonOrderId): array
    {
        $connector = $this->getConnector();
        
        if (!$connector) {
            return ['error' => 'Credenciais não configuradas'];
        }

        try {
            $financesApi = new FinancesApi($connector);
            $response = $financesApi->listFinancialEventsByOrderId($amazonOrderId);
            
            return json_decode($response->body(), true);
        } catch (\Exception $e) {
            Log::warning("Amazon: Erro ao buscar financeiro do pedido {$amazonOrderId} - " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Sync Amazon listings using Reports API (GET_MERCHANT_LISTINGS_DATA)
     */
    public function syncListings(): array
    {
        $connector = $this->getConnector();
        
        if (!$connector) {
            return ['success' => false, 'message' => 'Credenciais não configuradas'];
        }

        try {
            $reportsApi = new ReportsApi($connector);
            
            Log::info("Amazon: Iniciando sincronização de anúncios via Reports API");

            // 1. Criar o relatório
            $body = new CreateReportSpecification(
                reportType: 'GET_MERCHANT_LISTINGS_DATA',
                marketplaceIds: [Marketplace::BR->value]
            );
            
            $reportResponse = $reportsApi->createReport($body);
            $reportData = json_decode($reportResponse->body(), true);
            $reportId = $reportData['reportId'] ?? null;

            if (!$reportId) {
                return ['success' => false, 'message' => 'Não foi possível criar o relatório na Amazon'];
            }

            // 2. Aguardar o processamento (máximo 60 segundos para sync síncrono)
            $report = $this->waitForReport($reportsApi, $reportId);
            
            if ($report['processingStatus'] !== 'DONE') {
                return [
                    'success' => false, 
                    'message' => 'O relatório da Amazon ainda está sendo processado. Tente novamente em alguns minutos. (Status: ' . $report['processingStatus'] . ')'
                ];
            }

            $documentId = $report['reportDocumentId'] ?? null;
            if (!$documentId) {
                return ['success' => false, 'message' => 'ID do documento não encontrado no relatório'];
            }

            // 3. Buscar o documento e baixar os dados
            $docResponse = $reportsApi->getReportDocument($documentId, 'GET_MERCHANT_LISTINGS_DATA');
            $docData = json_decode($docResponse->body(), true);
            $url = $docData['url'] ?? null;

            if (!$url) {
                return ['success' => false, 'message' => 'URL de download do relatório não encontrada'];
            }

            // A Amazon agora compacta alguns relatórios ou envia direto
            $content = Http::get($url)->body();
            
            // 4. Processar o conteúdo (TSV)
            return $this->processListingsReport($content);

        } catch (\Exception $e) {
            Log::error("Amazon: Erro ao sincronizar anúncios - " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function waitForReport(ReportsApi $api, string $reportId, int $maxAttempts = 12): array
    {
        $attempts = 0;
        while ($attempts < $maxAttempts) {
            $response = $api->getReport($reportId);
            $data = json_decode($response->body(), true);
            
            Log::info("Amazon: Check report {$reportId} - Status: " . $data['processingStatus']);
            
            if (in_array($data['processingStatus'], ['DONE', 'CANCELLED', 'FATAL'])) {
                return $data;
            }
            
            $attempts++;
            sleep(5); // Espera 5 segundos entre tentativas
        }
        
        return ['processingStatus' => 'IN_PROGRESS'];
    }

    protected function processListingsReport(string $content): array
    {
        $encoding = mb_detect_encoding($content, 'UTF-8, ISO-8859-1, ASCII', true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $lines = explode("\n", str_replace("\r", "", $content));
        if (count($lines) < 2) {
            return ['success' => false, 'message' => 'Relatório vazio ou inválido'];
        }

        $header = explode("\t", array_shift($lines));
        $count = 0;
        $asinsBySku = [];

        $integracao = Integracao::where('empresa_id', $this->empresaId)
            ->where('marketplace', 'amazon')
            ->first();

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $row = explode("\t", $line);
            $data = array_combine(array_intersect_key($header, $row), array_intersect_key($row, $header));
            
            $sku = $data['seller-sku'] ?? null;
            $asin = $data['asin1'] ?? null;
            
            if (!$sku) continue;

            $itemData = [
                'integracao_id' => $integracao->id ?? null,
                'external_id' => $asin ?? $sku,
                'titulo' => $data['item-name'] ?? $sku,
                'status' => 'active',
                'preco' => floatval($data['price'] ?? 0),
                'estoque' => intval($data['quantity'] ?? 0),
                'json_data' => $data,
            ];

            MarketplaceAnuncio::updateOrCreate(
                [
                    'empresa_id' => $this->empresaId,
                    'marketplace' => 'amazon',
                    'sku' => $sku,
                ],
                $itemData
            );

            if ($asin) {
                $asinsBySku[$sku] = $asin;
            }

            $count++;
        }

        // 5. Buscar imagens se houver ASINs (em lotes de 20)
        if (!empty($asinsBySku)) {
            $this->updateThumbnailsBatched($asinsBySku);
        }

        return ['success' => true, 'message' => "{$count} anúncios sincronizados com sucesso via Report API", 'count' => $count];
    }

    public function updateThumbnailsBatched(array $asinsBySku): void
    {
        $connector = $this->getConnector();
        if (!$connector) return;

        $catalogApi = new CatalogApi($connector);
        $asins = array_values($asinsBySku);
        $chunks = array_chunk($asins, 20);

        foreach ($chunks as $chunk) {
            try {
                $response = $catalogApi->searchCatalogItems(
                    marketplaceIds: [Marketplace::BR->value],
                    identifiers: $chunk,
                    identifiersType: 'ASIN',
                    includedData: ['summaries', 'images'],
                    pageSize: 20
                );

                if ($response->ok()) {
                    $data = $response->json();
                    $items = $data['items'] ?? [];

                    foreach ($items as $item) {
                        $asin = $item['asin'] ?? null;
                        $image = null;

                        // Tentar buscar das imagens (dataset 'images')
                        $imagesData = $item['images'] ?? [];
                        if (!empty($imagesData)) {
                            // Marketplace level
                            foreach ($imagesData as $mktImages) {
                                if (!empty($mktImages['images'])) {
                                    foreach ($mktImages['images'] as $img) {
                                        if (($img['variant'] ?? '') === 'MAIN') {
                                            $image = $img['link'] ?? null;
                                            break 2;
                                        }
                                        // Fallback para a primeira se não achar MAIN
                                        if (!$image) $image = $img['link'] ?? null;
                                    }
                                }
                            }
                        }

                        // Fallback para summaries (dataset 'summaries')
                        if (!$image && !empty($item['summaries'])) {
                            $image = $item['summaries'][0]['mainImage']['link'] ?? null;
                        }

                        if ($asin && $image) {
                            // Encontrar todos os anúncios com este ASIN (um ASIN pode ter múltiplos SKUs/anúncios)
                            MarketplaceAnuncio::where('empresa_id', $this->empresaId)
                                ->where('marketplace', 'amazon')
                                ->where('external_id', $asin)
                                ->update(['thumbnail' => $image]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Amazon: Erro ao buscar imagens do catálogo - " . $e->getMessage());
            }
        }
    }

    /**
     * Reposição automática de estoque para pedidos cancelados
     */
    protected function reporEstoqueAutomatico(MarketplacePedido $pedido, array $orderItems): void
    {
        try {
            // Obter tipo de logística do pedido (FulfillmentChannel)
            $logisticType = $pedido->json_data['FulfillmentChannel'] ?? 'MFN';

            // Determinar depósito baseado na logística
            $depositoId = $this->getDepositoPorLogistica($logisticType, $pedido->empresa_id);

            if (! $depositoId) {
                Log::warning("Amazon Estoque (Reposição): Depósito não encontrado para logística: {$logisticType}");

                return;
            }

            $estoqueService = app(\App\Services\EstoqueMovimentacaoService::class);
            $reposicoes = 0;

            foreach ($orderItems as $item) {
                $sku = $item['SellerSKU'] ?? null;
                if (! $sku) {
                    continue;
                }

                // Buscar anúncio para obter o produto_id
                $anuncio = \App\Models\MarketplaceAnuncio::where('sku', $sku)
                    ->where('empresa_id', $pedido->empresa_id)
                    ->where('marketplace', 'amazon')
                    ->first();

                if (! $anuncio || ! $anuncio->produto_id) {
                    Log::warning("Amazon Estoque (Reposição): Anúncio ou Produto não encontrado para SKU {$sku}");

                    continue;
                }

                // Buscar SKU principal do produto
                $productSku = \App\Models\ProductSku::where('product_id', $anuncio->produto_id)->first();
                if (! $productSku) {
                    continue;
                }

                $quantidade = intval($item['QuantityOrdered'] ?? 1);

                // Tentar repor
                $result = $estoqueService->reporEstoquePorCancelamento(
                    $pedido,
                    $productSku->id,
                    $quantidade,
                    $depositoId
                );

                if ($result) {
                    $reposicoes++;
                    Log::info("Amazon Estoque (Reposição): Reposto {$quantidade} do SKU {$sku} no depósito {$depositoId}");
                }
            }

            Log::info("Amazon Estoque (Reposição): Pedido {$pedido->pedido_id} - {$reposicoes} reposições realizadas");

        } catch (\Exception $e) {
            Log::error('Amazon Estoque (Reposição): Erro ao repor estoque automaticamente: '.$e->getMessage());
        }
    }

    protected function getDepositoPorLogistica(?string $fulfillmentChannel, int $empresaId): ?int
    {
        $tipoDeposito = ($fulfillmentChannel === 'AFN') ? 'full' : 'loja';

        $deposito = \App\Models\Deposito::where('empresa_id', $empresaId)
            ->where('tipo', $tipoDeposito)
            ->where('ativo', true)
            ->first();

        return $deposito?->id;
    }
}
