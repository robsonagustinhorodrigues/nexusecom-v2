<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deposito;
use App\Models\Empresa;
use App\Models\EstoqueSaldo;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->get('empresa_id', session('empresa_id', 6));
        $empresa = Empresa::find($empresaId);
        $grupoId = $empresa?->grupo_id;

        $query = EstoqueSaldo::with(['sku', 'sku.product', 'deposito'])
            ->whereHas('deposito', function ($q) use ($empresaId, $grupoId, $request) {
                $q->where('ativo', true);
                $q->where(function ($q2) use ($empresaId, $grupoId) {
                    $q2->where('empresa_id', $empresaId)
                        ->orWhere('compartilhado', true)
                        ->orWhere('grupo_id', $grupoId);
                });

                if ($request->deposito_id) {
                    $q->where('id', $request->deposito_id);
                }
            });

        // Search: SKU, EAN, descrição/nome do produto
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('sku.product', function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('ean', 'like', "%{$search}%");
            })
                ->orWhereHas('sku', function ($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                        ->orWhere('ean', 'like', "%{$search}%");
                });
        }

        // Filter only active/inactive products
        if ($request->ativos === '1') {
            $query->whereHas('sku.product', function ($q) {
                $q->where('ativo', true);
            });
        } elseif ($request->ativos === '0') {
            $query->whereHas('sku.product', function ($q) {
                $q->where('ativo', false);
            });
        }

        if ($request->status === 'zerado') {
            $query->where('saldo', 0);
        } elseif ($request->status === 'baixo') {
            $query->whereRaw('saldo <= saldo_minimo AND saldo > 0');
        } elseif ($request->status === 'normal') {
            $query->whereRaw('saldo > saldo_minimo');
        }

        $estoque = $query->orderBy('updated_at', 'desc')->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => $estoque->map(function ($item) {
                $produto = $item->sku?->product;
                $valorUnitario = floatval($produto?->preco_custo ?? 0);
                $valorTotal = floatval($item->saldo) * $valorUnitario;

                return [
                    'id' => $item->id,
                    'produto_id' => $produto?->id,
                    'produto_nome' => $produto?->nome ?? 'Produto não encontrado',
                    'produto_codigo' => $produto?->sku ?? '',
                    'sku_id' => $item->product_sku_id,
                    'sku_codigo' => $item->sku?->sku ?? '',
                    'ean' => $item->sku?->ean ?? $produto?->ean ?? null,
                    'deposito_id' => $item->deposito_id,
                    'deposito_nome' => $item->deposito?->nome ?? 'Depósito não encontrado',
                    'saldo' => intval($item->saldo),
                    'saldo_minimo' => intval($item->saldo_minimo ?? 0),
                    'valor_unitario' => $valorUnitario,
                    'valor_total' => $valorTotal,
                    'updated_at' => $item->updated_at,
                ];
            }),
            'current_page' => $estoque->currentPage(),
            'last_page' => $estoque->lastPage(),
            'total' => $estoque->total(),
            'from' => $estoque->firstItem(),
            'to' => $estoque->lastItem(),
        ]);
    }

    public function depositos()
    {
        $empresaId = session('empresa_id', 6);
        $empresa = Empresa::find($empresaId);
        $grupoId = $empresa?->grupo_id;

        $depositos = Deposito::with('empresa')
            ->where('ativo', true)
            ->where(function ($q) use ($empresaId, $grupoId) {
                $q->where('empresa_id', $empresaId)
                    ->orWhere('compartilhado', true)
                    ->orWhere('grupo_id', $grupoId);
            })
            ->get()
            ->map(function ($d) {
                $compartilhadoCom = [];
                if ($d->compartilhado_com && is_array($d->compartilhado_com)) {
                    $compartilhadoCom = Empresa::whereIn('id', $d->compartilhado_com)
                        ->get(['id', 'nome'])
                        ->toArray();
                }

                return [
                    'id' => $d->id,
                    'nome' => $d->nome,
                    'tipo' => $d->tipo,
                    'ativo' => $d->ativo,
                    'compartilhado' => $d->compartilhado,
                    'compartilhado_com' => $compartilhadoCom,
                    'grupo_id' => $d->grupo_id,
                    'empresa' => $d->empresa ? ['id' => $d->empresa->id, 'nome' => $d->empresa->nome] : null,
                ];
            });

        return response()->json($depositos);
    }

    public function show($id)
    {
        $item = EstoqueSaldo::with(['sku', 'sku.product', 'deposito'])->findOrFail($id);

        return response()->json([
            'id' => $item->id,
            'saldo' => $item->saldo,
            'saldo_minimo' => $item->saldo_minimo,
            'deposito' => $item->deposito,
            'sku' => $item->sku,
            'produto' => $item->sku?->product,
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = EstoqueSaldo::findOrFail($id);

        if ($request->has('saldo')) {
            $item->saldo = $request->saldo;
        }

        if ($request->has('saldo_minimo')) {
            $item->saldo_minimo = $request->saldo_minimo;
        }

        $item->save();

        return response()->json(['success' => true, 'message' => 'Estoque atualizado']);
    }

    public function storeDeposito(Request $request)
    {
        try {
            $empresaId = session('empresa_id', 6);
            $empresa = Empresa::find($empresaId);

            $deposito = Deposito::create([
                'nome' => $request->nome,
                'tipo' => $request->tipo ?? 'armazem',
                'ativo' => $request->boolean('ativo', true),
                'empresa_id' => $empresaId,
                'compartilhado' => $request->boolean('compartilhado', false),
                'compartilhado_com' => $request->input('compartilhado_com', []),
                'grupo_id' => $empresa?->grupo_id,
            ]);

            return response()->json([
                'id' => $deposito->id,
                'nome' => $deposito->nome,
                'tipo' => $deposito->tipo,
                'ativo' => $deposito->ativo,
                'compartilhado' => $deposito->compartilhado,
                'empresa' => ['nome' => $empresa?->nome],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateDeposito(Request $request, $id)
    {
        $deposito = Deposito::findOrFail($id);

        $data = $request->only(['nome', 'tipo', 'ativo', 'compartilhado']);
        if ($request->has('compartilhado_com')) {
            $data['compartilhado_com'] = $request->compartilhado_com;
        }

        $deposito->update($data);

        return response()->json(['success' => true]);
    }

    public function destroyDeposito($id)
    {
        Deposito::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
