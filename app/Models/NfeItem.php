<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfeItem extends Model
{
    protected $table = 'nfe_items';

    protected $fillable = [
        'nfe_emitida_id',
        'nfe_recebida_id',
        'product_id',
        'numero_item',
        'codigo_produto',
        'gtin',
        'descricao',
        'ncm',
        'cfop',
        'unidade',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'valor_desconto',
        'valor_frete',
        'valor_seguro',
        'valor_outros',
        'base_calculo_icms',
        'aliquota_icms',
        'valor_icms',
        'base_calculo_icms_st',
        'aliquota_icms_st',
        'valor_icms_st',
        'aliquota_pis',
        'valor_pis',
        'aliquota_cofins',
        'valor_cofins',
        'aliquota_iss',
        'valor_iss',
        'tributado',
        'codigo_beneficio_fiscal',
        'informacoes_adicionais',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:4',
            'valor_unitario' => 'decimal:4',
            'valor_total' => 'decimal:2',
            'valor_desconto' => 'decimal:2',
            'valor_frete' => 'decimal:2',
            'valor_seguro' => 'decimal:2',
            'valor_outros' => 'decimal:2',
            'base_calculo_icms' => 'decimal:2',
            'aliquota_icms' => 'decimal:4',
            'valor_icms' => 'decimal:2',
            'base_calculo_icms_st' => 'decimal:2',
            'aliquota_icms_st' => 'decimal:4',
            'valor_icms_st' => 'decimal:2',
            'aliquota_pis' => 'decimal:4',
            'valor_pis' => 'decimal:2',
            'aliquota_cofins' => 'decimal:4',
            'valor_cofins' => 'decimal:2',
            'aliquota_iss' => 'decimal:4',
            'valor_iss' => 'decimal:2',
            'tributado' => 'boolean',
        ];
    }

    public function nfeEmitida(): BelongsTo
    {
        return $this->belongsTo(NfeEmitida::class, 'nfe_emitida_id');
    }

    public function nfeRecebida(): BelongsTo
    {
        return $this->belongsTo(NfeRecebida::class, 'nfe_recebida_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
