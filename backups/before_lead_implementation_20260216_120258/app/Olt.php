<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Olt extends Model
{
	use softDeletes;

	protected $fillable = [
		'name', 'vendor', 'type', 'ip', 'port', 'user', 'password', 'community_ro', 'community_rw','snmp_port', 'phone', 'updated_at','created_at','deleted_at'
	];
    //

	public function distpoint($id)
	{
		$olt = $this->where('id', $id)
		->first();
		return $olt;
		
	}

	
}
