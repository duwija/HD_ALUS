<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use SoftDeletes;

    public function workflowStage()
    {
        return $this->belongsTo(LeadWorkflow::class, 'workflow_stage_id');
    }
    use SoftDeletes;

	use SoftDeletes;

    protected $fillable =['customer_id','pppoe','password','name','id_card', 'contact_name','id_olt','id_onu','date_of_birth', 'phone','id_plan','id_distpoint','id_status','id_distrouter','email','address','id_merchant','npwp','tax','billing_start','isolir_date','coordinate','note','id_sale','lead_source','lead_notes','expected_close_date','conversion_probability','converted_at','converted_by','lost_at','lost_reason','lost_notes','lost_by','deletion_type','deletion_reason','created_by','updated_by','created_at','update_at','deleted_at','notification','ip','portal_password','remember_token','last_login_at','fcm_token','app_token'];

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

    /**
     * Varian nomor telepon untuk menangani data campuran lokal/internasional
     * (0..., 62..., +62..., +44..., 0044...), dipakai untuk mencocokkan
     * customer berdasarkan nomor HP tanpa peduli format aslinya di DB.
     */
    public static function phoneVariants(string $rawPhone): array
    {
        $digits = preg_replace('/\D+/', '', trim($rawPhone));
        if ($digits === '') {
            return [];
        }

        $variants = [];
        $add = function (string $value) use (&$variants) {
            $value = trim($value);
            if ($value !== '') {
                $variants[] = $value;
            }
        };

        // Bentuk dasar (dengan dan tanpa +)
        $add($digits);
        $add('+' . $digits);

        // Format internasional yang ditulis 00<country_code><number>
        if (strpos($digits, '00') === 0 && strlen($digits) > 2) {
            $intl = substr($digits, 2);
            $add($intl);
            $add('+' . $intl);
        }

        if (strpos($digits, '0') === 0) {
            $idIntl = '62' . substr($digits, 1);
            $add($idIntl);
            $add('+' . $idIntl);
        } elseif (strpos($digits, '62') === 0) {
            $add('0' . substr($digits, 2));
        } elseif (strpos($digits, '8') === 0) {
            $add('0' . $digits);
            $add('62' . $digits);
            $add('+62' . $digits);
        }

        return array_values(array_unique(array_filter($variants, function ($v) {
            return $v !== '';
        })));
    }

    public function customerlog_name()
    {

        return $this->hasMany('\App\Customerlog', 'id_customer')->withTrashed();
    }
    public function suminvoices()
    {
        return $this->hasMany('\App\Suminvoice', 'id_customer');
    }

    public function plan()
    {
        return $this->belongsTo('\App\Plan', 'id_plan')->withTrashed();
    }

    public function plan_name()
    {
        return $this->belongsTo('\App\Plan', 'id_plan')->withTrashed();
    }

    public function addons()
    {
        return $this->belongsToMany('\App\Addon', 'customer_addons', 'id_customer', 'id_addon');
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

    /**
     * Lead update history relationship
     */
    public function leadUpdates()
    {
        return $this->hasMany('\App\LeadUpdate', 'id_customer')->orderBy('created_at', 'desc');
    }

    /**
     * Per-customer workflow steps
     */
    public function customerSteps()
    {
        return $this->hasMany('\App\CustomerStep', 'customer_id')->orderBy('position');
    }

    

    public function update_status()
    {
        // \Log::channel('notif')->info('JOB ISOLLIR model '.$id.' | ' .$status); 
        // \App\Customer::where('id',$id)->update([
        //     'id_status' => $status,
        // ]);
        return "DATA DARI MODEL";
    }

    public function tags()
    {
        return $this->belongsToMany(\App\CustomerTag::class, 'customer_tag_map', 'customer_id', 'customer_tag_id');
    }

}
