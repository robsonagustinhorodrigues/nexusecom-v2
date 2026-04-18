<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->string('source'); // mercadolivre, bling, shopee, amazon, magalu
            $table->string('topic'); // orders, shipments, billing, etc
            $table->string('external_id')->nullable(); // ID externo do marketplace
            $table->json('payload'); // Dados recebidos
            $table->string('status')->default('pending'); // pending, processing, processed, failed
            $table->text('error')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();

            $table->index(['empresa_id', 'source', 'status']);
            $table->index(['source', 'external_id']);
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
