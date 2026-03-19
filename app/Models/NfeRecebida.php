<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NfeRecebida extends Model
{
    protected $fillable = [
        'empresa_id',
        'chave',
        'numero',
        'serie',
        'emitente_nome',
        'emitente_cnpj',
        'cliente_nome',
        'cliente_cnpj',
        'valor_total',
        'data_emissao',
        'data_recebimento',
        'xml_path',
        'status_manifestacao',
        'status_nfe',
        'tipo_fiscal',
        'tp_nf',
        'devolucao',
        'nfe_devolvida_chave',
        'nfe_devolvida_numero',
        'nfe_devolvida_serie',
        'protocolo_cancelamento',
        'motivo_cancelamento',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'datetime',
            'data_recebimento' => 'datetime',
            'valor_total' => 'decimal:2',
            'devolucao' => 'boolean',
        ];
    }

    public function scopeTenant(Builder $query, ?int $empresaId): Builder
    {
        return $query->where('empresa_id', $empresaId ?? 0);
    }

    // ── Relationships ─────────────────────────────
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class, 'emitente_cnpj', 'cpf_cnpj')
            ->where('empresa_id', $this->empresa_id);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(NfeEvento::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(NfeItem::class, 'nfe_recebida_id');
    }
}
