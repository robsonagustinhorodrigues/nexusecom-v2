<?php

use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use App\Services\FiscalService;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
use function Livewire\Volt\uses;

uses([WithFileUploads::class, \Livewire\WithPagination::class]);

state([
    'view' => 'recebidas',
    'filtroValorMin' => '0',
    'filtroValorMax' => '9999999999.99',
    'filtroCnpj' => '',
    'filtroDataDe' => '',
    'filtroDataAte' => '',
    'filtroStatus' => '',
    'search' => '',
    'filtroNome' => '',
    'filtroDevolvida' => '',
    'filtroTipoPessoa' => '',
    'tempFiles' => [],
    'selectedIds' => [],
    'isExporting' => false,
    'showRetroativa' => false,
    'retroativaDataDe' => '',
    'retroativaDataAte' => '',
    'isRetroativaRunning' => false,
    'showImportModal' => false,
    'importProgress' => 0,
    'importTotal' => 0,
    'importStatus' => '',
    'importResults' => [],
    'importIntegration' => '',
    'importDataDe' => '',
    'importDataAte' => '',
    'isImportRunning' => false,
]);

mount(function () {
    $this->filtroDataDe = \Carbon\Carbon::now()->subDays(5)->format('Y-m-d');
    $this->filtroDataAte = \Carbon\Carbon::now()->format('Y-m-d');
    $this->importDataDe = \Carbon\Carbon::now()->subDays(30)->format('Y-m-d');
    $this->importDataAte = \Carbon\Carbon::now()->format('Y-m-d');
});

$nfes = computed(function () {
    $empresaId = Auth::user()->current_empresa_id;

    if (! $empresaId) {
        return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
    }

    $query = $this->view === 'recebidas' ? NfeRecebida::tenant($empresaId) : NfeEmitida::tenant($empresaId);

    $dataDe = ! empty($this->filtroDataDe) ? $this->filtroDataDe : \Carbon\Carbon::now()->subDays(5)->format('Y-m-d');
    $dataAte = ! empty($this->filtroDataAte) ? $this->filtroDataAte : \Carbon\Carbon::now()->format('Y-m-d');

    $valorMin = $this->filtroValorMin !== '' ? (float) $this->filtroValorMin : 0;
    $valorMax = $this->filtroValorMax !== '' ? (float) $this->filtroValorMax : 9999999999.99;

    return $query
        ->when($valorMin > 0, fn ($q) => $q->where('valor_total', '>=', $valorMin))
        ->when($valorMax < 9999999999, fn ($q) => $q->where('valor_total', '<=', $valorMax))
        ->when($this->filtroCnpj, fn ($q) => $q->where($this->view === 'recebidas' ? 'emitente_cnpj' : 'cliente_cnpj', 'like', "%{$this->filtroCnpj}%"))
        ->when($this->filtroNome, fn ($q) => $q->where($this->view === 'recebidas' ? 'emitente_nome' : 'cliente_nome', 'like', "%{$this->filtroNome}%"))
        ->whereDate('data_emissao', '>=', $dataDe)
        ->whereDate('data_emissao', '<=', $dataAte)
        ->when($this->filtroStatus, fn ($q) => $q->where('status_nfe', $this->filtroStatus))
        ->when($this->search, fn ($q) => $q->where('chave', 'like', "%{$this->search}%")->orWhere('numero', 'like', "%{$this->search}%"))
        ->when($this->filtroDevolvida !== '', fn ($q) => $q->where($this->view === 'recebidas' ? 'devolucao' : 'devolvida', $this->filtroDevolvida === '1'))
        ->when($this->filtroTipoPessoa !== '', fn ($q) => $q->whereRaw(
            'LENGTH(REPLACE(REPLACE('.($this->view === 'recebidas' ? 'emitente_cnpj' : 'cliente_cnpj').', \'.\', \'\'), \'-\', \'\')) = ?',
            [$this->filtroTipoPessoa === 'PJ' ? 14 : 11]
        ))
        ->orderBy('data_emissao', 'desc')
        ->paginate(15);
});

$totais = computed(function () {
    $empresaId = Auth::user()->current_empresa_id;
    if (! $empresaId) {
        return ['qtd' => 0, 'valor' => 0];
    }

    $query = $this->view === 'recebidas' ? NfeRecebida::tenant($empresaId) : NfeEmitida::tenant($empresaId);

    $dataDe = ! empty($this->filtroDataDe) ? $this->filtroDataDe : \Carbon\Carbon::now()->subDays(5)->format('Y-m-d');
    $dataAte = ! empty($this->filtroDataAte) ? $this->filtroDataAte : \Carbon\Carbon::now()->format('Y-m-d');

    $valorMin = $this->filtroValorMin !== '' ? (float) $this->filtroValorMin : 0;
    $valorMax = $this->filtroValorMax !== '' ? (float) $this->filtroValorMax : 9999999999.99;

    $query = $query
        ->when($valorMin > 0, fn ($q) => $q->where('valor_total', '>=', $valorMin))
        ->when($valorMax < 9999999999, fn ($q) => $q->where('valor_total', '<=', $valorMax))
        ->when($this->filtroCnpj, fn ($q) => $q->where($this->view === 'recebidas' ? 'emitente_cnpj' : 'cliente_cnpj', 'like', "%{$this->filtroCnpj}%"))
        ->when($this->filtroNome, fn ($q) => $q->where($this->view === 'recebidas' ? 'emitente_nome' : 'cliente_nome', 'like', "%{$this->filtroNome}%"))
        ->whereDate('data_emissao', '>=', $dataDe)
        ->whereDate('data_emissao', '<=', $dataAte)
        ->when($this->filtroStatus, fn ($q) => $q->where('status_nfe', $this->filtroStatus))
        ->when($this->search, fn ($q) => $q->where('chave', 'like', "%{$this->search}%")->orWhere('numero', 'like', "%{$this->search}%"))
        ->when($this->filtroDevolvida !== '', fn ($q) => $q->where($this->view === 'recebidas' ? 'devolucao' : 'devolvida', $this->filtroDevolvida === '1'))
        ->when($this->filtroTipoPessoa !== '', fn ($q) => $q->whereRaw(
            'LENGTH(REPLACE(REPLACE('.($this->view === 'recebidas' ? 'emitente_cnpj' : 'cliente_cnpj').', \'.\', \'\'), \'-\', \'\')) = ?',
            [$this->filtroTipoPessoa === 'PJ' ? 14 : 11]
        ));

    return [
        'qtd' => $query->count(),
        'valor' => $query->sum('valor_total'),
    ];
});

$exportXml = function ($id) {
    session()->flash('success', 'Exportação de XML iniciada...');
};

$downloadByPeriod = function (FiscalService $service) {
    if (empty($this->filtroDataDe) && empty($this->filtroDataAte)) {
        session()->flash('error', 'Selecione um período para exportar.');

        return;
    }

    $empresaId = Auth::user()->current_empresa_id;
    if (! $empresaId) {
        return;
    }

    $this->isExporting = true;

    $model = $this->view === 'recebidas' ? NfeRecebida::class : NfeEmitida::class;

    $query = $model::tenant($empresaId)
        ->when($this->filtroDataDe, fn ($q) => $q->whereDate('data_emissao', '>=', $this->filtroDataDe))
        ->when($this->filtroDataAte, fn ($q) => $q->whereDate('data_emissao', '<=', $this->filtroDataAte))
        ->when($this->filtroValorMin, fn ($q) => $q->where('valor_total', '>=', $this->filtroValorMin))
        ->when($this->filtroValorMax, fn ($q) => $q->where('valor_total', '<=', $this->filtroValorMax));

    $xmlPaths = $query->pluck('xml_path')->filter()->toArray();

    if (empty($xmlPaths)) {
        $this->isExporting = false;
        session()->flash('error', 'Nenhum XML encontrado para o período selecionado.');

        return;
    }

    $periodLabel = ($this->filtroDataDe ? \Carbon\Carbon::parse($this->filtroDataDe)->format('d-m') : 'inicio').'_ate_'.($this->filtroDataAte ? \Carbon\Carbon::parse($this->filtroDataAte)->format('d-m-Y') : 'hoje');
    $zipPath = $service->generateZip($xmlPaths, "nfes_{$periodLabel}");

    $this->isExporting = false;

    if ($zipPath) {
        return redirect()->route('nfe.download-zip', ['path' => $zipPath]);
    }

    session()->flash('error', 'Erro ao gerar o arquivo ZIP.');
};

$downloadBatch = function (FiscalService $service) {
    if (empty($this->selectedIds)) {
        session()->flash('error', 'Selecione pelo menos uma nota para exportar.');

        return;
    }

    $model = $this->view === 'recebidas' ? NfeRecebida::class : NfeEmitida::class;
    $xmlPaths = $model::whereIn('id', $this->selectedIds)->pluck('xml_path')->toArray();

    $zipPath = $service->generateZip($xmlPaths, "nfes_{$this->view}");

    if ($zipPath) {
        $this->selectedIds = [];

        return redirect()->route('nfe.download-zip', ['path' => $zipPath]);
    }

    session()->flash('error', 'Erro ao gerar o arquivo ZIP. Verifique se os XMLs existem.');
};

$toggleView = function ($view) {
    $this->view = $view;
    $this->selectedIds = [];
    $this->resetPage();
};

$manifestar = function ($id, $tipo) {
    session()->flash('success', "Manifestação de '{$tipo}' realizada com sucesso!");
};

$salvarPedidoMarketplace = function ($id, $pedido) {
    $model = $this->view === 'recebidas' ? \App\Models\NfeRecebida::class : \App\Models\NfeEmitida::class;
    $nfe = $model::find($id);
    if ($nfe) {
        $nfe->update(['pedido_marketplace' => $pedido]);
        session()->flash('success', 'Pedido marketplace atualizado!');
    }
};

$refreshSefaz = function () {
    try {
        $empresaId = Auth::user()->current_empresa_id;
        if (! $empresaId) {
            session()->flash('error', 'Nenhuma empresa selecionada.');

            return;
        }

        $empresa = \App\Models\Empresa::find($empresaId);

        if ($empresa->last_sefaz_query_at) {
            $cooldownEnd = \Carbon\Carbon::parse($empresa->last_sefaz_query_at)->addHour();
            if (\Carbon\Carbon::now()->lt($cooldownEnd)) {
                session()->flash('error', 'Consulta SEFAZ bloqueada. Aguarde até '.$cooldownEnd->format('H:i').' para a próxima.');

                return;
            }
        }

        \App\Jobs\BuscarNfeJob::dispatch($empresaId, Auth::id());
        session()->flash('success', 'Consulta SEFAZ iniciada em background! Você será notificado quando concluída.');
    } catch (\Exception $e) {
        session()->flash('error', $e->getMessage());
    }
};

$startRetroativa = function () {
    try {
        $empresaId = Auth::user()->current_empresa_id;
        if (! $empresaId) {
            session()->flash('error', 'Nenhuma empresa selecionada.');

            return;
        }

        $empresa = \App\Models\Empresa::find($empresaId);

        if ($empresa->last_sefaz_query_at) {
            $cooldownEnd = \Carbon\Carbon::parse($empresa->last_sefaz_query_at)->addHour();
            if (\Carbon\Carbon::now()->lt($cooldownEnd)) {
                session()->flash('error', 'Consulta SEFAZ bloqueada. Aguarde até '.$cooldownEnd->format('H:i').' para a próxima.');

                return;
            }
        }

        $dataAte = \Carbon\Carbon::now()->format('Y-m-d');
        $dataDe = \Carbon\Carbon::now()->subDays(90)->format('Y-m-d');

        \App\Jobs\BuscarNfeRetroativaJob::dispatch($empresaId, Auth::id(), $dataDe, $dataAte);

        $this->showRetroativa = false;
        session()->flash('success', 'Busca retroativa (últimos 90 dias) iniciada em background! Você será notificado quando concluída.');
    } catch (\Exception $e) {
        session()->flash('error', $e->getMessage());
    }
};

$startImport = function () {
    $empresaId = Auth::user()->current_empresa_id;

    if (! $empresaId) {
        session()->flash('error', 'Nenhuma empresa selecionada.');

        return;
    }

    if (empty($this->importIntegration)) {
        session()->flash('error', 'Selecione uma integração.');

        return;
    }

    if (empty($this->importDataDe) || empty($this->importDataAte)) {
        session()->flash('error', 'Selecione o período de importação.');

        return;
    }

    $tarefa = \App\Models\Tarefa::create([
        'user_id' => Auth::id(),
        'empresa_id' => $empresaId,
        'tipo' => 'import_nfe_'.$this->importIntegration,
        'status' => 'pending',
        'total' => 1000,
        'processado' => 0,
        'sucesso' => 0,
        'falha' => 0,
        'mensagem' => 'Aguardando processamento...',
        'started_at' => now(),
    ]);

    if ($this->importIntegration === 'mercadolivre') {
        \App\Jobs\ImportNfeMeliJob::dispatch($empresaId, Auth::id(), $tarefa->id, $this->importDataDe, $this->importDataAte);
    } elseif ($this->importIntegration === 'bling') {
        \App\Jobs\ImportNfeBlingJob::dispatch($empresaId, Auth::id(), $tarefa->id, $this->importDataDe, $this->importDataAte);
    } elseif ($this->importIntegration === 'shopee') {
        \App\Jobs\ImportNfeShopeeJob::dispatch($empresaId, Auth::id(), $tarefa->id, $this->importDataDe, $this->importDataAte);
    } elseif ($this->importIntegration === 'amazon') {
        \App\Jobs\ImportNfeAmazonJob::dispatch($empresaId, Auth::id(), $tarefa->id, $this->importDataDe, $this->importDataAte);
    } elseif ($this->importIntegration === 'magalu') {
        \App\Jobs\ImportNfeMagaluJob::dispatch($empresaId, Auth::id(), $tarefa->id, $this->importDataDe, $this->importDataAte);
    }

    session()->flash('success', 'Importação iniciada! Acompanhe em: Administração > Tarefas');
};

$getIntegracoes = computed(function () {
    $empresaId = Auth::user()->current_empresa_id;
    if (! $empresaId) {
        return [];
    }

    return \App\Models\Integracao::where('empresa_id', $empresaId)
        ->where('ativo', true)
        ->whereIn('marketplace', ['mercadolivre', 'bling', 'shopee', 'amazon', 'magalu'])
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item->marketplace => $item->nome_conta ?? ucfirst($item->marketplace)];
        })
        ->toArray();
});

$updatedTempFiles = function (FiscalService $service) {
    $empresaId = Auth::user()->current_empresa_id;
    if (! $empresaId || empty($this->tempFiles)) {
        return;
    }

    $this->showImportModal = true;
    $this->importProgress = 0;
    $this->importResults = [];

    $maxSize = 100 * 1024 * 1024; // 100MB
    $allFiles = [];
    $xmlIndex = 0;
    $errors = [];

    foreach ($this->tempFiles as $file) {
        $extension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();

        if ($fileSize > $maxSize) {
            $errors[] = $file->getClientOriginalName().': Arquivo muito grande (máx 100MB)';

            continue;
        }

        if ($extension === 'xml') {
            $allFiles[] = ['type' => 'xml', 'content' => $file->get(), 'name' => $file->getClientOriginalName()];
        } elseif (in_array($extension, ['zip', '7z'])) {
            $this->importStatus = 'Escaneando arquivo ZIP (incluindo subpastas)...';

            $count = $service->extractZipCount($file->getRealPath());

            if ($count === 0) {
                $errors[] = $file->getClientOriginalName().': Nenhum XML encontrado ou arquivo inválido';

                continue;
            }

            $is7z = strtolower($extension) === '7z';

            for ($i = 0; $i < $count; $i++) {
                $xmlIndex++;
                $allFiles[] = [
                    'type' => $is7z ? '7z_content' : 'zip_content',
                    'path' => $file->getRealPath(),
                    'index' => $i,
                    'name' => "XML #{$xmlIndex}",
                ];
            }
        }
    }

    if (! empty($errors)) {
        foreach ($errors as $error) {
            \App\Models\Notificacao::criar(
                'error',
                'Erro na Importação NF-e',
                $error,
                'danger',
                '/fiscal/monitor'
            );
        }
        $this->importStatus = 'Erro(s) encontrado(s). Verifique os avisos.';

        return;
    }

    if (empty($allFiles)) {
        $this->importStatus = 'Nenhum arquivo encontrado.';

        \App\Models\Notificacao::criar(
            'error',
            'Erro na Importação NF-e',
            'Nenhum arquivo XML encontrado nos arquivos enviados.',
            '/fiscal/monitor',
            ['type' => 'import_error'],
            Auth::id()
        );

        return;
    }

    $this->importTotal = count($allFiles);
    $this->importStatus = 'Enviando para processamento em background...';

    \App\Jobs\ImportarNfeZipJob::dispatch($empresaId, Auth::id(), 'importacao_nfe', $allFiles);

    $this->importProgress = 100;
    $this->importStatus = 'Tarefa enviada! Acompanhe em Avisos ou Monitor de Tarefas.';
    $this->importResults = [
        'success' => 0,
        'errors' => [],
        'total' => $this->importTotal,
        'job' => true,
    ];

    $this->tempFiles = [];
};

?>

<div class="space-y-6">
    <!-- Header com Informações Principais -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <i class="fas fa-file-invoice text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Controle de NF-e</h2>
                    <p class="text-sm text-slate-500">Gestão de documentos fiscais eletrônicos</p>
                </div>
            </div>
            
            @if(Auth::user()->current_empresa_id)
                @php
                    $empresa = \App\Models\Empresa::find(Auth::user()->current_empresa_id);
                @endphp
                @if($empresa)
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-500/10 rounded-lg border border-indigo-100 dark:border-indigo-500/20">
                            <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase">NF-e {{ $view === 'recebidas' ? 'Entrada' : 'Saída' }}</span>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-100 dark:bg-dark-800 rounded-lg border border-slate-200 dark:border-dark-700">
                            <i class="fas fa-server text-xs text-slate-400"></i>
                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">NSU: {{ number_format($empresa->last_nsu, 0, ',', '.') }}</span>
                        </div>
                        @if($empresa->last_sefaz_query_at)
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-100 dark:bg-dark-800 rounded-lg border border-slate-200 dark:border-dark-700">
                                <i class="fas fa-clock text-xs text-slate-400"></i>
                                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Última consulta: {{ \Carbon\Carbon::parse($empresa->last_sefaz_query_at)->format('d/m H:i') }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
        </div>

        <!-- Cards de Totais -->
        <div class="flex gap-3">
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl px-5 py-3 shadow-sm">
                <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider mb-1">Total</div>
                <div class="text-xl font-bold text-slate-900 dark:text-white">{{ $this->totais['qtd'] }}</div>
            </div>
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl px-5 py-3 shadow-sm">
                <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider mb-1">Valor</div>
                <div class="text-xl font-bold text-emerald-500">R$ {{ number_format($this->totais['valor'], 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- Barra de Ações -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Toggle Entrada/Saída -->
            <div class="flex items-center gap-2">
                <div class="flex bg-slate-100 dark:bg-dark-800 rounded-xl p-1">
                    <button 
                        wire:click="toggleView('recebidas')" 
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-2 {{ $view === 'recebidas' ? 'bg-white dark:bg-dark-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-white' }}"
                    >
                        <i class="fas fa-sign-in-alt text-xs"></i>
                        Entrada
                    </button>
                    <button 
                        wire:click="toggleView('emitidas')" 
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-2 {{ $view === 'emitidas' ? 'bg-white dark:bg-dark-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-white' }}"
                    >
                        <i class="fas fa-sign-out-alt text-xs"></i>
                        Saída
                    </button>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="flex flex-wrap items-center gap-2">
                <label class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-dark-800 text-slate-600 dark:text-slate-400 font-semibold text-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-dark-700 transition-all flex items-center gap-2">
                    <i class="fas fa-upload text-xs"></i>
                    Importar XML
                    <input type="file" class="hidden" wire:model="tempFiles" multiple>
                </label>

                @if($view === 'recebidas')
                    <button 
                        wire:click="refreshSefaz" 
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2"
                    >
                        <i class="fas fa-sync-alt text-xs"></i>
                        Atualizar SEFAZ
                    </button>
                    <button 
                        wire:click="$set('showRetroativa', true)" 
                        class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-semibold text-sm shadow-lg shadow-amber-600/20 transition-all flex items-center gap-2"
                    >
                        <i class="fas fa-history text-xs"></i>
                        Retroativa
                    </button>
                @endif

                <button 
                    wire:click="downloadByPeriod" 
                    wire:loading.attr="disabled" 
                    class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2 disabled:opacity-50"
                >
                    <i class="fas fa-download text-xs" wire:loading.remove wire:target="downloadByPeriod"></i>
                    <i class="fas fa-spinner fa-spin text-xs" wire:loading wire:target="downloadByPeriod"></i>
                    Exportar
                </button>
            </div>
        </div>
    </div>

    <!-- Alertas -->
    @if (session()->has('success'))
        <div class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl font-semibold text-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 px-4 py-3 rounded-xl font-semibold text-sm flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Filtros -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                <i class="fas fa-filter text-slate-400"></i>
                Filtros
            </h3>
            @if(!empty($selectedIds))
                <button wire:click="downloadBatch" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                    <i class="fas fa-check-square"></i>
                    Exportar {{ count($selectedIds) }} selecionada(s)
                </button>
            @endif
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ $view === 'recebidas' ? '9' : '7' }} gap-3">
            <div>
                <input wire:model.live="filtroCnpj" type="text" placeholder="CNPJ..." class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm font-medium focus:border-indigo-500">
            </div>
            <div>
                <input wire:model.live="filtroNome" type="text" placeholder="Nome..." class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm font-medium focus:border-indigo-500">
            </div>
            <div>
                <input wire:model.live="filtroValorMin" type="number" placeholder="Valor mín..." class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm font-medium focus:border-indigo-500">
            </div>
            <div>
                <input wire:model.live="filtroValorMax" type="number" placeholder="Valor máx..." class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm font-medium focus:border-indigo-500">
            </div>
            <div>
                <input wire:model.live="filtroDataDe" type="date" x-bind:value="$wire.filtroDataDe || '{{ \Carbon\Carbon::now()->subDays(5)->format('Y-m-d') }}'" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm font-medium h-[42px]">
            </div>
            <div>
                <input wire:model.live="filtroDataAte" type="date" x-bind:value="$wire.filtroDataAte || '{{ \Carbon\Carbon::now()->format('Y-m-d') }}'" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm font-medium h-[42px]">
            </div>
            <div>
                <select wire:model.live="filtroDevolvida" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm font-medium h-[42px]">
                    <option value="">{{ $view === 'recebidas' ? 'Devolução' : 'Devolvida' }}</option>
                    <option value="1">Sim</option>
                    <option value="0">Não</option>
                </select>
            </div>
            <div>
                <select wire:model.live="filtroStatus" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm font-medium h-[42px]">
                    <option value="">Status NFe</option>
                    <option value="autorizada">Autorizada</option>
                    <option value="cancelada">Cancelada</option>
                    <option value="denegada">Denegada</option>
                </select>
            </div>
            <div>
                <select wire:model.live="filtroTipoPessoa" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm font-medium h-[42px]">
                    <option value="">Pessoa</option>
                    <option value="PJ">Jurídica</option>
                    <option value="PF">Física</option>
                </select>
            </div>
            <div>
                <input wire:model.live="search" type="text" placeholder="Chave/Número..." class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm font-medium">
            </div>
        </div>
    </div>

    <!-- Tabela de NF-es -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        <table class="w-full text-left">
            <thead class="bg-slate-50 dark:bg-dark-950 border-b border-slate-200 dark:border-dark-800">
                <tr>
                    <th class="px-4 py-3 w-8">
                        <input type="checkbox" wire:model.live="selectedIds" value="all" disabled class="rounded border-slate-300 dark:border-dark-700 bg-white dark:bg-dark-950 text-indigo-600">
                    </th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Número</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $view === 'recebidas' ? 'Emitente' : 'Destinatário' }}</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Valor</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pedido</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Emissão</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-dark-800">
                @forelse($this->nfes as $nfe)
                <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/50 transition-colors group {{ in_array($nfe->id, $selectedIds) ? 'bg-indigo-50 dark:bg-indigo-500/5' : '' }}">
                    <td class="px-4 py-3">
                        <input type="checkbox" wire:model.live="selectedIds" value="{{ $nfe->id }}" class="rounded border-slate-300 dark:border-dark-700 bg-white dark:bg-dark-950 text-indigo-600">
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $view === 'recebidas' ? 'bg-indigo-100 dark:bg-indigo-500/10 text-indigo-600' : 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600' }}">
                                <i class="fas {{ $view === 'recebidas' ? 'fa-sign-in-alt' : 'fa-sign-out-alt' }} text-xs"></i>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $nfe->serie ? $nfe->serie . '-' . ($nfe->numero ?? 'N/D') : ($nfe->numero ?? 'N/D') }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 max-w-[200px] truncate">{{ ($view === 'recebidas' ? $nfe->emitente_nome : $nfe->cliente_nome) ?? '---' }}</span>
                            <span class="text-xs text-slate-400 font-mono">{{ ($view === 'recebidas' ? $nfe->emitente_cnpj : $nfe->cliente_cnpj) }}</span>
                            @if($nfe->chave)
                            <div class="flex items-center gap-1 mt-1">
                                <span class="text-[10px] text-indigo-500 font-mono">{{ $nfe->chave }}</span>
                                <button 
                                    onclick="navigator.clipboard.writeText('{{ $nfe->chave }}')" 
                                    class="text-slate-400 hover:text-indigo-500 transition-colors"
                                    title="Copiar chave"
                                >
                                    <i class="fas fa-copy text-[10px]"></i>
                                </button>
                            </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="text-sm font-bold text-slate-900 dark:text-white">R$ {{ number_format($nfe->valor_total, 2, ',', '.') }}</span>
                    </td>
                    @if($view === 'emitidas')
                    <td class="px-4 py-3">
                        @if($nfe->pedido_marketplace)
                            <span class="text-xs font-mono bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 px-2 py-1 rounded-lg">
                                {{ $nfe->pedido_marketplace }}
                            </span>
                        @else
                            <span class="text-xs text-slate-400">-</span>
                        @endif
                    </td>
                    @endif
                    <td class="px-4 py-3">
                        <span class="text-xs text-slate-500">{{ $nfe->data_emissao ? $nfe->data_emissao->format('d/m/Y') : '---' }}</span>
                        <span class="text-xs text-slate-400 block">{{ $nfe->data_emissao ? $nfe->data_emissao->format('H:i') : '' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($view === 'recebidas')
                            @php
                                $statusConfig = [
                                    'sem_manifesto' => ['bg' => 'bg-slate-100 dark:bg-dark-800', 'text' => 'text-slate-600 dark:text-slate-400', 'label' => 'Pendente'],
                                    'ciencia' => ['bg' => 'bg-blue-100 dark:bg-blue-500/10', 'text' => 'text-blue-600 dark:text-blue-400', 'label' => 'Ciência'],
                                    'confirmada' => ['bg' => 'bg-emerald-100 dark:bg-emerald-500/10', 'text' => 'text-emerald-600 dark:text-emerald-400', 'label' => 'Confirmada'],
                                    'desconhecida' => ['bg' => 'bg-amber-100 dark:bg-amber-500/10', 'text' => 'text-amber-600 dark:text-amber-400', 'label' => 'Desconhecida'],
                                ];
                                $config = $statusConfig[$nfe->status_manifestacao] ?? $statusConfig['sem_manifesto'];
                            @endphp
                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $config['bg'] }} {{ $config['text'] }}">
                                {{ $config['label'] }}
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $nfe->status === 'autorizada' ? 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400' }}">
                                {{ ucfirst($nfe->status ?? 'Pendente') }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            @php
                                $downloadUrl = route('nfe.download-xml', ['id' => $nfe->id, 'tipo' => $view === 'recebidas' ? 'recebida' : 'emitida']);
                            @endphp
                            <button 
                                onclick="window.location.href='{{ $downloadUrl }}'" 
                                class="p-2 rounded-lg text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-all" 
                                title="Baixar XML"
                            >
                                <i class="fas fa-file-code text-sm"></i>
                            </button>
                            @php
                                $danfeUrl = route('nfe.danfe', ['id' => $nfe->id, 'tipo' => $view === 'recebidas' ? 'recebida' : 'emitida']);
                            @endphp
                            <button 
                                onclick="window.open('{{ $danfeUrl }}', '_blank')"
                                class="p-2 rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-all" 
                                title="Imprimir DANFE A4"
                            >
                                <i class="fas fa-file-pdf text-sm"></i>
                            </button>
                            @php
                                $danfeSimplificadaUrl = route('nfe.danfe-simplificada', ['id' => $nfe->id, 'tipo' => $view === 'recebidas' ? 'recebida' : 'emitida']);
                            @endphp
                            <button 
                                onclick="window.open('{{ $danfeSimplificadaUrl }}', '_blank')"
                                class="p-2 rounded-lg text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-all" 
                                title="Imprimir DANFE Simplificada"
                            >
                                <i class="fas fa-receipt text-sm"></i>
                            </button>
                            @if($view === 'recebidas' && $nfe->status_manifestacao === 'sem_manifesto')
                                <button 
                                    wire:click="manifestar({{ $nfe->id }}, 'confirmada')" 
                                    class="p-2 rounded-lg text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-all" 
                                    title="Manifestar"
                                >
                                    <i class="fas fa-check-double text-sm"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-dark-800 flex items-center justify-center mb-4">
                                <i class="fas fa-inbox text-2xl text-slate-400"></i>
                            </div>
                            <p class="text-slate-500 font-medium">Nenhum documento fiscal encontrado</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($this->nfes->hasPages())
        <div class="px-4 py-3 border-t border-slate-200 dark:border-dark-800 bg-slate-50 dark:bg-dark-950">
            {{ $this->nfes->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Busca Retroativa -->
    @if($showRetroativa)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-dark-950/80 backdrop-blur-sm" wire:click.self="$set('showRetroativa', false)">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-dark-800 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-history text-amber-500"></i>
                    Busca Retroativa
                </h3>
                <button wire:click="$set('showRetroativa', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-6 space-y-4">
                <p class="text-sm text-slate-500">Busque notas fiscais dos últimos 90 dias automaticamente.</p>
                
                <div class="p-4 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 rounded-xl">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-calendar-alt text-indigo-500 text-xl"></i>
                        <div>
                            <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-400">Período: Últimos 90 dias</p>
                            <p class="text-xs text-indigo-600 dark:text-indigo-300">
                                De: {{\Carbon\Carbon::now()->subDays(90)->format('d/m/Y')}} até {{\Carbon\Carbon::now()->format('d/m/Y')}}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-dark-950 flex gap-3">
                <button wire:click="$set('showRetroativa', false)" class="flex-1 px-4 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-400 font-semibold text-sm hover:bg-slate-300 dark:hover:bg-dark-700 transition-all">
                    Cancelar
                </button>
                <button 
                    wire:click="startRetroativa" 
                    wire:loading.attr="disabled" 
                    class="flex-1 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-semibold text-sm transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                >
                    <i class="fas fa-search" wire:loading.remove></i>
                    <i class="fas fa-spinner fa-spin" wire:loading></i>
                    Buscar
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de Importação -->
    @if($showImportModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-dark-950/80 backdrop-blur-sm">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-dark-800">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-file-import text-indigo-500"></i>
                    Importando Documentos Fiscais
                </h3>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Progresso -->
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Progresso</span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $importProgress }}%</span>
                    </div>
                    <div class="h-3 bg-slate-200 dark:bg-dark-700 rounded-full overflow-hidden">
                        <div 
                            class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-300" 
                            style="width: {{ $importProgress }}%"
                        ></div>
                    </div>
                    <p class="text-xs text-slate-500">{{ $importStatus }}</p>
                </div>

                <!-- Estatísticas -->
                @if($importResults)
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-emerald-50 dark:bg-emerald-500/10 rounded-xl p-4 text-center border border-emerald-200 dark:border-emerald-500/20">
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $importResults['success'] ?? 0 }}</div>
                        <div class="text-xs text-emerald-700 dark:text-emerald-400 font-medium uppercase">Importadas</div>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-500/10 rounded-xl p-4 text-center border border-amber-200 dark:border-amber-500/20">
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ count($importResults['errors'] ?? []) }}</div>
                        <div class="text-xs text-amber-700 dark:text-amber-400 font-medium uppercase">Erros</div>
                    </div>
                    <div class="bg-slate-100 dark:bg-dark-800 rounded-xl p-4 text-center border border-slate-200 dark:border-dark-700">
                        <div class="text-2xl font-bold text-slate-600 dark:text-slate-400">{{ $importResults['total'] ?? 0 }}</div>
                        <div class="text-xs text-slate-500 font-medium uppercase">Total</div>
                    </div>
                </div>

                <!-- Erros -->
                @if(!empty($importResults['errors']))
                <div class="bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 rounded-xl p-4 max-h-32 overflow-y-auto">
                    <div class="flex items-center gap-2 text-rose-700 dark:text-rose-400 font-semibold text-sm mb-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        Erros encontrados:
                    </div>
                    <ul class="text-xs text-rose-600 dark:text-rose-300 space-y-1">
                        @foreach($importResults['errors'] as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @endif
            </div>

            @if($importResults)
            <div class="px-6 py-4 bg-slate-50 dark:bg-dark-950 flex justify-end">
                <button 
                    wire:click="$set('showImportModal', false)" 
                    class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm transition-all"
                >
                    <i class="fas fa-check mr-2"></i>
                    Finalizar
                </button>
            </div>
            @else
            <div class="px-6 py-4 bg-slate-50 dark:bg-dark-950 flex items-center justify-center gap-2 text-slate-400">
                <i class="fas fa-circle-notch fa-spin text-indigo-500"></i>
                <span class="text-sm font-medium">Processando...</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Importação de NFes -->
    <div class="mt-6 bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-file-import text-indigo-500"></i>
            Importar NFes de Marketplaces
        </h3>
        
        <div class="flex flex-wrap items-end gap-4">
            <div class="w-48">
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Integração</label>
                <select wire:model="importIntegration" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-indigo-500">
                    <option value="">Selecione...</option>
                    @foreach($this->getIntegracoes as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="w-40">
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Data Inicial</label>
                <input type="date" wire:model="importDataDe" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-indigo-500">
            </div>
            
            <div class="w-40">
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Data Final</label>
                <input type="date" wire:model="importDataAte" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-indigo-500">
            </div>
            
            <button 
                wire:click="startImport"
                wire:loading.attr="disabled"
                type="button"
                class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <i class="fas fa-download"></i>
                Importar NFes
            </button>
        </div>
        
        <p class="text-xs text-slate-500 mt-3">
            Importe notas fiscais dos seus pedidos no Mercado Livre ou Bling. O processamento será feito em background.
        </p>
    </div>
</div>
