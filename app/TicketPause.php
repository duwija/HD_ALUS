<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketPause extends Model
{
    protected $fillable = ['ticket_id', 'reason', 'paused_by', 'paused_at', 'resumed_by', 'resumed_at'];

    protected $dates = ['paused_at', 'resumed_at'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function isActive(): bool
    {
        return is_null($this->resumed_at);
    }

    public function durationMinutes(): ?int
    {
        if (!$this->resumed_at) return null;
        return (int) $this->paused_at->diffInMinutes($this->resumed_at);
    }
}
