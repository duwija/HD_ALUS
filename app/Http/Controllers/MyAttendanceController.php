<?php

namespace App\Http\Controllers;

use App\Attendance;
use App\AttendanceLocation;
use App\LeaveRequest;
use App\Shift;
use App\ShiftSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
        $todayLeave   = LeaveRequest::where('user_id', $uid)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $todayStr)
            ->whereDate('end_date', '>=', $todayStr)
            ->first();

        $attendanceLockReason = null;
        if ($todayLeave) {
            $attendanceLockReason = 'Hari ini Anda memiliki cuti/izin yang sudah disetujui.';
        } elseif ($todaySched && in_array($todaySched->day_type, ['off', 'holiday', 'leave'], true)) {
            $attendanceLockReason = 'Hari ini bukan hari kerja aktif untuk jadwal Anda.';
        }

        $activeLocations = AttendanceLocation::where('is_active', true)
            ->orderBy('name')
            ->get();

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
            'todayAtt', 'todaySched', 'todayLeave',
            'attendanceLockReason', 'activeLocations',
            'leaveMonth', 'overtimeMonth'
        ));
    }

    public function clockIn(Request $request)
    {
        return $this->submitAttendance($request, 'clock-in');
    }

    public function clockOut(Request $request)
    {
        return $this->submitAttendance($request, 'clock-out');
    }

    private function submitAttendance(Request $request, string $action)
    {
        $request->validate([
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'gps_accuracy' => 'required|numeric|min:0',
            'gps_altitude' => 'nullable|numeric',
            'gps_speed'    => 'nullable|numeric',
            'device_info'  => 'nullable|string|max:255',
            'photo_base64' => 'required|string',
            'is_mock'      => 'nullable|boolean',
            'month'        => 'nullable|string|max:7',
        ]);

        if ($request->boolean('is_mock')) {
            return $this->attendanceResponse($request, false, 'Absensi ditolak: terdeteksi GPS palsu atau emulator.');
        }

        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $todaySchedule = ShiftSchedule::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $todayLeave = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        if ($todayLeave) {
            return $this->attendanceResponse($request, false, 'Hari ini Anda sedang cuti/izin yang sudah disetujui.');
        }

        if ($todaySchedule && in_array($todaySchedule->day_type, ['off', 'holiday', 'leave'], true)) {
            return $this->attendanceResponse($request, false, 'Hari ini tidak dibuka untuk absensi sesuai jadwal.');
        }

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($action === 'clock-in' && $attendance?->clock_in) {
            return $this->attendanceResponse($request, false, 'Anda sudah clock-in hari ini pukul ' . $attendance->clock_in);
        }

        if ($action === 'clock-out') {
            if (!$attendance?->clock_in) {
                return $this->attendanceResponse($request, false, 'Anda belum melakukan clock-in hari ini.');
            }

            if ($attendance->clock_out) {
                return $this->attendanceResponse($request, false, 'Anda sudah clock-out hari ini pukul ' . $attendance->clock_out);
            }
        }

        $locationResult = AttendanceLocation::findNearest(
            (float) $request->latitude,
            (float) $request->longitude
        );

        if (!$locationResult) {
            return $this->attendanceResponse($request, false, 'Lokasi tidak valid. Anda berada di luar radius absensi.');
        }

        $location = $locationResult['location'];
        $distance = (int) $locationResult['distance'];
        $accuracy = (float) $request->gps_accuracy;
        $radiusLimit = max(30, (int) round($location->radius / 2));

        if ($distance > $location->radius) {
            return $this->attendanceResponse($request, false, 'Lokasi tidak valid. Anda berada di luar radius absensi.');
        }

        if ($accuracy > $radiusLimit) {
            return $this->attendanceResponse($request, false, 'Akurasi GPS terlalu rendah. Dekatkan ke lokasi dan coba lagi.');
        }

        if ($this->looksLikeFakeGps($request)) {
            return $this->attendanceResponse($request, false, 'Absensi ditolak: data GPS mencurigakan. Pastikan lokasi perangkat asli.');
        }

        $photoPath = $this->savePhoto($request->photo_base64, $action === 'clock-in' ? 'in' : 'out', $user->id);
        $clockTime  = Carbon::now()->format('H:i:s');

        if ($action === 'clock-in') {
            $shift = $todaySchedule?->shift ?? $this->resolveDefaultShift();
            $lateMinutes = $shift ? $shift->lateMinutes($clockTime) : 0;
            $status = $lateMinutes > 0 ? 'late' : 'present';

            $attendance = Attendance::updateOrCreate(
                ['user_id' => $user->id, 'date' => $today],
                [
                    'shift_id'       => $shift?->id,
                    'location_id_in' => $location->id,
                    'clock_in'       => $clockTime,
                    'lat_in'         => $request->latitude,
                    'lng_in'         => $request->longitude,
                    'photo_in'       => $photoPath,
                    'distance_in'    => $distance,
                    'status'         => $status,
                    'late_minutes'   => $lateMinutes,
                    'device_info'    => $request->device_info,
                    'is_mock_in'     => false,
                ]
            );

            $message = $lateMinutes > 0
                ? "Clock-in berhasil. Anda terlambat {$lateMinutes} menit."
                : 'Clock-in berhasil. Selamat bekerja!';

            return $this->attendanceResponse($request, true, $message);
        }

        $attendance->update([
            'location_id_out' => $location->id,
            'clock_out'       => $clockTime,
            'lat_out'         => $request->latitude,
            'lng_out'         => $request->longitude,
            'photo_out'       => $photoPath,
            'distance_out'    => $distance,
            'is_mock_out'     => false,
        ]);

        $workMinutes = $attendance->calculateWorkMinutes();
        $attendance->update(['work_minutes' => $workMinutes]);

        $hours   = intdiv($workMinutes, 60);
        $minutes = $workMinutes % 60;

        return $this->attendanceResponse(
            $request,
            true,
            "Clock-out berhasil. Total kerja: {$hours}j {$minutes}m."
        );
    }

    private function attendanceResponse(Request $request, bool $success, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
            ], $success ? 200 : 422);
        }

        $flashKey = $success ? 'success' : 'error';
        return redirect()
            ->route('my.attendance', ['month' => $request->input('month', now()->format('Y-m'))])
            ->with($flashKey, $message);
    }

    private function resolveDefaultShift(): ?Shift
    {
        return Shift::where('is_active', true)
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%oh%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%office hour%']);
            })
            ->first()
            ?? Shift::where('is_active', true)->orderBy('id')->first();
    }

    private function looksLikeFakeGps(Request $request): bool
    {
        $accuracy = (float) $request->gps_accuracy;
        $altitude = $request->filled('gps_altitude') ? (float) $request->gps_altitude : null;
        $speed    = $request->filled('gps_speed') ? (float) $request->gps_speed : null;

        if ($accuracy > 0 && $accuracy < 1.0) {
            return true;
        }

        if ($altitude === 0.0 && $speed === 0.0 && $accuracy > 0 && $accuracy < 10.0) {
            return true;
        }

        return false;
    }

    /**
     * Simpan foto selfie ke storage tenant (disk public sudah diarahkan tenant middleware).
     */
    private function savePhoto($photo, string $type, int $userId): string
    {
        $dir = 'attendances/' . now()->format('Y/m') . '/' . $userId;

        if (is_string($photo) && str_contains($photo, 'base64,')) {
            $base64 = explode(',', $photo, 2)[1];
        } else {
            $base64 = (string) $photo;
        }

        $filename = $type . '_' . now()->format('His') . '_' . $userId . '.jpg';
        Storage::disk('public')->put($dir . '/' . $filename, base64_decode($base64));

        return $dir . '/' . $filename;
    }
}
