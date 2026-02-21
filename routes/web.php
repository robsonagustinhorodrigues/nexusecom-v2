<?php

use App\Http\Controllers\InertiaController;
use App\Livewire\Actions\Logout;
use App\Livewire\Admin\Avisos;
use App\Livewire\Admin\Empresas;
use App\Livewire\Admin\Roadmap;
use App\Livewire\Admin\Tarefas;
use App\Livewire\Admin\Usuarios;
use App\Livewire\Dashboard;
use App\Livewire\Integrations\Index as IntegrationsIndex;
use App\Livewire\Products\Create as ProductCreate;
use App\Livewire\Products\Index as ProductIndex;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Livewire\Component;

Route::get('/dashboard', function () {
    return redirect()->route('dashboard');
});

// Webhooks (sem autenticação) - fora do middleware auth
Route::post('/webhooks/meli', [\App\Http\Controllers\WebhookController::class, 'meli'])->name('webhooks.meli');
Route::post('/webhooks/bling', [\App\Http\Controllers\WebhookController::class, 'bling'])->name('webhooks.bling');
Route::post('/webhooks/{source}', [\App\Http\Controllers\WebhookController::class, 'generic'])->name('webhooks.generic');
Route::get('/webhooks/health', [\App\Http\Controllers\WebhookController::class, 'health'])->name('webhooks.health');

Route::middleware(['auth'])->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/roadmap', Roadmap::class)->name('roadmap');
    Route::get('/products', ProductIndex::class)->name('products.index');
    Route::get('/products/create', ProductCreate::class)->name('products.create');
    Route::get('/products/edit/{id}', ProductCreate::class)->name('products.edit');
    Route::get('/admin/empresas', Empresas::class)->name('admin.empresas');
    Route::get('/admin/usuarios', Usuarios::class)->name('admin.usuarios');
    Route::get('/admin/avisos', Avisos::class)->name('admin.avisos');
    Route::get('/admin/tarefas', Tarefas::class)->name('admin.tarefas');
    Route::get('/admin/configuracoes', \App\Livewire\Admin\Configuracoes::class)->name('admin.configuracoes');
    Volt::route('/admin/armazens', 'admin.armazens')->name('admin.armazens');
    Volt::route('/wms', 'wms.index')->name('wms.index');
    Volt::route('/financial', 'financial.dashboard')->name('financial.dashboard');

    Route::get('/nfe/download-zip', [\App\Http\Controllers\NfeController::class, 'downloadZip'])->name('nfe.download-zip');
    Route::get('/nfe/danfe/{id}/{tipo}', [\App\Http\Controllers\NfeController::class, 'danfe'])->name('nfe.danfe');
    Route::get('/nfe/danfe-simplificada/{id}/{tipo}', [\App\Http\Controllers\NfeController::class, 'danfeSimplificada'])->name('nfe.danfe-simplificada');
    Route::get('/nfe/etiqueta/{id}/{tipo}', [\App\Http\Controllers\NfeController::class, 'etiqueta'])->name('nfe.etiqueta');
    Route::get('/nfe/download-xml/{id}/{tipo}', [\App\Http\Controllers\NfeController::class, 'downloadXml'])->name('nfe.download-xml');
    Route::get('/nfe/download-log', [\App\Http\Controllers\NfeController::class, 'downloadLog'])->name('nfe.download-log');

    Route::get('/integrations', IntegrationsIndex::class)->name('integrations.index');
    Volt::route('/integrations/parceiros', 'integrations.parceiros')->name('integrations.parceiros');
    Route::get('/integrations/anuncios', \App\Livewire\Integrations\Anuncios::class)->name('integrations.anuncios');
    Volt::route('/fiscal/monitor', 'fiscal.monitor')->name('fiscal.monitor');
    Volt::route('/fiscal/skus', 'fiscal.sku-nfe')->name('fiscal.skus');
    Volt::route('/fiscal/relatorio/faturamento', 'fiscal.relatorios.faturamento')->name('fiscal.relatorio.faturamento');
    Volt::route('/fiscal/relatorio/simples', 'fiscal.relatorios.simples')->name('fiscal.relatorio.simples');

    // Orders
    Route::get('/orders', \App\Livewire\Orders\Index::class)->name('orders.index');
    Route::get('/orders/sync-nfe-meli', [\App\Http\Controllers\NfeMeliController::class, 'syncNfeEmitidas'])->name('orders.sync-nfe-meli');

    // Estoque
    Route::get('/estoque', \App\Livewire\Estoque\Dashboard::class)->name('estoque.dashboard');
    Route::get('/estoque/depositos', \App\Livewire\Estoque\Depositos::class)->name('estoque.depositos');
    Route::get('/estoque/movimentacoes', \App\Livewire\Estoque\Movimentacoes::class)->name('estoque.movimentacoes');

    // DRE
    Volt::route('/dre', 'dre.index')->name('dre.index');
    Volt::route('/finances/despesas', 'finances.despesas')->name('finances.despesas');

    Route::get('/integrations/meli/redirect', [\App\Http\Controllers\MeliController::class, 'redirect'])->name('meli.redirect');
    Route::get('/integrations/meli/auth/callback', [\App\Http\Controllers\MeliController::class, 'callback'])->name('meli.callback');
    Route::post('/integrations/meli/update-nome', [\App\Http\Controllers\MeliController::class, 'updateNome'])->name('meli.update-nome');

    Route::post('/integrations/bling/connect', [\App\Http\Controllers\BlingController::class, 'connect'])->name('bling.connect');
    Route::post('/integrations/bling/update-nome', [\App\Http\Controllers\BlingController::class, 'updateNome'])->name('bling.update-nome');
    Route::get('/integrations/bling/auth/callback', [\App\Http\Controllers\BlingController::class, 'oauthCallback'])->name('bling.callback');
    Route::post('/integrations/bling/notification/callback', [\App\Http\Controllers\BlingController::class, 'notificationCallback'])->name('bling.notification.callback');
    Route::get('/integrations/bling/disconnect', [\App\Http\Controllers\BlingController::class, 'disconnect'])->name('bling.disconnect');
    Route::get('/integrations/bling/sync-produtos', [\App\Http\Controllers\BlingController::class, 'syncProdutos'])->name('bling.sync-produtos');
    Route::get('/integrations/bling/sync-pedidos', [\App\Http\Controllers\BlingController::class, 'syncPedidos'])->name('bling.sync-pedidos');
    Route::get('/integrations/bling/processar-notificacoes', [\App\Http\Controllers\BlingController::class, 'processarNotificacoes'])->name('bling.processar-notificacoes');

    Route::post('/integrations/amazon/connect', [\App\Http\Controllers\AmazonController::class, 'connect'])->name('amazon.connect');
    Route::get('/integrations/amazon/disconnect', [\App\Http\Controllers\AmazonController::class, 'disconnect'])->name('amazon.disconnect');
    Route::get('/integrations/amazon/sync-pedidos', [\App\Http\Controllers\AmazonController::class, 'syncOrders'])->name('amazon.sync-pedidos');
    Route::get('/integrations/amazon/sync-inventario', [\App\Http\Controllers\AmazonController::class, 'syncInventory'])->name('amazon.sync-inventario');

    // Tools
    Volt::route('/tools', 'tools.index')->name('tools.index');
    Volt::route('/tools/zpl', 'tools.zpl')->name('tools.zpl');
    Volt::route('/tools/ean', 'tools.ean')->name('tools.ean');

    Route::post('/tools/zpl/process', [\App\Http\Controllers\Tools\ToolsController::class, 'zplProcess'])->name('tools.zpl.process');
    Route::post('/tools/ean/generate', [\App\Http\Controllers\Tools\ToolsController::class, 'eanGenerate'])->name('tools.ean.generate');

    Route::post('logout', Logout::class)->name('logout');
});

require __DIR__.'/auth.php';

Route::get('/inertia-test', [InertiaController::class, 'welcome']);

// API Products (Blade + Alpine)
Route::middleware(['auth'])->group(function () {
    Route::get('/api/products', [App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::get('/api/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'show']);
    Route::post('/api/products', [App\Http\Controllers\Api\ProductController::class, 'store']);
    Route::put('/api/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'update']);
    Route::delete('/api/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'destroy']);
    Route::get('/api/products/search', [App\Http\Controllers\Api\ProductController::class, 'search']);
    Route::get('/api/categorias', [App\Http\Controllers\Api\ProductController::class, 'categorias']);
    Route::get('/api/fornecedores', [App\Http\Controllers\Api\ProductController::class, 'fornecedores']);
    Route::get('/api/tags', [App\Http\Controllers\Api\ProductController::class, 'tags']);
});


Route::middleware(['auth'])->group(function () {
    Route::get('/products/create-alpine', function () {
        return view('products.create-alpine');
    });
});


Route::get('/login-alpine', function () {
    return view('auth.login-alpine');
});


// Simple Blade + Alpine Pages
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard-simple', function () {
        return view('dashboard-simple');
    });
});

Route::get('/login-simple', function () {
    return view('auth.login-simple');
})->name('login-simple');


// Simple Pages (Blade + Alpine)
Route::get('/dashboard-refined', function () {
    return view('dashboard-refined');
})->middleware('auth');

Route::get('/products-list', function () {
    return view('products.list-alpine');
})->middleware('auth');



// Product Edit
Route::get('/products/edit-alpine', function () {
    return view('products.edit-alpine');
})->middleware('auth');



// Anuncios List (Alpine)
Route::get('/anuncios-list', function () {
    return view('anuncios.list-alpine');
})->middleware('auth');



// Anuncios API
Route::get('/api/anuncios', [App\Http\Controllers\Api\AnuncioController::class, 'index']);
Route::get('/api/anuncios/{id}', [App\Http\Controllers\Api\AnuncioController::class, 'show']);
Route::put('/api/anuncios/{id}', [App\Http\Controllers\Api\AnuncioController::class, 'update']);
Route::post('/api/anuncios/sync', [App\Http\Controllers\Api\AnuncioController::class, 'sync']);



// Search Products for linking
Route::get('/api/anuncios/search-products', [App\Http\Controllers\Api\AnuncioController::class, 'searchProducts']);

