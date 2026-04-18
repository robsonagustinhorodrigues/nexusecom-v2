<?php

namespace App\Models;

use App\Traits\BelongsToGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstoqueMovimentacao extends Model
{
    use BelongsToGroup;

    protected $table = 'estoque_movimentacoes';

    protected $fillable = [
        'product_sku_id',
        'deposito_id',
        'user_id',
        'quantidade',
        'tipo',
        'origem',
        'observacao',
        'grupo_id',
    ];

    // ── Relationships ─────────────────────────────
    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
