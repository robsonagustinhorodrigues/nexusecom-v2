<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstoqueSaldo extends Model
{
    protected $table = 'estoque_saldos';

    protected $fillable = [
        'product_sku_id',
        'armazem_id',
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

    public function armazem(): BelongsTo
    {
        return $this->belongsTo(Armazem::class);
    }

    public static function atualizarSaldo(int $skuId, int $armazemId, int $quantidade)
    {
        $saldo = self::firstOrNew([
            'product_sku_id' => $skuId,
            'armazem_id' => $armazemId,
        ]);

        $saldo->saldo = ($saldo->saldo ?? 0) + $quantidade;
        $saldo->save();

        return $saldo;
    }
}
