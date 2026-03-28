<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Addon extends Model
{
    use SoftDeletes;

    protected $table = 'addons';

    protected $fillable = ['name', 'price', 'description', 'is_active'];

    /**
     * Customers that have this add-on.
     */
    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_addons', 'id_addon', 'id_customer');
    }
}
