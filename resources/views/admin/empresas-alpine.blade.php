@extends('layouts.alpine')

@section('title', 'Empresas - NexusEcom')
@section('header_title', 'Empresas')

@section('content')
<div x-data="empresasPage()" x-init="init()">
    <!-- Header Actions -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Hub de Empresas</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Gerenciamento de Unidades & Configurações Fiscais</p>
        </div>

        <button @click="openCreate()" class="px-6 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2 italic uppercase tracking-wider group">
            <i class="fas fa-plus text-xs group-hover:scale-110 transition-transform"></i>
            Nova Empresa
        </button>
    </div>

    <!-- Feedback Message -->
    <template x-if="message">
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-2xl font-black text-sm flex items-center gap-2 mb-6 italic animate-bounce">
            <i class="fas fa-check-circle"></i>
            <span x-text="message"></span>
        </div>
    </template>

    <!-- Grid de Empresas -->
    <div x-show="!showModal" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="empresa in empresas" :key="empresa.id">
            <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl hover:border-indigo-500/30 transition-all group relative overflow-hidden">
                <div class="flex justify-between items-start mb-6">
                    <div class="w-16 h-16 rounded-2xl bg-slate-900 border border-slate-700 flex items-center justify-center overflow-hidden shadow-inner">
                        <img x-if="empresa.logo_path" :src="'/storage/' + empresa.logo_path" class="w-full h-full object-cover">
                        <i x-else class="fas fa-building text-slate-700 text-2xl group-hover:rotate-12 transition-transform"></i>
                    </div>
                    <div class="flex gap-2">
                        <button @click="editEmpresa(empresa)" class="w-9 h-9 rounded-xl bg-slate-700 text-slate-300 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                            <i class="fas fa-edit text-xs"></i>
                        </button>
                        <button @click="deleteEmpresa(empresa.id)" class="w-9 h-9 rounded-xl bg-slate-700 text-slate-400 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>
                
                <h3 class="font-black text-white text-lg italic uppercase tracking-tighter mb-1 line-clamp-1" x-text="empresa.nome"></h3>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-4" x-text="empresa.cnpj || '00.000.000/0000-00'"></p>
                
                <div class="space-y-3 pt-4 border-t border-slate-700/50">
                    <div class="flex justify-between items-center text-[10px] font-black uppercase italic">
                        <span class="text-slate-500">Regime Tributário</span>
                        <span class="text-indigo-400" x-text="empresa.regime_tributario?.replace('_', ' ')"></span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-black uppercase italic">
                        <span class="text-slate-500">Certificado A1</span>
                        <span :class="empresa.certificado_a1_path ? 'text-emerald-400' : 'text-rose-500'">
                            <i :class="empresa.certificado_a1_path ? 'fa-check-circle' : 'fa-exclamation-triangle'" class="fas mr-1"></i>
                            <span x-text="empresa.certificado_a1_path ? 'ATIVO' : 'PENDENTE'"></span>
                        </span>
                    </div>
                </div>

                <!-- Glow effect on hover -->
                <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-indigo-600/5 rounded-full blur-3xl group-hover:bg-indigo-600/10 transition-all"></div>
            </div>
        </template>
    </div>

    <!-- Modal Form Full (Upgrade: Mesma funcionalidade do Livewire) -->
    <div x-show="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-slate-800 border border-slate-700 w-full max-w-4xl max-h-[90vh] rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col" @click.away="showModal = false">
            <!-- Modal Header -->
            <div class="p-8 border-b border-slate-700 bg-slate-900/50 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-600/20">
                        <i class="fas" :class="isEditing ? 'fa-edit' : 'fa-plus'"></i>
                    </div>
                    <h3 class="text-xl font-black text-white italic uppercase tracking-tighter" x-text="isEditing ? 'Editar Empresa' : 'Nova Unidade de Negócio'"></h3>
                </div>
                <button @click="showModal = false" class="text-slate-500 hover:text-white transition-colors"><i class="fas fa-times text-xl"></i></button>
            </div>

            <!-- Modal Tabs -->
            <div class="flex border-b border-slate-700 px-8 bg-slate-900/20">
                <button @click="activeTab = 'basic'" :class="activeTab === 'basic' ? 'border-indigo-500 text-white' : 'border-transparent text-slate-500'" class="px-6 py-4 text-xs font-black uppercase italic tracking-widest border-b-2 transition-all">Dados Básicos</button>
                <button @click="activeTab = 'fiscal'" :class="activeTab === 'fiscal' ? 'border-indigo-500 text-white' : 'border-transparent text-slate-500'" class="px-6 py-4 text-xs font-black uppercase italic tracking-widest border-b-2 transition-all">Fiscal & Impostos</button>
                <button @click="activeTab = 'danfe'" :class="activeTab === 'danfe' ? 'border-indigo-500 text-white' : 'border-transparent text-slate-500'" class="px-6 py-4 text-xs font-black uppercase italic tracking-widest border-b-2 transition-all">Personalização DANFE</button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-8 overflow-y-auto custom-scrollbar flex-1">
                <!-- Tab: Dados Básicos -->
                <div x-show="activeTab === 'basic'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 italic">Razão Social Completa</label>
                            <input type="text" x-model="form.razao_social" class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-5 py-3 text-white font-bold focus:border-indigo-500 outline-none transition-all italic shadow-inner">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 italic">Nome Fantasia</label>
                            <input type="text" x-model="form.nome" class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-5 py-3 text-white font-bold focus:border-indigo-500 outline-none transition-all italic shadow-inner">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 italic">Apelido Interno</label>
                            <input type="text" x-model="form.apelido" class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-5 py-3 text-white font-bold focus:border-indigo-500 outline-none transition-all italic shadow-inner">
                        </div>
                    </div>
                </div>

                <!-- Tab: Fiscal -->
                <div x-show="activeTab === 'fiscal'" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 italic">CNPJ / CPF</label>
                            <input type="text" x-model="form.cnpj" class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-5 py-3 text-white font-black tracking-widest focus:border-indigo-500 outline-none transition-all shadow-inner">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 italic">E-mail Contabilidade</label>
                            <input type="email" x-model="form.email_contabil" class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-5 py-3 text-white font-bold focus:border-indigo-500 outline-none transition-all shadow-inner">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 italic">Regime Tributário</label>
                            <select x-model="form.regime_tributario" class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-5 py-3 text-white font-black italic focus:border-indigo-500 outline-none shadow-inner uppercase text-xs">
                                <option value="simples_nacional">Simples Nacional</option>
                                <option value="lucro_presumido">Lucro Presumido</option>
                                <option value="lucro_real">Lucro Real</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 italic">% Alíquota Simples</label>
                                <input type="number" step="0.01" x-model="form.aliquota_simples" class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-5 py-3 text-emerald-400 font-black italic focus:border-indigo-500 outline-none shadow-inner">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 italic">% ICMS Padrão</label>
                                <input type="number" step="0.01" x-model="form.aliquota_icms" class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-5 py-3 text-indigo-400 font-black italic focus:border-indigo-500 outline-none shadow-inner">
                            </div>
                        </div>
                    </div>

                    <!-- Certificado Digital -->
                    <div class="p-6 bg-slate-900/50 border border-slate-700 rounded-[2rem] relative overflow-hidden group">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-xs font-black text-white italic uppercase tracking-tighter flex items-center gap-2">
                                <i class="fas fa-certificate text-amber-500"></i>
                                Certificado Digital A1 (.pfx)
                            </h4>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="form.auto_ciencia" class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-indigo-600 focus:ring-0">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest italic">Auto-Manifestar Ciência</span>
                            </label>
                        </div>
                        
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <label class="block w-full cursor-pointer bg-slate-800 border-2 border-dashed border-slate-700 hover:border-indigo-500/50 px-6 py-8 rounded-2xl text-center transition-all group-inner">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-slate-600 group-inner-hover:text-indigo-500 mb-2"></i>
                                    <p class="text-[10px] font-black text-slate-500 uppercase italic">Clique para fazer upload do novo certificado</p>
                                    <input type="file" @change="handleCertUpload" class="hidden">
                                </label>
                            </div>
                            <div class="w-full md:w-64 space-y-4">
                                <div>
                                    <label class="block text-[9px] font-black text-slate-600 uppercase tracking-widest mb-1 italic">Senha do Certificado</label>
                                    <input type="password" x-model="form.certificado_senha" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2 text-white font-bold outline-none focus:border-indigo-500">
                                </div>
                                <button type="button" @click="testCertificate()" class="w-full py-2.5 bg-slate-700 hover:bg-emerald-600 text-white font-black italic uppercase text-[10px] tracking-widest rounded-xl transition-all shadow-lg">
                                    Validar Certificado
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: DANFE -->
                <div x-show="activeTab === 'danfe'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-1">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4 italic">Logo do DANFE</label>
                            <label class="relative w-full aspect-square rounded-[2rem] bg-slate-900 border-2 border-dashed border-slate-700 flex flex-col items-center justify-center cursor-pointer hover:border-indigo-500 transition-all overflow-hidden group">
                                <img x-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain p-4">
                                <div x-else class="text-center p-4">
                                    <i class="fas fa-image text-3xl text-slate-700 mb-2"></i>
                                    <p class="text-[9px] font-black text-slate-600 uppercase tracking-tight">Upload JPG/PNG</p>
                                </div>
                                <input type="file" @change="handleLogoUpload" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer">
                            </label>
                        </div>
                        <div class="md:col-span-2 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <template x-for="(val, key) in danfeConfigs" :key="key">
                                    <div class="flex items-center justify-between p-4 bg-slate-900/50 border border-slate-700 rounded-2xl">
                                        <span class="text-[10px] font-black text-slate-300 uppercase italic" x-text="key.replace('danfe_show_', '').replace('_', ' ')"></span>
                                        <button @click="form[key] = !form[key]" class="w-10 h-6 rounded-full relative transition-all" :class="form[key] ? 'bg-indigo-600' : 'bg-slate-700'">
                                            <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform" :class="form[key] ? 'translate-x-4' : ''"></div>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 italic">Rodapé Personalizado</label>
                                <textarea x-model="form.danfe_rodape" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-2xl p-4 text-white font-medium italic focus:border-indigo-500 outline-none shadow-inner"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-8 border-t border-slate-700 bg-slate-900/50 flex justify-end gap-4">
                <button @click="showModal = false" class="px-8 py-3 text-slate-500 font-black italic uppercase text-xs tracking-widest hover:text-white transition-colors">Cancelar</button>
                <button @click="save()" class="bg-indigo-600 hover:bg-indigo-500 text-white px-12 py-3 rounded-2xl font-black shadow-lg shadow-indigo-600/20 transition-all italic uppercase text-sm tracking-tighter">
                    <i class="fas fa-save mr-2 text-xs"></i>
                    Finalizar Configuração
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function empresasPage() {
    return {
        empresas: [],
        loading: false,
        showModal: false,
        isEditing: false,
        activeTab: 'basic',
        message: '',
        logoPreview: null,
        danfeConfigs: {
            'danfe_show_logo': true,
            'danfe_show_itens': true,
            'danfe_show_valor_itens': true,
            'danfe_show_valor_total': true,
            'danfe_show_qrcode': true
        },
        form: {
            id: null, nome: '', razao_social: '', cnpj: '', email_contabil: '',
            regime_tributario: 'simples_nacional', aliquota_simples: 0, aliquota_icms: 0,
            auto_ciencia: false, certificado_senha: '', danfe_rodape: '',
            danfe_show_logo: true, danfe_show_itens: true, danfe_show_valor_itens: true,
            danfe_show_valor_total: true, danfe_show_qrcode: true
        },
        
        async init() {
            await this.loadEmpresas();
        },
        
        async loadEmpresas() {
            this.loading = true;
            try {
                const response = await fetch('/api/admin/empresas');
                if (response.ok) this.empresas = await response.json();
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        
        openCreate() {
            this.isEditing = false;
            this.activeTab = 'basic';
            this.resetForm();
            this.showModal = true;
        },
        
        editEmpresa(empresa) {
            this.isEditing = true;
            this.activeTab = 'basic';
            this.form = { ...empresa };
            this.logoPreview = empresa.logo_path ? '/storage/' + empresa.logo_path : null;
            this.showModal = true;
        },
        
        resetForm() {
            this.form = {
                id: null, nome: '', razao_social: '', cnpj: '', email_contabil: '',
                regime_tributario: 'simples_nacional', aliquota_simples: 0, aliquota_icms: 0,
                auto_ciencia: false, certificado_senha: '', danfe_rodape: '',
                danfe_show_logo: true, danfe_show_itens: true, danfe_show_valor_itens: true,
                danfe_show_valor_total: true, danfe_show_qrcode: true
            };
            this.logoPreview = null;
        },

        handleLogoUpload(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (f) => this.logoPreview = f.target.result;
            reader.readAsDataURL(file);
        },

        handleCertUpload(e) {
            alert('Upload de certificado via API em desenvolvimento.');
        },
        
        async testCertificate() {
            alert('Enviando requisição de validação para Engine Sefaz...');
        },

        async save() {
            this.loading = true;
            const method = this.isEditing ? 'PUT' : 'POST';
            const url = this.isEditing ? `/api/admin/empresas/${this.form.id}` : '/api/admin/empresas';
            
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.form)
                });
                
                if (response.ok) {
                    this.showModal = false;
                    this.message = 'UNIDADE ATUALIZADA COM SUCESSO! ⚡';
                    setTimeout(() => this.message = '', 3000);
                    await this.loadEmpresas();
                }
            } catch (e) { console.error(e); }
            this.loading = false;
        },

        async deleteEmpresa(id) {
            if(!confirm('Deseja realmente REMOVER esta unidade? Todos os dados vinculados serão inacessíveis.')) return;
            try {
                const response = await fetch(`/api/admin/empresas/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (response.ok) await this.loadEmpresas();
            } catch (e) { console.error(e); }
        },
        
        formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
        }
    }
}
</script>
@endsection
