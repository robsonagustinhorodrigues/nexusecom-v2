<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/20">
                <i class="fas fa-tools text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Ferramentas</h2>
                <p class="text-sm text-slate-500">Utilitários para gestão de etiquetas e códigos</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- ZPL -->
        <a href="{{ route('tools.zpl') }}" class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-2xl shadow-sm hover:border-violet-500/50 transition-all group">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-violet-500/10 flex items-center justify-center text-violet-500 border border-violet-500/20 group-hover:scale-110 transition-transform">
                    <i class="fas fa-print text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Impressora ZPL</h3>
                    <span class="text-xs text-slate-500">Conversor de etiquetas</span>
                </div>
            </div>
            <p class="text-sm text-slate-500">
                Converta código ZPL para PDF e imprima etiquetas para impressoras térmicas.
            </p>
        </a>

        <!-- EAN -->
        <a href="{{ route('tools.ean') }}" class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-2xl shadow-sm hover:border-cyan-500/50 transition-all group">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-cyan-500/10 flex items-center justify-center text-cyan-500 border border-cyan-500/20 group-hover:scale-110 transition-transform">
                    <i class="fas fa-barcode text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Gerador EAN</h3>
                    <span class="text-xs text-slate-500">Códigos de barras</span>
                </div>
            </div>
            <p class="text-sm text-slate-500">
                Gere códigos de barras EAN-13, EAN-8 e UPC com dígito verificador.
            </p>
        </a>

    </div>
</div>
