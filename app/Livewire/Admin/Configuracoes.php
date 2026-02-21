<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\GrupoConfiguracao;
use Illuminate\Support\Facades\Auth;

class Configuracoes extends Component
{
    public $grupo_id = '';
    public $sefaz_intervalo_minutos = 360;
    public $sefaz_auto_busca = true;
    public $sefaz_hora_inicio = '08:00';
    public $sefaz_hora_fim = '20:00';
    public $nfe_auto_manifestar = false;
    public $nfe_dias_retroativos = 5;
    public $observacoes = '';

    public function mount()
    {
        $this->loadConfig();
    }

    public function loadConfig()
    {
        $grupoId = Auth::user()->grupo_id;
        if (!$grupoId) return;
        
        $config = GrupoConfiguracao::getOrCreateForGrupo($grupoId);
        
        $this->grupo_id = $config->grupo_id;
        $this->sefaz_intervalo_minutos = $config->sefaz_intervalo_minutos;
        $this->sefaz_auto_busca = $config->sefaz_auto_busca;
        $this->sefaz_hora_inicio = $config->sefaz_hora_inicio ? \Carbon\Carbon::parse($config->sefaz_hora_inicio)->format('H:i') : '08:00';
        $this->sefaz_hora_fim = $config->sefaz_hora_fim ? \Carbon\Carbon::parse($config->sefaz_hora_fim)->format('H:i') : '20:00';
        $this->nfe_auto_manifestar = $config->nfe_auto_manifestar;
        $this->nfe_dias_retroativos = $config->nfe_dias_retroativos;
        $this->observacoes = $config->observacoes ?? '';
    }

    public function save()
    {
        $this->validate([
            'sefaz_intervalo_minutos' => 'required|integer|min:60|max:1440',
            'nfe_dias_retroativos' => 'required|integer|min:1|max:30',
        ]);
        
        $grupoId = Auth::user()->grupo_id;
        if (!$grupoId) return;
        
        GrupoConfiguracao::updateOrCreate(
            ['grupo_id' => $grupoId],
            [
                'sefaz_intervalo_minutos' => $this->sefaz_intervalo_minutos,
                'sefaz_auto_busca' => $this->sefaz_auto_busca,
                'sefaz_hora_inicio' => $this->sefaz_hora_inicio . ':00',
                'sefaz_hora_fim' => $this->sefaz_hora_fim . ':00',
                'nfe_auto_manifestar' => $this->nfe_auto_manifestar,
                'nfe_dias_retroativos' => $this->nfe_dias_retroativos,
                'observacoes' => $this->observacoes,
            ]
        );
        
        session()->flash('message', 'Configurações salvas com sucesso! ⚡');
    }

    public function render()
    {
        return view('livewire.admin.configuracoes');
    }
}
