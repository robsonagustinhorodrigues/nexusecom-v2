@extends('layouts.alpine')

@section('title', 'Estoque - NexusEcom')
@section('header_title', 'Estoque')

@section('content')
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Controle de Estoque</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Movimentação &balances em tempo real</p>
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
                        @input.debounce.300ms="loadEstoque()"
                        placeholder="Buscar por SKU, EAN ou descrição..."
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                    >
                </div>
            </div>
            <select x-model="filtroDeposito" @change="loadEstoque()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm min-w-[150px]">
                <option value="">Todos os Depósitos</option>
                <template x-for="dep in depositos" :key="dep.id">
                    <option :value="dep.id" x-text="dep.nome"></option>
                </template>
            </select>
            <select x-model="filtroStatus" @change="loadEstoque()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos</option>
                <option value="disponivel">Disponível</option>
                <option value="baixo">Estoque Baixo</option>
                <option value="zerado">Sem Estoque</option>
            </select>
            <select x-model="filtroAtivos" @change="loadEstoque()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos os status</option>
                <option value="1">Apenas Ativos</option>
                <option value="0">Apenas Inativos</option>
            </select>
            <button @click="openMovimentacao()" class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm flex items-center gap-2 ml-auto">
                <i class="fas fa-plus"></i> Nova Movimentação
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Total SKUs</p>
            <p class="text-2xl font-bold" x-text="itens.length"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Disponível</p>
            <p class="text-2xl font-bold text-green-400" x-text="disponivelCount"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Estoque Baixo</p>
            <p class="text-2xl font-bold text-amber-400" x-text="baixoCount"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Sem Estoque</p>
            <p class="text-2xl font-bold text-red-400" x-text="zeradoCount"></p>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-center py-12">
        <i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i>
    </div>

    <!-- Estoque Table -->
    <div x-show="!loading" class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-slate-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400">Produto</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400">SKU</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400">EAN</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-400">Saldo</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-400">Mín</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-400">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                <template x-for="item in itens" :key="item.id">
                    <tr class="hover:bg-slate-700/30">
                        <td class="px-4 py-3">
                            <p class="font-medium" x-text="item.produto_nome"></p>
                            <p class="text-xs text-slate-500" x-text="item.deposito_nome"></p>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-400" x-text="item.sku_codigo"></td>
                        <td class="px-4 py-3 text-sm text-slate-400" x-text="item.ean || '-'"></td>
                        <td class="px-4 py-3 text-right font-bold" :class="getSaldoClass(item)" x-text="item.saldo"></td>
                        <td class="px-4 py-3 text-right text-slate-400" x-text="item.saldo_minimo || 0"></td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-1 rounded-full" :class="getStatusClass(item)" x-text="getStatus(item)"></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button @click="openMovimentacao(item)" class="p-2 hover:bg-slate-700 rounded-lg">
                                <i class="fas fa-plus-circle text-indigo-400"></i>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Empty -->
    <div x-show="!loading && itens.length === 0" class="text-center py-12">
        <i class="fas fa-warehouse text-4xl text-slate-600 mb-4"></i>
        <p class="text-slate-400">Nenhum item em estoque</p>
    </div>

    <!-- Movimentação Modal -->
    <div x-show="showMovimentacao" x-transition class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-slate-800 rounded-xl border border-slate-700 w-full max-w-md p-6">
            <h3 class="font-bold text-lg mb-4">Nova Movimentação</h3>
            <form @submit.prevent="salvarMovimentacao()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Depósito</label>
                        <select x-model="movimentacao.deposito_id" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2">
                            <option value="">Selecione...</option>
                            <template x-for="dep in depositos" :key="dep.id">
                                <option :value="dep.id" x-text="dep.nome"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Tipo</label>
                        <select x-model="movimentacao.tipo" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2">
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Quantidade</label>
                        <input type="number" x-model="movimentacao.quantidade" required min="1" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Observação</label>
                        <input type="text" x-model="movimentacao.observacao" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" @click="showMovimentacao = false" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg">Cancelar</button>
                    <button type="submit" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg">Salvar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
function estoque() {
    return {
        empresaId: localStorage.getItem('empresa_id') || '6',
        loading: false,
        search: '',
        filtroDeposito: '',
        filtroStatus: '',
        filtroAtivos: '',
        itens: [],
        depositos: [],
        showMovimentacao: false,
        movimentacao: { deposito_id: '', tipo: 'entrada', quantidade: 1, observacao: '' },
        
        init() {
            // Get empresa from localStorage
            const savedEmpresa = localStorage.getItem('empresa_id');
            this.empresaId = savedEmpresa ? parseInt(savedEmpresa) : 6;
            
            // Watch for local changes
            this.$watch('empresaId', () => {
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadEstoque();
            });
            
            // Listen for empresa changes from the layout
            window.addEventListener('empresa-changed', (e) => {
                this.empresaId = parseInt(e.detail);
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadEstoque();
            });
            
            this.loadDepositos();
            this.loadEstoque();
        },
        
        async loadDepositos() {
            try {
                const response = await fetch('/api/estoque/depositos');
                this.depositos = await response.json();
            } catch (e) {
                console.error('Error:', e);
            }
        },
        
        async loadEstoque() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    empresa: this.empresaId,
                    search: this.search,
                    deposito_id: this.filtroDeposito,
                    status: this.filtroStatus,
                    ativos: this.filtroAtivos
                });
                
                const response = await fetch(`/api/estoque?${params}`);
                const data = await response.json();
                this.itens = data.data || [];
            } catch (e) {
                console.error('Error:', e);
            }
            this.loading = false;
        },
        
        openMovimentacao(item = null) {
            if (item) {
                this.movimentacao.deposito_id = item.deposito_id;
            } else {
                this.movimentacao = { deposito_id: this.depositos[0]?.id || '', tipo: 'entrada', quantidade: 1, observacao: '' };
            }
            this.showMovimentacao = true;
        },
        
        async salvarMovimentacao() {
            try {
                await fetch('/api/estoque/movimentacao', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.movimentacao)
                });
                this.showMovimentacao = false;
                this.loadEstoque();
            } catch (e) {
                console.error('Error:', e);
            }
        },
        
        get disponivelCount() {
            return this.itens.filter(i => i.saldo > (i.saldo_minimo || 0)).length;
        },
        
        get baixoCount() {
            return this.itens.filter(i => i.saldo > 0 && i.saldo <= (i.saldo_minimo || 0)).length;
        },
        
        get zeradoCount() {
            return this.itens.filter(i => !i.saldo || i.saldo <= 0).length;
        },
        
        getStatus(item) {
            if (!item.saldo || item.saldo <= 0) return 'Sem Estoque';
            if (item.saldo <= (item.saldo_minimo || 0)) return 'Baixo';
            return 'Disponível';
        },
        
        getStatusClass(item) {
            if (!item.saldo || item.saldo <= 0) return 'bg-red-500/20 text-red-400';
            if (item.saldo <= (item.saldo_minimo || 0)) return 'bg-amber-500/20 text-amber-400';
            return 'bg-green-500/20 text-green-400';
        },
        
        getSaldoClass(item) {
            if (!item.saldo || item.saldo <= 0) return 'text-red-400';
            if (item.saldo <= (item.saldo_minimo || 0)) return 'text-amber-400';
            return 'text-green-400';
        }
    }
}
</script>
@endsection
