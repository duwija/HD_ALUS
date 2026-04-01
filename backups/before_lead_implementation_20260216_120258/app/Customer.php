<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
	use SoftDeletes;

    protected $fillable =['customer_id','pppoe','password','name','id_card', 'contact_name','id_olt','id_onu','date_of_birth', 'phone','id_plan','id_distpoint','id_status','id_distrouter','email','address','id_merchant','npwp','tax','billing_start','coordinate','note','id_sale','created_by','updated_by','created_at','update_at','deleted_at','notification','ip','portal_password','remember_token','last_login_at'];

    protected $hidden = [
        'password',
        'portal_password',
        'remember_token',
    ];

    /**
     * Get the password for authentication (use portal_password instead of password)
     */
    public function getAuthPassword()
    {
        return $this->portal_password;
    }

    public function customerlog_name()
    {

        return $this->hasMany('\App\Customerlog', 'id_customer')->withTrashed();
    }
    public function suminvoices()
    {
        return $this->hasMany('\App\Suminvoice', 'id_customer');
    }
    public function plan_name()
    {
        return $this->belongsTo('\App\Plan', 'id_plan')->withTrashed();
    }
    public function olt_name()
    {
        return $this->belongsTo('\App\Olt', 'id_olt')->withTrashed();
    }
    public function sale_name()
    {
        return $this->belongsTo('\App\Sale', 'id_sale')->withTrashed();
    }
    public function distpoint_name()
    {
        return $this->belongsTo('\App\Distpoint', 'id_distpoint')->withTrashed();
    }
    public function status_name()
    {
        return $this->belongsTo('\App\Statuscustomer', 'id_status')->withTrashed();
    }
    public function merchant_name()
    {
        return $this->belongsTo('\App\Merchant', 'id_merchant')->withTrashed();
    }
    public function invoice()
    {

        return $this->hasMany('\App\Invoice', 'id_customer')->withTrashed();
    }
    public function invoices()
    {
        return $this->hasMany(\App\Invoice::class, 'id_customer');
    }
    public function device()
    {

        return $this->hasMany('\App\Device', 'id_customer');
    }
    public function file()
    {

        return $this->hasMany('\App\File', 'id_customer');
    }
    public function distrouter()
    {
        return $this->belongsTo('\App\Distrouter', 'id_distrouter');
    }
// Customer.php
    public function distpoint() {
        return $this->belongsTo('\App\Distpoint', 'id_distpoint');
    }

    

    public function update_status()
    {
        // \Log::channel('notif')->info('JOB ISOLLIR model '.$id.' | ' .$status); 
        // \App\Customer::where('id',$id)->update([
        //     'id_status' => $status,
        // ]);
        return "DATA DARI MODEL";
    }

}
