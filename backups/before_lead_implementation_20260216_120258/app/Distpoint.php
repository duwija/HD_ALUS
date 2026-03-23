<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Distpoint extends Model
{

 use SoftDeletes;
 protected $fillable =['name','distpointgroup_id','id_site', 'ip', 'security','parrent','coordinate','created_at','monitoring','status','description','deleted_at'];
 public function distpoint_name()
 {
    return $this->belongsTo('\App\Distpoint', 'parrent')->withTrashed();
}
public function olt_name()
{
    return $this->belongsTo('\App\Olt', 'id_olt')->withTrashed();
}
public function site_name()
{
    return $this->belongsTo('\App\Site', 'id_site')->withTrashed();
}
public function customer()
{
    return $this->hasMany(\App\Customer::class, 'id_distpoint');
}
public function distpoint($id)
{
    $distpoint = $this->where('id', $id)
    ->first();
    return $distpoint;

}
public function group()
{
    return $this->belongsTo(\App\Distpointgroup::class, 'distpointgroup_id');
}
public function parentDistPoint()
{
    return $this->belongsTo(Distpoint::class, 'parrent');
}
}
