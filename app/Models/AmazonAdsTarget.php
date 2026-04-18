<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonAdsTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_group_id',
        'target_id_amz',
        'type',
        'match_type',
        'value',
        'bid',
        'state',
    ];

    protected $casts = [
        'bid' => 'decimal:2',
    ];

    public function adGroup()
    {
        return $this->belongsTo(AmazonAdsAdGroup::class);
    }
}
