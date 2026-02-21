<?php

namespace App\Http\Controllers;

use App\Services\AmazonIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AmazonController extends Controller
{
    public function connect(Request $request)
    {
        $request->validate([
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'refresh_token' => 'required|string',
            'seller_id' => 'nullable|string',
            'marketplace_id' => 'nullable|string',
            'nome_conta' => 'nullable|string',
        ]);

        $empresaId = Auth::user()->current_empresa_id;
        $amazonService = new AmazonIntegrationService($empresaId);

        if ($request->edit_mode) {
            $testResult = $amazonService->testCredentials($request->all());
            if (! $testResult['success']) {
                return redirect()->route('integrations.index')->with('error', 'Credenciais inválidas: '.$testResult['message']);
            }

            $amazonService->updateCredentials($request->all());

            return redirect()->route('integrations.index')->with('message', 'Amazon atualizado com sucesso! ⚡');
        }

        $testResult = $amazonService->testCredentials($request->all());

        if (! $testResult['success']) {
            return redirect()->route('integrations.index')->with('error', 'Credenciais inválidas: '.$testResult['message']);
        }

        if ($amazonService->connect($request->all())) {
            return redirect()->route('integrations.index')->with('message', 'Amazon conectado com sucesso! ⚡');
        }

        return redirect()->route('integrations.index')->with('error', 'Falha ao salvar. Tente novamente.');
    }

    public function disconnect()
    {
        $empresaId = Auth::user()->current_empresa_id;
        $amazonService = new AmazonIntegrationService($empresaId);

        $amazonService->disconnect();

        return redirect()->route('integrations.index')->with('message', 'Amazon desconectado com sucesso.');
    }

    public function syncOrders()
    {
        $empresaId = Auth::user()->current_empresa_id;
        $amazonService = new AmazonIntegrationService($empresaId);

        if (! $amazonService->isConnected()) {
            return redirect()->route('integrations.index')->with('error', 'Amazon não está conectado.');
        }

        $orders = $amazonService->getOrders();

        if (isset($orders['error'])) {
            return redirect()->route('integrations.index')->with('error', $orders['error']);
        }

        $total = $orders['payload']['Orders'] ?? [];

        return redirect()->route('integrations.index')->with('message', 'Pedidos sincronizados! Total: '.count($total));
    }

    public function syncInventory()
    {
        $empresaId = Auth::user()->current_empresa_id;
        $amazonService = new AmazonIntegrationService($empresaId);

        if (! $amazonService->isConnected()) {
            return redirect()->route('integrations.index')->with('error', 'Amazon não está conectado.');
        }

        $inventory = $amazonService->getInventory();

        if (isset($inventory['error'])) {
            return redirect()->route('integrations.index')->with('error', $inventory['error']);
        }

        return redirect()->route('integrations.index')->with('message', 'Inventário sincronizado com sucesso!');
    }
}
