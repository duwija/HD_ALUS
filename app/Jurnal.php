<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jurnal extends Model
{
  use SoftDeletes;
    //
  protected $fillable =['date','code','id_akun', 'kredit', 'debet','reff','type','description',"created_by",'deleted_at','note','created_by', 'contact_id','category','memo',];

  public function akun_name()
  {
    return $this->belongsTo('\App\Akun', 'id_akun');
  }
  public function akun()
  {
    return $this->belongsTo(\App\Akun::class, 'id_akun', 'akun_code');
  }



}
