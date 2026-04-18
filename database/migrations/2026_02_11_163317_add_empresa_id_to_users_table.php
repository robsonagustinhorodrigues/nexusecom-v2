<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $col) {
            $col->foreignId('current_empresa_id')->nullable()->constrained('empresas');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $col) {
            $col->dropColumn('current_empresa_id');
        });
    }
};
