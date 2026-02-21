<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estoque_movimentacoes', function (Blueprint $table) {
            // Renomear armazem_id para deposito_id
            $table->renameColumn('armazem_id', 'deposito_id');
            
            // Adicionar campos novos
            $table->foreignId('empresa_id')->nullable()->after('grupo_id')->constrained('empresas')->nullOnDelete();
            $table->string('documento')->nullable()->after('tipo'); // NF, pedido, etc
            $table->string('documento_tipo')->nullable()->after('documento'); // nfe_compra, nfe_devolucao, pedido_venda, ajuste
            $table->decimal('valor_unitario', 12, 2)->nullable()->after('quantidade');
            $table->enum('status', ['pendente', 'confirmado', 'cancelado'])->default('confirmado')->after('observacao');
            $table->foreignId('pedido_id')->nullable()->after('status')->nullable();
            $table->foreignId('anuncio_id')->nullable()->after('pedido_id');
            $table->boolean('produto_bom')->default(true)->after('status'); // true = volta ao estoque, false = perda
        });
    }

    public function down(): void
    {
        Schema::table('estoque_movimentacoes', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropForeign(['pedido_id']);
            $table->dropForeign(['anuncio_id']);
            
            $table->renameColumn('deposito_id', 'armazem_id');
            $table->dropColumn([
                'empresa_id', 'documento', 'documento_tipo', 
                'valor_unitario', 'status', 'pedido_id', 
                'anuncio_id', 'produto_bom'
            ]);
        });
    }
};
