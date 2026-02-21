<?php

namespace App\Livewire\Tools;

use App\Services\Tools\ZplConverterService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Zpl extends Component
{
    public $zpl_data = '';

    public $dpmm = 12;

    public $width_mm = 100;

    public $height_mm = 50;

    public $error = '';

    public function convert()
    {
        $this->validate([
            'zpl_data' => 'required|string',
            'dpmm' => 'required|in:8,12,24',
            'width_mm' => 'required|numeric|min:10',
            'height_mm' => 'required|numeric|min:10',
        ]);

        try {
            $service = new ZplConverterService;
            $pdfContent = $service->convertZplToPdf(
                $this->zpl_data,
                $this->dpmm,
                $this->width_mm,
                $this->height_mm
            );

            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            }, 'etiquetas.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao converter ZPL: '.$e->getMessage());
            $this->error = 'Falha ao converter ZPL: '.$e->getMessage();
        }
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.tools.zpl');
    }
}
