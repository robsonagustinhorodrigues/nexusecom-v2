<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductsImport implements ToCollection
{
    protected $empresaId;

    protected $grupoId;

    public function __construct($empresaId)
    {
        $empresa = \App\Models\Empresa::find($empresaId);
        $this->empresaId = $empresaId;
        $this->grupoId = $empresa?->grupo_id;
    }

    public function collection(Collection $rows)
    {
        $header = $rows->shift();

        $updated = 0;
        $created = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                if (empty($row[1]) && empty($row[0])) {
                    continue;
                }

                $sku = trim($row[1] ?? '');
                $nome = trim($row[2] ?? '');
                $tipo = trim($row[3] ?? 'simples');
                $ativo = strtolower(trim($row[4] ?? 'sim')) === 'sim';
                $precoVenda = floatval($row[5] ?? 0);
                $precoCusto = floatval($row[6] ?? 0);
                $estoque = intval($row[7] ?? 0);
                $ean = trim($row[8] ?? '');
                $ncm = trim($row[9] ?? '');
                $cest = trim($row[10] ?? '');
                $marca = trim($row[11] ?? '');
                $peso = floatval($row[12] ?? 0);
                $altura = floatval($row[13] ?? 0);
                $largura = floatval($row[14] ?? 0);
                $profundidade = floatval($row[15] ?? 0);
                $categoriaNome = trim($row[16] ?? '');
                $descricao = trim($row[17] ?? '');
                $skuVariacao = trim($row[18] ?? '');
                $skuVariacaoPreco = floatval($row[19] ?? 0);
                $skuVariacaoEstoque = intval($row[20] ?? 0);
                $skuVariacaoEan = trim($row[21] ?? '');

                if (empty($nome) && empty($sku)) {
                    $errors[] = 'Linha '.($index + 2).': SKU e Nome vazios';

                    continue;
                }

                if (empty($nome)) {
                    $nome = $sku;
                }

                $productId = ! empty($row[0]) ? $row[0] : null;

                if ($productId) {
                    $product = Product::where('id', $productId)
                        ->where('grupo_id', $this->grupoId)
                        ->first();
                } elseif (! empty($sku)) {
                    $product = Product::where('sku', $sku)
                        ->where('grupo_id', $this->grupoId)
                        ->first();
                } else {
                    $product = null;
                }

                if ($product) {
                    $product->update([
                        'nome' => $nome,
                        'tipo' => $tipo,
                        'ativo' => $ativo,
                        'ean' => $ean ?: $product->ean,
                        'ncm' => $ncm ?: $product->ncm,
                        'cest' => $cest ?: $product->cest,
                        'marca' => $marca ?: $product->marca,
                        'peso' => $peso ?: $product->peso,
                        'altura' => $altura ?: $product->altura,
                        'largura' => $largura ?: $product->largura,
                        'profundidade' => $profundidade ?: $product->profundidade,
                        'descricao' => $descricao ?: $product->descricao,
                    ]);

                    if (! empty($sku) && $product->sku !== $sku) {
                        $product->sku = $sku;
                        $product->save();
                    }

                    if (! empty($skuVariacao)) {
                        $skuModel = ProductSku::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'sku' => $skuVariacao,
                            ],
                            [
                                'preco_venda' => $skuVariacaoPreco ?: $precoVenda,
                                'preco_custo' => $precoCusto,
                                'estoque' => $skuVariacaoEstoque ?: $estoque,
                                'gtin' => $skuVariacaoEan ?: $ean,
                                'ncm' => $ncm,
                            ]
                        );
                    } else {
                        $existingSku = $product->skus()->where('is_principal', true)->first();
                        if ($existingSku) {
                            $existingSku->update([
                                'preco_venda' => $precoVenda ?: $existingSku->preco_venda,
                                'preco_custo' => $precoCusto ?: $existingSku->preco_custo,
                                'estoque' => $estoque ?: $existingSku->estoque,
                                'gtin' => $ean ?: $existingSku->gtin,
                                'ncm' => $ncm ?: $existingSku->ncm,
                            ]);
                        } else {
                            ProductSku::create([
                                'product_id' => $product->id,
                                'sku' => $sku,
                                'is_principal' => true,
                                'preco_venda' => $precoVenda,
                                'preco_custo' => $precoCusto,
                                'estoque' => $estoque,
                                'gtin' => $ean,
                                'ncm' => $ncm,
                            ]);
                        }
                    }

                    $updated++;
                } else {
                    $slug = Str::slug($nome);
                    $contador = 1;
                    while (Product::where('slug', $slug)->exists()) {
                        $slug = Str::slug($nome).'-'.$contador;
                        $contador++;
                    }

                    $product = Product::create([
                        'empresa_id' => $this->empresaId,
                        'grupo_id' => $this->grupoId,
                        'nome' => $nome,
                        'slug' => $slug,
                        'sku' => $sku,
                        'tipo' => $tipo,
                        'ativo' => $ativo,
                        'ean' => $ean,
                        'ncm' => $ncm,
                        'cest' => $cest,
                        'marca' => $marca,
                        'peso' => $peso,
                        'altura' => $altura,
                        'largura' => $largura,
                        'profundidade' => $profundidade,
                        'descricao' => $descricao,
                        'preco_venda' => $precoVenda,
                    ]);

                    if (! empty($skuVariacao)) {
                        ProductSku::create([
                            'product_id' => $product->id,
                            'sku' => $skuVariacao,
                            'is_principal' => true,
                            'preco_venda' => $skuVariacaoPreco ?: $precoVenda,
                            'preco_custo' => $precoCusto,
                            'estoque' => $skuVariacaoEstoque ?: $estoque,
                            'gtin' => $skuVariacaoEan ?: $ean,
                            'ncm' => $ncm,
                        ]);
                    } else {
                        ProductSku::create([
                            'product_id' => $product->id,
                            'sku' => $sku,
                            'is_principal' => true,
                            'preco_venda' => $precoVenda,
                            'preco_custo' => $precoCusto,
                            'estoque' => $estoque,
                            'gtin' => $ean,
                            'ncm' => $ncm,
                        ]);
                    }

                    $created++;
                }
            } catch (\Exception $e) {
                $errors[] = 'Linha '.($index + 2).': '.$e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Importação concluída! Criados: {$created}, Atualizados: {$updated}",
            'errors' => $errors,
        ]);
    }
}
