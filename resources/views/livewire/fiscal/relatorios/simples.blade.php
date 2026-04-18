<?php

use App\Models\Empresa;
use App\Services\Tax\SimplesNacionalCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

state([
    'empresa_id' => '',
    'ano_mes' => '',
    'resultados' => [],
]);

$empresas = computed(function () {
    return Empresa::where('grupo_id', Auth::user()->grupo_id)
        ->orderBy('nome')
        ->get();
});

$meses = computed(function () {
    $meses = [];
    $dataAtual = Carbon::now();

    for ($i = 0; $i <= 12; $i++) {
        $data = $dataAtual->copy()->subMonths($i);
        $meses[] = [
            'value' => $data->format('Y-m'),
            'text' => $data->format('m/Y'),
        ];
    }

    return $meses;
});

mount(function () {
    $this->ano_mes = Carbon::now()->format('Y-m');
});

$calcular = function () {
    $this->validate([
        'empresa_id' => 'required',
        'ano_mes' => 'required|regex:/^\d{4}-\d{2}$/',
    ]);

    $empresa = Empresa::find($this->empresa_id);
    $calculator = new SimplesNacionalCalculator;
    $this->resultados = $calculator->calcular($empresa, $this->ano_mes);
};

$calcularImpostoSimples = function ($empresaId, $anoMesSelecionado) {
    $empresa = Empresa::find($empresaId);
    $calculator = new SimplesNacionalCalculator;

    return $calculator->calcular($empresa, $anoMesSelecionado);
};

$nomeAnexo = function ($tipoAtividade) {
    return match ($tipoAtividade) {
        'anexo_i' => 'Anexo I - Comércio',
        'anexo_ii' => 'Anexo II - Indústria',
        'anexo_iii' => 'Anexo III - Serviços',
        'anexo_iv' => 'Anexo IV - Serviços',
        'anexo_v' => 'Anexo V - Serviços Intelectuais',
        default => 'Anexo I - Comércio',
    };
};
?>

<div class="space-y-6">
    <header>
        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight italic uppercase text-indigo-500">Simples Nacional</h2>
        <p class="text-slate-500 font-medium font-bold italic">Cálculo da estimativa de imposto Simples Nacional.</p>
    </header>

    <form wire:submit.prevent="calcular" class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-3xl shadow-xl">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Selecione a Empresa</label>
                <select wire:model="empresa_id" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" required>
                    <option value="">Selecione...</option>
                    @foreach($this->empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Mês de Apuração</label>
                <select wire:model="ano_mes" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" required>
                    @foreach($this->meses as $mes)
                        <option value="{{ $mes['value'] }}">{{ $mes['text'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex items-end">
                <button type="submit" class="w-full py-3 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-calculator"></i>
                    Calcular
                </button>
            </div>
        </div>
    </form>

    @if(!empty($resultados))
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-5">
                <i class="fas fa-calculator text-indigo-500"></i>
                Estimativa do Imposto
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-amber-50 dark:bg-amber-500/10 rounded-xl border border-amber-200 dark:border-amber-500/20">
                    <span class="text-sm font-bold text-amber-700 dark:text-amber-400">Total de Vendas do Mês</span>
                    <span class="text-sm font-black text-amber-600 dark:text-amber-400">R$ {{ number_format($resultados['total_vendas'], 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-dark-950 rounded-xl">
                    <span class="text-sm font-medium text-slate-600">Vendas Tributáveis do Mês</span>
                    <span class="text-sm font-bold text-slate-900 dark:text-white">R$ {{ number_format($resultados['rpa_vendas'], 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-dark-950 rounded-xl">
                    <span class="text-sm font-medium text-slate-600">(-) Devoluções do Mês</span>
                    <span class="text-sm font-bold text-rose-600">R$ {{ number_format($resultados['devolucoes'], 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-indigo-50 dark:bg-indigo-500/10 rounded-xl border border-indigo-200 dark:border-indigo-500/20">
                    <span class="text-sm font-bold text-indigo-700 dark:text-indigo-400">Faturamento Tributável (RPA)</span>
                    <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">R$ {{ number_format($resultados['rpa'], 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-dark-950 rounded-xl">
                    <span class="text-sm font-medium text-slate-600">Total de Compras no Mês</span>
                    <span class="text-sm font-bold text-slate-900 dark:text-white">R$ {{ number_format($resultados['compras_mes'], 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-dark-950 rounded-xl">
                    <span class="text-sm font-medium text-slate-600">Receita Bruta 12 Meses (RBT12)</span>
                    <span class="text-sm font-bold text-slate-900 dark:text-white">R$ {{ number_format($resultados['rbt12'], 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-500/10 rounded-xl border border-blue-200 dark:border-blue-500/20">
                    <span class="text-sm font-bold text-blue-700 dark:text-blue-400">Alíquota Efetiva</span>
                    <span class="text-sm font-black text-blue-600 dark:text-blue-400">{{ number_format($resultados['aliquota_efetiva'] * 100, 4, ',', '.') }}%</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-emerald-50 dark:bg-emerald-500/10 rounded-xl border border-emerald-200 dark:border-emerald-500/20">
                    <span class="text-base font-bold text-emerald-700 dark:text-emerald-400">Valor Estimado do Imposto</span>
                    <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">R$ {{ number_format($resultados['imposto_devido'], 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-5">
                <i class="fas fa-chart-bar text-amber-500"></i>
                Detalhes do Cálculo ({{ $resultados['anexo'] }})
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-dark-950 rounded-xl">
                    <span class="text-sm font-medium text-slate-600">Faixa do Simples</span>
                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $resultados['faixa'] }}ª Faixa</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-dark-950 rounded-xl">
                    <span class="text-sm font-medium text-slate-600">Alíquota Nominal da Faixa</span>
                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ number_format($resultados['aliquota_nominal'], 2, ',', '.') }}%</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-dark-950 rounded-xl">
                    <span class="text-sm font-medium text-slate-600">Parcela a Deduzir</span>
                    <span class="text-sm font-bold text-slate-900 dark:text-white">R$ {{ number_format($resultados['parcela_deduzir'], 2, ',', '.') }}</span>
                </div>
            </div>
            
            <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl">
                <p class="text-xs font-semibold text-amber-700 dark:text-amber-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    Este é um cálculo estimativo. Consulte seu contador para valores oficiais.
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
