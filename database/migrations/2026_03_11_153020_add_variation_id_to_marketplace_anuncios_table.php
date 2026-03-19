<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->string('variation_id')->nullable()->after('external_id');
            // Adding index to ensure uniqueness per variation
            $table->index(['integracao_id', 'external_id', 'variation_id'], 'idx_marketplace_variation_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->dropIndex('idx_marketplace_variation_unique');
            $table->dropColumn('variation_id');
        });
    }
};
