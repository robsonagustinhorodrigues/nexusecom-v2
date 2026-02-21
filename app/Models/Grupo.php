<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    protected $fillable = ['nome', 'slug'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class);
    }

    public function armazens(): HasMany
    {
        return $this->hasMany(Armazem::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
