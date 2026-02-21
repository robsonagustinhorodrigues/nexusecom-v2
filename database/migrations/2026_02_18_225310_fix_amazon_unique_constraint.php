<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the unique constraint on (integracao_id, external_id)
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->dropUnique(['integracao_id', 'external_id']);
        });
        
        // Add unique constraint on (empresa_id, marketplace, sku) instead
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->unique(['empresa_id', 'marketplace', 'sku'], 'anuncios_empresa_marketplace_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->dropUnique('anuncios_empresa_marketplace_sku_unique');
            $table->unique(['integracao_id', 'external_id']);
        });
    }
};
