<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    protected $empresaId;

    protected $grupoId;

    public function __construct($empresaId)
    {
        $empresa = \App\Models\Empresa::find($empresaId);
        $this->empresaId = $empresaId;
        $this->grupoId = $empresa?->grupo_id;
    }

    public function headings(): array
    {
        return [
            'ID',
            'SKU',
            'Nome',
            'Tipo',
            'Ativo',
            'Preço Venda',
            'Preço Custo',
            'Custo Adicional',
            'Estoque',
            'EAN',
            'NCM',
            'CEST',
            'Marca',
            'Peso (g)',
            'Altura (cm)',
            'Largura (cm)',
            'Profundidade (cm)',
            'Categoria',
            'Descrição',
            'SKU Variação',
            'SKU Variação Preço',
            'SKU Variação Custo',
            'SKU Variação Estoque',
            'SKU Variação EAN',
        ];
    }

    public function collection(): Collection
    {
        $products = Product::where('grupo_id', $this->grupoId)
            ->with(['categoria', 'skus'])
            ->orderBy('nome')
            ->get();

        $rows = [];

        foreach ($products as $product) {
            $skus = $product->skus;

            if ($skus->isEmpty()) {
                $rows[] = $this->buildRow($product, null);
            } else {
                foreach ($skus as $sku) {
                    $rows[] = $this->buildRow($product, $sku);
                }
            }
        }

        return new Collection($rows);
    }

    protected function buildRow($product, $sku): array
    {
        return [
            $product->id,
            $product->sku,
            $product->nome,
            $product->tipo,
            $product->ativo ? 'Sim' : 'Não',
            $sku ? $sku->preco_venda : $product->preco_venda,
            $sku ? $sku->preco_custo : $product->preco_custo,
            $product->custo_adicional ?? 0,
            $sku ? $sku->estoque : $product->estoque,
            $sku ? $sku->gtin : $product->ean,
            $sku ? $sku->ncm : $product->ncm,
            $product->cest,
            $product->marca,
            $sku ? $sku->peso_g : ($product->peso * 1000),
            $product->altura,
            $product->largura,
            $product->profundidade,
            $product->categoria?->nome,
            strip_tags($product->descricao),
            $sku?->sku ?? '',
            $sku?->preco_venda ?? '',
            $sku?->preco_custo ?? '',
            $sku?->estoque ?? '',
            $sku?->gtin ?? '',
        ];
    }
}
