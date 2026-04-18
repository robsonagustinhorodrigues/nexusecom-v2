<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Categoria extends Model
{
    protected $fillable = [
        'grupo_id',
        'nome',
        'slug',
        'categoria_pai_id',
    ];

    protected function casts(): array
    {
        return [
            'nome' => 'string',
            'slug' => 'string',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($categoria) {
            if (empty($categoria->slug)) {
                $categoria->slug = Str::slug($categoria->nome);
            }
        });
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function pai(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_pai_id');
    }

    public function filhas(): HasMany
    {
        return $this->hasMany(Categoria::class, 'categoria_pai_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeRaizes($query)
    {
        return $query->whereNull('categoria_pai_id');
    }
}
