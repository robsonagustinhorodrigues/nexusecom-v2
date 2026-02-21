<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarefa extends Model
{
    protected $table = 'tarefas';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'tipo',
        'status',
        'total',
        'processado',
        'sucesso',
        'falha',
        'mensagem',
        'resultado',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'resultado' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function getProgressAttribute(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return (int) round(($this->processado / $this->total) * 100);
    }

    public function estaConcluida(): bool
    {
        return in_array($this->status, ['concluido', 'falhou', 'cancelado']);
    }

    public function atualizarProgresso(int $processado, int $sucesso, int $falha): void
    {
        $this->update([
            'processado' => $processado,
            'sucesso' => $sucesso,
            'falha' => $falha,
            'status' => $processado >= $this->total ? 'concluido' : 'processando',
            'finished_at' => $processado >= $this->total ? now() : null,
        ]);
    }

    public static function criar(string $tipo, int $total, ?int $empresaId = null, ?int $userId = null): self
    {
        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'empresa_id' => $empresaId ?? auth()->user()?->current_empresa_id,
            'tipo' => $tipo,
            'total' => $total,
            'status' => 'pending',
            'started_at' => now(),
        ]);
    }

    public function podeCancelar(): bool
    {
        return in_array($this->status, ['pending', 'processando']);
    }

    public function cancelar(): bool
    {
        if (! $this->podeCancelar()) {
            return false;
        }

        $this->update([
            'status' => 'cancelado',
            'finished_at' => now(),
            'mensagem' => 'Cancelado pelo usuário',
        ]);

        return true;
    }
}
