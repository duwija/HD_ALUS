<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerTag extends Model
{
    protected $table = 'customer_tag_definitions';

    protected $fillable = ['name'];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_tag_map', 'customer_tag_id', 'customer_id');
    }
}
