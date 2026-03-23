<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
	
	protected $fillable = [
		'id', 'queue', 'payload'
	];
    //

	

	
}
