<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ping extends Model
{
    protected $fillable = [
        'probe_id','host','host_name','is_up','rtt_avg_ms','loss_percent','polled_at'
    ];

    protected $casts = [
        'is_up' => 'boolean',
        'polled_at' => 'datetime',
    ];
    public function probe()
    {
        return $this->belongsTo(Probe::class, 'probe_id', 'id');
    }
}
