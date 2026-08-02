<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use HasFactory;
    use softDeletes;
    protected $fillable = [
        'name', 'contact_name', 'phone', 'address', 'coordinate', 'description','created_at','akun_code','payment_point'
    ];

    public function customer()
    {

     return $this->hasmany('\App\Customer', 'id_merchant');
 }
 public function merchant($id)
 {
   $merchant = $this->where('id', $id)
   ->first();
   return $merchant;

}
public function akun_name()
{
    return $this->belongsTo(\App\Akun::class, 'akun_code', 'akun_code');
}

}
