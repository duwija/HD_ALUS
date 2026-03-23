<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
 protected $fillable = ['name','created_at'];
 use HasFactory;
 public function tickets()
 {
    return $this->belongsToMany(\App\Ticket::class, 'tickettags', 'tag_id', 'ticket_id');
}
}
