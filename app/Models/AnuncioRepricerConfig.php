<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnuncioRepricerConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_anuncio_id',
        'is_active',
        'strategy',
        'offset_value',
        'min_profit_margin',
        'max_profit_margin',
        'filter_full_only',
        'filter_premium_only',
        'filter_classic_only',
        'last_run_at',
        'log_last_action',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'offset_value' => 'decimal:2',
        'min_profit_margin' => 'decimal:2',
        'max_profit_margin' => 'decimal:2',
        'filter_full_only' => 'boolean',
        'filter_premium_only' => 'boolean',
        'filter_classic_only' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function anuncio(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAnuncio::class, 'marketplace_anuncio_id');
    }
}
