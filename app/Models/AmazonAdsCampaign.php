<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonAdsCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'campaign_id_amz',
        'sku',
        'name',
        'type',
        'state',
        'daily_budget',
        'bidding_strategy',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function adGroups()
    {
        return $this->hasMany(AmazonAdsAdGroup::class, 'campaign_id');
    }
}
