<?php

namespace App\Livewire\Tools;

use App\Services\Tools\BarcodeGeneratorService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Ean extends Component
{
    public $type = 'EAN13';

    public $base_number = '';

    public $generated_code = '';

    public $is_valid = false;

    public function generate()
    {
        $this->validate([
            'type' => 'required|in:EAN13,EAN8,UPC',
            'base_number' => 'required|string',
        ]);

        $service = new BarcodeGeneratorService;

        $baseNumber = preg_replace('/[^0-9]/', '', $this->base_number);

        $result = match ($this->type) {
            'EAN13' => $service->generateEan13($baseNumber),
            'EAN8' => $service->generateEan8($baseNumber),
            'UPC' => $service->generateUpc($baseNumber),
            default => null,
        };

        $this->generated_code = $result;
        $this->is_valid = $service->validateEan($result);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.tools.ean');
    }
}
