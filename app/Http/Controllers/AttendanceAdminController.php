<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Attendance;
use App\AttendanceLocation;
use App\LeaveRequest;
use App\OvertimeRequest;
use App\Shift;
use App\ShiftSchedule;
use App\User;
use Carbon\Carbon;

class AttendanceAdminController extends Controller
{
    // ── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard(Request $request)
    {
        $month  = $request->get('month', now()->format('Y-m'));
        [$year, $m] = explode('-', $month);

        $totalEmployees = User::where('is_active_employee', true)->count();

        // ── Statistik bulan ini ──────────────────────────────────────────
        $monthAtt = Attendance::whereYear('date', $year)->whereMonth('date', $m)->get();
        $stats = [
            'present'  => $monthAtt->whereIn('status', ['present', 'late'])->count(),
            'late'     => $monthAtt->where('status', 'late')->count(),
            'absent'   => $monthAtt->where('status', 'absent')->count(),
            'leave'    => $monthAtt->where('status', 'leave')->count(),
            'work_hours'=> round($monthAtt->sum('work_minutes') / 60, 1),
        ];

        // ── Hari ini ─────────────────────────────────────────────────────
        $todayAtt   = Attendance::with(['user', 'shift'])->whereDate('date', today())->get();
        $clockedIn  = $todayAtt->whereNotNull('clock_in')->count();
        $clockedOut = $todayAtt->whereNotNull('clock_out')->count();
        $notYet     = $totalEmployees - $clockedIn;
        $late       = $todayAtt->where('status', 'late')->count();

        // ── Pending approvals ─────────────────────────────────────────────
        $pendingLeave    = LeaveRequest::where('status', 'pending')->count();
        $pendingOvertime = OvertimeRequest::where('status', 'pending')->count();

        // ── Izin/Cuti bulan ini ───────────────────────────────────────────
        $leaveSummary = LeaveRequest::whereRaw("DATE_FORMAT(start_date,'%Y-%m') = ?", [$month])
            ->selectRaw('type, status, COUNT(*) as total')
            ->groupBy('type', 'status')
            ->get();

        // ── Lembur bulan ini ──────────────────────────────────────────────
        $overtimeSummary = OvertimeRequest::whereRaw("DATE_FORMAT(date,'%Y-%m') = ?", [$month])
            ->where('status', 'approved')
            ->sum('duration_hours');
        $overtimePending = OvertimeRequest::whereRaw("DATE_FORMAT(date,'%Y-%m') = ?", [$month])
            ->where('status', 'pending')->count();

        // ── Tren 14 hari terakhir ──────────────────────────────────────────
        $trendDays  = 14;
        $trend      = [];
        for ($i = $trendDays - 1; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $ds  = $day->format('Y-m-d');
            $att = Attendance::whereDate('date', $ds)->get();
            $trend[] = [
                'date'    => $day->isoFormat('D MMM'),
                'present' => $att->whereIn('status', ['present', 'late'])->count(),
                'late'    => $att->where('status', 'late')->count(),
                'absent'  => $att->where('status', 'absent')->count(),
            ];
        }

        // ── 10 pengajuan leave terbaru ────────────────────────────────────
        $latestLeaves    = LeaveRequest::with('user')->orderByDesc('created_at')->limit(6)->get();
        $latestOvertimes = OvertimeRequest::with('user')->orderByDesc('created_at')->limit(6)->get();

        // ── Karyawan yang belum absen hari ini ────────────────────────────
        $today        = Carbon::today()->toDateString();
        $presentIds   = $todayAtt->whereNotNull('clock_in')->pluck('user_id');

        // Karyawan dengan attendance status off/leave/holiday hari ini
        $offTodayIds  = $todayAtt->whereIn('status', ['off', 'leave', 'holiday'])->pluck('user_id');

        // Karyawan dengan cuti/izin approved yang mencakup hari ini
        $onLeaveIds   = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date',   '>=', $today)
            ->pluck('user_id');

        $excludeIds   = $presentIds->merge($offTodayIds)->merge($onLeaveIds)->unique();

        $notCheckedIn = User::where('is_active_employee', true)
            ->whereNotIn('id', $excludeIds)
            ->orderBy('name')->limit(8)->get();

        // Update notYet to reflect exclusions
        $notYet = User::where('is_active_employee', true)
            ->whereNotIn('id', $excludeIds)
            ->count();

        return view('attendance.dashboard', compact(
            'month', 'totalEmployees', 'stats', 'todayAtt',
            'clockedIn', 'clockedOut', 'notYet', 'late',
            'pendingLeave', 'pendingOvertime',
            'leaveSummary', 'overtimeSummary', 'overtimePending',
            'trend', 'latestLeaves', 'latestOvertimes', 'notCheckedIn'
        ));
    }

    // ── Lokasi Absen ────────────────────────────────────────────────────────

    public function locations()
    {
        $locations = AttendanceLocation::withCount('attendances')->orderBy('name')->get();
        return view('attendance.locations.index', compact('locations'));
    }

    public function locationCreate()
    {
        return view('attendance.locations.form', ['location' => new AttendanceLocation]);
    }

    public function locationStore(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'address'   => 'nullable|string',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius'    => 'required|integer|min:10|max:5000',
            'is_active' => 'boolean',
            'note'      => 'nullable|string',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        AttendanceLocation::create($data);
        return redirect('attendance/locations')->with('success', 'Lokasi absen berhasil ditambahkan.');
    }

    public function locationEdit(AttendanceLocation $location)
    {
        return view('attendance.locations.form', compact('location'));
    }

    public function locationUpdate(Request $request, AttendanceLocation $location)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'address'   => 'nullable|string',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius'    => 'required|integer|min:10|max:5000',
            'is_active' => 'boolean',
            'note'      => 'nullable|string',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $location->update($data);
        return redirect('attendance/locations')->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function locationDestroy(AttendanceLocation $location)
    {
        $location->delete();
        return redirect('attendance/locations')->with('success', 'Lokasi dihapus.');
    }

    // ── Shift ────────────────────────────────────────────────────────────────

    public function shifts()
    {
        $shifts = Shift::orderBy('start_time')->get();
        return view('attendance.shifts.index', compact('shifts'));
    }

    public function shiftCreate()
    {
        return view('attendance.shifts.form', ['shift' => new Shift]);
    }

    public function shiftStore(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'start_time'     => 'required',
            'end_time'       => 'required',
            'late_tolerance' => 'integer|min:0|max:120',
            'color'          => 'nullable|string|max:20',
            'is_active'      => 'boolean',
            'note'           => 'nullable|string',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['color']     = $data['color'] ?? '#3498db';
        Shift::create($data);
        return redirect('attendance/shifts')->with('success', 'Shift berhasil ditambahkan.');
    }

    public function shiftEdit(Shift $shift)
    {
        return view('attendance.shifts.form', compact('shift'));
    }

    public function shiftUpdate(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'start_time'     => 'required',
            'end_time'       => 'required',
            'late_tolerance' => 'integer|min:0|max:120',
            'color'          => 'nullable|string|max:20',
            'is_active'      => 'boolean',
            'note'           => 'nullable|string',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $shift->update($data);
        return redirect('attendance/shifts')->with('success', 'Shift diperbarui.');
    }

    public function shiftDestroy(Shift $shift)
    {
        $shift->delete();
        return redirect('attendance/shifts')->with('success', 'Shift dihapus.');
    }

    // ── Jadwal Shift ────────────────────────────────────────────────────────

    public function schedule(Request $request)
    {
        $month     = $request->get('month', now()->format('Y-m'));
        [$year, $m] = explode('-', $month);

        $employees = User::where('is_active_employee', true)->orderBy('name')->get();
        $shifts    = Shift::where('is_active', true)->orderBy('start_time')->get();

        $schedules = ShiftSchedule::with(['user','shift'])
            ->whereYear('date', $year)
            ->whereMonth('date', $m)
            ->get()
            ->groupBy('user_id');

        // Ambil data absensi bulan ini, digroup user_id → date
        $attendances = Attendance::whereYear('date', $year)
            ->whereMonth('date', $m)
            ->get()
            ->groupBy('user_id')
            ->map(fn($rows) => $rows->keyBy(fn($a) => $a->date?->format('Y-m-d')));

        return view('attendance.schedule', compact('employees','shifts','schedules','month','year','m','attendances'));
    }

    public function scheduleStore(Request $request)
    {
        $request->validate([
            'user_id'  => 'required|exists:users,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'date'     => 'required|date',
            'day_type' => 'required|in:work,off,holiday,leave',
            'note'     => 'nullable|string',
        ]);

        ShiftSchedule::updateOrCreate(
            ['user_id' => $request->user_id, 'date' => $request->date],
            [
                'shift_id'   => $request->shift_id,
                'day_type'   => $request->day_type,
                'note'       => $request->note,
                'created_by' => auth()->id(),
            ]
        );

        return response()->json(['success' => true]);
    }

    // ── Rekap Absensi ───────────────────────────────────────────────────────

    public function report(Request $request)
    {
        $month     = $request->get('month', now()->format('Y-m'));
        [$year, $m] = explode('-', $month);
        $userId    = $request->get('user_id');

        $query = Attendance::with(['user','shift','locationIn'])
            ->whereYear('date', $year)
            ->whereMonth('date', $m);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $records    = $query->orderBy('date')->orderBy('user_id')->get();
        $employees  = User::where('is_active_employee', true)->orderBy('name')->get();

        // Ringkasan per karyawan
        $summary = $records->groupBy('user_id')->map(function($rows) {
            return [
                'present'           => $rows->whereIn('status',['present','late'])->count(),
                'late'              => $rows->where('status','late')->count(),
                'absent'            => $rows->where('status','absent')->count(),
                'leave'             => $rows->where('status','leave')->count(),
                'total_work_minutes'=> $rows->sum('work_minutes'),
            ];
        });

        return view('attendance.report', compact('records','employees','summary','month','userId'));
    }

    // ── Detail Absensi Harian ───────────────────────────────────────────────

    public function daily(Request $request)
    {
        $date   = $request->get('date', today()->format('Y-m-d'));
        $records = Attendance::with(['user','shift','locationIn'])
            ->whereDate('date', $date)
            ->orderBy('clock_in')
            ->get();

        return view('attendance.daily', compact('records','date'));
    }

    // ── Kelola Supervisor di User ───────────────────────────────────────────

    public function employees()
    {
        $employees  = User::with('supervisor')->orderBy('name')->get();
        $supervisors = User::orderBy('name')->get();
        return view('attendance.employees', compact('employees','supervisors'));
    }

    public function employeeUpdate(Request $request, User $user)
    {
        $data = $request->validate([
            'supervisor_id'     => 'nullable|exists:users,id',
            'employee_id'       => 'nullable|string|max:50',
            'is_active_employee'=> 'boolean',
        ]);
        $data['is_active_employee'] = $request->boolean('is_active_employee', true);
        $user->update($data);
        return redirect('attendance/employees')->with('success', "Data karyawan [{$user->name}] diperbarui.");
    }
}
