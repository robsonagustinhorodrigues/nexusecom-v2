<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplacePedido extends Model
{
    protected $fillable = [
        'empresa_id',
        'integracao_id',
        'cliente_id',
        'marketplace',
        'pedido_id',
        'external_id',
        'status',
        'status_pagamento',
        'status_envio',
        'comprador_nome',
        'comprador_email',
        'comprador_cpf',
        'comprador_cnpj',
        'telefone',
        'endereco',
        'cidade',
        'estado',
        'cep',
        'valor_total',
        'valor_frete',
        'valor_desconto',
        'valor_produtos',
        'valor_taxa_platform',
        'valor_taxa_fixa',
        'valor_taxa_pagamento',
        'valor_imposto',
        'valor_outros',
        'valor_liquido',
        'data_compra',
        'data_pagamento',
        'data_envio',
        'data_entrega',
        'codigo_rastreamento',
        'url_rastreamento',
        'json_data',
        'imported_at',
        'import_hash',
        'import_confirmed',
        'import_error',
        'last_status_update',
    ];

    protected function casts(): array
    {
        return [
            'data_compra' => 'datetime',
            'data_pagamento' => 'datetime',
            'data_envio' => 'datetime',
            'data_entrega' => 'datetime',
            'imported_at' => 'datetime',
            'import_confirmed' => 'boolean',
            'last_status_update' => 'datetime',
            'valor_total' => 'decimal:2',
            'valor_frete' => 'decimal:2',
            'valor_desconto' => 'decimal:2',
            'valor_produtos' => 'decimal:2',
            'valor_taxa_platform' => 'decimal:2',
            'valor_taxa_fixa' => 'decimal:2',
            'valor_taxa_pagamento' => 'decimal:2',
            'valor_imposto' => 'decimal:2',
            'valor_outros' => 'decimal:2',
            'valor_liquido' => 'decimal:2',
            'json_data' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function integracao(): BelongsTo
    {
        return $this->belongsTo(Integracao::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function scopeTenant($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopeMercadoLivre($query)
    {
        return $query->where('marketplace', 'mercadolivre');
    }

    public function scopePendente($query)
    {
        return $query->where('status', 'pending');
    }
}
