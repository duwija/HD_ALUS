<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    //
     protected $fillable =['id_customer','name','parrent','ip','sn','owner','type','position','note','updated_by','created_at','deleted_at',];

     public function customer()
    {
        return $this->belongsTo('\App\Customer', 'id_customer');
    }
     public function parrent_name()
    {
        return $this->belongsTo('\App\Device', 'parrent');
    }

}
