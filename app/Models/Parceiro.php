<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Parceiro extends Model
{
    protected $fillable = [
        'empresa_id',
        'nome',
        'cpf_cnpj',
        'tipo',
        'email',
        'telefone',
        'endereco',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function scopeTenant(Builder $query, ?int $empresaId): Builder
    {
        return $query->where('empresa_id', $empresaId ?? 0);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
    
    public function isCliente(): bool
    {
        return in_array($this->tipo, ['cliente', 'ambos']);
    }

    public function isFornecedor(): bool
    {
        return in_array($this->tipo, ['fornecedor', 'ambos']);
    }
}
