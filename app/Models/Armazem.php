<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\BelongsToGroup;

class Armazem extends Model
{
    use BelongsToGroup;

    protected $table = 'armazens';

    protected $fillable = [
        'nome',
        'slug',
        'endereco',
        'compartilhado',
        'ativo',
        'grupo_id',
    ];

    protected function casts(): array
    {
        return [
            'compartilhado' => 'boolean',
            'ativo'         => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────
    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'armazem_empresa');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(EstoqueMovimentacao::class);
    }
}
