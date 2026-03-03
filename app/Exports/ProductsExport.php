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
            'SKU 1',
            'SKU 2',
            'SKU 3',
            'SKU 4',
            'SKU 5',
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
            $rows[] = $this->buildRow($product);
        }

        return new Collection($rows);
    }

    protected function buildRow($product): array
    {
        $skus = $product->skus;

        $sku1 = $skus->get(0)?->sku ?? '';
        $sku2 = $skus->get(1)?->sku ?? '';
        $sku3 = $skus->get(2)?->sku ?? '';
        $sku4 = $skus->get(3)?->sku ?? '';
        $sku5 = $skus->get(4)?->sku ?? '';

        return [
            $product->id,
            $product->sku,
            $product->nome,
            $product->tipo,
            $product->ativo ? 'Sim' : 'Não',
            $product->preco_venda,
            $product->preco_custo,
            $product->custo_adicional ?? 0,
            $product->estoque ?? 0,
            $product->ean,
            $product->ncm,
            $product->cest,
            $product->marca,
            $product->peso * 1000,
            $product->altura,
            $product->largura,
            $product->profundidade,
            $product->categoria?->nome,
            strip_tags($product->descricao ?? ''),
            '',
            $sku1,
            $sku2,
            $sku3,
            $sku4,
            $sku5,
        ];
    }
}
