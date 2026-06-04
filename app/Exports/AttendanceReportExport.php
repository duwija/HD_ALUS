<?php

namespace App\Exports;

use App\Attendance;
use App\LeaveRequest;
use App\ShiftSchedule;
use App\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class AttendanceReportExport implements FromView
{
    protected string $month;
    protected $userId;

    public function __construct(string $month, $userId = null)
    {
        $this->month = $month;
        $this->userId = $userId;
    }

    public function view(): View
    {
        [$year, $m] = explode('-', $this->month);
        $monthStart = Carbon::parse($this->month . '-01')->startOfMonth();
        $monthEnd = Carbon::parse($this->month . '-01')->endOfMonth();

        $employees = User::where('is_active_employee', true)->orderBy('name')->get();
        $targetEmployees = $this->userId
            ? $employees->where('id', (int) $this->userId)->values()
            : $employees->values();
        $targetEmployeeIds = $targetEmployees->pluck('id')->all();

        $records = Attendance::with(['user', 'shift', 'locationIn'])
            ->whereYear('date', $year)
            ->whereMonth('date', $m)
            ->when(!empty($targetEmployeeIds), function ($query) use ($targetEmployeeIds) {
                $query->whereIn('user_id', $targetEmployeeIds);
            })
            ->orderBy('date')
            ->orderBy('user_id')
            ->get();

        $attendanceByUserDate = $records
            ->groupBy('user_id')
            ->map(function ($rows) {
                return $rows->keyBy(function ($row) {
                    return Carbon::parse($row->date)->toDateString();
                });
            });

        $schedulesByUserDate = collect();
        if (!empty($targetEmployeeIds)) {
            $schedulesByUserDate = ShiftSchedule::whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->whereIn('user_id', $targetEmployeeIds)
                ->get()
                ->groupBy('user_id')
                ->map(function ($rows) {
                    return $rows->keyBy(function ($row) {
                        return Carbon::parse($row->date)->toDateString();
                    });
                });
        }

        $leaveDaysByUser = [];
        if (!empty($targetEmployeeIds)) {
            $approvedLeaves = LeaveRequest::where('status', 'approved')
                ->whereIn('user_id', $targetEmployeeIds)
                ->whereDate('start_date', '<=', $monthEnd->toDateString())
                ->whereDate('end_date', '>=', $monthStart->toDateString())
                ->get();

            foreach ($approvedLeaves as $leave) {
                $from = Carbon::parse($leave->start_date)->startOfDay();
                $to = Carbon::parse($leave->end_date)->startOfDay();

                if ($from->lt($monthStart)) {
                    $from = $monthStart->copy();
                }
                if ($to->gt($monthEnd)) {
                    $to = $monthEnd->copy();
                }

                for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
                    $leaveDaysByUser[$leave->user_id][$day->toDateString()] = ['type' => $leave->type];
                }
            }
        }

        $summary = collect();
        $calendarDays = [];
        $calendarUser = !empty($targetEmployeeIds) ? $targetEmployees->first() : null;
        $month = $this->month;

        foreach ($targetEmployees as $employee) {
            $stats = [
                'attendance'         => 0,
                'late'               => 0,
                'cuti'               => 0,
                'sakit'              => 0,
                'izin'               => 0,
                'libur'              => 0,
                'tanpa_keterangan'   => 0,
                'total_work_minutes' => 0,
            ];

            for ($day = $monthStart->copy(); $day->lte($monthEnd); $day->addDay()) {
                $ds = $day->toDateString();
                $attendance = optional($attendanceByUserDate->get($employee->id))->get($ds);
                $schedule = optional($schedulesByUserDate->get($employee->id))->get($ds);
                $leaveDay = $leaveDaysByUser[$employee->id][$ds] ?? null;

                $statusKey = 'tanpa_keterangan';
                $label = 'Tanpa keterangan';

                if ($attendance) {
                    $stats['total_work_minutes'] += (int) ($attendance->work_minutes ?? 0);

                    if (in_array($attendance->status, ['present', 'late'], true)) {
                        $stats['attendance']++;
                        $label = $attendance->status === 'late' ? 'Terlambat' : 'Hadir';
                        $statusKey = 'attendance';
                        if ($attendance->status === 'late') {
                            $stats['late']++;
                        }
                    } elseif ($attendance->status === 'absent') {
                        $stats['tanpa_keterangan']++;
                    } elseif ($attendance->status === 'holiday' || $attendance->status === 'off') {
                        $stats['libur']++;
                        $label = 'Libur/Off';
                        $statusKey = 'libur';
                    } elseif ($attendance->status === 'leave') {
                        $leaveType = $leaveDay['type'] ?? null;
                        if ($leaveType === 'sakit') {
                            $stats['sakit']++;
                            $label = 'Sakit';
                            $statusKey = 'sakit';
                        } elseif ($leaveType === 'cuti') {
                            $stats['cuti']++;
                            $label = 'Cuti';
                            $statusKey = 'cuti';
                        } else {
                            $stats['izin']++;
                            $label = 'Izin';
                            $statusKey = 'izin';
                        }
                    }
                } else {
                    if ($leaveDay) {
                        if (($leaveDay['type'] ?? null) === 'sakit') {
                            $stats['sakit']++;
                            $label = 'Sakit';
                            $statusKey = 'sakit';
                        } elseif (($leaveDay['type'] ?? null) === 'cuti') {
                            $stats['cuti']++;
                            $label = 'Cuti';
                            $statusKey = 'cuti';
                        } else {
                            $stats['izin']++;
                            $label = 'Izin';
                            $statusKey = 'izin';
                        }
                    } elseif ($schedule && in_array($schedule->day_type, ['off', 'holiday'], true)) {
                        $stats['libur']++;
                        $label = 'Libur/Off';
                        $statusKey = 'libur';
                    } elseif ($day->isWeekend()) {
                        $stats['libur']++;
                        $label = 'Akhir pekan';
                        $statusKey = 'libur';
                    } else {
                        $stats['tanpa_keterangan']++;
                    }
                }

                if ($calendarUser && (int) $calendarUser->id === (int) $employee->id) {
                    $calendarDays[] = [
                        'date'      => $ds,
                        'day'       => $day->day,
                        'weekday'   => $day->dayOfWeek,
                        'status'    => $statusKey,
                        'label'     => $label,
                        'clock_in'  => $attendance->clock_in ?? null,
                        'clock_out' => $attendance->clock_out ?? null,
                    ];
                }
            }

            $summary->put($employee->id, $stats);
        }

        return view('attendance.report-excel', compact(
            'records',
            'employees',
            'summary',
            'monthStart',
            'monthEnd',
            'calendarDays',
            'calendarUser',
            'targetEmployees',
            'month'
        ));
    }
}
