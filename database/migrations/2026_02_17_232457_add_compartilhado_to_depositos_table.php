<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depositos', function (Blueprint $table) {
            $table->boolean('compartilhado')->default(false)->after('tipo');
            $table->foreignId('empresa_dona_id')->nullable()->after('empresa_id')->constrained('empresas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('depositos', function (Blueprint $table) {
            $table->dropForeign(['empresa_dona_id']);
            $table->dropColumn(['compartilhado', 'empresa_dona_id']);
        });
    }
};
