<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PppoeStat extends Model
{
    protected $table = 'pppoe_stats';

    protected $fillable = [
        'distrouter_id',
        'total',
        'active',
        'offline',
        'disabled',
        'collected_at',
    ];

    protected $dates = ['collected_at'];

    public function distrouter()
    {
        return $this->belongsTo(\App\Distrouter::class, 'distrouter_id');
    }
}
