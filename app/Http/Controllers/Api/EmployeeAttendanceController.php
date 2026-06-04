<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\User;
use App\Attendance;
use App\AttendanceLocation;
use App\LeaveRequest;
use App\ShiftSchedule;
use App\Shift;

/**
 * API Controller untuk Mobile App Karyawan
 * Base URL: /api/employee/
 *
 * Auth: Bearer token (Sanctum)
 */
class EmployeeAttendanceController extends Controller
{
    // ── Authentication ─────────────────────────────────────────────────────

    /**
     * POST /api/employee/login
     * Body: email, password, device_name
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required',
            'device_name' => 'required|string|max:200',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        if (isset($user->is_active_employee) && !$user->is_active_employee) {
            return response()->json([
                'success' => false,
                'message' => 'Akun karyawan tidak aktif.',
            ], 403);
        }

        // Hapus token lama untuk device ini
        $user->tokens()->where('name', $request->device_name)->delete();

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'          => $user->id,
                'name'        => $user->name,
                'full_name'   => $user->full_name,
                'email'       => $user->email,
                'phone'       => $user->phone,
                'photo'       => $this->photoUrl($user->photo),
                'job_title'   => $user->job_title,
                'employee_id' => $user->employee_id,
                'supervisor'  => optional($user->supervisor)->name,
            ],
        ]);
    }

    /**
     * POST /api/employee/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
    }

    // ── Profile ─────────────────────────────────────────────────────────────

    /**
     * GET /api/employee/profile
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load('supervisor');
        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $user->id,
                'name'         => $user->name,
                'full_name'    => $user->full_name,
                'email'        => $user->email,
                'phone'        => $user->phone,
                'photo'        => $this->photoUrl($user->photo),
                'job_title'    => $user->job_title,
                'employee_id'  => $user->employee_id,
                'join_date'    => $user->join_date,
                'supervisor'   => optional($user->supervisor)->name,
            ],
        ]);
    }

    // ── Shift & Schedule ────────────────────────────────────────────────────

    /**
     * GET /api/employee/shift/today
     */
    public function shiftToday(Request $request)
    {
        $user     = $request->user();
        $schedule = ShiftSchedule::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        if (!$schedule) {
            // Cari shift Office Hour (OH) sebagai default
            $shift = Shift::where('is_active', true)
                ->where(function($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%oh%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%office hour%']);
                })
                ->first()
                ?? Shift::where('is_active', true)->orderBy('id')->first();

            return response()->json([
                'success'       => true,
                'scheduled'     => false,
                'day_type'      => 'work',
                'shift'         => $shift ? $this->shiftData($shift) : null,
            ]);
        }

        return response()->json([
            'success'   => true,
            'scheduled' => true,
            'day_type'  => $schedule->day_type,
            'shift'     => $schedule->shift ? $this->shiftData($schedule->shift) : null,
        ]);
    }

    /**
     * GET /api/employee/schedule?month=YYYY-MM
     */
    public function schedule(Request $request)
    {
        $month     = $request->get('month', now()->format('Y-m'));
        [$year, $m] = explode('-', $month);

        // Ambil jadwal
        $schedules = ShiftSchedule::with('shift')
            ->where('user_id', $request->user()->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $m)
            ->orderBy('date')
            ->get();

        // Ambil kehadiran bulan yang sama sekaligus
        $attendances = Attendance::where('user_id', $request->user()->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $m)
            ->get()
            ->keyBy(fn($a) => $a->date?->format('Y-m-d'));

        $data = $schedules->map(function($s) use ($attendances) {
            $dateStr = is_string($s->date) ? $s->date : $s->date?->format('Y-m-d');
            $att     = $attendances->get($dateStr);
            return [
                'date'       => $dateStr,
                'day_type'   => $s->day_type,
                'shift'      => $s->shift ? $this->shiftData($s->shift) : null,
                'note'       => $s->note,
                'attendance' => $att ? [
                    'clock_in'  => $att->clock_in,
                    'clock_out' => $att->clock_out,
                    'status'    => $att->status,
                ] : null,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ── Locations ───────────────────────────────────────────────────────────

    /**
     * GET /api/employee/locations
     * Kembalikan semua titik absen aktif beserta koordinat dan radius.
     */
    public function locations()
    {
        $locations = AttendanceLocation::where('is_active', true)
            ->select('id','name','address','latitude','longitude','radius')
            ->get();

        return response()->json(['success' => true, 'data' => $locations]);
    }

    /**
     * POST /api/employee/location/check
     * Body: latitude, longitude
     * Cek apakah koordinat valid untuk absen.
     */
    public function checkLocation(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $result = AttendanceLocation::findNearest(
            (float) $request->latitude,
            (float) $request->longitude
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'valid'   => false,
                'message' => 'Anda berada di luar area absen yang diizinkan.',
            ]);
        }

        return response()->json([
            'success'  => true,
            'valid'    => true,
            'location' => [
                'id'       => $result['location']->id,
                'name'     => $result['location']->name,
                'distance' => $result['distance'] . ' meter',
            ],
        ]);
    }

    // ── Attendance ──────────────────────────────────────────────────────────

    /**
     * GET /api/employee/attendance/today
     */
    public function today(Request $request)
    {
        $attendance = Attendance::with(['shift','locationIn','locationOut'])
            ->where('user_id', $request->user()->id)
            ->whereDate('date', today())
            ->first();

        return response()->json([
            'success' => true,
            'data'    => $attendance ? $this->attendanceData($attendance) : null,
        ]);
    }

    /**
     * POST /api/employee/attendance/clock-in
     * Body: latitude, longitude, photo (base64 atau file), device_info (opsional)
     */
    public function clockIn(Request $request)
    {
        $request->validate([
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'photo'        => 'required',
            'device_info'  => 'nullable|string|max:200',
            'is_mock'      => 'nullable|boolean',
            'gps_accuracy' => 'required|numeric|min:0',
            'gps_altitude' => 'nullable|numeric',
            'gps_speed'    => 'nullable|numeric',
        ]);

        // Blokir jika client mendeteksi fake GPS
        if (filter_var($request->is_mock, FILTER_VALIDATE_BOOLEAN)) {
            \Log::warning("[FAKE-GPS] clock-in ditolak (is_mock) user={$request->user()->id} ({$request->user()->name}) ip={$request->ip()}");
            return response()->json(['success' => false, 'message' => 'Absensi ditolak: terdeteksi penggunaan GPS palsu atau emulator.'], 422);
        }

        // Validasi metadata GPS di sisi server
        if ($resp = $this->validateGpsMetadata($request)) return $resp;

        $user = $request->user();
        $today = today()->toDateString();

        if ($resp = $this->assertAttendanceAllowed($request)) {
            return $resp;
        }

        // Cek sudah clock-in hari ini?
        $existing = Attendance::where('user_id', $user->id)->whereDate('date', $today)->first();
        if ($existing?->clock_in) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah clock-in hari ini pukul ' . $existing->clock_in,
            ], 422);
        }

        // Validasi lokasi
        $locationResult = AttendanceLocation::findNearest(
            (float) $request->latitude,
            (float) $request->longitude
        );
        if (!$locationResult) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi tidak valid. Anda berada di luar area absen.',
            ], 422);
        }

        if ((int) $locationResult['distance'] > $locationResult['location']->radius) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi tidak valid. Anda berada di luar radius absensi.',
            ], 422);
        }

        if ($this->looksLikeFakeGps($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Absensi ditolak: data GPS mencurigakan. Pastikan lokasi perangkat asli.',
            ], 422);
        }

        $accuracy = (float) $request->gps_accuracy;
        $accuracyLimit = max(30, (int) round($locationResult['location']->radius / 2));
        if ($accuracy > $accuracyLimit) {
            return response()->json([
                'success' => false,
                'message' => 'Akurasi GPS terlalu rendah. Dekatkan ke lokasi dan coba lagi.',
            ], 422);
        }

        // Simpan foto selfie
        $photoPath = $this->savePhoto($request->photo, 'in', $user->id);

        // Ambil shift hari ini (fallback ke OH)
        $schedule = ShiftSchedule::with('shift')->where('user_id', $user->id)->whereDate('date', $today)->first();
        $shift    = $schedule?->shift ?? $this->resolveDefaultShift();

        // Hitung terlambat
        $clockInTime  = now()->format('H:i:s');
        $lateMinutes  = $shift ? $shift->lateMinutes($clockInTime) : 0;
        $status       = $lateMinutes > 0 ? 'late' : 'present';

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            [
                'shift_id'      => $shift?->id,
                'location_id_in'=> $locationResult['location']->id,
                'clock_in'      => $clockInTime,
                'lat_in'        => $request->latitude,
                'lng_in'        => $request->longitude,
                'photo_in'      => $photoPath,
                'distance_in'   => $locationResult['distance'],
                'status'        => $status,
                'late_minutes'  => $lateMinutes,
                'device_info'   => $request->device_info,
                'is_mock_in'    => false,
            ]
        );

        return response()->json([
            'success'      => true,
            'message'      => $lateMinutes > 0
                ? "Clock-in berhasil. Anda terlambat {$lateMinutes} menit."
                : 'Clock-in berhasil. Selamat bekerja!',
            'clock_in'     => $clockInTime,
            'late_minutes' => $lateMinutes,
            'location'     => $locationResult['location']->name,
            'distance'     => $locationResult['distance'] . ' meter',
        ]);
    }

    /**
     * POST /api/employee/attendance/clock-out
     * Body: latitude, longitude, photo
     */
    public function clockOut(Request $request)
    {
        $request->validate([
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'photo'        => 'required',
            'is_mock'      => 'nullable|boolean',
            'gps_accuracy' => 'required|numeric|min:0',
            'gps_altitude' => 'nullable|numeric',
            'gps_speed'    => 'nullable|numeric',
        ]);

        // Blokir jika client mendeteksi fake GPS
        if (filter_var($request->is_mock, FILTER_VALIDATE_BOOLEAN)) {
            \Log::warning("[FAKE-GPS] clock-out ditolak (is_mock) user={$request->user()->id} ({$request->user()->name}) ip={$request->ip()}");
            return response()->json(['success' => false, 'message' => 'Absensi ditolak: terdeteksi penggunaan GPS palsu atau emulator.'], 422);
        }

        // Validasi metadata GPS di sisi server
        if ($resp = $this->validateGpsMetadata($request)) return $resp;

        $user = $request->user();
        $today = today()->toDateString();

        if ($resp = $this->assertAttendanceAllowed($request)) {
            return $resp;
        }

        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', $today)->first();
        if (!$attendance?->clock_in) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum melakukan clock-in hari ini.',
            ], 422);
        }
        if ($attendance->clock_out) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah clock-out hari ini pukul ' . $attendance->clock_out,
            ], 422);
        }

        // Validasi lokasi
        $locationResult = AttendanceLocation::findNearest(
            (float) $request->latitude,
            (float) $request->longitude
        );
        if (!$locationResult) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi tidak valid. Anda berada di luar area absen.',
            ], 422);
        }

        if ((int) $locationResult['distance'] > $locationResult['location']->radius) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi tidak valid. Anda berada di luar radius absensi.',
            ], 422);
        }

        if ($this->looksLikeFakeGps($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Absensi ditolak: data GPS mencurigakan. Pastikan lokasi perangkat asli.',
            ], 422);
        }

        $accuracy = (float) $request->gps_accuracy;
        $accuracyLimit = max(30, (int) round($locationResult['location']->radius / 2));
        if ($accuracy > $accuracyLimit) {
            return response()->json([
                'success' => false,
                'message' => 'Akurasi GPS terlalu rendah. Dekatkan ke lokasi dan coba lagi.',
            ], 422);
        }

        $photoPath   = $this->savePhoto($request->photo, 'out', $user->id);
        $clockOutTime = now()->format('H:i:s');

        $attendance->update([
            'location_id_out' => $locationResult['location']->id,
            'clock_out'       => $clockOutTime,
            'lat_out'         => $request->latitude,
            'lng_out'         => $request->longitude,
            'photo_out'       => $photoPath,
            'distance_out'    => $locationResult['distance'],
            'is_mock_out'     => false,
        ]);

        // Hitung total jam kerja
        $workMinutes = $attendance->calculateWorkMinutes();
        $attendance->update(['work_minutes' => $workMinutes]);

        $hours   = intdiv($workMinutes, 60);
        $minutes = $workMinutes % 60;

        return response()->json([
            'success'      => true,
            'message'      => "Clock-out berhasil. Total kerja: {$hours}j {$minutes}m.",
            'clock_out'    => $clockOutTime,
            'work_minutes' => $workMinutes,
            'location'     => $locationResult['location']->name,
        ]);
    }

    /**
     * GET /api/employee/attendance/history?month=YYYY-MM
     */
    public function history(Request $request)
    {
        $month      = $request->get('month', now()->format('Y-m'));
        [$year, $m] = explode('-', $month);

        $records = Attendance::with(['shift','locationIn'])
            ->where('user_id', $request->user()->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $m)
            ->orderByDesc('date')
            ->get()
            ->map(fn($a) => $this->attendanceData($a));

        // Ringkasan bulan
        $summary = [
            'present'  => $records->whereIn('status', ['present','late'])->count(),
            'late'     => $records->where('status','late')->count(),
            'absent'   => $records->where('status','absent')->count(),
            'leave'    => $records->where('status','leave')->count(),
            'total_work_minutes' => $records->sum('work_minutes'),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'data'    => $records,
        ]);
    }

    // ── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Validasi metadata GPS dari perangkat.
     * Fake GPS apps biasanya menghasilkan nilai yang tidak alami:
     *  - accuracy terlalu kecil / sempurna (< 1m)
     *  - altitude persis 0.0 dan speed persis 0.0 bersamaan
     *  - accuracy bulat sempurna seperti 5.0000 tanpa noise
     */
    private function validateGpsMetadata(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $accuracy = (float) ($request->gps_accuracy ?? 999);
        $altitude = (float) ($request->gps_altitude ?? 999);
        $speed    = (float) ($request->gps_speed    ?? 999);
        $userId   = $request->user()->id;
        $userName = $request->user()->name;
        $ip       = $request->ip();

        // Akurasi < 1m adalah tidak mungkin untuk GPS perangkat biasa
        if ($accuracy > 0 && $accuracy < 1.0) {
            \Log::warning("[FAKE-GPS] akurasi tidak wajar accuracy={$accuracy} user={$userId} ({$userName}) ip={$ip}");
            return response()->json(['success' => false,
                'message' => 'Absensi ditolak: data GPS tidak valid (akurasi tidak wajar).'], 422);
        }

        // Altitude == 0.0 tepat DAN speed == 0.0 tepat DAN accuracy < 10 → sangat umum pada fake GPS
        if ($altitude == 0.0 && $speed == 0.0 && $accuracy > 0 && $accuracy < 10.0) {
            \Log::warning("[FAKE-GPS] kombinasi altitude+speed+accuracy mencurigakan accuracy={$accuracy} altitude={$altitude} speed={$speed} user={$userId} ({$userName}) ip={$ip}");
            return response()->json(['success' => false,
                'message' => 'Absensi ditolak: data GPS mencurigakan. Pastikan GPS aktif dan tidak ada aplikasi GPS palsu.'], 422);
        }

        return null; // OK
    }

    private function assertAttendanceAllowed(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $today = today()->toDateString();

        $leaveToday = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();

        if ($leaveToday) {
            return response()->json([
                'success' => false,
                'message' => 'Hari ini Anda sedang cuti/izin yang sudah disetujui.',
            ], 422);
        }

        $scheduleToday = ShiftSchedule::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($scheduleToday && in_array($scheduleToday->day_type, ['off', 'holiday', 'leave'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Hari ini tidak dibuka untuk absensi sesuai jadwal.',
            ], 422);
        }

        return null;
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

    private function resolveDefaultShift(): ?Shift
    {
        return Shift::where('is_active', true)
            ->where(function($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%oh%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%office hour%']);
            })->first()
            ?? Shift::where('is_active', true)->orderBy('id')->first();
    }

    private function photoUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function shiftData(Shift $shift): array
    {
        return [
            'id'             => $shift->id,
            'name'           => $shift->name,
            'start_time'     => $shift->start_time,
            'end_time'       => $shift->end_time,
            'late_tolerance' => $shift->late_tolerance,
            'color'          => $shift->color,
        ];
    }

    private function attendanceData(Attendance $a): array
    {
        return [
            'id'           => $a->id,
            'date'         => $a->date?->format('Y-m-d'),
            'shift'        => $a->shift ? $a->shift->name : null,
            'clock_in'     => $a->clock_in,
            'clock_out'    => $a->clock_out,
            'status'       => $a->status,
            'late_minutes' => $a->late_minutes,
            'work_minutes' => $a->work_minutes,
            'location_in'  => optional($a->locationIn)->name,
            'distance_in'  => $a->distance_in,
            'photo_in'     => $this->photoUrl($a->photo_in),
            'photo_out'    => $this->photoUrl($a->photo_out),
        ];
    }

    /**
     * Simpan foto selfie (base64 atau UploadedFile).
     */
    private function savePhoto($photo, string $type, int $userId): string
    {
        $dir     = 'attendances/' . now()->format('Y/m') . '/' . $userId;
        $filename = $type . '_' . now()->format('His') . '_' . $userId . '.jpg';

        if (is_string($photo) && str_contains($photo, 'base64,')) {
            // base64 encoded
            $base64 = explode(',', $photo, 2)[1];
            Storage::disk('public')->put($dir . '/' . $filename, base64_decode($base64));
        } elseif ($photo instanceof \Illuminate\Http\UploadedFile) {
            $photo->storeAs($dir, $filename, 'public');
        } else {
            // raw base64 tanpa header
            Storage::disk('public')->put($dir . '/' . $filename, base64_decode($photo));
        }

        return $dir . '/' . $filename;
    }
}
