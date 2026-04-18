<?php

use App\Models\Empresa;
use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use function Livewire\Volt\{state, computed, mount};

state([
    'empresa_id' => '',
    'ano_mes' => '',
    'resumoMensal' => collect([]),
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

$gerar = function () {
    $this->validate([
        'empresa_id' => 'required',
        'ano_mes' => 'required',
    ]);

    $this->resumoMensal = $this->getResumoMensal($this->empresa_id);
};

$getResumoMensal = function ($empresaId) {
    $driver = DB::connection()->getDriverName();
    
    $sqlMes = match ($driver) {
        'pgsql' => "TO_CHAR(data_emissao, 'YYYY-MM')",
        'sqlite' => "strftime('%Y-%m', data_emissao)",
        default => "DATE_FORMAT(data_emissao, '%Y-%m')"
    };

    $queryEmitidas = NfeEmitida::query()
        ->select(DB::raw("{$sqlMes} as mes"))
        ->selectRaw("SUM(CASE WHEN status = 'autorizada' THEN valor_total ELSE 0 END) as total_saida")
        ->selectRaw("COUNT(CASE WHEN status = 'autorizada' THEN 1 END) as quantidade_saida")
        ->selectRaw("SUM(CASE WHEN status_nfe = 'cancelada' THEN valor_total ELSE 0 END) as total_cancelada")
        ->selectRaw("COUNT(CASE WHEN status_nfe = 'cancelada' THEN 1 END) as quantidade_cancelada")
        ->where('empresa_id', $empresaId)
        ->where('data_emissao', '>=', Carbon::now()->subMonths(12)->startOfMonth())
        ->groupBy(DB::raw('mes'))
        ->orderBy(DB::raw('mes'), 'desc')
        ->get();

    $queryRecebidas = NfeRecebida::query()
        ->select(DB::raw("{$sqlMes} as mes"))
        ->selectRaw("SUM(CASE WHEN status_nfe = 'aprovada' THEN valor_total ELSE 0 END) as total_compra")
        ->selectRaw("COUNT(CASE WHEN status_nfe = 'aprovada' THEN 1 END) as quantidade_compra")
        ->where('empresa_id', $empresaId)
        ->where('data_emissao', '>=', Carbon::now()->subMonths(12)->startOfMonth())
        ->groupBy(DB::raw('mes'))
        ->get();

    $recebidasAgrupadas = $queryRecebidas->keyBy('mes');

    return $queryEmitidas->map(function ($item) use ($recebidasAgrupadas) {
        $compra = $recebidasAgrupadas->get($item->mes);
        
        $item->total_compra = $compra ? $compra->total_compra : 0;
        $item->quantidade_compra = $compra ? $compra->quantidade_compra : 0;
        $item->faturamento_liquido = $item->total_saida;
        $item->porcentagem_devolucao = 0;
        
        $dataObj = Carbon::createFromFormat('Y-m', $item->mes);
        $item->mes_formatado = ucfirst($dataObj->translatedFormat('M/Y'));
        
        return $item;
    });
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <i class="fas fa-chart-bar text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Faturamento 12M</h2>
                <p class="text-sm text-slate-500">Resumo do faturamento nos últimos 12 meses</p>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="gerar" class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Selecione a Empresa</label>
                <select wire:model="empresa_id" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" required>
                    <option value="">Selecione...</option>
                    @foreach($this->empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full py-3 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i>
                    Buscar
                </button>
            </div>
        </div>
    </form>

    @if($resumoMensal->isNotEmpty())
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 dark:bg-dark-950 border-b border-slate-200 dark:border-dark-800">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Mês</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Total Saídas</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Qtd Saídas</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Total Canc.</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Qtd Canc.</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Fat. Líquido</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Total Compras</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Qtd Compras</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-dark-800">
                    @foreach($resumoMensal as $resumo)
                    <tr class="hover:bg-dark-800/30 transition-colors">
                        <td class="px-6 py-4">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $resumo->mes_formatado }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">R$ {{ number_format($resumo->total_saida, 2, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $resumo->quantidade_saida }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-bold text-rose-600 dark:text-rose-400">R$ {{ number_format($resumo->total_cancelada, 2, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $resumo->quantidade_cancelada }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">R$ {{ number_format($resumo->faturamento_liquido, 2, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">R$ {{ number_format($resumo->total_compra, 2, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $resumo->quantidade_compra }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 p-6 rounded-2xl">
        <p class="text-amber-700 dark:text-amber-400 font-semibold flex items-center gap-2">
            <i class="fas fa-info-circle"></i>
            Selecione uma empresa para visualizar o relatório.
        </p>
    </div>
    @endif
</div>
