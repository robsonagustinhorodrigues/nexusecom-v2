<?php

use Illuminate\Support\Facades\Route;

// Rota para servir arquivos do storage via Laravel (bypassa o bloqueio do servidor estático)
Route::get('/media/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        abort(404);
    }
    return response()->file($fullPath);
})->where('path', '.*');

// Main routes - NEW Alpine pages
Route::get('/', function () {
    return redirect('/dashboard');
})->middleware('auth');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

// Login
Route::get('/login', function () {
    return view('auth.login-simple');
})->middleware('guest');

Route::get('/login-simple', function () {
    return view('auth.login-simple');
})->middleware('guest');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
        $request->session()->regenerate();

        return redirect('/dashboard');
    }

    return back()->withErrors([
        'email' => 'As credenciais fornecidas estão incorretas.',
    ])->onlyInput('email');
})->middleware('guest')->name('login');

// Logout (sem autenticação)
Route::post('/webhooks/meli', [\App\Http\Controllers\WebhookController::class, 'meli'])->name('webhooks.meli');
Route::post('/webhooks/bling', [\App\Http\Controllers\WebhookController::class, 'bling'])->name('webhooks.bling');
Route::post('/webhooks/{source}', [\App\Http\Controllers\WebhookController::class, 'generic'])->name('webhooks.generic');
Route::get('/webhooks/health', [\App\Http\Controllers\WebhookController::class, 'health'])->name('webhooks.health');

Route::middleware(['auth'])->group(function () {
    // Upload de cookies para scraping
    Route::post('/admin/integrations/upload-cookies', [\App\Http\Controllers\Admin\IntegracaoController::class, 'uploadCookies']);

    // Products
    Route::get('/products', function () {
        return view('products.list-alpine');
    })->name('products.index');

    Route::get('/products/create', function () {
        return view('products.create-alpine');
    })->name('products.create');

    Route::get('/products/edit', function () {
        return view('products.edit-alpine');
    })->name('products.edit');

    Route::get('/products/edit/{id}', function ($id) {
        return view('products.edit-alpine', ['id' => $id]);
    })->name('products.edit.id');

    // Orders
    Route::get('/orders', function () {
        return view('orders.list-alpine');
    })->name('orders.index');

    // Anuncios
    Route::get('/anuncios', function () {
        return view('anuncios.list-alpine');
    })->name('anuncios.index');

    // Estoque
    Route::get('/estoque', function () {
        return view('estoque.index-alpine');
    })->name('estoque.index');

    // Fiscal
    Route::get('/fiscal/nfe', function () {
        return view('fiscal.nfe-alpine');
    })->name('fiscal.nfe');

    Route::get('/fiscal/monitor', function () {
        return redirect('/fiscal/nfe');
    })->name('fiscal.monitor');

    // Other routes that still work
    Route::get('/integrations', function () {
        return view('admin.integrations-alpine');
    })->name('integrations.index');

    // Admin routes
    Route::get('/admin/empresas', function () {
        return view('admin.empresas-alpine');
    })->name('admin.empresas');
    Route::get('/admin/depositos', function () {
        return view('admin.armazens-alpine');
    })->name('admin.depositos');
    Route::get('/admin/usuarios', function () {
        return view('admin.usuarios-alpine');
    })->name('admin.usuarios');
    Route::get('/admin/configuracoes', function () {
        return view('admin.configuracoes-alpine');
    })->name('admin.configuracoes');

    // Novas rotas Alpine
    Route::get('/admin/avisos', function () {
        return view('admin.avisos-alpine');
    })->name('admin.avisos');

    Route::get('/admin/tarefas', function () {
        return view('admin.tarefas-alpine');
    })->name('admin.tarefas');

    // API Routes for Avisos e Tarefas
    Route::get('/api/admin/notificacoes', function () {
        $notificacoes = \App\Models\Notificacao::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json(['notificacoes' => $notificacoes]);
    });

    Route::post('/api/admin/notificacoes/marcar-lida', function () {
        \App\Models\Notificacao::where('user_id', auth()->id())->update(['lida' => true]);

        return response()->json(['success' => true]);
    });

    Route::post('/api/admin/notificacoes/{id}/marcar-lida', function ($id) {
        \App\Models\Notificacao::where('user_id', auth()->id())
            ->where('id', $id)
            ->update(['lida' => true]);

        return response()->json(['success' => true]);
    });

    Route::delete('/api/admin/notificacoes/{id}', function ($id) {
        \App\Models\Notificacao::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    });

    Route::delete('/api/admin/notificacoes/limpar', function () {
        \App\Models\Notificacao::where('user_id', auth()->id())->delete();

        return response()->json(['success' => true]);
    });

    Route::get('/api/admin/tarefas', function () {
        $tarefas = \App\Models\Tarefa::with('empresa')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $stats = [
            'total' => $tarefas->count(),
            'processando' => $tarefas->where('status', 'processando')->count(),
            'concluido' => $tarefas->where('status', 'concluido')->count(),
            'erro' => $tarefas->where('status', 'erro')->count(),
        ];

        return response()->json(['tarefas' => $tarefas, 'stats' => $stats]);
    });

    Route::delete('/api/admin/tarefas/limpar', function () {
        \App\Models\Tarefa::where('status', 'concluido')->delete();

        return response()->json(['success' => true]);
    });

    Route::post('/api/admin/tarefas/{id}/cancelar', function ($id) {
        $tarefa = \App\Models\Tarefa::findOrFail($id);
        if ($tarefa->cancelar()) {
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Tarefa não pode ser cancelada.'], 400);
    });

    Route::delete('/api/admin/tarefas/{id}', function ($id) {
        \App\Models\Tarefa::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    });

    Route::delete('/api/admin/tarefas/limpar-tudo', function () {
        \App\Models\Tarefa::query()->delete();
        return response()->json(['success' => true]);
    });

    Route::get('/roadmap', function () {
        $phases = (new \App\Livewire\Admin\Roadmap)->phases;

        return view('admin.roadmap-alpine', compact('phases'));
    })->name('roadmap');

    // Financial routes
    Route::get('/financial', function () {
        return redirect('/dre');
    })->name('financial.dashboard');
    Route::get('/dre', function () {
        return view('dre.index-alpine');
    })->name('dre.index');
    Route::get('/finances/despesas', function () {
        return redirect('/dashboard');
    })->name('finances.despesas');

    // Fiscal routes
    Route::get('/fiscal/skus', \App\Livewire\Fiscal\SkuNfe::class)->name('fiscal.skus');
    Route::get('/fiscal/relatorio/faturamento', function () {
        return redirect('/fiscal/nfe');
    })->name('fiscal.relatorio.faturamento');
    Route::get('/fiscal/relatorio/simples', function () {
        return redirect('/fiscal/nfe');
    })->name('fiscal.relatorio.simples');
    Route::get('/fiscal/relatorio/ncm', [\App\Http\Controllers\RelatorioNcmController::class, 'index'])->name('fiscal.relatorio.ncm');
    Route::get('/fiscal/relatorio/ncm/export', [\App\Http\Controllers\RelatorioNcmController::class, 'exportPdf'])->name('fiscal.relatorio.ncm.export');

    // Integration routes
    Route::get('/integrations/parceiros', function () {
        return redirect('/integrations');
    })->name('integrations.parceiros');

    // Tool routes
    Route::get('/tools/zpl', \App\Livewire\Tools\Zpl::class)->name('tools.zpl');
    Route::get('/tools/ean', \App\Livewire\Tools\Ean::class)->name('tools.ean');

    // NF-e download routes
    Route::get('/nfe/danfe/{id}/{tipo}', [\App\Http\Controllers\NfeController::class, 'danfe'])->name('nfe.danfe');
    Route::get('/nfe/danfe-simplificada/{id}/{tipo}', [\App\Http\Controllers\NfeController::class, 'danfeSimplificada'])->name('nfe.danfe-simplificada');
    Route::get('/nfe/download-xml/{id}/{tipo}', [\App\Http\Controllers\NfeController::class, 'downloadXml'])->name('nfe.download-xml');

    // Integration routes
    Route::get('/integrations/meli/redirect', [\App\Http\Controllers\MeliController::class, 'redirect'])->name('meli.redirect');
    Route::get('/integrations/meli/auth/callback', [\App\Http\Controllers\MeliController::class, 'callback'])->name('meli.callback');
    Route::post('/integrations/meli/update-nome', [\App\Http\Controllers\MeliController::class, 'updateNome'])->name('meli.update-nome');
    Route::post('/integrations/meli/test', [\App\Http\Controllers\MeliController::class, 'testConnection'])->name('meli.test');

    Route::get('/integrations/bling/connect', [\App\Http\Controllers\BlingController::class, 'connect'])->name('bling.connect');
    Route::get('/integrations/bling/callback', [\App\Http\Controllers\BlingController::class, 'oauthCallback'])->name('bling.callback');
    Route::post('/integrations/bling/disconnect', [\App\Http\Controllers\BlingController::class, 'disconnect'])->name('bling.disconnect');
    Route::post('/integrations/bling/update-nome', [\App\Http\Controllers\BlingController::class, 'updateNome'])->name('bling.update-nome');
    Route::post('/integrations/bling/sync-produtos', [\App\Http\Controllers\BlingController::class, 'syncProdutos'])->name('bling.sync-produtos');
    Route::post('/integrations/bling/sync-pedidos', [\App\Http\Controllers\BlingController::class, 'syncPedidos'])->name('bling.sync-pedidos');
    Route::post('/integrations/bling/test', [\App\Http\Controllers\BlingController::class, 'testConnection'])->name('bling.test');
    Route::get('/integrations/bling/processar-notificacoes', [\App\Http\Controllers\BlingController::class, 'processarNotificacoes'])->name('bling.processar-notificacoes');

    Route::get('/integrations/amazon/connect', [\App\Http\Controllers\AmazonController::class, 'showConnectForm'])->name('amazon.connect');
    Route::post('/integrations/amazon/connect', [\App\Http\Controllers\AmazonController::class, 'connect'])->name('amazon.connect.post');
    Route::post('/integrations/amazon/disconnect', [\App\Http\Controllers\AmazonController::class, 'disconnect'])->name('amazon.disconnect');
    Route::post('/integrations/amazon/update-nome', [\App\Http\Controllers\AmazonController::class, 'updateNome'])->name('amazon.update-nome');
    Route::post('/integrations/amazon/test', [\App\Http\Controllers\AmazonController::class, 'testConnection'])->name('amazon.test');
    Route::post('/integrations/amazon/sync-orders', [\App\Http\Controllers\AmazonController::class, 'syncOrders'])->name('amazon.sync-orders');
    Route::post('/integrations/amazon/sync-inventory', [\App\Http\Controllers\AmazonController::class, 'syncInventory'])->name('amazon.sync-inventory');

    // Legacy routes (commented - not available)
    // Route::get('/roadmap', \App\Livewire\Admin\Roadmap::class)->name('roadmap');
    // Route::get('/wms', \App\Livewire\Wms\Index::class)->name('wms.index');
    Route::get('/wms', function () {
        return redirect('/estoque');
    })->name('wms.index');
});

// API Routes
Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::post('/products', [App\Http\Controllers\Api\ProductController::class, 'store']);
    Route::get('/products/search', [App\Http\Controllers\Api\ProductController::class, 'search']);
    Route::get('/products/export', [App\Http\Controllers\Api\ProductController::class, 'export']);
    Route::post('/products/import', [App\Http\Controllers\Api\ProductController::class, 'import']);
    Route::get('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'show']);
    Route::put('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'update']);
    Route::delete('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'destroy']);

    Route::get('/anuncios', [App\Http\Controllers\Api\AnuncioController::class, 'index']);
    Route::get('/anuncios/promocoes', [App\Http\Controllers\Api\AnuncioController::class, 'listPromocoes']);
    Route::get('/anuncios/search-products', [App\Http\Controllers\Api\AnuncioController::class, 'searchProducts']);
    Route::get('/anuncios/{id}', [App\Http\Controllers\Api\AnuncioController::class, 'show']);
    Route::put('/anuncios/{id}', [App\Http\Controllers\Api\AnuncioController::class, 'update']);
    Route::post('/anuncios/sync', [App\Http\Controllers\Api\AnuncioController::class, 'sync']);
    Route::post('/anuncios/{id}/vincular', [App\Http\Controllers\Api\AnuncioController::class, 'vincular']);
    Route::post('/anuncios/{id}/desvincular', [App\Http\Controllers\Api\AnuncioController::class, 'desvincular']);
    Route::post('/anuncios/vincular-por-sku', [App\Http\Controllers\Api\AnuncioController::class, 'vincularPorSku']);
    Route::get('/anuncios/{id}/repricer', [App\Http\Controllers\Api\AnuncioController::class, 'getRepricerConfig']);
    Route::post('/anuncios/{id}/repricer', [App\Http\Controllers\Api\AnuncioController::class, 'saveRepricerConfig']);
    Route::post('/anuncios/{id}/importar', [App\Http\Controllers\Api\AnuncioController::class, 'importAsProduct']);
    Route::get('/anuncios/{id}/repricer/logs', [App\Http\Controllers\Api\AnuncioController::class, 'getRepricerLogs']);

    Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/integrations', [App\Http\Controllers\Api\OrderController::class, 'integrations']);
    Route::post('/orders/sync', [App\Http\Controllers\Api\OrderController::class, 'sync']);
    Route::post('/orders/{id}/refresh', [App\Http\Controllers\Api\OrderController::class, 'refresh']);
    Route::post('/orders/recalculate', [App\Http\Controllers\Api\OrderController::class, 'recalculate']);
    Route::get('/orders/{id}', [App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::put('/orders/{id}', [App\Http\Controllers\Api\OrderController::class, 'update']);
    Route::get('/orders/{id}/danfe', [App\Http\Controllers\Api\OrderController::class, 'danfe']);
    Route::get('/orders/{id}/danfe-simplificada', [App\Http\Controllers\Api\OrderController::class, 'danfeSimplificada']);
    Route::get('/orders/{id}/etiqueta', [App\Http\Controllers\Api\OrderController::class, 'etiqueta']);
    Route::get('/orders/{id}/etiqueta-meli', [App\Http\Controllers\Api\OrderController::class, 'etiquetaMeli']);
    Route::get('/dashboard/lucratividade', [App\Http\Controllers\Api\DashboardController::class, 'lucratividade']);
    Route::get('/dashboard/vendas-diarias', [App\Http\Controllers\Api\DashboardController::class, 'vendasDiarias']);
    Route::get('/dashboard/vendas-marketplace', [App\Http\Controllers\Api\DashboardController::class, 'vendasPorMarketplace']);
    Route::get('/dashboard/atividade-horaria', [App\Http\Controllers\Api\DashboardController::class, 'atividadeHoraria']);
    Route::get('/dashboard/top-produtos', [App\Http\Controllers\Api\DashboardController::class, 'topProdutos']);
    Route::get('/dashboard/pedidos-recentes', [App\Http\Controllers\Api\DashboardController::class, 'pedidosRecentes']);
    Route::get('/dashboard/vendas-negativas', [App\Http\Controllers\Api\DashboardController::class, 'vendasNegativas']);
    Route::post('/orders/link-item', [App\Http\Controllers\Api\OrderController::class, 'linkItem']);

    Route::get('/nfes', [App\Http\Controllers\Api\NfeController::class, 'index']);
    Route::get('/nfes/{id}', [App\Http\Controllers\Api\NfeController::class, 'show']);
    Route::post('/nfes/import', [App\Http\Controllers\Api\NfeController::class, 'import']);
    Route::post('/nfes/import-meli', [App\Http\Controllers\Api\NfeController::class, 'importMeli']);
    Route::post('/nfes/import-xml', [App\Http\Controllers\Api\NfeController::class, 'importXml']);
    Route::post('/nfes/import-zip', [App\Http\Controllers\Api\NfeController::class, 'importZip']);
    Route::post('/nfes/import-bling', [App\Http\Controllers\Api\NfeController::class, 'importBling']);
    Route::post('/nfes/reprocess-association', [App\Http\Controllers\Api\NfeController::class, 'reprocessAssociation']);
    Route::post('/api/nfe/import/meli', [App\Http\Controllers\Api\NfeController::class, 'importMeli']);
    Route::post('/api/nfe/import/xml', [App\Http\Controllers\Api\NfeController::class, 'importXml']);
    Route::post('/api/nfe/import/zip', [App\Http\Controllers\Api\NfeController::class, 'importZip']);

    Route::get('/estoque', [App\Http\Controllers\Api\EstoqueController::class, 'index']);
    Route::get('/estoque/depositos', [App\Http\Controllers\Api\EstoqueController::class, 'depositos']);
    Route::post('/estoque/depositos', [App\Http\Controllers\Api\EstoqueController::class, 'storeDeposito']);
    Route::put('/estoque/depositos/{id}', [App\Http\Controllers\Api\EstoqueController::class, 'updateDeposito']);
    Route::delete('/estoque/depositos/{id}', [App\Http\Controllers\Api\EstoqueController::class, 'destroyDeposito']);
    Route::get('/estoque/{id}', [App\Http\Controllers\Api\EstoqueController::class, 'show']);
    Route::put('/estoque/{id}', [App\Http\Controllers\Api\EstoqueController::class, 'update']);

    // Relatorios
    Route::get('/relatorio-ncm', [\App\Http\Controllers\RelatorioNcmController::class, 'getData']);
});

// API Routes
Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/admin/empresas', function () {
        return \App\Models\Empresa::orderBy('nome')->get();
    });
    Route::get('/admin/integrations', function (\Illuminate\Http\Request $request) {
        $empresaId = $request->get('empresa', session('empresa_id', 6));

        return \App\Models\Integracao::where('empresa_id', $empresaId)->get();
    });
    Route::put('/admin/empresas/{id}', function (\Illuminate\Http\Request $request, $id) {
        $empresa = \App\Models\Empresa::findOrFail($id);
        $empresa->update($request->except(['certificado_a1_path', 'certificado_senha']));

        return $empresa;
    });

    Route::delete('/admin/empresas/{id}', function ($id) {
        \App\Models\Empresa::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    });

    Route::post('/admin/empresas/{id}/certificado', function (\Illuminate\Http\Request $request, $id) {
        // Forçar resposta JSON mesmo em erros de validação
        $request->headers->set('Accept', 'application/json');

        $request->validate([
            'certificado' => 'required|file|max:5120',
            'senha'       => 'required|string',
        ]);

        // Verifica extensão manualmente (pfx ou p12)
        $extensao = strtolower($request->file('certificado')->getClientOriginalExtension());
        if (!in_array($extensao, ['pfx', 'p12'])) {
            return response()->json(['success' => false, 'error' => 'Arquivo inválido. Envie um certificado .pfx ou .p12.'], 422);
        }

        $empresa = \App\Models\Empresa::findOrFail($id);

        // Salva o arquivo no storage
        $path = $request->file('certificado')->store('certificados');

        // Valida o certificado antes de salvar
        try {
            $pfxContent = \Illuminate\Support\Facades\Storage::get($path);
            \NFePHP\Common\Certificate::readPfx($pfxContent, $request->senha);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Storage::delete($path);

            // Tenta converter certificado legado
            if (strpos($e->getMessage(), 'unsupported') !== false || strpos($e->getMessage(), '0308010C') !== false || strpos($e->getMessage(), 'mac verify') !== false) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Certificado inválido ou senha incorreta: ' . $e->getMessage(),
                ], 422);
            }

            return response()->json([
                'success' => false,
                'error'   => 'Certificado inválido ou senha incorreta: ' . $e->getMessage(),
            ], 422);
        }

        // Remove certificado antigo se existir
        if ($empresa->certificado_a1_path && $empresa->certificado_a1_path !== $path) {
            \Illuminate\Support\Facades\Storage::delete($empresa->certificado_a1_path);
        }

        $empresa->update([
            'certificado_a1_path' => $path,
            'certificado_senha'   => $request->senha,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Certificado enviado e validado com sucesso!',
            'path'    => $path,
        ]);
    });

    Route::post('/sefaz/testar-certificado/{id}', function ($id) {
        $empresa = \App\Models\Empresa::findOrFail($id);
        try {
            $engine = new \App\Services\SefazEngine();
            $cert = (new \ReflectionClass($engine))->getMethod('getCertificate');
            $cert->setAccessible(true);
            $cert->invoke($engine, $empresa);
            return response()->json(['success' => true, 'message' => 'Certificado lido e validado com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    });

    Route::get('/admin/configuracoes', function () {
        $grupoId = auth()->user()->grupo_id;
        $config = \App\Models\GrupoConfiguracao::getOrCreateForGrupo($grupoId);
        $config->sefaz_hora_inicio = $config->sefaz_hora_inicio ? \Carbon\Carbon::parse($config->sefaz_hora_inicio)->format('H:i') : '08:00';
        $config->sefaz_hora_fim = $config->sefaz_hora_fim ? \Carbon\Carbon::parse($config->sefaz_hora_fim)->format('H:i') : '20:00';

        return $config;
    });

    Route::post('/admin/configuracoes', function (\Illuminate\Http\Request $request) {
        $grupoId = auth()->user()->grupo_id;

        return \App\Models\GrupoConfiguracao::updateOrCreate(
            ['grupo_id' => $grupoId],
            [
                'sefaz_intervalo_minutos' => $request->sefaz_intervalo_minutos,
                'sefaz_auto_busca' => $request->sefaz_auto_busca,
                'sefaz_hora_inicio' => $request->sefaz_hora_inicio.':00',
                'sefaz_hora_fim' => $request->sefaz_hora_fim.':00',
                'nfe_auto_manifestar' => $request->nfe_auto_manifestar,
                'nfe_dias_retroativos' => $request->nfe_dias_retroativos,
                'observacoes' => $request->observacoes,
            ]
        );
    });

    Route::get('/admin/usuarios', function () {
        return \App\Models\User::with(['roles', 'empresas'])->get();
    });

    Route::post('/admin/usuarios', function (\Illuminate\Http\Request $request) {
        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        if ($request->role_id) {
            $user->syncRoles([\Spatie\Permission\Models\Role::findById($request->role_id)]);
        }
        $user->empresas()->sync($request->selected_empresas);

        return $user;
    });

    Route::put('/admin/usuarios/{id}', function (\Illuminate\Http\Request $request, $id) {
        $user = \App\Models\User::findOrFail($id);
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);
        if ($request->password) {
            $user->update(['password' => bcrypt($request->password)]);
        }
        if ($request->role_id) {
            $user->syncRoles([\Spatie\Permission\Models\Role::findById($request->role_id)]);
        }
        $user->empresas()->sync($request->selected_empresas);

        return $user;
    });

    Route::get('/admin/roles', function () {
        return \Spatie\Permission\Models\Role::all();
    });

    // DRE API
    Route::get('/dre', function (\Illuminate\Http\Request $request) {
        $dreController = new \App\Livewire\Dre\Index;
        $dreController->empresaId = $request->empresa ?? auth()->user()->current_empresa_id;
        $dreController->mes = $request->mes ?? now()->month;
        $dreController->ano = $request->ano ?? now()->year;

        return response()->json([
            'dre' => $dreController->getDreDataProperty(),
            'despesas' => $dreController->getDespesasPorCategoria(),
        ]);
    });
});

// Logout route
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->name('logout');

// Amazon Ads Automator Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/amazon-ads/dashboard', [App\Http\Controllers\AmazonAdsWebController::class, 'index'])->name('amazon-ads.dashboard');
    Route::get('/amazon-ads/settings', [App\Http\Controllers\AmazonAdsWebController::class, 'settings'])->name('amazon-ads.settings');

    Route::prefix('api/amazon-ads')->group(function () {
        Route::get('/config', [App\Http\Controllers\Api\AmazonAdsController::class, 'getConfig']);
        Route::post('/config', [App\Http\Controllers\Api\AmazonAdsController::class, 'saveConfig']);
        Route::get('/sku-configs', [App\Http\Controllers\Api\AmazonAdsController::class, 'getSkuConfigs']);
        Route::post('/sku-configs', [App\Http\Controllers\Api\AmazonAdsController::class, 'saveSkuConfig']);
        Route::get('/listings/search', [App\Http\Controllers\Api\AmazonAdsController::class, 'searchListings']);
        Route::post('/ai/suggestions', [App\Http\Controllers\Api\AmazonAdsController::class, 'generateAiSuggestions']);
        Route::get('/campaigns', [App\Http\Controllers\Api\AmazonAdsController::class, 'listCampaigns']);
        Route::post('/campaigns/sync', [App\Http\Controllers\Api\AmazonAdsController::class, 'syncCampaigns']);
    });
});

require __DIR__.'/auth.php';
