<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonAdsSkuConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'marketplace_anuncio_id',
        'sku',
        'is_active',
        'margem_alvo',
        'keywords',
        'categories',
        'asins',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'margem_alvo' => 'decimal:2',
        'keywords' => 'array',
        'categories' => 'array',
        'asins' => 'array',
    ];

    public function anuncio()
    {
        return $this->belongsTo(MarketplaceAnuncio::class, 'marketplace_anuncio_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
