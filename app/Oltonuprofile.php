<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oltonuprofile extends Model
{
    use HasFactory;
    protected $fillable = ['id', 'id_olt','name','vlan','created_at','updated_at','deleted_at'];

    public function olt()
    {
        return $this->belongsTo('\App\Olt', 'id_olt')->withTrashed();
    }

}
