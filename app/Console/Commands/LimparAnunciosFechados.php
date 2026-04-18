<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAnuncio;
use Illuminate\Console\Command;

class LimparAnunciosFechados extends Command
{
    protected $signature = 'anuncios:limpar-fechados';

    protected $description = 'Remove anúncios fechados há mais de 30 dias do Mercado Livre';

    public function handle()
    {
        $this->info('Iniciando limpeza de anúncios fechados...');

        $anunciosFechados = MarketplaceAnuncio::whereNotNull('closed_at')
            ->where('closed_at', '<', now()->subDays(30))
            ->get();

        $quantidade = $anunciosFechados->count();

        if ($quantidade === 0) {
            $this->info('Nenhum anúncio fechado há mais de 30 dias para remover.');

            return 0;
        }

        foreach ($anunciosFechados as $anuncio) {
            $this->line("Removendo: {$anuncio->titulo} (ID: {$anuncio->id})");
            $anuncio->delete();
        }

        $this->info("{$quantidade} anúncio(s) removido(s) com sucesso!");

        return 0;
    }
}
