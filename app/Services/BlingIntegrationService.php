<?php

namespace App\Services;

use App\Models\Integracao;
use App\Models\MarketplacePedido;
use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlingIntegrationService
{
    private string $accessToken;

    private string $refreshToken;

    private string $baseUrl;

    private int $empresaId;

    private ?Integracao $integracao = null;

    public function __construct(int $empresaId)
    {
        $this->empresaId = $empresaId;
        $this->baseUrl = config('services.bling.api_url', env('BLING_API_URL', 'https://bling.com.br/Api/v6/'));

        $this->loadIntegracao();
    }

    private function loadIntegracao(): void
    {
        $this->integracao = Integracao::where('empresa_id', $this->empresaId)
            ->where('marketplace', 'bling')
            ->where('ativo', true)
            ->first();

        if ($this->integracao) {
            $this->accessToken = $this->integracao->access_token;
            $this->refreshToken = $this->integracao->refresh_token;
        }
    }

    public function isConnected(): bool
    {
        return $this->integracao !== null && ! empty($this->accessToken);
    }

    public function updateNome(string $nome): bool
    {
        if (! $this->integracao) {
            return false;
        }

        try {
            $this->integracao->update(['nome_conta' => $nome]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar nome do Bling: '.$e->getMessage());

            return false;
        }
    }

    public function connect(string $apiKey, ?string $nomeConta = null): bool
    {
        try {
            $response = Http::get($this->baseUrl.'contato/json/', [
                'apikey' => $apiKey,
            ]);

            if ($response->successful() && isset($response->json()['retorno']['contatos'])) {
                $this->accessToken = $apiKey;

                Integracao::updateOrCreate(
                    [
                        'empresa_id' => $this->empresaId,
                        'marketplace' => 'bling',
                    ],
                    [
                        'nome_conta' => $nomeConta ?: 'Bling ERP',
                        'access_token' => $apiKey,
                        'ativo' => true,
                        'configuracoes' => [
                            'default_cfop' => config('services.bling.default_cfop', env('BLING_NFE_DEFAULT_CFOP', '5102')),
                            'default_csosn' => config('services.bling.default_csosn', env('BLING_NFE_DEFAULT_CSOSN', '102')),
                            'natureza_operacao' => config('services.bling.natureza_operacao', env('BLING_NFE_OPERATION_NATURE', 'Saída')),
                        ],
                    ]
                );

                $this->loadIntegracao();

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao conectar com Bling: '.$e->getMessage());

            return false;
        }
    }

    public function saveOAuthTokens(string $accessToken, string $refreshToken, int $expiresIn, ?string $nomeConta = null): bool
    {
        try {
            $expiresAt = Carbon::now()->addSeconds($expiresIn);

            Integracao::updateOrCreate(
                [
                    'empresa_id' => $this->empresaId,
                    'marketplace' => 'bling',
                ],
                [
                    'nome_conta' => $nomeConta ?: 'Bling ERP',
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_at' => $expiresAt,
                    'ativo' => true,
                    'configuracoes' => [
                        'default_cfop' => config('services.bling.default_cfop', env('BLING_NFE_DEFAULT_CFOP', '5102')),
                        'default_csosn' => config('services.bling.default_csosn', env('BLING_NFE_DEFAULT_CSOSN', '102')),
                        'natureza_operacao' => config('services.bling.natureza_operacao', env('BLING_NFE_OPERATION_NATURE', 'Saída')),
                    ],
                ]
            );

            $this->accessToken = $accessToken;
            $this->refreshToken = $refreshToken;
            $this->loadIntegracao();

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao salvar tokens OAuth do Bling: '.$e->getMessage());

            return false;
        }
    }

    public function refreshTokenIfNeeded(): bool
    {
        if (! $this->integracao || ! $this->refreshToken) {
            return false;
        }

        $expiresAt = $this->integracao->expires_at;
        if ($expiresAt && Carbon::now()->lessThan($expiresAt->subMinutes(5))) {
            return true;
        }

        try {
            $clientId = config('services.bling.client_id');
            $clientSecret = config('services.bling.client_secret');

            $response = Http::withHeaders([
                'Authorization' => 'Basic '.base64_encode($clientId.':'.$clientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post('https://www.bling.com.br/Api/v3/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $this->integracao->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_at' => Carbon::now()->addSeconds($data['expires_in']),
                ]);

                $this->accessToken = $data['access_token'];
                $this->refreshToken = $data['refresh_token'];

                return true;
            }

            Log::error('Erro ao refresh token Bling: '.$response->body());

            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao refresh token Bling: '.$e->getMessage());

            return false;
        }
    }

    private function getAccessToken(): string
    {
        $this->refreshTokenIfNeeded();

        return $this->accessToken;
    }

    public function disconnect(): bool
    {
        if ($this->integracao) {
            $this->integracao->update(['ativo' => false]);
            $this->integracao = null;

            return true;
        }

        return false;
    }

    public function getContatos(array $filters = []): ?array
    {
        try {
            $params = array_merge(['apikey' => $this->accessToken], $filters);
            $response = Http::get($this->baseUrl.'contato/json/', $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar contatos no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function createContato(array $data): ?array
    {
        try {
            $response = Http::asForm()->post($this->baseUrl.'contato/json/', array_merge([
                'apikey' => $this->accessToken,
            ], $data));

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao criar contato no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function getProdutos(array $filters = []): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get('https://api.bling.com.br/Api/v3/produtos', $filters);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Erro ao buscar produtos no Bling: '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar produtos no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function syncProdutosAsAnuncios(): array
    {
        $resultado = ['importados' => 0, 'atualizados' => 0, 'erros' => 0];

        $pagina = 1;
        $limite = 100;
        $maxPaginas = 50;

        while ($pagina <= $maxPaginas) {
            $produtos = $this->getProdutos(['pagina' => $pagina, 'limite' => $limite]);

            if (! $produtos || ! isset($produtos['data']) || empty($produtos['data'])) {
                break;
            }

            foreach ($produtos['data'] as $produto) {
                try {
                    $externalId = (string) ($produto['id'] ?? uniqid('bling_'));
                    $nome = $produto['nome'] ?? 'Produto sem nome';
                    $sku = $produto['codigo'] ?? null;
                    $preco = floatval($produto['preco'] ?? 0);
                    $estoque = intval($produto['estoque'] ?? 0);
                    $imagem = $produto['imagens'][0]['url'] ?? null;

                    $anuncio = \App\Models\MarketplaceAnuncio::updateOrCreate(
                        [
                            'empresa_id' => $this->empresaId,
                            'integracao_id' => $this->integracao->id,
                            'external_id' => $externalId,
                        ],
                        [
                            'marketplace' => 'bling',
                            'titulo' => $nome,
                            'sku' => $sku,
                            'preco' => $preco,
                            'estoque' => $estoque,
                            'status' => $estoque > 0 ? 'active' : 'inactive',
                            'json_data' => $produto,
                        ]
                    );

                    if ($anuncio->wasRecentlyCreated) {
                        $resultado['importados']++;
                    } else {
                        $resultado['atualizados']++;
                    }
                } catch (\Exception $e) {
                    $resultado['erros']++;
                    Log::error('Erro ao importar produto Bling: '.$e->getMessage());
                }
            }

            $pagination = $produtos['meta']['pagination'] ?? [];
            $currentPage = $pagination['current_page'] ?? $pagina;
            $totalPages = $pagination['total_pages'] ?? 1;

            if ($currentPage >= $totalPages) {
                break;
            }

            $pagina++;
        }

        return $resultado;
    }

    public function updateProduto(string $blingProductId, array $data): array
    {
        try {
            $payload = [];

            if (isset($data['preco'])) {
                $payload['preco'] = $data['preco'];
            }

            if (isset($data['estoque'])) {
                $payload['estoque'] = $data['estoque'];
            }

            if (isset($data['nome'])) {
                $payload['nome'] = $data['nome'];
            }

            if (isset($data['codigo'])) {
                $payload['codigo'] = $data['codigo'];
            }

            if (empty($payload)) {
                return ['success' => false, 'error' => 'Nenhum dado para atualizar'];
            }

            $response = Http::withHeaders($this->headers())
                ->put("https://api.bling.com.br/Api/v3/produtos/{$blingProductId}", $payload);

            if ($response->successful()) {
                Log::info("Bling: Produto {$blingProductId} atualizado com sucesso");

                return ['success' => true, 'data' => $response->json()];
            }

            $error = $response->json();
            Log::error("Bling: Erro ao atualizar produto {$blingProductId}: ".json_encode($error));

            return ['success' => false, 'error' => $error['error'] ?? 'Erro ao atualizar produto'];
        } catch (\Exception $e) {
            Log::error('Bling updateProduto exception: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createProduto(array $data): ?array
    {
        try {
            $response = Http::asForm()->post($this->baseUrl.'produto/json/', array_merge([
                'apikey' => $this->accessToken,
            ], $data));

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao criar produto no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function getPedidos(array $filters = []): ?array
    {
        try {
            $params = array_merge(['apikey' => $this->accessToken], $filters);
            $response = Http::get($this->baseUrl.'pedido/json/', $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar pedidos no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function createPedido(array $data): ?array
    {
        try {
            $response = Http::asForm()->post($this->baseUrl.'pedido/json/', array_merge([
                'apikey' => $this->accessToken,
            ], $data));

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao criar pedido no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function getNotasFiscais(array $filters = []): ?array
    {
        try {
            $params = array_merge(['apikey' => $this->accessToken], $filters);
            $response = Http::get($this->baseUrl.'notafiscal/json/', $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar notas fiscais no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function createNotaFiscal(array $data): ?array
    {
        try {
            $response = Http::asForm()->post($this->baseUrl.'notafiscal/json/', array_merge([
                'apikey' => $this->accessToken,
            ], $data));

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao criar nota fiscal no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function getNotaFiscal(int $id): ?array
    {
        try {
            $response = Http::get($this->baseUrl.'notafiscal/'.$id.'/json/', [
                'apikey' => $this->accessToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar nota fiscal no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function sendNotaFiscal(int $id): ?array
    {
        try {
            $response = Http::asForm()->post($this->baseUrl.'notafiscal/enviar/json/', [
                'apikey' => $this->accessToken,
                'id' => $id,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao enviar nota fiscal no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function getSerie(): ?array
    {
        try {
            $response = Http::get($this->baseUrl.'serie/json/', [
                'apikey' => $this->accessToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar séries no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function getLojas(): ?array
    {
        try {
            $response = Http::get($this->baseUrl.'loja/json/', [
                'apikey' => $this->accessToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar lojas no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function getFormasPagamento(): ?array
    {
        try {
            $response = Http::get($this->baseUrl.'formapagamento/json/', [
                'apikey' => $this->accessToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar formas de pagamento no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function getTransportadoras(): ?array
    {
        try {
            $response = Http::get($this->baseUrl.'transportadora/json/', [
                'apikey' => $this->accessToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar transportadoras no Bling: '.$e->getMessage());

            return null;
        }
    }

    public function getIntegracao(): ?Integracao
    {
        return $this->integracao;
    }

    public function testCredentials(string $apiKey): array
    {
        try {
            $response = Http::get($this->baseUrl.'/contato/json/', [
                'apikey' => $apiKey,
                'limit' => 1,
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'API Key válida!'];
            }

            $error = $response->json();

            return ['success' => false, 'message' => $error['retorno']['erros'][0]['erro'] ?? 'Erro na autenticação'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->getAccessToken(),
            'Accept' => 'application/json',
        ];
    }

    public function getNotificacoes(?string $periodo = null): ?array
    {
        try {
            $params = [];
            if ($periodo) {
                $params['periodo'] = $periodo;
            }

            $response = Http::withHeaders($this->headers())
                ->get('https://api.bling.com.br/Api/v3/notificacoes', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Erro ao buscar notificações: '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar notificações: '.$e->getMessage());

            return null;
        }
    }

    public function confirmarLeitura(int $idNotificacao): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->post('https://api.bling.com.br/Api/v3/notificacoes/'.$idNotificacao.'/confirmar-leitura');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Erro ao confirmar leitura: '.$e->getMessage());

            return false;
        }
    }

    public function getPedidosVendas(array $filters = []): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get('https://api.bling.com.br/Api/v3/pedidos/vendas', $filters);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Erro ao buscar pedidos: '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar pedidos: '.$e->getMessage());

            return null;
        }
    }

    public function getNotaFiscalById(int $id): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get('https://api.bling.com.br/Api/v3/nfe/'.$id);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Erro ao buscar nota fiscal: '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar nota fiscal: '.$e->getMessage());

            return null;
        }
    }

    public function processarNotificacoes(): array
    {
        $resultado = [
            'pedidos' => 0,
            'notas' => 0,
            'erros' => [],
        ];

        $notificacoes = $this->getNotificacoes();

        if (! $notificacoes || ! isset($notificacoes['data'])) {
            return $resultado;
        }

        foreach ($notificacoes['data'] as $notificacao) {
            $tipo = $notificacao['tipo'] ?? '';
            $id = $notificacao['id'] ?? null;

            if (! $id) {
                continue;
            }

            try {
                if ($tipo === 'PedidoVenda') {
                    $pedido = $this->getPedidosVendas(['id' => $id]);
                    if ($pedido && isset($pedido['data'])) {
                        $this->salvarPedido($pedido['data']);
                        $resultado['pedidos']++;
                    }
                } elseif ($tipo === 'NotaFiscal') {
                    $nota = $this->getNotaFiscalById($id);
                    if ($nota && isset($nota['data'])) {
                        $this->salvarNotaFiscal($nota['data']);
                        $resultado['notas']++;
                    }
                }

                $this->confirmarLeitura($id);
            } catch (\Exception $e) {
                $resultado['erros'][] = "Notificação {$id}: ".$e->getMessage();
                Log::error('Erro ao processar notificação: '.$e->getMessage());
            }
        }

        return $resultado;
    }

    public function processarNotificacao(string $tipo, int $id): bool
    {
        try {
            if ($tipo === 'PedidoVenda') {
                $pedido = $this->getPedidosVendas(['id' => $id]);
                if ($pedido && isset($pedido['data'])) {
                    $this->salvarPedido($pedido['data']);

                    return true;
                }
            } elseif ($tipo === 'NotaFiscal') {
                $nota = $this->getNotaFiscalById($id);
                if ($nota && isset($nota['data'])) {
                    $this->salvarNotaFiscal($nota['data']);

                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao processar notificação: '.$e->getMessage());

            return false;
        }
    }

    public function salvarPedido(array $pedido): void
    {
        $externalId = $pedido['id'] ?? null;
        if (! $externalId) {
            return;
        }

        $numero = $pedido['numero'] ?? $externalId;
        $status = $pedido['situacao'] ?? 'pendente';
        $valorTotal = floatval($pedido['totalvenda'] ?? 0);
        $dataEmissao = isset($pedido['data']) ? Carbon::parse($pedido['data']) : now();

        $cliente = $pedido['cliente'] ?? [];
        $nome = $cliente['nome'] ?? '';
        $cpfCnpj = $cliente['documento'] ?? '';

        MarketplacePedido::updateOrCreate(
            [
                'empresa_id' => $this->empresaId,
                'external_id' => (string) $externalId,
                'marketplace' => 'bling',
            ],
            [
                'pedido_id' => $numero,
                'status' => $this->mapStatusPedido($status),
                'status_pagamento' => $this->mapStatusPagamento($status),
                'comprador_nome' => $nome,
                'comprador_cnpj' => preg_replace('/[^0-9]/', '', $cpfCnpj),
                'valor_total' => $valorTotal,
                'data_compra' => $dataEmissao,
                'json_data' => $pedido,
            ]
        );
    }

    public function salvarNotaFiscal(array $nota): void
    {
        $chave = $nota['chaveAcesso'] ?? null;
        $numero = $nota['numero'] ?? null;
        $serie = $nota['serie'] ?? 1;
        $valorTotal = floatval($nota['valorTotal'] ?? 0);
        $status = $nota['situacao'] ?? 1;

        if (! $chave && ! $numero) {
            return;
        }

        $emitente = $nota['emitente'] ?? [];
        $destinatario = $nota['destinatario'] ?? [];

        $tipo = (int) ($nota['tipo'] ?? 1) === 0 ? 'entrada' : 'saida';

        // Se é nota de saída, salva em nfe_emitidas
        if ($tipo === 'saida') {
            NfeEmitida::updateOrCreate(
                [
                    'empresa_id' => $this->empresaId,
                    'chave' => $chave,
                ],
                [
                    'numero' => $numero,
                    'serie' => $serie,
                    'valor_total' => $valorTotal,
                    'emitente_cnpj' => $emitente['cnpj'] ?? ($emitente['cpf'] ?? ''),
                    'emitente_nome' => $emitente['nome'] ?? '',
                    'destinatario_cnpj' => $destinatario['cnpj'] ?? ($destinatario['cpf'] ?? ''),
                    'destinatario_nome' => $destinatario['nome'] ?? '',
                    'status' => $this->mapStatusNfe($status),
                ]
            );
        } else {
            NfeRecebida::updateOrCreate(
                [
                    'empresa_id' => $this->empresaId,
                    'chave' => $chave,
                ],
                [
                    'numero' => $numero,
                    'serie' => $serie,
                    'valor_total' => $valorTotal,
                    'emitente_cnpj' => $emitente['cnpj'] ?? ($emitente['cpf'] ?? ''),
                    'emitente_nome' => $emitente['nome'] ?? '',
                    'destinatario_cnpj' => $destinatario['cnpj'] ?? ($destinatario['cpf'] ?? ''),
                    'destinatario_nome' => $destinatario['nome'] ?? '',
                    'status_nfe' => $this->mapStatusNfe($status),
                ]
            );
        }
    }

    protected function mapStatusPedido(string $status): string
    {
        $map = [
            'Pendente' => 'pending',
            'Aprovado' => 'paid',
            'Em produção' => 'processing',
            'Encerrado' => 'completed',
            'Cancelado' => 'cancelled',
        ];

        return $map[$status] ?? 'pending';
    }

    protected function mapStatusPagamento(string $status): string
    {
        if (in_array($status, ['Aprovado', 'Encerrado'])) {
            return 'paid';
        }
        if ($status === 'Cancelado') {
            return 'cancelled';
        }

        return 'pending';
    }

    protected function mapStatusNfe(int $status): string
    {
        $map = [
            1 => 'pendente',
            2 => 'cancelada',
            3 => 'aguardando_recibo',
            4 => 'rejeitada',
            5 => 'autorizada',
            6 => 'emitida_danfe',
            7 => 'registrada',
            8 => 'aguardando_protocolo',
            9 => 'denegada',
            10 => 'consulta_situacao',
            11 => 'bloqueada',
        ];

        return $map[$status] ?? 'pendente';
    }
}
