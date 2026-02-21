<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Despesa extends Model
{
    protected $fillable = [
        'empresa_id',
        'descricao',
        'valor',
        'data_pagamento',
        'data_competencia',
        'categoria',
        'status',
        'forma_pagamento',
        'recorrente',
        'recorrencia_meses',
        'fornecedor_id',
        'marketplace_pedido_id',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_pagamento' => 'date',
            'data_competencia' => 'date',
            'recorrente' => 'boolean',
            'recorrencia_meses' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class, 'fornecedor_id');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(MarketplacePedido::class, 'marketplace_pedido_id');
    }

    public function scopeTenant($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'pago');
    }

    public function scopeByCategory($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('data_pagamento', [$startDate, $endDate]);
    }

    public static function getCategorias(): array
    {
        return [
            'frete' => 'Frete',
            'taxa_plataforma' => 'Taxa de Plataforma',
            'taxa_pagamento' => 'Taxa de Pagamento',
            'imposto' => 'Imposto',
            'marketing' => 'Marketing',
            'fornecedor' => 'Fornecedor',
            'funcionario' => 'Funcionário',
            'aluguel' => 'Aluguel',
            'luz' => 'Luz',
            'agua' => 'Água',
            'internet' => 'Internet',
            'telefone' => 'Telefone',
            'software' => 'Software',
            'marketing_digital' => 'Marketing Digital',
            'embalagem' => 'Embalagem',
            'estoque' => 'Estoque',
            'contabilidade' => 'Contabilidade',
            'juridico' => 'Jurídico',
            'banco' => 'Banco',
            'outros' => 'Outros',
        ];
    }
}
