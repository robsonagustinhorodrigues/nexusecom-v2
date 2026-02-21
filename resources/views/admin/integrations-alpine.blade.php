@extends('layouts.alpine')

@section('title', 'Integrações - NexusEcom')
@section('header_title', 'Integrações')

@section('content')
<div x-data="integrationsPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Integrações Marketplace</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Conecte suas lojas e sincronize pedidos</p>
        </div>
    </div>

    <!-- Modal Renomear -->
    <div x-show="showRenameModal" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4" x-cloak>
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 w-full max-w-sm">
            <h3 class="text-lg font-bold text-white mb-4">Renomear Conexão</h3>
            <input type="text" x-model="renameValue" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white mb-4" placeholder="Novo nome">
            <div class="flex gap-3">
                <button @click="showRenameModal = false" class="flex-1 py-3 bg-slate-700 text-white rounded-xl font-bold">Cancelar</button>
                <button @click="saveRename()" class="flex-1 py-3 bg-indigo-600 text-white rounded-xl font-bold">Salvar</button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Mercado Livre -->
        <div class="bg-slate-800 border rounded-3xl p-6 shadow-xl transition-all group"
             :class="meliIntegration ? 'border-yellow-500/30 hover:border-yellow-500' : 'border-slate-700 hover:border-slate-600'">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-yellow-400 rounded-2xl flex items-center justify-center shadow-lg shadow-yellow-400/20 group-hover:scale-110 transition-transform">
                        <i class="fas fa-shopping-bag text-slate-900 text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white text-lg italic uppercase tracking-tight">Mercado Livre</h3>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Integração Oficial</span>
                    </div>
                </div>
                <div class="w-2 h-2 rounded-full" :class="meliIntegration ? 'bg-emerald-500 animate-pulse' : 'bg-slate-600'"></div>
            </div>
            
            <div class="space-y-3">
                <template x-if="meliIntegration">
                    <div class="p-3 bg-slate-900/50 rounded-2xl border border-slate-700/50">
                        <div class="flex justify-between items-center text-xs mb-2">
                            <span class="text-slate-500 font-bold uppercase">Conta</span>
                            <span class="text-white" x-text="meliIntegration.nome_conta || '-'"></span>
                        </div>
                        <div class="flex justify-between items-center text-xs mb-2">
                            <span class="text-slate-500 font-bold uppercase">Status</span>
                            <span class="text-emerald-400 font-black italic">Ativo</span>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <a href="/anuncios" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-center text-xs">
                                Ver Anúncios
                            </a>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button @click="testConnection('meli')" :disabled="testing" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold text-xs">
                                <i class="fas fa-plug mr-1"></i> Testar
                            </button>
                            <button @click="openRenameModal('meli')" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold text-xs">
                                <i class="fas fa-edit mr-1"></i> Renomear
                            </button>
                        </div>
                    </div>
                </template>
                <template x-if="!meliIntegration">
                    <a href="/integrations/meli/redirect" class="block w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl font-black text-center transition-all shadow-lg shadow-indigo-600/20 italic uppercase text-sm">
                        Conectar Conta
                    </a>
                </template>
            </div>
        </div>

        <!-- Bling -->
        <div class="bg-slate-800 border rounded-3xl p-6 shadow-xl transition-all group"
             :class="blingIntegration ? 'border-emerald-500/30 hover:border-emerald-500' : 'border-slate-700 hover:border-slate-600'">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-emerald-500 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/20 group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-invoice text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white text-lg italic uppercase tracking-tight">Bling ERP</h3>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Sincronização</span>
                    </div>
                </div>
                <div class="w-2 h-2 rounded-full" :class="blingIntegration ? 'bg-emerald-500 animate-pulse' : 'bg-slate-600'"></div>
            </div>
            
            <div class="space-y-3">
                <template x-if=" <div class="p-3 bgblingIntegration">
                   -slate-900/50 rounded-2xl border border-slate-700/50">
                        <div class="flex justify-between items-center text-xs mb-2">
                            <span class="text-slate-500 font-bold uppercase">Loja</span>
                            <span class="text-white" x-text="blingIntegration.nome_conta || '-'"></span>
                        </div>
                        <div class="flex justify-between items-center text-xs mb-2">
                            <span class="text-slate-500 font-bold uppercase">Status</span>
                            <span class="text-emerald-400 font-black italic">Ativo</span>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button @click="syncBlingProdutos()" :disabled="blingSyncing" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold text-xs">
                                <i class="fas fa-sync mr-1" :class="blingSyncing ? 'fa-spin' : ''"></i>
                                <span x-text="blingSyncing ? '...' : 'Produtos'"></span>
                            </button>
                            <button @click="syncBlingPedidos()" :disabled="blingSyncingPedidos" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold text-xs">
                                <i class="fas fa-shopping-cart mr-1"></i>
                                <span x-text="blingSyncingPedidos ? '...' : 'Pedidos'"></span>
                            </button>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button @click="testConnection('bling')" :disabled="testing" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold text-xs">
                                <i class="fas fa-plug mr-1"></i> Testar
                            </button>
                            <button @click="openRenameModal('bling')" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold text-xs">
                                <i class="fas fa-edit mr-1"></i> Renomear
                            </button>
                        </div>
                    </div>
                </template>
                <template x-if="!blingIntegration">
                    <a href="/integrations/bling/connect" class="block w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-black text-center transition-all shadow-lg shadow-emerald-600/20 italic uppercase text-sm">
                        Conectar Bling
                    </a>
                </template>
            </div>
        </div>

        <!-- Amazon -->
        <div class="bg-slate-800 border rounded-3xl p-6 shadow-xl transition-all group"
             :class="amazonIntegration ? 'border-orange-500/30 hover:border-orange-500' : 'border-slate-700 hover:border-slate-600'">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-orange-500 rounded-2xl flex items-center justify-center shadow-lg shadow-orange-500/20 group-hover:scale-110 transition-transform">
                        <i class="fab fa-amazon text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white text-lg italic uppercase tracking-tight">Amazon SP-API</h3>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Sales Partner</span>
                    </div>
                </div>
                <div class="w-2 h-2 rounded-full" :class="amazonIntegration ? 'bg-emerald-500 animate-pulse' : 'bg-slate-600'"></div>
            </div>
            
            <div class="space-y-3">
                <template x-if="amazonIntegration">
                    <div class="p-3 bg-slate-900/50 rounded-2xl border border-slate-700/50">
                        <div class="flex justify-between items-center text-xs mb-2">
                            <span class="text-slate-500 font-bold uppercase">Seller ID</span>
                            <span class="text-white" x-text="amazonIntegration.external_user_id || '-'"></span>
                        </div>
                        <div class="flex justify-between items-center text-xs mb-2">
                            <span class="text-slate-500 font-bold uppercase">Status</span>
                            <span class="text-emerald-400 font-black italic">Ativo</span>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button @click="syncAmazonOrders()" :disabled="amazonSyncing" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold text-xs">
                                <i class="fas fa-sync mr-1" :class="amazonSyncing ? 'fa-spin' : ''"></i>
                                <span x-text="amazonSyncing ? '...' : 'Pedidos'"></span>
                            </button>
                            <button @click="syncAmazonInventory()" :disabled="amazonSyncingInv" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold text-xs">
                                <i class="fas fa-boxes mr-1"></i>
                                <span x-text="amazonSyncingInv ? '...' : 'Estoque'"></span>
                            </button>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button @click="testConnection('amazon')" :disabled="testing" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold text-xs">
                                <i class="fas fa-plug mr-1"></i> Testar
                            </button>
                            <button @click="openRenameModal('amazon')" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold text-xs">
                                <i class="fas fa-edit mr-1"></i> Renomear
                            </button>
                        </div>
                    </div>
                </template>
                <template x-if="!amazonIntegration">
                    <a href="/integrations/amazon/connect" class="block w-full py-3 bg-orange-600 hover:bg-orange-500 text-white rounded-2xl font-black text-center transition-all shadow-lg shadow-orange-600/20 italic uppercase text-sm">
                        Conectar Amazon
                    </a>
                </template>
            </div>
        </div>

        <!-- Shopee (Em Breve) -->
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl hover:border-slate-600 transition-all group opacity-75">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-red-500 rounded-2xl flex items-center justify-center shadow-lg shadow-red-500/20 group-hover:scale-110 transition-transform">
                        <i class="fas fa-shopping-bag text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white text-lg italic uppercase tracking-tight">Shopee</h3>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Marketplace</span>
                    </div>
                </div>
                <div class="w-2 h-2 rounded-full bg-slate-600"></div>
            </div>
            
            <div class="space-y-3">
                <button disabled class="w-full py-3 bg-slate-700 text-slate-500 rounded-2xl font-black italic uppercase text-sm cursor-not-allowed">
                    Em Breve
                </button>
            </div>
        </div>

        <!-- Magalu (Em Breve) -->
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl hover:border-slate-600 transition-all group opacity-75">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-green-500 rounded-2xl flex items-center justify-center shadow-lg shadow-green-500/20 group-hover:scale-110 transition-transform">
                        <i class="fas fa-box text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white text-lg italic uppercase tracking-tight">Magazine Luiza</h3>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Marketplace</span>
                    </div>
                </div>
                <div class="w-2 h-2 rounded-full bg-slate-600"></div>
            </div>
            
            <div class="space-y-3">
                <button disabled class="w-full py-3 bg-slate-700 text-slate-500 rounded-2xl font-black italic uppercase text-sm cursor-not-allowed">
                    Em Breve
                </button>
            </div>
        </div>

        <!-- Shopify (Em Breve) -->
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl hover:border-slate-600 transition-all group opacity-75">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-green-600 rounded-2xl flex items-center justify-center shadow-lg shadow-green-600/20 group-hover:scale-110 transition-transform">
                        <i class="fab fa-shopify text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white text-lg italic uppercase tracking-tight">Shopify</h3>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">E-commerce</span>
                    </div>
                </div>
                <div class="w-2 h-2 rounded-full bg-slate-600"></div>
            </div>
            
            <div class="space-y-3">
                <button disabled class="w-full py-3 bg-slate-700 text-slate-500 rounded-2xl font-black italic uppercase text-sm cursor-not-allowed">
                    Em Breve
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function integrationsPage() {
    return {
        integrations: [],
        meliIntegration: null,
        blingIntegration: null,
        amazonIntegration: null,
        blingSyncing: false,
        blingSyncingPedidos: false,
        amazonSyncing: false,
        amazonSyncingInv: false,
        testing: false,
        showRenameModal: false,
        renameType: '',
        renameValue: '',
        
        init() {
            this.loadIntegrations();
        },
        
        async loadIntegrations() {
            try {
                const empresaId = localStorage.getItem('empresa_id') || '6';
                const response = await fetch(`/api/admin/integrations?empresa=${empresaId}`);
                this.integrations = await response.json();
                
                this.meliIntegration = this.integrations.find(i => i.marketplace === 'mercadolivre');
                this.blingIntegration = this.integrations.find(i => i.marketplace === 'bling');
                this.amazonIntegration = this.integrations.find(i => i.marketplace === 'amazon');
            } catch (e) {
                console.error('Error loading integrations:', e);
            }
        },
        
        async testConnection(type) {
            this.testing = true;
            try {
                const response = await fetch(`/integrations/${type}/test`, { method: 'POST' });
                const data = await response.json();
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao testar conexão');
            }
            this.testing = false;
        },
        
        openRenameModal(type) {
            this.renameType = type;
            let currentName = '';
            if (type === 'meli' && this.meliIntegration) currentName = this.meliIntegration.nome_conta || '';
            if (type === 'bling' && this.blingIntegration) currentName = this.blingIntegration.nome_conta || '';
            if (type === 'amazon' && this.amazonIntegration) currentName = this.amazonIntegration.nome_conta || '';
            this.renameValue = currentName;
            this.showRenameModal = true;
        },
        
        async saveRename() {
            try {
                const response = await fetch(`/integrations/${this.renameType}/update-nome`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ nome: this.renameValue })
                });
                if (response.ok) {
                    this.showRenameModal = false;
                    this.loadIntegrations();
                    alert('Nome atualizado!');
                } else {
                    alert('Erro ao atualizar');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao salvar');
            }
        },
        
        async syncBlingProdutos() {
            this.blingSyncing = true;
            try {
                const response = await fetch('/integrations/bling/sync-produtos', { method: 'POST' });
                const data = await response.json();
                alert(data.message || 'Sincronização concluída!');
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao sincronizar');
            }
            this.blingSyncing = false;
        },
        
        async syncBlingPedidos() {
            this.blingSyncingPedidos = true;
            try {
                const response = await fetch('/integrations/bling/sync-pedidos', { method: 'POST' });
                const data = await response.json();
                alert(data.message || 'Sincronização concluída!');
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao sincronizar');
            }
            this.blingSyncingPedidos = false;
        },
        
        async syncAmazonOrders() {
            this.amazonSyncing = true;
            try {
                const response = await fetch('/integrations/amazon/sync-orders', { method: 'POST' });
                const data = await response.json();
                alert(data.message || 'Sincronização concluída!');
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao sincronizar');
            }
            this.amazonSyncing = false;
        },
        
        async syncAmazonInventory() {
            this.amazonSyncingInv = true;
            try {
                const response = await fetch('/integrations/amazon/sync-inventory', { method: 'POST' });
                const data = await response.json();
                alert(data.message || 'Sincronização concluída!');
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao sincronizar');
            }
            this.amazonSyncingInv = false;
        }
    }
}
</script>
@endsection
