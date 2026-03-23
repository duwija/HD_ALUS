<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MyAttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user   = Auth::user();
        $uid    = $user->id;

        // Default: current month
        $month  = $request->input('month', date('Y-m'));
        $start  = Carbon::parse($month . '-01')->startOfMonth();
        $end    = Carbon::parse($month . '-01')->endOfMonth();

        // ── Absensi bulan ini ────────────────────────
        $attendances = \App\Attendance::where('user_id', $uid)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('shift')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy(function ($a) {
                return Carbon::parse($a->date)->toDateString();
            });

        // ── Jadwal shift bulan ini ───────────────────
        $schedules = \App\ShiftSchedule::where('user_id', $uid)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('shift')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy(function ($s) {
                return Carbon::parse($s->date)->toDateString();
            });

        // ── Rekap summary bulan ini ──────────────────
        $summary = [
            'hadir'   => $attendances->whereIn('status', ['present', 'late'])->count(),
            'late'    => $attendances->where('status', 'late')->count(),
            'absent'  => $attendances->where('status', 'absent')->count(),
            'leave'   => $attendances->whereIn('status', ['leave', 'off', 'holiday'])->count(),
            'total'   => 0, // dihitung dari hari kerja di bulan tsb
        ];
        // Total hari kerja (tidak termasuk Sabtu & Minggu)
        $workdays = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (!$d->isWeekend()) $workdays++;
        }
        $summary['total'] = $workdays;

        // ── Hari ini ─────────────────────────────────
        $todayStr    = Carbon::today()->toDateString();
        $todayAtt    = $attendances->get($todayStr);
        $todaySched  = $schedules->get($todayStr);

        // ── Rata-rata jam kerja bulan ini ─────────────
        $workMinutes = $attendances->whereNotNull('work_minutes')->sum('work_minutes');
        $attCount    = $attendances->whereNotNull('work_minutes')->count();
        $avgWorkMin  = $attCount > 0 ? round($workMinutes / $attCount) : 0;

        // ── Izin & Lembur bulan ini ───────────────────
        $leaveMonth    = \App\LeaveRequest::where('user_id', $uid)
            ->where('status', 'approved')
            ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
            ->get();
        $overtimeMonth = \App\OvertimeRequest::where('user_id', $uid)
            ->where('status', 'approved')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        // ── Build calendar grid ───────────────────────
        // Semua hari dalam bulan
        $calDays = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $ds  = $d->toDateString();
            $att = $attendances->get($ds);
            $sc  = $schedules->get($ds);
            $calDays[] = [
                'date'    => $ds,
                'day'     => $d->day,
                'weekday' => $d->dayOfWeek, // 0=Sun,6=Sat
                'isToday' => $ds === $todayStr,
                'att'     => $att,
                'sched'   => $sc,
            ];
        }

        return view('attendance.my', compact(
            'user', 'month', 'start', 'end',
            'attendances', 'schedules', 'calDays',
            'summary', 'workdays', 'avgWorkMin',
            'todayAtt', 'todaySched',
            'leaveMonth', 'overtimeMonth'
        ));
    }
}
