<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Distpointgroup extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable =['name','capacity','created_at','deleted_at'];

    public function distpoints()
    {
        return $this->hasMany(\App\Distpoint::class, 'distpointgroup_id');
    }
    public function customers()
    {
        return $this->hasManyThrough(Customer::class, Distpoint::class, 'distpointgroup_id', 'id_distpoint', 'id', 'id');
    }
    public function distpoint_name()
    {
        return $this->belongsTo('\App\Distpoint', 'parrent')->withTrashed();
    }

}
