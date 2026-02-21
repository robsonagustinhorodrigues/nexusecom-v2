@extends('layouts.alpine')

@section('title', 'Configurações - NexusEcom')
@section('header_title', 'Configurações')

@section('content')
<div x-data="configPage()" x-init="init()">
    <!-- Header Actions -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-700 to-slate-600 flex items-center justify-center shadow-lg shadow-slate-900/20">
                <i class="fas fa-cog text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Configurações do Grupo</h2>
                <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Preferências globais e configurações do sistema</p>
                <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Parâmetros de Sistema & Performance</p>
            </div>
        </div>

        <button @click="save()" class="px-6 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2 italic uppercase tracking-wider group">
            <i class="fas fa-save text-xs group-hover:scale-110 transition-transform"></i>
            Salvar Alterações
        </button>
    </div>

    <!-- Feedback Message -->
    <template x-if="message">
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-2xl font-black text-sm flex items-center gap-2 mb-6 italic animate-bounce">
            <i class="fas fa-check-circle"></i>
            <span x-text="message"></span>
        </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Configurações SEFAZ -->
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl hover:border-indigo-500/30 transition-all group">
            <h3 class="text-lg font-black text-white flex items-center gap-2 mb-6 italic uppercase tracking-tight">
                <i class="fas fa-server text-indigo-500 group-hover:rotate-12 transition-transform"></i>
                Engine SEFAZ
            </h3>

            <div class="space-y-6">
                <!-- Busca Automática -->
                <div class="flex items-center justify-between p-4 bg-slate-900/50 border border-slate-700 rounded-2xl hover:border-slate-600 transition-colors">
                    <div>
                        <span class="text-sm font-black text-white italic uppercase tracking-wider">Busca Automática NF-e</span>
                        <p class="text-[10px] text-slate-500 font-bold uppercase mt-1">Sincronização em Background 24/7</p>
                    </div>
                    <button type="button" @click="form.sefaz_auto_busca = !form.sefaz_auto_busca" 
                            class="w-12 h-7 rounded-full relative transition-all duration-300"
                            :class="form.sefaz_auto_busca ? 'bg-indigo-600' : 'bg-slate-700'">
                        <div class="absolute top-1 left-1 w-5 h-5 rounded-full bg-white transition-all transform"
                             :class="form.sefaz_auto_busca ? 'translate-x-5' : ''"></div>
                    </button>
                </div>

                <!-- Intervalo -->
                <div class="p-4 bg-slate-900/50 border border-slate-700 rounded-2xl">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">
                        Intervalo de Pooling
                    </label>
                    <div class="flex items-center gap-4">
                        <input 
                            type="range" 
                            x-model="form.sefaz_intervalo_minutos" 
                            min="60" 
                            max="1440" 
                            step="60"
                            class="flex-1 h-1.5 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-indigo-500"
                        >
                        <div class="w-16 h-10 bg-indigo-600/10 border border-indigo-500/20 rounded-xl flex items-center justify-center">
                            <span class="text-sm font-black text-indigo-400 italic" x-text="Math.floor(form.sefaz_intervalo_minutos / 60) + 'H'"></span>
                        </div>
                    </div>
                </div>

                <!-- Horário -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-900/50 border border-slate-700 rounded-2xl">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Janela Start</label>
                        <input type="time" x-model="form.sefaz_hora_inicio" class="w-full bg-transparent border-none text-white font-black italic text-lg focus:ring-0">
                    </div>
                    <div class="p-4 bg-slate-900/50 border border-slate-700 rounded-2xl">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Janela End</label>
                        <input type="time" x-model="form.sefaz_hora_fim" class="w-full bg-transparent border-none text-white font-black italic text-lg focus:ring-0">
                    </div>
                </div>
            </div>
        </div>

        <!-- Configurações NF-e -->
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl hover:border-emerald-500/30 transition-all group">
            <h3 class="text-lg font-black text-white flex items-center gap-2 mb-6 italic uppercase tracking-tight">
                <i class="fas fa-file-invoice-dollar text-emerald-500 group-hover:scale-110 transition-transform"></i>
                Automação Fiscal
            </h3>

            <div class="space-y-6">
                <!-- Auto Manifestar -->
                <div class="flex items-center justify-between p-4 bg-slate-900/50 border border-slate-700 rounded-2xl hover:border-slate-600 transition-colors">
                    <div>
                        <span class="text-sm font-black text-white italic uppercase tracking-wider">Manifestação Automática</span>
                        <p class="text-[10px] text-slate-500 font-bold uppercase mt-1">Ciência do Destinatário Instantânea</p>
                    </div>
                    <button type="button" @click="form.nfe_auto_manifestar = !form.nfe_auto_manifestar" 
                            class="w-12 h-7 rounded-full relative transition-all duration-300"
                            :class="form.nfe_auto_manifestar ? 'bg-emerald-600' : 'bg-slate-700'">
                        <div class="absolute top-1 left-1 w-5 h-5 rounded-full bg-white transition-all transform"
                             :class="form.nfe_auto_manifestar ? 'translate-x-5' : ''"></div>
                    </button>
                </div>

                <!-- Dias Retroativos -->
                <div class="p-4 bg-slate-900/50 border border-slate-700 rounded-2xl">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 italic">Histórico de Importação (Dias)</label>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-calendar-alt text-slate-600"></i>
                        <input 
                            type="number" 
                            x-model="form.nfe_dias_retroativos" 
                            min="1" 
                            max="30"
                            class="w-full bg-transparent border-none text-white font-black italic text-2xl focus:ring-0"
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- Observações -->
        <div class="lg:col-span-2 bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl group">
            <h3 class="text-lg font-black text-white flex items-center gap-2 mb-4 italic uppercase tracking-tight">
                <i class="fas fa-sticky-note text-amber-500 group-hover:-rotate-6 transition-transform"></i>
                Logs & Notas Internas
            </h3>
            
            <textarea 
                x-model="form.observacoes" 
                rows="3" 
                class="w-full bg-slate-900/50 border border-slate-700 rounded-2xl p-4 text-white font-medium focus:border-indigo-500 outline-none transition-all placeholder-slate-600 italic"
                placeholder="Insira notas de auditoria ou lembretes do grupo..."
            ></textarea>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function configPage() {
    return {
        form: {
            sefaz_intervalo_minutos: 360,
            sefaz_auto_busca: true,
            sefaz_hora_inicio: '08:00',
            sefaz_hora_fim: '20:00',
            nfe_auto_manifestar: false,
            nfe_dias_retroativos: 5,
            observacoes: ''
        },
        loading: false,
        message: '',
        
        async init() {
            await this.loadConfig();
        },
        
        async loadConfig() {
            this.loading = true;
            try {
                const response = await fetch('/api/admin/configuracoes');
                if (response.ok) {
                    this.form = await response.json();
                }
            } catch (e) {
                console.error('Erro ao carregar configs:', e);
            }
            this.loading = false;
        },
        
        async save() {
            this.loading = true;
            try {
                const response = await fetch('/api/admin/configuracoes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.form)
                });
                
                if (response.ok) {
                    this.message = 'CONFIGURAÇÕES ATUALIZADAS COM SUCESSO! ⚡';
                    setTimeout(() => this.message = '', 3000);
                }
            } catch (e) {
                console.error('Erro ao salvar:', e);
            }
            this.loading = false;
        }
    }
}
</script>
@endsection
