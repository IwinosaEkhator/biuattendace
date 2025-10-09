<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    protected $fillable = ['code', 'name'];

    public function services()
    {
        return $this->hasMany(Service::class);
    }
}
