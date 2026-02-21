<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfe_emitidas', function (Blueprint $col) {
            $col->id();
            $col->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $col->string('chave')->unique();
            $col->string('numero');
            $col->string('serie');
            $col->string('cliente_nome');
            $col->string('cliente_cnpj');
            $col->decimal('valor_total', 15, 2);
            $col->datetime('data_emissao');
            $col->string('xml_path')->nullable();
            $col->string('status')->default('autorizada'); // autorizada, cancelada, inutilizada
            $col->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfe_emitidas');
    }
};
