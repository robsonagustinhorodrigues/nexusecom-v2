<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empresa;
use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RelatorioFaturamentoController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $empresas = Empresa::where('grupo_id', $user->grupo_id)
            ->orderBy('nome')
            ->get();

        return view('livewire.fiscal.relatorios.faturamento', [
            'empresas' => $empresas,
            'resumoMensal' => collect([]),
        ]);
    }

    public function gerar(Request $request)
    {
        $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
        ]);

        $user = Auth::user();
        $empresas = Empresa::where('grupo_id', $user->grupo_id)
            ->orderBy('nome')
            ->get();

        $empresaId = $request->empresa_id;
        $resumoMensal = $this->getResumoMensal($empresaId);

        return view('livewire.fiscal.relatorios.faturamento', [
            'empresas' => $empresas,
            'empresa_id' => $empresaId,
            'resumoMensal' => $resumoMensal,
        ]);
    }

    private function getResumoMensal($empresaId)
    {
        $driver = DB::connection()->getDriverName();
        
        $sqlMes = match ($driver) {
            'pgsql' => "TO_CHAR(data_emissao, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', data_emissao)",
            default => "DATE_FORMAT(data_emissao, '%Y-%m')"
        };

        $queryEmitidas = NfeEmitida::query()
            ->select(DB::raw("{$sqlMes} as mes"))
            ->selectRaw("SUM(CASE WHEN status = 'autorizada' THEN valor_total ELSE 0 END) as total_saida")
            ->selectRaw("COUNT(CASE WHEN status = 'autorizada' THEN 1 END) as quantidade_saida")
            ->selectRaw("SUM(CASE WHEN status_nfe = 'cancelada' THEN valor_total ELSE 0 END) as total_cancelada")
            ->selectRaw("COUNT(CASE WHEN status_nfe = 'cancelada' THEN 1 END) as quantidade_cancelada")
            ->where('empresa_id', $empresaId)
            ->where('data_emissao', '>=', Carbon::now()->subMonths(12)->startOfMonth())
            ->groupBy(DB::raw('mes'))
            ->orderBy(DB::raw('mes'), 'desc')
            ->get();

        $queryRecebidas = NfeRecebida::query()
            ->select(DB::raw("{$sqlMes} as mes"))
            ->selectRaw("SUM(CASE WHEN status_nfe = 'aprovada' THEN valor_total ELSE 0 END) as total_compra")
            ->selectRaw("COUNT(CASE WHEN status_nfe = 'aprovada' THEN 1 END) as quantidade_compra")
            ->where('empresa_id', $empresaId)
            ->where('data_emissao', '>=', Carbon::now()->subMonths(12)->startOfMonth())
            ->groupBy(DB::raw('mes'))
            ->get();

        $recebidasAgrupadas = $queryRecebidas->keyBy('mes');

        return $queryEmitidas->map(function ($item) use ($recebidasAgrupadas) {
            $compra = $recebidasAgrupadas->get($item->mes);
            
            $item->total_compra = $compra ? $compra->total_compra : 0;
            $item->quantidade_compra = $compra ? $compra->quantidade_compra : 0;
            $item->faturamento_liquido = $item->total_saida;
            $item->porcentagem_devolucao = 0;
            
            $dataObj = Carbon::createFromFormat('Y-m', $item->mes);
            $item->mes_formatado = ucfirst($dataObj->translatedFormat('M/Y'));
            
            return $item;
        });
    }
}
