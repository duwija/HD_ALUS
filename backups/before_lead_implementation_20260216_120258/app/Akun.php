<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Akun extends Model
{
    //
    
   protected $fillable = [
    'name',
    'akun_code',
    'category',
    'tax',
    'group',
    'tax_value',
    'parent',
    'description',
    'created_at',
    'deleted_at',
];
protected $casts = [
    'akun_code' => 'string',
];
   protected $primaryKey = 'akun_code'; // Assuming 'id' is the primary key
   public function users()
   {
    return $this->belongsToMany(User::class, 'akunusers', 'akun_code', 'id_user');

}





    // Relasi untuk memeriksa jurnal
public function journals()
{
    return $this->hasMany(Jurnal::class, 'id_akun', 'akun_code');
}

    // Cek apakah akun digunakan di jurnal
public function isUsedInJournals()
{
    return $this->journals()->exists();
}


 // Relasi untuk akun child
public function children()
{
    return $this->hasMany(Akun::class, 'parent', 'akun_code');
}

    // Relasi untuk akun parent
public function parent()
{
    return $this->belongsTo(Akun::class, 'parent', 'akun_code');
}

    // Relasi ke transaksi jurnal
public function transactions()
{
    return $this->hasMany(Jurnal::class, 'id_akun', 'akun_code');
}

    // Saldo akun (Kredit - Debet) + Saldo Child
public function getSaldoAttribute()
{
   $currentSaldo = $this->transactions->sum('debet') - $this->transactions->sum('kredit');

   $childSaldo = $this->children->sum(function ($child) {
        return $child->saldo; // Rekursif menghitung saldo child
    });

   return $currentSaldo + $childSaldo;
}



public function parentAkun()
{
    return $this->belongsTo(self::class, 'parent', 'akun_code'); // parent -> akun_code INDuk
}

}
