<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integracoes', function (Blueprint $col) {
            $col->id();
            $col->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $col->string('marketplace'); // meli, shopee, amazon, etc
            $col->string('nome_conta'); // Ex: MaxLider Oficial
            $col->string('external_user_id')->nullable(); // ID do usuário no marketplace
            
            // Segurança: Armazenamento de Tokens
            $col->text('access_token')->nullable();
            $col->text('refresh_token')->nullable();
            $col->timestamp('expires_at')->nullable();
            
            $col->boolean('ativo')->default(true);
            $col->json('configuracoes')->nullable(); // Regras específicas por conta
            $col->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integracoes');
    }
};
