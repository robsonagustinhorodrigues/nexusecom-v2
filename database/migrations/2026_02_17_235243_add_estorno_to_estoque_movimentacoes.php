<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estoque_movimentacoes', function (Blueprint $table) {
            $table->foreignId('movimentacao_estornada_id')
                ->nullable()
                ->after('anuncio_id')
                ->references('id')
                ->on('estoque_movimentacoes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('estoque_movimentacoes', function (Blueprint $table) {
            $table->dropForeign(['movimentacao_estornada_id']);
            $table->dropColumn('movimentacao_estornada_id');
        });
    }
};
