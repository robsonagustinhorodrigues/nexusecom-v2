@extends('layouts.alpine')

@section('title', 'Anúncios - NexusEcom')
@section('header_title', 'Anúncios')

@section('content')
<div x-data="anunciosPage()" x-init="init()">
    <!-- Premium Dashboard Header -->
    <div class="space-y-4 mb-6">
        <!-- Top Row: Title & Global Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tighter uppercase italic flex items-center gap-3">
                    <span class="bg-yellow-500 w-2 h-8 rounded-full"></span>
                    Meus <span class="text-yellow-500">Anúncios</span>
                </h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] ml-5">Marketplace Listings Manager</p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Sync Progress Indicator -->
                <div x-show="syncing" class="flex items-center gap-2 px-3 py-2 bg-yellow-500/10 border border-yellow-500/20 rounded-xl text-yellow-400 text-xs font-bold animate-pulse shadow-inner">
                    <i class="fas fa-circle-notch fa-spin"></i>
                    <span>Sincronizando...</span>
                </div>

                <!-- View Mode -->
                <div class="flex bg-slate-800 border border-slate-700/50 rounded-xl p-1">
                    <button @click="viewMode = 'cards'" :class="viewMode === 'cards' ? 'bg-slate-700 text-white shadow' : 'text-slate-400'" class="px-3 py-1.5 rounded-lg text-sm font-bold transition-all">
                        <i class="fas fa-th-large mr-1"></i> Cards
                    </button>
                    <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-slate-700 text-white shadow' : 'text-slate-400'" class="px-3 py-1.5 rounded-lg text-sm font-bold transition-all">
                        <i class="fas fa-list mr-1"></i> Tabela
                    </button>
                </div>

                <!-- Actions Menu -->
                <div class="relative">
                    <button @click="actionsDropdownOpen = !actionsDropdownOpen" :disabled="syncing" 
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl flex items-center gap-3 text-sm text-white font-bold transition-all shadow-lg active:scale-95 disabled:opacity-50">
                        <i class="fas fa-cog" :class="syncing ? 'fa-spin' : ''"></i>
                        <span>Ações</span>
                        <i class="fas fa-chevron-down text-[10px] text-slate-500"></i>
                    </button>
                    
                    <div x-show="actionsDropdownOpen" @click.away="actionsDropdownOpen = false" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="absolute right-0 mt-2 w-72 bg-black border border-slate-700/50 backdrop-blur-xl rounded-2xl shadow-2xl z-50 overflow-hidden py-1">
                        
                        <button @click="syncAnuncios('mercadolivre'); actionsDropdownOpen = false" :disabled="syncing"
                            class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5 hover:text-white flex items-center gap-3 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-yellow-500/10 flex items-center justify-center">
                                <i class="fas fa-sync" :class="syncing ? 'fa-spin' : ''" class="text-yellow-400"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold">Sincronizar Mercado Livre</span>
                                <span class="text-[10px] text-slate-500">Buscar novos anúncios do ML</span>
                            </div>
                        </button>

                        <button @click="syncAnuncios('amazon'); actionsDropdownOpen = false" :disabled="syncing"
                            class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5 hover:text-white flex items-center gap-3 transition-colors border-t border-white/5">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <i class="fas fa-sync" :class="syncing ? 'fa-spin' : ''" class="text-amber-400"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold">Sincronizar Amazon</span>
                                <span class="text-[10px] text-slate-500">Buscar novos anúncios da Amazon</span>
                            </div>
                        </button>

                        <button @click="vincularPorSku(); actionsDropdownOpen = false" 
                            class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5 hover:text-white flex items-center gap-3 transition-colors border-t border-white/5">
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                                <i class="fas fa-link text-indigo-400"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold">Vincular por SKU</span>
                                <span class="text-[10px] text-slate-500">Auto-vincular anúncios sem vínculo</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Total Anúncios -->
            <div class="bg-gradient-to-br from-indigo-600/20 to-transparent border border-indigo-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Anúncios</span>
                    <i class="fas fa-bullhorn text-indigo-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="anuncios.length"></div>
                <div class="mt-1 h-1 w-12 bg-indigo-500/50 rounded-full"></div>
            </div>

            <!-- Ativos -->
            <div class="bg-gradient-to-br from-emerald-600/20 to-transparent border border-emerald-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Ativos</span>
                    <i class="fas fa-check-circle text-emerald-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="activeCount"></div>
                <div class="mt-1 h-1 w-12 bg-emerald-500/50 rounded-full"></div>
            </div>

            <!-- Inativos -->
            <div class="bg-gradient-to-br from-red-600/20 to-transparent border border-red-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-red-400 uppercase tracking-widest">Inativos</span>
                    <i class="fas fa-times-circle text-red-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="inactiveCount"></div>
                <div class="mt-1 h-1 w-12 bg-red-500/50 rounded-full"></div>
            </div>

            <!-- Sem Estoque -->
            <div class="bg-gradient-to-br from-amber-600/20 to-transparent border border-amber-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Sem Estoque</span>
                    <i class="fas fa-box-open text-amber-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="noStockCount"></div>
                <div class="mt-1 h-1 w-12 bg-amber-500/50 rounded-full"></div>
            </div>

            <!-- Lucro Médio -->
            <div class="bg-gradient-to-br from-slate-800 to-transparent border border-slate-700/50 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black uppercase tracking-widest" :class="lucroMedio >= 0 ? 'text-green-400' : 'text-red-400'">Lucro Médio</span>
                    <i class="fas fa-chart-line text-slate-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="formatMoney(lucroMedio)"></div>
                <div class="mt-1 h-1 w-12 rounded-full" :class="lucroMedio >= 0 ? 'bg-green-500/50' : 'bg-red-500/50'"></div>
            </div>
        </div>

        <!-- Filters & Control Bar -->
        <div class="bg-slate-800/80 backdrop-blur-md border border-slate-700/50 rounded-2xl p-3 shadow-2xl flex flex-wrap items-center gap-3 transition-all">
            <!-- Search -->
            <div class="flex-1 min-w-[200px] relative group">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-yellow-500 transition-colors"></i>
                <input type="text" x-model="search" @input.debounce.300ms="loadAnuncios()" 
                    placeholder="Pesquisar por título, ID ou SKU..."
                    class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-yellow-500/50 focus:border-yellow-500 focus:outline-none transition-all">
            </div>

            <!-- Category Filters -->
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 md:pb-0">
                <select x-model="marketplace" @change="loadAnuncios()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-300 focus:ring-2 focus:ring-yellow-500/50 outline-none appearance-none cursor-pointer">
                    <option value="" class="bg-black">Marketplace</option>
                    <option value="mercadolivre" class="bg-black">🤝 Mercado Livre</option>
                    <option value="amazon" class="bg-black">📦 Amazon</option>
                </select>
                <select x-model="statusFilter" @change="loadAnuncios()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-300 focus:ring-2 focus:ring-yellow-500/50 outline-none appearance-none cursor-pointer">
                    <option value="" class="bg-black">Status</option>
                    <option value="active" class="bg-black">✅ Ativos</option>
                    <option value="inactive" class="bg-black">❌ Inativos</option>
                </select>
                <select x-model="tipoFilter" @change="loadAnuncios()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-300 focus:ring-2 focus:ring-yellow-500/50 outline-none appearance-none cursor-pointer">
                    <option value="" class="bg-black">Tipo</option>
                    <option value="catalogo" class="bg-black">📑 Catálogo</option>
                    <option value="normal" class="bg-black">📄 Normal</option>
                </select>
                <select x-model="vinculoFilter" @change="loadAnuncios()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-300 focus:ring-2 focus:ring-yellow-500/50 outline-none appearance-none cursor-pointer">
                    <option value="" class="bg-black">Vínculo</option>
                    <option value="vinculado" class="bg-black">🔗 Vinculados</option>
                    <option value="nao_vinculado" class="bg-black">🔓 Não Vinculados</option>
                </select>
                <select x-model="repricerFilter" @change="loadAnuncios()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-300 focus:ring-2 focus:ring-yellow-500/50 outline-none appearance-none cursor-pointer">
                    <option value="" class="bg-black">Repricer</option>
                    <option value="ativo" class="bg-black">🤖 Ativo</option>
                    <option value="inativo" class="bg-black">💤 Inativo</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-center py-16">
        <div class="inline-flex items-center gap-3 px-6 py-3 bg-slate-800/80 border border-slate-700/50 rounded-2xl shadow-2xl backdrop-blur-md">
            <i class="fas fa-circle-notch fa-spin text-xl text-yellow-500"></i>
            <span class="text-slate-300 font-bold text-sm">Carregando inteligência de anúncios...</span>
        </div>
    </div>

    <!-- MAIN LISTING VIEW -->
    <div x-show="!loading && anuncios.length > 0 && viewMode === 'cards'" class="space-y-4">
        <template x-for="anuncio in anuncios" :key="anuncio.id">
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden hover:border-yellow-500/30 transition-all shadow-xl hover:shadow-yellow-500/5 group">
                <!-- CARD HEADER/STRIP -->
                <div class="bg-slate-900/40 px-4 py-2.5 border-b border-slate-700/30 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-4 rounded-full bg-yellow-500/50"></div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]" x-text="anuncio.marketplace + ' listing'"></span>
                        <span class="text-[10px] px-2 py-0.5 rounded-md bg-slate-800 border border-slate-700 text-slate-500 font-mono" x-text="anuncio.external_id"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span x-show="anuncio.is_catalog" class="text-[9px] px-2 py-0.5 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-400 font-bold uppercase tracking-wider">Catálogo</span>
                        <span class="text-[10px] px-2.5 py-1 rounded-lg font-black uppercase tracking-widest shadow-sm border border-white/5"
                            :class="anuncio.status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'"
                            x-text="anuncio.status === 'active' ? 'Ativo' : 'Inativo'">
                        </span>
                        <template x-if="anuncio.is_catalog && anuncio.json_data.catalog_product_id">
                            <a :href="'https://www.mercadolivre.com.br/' + anuncio.slug + '/p/' + anuncio.json_data.catalog_product_id + '/s?'" 
                               target="_blank"
                               class="text-[9px] px-2 py-1 rounded-lg bg-indigo-500/20 border border-indigo-500/30 text-indigo-400 font-black uppercase tracking-widest hover:bg-indigo-500/30 transition-all flex items-center gap-1.5"
                               title="Ver Concorrentes no Catálogo">
                               <i class="fas fa-users-viewfinder"></i>
                               <span>Concorrentes</span>
                            </a>
                        </template>
                    </div>
                </div>

                <!-- MAIN CONTENT BODY -->
                <div class="p-4 flex flex-col lg:flex-row gap-6">
                    <!-- LEFT COLUMN: PRODUCT INFO -->
                    <div class="flex-1 flex items-start gap-4">
                        <div class="w-20 h-20 shrink-0 bg-slate-900 rounded-xl overflow-hidden shadow-inner border border-slate-700/50 flex items-center justify-center group-hover:border-yellow-500/20 transition-colors">
                            <img x-show="anuncio.thumbnail" :src="anuncio.thumbnail" class="w-full h-full object-cover">
                            <i x-show="!anuncio.thumbnail" class="fas fa-image text-slate-700 text-xl"></i>
                        </div>
                        
                        <div class="flex-1 min-w-0 flex flex-col gap-2">
                            <div class="flex items-start justify-between gap-3 group/title">
                                <template x-if="editingTitulo !== anuncio.id">
                                    <h3 class="font-bold text-sm text-white line-clamp-2 leading-tight cursor-pointer hover:text-yellow-400 transition-colors"
                                        @click="startEditTitulo(anuncio)" x-text="anuncio.titulo"></h3>
                                </template>
                                <template x-if="editingTitulo === anuncio.id">
                                    <div class="flex items-center gap-1 w-full">
                                        <input type="text" x-model="editingTituloValue" class="bg-black/40 border border-slate-700/50 text-white text-xs px-2 py-1.5 rounded-lg flex-1 focus:ring-1 focus:ring-yellow-500 outline-none" @keyup.enter="saveTitulo(anuncio)" @keyup.escape="cancelEditTitulo()">
                                        <button @click="saveTitulo(anuncio)" class="p-1.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-lg hover:bg-emerald-500/30 transition-colors">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <!-- SKU -->
                                <div class="flex items-center gap-1.5 bg-slate-900 px-2 py-1 rounded-lg border border-slate-700/50 shadow-sm group/sku">
                                    <span class="text-[9px] font-black text-slate-500 uppercase">SKU:</span>
                                    <template x-if="editingSku !== anuncio.id">
                                        <span class="text-xs font-mono font-bold text-indigo-400 cursor-pointer hover:text-yellow-400" @click="startEditSku(anuncio)" x-text="anuncio.sku || '-'"></span>
                                    </template>
                                    <template x-if="editingSku === anuncio.id">
                                        <input type="text" x-model="editingSkuValue" class="bg-black/40 border border-slate-700/50 text-white text-[10px] px-1 py-0.5 rounded w-24 focus:ring-1 focus:ring-yellow-500 outline-none" @keyup.enter="saveSku(anuncio)" @keyup.escape="cancelEditSku()">
                                    </template>
                                    <button @click="copyToClipboard(anuncio.sku, 'SKU copiado!')" class="text-slate-600 hover:text-yellow-400">
                                        <i class="fas fa-copy text-[10px]"></i>
                                    </button>
                                </div>

                                <!-- STOCK -->
                                <div class="flex items-center gap-1.5 bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-700/50 shadow-sm">
                                    <span class="text-[9px] font-black text-slate-500 uppercase">Estoque:</span>
                                    <span class="text-xs font-black" :class="(anuncio.estoque || 0) > 0 ? 'text-emerald-400' : 'text-red-400'" x-text="anuncio.estoque || 0"></span>
                                    <div class="w-1.5 h-1.5 rounded-full" :class="(anuncio.estoque || 0) > 0 ? 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)]' : 'bg-red-400 shadow-[0_0_8px_rgba(248,113,113,0.5)]'"></div>
                                </div>

                                <!-- TYPE -->
                                <div class="flex items-center gap-1.5 bg-slate-900 px-2 py-1 rounded-lg border border-slate-700/50 shadow-sm">
                                    <span class="text-[9px] font-black text-slate-500 uppercase">Tipo:</span>
                                    <span class="text-[11px] font-bold" :class="anuncio.listing_type === 'gold_pro' ? 'text-amber-400' : 'text-blue-400'" 
                                        x-text="anuncio.listing_type === 'gold_pro' ? 'Premium' : (anuncio.listing_type === 'gold_special' ? 'Clássico' : anuncio.listing_type)"></span>
                                </div>

                                <!-- DIMENSIONS -->
                                <template x-if="anuncio.medidas && Object.keys(anuncio.medidas).length > 0">
                                    <div class="flex items-center gap-1.5 bg-slate-900 px-2 py-1 rounded-lg border border-slate-700/50 shadow-sm">
                                        <i class="fas fa-ruler-combined text-[9px] text-slate-500"></i>
                                        <span class="text-[10px] text-slate-400 font-medium">
                                            <span x-text="anuncio.medidas.altura || ''"></span>
                                            <template x-if="anuncio.medidas.altura && anuncio.medidas.largura"><span> x </span></template>
                                            <span x-text="anuncio.medidas.largura || ''"></span>
                                            <template x-if="anuncio.medidas.largura && anuncio.medidas.comprimento"><span> x </span></template>
                                            <span x-text="anuncio.medidas.comprimento || ''"></span>
                                            <template x-if="anuncio.medidas.peso">
                                                <span class="ml-1 text-slate-500">|</span>
                                                <i class="fas fa-weight-hanging text-[8px] ml-1"></i>
                                                <span x-text="anuncio.medidas.peso"></span>
                                            </template>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- CENTER COLUMN: FINANCIAL DETAILS -->
                    <div class="w-full lg:w-[260px] flex-shrink-0">
                        <div class="bg-slate-900/40 rounded-xl border border-slate-700/50 p-3 h-full flex flex-col justify-center gap-2">
                            <div class="flex justify-between items-center text-[11px]">
                                <span class="text-slate-500 font-bold uppercase tracking-wider">Preço Venda</span>
                                <span class="font-black text-white text-sm" x-text="formatMoney(anuncio.preco)"></span>
                            </div>
                            <div class="flex justify-between items-center text-[11px]">
                                <span class="text-slate-500 font-bold uppercase tracking-wider">Custo Produto</span>
                                <span class="font-bold text-red-500/80" x-text="'-' + formatMoney(anuncio.custo || 0)"></span>
                            </div>
                            <div class="flex justify-between items-center text-[11px]">
                                <span class="text-slate-500 font-bold uppercase tracking-wider">Comissão/Taxa</span>
                                <span class="font-bold text-amber-500/80" x-text="'-' + formatMoney(anuncio.taxas || 0)"></span>
                            </div>
                            <div class="flex justify-between items-center text-[11px]">
                                <span class="text-slate-500 font-bold uppercase tracking-wider">Frete/Envio</span>
                                <span class="font-bold text-amber-500/80" x-text="'-' + formatMoney(anuncio.frete || 0)"></span>
                            </div>
                            <div class="flex justify-between items-center text-[11px]">
                                <span class="text-slate-500 font-bold uppercase tracking-wider">Imposto</span>
                                <span class="font-bold text-red-400/80" x-text="'-' + formatMoney(anuncio.imposto || 0)"></span>
                            </div>
                            
                            <div class="mt-1 pt-2 border-t border-slate-700/50 flex justify-between items-end">
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest leading-none mb-1">Lucro Líquido</span>
                                    <span class="text-lg font-black leading-none" :class="(anuncio.lucro || 0) >= 0 ? 'text-emerald-400' : 'text-red-400'" x-text="formatMoney(anuncio.lucro || 0)"></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-[9px] font-black p-1 rounded bg-slate-800 text-slate-500 uppercase border border-slate-700" :class="(anuncio.margem || 0) >= 0 ? 'text-emerald-500/80' : 'text-red-500/80'" x-text="(anuncio.margem || 0).toFixed(1) + '% margem'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: ACTIONS & LINKAGE -->
                    <div class="w-full lg:w-[180px] flex-shrink-0 flex flex-col justify-between gap-3">
                        <!-- LINK STATUS -->
                        <div class="flex-1 flex flex-col justify-center">
                            <template x-if="anuncio.product_linked">
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-500/20 border border-emerald-500/30 rounded-xl">
                                        <i class="fas fa-link text-emerald-500 text-xs"></i>
                                        <span class="text-[10px] font-black text-emerald-400 uppercase tracking-wider">Vinculado</span>
                                    </div>
                                    <div class="px-1 flex flex-col">
                                        <p class="text-[10px] text-slate-300 font-bold truncate" x-text="anuncio.product_nome"></p>
                                        <p class="text-[9px] text-indigo-400 font-mono font-bold uppercase" x-text="'SKU: ' + (anuncio.product_sku || 'N/A')"></p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!anuncio.product_linked">
                                <button @click="showVincularModal = true; anuncioSelecionado = anuncio"
                                    class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-yellow-500/10 border border-yellow-500/30 rounded-xl hover:bg-yellow-500/20 hover:border-yellow-500/50 transition-all group/vincular shadow-sm">
                                    <i class="fas fa-link text-yellow-500 text-xs animate-pulse"></i>
                                    <span class="text-[10px] font-black text-yellow-500 uppercase tracking-wider">Vincular</span>
                                </button>
                            </template>
                        </div>

                        <!-- QUICK ACTIONS -->
                        <div class="flex items-center gap-2">
                            <button @click="openRepricer(anuncio)" title="Configurar Repricer"
                                class="flex-1 h-9 rounded-xl bg-slate-700/50 hover:bg-indigo-500/20 text-slate-400 hover:text-indigo-400 border border-slate-600/50 hover:border-indigo-500/30 transition-all flex items-center justify-center">
                                <i class="fas fa-robot text-sm" :class="anuncio.repricer_config?.is_active ? 'text-indigo-400' : ''"></i>
                            </button>
                            <button @click="editAnuncio(anuncio)" title="Editar Anúncio"
                                class="flex-1 h-9 rounded-xl bg-slate-700/50 hover:bg-yellow-500/20 text-slate-400 hover:text-yellow-400 border border-slate-600/50 hover:border-yellow-500/30 transition-all flex items-center justify-center">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                            <a :href="anuncio.url" target="_blank" title="Ver no Marketplace"
                                class="flex-1 h-9 rounded-xl bg-slate-700/50 hover:bg-slate-600 text-slate-400 hover:text-white border border-slate-600/50 transition-all flex items-center justify-center">
                                <i class="fas fa-external-link-alt text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty -->
    <div x-show="!loading && anuncios.length === 0" class="text-center py-20">
        <div class="inline-flex flex-col items-center gap-4 px-12 py-10 bg-slate-800/40 border border-slate-700/50 rounded-3xl shadow-2xl backdrop-blur-md">
            <div class="w-16 h-16 rounded-full bg-slate-900 flex items-center justify-center border border-slate-700 shadow-inner">
                <i class="fas fa-bullhorn text-4xl text-slate-700"></i>
            </div>
            <div class="space-y-1">
                <p class="text-white font-black uppercase tracking-widest">Nenhum anúncio encontrado</p>
                <p class="text-slate-500 text-xs">Tente ajustar seus filtros ou sincronizar com o marketplace para carregar novos itens.</p>
            </div>
            <button @click="syncAnuncios()" class="mt-2 px-6 py-2 bg-yellow-500 hover:bg-yellow-400 text-slate-900 font-black text-[10px] uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-yellow-500/20 active:scale-95">
                Sincronizar Agora
            </button>
        </div>
    </div>

    <!-- Table View -->
    <div x-show="!loading && viewMode === 'table'" class="bg-slate-800/80 rounded-2xl border border-slate-700/50 overflow-hidden shadow-xl">
        <table class="w-full">
            <thead class="bg-slate-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-400">Anúncio</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-400">SKU</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-400">Tipo/Tarifa</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-slate-400">Promoção</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-400">Datas</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Preço</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Custo</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Taxas</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Envio</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Imposto</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Lucro</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Margem</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-slate-400">Estoque</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-slate-400">Vínculo</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-slate-400">Ações</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-slate-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                <template x-for="anuncio in anuncios" :key="anuncio.id">
                    <tr class="hover:bg-slate-700/30">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img x-show="anuncio.thumbnail" :src="anuncio.thumbnail" class="w-10 h-10 rounded object-cover">
                                <div x-show="!anuncio.thumbnail" class="w-10 h-10 rounded bg-slate-700 flex items-center justify-center">
                                    <i class="fas fa-image text-slate-500 text-xs"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <template x-if="editingTitulo !== anuncio.id">
                                        <p class="text-sm font-medium text-white line-clamp-1 flex items-center gap-1 cursor-pointer hover:text-yellow-400" @click="startEditTitulo(anuncio)" title="Clique para editar">
                                            <span x-text="anuncio.titulo"></span>
                                            <i class="fas fa-edit text-xs text-slate-500"></i>
                                        </p>
                                    </template>
                                    <template x-if="editingTitulo === anuncio.id">
                                        <div class="flex items-center gap-1">
                                            <input type="text" x-model="editingTituloValue" class="bg-slate-700 text-white text-sm px-2 py-1 rounded flex-1 min-w-0" @keyup.enter="saveTitulo(anuncio)" @keyup.escape="cancelEditTitulo()">
                                            <button @click="saveTitulo(anuncio)" :disabled="savingField" class="p-1 bg-green-500 text-white rounded hover:bg-green-400 flex-shrink-0">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                            <button @click="cancelEditTitulo()" class="p-1 bg-slate-600 text-white rounded hover:bg-slate-500 flex-shrink-0">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                    </template>
                                    <p class="text-xs text-slate-500 flex items-center gap-1">
                                        <span x-text="anuncio.marketplace"></span>
                                        <template x-if="anuncio.external_id">
                                            <span class="text-slate-600">-</span>
                                        </template>
                                        <template x-if="anuncio.external_id">
                                            <button @click="copyToClipboard(anuncio.external_id, 'MLB copiado!')" class="text-slate-500 hover:text-yellow-400" title="Copiar MLB">
                                                <span x-text="anuncio.external_id"></span>
                                                <i class="fas fa-copy text-[10px]"></i>
                                            </button>
                                        </template>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <template x-if="editingSku !== anuncio.id">
                                    <span class="text-sm text-slate-400 cursor-pointer hover:text-yellow-400 flex items-center gap-1" @click="startEditSku(anuncio)" title="Clique para editar">
                                        <span x-text="anuncio.sku || '-'"></span>
                                        <i class="fas fa-edit text-xs text-slate-500"></i>
                                    </span>
                                </template>
                                <template x-if="editingSku === anuncio.id">
                                    <div class="flex items-center gap-1">
                                        <input type="text" x-model="editingSkuValue" class="bg-slate-700 text-white text-sm px-2 py-1 rounded w-24" @keyup.enter="saveSku(anuncio)" @keyup.escape="cancelEditSku()">
                                        <button @click="saveSku(anuncio)" :disabled="savingField" class="p-1 bg-green-500 text-white rounded hover:bg-green-400">
                                            <i class="fas fa-check text-xs"></i>
                                        </button>
                                        <button @click="cancelEditSku()" class="p-1 bg-slate-600 text-white rounded hover:bg-slate-500">
                                            <i class="fas fa-times text-xs"></i>
                                        </button>
                                    </div>
                                </template>
                                <button @click="copyToClipboard(anuncio.sku, 'SKU copiado!')" class="text-slate-500 hover:text-yellow-400" title="Copiar SKU" x-show="anuncio.sku">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <div class="mt-1 flex items-center gap-1 text-[10px]" x-show="anuncio.variation_id">
                                <span class="font-bold text-purple-400 border border-purple-400/30 px-1 rounded">ID Var</span>
                                <span class="text-slate-400" x-text="anuncio.variation_id"></span>
                            </div>
                            <template x-if="anuncio.medidas && Object.keys(anuncio.medidas).length > 0">
                                <div class="mt-1 flex items-center gap-1 text-[10px]">
                                    <i class="fas fa-ruler-combined text-slate-500"></i>
                                    <span class="text-slate-400">
                                        <span x-text="anuncio.medidas.altura || ''"></span>
                                        <template x-if="anuncio.medidas.altura && anuncio.medidas.largura"><span>x</span></template>
                                        <span x-text="anuncio.medidas.largura || ''"></span>
                                        <template x-if="anuncio.medidas.largura && anuncio.medidas.comprimento"><span>x</span></template>
                                        <span x-text="anuncio.medidas.comprimento || ''"></span>
                                        <template x-if="anuncio.medidas.peso">
                                            <span class="ml-0.5 text-slate-600">|</span>
                                            <span x-text="anuncio.medidas.peso"></span>
                                        </template>
                                    </span>
                                </div>
                            </template>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-bold" :class="anuncio.listing_type === 'gold_pro' ? 'text-amber-400' : 'text-blue-400'" 
                                    x-text="anuncio.listing_type === 'gold_pro' ? 'Premium' : (anuncio.listing_type === 'gold_special' ? 'Clássico' : anuncio.listing_type)"></span>
                                <span class="text-[10px] text-slate-400" x-text="'Tarifa: ' + (anuncio.taxa_percent || 0).toFixed(1) + '%'"></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-col items-center gap-1" x-show="anuncio.has_promotion">
                                <span class="text-xs px-2 py-1 rounded-full bg-green-500/20 text-green-400" title="Valor Promocional">
                                    <i class="fas fa-tag mr-1"></i><span x-text="formatMoney(anuncio.promocao_valor)"></span>
                                </span>
                                <span class="text-[10px] text-emerald-500" x-show="anuncio.promocao_desconto > 0" x-text="'- ' + formatMoney(anuncio.promocao_desconto)"></span>
                            </div>
                            <span x-show="!anuncio.has_promotion" class="text-slate-600">-</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1 text-[10px] whitespace-nowrap">
                                <div class="flex items-center justify-between gap-2 mb-1 border-b border-slate-600/30 pb-1">
                                    <div class="flex items-center gap-1" title="Vendas">
                                        <i class="fas fa-shopping-cart text-green-500"></i> <span class="text-green-400 font-bold" x-text="anuncio.sold_quantity || 0"></span>
                                    </div>
                                    <div class="flex items-center gap-1" title="Visitas">
                                        <i class="fas fa-eye text-blue-500"></i> <span class="text-blue-400 font-bold" x-text="anuncio.visits || 0"></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 text-slate-400" x-show="anuncio.meli_date_created" title="Data de Criação">
                                    <i class="fas fa-calendar-plus w-3"></i> <span x-text="anuncio.meli_date_created"></span>
                                </div>
                                <div class="flex items-center gap-1 text-slate-500" x-show="anuncio.meli_last_updated" title="Última Atualização">
                                    <i class="fas fa-history w-3"></i> <span x-text="anuncio.meli_last_updated"></span>
                                </div>
                                <div class="flex flex-col mt-1 pt-1 border-t border-slate-600/50" x-show="anuncio.medidas && Object.keys(anuncio.medidas).length > 0">
                                    <span x-show="anuncio.medidas.peso" x-text="anuncio.medidas.peso" class="text-amber-500/80 font-bold" title="Peso"></span>
                                    <span x-show="anuncio.medidas.comprimento || anuncio.medidas.largura || anuncio.medidas.altura" 
                                          x-text="(anuncio.medidas.comprimento || '-') + 'x' + (anuncio.medidas.largura || '-') + 'x' + (anuncio.medidas.altura || '-')"
                                          class="text-slate-400" title="Comp/Larg/Alt"></span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-white" x-text="formatMoney(anuncio.preco)"></td>
                        <td class="px-4 py-3 text-right text-red-400" x-text="formatMoney(anuncio.custo || 0)"></td>
                        <td class="px-4 py-3 text-right text-amber-500/80" x-text="formatMoney(anuncio.taxas || 0)"></td>
                        <td class="px-4 py-3 text-right text-amber-500/80" x-text="formatMoney(anuncio.frete || 0)"></td>
                        <td class="px-4 py-3 text-right text-red-400/80" x-text="formatMoney(anuncio.imposto || 0)"></td>
                        <td class="px-4 py-3 text-right font-bold" :class="(anuncio.lucro || 0) >= 0 ? 'text-green-400' : 'text-red-400'" x-text="formatMoney(anuncio.lucro || 0)"></td>
                        <td class="px-4 py-3 text-right font-bold" :class="(anuncio.margem || 0) >= 0 ? 'text-green-400' : 'text-red-400'" x-text="(anuncio.margem || 0).toFixed(1) + '%'"></td>
                        <td class="px-4 py-3 text-center" :class="(anuncio.estoque || 0) > 0 ? 'text-green-400' : 'text-red-400'" x-text="anuncio.estoque || 0"></td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <template x-if="anuncio.product_linked">
                                    <div class="flex items-center gap-1">
                                        <div class="flex flex-col items-center">
                                            <span class="text-[10px] bg-indigo-500/20 text-indigo-400 px-2 py-0.5 rounded font-mono font-bold mb-1" x-text="anuncio.product_sku || 'N/A'"></span>
                                            <div class="flex items-center gap-1">
                                                <button @click="openVincular(anuncio)" class="p-1.5 rounded bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/30 transition-all" title="Trocar Vínculo">
                                                    <i class="fas fa-link text-xs"></i>
                                                </button>
                                                <button @click="desvincularProduto(anuncio)" class="p-1.5 rounded text-red-400 hover:bg-red-500/10" title="Desvincular">
                                                    <i class="fas fa-unlink text-xs"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!anuncio.product_linked">
                                    <div class="flex flex-col items-center gap-1">
                                        <button @click="openVincular(anuncio)" class="p-1.5 rounded bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/30 transition-all" title="Vincular a Produto">
                                            <i class="fas fa-link text-xs animate-pulse"></i>
                                        </button>
                                        <button @click="importAsProduct(anuncio)" class="text-[9px] text-emerald-500 hover:text-emerald-400 font-bold uppercase tracking-tighter" title="Importar como Novo Produto">
                                            + Importar
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <template x-if="anuncio.is_catalog && anuncio.marketplace === 'mercadolivre'">
                                    <a :href="getUrlConcorrentes(anuncio)" target="_blank" class="p-1.5 rounded text-amber-400 hover:bg-amber-500/10" title="Ver Concorrentes">
                                        <i class="fas fa-chart-line text-xs"></i>
                                    </a>
                                </template>
                                <template x-if="anuncio.marketplace === 'mercadolivre' && anuncio.is_catalog">
                                    <button @click="openRepricer(anuncio)" class="p-1.5 rounded" :class="anuncio.repricer_active ? 'text-indigo-400 bg-indigo-500/10' : 'text-slate-400 hover:bg-slate-700'" title="Re-preço">
                                        <i class="fas fa-robot text-xs"></i>
                                    </button>
                                </template>
                                <button @click="editAnuncio(anuncio)" class="p-1.5 rounded text-yellow-500 hover:bg-yellow-500/10" title="Editar">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                                <a :href="anuncio.url" target="_blank" class="p-1.5 rounded text-blue-500 hover:bg-blue-500/10" title="Ver no Marketplace">
                                    <i class="fas fa-external-link-alt text-xs"></i>
                                </a>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-1 rounded-full" :class="anuncio.status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'" x-text="anuncio.status === 'active' ? 'Ativo' : 'Inativo'"></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Empty -->
    <div x-show="!loading && anuncios.length === 0" class="text-center py-16">
        <div class="inline-flex flex-col items-center gap-3 px-8 py-6 bg-slate-800/60 border border-slate-700/50 rounded-2xl shadow-xl">
            <i class="fas fa-store text-4xl text-slate-600"></i>
            <p class="text-slate-400 font-bold">Nenhum anúncio encontrado</p>
            <p class="text-slate-500 text-xs">Tente alterar os filtros ou sincronizar os anúncios</p>
        </div>
    </div>

    <!-- Modal Vincular -->
    <div x-show="showVincularModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showVincularModal = false"></div>
        <div class="relative bg-slate-800 border border-slate-700/50 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] flex flex-col overflow-hidden">
            <div class="p-5 border-b border-slate-700/50 flex items-center justify-between bg-gradient-to-r from-indigo-500/5 to-transparent">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-yellow-500/10 border border-yellow-500/20 flex items-center justify-center text-yellow-500">
                        <i class="fas fa-link"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white leading-none mb-1">Vincular Produto</h3>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider" x-text="anuncioSelecionado?.titulo"></p>
                    </div>
                </div>
                <button @click="showVincularModal = false" class="text-slate-500 hover:text-white transition-colors p-2">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="p-5 flex flex-col flex-1 overflow-hidden gap-4">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                    <input type="text" 
                        x-model="searchProduto" 
                        @input.debounce.500ms="searchProdutos()"
                        placeholder="Buscar por nome ou SKU..." 
                        class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl pl-12 pr-4 py-3 text-sm text-white placeholder-slate-600 focus:border-yellow-500/50 focus:ring-1 focus:ring-yellow-500/20 outline-none transition-all">
                </div>

                <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                    <template x-for="produto in produtos" :key="produto.id">
                        <button @click="vincularProduto(produto.id)" 
                            class="w-full text-left p-3 mb-2 bg-slate-900/40 border border-slate-700/30 rounded-xl hover:border-indigo-500/50 hover:bg-indigo-500/5 transition-all group">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-slate-800 border border-slate-700 overflow-hidden flex items-center justify-center shrink-0">
                                    <template x-if="produto.foto">
                                        <img :src="produto.foto" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!produto.foto">
                                        <i class="fas fa-box text-slate-700 text-lg"></i>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-200 truncate group-hover:text-white transition-colors" x-text="produto.nome"></p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] bg-slate-800 border border-slate-700 px-1.5 py-0.5 rounded text-indigo-400 font-mono font-bold" x-text="produto.sku"></span>
                                        <span class="text-[10px] text-slate-500">|</span>
                                        <span class="text-[10px] text-slate-500">Preço: <span class="text-emerald-500 font-bold" x-text="formatMoney(produto.preco_venda)"></span></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[10px] font-black uppercase text-slate-500 mb-0.5">Estoque</div>
                                    <span class="text-xs font-black text-slate-300" x-text="produto.estoque"></span>
                                </div>
                            </div>
                        </button>
                    </template>
                    <div x-show="searchProduto && produtos.length === 0" class="text-center py-8 text-slate-400">
                        <i class="fas fa-search text-2xl mb-2"></i>
                        <p>Nenhum produto encontrado</p>
                    </div>
                    <div x-show="!searchProduto" class="text-center py-8 text-slate-400">
                        <i class="fas fa-keyboard text-2xl mb-2"></i>
                        <p>Digite para buscar produtos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Repricer -->
    <div x-show="showRepricerModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showRepricerModal = false"></div>
        <div class="relative bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-700 flex items-center justify-between bg-gradient-to-r from-indigo-500/10 to-transparent">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500 flex items-center justify-center text-white">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Re-preço Automático</h3>
                        <p class="text-xs text-slate-400">Automação de preços (Catálogo ML)</p>
                    </div>
                </div>
                <button @click="showRepricerModal = false" class="p-2 hover:bg-slate-700 rounded-lg text-slate-400">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex items-center justify-between p-3 bg-indigo-500/10 rounded-xl border border-indigo-500/20">
                    <div>
                        <div class="font-medium text-white">Motor Ativo</div>
                        <div class="text-xs text-slate-400">Sincroniza com concorrentes</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="repricerConfig.is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-600 peer-checked:bg-indigo-500 rounded-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-300 block mb-2">Estratégia</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors" 
                            :class="repricerConfig.strategy === 'igualar_menor' ? 'bg-indigo-500/10 border-indigo-500/30' : 'border-slate-600 hover:bg-slate-700'">
                            <input type="radio" x-model="repricerConfig.strategy" value="igualar_menor" class="sr-only">
                            <div class="flex-1">
                                <div class="font-medium text-white text-sm">Igualar ao menor</div>
                                <div class="text-xs text-slate-400">Fica sempre abaixo do concorrente mais barato</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors" 
                            :class="repricerConfig.strategy === 'valor_abaixo' ? 'bg-indigo-500/10 border-indigo-500/30' : 'border-slate-600 hover:bg-slate-700'">
                            <input type="radio" x-model="repricerConfig.strategy" value="valor_abaixo" class="sr-only">
                            <div class="flex-1">
                                <div class="font-medium text-white text-sm">Valor abaixo</div>
                                <div class="text-xs text-slate-400">Define um valor fixo abaixo do menor preço</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors" 
                            :class="repricerConfig.strategy === 'valor_acima' ? 'bg-indigo-500/10 border-indigo-500/30' : 'border-slate-600 hover:bg-slate-700'">
                            <input type="radio" x-model="repricerConfig.strategy" value="valor_acima" class="sr-only">
                            <div class="flex-1">
                                <div class="font-medium text-white text-sm">Valor acima</div>
                                <div class="text-xs text-slate-400">Define um valor fixo acima do menor preço</div>
                            </div>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-300 block mb-2">Diferença (R$)</label>
                    <input type="number" x-model="repricerConfig.offset_value" step="0.01"
                        class="w-full bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white focus:border-yellow-500 outline-none">
                    <p class="text-xs text-slate-500 mt-1">Negativo = mais barato que concorrentes | Positivo = mais caro</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-300 block mb-2">Margem Mínima</label>
                    <div class="flex gap-2">
                        <input type="number" x-model="repricerConfig.min_profit_margin" step="0.01" placeholder="0"
                            class="flex-1 bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white focus:border-yellow-500 outline-none">
                        <select x-model="repricerConfig.min_profit_type" class="bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white focus:border-yellow-500 outline-none">
                            <option value="percent">%</option>
                            <option value="value">R$</option>
                        </select>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Para garantir lucro mínimo por venda</p>
                </div>
                
                <!-- Filtros -->
                <div class="space-y-3 pt-2 border-t border-slate-700">
                    <label class="flex items-center gap-3 p-3 bg-slate-900/50 rounded-lg border border-slate-700 cursor-pointer hover:border-indigo-500/50 transition">
                        <input type="checkbox" x-model="repricerConfig.filter_full_only" class="rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800">
                        <div class="flex-1">
                            <div class="font-medium text-white text-sm">Apenas Full</div>
                            <div class="text-xs text-slate-400">Competir apenas com anúncios do Mercado Livre Full (fulfillment)</div>
                        </div>
                    </label>
                    
                    <div>
                        <label class="text-sm font-medium text-slate-300 block mb-2">Tipo de Anúncio do Concorrente</label>
                        <div class="flex gap-2">
                            <button type="button" 
                                @click="repricerConfig.filter_type = 'todos'"
                                :class="repricerConfig.filter_type === 'todos' ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-800 border-slate-700 text-slate-400'"
                                class="flex-1 py-2 px-3 rounded-lg border-2 text-sm font-medium transition-all">
                                Todos
                            </button>
                            <button type="button" 
                                @click="repricerConfig.filter_type = 'classic'"
                                :class="repricerConfig.filter_type === 'classic' ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-800 border-slate-700 text-slate-400'"
                                class="flex-1 py-2 px-3 rounded-lg border-2 text-sm font-medium transition-all">
                                <i class="fas fa-star mr-1"></i> Classic
                            </button>
                            <button type="button" 
                                @click="repricerConfig.filter_type = 'premium'"
                                :class="repricerConfig.filter_type === 'premium' ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-800 border-slate-700 text-slate-400'"
                                class="flex-1 py-2 px-3 rounded-lg border-2 text-sm font-medium transition-all">
                                <i class="fas fa-crown mr-1"></i> Premium
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Filtrar concorrentes por tipo de anúncio</p>
                    </div>
                </div>
                <button @click="saveRepricerConfig()" :disabled="savingRepricer"
                    class="w-full py-2.5 bg-indigo-500 hover:bg-indigo-400 text-white rounded-lg font-medium flex items-center justify-center gap-2">
                    <i x-show="savingRepricer" class="fas fa-spinner fa-spin"></i>
                    <span x-text="savingRepricer ? 'Salvando...' : 'Salvar Configuração'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function anunciosPage() {
    return {
        empresaId: localStorage.getItem('empresa_id') || '6',
        loading: false,
        syncing: false,
        actionsDropdownOpen: false,
        viewMode: 'cards',
        marketplace: '',
        statusFilter: '',
        tipoFilter: '',
        vinculoFilter: '',
        repricerFilter: '',
        search: '',
        anuncios: [],
        
        // Vincular modal
        showVincularModal: false,
        anuncioSelecionado: null,
        searchProduto: '',
        produtos: [],
        
        // Repricer modal
        showRepricerModal: false,
        repricerAnuncioId: null,
        repricerConfig: {
            is_active: false,
            strategy: 'igualar_menor',
            offset_value: 0,
            min_profit_margin: null,
            min_profit_type: 'percent',
            filter_full_only: false,
            filter_type: 'todos'
        },
        savingRepricer: false,
        
        editingSku: null,
        editingSkuValue: '',
        editingTitulo: null,
        editingTituloValue: '',
        savingField: false,
        
        init() {
            // Get empresa from localStorage or default
            const savedEmpresa = localStorage.getItem('empresa_id');
            this.empresaId = savedEmpresa ? parseInt(savedEmpresa) : 6;
            
            console.log('Initial empresaId:', this.empresaId);
            
            // Watch for local changes
            this.$watch('empresaId', () => {
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadAnuncios();
            });
            
            // Listen for empresa changes from the layout
            window.addEventListener('empresa-changed', (e) => {
                console.log('Empresa changed event:', e.detail);
                this.empresaId = parseInt(e.detail);
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadAnuncios();
            });
            
            this.initFromUrl();
            this.loadAnuncios();
        },

        initFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('marketplace')) this.marketplace = urlParams.get('marketplace');
            if (urlParams.has('status')) this.statusFilter = urlParams.get('status');
            if (urlParams.has('tipo')) this.tipoFilter = urlParams.get('tipo');
            if (urlParams.has('vinculo')) this.vinculoFilter = urlParams.get('vinculo');
            if (urlParams.has('repricer')) this.repricerFilter = urlParams.get('repricer');
            if (urlParams.has('search')) this.search = urlParams.get('search');
            // if (urlParams.has('page')) this.currentPage = parseInt(urlParams.get('page')); // Se houver paginação no futuro
        },

        updateUrlParams() {
            const params = new URLSearchParams();
            if (this.marketplace) params.set('marketplace', this.marketplace);
            if (this.statusFilter) params.set('status', this.statusFilter);
            if (this.tipoFilter) params.set('tipo', this.tipoFilter);
            if (this.vinculoFilter) params.set('vinculo', this.vinculoFilter);
            if (this.repricerFilter) params.set('repricer', this.repricerFilter);
            if (this.search) params.set('search', this.search);

            const newRelativePathQuery = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            history.replaceState(null, '', newRelativePathQuery);
        },
        
        async loadAnuncios() {
            this.loading = true;
            console.log('Loading anuncios for empresa:', this.empresaId);
            try {
                const params = new URLSearchParams({
                    empresa: this.empresaId,
                    marketplace: this.marketplace,
                    status: this.statusFilter,
                    tipo: this.tipoFilter,
                    vinculo: this.vinculoFilter,
                    repricer: this.repricerFilter,
                    search: this.search
                });
                
                const response = await fetch(`/api/anuncios?${params}`);
                console.log('Response status:', response.status);
                const data = await response.json();
                console.log('Data received:', data);
                this.anuncios = data.data || data;

                this.updateUrlParams();
            } catch (e) {
                console.error('Error:', e);
            }
            this.loading = false;
        },
        
        async syncAnuncios(marketplace = null) {
            this.syncing = true;
            try {
                const targetMarketplace = marketplace || this.marketplace || 'mercadolivre';
                const response = await fetch(`/api/anuncios/sync?empresa=${this.empresaId}&marketplace=${targetMarketplace}`, { 
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message || 'Sincronização concluída!');
                } else if (data.message) {
                    alert('Erro: ' + data.message);
                }
                
                await this.loadAnuncios();
            } catch (e) {
                console.error('Sync error:', e);
                alert('Erro na sincronização: ' + e.message);
            }
            this.syncing = false;
        },
        
        async vincularPorSku() {
            if (!confirm('Vincular produtos automaticamente pelo SKU? Apenas anúncios sem vínculo serão afetados.')) return;
            
            this.syncing = true;
            try {
                const response = await fetch(`/api/anuncios/vincular-por-sku?empresa=${this.empresaId}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    if (data.nao_encontrados > 0) {
                        console.log('SKUs não encontrados:', data.skus_nao_encontrados);
                    }
                    await this.loadAnuncios();
                } else {
                    alert(data.message || 'Erro ao vincular');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao vincular produtos por SKU');
            }
            this.syncing = false;
        },
        
        editAnuncio(anuncio) {
            window.location.href = `/anuncios/editar?id=${anuncio.id}`;
        },
        
        // Vincular methods
        async openVincular(anuncio) {
            this.anuncioSelecionado = anuncio;
            this.searchProduto = '';
            this.produtos = [];
            this.showVincularModal = true;
        },
        
        async searchProdutos() {
            if (!this.searchProduto || this.searchProduto.length < 2) {
                this.produtos = [];
                return;
            }
            try {
                const response = await fetch(`/api/anuncios/search-products?empresa=${this.empresaId}&q=${encodeURIComponent(this.searchProduto)}`);
                const data = await response.json();
                this.produtos = data;
            } catch (e) {
                console.error('Error searching products:', e);
            }
        },
        
        async vincularProduto(produtoId) {
            try {
                const response = await fetch(`/api/anuncios/${this.anuncioSelecionado.id}/vincular`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ produto_id: produtoId })
                });
                const data = await response.json();
                if (data.success) {
                    this.showVincularModal = false;
                    this.loadAnuncios();
                } else {
                    alert(data.message || 'Erro ao vincular');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao vincular produto');
            }
        },
        
        async desvincularProduto(anuncio) {
            if (!confirm('Desvincular este produto do anúncio?')) return;
            
            try {
                const response = await fetch(`/api/anuncios/${anuncio.id}/desvincular`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.loadAnuncios();
                } else {
                    alert(data.message || 'Erro ao desvincular');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao desvincular produto');
            }
        },
        
        // Importar como produto
        async importAsProduct(anuncio) {
            if (!confirm('Criar produto a partir deste anúncio?')) return;
            
            try {
                const response = await fetch(`/api/anuncios/${anuncio.id}/importar`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    alert('Produto criado com sucesso!');
                    this.loadAnuncios();
                } else {
                    alert(data.message || 'Erro ao importar');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao importar produto');
            }
        },
        
        // Concorrentes URL
        getUrlConcorrentes(anuncio) {
            if (anuncio.marketplace !== 'mercadolivre') return null;
            const jsonData = anuncio.json_data || {};
            const permalink = jsonData.permalink || '';
            const productId = jsonData.catalog_product_id || anuncio.external_id;
            
            if (permalink) {
                const match = permalink.match(/MLB-\d+-(.+?)(?:\?|_JM|$)/);
                if (match) {
                    let slug = match[1].replace(/-$/, '');
                    return `https://www.mercadolivre.com.br/${slug}/p/${productId}/s?`;
                }
            }
            if (anuncio.titulo) {
                let slug = anuncio.titulo.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').trim('-');
                return `https://www.mercadolivre.com.br/${slug}/p/${productId}/s?`;
            }
            return null;
        },
        
        // Copy methods
        copyToClipboard(text, message = 'Copiado!') {
            navigator.clipboard.writeText(text).then(() => {
                alert(message);
            }).catch(() => {
                alert('Erro ao copiar');
            });
        },
        
        // Edit SKU
        startEditSku(anuncio) {
            this.editingSku = anuncio.id;
            this.editingSkuValue = anuncio.sku || '';
        },
        
        async saveSku(anuncio) {
            if (!this.editingSkuValue.trim()) {
                alert('SKU não pode estar vazio');
                return;
            }
            
            this.savingField = true;
            try {
                const response = await fetch(`/api/anuncios/${anuncio.id}`, {
                    method: 'PUT',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ sku: this.editingSkuValue })
                });
                const data = await response.json();
                if (data.success) {
                    anuncio.sku = this.editingSkuValue;
                    this.editingSku = null;
                    if (data.meli_synced) {
                        alert('SKU atualizado no Mercado Livre!');
                    } else if (data.meli_error) {
                        alert('SKU atualizado localmente, mas erro ao sync ML: ' + data.meli_error);
                    } else {
                        alert('SKU atualizado!');
                    }
                } else {
                    alert(data.message || 'Erro ao atualizar');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao atualizar SKU');
            }
            this.savingField = false;
        },
        
        cancelEditSku() {
            this.editingSku = null;
            this.editingSkuValue = '';
        },
        
        // Edit Título
        startEditTitulo(anuncio) {
            this.editingTitulo = anuncio.id;
            this.editingTituloValue = anuncio.titulo || '';
        },
        
        async saveTitulo(anuncio) {
            if (!this.editingTituloValue.trim()) {
                alert('Título não pode estar vazio');
                return;
            }
            
            this.savingField = true;
            try {
                const response = await fetch(`/api/anuncios/${anuncio.id}`, {
                    method: 'PUT',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ titulo: this.editingTituloValue })
                });
                const data = await response.json();
                if (data.success) {
                    anuncio.titulo = this.editingTituloValue;
                    this.editingTitulo = null;
                    if (data.meli_synced) {
                        alert('Título atualizado no Mercado Livre!');
                    } else if (data.meli_error) {
                        alert('Título atualizado localmente, mas erro ao sync ML: ' + data.meli_error);
                    } else {
                        alert('Título atualizado!');
                    }
                } else {
                    alert(data.message || 'Erro ao atualizar');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao atualizar título');
            }
            this.savingField = false;
        },
        
        cancelEditTitulo() {
            this.editingTitulo = null;
            this.editingTituloValue = '';
        },
        
        // Repricer methods
        async openRepricer(anuncio) {
            this.repricerAnuncioId = anuncio.id;
            this.repricerConfig = {
                is_active: false,
                strategy: 'igualar_menor',
                offset_value: 0,
                min_profit_margin: null,
                min_profit_type: 'percent',
                filter_full_only: false,
                filter_type: 'todos'
            };
            try {
                const response = await fetch(`/api/anuncios/${anuncio.id}/repricer`);
                const data = await response.json();
                if (data) {
                    let filterType = 'todos';
                    if (data.filter_classic_only) filterType = 'classic';
                    else if (data.filter_premium_only) filterType = 'premium';
                    
                    this.repricerConfig = {
                        is_active: data.is_active ?? false,
                        strategy: data.strategy ?? 'igualar_menor',
                        offset_value: data.offset_value ?? 0,
                        min_profit_margin: data.min_profit_margin ?? null,
                        min_profit_type: data.min_profit_type ?? 'percent',
                        filter_full_only: data.filter_full_only ?? false,
                        filter_type: filterType
                    };
                }
            } catch (e) {
                console.error('Error loading repricer config:', e);
            }
            this.showRepricerModal = true;
        },
        
        async saveRepricerConfig() {
            this.savingRepricer = true;
            try {
                const config = { ...this.repricerConfig };
                config.filter_classic_only = config.filter_type === 'classic';
                config.filter_premium_only = config.filter_type === 'premium';
                
                const response = await fetch(`/api/anuncios/${this.repricerAnuncioId}/repricer`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(config)
                });
                const data = await response.json();
                if (data.success) {
                    this.showRepricerModal = false;
                    this.loadAnuncios();
                } else {
                    alert(data.message || 'Erro ao salvar');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Erro ao salvar configuração');
            }
            this.savingRepricer = false;
        },
        
        get activeCount() {
            return this.anuncios.filter(a => a.status === 'active').length;
        },
        
        get inactiveCount() {
            return this.anuncios.filter(a => a.status !== 'active').length;
        },
        
        get noStockCount() {
            return this.anuncios.filter(a => !a.estoque || a.estoque <= 0).length;
        },
        
        get lucroMedio() {
            if (this.anuncios.length === 0) return 0;
            const totalLucro = this.anuncios.reduce((sum, a) => sum + (a.lucro || 0), 0);
            return totalLucro / this.anuncios.length;
        },
        
        formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
        }
    }
}
</script>
@endsection
