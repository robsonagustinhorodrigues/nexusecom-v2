<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 md:p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 md:w-12 h-10 md:h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20 flex-shrink-0">
                    <i class="fas fa-warehouse text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Depósitos</h2>
                    <p class="text-xs md:text-sm text-slate-500 hidden md:block">Gerencie locais de estoque</p>
                </div>
            </div>
            
            <button wire:click="novoDeposito" class="px-4 py-2 rounded-xl bg-blue-500 hover:bg-blue-400 text-white font-semibold text-sm transition-all flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>Novo Depósito</span>
            </button>
        </div>
    </div>

    <!-- Filtro -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
        <div class="flex gap-4">
            <select wire:model.live="empresa_filtro" class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm">
                <option value="">Todas as empresas</option>
                @foreach($empresas as $empresa)
                    <option value="{{ $empresa->id }}">{{ $empresa->nome }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Lista de Depósitos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($depositos as $deposito)
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white">{{ $deposito->nome }}</h3>
                        <span class="text-xs text-slate-500">
                            {{ $deposito->empresa?->nome ?? 'Compartilhado' }}
                        </span>
                    </div>
                    <span class="px-2 py-1 rounded-lg text-xs font-medium {{ $deposito->ativo ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                        {{ $deposito->ativo ? 'Ativo' : 'Inativo' }}
                    </span>
                </div>
                
                <div class="flex flex-wrap gap-2 mb-3">
                    <span class="px-2 py-1 rounded-lg text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                        {{ ucfirst($deposito->tipo) }}
                    </span>
                    @if($deposito->compartilhado)
                        <span class="px-2 py-1 rounded-lg text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                            <i class="fas fa-share-alt mr-1"></i>Compartilhado
                        </span>
                    @endif
                </div>
                
                @if($deposito->descricao)
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">{{ $deposito->descricao }}</p>
                @endif
                
                <div class="flex gap-2">
                    <button wire:click="editarDeposito({{ $deposito->id }})" class="flex-1 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-300 text-sm font-medium transition-colors">
                        <i class="fas fa-edit mr-1"></i>Editar
                    </button>
                    <button wire:click="deletar({{ $deposito->id }})" onclick="return confirm('Tem certeza?')" class="px-3 py-2 rounded-xl bg-rose-100 hover:bg-rose-200 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 text-rose-700 dark:text-rose-400 text-sm font-medium transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <i class="fas fa-warehouse text-4xl text-slate-300 dark:text-slate-600 mb-4"></i>
                <p class="text-slate-500 dark:text-slate-400">Nenhum depósito encontrado</p>
            </div>
        @endforelse
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" wire:click.self="showModal = false">
            <div class="bg-white dark:bg-dark-900 rounded-2xl p-6 w-full max-w-md shadow-xl">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">
                    {{ $depositoId ? 'Editar' : 'Novo' }} Depósito
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome</label>
                        <input wire:model="nome" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo</label>
                        <select wire:model="tipo" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm">
                            <option value="loja">Loja</option>
                            <option value="armazem">Armazém</option>
                            <option value="full">Full ( Mercado Livre)</option>
                            <option value="virtual">Virtual</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descrição</label>
                        <textarea wire:model="descricao" rows="2" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm"></textarea>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <input wire:model="compartilhado" type="checkbox" id="compartilhado" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <label for="compartilhado" class="text-sm text-slate-700 dark:text-slate-300">Compartilhado entre empresas</label>
                    </div>
                    
                    @if(!$compartilhado)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Empresa Dono</label>
                            <select wire:model="empresa_dona_id" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm">
                                <option value="">Selecione...</option>
                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}">{{ $empresa->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    
                    <div class="flex items-center gap-3">
                        <input wire:model="ativo" type="checkbox" id="ativo" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <label for="ativo" class="text-sm text-slate-700 dark:text-slate-300">Ativo</label>
                    </div>
                </div>
                
                <div class="flex gap-3 mt-6">
                    <button wire:click="showModal = false" class="flex-1 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-300 font-medium transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="salvar" class="flex-1 px-4 py-2 rounded-xl bg-blue-500 hover:bg-blue-400 text-white font-medium transition-colors">
                        Salvar
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Success Message -->
    @if(session('success'))
        <div class="fixed bottom-4 right-4 bg-emerald-500 text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif
</div>
