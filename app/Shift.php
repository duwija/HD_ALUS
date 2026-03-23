<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['name','start_time','end_time','late_tolerance','color','is_active','note'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function schedules()
    {
        return $this->hasMany(ShiftSchedule::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Hitung berapa menit terlambat berdasarkan jam clock-in.
     * Return 0 jika tepat waktu / lebih awal.
     */
    public function lateMinutes(string $clockIn): int
    {
        $limit = strtotime(date('Y-m-d') . ' ' . $this->start_time . ' +' . $this->late_tolerance . ' minutes');
        $actual = strtotime(date('Y-m-d') . ' ' . $clockIn);
        return max(0, (int) round(($actual - $limit) / 60));
    }
}
