@extends('layouts.alpine')

@section('title', 'DRE - NexusEcom')
@section('header_title', 'DRE')

@section('content')
<div x-data="drePage()" x-init="init()">
    <!-- Header com Filtros -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Demonstrativo de Resultados</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Análise de Lucratividade & Performance Financeira</p>
        </div>

        <div class="flex items-center gap-3 bg-slate-800 p-2 rounded-2xl border border-slate-700 shadow-lg">
            <select x-model="mes" @change="loadDre()" class="bg-slate-900 border-none text-white font-black italic text-xs uppercase rounded-xl px-4 py-2 focus:ring-indigo-500 outline-none">
                <template x-for="(nome, id) in meses" :key="id">
                    <option :value="id" x-text="nome"></option>
                </template>
            </select>
            <select x-model="ano" @change="loadDre()" class="bg-slate-900 border-none text-white font-black italic text-xs uppercase rounded-xl px-4 py-2 focus:ring-indigo-500 outline-none">
                <template x-for="a in anos" :key="a">
                    <option :value="a" x-text="a"></option>
                </template>
            </select>
            <button @click="loadDre()" class="w-10 h-10 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl flex items-center justify-center transition-all group">
                <i class="fas fa-sync-alt group-hover:rotate-180 transition-transform duration-500"></i>
            </button>
        </div>
    </div>

    <!-- Grid de Métricas de Topo -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl group hover:border-indigo-500/30 transition-all">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Receita Bruta</p>
            <h3 class="text-2xl font-black text-white italic" x-text="formatMoney(data.receita_bruta)">R$ 0,00</h3>
            <div class="mt-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Total Faturado</span>
            </div>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl group hover:border-rose-500/30 transition-all">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Deduções & Taxas</p>
            <h3 class="text-2xl font-black text-rose-400 italic" x-text="formatMoney(data.deducoes)">R$ 0,00</h3>
            <div class="mt-4 flex items-center gap-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight" x-text="((data.deducoes/data.receita_bruta)*100).toFixed(1) + '% da receita'"></span>
            </div>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl group hover:border-emerald-500/30 transition-all text-emerald-400">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Lucro Líquido</p>
            <h3 class="text-2xl font-black italic" x-text="formatMoney(data.lucro_liquido)">R$ 0,00</h3>
            <div class="mt-4 flex items-center gap-2 text-white">
                <span class="px-2 py-0.5 rounded-lg bg-emerald-500 text-[10px] font-black italic uppercase" x-text="data.margem_lucro.toFixed(1) + '% Margem'"></span>
            </div>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl group hover:border-amber-500/30 transition-all">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Impostos Est.</p>
            <h3 class="text-2xl font-black text-amber-400 italic" x-text="formatMoney(data.impostos)">R$ 0,00</h3>
            <div class="mt-4 flex items-center gap-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Regime Simples</span>
            </div>
        </div>
    </div>

    <!-- Tabela DRE Estilizada -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-slate-800 border border-slate-700 rounded-3xl overflow-hidden shadow-2xl">
                <div class="p-6 border-b border-slate-700 bg-slate-900/50 flex items-center justify-between">
                    <h3 class="font-black text-white italic uppercase tracking-tighter">Estrutura de Resultados</h3>
                    <i class="fas fa-file-invoice-dollar text-slate-600"></i>
                </div>
                
                <div class="p-0">
                    <table class="w-full border-collapse">
                        <tbody class="divide-y divide-slate-700/50">
                            <!-- Receita Bruta -->
                            <tr class="group hover:bg-slate-700/20 transition-colors">
                                <td class="px-8 py-4">
                                    <span class="text-xs font-black text-white italic uppercase tracking-wider">(+) RECEITA OPERACIONAL BRUTA</span>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <span class="font-black text-white italic" x-text="formatMoney(data.receita_bruta)"></span>
                                </td>
                            </tr>
                            <!-- Deduções -->
                            <tr class="group hover:bg-slate-700/20 transition-colors">
                                <td class="px-8 py-4">
                                    <span class="text-xs font-black text-rose-500/70 italic uppercase tracking-wider">(-) Deduções e Abatimentos (Mktplace/Taxas)</span>
                                </td>
                                <td class="px-8 py-4 text-right text-rose-400">
                                    <span class="font-black italic" x-text="formatMoney(data.deducoes)"></span>
                                </td>
                            </tr>
                            <!-- Receita Líquida -->
                            <tr class="bg-slate-900/30">
                                <td class="px-8 py-4">
                                    <span class="text-sm font-black text-indigo-400 italic uppercase tracking-tighter">(=) RECEITA OPERACIONAL LÍQUIDA</span>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <span class="font-black text-indigo-400 italic text-lg" x-text="formatMoney(data.receita_liquida)"></span>
                                </td>
                            </tr>
                            <!-- CMV -->
                            <tr class="group hover:bg-slate-700/20 transition-colors">
                                <td class="px-8 py-4">
                                    <span class="text-xs font-black text-rose-500/70 italic uppercase tracking-wider">(-) Custo dos Produtos Vendidos (CMV)</span>
                                </td>
                                <td class="px-8 py-4 text-right text-rose-400">
                                    <span class="font-black italic" x-text="formatMoney(data.cmv)"></span>
                                </td>
                            </tr>
                            <!-- Lucro Bruto -->
                            <tr class="bg-slate-900/30">
                                <td class="px-8 py-4">
                                    <span class="text-sm font-black text-white italic uppercase tracking-tighter">(=) LUCRO BRUTO</span>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <span class="font-black text-white italic text-lg" x-text="formatMoney(data.lucro_bruto)"></span>
                                </td>
                            </tr>
                            <!-- Despesas -->
                            <tr class="group hover:bg-slate-700/20 transition-colors">
                                <td class="px-8 py-4">
                                    <span class="text-xs font-black text-rose-500/70 italic uppercase tracking-wider">(-) Despesas Operacionais / Fixas</span>
                                </td>
                                <td class="px-8 py-4 text-right text-rose-400">
                                    <span class="font-black italic" x-text="formatMoney(data.despesas)"></span>
                                </td>
                            </tr>
                            <!-- Impostos -->
                            <tr class="group hover:bg-slate-700/20 transition-colors">
                                <td class="px-8 py-4">
                                    <span class="text-xs font-black text-rose-500/70 italic uppercase tracking-wider">(-) Provisão de Impostos (DAS/Simples)</span>
                                </td>
                                <td class="px-8 py-4 text-right text-rose-400">
                                    <span class="font-black italic" x-text="formatMoney(data.impostos)"></span>
                                </td>
                            </tr>
                            <!-- Lucro Líquido -->
                            <tr class="bg-indigo-600/10 border-t-2 border-indigo-600/50">
                                <td class="px-8 py-6">
                                    <span class="text-base font-black text-emerald-400 italic uppercase tracking-tighter">(=) RESULTADO LÍQUIDO DO PERÍODO</span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <span class="font-black text-emerald-400 italic text-2xl" x-text="formatMoney(data.lucro_liquido)"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Despesas por Categoria -->
        <div class="space-y-6">
            <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl group">
                <h4 class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-6 italic">Breakdown de Despesas</h4>
                <div class="space-y-4">
                    <template x-for="(valor, cat) in despesas" :key="cat">
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-[10px] font-black text-slate-400 uppercase italic">
                                <span x-text="cat"></span>
                                <span x-text="formatMoney(valor)"></span>
                            </div>
                            <div class="h-1.5 w-full bg-slate-900 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full" :style="'width: ' + ((valor/data.despesas)*100) + '%'"></div>
                            </div>
                        </div>
                    </template>
                    <template x-if="Object.keys(despesas).length === 0">
                        <div class="text-center py-8 text-slate-600 italic">
                            <i class="fas fa-receipt text-2xl mb-2"></i>
                            <p class="text-xs uppercase font-black">Nenhuma despesa no período</p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Alerta de Performance -->
            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-3xl p-6 shadow-xl text-white italic">
                <div class="flex items-center gap-3 mb-4">
                    <i class="fas fa-lightbulb text-yellow-300"></i>
                    <span class="font-black uppercase text-xs tracking-tighter">Insight Eliot ⚡</span>
                </div>
                <p class="text-sm font-bold leading-relaxed">
                    Sua margem líquida atual é de <span x-text="data.margem_lucro.toFixed(1) + '%'"></span>. 
                    <span x-show="data.margem_lucro < 15">Atenção: Margem abaixo do ideal para e-commerce. Revise seus custos fixos.</span>
                    <span x-show="data.margem_lucro >= 15">Parabéns! Sua operação está saudável e acima da média do mercado.</span>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function drePage() {
    return {
        mes: new Date().getMonth() + 1,
        ano: new Date().getFullYear(),
        loading: false,
        data: {
            receita_bruta: 0,
            deducoes: 0,
            receita_liquida: 0,
            cmv: 0,
            lucro_bruto: 0,
            despesas: 0,
            impostos: 0,
            lucro_liquido: 0,
            margem_lucro: 0
        },
        despesas: {},
        meses: {
            1: 'Janeiro', 2: 'Fevereiro', 3: 'Março', 4: 'Abril', 5: 'Maio', 6: 'Junho',
            7: 'Julho', 8: 'Agosto', 9: 'Setembro', 10: 'Outubro', 11: 'Novembro', 12: 'Dezembro'
        },
        anos: [new Date().getFullYear(), new Date().getFullYear() - 1, new Date().getFullYear() - 2],
        
        async init() {
            await this.loadDre();
        },
        
        async loadDre() {
            this.loading = true;
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch(`/api/dre?empresa=${empresaId}&mes=${this.mes}&ano=${this.ano}`);
                if (response.ok) {
                    const result = await response.json();
                    this.data = result.dre;
                    this.despesas = result.despesas;
                }
            } catch (e) {
                console.error('Erro ao carregar DRE:', e);
            }
            this.loading = false;
        },
        
        formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
        }
    }
}
</script>
@endsection
