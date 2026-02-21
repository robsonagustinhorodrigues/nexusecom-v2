<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('danfe_enabled')->default(true)->after('certificado_senha');
            $table->string('danfe_layout')->default('simplificado')->after('danfe_enabled');
            $table->boolean('danfe_show_logo')->default(true)->after('danfe_layout');
            $table->string('danfe_logo_position')->default('top')->after('danfe_show_logo');
            $table->boolean('danfe_show_itens')->default(true)->after('danfe_logo_position');
            $table->boolean('danfe_show_valor_itens')->default(true)->after('danfe_show_itens');
            $table->boolean('danfe_show_valor_total')->default(true)->after('danfe_show_valor_itens');
            $table->boolean('danfe_show_qrcode')->default(true)->after('danfe_show_valor_total');
            $table->string('danfe_rodape')->nullable()->after('danfe_show_qrcode');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'danfe_enabled',
                'danfe_layout',
                'danfe_show_logo',
                'danfe_logo_position',
                'danfe_show_itens',
                'danfe_show_valor_itens',
                'danfe_show_valor_total',
                'danfe_show_qrcode',
                'danfe_rodape',
            ]);
        });
    }
};
