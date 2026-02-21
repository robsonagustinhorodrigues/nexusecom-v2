<?php

namespace App\Livewire\Integrations;

use App\Models\Integracao;
use App\Models\MarketplaceAnuncio;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Anuncios extends Component
{
    use WithPagination;

    public $search = '';

    public $isSyncing = false;

    public $syncProgress = '';

    public $integracao_id = '';

    public $status_filtro = 'ativo';

    public $tipo_filtro = '';

    public $vinculo_filtro = '';

    public $marketplace_filtro = '';

    public $selectedAds = [];

    public $showVincularModal = false;

    public $anuncioSelecionado = null;

    public $searchProduto = '';

    public $variacaoSelecionada = null;

    public $showJsonModal = false;

    public $jsonData = null;

    public $jsonAdTitulo = '';

    public $viewMode = 'cards';

    public function mount()
    {
        $this->syncOnLoad();
    }

    public function syncOnLoad()
    {
        $empresaId = Auth::user()->current_empresa_id;

        $integracao = Integracao::where('empresa_id', $empresaId)
            ->where('marketplace', 'mercadolivre')
            ->where('ativo', true)
            ->first();

        if ($integracao) {
            $this->isSyncing = true;
            $this->syncProgress = 'Sincronizando anúncios...';

            try {
                $meliService = new \App\Services\MeliService;
                $meliService->syncAnuncios($integracao);
                $this->syncProgress = 'Sincronizando fretes...';
                $meliService->syncFreteAnuncios($integracao, 50);
                $this->syncProgress = 'Sincronizando promoções...';
                $this->syncPromocoes($integracao);
            } catch (\Exception $e) {
                \Log::error('Erro sync anuncios: '.$e->getMessage());
            }

            $this->isSyncing = false;
            $this->syncProgress = '';
        }
    }

    public function syncPromocoes($integracao)
    {
        $meliService = new \App\Services\MeliService;
        $itensPromocao = $meliService->syncPromocoes($integracao);

        $anuncios = MarketplaceAnuncio::where('integracao_id', $integracao->id)
            ->whereNotNull('external_id')
            ->get();

        foreach ($anuncios as $anuncio) {
            if (isset($itensPromocao[$anuncio->external_id])) {
                $promo = $itensPromocao[$anuncio->external_id];

                $descontoPercent = 0;
                if ($promo['original_price'] > 0 && $promo['deal_price'] > 0) {
                    $descontoPercent = round((($promo['original_price'] - $promo['deal_price']) / $promo['original_price']) * 100, 2);
                }

                $anuncio->update([
                    'preco_original' => $promo['original_price'],
                    'promocao_tipo' => $promo['tipo'],
                    'promocao_id' => $promo['id'],
                    'promocao_desconto' => $descontoPercent,
                    'promocao_valor' => $promo['deal_price'],
                    'promocao_inicio' => $promo['start_date'] ? \Carbon\Carbon::parse($promo['start_date']) : null,
                    'promocao_fim' => $promo['finish_date'] ? \Carbon\Carbon::parse($promo['finish_date']) : null,
                ]);
            } else {
                // Limpar campos de promoção se não houver promoção ativa
                $anuncio->update([
                    'preco_original' => null,
                    'promocao_tipo' => null,
                    'promocao_id' => null,
                    'promocao_desconto' => null,
                    'promocao_valor' => null,
                    'promocao_inicio' => null,
                    'promocao_fim' => null,
                ]);
            }
        }
    }

    public function checkAndSync()
    {
        $empresaId = Auth::user()->current_empresa_id;

        $integracao = Integracao::where('empresa_id', $empresaId)
            ->where('marketplace', 'mercadolivre')
            ->where('ativo', true)
            ->first();

        if ($integracao) {
            $this->isSyncing = true;
            $this->syncProgress = 'Sincronizando anúncios...';

            try {
                $meliService = new \App\Services\MeliService;
                $meliService->syncAnuncios($integracao);
                $this->syncProgress = 'Sincronizando fretes...';
                $meliService->syncFreteAnuncios($integracao, 50);
            } catch (\Exception $e) {
                \Log::error('Erro sync anuncios: '.$e->getMessage());
            }

            $this->isSyncing = false;
            $this->syncProgress = '';
        }
    }

    protected function anuncios()
    {
        $empresaId = Auth::user()->current_empresa_id;

        if (! $empresaId) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        // Get active integration for this empresa
        $integracao = Integracao::where('empresa_id', $empresaId)
            ->where('marketplace', 'mercadolivre')
            ->where('ativo', true)
            ->first();

        // If has ML integration, read from database (sync feito via comando ou manualmente)
        if ($integracao) {
            return MarketplaceAnuncio::tenant($empresaId)
                ->where('marketplace', 'mercadolivre')
                ->when($this->search, fn ($q) => $q->where(function ($query) {
                    $searchTerm = '%'.mb_strtolower($this->search, 'UTF-8').'%';
                    $query->whereRaw('LOWER(titulo) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(external_id) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(sku) LIKE ?', [$searchTerm]);
                }))
                ->when($this->status_filtro === 'ativo', fn ($q) => $q->where('status', 'active'))
                ->when($this->status_filtro === 'inativo', fn ($q) => $q->where('status', 'inactive'))
                ->when($this->tipo_filtro === 'catalogo', fn ($q) => $q->where('json_data->catalog_listing', true))
                ->when($this->vinculo_filtro === 'vinculado', fn ($q) => $q->whereNotNull('product_sku_id'))
                ->when($this->vinculo_filtro === 'nao_vinculado', fn ($q) => $q->whereNull('product_sku_id'))
                ->with(['productSku.product', 'integracao'])
                ->latest()
                ->paginate(15);
        }

        // Fallback: read from database (for other marketplaces or if no integration)
        return MarketplaceAnuncio::tenant($empresaId)
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $searchTerm = '%'.mb_strtolower($this->search, 'UTF-8').'%';
                $query->whereRaw('LOWER(titulo) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(external_id) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(sku) LIKE ?', [$searchTerm]);
            }))
            ->when($this->integracao_id, fn ($q) => $q->where('integracao_id', $this->integracao_id))
            ->when($this->marketplace_filtro, fn ($q) => $q->where('marketplace', $this->marketplace_filtro))
            ->when($this->status_filtro === 'ativo', fn ($q) => $q->where('status', 'active'))
            ->when($this->status_filtro === 'inativo', fn ($q) => $q->where('status', 'inactive'))
            ->when($this->tipo_filtro === 'catalogo', fn ($q) => $q->where('json_data->catalog_listing', true))
            ->when($this->vinculo_filtro === 'vinculado', fn ($q) => $q->whereNotNull('product_sku_id'))
            ->when($this->vinculo_filtro === 'nao_vinculado', fn ($q) => $q->whereNull('product_sku_id'))
            ->with(['productSku.product', 'integracao'])
            ->latest()
            ->paginate(15);
    }

    /**
     * Fetch anuncios directly from Mercado Livre API
     */
    protected function fetchAnunciosFromMeli($integracao)
    {
        $cacheKey = 'ml_anuncios_'.$integracao->empresa_id;

        // Try to get from cache (5 minutes)
        $cached = cache()->get($cacheKey);
        if ($cached) {
            // Apply filters to cached data
            $filtered = collect($cached);

            // Filtro de busca (search)
            if ($this->search) {
                $searchTerm = mb_strtolower($this->search, 'UTF-8');
                $filtered = $filtered->filter(fn ($a) => mb_strpos(mb_strtolower($a->titulo ?? '', 'UTF-8'), $searchTerm) !== false ||
                    mb_strpos(mb_strtolower($a->external_id ?? '', 'UTF-8'), $searchTerm) !== false ||
                    mb_strpos(mb_strtolower($a->sku ?? '', 'UTF-8'), $searchTerm) !== false
                );
            }

            if ($this->status_filtro === 'ativo') {
                $filtered = $filtered->where('status', 'active');
            } elseif ($this->status_filtro === 'inativo') {
                $filtered = $filtered->filter(fn ($a) => ($a->status ?? '') !== 'active');
            }

            if ($this->tipo_filtro === 'catalogo') {
                $filtered = $filtered->filter(fn ($a) => ! empty($a->json_data['catalog_listing'] ?? false));
            }

            if ($this->vinculo_filtro === 'vinculado') {
                $filtered = $filtered->whereNotNull('product_sku_id');
            } elseif ($this->vinculo_filtro === 'nao_vinculado') {
                $filtered = $filtered->whereNull('product_sku_id');
            }

            $page = (int) request()->get('page', 1);

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $filtered->forPage($page, 15),
                $filtered->count(),
                15,
                $page,
                ['path' => request()->url()]
            );
        }

        // Fetch from API
        $anuncios = [];
        $userId = $integracao->external_user_id;
        $token = $integracao->access_token;
        $offset = 0;
        $limit = 100;

        try {
            do {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer '.$token,
                ])->get("https://api.mercadolibre.com/users/{$userId}/items/search", [
                    'limit' => $limit,
                    'offset' => $offset,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $items = $data['results'] ?? [];

                    // Get details for each item
                    foreach ($items as $itemId) {
                        $itemResponse = \Illuminate\Support\Facades\Http::withHeaders([
                            'Authorization' => 'Bearer '.$token,
                        ])->get("https://api.mercadolibre.com/items/{$itemId}");

                        if ($itemResponse->successful()) {
                            $item = $itemResponse->json();

                            $anuncios[] = (object) [
                                'id' => 0,
                                'external_id' => $itemId,
                                'titulo' => $item['title'] ?? $itemId,
                                'preco' => floatval($item['price'] ?? 0),
                                'estoque' => intval($item['available_quantity'] ?? 0),
                                'status' => $item['status'] ?? 'active',
                                'sku' => $item['seller_custom_field'] ?? null,
                                'marketplace' => 'mercadolivre',
                                'empresa_id' => $integracao->empresa_id,
                                'integracao_id' => $integracao->id,
                                'json_data' => $item,
                                'frete_custo_seller' => 0,
                                'product_sku_id' => null,
                                'produto_id' => null,
                                'repricerConfig' => null,
                                'created_at' => now(),
                            ];
                        }
                    }

                    $offset += $limit;
                }
            } while (count($items) === $limit);

            // Cache for 5 minutes
            cache()->put($cacheKey, $anuncios, now()->addMinutes(5));

        } catch (\Exception $e) {
            \Log::error('Erro ao buscar anuncios ML: '.$e->getMessage());
        }

        // Apply filters to API data
        $filtered = collect($anuncios);

        // Filtro de busca (search)
        if ($this->search) {
            $searchTerm = mb_strtolower($this->search, 'UTF-8');
            $filtered = $filtered->filter(fn ($a) => mb_strpos(mb_strtolower($a->titulo ?? '', 'UTF-8'), $searchTerm) !== false ||
                mb_strpos(mb_strtolower($a->external_id ?? '', 'UTF-8'), $searchTerm) !== false ||
                mb_strpos(mb_strtolower($a->sku ?? '', 'UTF-8'), $searchTerm) !== false
            );
        }

        if ($this->status_filtro === 'ativo') {
            $filtered = $filtered->where('status', 'active');
        } elseif ($this->status_filtro === 'inativo') {
            $filtered = $filtered->filter(fn ($a) => ($a->status ?? '') !== 'active');
        }

        if ($this->tipo_filtro === 'catalogo') {
            $filtered = $filtered->filter(fn ($a) => ! empty($a->json_data['catalog_listing'] ?? false));
        }

        if ($this->vinculo_filtro === 'vinculado') {
            $filtered = $filtered->whereNotNull('product_sku_id');
        } elseif ($this->vinculo_filtro === 'nao_vinculado') {
            $filtered = $filtered->whereNull('product_sku_id');
        }

        // Get page from Livewire
        $page = (int) request()->get('page', 1);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $filtered->forPage($page, 15),
            $filtered->count(),
            15,
            $page,
            ['path' => request()->url()]
        );
    }

    protected function integracoes()
    {
        return Integracao::where('empresa_id', Auth::user()->current_empresa_id)->get();
    }

    protected function produtos()
    {
        $empresaId = Auth::user()->current_empresa_id;
        if (! $empresaId || ! $this->searchProduto) {
            return [];
        }

        return Product::where('empresa_id', $empresaId)
            ->where(function ($q) {
                $searchTerm = '%'.mb_strtolower($this->searchProduto, 'UTF-8').'%';
                $q->whereRaw('LOWER(nome) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(ean) LIKE ?', [$searchTerm])
                    ->orWhereHas('skus', fn ($sq) => $sq->whereRaw('LOWER(sku) LIKE ?', [$searchTerm]));
            })
            ->with('skus')
            ->limit(10)
            ->get();
    }

    public function openJsonModal($anuncioId)
    {
        $anuncio = MarketplaceAnuncio::find($anuncioId);
        if ($anuncio) {
            $this->jsonData = $anuncio->json_data;
            $this->jsonAdTitulo = $anuncio->titulo;
            $this->showJsonModal = true;
        }
    }

    public function abrirVincular($anuncioId)
    {
        // Find the ad by ID or external_id
        $anuncio = MarketplaceAnuncio::find($anuncioId);
        
        if (!$anuncio) {
            // Try to find by external_id from the API data
            $this->anuncioSelecionado = $anuncioId;
        } else {
            $this->anuncioSelecionado = $anuncio;
        }
        
        $this->showVincularModal = true;
        $this->searchProduto = '';
    }

    public function vincularProduto($produtoId)
    {
        $produto = Product::find($produtoId);
        if (!$produto) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Produto não encontrado']);
            return;
        }
        
        // Get first SKU or create one
        $sku = $produto->skus()->first();
        
        if (!$sku) {
            // Create a new SKU if none exists
            $sku = $produto->skus()->create([
                'sku' => $produto->id . '-default',
                'label' => 'Padrão',
                'preco_venda' => $produto->preco_venda,
                'estoque' => 0,
            ]);
        }
        
        // Get the ad (can be MarketplaceAnuncio or just an ID from API)
        $anuncioId = $this->anuncioSelecionado;
        
        if ($anuncioId instanceof MarketplaceAnuncio) {
            $anuncioId->update(['product_sku_id' => $sku->id]);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Produto vinculado com sucesso!']);
        } else {
            // It's an API ad ID - we need to create a record
            // For now, just show info
            $this->dispatch('notify', ['type' => 'info', 'message' => 'Funcionalidade em desenvolvimento para anúncios da API']);
        }
        
        $this->showVincularModal = false;
        $this->anuncioSelecionado = null;
    }

    public function getMedidas($jsonData)
    {
        $medidas = [];

        if (! empty($jsonData['attributes'])) {
            foreach ($jsonData['attributes'] as $attr) {
                $attrId = $attr['id'] ?? '';
                $attrValue = $attr['value_name'] ?? $attr['value'] ?? '';

                if (in_array($attrId, ['SELLER_PACKAGE_HEIGHT', 'PACKAGE_HEIGHT'])) {
                    $medidas['altura'] = $attrValue;
                }
                if (in_array($attrId, ['SELLER_PACKAGE_LENGTH', 'PACKAGE_LENGTH'])) {
                    $medidas['comprimento'] = $attrValue;
                }
                if (in_array($attrId, ['SELLER_PACKAGE_WIDTH', 'PACKAGE_WIDTH'])) {
                    $medidas['largura'] = $attrValue;
                }
                if (in_array($attrId, ['SELLER_PACKAGE_WEIGHT', 'PACKAGE_WEIGHT', 'WEIGHT'])) {
                    $medidas['peso'] = $attrValue;
                }
            }
        }

        return $medidas;
    }

    public function getSkuMl($jsonData)
    {
        if (! empty($jsonData['seller_custom_field'])) {
            return $jsonData['seller_custom_field'];
        }

        if (! empty($jsonData['attributes'])) {
            foreach ($jsonData['attributes'] as $attr) {
                if (($attr['id'] ?? '') === 'SELLER_SKU') {
                    return $attr['value_name'] ?? $attr['value'] ?? null;
                }
            }
        }

        return null;
    }

    public function getShippingInfo($jsonData)
    {
        $shipping = $jsonData['shipping'] ?? [];

        $info = [
            'mode' => $shipping['mode'] ?? 'N/A',
            'free_shipping' => ! empty($shipping['free_shipping']),
            'logistic_type' => $shipping['logistic_type'] ?? 'N/A',
            'local_pick_up' => ! empty($shipping['local_pick_up']),
            'store_pick_up' => ! empty($shipping['store_pick_up']),
        ];

        // Mapeia tipos de logística para nomes amigáveis
        $logisticNames = [
            'fulfillment' => 'Full',
            'cross_docking' => 'Cross-docking',
            'drop_off' => 'Drop-off',
            'me2' => 'Mercado Envios',
            'not_specified' => 'Não especificado',
        ];

        $info['logistic_name'] = $logisticNames[$info['logistic_type']] ?? $info['logistic_type'];

        return $info;
    }

    /**
     * Calcula valor estimado dofrete com base nas dimensões
     */
    public function getFreteEstimado($jsonData, $marketplace = 'mercadolivre')
    {
        if ($marketplace !== 'mercadolivre') {
            return null;
        }

        // Extrair dimensões dos atributos
        $peso = 0;
        $altura = 0;
        $largura = 0;
        $comprimento = 0;

        if (! empty($jsonData['attributes'])) {
            foreach ($jsonData['attributes'] as $attr) {
                $attrId = $attr['id'] ?? '';
                // O value pode ser string ou array com struct
                $attrValue = $attr['value_name'] ?? $attr['value'] ?? $attr;

                // Peso (em kg)
                if (in_array($attrId, ['WEIGHT', 'PACKAGE_WEIGHT', 'SELLER_PACKAGE_WEIGHT'])) {
                    $peso = $this->convertWeightToKg($attrValue);
                }
                // Dimensões (em cm)
                if (in_array($attrId, ['PACKAGE_HEIGHT', 'SELLER_PACKAGE_HEIGHT'])) {
                    $altura = $this->convertDimensionToCm($attrValue);
                }
                if (in_array($attrId, ['PACKAGE_WIDTH', 'SELLER_PACKAGE_WIDTH'])) {
                    $largura = $this->convertDimensionToCm($attrValue);
                }
                if (in_array($attrId, ['PACKAGE_LENGTH', 'SELLER_PACKAGE_LENGTH'])) {
                    $comprimento = $this->convertDimensionToCm($attrValue);
                }
            }
        }

        // Se não tiver dimensões, retorna null
        if ($peso <= 0 || $altura <= 0 || $largura <= 0 || $comprimento <= 0) {
            return null;
        }

        // Converter para gramas
        $pesoGramas = $peso * 1000;

        // Calcular peso volumétrico (comprimento x largura x altura / 6000)
        $pesoVolumetrico = ($comprimento * $largura * $altura) / 6;

        // Usar o maior peso
        $pesoCobravel = max($pesoGramas, $pesoVolumetrico);

        // Calcular custo estimado baseado em faixas de preço do ML (2024)
        // Faixas aproximadas do Mercado Envios
        $custoEstimado = $this->calculateMeliShippingCost($pesoCobravel);

        return [
            'peso_real_kg' => round($peso, 3),
            'peso_real_g' => round($pesoGramas, 0),
            'peso_volumetrico_g' => round($pesoVolumetrico, 0),
            'peso_cobravel_g' => round($pesoCobravel, 0),
            'dimensoes' => [
                'altura' => $altura,
                'largura' => $largura,
                'comprimento' => $comprimento,
            ],
            'frete_estimado' => $custoEstimado,
            'frete_formatado' => $custoEstimado ? 'R$ '.number_format($custoEstimado, 2, ',', '.') : 'N/A',
        ];
    }

    /**
     * Calcula custo defrete baseado nas faixas do Mercado Livre
     * Valores aproximados para 2024
     */
    private function calculateMeliShippingCost(float $pesoGramas): ?float
    {
        // Faixas de preço do Mercado Envios (valores aproximados)
        // Podem variar por região e tipo de logística

        $faixas = [
            ['max' => 300, 'valor' => 8.90],    // Até 300g
            ['max' => 500, 'valor' => 10.90],   // Até 500g
            ['max' => 1000, 'valor' => 12.90], // Até 1kg
            ['max' => 1500, 'valor' => 14.90],  // Até 1.5kg
            ['max' => 2000, 'valor' => 16.90],  // Até 2kg
            ['max' => 3000, 'valor' => 19.90],  // Até 3kg
            ['max' => 5000, 'valor' => 24.90],  // Até 5kg
            ['max' => 10000, 'valor' => 34.90], // Até 10kg
            ['max' => 15000, 'valor' => 44.90], // Até 15kg
            ['max' => 20000, 'valor' => 54.90], // Até 20kg
            ['max' => 25000, 'valor' => 64.90], // Até 25kg
            ['max' => 30000, 'valor' => 74.90], // Até 30kg
        ];

        foreach ($faixas as $faixa) {
            if ($pesoGramas <= $faixa['max']) {
                return $faixa['valor'];
            }
        }

        // Para pesos maiores, calcula valor adicional
        $valorBase = 74.90;
        $pesoExcedente = ($pesoGramas - 30000) / 5000; // R$ 10 por kg adicional

        return round($valorBase + ($pesoExcedente * 10), 2);
    }

    /**
     * Calcula o valor do frete cobrado na venda do lojista se o frete não for gratis
     * ou o custo de frete grátis via API do ML.
     */
    public function calculofretemercadolivre($integracaoId, $mlb)
    {
        try {
            $integracao = Integracao::find($integracaoId);
            if (! $integracao || $integracao->marketplace !== 'mercadolivre') {
                return 0;
            }

            $meliService = new \App\Services\MeliIntegrationService($integracao->empresa_id);
            $resultado = $meliService->obterCustoFreteMercadoLivre($mlb);

            return $resultado['cost'] ?? 0;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro calculofretemercadolivre: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Converte peso para kg
     */
    private function convertWeightToKg($value): float
    {
        if (! $value) {
            return 0;
        }

        // Verificar se é um array com struct
        if (is_array($value)) {
            if (isset($value['number']) && isset($value['unit'])) {
                $num = floatval($value['number']);
                $unit = strtolower($value['unit']);
                if ($unit === 'g') {
                    return $num / 1000;
                }

                return $num;
            }

            // Se não tiver struct, retorna 0
            return 0;
        }

        $value = trim($value);

        // Se for string
        if (is_string($value)) {
            // Verificar se tem formato "number unit" (ex: "1010 g")
            if (preg_match('/([\d.,]+)\s*(kg|g)/i', $value, $matches)) {
                $num = floatval(str_replace(',', '.', $matches[1]));
                if (strtolower($matches[2]) === 'g') {
                    return $num / 1000;
                }

                return $num;
            }

            // Se for só número
            if (is_numeric($value)) {
                $num = floatval($value);
                // Se for muito pequeno, assume kg
                if ($num < 10) {
                    return $num;
                }

                // Se for maior, assume g
                return $num / 1000;
            }
        }

        return 0;
    }

    /**
     * Converte dimensão para cm
     */
    private function convertDimensionToCm($value): float
    {
        if (! $value) {
            return 0;
        }

        // Verificar se é um array com struct
        if (is_array($value)) {
            if (isset($value['number']) && isset($value['unit'])) {
                return floatval($value['number']);
            }

            return 0;
        }

        $value = trim($value);

        // Se for string
        if (is_string($value)) {
            // Verificar se tem formato "number unit" (ex: "2.4 cm")
            if (preg_match('/([\d.,]+)/', $value, $matches)) {
                return floatval(str_replace(',', '.', $matches[1]));
            }
        }

        return 0;
    }

    private function convertWeightToGrams($value): float
    {
        if (! $value) {
            return 0;
        }

        if (is_array($value)) {
            if (isset($value['number']) && isset($value['unit'])) {
                $num = floatval($value['number']);
                $unit = strtolower($value['unit']);
                if (strpos($unit, 'kg') !== false || strpos($unit, 'kilo') !== false) {
                    return $num * 1000;
                }

                return $num;
            }

            return 0;
        }

        $value = trim($value);

        if (preg_match("/([\d.,]+)/", $value, $matches)) {
            $num = floatval(str_replace(',', '.', $matches[1]));
            if (stripos($value, 'kg') !== false || stripos($value, 'kilo') !== false) {
                return $num * 1000;
            }

            return $num;
        }

        return 0;
    }

    public function getTipoAnuncio($jsonData)
    {
        $listingType = $jsonData['listing_type_id'] ?? '';

        $tipos = [
            'gold_pro' => ['nome' => 'Gold Pro', 'cor' => 'purple', 'icone' => 'crown'],
            'gold_special' => ['nome' => 'Gold Special', 'cor' => 'yellow', 'icone' => 'star'],
            'gold' => ['nome' => 'Gold', 'cor' => 'amber', 'icone' => 'star'],
            'classic' => ['nome' => 'Clássico', 'cor' => 'blue', 'icone' => 'bookmark'],
            'premium' => ['nome' => 'Premium', 'cor' => 'indigo', 'icone' => 'gem'],
            'free' => ['nome' => 'Grátis', 'cor' => 'gray', 'icone' => 'gift'],
        ];

        return $tipos[$listingType] ?? ['nome' => $listingType, 'cor' => 'gray', 'icone' => 'tag'];
    }

    public function getMarketplaceInfo($marketplace)
    {
        $infos = [
            'mercadolivre' => [
                'nome' => 'Mercado Livre',
                'cor' => 'yellow',
                'icone' => 'fab fa-mercury',
                'bg' => 'from-yellow-500 to-orange-600',
                'logo' => '/images/marketplaces/mercado-livre.svg',
            ],
            'amazon' => [
                'nome' => 'Amazon',
                'cor' => 'orange',
                'icone' => 'fab fa-amazon',
                'bg' => 'from-orange-400 to-yellow-500',
                'logo' => '/images/marketplaces/amazon.svg',
            ],
            'bling' => [
                'nome' => 'Bling',
                'cor' => 'blue',
                'icone' => 'fas fa-bullseye',
                'bg' => 'from-blue-500 to-blue-600',
                'logo' => '/images/marketplaces/bling.svg',
            ],
            'shopee' => [
                'nome' => 'Shopee',
                'cor' => 'red',
                'icone' => 'fas fa-shopping-bag',
                'bg' => 'from-red-500 to-pink-500',
                'logo' => '/images/marketplaces/shopee.svg',
            ],
            'magalu' => [
                'nome' => 'Magalu',
                'cor' => 'green',
                'icone' => 'fas fa-box',
                'bg' => 'from-green-500 to-emerald-600',
                'logo' => '/images/marketplaces/magalu.svg',
            ],
            'shopify' => [
                'nome' => 'Shopify',
                'cor' => 'green',
                'icone' => 'fab fa-shopify',
                'bg' => 'from-green-600 to-teal-500',
                'logo' => '/images/marketplaces/shopify.svg',
            ],
            'magazine-luiza' => [
                'nome' => 'Magazine Luiza',
                'cor' => 'red',
                'icone' => 'fas fa-box',
                'bg' => 'from-red-600 to-red-700',
                'logo' => '/images/marketplaces/magazine-luiza.svg',
            ],
            'shein' => [
                'nome' => 'Shein',
                'cor' => 'black',
                'icone' => 'fas fa-shopping-bag',
                'bg' => 'from-black to-gray-800',
                'logo' => '/images/marketplaces/shein.svg',
            ],
        ];

        return $infos[$marketplace] ?? [
            'nome' => ucfirst($marketplace),
            'cor' => 'gray',
            'icone' => 'fas fa-store',
            'bg' => 'from-slate-500 to-slate-600',
            'logo' => null,
        ];
    }

    public function isCatalogo($jsonData)
    {
        return ! empty($jsonData['catalog_listing']);
    }

    public function getUrlConcorrentes($anuncio)
    {
        if ($anuncio->marketplace !== 'mercadolivre') {
            return null;
        }

        $jsonData = $anuncio->json_data ?? [];
        $permalink = $jsonData['permalink'] ?? null;

        // Usa catalog_product_id se existir, senãouse external_id
        $productId = $jsonData['catalog_product_id'] ?? $anuncio->external_id;

        // Tenta extrair o slug do permalink
        // Ex: https://produto.mercadolivre.com.br/MLB-1234567890-produto/_JM
        if ($permalink) {
            // Extrai a parte do slug entre o ID e ? ou _JM
            if (preg_match('/MLB-\d+-(.+?)(?:\?|_JM|$)/', $permalink, $matches)) {
                $slug = $matches[1];
                // Removetrailing de caracteres especiais
                $slug = rtrim($slug, '-');

                return "https://www.mercadolivre.com.br/{$slug}/p/{$productId}/s?";
            }
        }

        // Se não conseguir extrair do permalink, tenta pelo título
        if ($anuncio->titulo && $productId) {
            $slug = strtolower(preg_replace('/[^a-z0-9\s-]/', '', $anuncio->titulo));
            $slug = preg_replace('/\s+/', '-', $slug);
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');

            return "https://www.mercadolivre.com.br/{$slug}/p/{$productId}/s?";
        }

        return null;
    }

    public function getTaxasEstimadas($preco, $listingType, $companyId = 1)
    {
        // Primeiro tenta obter da API do ML
        $meliService = app(\App\Services\MeliIntegrationService::class);

        // Obter logistics do anúncio se disponível
        $logisticType = null;

        $pricing = $meliService->getListingPrices([
            'price' => $preco,
            'listing_type_id' => $listingType,
            'logistic_type' => $logisticType,
        ]);

        if ($pricing && isset($pricing['sale_fee_amount'])) {
            $feeDetails = $pricing['sale_fee_details'] ?? [];

            return [
                'taxa_venda' => $feeDetails['percentage_value'] ?? $pricing['sale_fee_amount'],
                'taxa_fixa' => $feeDetails['fixed_fee'] ?? 0,
                'taxa_pagamento' => 0, // Já incluída na percentage
                'total_taxas' => $pricing['sale_fee_amount'],
                'percentual' => $feeDetails['percentage_fee'] ?? 0,
                'source' => 'api',
            ];
        }

        // Fallback para cálculo local
        $taxaVenda = match ($listingType) {
            'gold_pro' => 0.10,
            'gold_special' => 0.11,
            'gold' => 0.12,
            'classic' => 0.12,
            'premium' => 0.15,
            'free' => 0.13,
            default => 0.12,
        };

        $taxaFixa = 0.50;
        $taxaPagamento = 0.0389;

        $valorTaxaVenda = $preco * $taxaVenda;
        $valorTaxaPagamento = $preco * $taxaPagamento;

        return [
            'taxa_venda' => $valorTaxaVenda,
            'taxa_fixa' => $taxaFixa,
            'taxa_pagamento' => $valorTaxaPagamento,
            'total_taxas' => $valorTaxaVenda + $taxaFixa + $valorTaxaPagamento,
            'percentual' => $taxaVenda * 100,
            'source' => 'local',
        ];
    }

    public function getPreco($anuncio)
    {
        return $anuncio->preco ?? 0;
    }

    public function calcularLucratividade($anuncio)
    {
        $preco = $anuncio->preco ?? 0;
        
        // Get custo - try to get from relationship, fallback to direct access
        $custo = 0;
        $custoAdicional = 0;
        
        // Check if it's a database model with relationship
        if (is_object($anuncio) && method_exists($anuncio, 'productSku')) {
            $sku = $anuncio->productSku;
            if ($sku) {
                // Try SKU custo first, then product custo
                $custo = floatval($sku->preco_custo ?? 0);
                if ($sku->product) {
                    $custoAdicional = floatval($sku->product->custo_adicional ?? 0);
                }
                if ($custo == 0 && $sku->product) {
                    $custo = floatval($sku->product->preco_custo ?? 0);
                    $custoAdicional = floatval($sku->product->custo_adicional ?? 0);
                }
            }
        }
        
        // If still 0, try direct property access
        if ($custo == 0 && isset($anuncio->preco_custo)) {
            $custo = floatval($anuncio->preco_custo);
        }
        
        // Custo adicional (etiqueta/embalagem)
        if (!isset($anuncio->custo_adicional) && isset($anuncio->custo_adicional)) {
            $custoAdicional = floatval($anuncio->custo_adicional);
        }

        $jsonData = $anuncio->json_data ?? [];
        $listingType = $jsonData['listing_type_id'] ?? 'gold_special';

        $taxas = $this->getTaxasEstimadas($preco, $listingType);

        $valorTaxas = $taxas['total_taxas'];

        // Frete
        $frete = floatval($anuncio->frete_custo_seller ?? 0);
        $freteSource = $anuncio->frete_source ?? null;
        $freteType = $anuncio->frete_type ?? null;
        $freteGratis = ($anuncio->frete_custo_seller ?? 0) <= 0;

        // Imposto (10% do preço)
        $imposto = $preco * 0.10;

        // Custo total = custo base + custo adicional
        $custoTotal = $custo + $custoAdicional;
        
        $lucroBruto = $preco - $custoTotal - $valorTaxas - $frete - $imposto;
        $margem = $preco > 0 ? ($lucroBruto / $preco) * 100 : 0;

        return [
            'preco' => $preco,
            'custo' => $custo,
            'custo_adicional' => $custoAdicional,
            'custo_total' => $custoTotal,
            'taxas' => $valorTaxas,
            'frete' => $frete,
            'frete_source' => $freteSource,
            'frete_type' => $freteType,
            'frete_gratis' => $freteGratis,
            'imposto' => $imposto,
            'lucro_bruto' => $lucroBruto,
            'margem' => $margem,
        ];
    }

    public function importAsProduct($anuncioId)
    {
        $anuncio = MarketplaceAnuncio::findOrFail($anuncioId);

        if ($anuncio->product_sku_id) {
            session()->flash('error', 'Este anúncio já está vinculado a um produto.');

            return;
        }

        // Buscar dados completos da API para garantir que temos tudo
        // FORÇAR a busca mesmo se já tiver algo, pois o usuário reclamou de dados faltantes
        $itemData = null;
        $description = null;

        if ($anuncio->marketplace === 'mercadolivre') {
            try {
                $service = new \App\Services\MeliIntegrationService($anuncio->empresa_id);
                $itemData = $service->getItem($anuncio->external_id);
                $description = $service->getItemDescription($anuncio->external_id);

                if ($itemData) {
                    $anuncio->update(['json_data' => $itemData]);
                }
            } catch (\Exception $e) {
                // Falhou ao buscar dados frescos, usa o que tem no banco
                \Illuminate\Support\Facades\Log::error('Erro ao buscar dados atualizados do ML para importação: '.$e->getMessage());
                $itemData = $anuncio->json_data;
                $description = null;
            }
        }

        // Se ainda nulo, tenta pegar do banco
        if (! $itemData) {
            $itemData = $anuncio->json_data;
        }

        $jsonData = is_array($itemData) ? $itemData : json_decode($itemData ?? '{}', true);

        // DEBUG: Log raw data to understand why fields are missing
        \Illuminate\Support\Facades\Log::info("Importing Product {$anuncio->external_id}. Payload:", [
            'sku_from_source' => $jsonData['seller_custom_field'] ?? 'N/A',
            'attributes_count' => count($jsonData['attributes'] ?? []),
            'pictures_count' => count($jsonData['pictures'] ?? []),
            'shipping_mode' => $jsonData['shipping']['mode'] ?? 'N/A',
            'status' => $jsonData['status'] ?? 'N/A',
        ]);

        // Mapeamento de Campos
        $nome = $jsonData['title'] ?? $anuncio->titulo;
        $descricao = $description ?? $nome; // Descrição da API ou Nome como fallback
        $precoVenda = floatval($jsonData['price'] ?? $anuncio->preco);
        $estoque = intval($jsonData['available_quantity'] ?? $anuncio->estoque);
        $externalId = $anuncio->external_id;

        // Imagens
        $fotos = [];
        if (! empty($jsonData['pictures'])) {
            foreach ($jsonData['pictures'] as $pic) {
                $fotos[] = $pic['url'] ?? $pic['secure_url'];
            }
        }

        // Atributos (NCM, CEST, Marca, EAN)
        $attributes = $jsonData['attributes'] ?? [];
        $ean = null;
        $ncm = null;
        $cest = null;
        $marca = null;
        $peso = null;
        $altura = null;
        $largura = null;
        $profundidade = null;
        $skuFromApi = $jsonData['seller_custom_field'] ?? null; // Tenta pegar do campo direto primeiro

        foreach ($attributes as $attr) {
            $attrId = $attr['id'] ?? '';
            $attrValue = $attr['value_name'] ?? $attr['value_id'] ?? null;

            if ($attrValue) {
                if ($attrId === 'GTIN' || $attrId === 'EAN') {
                    $ean = $attrValue;
                }
                if ($attrId === 'NCM') {
                    $ncm = $attrValue;
                }
                if ($attrId === 'CEST') {
                    $cest = $attrValue;
                }
                if ($attrId === 'BRAND') {
                    $marca = $attrValue;
                }
                if ($attrId === 'SELLER_SKU') {
                    $skuFromApi = $attrValue;
                } // Pega SKU dos atributos

                // Dimensões nos atributos (algumas categorias usam)
                if ($attrId === 'PACKAGE_HEIGHT') {
                    $altura = $this->extractNumber($attrValue);
                }
                if ($attrId === 'PACKAGE_WIDTH') {
                    $largura = $this->extractNumber($attrValue);
                }
                if ($attrId === 'PACKAGE_LENGTH') {
                    $profundidade = $this->extractNumber($attrValue);
                }
                if ($attrId === 'PACKAGE_WEIGHT') {
                    $peso = $this->convertWeightToGrams($attrValue) / 1000;
                } // Convert to Kg
            }
        }

        // Info de envio
        $shipping = $jsonData['shipping'] ?? [];
        $modoEnvio = $shipping['mode'] ?? 'me2';
        $logistica = $shipping['logistic_type'] ?? 'not_specified';
        $freteGratis = ! empty($shipping['free_shipping']);

        $precoCusto = $precoCusto ?? 0;
        $variacoes = $jsonData['variations'] ?? [];
        $tipoProduto = ! empty($variacoes) ? 'variacao' : 'simples';

        $slug = \Illuminate\Support\Str::slug($nome);
        $slugBase = $slug;
        $contador = 1;
        while (\App\Models\Product::where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$contador;
            $contador++;
        }

        $product = Product::create([
            'empresa_id' => $anuncio->empresa_id,
            'nome' => $nome,
            'slug' => $slug,
            'descricao' => $descricao, // Agora com descrição completa
            'marca' => $marca,
            'preco_venda' => $precoVenda,
            'preco_custo' => $precoCusto,
            'peso' => floatval($peso ?? 0),
            'altura' => floatval($altura ?? 0),
            'largura' => floatval($largura ?? 0),
            'profundidade' => floatval($profundidade ?? 0),
            'ean' => $ean,
            'ncm' => $ncm,
            'cest' => $cest,
            'tipo' => $tipoProduto,
            'ativo' => $anuncio->status === 'active',
            // Campos adicionais para integração ML
            'marketplace' => $anuncio->marketplace,
            'external_id' => $anuncio->external_id,
            'marketplace_url' => $jsonData['permalink'] ?? null,
            'condicao' => $jsonData['condition'] ?? 'new',
            'foto_principal' => $fotos[0] ?? null, // Salva primeira foto
            'fotos_galeria' => count($fotos) > 1 ? json_encode(array_slice($fotos, 1)) : null, // Resto na galeria
        ]);

        if ($product) {
            if (! empty($variacoes)) {
                $firstSkuId = null;
                $varIndex = 0;
                foreach ($variacoes as $variacao) {
                    $varIndex++;
                    $skuVariacao = $variacao['seller_custom_field'] ?? $variacao['sku'] ?? 'VAR-'.$variacao['id'] ?? null;
                    if (! $skuVariacao) {
                        $skuVariacao = 'VAR-'.$product->id.'-'.$varIndex;
                    }
                    $precoVariacao = floatval($variacao['price'] ?? $precoVenda);
                    $estoqueVariacao = intval($variacao['available_quantity'] ?? $variacao['stock'] ?? 0);

                    $sku = ProductSku::create([
                        'product_id' => $product->id,
                        'sku' => $skuVariacao,
                        'preco_venda' => $precoVariacao,
                        'estoque' => $estoqueVariacao,
                        'gtin' => $variacao['gtin'] ?? null,
                    ]);

                    if ($firstSkuId === null) {
                        $firstSkuId = $sku->id;
                    }
                }
                if ($firstSkuId) {
                    $anuncio->update(['product_sku_id' => $firstSkuId]);
                }
            } else {
                // Fallback para SKU de produto simples
                // Prioridade: SKU da API > SKU do Banco > External ID
                $skuSimples = $skuFromApi;
                if (empty($skuSimples)) {
                    $skuSimples = $anuncio->sku;
                }
                if (empty($skuSimples)) {
                    $skuSimples = $externalId; // Fallback final
                }

                $productSku = ProductSku::create([
                    'product_id' => $product->id,
                    'sku' => $skuSimples,
                    'preco_venda' => $anuncio->preco,
                    'estoque' => $anuncio->estoque,
                    'gtin' => $ean,
                    'ncm' => $ncm,
                ]);

                $anuncio->update(['product_sku_id' => $productSku->id]);
            }
        }

        session()->flash('success', 'Produto criado com sucesso a partir do anúncio!');

        return $product;
    }

    public function toggleStatus($anuncioId)
    {
        $anuncio = MarketplaceAnuncio::findOrFail($anuncioId);
        $newStatus = $anuncio->status === 'active' ? 'inactive' : 'active';
        $anuncio->update(['status' => $newStatus]);

        if ($anuncio->marketplace === 'mercadolivre' && $anuncio->integracao) {
            try {
                $service = new \App\Services\MeliIntegrationService($anuncio->empresa_id);
                $service->updateAnuncioStatus($anuncio->external_id, $newStatus === 'active');
            } catch (\Exception $e) {
                // Continua mesmo se der erro na API
            }
        }
    }

    public function editarAnuncio($anuncioId)
    {
        $this->dispatch('abrir-editar-anuncio', $anuncioId);
    }

    public $showRepricerModal = false;

    public $repricerAnuncioId = null;

    public $repricerConfig = [];

    public function openRepricer($anuncioId)
    {
        $this->repricerAnuncioId = $anuncioId;
        $anuncio = \App\Models\MarketplaceAnuncio::find($anuncioId);

        $config = \App\Models\AnuncioRepricerConfig::where('marketplace_anuncio_id', $anuncioId)->first();

        $this->repricerConfig = $config ? [
            'strategy' => $config->strategy ?? 'igualar_menor',
            'offset_value' => $config->offset_value ?? 0,
            'min_profit_margin' => $config->min_profit_margin ?? null,
        ] : [
            'strategy' => 'igualar_menor',
            'offset_value' => 0,
            'min_profit_margin' => null,
        ];

        $this->showRepricerModal = true;
    }

    public function saveRepricerConfig()
    {
        $this->validate([
            'repricerConfig.strategy' => 'required|in:igualar_menor,valor_abaixo,valor_acima',
            'repricerConfig.offset_value' => 'required|numeric|min:0',
            'repricerConfig.min_profit_margin' => 'nullable|numeric',
        ]);

        \App\Models\AnuncioRepricerConfig::updateOrCreate(
            ['marketplace_anuncio_id' => $this->repricerAnuncioId],
            $this->repricerConfig
        );

        $this->showRepricerModal = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Configuração do Repricer salva!']);
    }

    public function syncAnuncio($anuncioId)
    {
        $anuncio = MarketplaceAnuncio::find($anuncioId);
        if (! $anuncio || $anuncio->marketplace !== 'mercadolivre') {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Apenas anúncios do Mercado Livre suportados']);

            return;
        }

        $integracao = $anuncio->integracao;
        if (! $integracao) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Integração não encontrada']);

            return;
        }

        $meliService = new \App\Services\MeliService;

        // Busca dados atualizados do item na API
        $itemResponse = \Illuminate\Support\Facades\Http::withToken($integracao->access_token)
            ->get("https://api.mercadolibre.com/items/{$anuncio->external_id}?include_attributes=all");

        if (! $itemResponse->successful()) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Erro ao buscar dados do ML']);

            return;
        }

        $data = $itemResponse->json();

        // Busca custo dofrete
        $freteData = $meliService->getFreteCusto($integracao, $anuncio->external_id);

        // Atualiza o anúncio com todos os dados
        $anuncio->update([
            'preco' => $data['price'],
            'estoque' => $data['available_quantity'],
            'status' => $data['status'],
            'url_anuncio' => $data['permalink'],
            'json_data' => $data,
            'frete_custo_seller' => $freteData['cost'],
            'frete_source' => $freteData['source'],
            'frete_updated_at' => now(),
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Anúncio sincronizado! Preço: R$ '.number_format($data['price'], 2, ',', '.').' | Frete: R$ '.number_format($freteData['cost'], 2, ',', '.'),
        ]);
    }

    public function render()
    {
        return view('livewire.integrations.anuncios', [
            'anuncios' => $this->anuncios(),
            'integracoes' => $this->integracoes(),
            'produtos' => $this->produtos(),
        ]);
    }

    private function extractNumber($value)
    {
        if (is_numeric($value)) {
            return floatval($value);
        }

        if (is_string($value) && preg_match('/([\d\.,]+)/', $value, $matches)) {
            // Remove 'thousand' separators if present (logic can be complex, assuming standard float for now or simple replace)
            // For simplicity in BR/US mix, let's just replace comma with dot if it looks like a decimal separator
            return floatval(str_replace(',', '.', $matches[1]));
        }

        return 0;
    }
}
