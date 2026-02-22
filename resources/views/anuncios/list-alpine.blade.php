@extends('layouts.alpine')

@section('title', 'Anúncios - NexusEcom')
@section('header_title', 'Anúncios')

@section('content')
<div x-data="anunciosPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Meus Anúncios</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Gerencie anúncios dos marketplaces</p>
        </div>
        
        <div class="flex items-center gap-3">
            <!-- View Mode -->
            <div class="flex bg-slate-800 rounded-xl p-1">
                <button @click="viewMode = 'cards'" :class="viewMode === 'cards' ? 'bg-slate-700 text-white' : 'text-slate-400'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all">
                    <i class="fas fa-th-large mr-1"></i> Cards
                </button>
                <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-slate-700 text-white' : 'text-slate-400'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all">
                    <i class="fas fa-list mr-1"></i> Tabela
                </button>
            </div>
            
            <button @click="syncAnuncios()" :disabled="syncing" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-400 text-slate-900 rounded-xl font-bold text-sm flex items-center gap-2">
                <i class="fas fa-sync" :class="syncing ? 'fa-spin' : ''"></i>
                <span x-text="syncing ? 'Sincronizando...' : 'Sincronizar'"></span>
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-4 mb-6">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                    <input type="text" x-model="search" @input.debounce.300ms="loadAnuncios()" placeholder="Buscar por título, ID ou SKU..." 
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-yellow-500 outline-none">
                </div>
            </div>
            <select x-model="marketplace" @change="loadAnuncios()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos marketplaces</option>
                <option value="mercadolivre">Mercado Livre</option>
                <option value="amazon">Amazon</option>
            </select>
            <select x-model="statusFilter" @change="loadAnuncios()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Status: Todos</option>
                <option value="active">Ativos</option>
                <option value="inactive">Inativos</option>
            </select>
            <select x-model="tipoFilter" @change="loadAnuncios()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Tipo: Todos</option>
                <option value="catalogo">Catálogo</option>
                <option value="normal">Normal</option>
            </select>
            <select x-model="vinculoFilter" @change="loadAnuncios()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Vínculo: Todos</option>
                <option value="vinculado">Vinculados</option>
                <option value="nao_vinculado">Não Vinculados</option>
            </select>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Total Anúncios</p>
            <p class="text-2xl font-bold" x-text="anuncios.length"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Ativos</p>
            <p class="text-2xl font-bold text-green-400" x-text="activeCount"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Inativos</p>
            <p class="text-2xl font-bold text-red-400" x-text="inactiveCount"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Sem Estoque</p>
            <p class="text-2xl font-bold text-amber-400" x-text="noStockCount"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Lucro Médio</p>
            <p class="text-2xl font-bold" :class="lucroMedio >= 0 ? 'text-green-400' : 'text-red-400'" x-text="formatMoney(lucroMedio)"></p>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-center py-12">
        <i class="fas fa-spinner fa-spin text-2xl text-yellow-500"></i>
        <p class="text-slate-400 mt-2">Carregando anúncios...</p>
    </div>

    <!-- Cards View -->
    <div x-show="!loading && viewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template x-for="anuncio in anuncios" :key="anuncio.id">
            <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden hover:border-yellow-500/50 transition-all">
                <div class="aspect-square bg-slate-700 relative">
                    <img x-show="anuncio.thumbnail" :src="anuncio.thumbnail" class="w-full h-full object-cover">
                    <div x-show="!anuncio.thumbnail" class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-image text-3xl text-slate-600"></i>
                    </div>
                    <div class="absolute top-2 left-2">
                        <span class="text-xs px-2 py-1 rounded-full font-medium"
                            :class="anuncio.status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'"
                            x-text="anuncio.status === 'active' ? 'Ativo' : 'Inativo'"
                        ></span>
                    </div>
                    <div class="absolute top-2 right-2">
                        <span class="text-xs px-2 py-1 rounded-full bg-slate-800 text-slate-300" x-text="anuncio.marketplace"></span>
                    </div>
                    <div x-show="anuncio.is_catalog" class="absolute bottom-2 left-2">
                        <span class="text-[10px] px-2 py-1 rounded-full bg-purple-500/20 text-purple-400">Catálogo</span>
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-sm line-clamp-2 mb-2 text-white" x-text="anuncio.titulo"></h3>
                    
                    <div class="flex items-center justify-between mb-2 text-xs">
                        <span class="text-slate-400" x-text="'SKU: ' + (anuncio.sku || '-')"></span>
                        <span :class="(anuncio.estoque || 0) > 0 ? 'text-green-400' : 'text-red-400'" x-text="'Estoque: ' + (anuncio.estoque || 0)"></span>
                    </div>
                    
                    <!-- Lucratividade -->
                    <div class="bg-slate-700/50 rounded-lg p-2 mb-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-slate-400">Preço</span>
                            <span class="text-white font-bold" x-text="formatMoney(anuncio.preco)"></span>
                        </div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-slate-400">Custo</span>
                            <span class="text-red-400" x-text="formatMoney(anuncio.custo || 0)"></span>
                        </div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-slate-400">Taxas</span>
                            <span class="text-amber-400" x-text="formatMoney(anuncio.taxas || 0)"></span>
                        </div>
                        <div class="border-t border-slate-600 mt-1 pt-1 flex justify-between text-xs">
                            <span class="text-slate-400">Lucro</span>
                            <span class="font-bold" :class="(anuncio.lucro || 0) >= 0 ? 'text-green-400' : 'text-red-400'" x-text="formatMoney(anuncio.lucro || 0)"></span>
                        </div>
                        <div class="flex justify-between text-xs mt-1">
                            <span class="text-slate-400">Margem</span>
                            <span class="font-bold" :class="(anuncio.margem || 0) >= 0 ? 'text-green-400' : 'text-red-400'" x-text="(anuncio.margem || 0).toFixed(1) + '%'"></span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex gap-1">
                            <template x-if="anuncio.product_linked">
                                <span class="p-2 bg-indigo-500/20 text-indigo-400 rounded-lg text-xs" title="Vinculado">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            </template>
                            <template x-if="!anuncio.product_linked && !anuncio.produto_id">
                                <div class="flex gap-1">
                                    <button @click="importAsProduct(anuncio)" class="p-2 bg-emerald-500/20 text-emerald-400 rounded-lg hover:bg-emerald-500/30 text-xs" title="Importar como Produto">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button @click="openVincular(anuncio)" class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500/30 text-xs" title="Vincular a Produto">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>
                            </template>
                            <template x-if="!anuncio.product_linked && announcement.produto_id">
                                <button @click="openVincular(anuncio)" class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500/30 text-xs" title="Vincular a Produto">
                                    <i class="fas fa-link"></i>
                                </button>
                            </template>
                            <template x-if="!anuncio.product_linked && anuncio.produto_id">
                                <button @click="openVincular(anuncio)" class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500/30" title="Vincular a Produto">
                                    <i class="fas fa-link"></i>
                                </button>
                            </template>
                        </div>
                        <div class="flex gap-1">
                            <template x-if="anuncio.is_catalog && announcement.marketplace === 'mercadolivre'">
                                <a :href="getUrlConcorrentes(anuncio)" target="_blank" class="p-2 bg-slate-700 hover:bg-amber-500 hover:text-slate-900 rounded-lg text-xs" title="Ver Concorrentes">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                            </template>
                            <template x-if="anuncio.marketplace === 'mercadolivre' && anuncio.is_catalog">
                                <button @click="openRepricer(anuncio)" class="p-2 rounded-lg text-xs" :class="anuncio.repricer_active ? 'bg-indigo-500/20 text-indigo-400' : 'bg-slate-700 hover:bg-indigo-500 hover:text-white'" title="Re-preço">
                                    <i class="fas fa-robot"></i>
                                </button>
                            </template>
                            <button @click="editAnuncio(anuncio)" class="p-2 bg-slate-700 hover:bg-indigo-600 rounded-lg text-xs text-white" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a :href="anuncio.url" target="_blank" class="p-2 bg-slate-700 hover:bg-yellow-500 hover:text-slate-900 rounded-lg text-xs" title="Ver no Marketplace">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Table View -->
    <div x-show="!loading && viewMode === 'table'" class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-slate-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-400">Anúncio</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-400">SKU</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Preço</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Custo</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Taxas</th>
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
                                <div>
                                    <p class="text-sm font-medium text-white line-clamp-1" x-text="anuncio.titulo"></p>
                                    <p class="text-xs text-slate-500" x-text="anuncio.marketplace"></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-400" x-text="anuncio.sku || '-'"></td>
                        <td class="px-4 py-3 text-right font-bold text-white" x-text="formatMoney(anuncio.preco)"></td>
                        <td class="px-4 py-3 text-right text-red-400" x-text="formatMoney(anuncio.custo || 0)"></td>
                        <td class="px-4 py-3 text-right text-amber-400" x-text="formatMoney(anuncio.taxas || 0)"></td>
                        <td class="px-4 py-3 text-right font-bold" :class="(anuncio.lucro || 0) >= 0 ? 'text-green-400' : 'text-red-400'" x-text="formatMoney(anuncio.lucro || 0)"></td>
                        <td class="px-4 py-3 text-right font-bold" :class="(anuncio.margem || 0) >= 0 ? 'text-green-400' : 'text-red-400'" x-text="(anuncio.margem || 0).toFixed(1) + '%'"></td>
                        <td class="px-4 py-3 text-center" :class="(anuncio.estoque || 0) > 0 ? 'text-green-400' : 'text-red-400'" x-text="anuncio.estoque || 0"></td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <template x-if="anuncio.product_linked">
                                    <span class="text-xs bg-indigo-500/20 text-indigo-400 px-2 py-1 rounded">
                                        <i class="fas fa-check-circle text-[10px]"></i>
                                    </span>
                                </template>
                                <template x-if="!anuncio.product_linked && !anuncio.produto_id">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="importAsProduct(anuncio)" class="p-1.5 rounded text-emerald-400 hover:bg-emerald-500/10" title="Importar como Produto">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                        <button @click="openVincular(anuncio)" class="p-1.5 rounded text-amber-400 hover:bg-amber-500/10" title="Vincular a Produto">
                                            <i class="fas fa-link text-xs"></i>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="!anuncio.product_linked && anuncio.produto_id">
                                    <button @click="openVincular(anuncio)" class="p-1.5 rounded text-amber-400 hover:bg-amber-500/10" title="Vincular a Produto">
                                        <i class="fas fa-link text-xs"></i>
                                    </button>
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
    <div x-show="!loading && anuncios.length === 0" class="text-center py-12">
        <i class="fas fa-store text-4xl text-slate-600 mb-4"></i>
        <p class="text-slate-400">Nenhum anúncio encontrado</p>
    </div>

    <!-- Modal Vincular -->
    <div x-show="showVincularModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showVincularModal = false"></div>
        <div class="relative bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] flex flex-col">
            <div class="p-5 border-b border-slate-700 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-white">Vincular Produto</h3>
                    <p class="text-sm text-slate-400" x-text="anuncioSelecionado?.titulo"></p>
                </div>
                <button @click="showVincularModal = false" class="p-2 hover:bg-slate-700 rounded-lg text-slate-400">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4 border-b border-slate-700">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                    <input type="text" x-model="searchProduto" @input.debounce.300ms="searchProdutos()" placeholder="Buscar por nome, SKU..." 
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-yellow-500 outline-none">
                </div>
            </div>
            <div class="flex-1 overflow-auto p-4">
                <div class="space-y-2">
                    <template x-for="produto in produtos" :key="produto.id">
                        <button @click="vincularProduto(produto.id)" class="w-full p-3 bg-slate-700/50 hover:bg-slate-700 rounded-xl transition-colors text-left">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-white" x-text="produto.nome"></p>
                                    <p class="text-sm text-slate-400">SKU: <span x-text="produto.sku || 'N/A'"></span></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-yellow-400" x-text="formatMoney(produto.preco_venda)"></p>
                                    <p class="text-xs text-slate-400">Estoque: <span x-text="produto.estoque"></span></p>
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
        viewMode: 'cards',
        marketplace: '',
        statusFilter: '',
        tipoFilter: '',
        vinculoFilter: '',
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
            min_profit_type: 'percent'
        },
        savingRepricer: false,
        
        init() {
            this.$watch('empresaId', () => {
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadAnuncios();
            });
            this.loadAnuncios();
        },
        
        async loadAnuncios() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    empresa: this.empresaId,
                    marketplace: this.marketplace,
                    status: this.statusFilter,
                    tipo: this.tipoFilter,
                    vinculo: this.vinculoFilter,
                    search: this.search
                });
                
                const response = await fetch(`/api/anuncios?${params}`);
                const data = await response.json();
                this.anuncios = data.data || data;
            } catch (e) {
                console.error('Error:', e);
            }
            this.loading = false;
        },
        
        async syncAnuncios() {
            this.syncing = true;
            try {
                await fetch(`/api/anuncios/sync?empresa=${this.empresaId}`, { method: 'POST' });
                await this.loadAnuncios();
            } catch (e) {
                console.error('Sync error:', e);
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
                    headers: { 'Content-Type': 'application/json' },
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
        
        // Importar como produto
        async importAsProduct(anuncio) {
            if (!confirm('Criar produto a partir deste anúncio?')) return;
            
            try {
                const response = await fetch(`/api/anuncios/${anuncio.id}/importar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
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
        
        // Repricer methods
        async openRepricer(anuncio) {
            this.repricerAnuncioId = anuncio.id;
            this.repricerConfig = {
                is_active: false,
                strategy: 'igualar_menor',
                offset_value: 0,
                min_profit_margin: null,
                min_profit_type: 'percent'
            };
            try {
                const response = await fetch(`/api/anuncios/${anuncio.id}/repricer`);
                const data = await response.json();
                if (data) {
                    this.repricerConfig = {
                        is_active: data.is_active ?? false,
                        strategy: data.strategy ?? 'igualar_menor',
                        offset_value: data.offset_value ?? 0,
                        min_profit_margin: data.min_profit_margin ?? null,
                        min_profit_type: data.min_profit_type ?? 'percent'
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
                const response = await fetch(`/api/anuncios/${this.repricerAnuncioId}/repricer`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.repricerConfig)
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
