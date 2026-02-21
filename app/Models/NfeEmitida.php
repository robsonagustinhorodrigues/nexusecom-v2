<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NfeEmitida extends Model
{
    protected $table = 'nfe_emitidas';

    protected $fillable = [
        'empresa_id',
        'chave',
        'pedido_marketplace',
        'numero',
        'serie',
        'cliente_nome',
        'cliente_cnpj',
        'valor_total',
        'data_emissao',
        'xml_path',
        'status',
        'status_nfe',
        'devolvida',
        'nfe_devolucao_chave',
        'nfe_devolucao_numero',
        'nfe_devolucao_serie',
    ];

    protected $casts = [
        'data_emissao' => 'datetime',
        'valor_total' => 'decimal:2',
        'devolvida' => 'boolean',
    ];

    public function scopeTenant(Builder $query, ?int $empresaId): Builder
    {
        return $query->where('empresa_id', $empresaId ?? 0);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class, 'cliente_cnpj', 'cpf_cnpj')
            ->where('empresa_id', $this->empresa_id);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(NfeItem::class, 'nfe_emitida_id');
    }
}
