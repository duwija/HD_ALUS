<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\LeaveRequest;
use App\OvertimeRequest;

class MyTeamController extends Controller
{
    public function index()
    {
        $me   = Auth::user();
        $team = $me->subordinates()
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->get();

        // Attach pending counts per member
        $team->each(function ($member) {
            $member->pending_leaves   = LeaveRequest::where('user_id', $member->id)->where('status', 'pending')->count();
            $member->pending_overtimes = OvertimeRequest::where('user_id', $member->id)->where('status', 'pending')->count();
            $member->last_leave       = LeaveRequest::where('user_id', $member->id)->latest()->first();
            $member->last_overtime    = OvertimeRequest::where('user_id', $member->id)->latest()->first();
        });

        return view('leave.my_team', compact('team', 'me'));
    }
}
