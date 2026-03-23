<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Walog extends Model
{
    use HasFactory;

    protected $fillable = [
        'session',
        'number', 
        'message',
        'status',
        'message_id',
        'direction'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeBySession($query, $session)
    {
        return $query->where('session', $session);
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'out');
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', 'in');
    }
}