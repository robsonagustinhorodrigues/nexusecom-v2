<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonAdsAdGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'ad_group_id_amz',
        'name',
        'default_bid',
        'state',
    ];

    protected $casts = [
        'default_bid' => 'decimal:2',
    ];

    public function campaign()
    {
        return $this->belongsTo(AmazonAdsCampaign::class);
    }

    public function targets()
    {
        return $this->hasMany(AmazonAdsTarget::class, 'ad_group_id');
    }
}
