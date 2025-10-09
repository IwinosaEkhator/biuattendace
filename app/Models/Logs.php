<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    protected $fillable = ['user_id', 'log', 'campus_id', 'service_id', 'mat_no'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
