<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticketcategorie extends Model
{
    use SoftDeletes;

    protected $casts = [
        'workflow' => 'array',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Get tickets using this category
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'id_categori');
    }
}
