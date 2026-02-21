<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacao extends Model
{
    protected $table = 'notificacoes';
    
    protected $fillable = [
        'user_id',
        'tipo',
        'titulo',
        'mensagem',
        'icone',
        'cor',
        'link',
        'lida',
    ];

    protected $casts = [
        'lida' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function criar(string $tipo, string $titulo, string $mensagem, string $cor = 'info', string $link = null): self
    {
        return self::create([
            'user_id' => 1, // Admin principal
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'icone' => self::getIcone($tipo),
            'cor' => $cor,
            'link' => $link,
        ]);
    }

    private static function getIcone(string $tipo): string
    {
        return match($tipo) {
            'pedido' => 'fa-shopping-cart',
            'estoque' => 'fa-box',
            'webhook' => 'fa-webhook',
            'frete' => 'fa-shipping-fast',
            default => 'fa-bell'
        };
    }
}
