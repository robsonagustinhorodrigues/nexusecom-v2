<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonAdsMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'date',
        'entity_type',
        'entity_id_amz',
        'impressions',
        'clicks',
        'spend',
        'sales',
        'orders',
        'acos',
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'spend' => 'decimal:2',
        'sales' => 'decimal:2',
        'orders' => 'integer',
        'acos' => 'decimal:2',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
