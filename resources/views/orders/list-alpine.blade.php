@extends('layouts.alpine')

@section('title', 'Pedidos - NexusEcom')
@section('header_title', 'Pedidos')

@section('content')
<div x-data="ordersPage()" x-init="init()">
    <!-- Premium Dashboard Header -->
    <div class="space-y-4 mb-6">
        <!-- Top Row: Title & Global Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tighter uppercase italic flex items-center gap-3">
                    <span class="bg-indigo-600 w-2 h-8 rounded-full"></span>
                    Pedidos <span class="text-indigo-500">Vendas</span>
                </h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] ml-5">Marketplace Intelligence Dashboard</p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Sync Progress Indicator -->
                <div x-show="syncing" class="flex items-center gap-2 px-3 py-2 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-indigo-400 text-xs font-bold animate-pulse shadow-inner">
                    <i class="fas fa-circle-notch fa-spin"></i>
                    <span x-text="syncStatus"></span>
                </div>

                <!-- Sync Menu -->
                <div class="relative">
                    <button @click="syncDropdownOpen = !syncDropdownOpen" :disabled="syncing" 
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl flex items-center gap-3 text-sm text-white font-bold transition-all shadow-lg active:scale-95 disabled:opacity-50">
                        <i class="fas fa-sync" :class="syncing ? 'fa-spin' : ''"></i>
                        <span>Sincronizar</span>
                        <i class="fas fa-chevron-down text-[10px] text-slate-500"></i>
                    </button>
                    
                    <div x-show="syncDropdownOpen" @click.away="syncDropdownOpen = false" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="absolute right-0 mt-2 w-72 bg-black border border-slate-700/50 backdrop-blur-xl rounded-2xl shadow-2xl z-50 overflow-hidden py-1">
                        
                        <div x-show="!hasMeliIntegration && !hasAmazonIntegration" class="px-4 py-3 text-xs text-slate-500 flex items-center gap-2 italic">
                            <i class="fas fa-exclamation-triangle"></i> Sem integração ativa
                        </div>

                        <div x-show="hasMeliIntegration" class="contents">
                            <button @click="syncOrders('mercadolivre'); syncDropdownOpen = false" 
                                class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5 hover:text-white flex items-center gap-3 transition-colors">
                                <div class="w-8 h-8 rounded-lg bg-yellow-500/10 flex items-center justify-center">
                                    <i class="fab fa-mercadolivre text-yellow-400"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-bold">Mercado Livre</span>
                                    <span class="text-[10px] text-slate-500">Sincronizar pedidos recentes</span>
                                </div>
                            </button>
                            
                            <button @click="syncOrders('mercadolivre', true); syncDropdownOpen = false" 
                                class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5 hover:text-white flex items-center gap-3 transition-colors border-t border-white/5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                                    <i class="fas fa-calendar-alt text-indigo-400"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-bold">Por Período (Meli)</span>
                                    <span class="text-[10px] text-slate-500">Data personalizada (Max 31d)</span>
                                </div>
                            </button>
                        </div>

                        <div x-show="hasAmazonIntegration" class="contents">
                            <button @click="syncOrders('amazon'); syncDropdownOpen = false" 
                                class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5 hover:text-white flex items-center gap-3 transition-colors border-t border-white/5">
                                <div class="w-8 h-8 rounded-lg bg-orange-500/10 flex items-center justify-center">
                                    <i class="fab fa-amazon text-orange-400"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-bold">Amazon</span>
                                    <span class="text-[10px] text-slate-500">Sincronizar pedidos recentes</span>
                                </div>
                            </button>

                            <button @click="syncOrders('amazon', true); syncDropdownOpen = false" 
                                class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5 hover:text-white flex items-center gap-3 transition-colors border-t border-white/5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                                    <i class="fas fa-calendar-alt text-indigo-400"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-bold">Por Período (Amazon)</span>
                                    <span class="text-[10px] text-slate-500">Data personalizada (Max 31d)</span>
                                </div>
                            </button>
                        </div>

                        <div x-show="hasMeliIntegration || hasAmazonIntegration" class="contents">
                            <button @click="recalculatePeriod(); syncDropdownOpen = false" 
                                class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5 hover:text-white flex items-center gap-3 transition-colors border-t border-white/5">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                    <i class="fas fa-calculator text-amber-400"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-bold">Recalcular Período</span>
                                    <span class="text-[10px] text-slate-500">Atualizar lucros locais</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Total Pedidos -->
            <div class="bg-gradient-to-br from-indigo-600/20 to-transparent border border-indigo-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Pedidos</span>
                    <i class="fas fa-shopping-bag text-indigo-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="globalStats.total_pedidos"></div>
                <div class="mt-1 h-1 w-12 bg-indigo-500/50 rounded-full"></div>
            </div>

            <!-- Faturamento -->
            <div class="bg-gradient-to-br from-emerald-600/20 to-transparent border border-emerald-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Faturamento</span>
                    <i class="fas fa-dollar-sign text-emerald-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="formatMoney(totalValue)"></div>
                <div class="mt-1 h-1 w-12 bg-emerald-500/50 rounded-full"></div>
            </div>

            <!-- Lucro -->
            <div class="bg-gradient-to-br from-slate-800 to-transparent border border-slate-700/50 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black uppercase tracking-widest" :class="totalProfit >= 0 ? 'text-green-400' : 'text-red-400'">Lucro</span>
                    <i class="fas fa-chart-line text-slate-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white" x-text="formatMoney(totalProfit)"></div>
                <div class="mt-1 h-1 w-12 rounded-full" :class="totalProfit >= 0 ? 'bg-green-500/50' : 'bg-red-500/50'"></div>
            </div>

            <!-- Selecionados Contextual -->
            <div class="col-span-2 lg:col-span-2 grid grid-cols-2 gap-3">
                <div class="bg-slate-900/60 border border-indigo-500/30 rounded-2xl p-4 shadow-xl border-dashed">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black text-indigo-300 uppercase tracking-widest">Selecionados</span>
                        <i class="fas fa-check-double text-indigo-400"></i>
                    </div>
                    <div class="text-2xl font-black text-white" x-text="selectedCount"></div>
                </div>
                <div class="bg-slate-900/60 border border-indigo-500/30 rounded-2xl p-4 shadow-xl border-dashed">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black text-indigo-300 uppercase tracking-widest">Valor Seleção</span>
                        <i class="fas fa-calculator text-indigo-400"></i>
                    </div>
                    <div class="text-2xl font-black text-indigo-400" x-text="formatMoney(selectedValue)"></div>
                </div>
            </div>
        </div>

        <!-- Filters & Control Bar -->
        <div class="relative">
            <div class="bg-slate-800/80 backdrop-blur-md border border-slate-700/50 rounded-2xl p-3 shadow-2xl flex flex-wrap items-center gap-3 transition-all">
                
                <!-- Search -->
                <div class="flex-1 min-w-[200px] relative group">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="text" x-model="search" @input.debounce.300ms="loadOrders()" 
                        placeholder="Pesquisar venda, cliente ou produto..."
                        class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 focus:outline-none transition-all">
                </div>

                <!-- Date Range -->
                <div class="flex items-center bg-slate-900/50 border border-slate-700/50 rounded-xl px-2 gap-2">
                    <input type="date" x-model="dataDe" @change="loadOrders()" class="bg-transparent border-none py-2 text-xs text-slate-300 focus:ring-0 outline-none">
                    <span class="text-slate-600 font-bold text-[10px]">ATÉ</span>
                    <input type="date" x-model="dataAte" @change="loadOrders()" class="bg-transparent border-none py-2 text-xs text-slate-300 focus:ring-0 outline-none">
                </div>

                <!-- Category Filters (Scrollable on small screens) -->
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 md:pb-0">
                    <select x-model="status" @change="loadOrders()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-300 focus:ring-2 focus:ring-indigo-500/50 outline-none appearance-none cursor-pointer">
                        <option value="" class="bg-black">Status Pedido</option>
                        <option value="paid" class="bg-black">✅ Pago</option>
                        <option value="pending" class="bg-black">⏳ Pendente</option>
                        <option value="shipped" class="bg-black">🚚 Enviado</option>
                        <option value="delivered" class="bg-black">🏁 Entregue</option>
                        <option value="canceled" class="bg-black">❌ Cancelado</option>
                    </select>

                    <select x-model="statusEnvio" @change="loadOrders()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-400 focus:ring-2 focus:ring-indigo-500/50 outline-none appearance-none cursor-pointer">
                        <option value="" class="bg-black">Status Envio</option>
                        <option value="pending" class="bg-black">🟡 Aguardando</option>
                        <option value="shipped" class="bg-black">🔵 Enviado</option>
                        <option value="delivered" class="bg-black">🟢 Entregue</option>
                    </select>

                    <select x-model="logistics" @change="loadOrders()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-400 focus:ring-2 focus:ring-indigo-500/50 outline-none appearance-none cursor-pointer">
                        <option value="" class="bg-black">Logística</option>
                        <option value="me2" class="bg-black">Mercado Envios</option>
                        <option value="fulfillment" class="bg-black">🚀 Full</option>
                        <option value="classic" class="bg-black">🚚 Classic</option>
                    </select>

                    <select x-model="marketplace" @change="loadOrders()" class="bg-black border border-slate-700/50 rounded-xl px-3 py-2 text-xs text-slate-400 focus:ring-2 focus:ring-indigo-500/50 outline-none appearance-none cursor-pointer">
                        <option value="" class="bg-black">Marketplace</option>
                        <option value="mercadolivre" class="bg-black">🤝 Meli</option>
                        <option value="amazon" class="bg-black">📦 Amazon</option>
                        <option value="bling" class="bg-black">💹 Bling</option>
                    </select>
                </div>

                <!-- Sort -->
                <div class="flex items-center bg-black border border-slate-700/50 rounded-xl px-3 h-[42px] gap-2">
                    <i class="fas fa-sort text-slate-500 text-xs"></i>
                    <select x-model="sortBy" @change="loadOrders()" class="bg-transparent border-none py-0 text-xs text-white font-bold focus:ring-0 outline-none cursor-pointer">
                        <option value="data_compra" class="bg-black border-none">📅 Data</option>
                        <option value="valor_total" class="bg-black border-none">💰 Valor</option>
                        <option value="lucro" class="bg-black border-none">📈 Lucro</option>
                        <option value="lucro_percent" class="bg-black border-none">📊 Lucro %</option>
                    </select>
                    <button @click="sortDir = (sortDir === 'asc' ? 'desc' : 'asc'); loadOrders()" class="text-indigo-400 hover:text-indigo-300 transition-colors">
                        <i class="fas" :class="sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                    </button>
                </div>
            </div>

            <!-- Selection Overlay (Active when orders are selected) -->
            <template x-if="selectedCount > 0">
                <div x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="absolute inset-0 bg-indigo-600 rounded-2xl flex items-center justify-between px-6 shadow-2xl z-10 border border-indigo-400/50">
                    <div class="flex items-center gap-4">
                        <i class="fas fa-check-circle text-2xl text-white"></i>
                        <span class="text-white font-black text-lg" x-text="selectedCount + ' Pedidos Selecionados'"></span>
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs text-white font-bold" x-text="formatMoney(selectedValue)"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="printSelectedLabels()" class="px-5 py-2.5 bg-white text-indigo-600 rounded-xl font-black text-sm hover:bg-slate-100 transition-all flex items-center gap-2 shadow-lg active:scale-95">
                            <i class="fas fa-print"></i> Imprimir Etiquetas
                        </button>
                        <button @click="selectedOrders = []" class="p-2.5 bg-indigo-700 hover:bg-indigo-800 text-white rounded-xl transition-all">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-center py-12">
        <i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i>
    </div>

    <!-- Orders List -->
    <div x-show="!loading" class="space-y-4">
        <!-- Header da Lista -->
        <div class="flex items-center gap-2 px-3 py-2 bg-slate-800/80 rounded-lg text-xs font-semibold text-slate-300 border border-slate-700">
            <input type="checkbox" 
                @change="toggleAll()" 
                :checked="selectedOrders.length > 0 && selectedOrders.length === orders.length"
                class="rounded bg-slate-900 border-slate-600 focus:ring-indigo-500"
            >
            <span class="flex-1 ml-2">Detalhes da Venda</span>
            <span class="w-48 text-center hidden lg:block">Valores e Custos</span>
            <span class="w-32 text-center hidden lg:block">Status Envio</span>
            <span class="w-24 text-center">Ações</span>
        </div>
        
        <template x-for="order in groupedOrders" :key="order.id">
            <div class="bg-slate-800 rounded-xl border border-slate-700/60 overflow-hidden shadow-lg hover:border-slate-600 transition-colors" 
                 :class="selectedOrders.includes(order.id) ? 'ring-2 ring-indigo-500 border-transparent' : ''">
                
                <!-- TOP HEADER BAR: MARKETPLACE & IDS -->
                <div class="bg-slate-900/50 px-3 py-2 flex flex-wrap justify-between items-center border-b border-slate-700/50">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" 
                            :checked="selectedOrders.includes(order.id)"
                            @change="toggleOrder(order.id)"
                            class="rounded bg-slate-900 border-slate-600 focus:ring-indigo-500"
                        >
                        <div class="flex items-center gap-2">
                            <i :class="getMarketplaceIcon(order.marketplace)" class="text-sm" :class="getMarketplaceColorOnlyText(order.marketplace)"></i>
                            <span class="font-bold text-slate-200 text-sm">Venda <span class="text-indigo-400" x-text="'#' + (order.pedido_id || order.id)"></span></span>
                            <template x-if="order.pack_id">
                                <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-fuchsia-500/20 text-fuchsia-300 border border-fuchsia-500/30 font-medium tracking-wide flex items-center gap-1" title="Pertence a um Carrinho de Compras do Mercado Livre">
                                    <i class="fas fa-shopping-cart text-[9px]"></i> <span x-text="order.pack_id"></span>
                                </span>
                            </template>
                        </div>
                        <span class="hidden md:inline text-xs text-slate-500">|</span>
                        <div class="hidden md:flex items-center gap-2 text-xs text-slate-400">
                            <i class="far fa-calendar-alt"></i>
                            <span x-text="formatDate(order.data_compra)"></span>
                            <span x-show="order.data_pagamento" class="text-slate-600 flex items-center gap-1"><i class="fas fa-chevron-right text-[8px]"></i> Pago: <span class="text-slate-400" x-text="formatDate(order.data_pagamento)"></span></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-2 md:mt-0">
                        <template x-if="order.nfe_vinculada">
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center gap-1 font-medium">
                                <i class="fas fa-file-invoice"></i> NF-e <span x-text="order.nfe_vinculada.numero"></span>
                            </span>
                        </template>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium shadow-sm border border-black/20" :class="getStatusClass(order.status)" x-text="order.status.toUpperCase()"></span>
                    </div>
                </div>

                <!-- MAIN CARD BODY -->
                <div class="p-3 flex flex-col lg:flex-row gap-4">
                    
                    <!-- LEFT COL: BUYER & PRODUCT DETAILS -->
                    <div class="flex-1 flex flex-col gap-3 min-w-0">
                        
                        <!-- BUYER INFO GRID -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 bg-slate-900/30 p-2.5 rounded-lg border border-slate-700/30">
                            <div class="flex flex-col gap-1 text-xs">
                                <div class="text-slate-500 font-medium mb-0.5 text-[10px] uppercase tracking-wider">Comprador</div>
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <i class="fas fa-user text-slate-500 w-3 text-center"></i>
                                    <span class="font-semibold text-slate-200 truncate" x-text="order.comprador_nome || 'Não identificado'"></span>
                                    <span x-show="order.comprador_apelido" class="px-1.5 py-0.5 bg-slate-800 rounded border border-slate-700/50 text-[10px] text-indigo-300 font-medium" x-text="'@' + order.comprador_apelido"></span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-400 mt-0.5">
                                    <i class="fas fa-id-card text-slate-500 w-3 text-center"></i>
                                    <span class="font-mono text-[11px]" x-text="order.comprador_cpf || order.comprador_cnpj || 'Doc. Indisponível'"></span>
                                    <button @click="copyToClipboard(order.comprador_cpf || order.comprador_cnpj, 'Documento')" class="text-indigo-400 hover:text-indigo-300 ml-1"><i class="fas fa-copy"></i></button>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-400 mt-0.5" x-show="order.telefone">
                                    <i class="fab fa-whatsapp text-slate-500 w-3 text-center"></i>
                                    <span x-text="order.telefone"></span>
                                </div>
                            </div>
                            
                            <div class="flex flex-col gap-1 text-xs">
                                <div class="text-slate-500 font-medium mb-0.5 text-[10px] uppercase tracking-wider">Endereço de Entrega</div>
                                <div class="flex items-start gap-1.5 text-slate-300 leading-tight">
                                    <i class="fas fa-map-marker-alt text-slate-500 w-3 text-center mt-0.5"></i>
                                    <div class="flex-1">
                                        <div class="truncate max-w-[250px] font-medium" x-text="order.endereco"></div>
                                        <div class="text-slate-400 mt-0.5">
                                            <span x-text="order.cidade"></span> <span x-show="order.estado" x-text="'- ' + order.estado"></span> 
                                            <span x-show="order.cep" class="ml-1 text-indigo-300 font-mono text-[10px]" x-text="'CEP: ' + order.cep"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ITEMS BLOCK -->
                        <div class="flex flex-col gap-2">
                            <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider ml-1">Produtos</span>
                            <div class="space-y-2">
                                <template x-for="(item, idx) in order.itens" :key="(item.sku || 'nosku') + '_' + idx">
                                    <div class="flex items-center gap-3 bg-slate-800 rounded-lg p-2 border border-slate-700 hover:border-slate-600 transition-colors">
                                        <div class="w-12 h-12 flex-shrink-0 bg-slate-900 rounded overflow-hidden shadow-inner border border-slate-700/50 flex items-center justify-center">
                                            <img x-show="item.thumbnail" :src="item.thumbnail" class="w-full h-full object-cover text-[8px] text-slate-600" alt="img">
                                            <i x-show="!item.thumbnail" class="fas fa-box text-slate-500"></i>
                                        </div>
                                        <div class="flex-1 min-w-0 flex flex-col justify-between">
                                            <div class="flex items-start justify-between gap-2">
                                                <a :href="item.item_id ? 'https://produto.mercadolivre.com.br/MLB-' + item.item_id.replace('MLB', '') : '#'" target="_blank" 
                                                   class="text-xs font-semibold text-slate-200 hover:text-indigo-400 line-clamp-1" title="Ver no Mercado Livre">
                                                   <span x-text="item.titulo_reduzido || item.titulo || 'Produto sem título'"></span>
                                                   <i class="fas fa-external-link-alt ml-1 text-[9px] text-slate-500"></i>
                                                </a>
                                                
                                                <button x-show="!item.is_linked" @click="openLinkModal(order, item)" class="flex-shrink-0 animate-pulse hover:animate-none ml-auto text-[10px] px-2 py-0.5 rounded-full bg-red-500/10 border border-red-500/30 text-red-400 flex items-center gap-1 font-bold tracking-wide hover:bg-red-500 hover:text-white transition-all shadow shadow-red-500/20 cursor-pointer">
                                                    <i class="fas fa-exclamation-triangle"></i> Associar
                                                </button>
                                            </div>
                                            <div class="flex items-center gap-3 mt-1.5 text-[11px]">
                                                <div class="flex items-center gap-1.5 bg-slate-900 px-2 py-0.5 rounded text-indigo-300 border border-indigo-500/20 shadow-sm">
                                                    <span class="font-mono" x-text="item.sku || 'S/ SKU'"></span>
                                                    <button @click="copyToClipboard(item.sku, 'SKU')" class="hover:text-indigo-100 ml-0.5"><i class="fas fa-copy text-[10px]"></i></button>
                                                </div>
                                                <span class="text-slate-500 font-mono text-[10px]" x-show="item.item_id" x-text="'MLB' + item.item_id.replace('MLB', '')"></span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end justify-center pr-2">
                                            <span class="text-[13px] font-black text-emerald-400" x-text="formatMoney(item.preco_unitario)"></span>
                                            <span class="text-[11px] px-1.5 py-0.5 bg-slate-700/50 rounded text-slate-300 font-medium mt-1" x-text="'Qtd: ' + item.quantidade"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                    
                    <!-- DIVIDER ON DESKTOP -->
                    <div class="hidden lg:block w-px bg-slate-700/50 my-2"></div>

                    <!-- RIGHT COL: FINANCIALS & STATUS -->
                    <div class="flex flex-col gap-3 w-full lg:w-[220px] flex-shrink-0">
                        
                        <!-- FINANCIAL SUMMARY (Mercado Turbo style) -->
                        <div class="bg-slate-900/40 rounded-lg border border-slate-700/50 p-3 h-full flex flex-col">
                            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2.5 pb-2 border-b border-slate-700/50 text-center">Resumo Financeiro</div>
                            
                            <div class="flex flex-col gap-2 text-xs flex-1 justify-center">
                                <div class="flex justify-between items-center group">
                                    <span class="text-slate-400 group-hover:text-slate-300">Valor Venda</span>
                                    <span class="font-bold text-slate-200 text-[13px]" x-text="formatMoney(order.valor_produtos)"></span>
                                </div>
                                <div class="flex justify-between items-center group" title="Custo do Produto (Média)">
                                    <span class="text-slate-400 group-hover:text-slate-300">Custo Produto</span>
                                    <span class="text-amber-500 font-medium" x-text="'-' + formatMoney(order.custo_total)"></span>
                                </div>
                                <div class="flex justify-between items-center group" title="Tarifa do Marketplace">
                                    <div class="flex items-center gap-1.5 text-slate-400 group-hover:text-slate-300">
                                        Tarifa ML
                                        <span class="text-[9px] bg-slate-800 border border-slate-700 px-1 py-px rounded font-mono" x-show="order.taxa_platform > 0" x-text="(Math.round((order.taxa_platform / (order.valor_produtos || 1)) * 100) + '%')"></span>
                                    </div>
                                    <span class="text-red-400 font-medium" x-text="'-' + formatMoney(order.taxa_platform)"></span>
                                </div>
                                <div class="flex justify-between items-center group" x-show="order.taxa_pagamento > 0" title="Taxa de Pagamento">
                                    <div class="flex items-center gap-1.5 text-slate-400 group-hover:text-slate-300">
                                        Taxas Pgto
                                    </div>
                                    <span class="text-red-400 font-medium" x-text="'-' + formatMoney(order.taxa_pagamento)"></span>
                                </div>
                                <div class="flex justify-between items-center group" title="Imposto/Tributação NFe">
                                    <div class="flex items-center gap-1.5 text-slate-400 group-hover:text-slate-300">
                                        Imposto/Tributos
                                        <span class="text-[9px] bg-slate-800 border border-slate-700 px-1 py-px rounded font-mono" x-show="order.aliquota_imposto > 0" x-text="order.aliquota_imposto + '%'"></span>
                                    </div>
                                    <span class="text-red-400 font-medium" x-text="'-' + formatMoney(order.valor_imposto)"></span>
                                </div>
                                <div class="flex justify-between items-center group" title="Custo do Frete">
                                    <span class="text-slate-400 group-hover:text-slate-300">Custo Frete</span>
                                    <span class="text-red-400 font-medium" x-text="'-' + formatMoney(order.valor_frete)"></span>
                                </div>
                                
                                <div class="mt-auto pt-3">
                                    <div class="bg-slate-900 border border-slate-700 rounded-lg p-2.5 flex justify-between items-center shadow-inner">
                                        <span class="text-xs font-black text-slate-300 uppercase tracking-widest">Lucro</span>
                                        <div class="flex flex-col items-end">
                                            <span class="text-[15px] font-black drop-shadow-sm" :class="(order.lucro || 0) >= 0 ? 'text-emerald-400' : 'text-red-400'" x-text="formatMoney(order.lucro || 0)"></span>
                                            <span class="text-[10px] mt-0.5 font-bold" :class="(order.lucro || 0) >= 0 ? 'text-emerald-500/80' : 'text-red-500/80'" x-text="order.lucro_percent + '% margem'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- DIVIDER ON DESKTOP -->
                    <div class="hidden lg:block w-px bg-slate-700/50 my-2"></div>

                    <!-- FAR RIGHT: LOGISTICS & ACTIONS -->
                    <div class="flex flex-col justify-between w-full lg:w-36 flex-shrink-0 gap-3">
                        
                        <!-- SHIPPING STATUS -->
                        <div class="flex flex-col items-center justify-center text-center gap-2 bg-slate-900/30 p-2.5 rounded-lg border border-slate-700/30 relative">
                            
                            <span class="absolute top-0 right-0 left-0 h-1 rounded-t-lg opacity-50 block" :class="order.status_envio === 'delivered' ? 'bg-emerald-500' : (order.status_envio === 'shipped' ? 'bg-blue-500' : 'bg-yellow-500')"></span>

                            <span class="text-[10px] px-2.5 py-1 rounded-full w-full font-bold shadow-sm uppercase tracking-wider mt-1" :class="getLogisticsClass(order.logistics?.mode)">
                                <i class="fas fa-truck-fast mr-1"></i> <span x-text="getLogisticsLabel(order.logistics?.mode)"></span>
                            </span>
                            
                            <div class="flex flex-col gap-1 my-2">
                                <span class="text-sm font-black uppercase tracking-wider drop-shadow-sm" :class="order.status_envio === 'delivered' ? 'text-emerald-400' : (order.status_envio === 'shipped' ? 'text-blue-400' : 'text-yellow-400')" x-text="(order.status_envio === 'delivered' ? 'Entregue' : (order.status_envio === 'shipped' ? 'A Caminho' : 'Aguardando'))"></span>
                                <span x-show="order.codigo_rastreamento" class="text-[11px] bg-slate-900 border border-slate-600 px-2 py-1 rounded font-mono mt-1 cursor-pointer hover:bg-slate-700 transition-colors text-slate-300" @click="copyToClipboard(order.codigo_rastreamento, 'Rastreio')" title="Copiar Rastreio">
                                    <i class="fas fa-barcode text-slate-500 mr-1"></i><span x-text="order.codigo_rastreamento"></span>
                                </span>
                            </div>
                            
                            <a x-show="order.url_rastreamento" :href="order.url_rastreamento" target="_blank" class="mt-1 text-[10px] text-indigo-400 hover:text-indigo-300 hover:underline font-medium bg-indigo-500/10 px-2 py-1 rounded w-full border border-indigo-500/20 shadow-sm">
                                Rastrear <i class="fas fa-external-link-alt ml-1 text-[9px]"></i>
                            </a>
                        </div>
                        
                        <!-- ACTIONS -->
                        <div class="flex items-center gap-1.5 w-full relative mt-auto">
                            <!-- primary action -->
                            <template x-if="order.nfe_vinculada">
                                <button @click="window.open('/api/orders/' + order.id + '/etiqueta?empresa_id=' + empresaId, '_blank')" class="flex-1 bg-indigo-600 hover:bg-indigo-500 border-t border-indigo-400/30 text-white text-xs py-2 rounded-lg transition-colors shadow shadow-indigo-500/20 font-bold flex items-center justify-center gap-1.5">
                                    <i class="fas fa-print"></i> Etiqueta
                                </button>
                            </template>
                            <template x-if="!order.nfe_vinculada">
                                <button @click="window.open('/api/orders/' + order.id + '/etiqueta-meli?empresa_id=' + empresaId, '_blank')" class="flex-1 bg-gradient-to-t from-yellow-600/30 to-yellow-500/20 hover:from-yellow-500/40 hover:to-yellow-400/30 border border-yellow-500/40 text-yellow-400 text-xs py-2 rounded-lg transition-colors shadow-sm font-bold flex items-center justify-center gap-1.5 cursor-pointer">
                                    <i class="fas fa-tag"></i> Etiq. ML
                                </button>
                            </template>
                            
                            <!-- Dropdown Trigger -->
                            <button @click="toggleActionsMenu(order.id)" class="px-2.5 py-2 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-lg transition-colors shadow-sm cursor-pointer border border-slate-600" :class="actionsMenuOpen === order.id ? 'bg-slate-600 ring-2 ring-slate-400' : ''">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="actionsMenuOpen === order.id" @click.away="actionsMenuOpen = null"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                class="absolute right-0 bottom-full mb-2 w-52 bg-slate-800 border border-slate-600 rounded-xl shadow-2xl z-50 overflow-hidden text-xs">
                                
                                <div class="px-3 py-2 bg-slate-900/80 text-slate-400 font-bold uppercase text-[10px] border-b border-slate-700 tracking-wider">Impressão NFe</div>
                                <a x-show="order.nfe_vinculada" :href="'/api/orders/' + order.id + '/danfe?empresa_id=' + empresaId" target="_blank" class="block px-4 py-2 text-slate-200 hover:bg-slate-700 hover:text-white transition-colors flex items-center gap-2 cursor-pointer">
                                    <i class="fas fa-file-pdf w-4 text-red-400"></i> Imprimir DANFE A4
                                </a>
                                <a x-show="order.nfe_vinculada" :href="'/api/orders/' + order.id + '/danfe-simplificada?empresa_id=' + empresaId" target="_blank" class="block px-4 py-2 text-slate-200 hover:bg-slate-700 hover:text-white transition-colors flex items-center gap-2 cursor-pointer">
                                    <i class="fas fa-receipt w-4 text-emerald-400"></i> Imprimir DANFE Simples
                                </a>
                                
                                <div class="px-3 py-2 bg-slate-900/80 text-slate-400 font-bold uppercase text-[10px] border-y border-slate-700 tracking-wider mt-1">Gerenciamento</div>
                                <a :href="'https://www.mercadolivre.com.br/vendas/' + order.pedido_id + '/detalhe'" target="_blank" class="block px-4 py-2 text-slate-200 hover:bg-slate-700 hover:text-white transition-colors flex items-center gap-2 font-medium cursor-pointer">
                                    <i class="fab fa-mercadolivre w-4 text-yellow-400"></i> Ver Venda no Mercado Livre
                                </a>
                                <button @click="refreshOrder(order.id); actionsMenuOpen = null" class="w-full text-left px-4 py-2 text-slate-200 hover:bg-slate-700 hover:text-white transition-colors flex items-center gap-2 cursor-pointer">
                                    <i class="fas fa-sync w-4 text-blue-400"></i> Sincronizar Novos Dados
                                </button>
                                <button @click="recalculateOrder(order.id); actionsMenuOpen = null" class="w-full text-left px-4 py-2 text-slate-200 hover:bg-slate-700 hover:text-white transition-colors flex items-center gap-2 cursor-pointer">
                                    <i class="fas fa-calculator w-4 text-amber-400"></i> Recalcular Lucro (Local)
                                </button>
                                <button @click="viewJson(order); actionsMenuOpen = null" class="w-full text-left px-4 py-2 text-slate-200 hover:bg-slate-700 hover:text-white transition-colors flex items-center gap-2 mb-1 cursor-pointer">
                                    <i class="fas fa-code w-4 text-slate-500"></i> Ver JSON Técnico
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty -->
    <div x-show="!loading && orders.length === 0" class="text-center py-12">
        <i class="fas fa-shopping-cart text-4xl text-slate-600 mb-4"></i>
        <p class="text-slate-400">Nenhum pedido encontrado</p>
    </div>
    
    <!-- Pagination -->
    <div x-show="!loading && total > 0" class="flex items-center justify-between mt-4 px-2">
        <div class="text-sm text-slate-400">
            Mostrando <span x-text="from"></span> - <span x-text="to"></span> de <span x-text="total"></span>
        </div>
        <div class="flex items-center gap-2">
            <button @click="changePage(currentPage - 1)" :disabled="currentPage <= 1"
                class="px-3 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <template x-for="page in visiblePages" :key="page">
                <button @click="changePage(page)" 
                    class="px-3 py-1 rounded text-sm"
                    :class="page === currentPage ? 'bg-indigo-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
                    x-text="page"></button>
            </template>
            
            <button @click="changePage(currentPage + 1)" :disabled="currentPage >= lastPage"
                class="px-3 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    
    <!-- Modal JSON -->
    <div x-show="showJsonModal" style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-100" class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4" @click.self="closeJsonModal()">
        <div x-show="showJsonModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="bg-slate-800 rounded-xl border border-slate-600 w-full max-w-4xl max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between p-4 border-b border-slate-700">
                <h3 class="text-lg font-bold text-white">JSON do Pedido</h3>
                <button @click="closeJsonModal()" class="text-slate-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="flex-1 overflow-auto p-4 space-y-6">
                <template x-if="currentJsons?.order">
                    <div>
                        <h4 class="text-indigo-400 font-bold mb-2 uppercase text-[10px] tracking-widest flex items-center gap-2">
                            <i class="fas fa-shopping-bag"></i> API orders/{order_id}
                        </h4>
                        <pre class="bg-slate-900/50 p-3 rounded border border-slate-700 text-xs text-green-400 font-mono whitespace-pre-wrap max-h-96 overflow-auto" x-text="JSON.stringify(currentJsons.order, null, 2)"></pre>
                    </div>
                </template>

                <template x-if="currentJsons?.cart">
                    <div>
                        <h4 class="text-fuchsia-400 font-bold mb-2 uppercase text-[10px] tracking-widest flex items-center gap-2">
                            <i class="fas fa-shopping-cart"></i> API carts/{cart_id}
                        </h4>
                        <pre class="bg-slate-900/50 p-3 rounded border border-slate-700 text-xs text-green-400 font-mono whitespace-pre-wrap max-h-96 overflow-auto" x-text="JSON.stringify(currentJsons.cart, null, 2)"></pre>
                    </div>
                </template>

                <template x-if="currentJsons?.payments">
                    <div>
                        <h4 class="text-emerald-400 font-bold mb-2 uppercase text-[10px] tracking-widest flex items-center gap-2">
                            <i class="fas fa-credit-card"></i> API orders/{order_id}/payments
                        </h4>
                        <pre class="bg-slate-900/50 p-3 rounded border border-slate-700 text-xs text-green-400 font-mono whitespace-pre-wrap max-h-96 overflow-auto" x-text="JSON.stringify(currentJsons.payments, null, 2)"></pre>
                    </div>
                </template>

                <template x-if="currentJsons?.shipments">
                    <div>
                        <h4 class="text-blue-400 font-bold mb-2 uppercase text-[10px] tracking-widest flex items-center gap-2">
                            <i class="fas fa-truck"></i> API shipments/{shipment_id}
                        </h4>
                        <pre class="bg-slate-900/50 p-3 rounded border border-slate-700 text-xs text-green-400 font-mono whitespace-pre-wrap max-h-96 overflow-auto" x-text="JSON.stringify(currentJsons.shipments, null, 2)"></pre>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal Link Product -->
    <div x-show="showLinkModal" style="display: none;" class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4" @click.self="closeLinkModal()">
        <div class="bg-slate-800 rounded-xl border border-slate-600 w-full max-w-lg shadow-2xl overflow-hidden flex flex-col">
            <div class="bg-slate-900 border-b border-slate-700 p-4 shrink-0 px-6 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black tracking-tight text-white mb-1"><i class="fas fa-link mr-2 text-indigo-400"></i> Associar Produto Local</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Ligue o item do ML a um produto no sistema para calcular o lucro.</p>
                </div>
                <button @click="closeLinkModal()" class="text-slate-500 hover:text-slate-300 w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center transition-colors">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            
            <div class="p-6 bg-slate-800/50 flex-1 overflow-y-auto">
                <div class="mb-5 bg-indigo-500/10 border border-indigo-400/20 rounded-lg p-3">
                    <div class="text-[10px] uppercase font-bold text-indigo-400 mb-1">Item do Pedido</div>
                    <div class="text-sm font-semibold text-slate-200 line-clamp-2" x-text="linkingItem?.titulo"></div>
                    <div class="text-[11px] text-slate-400 mt-1 font-mono" x-show="linkingItem?.item_id">ID: <span x-text="linkingItem?.item_id"></span></div>
                </div>

                <div class="relative">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wide mb-2">Buscar Produto Local</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3.5 top-3.5 text-slate-400 text-sm pointer-events-none"></i>
                        <input type="text" x-model="searchQuery" @input.debounce.300ms="searchProducts" placeholder="Digite nome, SKU ou EAN do produto..." 
                            class="w-full bg-slate-900 border border-slate-600 rounded-lg pl-10 pr-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-inner" autocomplete="off">
                    </div>
                    
                    <div x-show="isSearching" class="absolute right-3 top-[36px] text-indigo-400 animate-spin">
                        <i class="fas fa-spinner"></i>
                    </div>
                </div>

                <div class="mt-4 border border-slate-700 bg-slate-900 rounded-lg max-h-60 overflow-y-auto overflow-x-hidden p-1 space-y-1">
                    <div x-show="searchResults.length === 0 && searchQuery.length > 2 && !isSearching" class="p-4 text-center text-slate-500 text-sm">
                        Nenhum produto local encontrado.
                    </div>
                    
                    <div x-show="searchQuery.length <= 2" class="p-4 text-center text-slate-500 text-xs italic">
                        Digite pelo menos 3 caracteres para buscar...
                    </div>

                    <template x-for="prod in searchResults" :key="prod.id">
                        <div @click="selectedProduct = prod" 
                             class="flex items-center gap-3 p-2.5 rounded-md cursor-pointer transition-colors border-2"
                             :class="selectedProduct?.id === prod.id ? 'bg-indigo-500/20 border-indigo-500' : 'border-transparent hover:bg-slate-700'">
                            <div class="w-10 h-10 rounded bg-slate-800 flex items-center justify-center shrink-0 border border-slate-600 relative overflow-hidden">
                                <template x-if="prod.fotos && prod.fotos.length > 0">
                                    <img :src="prod.fotos[0].url" class="absolute w-full h-full object-cover">
                                </template>
                                <i x-show="!prod.fotos || prod.fotos.length === 0" class="fas fa-box text-slate-500"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold text-slate-200 truncate" x-text="prod.nome"></div>
                                <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-2 font-mono">
                                    <span x-text="prod.tipo.toUpperCase()" class="lowercase font-semibold bg-slate-800 px-1.5 py-0.5 rounded text-[9px]"></span>
                                    <span>ID: <span x-text="prod.id"></span></span>
                                    <span x-show="prod.sku">SKU: <span x-text="prod.sku"></span></span>
                                </div>
                            </div>
                            <div class="w-5 h-5 flex items-center justify-center shrink-0 rounded-full" 
                                 :class="selectedProduct?.id === prod.id ? 'bg-indigo-500 text-white' : 'border border-slate-500 text-transparent'">
                                <i class="fas fa-check text-[10px]" x-show="selectedProduct?.id === prod.id"></i>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <div class="bg-slate-900 border-t border-slate-700 p-4 shrink-0 flex items-center justify-end gap-3">
                <button type="button" @click="closeLinkModal()" class="px-5 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-700 transition-colors">
                    Cancelar
                </button>
                <button type="button" @click="submitLink()" :disabled="!selectedProduct || isSubmitting" 
                    class="px-5 py-2.5 rounded-lg text-sm font-bold shadow-lg transition-all flex items-center gap-2"
                    :class="selectedProduct && !isSubmitting ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-500/30' : 'bg-slate-700 text-slate-400 cursor-not-allowed'">
                    <i class="fas fa-spinner animate-spin" x-show="isSubmitting"></i>
                    <i class="fas fa-link" x-show="!isSubmitting"></i>
                    Vincular e Recalcular
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function ordersPage() {
    return {
        empresaId: localStorage.getItem('empresa_id') || '4',
        orders: [],
        loading: false,
        syncing: false,
        syncStatus: '',
        search: '',
        status: '',
        statusEnvio: '',
        logistics: '',
        marketplace: '',
        dataDe: '',
        dataAte: '',
        sortBy: 'data_compra',
        sortDir: 'desc',
        hasMeliIntegration: false,
        hasAmazonIntegration: false,
        syncDropdownOpen: false,
        showJsonModal: false,
        currentJsons: { order: null, cart: null, payments: null, shipments: null },
        selectedOrders: [],
        actionsMenuOpen: null,
        currentPage: 1,
        lastPage: 1,
        total: 0,
        from: 0,
        to: 0,
        globalStats: {
            total_pedidos: 0,
            total_faturamento: 0,
            total_lucro: 0
        },

        showLinkModal: false,
        linkingOrder: null,
        linkingItem: null,
        searchQuery: '',
        searchResults: [],
        isSearching: false,
        selectedProduct: null,
        isSubmitting: false,
        
        init() {
            const savedEmpresa = localStorage.getItem('empresa_id');
            this.empresaId = savedEmpresa ? parseInt(savedEmpresa) : 4;
            
            // Default date range: last 30 days
            const today = new Date();
            const thirtyDaysAgo = new Date(today);
            thirtyDaysAgo.setDate(today.getDate() - 30);
            this.dataAte = today.toISOString().split('T')[0];
            this.dataDe = thirtyDaysAgo.toISOString().split('T')[0];
            
            this.$watch('empresaId', () => {
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadOrders();
                this.loadIntegrations();
            });
            
            window.addEventListener('empresa-changed', (e) => {
                this.empresaId = parseInt(e.detail);
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadOrders();
                this.loadIntegrations();
            });
            
            this.initFromUrl();
            this.loadOrders(false);
            this.loadIntegrations();

            window.addEventListener('popstate', () => {
                this.initFromUrl();
                this.loadOrders(false);
            });
        },

        initFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('search')) this.search = urlParams.get('search');
            if (urlParams.has('status')) this.status = urlParams.get('status');
            if (urlParams.has('status_envio')) this.statusEnvio = urlParams.get('status_envio');
            if (urlParams.has('logistics')) this.logistics = urlParams.get('logistics');
            if (urlParams.has('marketplace')) this.marketplace = urlParams.get('marketplace');
            if (urlParams.has('data_de')) this.dataDe = urlParams.get('data_de');
            if (urlParams.has('data_ate')) this.dataAte = urlParams.get('data_ate');
            if (urlParams.has('sort_by')) this.sortBy = urlParams.get('sort_by');
            if (urlParams.has('sort_dir')) this.sortDir = urlParams.get('sort_dir');
            if (urlParams.has('page')) this.currentPage = parseInt(urlParams.get('page'));
        },

        updateUrlParams() {
            const params = new URLSearchParams();
            if (this.search) params.set('search', this.search);
            if (this.status) params.set('status', this.status);
            if (this.statusEnvio) params.set('status_envio', this.statusEnvio);
            if (this.logistics) params.set('logistics', this.logistics);
            if (this.marketplace) params.set('marketplace', this.marketplace);
            if (this.dataDe) params.set('data_de', this.dataDe);
            if (this.dataAte) params.set('data_ate', this.dataAte);
            if (this.sortBy) params.set('sort_by', this.sortBy);
            if (this.sortDir) params.set('sort_dir', this.sortDir);
            if (this.currentPage > 1) params.set('page', this.currentPage);

            const queryString = params.toString();
            const currentQuery = window.location.search.replace(/^\?/, '');
            
            if (queryString !== currentQuery) {
                const newUrl = window.location.pathname + (queryString ? '?' + queryString : '');
                history.pushState(null, '', newUrl);
            }
        },
        
        async loadIntegrations() {
            try {
                const response = await fetch(`/api/orders/integrations?empresa_id=${this.empresaId}`);
                const data = await response.json();
                this.hasMeliIntegration = data.mercadolivre && data.mercadolivre.length > 0;
                this.hasAmazonIntegration = data.amazon && data.amazon.length > 0;
            } catch (e) {
                this.hasMeliIntegration = false;
                this.hasAmazonIntegration = false;
            }
        },
        
        async loadOrders(resetPage = true) {
            if (resetPage) {
                this.currentPage = 1;
            }
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    empresa_id: this.empresaId,
                    status: this.status,
                    status_envio: this.statusEnvio,
                    logistics: this.logistics,
                    marketplace: this.marketplace,
                    search: this.search,
                    data_de: this.dataDe,
                    data_ate: this.dataAte,
                    sort_by: this.sortBy,
                    sort_dir: this.sortDir,
                    page: this.currentPage,
                });
                
                const response = await fetch(`/api/orders?${params}`);
                const result = await response.json();
                this.orders = result.data || [];
                this.currentPage = result.current_page || 1;
                this.lastPage = result.last_page || 1;
                this.total = result.total || 0;
                this.from = result.from || 0;
                this.to = result.to || 0;
                if (result.stats) {
                    this.globalStats = result.stats;
                }

                this.updateUrlParams();
                this.selectedOrders = [];
            } catch (e) {
                console.error('Error:', e);
                this.orders = [];
            }
            this.loading = false;
        },
        
        changePage(page) {
            if (page >= 1 && page <= this.lastPage) {
                this.currentPage = page;
                this.loadOrders(false);
            }
        },
        
        getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },
        
        async syncOrders(marketplace = 'mercadolivre', usePeriod = false) {
            this.syncing = true;
            this.syncStatus = 'Iniciando...';
            let totalImported = 0;
            let page = 0;
            let hasMore = true;

            try {
                if (usePeriod) {
                    if (!this.dataDe || !this.dataAte) {
                        alert('Selecione um período');
                        this.syncing = false;
                        return;
                    }

                    const start = new Date(this.dataDe);
                    const end = new Date(this.dataAte);
                    const diffDays = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24));
                    if (diffDays > 31) {
                        alert('O período máximo para sincronização é de 31 dias.');
                        this.syncing = false;
                        return;
                    }
                }

                while (hasMore) {
                    this.syncStatus = `Pag. ${page + 1}...`;
                    let url = `/api/orders/sync?empresa_id=${this.empresaId}&marketplace=${marketplace}&page=${page}`;
                    
                    if (usePeriod) {
                        url += `&data_de=${this.dataDe}&data_ate=${this.dataAte}`;
                    }

                    const response = await fetch(url, { 
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Content-Type': 'application/json',
                        }
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Erro na requisição');
                    }

                    const data = await response.json();
                    if (!data.success) {
                        throw new Error(data.message);
                    }

                    totalImported += (data.imported || 0);
                    hasMore = data.has_more;
                    page = data.next_page;

                    // Pequena pausa para não atropelar o servidor
                    if (hasMore) await new Promise(r => setTimeout(r, 500));
                }

                alert(`${totalImported} pedidos sincronizados com sucesso.`);
                await this.loadOrders();
            } catch (e) {
                console.error('Sync error:', e);
                alert('Erro durante a sincronização: ' + e.message);
            } finally {
                this.syncing = false;
                this.syncStatus = '';
            }
        },
        
        async refreshOrder(orderId) {
            try {
                const response = await fetch(`/api/orders/${orderId}/refresh?empresa_id=${this.empresaId}`, { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Content-Type': 'application/json',
                    }
                });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    await this.loadOrders();
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (e) {
                alert('Erro ao atualizar pedido');
            }
        },

        async recalculateOrder(orderId) {
            try {
                const response = await fetch(`/api/orders/recalculate`, { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: orderId,
                        empresa_id: this.empresaId
                    })
                });
                const data = await response.json();
                if (data.success) {
                    // Update the order in the local list
                    if (data.orders && data.orders.length > 0) {
                        const updatedOrder = data.orders[0];
                        const index = this.orders.findIndex(o => o.id === orderId);
                        if (index !== -1) {
                            this.orders[index] = updatedOrder;
                        }
                    }
                    alert(data.message);
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (e) {
                alert('Erro ao recalcular pedido');
            }
        },

        async recalculatePeriod() {
            if (!this.dataDe || !this.dataAte) {
                alert('Selecione um período');
                return;
            }

            const start = new Date(this.dataDe);
            const end = new Date(this.dataAte);
            const diffDays = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24));
            if (diffDays > 31) {
                alert('O período máximo para recálculo é de 31 dias.');
                return;
            }

            if (!confirm(`Deseja recalcular todos os pedidos entre ${this.dataDe} e ${this.dataAte}? (Isso usará apenas os dados locais já baixados)`)) {
                return;
            }

            this.loading = true;
            try {
                const response = await fetch(`/api/orders/recalculate`, { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        from: this.dataDe,
                        to: this.dataAte,
                        empresa_id: this.empresaId
                    })
                });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    await this.loadOrders();
                } else {
                    alert('Erro: ' + (data.error || data.message));
                }
            } catch (e) {
                alert('Erro ao recalcular período');
            }
            this.loading = false;
        },
        
        toggleOrder(orderId) {
            const idx = this.selectedOrders.indexOf(orderId);
            if (idx > -1) {
                this.selectedOrders.splice(idx, 1);
            } else {
                this.selectedOrders.push(orderId);
            }
        },
        
        toggleAll() {
            if (this.selectedOrders.length === this.orders.length) {
                this.selectedOrders = [];
            } else {
                this.selectedOrders = this.orders.map(o => o.id);
            }
        },
        
        printSelectedLabels() {
            this.selectedOrders.forEach(orderId => {
                const url = `/api/orders/${orderId}/etiqueta-meli?empresa_id=${this.empresaId}`;
                window.open(url, '_blank');
            });
        },
        
        toggleActionsMenu(orderId) {
            this.actionsMenuOpen = this.actionsMenuOpen === orderId ? null : orderId;
        },
        
        copyToClipboard(text, label) {
            if (!text) return;
            navigator.clipboard.writeText(text.toString()).then(() => {
                alert(label + ' copiado!');
            });
        },
        
        get groupedOrders() {
            let groups = [];
            let packMap = {};
            
            this.orders.forEach(order => {
                if (order.pack_id) {
                    if (!packMap[order.pack_id]) {
                        packMap[order.pack_id] = {
                            is_pack: true,
                            pack_id: order.pack_id,
                            id: order.id,
                            pedido_id: order.pack_id,
                            external_id: order.external_id,
                            marketplace: order.marketplace,
                            data_compra: order.data_compra,
                            data_pagamento: order.data_pagamento,
                            data_envio: order.data_envio,
                            data_entrega: order.data_entrega,
                            comprador_nome: order.comprador_nome,
                            comprador_apelido: order.comprador_apelido,
                            comprador_cpf: order.comprador_cpf,
                            comprador_cnpj: order.comprador_cnpj,
                            telefone: order.telefone,
                            cidade: order.cidade,
                            estado: order.estado,
                            cep: order.cep,
                            endereco: order.endereco,
                            status: order.status,
                            nfe_vinculada: order.nfe_vinculada,
                            logistics: order.logistics,
                            url_rastreamento: order.url_rastreamento,
                            json_data: order.json_data,
                            order_json: order.order_json,
                            cart_json: order.cart_json,
                            payments_json: order.payments_json,
                            shipments_json: order.shipments_json,
                            // Sums
                            valor_total: 0,
                            valor_frete: 0,
                            valor_desconto: 0,
                            valor_produtos: 0,
                            taxas: 0,
                            lucro: 0,
                            custo_total: 0,
                            lucro_percent: 0,
                            taxa_platform: 0,
                            taxa_pagamento: 0,
                            valor_imposto: 0,
                            itens: [],
                            sub_orders: []
                        };
                        groups.push(packMap[order.pack_id]);
                    }
                    
                    let g = packMap[order.pack_id];
                    g.sub_orders.push(order);
                    g.valor_total += (order.valor_total || 0);
                    g.valor_frete += (order.valor_frete || 0);
                    g.valor_desconto += (order.valor_desconto || 0);
                    g.valor_produtos += (order.valor_produtos || 0);
                    g.taxas += (order.taxas || 0);
                    g.lucro += (order.lucro || 0);
                    g.custo_total += (order.custo_total || 0);
                    g.taxa_platform += (order.taxa_platform || 0);
                    g.taxa_pagamento += (order.taxa_pagamento || 0);
                    g.valor_imposto += (order.valor_imposto || 0);
                    
                    if (g.valor_total > 0) {
                        g.lucro_percent = Math.round((g.lucro / g.valor_total) * 100);
                    }
                    
                    if (order.itens && order.itens.length > 0) {
                        g.itens.push(...order.itens);
                    }
                } else {
                    groups.push({
                        is_pack: false,
                        ...order,
                        sub_orders: [order]
                    });
                }
            });
            return groups;
        },
        
        get totalProfit() {
            return this.globalStats.total_lucro || 0;
        },

        get totalValue() {
            return this.globalStats.total_faturamento || 0;
        },

        get selectedCount() {
            return this.selectedOrders.length;
        },

        get selectedValue() {
            // This is complex because we only have data for the current page of orders
            // but the user might select orders across pages. 
            // However, typical behavior is selecting what's visible.
            // For now, calculate from visible orders that are selected.
            return this.orders
                .filter(o => this.selectedOrders.includes(o.id))
                .reduce((sum, o) => sum + (o.valor_total || 0), 0);
        },
        
        get visiblePages() {
            const pages = [];
            const maxVisible = 5;
            let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
            let end = Math.min(this.lastPage, start + maxVisible - 1);
            
            if (end - start + 1 < maxVisible) {
                start = Math.max(1, end - maxVisible + 1);
            }
            
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        },
        
        getMarketplaceIcon(mp) {
            const icons = { 'mercadolivre': 'fab fa-mercado-livre', 'amazon': 'fab fa-amazon', 'bling': 'fas fa-cash-register' };
            return icons[mp] || 'fas fa-store';
        },
        
        getMarketplaceColor(mp) {
            const colors = { 'mercadolivre': 'bg-yellow-500/20 text-yellow-400', 'amazon': 'bg-orange-500/20 text-orange-400', 'bling': 'bg-green-500/20 text-green-400' };
            return colors[mp] || 'bg-slate-600';
        },
        
        getMarketplaceColorOnlyText(mp) {
            const colors = { 'mercadolivre': 'text-yellow-400', 'amazon': 'text-orange-400', 'bling': 'text-green-400' };
            return colors[mp] || 'text-slate-400';
        },
        
        getStatusClass(status) {
            const classes = { 
                'paid': 'bg-green-500/20 text-green-400', 
                'pending': 'bg-yellow-500/20 text-yellow-400', 
                'shipped': 'bg-blue-500/20 text-blue-400',
                'delivered': 'bg-emerald-500/20 text-emerald-400',
                'canceled': 'bg-red-500/20 text-red-400',
                'em_aberto': 'bg-green-500/20 text-green-400',
                'enviado': 'bg-blue-500/20 text-blue-400',
                'entregue': 'bg-emerald-500/20 text-emerald-400',
            };
            return classes[status] || 'bg-slate-600 text-slate-300';
        },
        
        getLogisticsClass(mode) {
            const classes = { 
                'me2': 'bg-blue-500/20 text-blue-400',
                'fulfillment': 'bg-purple-500/20 text-purple-400',
                'classic': 'bg-orange-500/20 text-orange-400',
            };
            return classes[mode] || 'bg-slate-600 text-slate-400';
        },
        
        getLogisticsLabel(mode) {
            const labels = { 
                'me2': 'M.E2',
                'fulfillment': 'Full',
                'classic': 'Classic',
            };
            return labels[mode] || '-';
        },
        
        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('pt-BR');
        },
        
        formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
        },
        
        viewJson(order) {
            this.currentJsons = {
                order: order.order_json || order.json_data || {},
                cart: order.cart_json || null,
                payments: order.payments_json || null,
                shipments: order.shipments_json || null
            };
            this.showJsonModal = true;
        },
        
        closeJsonModal() {
            this.showJsonModal = false;
            this.currentJsons = { order: null, cart: null, payments: null, shipments: null };
        },

        openLinkModal(order, item) {
            this.linkingOrder = order;
            this.linkingItem = item;
            this.searchQuery = '';
            this.searchResults = [];
            this.selectedProduct = null;
            this.showLinkModal = true;
        },

        closeLinkModal() {
            this.showLinkModal = false;
            setTimeout(() => {
                this.linkingOrder = null;
                this.linkingItem = null;
                this.searchResults = [];
                this.searchQuery = '';
                this.selectedProduct = null;
            }, 300);
        },

        async searchProducts() {
            if (this.searchQuery.length < 3) {
                this.searchResults = [];
                return;
            }
            this.isSearching = true;
            try {
                const response = await fetch(`/api/products/search?q=${encodeURIComponent(this.searchQuery)}`);
                const data = await response.json();
                this.searchResults = data || [];
            } catch (error) {
                console.error('Error searching products:', error);
            }
            this.isSearching = false;
        },

        async submitLink() {
            if (!this.selectedProduct || !this.linkingItem || !this.linkingOrder) return;
            
            this.isSubmitting = true;
            try {
                const response = await fetch('/api/orders/link-item', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: this.linkingOrder.id,
                        item_id: this.linkingItem.item_id,
                        produto_id: this.selectedProduct.id
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.order) {
                    // Update order natively
                    const idx = this.orders.findIndex(o => o.id === this.linkingOrder.id);
                    if (idx !== -1) {
                        this.orders[idx] = data.order;
                    }
                    this.closeLinkModal();
                    alert(data.message);
                } else {
                    alert('Erro: ' + (data.error || 'Falha ao vincular o produto.'));
                }
            } catch (error) {
                console.error('Erro ao vincular item:', error);
                alert('Erro ao vincular produto. Veja o console.');
            }
            this.isSubmitting = false;
        }
    }
}
</script>
@endsection
