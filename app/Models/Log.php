<?php

namespace App\Models;

use App\Models\Scopes\CampusScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;
    // protected $guarded = [];
    // protected $fillable = ['user_id', 'service_id', 'campus_id', 'log', 'mat_no'];

    protected $fillable = [
        'client_id',
        'user_id',
        'service_id',
        'campus_id',
        'log',
        'mat_no',
        'scanned_at',
        'lat',
        'lng',
        'meta',
    ];

    protected $casts = [
        'meta'       => 'array',
        'scanned_at' => 'datetime',
        'lat'        => 'float',
        'lng'        => 'float',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CampusScope);
    }

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
