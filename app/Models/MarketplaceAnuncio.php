<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAnuncio extends Model
{
    protected $table = 'marketplace_anuncios';

    protected $fillable = [
        'empresa_id',
        'integracao_id',
        'product_sku_id',
        'marketplace',
        'external_id',
        'sku',
        'titulo',
        'preco',
        'estoque',
        'status',
        'url_anuncio',
        'json_data',
        'frete_custo_seller',
        'frete_source',
        'frete_updated_at',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'json_data' => 'array',
        'estoque' => 'integer',
        'frete_custo_seller' => 'decimal:2',
        'frete_updated_at' => 'datetime',
    ];

    public function scopeTenant(Builder $query, ?int $empresaId): Builder
    {
        return $query->where('empresa_id', $empresaId ?? 0);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function integracao(): BelongsTo
    {
        return $this->belongsTo(Integracao::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'produto_id');
    }

    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function importAsProduct(): ?Product
    {
        $jsonData = $this->json_data ?? [];

        $product = Product::create([
            'empresa_id' => $this->empresa_id,
            'nome' => $this->titulo,
            'sku' => $this->sku,
            'descricao' => $jsonData['descricao'] ?? null,
            'preco_venda' => $this->preco,
            'preco_custo' => $jsonData['precoCusto'] ?? null,
            'peso' => $jsonData['peso'] ?? null,
            'altura' => $jsonData['altura'] ?? null,
            'largura' => $jsonData['largura'] ?? null,
            'profundidade' => $jsonData['profundidade'] ?? null,
            'imagem' => $jsonData['imagens'][0]['url'] ?? null,
            'tipo' => 'simples',
            'ativo' => $this->status === 'active',
        ]);

        if ($product) {
            ProductSku::create([
                'product_id' => $product->id,
                'sku' => $this->sku,
                'preco' => $this->preco,
                'estoque' => $this->estoque,
                'codigo_barras' => $jsonData['codigoBarras'] ?? null,
            ]);
        }

        return $product;
    }

    // Estoque Virtual
    public function estoqueVirtual()
    {
        return $this->hasOne(AnuncioEstoqueVirtual::class, 'anuncio_id');
    }

    public function repricerConfig()
    {
        return $this->hasOne(AnuncioRepricerConfig::class, 'marketplace_anuncio_id');
    }
}
