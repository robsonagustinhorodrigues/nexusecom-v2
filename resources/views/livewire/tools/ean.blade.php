<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg shadow-cyan-500/20">
                    <i class="fas fa-barcode text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Gerador EAN</h2>
                    <p class="text-sm text-slate-500">Gere códigos de barras com dígito verificador</p>
                </div>
            </div>
            <a href="{{ route('tools.index') }}" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-dark-800 text-slate-500 hover:bg-cyan-500 hover:text-white transition-all text-sm font-semibold">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Form -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
            <form wire:submit.prevent="generate" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Tipo de Código</label>
                    <select wire:model="type" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-cyan-500">
                        <option value="EAN13">EAN-13 (Brasil)</option>
                        <option value="EAN8">EAN-8</option>
                        <option value="UPC">UPC (USA)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Número Base</label>
                    <input type="text" wire:model="base_number" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-cyan-500" placeholder="123456789012" required>
                    <p class="text-xs text-slate-500 mt-1">Apenas números. O dígito verificador será calculado automaticamente.</p>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-white font-semibold transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-barcode"></i> Gerar Código
                </button>
            </form>

            @if($generated_code)
            <div class="mt-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold text-emerald-400">Código Gerado</span>
                    @if($is_valid)
                    <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-1 rounded-full">Válido ✓</span>
                    @endif
                </div>
                <div class="text-2xl font-bold text-white font-mono tracking-widest text-center py-3 bg-slate-900/50 rounded-lg">
                    {{ $generated_code }}
                </div>
                <div class="mt-2 text-xs text-slate-400 text-center">
                    Tipo: {{ $type }}
                </div>
            </div>
            @endif
        </div>

        <!-- Info -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Informações</h3>
            
            <div class="space-y-3">
                <div class="p-3 bg-slate-50 dark:bg-dark-950 rounded-xl">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">EAN-13</span>
                        <span class="text-xs text-slate-400">13 dígitos</span>
                    </div>
                    <p class="text-xs text-slate-500">Código usado no Brasil para produtos comerciais.</p>
                </div>

                <div class="p-3 bg-slate-50 dark:bg-dark-950 rounded-xl">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">EAN-8</span>
                        <span class="text-xs text-slate-400">8 dígitos</span>
                    </div>
                    <p class="text-xs text-slate-500">Para produtos pequenos com espaço limitado.</p>
                </div>

                <div class="p-3 bg-slate-50 dark:bg-dark-950 rounded-xl">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">UPC</span>
                        <span class="text-xs text-slate-400">12 dígitos</span>
                    </div>
                    <p class="text-xs text-slate-500">Universal Product Code - usado nos EUA.</p>
                </div>
            </div>
        </div>
    </div>
</div>
