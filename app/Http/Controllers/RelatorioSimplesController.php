<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empresa;
use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RelatorioSimplesController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $empresas = Empresa::where('grupo_id', $user->grupo_id)
            ->orderBy('nome')
            ->get();

        $meses = $this->getMesesDisponiveis();

        return view('livewire.fiscal.relatorios.simples', [
            'empresas' => $empresas,
            'meses' => $meses,
            'resultados' => [],
        ]);
    }

    public function gerar(Request $request)
    {
        $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'ano_mes' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $user = Auth::user();
        $empresas = Empresa::where('grupo_id', $user->grupo_id)
            ->orderBy('nome')
            ->get();

        $meses = $this->getMesesDisponiveis();
        $resultados = $this->calcularImpostoSimples($request->empresa_id, $request->ano_mes);

        return view('livewire.fiscal.relatorios.simples', [
            'empresas' => $empresas,
            'meses' => $meses,
            'empresa_id' => $request->empresa_id,
            'ano_mes' => $request->ano_mes,
            'resultados' => $resultados,
        ]);
    }

    private function getMesesDisponiveis()
    {
        $meses = [];
        $dataAtual = Carbon::now();

        for ($i = 0; $i <= 12; $i++) {
            $data = $dataAtual->copy()->subMonths($i);
            $meses[] = [
                'value' => $data->format('Y-m'),
                'text' => $data->format('m/Y'),
            ];
        }
        return $meses;
    }

    private function calcularImpostoSimples($empresaId, $anoMesSelecionado)
    {
        $inicioRpa = Carbon::createFromFormat('Y-m', $anoMesSelecionado)->startOfMonth();
        $fimRpa = Carbon::createFromFormat('Y-m', $anoMesSelecionado)->endOfMonth();

        $fimRbt12 = $inicioRpa->copy()->subDay();
        $inicioRbt12 = $fimRbt12->copy()->subYear()->addDay();

        $rpaVendas = NfeEmitida::where('empresa_id', $empresaId)
            ->whereBetween('data_emissao', [$inicioRpa, $fimRpa])
            ->where('status', 'autorizada')
            ->sum('valor_total');

        $rpaDevolucoes = NfeEmitida::where('empresa_id', $empresaId)
            ->whereBetween('data_emissao', [$inicioRpa, $fimRpa])
            ->where('status', 'autorizada')
            ->where('status_nfe', 'devolucao')
            ->sum('valor_total');

        $rbt12 = NfeEmitida::where('empresa_id', $empresaId)
            ->whereBetween('data_emissao', [$inicioRbt12, $fimRbt12])
            ->where('status', 'autorizada')
            ->sum('valor_total');

        $comprasMes = NfeRecebida::where('empresa_id', $empresaId)
            ->whereBetween('data_emissao', [$inicioRpa, $fimRpa])
            ->where('status_nfe', 'aprovada')
            ->sum('valor_total');

        $rpa = $rpaVendas - $rpaDevolucoes;

        $anexo = $this->getAliquotaSimples($rbt12);
        $aliquota = $anexo['aliquota'];
        $parcelaDeduzir = $anexo['parcela_deduzir'];

        $aliquotaEfetiva = 0;
        if ($rbt12 > 0) {
            $aliquotaEfetiva = (($rbt12 * ($aliquota / 100)) - $parcelaDeduzir) / $rbt12;
        }

        $impostoDevido = $rpa * $aliquotaEfetiva;

        return [
            'rpa_vendas' => $rpaVendas,
            'rpa_devolucoes' => $rpaDevolucoes,
            'rpa' => $rpa,
            'rpa_isento_vendas' => 0,
            'rpa_isento_devolucoes' => 0,
            'rpa_isento' => 0,
            'compras_mes' => $comprasMes,
            'rbt12' => $rbt12,
            'aliquota_efetiva' => $aliquotaEfetiva,
            'imposto_devido' => $impostoDevido,
            'faixa' => $anexo['faixa'],
            'aliquota_nominal' => $aliquota,
            'parcela_deduzir' => $parcelaDeduzir
        ];
    }

    private function getAliquotaSimples($rbt12)
    {
        if ($rbt12 <= 180000) {
            return ['faixa' => 1, 'aliquota' => 4.00, 'parcela_deduzir' => 0];
        } elseif ($rbt12 <= 360000) {
            return ['faixa' => 2, 'aliquota' => 7.30, 'parcela_deduzir' => 5940];
        } elseif ($rbt12 <= 720000) {
            return ['faixa' => 3, 'aliquota' => 9.50, 'parcela_deduzir' => 13860];
        } elseif ($rbt12 <= 1800000) {
            return ['faixa' => 4, 'aliquota' => 10.70, 'parcela_deduzir' => 22500];
        } elseif ($rbt12 <= 3600000) {
            return ['faixa' => 5, 'aliquota' => 14.30, 'parcela_deduzir' => 87300];
        } elseif ($rbt12 <= 4800000) {
            return ['faixa' => 6, 'aliquota' => 19.00, 'parcela_deduzir' => 378000];
        } else {
            return ['faixa' => 6, 'aliquota' => 19.00, 'parcela_deduzir' => 378000];
        }
    }
}
