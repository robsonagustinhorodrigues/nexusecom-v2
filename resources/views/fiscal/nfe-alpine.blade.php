@extends('layouts.alpine')

@section('title', 'Hub Fiscal NF-e - NexusEcom')
@section('header_title', 'Hub Fiscal')

@section('content')
<div x-data="fiscalPage()" x-init="init()">
    <!-- Premium Dashboard Header -->
    <div class="space-y-4 mb-6">
        <!-- Top Row: Title & Global Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tighter uppercase italic flex items-center gap-3">
                    <span class="bg-indigo-600 w-2 h-8 rounded-full"></span>
                    Hub <span class="text-indigo-500">Fiscal</span>
                </h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] ml-5">Monitor de Notas Fiscais • SEFAZ & XML</p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Importing indicator -->
                <div x-show="importing" class="flex items-center gap-2 px-3 py-2 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-indigo-400 text-xs font-bold animate-pulse shadow-inner">
                    <i class="fas fa-circle-notch fa-spin"></i>
                    <span>Processando...</span>
                </div>

                <!-- Actions Menu -->
                <div class="relative">
                    <button @click="importMenu = !importMenu" :disabled="importing"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl flex items-center gap-3 text-sm text-white font-bold transition-all shadow-lg active:scale-95 disabled:opacity-50">
                        <i class="fas fa-cog" :class="importing ? 'fa-spin' : ''"></i>
                        <span>Ações</span>
                        <i class="fas fa-chevron-down text-[10px] text-slate-500"></i>
                    </button>

                    <div x-show="importMenu" @click.away="importMenu = false" x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="absolute right-0 mt-2 w-72 bg-black border border-slate-700/50 backdrop-blur-xl rounded-2xl shadow-2xl z-50 overflow-hidden py-1">

                        <button @click="importMenu = false; reprocessSelected()" :disabled="selected.length === 0"
                                class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5 hover:text-white flex items-center gap-3 transition-colors disabled:opacity-30">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <i class="fas fa-sync text-amber-400"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold">Reprocessar Associação</span>
                                <span class="text-[10px] text-slate-500" x-text="selected.length > 0 ? selected.length + ' notas selecionadas' : 'Selecione notas'"></span>
                            </div>
                        </button>

                        <div class="h-px bg-slate-700/30 mx-3"></div>

                        <button @click="importMenu = false; showImportMeli = true; meliDataInicio = dataInicio; meliDataFim = dataFim"
                                class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5 hover:text-white flex items-center gap-3 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-yellow-500/10 flex items-center justify-center">
                                <i class="fab fa-mercadolivre text-yellow-400"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold">Mercado Livre</span>
                                <span class="text-[10px] text-slate-500">Importar NF-es por data</span>
                            </div>
                        </button>

                        <button @click="importMenu = false; canSearchSefaz ? importFromSefaz() : null"
                                :class="!canSearchSefaz ? 'opacity-30 cursor-not-allowed' : ''"
                                class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5 hover:text-white flex items-center gap-3 transition-colors border-t border-white/5">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                                <i class="fas fa-satellite-dish text-blue-400"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold">Buscar SEFAZ</span>
                                <span class="text-[10px] text-slate-500" x-text="canSearchSefaz ? 'Consultar novos NSUs' : 'Aguarde: ' + sefazCountdown"></span>
                            </div>
                        </button>

                        <div class="h-px bg-slate-700/30 mx-3"></div>

                        <button @click="importMenu = false; showImportXml = true"
                                class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5 hover:text-white flex items-center gap-3 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                <i class="fas fa-file-code text-emerald-400"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold">XML / ZIP</span>
                                <span class="text-[10px] text-slate-500">Importar manual</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- SEFAZ Status -->
            <div class="bg-gradient-to-br from-indigo-600/20 to-transparent border border-indigo-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">SEFAZ NSU</span>
                    <i class="fas fa-satellite-dish text-indigo-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="empresaStats.last_nsu || '0'"></div>
                <div class="mt-1 text-[9px] text-slate-500 font-bold" x-text="empresaStats.last_sefaz_at || 'Nunca'"></div>
            </div>

            <!-- Volume Financeiro -->
            <div class="bg-gradient-to-br from-emerald-600/20 to-transparent border border-emerald-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Volume</span>
                    <i class="fas fa-dollar-sign text-emerald-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="formatMoney(totalValue)"></div>
                <div class="mt-1 h-1 w-12 bg-emerald-500/50 rounded-full"></div>
            </div>

            <!-- Total Notas -->
            <div class="bg-gradient-to-br from-slate-800 to-transparent border border-slate-700/50 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Notas</span>
                    <i class="fas fa-file-invoice text-slate-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="total"></div>
                <div class="mt-1 h-1 w-12 bg-slate-500/50 rounded-full"></div>
            </div>

            <!-- Selecionados Contextual -->
            <div class="col-span-2 lg:col-span-2 grid grid-cols-2 gap-3">
                <div class="bg-slate-900/60 border border-indigo-500/30 rounded-2xl p-4 shadow-xl border-dashed">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black text-indigo-300 uppercase tracking-widest">Selecionadas</span>
                        <i class="fas fa-check-double text-indigo-400"></i>
                    </div>
                    <div class="text-2xl font-black text-white" x-text="selected.length"></div>
                </div>
                <div class="bg-slate-900/60 border border-indigo-500/30 rounded-2xl p-4 shadow-xl border-dashed">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black text-indigo-300 uppercase tracking-widest">Valor Seleção</span>
                        <i class="fas fa-calculator text-indigo-400"></i>
                    </div>
                    <div class="text-2xl font-black text-indigo-400" x-text="formatMoney(totalSelecionadas)"></div>
                </div>
            </div>
        </div>

        <!-- Filters & Control Bar -->
        <div class="relative">
            <div class="bg-slate-800/80 backdrop-blur-md border border-slate-700/50 rounded-2xl p-3 shadow-2xl flex flex-wrap items-center gap-3 transition-all">

                <!-- Search -->
                <div class="flex-1 min-w-[200px] relative group">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="text" x-model="search" @input.debounce.300ms="loadNfe()"
                        placeholder="Pesquisar número, chave, fornecedor..."
                        class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 focus:outline-none transition-all">
                </div>

                <!-- Date Range -->
                <div class="flex items-center bg-slate-900/50 border border-slate-700/50 rounded-xl px-2 gap-2">
                    <input type="date" x-model="dataInicio" @change="loadNfe()" class="bg-transparent border-none py-2 text-xs text-slate-300 focus:ring-0 outline-none">
                    <span class="text-slate-600 font-bold text-[10px]">ATÉ</span>
                    <input type="date" x-model="dataFim" @change="loadNfe()" class="bg-transparent border-none py-2 text-xs text-slate-300 focus:ring-0 outline-none">
                </div>

                <!-- Category Filters -->
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 md:pb-0">
                    <select x-model="view" @change="filtroSituacao = ''; loadNfe()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-300 focus:ring-2 focus:ring-indigo-500/50 outline-none appearance-none cursor-pointer">
                        <option value="entradas" class="bg-black">📥 Entradas</option>
                        <option value="saidas" class="bg-black">📤 Saídas</option>
                    </select>

                    <select x-model="filtroSituacao" @change="loadNfe()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-400 focus:ring-2 focus:ring-indigo-500/50 outline-none appearance-none cursor-pointer">
                        <option value="" class="bg-black">Situação</option>
                        <option value="autorizada" class="bg-black">✅ Autorizada</option>
                        <option value="cancelada" class="bg-black">❌ Cancelada</option>
                        <option value="denegada" class="bg-black">⛔ Denegada</option>
                        <option value="inutilizada" class="bg-black">🚫 Inutilizada</option>
                    </select>

                    <select x-model="categoriaFilter" @change="loadNfe()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-400 focus:ring-2 focus:ring-indigo-500/50 outline-none appearance-none cursor-pointer">
                        <option value="" class="bg-black">Origem</option>
                        <option value="emitida" class="bg-black">📝 Emitidas</option>
                        <option value="recebida" class="bg-black">📩 Recebidas</option>
                    </select>

                    <select x-model="finalidadeFilter" @change="loadNfe()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-400 focus:ring-2 focus:ring-indigo-500/50 outline-none appearance-none cursor-pointer">
                        <option value="" class="bg-black">Finalidade</option>
                        <option value="venda" class="bg-black">🛍️ Venda</option>
                        <option value="devolucao" class="bg-black">↩️ Devolução / Retorno</option>
                        <option value="transferencia" class="bg-black">🚚 Transferência</option>
                        <option value="outras" class="bg-black">📦 Outras</option>
                    </select>

                    <select x-model="associationFilter" @change="loadNfe()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-400 focus:ring-2 focus:ring-indigo-500/50 outline-none appearance-none cursor-pointer">
                        <option value="" class="bg-black">Associação</option>
                        <option value="pending" class="bg-black">🟡 Pendentes</option>
                        <option value="partial" class="bg-black">🔵 Parciais</option>
                        <option value="associated" class="bg-black">🟢 Associadas</option>
                    </select>
                </div>
            </div>

            <!-- Selection Overlay -->
            <template x-if="selected.length > 0">
                <div x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="absolute inset-0 bg-indigo-600 rounded-2xl flex items-center justify-between px-6 shadow-2xl z-10 border border-indigo-400/50">
                    <div class="flex items-center gap-4">
                        <i class="fas fa-check-circle text-2xl text-white"></i>
                        <span class="text-white font-black text-lg" x-text="selected.length + ' Notas Selecionadas'"></span>
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs text-white font-bold" x-text="formatMoney(totalSelecionadas)"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="exportSelectedXml()" class="px-5 py-2.5 bg-white text-indigo-600 rounded-xl font-black text-sm hover:bg-slate-100 transition-all flex items-center gap-2 shadow-lg active:scale-95">
                            <i class="fas fa-file-export"></i> Exportar XML
                        </button>
                        <button @click="selected = []" class="p-2.5 bg-indigo-700 hover:bg-indigo-800 text-white rounded-xl transition-all">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Tabela de Notas -->
    <div class="bg-slate-800 border border-slate-700 rounded-3xl overflow-hidden shadow-2xl">
        
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-900/30">
                        <th class="px-2 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-center">
                            <input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded bg-slate-700 border-slate-600 text-indigo-500">
                        </th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-left">Número / Data</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-left" x-text="view === 'saidas' ? 'Cliente / Destinatário' : 'Fornecedor / Emitente'">Emitente / Destinatário</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-right">Valor Total</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-center">Associação</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <template x-for="n in nfe" :key="n.categoria + '-' + n.id">
                        <tr class="group hover:bg-slate-700/20 transition-colors">
                            <td class="px-2 py-4 text-center">
                                <input type="checkbox" :value="n.categoria + '-' + n.id" x-model="selected" class="rounded bg-slate-700 border-slate-600 text-indigo-500">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-white italic uppercase tracking-tight" x-text="n.numero"></span>
                                    <span class="text-[10px] text-slate-500 font-bold" x-text="formatDate(n.data_emissao)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-300 italic uppercase" x-text="n.counterparty_nome"></span>
                                    <span class="text-[10px] text-slate-500 font-mono" x-text="n.counterparty_cnpj"></span>
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
                                    <a :href="'/nfe/danfe/' + n.id + '/' + n.categoria" target="_blank" 
                                       class="w-8 h-8 rounded-lg bg-indigo-600/10 text-indigo-400 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="DANFE A4">
                                        <i class="fas fa-print text-xs"></i>
                                    </a>
                                    <a :href="'/nfe/danfe-simplificada/' + n.id + '/' + n.categoria" target="_blank" 
                                       class="w-8 h-8 rounded-lg bg-blue-600/10 text-blue-400 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="DANFE Simplificada">
                                        <i class="fas fa-receipt text-xs"></i>
                                    </a>
                                    <a :href="'/nfe/download-xml/' + n.id + '/' + n.categoria" 
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

    <!-- Import Modals (inside x-data scope) -->
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
</div>
@endsection

@section('scripts')
<script>
function fiscalPage() {
    return {
            view: 'entradas',
            filtroSituacao: '',
            categoriaFilter: '',
            filtersOpen: false,
            associationFilter: '',
            dataInicio: '',
            dataFim: '',
            search: '',
            importMenu: false,
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
        empresaId: 6,
        // Server-side aggregates
        totalValue: 0,
        canceladasCount: 0,
        
        get totalSelecionadas() {
            return this.nfe.filter(n => this.selected.includes(n.categoria + '-' + n.id)).reduce((sum, n) => sum + parseFloat(n.valor_total || 0), 0);
        },
        
        toggleAll(checked) {
            if (checked) {
                this.selected = this.nfe.map(n => n.categoria + '-' + n.id);
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
            // Get empresa from localStorage
            const savedEmpresa = localStorage.getItem('empresa_id');
            this.empresaId = savedEmpresa ? parseInt(savedEmpresa) : 6;
            
            // Iniciar com data padrão -60 dias e data final hoje
            const hoje = new Date();
            const sessentaDiasAtras = new Date();
            sessentaDiasAtras.setDate(hoje.getDate() - 60);
            
            this.dataFim = hoje.toISOString().split('T')[0];
            this.dataInicio = sessentaDiasAtras.toISOString().split('T')[0];
            
            // Restaurar filtros da URL (sobrescreve os defaults acima se existirem)
            this.initFromUrl();
            
            await this.loadNfe(false);
            
            // Listen for empresa changes from the layout
            window.addEventListener('empresa-changed', (e) => {
                this.empresaId = parseInt(e.detail);
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadNfe();
            });

            // Listen for browser back/forward
            window.addEventListener('popstate', () => {
                this.initFromUrl();
                this.loadNfe(false);
            });
        },

        initFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('search')) this.search = urlParams.get('search');
            if (urlParams.has('view')) {
                const v = urlParams.get('view');
                if (v === 'recebidas') this.view = 'entradas';
                else if (v === 'emitidas') this.view = 'saidas';
                else this.view = v;
            }
            if (urlParams.has('situacao')) this.filtroSituacao = urlParams.get('situacao');
            if (urlParams.has('categoria')) this.categoriaFilter = urlParams.get('categoria');
            if (urlParams.has('finalidade')) this.finalidadeFilter = urlParams.get('finalidade');
            if (urlParams.has('association')) this.associationFilter = urlParams.get('association');
            if (urlParams.has('data_inicio')) this.dataInicio = urlParams.get('data_inicio');
            if (urlParams.has('data_fim')) this.dataFim = urlParams.get('data_fim');
            if (urlParams.has('page')) this.currentPage = parseInt(urlParams.get('page'));
        },

        updateUrlParams() {
            const params = new URLSearchParams();
            if (this.search) params.set('search', this.search);
            if (this.view && this.view !== 'entradas') params.set('view', this.view);
            if (this.filtroSituacao) params.set('situacao', this.filtroSituacao);
            if (this.categoriaFilter) params.set('categoria', this.categoriaFilter);
            if (this.finalidadeFilter) params.set('finalidade', this.finalidadeFilter);
            if (this.associationFilter) params.set('association', this.associationFilter);
            if (this.dataInicio) params.set('data_inicio', this.dataInicio);
            if (this.dataFim) params.set('data_fim', this.dataFim);
            if (this.currentPage > 1) params.set('page', this.currentPage);

            const queryString = params.toString();
            const currentQuery = window.location.search.replace(/^\?/, '');

            if (queryString !== currentQuery) {
                const newUrl = window.location.pathname + (queryString ? '?' + queryString : '');
                history.pushState(null, '', newUrl);
            }
        },
        
        async loadNfe(resetPage = true) {
            if (resetPage) {
                this.currentPage = 1;
            }
            this.loading = true;
            this.updateUrlParams();
            try {
                const empresaId = this.empresaId || localStorage.getItem('empresa_id') || 6;
                const params = new URLSearchParams({
                    empresa: empresaId,
                    tipo: this.view,
                    situacao: this.filtroSituacao,
                    data_inicio: this.dataInicio,
                    data_fim: this.dataFim,
                    search: this.search,
                    categoria: this.categoriaFilter,
                    finalidade: this.finalidadeFilter,
                    association: this.associationFilter,
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

                    // Server-side aggregates
                    if (data.aggregates) {
                        this.totalValue = data.aggregates.total_value || 0;
                        this.canceladasCount = data.aggregates.canceladas_count || 0;
                    }
                    
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
                const empresaId = this.empresaId || localStorage.getItem('empresa_id') || 6;
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
                const empresaId = this.empresaId || localStorage.getItem('empresa_id') || 6;
                const response = await fetch(`/api/nfes/import?empresa=${empresaId}`, {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
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
                const empresaId = this.empresaId || localStorage.getItem('empresa_id') || 6;
                const response = await fetch(`/api/nfes/import?empresa=${empresaId}`, {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
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
                const empresaId = this.empresaId || localStorage.getItem('empresa_id') || 6;
                const response = await fetch('/api/nfes/import-meli', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
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
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
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
                const empresaId = this.empresaId || localStorage.getItem('empresa_id') || 6;
                const response = await fetch('/api/nfes/import-bling', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
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
            
            const emitidas = this.selected.filter(s => s.startsWith('emitida-')).map(s => s.replace('emitida-', ''));
            const recebidas = this.selected.filter(s => s.startsWith('recebida-')).map(s => s.replace('recebida-', ''));
            
            try {
                if (emitidas.length > 0) {
                    await fetch('/api/nfes/reprocess-association', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ ids: emitidas, type: 'emitida' })
                    });
                }
                
                if (recebidas.length > 0) {
                    await fetch('/api/nfes/reprocess-association', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ ids: recebidas, type: 'recebida' })
                    });
                }
                
                this.selected = [];
                await this.loadNfe();
                alert('Associação reprocessada com sucesso!');
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
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
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
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
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
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

@endsection
