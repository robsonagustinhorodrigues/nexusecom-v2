<?php

namespace App\Traits;

use App\Models\Grupo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToGroup
{
    public static function bootBelongsToGroup()
    {
        static::creating(function ($model) {
            if (!$model->grupo_id && auth()->check()) {
                $model->grupo_id = auth()->user()->grupo_id;
            }
        });

        static::addGlobalScope('group_scope', function (Builder $builder) {
            if (!app()->runningInConsole() && auth()->check()) {
                $user = auth()->user();
                if ($user && $user->grupo_id) {
                    $builder->where($builder->getModel()->getTable() . '.grupo_id', $user->grupo_id);
                }
            }
        });
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }
}
