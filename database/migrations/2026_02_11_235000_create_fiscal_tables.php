<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfe_recebidas', function (Blueprint $col) {
            $col->id();
            $col->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $col->string('chave', 44)->unique();
            $col->string('numero', 15)->nullable();
            $col->string('serie', 3)->nullable();
            $col->string('emitente_nome')->nullable();
            $col->string('emitente_cnpj', 14)->nullable();
            $col->decimal('valor_total', 15, 2)->default(0);
            $col->timestamp('data_emissao')->nullable();
            $col->timestamp('data_recebimento')->nullable();
            $col->string('xml_path')->nullable();
            $col->enum('status_manifestacao', ['sem_manifesto', 'ciencia', 'confirmada', 'desconhecida', 'nao_realizada'])->default('sem_manifesto');
            $col->timestamps();
        });

        Schema::create('nfe_eventos', function (Blueprint $col) {
            $col->id();
            $col->foreignId('nfe_recebida_id')->constrained('nfe_recebidas')->onDelete('cascade');
            $col->string('tipo_evento'); // ciencia, confirmacao, etc
            $col->string('protocolo')->nullable();
            $col->text('x_motivo')->nullable();
            $col->json('payload_envio')->nullable();
            $col->json('payload_retorno')->nullable();
            $col->timestamps();
        });
        
        // Configurações por empresa
        Schema::table('empresas', function (Blueprint $col) {
            $col->boolean('auto_ciencia')->default(false)->after('email_contabil');
            $col->integer('last_nsu')->default(0)->after('auto_ciencia');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $col) {
            $col->dropColumn(['auto_ciencia', 'last_nsu']);
        });
        Schema::dropIfExists('nfe_eventos');
        Schema::dropIfExists('nfe_recebidas');
    }
};
