<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAnuncio;
use App\Models\Product;
use Illuminate\Console\Command;

class FixImportedProductImages extends Command
{
    protected $signature = 'products:fix-imported-images';

    protected $description = 'Corrige imagens de produtos importados de anúncios do marketplace';

    public function handle()
    {
        $this->info('Iniciando correção de imagens de produtos importados...');

        $anuncios = MarketplaceAnuncio::whereNotNull('json_data')
            ->where(function ($query) {
                $query->whereNotNull('produto_id')
                    ->orWhereNotNull('product_sku_id');
            })
            ->with('productSku.product')
            ->get();

        if ($anuncios->isEmpty()) {
            $this->info('Nenhum anúncio com produto importado encontrado.');

            return 0;
        }

        $this->info("Encontrados {$anuncios->count()} anúncios com produtos importados.");

        $corrigidos = 0;
        $ignorados = 0;

        foreach ($anuncios as $anuncio) {
            $product = null;

            if ($anuncio->produto_id) {
                $product = Product::find($anuncio->produto_id);
            } elseif ($anuncio->product_sku_id && $anuncio->productSku) {
                $product = $anuncio->productSku->product;
            }

            if (! $product) {
                $this->warn("Produto não encontrado para anúncio {$anuncio->id}");
                $ignorados++;

                continue;
            }

            $jsonData = $anuncio->json_data;
            $imagens = $jsonData['pictures'] ?? [];
            $thumbnail = $jsonData['thumbnail'] ?? null;

            $fotoPrincipal = null;
            $fotosGaleria = [];

            if (! empty($imagens)) {
                foreach ($imagens as $index => $pic) {
                    $url = $pic['url'] ?? $pic['secure_url'] ?? null;
                    if ($url) {
                        if ($index === 0) {
                            $fotoPrincipal = $url;
                        } else {
                            $fotosGaleria[] = $url;
                        }
                    }
                }
            }

            if (! $fotoPrincipal && $thumbnail) {
                $fotoPrincipal = $thumbnail;
            }

            if (! $fotoPrincipal) {
                $this->warn("Nenhuma imagem encontrada para anúncio {$anuncio->id}");
                $ignorados++;

                continue;
            }

            $product->update([
                'imagem' => $fotoPrincipal,
                'foto_principal' => $fotoPrincipal,
                'fotos_galeria' => $fotosGaleria,
            ]);

            $corrigidos++;
            $this->line("Corrigido: {$product->nome} (".(count($fotosGaleria) + 1).' imagens)');
        }

        $this->info("Correção concluída: {$corrigidos} produtos corrigidos, {$ignorados} ignorados.");

        return 0;
    }
}
