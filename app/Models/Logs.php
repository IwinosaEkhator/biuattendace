<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    protected $fillable = [
        'log'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
