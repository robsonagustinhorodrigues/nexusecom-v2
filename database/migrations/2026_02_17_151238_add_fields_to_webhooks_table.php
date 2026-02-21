<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona colunas primeiro (sem alterar o enum)
        Schema::table('webhooks', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('external_id');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->json('headers')->nullable()->after('user_agent');
            $table->string('resource_url', 500)->nullable()->after('headers');
            $table->json('response_data')->nullable()->after('processed_at');
            $table->integer('duration_ms')->nullable()->after('response_data');
            $table->string('job_id', 100)->nullable()->after('duration_ms');
            $table->timestamp('next_retry_at')->nullable()->after('job_id');
        });
        
        // Altera o status via SQL raw para evitar problema com enum no PostgreSQL
        Schema::table('webhooks', function (Blueprint $table) {
            $table->string('status', 50)->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropColumn([
                'ip_address',
                'user_agent', 
                'headers',
                'resource_url',
                'response_data',
                'duration_ms',
                'job_id',
                'next_retry_at',
            ]);
        });
    }
};
