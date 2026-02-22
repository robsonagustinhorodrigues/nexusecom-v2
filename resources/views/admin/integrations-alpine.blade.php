@extends('layouts.alpine')

@section('title', 'Integrações - NexusEcom')
@section('header_title', 'Integrações')

@section('content')
<div x-data="integrationsPage()" x-init="init()">
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
        <div class="bg-slate-800 border rounded-3xl p-6 shadow-xl" :class="meliIntegration ? 'border-yellow-500/50' : 'border-slate-700'">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-yellow-400 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-slate-900 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white">Mercado Livre</h3>
                        <span class="text-[10px] text-slate-500 uppercase">Integração Oficial</span>
                    </div>
                </div>
                <div class="w-3 h-3 rounded-full" :class="meliIntegration ? 'bg-green-500' : 'bg-slate-600'"></div>
            </div>
            
            <template x-if="meliIntegration">
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Conta</span>
                        <span class="text-white font-medium" x-text="meliIntegration.nome_conta || '-'"></span>
                    </div>
                    <div class="flex gap-2">
                        <button @click="testConnection('meli')" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-bold text-sm">Testar</button>
                        <button @click="openRenameModal('meli')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm">Renomear</button>
                    </div>
                </div>
            </template>
            <template x-if="!meliIntegration">
                <a href="/integrations/meli/redirect" class="block w-full py-3 bg-yellow-500 hover:bg-yellow-400 text-slate-900 rounded-xl font-bold text-center">Conectar</a>
            </template>
        </div>

        <!-- Bling -->
        <div class="bg-slate-800 border rounded-3xl p-6 shadow-xl" :class="blingIntegration ? 'border-green-500/50' : 'border-slate-700'">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-invoice text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white">Bling ERP</h3>
                        <span class="text-[10px] text-slate-500 uppercase">Sincronização</span>
                    </div>
                </div>
                <div class="w-3 h-3 rounded-full" :class="blingIntegration ? 'bg-green-500' : 'bg-slate-600'"></div>
            </div>
            
            <template x-if="blingIntegration">
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Loja</span>
                        <span class="text-white font-medium" x-text="blingIntegration.nome_conta || '-'"></span>
                    </div>
                    <div class="flex gap-2">
                        <button @click="testConnection('bling')" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-bold text-sm">Testar</button>
                        <button @click="openRenameModal('bling')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm">Renomear</button>
                    </div>
                </div>
            </template>
            <template x-if="!blingIntegration">
                <a href="/integrations/bling/connect" class="block w-full py-3 bg-green-500 hover:bg-green-400 text-white rounded-xl font-bold text-center">Conectar</a>
            </template>
        </div>

        <!-- Amazon -->
        <div class="bg-slate-800 border rounded-3xl p-6 shadow-xl" :class="amazonIntegration ? 'border-orange-500/50' : 'border-slate-700'">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center">
                        <i class="fab fa-amazon text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white">Amazon</h3>
                        <span class="text-[10px] text-slate-500 uppercase">SP-API</span>
                    </div>
                </div>
                <div class="w-3 h-3 rounded-full" :class="amazonIntegration ? 'bg-green-500' : 'bg-slate-600'"></div>
            </div>
            
            <template x-if="amazonIntegration">
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Seller ID</span>
                        <span class="text-white font-medium" x-text="amazonIntegration.external_user_id || '-'"></span>
                    </div>
                    <div class="flex gap-2">
                        <button @click="testConnection('amazon')" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-bold text-sm">Testar</button>
                        <button @click="openRenameModal('amazon')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm">Renomear</button>
                    </div>
                </div>
            </template>
            <template x-if="!amazonIntegration">
                <a href="/integrations/amazon/connect" class="block w-full py-3 bg-orange-500 hover:bg-orange-400 text-white rounded-xl font-bold text-center">Conectar</a>
            </template>
        </div>

        <!-- Shopee -->
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 opacity-60">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-red-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white">Shopee</h3>
                    </div>
                </div>
                <span class="text-xs text-slate-500">Em Breve</span>
            </div>
        </div>

        <!-- Magalu -->
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 opacity-60">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-box text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white">Magazine Luiza</h3>
                    </div>
                </div>
                <span class="text-xs text-slate-500">Em Breve</span>
            </div>
        </div>

        <!-- Shopify -->
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 opacity-60">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fab fa-shopify text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white">Shopify</h3>
                    </div>
                </div>
                <span class="text-xs text-slate-500">Em Breve</span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function integrationsPage() {
    return {
        empresaId: 6,
        integrations: [],
        meliIntegration: null,
        blingIntegration: null,
        amazonIntegration: null,
        testing: false,
        showRenameModal: false,
        renameType: '',
        renameValue: '',
        
        init() {
            // Get empresa from localStorage
            const savedEmpresa = localStorage.getItem('empresa_id');
            this.empresaId = savedEmpresa ? parseInt(savedEmpresa) : 6;
            
            this.loadIntegrations(this.empresaId);
            
            // Listen for empresa changes from the layout
            window.addEventListener('empresa-changed', (e) => {
                this.empresaId = parseInt(e.detail);
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadIntegrations(this.empresaId);
            });
        },
        
        async loadIntegrations(empresaId = null) {
            if (!empresaId) {
                empresaId = this.empresaId || parseInt(localStorage.getItem('empresa_id')) || 6;
            }
            
            try {
                const response = await fetch(`/api/admin/integrations?empresa=${empresaId}`);
                const data = await response.json();
                this.integrations = data;
                
                this.meliIntegration = this.integrations.find(i => i.marketplace === 'mercadolivre');
                this.blingIntegration = this.integrations.find(i => i.marketplace === 'bling');
                this.amazonIntegration = this.integrations.find(i => i.marketplace === 'amazon');
                
                console.log('Loaded integrations for empresa', empresaId, data);
            } catch (e) {
                console.error('Error:', e);
            }
        },
        
        async testConnection(type) {
            this.testing = true;
            const empresaId = this.empresaId || localStorage.getItem('empresa_id') || 6;
            try {
                const response = await fetch(`/integrations/${type}/test?empresa=${empresaId}`, { 
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
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
                const empresaId = this.empresaId || localStorage.getItem('empresa_id') || 6;
                const response = await fetch(`/integrations/${this.renameType}/update-nome?empresa=${empresaId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ nome: this.renameValue })
                });
                if (response.ok) {
                    this.showRenameModal = false;
                    this.loadIntegrations();
                    alert('Nome atualizado!');
                }
            } catch (e) {
                console.error('Error:', e);
            }
        }
    }
}
</script>
@endsection
