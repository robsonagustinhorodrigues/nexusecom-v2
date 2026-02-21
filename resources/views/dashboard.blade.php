@extends('layouts.alpine')

@section('header_title', 'Dashboard')
@section('content')
<div x-data="dashboard()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Dashboard</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Visão geral das operações em tempo real</p>
        </div>
    </div>

    <!-- Dashboard Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Vendas Hoje -->
        <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700 shadow-sm transition-all hover:border-indigo-500/50 group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-500 group-hover:bg-indigo-500 group-hover:text-white transition-all">
                    <i class="fas fa-shopping-bag text-xl"></i>
                </div>
                <span class="text-xs font-bold text-emerald-500 bg-emerald-500/10 px-2 py-1 rounded-full">+12%</span>
            </div>
            <p class="text-slate-400 text-sm font-medium">Vendas Hoje</p>
            <h3 class="text-2xl font-bold mt-1" x-text="formatMoney(stats.vendas_hoje)">R$ 0,00</h3>
        </div>

        <!-- Pedidos -->
        <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700 shadow-sm transition-all hover:border-purple-500/50 group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-500 group-hover:bg-purple-500 group-hover:text-white transition-all">
                    <i class="fas fa-box text-xl"></i>
                </div>
                <span class="text-xs font-bold text-slate-400 bg-slate-400/10 px-2 py-1 rounded-full" x-text="stats.pedidos_count">0</span>
            </div>
            <p class="text-slate-400 text-sm font-medium">Pedidos</p>
            <h3 class="text-2xl font-bold mt-1" x-text="stats.pedidos_count">0</h3>
        </div>

        <!-- Lucro Estimado -->
        <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700 shadow-sm transition-all hover:border-emerald-500/50 group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-all">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
                <i class="fas fa-info-circle text-slate-600 cursor-help" title="Cálculo baseado na margem média"></i>
            </div>
            <p class="text-slate-400 text-sm font-medium">Lucro Estimado</p>
            <h3 class="text-2xl font-bold mt-1 text-emerald-400" x-text="formatMoney(stats.lucro_estimado)">R$ 0,00</h3>
        </div>

        <!-- Frete Médio -->
        <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700 shadow-sm transition-all hover:border-amber-500/50 group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-all">
                    <i class="fas fa-truck text-xl"></i>
                </div>
                <span class="text-xs font-bold text-amber-500 bg-amber-500/10 px-2 py-1 rounded-full">Alerta</span>
            </div>
            <p class="text-slate-400 text-sm font-medium">Frete Médio</p>
            <h3 class="text-2xl font-bold mt-1 text-amber-400" x-text="formatMoney(stats.frete_medio)">R$ 0,00</h3>
        </div>
    </div>

    <!-- Charts and Tables Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Chart -->
        <div class="lg:col-span-2 bg-slate-800 rounded-2xl border border-slate-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-lg">Desempenho de Vendas</h3>
                <select class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-400">
                    <option>Últimos 7 dias</option>
                    <option>Últimos 30 dias</option>
                </select>
            </div>
            <div class="h-64 flex items-center justify-center border border-dashed border-slate-700 rounded-xl bg-slate-900/50">
                <p class="text-slate-500 text-sm">Gráfico em processamento...</p>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-700 flex items-center justify-between">
                <h3 class="font-bold text-lg">Pedidos Recentes</h3>
                <a href="/orders" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold">Ver todos</a>
            </div>
            <div class="flex-1 overflow-y-auto">
                <template x-for="order in recentOrders" :key="order.id">
                    <div class="p-4 border-b border-slate-700/50 hover:bg-slate-700/30 transition-all flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-slate-700 flex items-center justify-center">
                                <i :class="getMarketplaceIcon(order.marketplace)" class="text-slate-400"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold" x-text="order.pedido_id"></p>
                                <p class="text-[10px] text-slate-500" x-text="formatDate(order.data_criacao)"></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold" x-text="formatMoney(order.valor_total)"></p>
                            <span :class="'status-' + order.status" class="text-[10px] px-2 py-0.5 rounded-full uppercase font-bold" x-text="order.status"></span>
                        </div>
                    </div>
                </template>
                <div x-show="recentOrders.length === 0" class="p-8 text-center text-slate-500">
                    <i class="fas fa-shopping-cart mb-2 text-2xl"></i>
                    <p class="text-sm">Nenhum pedido</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function dashboard() {
        return {
            stats: {
                vendas_hoje: 12540.80,
                pedidos_count: 42,
                lucro_estimado: 3240.15,
                frete_medio: 18.90
            },
            recentOrders: [],
            
            init() {
                this.loadRecentOrders();
                
                // Listen for company changes
                window.addEventListener('empresa-changed', (e) => {
                    this.loadRecentOrders();
                });
            },
            
            async loadRecentOrders() {
                try {
                    const empresaId = localStorage.getItem('empresa_id') || '4';
                    const response = await fetch(`/api/orders?empresa=${empresaId}&limit=5`);
                    this.recentOrders = await response.json();
                } catch (e) {
                    console.error('Error loading orders:', e);
                }
            },
            
            getMarketplaceIcon(mp) {
                const icons = {
                    'mercadolivre': 'fab fa-mercado-livre',
                    'amazon': 'fab fa-amazon',
                    'bling': 'fas fa-cash-register'
                };
                return icons[mp] || 'fas fa-store';
            },
            
            formatDate(date) {
                return new Date(date).toLocaleDateString('pt-BR');
            },
            
            formatMoney(value) {
                return new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                }).format(value);
            }
        }
    }
    
    // Auto-init dashboard if on the right page
    document.addEventListener('alpine:init', () => {
        if (window.location.pathname === '/dashboard') {
            // This is a bit hacky for standalone Alpine components in a shared layout
        }
    });
</script>
@endsection
