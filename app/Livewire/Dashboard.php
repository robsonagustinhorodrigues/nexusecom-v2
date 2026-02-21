<?php

namespace App\Livewire;

use App\Models\Empresa;
use App\Models\MarketplacePedido;
use App\Models\NfeEmitida;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public $empresaId;

    public $vendasHoje = 0;

    public $faturamentoHoje = 0;

    public $pedidosHoje = 0;

    public $vendasPorMarketplace = [];

    public $vendasUltimos7Dias = [];

    public function mount()
    {
        $this->empresaId = Auth::user()?->current_empresa_id;
        $this->carregarDados();
    }

    public function carregarDados()
    {
        if (! $this->empresaId) {
            return;
        }

        $this->carregarVendasHoje();
        $this->carregarVendasPorMarketplace();
        $this->carregarVendasUltimos7Dias();
    }

    protected function carregarVendasHoje()
    {
        $hoje = now()->startOfDay();
        $agora = now();

        $this->faturamentoHoje = NfeEmitida::where('empresa_id', $this->empresaId)
            ->whereBetween('data_emissao', [$hoje, $agora])
            ->where('status', 'autorizada')
            ->where('status_nfe', '!=', 'devolucao')
            ->sum('valor_total');

        $this->pedidosHoje = MarketplacePedido::where('empresa_id', $this->empresaId)
            ->whereBetween('created_at', [$hoje, $agora])
            ->count();

        $this->vendasHoje = $this->faturamentoHoje;
    }

    protected function carregarVendasPorMarketplace()
    {
        $hoje = now()->startOfDay();
        $agora = now();

        $vendas = MarketplacePedido::where('empresa_id', $this->empresaId)
            ->whereBetween('created_at', [$hoje, $agora])
            ->selectRaw('marketplace, SUM(valor_total) as total, COUNT(*) as quantidade')
            ->groupBy('marketplace')
            ->get();

        $this->vendasPorMarketplace = [
            'mercadolivre' => ['nome' => 'Mercado Livre', 'total' => 0, 'quantidade' => 0],
            'bling' => ['nome' => 'Bling', 'total' => 0, 'quantidade' => 0],
            'shopee' => ['nome' => 'Shopee', 'total' => 0, 'quantidade' => 0],
            'amazon' => ['nome' => 'Amazon', 'total' => 0, 'quantidade' => 0],
            'magalu' => ['nome' => 'Magalu', 'total' => 0, 'quantidade' => 0],
            'outros' => ['nome' => 'Outros', 'total' => 0, 'quantidade' => 0],
        ];

        foreach ($vendas as $venda) {
            $key = $venda->marketplace ?? 'outros';
            if (! isset($this->vendasPorMarketplace[$key])) {
                $key = 'outros';
            }
            $this->vendasPorMarketplace[$key]['total'] = (float) $venda->total;
            $this->vendasPorMarketplace[$key]['quantidade'] = (int) $venda->quantidade;
        }
    }

    protected function carregarVendasUltimos7Dias()
    {
        $dados = [];

        for ($i = 6; $i >= 0; $i--) {
            $data = now()->subDays($i)->startOfDay();
            $dataFim = now()->subDays($i)->endOfDay();
            $dia = now()->subDays($i)->format('d/m');

            $total = NfeEmitida::where('empresa_id', $this->empresaId)
                ->whereBetween('data_emissao', [$data, $dataFim])
                ->where('status', 'autorizada')
                ->where('status_nfe', '!=', 'devolucao')
                ->sum('valor_total');

            $dados[] = [
                'dia' => $dia,
                'data' => $data->format('Y-m-d'),
                'total' => (float) $total,
            ];
        }

        $this->vendasUltimos7Dias = $dados;
    }

    public function render()
    {
        $empresa = Empresa::find($this->empresaId);

        return view('livewire.dashboard', [
            'empresa' => $empresa,
        ]);
    }
}
