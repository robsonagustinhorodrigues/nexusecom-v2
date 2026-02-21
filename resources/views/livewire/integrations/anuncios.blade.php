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

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center shadow-lg shadow-yellow-500/20">
                    <i class="fas fa-bullhorn text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Meus Anúncios</h2>
                    <p class="text-sm text-slate-500">Gerencie anúncios dos marketplaces</p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <!-- Toggle View Mode -->
            <div class="flex bg-slate-100 dark:bg-dark-800 rounded-xl p-1">
                <button 
                    wire:click="$set('viewMode', 'cards')"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all {{ $viewMode === 'cards' ? 'bg-white dark:bg-dark-700 text-slate-900 dark:text-white shadow' : 'text-slate-500' }}"
                >
                    <i class="fas fa-th-large mr-1"></i> Cards
                </button>
                <button 
                    wire:click="$set('viewMode', 'table')"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all {{ $viewMode === 'table' ? 'bg-white dark:bg-dark-700 text-slate-900 dark:text-white shadow' : 'text-slate-500' }}"
                >
                    <i class="fas fa-list mr-1"></i> Tabela
                </button>
            </div>
            
            <select wire:model.live="integracao_id" class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm focus:border-yellow-500">
                <option value="">Todas as Contas</option>
                @foreach($integracoes as $int)
                    <option value="{{ $int->id }}">{{ $int->nome_conta }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
        <div class="flex flex-wrap items-center gap-4">
            <!-- Busca -->
            <div class="relative flex-1 min-w-[200px] max-w-md">
                <input wire:model.live="search" type="text" placeholder="Buscar por título, ID ou SKU..." class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 pl-10">
                <i class="fas fa-search absolute left-4 top-3 text-slate-400"></i>
            </div>
            
            <!-- Filtro Status -->
            <select wire:model.live="status_filtro" class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm focus:border-yellow-500">
                <option value="">Status: Todos</option>
                <option value="ativo">Ativos</option>
                <option value="inativo">Inativos</option>
            </select>
            
            <!-- Filtro Tipo -->
            <select wire:model.live="tipo_filtro" class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm focus:border-yellow-500">
                <option value="">Tipo: Todos</option>
                <option value="catalogo">Catálogo</option>
                <option value="normal">Normal</option>
            </select>
            
            <!-- Filtro Vinculo -->
            <select wire:model.live="vinculo_filtro" class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm focus:border-yellow-500">
                <option value="">Vínculo: Todos</option>
                <option value="vinculado">Vinculados</option>
                <option value="nao_vinculado">Não Vinculados</option>
            </select>
        </div>
    </div>

    <!-- Cards de Totais -->
    @php
    $isCatalogoFn = function($jsonData) { return $this->isCatalogo($jsonData); };
    $selectedIntegration = null;
    $isBling = $selectedIntegration && $selectedIntegration->marketplace === 'bling';
    @endphp
    
    @if($isBling)
    <div class="bg-blue-500/10 border border-blue-500/20 text-blue-400 px-4 py-4 rounded-xl flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fas fa-warehouse text-xl"></i>
            <div>
                <div class="font-semibold">Bling ERP selecionado</div>
                <div class="text-sm text-blue-300">Para visualizar os produtos do Bling, vá para a página de Produtos</div>
            </div>
        </div>
        <a href="{{ route('products.index') }}" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium">
            <i class="fas fa-arrow-right mr-2"></i> Ver Produtos
        </a>
    </div>
    @else
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
            <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Total</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $anuncios->total() }}</div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
            <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Ativos</div>
            <div class="text-2xl font-bold text-emerald-500">{{ $anuncios->filter(fn($a) => ($a->status ?? '') === 'active')->count() }}</div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
            <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Catálogo</div>
            <div class="text-2xl font-bold text-amber-500">{{ $anuncios->filter(fn($a) => $isCatalogoFn($a->json_data ?? []))->count() }}</div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
            <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Vinculados</div>
            <div class="text-2xl font-bold text-indigo-500">{{ $anuncios->filter(fn($a) => ($a->product_sku_id ?? null))->count() }}</div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
            <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">ML</div>
            <div class="text-2xl font-bold text-yellow-500">{{ $anuncios->filter(fn($a) => ($a->marketplace ?? '') === 'mercadolivre')->count() }}</div>
        </div>
    </div>
    @endif

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
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <!-- Header com cor do Marketplace -->
            <div class="relative h-32 bg-gradient-to-br {{ $meli['bg'] }}">
                <!-- Imagem -->
                <div class="absolute inset-0 flex items-center justify-center">
                    @php
                        $imagemUrl = null;
                        
                        // Verifica thumbnail normal (Mercado Livre, etc)
                        if (!empty($ads->json_data['thumbnail'])) {
                            $imagemUrl = $ads->json_data['thumbnail'];
                        }
                        // Para Amazon, usa ASIN
                        elseif ($ads->marketplace === 'amazon' && !empty($ads->json_data['ASIN'])) {
                            $imagemUrl = 'https://images-na.ssl-images-amazon.com/images/I/' . $ads->json_data['ASIN'] . '._AC_US400_.jpg';
                        }
                        // Para produto vinculado
                        elseif ($ads->productSku?->product?->imagem) {
                            $imagemUrl = is_object($ads->productSku) ? $ads->productSku?->product?->imagem : null;
                        }
                    @endphp
                    
                    @if($imagemUrl)
                        <img src="{{ $imagemUrl }}" alt="" class="w-full h-full object-cover opacity-90" 
                             onerror="this.parentElement.innerHTML='<i class=fas fa-image text-5xl text-white/30></i>'">
                    @else
                        <i class="fas fa-image text-5xl text-white/30"></i>
                    @endif
                </div>
                
                <!-- Badge Marketplace -->
                <div class="absolute top-2 left-2">
                    @if(!empty($meli['logo']))
                        <img src="{{ $meli['logo'] }}" alt="{{ $meli['nome'] }}" class="h-6 w-auto">
                    @else
                        <span class="px-2 py-1 rounded-lg text-xs font-bold bg-white/20 backdrop-blur-sm text-white flex items-center gap-1">
                            <i class="{{ $meli['icone'] }}"></i>
                            {{ $meli['nome'] }}
                        </span>
                    @endif
                </div>
                
                <!-- Badge Status + Catálogo -->
                <div class="absolute top-2 right-2 flex flex-col gap-1 items-end">
                    @if($isCatalogo)
                        <span class="px-2 py-1 rounded-lg text-xs font-bold bg-amber-500 text-white shadow-lg flex items-center gap-1">
                            <i class="fas fa-book"></i> Catálogo
                        </span>
                    @endif
                    @if($ads->status === 'active')
                        <span class="px-2 py-1 rounded-lg text-xs font-bold bg-emerald-500 text-white shadow-lg">Ativo</span>
                    @else
                        <span class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-400 text-white shadow-lg">Inativo</span>
                    @endif
                    @if(optional($ads)->repricerConfig?->is_active)
                        <span class="px-2 py-1 rounded-lg text-xs font-bold bg-indigo-600 text-white shadow-lg flex items-center gap-1" title="Repricer Automático Ativo">
                            <i class="fas fa-robot"></i> ROBÔ
                        </span>
                    @endif
                </div>
            </div>
            
            <!-- Conteúdo -->
            <div class="p-4">
                <!-- Título -->
                <h3 class="text-sm font-bold text-slate-900 dark:text-white line-clamp-2 mb-2" title="{{ $ads->titulo }}">
                    {{ $ads->titulo }}
                </h3>
                
                <!-- ID e SKU -->
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-xs text-slate-500">{{ $ads->external_id }}</span>
                    @if($skuMl)
                        <span class="text-xs font-mono bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded">
                            ML: {{ $skuMl }}
                        </span>
                    @endif
                </div>
                
                <!-- Preço e Lucratividade -->
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div>
                        <div class="text-xs text-slate-500">Preço</div>
                        @if($ads->promocao_valor)
                        <div class="text-lg font-bold text-rose-600 dark:text-rose-400">R$ {{ number_format($ads->promocao_valor, 2, ',', '.') }}</div>
                        <div class="text-xs text-slate-400 line-through">R$ {{ number_format($ads->preco_original ?: $ads->preco, 2, ',', '.') }}</div>
                        @else
                        <div class="text-lg font-bold text-slate-900 dark:text-white">R$ {{ number_format($lucro['preco'], 2, ',', '.') }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Lucro</div>
                        <div class="text-lg font-bold {{ $lucro['margem'] > 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                            R$ {{ number_format($lucro['lucro_bruto'], 2, ',', '.') }}
                            <span class="text-xs">({{ number_format($lucro['margem'], 1) }}%)</span>
                        </div>
                    </div>
                </div>
                
                <!-- Taxas e Frete -->
                <div class="text-xs text-slate-500 mb-3 bg-slate-50 dark:bg-dark-800 rounded-lg p-2">
                    <div class="flex justify-between">
                        <span>Taxa ({{ number_format($lucro['taxas'] / max($lucro['preco'], 0.01) * 100, 1) }}%):</span>
                        <span class="text-rose-500 font-medium">- R$ {{ number_format($lucro['taxas'], 2, ',', '.') }}</span>
                    </div>
                    @if(isset($lucro['frete']))
                    <div class="flex justify-between">
                        <span class="flex items-center gap-1">
                            Frete:
                            @if(isset($lucro['frete_gratis']) && $lucro['frete_gratis'])
                                <span class="text-[10px] bg-emerald-500/20 text-emerald-400 px-1 rounded border border-emerald-500/20 font-bold">FREE</span>
                            @elseif(isset($lucro['frete_type']))
                                @if($lucro['frete_type'] === 'fulfillment')
                                    <span class="text-[10px] bg-emerald-500/20 text-emerald-400 px-1 rounded border border-emerald-500/20 font-bold">FULL</span>
                                @else
                                    <span class="text-[10px] bg-amber-500/10 text-amber-500 px-1 rounded border border-amber-500/10">{{ $lucro['frete_type'] }}</span>
                                @endif
                            @elseif(isset($lucro['frete_source']) && $lucro['frete_source'] === 'api')
                                <span class="text-[10px] bg-emerald-500/10 text-emerald-500 px-1 rounded border border-emerald-500/10" title="Valor real via API">API</span>
                            @else
                                <span class="text-[10px] bg-amber-500/10 text-amber-500 px-1 rounded border border-amber-500/10" title="Valor estimado via sistema">EST</span>
                            @endif
                        </span>
                        @if(isset($lucro['frete_gratis']) && $lucro['frete_gratis'])
                            <span class="text-emerald-400 font-bold">GRÁTIS</span>
                        @else
                            <span class="text-rose-500 font-medium">- R$ {{ number_format($lucro['frete'] ?? 0, 2, ',', '.') }}</span>
                        @endif
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span>Imposto (10%):</span>
                        <span class="text-rose-500 font-medium">- R$ {{ number_format($lucro['imposto'] ?? 0, 2, ',', '.') }}</span>
                    </div>
                    @if(is_object($ads->productSku) && !empty($ads->productSku?->product?->preco_custo))
                    <div class="flex justify-between">
                        <span>Custo:</span>
                        <span class="text-rose-500 font-medium">- R$ {{ number_format($lucro['custo'] ?? 0, 2, ',', '.') }}</span>
                    </div>
                    @endif
                </div>
                
                <!-- Medidas Embalagem -->
                @if(count($medidas) > 0)
                <div class="text-xs text-slate-500 mb-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-2">
                    <div class="font-semibold text-amber-700 dark:text-amber-400 mb-1">
                        <i class="fas fa-box mr-1"></i> Embalagem
                    </div>
                    <div class="grid grid-cols-2 gap-1">
                        @if(!empty($medidas['altura']))
                        <span>📦 A: {{ $medidas['altura'] }}</span>
                        @endif
                        @if(!empty($medidas['largura']))
                        <span>L: {{ $medidas['largura'] }}</span>
                        @endif
                        @if(!empty($medidas['comprimento']))
                        <span>C: {{ $medidas['comprimento'] }}</span>
                        @endif
                        @if(!empty($medidas['peso']))
                        <span>⚖️ {{ $medidas['peso'] }}</span>
                        @endif
                    </div>
                </div>
                @endif
                
                <!-- Info Promoção -->
                @if($ads->promocao_valor)
                @php
                $lucroComPromo = null;
                $lucroSemPromo = $lucro['lucro_bruto'];
                if ($ads->preco_original > 0) {
                    $precoBase = $ads->preco_original;
                    $custo = $lucro['custo'];
                    $frete = $lucro['frete'] ?? 0;
                    $taxas = $precoBase * 0.12; // Taxa ML ~12%
                    $imposto = $precoBase * 0.10; // Imposto ~10%
                    $lucroComPromo = $precoBase - $custo - $taxas - $frete - $imposto;
                }
                $tipoIcone = match($ads->promocao_tipo) {
                    'DEAL' => '🔥',
                    'DOD' => '⚡',
                    'LIGHTNING' => '⚡',
                    'MARKETPLACE_CAMPAIGN' => '🎯',
                    'PRICE_DISCOUNT' => '💰',
                    'SELLER_CAMPAIGN' => '🏷️',
                    default => '🏷️'
                };
                @endphp
                <div class="text-xs text-slate-500 mb-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-lg p-2">
                    <div class="font-semibold text-rose-700 dark:text-rose-400 mb-1">
                        <i class="fas fa-tag mr-1"></i> Promoção Ativa
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg">{{ $tipoIcone }}</span>
                        <span class="font-bold text-rose-600">{{ $ads->promocao_tipo }}</span>
                        <span class="bg-rose-500 text-white px-2 py-0.5 rounded-full text-xs font-bold">
                            -{{ number_format($ads->promocao_desconto, 0) }}%
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-1">
                        <div>
                            <span class="text-slate-500">Sem promoção:</span>
                            <span class="font-medium text-slate-700">R$ {{ number_format($lucroSemPromo, 2, ',', '.') }}</span>
                        </div>
                        @if($lucroComPromo !== null)
                        <div>
                            <span class="text-rose-500 font-medium">Com promoção:</span>
                            <span class="font-bold text-rose-600">R$ {{ number_format($lucroComPromo, 2, ',', '.') }}</span>
                        </div>
                        @endif
                    </div>
                    @if($ads->promocao_fim)
                    <div class="mt-1 text-[10px] text-slate-500">
                        <i class="fas fa-clock mr-1"></i>Validade: {{ \Carbon\Carbon::parse($ads->promocao_fim)->format('d/m H:i') }}
                    </div>
                    @endif
                </div>
                @endif
                
                <!-- Info Envio -->
                @php
                $shipping = $this->getShippingInfo($ads->json_data ?? []);
                $freteCustoSeller = $ads->frete_custo_seller ?? null;
                $freteSource = $ads->frete_source ?? null;
                $freteUpdatedAt = null;
                if (!empty($ads->frete_updated_at)) {
                    $freteUpdatedAt = is_string($ads->frete_updated_at) 
                        ? \Carbon\Carbon::parse($ads->frete_updated_at)->format('d/m H:i')
                        : $ads->frete_updated_at->format('d/m H:i');
                }
                @endphp
                <div class="text-xs text-slate-500 mb-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-2">
                    <div class="font-semibold text-blue-700 dark:text-blue-400 mb-1">
                        <i class="fas fa-truck mr-1"></i> Frete
                    </div>
                    <div class="flex flex-col gap-1">
                        <span>{{ $shipping['logistic_name'] }}</span>
                        @if($shipping['free_shipping'])
                        <span class="text-emerald-600"><i class="fas fa-check-circle mr-1"></i>Frete Grátis</span>
                        @endif
                        @if($freteCustoSeller !== null && $freteCustoSeller > 0)
                        <span class="text-amber-600 font-medium">
                            <i class="fas fa-money-bill mr-1"></i>Custo: R$ {{ number_format($freteCustoSeller, 2, ',', '.') }}
                        </span>
                        @elseif($freteCustoSeller === 0)
                        <span class="text-emerald-600">
                            <i class="fas fa-check mr-1"></i>Custo: Grátis
                        </span>
                        @endif
                        @if($freteUpdatedAt)
                        <span class="text-slate-400 text-[10px]">Atualizado: {{ $freteUpdatedAt }}</span>
                        @endif
                        @if($shipping['local_pick_up'])
                        <span class="text-slate-600"><i class="fas fa-store mr-1"></i>Retirada</span>
                        @endif
                    </div>
                </div>
                
                <!-- Estoque e Vínculo -->
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">Estoque:</span>
                        <span class="font-bold {{ $ads->estoque > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $ads->estoque ?? 0 }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($ads->product_sku_id && is_object($ads->productSku))
                            <span class="text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 px-2 py-1 rounded" title="{{ is_object($ads->productSku) ? $ads->productSku?->product?->nome : $ads->sku }}">
                                {{ is_object($ads->productSku) ? $ads->sku->sku : $ads->sku }}
                            </span>
                        @else
                            <button wire:click="abrirVincular({{ $ads->id }})" class="text-xs bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 px-2 py-1 rounded hover:bg-amber-200 dark:hover:bg-amber-800 transition-colors">
                                <i class="fas fa-link mr-1"></i> Vincular
                            </button>
                        @endif
                    </div>
                </div>
                
                <!-- Ações -->
                <div class="flex items-center gap-2 pt-3 border-t border-slate-200 dark:border-dark-700">
                    <!-- Criar Produto -->
                    @if(!$ads->produto_id)
                        <button wire:click="importAsProduct({{ $ads->id }})" wire:confirm="Criar produto a partir deste anúncio?" class="flex-1 px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-xs font-medium text-center transition-colors">
                            <i class="fas fa-plus mr-1"></i> Criar Produto
                        </button>
                    @else
                        <span class="text-xs text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-1 rounded">
                            <i class="fas fa-check-circle mr-1"></i> Vinculado
                        </span>
                    @endif
                    
                    <!-- Editar -->
                    <button wire:click="editarAnuncio({{ $ads->id }})" class="flex-1 px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-xs font-medium text-center transition-colors">
                        <i class="fas fa-edit mr-1"></i> Editar
                    </button>
                    
                    <!-- Ver no ML -->
                    @php
                        $jsonData = is_array($ads->json_data) ? $ads->json_data : json_decode($ads->json_data ?? '{}', true);
                        $permalink = $ads->url_anuncio ?? ($jsonData['permalink'] ?? null);
                    @endphp
                    @if($permalink)
                        <a href="{{ $permalink }}" target="_blank" class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-lg transition-colors" title="Ver no ML">
                            <i class="fas fa-external-link-alt text-xs"></i>
                        </a>
                    @endif
                    
                    <!-- Preços Concorrentes (apenas catálogo ML) -->
                    @if($isCatalogo && $urlConcorrentes)
                        <a href="{{ $urlConcorrentes }}" target="_blank" class="p-2 text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-500/10 rounded-lg transition-colors" title="Ver preços concorrentes">
                            <i class="fas fa-chart-line text-xs"></i>
                        </a>
                    @endif
                    
                    <!-- JSON -->
                    <button wire:click="openJsonModal({{ $ads->id }})" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-dark-800 rounded-lg transition-colors" title="Ver JSON">
                        <i class="fas fa-code text-xs"></i>
                    </button>
                    
                    <!-- Status -->
                    <button wire:click="toggleStatus({{ $ads->id }})" class="p-2 {{ $ads->status === 'active' ? 'text-emerald-500 hover:bg-emerald-50' : 'text-slate-400 hover:bg-slate-100' }} dark:hover:bg-dark-800 rounded-lg transition-colors" title="{{ $ads->status === 'active' ? 'Desativar' : 'Ativar' }}">
                        <i class="fas fa-power-off text-xs"></i>
                    </button>

                    <!-- Sync Anuncio (Apenas ML) -->
                    @if($ads->marketplace === 'mercadolivre')
                        <button wire:click="syncAnuncio({{ $ads->id }})" wire:loading.attr="disabled" class="p-2 text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-500/10 rounded-lg transition-colors" title="Sincronizar Anúncio (dados + frete)">
                            <i class="fas fa-sync-alt text-xs" wire:loading.remove wire:target="syncAnuncio({{ $ads->id }})"></i>
                            <i class="fas fa-spinner fa-spin text-xs" wire:loading wire:target="syncAnuncio({{ $ads->id }})"></i>
                        </button>
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
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px]">
                <thead class="bg-slate-50 dark:bg-dark-950 border-b border-slate-200 dark:border-dark-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Anúncio</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Marketplace</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">SKU ML</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Preço</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Taxa</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Imposto</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Estoque</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Catálogo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Vínculo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Ações</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-dark-800">
                    @forelse($anuncios as $ads)
                    @php
                        $medidas = $this->getMedidas($ads->json_data ?? []);
                        $skuMl = $this->getSkuMl($ads->json_data ?? []);
                        $tipo = $this->getTipoAnuncio($ads->json_data ?? []);
                        $meli = $this->getMarketplaceInfo($ads->marketplace ?? 'mercadolivre');
                        $isCatalogo = $isCatalogoFn($ads->json_data ?? []);
                        $urlConcorrentes = $this->getUrlConcorrentes($ads);
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-slate-100 dark:bg-dark-950 flex items-center justify-center border border-slate-200 dark:border-dark-700 overflow-hidden">
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
                                        <img src="{{ $thumbUrl }}" alt="" class="w-full h-full object-cover" 
                                             onerror="this.parentElement.innerHTML='<i class=fas fa-image text-slate-400></i>'">
                                    @else
                                        <i class="fas fa-image text-slate-400"></i>
    @endif
</div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white truncate max-w-xs">{{ $ads->titulo }}</span>
                                    <span class="text-xs text-slate-500">{{ $ads->external_id }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if(!empty($meli['logo']))
                                <img src="{{ $meli['logo'] }}" alt="{{ $meli['nome'] }}" class="h-5 w-auto mx-auto">
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-{{ $meli['cor'] }}-500/10 text-{{ $meli['cor'] }}-500">
                                    <i class="{{ $meli['icone'] }}"></i>
                                    {{ $meli['nome'] }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($skuMl)
                                <span class="text-xs font-mono bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 px-2 py-1 rounded">
                                    {{ $skuMl }}
                                </span>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">R$ {{ number_format($this->getPreco($ads), 2, ',', '.') }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-xs">
                            @php $tableLucro = $this->calcularLucratividade($ads); @endphp
                            <div class="flex flex-col items-end">
                                <span class="text-rose-500">R$ {{ number_format($tableLucro['taxas'], 2, ',', '.') }}</span>
                                @if($tableLucro['frete'] > 0)
                                    <span class="text-rose-500 text-[10px]">- R$ {{ number_format($tableLucro['frete'], 2, ',', '.') }} (Frete)</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right text-xs">
                            <span class="text-amber-600">R$ {{ number_format($tableLucro['imposto'], 2, ',', '.') }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold {{ $ads->estoque > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $ads->estoque ?? 0 }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-{{ $tipo['cor'] }}-500/10 text-{{ $tipo['cor'] }}-500">
                                <i class="fas fa-{{ $tipo['icone'] }}"></i>
                                {{ $tipo['nome'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($isCatalogo)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-500/10 text-amber-500">
                                    <i class="fas fa-book"></i>
                                    @if($urlConcorrentes)
                                        <a href="{{ $urlConcorrentes }}" target="_blank" class="hover:underline">Sim</a>
                                    @else
                                        Sim
                                    @endif
                                </span>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($ads->product_sku_id && $ads->sku)
                                <span class="text-xs font-mono text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 px-2 py-1 rounded">
                                    {{ is_object($ads->productSku) ? $ads->sku->sku : $ads->sku }}
                                </span>
                            @else
                                <button wire:click="abrirVincular({{ $ads->id }})" class="text-xs bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 px-2 py-1 rounded hover:bg-amber-200 dark:hover:bg-amber-800 transition-colors">
                                    <i class="fas fa-link mr-1"></i> Vincular
                                </button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="editarAnuncio({{ $ads->id }})" class="p-2 rounded-lg text-yellow-500 hover:bg-yellow-500/10 transition-all" title="Editar">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                                @php
                                    $tableJsonData = is_array($ads->json_data) ? $ads->json_data : json_decode($ads->json_data ?? '{}', true);
                                    $tablePermalink = $ads->url_anuncio ?? ($tableJsonData['permalink'] ?? null);
                                @endphp
                                @if($ads->url_anuncio || $tableJsonData['permalink'] ?? null)
                                    <a href="{{ $tablePermalink }}" target="_blank" class="p-2 rounded-lg text-blue-500 hover:bg-blue-500/10 transition-all" title="Ver no ML">
                                        <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>
                                @endif
                                @if($isCatalogo && $urlConcorrentes)
                                    <a href="{{ $urlConcorrentes }}" target="_blank" class="p-2 rounded-lg text-amber-500 hover:bg-amber-500/10 transition-all" title="Concorrentes">
                                        <i class="fas fa-chart-line text-xs"></i>
                                    </a>
                                @endif
                                <button wire:click="openJsonModal({{ $ads->id }})" class="p-2 rounded-lg text-slate-400 hover:bg-slate-500/10 transition-all" title="Ver JSON">
                                    <i class="fas fa-code text-xs"></i>
                                </button>
                                <button wire:click="toggleStatus({{ $ads->id }})" class="p-2 rounded-lg {{ $ads->status === 'active' ? 'text-emerald-500 hover:bg-emerald-500/10' : 'text-slate-400 hover:bg-slate-500/10' }} transition-all" title="{{ $ads->status === 'active' ? 'Desativar' : 'Ativar' }}">
                                    <i class="fas fa-power-off text-xs"></i>
                                </button>
                                @if($ads->marketplace === 'mercadolivre' && !empty($ads->json_data['catalog_product_id']))
                                    <button wire:click="openRepricerModal({{ $ads->id }})" class="p-2 rounded-lg {{ optional($ads)->repricerConfig?->is_active ? 'text-indigo-500 hover:bg-indigo-500/10' : 'text-slate-400 hover:bg-slate-500/10' }} transition-all" title="Repricer">
                                        <i class="fas fa-robot text-xs"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-col items-end gap-1">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wide 
                                    {{ $ads->status === 'active' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-slate-500/10 text-slate-500 border border-slate-500/20' }}">
                                    {{ $ads->status === 'active' ? 'Ativo' : 'Inativo' }}
                                </span>
                                @if(optional($ads)->repricerConfig?->is_active)
                                    <span class="inline-flex px-2 py-0.5 bg-indigo-500/10 text-indigo-600 border border-indigo-500/20 rounded text-[10px] font-bold">
                                        REPRICER
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                            <i class="fas fa-inbox text-3xl mb-3 block"></i>
                            Nenhum anúncio encontrado
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($anuncios->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-dark-800">
            {{ $anuncios->links() }}
        </div>
        @endif
    </div>
    @endif

    <!-- Componente de Edição -->
    <livewire:integrations.editar-anuncio />

    <!-- Modal Vincular Produto -->
    @if($showVincularModal && $anuncioSelecionado)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" wire:click="$set('showVincularModal', false)"></div>
        <div class="relative bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col">
            <div class="p-6 border-b border-slate-200 dark:border-dark-800 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Vincular Produto</h3>
                    <p class="text-sm text-slate-500">{{ $anuncioSelecionado->titulo }}</p>
                </div>
                <button wire:click="$set('showVincularModal', false)" class="p-2 hover:bg-slate-100 dark:hover:bg-dark-800 rounded-lg">
                    <i class="fas fa-times text-slate-500"></i>
                </button>
            </div>
            <div class="flex-1 overflow-auto p-6">
                <!-- Busca -->
                <div class="mb-4">
                    <input wire:model.live="searchProduto" type="text" placeholder="Buscar por nome, EAN ou SKU..." class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm">
                </div>
                
                <!-- Resultados -->
                <div class="space-y-2">
                    @forelse($produtos as $produto)
                    <button wire:click="vincularProduto({{ $produto->id }})" class="w-full p-4 bg-slate-50 dark:bg-dark-800 rounded-xl hover:bg-slate-100 dark:hover:bg-dark-700 transition-colors text-left">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $produto->nome }}</p>
                                <p class="text-sm text-slate-500">SKU: {{ $produto->skus->first()?->sku ?? 'N/A' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-slate-900 dark:text-white">R$ {{ number_format($produto->preco_venda, 2, ',', '.') }}</p>
                                <p class="text-xs text-slate-500">Estoque: {{ $produto->skus->sum('estoque') }}</p>
                            </div>
                        </div>
                    </button>
                    @empty
                    <div class="text-center py-8 text-slate-500">
                        <i class="fas fa-search text-3xl mb-3 block"></i>
                        @if($searchProduto)
                            Nenhum produto encontrado
                        @else
                            Digite para buscar produtos
                        @endif
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Repricer -->
    @if($showRepricerModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showRepricerModal', false)"></div>
        <div class="relative bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden flex flex-col animate-in fade-in zoom-in duration-200">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-slate-100 dark:border-dark-800 flex items-center justify-between bg-gradient-to-r from-indigo-500/10 to-transparent">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                        <i class="fas fa-robot text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Repricer Inteligente</h3>
                        <p class="text-xs text-slate-500">Automação de preços (Catálogo ML)</p>
                    </div>
                </div>
                <button wire:click="$set('showRepricerModal', false)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 dark:hover:bg-dark-800 rounded-lg text-slate-400 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto max-h-[70vh] space-y-6">
                <!-- Ativar/Desativar -->
                <div class="flex items-center justify-between p-4 bg-indigo-50 dark:bg-indigo-500/5 rounded-2xl border border-indigo-100 dark:border-indigo-500/10">
                    <div>
                        <div class="font-bold text-slate-900 dark:text-white">Motor de Preços</div>
                        <div class="text-xs text-slate-500">Sincroniza automaticamente com a concorrência</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="repricerConfig.is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-dark-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <!-- Estratégia -->
                <div class="space-y-3">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300 block">Como deseja competir?</label>
                    <div class="grid grid-cols-1 gap-2">
                        <label class="relative p-4 border rounded-2xl cursor-pointer transition-all {{ $repricerConfig['strategy'] === 'igualar_menor' ? 'bg-indigo-50/50 border-indigo-200 dark:border-indigo-500/30' : 'border-slate-200 dark:border-dark-800 hover:bg-slate-50' }}">
                            <input type="radio" wire:model.live="repricerConfig.strategy" value="igualar_menor" class="sr-only">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-white dark:bg-dark-950 flex items-center justify-center border {{ $repricerConfig['strategy'] === 'igualar_menor' ? 'border-indigo-500' : 'border-slate-200 dark:border-dark-800' }}">
                                    <i class="fas fa-equals {{ $repricerConfig['strategy'] === 'igualar_menor' ? 'text-indigo-500' : 'text-slate-400' }}"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">Igualar ao menor preço</div>
                                    <div class="text-[10px] text-slate-500 uppercase tracking-wider">ESTRATÉGIA PADRÃO</div>
                                </div>
                            </div>
                        </label>
                        <label class="relative p-4 border rounded-2xl cursor-pointer transition-all {{ $repricerConfig['strategy'] === 'valor_abaixo' ? 'bg-indigo-50/50 border-indigo-200 dark:border-indigo-500/30' : 'border-slate-200 dark:border-dark-800 hover:bg-slate-50' }}">
                            <input type="radio" wire:model.live="repricerConfig.strategy" value="valor_abaixo" class="sr-only">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-white dark:bg-dark-950 flex items-center justify-center border {{ $repricerConfig['strategy'] === 'valor_abaixo' ? 'border-indigo-500' : 'border-slate-200 dark:border-dark-800' }}">
                                    <i class="fas fa-minus {{ $repricerConfig['strategy'] === 'valor_abaixo' ? 'text-indigo-500' : 'text-slate-400' }}"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">Ficar um valor abaixo (R$)</div>
                                    <div class="text-[10px] text-slate-500 uppercase tracking-wider">AGRESSIVO</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Offset Value -->
                @if($repricerConfig['strategy'] !== 'igualar_menor')
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-slate-500 uppercase">Diferença de Valor (R$)</label>
                    <input type="number" step="0.01" wire:model.live="repricerConfig.offset_value" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20">
                </div>
                @endif

                <!-- Margens de Segurança -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-rose-500 uppercase">Margem Mínima (%)</label>
                        <input type="number" step="0.1" wire:model.live="repricerConfig.min_profit_margin" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm" placeholder="Ex: 5%">
                        <p class="text-[10px] text-slate-500">Nunca venderá abaixo disso.</p>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-emerald-500 uppercase">Margem Máxima (%)</label>
                        <input type="number" step="0.1" wire:model.live="repricerConfig.max_profit_margin" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm" placeholder="Ex: 40%">
                        <p class="text-[10px] text-slate-500">Preço teto de catálogo.</p>
                    </div>
                </div>

                <!-- Filtros de Competição -->
                <div class="space-y-3">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300 block italic">Filtros de Competição</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-800 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors">
                            <input type="checkbox" wire:model.live="repricerConfig.filter_full_only" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-sm font-medium text-slate-900 dark:text-white">Competir apenas com "Full"</span>
                                <p class="text-[10px] text-slate-500">Ignora vendedores que não usam fulfillment.</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-800 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors">
                            <input type="checkbox" wire:model.live="repricerConfig.filter_premium_only" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-sm font-medium text-slate-900 dark:text-white">Competir apenas com "Premium"</span>
                                <p class="text-[10px] text-slate-500">Ignora vendedores em anúncios Clássicos.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 bg-slate-50 dark:bg-dark-950 border-t border-slate-200 dark:border-dark-800 flex items-center justify-between">
                <button wire:click="$set('showRepricerModal', false)" class="px-6 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                    Cancelar
                </button>
                <button wire:click="saveRepricerConfig" class="px-8 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/20 transition-all">
                    Salvar Configurações
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
    <!-- Modal JSON -->
    @if($showJsonModal && $jsonData)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" wire:click="$set('showJsonModal', false)"></div>
        <div class="relative bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-slate-200 dark:border-dark-800 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">JSON do Anúncio</h3>
                    <p class="text-sm text-slate-500">{{ $jsonAdTitulo }}</p>
                </div>
                <button wire:click="$set('showJsonModal', false)" class="p-2 hover:bg-slate-100 dark:hover:bg-dark-800 rounded-lg">
                    <i class="fas fa-times text-slate-500"></i>
                </button>
            </div>
            <div class="flex-1 overflow-auto p-6">
                <pre class="text-xs bg-slate-50 dark:bg-dark-950 p-4 rounded-xl overflow-auto">{{ json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            <div class="p-6 border-t border-slate-200 dark:border-dark-800">
                <button wire:click="$set('showJsonModal', false)" class="w-full px-4 py-2 bg-slate-100 dark:bg-dark-800 text-slate-700 dark:text-slate-300 rounded-xl font-medium">
                    Fechar
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
