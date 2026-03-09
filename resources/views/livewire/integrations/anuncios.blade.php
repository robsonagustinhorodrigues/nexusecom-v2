<div class="space-y-6">
    <!-- Global Loading Overlay -->
    @if($isSyncing)
    <div class="fixed inset-0 z-[100] bg-slate-900/50 backdrop-blur-sm flex items-center justify-center font-sans">
        <div class="bg-white dark:bg-dark-900 p-8 rounded-3xl shadow-2xl flex flex-col items-center gap-4 animate-in zoom-in-95 duration-200">
            <div class="relative w-16 h-16 flex items-center justify-center">
                <div class="absolute inset-0 border-4 border-slate-100 dark:border-dark-800 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-yellow-500 rounded-full border-t-transparent animate-spin"></div>
                <i class="fas fa-sync-alt text-yellow-500 text-xl"></i>
            </div>
            <div class="text-center">
                <div class="text-base font-bold text-slate-900 dark:text-white">Sincronizando</div>
                <div class="text-xs text-slate-500">{{ $syncProgress ?: 'Por favor, aguarde...' }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Premium Dashboard Header -->
    <div class="space-y-4 mb-6">
        <!-- Top Row: Title & Account Selector -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tighter uppercase italic flex items-center gap-3">
                    <span class="bg-yellow-500 w-2 h-8 rounded-full shadow-[0_0_15px_rgba(234,179,8,0.5)]"></span>
                    Anúncios <span class="text-yellow-500">Vendas</span>
                </h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] ml-5">Marketplace Inventory Dashboard</p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Sync Status Indicator -->
                @if($isSyncing)
                <div class="flex items-center gap-2 px-3 py-2 bg-yellow-500/10 border border-yellow-500/20 rounded-xl text-yellow-400 text-xs font-bold animate-pulse shadow-inner">
                    <i class="fas fa-sync-alt fa-spin"></i>
                    <span>Sincronizando...</span>
                </div>
                @endif

                <!-- Account Selector -->
                <div class="relative group">
                    <i class="fas fa-store absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-yellow-500 transition-colors z-10"></i>
                    <select wire:model.live="integracao_id" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl pl-9 pr-10 py-2.5 text-sm text-white font-bold tracking-tight focus:ring-2 focus:ring-yellow-500/50 outline-none transition-all shadow-lg appearance-none cursor-pointer min-w-[200px]">
                        <option value="">Todas as Contas</option>
                        @foreach($integracoes as $int)
                            <option value="{{ $int->id }}">{{ $int->nome_conta }}</option>
                        @endforeach
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 text-[10px] pointer-events-none"></i>
                </div>

                <!-- Global Sync Button -->
                <button wire:click="checkAndSync" :disabled="$isSyncing"
                    class="px-4 py-2.5 bg-yellow-500 hover:bg-yellow-400 text-black rounded-xl flex items-center gap-2 text-sm font-black transition-all shadow-lg active:scale-95 disabled:opacity-50 italic">
                    <i class="fas fa-sync-alt {{ $isSyncing ? 'fa-spin' : '' }}"></i>
                    <span>Sincronizar Tudo</span>
                </button>

                <!-- Toggle View Mode -->
                <div class="flex bg-slate-800/80 border border-slate-700/50 rounded-xl p-1 shadow-lg">
                    <button wire:click="$set('viewMode', 'cards')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2 {{ $viewMode === 'cards' ? 'bg-yellow-500 text-black shadow-lg' : 'text-slate-400 hover:text-white' }}">
                        <i class="fas fa-th-large"></i> Cards
                    </button>
                    <button wire:click="$set('viewMode', 'table')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2 {{ $viewMode === 'table' ? 'bg-yellow-500 text-black shadow-lg' : 'text-slate-400 hover:text-white' }}">
                        <i class="fas fa-list"></i> Tabela
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Bar -->
        @if(!$isBling)
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Total Anúncios -->
            <div class="bg-gradient-to-br from-slate-800 to-transparent border border-slate-700/50 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Anúncios</span>
                    <i class="fas fa-bullhorn text-slate-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white">{{ $anuncios->total() }}</div>
                <div class="mt-1 h-1 w-12 bg-slate-500/50 rounded-full"></div>
            </div>

            <!-- Ativos -->
            <div class="bg-gradient-to-br from-emerald-600/10 to-transparent border border-emerald-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Anúncios Ativos</span>
                    <i class="fas fa-check-circle text-emerald-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white">{{ $anuncios->filter(fn($a) => ($a->status ?? '') === 'active')->count() }}</div>
                <div class="mt-1 h-1 w-12 bg-emerald-500/50 rounded-full"></div>
            </div>

            <!-- Catálogo -->
            <div class="bg-gradient-to-br from-amber-600/10 to-transparent border border-amber-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-amber-500 uppercase tracking-widest">Catálogo ML</span>
                    <i class="fas fa-book text-amber-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white">{{ $anuncios->filter(fn($a) => $isCatalogoFn($a->json_data ?? []))->count() }}</div>
                <div class="mt-1 h-1 w-12 bg-amber-500/50 rounded-full"></div>
            </div>

            <!-- Vinculados -->
            <div class="bg-gradient-to-br from-indigo-600/10 to-transparent border border-indigo-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Vinculados</span>
                    <i class="fas fa-link text-indigo-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white">{{ $anuncios->filter(fn($a) => ($a->product_sku_id ?? null))->count() }}</div>
                <div class="mt-1 h-1 w-12 bg-indigo-500/50 rounded-full"></div>
            </div>

            <!-- Mercado Livre -->
            <div class="bg-gradient-to-br from-yellow-600/10 to-transparent border border-yellow-500/20 rounded-2xl p-4 shadow-xl backdrop-blur-sm group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-yellow-500 uppercase tracking-widest">Meli Store</span>
                    <i class="fab fa-mercadolivre text-yellow-500 group-hover:scale-110 transition-transform"></i>
                </div>
                <div class="text-2xl font-black text-white">{{ $anuncios->filter(fn($a) => ($a->marketplace ?? '') === 'mercadolivre')->count() }}</div>
                <div class="mt-1 h-1 w-12 bg-yellow-400/50 rounded-full"></div>
            </div>
        </div>
        @else
        <div class="bg-blue-500/10 border border-blue-500/20 text-blue-400 px-6 py-4 rounded-2xl flex items-center justify-between backdrop-blur-sm shadow-xl">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-warehouse text-xl"></i>
                </div>
                <div>
                    <div class="font-black uppercase tracking-wider">Bling ERP selecionado</div>
                    <div class="text-sm text-blue-300 opacity-80">Para visualizar os produtos do Bling, utilize a página de Controle de Estoque/Produtos.</div>
                </div>
            </div>
            <a href="{{ route('products.index') }}" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold flex items-center gap-2 shadow-lg transition-all active:scale-95">
                <i class="fas fa-arrow-right"></i> Ver Produtos
            </a>
        </div>
        @endif

        <!-- Filters & Control Bar -->
        <div class="bg-slate-800/80 backdrop-blur-md border border-slate-700/50 rounded-2xl p-3 shadow-2xl flex flex-wrap items-center gap-3">
            <!-- Search -->
            <div class="flex-1 min-w-[250px] relative group">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-yellow-500 transition-colors"></i>
                <input wire:model.live="search" type="text" placeholder="Buscar por título, ID ou SKU..." 
                    class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-yellow-500/50 focus:border-yellow-500 outline-none transition-all">
            </div>

            <!-- Quick Filters -->
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 md:pb-0">
                <select wire:model.live="status_filtro" class="bg-black border border-slate-700/50 rounded-xl px-4 py-2.5 text-xs text-slate-300 font-bold focus:ring-2 focus:ring-yellow-500/50 outline-none cursor-pointer appearance-none min-w-[120px]">
                    <option value="" class="bg-black">Status: Todos</option>
                    <option value="ativo" class="bg-black text-emerald-400">✅ Ativos</option>
                    <option value="inativo" class="bg-black text-slate-500">❌ Inativos</option>
                </select>

                <select wire:model.live="tipo_filtro" class="bg-black border border-slate-700/50 rounded-xl px-4 py-2.5 text-xs text-slate-300 font-bold focus:ring-2 focus:ring-yellow-500/50 outline-none cursor-pointer appearance-none min-w-[120px]">
                    <option value="" class="bg-black">Tipo: Todos</option>
                    <option value="catalogo" class="bg-black text-indigo-400">📚 Catálogo</option>
                    <option value="normal" class="bg-black">📦 Normal</option>
                </select>

                <select wire:model.live="vinculo_filtro" class="bg-black border border-slate-700/50 rounded-xl px-4 py-2.5 text-xs text-slate-300 font-bold focus:ring-2 focus:ring-yellow-500/50 outline-none cursor-pointer appearance-none min-w-[140px]">
                    <option value="" class="bg-black">Vínculo: Todos</option>
                    <option value="vinculado" class="bg-black text-indigo-400">🔗 Vinculados</option>
                    <option value="nao_vinculado" class="bg-black text-amber-500">⚠️ Pendentes</option>
                </select>
            </div>
        </div>
    </div>

    <!-- VIEW: CARDS -->
    @if($viewMode === 'cards')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($anuncios as $ads)
        @php
            $medidas = $this->getMedidas($ads->json_data ?? []);
            $skuMl = $this->getSkuMl($ads->json_data ?? []);
            $tipo = $this->getTipoAnuncio($ads->json_data ?? []);
            $meli = $this->getMarketplaceInfo($ads->marketplace ?? 'mercadolivre');
            $lucro = $this->calcularLucratividade($ads);
            $isCatalogo = $isCatalogoFn($ads->json_data ?? []);
            $urlConcorrentes = $this->getUrlConcorrentes($ads);
        @endphp
        <div class="bg-slate-900/50 backdrop-blur-sm border border-slate-800 rounded-2xl overflow-hidden shadow-2xl hover:shadow-yellow-500/5 transition-all duration-300 group/card flex flex-col h-full">
            <!-- Header with Marketplace Color -->
            <div class="relative h-32 bg-slate-950 overflow-hidden">
                <!-- Imagem -->
                <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-t from-slate-900 to-transparent z-0">
                    @php
                        $imagemUrl = null;
                        if (!empty($ads->json_data['thumbnail'])) {
                            $imagemUrl = $ads->json_data['thumbnail'];
                        } elseif ($ads->marketplace === 'amazon' && !empty($ads->json_data['ASIN'])) {
                            $imagemUrl = 'https://images-na.ssl-images-amazon.com/images/I/' . $ads->json_data['ASIN'] . '._AC_US400_.jpg';
                        } elseif ($ads->productSku?->product?->imagem) {
                            $imagemUrl = is_object($ads->productSku) ? $ads->productSku?->product?->imagem : null;
                        }
                    @endphp
                    
                    @if($imagemUrl)
                        <img src="{{ $imagemUrl }}" alt="" class="w-full h-full object-contain opacity-60 group-hover/card:scale-110 group-hover/card:opacity-80 transition-all duration-500" 
                             onerror="this.parentElement.innerHTML='<i class=fas fa-image text-5xl text-white/30></i>'">
                    @else
                        <i class="fas fa-image text-5xl text-white/30"></i>
                    @endif
                </div>
                
                <!-- Badge Marketplace -->
                <div class="absolute top-3 left-3 z-10">
                    @if(!empty($meli['logo']))
                        <div class="bg-black/40 backdrop-blur-md p-1.5 rounded-xl border border-white/10 shadow-lg">
                            <img src="{{ $meli['logo'] }}" alt="{{ $meli['nome'] }}" class="h-5 w-auto">
                        </div>
                    @else
                        <span class="px-3 py-1.5 rounded-xl text-[10px] font-black bg-black/40 backdrop-blur-md text-white border border-white/10 flex items-center gap-2 shadow-lg uppercase tracking-wider">
                            <i class="{{ $meli['icone'] }} text-yellow-500"></i>
                            {{ $meli['nome'] }}
                        </span>
                    @endif
                </div>
                
                <!-- Badge Status Header -->
                <div class="absolute top-3 right-3 flex flex-col gap-2 items-end z-10">
                    @if($isCatalogo)
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-yellow-500 text-black shadow-[0_0_15px_rgba(234,179,8,0.4)] flex items-center gap-1.5 uppercase tracking-tighter">
                            <i class="fas fa-book"></i> Catálogo
                        </span>
                    @endif
                    @if($ads->status === 'active')
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 shadow-lg uppercase tracking-tighter backdrop-blur-md">Ativo</span>
                    @else
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-slate-500/20 text-slate-400 border border-slate-500/40 shadow-lg uppercase tracking-tighter backdrop-blur-md">Inativo</span>
                    @endif
                </div>
            </div>
            
            <!-- Conteúdo -->
            <div class="p-4 flex flex-col flex-1 gap-4">
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-white line-clamp-2 leading-tight min-h-[2.5rem] group-hover/card:text-yellow-500 transition-colors" title="{{ $ads->titulo }}">
                        {{ $ads->titulo }}
                    </h3>
                    
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ $ads->external_id }}</span>
                        @if($skuMl)
                            <span class="text-[9px] font-black bg-indigo-500/10 text-indigo-400 px-2 py-0.5 rounded-full border border-indigo-500/20 uppercase">
                                SKU: {{ $skuMl }}
                            </span>
                        @endif
                    </div>
                </div>
                
                <!-- Preços -->
                <div class="grid grid-cols-2 gap-4 py-3 border-y border-slate-800/50">
                    <div>
                        <div class="text-[10px] font-black text-slate-500 uppercase mb-1">Preço Venda</div>
                        @if($ads->promocao_valor)
                            <div class="text-xl font-black text-rose-500">R$ {{ number_format($ads->promocao_valor, 2, ',', '.') }}</div>
                            <div class="text-[10px] text-slate-500 line-through font-bold">R$ {{ number_format($ads->preco_original ?: $ads->preco, 2, ',', '.') }}</div>
                        @else
                            <div class="text-xl font-black text-white italic tracking-tighter">R$ {{ number_format($lucro['preco'], 2, ',', '.') }}</div>
                        @endif
                    </div>
                    <div class="border-l border-slate-800 pl-4">
                        <div class="text-[10px] font-black text-slate-500 uppercase mb-1">Margem Lucro</div>
                        <div class="text-xl font-black {{ $lucro['margem'] > 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                            R$ {{ number_format($lucro['lucro_bruto'], 2, ',', '.') }}
                            <span class="text-xs opacity-70">({{ number_format($lucro['margem'], 1) }}%)</span>
                        </div>
                    </div>
                </div>
                
                <!-- Detalhamento de Custo -->
                <div class="space-y-1.5 bg-black/40 rounded-xl p-3 border border-slate-800/50">
                    <div class="flex justify-between text-[11px]">
                        <span class="text-slate-400 font-bold uppercase tracking-tighter">Taxas Marketplace</span>
                        <span class="text-rose-500 font-black">-R$ {{ number_format($lucro['taxas'], 2, ',', '.') }}</span>
                    </div>
                    @if(isset($lucro['frete']) && $lucro['frete'] > 0)
                    <div class="flex justify-between text-[11px]">
                        <span class="text-slate-400 font-bold uppercase tracking-tighter flex items-center gap-1">
                            Frete
                            <span class="bg-blue-500/10 text-blue-400 px-1 rounded text-[8px] border border-blue-500/20 font-black uppercase tracking-widest">
                                {{ $lucro['frete_type'] ?? 'Envio' }}
                            </span>
                        </span>
                        <span class="text-rose-500 font-black">-R$ {{ number_format($lucro['frete'], 2, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-[11px]">
                        <span class="text-slate-400 font-bold uppercase tracking-tighter">Impostos (10%)</span>
                        <span class="text-rose-500 font-black">-R$ {{ number_format($lucro['imposto'] ?? 0, 2, ',', '.') }}</span>
                    </div>
                    @if(is_object($ads->productSku) && !empty($ads->productSku?->product?->preco_custo))
                    <div class="pt-1 mt-1 border-t border-slate-800 flex justify-between text-[11px]">
                        <span class="text-slate-300 font-black uppercase tracking-tighter">Custo Produto</span>
                        <span class="text-rose-400 font-black">-R$ {{ number_format($lucro['custo'] ?? 0, 2, ',', '.') }}</span>
                    </div>
                    @endif
                </div>

                <!-- Estoque e Vínculo Row -->
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-slate-500 uppercase">Estoque Disponível</span>
                        <span class="text-lg font-black {{ $ads->estoque > 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ $ads->estoque ?? 0 }} <span class="text-xs opacity-50 font-medium">UN</span></span>
                    </div>
                    
                    <div>
                        @if($ads->product_sku_id && is_object($ads->productSku))
                            <div class="flex flex-col items-end">
                                <span class="text-[10px] font-black text-slate-500 uppercase">ERP Link</span>
                                <span class="text-xs font-bold text-indigo-400 bg-indigo-500/10 px-2 py-1 rounded-lg border border-indigo-500/20">
                                    {{ is_object($ads->productSku) ? $ads->sku->sku : $ads->sku }}
                                </span>
                            </div>
                        @else
                            <button wire:click="abrirVincular({{ $ads->id }})" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-black rounded-xl text-xs font-black shadow-lg shadow-amber-500/10 transition-all active:scale-95 uppercase italic tracking-tighter">
                                <i class="fas fa-link mr-1.5"></i> Vincular ERP
                            </button>
                        @endif
                    </div>
                </div>
                
                <!-- Ações Contextuais -->
                <div class="mt-auto pt-4 border-t border-slate-800 grid grid-cols-2 gap-2">
                    @if(!$ads->produto_id)
                    @endif

                    <!-- Repricer (Apenas ML Catálogo) -->
                    @if($ads->marketplace === 'mercadolivre' && !empty($ads->json_data['catalog_product_id']))
                        <button wire:click="openRepricerModal({{ $ads->id }})" class="p-2 {{ optional($ads)->repricerConfig?->is_active ? 'text-indigo-500 bg-indigo-500/10' : 'text-slate-400' }} hover:bg-indigo-500/10 rounded-lg transition-colors" title="Configurar Repricer">
                            <i class="fas fa-robot text-xs"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-12">
            <i class="fas fa-inbox text-4xl text-slate-300 mb-3 block"></i>
            <p class="text-slate-500">Nenhum anúncio encontrado</p>
        </div>
        @endforelse
    </div>
    
    <!-- Paginação para Cards -->
    @if($anuncios->hasPages())
    <div class="p-4 border-t border-slate-200 dark:border-dark-800">
        {{ $anuncios->links() }}
    </div>
    @endif
    
    @else
    <!-- VIEW: TABELA -->
    <div class="bg-slate-900/50 backdrop-blur-md border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full min-w-[1100px] border-collapse">
                <thead class="bg-black/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Info do Anúncio</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Marketplace</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">SKU Meli</th>
                        <th class="px-4 py-4 text-right text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Preço Venda</th>
                        <th class="px-4 py-4 text-right text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Taxas/Frete</th>
                        <th class="px-4 py-4 text-right text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Imposto (10%)</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Disponível</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Vínculo ERP</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Inteligência</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($anuncios as $ads)
                    @php
                        $medidas = $this->getMedidas($ads->json_data ?? []);
                        $skuMl = $this->getSkuMl($ads->json_data ?? []);
                        $tipo = $this->getTipoAnuncio($ads->json_data ?? []);
                        $meli = $this->getMarketplaceInfo($ads->marketplace ?? 'mercadolivre');
                        $isCatalogo = $isCatalogoFn($ads->json_data ?? []);
                        $urlConcorrentes = $this->getUrlConcorrentes($ads);
                        $tableLucro = $this->calcularLucratividade($ads);
                    @endphp
                    <tr class="group/row hover:bg-yellow-500/5 transition-all duration-300">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-black/40 flex items-center justify-center border border-slate-700/50 overflow-hidden group-hover/row:border-yellow-500/30 transition-colors shadow-lg">
                                    @php
                                        $thumbUrl = null;
                                        if (!empty($ads->json_data['thumbnail'])) {
                                            $thumbUrl = $ads->json_data['thumbnail'];
                                        } elseif ($ads->marketplace === 'amazon' && !empty($ads->json_data['ASIN'])) {
                                            $thumbUrl = 'https://images-na.ssl-images-amazon.com/images/I/' . $ads->json_data['ASIN'] . '._AC_US400_.jpg';
                                        } elseif ($ads->productSku?->product?->imagem) {
                                            $thumbUrl = is_object($ads->productSku) ? $ads->productSku?->product?->imagem : null;
                                        }
                                    @endphp
                                    @if($thumbUrl)
                                        <img src="{{ $thumbUrl }}" alt="" class="w-full h-full object-contain opacity-80 group-hover/row:scale-110 group-hover/row:opacity-100 transition-all duration-500" 
                                             onerror="this.parentElement.innerHTML='<i class=fas fa-image text-slate-600></i>'">
                                    @else
                                        <i class="fas fa-image text-slate-600"></i>
                                    @endif
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-bold text-white group-hover/row:text-yellow-500 transition-colors truncate max-w-[280px]">{{ $ads->titulo }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ $ads->external_id }}</span>
                                        @if($isCatalogo)
                                            <span class="bg-amber-500/10 text-amber-500 text-[8px] font-black px-1.5 py-0.5 rounded border border-amber-500/20 uppercase tracking-tighter">CATÁLOGO</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if(!empty($meli['logo']))
                                <div class="bg-white/5 backdrop-blur-sm p-1.5 rounded-lg border border-white/5 inline-block shadow-lg">
                                    <img src="{{ $meli['logo'] }}" alt="{{ $meli['nome'] }}" class="h-4 w-auto">
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-black bg-slate-800 text-slate-400 border border-slate-700 uppercase tracking-tighter">
                                    <i class="{{ $meli['icone'] }} text-yellow-500"></i>
                                    {{ $meli['nome'] }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($skuMl)
                                <span class="text-[10px] font-bold text-indigo-400 bg-indigo-500/10 px-2.5 py-1 rounded-lg border border-indigo-500/20 uppercase">
                                    {{ $skuMl }}
                                </span>
                            @else
                                <span class="text-[10px] text-slate-600 font-bold uppercase">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-right">
                            <span class="text-sm font-black text-white italic tracking-tighter">R$ {{ number_format($this->getPreco($ads), 2, ',', '.') }}</span>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex flex-col items-end">
                                <span class="text-rose-500 text-[11px] font-black">-R$ {{ number_format($tableLucro['taxas'], 2, ',', '.') }}</span>
                                @if($tableLucro['frete'] > 0)
                                    <span class="text-rose-400 text-[9px] font-bold uppercase opacity-80">-R$ {{ number_format($tableLucro['frete'], 2, ',', '.') }} (FRETE)</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <span class="text-rose-400 text-[11px] font-black">-R$ {{ number_format($tableLucro['imposto'], 2, ',', '.') }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="text-sm font-black {{ $ads->estoque > 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $ads->estoque ?? 0 }} <span class="text-[10px] opacity-50 font-medium uppercase">un</span>
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($ads->product_sku_id && $ads->sku)
                                <span class="text-[10px] font-bold text-indigo-400 bg-indigo-500/5 px-2.5 py-1 rounded-lg border border-indigo-500/10 uppercase italic">
                                    {{ is_object($ads->productSku) ? $ads->sku->sku : $ads->sku }}
                                </span>
                            @else
                                <button wire:click="abrirVincular({{ $ads->id }})" class="text-[9px] font-black bg-amber-500/10 text-amber-500 border border-amber-500/30 px-2.5 py-1.5 rounded-lg hover:bg-amber-500 hover:text-black transition-all uppercase tracking-tighter italic">
                                    <i class="fas fa-link mr-1"></i> Vincular ERP
                                </button>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover/row:opacity-100 transition-opacity">
                                @if($ads->marketplace === 'mercadolivre')
                                    <button wire:click="syncAnuncio({{ $ads->id }})" wire:loading.attr="disabled" class="w-8 h-8 flex items-center justify-center text-yellow-500 bg-yellow-500/10 hover:bg-yellow-500/20 rounded-lg transition-all border border-yellow-500/10" title="Sincronizar">
                                        <i class="fas fa-sync-alt text-xs" wire:loading.remove wire:target="syncAnuncio({{ $ads->id }})"></i>
                                        <i class="fas fa-spinner fa-spin text-xs" wire:loading wire:target="syncAnuncio({{ $ads->id }})"></i>
                                    </button>
                                @endif
                                @if($ads->marketplace === 'mercadolivre' && !empty($ads->json_data['catalog_product_id']))
                                    <button wire:click="openRepricerModal({{ $ads->id }})" class="w-8 h-8 flex items-center justify-center {{ optional($ads)->repricerConfig?->is_active ? 'text-indigo-400 bg-indigo-500/20 border-indigo-500/30' : 'text-slate-500 bg-slate-800 border-slate-700' }} hover:scale-105 rounded-lg transition-all border" title="IA Repricer">
                                        <i class="fas fa-robot text-xs"></i>
                                    </button>
                                @endif
                                <button wire:click="openJsonModal({{ $ads->id }})" class="w-8 h-8 flex items-center justify-center text-slate-400 bg-slate-800 hover:bg-slate-700 rounded-lg transition-all border border-slate-700/50" title="JSON">
                                    <i class="fas fa-code text-xs"></i>
                                </button>
                                @if($isCatalogo && $urlConcorrentes)
                                    <a href="{{ $urlConcorrentes }}" target="_blank" class="w-8 h-8 flex items-center justify-center text-amber-500 bg-amber-500/10 hover:bg-amber-500/20 rounded-lg transition-all border border-amber-500/20" title="Concorrentes">
                                        <i class="fas fa-chart-line text-xs"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <span class="inline-flex px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider 
                                    {{ $ads->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-500 border border-slate-700' }}">
                                    {{ $ads->status === 'active' ? 'Ativo' : 'Inativo' }}
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <button wire:click="editarAnuncio({{ $ads->id }})" class="p-2.5 bg-yellow-500 hover:bg-yellow-400 text-black rounded-lg transition-all shadow-lg active:scale-95" title="Gerenciar">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                    <button wire:click="toggleStatus({{ $ads->id }})" class="p-2.5 {{ $ads->status === 'active' ? 'bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30' : 'bg-rose-500/20 text-rose-400 hover:bg-rose-500/30' }} rounded-lg transition-all border {{ $ads->status === 'active' ? 'border-emerald-500/30' : 'border-rose-500/30' }}" title="Alternar Status">
                                        <i class="fas fa-power-off text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center gap-3">
                                <div class="w-20 h-20 rounded-full bg-slate-800/50 flex items-center justify-center border border-slate-700/50">
                                    <i class="fas fa-inbox text-4xl text-slate-600"></i>
                                </div>
                                <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">Nenhum anúncio encontrado neste filtro</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($anuncios->hasPages())
        <div class="p-6 bg-black/20 border-t border-slate-800 backdrop-blur-md">
            {{ $anuncios->links() }}
        </div>
        @endif
    </div>
    @endif

    <!-- Componente de Edição -->
    <livewire:integrations.editar-anuncio />

    <!-- Modal Vincular Produto -->
    @if($showVincularModal && $anuncioSelecionado)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" wire:click="$set('showVincularModal', false)"></div>
        <div class="relative bg-slate-900/90 backdrop-blur-2xl border border-slate-800 rounded-3xl shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-300">
            <!-- Header -->
            <div class="p-6 border-b border-slate-800/50 flex items-center justify-between bg-gradient-to-r from-yellow-500/10 to-transparent">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-yellow-500 flex items-center justify-center text-black shadow-lg shadow-yellow-500/20">
                        <i class="fas fa-link text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-white italic tracking-tighter uppercase">Vincular a Produto ERP</h3>
                        <p class="text-sm text-slate-400 font-bold max-w-sm truncate">{{ $anuncioSelecionado->titulo }}</p>
                    </div>
                </div>
                <button wire:click="$set('showVincularModal', false)" class="w-10 h-10 flex items-center justify-center hover:bg-slate-800 rounded-xl text-slate-500 transition-colors border border-slate-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flex-1 overflow-auto p-6 space-y-6">
                <!-- Busca -->
                <div class="relative group">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-yellow-500 transition-colors"></i>
                    <input wire:model.live="searchProduto" type="text" placeholder="Localizar produto por nome, EAN ou SKU..." 
                           class="w-full bg-black/40 border border-slate-800 rounded-2xl pl-12 pr-4 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-500/50 transition-all outline-none">
                </div>
                
                <!-- Resultados -->
                <div class="space-y-3">
                    <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-2">Sugestões Encontradas</div>
                    @forelse($produtos as $produto)
                    <button wire:click="vincularProduto({{ $produto->id }})" class="w-full p-4 bg-slate-950/50 border border-slate-800 rounded-2xl hover:bg-yellow-500/10 hover:border-yellow-500/30 transition-all group/item text-left">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 overflow-hidden group-hover/item:border-yellow-500/30 transition-colors">
                                    @if($produto->imagem)
                                        <img src="{{ $produto->imagem }}" alt="" class="w-full h-full object-contain opacity-70 group-hover/item:opacity-100 transition-opacity">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fas fa-box text-slate-700"></i>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-black text-white group-hover/item:text-yellow-500 transition-colors">{{ $produto->nome }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] bg-slate-800 text-slate-400 px-2 py-0.5 rounded border border-slate-700 uppercase font-black">SKU: {{ $produto->skus->first()?->sku ?? 'N/A' }}</span>
                                        @if($produto->referencia)
                                            <span class="text-[10px] text-slate-600 font-bold italic">REF: {{ $produto->referencia }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-black text-white italic">R$ {{ number_format($produto->preco_venda, 2, ',', '.') }}</p>
                                <p class="text-[10px] text-emerald-400 font-black uppercase tracking-tighter">Estoque: {{ $produto->skus->sum('estoque') }} UN</p>
                            </div>
                        </div>
                    </button>
                    @empty
                    <div class="text-center py-12 flex flex-col items-center gap-4">
                        <div class="w-20 h-20 rounded-full bg-slate-800/30 flex items-center justify-center">
                            <i class="fas fa-search-minus text-4xl text-slate-700"></i>
                        </div>
                        <div class="space-y-1">
                            <p class="text-slate-500 font-black uppercase tracking-widest">Nenhum produto localizado</p>
                            <p class="text-[11px] text-slate-600 font-bold">Refine sua busca para vincular este anúncio</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Repricer -->
    @if($showRepricerModal)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" wire:click="$set('showRepricerModal', false)"></div>
        <div class="relative bg-slate-900/90 backdrop-blur-2xl border border-slate-800 rounded-[2.5rem] shadow-2xl max-w-lg w-full overflow-hidden flex flex-col animate-in fade-in zoom-in duration-300">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-slate-800/50 flex items-center justify-between bg-gradient-to-r from-indigo-500/10 to-transparent">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-600/20">
                        <i class="fas fa-robot text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-white italic tracking-tighter uppercase">Nexus Repricer IA</h3>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Otmização de Catálogo Mercado Livre</p>
                    </div>
                </div>
                <button wire:click="$set('showRepricerModal', false)" class="w-10 h-10 flex items-center justify-center hover:bg-slate-800 rounded-xl text-slate-500 transition-colors border border-slate-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-8 overflow-y-auto max-h-[70vh] space-y-8 no-scrollbar">
                <!-- Ativar/Desativar -->
                <div class="flex items-center justify-between p-5 bg-indigo-500/5 rounded-3xl border border-indigo-500/20 shadow-inner">
                    <div class="space-y-0.5">
                        <div class="font-black text-white uppercase italic tracking-tighter text-base">Motor de Inteligência</div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Sincronização em Tempo Real</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer scale-110">
                        <input type="checkbox" wire:model.live="repricerConfig.is_active" class="sr-only peer">
                        <div class="w-14 h-7 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-slate-400 after:rounded-full after:h-[21px] after:w-[21px] after:transition-all peer-checked:bg-indigo-600 after:shadow-lg peer-checked:after:bg-white"></div>
                    </label>
                </div>

                <!-- Estratégia -->
                <div class="space-y-4">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">Modo de Combate</label>
                    <div class="grid grid-cols-1 gap-3">
                        <label class="relative p-5 border rounded-3xl cursor-pointer transition-all {{ $repricerConfig['strategy'] === 'igualar_menor' ? 'bg-indigo-500/10 border-indigo-500/50 shadow-lg shadow-indigo-500/5' : 'bg-black/20 border-slate-800 hover:border-slate-700' }}">
                            <input type="radio" wire:model.live="repricerConfig.strategy" value="igualar_menor" class="sr-only">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center border {{ $repricerConfig['strategy'] === 'igualar_menor' ? 'border-indigo-500' : 'border-slate-800' }}">
                                    <i class="fas fa-equals {{ $repricerConfig['strategy'] === 'igualar_menor' ? 'text-indigo-400' : 'text-slate-600' }}"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-black text-white italic">Igualar ao menor preço</div>
                                    <div class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">Padrão de Mercado</div>
                                </div>
                                @if($repricerConfig['strategy'] === 'igualar_menor')
                                    <i class="fas fa-check-circle text-indigo-500"></i>
                                @endif
                            </div>
                        </label>
                        <label class="relative p-5 border rounded-3xl cursor-pointer transition-all {{ $repricerConfig['strategy'] === 'valor_abaixo' ? 'bg-rose-500/5 border-rose-500/30' : 'bg-black/20 border-slate-800 hover:border-slate-700' }}">
                            <input type="radio" wire:model.live="repricerConfig.strategy" value="valor_abaixo" class="sr-only">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center border {{ $repricerConfig['strategy'] === 'valor_abaixo' ? 'border-rose-500/50' : 'border-slate-800' }}">
                                    <i class="fas fa-minus {{ $repricerConfig['strategy'] === 'valor_abaixo' ? 'text-rose-400' : 'text-slate-600' }}"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-black text-white italic">Ficar um valor abaixo (R$)</div>
                                    <div class="text-[9px] text-slate-500 font-bold uppercase tracking-widest uppercase">Modo Agressivo</div>
                                </div>
                                @if($repricerConfig['strategy'] === 'valor_abaixo')
                                    <i class="fas fa-bolt text-rose-500"></i>
                                @endif
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Offset Value -->
                @if($repricerConfig['strategy'] !== 'igualar_menor')
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-2">Diferença de Valor (R$)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-black">R$</span>
                        <input type="number" step="0.01" wire:model.live="repricerConfig.offset_value" 
                               class="w-full bg-black/40 border border-slate-800 rounded-2xl pl-12 pr-4 py-4 text-sm font-black text-white focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500/50 outline-none transition-all">
                    </div>
                </div>
                @endif

                <!-- Margens de Segurança -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-rose-500/80 uppercase tracking-widest ml-1">Margem Mínima (%)</label>
                        <input type="number" step="0.1" wire:model.live="repricerConfig.min_profit_margin" 
                               class="w-full bg-black/40 border border-slate-800 rounded-2xl px-4 py-4 text-sm font-black text-white focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500/50 outline-none transition-all" placeholder="Ex: 5%">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-emerald-500/80 uppercase tracking-widest ml-1">Margem Máxima (%)</label>
                        <input type="number" step="0.1" wire:model.live="repricerConfig.max_profit_margin" 
                               class="w-full bg-black/40 border border-slate-800 rounded-2xl px-4 py-4 text-sm font-black text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500/50 outline-none transition-all" placeholder="Ex: 40%">
                    </div>
                </div>

                <!-- Filtros de Competição -->
                <div class="border-t border-slate-800/50 pt-8 space-y-4">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2 italic">Barreiras de Proteção</label>
                    <div class="grid grid-cols-1 gap-3">
                        <label class="flex items-center gap-4 p-5 bg-black/20 border border-slate-800 rounded-3xl cursor-pointer hover:bg-black/40 transition-all group">
                            <input type="checkbox" wire:model.live="repricerConfig.filter_full_only" class="rounded-lg border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500/20 w-5 h-5">
                            <div>
                                <span class="text-sm font-black text-white italic">Competir apenas com "Full"</span>
                                <p class="text-[9px] text-slate-500 font-bold uppercase mt-0.5">Ignora vendedores que não usam fulfillment.</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-4 p-5 bg-black/20 border border-slate-800 rounded-3xl cursor-pointer hover:bg-black/40 transition-all group">
                            <input type="checkbox" wire:model.live="repricerConfig.filter_premium_only" class="rounded-lg border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500/20 w-5 h-5">
                            <div>
                                <span class="text-sm font-black text-white italic">Competir apenas com "Premium"</span>
                                <p class="text-[9px] text-slate-500 font-bold uppercase mt-0.5">Ignora vendedores em anúncios Clássicos.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-8 bg-black/40 border-t border-slate-800 flex items-center justify-between">
                <button wire:click="$set('showRepricerModal', false)" class="text-sm font-black text-slate-500 hover:text-white transition-colors uppercase tracking-widest">
                    Descartar
                </button>
                <button wire:click="saveRepricerConfig" class="px-10 py-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl text-sm font-black shadow-2xl shadow-indigo-600/20 transition-all active:scale-95 italic uppercase tracking-tighter">
                    Ativar nexus repricer
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal JSON -->
    @if($showJsonModal && $jsonData)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" wire:click="$set('showJsonModal', false)"></div>
        <div class="relative bg-slate-900/95 backdrop-blur-2xl border border-slate-800 rounded-[2.5rem] shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-300">
            <!-- Header -->
            <div class="p-8 border-b border-slate-800/50 flex items-center justify-between bg-gradient-to-r from-slate-500/10 to-transparent">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-700 shadow-lg">
                        <i class="fas fa-code text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-white italic tracking-tighter uppercase">API Technical Data</h3>
                        <p class="text-sm text-slate-400 font-bold max-w-sm truncate">{{ $jsonAdTitulo }}</p>
                    </div>
                </div>
                <button wire:click="$set('showJsonModal', false)" class="w-10 h-10 flex items-center justify-center hover:bg-slate-800 rounded-xl text-slate-500 transition-colors border border-slate-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-auto p-8 no-scrollbar bg-black/20">
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500/20 to-purple-500/20 rounded-3xl blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>
                    <pre class="relative text-[11px] bg-black/60 p-6 rounded-2xl overflow-auto text-indigo-300 font-mono border border-slate-800 space-y-1 custom-scrollbar"><code>{{ json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-8 border-t border-slate-800 bg-black/40">
                <button wire:click="$set('showJsonModal', false)" class="w-full py-4 bg-slate-800 hover:bg-slate-700 text-white rounded-2xl font-black uppercase tracking-[0.2em] transition-all shadow-xl active:scale-[0.98]">
                    Fechar Inspeção
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
