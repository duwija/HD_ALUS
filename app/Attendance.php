<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id','date','shift_id',
        'location_id_in','location_id_out',
        'clock_in','lat_in','lng_in','photo_in','distance_in',
        'clock_out','lat_out','lng_out','photo_out','distance_out',
        'status','late_minutes','work_minutes','device_info','note',
        'is_mock_in','is_mock_out',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function locationIn()
    {
        return $this->belongsTo(AttendanceLocation::class, 'location_id_in');
    }

    public function locationOut()
    {
        return $this->belongsTo(AttendanceLocation::class, 'location_id_out');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function statusBadge(): string
    {
        $map = [
            'present'  => '<span class="badge badge-success">Hadir</span>',
            'late'     => '<span class="badge badge-warning">Terlambat</span>',
            'absent'   => '<span class="badge badge-danger">Absen</span>',
            'leave'    => '<span class="badge badge-info">Izin/Sakit</span>',
            'holiday'  => '<span class="badge badge-secondary">Libur</span>',
            'off'      => '<span class="badge badge-dark">Off</span>',
        ];
        return $map[$this->status] ?? '<span class="badge badge-light">' . $this->status . '</span>';
    }

    /**
     * Hitung total menit kerja dari clock_in ke clock_out.
     */
    public function calculateWorkMinutes(): int
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }
        $in  = strtotime(date('Y-m-d') . ' ' . $this->clock_in);
        $out = strtotime(date('Y-m-d') . ' ' . $this->clock_out);
        return max(0, (int) round(($out - $in) / 60));
    }

    /**
     * Format jam kerja: "8j 30m"
     */
    public function workHoursFormatted(): string
    {
        $minutes = $this->work_minutes ?? 0;
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return ($h > 0 ? "{$h}j " : '') . "{$m}m";
    }
}
