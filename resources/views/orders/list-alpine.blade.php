@extends('layouts.alpine')

@section('title', 'Pedidos - NexusEcom')
@section('header_title', 'Pedidos')

@section('content')
    <!-- Header com Filtros -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Pedidos Marketplace</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Acompanhamento de vendas em tempo real</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-4 mb-6">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                    <input 
                        type="text" 
                        x-model="search"
                        @input.debounce.300ms="loadOrders()"
                        placeholder="Buscar pedido..."
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                    >
                </div>
            </div>
            <select x-model="status" @change="loadOrders()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos status</option>
                <option value="paid">Pago</option>
                <option value="pending">Pendente</option>
                <option value="canceled">Cancelado</option>
            </select>
            <select x-model="marketplace" @change="loadOrders()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos marketplaces</option>
                <option value="mercadolivre">Mercado Livre</option>
                <option value="amazon">Amazon</option>
                <option value="bling">Bling</option>
            </select>
            <button @click="syncOrders()" :disabled="syncing" class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <i class="fas fa-sync" :class="syncing ? 'fa-spin' : ''"></i>
                <span x-text="syncing ? 'Sincronizando...' : 'Sincronizar'"></span>
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Total Pedidos</p>
            <p class="text-2xl font-bold" x-text="orders.length"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Faturamento</p>
            <p class="text-2xl font-bold text-emerald-400" x-text="formatMoney(totalValue)"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Lucro</p>
            <p class="text-2xl font-bold" :class="totalProfit >= 0 ? 'text-green-400' : 'text-red-400'" x-text="formatMoney(totalProfit)"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Frete Médio</p>
            <p class="text-2xl font-bold text-amber-400" x-text="formatMoney(avgFrete)"></p>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-center py-12">
        <i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i>
    </div>

    <!-- Orders List -->
    <div x-show="!loading" class="space-y-4">
        <template x-for="order in orders" :key="order.id">
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                            :class="getMarketplaceColor(order.marketplace)"
                        >
                            <i :class="getMarketplaceIcon(order.marketplace)"></i>
                        </div>
                        <div>
                            <p class="font-bold" x-text="order.pedido_id || order.id"></p>
                            <p class="text-xs text-slate-400" x-text="formatDate(order.data_criacao)"></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-lg" x-text="formatMoney(order.valor_total)"></p>
                        <span class="text-xs px-2 py-1 rounded-full" :class="getStatusClass(order.status)" x-text="order.status"></span>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-700 flex flex-wrap gap-4 text-sm">
                    <span class="text-slate-400"><i class="fas fa-box mr-1"></i> <span x-text="order.itens?.length || 0"></span> itens</span>
                    <span class="text-slate-400"><i class="fas fa-truck mr-1"></i> <span x-text="formatMoney(order.frete || 0)"></span></span>
                    <span class="text-slate-400"><i class="fas fa-money-bill mr-1"></i> Lucro: <span :class="(order.lucro || 0) >= 0 ? 'text-green-400' : 'text-red-400'" x-text="formatMoney(order.lucro || 0)"></span></span>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty -->
    <div x-show="!loading && orders.length === 0" class="text-center py-12">
        <i class="fas fa-shopping-cart text-4xl text-slate-600 mb-4"></i>
        <p class="text-slate-400">Nenhum pedido encontrado</p>
    </div>
@endsection

@section('scripts')
<script>
function orders() {
    return {
        empresaId: localStorage.getItem('empresa_id') || '6',
        orders: [],
        loading: false,
        syncing: false,
        search: '',
        status: 'paid',
        marketplace: '',
        
        init() {
            this.$watch('empresaId', () => {
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadOrders();
            });
            this.loadOrders();
        },
        
        async loadOrders() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    empresa: this.empresaId,
                    status: this.status,
                    marketplace: this.marketplace,
                    search: this.search
                });
                
                const response = await fetch(`/api/orders?${params}`);
                this.orders = await response.json();
            } catch (e) {
                console.error('Error:', e);
            }
            this.loading = false;
        },
        
        async syncOrders() {
            this.syncing = true;
            try {
                await fetch(`/api/orders/sync?empresa=${this.empresaId}`, { method: 'POST' });
                await this.loadOrders();
            } catch (e) {
                console.error('Sync error:', e);
            }
            this.syncing = false;
        },
        
        get totalValue() {
            return this.orders.reduce((sum, o) => sum + (o.valor_total || 0), 0);
        },
        
        get totalProfit() {
            return this.orders.reduce((sum, o) => sum + (o.lucro || 0), 0);
        },
        
        get avgFrete() {
            if (!this.orders.length) return 0;
            const total = this.orders.reduce((sum, o) => sum + (o.frete || 0), 0);
            return total / this.orders.length;
        },
        
        getMarketplaceIcon(mp) {
            const icons = { 'mercadolivre': 'fab fa-mercado-livre', 'amazon': 'fab fa-amazon', 'bling': 'fas fa-cash-register' };
            return icons[mp] || 'fas fa-store';
        },
        
        getMarketplaceColor(mp) {
            const colors = { 'mercadolivre': 'bg-yellow-500/20 text-yellow-400', 'amazon': 'bg-orange-500/20 text-orange-400', 'bling': 'bg-green-500/20 text-green-400' };
            return colors[mp] || 'bg-slate-600';
        },
        
        getStatusClass(status) {
            const classes = { 'paid': 'bg-green-500/20 text-green-400', 'pending': 'bg-yellow-500/20 text-yellow-400', 'canceled': 'bg-red-500/20 text-red-400' };
            return classes[status] || 'bg-slate-600';
        },
        
        formatDate(date) {
            return new Date(date).toLocaleDateString('pt-BR');
        },
        
        formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
        }
    }
}
</script>
@endsection
