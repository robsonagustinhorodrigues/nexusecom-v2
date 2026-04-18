<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrupoConfiguracao extends Model
{
    protected $table = 'grupo_configuracoes';

    protected $fillable = [
        'grupo_id',
        'sefaz_intervalo_minutos',
        'sefaz_auto_busca',
        'sefaz_hora_inicio',
        'sefaz_hora_fim',
        'nfe_auto_manifestar',
        'nfe_dias_retroativos',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'sefaz_auto_busca' => 'boolean',
            'nfe_auto_manifestar' => 'boolean',
            'sefaz_hora_inicio' => 'datetime:H:i:s',
            'sefaz_hora_fim' => 'datetime:H:i:s',
        ];
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public static function getOrCreateForGrupo(int $grupoId): self
    {
        return static::firstOrCreate(
            ['grupo_id' => $grupoId],
            [
                'sefaz_intervalo_minutos' => 360,
                'sefaz_auto_busca' => true,
                'sefaz_hora_inicio' => '08:00:00',
                'sefaz_hora_fim' => '20:00:00',
                'nfe_auto_manifestar' => false,
                'nfe_dias_retroativos' => 5,
            ]
        );
    }
}
