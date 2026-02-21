<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/20">
                    <i class="fas fa-print text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Impressora ZPL</h2>
                    <p class="text-sm text-slate-500">Converta código ZPL para PDF</p>
                </div>
            </div>
            <a href="{{ route('tools.index') }}" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-dark-800 text-slate-500 hover:bg-violet-500 hover:text-white transition-all text-sm font-semibold">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </div>

    @if ($error)
        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 px-4 py-3 rounded-xl flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            {{ $error }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Form -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
            <form wire:submit.prevent="convert" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Código ZPL</label>
                    <textarea wire:model="zpl_data" rows="10" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-mono focus:border-violet-500" placeholder="^XA&#10;^FO50,50^A0N,30,30^FDProduto^FS&#10;^XZ" required></textarea>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Resolução</label>
                        <select wire:model="dpmm" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-violet-500">
                            <option value="8">8 dpmm</option>
                            <option value="12">12 dpmm</option>
                            <option value="24">24 dpmm</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Largura (mm)</label>
                        <input type="number" wire:model="width_mm" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-violet-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Altura (mm)</label>
                        <input type="number" wire:model="height_mm" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm focus:border-violet-500">
                    </div>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-violet-500 hover:bg-violet-400 text-white font-semibold transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-file-pdf"></i> Converter para PDF
                </button>
            </form>
        </div>

        <!-- Help -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Exemplo de ZPL</h3>
            
            <div class="bg-slate-900 rounded-xl p-4 font-mono text-xs text-slate-300 overflow-x-auto">
<pre>^XA
^FO50,50^A0N,30,30^FDNome do Produto^FS
^FO50,90^A0N,25,25^FDSKU: 12345^FS
^FO50,130^BY3
^BCN,80,Y,N,N^FD123456789012^FS
^FO50,230^A0N,40,40^FDR$ 99,90^FS
^XZ</pre>
            </div>

            <div class="mt-4">
                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Comandos básicos</h4>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-slate-50 dark:bg-dark-950 rounded-lg p-2"><code class="text-violet-500">^XA</code> Início</div>
                    <div class="bg-slate-50 dark:bg-dark-950 rounded-lg p-2"><code class="text-violet-500">^XZ</code> Fim</div>
                    <div class="bg-slate-50 dark:bg-dark-950 rounded-lg p-2"><code class="text-violet-500">^FOx,y</code> Posição</div>
                    <div class="bg-slate-50 dark:bg-dark-950 rounded-lg p-2"><code class="text-violet-500">^FD...^FS</code> Dados</div>
                </div>
            </div>
        </div>
    </div>
</div>
