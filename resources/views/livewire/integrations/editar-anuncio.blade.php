<div>
    @if($showModal && $anuncio)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="fechar"></div>
        
        <div class="relative bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[95vh] flex flex-col">
            <!-- Header -->
            <div class="p-6 border-b border-slate-200 dark:border-dark-800 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center overflow-hidden
                        {{ $anuncio->marketplace === 'mercadolivre' ? 'bg-white' : '' }}
                        {{ $anuncio->marketplace === 'amazon' ? 'bg-orange-50' : '' }}
                        {{ $anuncio->marketplace === 'bling' ? 'bg-blue-50' : '' }}
                        {{ $anuncio->marketplace === 'shopee' ? 'bg-red-50' : '' }}
                        {{ $anuncio->marketplace === 'magalu' ? 'bg-green-50' : '' }}
                    ">
                        @if($anuncio->marketplace === 'mercadolivre')
                            <img src="/images/marketplaces/mercado-livre.svg" alt="ML" class="w-8 h-auto">
                        @elseif($anuncio->marketplace === 'amazon')
                            <i class="fab fa-amazon text-orange-500 text-xl"></i>
                        @elseif($anuncio->marketplace === 'bling')
                            <img src="/images/marketplaces/bling.svg" alt="Bling" class="w-8 h-auto">
                        @elseif($anuncio->marketplace === 'shopee')
                            <img src="/images/marketplaces/shopee.svg" alt="Shopee" class="w-8 h-auto">
                        @elseif($anuncio->marketplace === 'magazine-luiza')
                            <img src="/images/marketplaces/magazine-luiza.svg" alt="Magazine Luiza" class="w-8 h-auto">
                        @else
                            <i class="fas fa-store text-slate-500 text-xl"></i>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Editar Anúncio</h3>
                        <p class="text-sm text-slate-500">{{ $anuncio->external_id }}</p>
                    </div>
                </div>
                <button wire:click="fechar" class="p-2 hover:bg-slate-100 dark:hover:bg-dark-800 rounded-lg transition-colors">
                    <i class="fas fa-times text-slate-500"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- Título -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Título</label>
                    <input wire:model="titulo" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500" placeholder="Título do anúncio">
                </div>

                <!-- Descrição -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Descrição 
                        <span class="text-xs text-slate-400 ml-2">(Aceita HTML)</span>
                    </label>
                    <textarea wire:model="descricao" rows="8" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 font-mono" placeholder="Descrição do produto..."></textarea>
                    <div class="mt-2 flex gap-2">
                        <button type="button" class="text-xs px-2 py-1 bg-slate-100 dark:bg-dark-800 rounded hover:bg-slate-200" onclick="inserirTag('<b>', '</b>')"><b>Negrito</b></button>
                        <button type="button" class="text-xs px-2 py-1 bg-slate-100 dark:bg-dark-800 rounded hover:bg-slate-200"><i	Itálico	></i></button>
                        <button type="button" class="text-xs px-2 py-1 bg-slate-100 dark:bg-dark-800 rounded hover:bg-slate-200"><br>Linha</button>
                        <button type="button" class="text-xs px-2 py-1 bg-slate-100 dark:bg-dark-800 rounded hover:bg-slate-200"><img></button>
                    </div>
                </div>

                <!-- Preço e Estoque -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Preço (R$)</label>
                        <input wire:model="preco" type="number" step="0.01" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500" placeholder="0,00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Estoque</label>
                        <input wire:model="estoque" type="number" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">SKU</label>
                        <input wire:model="sku" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500" placeholder="SKU">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Custo (R$)</label>
                        <input wire:model="preco_custo" type="number" step="0.01" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500" placeholder="0,00">
                    </div>
                </div>

                <!-- Status e Mercado Pago -->
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="status_ativo" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-yellow-500 focus:ring-yellow-500">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Anúncio ativo</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="aceita_mercadopago" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-blue-500 focus:ring-blue-500">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Aceita Mercado Pago</span>
                    </label>
                </div>

                <!-- Imagens Atuais -->
                @if(count($imagens) > 0)
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Imagens Atuais</label>
                    <div class="grid grid-cols-4 md:grid-cols-6 gap-2">
                        @foreach($imagens as $img)
                        <div class="relative aspect-square rounded-lg overflow-hidden border border-slate-200 dark:border-dark-700">
                            <img src="{{ $img['url'] }}" alt="" class="w-full h-full object-cover">
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Variações -->
                @if(count($variacoes) > 0)
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Variações</label>
                    <div class="space-y-3">
                        @foreach($variacoes as $index => $var)
                        <div class="bg-slate-50 dark:bg-dark-800 rounded-xl p-4 border border-slate-200 dark:border-dark-700">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    @if(!empty($var['atributos']))
                                        @foreach($var['atributos'] as $attr)
                                            {{ $attr['name'] ?? '' }}: {{ $attr['value_name'] ?? '' }}
                                            @if(!$loop->last) / @endif
                                        @endforeach
                                    @else
                                        Variação {{ $index + 1 }}
                                    @endif
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">SKU</label>
                                    <input wire:model="variacoes.{{ $index }}.sku" type="text" class="w-full bg-white dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-lg px-3 py-1.5 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">Preço</label>
                                    <input wire:model="variacoes.{{ $index }}.preco" type="number" step="0.01" class="w-full bg-white dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-lg px-3 py-1.5 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">Estoque</label>
                                    <input wire:model="variacoes.{{ $index }}.estoque" type="number" class="w-full bg-white dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-lg px-3 py-1.5 text-sm">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Feedback -->
                @if($feedback)
                <div class="p-4 rounded-xl {{ str_contains($feedback, 'Erro') ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-emerald-50 text-emerald-600 border border-emerald-200' }}">
                    <i class="fas {{ str_contains($feedback, 'Erro') ? 'fa-exclamation-circle' : 'fa-check-circle' }} mr-2"></i>
                    {{ $feedback }}
                </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-slate-200 dark:border-dark-800 flex items-center justify-between flex-shrink-0">
                <a href="{{ $anuncio->json_data['permalink'] ?? $anuncio->url_anuncio ?? '#' }}" target="_blank" class="text-sm text-blue-500 hover:text-blue-600">
                    <i class="fas fa-external-link-alt mr-1"></i> Ver no Marketplace
                </a>
                <div class="flex gap-3">
                    <button wire:click="fechar" class="px-6 py-2.5 bg-slate-100 dark:bg-dark-800 text-slate-700 dark:text-slate-300 rounded-xl font-medium hover:bg-slate-200 dark:hover:bg-dark-700 transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="salvar" wire:disabled="saving" class="px-6 py-2.5 bg-yellow-500 text-white rounded-xl font-medium hover:bg-yellow-600 transition-colors disabled:opacity-50">
                        @if($saving)
                            <i class="fas fa-spinner fa-spin mr-2"></i> Salvando...
                        @else
                            <i class="fas fa-save mr-2"></i> Salvar
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
