<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
 use HasFactory, SoftDeletes;
 protected $fillable = ['name', 'created_at'];
 protected $dates = ['deleted_at'];

 public function tickets()
 {
    return $this->belongsToMany(\App\Ticket::class, 'tickettags', 'tag_id', 'ticket_id');
 }
 public function customers()
 {
    return $this->belongsToMany(\App\Customer::class, 'customer_tags', 'tag_id', 'customer_id');
 }
}
