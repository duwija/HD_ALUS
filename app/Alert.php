<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'probe_id','host','host_name','status','message'
    ];

    public function probe() {
        return $this->belongsTo(Probe::class);
    }
}
