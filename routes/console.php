<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Models\Empresa;
use App\Jobs\BuscarNfeJob;
use Illuminate\Support\Facades\Schedule;

// Buscar NF-e - a cada 6 horas
Schedule::call(function () {
    Empresa::where('ativo', true)
        ->whereNotNull('certificado_a1_path')
        ->each(fn($empresa) => BuscarNfeJob::dispatch($empresa));
})->everySixHours();

// Repricer - a cada 3 horas para empresas habilitadas
Schedule::call(function () {
    Empresa::where('ativo', true)
        ->where('repricer_enabled', true)
        ->each(function ($empresa) {
            // Verifica se passou o intervalo configurado
            if (!$empresa->repricer_ultima_execucao || 
                $empresa->repricer_ultima_execucao->addHours($empresa->repricer_intervalo_horas ?? 3)->isPast()) {
                
                \Illuminate\Support\Facades\Artisan::call('repricer:run', ['empresaId' => $empresa->id]);
                
                // Atualiza última execução
                $empresa->update(['repricer_ultima_execucao' => now()]);
            }
        });
})->everyThreeHours();
