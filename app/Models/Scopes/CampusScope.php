<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CampusScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (app()->runningInConsole()) {
            return;
        }

        // Only apply if the table actually has a campus_id column
        if (!Schema::hasColumn($model->getTable(), 'campus_id')) {
            return;
        }

        $user = Auth::user(); // Intelephense knows this method
        if ($user && $user->campus_id) {
            $builder->where($model->getTable() . '.campus_id', $user->campus_id);
        }
    }
}
