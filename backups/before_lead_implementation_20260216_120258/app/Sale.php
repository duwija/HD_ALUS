<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'full_name', 'email', 'password', 'date_of_bird', 'job_title', 'join_date', 'address','sale_type', 'phone','description','date_of_birth', 'email', 'photo', 'last_login_at', 'updated_at','created_at','deleted_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
public function customer()
    {
        // return $this->hasmany('\App\Customer', 'id_distpoint')->withTrashed();
         return $this->hasmany('\App\Customer', 'id_sale');
    }
     public function sale($id)
    {
           $sale = $this->where('id', $id)
           ->first();
           return $sale;
         
    }
}
