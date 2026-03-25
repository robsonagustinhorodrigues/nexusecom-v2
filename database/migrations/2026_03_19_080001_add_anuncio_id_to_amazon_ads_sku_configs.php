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
        Schema::table('amazon_ads_sku_configs', function (Blueprint $table) {
            $table->foreignId('marketplace_anuncio_id')->nullable()->after('empresa_id')->constrained('marketplace_anuncios')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amazon_ads_sku_configs', function (Blueprint $table) {
            $table->dropForeign(['marketplace_anuncio_id']);
            $table->dropColumn('marketplace_anuncio_id');
        });
    }
};
