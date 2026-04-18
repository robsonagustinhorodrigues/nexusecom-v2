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
        // 1. Criar tabela de grupos
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // 2. Adicionar grupo_id às tabelas existentes
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('grupo_id')->nullable()->after('id')->constrained('grupos')->onDelete('cascade');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->foreignId('grupo_id')->nullable()->after('id')->constrained('grupos')->onDelete('cascade');
        });

        Schema::table('armazens', function (Blueprint $table) {
            $table->foreignId('grupo_id')->nullable()->after('id')->constrained('grupos')->onDelete('cascade');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('grupo_id')->nullable()->after('id')->constrained('grupos')->onDelete('cascade');
        });

        Schema::table('estoque_movimentacoes', function (Blueprint $table) {
            $table->foreignId('grupo_id')->nullable()->after('id')->constrained('grupos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estoque_movimentacoes', function (Blueprint $table) {
            $table->dropForeign(['grupo_id']);
            $table->dropColumn('grupo_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['grupo_id']);
            $table->dropColumn('grupo_id');
        });

        Schema::table('armazens', function (Blueprint $table) {
            $table->dropForeign(['grupo_id']);
            $table->dropColumn('grupo_id');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropForeign(['grupo_id']);
            $table->dropColumn('grupo_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['grupo_id']);
            $table->dropColumn('grupo_id');
        });

        Schema::dropIfExists('grupos');
    }
};
