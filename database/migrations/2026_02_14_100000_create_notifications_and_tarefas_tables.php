<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type', 50)->default('info');
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('link')->nullable();
            $table->boolean('read')->default(false);
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('tarefas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tipo', 50);
            $table->string('status', 20)->default('pending');
            $table->integer('total')->default(0);
            $table->integer('processado')->default(0);
            $table->integer('sucesso')->default(0);
            $table->integer('falha')->default(0);
            $table->text('mensagem')->nullable();
            $table->json('resultado')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarefas');
        Schema::dropIfExists('notifications');
    }
};
