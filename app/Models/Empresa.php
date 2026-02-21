<?php

namespace App\Models;

use App\Traits\BelongsToGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use BelongsToGroup;

    protected $fillable = [
        'nome',
        'razao_social',
        'apelido',
        'slug',
        'cnpj',
        'logo_path',
        'certificado_a1_path',
        'certificado_senha',
        'email_contabil',
        'auto_ciencia',
        'last_nsu',
        'ativo',
        'last_sefaz_query_at',
        'grupo_id',
        'danfe_enabled',
        'danfe_layout',
        'danfe_show_logo',
        'danfe_logo_position',
        'danfe_show_itens',
        'danfe_show_valor_itens',
        'danfe_show_valor_total',
        'danfe_show_qrcode',
        'danfe_rodape',
        'tipo_atividade',
        'regime_tributario',
        'aliquota_icms',
        'aliquota_pis',
        'aliquota_cofins',
        'aliquota_csll',
        'aliquota_irpj',
        'aliquota_iss',
        'percentual_lucro_presumido',
        'aliquota_simples',
        'calcula_imposto_auto',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'auto_ciencia' => 'boolean',
            'last_nsu' => 'integer',
            'last_sefaz_query_at' => 'datetime',
            'danfe_enabled' => 'boolean',
            'danfe_show_logo' => 'boolean',
            'danfe_show_itens' => 'boolean',
            'danfe_show_valor_itens' => 'boolean',
            'danfe_show_valor_total' => 'boolean',
            'danfe_show_qrcode' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function integracoes(): HasMany
    {
        return $this->hasMany(Integracao::class);
    }

    public function armazens(): BelongsToMany
    {
        return $this->belongsToMany(Armazem::class, 'armazem_empresa');
    }

    public function nfeRecebidas(): HasMany
    {
        return $this->hasMany(NfeRecebida::class);
    }

    public function parceiros(): HasMany
    {
        return $this->hasMany(Parceiro::class);
    }
}
