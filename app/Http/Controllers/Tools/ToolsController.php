<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Services\Tools\BarcodeGeneratorService;
use App\Services\Tools\ZplConverterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ToolsController extends Controller
{
    public function index()
    {
        return view('livewire.tools.index');
    }

    public function zplIndex()
    {
        return view('livewire.tools.zpl');
    }

    public function zplProcess(Request $request)
    {
        $request->validate([
            'zpl_data' => 'required|string',
            'dpmm' => 'required|in:8,12,24',
            'width_mm' => 'required|numeric|min:10',
            'height_mm' => 'required|numeric|min:10',
        ]);

        try {
            $service = new ZplConverterService;
            $pdfContent = $service->convertZplToPdf(
                $request->zpl_data,
                $request->dpmm,
                $request->width_mm,
                $request->height_mm
            );

            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            }, 'etiquetas.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao converter ZPL: '.$e->getMessage());

            return redirect()->route('tools.zpl')->with('error', 'Falha ao converter ZPL: '.$e->getMessage());
        }
    }

    public function eanIndex()
    {
        return view('livewire.tools.ean');
    }

    public function eanGenerate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:EAN13,EAN8,UPC',
            'base_number' => 'required|string',
        ]);

        $service = new BarcodeGeneratorService;

        $baseNumber = preg_replace('/[^0-9]/', '', $request->base_number);

        $result = match ($request->type) {
            'EAN13' => $service->generateEan13($baseNumber),
            'EAN8' => $service->generateEan8($baseNumber),
            'UPC' => $service->generateUpc($baseNumber),
            default => null,
        };

        $isValid = $service->validateEan($result);

        return redirect()->route('tools.ean')->with([
            'generated_code' => $result,
            'is_valid' => $isValid,
            'type' => $request->type,
        ]);
    }
}
