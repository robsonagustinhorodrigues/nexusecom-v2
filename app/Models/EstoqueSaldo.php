<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstoqueSaldo extends Model
{
    protected $table = 'estoque_por_deposito';

    protected $fillable = [
        'product_sku_id',
        'deposito_id',
        'saldo',
    ];

    protected function casts(): array
    {
        return [
            'saldo' => 'integer',
        ];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class);
    }

    public static function atualizarSaldo(int $skuId, int $depositoId, int $quantidade)
    {
        $saldo = self::firstOrNew([
            'product_sku_id' => $skuId,
            'deposito_id' => $depositoId,
        ]);

        $saldo->saldo = ($saldo->saldo ?? 0) + $quantidade;
        $saldo->save();

        return $saldo;
    }
}
