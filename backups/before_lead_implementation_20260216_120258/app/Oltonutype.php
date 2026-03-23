<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oltonutype extends Model
{
    protected $fillable = ['id', 'id_olt','name','created_at'];
    use HasFactory;
    public function olt()
    {
        return $this->belongsTo('\App\Olt', 'id_olt')->withTrashed();
    }
}
