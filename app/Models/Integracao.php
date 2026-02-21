<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Integracao extends Model
{
    protected $table = 'integracoes';

    protected $fillable = [
        'empresa_id',
        'marketplace',
        'nome_conta',
        'external_user_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'ativo',
        'configuracoes',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'     => 'datetime',
            'ativo'          => 'boolean',
            'configuracoes'  => 'array',
        ];
    }

    // ── Helpers ────────────────────────────────────
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function needsRefresh(): bool
    {
        return $this->expires_at && $this->expires_at->subMinutes(30)->isPast();
    }

    // ── Relationships ─────────────────────────────
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
