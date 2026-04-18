<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->decimal('frete_custo_seller', 10, 2)->nullable()->after('json_data');
            $table->string('frete_source', 50)->nullable()->after('frete_custo_seller');
            $table->timestamp('frete_updated_at')->nullable()->after('frete_source');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->dropColumn(['frete_custo_seller', 'frete_source', 'frete_updated_at']);
        });
    }
};
