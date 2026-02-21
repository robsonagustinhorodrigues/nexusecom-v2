<?php

namespace App\Livewire\Products;

use App\Models\Categoria;
use App\Models\Fornecedor;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductSku;
use App\Models\Tag;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public $productId = null;

    public $nome;

    public $marca;

    public $ean;

    public $sku; // Added SKU for simple products

    public $tipo = 'simples';
    
    // Compound product components
    public $components = [];
    public $searchComponent = '';
    public $componentSearchResults = [];

    public $descricao;

    public $categoria_id;

    public $tags = [];

    public $fornecedor_id;

    public $newTag = '';

    public $unidade_medida = 'UN';

    public $ncm = '';

    public $cest = '';

    public $origem = '0';

    public $preco_venda = 0;

    public $preco_custo = 0;

    public $custo_adicional = 0;

    public $quantidade_virtual = 0;

    public $usar_virtual = false;
    
    public $marketplace_url = '';

    public $peso = 0;

    public $altura = 0;

    public $largura = 0;

    public $profundidade = 0;

    public $ativo = true;

    public $variantGroups = [];

    public $variations = [];

    protected $rules = [
        'nome' => 'required|min:3',
        'marca' => 'nullable',
        'tipo' => 'required',
    ];

    public function mount($id = null)
    {
        if ($id) {
            $this->productId = $id;
            $this->loadProduct($id);
        } else {
            $this->variantGroups[] = ['name' => '', 'values' => []];
        }
    }

    protected function loadProduct(int $id): void
    {
        $product = Product::with('skus')->findOrFail($id);

        $this->nome = $product->nome;
        $this->marca = $product->marca;
        $this->ean = $product->ean;
        $this->tipo = $product->tipo;
        $this->descricao = $product->descricao;
        $this->categoria_id = $product->categoria_id;
        $this->tags = $product->tags ?? [];
        $this->unidade_medida = $product->unidade_medida;
        $this->ncm = $product->ncm;
        $this->cest = $product->cest;
        $this->origem = $product->origem;
        $this->preco_venda = $product->preco_venda;
        $this->preco_custo = $product->preco_custo;
        $this->peso = $product->peso;
        $this->altura = $product->altura;
        $this->largura = $product->largura;
        $this->profundidade = $product->profundidade;
        $this->ativo = $product->ativo;

        // Load variations
        if ($product->tipo === 'variacao' && $product->skus->isNotEmpty()) {
            $this->variations = $product->skus->map(function ($sku) {
                return [
                    'sku' => $sku->sku,
                    'gtin' => $sku->gtin,
                    'preco_venda' => $sku->preco_venda,
                    'preco_custo' => $sku->preco_custo,
                    'estoque' => $sku->estoque,
                    'fornecedor_id' => $sku->fornecedor_id,
                    'label' => $sku->label,
                    'atributos' => $sku->atributos_json ?? [],
                ];
            })->toArray();

            // Group attributes
            $attributeGroups = [];
            foreach ($this->variations as $variation) {
                if (! empty($variation['atributos'])) {
                    foreach ($variation['atributos'] as $key => $value) {
                        if (! isset($attributeGroups[$key])) {
                            $attributeGroups[$key] = ['name' => $key, 'values' => []];
                        }
                        if (! in_array($value, $attributeGroups[$key]['values'])) {
                            $attributeGroups[$key]['values'][] = $value;
                        }
                    }
                }
            }
            $this->variantGroups = array_values($attributeGroups);
            if (empty($this->variantGroups)) {
                $this->variantGroups[] = ['name' => '', 'values' => []];
            }
        } else {
            $this->variantGroups[] = ['name' => '', 'values' => []];
            // Load SKU for simple product
            $skuSimples = $product->skus->first();
            if ($skuSimples) {
                $this->sku = $skuSimples->sku;
            }
        }
        
        // Load compound components
        if ($product->tipo === 'composto') {
            foreach ($product->components as $comp) {
                $this->components[] = [
                    'product_id' => $comp->component_product_id,
                    'nome' => $comp->componentProduct->nome ?? 'Produto #'.$comp->component_product_id,
                    'sku' => $comp->componentProduct->sku ?? '',
                    'preco_venda' => $comp->componentProduct->preco_venda ?? 0,
                    'preco_custo' => $comp->componentProduct->preco_custo ?? 0,
                    'estoque' => $comp->componentProduct->estoque ?? 0,
                    'quantity' => $comp->quantity,
                    'unit_price' => $comp->unit_price,
                ];
            }
        }
    }

    public function getCategoriasProperty()
    {
        return Categoria::whereNull('categoria_pai_id')
            ->with('filhas')
            ->orderBy('nome')
            ->get();
    }

    public function getTagsDisponiveisProperty()
    {
        return Tag::orderBy('nome')->get();
    }

    public function getFornecedoresProperty()
    {
        return Fornecedor::orderBy('razao_social')->get();
    }

    public function addGroup()
    {
        if (count($this->variantGroups) < 3) {
            $this->variantGroups[] = ['name' => '', 'values' => []];
        }
    }

    public function removeGroup($index)
    {
        unset($this->variantGroups[$index]);
        $this->variantGroups = array_values($this->variantGroups);
        $this->generateVariations();
    }

    public function addVariantValue($groupIndex, $value)
    {
        if (empty($value)) {
            return;
        }

        if (! in_array($value, $this->variantGroups[$groupIndex]['values'])) {
            $this->variantGroups[$groupIndex]['values'][] = trim($value);
            $this->generateVariations();
        }
    }

    public function removeVariantValue($groupIndex, $valueIndex)
    {
        unset($this->variantGroups[$groupIndex]['values'][$valueIndex]);
        $this->variantGroups[$groupIndex]['values'] = array_values($this->variantGroups[$groupIndex]['values']);
        $this->generateVariations();
    }

    public function generateVariations()
    {
        $activeGroups = array_filter($this->variantGroups, fn ($g) => ! empty($g['name']) && ! empty($g['values']));

        if (empty($activeGroups)) {
            $this->variations = [];

            return;
        }

        $combinations = [[]];
        foreach ($activeGroups as $group) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($group['values'] as $value) {
                    $newCombinations[] = array_merge($combination, [$group['name'] => $value]);
                }
            }
            $combinations = $newCombinations;
        }

        $this->variations = array_map(function ($combo) {
            $label = implode(' / ', array_values($combo));
            $existing = collect($this->variations)->first(fn ($v) => $v['label'] === $label);

            return [
                'label' => $label,
                'sku' => $existing['sku'] ?? '',
                'gtin' => $existing['gtin'] ?? '',
                'preco_venda' => $existing['preco_venda'] ?? '',
                'preco_custo' => $existing['preco_custo'] ?? '',
                'estoque' => $existing['estoque'] ?? 0,
                'fornecedor_id' => $existing['fornecedor_id'] ?? '',
                'atributos' => $combo,
            ];
        }, $combinations);
    }

    public function addTag()
    {
        if (empty(trim($this->newTag))) {
            return;
        }

        $tagNome = trim($this->newTag);
        if (! in_array($tagNome, $this->tags)) {
            $this->tags[] = $tagNome;
        }
        $this->newTag = '';
    }

    public function removeTag($index)
    {
        unset($this->tags[$index]);
        $this->tags = array_values($this->tags);
    }

    public function save()
    {
        $this->validate();

        $empresaId = \Illuminate\Support\Facades\Auth::user()->current_empresa_id;

        $slug = Str::slug($this->nome);
        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $slugBase = $slug;
            $contador = 1;
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $slugBase.'-'.$contador;
                $contador++;
            }
            $product->update([
                'nome' => $this->nome,
                'slug' => $slug,
                'marca' => $this->marca,
                'ean' => $this->ean,
                'descricao' => $this->descricao,
                'tipo' => $this->tipo,
                'categoria_id' => $this->categoria_id ?: null,
                'tags' => array_values($this->tags),
                'unidade_medida' => $this->unidade_medida,
                'ncm' => $this->ncm,
                'cest' => $this->cest,
                'origem' => $this->origem,
                'preco_venda' => $this->preco_venda,
                'preco_custo' => $this->preco_custo,
                'custo_adicional' => $this->custo_adicional,
                'peso' => $this->peso,
                'altura' => $this->altura,
                'largura' => $this->largura,
                'profundidade' => $this->profundidade,
                'ativo' => $this->ativo,
            ]);

            // Delete old SKUs and recreate
            if ($this->tipo === 'variacao') {
                $product->skus()->delete();
                foreach ($this->variations as $var) {
                    ProductSku::create([
                        'product_id' => $product->id,
                        'sku' => $var['sku'] ?: $product->id.'-'.Str::slug($var['label']),
                        'gtin' => $var['gtin'],
                        'label' => $var['label'],
                        'preco_venda' => $var['preco_venda'] ?: 0,
                        'preco_custo' => $var['preco_custo'] ?: 0,
                        'estoque' => $var['estoque'] ?: 0,
                        'fornecedor_id' => $var['fornecedor_id'] ?: null,
                        'atributos_json' => $var['atributos'],
                    ]);
                }
            }
            
            // Handle compound products
            if ($this->tipo === 'composto') {
                // Delete old components
                $product->components()->delete();
                
                // Create new components
                foreach ($this->components as $comp) {
                    ProductComponent::create([
                        'product_id' => $product->id,
                        'component_product_id' => $comp['product_id'],
                        'quantity' => $comp['quantity'] ?? 1,
                        'unit_price' => $comp['unit_price'] ?? $comp['preco_venda'],
                        'sort_order' => array_search($comp, $this->components) + 1,
                    ]);
                }
            }
            
            session()->flash('message', 'Produto atualizado com sucesso! ⚡');
        } else {
            $slugBase = $slug;
            $contador = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $slugBase.'-'.$contador;
                $contador++;
            }

            $product = Product::create([
                'empresa_id' => $empresaId,
                'nome' => $this->nome,
                'slug' => $slug,
                'marca' => $this->marca,
                'ean' => $this->ean,
                'descricao' => $this->descricao,
                'tipo' => $this->tipo,
                'categoria_id' => $this->categoria_id ?: null,
                'tags' => array_values($this->tags),
                'unidade_medida' => $this->unidade_medida,
                'ncm' => $this->ncm,
                'cest' => $this->cest,
                'origem' => $this->origem,
                'preco_venda' => $this->preco_venda,
                'preco_custo' => $this->preco_custo,
                'peso' => $this->peso,
                'altura' => $this->altura,
                'largura' => $this->largura,
                'profundidade' => $this->profundidade,
                'ativo' => $this->ativo,
            ]);

            if ($this->tipo === 'variacao') {
                foreach ($this->variations as $var) {
                    ProductSku::create([
                        'product_id' => $product->id,
                        'sku' => $var['sku'] ?: $product->id.'-'.Str::slug($var['label']),
                        'gtin' => $var['gtin'],
                        'label' => $var['label'],
                        'preco_venda' => $var['preco_venda'] ?: 0,
                        'preco_custo' => $var['preco_custo'] ?: 0,
                        'estoque' => $var['estoque'] ?: 0,
                        'fornecedor_id' => $var['fornecedor_id'] ?: null,
                        'atributos_json' => $var['atributos'],
                    ]);
                }
            } else {
                ProductSku::create([
                    'product_id' => $product->id,
                    'sku' => $this->sku ?: $product->id.'-unico',
                    'label' => 'Padrão',
                    'preco_venda' => $this->preco_venda, // Use current price
                    'estoque' => $this->quantidade_virtual, // Use current stock or virtual
                    'fornecedor_id' => $this->fornecedor_id ?: null,
                ]);
            }
            
            // Handle compound products
            if ($this->tipo === 'composto') {
                foreach ($this->components as $comp) {
                    ProductComponent::create([
                        'product_id' => $product->id,
                        'component_product_id' => $comp['product_id'],
                        'quantity' => $comp['quantity'] ?? 1,
                        'unit_price' => $comp['unit_price'] ?? $comp['preco_venda'],
                        'sort_order' => array_search($comp, $this->components) + 1,
                    ]);
                }
            }

            session()->flash('message', 'Produto cadastrado com sucesso! ⚡');
        }

        return redirect()->to('/products');
    }
    
    public function importImageFromMarketplace()
    {
        $imageUrl = null;
        
        // First: Try to get from linked announcement (if product_sku_id is set)
        if ($this->productId) {
            $product = Product::find($this->productId);
            if ($product && $product->skus->isNotEmpty()) {
                $sku = $product->skus->first();
                
                // Check if SKU has marketplace link
                if ($sku->marketplace_link) {
                    $imageUrl = $this->fetchImageFromUrl($sku->marketplace_link);
                }
                
                // If no marketplace link, check if there's an associated integration_anuncio
                if (!$imageUrl && $sku->external_id) {
                    $imageUrl = $this->fetchFromMeliByExternalId($sku->external_id);
                }
            }
        }
        
        // Second: If no linked image, try the URL provided
        if (!$imageUrl && !empty($this->marketplace_url)) {
            $imageUrl = $this->fetchImageFromUrl($this->marketplace_url);
        }
        
        if ($imageUrl) {
            $this->downloadAndSaveImage($imageUrl);
            $this->dispatch('notify', message: 'Imagem importada com sucesso!', type: 'success');
        } else {
            $this->dispatch('notify', message: 'Não foi possível encontrar a imagem', type: 'error');
        }
    }
    
    protected function fetchFromMeliByExternalId($externalId)
    {
        // Try to get from Mercado Libre API using item ID
        try {
            $response = \Illuminate\Support\Facades\Http::get("https://api.mercadolibre.com/items/{$externalId}");
            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['thumbnail'])) {
                    return $data['thumbnail'];
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Erro ao buscar imagem do ML: ' . $e->getMessage());
        }
        return null;
    }
    
    protected function fetchImageFromUrl($url)
    {
        // Detect marketplace and fetch image
        $url = trim($url);
        
        // Mercado Livre
        if (str_contains($url, 'mercadolivre') || str_contains($url, 'mercadolive') || str_contains($url, 'MLB-')) {
            // Try to get item ID from URL
            if (preg_match('/MLB-(\d+)/', $url, $matches)) {
                $itemId = $matches[1];
                try {
                    $response = \Illuminate\Support\Facades\Http::get("https://api.mercadolibre.com/items/{$itemId}");
                    if ($response->successful()) {
                        $data = $response->json();
                        if (!empty($data['thumbnail'])) {
                            return $data['thumbnail'];
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Erro ao buscar imagem do ML: ' . $e->getMessage());
                }
            }
            
            // Try OpenGraph or page parsing
            try {
                $html = \Illuminate\Support\Facades\Http::get($url)->body();
                if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $matches)) {
                    return $matches[1];
                }
            } catch (\Exception $e) {}
        }
        // Amazon
        elseif (str_contains($url, 'amazon.')) {
            try {
                $html = \Illuminate\Support\Facades\Http::get($url, [
                    'headers' => ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36']
                ])->body();
                
                if (preg_match('/"image":"([^"]+)"/', $html, $matches)) {
                    return $matches[1];
                }
                
                if (preg_match('/<img id="landingImage"[^>]+src="([^"]+)"/', $html, $matches)) {
                    return $matches[1];
                }
            } catch (\Exception $e) {}
        }
        // Shopee
        elseif (str_contains($url, 'shopee.')) {
            try {
                $html = \Illuminate\Support\Facades\Http::get($url)->body();
                if (preg_match('/"image":"([^"]+)"/', $html, $matches)) {
                    return $matches[1];
                }
            } catch (\Exception $e) {}
        }
        
        return null;
    }
    
    protected function downloadAndSaveImage($imageUrl)
    {
        // Download image
        $imageContent = \Illuminate\Support\Facades\Http::get($imageUrl)->body();
        
        // Get extension
        $extension = 'jpg';
        if (str_contains($imageUrl, '.png')) $extension = 'png';
        if (str_contains($imageUrl, '.webp')) $extension = 'webp';
        
        // Generate filename
        $filename = 'product-' . time() . '-' . Str::random(6) . '.' . $extension;
        
        // Save to storage
        $path = storage_path('app/public/products/' . $filename);
        
        // Create directory if not exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, $imageContent);
        
        // Save to database (as multiple images could be added)
        // For now, we'll use a simple approach - just set as main image
        // This would need to be integrated with the existing image handling
    }

    public function getLinkedAnunciosProperty()
    {
        if (!$this->productId) {
            return [];
        }
        
        $produto = Product::with('skus.marketplaceAnuncios')->find($this->productId);
        if (!$produto) {
            return [];
        }
        
        $anuncios = [];
        foreach ($produto->skus as $sku) {
            foreach ($sku->marketplaceAnuncios as $anuncio) {
                $anuncios[] = [
                    'sku' => $sku->sku,
                    'marketplace' => $anuncio->marketplace,
                    'titulo' => $anuncio->titulo,
                    'preco' => $anuncio->preco,
                    'status' => $anuncio->status,
                    'estoque' => $anuncio->estoque,
                ];
            }
        }
        
        return $anuncios;
    }

    // ==================== COMPOUND PRODUCT METHODS ====================
    
    public function searchComponent()
    {
        if (strlen($this->searchComponent) < 2) {
            $this->componentSearchResults = [];
            return;
        }
        
        $empresaId = auth()->user()->current_empresa_id;
        
        $this->componentSearchResults = Product::where('empresa_id', $empresaId)
            ->where('id', '!=', $this->productId) // Can't add itself
            ->whereNotNull('nome')
            ->where(function ($q) {
                $q->where('nome', 'ilike', '%'.$this->searchComponent.'%')
                    ->orWhere('sku', 'ilike', '%'.$this->searchComponent.'%');
            })
            ->limit(10)
            ->get(['id', 'nome', 'sku', 'preco_venda', 'estoque']);
    }
    
    public function addComponent($productId)
    {
        $product = Product::find($productId);
        if (!$product) return;
        
        // Check if already added
        $exists = collect($this->components)->firstWhere('product_id', $productId);
        if ($exists) return;
        
        $this->components[] = [
            'product_id' => $product->id,
            'nome' => $product->nome,
            'sku' => $product->sku,
            'preco_venda' => $product->preco_venda,
            'preco_custo' => $product->preco_custo,
            'estoque' => $product->estoque,
            'quantity' => 1,
            'unit_price' => $product->preco_venda,
        ];
        
        $this->searchComponent = '';
        $this->componentSearchResults = [];
        
        // Auto-calculate kit price
        $this->updateKitPrice();
    }
    
    public function removeComponent($index)
    {
        unset($this->components[$index]);
        $this->components = array_values($this->components);
        
        // Update kit price
        $this->updateKitPrice();
    }
    
    public function updateComponentQuantity($index, $quantity)
    {
        if (isset($this->components[$index])) {
            $this->components[$index]['quantity'] = max(1, (int) $quantity);
        }
    }
    
    public function updateComponentPrice($index, $price)
    {
        if (isset($this->components[$index])) {
            $this->components[$index]['unit_price'] = (float) $price;
        }
    }
    
    public function updateKitPrice()
    {
        $total = 0;
        foreach ($this->components as $comp) {
            $total += ($comp['unit_price'] ?? $comp['preco_venda']) * ($comp['quantity'] ?? 1);
        }
        $this->preco_venda = $total;
    }
    
    public function getComponentsTotalPrice(): float
    {
        $total = 0;
        foreach ($this->components as $comp) {
            $price = $comp['unit_price'] ?? $comp['preco_venda'] ?? 0;
            $qty = $comp['quantity'] ?? 1;
            $total += $price * $qty;
        }
        return $total;
    }
    
    public function getMaxCompoundStock(): int
    {
        if (empty($this->components)) return 0;
        
        $maxStock = PHP_INT_MAX;
        
        foreach ($this->components as $comp) {
            $estoque = $comp['estoque'] ?? 0;
            $qty = $comp['quantity'] ?? 1;
            
            if ($qty > 0) {
                $possible = intdiv($estoque, $qty);
                $maxStock = min($maxStock, $possible);
            }
        }
        
        return $maxStock === PHP_INT_MAX ? 0 : $maxStock;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.products.create');
    }
}
