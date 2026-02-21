<?php

namespace App\Http\Controllers;

use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use App\Services\DanfeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NfeController extends Controller
{
    public function downloadZip(Request $request)
    {
        $path = $request->query('path');

        if (! $path || ! file_exists($path)) {
            abort(404, 'Arquivo não encontrado ou expirado.');
        }

        if (! str_contains($path, storage_path('app/temp'))) {
            abort(403, 'Acesso negado.');
        }

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function downloadXml(int $id, string $tipo = 'recebida')
    {
        $model = $tipo === 'emitida' ? NfeEmitida::class : NfeRecebida::class;
        $nfe = $model::findOrFail($id);

        if (! $nfe->xml_path || ! Storage::exists($nfe->xml_path)) {
            abort(404, 'XML não encontrado.');
        }

        return Storage::download($nfe->xml_path, 'NFe_'.$nfe->chave.'.xml');
    }

    public function danfe(int $id, string $tipo = 'recebida')
    {
        $model = $tipo === 'emitida' ? NfeEmitida::class : NfeRecebida::class;
        $nfe = $model::findOrFail($id);

        if (! $nfe->xml_path || ! Storage::exists($nfe->xml_path)) {
            abort(404, 'XML não encontrado.');
        }

        $xml = Storage::get($nfe->xml_path);
        $empresa = $nfe->empresa;

        $danfeService = new DanfeService;
        $pdf = $danfeService->gerarDanfeA4($xml, $empresa->toArray());

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="DANFE_'.$nfe->chave.'.pdf"',
        ]);
    }

    public function danfeSimplificada(int $id, string $tipo = 'recebida')
    {
        $model = $tipo === 'emitida' ? NfeEmitida::class : NfeRecebida::class;
        $nfe = $model::findOrFail($id);

        if (! $nfe->xml_path || ! Storage::exists($nfe->xml_path)) {
            abort(404, 'XML não encontrado.');
        }

        $xml = Storage::get($nfe->xml_path);
        $empresa = $nfe->empresa;

        $danfeService = new DanfeService;
        $html = $danfeService->gerarDanfeSimplificado($xml, $empresa->toArray());

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => 'inline; filename="DANFE_Simplificada_'.$nfe->chave.'.html"',
        ]);
    }

    public function etiqueta(int $id, string $tipo = 'recebida')
    {
        $model = $tipo === 'emitida' ? NfeEmitida::class : NfeRecebida::class;
        $nfe = $model::findOrFail($id);

        if (! $nfe->xml_path || ! Storage::exists($nfe->xml_path)) {
            abort(404, 'XML não encontrado.');
        }

        $xml = Storage::get($nfe->xml_path);
        $empresa = $nfe->empresa;

        $danfeService = new DanfeService;
        $html = $danfeService->gerarEtiqueta($xml, $empresa->toArray());

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => 'inline; filename="Etiqueta_NFe_'.$nfe->chave.'.html"',
        ]);
    }

    public function downloadLog(Request $request)
    {
        $path = $request->query('path');
        $filename = $request->query('filename', 'importacao.log');

        if (! $path || ! Storage::exists($path)) {
            abort(404, 'Log não encontrado ou expirado.');
        }

        $content = Storage::get($path);
        Storage::delete($path);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
