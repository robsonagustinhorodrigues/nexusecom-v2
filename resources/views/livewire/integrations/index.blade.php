<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <i class="fas fa-plug text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Central de Integrações</h2>
                    <p class="text-sm text-slate-500">Conecte suas lojas e sincronize vendas</p>
                </div>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-xl flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span class="font-medium">{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 px-4 py-3 rounded-xl flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    @if($testResult)
        <div class="{{ $testResult['success'] ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-rose-500/10 border-rose-500/20 text-rose-400' }} border px-4 py-3 rounded-xl flex items-center gap-2">
            <i class="fas {{ $testResult['success'] ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
            <span class="font-medium">{{ $testResult['message'] }}</span>
            <button wire:click="$set('testResult', null)" class="ml-auto text-xs hover:opacity-70">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- CARD MERCADO LIVRE -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-2xl shadow-sm hover:border-yellow-500/50 transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-yellow-500/10 flex items-center justify-center text-yellow-500 border border-yellow-500/20">
                    <i class="fab fa-shopping-bag text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Mercado Livre</h3>
                    <span class="text-xs text-slate-500">Marketplace</span>
                </div>
            </div>

            <p class="text-sm text-slate-500 mb-4">
                Sincronize anúncios, vendas e perguntas.
            </p>

            @php
                $meli = $integracoes->where('marketplace', 'mercadolivre')->first();
            @endphp

            @if($meli)
                <div class="space-y-3">
                    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-2 rounded-lg flex items-center gap-2 text-sm font-medium">
                        <i class="fas fa-check-circle"></i>
                        <span>Conectado</span>
                    </div>
                    
                    <form action="{{ route('meli.update-nome') }}" method="POST" class="space-y-2">
                        @csrf
                        <input 
                            type="text" 
                            name="nome" 
                            value="{{ $meli->nome_conta }}"
                            placeholder="Nome da conexão"
                            class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm focus:border-yellow-500"
                        >
                        <button type="submit" class="w-full py-2 rounded-lg bg-yellow-500/10 text-yellow-400 hover:bg-yellow-500/20 text-sm font-medium text-center transition-all">
                            <i class="fas fa-edit mr-1"></i> Editar Nome
                        </button>
                    </form>
                    
                    <div class="flex gap-2">
                        <button wire:click="testConnection('meli')" wire:loading.attr="disabled" class="flex-1 py-2 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 text-sm font-medium text-center transition-all">
                            <i class="fas fa-plug mr-1"></i> Testar
                        </button>
                    </div>
                    <button wire:click="disconnect({{ $meli->id }})" wire:confirm="Deseja desconectar?" class="w-full py-2 rounded-lg border border-rose-500/20 text-rose-400 hover:bg-rose-500/10 text-sm font-medium transition-all">
                        Desconectar
                    </button>
                </div>
            @else
                <button wire:click="connectMeli" class="w-full py-3 rounded-xl bg-yellow-500 hover:bg-yellow-400 text-white font-semibold transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-plug"></i> Conectar
                </button>
            @endif
        </div>

        <!-- CARD BLING ERP -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-2xl shadow-sm hover:border-green-500/50 transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-green-500/10 flex items-center justify-center text-green-500 border border-green-500/20">
                    <i class="fas fa-warehouse text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Bling ERP</h3>
                    <span class="text-xs text-slate-500">ERP</span>
                </div>
            </div>

            <p class="text-sm text-slate-500 mb-4">
                Produtos, pedidos e notas fiscais.
            </p>

            @php
                $bling = $integracoes->where('marketplace', 'bling')->first();
            @endphp

            @if($bling)
                <div class="space-y-3">
                    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-2 rounded-lg flex items-center gap-2 text-sm font-medium">
                        <i class="fas fa-check-circle"></i>
                        <span>Conectado</span>
                    </div>
                    
                    <form action="{{ route('bling.update-nome') }}" method="POST" class="space-y-2">
                        @csrf
                        <input 
                            type="text" 
                            name="nome" 
                            value="{{ $bling->nome_conta }}"
                            placeholder="Nome da conexão"
                            class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm focus:border-green-500"
                        >
                        <button type="submit" class="w-full py-2 rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 text-sm font-medium text-center transition-all">
                            <i class="fas fa-edit mr-1"></i> Editar Nome
                        </button>
                    </form>
                    
                    <div class="flex gap-2">
                        <button wire:click="testConnection('bling')" wire:loading.attr="disabled" class="flex-1 py-2 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 text-sm font-medium text-center transition-all">
                            <i class="fas fa-plug mr-1"></i> Testar
                        </button>
                        <a href="{{ route('bling.processar-notificacoes') }}" class="flex-1 py-2 rounded-lg bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 text-sm font-medium text-center transition-all flex items-center justify-center gap-1">
                            <i class="fas fa-bell mr-1"></i> Notificações
                        </a>
                    </div>
                    <button wire:click="disconnect({{ $bling->id }})" wire:confirm="Deseja desconectar?" class="w-full py-2 rounded-lg border border-rose-500/20 text-rose-400 hover:bg-rose-500/10 text-sm font-medium transition-all">
                        Desconectar
                    </button>
                </div>
            @else
                <form action="{{ route('bling.connect') }}" method="POST" class="space-y-3">
                    @csrf
                    <button type="submit" class="w-full py-3 rounded-xl bg-green-500 hover:bg-green-400 text-white font-semibold transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-plug"></i> Conectar com OAuth2
                    </button>
                    <p class="text-xs text-slate-500 text-center">Você será redirecionado para o Bling</p>
                </form>
            @endif
        </div>

        <!-- CARD AMAZON -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-2xl shadow-sm hover:border-orange-500/50 transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-orange-500/10 flex items-center justify-center text-orange-500 border border-orange-500/20">
                    <i class="fab fa-amazon text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Amazon</h3>
                    <span class="text-xs text-slate-500">Marketplace Global</span>
                </div>
            </div>

            <p class="text-sm text-slate-500 mb-4">
                Pedidos, inventário e preços.
            </p>

            @php
                $amazon = $integracoes->where('marketplace', 'amazon')->first();
            @endphp

            @if($amazon)
                <div class="space-y-3">
                    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-2 rounded-lg flex items-center gap-2 text-sm font-medium">
                        <i class="fas fa-check-circle"></i>
                        <span>Conectado</span>
                    </div>
                    
                    <!-- Credenciais Mascaras -->
                    <div class="space-y-1 text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-dark-950 p-3 rounded-lg">
                        <div class="flex justify-between">
                            <span>Client ID:</span>
                            <span class="font-mono text-slate-600 dark:text-slate-300">{{ substr($amazon->configuracoes['client_id'] ?? '', 0, 8) }}...</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Client Secret:</span>
                            <span class="font-mono text-slate-600 dark:text-slate-300">****</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Refresh Token:</span>
                            <span class="font-mono text-slate-600 dark:text-slate-300">****</span>
                        </div>
                    </div>
                    
                    <form action="{{ route('amazon.connect') }}" method="POST" class="space-y-2">
                        @csrf
                        <input type="hidden" name="edit_mode" value="1">
                        <input type="hidden" name="client_id" value="{{ $amazon->configuracoes['client_id'] ?? '' }}">
                        <input type="hidden" name="client_secret" value="{{ $amazon->configuracoes['client_secret'] ?? '' }}">
                        <input type="hidden" name="seller_id" value="{{ $amazon->configuracoes['seller_id'] ?? '' }}">
                        <input type="hidden" name="marketplace_id" value="A2Q3Y263D00KWC">
                        
                        <input 
                            type="text" 
                            name="nome_conta" 
                            value="{{ $amazon->nome_conta }}"
                            placeholder="Nome da conexão"
                            class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm focus:border-orange-500"
                        >
                        <input 
                            type="password" 
                            name="refresh_token" 
                            value=""
                            placeholder="******** (alterar se necessário)"
                            class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm focus:border-orange-500"
                        >
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 py-2 rounded-lg bg-orange-500/10 text-orange-400 hover:bg-orange-500/20 text-sm font-medium text-center transition-all">
                                <i class="fas fa-save mr-1"></i> Salvar
                            </button>
                            <button wire:click="testConnection('amazon')" wire:loading.attr="disabled" class="flex-1 py-2 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 text-sm font-medium text-center transition-all">
                                <i class="fas fa-plug mr-1"></i> Testar
                            </button>
                        </div>
                    </form>
                    <button wire:click="disconnect({{ $amazon->id }})" wire:confirm="Deseja desconectar?" class="w-full py-2 rounded-lg border border-rose-500/20 text-rose-400 hover:bg-rose-500/10 text-sm font-medium transition-all">
                        Desconectar
                    </button>
                </div>
            @else
                <form action="{{ route('amazon.connect') }}" method="POST" class="space-y-3">
                    @csrf
                    <input 
                        type="text" 
                        name="nome_conta" 
                        placeholder="Nome da conexão (opcional)"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-orange-500"
                    >
                    <input 
                        type="text" 
                        name="client_id" 
                        placeholder="Client ID (LWA)"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-orange-500"
                        required
                    >
                    <input 
                        type="text" 
                        name="client_secret" 
                        placeholder="Client Secret (LWA)"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-orange-500"
                        required
                    >
                    <input 
                        type="text" 
                        name="refresh_token" 
                        placeholder="Refresh Token"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-orange-500"
                        required
                    >
                    <input type="hidden" name="marketplace_id" value="A2Q3Y263D00KWC">
                    <button type="submit" class="w-full py-3 rounded-xl bg-orange-500 hover:bg-orange-400 text-white font-semibold transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-plug"></i> Conectar
                    </button>
                </form>
            @endif
        </div>

        <!-- CARD SHOPEE (Placeholder) -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-2xl shadow-sm opacity-60">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-pink-500/10 flex items-center justify-center text-pink-500 border border-pink-500/20">
                    <i class="fas fa-shopping-cart text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Shopee</h3>
                    <span class="text-xs text-slate-500">Em breve</span>
                </div>
            </div>
            <p class="text-sm text-slate-500">Sincronização em desenvolvimento.</p>
        </div>

    </div>
</div>
