<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposito extends Model
{
    protected $table = 'depositos';

    protected $fillable = [
        'empresa_id',
        'nome',
        'descricao',
        'tipo', // loja, armazem, full, virtual
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
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
