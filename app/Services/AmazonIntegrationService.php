<?php

namespace App\Services;

use App\Models\Integracao;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmazonIntegrationService
{
    protected ?int $empresaId = null;

    protected ?Integracao $integracao = null;

    protected string $marketplace = 'amazon';

    protected ?string $accessToken = null;

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
            ->where('marketplace', $this->marketplace)
            ->where('ativo', true)
            ->first();

        return $this->integracao;
    }

    public function isConnected(): bool
    {
        $integracao = $this->getIntegracao();
        
        if (!$integracao) {
            return false;
        }
        
        // Handle configuracoes as string or array
        $config = $integracao->configuracoes;
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        return $integracao && ! empty($config['client_id'] ?? null);
    }

    public function connect(array $credentials): bool
    {
        try {
            $integracao = Integracao::updateOrCreate(
                [
                    'empresa_id' => $this->empresaId,
                    'marketplace' => $this->marketplace,
                ],
                [
                    'nome_conta' => $credentials['nome_conta'] ?? 'Amazon Seller',
                    'access_token' => $credentials['access_token'] ?? null,
                    'refresh_token' => $credentials['refresh_token'] ?? null,
                    'expires_at' => now()->addDays(30),
                    'ativo' => true,
                    'configuracoes' => [
                        'client_id' => $credentials['client_id'],
                        'client_secret' => $credentials['client_secret'],
                        'refresh_token' => $credentials['refresh_token'],
                        'marketplace_id' => $credentials['marketplace_id'] ?? 'ATVPDKIKX0DER',
                        'seller_id' => $credentials['seller_id'] ?? null,
                    ],
                ]
            );

            $this->integracao = $integracao;

            return true;
        } catch (\Exception $e) {
            Log::error('Amazon connect error: '.$e->getMessage());

            return false;
        }
    }

    public function disconnect(): bool
    {
        try {
            if ($this->integracao) {
                $this->integracao->update([
                    'ativo' => false,
                    'access_token' => null,
                    'refresh_token' => null,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Amazon disconnect error: '.$e->getMessage());

            return false;
        }
    }

    protected function getConfig(): array
    {
        $integracao = $this->getIntegracao();

        if (! $integracao || ! $integracao->configuracoes) {
            return [];
        }

        $config = $integracao->configuracoes;
        
        // Handle configuracoes as string (JSON) or array
        if (is_string($config)) {
            $config = json_decode($config, true) ?? [];
        }

        return $config;
    }

    protected function getLwaToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $integracao = $this->getIntegracao();

        if (! $integracao) {
            Log::error('Amazon: Nenhuma integração encontrada para empresa '.$this->empresaId);

            return null;
        }

        if ($integracao->access_token && $integracao->expires_at && ! $integracao->expires_at->isPast()) {
            $this->accessToken = $integracao->access_token;

            return $this->accessToken;
        }

        // Se tem access_token mas não tem refresh_token, usa o que tem (pode estar expirado ou não)
        if ($integracao->access_token) {
            $this->accessToken = $integracao->access_token;
            return $this->accessToken;
        }

        $config = $this->getConfig();

        if (empty($config['refresh_token'])) {
            Log::error('Amazon: refresh_token não encontrado nas configurações');

            return null;
        }

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            Log::error('Amazon: client_id ou client_secret não encontrado nas configurações');

            return null;
        }

        try {
            Log::info('Amazon: Solicitando LWA token com client_id: '.substr($config['client_id'], 0, 10).'...');

            $response = Http::asForm()->post('https://api.amazon.com/auth/o2/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $config['refresh_token'],
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
            ]);

            Log::info('Amazon LWA response status: '.$response->status());
            Log::info('Amazon LWA response body: '.$response->body());

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'];

                $integracao->update([
                    'access_token' => $this->accessToken,
                    'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                ]);

                return $this->accessToken;
            }

            Log::error('Amazon LWA token error: '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('Amazon LWA token exception: '.$e->getMessage());

            return null;
        }
    }

    protected function getMarketplaceId(): string
    {
        $config = $this->getConfig();

        return $config['marketplace_id'] ?? 'ATVPDKIKX0DER';
    }

    protected function getApiEndpoint(): string
    {
        $config = $this->getConfig();
        $region = $config['region'] ?? 'br';

        $endpoints = [
            'br' => 'https://sellingpartnerapi-na.amazon.com',
            'us' => 'https://sellingpartnerapi-na.amazon.com',
            'na' => 'https://sellingpartnerapi-na.amazon.com',
            'eu' => 'https://sellingpartnerapi-eu.amazon.com',
            'fe' => 'https://sellingpartnerapi-fe.amazon.com',
        ];

        return $endpoints[$region] ?? $endpoints['br'];
    }

    public function getOrders(array $params = []): array
    {
        if (! $this->isConnected()) {
            return ['error' => 'Amazon não conectado'];
        }

        $token = $this->getLwaToken();
        if (! $token) {
            return ['error' => 'Falha ao obter token de acesso'];
        }

        $marketplaceId = $this->getMarketplaceId();
        Log::info('Amazon getOrders: marketplaceId = ' . $marketplaceId);
        $baseUrl = $this->getApiEndpoint();

        $defaultParams = [
            'MarketplaceId' => $marketplaceId,
            'OrderStatus' => 'Unshipped,PartiallyShipped,Shipped',
        ];
        $params = array_merge($defaultParams, $params);

        try {
            Log::info('Amazon getOrders request: url=' . $baseUrl . '/orders/v0/orders, params=' . json_encode($params));
            
            $response = Http::withHeaders([
                'x-amz-access-token' => $token,
                'x-amz-marketplace-id' => $marketplaceId,
                'Content-Type' => 'application/json',
            ])->get($baseUrl.'/orders/v0/orders', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Amazon getOrders error: '.$response->body());

            return ['error' => $response->json() ?? 'Erro ao buscar pedidos'];
        } catch (\Exception $e) {
            Log::error('Amazon getOrders exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function getOrderItems(string $amazonOrderId): array
    {
        if (! $this->isConnected()) {
            return ['error' => 'Amazon não conectado'];
        }

        $token = $this->getLwaToken();
        if (! $token) {
            return ['error' => 'Falha ao obter token de acesso'];
        }

        $marketplaceId = $this->getMarketplaceId();
        $baseUrl = $this->getApiEndpoint();

        try {
            $response = Http::withHeaders([
                'x-amz-access-token' => $token,
                'x-amz-marketplace-id' => $marketplaceId,
                'Content-Type' => 'application/json',
            ])->get($baseUrl.'/orders/v0/orders/'.$amazonOrderId.'/orderItems');

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->json() ?? 'Erro ao buscar itens do pedido'];
        } catch (\Exception $e) {
            Log::error('Amazon getOrderItems exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function getInventory(array $params = []): array
    {
        if (! $this->isConnected()) {
            return ['error' => 'Amazon não conectado'];
        }

        $token = $this->getLwaToken();
        if (! $token) {
            return ['error' => 'Falha ao obter token de acesso'];
        }

        $marketplaceId = $this->getMarketplaceId();
        $baseUrl = $this->getApiEndpoint();

        try {
            $response = Http::withHeaders([
                'x-amz-access-token' => $token,
                'x-amz-marketplace-id' => $marketplaceId,
                'Content-Type' => 'application/json',
            ])->get($baseUrl.'/fba/inventory/v1/summaries', $params);

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->json() ?? 'Erro ao buscar inventário'];
        } catch (\Exception $e) {
            Log::error('Amazon getInventory exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function getCatalogItems(array $params = []): array
    {
        if (! $this->isConnected()) {
            return ['error' => 'Amazon não conectado'];
        }

        $token = $this->getLwaToken();
        if (! $token) {
            return ['error' => 'Falha ao obter token de acesso'];
        }

        $marketplaceId = $this->getMarketplaceId();
        $baseUrl = $this->getApiEndpoint();

        try {
            $response = Http::withHeaders([
                'x-amz-access-token' => $token,
                'x-amz-marketplace-id' => $marketplaceId,
                'Content-Type' => 'application/json',
            ])->get($baseUrl.'/catalog/v0/items', $params);

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->json() ?? 'Erro ao buscar catálogo'];
        } catch (\Exception $e) {
            Log::error('Amazon getCatalogItems exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function testConnection(): array
    {
        if (! $this->isConnected()) {
            return ['success' => false, 'message' => 'Amazon não conectado'];
        }

        $result = $this->getOrders(['MaxResults' => 1]);

        if (isset($result['error'])) {
            return ['success' => false, 'message' => $result['error']];
        }

        return ['success' => true, 'message' => 'Conexão estabelecida com sucesso!'];
    }

    public function testCredentials(array $credentials): array
    {
        try {
            $response = Http::asForm()->post('https://api.amazon.com/auth/o2/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $credentials['refresh_token'],
                'client_id' => $credentials['client_id'],
                'client_secret' => $credentials['client_secret'],
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Credenciais válidas!'];
            }

            $error = $response->json();

            return ['success' => false, 'message' => $error['error_description'] ?? 'Erro na autenticação'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateCredentials(array $credentials): bool
    {
        try {
            $integracao = $this->getIntegracao();

            if (! $integracao) {
                return false;
            }

            $config = $integracao->configuracoes ?? [];

            $config['refresh_token'] = $credentials['refresh_token'] ?? $config['refresh_token'];
            $config['client_id'] = $credentials['client_id'] ?? $config['client_id'];
            $config['client_secret'] = $credentials['client_secret'] ?? $config['client_secret'];
            $config['marketplace_id'] = $credentials['marketplace_id'] ?? $config['marketplace_id'];
            $config['seller_id'] = $credentials['seller_id'] ?? $config['seller_id'];

            $integracao->update([
                'nome_conta' => $credentials['nome_conta'] ?? $integracao->nome_conta,
                'configuracoes' => $config,
                'access_token' => null,
                'expires_at' => null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Amazon updateCredentials error: '.$e->getMessage());

            return false;
        }
    }

    public function getListings(array $params = []): array
    {
        if (! $this->isConnected()) {
            return ['error' => 'Amazon não conectado'];
        }

        $token = $this->getLwaToken();
        if (! $token) {
            return ['error' => 'Falha ao obter token de acesso'];
        }

        $marketplaceId = $this->getMarketplaceId();

        try {
            $response = Http::withHeaders([
                'x-amz-access-token' => $token,
                'x-amz-marketplace-id' => $marketplaceId,
                'Content-Type' => 'application/json',
            ])->get('https://api.amazon.com.br/sellingPartnerAPI-v1/listings', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Amazon getListings error: '.$response->body());

            return ['error' => $response->json() ?? 'Erro ao buscar anúncios'];
        } catch (\Exception $e) {
            Log::error('Amazon getListings exception: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }
}
