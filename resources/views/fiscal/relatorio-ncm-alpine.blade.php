@extends('layouts.alpine')

@section('title', 'Relatório de NCM - NexusEcom')
@section('header_title', 'Relatório de NCM')

@section('content')
<div x-data="relatorioNcm()" x-init="init()">
    <!-- Premium Dashboard Header -->
    <div class="space-y-4 mb-6">
        <!-- Top Row: Title -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tighter uppercase italic flex items-center gap-3">
                    <span class="bg-indigo-600 w-2 h-8 rounded-full"></span>
                    Relatório de <span class="text-indigo-500">NCM</span>
                </h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] ml-5">Resumo financeiro por NCM</p>
            </div>
            
            <!-- Generate Button -->
            <button @click="loadData()" :disabled="loading"
                    class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition-all shadow-lg active:scale-95 disabled:opacity-50 flex items-center gap-2">
                <i class="fas fa-search" :class="loading ? 'fa-spin' : ''"></i>
                <span x-text="loading ? 'Gerando...' : 'Gerar Relatório'"></span>
            </button>
        </div>

        <!-- Filters & Control Bar -->
        <div class="relative">
            <div class="bg-slate-800/80 backdrop-blur-md border border-slate-700/50 rounded-2xl p-4 shadow-2xl flex flex-wrap items-center gap-4 transition-all w-full">
                
                <!-- Date Range -->
                <div class="flex items-center gap-2 flex-grow max-w-md">
                    <div class="flex-1">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Data Inicial</label>
                        <input type="date" x-model="dataInicio" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-300 focus:ring-2 focus:ring-indigo-500/50 outline-none">
                    </div>
                    <span class="text-slate-600 font-bold text-[10px] mt-5">ATÉ</span>
                    <div class="flex-1">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Data Final</label>
                        <input type="date" x-model="dataFim" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-300 focus:ring-2 focus:ring-indigo-500/50 outline-none">
                    </div>
                </div>

            </div>
        </div>

        <!-- Stats Bar -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-4" x-show="summary !== null" x-cloak>
            <div class="bg-gradient-to-br from-emerald-600/20 to-transparent border border-emerald-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Total Compras</span>
                    <i class="fas fa-arrow-down text-emerald-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="formatMoney(summary.compra_total)"></div>
            </div>

            <div class="bg-gradient-to-br from-blue-600/20 to-transparent border border-blue-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Total Vendas</span>
                    <i class="fas fa-arrow-up text-blue-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="formatMoney(summary.venda_total)"></div>
            </div>

            <div class="bg-gradient-to-br from-red-600/20 to-transparent border border-red-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-red-400 uppercase tracking-widest">Total Canceladas</span>
                    <i class="fas fa-times text-red-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="formatMoney(summary.cancelada_total)"></div>
            </div>

            <div class="bg-gradient-to-br from-orange-600/20 to-transparent border border-orange-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-orange-400 uppercase tracking-widest">Total Devolvidas</span>
                    <i class="fas fa-undo text-orange-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="formatMoney(summary.devolvida_total)"></div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-slate-800 border border-slate-700 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-900/30">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-left">NCM</th>
                        <th class="px-6 py-4 text-[10px] font-black text-emerald-500 uppercase tracking-widest italic text-right">Compra (Entrada)</th>
                        <th class="px-6 py-4 text-[10px] font-black text-blue-500 uppercase tracking-widest italic text-right">Venda (Saída)</th>
                        <th class="px-6 py-4 text-[10px] font-black text-red-500 uppercase tracking-widest italic text-right">Canceladas</th>
                        <th class="px-6 py-4 text-[10px] font-black text-orange-500 uppercase tracking-widest italic text-right">Devolvidas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <template x-for="row in data" :key="row.ncm">
                        <tr class="hover:bg-slate-700/20 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-sm font-black text-white" x-text="row.ncm"></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-bold text-emerald-400" x-text="formatMoney(row.total_compra)"></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-bold text-blue-400" x-text="formatMoney(row.total_venda)"></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-bold text-red-400" x-text="formatMoney(row.total_cancelada)"></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-bold text-orange-400" x-text="formatMoney(row.total_devolvida)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div x-show="!loading && data.length === 0" class="p-20 text-center text-slate-600 italic">
                <i class="fas fa-chart-bar text-4xl mb-4 opacity-20"></i>
                <p class="text-sm font-black uppercase tracking-widest">Nenhuma informação para o período selecionado</p>
            </div>

            <div x-show="loading" class="p-20 text-center text-indigo-500">
                <i class="fas fa-circle-notch fa-spin text-4xl mb-4"></i>
                <p class="text-sm font-black uppercase tracking-widest animate-pulse">Calculando Totais...</p>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
function relatorioNcm() {
    return {
        empresaId: 6,
        dataInicio: '',
        dataFim: '',
        loading: false,
        data: [],
        summary: {
            venda_total: 0,
            compra_total: 0,
            cancelada_total: 0,
            devolvida_total: 0
        },

        async init() {
            // Get empresa from localStorage
            const savedEmpresa = localStorage.getItem('empresa_id');
            this.empresaId = savedEmpresa ? parseInt(savedEmpresa) : 4;
            
            // Iniciar com data padrão: primeiro dia do mês até hoje
            const hoje = new Date();
            const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
            
            this.dataFim = hoje.toISOString().split('T')[0];
            this.dataInicio = primeiroDia.toISOString().split('T')[0];
            
            // Listen for empresa changes from the layout
            window.addEventListener('empresa-changed', (e) => {
                this.empresaId = parseInt(e.detail);
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadData();
            });

            // Initial load
            this.loadData();
        },

        async loadData() {
            if (!this.dataInicio || !this.dataFim || !this.empresaId) return;
            
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    empresa_id: this.empresaId,
                    data_inicial: this.dataInicio,
                    data_final: this.dataFim
                });
                
                const response = await fetch(`/api/relatorio-ncm?${params}`);
                if (response.ok) {
                    const result = await response.json();
                    this.data = result.data;
                    this.summary = result.summary;
                } else {
                    alert('Erro ao carregar relatório');
                }
            } catch (e) {
                console.error('Erro ao carregar relatório:', e);
            }
            this.loading = false;
        },

        formatMoney(value) {
            const num = parseFloat(value || 0);
            return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }
    }
}
</script>
@endsection
