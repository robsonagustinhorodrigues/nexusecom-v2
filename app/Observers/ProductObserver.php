<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // 1. Cascading the inherited pricing across variations
        if ($product->tipo === 'variacao' && ($product->isDirty('preco_venda') || $product->isDirty('preco_custo'))) {
            try {
                // Find all child products of this variation that inherit prices
                $children = Product::where('parent_id', $product->id)
                    ->where('herdar', true)
                    ->get();
                
                if ($children->isNotEmpty()) {
                    $updates = [];
                    if ($product->isDirty('preco_venda')) {
                        $updates['preco_venda'] = $product->preco_venda;
                    }
                    if ($product->isDirty('preco_custo')) {
                        $updates['preco_custo'] = $product->preco_custo;
                    }

                    foreach ($children as $child) {
                        // Update child without firing events recursively
                        Product::withoutEvents(function () use ($child, $updates) {
                            $child->update($updates);
                        });
                        
                        // Also update the connected SKU record
                        ProductSku::where('product_id', $child->id)->update($updates);
                    }
                    
                    Log::info("ProductObserver: Cascataded prices from Parent Product ID {$product->id} to {$children->count()} variations.");
                }
                
                // Also ensure the parent's own principal SKU matches
                ProductSku::where('product_id', $product->id)->update([
                    'preco_venda' => $product->preco_venda,
                    'preco_custo' => $product->preco_custo
                ]);
                
            } catch (\Exception $e) {
                Log::error("ProductObserver: Error cascading variation pricing from Parent {$product->id}: " . $e->getMessage());
            }
        }
    }
}
