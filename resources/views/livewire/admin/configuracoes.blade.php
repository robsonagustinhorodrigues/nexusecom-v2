<?php

use function Livewire\Volt\{state, rules, mount};
use App\Models\GrupoConfiguracao;
use Illuminate\Support\Facades\Auth;

state([
    'grupo_id' => '',
    'sefaz_intervalo_minutos' => 360,
    'sefaz_auto_busca' => true,
    'sefaz_hora_inicio' => '08:00',
    'sefaz_hora_fim' => '20:00',
    'nfe_auto_manifestar' => false,
    'nfe_dias_retroativos' => 5,
    'observacoes' => '',
]);

rules([
    'sefaz_intervalo_minutos' => 'required|integer|min:60|max:1440',
    'nfe_dias_retroativos' => 'required|integer|min:1|max:30',
]);

mount(function () {
    $this->loadConfig();
});

$loadConfig = function () {
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
};

$save = function () {
    $this->validate();
    
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
};

?>

<div class="space-y-6" x-data="{ mounted: false }" x-init="mounted = true; $wire.loadConfig()">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-500 to-slate-600 flex items-center justify-center shadow-lg shadow-slate-500/20">
                <i class="fas fa-cog text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Configurações do Grupo</h2>
                <p class="text-sm text-slate-500">Configure parâmetros globais do grupo empresarial</p>
            </div>
        </div>

        <button wire:click="save" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2">
            <i class="fas fa-save text-xs"></i>
            Salvar Configurações
        </button>
    </div>

    <!-- Alerta -->
    @if (session()->has('message'))
        <div class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl font-semibold text-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Configurações SEFAZ -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-5">
                <i class="fas fa-server text-indigo-500"></i>
                Integração SEFAZ
            </h3>

            <div class="space-y-5">
                <!-- Busca Automática -->
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl">
                    <div>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Busca Automática NF-e</span>
                        <p class="text-xs text-slate-500">Buscar automaticamente por novas notas na SEFAZ</p>
                    </div>
                    <button type="button" wire:click="$toggle('sefaz_auto_busca')" class="w-11 h-6 rounded-full relative transition-all duration-300 {{ $sefaz_auto_busca ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-dark-700' }}">
                        <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white transition-all transform {{ $sefaz_auto_busca ? 'translate-x-5' : '' }}"></div>
                    </button>
                </div>

                <!-- Intervalo -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                        Intervalo de Busca (minutos)
                    </label>
                    <div class="flex items-center gap-3">
                        <input 
                            type="range" 
                            wire:model="sefaz_intervalo_minutos" 
                            min="60" 
                            max="1440" 
                            step="60"
                            class="flex-1 h-2 bg-slate-200 dark:bg-dark-700 rounded-lg appearance-none cursor-pointer"
                        >
                        <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400 w-20 text-right">
                            {{ floor($sefaz_intervalo_minutos / 60) }}h
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Mínimo 1 hora, máximo 24 horas</p>
                </div>

                <!-- Horário -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Início</label>
                        <input type="time" wire:model="sefaz_hora_inicio" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Fim</label>
                        <input type="time" wire:model="sefaz_hora_fim" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium">
                    </div>
                </div>
            </div>
        </div>

        <!-- Configurações NF-e -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-5">
                <i class="fas fa-file-invoice-dollar text-emerald-500"></i>
                Recebimento NF-e
            </h3>

            <div class="space-y-5">
                <!-- Auto Manifestar -->
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl">
                    <div>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Manifestação Automática</span>
                        <p class="text-xs text-slate-500">Confirmar ciência automaticamente para todas as notas</p>
                    </div>
                    <button type="button" wire:click="$toggle('nfe_auto_manifestar')" class="w-11 h-6 rounded-full relative transition-all duration-300 {{ $nfe_auto_manifestar ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-dark-700' }}">
                        <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white transition-all transform {{ $nfe_auto_manifestar ? 'translate-x-5' : '' }}"></div>
                    </button>
                </div>

                <!-- Dias Retroativos -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                        Dias Retroativos para Importação
                    </label>
                    <input 
                        type="number" 
                        wire:model="nfe_dias_retroativos" 
                        min="1" 
                        max="30"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium"
                    >
                    <p class="text-xs text-slate-400 mt-1">Buscar notas dos últimos X dias ao importar</p>
                </div>
            </div>
        </div>

        <!-- Observações -->
        <div class="lg:col-span-2 bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-5">
                <i class="fas fa-sticky-note text-amber-500"></i>
                Observações
            </h3>
            
            <textarea 
                wire:model="observacoes" 
                rows="3" 
                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium"
                placeholder="Observações internas do grupo..."
            ></textarea>
        </div>
    </div>
</div>
