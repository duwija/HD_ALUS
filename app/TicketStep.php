<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketStep extends Model
{
    protected $fillable = ['ticket_id', 'name', 'position'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
