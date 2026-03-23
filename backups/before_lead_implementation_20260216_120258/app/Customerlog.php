<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customerlog extends Model
{
    use SoftDeletes;


    protected $fillable =['id_customer','date','updated_by','topic','updates'];

    public function customer()
    {
        return $this->belongsTo('\App\Customer', 'id_customer')->withTrashed();
    }
}
