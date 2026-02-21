<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductComponent extends Model
{
    protected $table = 'product_components';
    
    protected $fillable = [
        'product_id',
        'component_product_id',
        'quantity',
        'unit_price',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // Produto composto (pai)
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Produto componente (filho)
    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    // Preço total deste componente no kit
    public function getTotalPriceAttribute(): float
    {
        $price = $this->unit_price ?? $this->componentProduct->preco_venda ?? 0;
        return $price * $this->quantity;
    }
}
