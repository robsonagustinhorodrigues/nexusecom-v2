<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepricerLog extends Model
{
    protected $table = 'repricer_logs';

    protected $fillable = [
        'marketplace_anuncio_id',
        'empresa_id',
        'strategy',
        'preco_anterior',
        'preco_novo',
        'menor_concorrente',
        'margem_lucro',
        'lucro_bruto',
        'status',
        'mensagem',
        'detalhes',
    ];

    protected $casts = [
        'preco_anterior' => 'decimal:2',
        'preco_novo' => 'decimal:2',
        'menor_concorrente' => 'decimal:2',
        'margem_lucro' => 'decimal:2',
        'lucro_bruto' => 'decimal:2',
        'detalhes' => 'array',
    ];

    public function anuncio(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAnuncio::class, 'marketplace_anuncio_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
