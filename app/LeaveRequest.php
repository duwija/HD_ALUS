<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id', 'type', 'start_date', 'end_date', 'days',
        'reason', 'attachment', 'status', 'approved_by', 'approved_at', 'approval_notes',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getTypeTextAttribute(): string
    {
        return match ($this->type) {
            'cuti'         => 'Cuti',
            'sakit'        => 'Sakit',
            'izin_lainnya' => 'Izin Lainnya',
            default        => ucfirst($this->type),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default    => 'Menunggu',
        };
    }
}
