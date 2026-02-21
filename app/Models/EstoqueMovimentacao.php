<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\BelongsToGroup;

class EstoqueMovimentacao extends Model
{
    use BelongsToGroup;

    protected $table = 'estoque_movimentacoes';

    protected $fillable = [
        'product_sku_id',
        'armazem_id',
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

    public function armazem(): BelongsTo
    {
        return $this->belongsTo(Armazem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
