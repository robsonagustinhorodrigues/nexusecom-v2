<?php

namespace App\Console\Commands;

use App\Models\Integracao;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncBlingProdutos extends Command
{
    protected $signature = 'bling:sync-produtos {empresaId? : ID da empresa} {--limit=100 : Limite de produtos}';

    protected $description = 'Sincroniza produtos do Bling para o NexusEcom';

    public function handle()
    {
        $empresaId = $this->argument('empresaId');
        
        if (!$empresaId) {
            $this->error('Informe o ID da empresa: php artisan bling:sync-produtos {empresaId}');
            return 1;
        }

        $this->info("Sincronizando produtos do Bling para empresa {$empresaId}...");

        $bling = Integracao::where('empresa_id', $empresaId)
            ->where('marketplace', 'bling')
            ->where('ativo', true)
            ->first();

        if (!$bling) {
            $this->error('Bling não configurado para esta empresa');
            return 1;
        }

        $limit = (int) $this->option('limit');
        $page = 1;
        $imported = 0;
        $updated = 0;

        do {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $bling->access_token,
            ])->get("https://api.bling.com.br/Api/v3/produtos", [
                'limit' => $limit,
                'page' => $page,
            ]);

            if (!$response->successful()) {
                $this->error('Erro na API: ' . $response->body());
                return 1;
            }

            $data = $response->json();
            $produtos = $data['data'] ?? [];

            if (empty($produtos)) {
                break;
            }

            foreach ($produtos as $produtoBling) {
                $codigo = $produtoBling['codigo'] ?? null;
                $nome = $produtoBling['nome'] ?? 'Sem nome';
                $preco = (float) ($produtoBling['preco'] ?? 0);
                $precoCusto = (float) ($produtoBling['precoCusto'] ?? 0);
                $estoque = (int) ($produtoBling['estoqueAtual'] ?? 0);
                $gtin = $produtoBling['gtin'] ?? null;
                $ncm = $produtoBling['ncm'] ?? null;
                $peso = $produtoBling['peso'] ?? null;
                $altura = $produtoBling['altura'] ?? null;
                $largura = $produtoBling['largura'] ?? null;
                $profundidade = $produtoBling['profundidade'] ?? null;
                $unidade = $produtoBling['unidade'] ?? 'un';
                $tipo = $produtoBling['tipo'] ?? 'P';
                $situacao = $produtoBling['situacao'] ?? 'ATIVO';
                $descricao = $produtoBling['descricao'] ?? '';

                if (!$codigo) {
                    continue;
                }

                $existing = Product::where('empresa_id', $empresaId)
                    ->where('sku', $codigo)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'nome' => $nome,
                        'preco_venda' => $preco,
                        'preco_custo' => $precoCusto,
                        'estoque' => $estoque,
                        'gtin' => $gtin,
                        'ncm' => $ncm,
                        'peso' => $peso,
                        'altura' => $altura,
                        'largura' => $largura,
                        'profundidade' => $profundidade,
                        'unidade' => $unidade,
                        'tipo' => $tipo,
                        'descricao' => $descricao,
                        'origem' => 'bling',
                        'bling_id' => $produtoBling['id'],
                        'json_data' => $produtoBling,
                    ]);
                    $updated++;
                } else {
                    Product::create([
                        'empresa_id' => $empresaId,
                        'nome' => $nome,
                        'sku' => $codigo,
                        'preco_venda' => $preco,
                        'preco_custo' => $precoCusto,
                        'estoque' => $estoque,
                        'gtin' => $gtin,
                        'ncm' => $ncm,
                        'peso' => $peso,
                        'altura' => $altura,
                        'largura' => $largura,
                        'profundidade' => $profundidade,
                        'unidade' => $unidade,
                        'tipo' => $tipo,
                        'descricao' => $descricao,
                        'origem' => 'bling',
                        'bling_id' => $produtoBling['id'],
                        'json_data' => $produtoBling,
                    ]);
                    $imported++;
                }
            }

            $this->info("Página {$page}: " . count($produtos) . " produtos processados");

            $page++;
            
        } while (count($produtos) >= $limit);

        $this->info("Importados: {$imported} | Atualizados: {$updated}");

        return 0;
    }
}
