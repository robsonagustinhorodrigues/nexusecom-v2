<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->nullable()->constrained()->onDelete('set null');
            $table->string('nome');
            $table->string('slug')->unique();
            $table->foreignId('categoria_pai_id')->nullable()->constrained('categorias')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->nullable()->constrained()->onDelete('set null');
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('cor')->nullable()->default('#6366f1');
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');
            $table->json('tags')->nullable();
        });

        Schema::table('product_skus', function (Blueprint $table) {
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('product_skus', function (Blueprint $table) {
            $table->dropForeign(['fornecedor_id']);
            $table->dropColumn('fornecedor_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
            $table->dropColumn('tags');
        });

        Schema::dropIfExists('tags');
        Schema::dropIfExists('categorias');
    }
};
