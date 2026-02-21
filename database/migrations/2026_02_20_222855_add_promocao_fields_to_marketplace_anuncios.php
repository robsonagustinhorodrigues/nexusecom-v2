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
            $table->decimal('preco_original', 10, 2)->nullable()->after('preco');
            $table->string('promocao_tipo', 30)->nullable()->after('preco_original');
            $table->string('promocao_id', 50)->nullable()->after('promocao_tipo');
            $table->decimal('promocao_desconto', 5, 2)->nullable()->after('promocao_id');
            $table->decimal('promocao_valor', 10, 2)->nullable()->after('promocao_desconto');
            $table->timestamp('promocao_inicio')->nullable()->after('promocao_valor');
            $table->timestamp('promocao_fim')->nullable()->after('promocao_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            //
        });
    }
};
