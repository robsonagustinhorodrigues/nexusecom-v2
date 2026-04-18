<?php

namespace App\Livewire\Dre;

use App\Models\Despesa;
use App\Models\Empresa;
use App\Models\MarketplacePedido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public $empresaId;

    public $ano;

    public $mes;

    public $anos = [];

    public $meses = [];

    public $regimeTributario = 'simples_nacional';

    public $aliquotaSimples = 0;

    public $showDetails = false;

    public $detalheCategoria = null;

    public function mount()
    {
        $this->empresaId = Auth::user()->current_empresa_id;
        $this->ano = now()->year;
        $this->mes = now()->month;

        $this->anos = [
            now()->year => now()->year,
            now()->year - 1 => now()->year - 1,
            now()->year - 2 => now()->year - 2,
        ];

        $this->meses = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        $empresa = Empresa::find($this->empresaId);
        if ($empresa) {
            $this->regimeTributario = $empresa->regime_tributario ?? 'simples_nacional';
            $this->aliquotaSimples = $this->getAliquotaSimples($empresa);
        }
    }

    protected function getAliquotaSimples(Empresa $empresa): float
    {
        $receitaBruta = $this->getReceitaBruta();

        if ($receitaBruta <= 180000) {
            return 4.5;
        }
        if ($receitaBruta <= 360000) {
            return 7.3;
        }
        if ($receitaBruta <= 720000) {
            return 9.5;
        }
        if ($receitaBruta <= 1800000) {
            return 10.7;
        }
        if ($receitaBruta <= 3600000) {
            return 14.3;
        }
        if ($receitaBruta <= 4800000) {
            return 19.0;
        }

        return 22.0;
    }

    public function getDatasPeriodo()
    {
        $startDate = "{$this->ano}-{$this->mes}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        return [$startDate, $endDate];
    }

    public function getReceitaBruta()
    {
        [$startDate, $endDate] = $this->getDatasPeriodo();

        return MarketplacePedido::where('empresa_id', $this->empresaId)
            ->whereIn('status_pagamento', ['paid', 'approved', 'pago'])
            ->whereBetween('data_pagamento', [$startDate, $endDate])
            ->sum('valor_total');
    }

    public function getReceitaLiquida()
    {
        [$startDate, $endDate] = $this->getDatasPeriodo();

        return MarketplacePedido::where('empresa_id', $this->empresaId)
            ->whereIn('status_pagamento', ['paid', 'approved', 'pago'])
            ->whereBetween('data_pagamento', [$startDate, $endDate])
            ->sum(DB::raw('COALESCE(valor_liquido, 0)'));
    }

    public function getDeducoes()
    {
        [$startDate, $endDate] = $this->getDatasPeriodo();

        $taxasPlataforma = MarketplacePedido::where('empresa_id', $this->empresaId)
            ->whereIn('status_pagamento', ['paid', 'approved', 'pago'])
            ->whereBetween('data_pagamento', [$startDate, $endDate])
            ->sum(DB::raw('COALESCE(valor_taxa_platform, 0) + COALESCE(valor_taxa_fixa, 0) + COALESCE(valor_taxa_pagamento, 0)'));

        $descontos = MarketplacePedido::where('empresa_id', $this->empresaId)
            ->whereIn('status_pagamento', ['paid', 'approved', 'pago'])
            ->whereBetween('data_pagamento', [$startDate, $endDate])
            ->sum('valor_desconto');

        return $taxasPlataforma + $descontos;
    }

    public function getCmv()
    {
        return 0;
    }

    public function getDespesasPorCategoria()
    {
        [$startDate, $endDate] = $this->getDatasPeriodo();

        return Despesa::where('empresa_id', $this->empresaId)
            ->where('status', 'pago')
            ->whereBetween('data_pagamento', [$startDate, $endDate])
            ->select('categoria', DB::raw('SUM(valor) as total'))
            ->groupBy('categoria')
            ->pluck('total', 'categoria')
            ->toArray();
    }

    public function getTotalDespesas()
    {
        [$startDate, $endDate] = $this->getDatasPeriodo();

        return Despesa::where('empresa_id', $this->empresaId)
            ->where('status', 'pago')
            ->whereBetween('data_pagamento', [$startDate, $endDate])
            ->sum('valor');
    }

    public function getImpostoSimples()
    {
        $receitaBruta = $this->getReceitaBruta();
        $aliquota = $this->getAliquotaSimples(Empresa::find($this->empresaId));

        return $receitaBruta * ($aliquota / 100);
    }

    public function getImpostoCalculado()
    {
        $empresa = Empresa::find($this->empresaId);

        if (! $empresa || ! $empresa->calcula_imposto_auto) {
            return 0;
        }

        return match ($this->regimeTributario) {
            'simples_nacional' => $this->getImpostoSimples(),
            'lucro_presumido' => $this->getImpostoLucroPresumido(),
            'lucro_real' => $this->getImpostoLucroReal(),
            default => 0,
        };
    }

    protected function getImpostoLucroPresumido()
    {
        $receitaLiquida = $this->getReceitaLiquida();
        $empresa = Empresa::find($this->empresaId);

        $percentualLucro = $empresa->percentual_lucro_presumido ?? 32;
        $lucroPresumido = $receitaLiquida * ($percentualLucro / 100);

        $irpj = $lucroPresumido * (($empresa->aliquota_irpj ?? 15) / 100);
        $csll = $lucroPresumido * (($empresa->aliquota_csll ?? 9) / 100);

        $pis = $receitaLiquida * (($empresa->aliquota_pis ?? 0.65) / 100);
        $cofins = $receitaLiquida * (($empresa->aliquota_cofins ?? 3) / 100);

        return $irpj + $csll + $pis + $cofins;
    }

    protected function getImpostoLucroReal()
    {
        return 0;
    }

    public function getDreDataProperty()
    {
        $receitaBruta = $this->getReceitaBruta();
        $deducoes = $this->getDeducoes();
        $receitaLiquida = $receitaBruta - $deducoes;
        $cmv = $this->getCmv();
        $lucroBruto = $receitaLiquida - $cmv;
        $despesas = $this->getTotalDespesas();
        $resultadoOperacional = $lucroBruto - $despesas;
        $impostos = $this->getImpostoCalculado();
        $lucroLiquido = $resultadoOperacional - $impostos;

        return [
            'receita_bruta' => $receitaBruta,
            'deducoes' => $deducoes,
            'receita_liquida' => $receitaLiquida,
            'cmv' => $cmv,
            'lucro_bruto' => $lucroBruto,
            'despesas' => $despesas,
            'resultado_operacional' => $resultadoOperacional,
            'impostos' => $impostos,
            'lucro_liquido' => $lucroLiquido,
            'margem_lucro' => $receitaLiquida > 0 ? ($lucroLiquido / $receitaLiquida) * 100 : 0,
        ];
    }

    public function getDespesasDetalhadasProperty()
    {
        return $this->getDespesasPorCategoria();
    }

    public function toggleDetails($categoria = null)
    {
        $this->detalheCategoria = $categoria;
        $this->showDetails = ! empty($categoria);
    }

    public function render()
    {
        return view('livewire.dre.index');
    }
}
