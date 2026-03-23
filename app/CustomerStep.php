<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerStep extends Model
{
    protected $fillable = ['customer_id', 'name', 'position', 'selected_at'];

    protected $casts = [
        'selected_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
