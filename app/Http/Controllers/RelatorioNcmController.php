<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioNcmController extends Controller
{
    public function index()
    {
        return view('fiscal.relatorio-ncm-alpine');
    }

    public function getData(Request $request)
    {
        $request->validate([
            'empresa_id' => 'required|integer',
            'data_inicial' => 'required|date',
            'data_final' => 'required|date|after_or_equal:data_inicial',
        ]);

        $empresaId = $request->empresa_id;
        $dataInicial = $request->data_inicial . ' 00:00:00';
        $dataFinal = $request->data_final . ' 23:59:59';

        // Base query for Nfe Emitidas
        $emitidasConsulta = DB::table('nfe_emitidas as e')
            ->join('nfe_items as i', 'e.id', '=', 'i.nfe_emitida_id')
            ->where('e.empresa_id', $empresaId)
            ->whereBetween('e.data_emissao', [$dataInicial, $dataFinal])
            ->select(
                'i.ncm',
                DB::raw("SUM(CASE WHEN e.status != 'cancelada' AND LEFT(i.cfop, 1) IN ('5', '6', '7') AND e.tipo_fiscal = 'saida' AND RIGHT(i.cfop, 3) NOT IN ('151', '152', '153', '154', '155', '156', '157', '551', '552', '553', '554', '555', '556', '557', '408', '409', '949', '901', '902', '903', '904', '905', '906', '907', '908', '909', '910', '911', '912', '913', '914', '915', '916', '917', '918', '919', '920', '921', '922') THEN i.valor_total ELSE 0 END) as valor_venda"),
                DB::raw("0 as valor_compra"),
                DB::raw("SUM(CASE WHEN e.status = 'cancelada' THEN i.valor_total ELSE 0 END) as valor_cancelada"),
                DB::raw("SUM(CASE WHEN e.status != 'cancelada' AND (e.devolvida = true OR (LEFT(i.cfop, 1) IN ('1', '2', '3') AND e.tipo_fiscal = 'entrada')) AND RIGHT(i.cfop, 3) NOT IN ('151', '152', '153', '154', '155', '156', '157', '551', '552', '553', '554', '555', '556', '557', '408', '409', '949', '901', '902', '903', '904', '905', '906', '907', '908', '909', '910', '911', '912', '913', '914', '915', '916', '917', '918', '919', '920', '921', '922') THEN i.valor_total ELSE 0 END) as valor_devolvida")
            )
            ->groupBy('i.ncm');

        // Base query for Nfe Recebidas
        $recebidasConsulta = DB::table('nfe_recebidas as r')
            ->join('nfe_items as i', 'r.id', '=', 'i.nfe_recebida_id')
            ->where('r.empresa_id', $empresaId)
            ->whereBetween('r.data_emissao', [$dataInicial, $dataFinal])
            ->select(
                DB::raw("i.ncm"),
                DB::raw("0 as valor_venda"),
                DB::raw("SUM(CASE WHEN r.status_nfe != 'cancelada' AND LEFT(i.cfop, 1) IN ('5', '6', '7') AND RIGHT(i.cfop, 3) NOT IN ('151', '152', '153', '154', '155', '156', '157', '551', '552', '553', '554', '555', '556', '557', '408', '409', '949', '901', '902', '903', '904', '905', '906', '907', '908', '909', '910', '911', '912', '913', '914', '915', '916', '917', '918', '919', '920', '921', '922') THEN i.valor_total ELSE 0 END) as valor_compra"),
                DB::raw("SUM(CASE WHEN r.status_nfe = 'cancelada' THEN i.valor_total ELSE 0 END) as valor_cancelada"),
                DB::raw("SUM(CASE WHEN r.status_nfe != 'cancelada' AND (r.devolucao = true OR LEFT(i.cfop, 1) IN ('1', '2', '3')) AND RIGHT(i.cfop, 3) NOT IN ('151', '152', '153', '154', '155', '156', '157', '551', '552', '553', '554', '555', '556', '557', '408', '409', '949', '901', '902', '903', '904', '905', '906', '907', '908', '909', '910', '911', '912', '913', '914', '915', '916', '917', '918', '919', '920', '921', '922') THEN i.valor_total ELSE 0 END) as valor_devolvida")
            )
            ->groupBy('i.ncm');

        // Combine using UNION ALL and aggregate again to group by NCM
        $resultados = DB::table(DB::raw("({$emitidasConsulta->toSql()} UNION ALL {$recebidasConsulta->toSql()}) as relatorio"))
            ->mergeBindings($emitidasConsulta)
            ->mergeBindings($recebidasConsulta)
            ->select(
                'ncm',
                DB::raw('SUM(valor_venda) as total_venda'),
                DB::raw('SUM(valor_compra) as total_compra'),
                DB::raw('SUM(valor_cancelada) as total_cancelada'),
                DB::raw('SUM(valor_devolvida) as total_devolvida')
            )
            ->whereNotNull('ncm')
            ->groupBy('ncm')
            ->orderBy('total_venda', 'desc')
            ->get();

        return response()->json([
            'data' => $resultados,
            'summary' => [
                'venda_total' => $resultados->sum('total_venda'),
                'compra_total' => $resultados->sum('total_compra'),
                'cancelada_total' => $resultados->sum('total_cancelada'),
                'devolvida_total' => $resultados->sum('total_devolvida'),
                'liquido_total' => $resultados->sum('total_venda') - $resultados->sum('total_devolvida'),
            ]
        ]);
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'empresa_id' => 'required|integer',
            'data_inicial' => 'required|date',
            'data_final' => 'required|date|after_or_equal:data_inicial',
        ]);

        $empresaId = $request->empresa_id;
        $dataInicial = $request->data_inicial . ' 00:00:00';
        $dataFinal = $request->data_final . ' 23:59:59';

        $empresa = Empresa::findOrFail($empresaId);

        // Base query for Nfe Emitidas
        $emitidasConsulta = DB::table('nfe_emitidas as e')
            ->join('nfe_items as i', 'e.id', '=', 'i.nfe_emitida_id')
            ->where('e.empresa_id', $empresaId)
            ->whereBetween('e.data_emissao', [$dataInicial, $dataFinal])
            ->select(
                'i.ncm',
                DB::raw("SUM(CASE WHEN e.status != 'cancelada' AND LEFT(i.cfop, 1) IN ('5', '6', '7') AND e.tipo_fiscal = 'saida' AND RIGHT(i.cfop, 3) NOT IN ('151', '152', '153', '154', '155', '156', '157', '551', '552', '553', '554', '555', '556', '557', '408', '409', '949', '901', '902', '903', '904', '905', '906', '907', '908', '909', '910', '911', '912', '913', '914', '915', '916', '917', '918', '919', '920', '921', '922') THEN i.valor_total ELSE 0 END) as valor_venda"),
                DB::raw("0 as valor_compra"),
                DB::raw("SUM(CASE WHEN e.status = 'cancelada' THEN i.valor_total ELSE 0 END) as valor_cancelada"),
                DB::raw("SUM(CASE WHEN e.status != 'cancelada' AND (e.devolvida = true OR (LEFT(i.cfop, 1) IN ('1', '2', '3') AND e.tipo_fiscal = 'entrada')) AND RIGHT(i.cfop, 3) NOT IN ('151', '152', '153', '154', '155', '156', '157', '551', '552', '553', '554', '555', '556', '557', '408', '409', '949', '901', '902', '903', '904', '905', '906', '907', '908', '909', '910', '911', '912', '913', '914', '915', '916', '917', '918', '919', '920', '921', '922') THEN i.valor_total ELSE 0 END) as valor_devolvida")
            )
            ->groupBy('i.ncm');

        // Base query for Nfe Recebidas
        $recebidasConsulta = DB::table('nfe_recebidas as r')
            ->join('nfe_items as i', 'r.id', '=', 'i.nfe_recebida_id')
            ->where('r.empresa_id', $empresaId)
            ->whereBetween('r.data_emissao', [$dataInicial, $dataFinal])
            ->select(
                DB::raw("i.ncm"),
                DB::raw("0 as valor_venda"),
                DB::raw("SUM(CASE WHEN r.status_nfe != 'cancelada' AND LEFT(i.cfop, 1) IN ('5', '6', '7') AND RIGHT(i.cfop, 3) NOT IN ('151', '152', '153', '154', '155', '156', '157', '551', '552', '553', '554', '555', '556', '557', '408', '409', '949', '901', '902', '903', '904', '905', '906', '907', '908', '909', '910', '911', '912', '913', '914', '915', '916', '917', '918', '919', '920', '921', '922') THEN i.valor_total ELSE 0 END) as valor_compra"),
                DB::raw("SUM(CASE WHEN r.status_nfe = 'cancelada' THEN i.valor_total ELSE 0 END) as valor_cancelada"),
                DB::raw("SUM(CASE WHEN r.status_nfe != 'cancelada' AND (r.devolucao = true OR LEFT(i.cfop, 1) IN ('1', '2', '3')) AND RIGHT(i.cfop, 3) NOT IN ('151', '152', '153', '154', '155', '156', '157', '551', '552', '553', '554', '555', '556', '557', '408', '409', '949', '901', '902', '903', '904', '905', '906', '907', '908', '909', '910', '911', '912', '913', '914', '915', '916', '917', '918', '919', '920', '921', '922') THEN i.valor_total ELSE 0 END) as valor_devolvida")
            )
            ->groupBy('i.ncm');

        $resultados = DB::table(DB::raw("({$emitidasConsulta->toSql()} UNION ALL {$recebidasConsulta->toSql()}) as relatorio"))
            ->mergeBindings($emitidasConsulta)
            ->mergeBindings($recebidasConsulta)
            ->select(
                'ncm',
                DB::raw('SUM(valor_venda) as total_venda'),
                DB::raw('SUM(valor_compra) as total_compra'),
                DB::raw('SUM(valor_cancelada) as total_cancelada'),
                DB::raw('SUM(valor_devolvida) as total_devolvida')
            )
            ->whereNotNull('ncm')
            ->groupBy('ncm')
            ->orderBy('total_venda', 'desc')
            ->get();

        $summary = [
            'venda_total' => $resultados->sum('total_venda'),
            'compra_total' => $resultados->sum('total_compra'),
            'cancelada_total' => $resultados->sum('total_cancelada'),
            'devolvida_total' => $resultados->sum('total_devolvida'),
            'liquido_total' => $resultados->sum('total_venda') - $resultados->sum('total_devolvida'),
        ];

        return view('fiscal.relatorio-ncm-pdf', compact('resultados', 'summary', 'empresa', 'request'));
    }
}
