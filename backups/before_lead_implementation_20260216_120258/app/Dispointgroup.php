<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;


class Distpointgroup extends Model
{
    use HasFactory;
    use softDeletes;
    protected $fillable =['name','capacity','description','created_at','deleted_at'];
}
