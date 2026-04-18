<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_skus', function (Blueprint $table) {
            // Para indicar qual SKU é a principal
            $table->boolean('is_principal')->default(false)->after('sku');
            
            // Para linkar SKU a uma variação específica (se forSKU de variação)
            $table->foreignId('variation_product_id')->nullable()->after('product_id')->constrained('products')->nullOnDelete();
            
            // reorder
            $table->integer('sort_order')->default(0)->after('is_principal');
        });
    }

    public function down(): void
    {
        Schema::table('product_skus', function (Blueprint $table) {
            $table->dropForeign(['variation_product_id']);
            $table->dropColumn(['is_principal', 'variation_product_id', 'sort_order']);
        });
    }
};
