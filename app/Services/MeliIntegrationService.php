<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Integracao;
use App\Models\MarketplacePedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeliIntegrationService
{
    protected ?int $empresaId = null;

    protected ?Integracao $integracao = null;

    public function __construct(?int $empresaId = null)
    {
        $this->empresaId = $empresaId;
    }

    public function getIntegracao(): ?Integracao
    {
        if ($this->integracao) {
            return $this->integracao;
        }

        $this->integracao = Integracao::where('empresa_id', $this->empresaId)
            ->where('marketplace', 'mercadolivre')
            ->where('ativo', true)
            ->first();

        return $this->integracao;
    }

    public function isConnected(): bool
    {
        $integracao = $this->getIntegracao();

        return $integracao && ! empty($integracao->access_token);
    }

    public function updateNome(string $nome): bool
    {
        $integracao = $this->getIntegracao();

        if (! $integracao) {
            return false;
        }

        try {
            $integracao->update(['nome_conta' => $nome]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar nome do Meli: '.$e->getMessage());

            return false;
        }
    }

    protected function getAccessToken(): ?string
    {
        $integracao = $this->getIntegracao();

        if (! $integracao) {
            return null;
        }

        if ($integracao->expires_at && $integracao->expires_at->isPast()) {
            $this->refreshToken();
            $integracao->refresh();
        }

        return $integracao->access_token;
    }

    public function refreshToken(): bool
    {
        $integracao = $this->getIntegracao();

        if (! $integracao || ! $integracao->refresh_token) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://api.mercadolibre.com/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => config('services.meli.app_id'),
                'client_secret' => config('services.meli.app_secret'),
                'refresh_token' => $integracao->refresh_token,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $integracao->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_at' => now()->addSeconds($data['expires_in']),
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Meli refresh token error: '.$e->getMessage());

            return false;
        }
    }

    public function getOrders(array $params = []): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return ['error' => 'Meli não conectado'];
        }

        $integracao = $this->getIntegracao();

        if (! $integracao || ! $integracao->external_user_id) {
            return ['error' => 'ID do usuário Meli não encontrado'];
        }

        try {
            $sellerId = $integracao->external_user_id;

            Log::info('Meli getOrders - seller: '.$sellerId.' params: '.json_encode($params));

            $url = 'https://api.mercadolibre.com/orders/search';
            $queryParams = array_merge([
                'seller' => $sellerId,
            ], $params);

            Log::info('Meli getOrders - URL: '.$url.'?'.http_build_query($queryParams));

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get($url, $queryParams);

            Log::info('Meli getOrders - Status: '.$response->status());
            Log::info('Meli getOrders - Response: '.$response->body());

            if ($response->successful()) {
                return $response->json();
            }

            // Retry if token expired prematurely
            if ($response->status() === 401) {
                Log::warning('Meli getOrders - 401 Unauthorized. Tentando refreshToken...');
                if ($this->refreshToken()) {
                    $integracao->refresh();
                    $token = $integracao->access_token;
                    
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer '.$token,
                    ])->get($url, $queryParams);
                    
                    if ($response->successful()) {
                        Log::info('Meli getOrders - Retry bem sucedido após refreshToken.');
                        return $response->json();
                    }
                }
            }

            Log::error('Meli getOrders error: '.$response->body());

            return ['error' => 'Erro ao buscar pedidos: '.$response->status()];
        } catch (\Exception $e) {
            Log::error('Meli getOrders exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function getOrderDetail(string $orderId): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return ['error' => 'Meli não conectado'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/orders/{$orderId}");

            if ($response->successful()) {
                return $response->json();
            }

            if ($response->status() === 401) {
                if ($this->refreshToken()) {
                    $integracao = $this->getIntegracao();
                    $integracao->refresh();
                    $token = $integracao->access_token;
                    
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer '.$token,
                    ])->get("https://api.mercadolibre.com/orders/{$orderId}");
                    
                    if ($response->successful()) {
                        return $response->json();
                    }
                }
            }

            return ['error' => $response->json() ?? 'Erro ao buscar pedido'];
        } catch (\Exception $e) {
            Log::error('Meli getOrderDetail exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function getItemDetails(string $itemId): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return ['error' => 'Meli não conectado'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/items/{$itemId}");

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->json() ?? 'Erro ao buscar item'];
        } catch (\Exception $e) {
            Log::error('Meli getItemDetails exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function getOrderBillingInfo(string $orderId): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return ['error' => 'Meli não conectado'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'x-version' => '2',
            ])->get("https://api.mercadolibre.com/orders/{$orderId}/billing_info");

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->json() ?? 'Erro ao buscar billing info'];
        } catch (\Exception $e) {
            Log::error('Meli getOrderBillingInfo exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function refreshOrder(int $orderId): array
    {
        if (! $this->isConnected()) {
            return ['success' => false, 'message' => 'Meli não conectado'];
        }

        try {
            $pedido = MarketplacePedido::find($orderId);

            if (! $pedido) {
                return ['success' => false, 'message' => 'Pedido não encontrado'];
            }

            Log::info("Meli refreshOrder - atualizando pedido ID: {$orderId}, ML ID: {$pedido->pedido_id}");

            $this->processImport(['id' => $pedido->pedido_id], $pedido->import_hash ?? md5($this->empresaId.'_'.$pedido->pedido_id));

            Log::info("Meli refreshOrder - pedido {$pedido->pedido_id} atualizado com sucesso");

            return ['success' => true, 'message' => 'Pedido atualizado com sucesso'];
        } catch (\Exception $e) {
            Log::error('Meli refreshOrder exception: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getBillingInfo(string $orderId): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return ['error' => 'Meli não conectado'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'x-version' => '2',
            ])->get("https://api.mercadolibre.com/orders/{$orderId}/billing_info");

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->json() ?? 'Erro ao buscar billing info'];
        } catch (\Exception $e) {
            Log::error('Meli getBillingInfo exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function getFiscalDocuments(string $packId): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return ['error' => 'Meli não conectado'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/packs/{$packId}/fiscal_documents");

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->json() ?? 'Erro ao buscar fiscal documents'];
        } catch (\Exception $e) {
            Log::error('Meli getFiscalDocuments exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function downloadFiscalDocument(string $packId, string $documentId): ?string
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/packs/{$packId}/fiscal_documents/{$documentId}");

            if ($response->successful()) {
                return $response->body();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Meli downloadFiscalDocument exception: '.$e->getMessage());

            return null;
        }
    }

    public function syncOrders(int $limit = 50): array
    {
        if (! $this->isConnected()) {
            return ['success' => false, 'message' => 'Meli não conectado'];
        }

        try {
            $imported = 0;
            $page = 0;
            $maxPages = 5;
            $maxOrders = 250;

            Log::info('Meli syncOrders started - maxPages: '.$maxPages.' maxOrders: '.$maxOrders);

            while ($page < $maxPages && $imported < $maxOrders) {
                $offset = $page * $limit;

                Log::info('Meli syncOrders - fetching page '.$page.' offset '.$offset);

                $result = $this->getOrders([
                    'limit' => $limit,
                    'offset' => $offset,
                    'sort' => 'date_desc',
                ]);

                if (isset($result['error'])) {
                    Log::error('Meli syncOrders - error: '.$result['error']);

                    return ['success' => false, 'message' => $result['error']];
                }

                $orders = $result['results'] ?? [];

                Log::info('Meli syncOrders - got '.count($orders).' orders on page '.$page);

                if (empty($orders)) {
                    break;
                }

                foreach ($orders as $order) {
                    $this->importOrder($order);
                    $imported++;
                }

                if (count($orders) < $limit) {
                    break;
                }

                $page++;
            }

            Log::info('Meli syncOrders completed - imported: '.$imported);

            return ['success' => true, 'message' => "{$imported} pedidos sincronizados"];
        } catch (\Exception $e) {
            Log::error('Meli syncOrders exception: '.$e->getMessage().' - '.$e->getTraceAsString());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function importOrder(array $orderData): MarketplacePedido
    {
        $integracao = $this->getIntegracao();

        // Gerar hash único para evitar duplicatas
        $importHash = md5($integracao->id.'_'.$orderData['id'].'_'.($orderData['date_created'] ?? time()));

        // Verificar se já foi importado (confirmação)
        $existingByHash = MarketplacePedido::where('import_hash', $importHash)
            ->where('import_confirmed', true)
            ->first();

        if ($existingByHash) {
            Log::info("Pedido {$orderData['id']} já importado com hash {$importHash}, atualizando apenas status");

            // Apenas atualiza status
            return $this->updateOrderStatus($existingByHash, $orderData);
        }

        try {
            return $this->processImport($orderData, $importHash);
        } catch (\Exception $e) {
            Log::error("Erro ao importar pedido {$orderData['id']}: ".$e->getMessage());

            // Tentar salvar com erro para rastreamento
            return $this->saveWithError($orderData, $importHash, $e->getMessage());
        }
    }

    protected function processImport(array $orderData, string $importHash): MarketplacePedido
    {
        $integracao = $this->getIntegracao();

        // Buscar dados detalhados do pedido para obter informações completas (nome, CPF, endereço)
        $orderId = $orderData['id'] ?? null;
        if ($orderId) {
            $detailedOrder = $this->getOrderDetail($orderId);
            if (! isset($detailedOrder['error']) && ! empty($detailedOrder)) {
                Log::info("Meli processImport - obtendo dados detalhados do pedido {$orderId}");
                $orderData = array_merge($orderData, $detailedOrder);
            }

            // Buscar informações de billing (CPF/CNPJ, endereço completo)
            $billingInfo = $this->getOrderBillingInfo($orderId);
            if (! isset($billingInfo['error']) && ! empty($billingInfo)) {
                Log::info("Meli processImport - obtendo billing info do pedido {$orderId}");
                $orderData['billing_info'] = $billingInfo;
            }
        }

        $buyer = $orderData['buyer'] ?? [];
        $shipping = $orderData['shipping'] ?? [];
        $payments = $orderData['payments'] ?? [];

        $shippingAddress = $shipping['shipping_address'] ?? $orderData['shipping_address'] ?? [];

        // Mapear status
        $statusMap = [
            'paid' => 'em_aberto',
            'pending' => 'pendente',
            'in_process' => 'em_processamento',
            'shipped' => 'enviado',
            'delivered' => 'entregue',
            'cancelled' => 'cancelado',
        ];

        $status = $statusMap[$orderData['status']] ?? $orderData['status'];

        // Verificar status do shipment
        $shippingStatus = $shipping['status'] ?? null;
        if ($shippingStatus === 'shipped' || $shippingStatus === 'delivered') {
            $status = $statusMap[$shippingStatus] ?? $shippingStatus;
        } elseif (! empty($orderData['shipping'])) {
            $shipmentId = $orderData['shipping']['id'] ?? null;
            if ($shipmentId) {
                $shipment = $this->getShipment($shipmentId);
                if (isset($shipment['status'])) {
                    $shipmentStatus = $shipment['status'];
                    if ($shipmentStatus === 'shipped' || $shipmentStatus === 'delivered') {
                        $status = $statusMap[$shipmentStatus] ?? $shipmentStatus;
                    }
                    // Adicionar dados do shipment ao orderData
                    $orderData['shipment_details'] = [
                        'mode' => $shipment['mode'] ?? null,
                        'logistics_type' => $shipment['logistics_type'] ?? null,
                        'status' => $shipment['status'] ?? null,
                        'label' => $shipment['label'] ?? null,
                        'label_pdf' => $shipment['label_pdf'] ?? null,
                        'tracking_number' => $shipment['tracking_number'] ?? null,
                        'tracking_url' => $shipment['tracking_url'] ?? null,
                        'base_cost' => floatval($shipment['base_cost'] ?? 0),
                        'cost_components' => $shipment['cost_components'] ?? [],
                        'shipping_option' => $shipment['shipping_option'] ?? [],
                    ];
                }
            }
        }

        // Calcular taxas e valores base
        $valorTotal = floatval($orderData['total_amount'] ?? 0);
        
        // Buscar custo real do frete via endpoint /shipments/{id}/costs
        $valorFrete = 0;
        $shipmentId = $orderData['shipping']['id'] ?? null;
        if ($shipmentId) {
            $shippingCosts = $this->getShipmentCosts($shipmentId);
            $valorFrete = floatval($shippingCosts['sender_cost'] ?? 0);
        }
        // Fallback: usar shipping_cost do order se a API de costs não retornou nada
        if ($valorFrete <= 0 && !empty($orderData['shipping_cost'])) {
            $valorFrete = floatval($orderData['shipping_cost']);
        }
        
        $valorProdutos = 0;
        if (!empty($orderData['order_items'])) {
            foreach ($orderData['order_items'] as $item) {
                $valorProdutos += floatval($item['unit_price'] ?? 0) * intval($item['quantity'] ?? 1);
            }
        }
        
        $valorDesconto = floatval($orderData['discounts'] ?? 0);

        // EXTRAÇÃO DE TARIFAS REAIS (Mercado Livre Sale Fee)
        // O ML embute os subsídios (rebates) reduzindo a sale_fee nativa. Não usamos mais 12% fixo.
        $taxaPlatform = 0;
        if (!empty($orderData['order_items'])) {
            foreach ($orderData['order_items'] as $item) {
                if (isset($item['sale_fee'])) {
                    $taxaPlatform += floatval($item['sale_fee']);
                }
            }
        }
        
        // Fallback apenas se o ML estranhamente não enviar a tarifa (muito raro)
        if ($taxaPlatform == 0 && $valorTotal > 0) {
            $taxaPlatform = $valorTotal * 0.12; 
        }

        // No Mercado Livre, a taxa de pagamento do Mercado Pago já está embutida na tarifa de venda (sale_fee).
        $taxaPagamento = 0;
        
        // Sum any official Taxes from ML Payload
        $valorImposto = 0;
        if (!empty($orderData['taxes']) && is_array($orderData['taxes'])) {
            $valorImposto = collect($orderData['taxes'])->sum('amount');
        }

        // O valor_liquido definitivo usa a tarifa exata recebida.
        $valorLiquido = $valorTotal - $taxaPlatform - $taxaPagamento - $valorFrete - $valorImposto;

        // Buscar thumbnails dos produtos
        $orderItems = $orderData['order_items'] ?? [];
        foreach ($orderItems as &$item) {
            $itemId = $item['item']['id'] ?? null;
            if ($itemId) {
                $itemDetails = $this->getItemDetails($itemId);
                if (! isset($itemDetails['error']) && ! empty($itemDetails)) {
                    $item['item']['thumbnail'] = $itemDetails['thumbnail'] ?? null;
                    $item['item']['picture'] = $itemDetails['pictures'][0]['url'] ?? $itemDetails['thumbnail'] ?? null;
                }
            }
        }
        $orderData['order_items'] = $orderItems;

        // Buscar informações de billing (CPF/CNPJ, endereço completo)
        $billingInfo = $orderData['billing_info'] ?? [];
        $billingBuyer = $billingInfo['buyer']['billing_info'] ?? [];
        $billingAddress = $billingBuyer['address'] ?? [];
        $identification = $billingBuyer['identification'] ?? [];

        // Determinar se é CPF ou CNPJ
        $docType = $identification['type'] ?? '';
        $docNumber = $identification['number'] ?? null;

        $compradorNome = $billingBuyer['name'] ?? ($buyer['first_name'] ?? '');
        $compradorSobrenome = $billingBuyer['last_name'] ?? ($buyer['last_name'] ?? '');

        // Endereço do billing
        $enderecoRua = $billingAddress['street_name'] ?? '';
        $enderecoNumero = $billingAddress['street_number'] ?? '';
        $enderecoCidade = $billingAddress['city_name'] ?? '';
        $enderecoEstado = isset($billingAddress['state']) ? ($billingAddress['state']['name'] ?? $billingAddress['state']) : '';
        $enderecoCep = $billingAddress['zip_code'] ?? '';

        $pedido = MarketplacePedido::updateOrCreate(
            [
                'empresa_id' => $this->empresaId,
                'integracao_id' => $integracao->id,
                'pedido_id' => $orderData['id'],
            ],
            [
                'marketplace' => 'mercadolivre',
                'external_id' => $orderData['external_id'] ?? null,
                'status' => $status,
                'status_pagamento' => $status,
                'status_envio' => $shipping['status'] ?? null,
                'comprador_nome' => trim($compradorNome.' '.$compradorSobrenome),
                'comprador_email' => $buyer['email'] ?? null,
                'comprador_cpf' => ($docType === 'CPF' || $docType === 'DNI' || $docType === 'NIRE' || $docType === 'CUIT') ? $docNumber : null,
                'comprador_cnpj' => ($docType === 'CNPJ' || $docType === 'JDE') ? $docNumber : null,
                'telefone' => $buyer['phone'] ?? null,
                'endereco' => $enderecoRua.($enderecoNumero ? ', '.$enderecoNumero : ''),
                'cidade' => $enderecoCidade ?: (isset($shippingAddress['city']) ? ($shippingAddress['city']['name'] ?? $shippingAddress['city']) : null),
                'estado' => $enderecoEstado ?: (isset($shippingAddress['state']) ? ($shippingAddress['state']['name'] ?? $shippingAddress['state']) : null),
                'cep' => $enderecoCep ?: ($shippingAddress['zip_code'] ?? null),
                'valor_total' => $valorTotal,
                'valor_frete' => $valorFrete,
                'valor_desconto' => $valorDesconto,
                'valor_produtos' => $valorProdutos,
                'valor_taxa_platform' => round($taxaPlatform, 2),
                'valor_taxa_pagamento' => round($taxaPagamento, 2),
                'valor_imposto' => round($valorImposto, 2),
                'valor_liquido' => round($valorLiquido, 2),
                'data_compra' => isset($orderData['date_created']) ? \Carbon\Carbon::parse($orderData['date_created']) : null,
                'data_pagamento' => isset($orderData['date_closed']) ? \Carbon\Carbon::parse($orderData['date_closed']) : null,
                'codigo_rastreamento' => $shipping['tracking_number'] ?? null,
                'url_rastreamento' => $shipping['tracking_url'] ?? null,
                'json_data' => $orderData,
                'import_hash' => $importHash,
                'import_confirmed' => true,
                'imported_at' => now(),
                'last_status_update' => now(),
                'import_error' => null,
            ]
        );

        // Cadastrar cliente
        $this->cadastrarCliente($pedido, $buyer, $shippingAddress, $orderData);

        // Baixa automática de estoque
        $this->baixaEstoqueAutomatica($pedido, $orderData);

        Log::info("Pedido {$orderData['id']} importado com sucesso. Hash: {$importHash}");

        return $pedido;
    }

    protected function updateOrderStatus(MarketplacePedido $pedido, array $orderData): MarketplacePedido
    {
        $statusMap = [
            'paid' => 'em_aberto',
            'pending' => 'pendente',
            'in_process' => 'em_processamento',
            'shipped' => 'enviado',
            'delivered' => 'entregue',
            'cancelled' => 'cancelado',
        ];

        $newStatus = $statusMap[$orderData['status']] ?? $orderData['status'];

        // Verificar se status mudou
        if ($pedido->status !== $newStatus) {
            Log::info("Pedido {$pedido->pedido_id} status alterado: {$pedido->status} -> {$newStatus}");
        }

        // Recalcular valor_produtos se estiver zerado (ML não manda item_price no topo)
        $valorProdutos = $pedido->valor_produtos;
        if ($valorProdutos <= 0 && !empty($orderData['order_items'])) {
            $valorProdutos = 0;
            foreach ($orderData['order_items'] as $item) {
                $valorProdutos += floatval($item['unit_price'] ?? 0) * intval($item['quantity'] ?? 1);
            }
        }

        $existingJsonData = is_string($pedido->json_data) ? json_decode($pedido->json_data, true) : ($pedido->json_data ?? []);
        
        $mergedJsonData = $existingJsonData;
        
        // Let's replace only top-level primitives from the shallow API hit, leaving deep structures like billing_info and shipment_details untouched
        foreach ($orderData as $key => $value) {
            if (!is_array($value)) {
                $mergedJsonData[$key] = $value;
            } elseif (in_array($key, ['shipping', 'payments', 'order_items', 'feedback'])) {
                 // overwrite arrays if they came in fuller or differently
                 $mergedJsonData[$key] = $value;
            }
        }
        
        // Preserve billing_info and shipment_details explicitly just in case orderData came with them but empty
        if (isset($existingJsonData['billing_info']) && empty($orderData['billing_info'])) {
            $mergedJsonData['billing_info'] = $existingJsonData['billing_info'];
        }
        if (isset($existingJsonData['shipment_details']) && empty($orderData['shipment_details'])) {
            $mergedJsonData['shipment_details'] = $existingJsonData['shipment_details'];
        }
        if (isset($existingJsonData['buyer']) && !empty($existingJsonData['buyer']['first_name']) && empty($orderData['buyer']['first_name'])) {
            // Keep full buyer info if the new one is just a stub
            $mergedJsonData['buyer'] = $existingJsonData['buyer'];
        }

        $pedido->update([
            'status' => $newStatus,
            'status_pagamento' => $newStatus,
            'status_envio' => $mergedJsonData['shipping']['status'] ?? null,
            'codigo_rastreamento' => $mergedJsonData['shipping']['tracking_number'] ?? $pedido->codigo_rastreamento,
            'url_rastreamento' => $mergedJsonData['shipping']['tracking_url'] ?? $pedido->url_rastreamento,
            'valor_produtos' => $valorProdutos,
            'json_data' => $mergedJsonData,
            'last_status_update' => now(),
        ]);

        return $pedido;
    }

    protected function saveWithError(array $orderData, string $importHash, string $errorMessage): MarketplacePedido
    {
        $integracao = $this->getIntegracao();

        Log::error("Salvando pedido com erro: {$orderData['id']} - {$errorMessage}");

        $pedido = MarketplacePedido::updateOrCreate(
            [
                'empresa_id' => $this->empresaId,
                'integracao_id' => $integracao->id,
                'pedido_id' => $orderData['id'],
            ],
            [
                'marketplace' => 'mercadolivre',
                'status' => 'erro_importacao',
                'json_data' => $orderData,
                'import_hash' => $importHash,
                'import_confirmed' => false,
                'import_error' => $errorMessage,
                'imported_at' => now(),
            ]
        );

        // Enviar notificação de erro (pode implementar depois com Slack/Email)

        return $pedido;
    }

    protected function cadastrarCliente(MarketplacePedido $pedido, array $buyer, array $shippingAddress, array $orderData): ?Cliente
    {
        $cpfCnpj = isset($buyer['billing_info']) ? ($buyer['billing_info']['doc_number'] ?? null) : null;
        if (! $cpfCnpj) {
            return null;
        }

        $cpfCnpj = preg_replace('/[^0-9]/', '', $cpfCnpj);

        $nomeCompleto = ($buyer['first_name'] ?? '').' '.($buyer['last_name'] ?? '');

        $tipo = strlen($cpfCnpj) > 11 ? 'pj' : 'pf';

        $endereco = ($shippingAddress['address_line'] ?? '').', '.($shippingAddress['street_number'] ?? '');
        $cidade = isset($shippingAddress['city']) ? ($shippingAddress['city']['name'] ?? $shippingAddress['city']) : null;
        $estado = isset($shippingAddress['state']) ? ($shippingAddress['state']['name'] ?? $shippingAddress['state']) : null;
        $cep = isset($shippingAddress['zip_code']) ? preg_replace('/[^0-9]/', '', $shippingAddress['zip_code']) : null;

        $complemento = $shippingAddress['comment'] ?? '';
        $bairro = $shippingAddress['neighborhood'] ?? '';

        $telefone = '';
        if (isset($buyer['phone']['area_code']) && isset($buyer['phone']['number'])) {
            $telefone = $buyer['phone']['area_code'].$buyer['phone']['number'];
        }

        $cliente = Cliente::updateOrCreate(
            [
                'empresa_id' => $this->empresaId,
                'cpf_cnpj' => $cpfCnpj,
            ],
            [
                'nome' => $nomeCompleto,
                'tipo' => $tipo,
                'email' => $buyer['email'] ?? null,
                'telefone' => $telefone,
                'endereco' => $endereco,
                'bairro' => $bairro,
                'cidade' => $cidade,
                'estado' => $estado,
                'cep' => $cep,
                'complemento' => $complemento,
                'numero' => $shippingAddress['street_number'] ?? null,
            ]
        );

        $pedido->update(['cliente_id' => $cliente->id]);

        return $cliente;
    }

    public function getShipment(string $shipmentId): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return ['error' => 'Meli não conectado'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/shipments/{$shipmentId}");

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->json() ?? 'Erro ao buscar shipment'];
        } catch (\Exception $e) {
            Log::error('Meli getShipment exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Busca os custos detalhados do frete via endpoint /shipments/{id}/costs
     * Retorna o custo real que o vendedor paga pelo frete.
     */
    public function getShipmentCosts(string $shipmentId): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return ['sender_cost' => 0, 'gross_amount' => 0];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/shipments/{$shipmentId}/costs");

            if ($response->successful()) {
                $data = $response->json();
                $senderCost = 0;
                
                if (!empty($data['senders'])) {
                    foreach ($data['senders'] as $sender) {
                        $senderCost += floatval($sender['cost'] ?? 0);
                    }
                }

                return [
                    'sender_cost' => $senderCost,
                    'gross_amount' => floatval($data['gross_amount'] ?? 0),
                    'raw' => $data,
                ];
            }

            return ['sender_cost' => 0, 'gross_amount' => 0];
        } catch (\Exception $e) {
            Log::error('Meli getShipmentCosts exception: '.$e->getMessage());
            return ['sender_cost' => 0, 'gross_amount' => 0];
        }
    }

    public function registerWebhooks(): array
    {
        $token = $this->getAccessToken();
        $integracao = $this->getIntegracao();

        if (! $token || ! $integracao) {
            return ['error' => 'Meli não conectado'];
        }

        $appId = config('services.meli.app_id');
        $callbackUrl = config('services.meli.notification_url');
        $userId = $integracao->external_user_id;

        $topics = [
            'orders_v2',
            'shipments',
            'inventory',
        ];

        $results = [];

        foreach ($topics as $topic) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ])->post("https://api.mercadolibre.com/applications/{$appId}/webhooks", [
                    'url' => $callbackUrl,
                    'topic' => $topic,
                    'user_id' => $userId,
                ]);

                $responseBody = $response->json();

                if ($response->successful() || isset($responseBody['id'])) {
                    $results[$topic] = ['success' => true, 'data' => $responseBody];
                    Log::info("Meli: Webhook registrado para topic {$topic}");
                } else {
                    $results[$topic] = ['success' => false, 'error' => $responseBody];
                    Log::error("Meli: Erro ao registrar webhook {$topic}: ".json_encode($responseBody));
                }
            } catch (\Exception $e) {
                $results[$topic] = ['success' => false, 'error' => $e->getMessage()];
                Log::error("Meli: Exception ao registrar webhook {$topic}: ".$e->getMessage());
            }
        }

        return $results;
    }

    public function listWebhooks(): array
    {
        $token = $this->getAccessToken();
        $integracao = $this->getIntegracao();

        if (! $token || ! $integracao) {
            return ['error' => 'Meli não conectado'];
        }

        $appId = config('services.meli.app_id');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/applications/{$appId}/webhooks");

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->json() ?? 'Erro ao listar webhooks'];
        } catch (\Exception $e) {
            Log::error('Meli listWebhooks exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function deleteWebhook(int $webhookId): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return ['error' => 'Meli não conectado'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->delete("https://api.mercadolibre.com/applications/webhooks/{$webhookId}");

            if ($response->successful()) {
                return ['success' => true];
            }

            return ['error' => $response->json() ?? 'Erro ao deletar webhook'];
        } catch (\Exception $e) {
            Log::error('Meli deleteWebhook exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function updateAnuncioStatus(string $itemId, bool $active): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return ['success' => false, 'error' => 'Meli não conectado'];
        }

        try {
            $status = $active ? 'active' : 'paused';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->put("https://api.mercadolibre.com/items/{$itemId}", [
                'status' => $status,
            ]);

            if ($response->successful()) {
                Log::info("Meli: Anúncio {$itemId} alterado para {$status}");

                return ['success' => true, 'data' => $response->json()];
            }

            $error = $response->json();
            Log::error("Meli: Erro ao atualizar status do anúncio {$itemId}: ".json_encode($error));

            return ['success' => false, 'error' => $error['message'] ?? 'Erro ao atualizar status'];
        } catch (\Exception $e) {
            Log::error('Meli updateAnuncioStatus exception: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Atualiza título e/ou preço de um anúncio
     */
    public function atualizarAnuncio(string $itemId, array $dados): array
    {
        $this->refreshToken();

        $token = $this->getAccessToken();

        if (! $token) {
            return ['success' => false, 'error' => 'Meli não conectado'];
        }

        try {
            $payload = [];

            if (isset($dados['title'])) {
                $payload['title'] = $dados['title'];
            }
            if (isset($dados['price'])) {
                $payload['price'] = floatval($dados['price']);
            }
            if (isset($dados['sku'])) {
                $payload['seller_custom_field'] = $dados['sku'];
            }

            if (empty($payload)) {
                return ['success' => false, 'error' => 'Nenhum dado para atualizar'];
            }

            Log::info("Meli: Tentando atualizar item {$itemId} com payload: ".json_encode($payload));

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->put("https://api.mercadolibre.com/items/{$itemId}", $payload);

            if ($response->successful()) {
                Log::info("Meli: Anúncio {$itemId} atualizado com ".json_encode($payload));

                return ['success' => true, 'data' => $response->json()];
            }

            $error = $response->json();
            Log::error("Meli: Erro ao atualizar anúncio {$itemId}: ".json_encode($error));

            return ['success' => false, 'error' => $error['message'] ?? 'Erro ao atualizar'];
        } catch (\Exception $e) {
            Log::error('Meli atualizarAnuncio exception: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtém a tabela de custos de envio do seller
     */
    public function getShippingCosts(): ?array
    {
        $token = $this->getAccessToken();
        if (! $token) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get('https://api.mercadolibre.com/shipping/pricing/by_dimensions');

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Meli getShippingCosts exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Calcula o valor estimado dofrete com base no peso/dimensões
     * Peso volumétrico = (comprimento x largura x altura) / 6000
     * Usa o maior entre peso real e volumétrico
     */
    public function calculateShippingCost(array $dimensions): ?array
    {
        $weight = floatval($dimensions['weight'] ?? 0); // em kg
        $length = floatval($dimensions['length'] ?? 0);  // em cm
        $width = floatval($dimensions['width'] ?? 0);    // em cm
        $height = floatval($dimensions['height'] ?? 0);  // em cm

        if ($weight <= 0 || $length <= 0 || $width <= 0 || $height <= 0) {
            return null;
        }

        // Converter kg para gramas
        $weightInGrams = $weight * 1000;

        // Calcular peso volumétrico (em gramas)
        $volumetricWeight = ($length * $width * $height) / 6;

        // Usar o maior peso
        $chargeableWeight = max($weightInGrams, $volumetricWeight);

        // Obter tabela de preços do seller
        $pricing = $this->getShippingCosts();

        if (! $pricing) {
            // Retornar estimation básica se não conseguir a tabela
            return [
                'weight' => $weightInGrams,
                'volumetric_weight' => $volumetricWeight,
                'chargeable_weight' => $chargeableWeight,
                'estimated_cost' => null,
                'pricing_source' => 'not_available',
            ];
        }

        // Calcular custo baseado na tabela
        $baseCost = $pricing['base_cost'] ?? 0;
        $costPerKg = $pricing['cost_per_kg'] ?? 0;
        $estimatedCost = $baseCost + ($chargeableWeight / 1000) * $costPerKg;

        return [
            'weight' => $weightInGrams,
            'volumetric_weight' => $volumetricWeight,
            'chargeable_weight' => $chargeableWeight,
            'estimated_cost' => round($estimatedCost, 2),
            'pricing_source' => 'api',
            'dimensions' => [
                'length' => $length,
                'width' => $width,
                'height' => $height,
            ],
        ];
    }

    /**
     * Obtém os custos reais de venda via API do ML
     * https://developers.mercadolivre.com.br/pt_br/comissao-por-vender
     */
    public function getListingPrices(array $params): ?array
    {
        $token = $this->getAccessToken();
        if (! $token) {
            return null;
        }

        // Parâmetros obrigatórios
        $required = ['price', 'listing_type_id'];
        foreach ($required as $param) {
            if (! isset($params[$param])) {
                Log::warning("Meli getListingPrices: parâmetro obrigatório ausente: {$param}");

                return null;
            }
        }

        // Parâmetros padrão
        $params['currency_id'] = $params['currency_id'] ?? 'BRL';

        try {
            $query = http_build_query($params);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/sites/MLB/listing_prices?{$query}");

            if ($response->successful()) {
                $data = $response->json();

                // Pode retornar array de arrays, pega o primeiro
                if (is_array($data) && isset($data[0])) {
                    return $data[0];
                }

                return $data;
            }

            Log::warning('Meli getListingPrices erro: '.$response->status());

            return null;
        } catch (\Exception $e) {
            Log::error('Meli getListingPrices exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Busca descrição do item
     */
    public function getItemDescription(string $itemId): ?string
    {
        $token = $this->getAccessToken();
        if (! $token) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/items/{$itemId}/description");

            if ($response->successful()) {
                return $response->json()['plain_text'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calcula custo de venda com base no tipo de anúncio e logística
     * Usa API do ML para obter valores Reais
     */
    public function calculateSaleFee(float $price, string $listingType, ?string $logisticType = null, ?int $billableWeight = null): ?array
    {
        $params = [
            'price' => $price,
            'listing_type_id' => $listingType,
        ];

        // Adicionar logística se informada
        if ($logisticType) {
            $params['logistic_type'] = $logisticType;
            $params['shipping_mode'] = 'me2';
        }

        if ($billableWeight) {
            $params['billable_weight'] = $billableWeight;
        }

        $pricing = $this->getListingPrices($params);

        if (! $pricing) {
            // Fallback para cálculo local
            return $this->calculateLocalFee($price, $listingType);
        }

        return [
            'sale_fee' => $pricing['sale_fee_amount'] ?? 0,
            'sale_fee_details' => $pricing['sale_fee_details'] ?? [],
            'listing_type' => $pricing['listing_type_name'] ?? $listingType,
            'source' => 'api',
        ];
    }

    /**
     * Calcula taxa local (fallback) baseado em percentuaisfixos
     */
    private function calculateLocalFee(float $price, string $listingType): array
    {
        // Percentuais base (aproximados para Brasil)
        $fees = [
            'gold_pro' => ['percentage' => 10, 'fixed' => 0],
            'gold_special' => ['percentage' => 11, 'fixed' => 0],
            'classic' => ['percentage' => 11, 'fixed' => 0],
            'premium' => ['percentage' => 15, 'fixed' => 0],
            'silver' => ['percentage' => 11, 'fixed' => 0],
            'bronze' => ['percentage' => 12, 'fixed' => 0],
            'free' => ['percentage' => 0, 'fixed' => 0],
        ];

        $fee = $fees[$listingType] ?? $fees['gold_special'];

        $percentageFee = $price * ($fee['percentage'] / 100);
        $totalFee = $percentageFee + $fee['fixed'];

        return [
            'sale_fee' => round($totalFee, 2),
            'sale_fee_details' => [
                'percentage_fee' => $fee['percentage'],
                'fixed_fee' => $fee['fixed'],
                'percentage_value' => round($percentageFee, 2),
            ],
            'listing_type' => $listingType,
            'source' => 'local',
        ];
    }

    /**
     * Baixa automática de estoque ao importar pedido
     * Baseado no tipo de logística: Full = Full, Place/Flex = Loja
     */
    protected function baixaEstoqueAutomatica(MarketplacePedido $pedido, array $orderData): void
    {
        try {
            // Só faz baixa para pedidos pagos ou em aberto
            if (! in_array($pedido->status, ['em_aberto', 'paid', 'pendente', 'pending'])) {
                Log::info("Estoque: Pedido {$pedido->pedido_id} não está pago, não fará baixa. Status: {$pedido->status}");

                return;
            }

            // Obter tipo de logística do pedido
            $shipping = $orderData['shipping'] ?? [];
            $logisticType = $shipping['logistic_type'] ?? null;

            // Determinar depósito baseado na logística
            $depositoId = $this->getDepositoPorLogistica($logisticType, $pedido->empresa_id);

            if (! $depositoId) {
                Log::warning("Estoque: Depósito não encontrado para logística: {$logisticType}");

                return;
            }

            // Obter itens do pedido
            $orderItems = $orderData['order_items'] ?? [];
            if (empty($orderItems)) {
                Log::warning("Estoque: Pedido {$pedido->pedido_id} sem itens");

                return;
            }

            $estoqueService = app(\App\Services\EstoqueMovimentacaoService::class);
            $baixas = 0;

            foreach ($orderItems as $item) {
                // Buscar SKU pelo external_id do item
                $itemId = $item['item']['id'] ?? null;

                if (! $itemId) {
                    continue;
                }

                // Buscar anúncio para obter o SKU vinculado
                $anuncio = \App\Models\MarketplaceAnuncio::where('external_id', $itemId)
                    ->where('empresa_id', $pedido->empresa_id)
                    ->first();

                if (! $anuncio) {
                    Log::warning("Estoque: Anúncio não encontrado para item {$itemId}");

                    continue;
                }

                // Buscar SKU vinculado ao produto
                $skuId = null;
                if ($anuncio->produto_id) {
                    $sku = \App\Models\ProductSku::where('product_id', $anuncio->produto_id)->first();
                    $skuId = $sku?->id;
                }

                if (! $skuId) {
                    Log::warning("Estoque: SKU não encontrado para produto {$anuncio->produto_id}");

                    continue;
                }

                $quantidade = intval($item['quantity'] ?? 1);

                // Fazer baixa
                $result = $estoqueService->registrarSaidaPorPedido(
                    $pedido,
                    $skuId,
                    $quantidade,
                    $depositoId
                );

                if ($result) {
                    $baixas++;
                    Log::info("Estoque: Baixado {$quantidade} do SKU {$skuId} no depósito {$depositoId}");
                }
            }

            Log::info("Estoque: Pedido {$pedido->pedido_id} - {$baixas} baixas realizadas");

        } catch (\Exception $e) {
            Log::error('Estoque: Erro ao fazer baixa automática: '.$e->getMessage());
        }
    }

    /**
     * Determina o depósito baseado no tipo de logística
     */
    protected function getDepositoPorLogistica(?string $logisticType, int $empresaId): ?int
    {
        $tipoDeposito = match ($logisticType) {
            'fulfillment' => 'full',
            'self_service', 'cross_docking', 'drop_off', 'xd_drop_off', 'turbo' => 'loja',
            default => 'loja',
        };

        $deposito = \App\Models\Deposito::where('empresa_id', $empresaId)
            ->where('tipo', $tipoDeposito)
            ->where('ativo', true)
            ->first();

        return $deposito?->id;
    }

    /**
     * Busca detalhes de um item do ML
     */
    public function getItem(string $itemId): ?array
    {
        $token = $this->getAccessToken();
        if (! $token) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/items/{$itemId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Meli getItem exception: '.$e->getMessage());

            return null;
        }
    }

    public function obterCustoFreteMercadoLivre(string $itemId): array
    {
        $token = $this->getAccessToken();
        $integracao = $this->getIntegracao();

        if (! $token || ! $integracao) {
            return ['cost' => 0, 'source' => 'no_integration', 'is_free_shipping' => false];
        }

        try {
            $userId = $integracao->external_user_id;

            // 1. Tentar obter o custo do frete grátis via API de shipping_options
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/users/{$userId}/shipping_options/free", [
                'item_id' => $itemId,
            ]);

            $cost = 0;
            $isFreeShipping = false;

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['coverage']['all_country']['cost'])) {
                    $cost = $data['coverage']['all_country']['cost'];
                }
            }

            // 2. Tentar obter a taxa fixa (caso o item não tenha frete grátis mas o vendedor pague unitário)
            $itemResponse = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/items/{$itemId}");

            if ($itemResponse->successful()) {
                $itemData = $itemResponse->json();
                $isFreeShipping = $itemData['shipping']['free_shipping'] ?? false;

                if (! $isFreeShipping) {
                    $price = $itemData['price'] ?? 0;
                    $listingTypeId = $itemData['listing_type_id'] ?? 'gold_pro';

                    $pricingResponse = Http::withHeaders([
                        'Authorization' => 'Bearer '.$token,
                    ])->get('https://api.mercadolibre.com/sites/MLB/listing_prices', [
                        'price' => $price,
                        'listing_type_id' => $listingTypeId,
                    ]);

                    if ($pricingResponse->successful()) {
                        $pricingData = $pricingResponse->json();
                        // Geralmente retorna array
                        if (is_array($pricingData) && isset($pricingData[0]['sale_fee_details']['fixed_fee'])) {
                            $cost = $pricingData[0]['sale_fee_details']['fixed_fee'];
                        } elseif (isset($pricingData['sale_fee_details']['fixed_fee'])) {
                            $cost = $pricingData['sale_fee_details']['fixed_fee'];
                        }
                    }
                }
            }

            return [
                'cost' => floatval($cost),
                'is_free_shipping' => $isFreeShipping,
                'source' => $isFreeShipping ? 'shipping_options' : 'fixed_fee',
            ];

        } catch (\Exception $e) {
            Log::error('Meli obterCustoFreteMercadoLivre exception: '.$e->getMessage());

            return ['cost' => 0, 'source' => 'error', 'is_free_shipping' => false];
        }
    }

    /**
     * Busca ofertas de concorrentes para um produto do catálogo do Mercado Livre
     */
    public function getCatalogOffers(string $catalogProductId, ?string $permalink = null): ?array
    {
        $token = $this->getAccessToken();
        if (! $token) {
            Log::warning('Meli getCatalogOffers: Token não disponível');

            return null;
        }

        try {
            // Tenta API oficial primeiro
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get("https://api.mercadolibre.com/catalog_products/{$catalogProductId}/offers", [
                'include' => 'all',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $offers = [];

                if (isset($data['offers']) && is_array($data['offers'])) {
                    foreach ($data['offers'] as $offer) {
                        $listingType = $offer['listing_type']['id'] ?? 'gold_special';
                        $sellerType = 'classic';
                        if ($listingType === 'fulfillment') {
                            $sellerType = 'full';
                        } elseif (in_array($listingType, ['gold_pro', 'premium'])) {
                            $sellerType = 'premium';
                        }

                        $offers[] = [
                            'id' => $offer['seller']['id'] ?? null,
                            'price' => floatval($offer['price'] ?? 0),
                            'listing_type_id' => $listingType,
                            'shipping' => [
                                'logistic_type' => $offer['shipping']['logistic_type'] ?? null,
                            ],
                            'seller_type' => $sellerType,
                            'is_full' => $sellerType === 'full',
                            'is_premium' => $sellerType === 'premium',
                        ];
                    }
                }

                return [
                    'offers' => $offers,
                    'product_id' => $catalogProductId,
                    'total_offers' => count($offers),
                ];
            }

            // Fallback: tenta scraping
            return $this->buscarConcorrentesViaScraping($catalogProductId, $permalink);

        } catch (\Exception $e) {
            Log::error('Meli getCatalogOffers exception: '.$e->getMessage());

            return $this->buscarConcorrentesViaScraping($catalogProductId, $permalink);
        }
    }

    /**
     * Busca concorrentes via scraping com detecção de full/premium/classic
     */
    private function buscarConcorrentesViaScraping(string $catalogProductId, ?string $permalink): ?array
    {
        if (! $permalink) {
            return null;
        }

        $cookiesFile = storage_path('app/cookies/cookies_meli_'.$this->empresaId.'.json');
        if (! file_exists($cookiesFile)) {
            $cookiesFile = storage_path('app/cookies/cookies_meli_default.json');
        }

        if (! file_exists($cookiesFile)) {
            Log::warning('Meli scraping: Arquivo de cookies não encontrado');

            return null;
        }

        try {
            $nodeScript = '
                const puppeteer = require("puppeteer");
                const fs = require("fs");
                
                const COOKIES_FILE = "'.$cookiesFile.'";
                
                (async () => {
                    const browser = await puppeteer.launch({
                        headless: true,
                        executablePath: "/usr/bin/chromium-browser",
                        args: ["--no-sandbox", "--disable-setuid-sandbox"]
                    });
                    
                    const context = await browser.createBrowserContext();
                    const page = await context.newPage();
                    
                    await page.setUserAgent("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36");
                    await page.setViewport({ width: 1366, height: 768 });
                    
                    const cookies = JSON.parse(fs.readFileSync(COOKIES_FILE, "utf8"));
                    await page.setCookie(...cookies);
                    
                    await page.goto("'.addslashes($permalink).'", { waitUntil: "networkidle2", timeout: 60000 });
                    await new Promise(r => setTimeout(r, 3000));
                    
                    const offers = await page.evaluate(() => {
                        const results = [];
                        
                        // Busca resultados na página de lista
                        const items = document.querySelectorAll(".ui-search-result__wrapper");
                        
                        items.forEach(item => {
                            const priceElement = item.querySelector(".price-tag-text, .andes-money-amount");
                            if (!priceElement) return;
                            
                            const priceText = priceElement.textContent.trim();
                            const price = parseFloat(priceText.replace(/[^\d,]/g, "").replace(",", "."));
                            
                            if (!price || price <= 15) return;
                            
                            // Detecta se é Full
                            const isFull = item.querySelector(".ui-pdp-icon--full, .ui-pdp-icon--fulfillment") !== null;
                            
                            // Detecta se é Premium (tem "sem juros")
                            const semJurosElement = item.querySelector(".ui-pdp-installments");
                            const isPremium = semJurosElement && semJurosElement.textContent.includes("sem juros");
                            
                            let sellerType = "classic";
                            if (isFull) sellerType = "full";
                            else if (isPremium) sellerType = "premium";
                            
                            results.push({
                                price: price,
                                type: sellerType,
                                seller: "Concorrente",
                                is_full: isFull,
                                is_premium: isPremium
                            });
                        });
                        
                        return results;
                    });
                    
                    console.log(JSON.stringify(offers));
                    
                    await context.close();
                    await browser.close();
                })();
            ';

            $command = 'cd /var/www && node -e '.escapeshellarg($nodeScript).' 2>&1';
            $output = shell_exec($command);

            if (! $output) {
                Log::warning('Meli scraping: Comando não retornou output');

                return null;
            }

            $offersData = json_decode($output, true);

            if (empty($offersData)) {
                Log::warning('Meli scraping: Nenhum preço encontrado');

                return null;
            }

            $offers = [];
            foreach ($offersData as $offerData) {
                $price = floatval($offerData['price'] ?? 0);
                $sellerType = $offerData['type'] ?? 'classic';

                $listingType = match ($sellerType) {
                    'full' => 'fulfillment',
                    'premium' => 'gold_pro',
                    default => 'gold_special'
                };

                if ($price > 0 && $price > 15) {
                    $offers[] = [
                        'id' => null,
                        'price' => $price,
                        'listing_type_id' => $listingType,
                        'shipping' => ['logistic_type' => $sellerType === 'full' ? 'fulfillment' : null],
                        'title' => 'Concorrente',
                        'seller_type' => $sellerType,
                        'is_full' => $offerData['is_full'] ?? false,
                        'is_premium' => $offerData['is_premium'] ?? false,
                    ];
                }
            }

            Log::info('Meli scraping: Encontrados '.count($offers).' preços');

            return [
                'offers' => $offers,
                'product_id' => $catalogProductId,
                'total_offers' => count($offers),
                'scraped' => true,
            ];

        } catch (\Exception $e) {
            Log::error('Meli scraping exception: '.$e->getMessage());

            return null;
        }
    }
}
