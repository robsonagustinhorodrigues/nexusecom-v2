<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnuncioEstoqueVirtual extends Model
{
    protected $table = 'anuncios_estoque_virtual';

    protected $fillable = [
        'anuncio_id',
        'quantidade_virtual',
        'usar_virtual',
    ];

    protected function casts(): array
    {
        return [
            'usar_virtual' => 'boolean',
        ];
    }

    public function anuncio(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAnuncio::class, 'anuncio_id');
    }
}
