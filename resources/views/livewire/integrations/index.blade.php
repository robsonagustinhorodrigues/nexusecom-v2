<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Mercado Livre -->
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-yellow-400 rounded-xl flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-slate-900 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white">Mercado Livre</h3>
                    <span class="text-xs text-slate-400">Integração Oficial</span>
                </div>
            </div>
            
            <div class="space-y-4">
                <button wire:click="connectMeli" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition-all">
                    Conectar Conta
                </button>
            </div>
        </div>

        <!-- Bling -->
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-emerald-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-file-invoice text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white">Bling ERP</h3>
                    <span class="text-xs text-slate-400">Sincronização de Pedidos</span>
                </div>
            </div>
            
            <div class="space-y-4">
                <button class="w-full py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold transition-all">
                    Configurar API
                </button>
            </div>
        </div>

        <!-- Amazon -->
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center">
                    <i class="fab fa-amazon text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white">Amazon SP-API</h3>
                    <span class="text-xs text-slate-400">Vendas Internacionais</span>
                </div>
            </div>
            
            <div class="space-y-4">
                <button class="w-full py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold transition-all">
                    Autorizar App
                </button>
            </div>
        </div>
    </div>
</div>
