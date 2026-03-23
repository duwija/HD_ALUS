<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\LeaveRequest;
use App\OvertimeRequest;
use Carbon\Carbon;

class MyLeaveController extends Controller
{
    /** GET /my-pengajuan — halaman kombinasi izin/cuti & lembur */
    public function index()
    {
        $user     = Auth::user();
        $leaves   = LeaveRequest::where('user_id', $user->id)
                        ->orderByDesc('created_at')->paginate(15, ['*'], 'lpage');
        $overtimes = OvertimeRequest::where('user_id', $user->id)
                        ->orderByDesc('created_at')->paginate(15, ['*'], 'opage');

        return view('leave.my_pengajuan', compact('leaves', 'overtimes'));
    }

    /** POST /my-pengajuan/leave — simpan pengajuan izin/cuti */
    public function leaveStore(Request $request)
    {
        $request->validate([
            'type'       => 'required|in:cuti,sakit,izin_lainnya',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user  = Auth::user();
        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);
        $days  = $start->diffInDays($end) + 1;

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')
                ->store('leave_attachments', 'public');
        }

        LeaveRequest::create([
            'user_id'    => $user->id,
            'type'       => $request->type,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'days'       => $days,
            'reason'     => $request->reason,
            'attachment' => $attachmentPath,
            'status'     => 'pending',
        ]);

        return redirect('/my-pengajuan?tab=leave')
            ->with('success', 'Pengajuan izin/cuti berhasil dikirim dan menunggu persetujuan.');
    }

    /** POST /my-pengajuan/overtime — simpan pengajuan lembur */
    public function overtimeStore(Request $request)
    {
        $request->validate([
            'date'       => 'required|date',
            'start_time' => 'required',
            'end_time'   => 'required',
            'reason'     => 'required|string|max:1000',
        ]);

        $user  = Auth::user();
        $start = Carbon::createFromFormat('H:i', $request->start_time);
        $end   = Carbon::createFromFormat('H:i', $request->end_time);
        $hours = round($start->floatDiffInHours($end, false), 2);
        if ($hours <= 0) $hours = round($hours + 24, 2); // lewat tengah malam

        OvertimeRequest::create([
            'user_id'        => $user->id,
            'date'           => $request->date,
            'start_time'     => $request->start_time,
            'end_time'       => $request->end_time,
            'duration_hours' => $hours,
            'reason'         => $request->reason,
            'status'         => 'pending',
        ]);

        return redirect('/my-pengajuan?tab=overtime')
            ->with('success', 'Pengajuan lembur berhasil dikirim dan menunggu persetujuan.');
    }
}
