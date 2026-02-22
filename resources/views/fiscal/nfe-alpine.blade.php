@extends('layouts.alpine')

@section('title', 'Hub Fiscal NF-e - NexusEcom')
@section('header_title', 'Hub Fiscal')

@section('content')
<div x-data="fiscalPage()" x-init="init()">
    <!-- Header com Filtros e Ações -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Monitor de Notas Fiscais</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Consulte até 60 dias retroativos • Integração SEFAZ & Gestão de XML</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 bg-slate-800 p-2 rounded-2xl border border-slate-700 shadow-lg">
            <div class="flex items-center gap-2 px-3 border-r border-slate-700">
                <input type="date" x-model="dataInicio" @change="currentPage = 1; loadNfe()" class="bg-slate-900 border-none text-white font-black italic text-xs uppercase rounded-xl px-3 py-2 focus:ring-indigo-500 outline-none">
                <span class="text-slate-600 font-black italic">A</span>
                <input type="date" x-model="dataFim" @change="currentPage = 1; loadNfe()" class="bg-slate-900 border-none text-white font-black italic text-xs uppercase rounded-xl px-3 py-2 focus:ring-indigo-500 outline-none">
            </div>
            
            <select x-model="view" @change="filtroSituacao = ''; currentPage = 1; loadNfe()" class="bg-slate-900 border-none text-white font-black italic text-xs uppercase rounded-xl px-4 py-2 focus:ring-indigo-500 outline-none">
                <option value="recebidas">📥 Recebidas</option>
                <option value="emitidas">📤 Emitidas</option>
            </select>

            <select x-model="filtroSituacao" @change="currentPage = 1; loadNfe()" class="bg-slate-900 border-none text-white font-black italic text-xs uppercase rounded-xl px-4 py-2 focus:ring-indigo-500 outline-none">
                <option value="">Todas as Situações</option>
                <option value="autorizada">Autorizada</option>
                <option value="cancelada">Cancelada</option>
                <option value="denegada">Denegada</option>
                <option value="inutilizada">Inutilizada</option>
            </select>

            <!-- Botão de Funções (Dropdown) -->
            <div class="relative" x-data="{ importMenu: false }">
                <button @click="importMenu = !importMenu" :disabled="importing" 
                        class="w-10 h-10 bg-slate-700 hover:bg-slate-600 border border-slate-600 text-white rounded-xl transition-all flex items-center justify-center shadow-lg"
                        title="Funções e Importações">
                    <i class="fas fa-ellipsis-v" :class="importing || loading ? 'animate-spin' : ''"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div x-show="importMenu" @click.away="importMenu = false" x-cloak
                     class="absolute right-0 mt-2 w-72 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden">
                    <div class="p-2">
                        <p class="text-[10px] font-black text-slate-500 uppercase px-3 py-2">Processamento</p>
                        
                        <button @click="importMenu = false; reprocessSelected()" :disabled="selected.length === 0"
                                class="w-full flex items-center gap-3 px-3 py-3 rounded-lg text-left hover:bg-slate-700/50 transition-colors disabled:opacity-30">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center">
                                <i class="fas fa-sync text-amber-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Reprocessar Associação</p>
                                <p class="text-[10px] text-slate-500" x-text="selected.length > 0 ? 'Reprocessar ' + selected.length + ' notas' : 'Selecione notas para reprocessar'"></p>
                            </div>
                        </button>

                        <div class="h-px bg-slate-700/50 my-1 mx-2"></div>
                        
                        <p class="text-[10px] font-black text-slate-500 uppercase px-3 py-2">Importação (Fila)</p>
                        
                        <button @click="importMenu = false; showImportMeli = true; meliDataInicio = dataInicio; meliDataFim = dataFim" 
                                class="w-full flex items-center gap-3 px-3 py-3 rounded-lg text-left hover:bg-slate-700/50 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-yellow-500/20 flex items-center justify-center">
                                <i class="fab fa-mercadolivre text-yellow-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Mercado Livre</p>
                                <p class="text-[10px] text-slate-500">Importar NF-es por data</p>
                            </div>
                        </button>

                        <button @click="importMenu = false; canSearchSefaz ? importFromSefaz() : null" 
                                :class="!canSearchSefaz ? 'opacity-30 cursor-not-allowed' : ''"
                                class="w-full flex items-center gap-3 px-3 py-3 rounded-lg text-left hover:bg-slate-700/50 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                <i class="fas fa-file-invoice-dollar text-blue-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Buscar NF-e SEFAZ</p>
                                <p class="text-[10px] text-slate-500" x-text="canSearchSefaz ? 'Consultar novos NSUs' : 'Aguarde: ' + sefazCountdown"></p>
                            </div>
                        </button>

                        <div class="h-px bg-slate-700/50 my-1 mx-2"></div>

                        <button @click="importMenu = false; showImportXml = true" 
                                class="w-full flex items-center gap-3 px-3 py-3 rounded-lg text-left hover:bg-slate-700/50 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                                <i class="fas fa-file-code text-emerald-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">XML / ZIP</p>
                                <p class="text-[10px] text-slate-500">Importar manual</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid de Métricas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl group hover:border-indigo-500/30 transition-all relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Status SEFAZ</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-xl font-black text-white italic" x-text="empresaStats.last_nsu || '0'">0</h3>
                    <span class="text-[10px] text-slate-500 font-bold uppercase italic">NSU</span>
                </div>
                <div class="mt-4 flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tight">Última Consulta:</span>
                    </div>
                    <span class="text-[10px] text-indigo-400 font-black italic" x-text="empresaStats.last_sefaz_at || 'Nunca consultado'"></span>
                </div>
            </div>
            <i class="fas fa-satellite-dish absolute -right-4 -bottom-4 text-6xl text-slate-700/20 group-hover:text-indigo-500/10 transition-colors"></i>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl group hover:border-emerald-500/30 transition-all">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Selecionadas</p>
            <h3 class="text-2xl font-black text-emerald-400 italic" x-text="formatMoney(totalSelecionadas)">R$ 0,00</h3>
            <div class="mt-4 flex items-center gap-2 text-white">
                <span class="px-2 py-0.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-[10px] font-black italic uppercase" x-text="selected.length + ' notas'"></span>
            </div>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl group hover:border-amber-500/30 transition-all">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Volume Financeiro</p>
            <h3 class="text-2xl font-black text-amber-400 italic" x-text="formatMoney(totalValue)">R$ 0,00</h3>
            <div class="mt-4 flex items-center gap-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Notas Listadas</span>
            </div>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl group hover:border-rose-500/30 transition-all">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Canceladas</p>
            <h3 class="text-2xl font-black text-rose-400 italic" x-text="canceladasCount">0</h3>
            <div class="mt-4 flex items-center gap-2 text-white">
                <span class="px-2 py-0.5 rounded-lg bg-rose-500/10 border border-rose-500/20 text-[10px] font-black italic uppercase">Notas Inválidas</span>
            </div>
        </div>
    </div>

    <!-- Tabela de Notas -->
    <div class="bg-slate-800 border border-slate-700 rounded-3xl overflow-hidden shadow-2xl">
        <div class="p-4 border-b border-slate-700 bg-slate-900/50">
            <!-- Header Compacto -->
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button @click="filtersOpen = !filtersOpen" class="flex items-center gap-2 text-slate-400 hover:text-white transition">
                        <i class="fas" :class="filtersOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        <h3 class="font-black text-white italic uppercase tracking-tighter" x-text="'Listagem de Notas ' + (view === 'recebidas' ? 'Recebidas' : 'Emitidas')"></h3>
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative w-48">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                        <input type="text" x-model="search" @input.debounce.300ms="currentPage = 1; loadNfe()" placeholder="Buscar..." 
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-4 py-2 text-xs font-bold italic text-white focus:border-indigo-500 outline-none">
                    </div>
                </div>
            </div>
            
            <!-- Filtros Colapsáveis -->
            <div x-show="filtersOpen" x-collapse class="mt-4 pt-4 border-t border-slate-700/50">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black text-slate-500 uppercase italic">Data:</span>
                        <input type="date" x-model="dataInicio" @change="currentPage = 1; loadNfe()" 
                               class="bg-slate-900 border-none text-white font-black italic text-xs uppercase rounded-lg px-2 py-1">
                        <span class="text-slate-600">até</span>
                        <input type="date" x-model="dataFim" @change="currentPage = 1; loadNfe()" 
                               class="bg-slate-900 border-none text-white font-black italic text-xs uppercase rounded-lg px-2 py-1">
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black text-slate-500 uppercase italic">Associação:</span>
                        <select x-model="associationFilter" @change="currentPage = 1; loadNfe()" 
                                class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-1 text-xs font-bold italic text-white focus:border-indigo-500 outline-none">
                            <option value="">Todas</option>
                            <option value="pending">Pendentes</option>
                            <option value="partial">Parciais</option>
                            <option value="associated">Associadas</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-900/30">
                        <th class="px-2 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-center">
                            <input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded bg-slate-700 border-slate-600 text-indigo-500">
                        </th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-left">Número / Data</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-left">Emitente / Destinatário</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-right">Valor Total</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-center">Associação</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <template x-for="n in nfe" :key="n.id">
                        <tr class="group hover:bg-slate-700/20 transition-colors">
                            <td class="px-2 py-4 text-center">
                                <input type="checkbox" :value="n.id" x-model="selected" class="rounded bg-slate-700 border-slate-600 text-indigo-500">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-white italic uppercase tracking-tight" x-text="n.numero"></span>
                                    <span class="text-[10px] text-slate-500 font-bold" x-text="formatDate(n.data_emissao)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-300 italic uppercase" x-text="n.emitente_nome || n.cliente_nome || '-'"></span>
                                    <span class="text-[10px] text-slate-500 font-mono" x-text="n.emitente_cnpj || n.cliente_cnpj || '-'"></span>
                                    <span class="text-[9px] text-slate-600 font-mono" x-text="n.chave"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-black text-white italic text-sm" x-text="formatMoney(n.valor_total)"></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase italic" 
                                      :class="getStatusClass(n.status_nfe)" x-text="n.status_nfe"></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 rounded-full text-[9px] font-black uppercase italic" 
                                      :class="getAssociationClass(n.association_status)" 
                                      x-text="getAssociationLabel(n.association_status)"></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a :href="'/nfe/danfe/' + n.id + '/' + (view === 'recebidas' ? 'recebida' : 'emitida')" target="_blank" 
                                       class="w-8 h-8 rounded-lg bg-indigo-600/10 text-indigo-400 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="DANFE A4">
                                        <i class="fas fa-print text-xs"></i>
                                    </a>
                                    <a :href="'/nfe/danfe-simplificada/' + n.id + '/' + (view === 'recebidas' ? 'recebida' : 'emitida')" target="_blank" 
                                       class="w-8 h-8 rounded-lg bg-blue-600/10 text-blue-400 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="DANFE Simplificada">
                                        <i class="fas fa-receipt text-xs"></i>
                                    </a>
                                    <a :href="'/nfe/download-xml/' + n.id + '/' + (view === 'recebidas' ? 'recebida' : 'emitida')" 
                                       class="w-8 h-8 rounded-lg bg-amber-600/10 text-amber-400 flex items-center justify-center hover:bg-amber-600 hover:text-white transition-all shadow-sm" title="Baixar XML">
                                        <i class="fas fa-file-code text-xs"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div x-show="!loading && nfe.length === 0" class="p-20 text-center text-slate-600 italic">
                <i class="fas fa-file-invoice text-4xl mb-4 opacity-20"></i>
                <p class="text-sm font-black uppercase tracking-widest">Nenhuma nota encontrada no período</p>
            </div>

            <div x-show="loading" class="p-20 text-center text-indigo-500">
                <i class="fas fa-circle-notch fa-spin text-4xl mb-4"></i>
                <p class="text-sm font-black uppercase tracking-widest animate-pulse">Consultando Database...</p>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && total > 0" class="flex items-center justify-between mt-4 bg-slate-800 rounded-xl border border-slate-700 p-4">
        <div class="text-sm text-slate-400">
            Mostrando <span class="text-white font-bold" x-text="from"></span> - <span class="text-white font-bold" x-text="to"></span> de <span class="text-white font-bold" x-text="total"></span> notas
        </div>
        <div class="flex items-center gap-2">
            <button @click="changePage(currentPage - 1)" :disabled="currentPage <= 1" class="px-3 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span class="text-sm text-slate-400">Página <span class="text-white font-bold" x-text="currentPage"></span> de <span class="text-white font-bold" x-text="lastPage"></span></span>
            <button @click="changePage(currentPage + 1)" :disabled="currentPage >= lastPage" class="px-3 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function fiscalPage() {
    return {
        view: 'recebidas',
        filtroSituacao: '',
        filtersOpen: false,
        associationFilter: '',
        dataInicio: '',
        dataFim: '',
        search: '',
        showImportMeli: false,
        showImportBling: false,
        showImportXml: false,
        showImportZip: false,
        importLoading: false,
        importResult: null,
        meliDataInicio: '',
        meliDataFim: '',
        blingDataInicio: '',
        blingDataFim: '',
        sefazCountdown: '',
        canSearchSefaz: true,
        empresaStats: {
            last_nsu: 0,
            last_sefaz_at: null
        },
        nfe: [],
        selected: [],
        loading: false,
        importing: false,
        currentPage: 1,
        lastPage: 1,
        total: 0,
        from: 0,
        to: 0,
        
        get totalSelecionadas() {
            return this.nfe.filter(n => this.selected.includes(n.id)).reduce((sum, n) => sum + parseFloat(n.valor_total || 0), 0);
        },
        
        toggleAll(checked) {
            if (checked) {
                this.selected = this.nfe.map(n => n.id);
            } else {
                this.selected = [];
            }
        },
        
        changePage(page) {
            if (page >= 1 && page <= this.lastPage) {
                this.currentPage = page;
                this.loadNfe();
            }
        },
        
        async init() {
            // Iniciar com data inicial -60 dias e data final hoje (dados históricos)
            const hoje = new Date();
            const sessentaDiasAtras = new Date();
            sessentaDiasAtras.setDate(hoje.getDate() - 60);
            
            this.dataFim = hoje.toISOString().split('T')[0];
            this.dataInicio = sessentaDiasAtras.toISOString().split('T')[0];
            
            await this.loadNfe();
            
            // Ouvir mudança de empresa no layout
            window.addEventListener('empresa-changed', () => this.loadNfe());
        },
        
        async loadNfe() {
            this.loading = true;
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const params = new URLSearchParams({
                    empresa: empresaId,
                    tipo: this.view,
                    situacao: this.filtroSituacao,
                    data_inicio: this.dataInicio,
                    data_fim: this.dataFim,
                    search: this.search,
                    page: this.currentPage
                });
                
                const response = await fetch(`/api/nfes?${params}`);
                if (response.ok) {
                    const data = await response.json();
                    this.nfe = data.data || data;
                    this.currentPage = data.current_page || 1;
                    this.lastPage = data.last_page || 1;
                    this.total = data.total || 0;
                    this.from = data.from || 0;
                    this.to = data.to || 0;
                    
                    // Atualizar stats da empresa se vierem na resposta ou buscar separado
                    if (data.empresa_stats) {
                        this.empresaStats = data.empresa_stats;
                        this.updateSefazTimer();
                    } else {
                        this.loadEmpresaStats();
                    }
                }
            } catch (e) {
                console.error('Erro ao carregar NF-e:', e);
            }
            this.loading = false;
        },

        updateSefazTimer() {
            if (!this.empresaStats.last_sefaz_at || this.empresaStats.last_sefaz_at === 'Nunca') {
                this.canSearchSefaz = true;
                return;
            }

            // Converter data string (d/m/Y H:i:s) para Date
            const [datePart, timePart] = this.empresaStats.last_sefaz_at.split(' ');
            const [d, m, y] = datePart.split('/');
            const [h, i, s] = timePart.split(':');
            const lastSefaz = new Date(y, m - 1, d, h, i, s);
            
            const now = new Date();
            const diffMs = now - lastSefaz;
            const hourInMs = 60 * 60 * 1000;

            if (diffMs < hourInMs) {
                this.canSearchSefaz = false;
                const remainingMs = hourInMs - diffMs;
                const minutes = Math.floor(remainingMs / 60000);
                const seconds = Math.floor((remainingMs % 60000) / 1000);
                this.sefazCountdown = `${minutes}m ${seconds}s`;
                
                // Atualizar a cada segundo
                setTimeout(() => this.updateSefazTimer(), 1000);
            } else {
                this.canSearchSefaz = true;
                this.sefazCountdown = '';
            }
        },

        async loadEmpresaStats() {
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch(`/api/admin/empresas/${empresaId}`);
                if (response.ok) {
                    const empresa = await response.json();
                    this.empresaStats = {
                        last_nsu: empresa.last_nsu || 0,
                        last_sefaz_at: empresa.updated_at ? this.formatDateTime(empresa.updated_at) : 'Nunca'
                    };
                    this.updateSefazTimer();
                }
            } catch (e) {}
        },
        
        async importNfe() {
            this.importing = true;
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch(`/api/nfes/import?empresa=${empresaId}`, {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    await this.loadNfe();
                    alert('Sincronização com SEFAZ concluída! ⚡');
                } else {
                    const error = await response.json();
                    alert('Erro na sincronização: ' + (error.message || 'Erro desconhecido'));
                }
            } catch (e) {
                console.error('Erro na importação:', e);
                alert('Erro crítico na conexão com a API.');
            }
            this.importing = false;
        },
        
        async importFromSefaz() {
            // Existing SEFAZ import
            this.importing = true;
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch(`/api/nfes/import?empresa=${empresaId}`, {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const result = await response.json();
                    await this.loadNfe();
                    
                    // Alert estilizado usando SweetAlert2 (se disponível)
                    if (window.Swal) {
                        Swal.fire({
                            title: 'Busca SEFAZ Concluída! ⚡',
                            html: `
                                <div class="text-left space-y-2 text-slate-300 py-2">
                                    <div class="flex justify-between items-center bg-slate-900/50 p-2 rounded-lg mb-1">
                                        <span class="text-xs font-bold uppercase text-slate-500">NSU Inicial:</span> 
                                        <span class="font-mono font-bold text-white">${result.nsu_inicial}</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-slate-900/50 p-2 rounded-lg mb-3">
                                        <span class="text-xs font-bold uppercase text-slate-500">NSU Final:</span> 
                                        <span class="font-mono font-bold text-indigo-400">${result.nsu_final}</span>
                                    </div>
                                    <div class="h-px bg-slate-700 my-4"></div>
                                    <div class="flex justify-between items-center p-2">
                                        <span class="font-black uppercase italic text-slate-400">Notas Recebidas:</span> 
                                        <span class="text-2xl font-black text-emerald-400 italic">${result.qtd_notas}</span>
                                    </div>
                                </div>
                            `,
                            icon: 'success',
                            background: '#1e293b',
                            color: '#fff',
                            confirmButtonColor: '#4f46e5',
                            confirmButtonText: 'ENTENDIDO',
                            customClass: {
                                popup: 'rounded-3xl border border-slate-700 shadow-2xl',
                                title: 'italic font-black uppercase text-xl tracking-tighter'
                            }
                        });
                    } else {
                        alert(`Busca SEFAZ Concluída!\n\nNSU: ${result.nsu_inicial} -> ${result.nsu_final}\nNotas: ${result.qtd_notas}`);
                    }
                } else {
                    const error = await response.json();
                    alert('Erro na sincronização: ' + (error.message || 'Erro desconhecido'));
                }
            } catch (e) {
                console.error('Erro na importação:', e);
                alert('Erro crítico na conexão com a API.');
            }
            this.importing = false;
        },
        
        async importFromMeli() {
            if (!this.meliDataInicio || !this.meliDataFim) {
                if (window.Swal) {
                    Swal.fire({
                        title: 'Atenção!',
                        text: 'Selecione as datas inicial e final para continuar.',
                        icon: 'warning',
                        background: '#1e293b',
                        color: '#fff',
                        confirmButtonColor: '#4f46e5'
                    });
                } else {
                    alert('Selecione as datas inicial e final');
                }
                return;
            }
            
            this.importing = true;
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch('/api/nfes/import-meli', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        empresa_id: empresaId,
                        data_inicio: this.meliDataInicio,
                        data_fim: this.meliDataFim
                    })
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    this.showImportMeli = false;
                    
                    if (window.Swal) {
                        Swal.fire({
                            title: 'Importação Iniciada! 🚀',
                            text: 'As notas do Mercado Livre estão sendo processadas em background. Acompanhe na aba de tarefas.',
                            icon: 'info',
                            background: '#1e293b',
                            color: '#fff',
                            confirmButtonColor: '#4f46e5',
                            confirmButtonText: 'VER TAREFAS',
                            showCancelButton: true,
                            cancelButtonText: 'FECHAR',
                            customClass: {
                                popup: 'rounded-3xl border border-slate-700 shadow-2xl',
                                title: 'italic font-black uppercase tracking-tighter'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '/admin/tarefas';
                            }
                        });
                    } else {
                        alert(`Importação iniciada!\nNotas serão processadas em background.`);
                        window.location.href = '/admin/tarefas';
                    }
                } else {
                    if (window.Swal) {
                        Swal.fire({
                            title: 'Erro!',
                            text: result.message || 'Erro desconhecido ao importar.',
                            icon: 'error',
                            background: '#1e293b',
                            color: '#fff'
                        });
                    } else {
                        alert('Erro: ' + (result.message || 'Erro desconhecido'));
                    }
                }
            } catch (e) {
                console.error('Erro:', e);
                alert('Erro na conexão.');
            }
            this.importing = false;
        },
        
        async importXml() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.xml,.zip';
            input.onchange = async (e) => {
                const file = e.target.files[0];
                if (!file) return;
                
                this.importing = true;
                const formData = new FormData();
                formData.append('file', file);
                
                try {
                    const response = await fetch('/api/nfes/import-xml', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: formData
                    });
                    
                    const result = await response.json();
                    if (response.ok) {
                        alert(`Importado: ${result.imported || 0} notas`);
                        await this.loadNfe();
                    } else {
                        alert('Erro: ' + (result.message || 'Erro'));
                    }
                } catch (e) {
                    console.error(e);
                    alert('Erro na conexão');
                }
                this.importing = false;
            };
            input.click();
        },
        
        async importFromBling() {
            if (!this.blingDataInicio || !this.blingDataFim) {
                alert('Selecione as datas inicial e final');
                return;
            }
            
            this.importing = true;
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch('/api/nfes/import-bling', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        empresa_id: empresaId,
                        data_inicio: this.blingDataInicio,
                        data_fim: this.blingDataFim
                    })
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    this.showImportBling = false;
                    
                    if (window.Swal) {
                        Swal.fire({
                            title: 'Importação do Bling! 📦',
                            text: 'As notas estão sendo importadas em background. Acompanhe na aba de tarefas.',
                            icon: 'success',
                            background: '#1e293b',
                            color: '#fff',
                            confirmButtonColor: '#f97316',
                            confirmButtonText: 'VER TAREFAS',
                            showCancelButton: true,
                            cancelButtonText: 'FECHAR',
                            customClass: {
                                popup: 'rounded-3xl border border-slate-700 shadow-2xl',
                                title: 'italic font-black uppercase tracking-tighter'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '/admin/tarefas';
                            }
                        });
                    } else {
                        alert(`Importação do Bling iniciada!\nAs notas serão processadas em background.`);
                        window.location.href = '/admin/tarefas';
                    }
                } else {
                    alert('Erro: ' + (result.message || 'Erro desconhecido'));
                }
            } catch (e) {
                console.error('Erro:', e);
                alert('Erro na conexão.');
            }
            this.importing = false;
        },
        
        get totalValue() {
            return this.nfe.reduce((sum, n) => sum + parseFloat(n.valor_total || 0), 0);
        },
        
        get totalIcms() {
            return this.nfe.reduce((sum, n) => sum + parseFloat(n.icms_valor || 0), 0);
        },
        
        get canceladasCount() {
            return this.nfe.filter(n => n.status_nfe?.toLowerCase() === 'cancelada').length;
        },
        
        getStatusClass(status) {
            const s = status?.toLowerCase();
            if (s === 'autorizada') return 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400';
            if (s === 'cancelada') return 'bg-rose-500/10 border border-rose-500/20 text-rose-400';
            return 'bg-amber-500/10 border border-amber-500/20 text-amber-400';
        },
        
        getAssociationClass(status) {
            if (status === 'associated') return 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400';
            if (status === 'partial') return 'bg-amber-500/10 border border-amber-500/20 text-amber-400';
            return 'bg-slate-500/10 border border-slate-500/20 text-slate-400';
        },
        
        getAssociationLabel(status) {
            if (status === 'associated') return 'Associada';
            if (status === 'partial') return 'Parcial';
            return 'Pendente';
        },
        
        async reprocessSelected() {
            if (this.selected.length === 0) return;
            
            if (!confirm(`Reprocessar ${this.selected.length} notas?`)) return;
            
            this.loading = true;
            try {
                const response = await fetch('/api/nfe/reprocess-association', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        ids: this.selected,
                        type: this.view === 'recebidas' ? 'recebida' : 'emitida'
                    })
                });
                
                if (response.ok) {
                    this.selected = [];
                    await this.loadNfe();
                    alert('Associação reprocessada com sucesso!');
                }
            } catch (e) {
                console.error(e);
                alert('Erro ao reprocessar');
            }
            this.loading = false;
        },
        
        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('pt-BR');
        },

        formatDateTime(date) {
            if (!date) return '-';
            return new Date(date).toLocaleString('pt-BR');
        },
        
        formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
        },
        
        // Import functions
        async importMeli() {
            if (!this.meliDataInicio || !this.meliDataFim) {
                alert('Selecione as datas inicial e final');
                return;
            }
            
            this.importLoading = true;
            try {
                const response = await fetch('/api/nfe/import/meli', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        data_inicio: this.meliDataInicio,
                        data_fim: this.meliDataFim
                    })
                });
                
                const data = await response.json();
                this.importResult = data;
                if (data.success) {
                    alert('Importação iniciada! As notas serão processadas em background.');
                    this.showImportMeli = false;
                    await this.loadNfe();
                } else {
                    alert('Erro: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (e) {
                console.error(e);
                alert('Erro ao iniciar importação');
            }
            this.importLoading = false;
        },
        
        async importXml(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            this.importLoading = true;
            const formData = new FormData();
            formData.append('xml', file);
            
            try {
                const response = await fetch('/api/nfe/import/xml', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('XML importado com sucesso!');
                    this.showImportXml = false;
                    await this.loadNfe();
                } else {
                    alert('Erro: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (e) {
                console.error(e);
                alert('Erro ao importar XML');
            }
            this.importLoading = false;
        },
        
        async importZip(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            this.importLoading = true;
            const formData = new FormData();
            formData.append('zip', file);
            
            try {
                const response = await fetch('/api/nfe/import/zip', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('ZIP importado! As notas serão processadas em background.');
                    this.showImportZip = false;
                    await this.loadNfe();
                } else {
                    alert('Erro: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (e) {
                console.error(e);
                alert('Erro ao importar ZIP');
            }
            this.importLoading = false;
        }
    }
}
</script>

<!-- Import Modals -->
<!-- Meli Import Modal -->
<div x-show="showImportMeli" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 w-full max-w-md" @click.stop>
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/20 flex items-center justify-center">
                    <i class="fab fa-mercadolivre text-rose-400"></i>
                </div>
                <h3 class="font-bold text-white uppercase italic">Importar Mercado Livre</h3>
            </div>
            <button @click="showImportMeli = false" class="text-slate-400 hover:text-white transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase italic mb-2">Data Inicial</label>
                <input type="date" x-model="meliDataInicio" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-black italic focus:ring-rose-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase italic mb-2">Data Final</label>
                <input type="date" x-model="meliDataFim" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-black italic focus:ring-rose-500 outline-none transition-all">
            </div>
            
            <p class="text-[10px] text-slate-500 font-bold italic underline decoration-rose-500/30">As notas serão processadas em background. Acompanhe na aba de Tarefas.</p>
            
            <button @click="importFromMeli()" :disabled="importing"
                    class="w-full py-4 bg-rose-600 hover:bg-rose-500 text-white font-black italic uppercase rounded-xl flex items-center justify-center gap-2 disabled:opacity-50 transition-all shadow-lg shadow-rose-900/20">
                <i x-show="importing" class="fas fa-spinner fa-spin"></i>
                <span x-show="!importing"><i class="fas fa-download mr-2"></i> Iniciar Importação</span>
            </button>
        </div>
    </div>
</div>

<!-- Bling Import Modal -->
<div x-show="showImportBling" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 w-full max-w-md" @click.stop>
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-orange-500/20 flex items-center justify-center">
                    <i class="fas fa-box text-orange-400"></i>
                </div>
                <h3 class="font-bold text-white uppercase italic">Importar do Bling</h3>
            </div>
            <button @click="showImportBling = false" class="text-slate-400 hover:text-white transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase italic mb-2">Data Inicial</label>
                <input type="date" x-model="blingDataInicio" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-black italic focus:ring-orange-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase italic mb-2">Data Final</label>
                <input type="date" x-model="blingDataFim" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-black italic focus:ring-orange-500 outline-none transition-all">
            </div>
            
            <p class="text-[10px] text-slate-500 font-bold italic underline decoration-orange-500/30">A importação trará as NF-es emitidas e autorizadas no Bling. Acompanhe em Tarefas.</p>
            
            <button @click="importFromBling()" :disabled="importing"
                    class="w-full py-4 bg-orange-600 hover:bg-orange-500 text-white font-black italic uppercase rounded-xl flex items-center justify-center gap-2 disabled:opacity-50 transition-all shadow-lg shadow-orange-900/20">
                <i x-show="importing" class="fas fa-spinner fa-spin"></i>
                <span x-show="!importing"><i class="fas fa-download mr-2"></i> Iniciar Importação</span>
            </button>
        </div>
    </div>
</div>

<!-- XML Import Modal -->
<div x-show="showImportXml" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 w-full max-w-md" @click.stop>
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center">
                    <i class="fas fa-file-code text-indigo-400"></i>
                </div>
                <h3 class="font-bold text-white">Importar XML</h3>
            </div>
            <button @click="showImportXml = false" class="text-slate-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <label class="border-2 border-dashed border-slate-600 rounded-xl p-8 flex flex-col items-center cursor-pointer hover:border-indigo-500 transition">
            <i class="fas fa-cloud-upload-alt text-3xl text-slate-500 mb-2"></i>
            <span class="text-sm text-slate-400">Clique para selecionar arquivo XML</span>
            <input type="file" accept=".xml" @change="importXml($event)" class="hidden">
        </label>
    </div>
</div>

<!-- ZIP Import Modal -->
<div x-show="showImportZip" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 w-full max-w-md" @click.stop>
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">
                    <i class="fas fa-file-archive text-amber-400"></i>
                </div>
                <h3 class="font-bold text-white">Importar ZIP</h3>
            </div>
            <button @click="showImportZip = false" class="text-slate-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <label class="border-2 border-dashed border-slate-600 rounded-xl p-8 flex flex-col items-center cursor-pointer hover:border-indigo-500 transition">
            <i class="fas fa-cloud-upload-alt text-3xl text-slate-500 mb-2"></i>
            <span class="text-sm text-slate-400">Clique para selecionar arquivo ZIP</span>
            <input type="file" accept=".zip" @change="importZip($event)" class="hidden">
        </label>
    </div>
</div>
@endsection
