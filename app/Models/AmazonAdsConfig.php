<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonAdsConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'profile_id',
        'client_id',
        'client_secret',
        'access_token',
        'refresh_token',
        'region',
        'margem_alvo_padrao',
        'gemini_model',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'margem_alvo_padrao' => 'decimal:2',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
