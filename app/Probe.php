<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Probe extends Model
{
    protected $fillable = [
        'probe_id','name','token','type','location','is_active'
    ];

    public function pings() {
        return $this->hasMany(Ping::class);
    }

    public function alerts() {
        return $this->hasMany(Alert::class);
    }
}
