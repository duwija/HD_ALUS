<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes; // Gunakan SoftDeletes
    use HasFactory;
    protected $fillable = ['contact_id',
    'category', 'name','phone', 'email', 'address','note','created_at','updated_at','deleted_at'
];
}
