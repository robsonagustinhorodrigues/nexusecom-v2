<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposito extends Model
{
    protected $table = 'depositos';

    protected $fillable = [
        'empresa_id',
        'grupo_id',
        'nome',
        'descricao',
        'tipo', // loja, armazem, full, virtual
        'ativo',
        'compartilhado',
        'compartilhado_com',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'compartilhado' => 'boolean',
            'compartilhado_com' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function saldos()
    {
        return $this->hasMany(EstoqueSaldo::class, 'deposito_id');
    }
}
