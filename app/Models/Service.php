<?php

namespace App\Models;

use App\Models\Scopes\CampusScope;
use Illuminate\Container\Attributes\Log;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['slug', 'name', 'active'];

    protected static function booted()
    {
        static::addGlobalScope(new CampusScope);
    }


    public function logs()
    {
        return $this->hasMany(Log::class);
    }
}
