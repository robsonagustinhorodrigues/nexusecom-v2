<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonAdsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'action',
        'entity_type',
        'entity_id_amz',
        'sku',
        'old_value',
        'new_value',
        'reason',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
