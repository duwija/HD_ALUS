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

	// Password Telnet OLT dienkripsi at-rest (APP_KEY). Transparan untuk semua $olt->password
	// yang sudah ada di codebase — otomatis encrypt saat save, decrypt saat dibaca.
	// Data lama sudah dimigrasi lewat: php artisan olt:encrypt-passwords
	protected $casts = [
		'password' => 'encrypted',
	];
    //

	public function distpoint($id)
	{
		$olt = $this->where('id', $id)
		->first();
		return $olt;
		
	}

	
}
