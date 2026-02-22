<?php

use Illuminate\Support\Facades\Route;

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

    // OLD routes - Livewire (backup)
    Route::prefix('_old')->group(function () {
        Route::get('/dashboard', \App\Livewire\Dashboard::class);
        Route::get('/products', \App\Livewire\Products\Index::class);
        Route::get('/products/create', \App\Livewire\Products\Create::class);
        Route::get('/products/edit/{id}', \App\Livewire\Products\Create::class);
        Route::get('/orders', \App\Livewire\Orders\Index::class);
        Route::get('/anuncios', \App\Livewire\Integrations\Anuncios::class);
        Route::get('/estoque', \App\Livewire\Estoque\Dashboard::class);
    });

    // Other routes that still work
    Route::get('/integrations', function () {
        return view('admin.integrations-alpine');
    })->name('integrations.index');
    Route::get('/integrations/anuncios', \App\Livewire\Integrations\Anuncios::class)->name('integrations.anuncios');

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
    Route::get('/admin/avisos', \App\Livewire\Admin\Avisos::class)->name('admin.avisos');
    Route::get('/admin/tarefas', \App\Livewire\Admin\Tarefas::class)->name('admin.tarefas');
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
    Route::post('/integrations/amazon/connect', [\App\Http\Controllers\AmazonController::class, 'connect'])->name('amazon.connect');
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
    Route::get('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'show']);
    Route::put('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'update']);
    Route::delete('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'destroy']);

    Route::get('/anuncios', [App\Http\Controllers\Api\AnuncioController::class, 'index']);
    Route::get('/anuncios/search-products', [App\Http\Controllers\Api\AnuncioController::class, 'searchProducts']);
    Route::get('/anuncios/{id}', [App\Http\Controllers\Api\AnuncioController::class, 'show']);
    Route::put('/anuncios/{id}', [App\Http\Controllers\Api\AnuncioController::class, 'update']);
    Route::post('/anuncios/sync', [App\Http\Controllers\Api\AnuncioController::class, 'sync']);
    Route::post('/anuncios/{id}/vincular', [App\Http\Controllers\Api\AnuncioController::class, 'vincular']);
    Route::get('/anuncios/{id}/repricer', [App\Http\Controllers\Api\AnuncioController::class, 'getRepricerConfig']);
    Route::post('/anuncios/{id}/repricer', [App\Http\Controllers\Api\AnuncioController::class, 'saveRepricerConfig']);
    Route::post('/anuncios/{id}/importar', [App\Http\Controllers\Api\AnuncioController::class, 'importAsProduct']);

    Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/{id}', [App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::put('/orders/{id}', [App\Http\Controllers\Api\OrderController::class, 'update']);
    Route::post('/orders/sync', [App\Http\Controllers\Api\OrderController::class, 'sync']);

    Route::get('/nfes', [App\Http\Controllers\Api\NfeController::class, 'index']);
    Route::get('/nfes/{id}', [App\Http\Controllers\Api\NfeController::class, 'show']);
    Route::post('/nfes/import', [App\Http\Controllers\Api\NfeController::class, 'import']);

    Route::get('/estoque', [App\Http\Controllers\Api\EstoqueController::class, 'index']);
    Route::get('/estoque/depositos', [App\Http\Controllers\Api\EstoqueController::class, 'depositos']);
    Route::post('/estoque/depositos', [App\Http\Controllers\Api\EstoqueController::class, 'storeDeposito']);
    Route::put('/estoque/depositos/{id}', [App\Http\Controllers\Api\EstoqueController::class, 'updateDeposito']);
    Route::delete('/estoque/depositos/{id}', [App\Http\Controllers\Api\EstoqueController::class, 'destroyDeposito']);
    Route::get('/estoque/{id}', [App\Http\Controllers\Api\EstoqueController::class, 'show']);
    Route::put('/estoque/{id}', [App\Http\Controllers\Api\EstoqueController::class, 'update']);
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
        $empresa->update($request->all());

        return $empresa;
    });

    Route::delete('/admin/empresas/{id}', function ($id) {
        \App\Models\Empresa::findOrFail($id)->delete();

        return response()->json(['success' => true]);
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

require __DIR__.'/auth.php';
