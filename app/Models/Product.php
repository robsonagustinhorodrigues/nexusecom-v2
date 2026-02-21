<?php

namespace App\Models;

use App\Traits\BelongsToGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToGroup;

    protected $fillable = [
        'empresa_id',
        'parent_id',
        'variation_color',
        'variation_size',
        'variation_order',
        'nome',
        'sku',
        'slug',
        'marca',
        'ean',
        'gtin',
        'descricao',
        'tipo',
        'grupo_id',
        'categoria_id',
        'tags',
        'unidade_medida',
        'unidade',
        'ncm',
        'cest',
        'origem',
        'preco_venda',
        'preco_custo',
        'custo_adicional',
        'unidade_custo_adicional',
        'estoque',
        'quantidade_virtual',
        'usar_virtual',
        'peso',
        'altura',
        'largura',
        'profundidade',
        'imagem',
        'ativo',
        'marketplace',
        'external_id',
        'marketplace_url',
        'condicao',
        'foto_principal',
        'fotos_galeria',
        'bling_id',
        'json_data',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'preco_venda' => 'decimal:2',
            'preco_custo' => 'decimal:2',
            'custo_adicional' => 'decimal:2',
            'peso' => 'decimal:3',
            'altura' => 'decimal:2',
            'largura' => 'decimal:2',
            'profundidade' => 'decimal:2',
            'ativo' => 'boolean',
            'fotos_galeria' => 'array',
            'json_data' => 'array',
        ];
    }

    public function scopeTenant(Builder $query, ?int $empresaId): Builder
    {
        return $query;
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class);
    }

    // Produto Pai (se for variação)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    // Variações deste produto (se for pai)
    public function variations(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_id')->orderBy('variation_order');
    }

    // Todos os filhos (recursivo - para pai buscar tudo)
    public function allVariations(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_id')->with('allVariations');
    }

    // Verifica se é variação
    public function getIsVariationAttribute(): bool
    {
        return !empty($this->parent_id);
    }

    // Verifica se é pai de variações
    public function getHasVariationsAttribute(): bool
    {
        return $this->variations()->count() > 0;
    }

    // SKU único (para variações, usa o SKU do produto)
    public function getMainSkuAttribute(): string
    {
        return $this->sku ?? 'SKU-'.$this->id;
    }

    // Estoque total (pai + variações)
    public function getTotalStockAttribute(): int
    {
        if ($this->has_variations) {
            return $this->estoque + $this->variations->sum('estoque');
        }
        return $this->estoque;
    }

    // Custo total (custo + custo adicional)
    public function getCustoTotalAttribute(): float
    {
        $custoBase = floatval($this->preco_custo ?? 0);
        $custoAdicional = floatval($this->custo_adicional ?? 0);
        
        // Se custo adicional for por unidade, soma ao custo base
        // Se for por produto (kit), já está incluído no custo base
        return $custoBase + $custoAdicional;
    }

    // ==================== PRODUTO COMPOSTO ====================
    
    // Componentes deste produto composto
    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'product_id')->orderBy('sort_order');
    }
    
    // Produtos que são componentes (buscar quem usa este produto como componente)
    public function usedInCompounds(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'component_product_id');
    }
    
    // Produtos compostos que usam este produto
    public function compounds(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_components', 'component_product_id', 'product_id')
            ->withPivot('quantity', 'unit_price')
            ->withTimestamps();
    }
    
    // Verifica se é produto composto
    public function getIsCompoundAttribute(): bool
    {
        return $this->components()->count() > 0;
    }
    
    // Estoque máximo possível do kit (baseado nos componentes)
    public function getCompoundMaxStockAttribute(): int
    {
        if (!$this->is_compound) {
            return $this->estoque;
        }
        
        $maxStock = PHP_INT_MAX;
        
        foreach ($this->components as $component) {
            $componentProduct = $component->componentProduct;
            if (!$componentProduct) {
                continue;
            }
            
            $available = $componentProduct->estoque;
            $needed = $component->quantity;
            
            if ($needed > 0) {
                $possible = intdiv($available, $needed);
                $maxStock = min($maxStock, $possible);
            }
        }
        
        return $maxStock === PHP_INT_MAX ? 0 : $maxStock;
    }
    
    // Valor total dos componentes
    public function getComponentsTotalPriceAttribute(): float
    {
        if (!$this->is_compound) {
            return $this->preco_venda;
        }
        
        $total = 0;
        foreach ($this->components as $component) {
            $price = $component->unit_price ?? $component->componentProduct->preco_venda ?? 0;
            $total += $price * $component->quantity;
        }
        
        return $total;
    }

    public function tagsList(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }
}
