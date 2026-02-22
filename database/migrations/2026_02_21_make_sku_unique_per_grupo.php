<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_skus', function (Blueprint $table) {
            // Remove old unique constraint on sku
            $table->dropUnique(['sku']);
            
            // Add unique constraint on sku + grupo_id
            $table->unique(['sku', 'grupo_id'], 'skus_sku_grupo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_skus', function (Blueprint $table) {
            $table->dropUnique(['sku', 'grupo_id']);
            $table->string('sku')->unique()->change();
        });
    }
};
