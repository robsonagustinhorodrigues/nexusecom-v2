<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\BelongsToGroup;

class ProductSku extends Model
{
    use BelongsToGroup;

    protected $fillable = [
        'product_id',
        'variation_product_id',
        'grupo_id',
        'sku',
        'is_principal',
        'sort_order',
        'gtin',
        'ncm',
        'label',
        'preco_venda',
        'preco_custo',
        'estoque',
        'peso_g',
        'comprimento_cm',
        'largura_cm',
        'altura_cm',
        'descricao_sku',
        'fotos_sku',
        'atributos_json',
        'link_fornecedor',
        'fornecedor_id',
    ];

    protected function casts(): array
    {
        return [
            'fotos_sku'      => 'array',
            'atributos_json' => 'array',
            'preco_venda'    => 'decimal:2',
            'preco_custo'    => 'decimal:2',
            'is_principal'   => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variation_product_id');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function movimentacoes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EstoqueMovimentacao::class, 'product_sku_id');
    }

    public function saldos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EstoqueSaldo::class, 'product_sku_id');
    }

    public function marketplaceAnuncios(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MarketplaceAnuncio::class, 'product_sku_id');
    }
}
