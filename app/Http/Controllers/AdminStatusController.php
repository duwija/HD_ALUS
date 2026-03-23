<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Only admin can access
        if (Auth::user()->privilege !== 'admin') {
            abort(403, 'Akses ditolak: hanya admin.');
        }

        $today       = Carbon::today()->toDateString();
        $monthStart  = date('Y-m-01');
        $monthEnd    = date('Y-m-t');

        // =====================================================
        // 👥 USERS
        // =====================================================
        $usersByPrivilege = \App\User::whereNull('deleted_at')
            ->selectRaw('privilege, count(*) as cnt')
            ->groupBy('privilege')
            ->pluck('cnt', 'privilege');
        $totalUsers = $usersByPrivilege->sum();

        // =====================================================
        // 🎟️ TIKET
        // =====================================================
        $ticketStatuses   = ['Open', 'Pending', 'Inprogress', 'Solve', 'Close'];
        $ticketByStatus   = [];
        foreach ($ticketStatuses as $s) {
            $ticketByStatus[$s] = \App\Ticket::where('status', $s)->count();
        }
        $ticketToday      = \App\Ticket::whereDate('date', $today)->count();
        $ticketThisMonth  = \App\Ticket::whereBetween('date', [$monthStart, $monthEnd])->count();
        $ticketOpenNow    = $ticketByStatus['Open'] + $ticketByStatus['Pending'] + $ticketByStatus['Inprogress'];

        // =====================================================
        // 💸 INVOICE / KEUANGAN
        // =====================================================
        $invoiceUnpaid    = \App\Suminvoice::where('payment_status', 0)->count();
        $invoicePaidToday = \App\Suminvoice::where('payment_status', 1)
            ->whereDate('payment_date', $today)->count();
        $invoicePaidMonth = \App\Suminvoice::where('payment_status', 1)
            ->whereBetween('payment_date', [$monthStart, $monthEnd])->count();
        $revenueMonth     = \App\Suminvoice::where('payment_status', 1)
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->sum('recieve_payment');
        $revenueToday     = \App\Suminvoice::where('payment_status', 1)
            ->whereDate('payment_date', $today)
            ->sum('recieve_payment');

        // =====================================================
        // 👤 PELANGGAN
        // =====================================================
        $custActive    = \App\Customer::where('id_status', 2)->count();
        $custBlock     = \App\Customer::where('id_status', 4)->count();
        $custInactive  = \App\Customer::where('id_status', 3)->count();
        $custPotential = \App\Customer::where('id_status', 1)->count();
        $custNewMonth  = \App\Customer::whereBetween('created_at', [$monthStart, $monthEnd])->count();

        // =====================================================
        // 🕐 ABSENSI / HRD
        // =====================================================
        $todayAtt    = \App\Attendance::whereDate('date', $today)->get();
        $attHadir    = $todayAtt->whereIn('status', ['present', 'late'])->count();
        $attLate     = $todayAtt->where('status', 'late')->count();
        $attCuti     = $todayAtt->whereIn('status', ['leave', 'off', 'holiday'])->count();

        // Belum absen (excluding off/cuti)
        $presentIds  = $todayAtt->whereNotNull('clock_in')->pluck('user_id');
        $skipIds     = $todayAtt->whereIn('status', ['off', 'leave', 'holiday'])->pluck('user_id');
        $onLeaveIds  = \App\LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('user_id');
        $excludeIds  = $presentIds->merge($skipIds)->merge($onLeaveIds)->unique();
        $attBelum    = \App\User::where('is_active_employee', true)
            ->whereNotIn('id', $excludeIds)->count();

        // Lihat izin & lembur pending
        $leavePending    = \App\LeaveRequest::where('status', 'pending')->count();
        $overtimePending = \App\OvertimeRequest::where('status', 'pending')->count();

        // Leave this month breakdown
        $leaveMonth = \App\LeaveRequest::where('status', 'approved')
            ->whereBetween('start_date', [$monthStart, $monthEnd])
            ->selectRaw('type, count(*) as cnt')
            ->groupBy('type')
            ->pluck('cnt', 'type');

        // Pending leave list (max 5)
        $leavePendingList = \App\LeaveRequest::where('status', 'pending')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)->get();

        // Pending overtime list (max 5)
        $overtimePendingList = \App\OvertimeRequest::where('status', 'pending')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)->get();

        // =====================================================
        // 📲 WA GATEWAY
        // =====================================================
        $waTodayTotal   = \App\Walog::whereDate('created_at', $today)->count();
        $waTodaySent    = \App\Walog::whereDate('created_at', $today)->where('status', 'sent')->count();
        $waTodayFailed  = \App\Walog::whereDate('created_at', $today)->where('status', 'failed')->count();
        $waTodayPending = \App\Walog::whereDate('created_at', $today)->where('status', 'pending')->count();
        $waMonthTotal   = \App\Walog::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $waMonthFailed  = \App\Walog::whereBetween('created_at', [$monthStart, $monthEnd])->where('status', 'failed')->count();
        $waSuccessRate  = $waMonthTotal > 0
            ? round((($waMonthTotal - $waMonthFailed) / $waMonthTotal) * 100, 1)
            : 100;

        // Recentfailed WA (max 5)
        $waRecentFailed = \App\Walog::where('status', 'failed')
            ->orderBy('created_at', 'desc')
            ->limit(5)->get();

        // =====================================================
        // 💳 PAYMENT GATEWAY
        // =====================================================
        $paymentGateways = \App\PaymentGateway::orderBy('sort_order')->get();
        $pgEnabled       = $paymentGateways->where('enabled', 1)->count();
        $pgDisabled      = $paymentGateways->where('enabled', '!=', 1)->count();

        // =====================================================
        // 🌐 NETWORK / INFRASTRUKTUR
        // =====================================================
        $oltCount        = \App\Olt::count();
        $distrouterCount = \App\Distrouter::count();

        // Mikrotik sync failures
        $mikrotikFailures = \App\MikrotikSyncFailure::orderBy('created_at', 'desc')->limit(10)->get();
        $mikrotikPending  = \App\MikrotikSyncFailure::where('status', 'pending')->count();
        $mikrotikResolved = \App\MikrotikSyncFailure::where('status', 'resolved')->count();

        // =====================================================
        // 🔖 AKUNTANSI
        // =====================================================
        $accountingMonth = 0; // tabel akuntransactions tidak memiliki kolom tanggal
        $accountingTotal = \App\Akuntransaction::count();

        // =====================================================
        // 🔔 ALERTS
        // =====================================================
        $alertCount = \App\Alert::count();

        return view('admin.status', compact(
            // users
            'usersByPrivilege', 'totalUsers',
            // tiket
            'ticketByStatus', 'ticketToday', 'ticketThisMonth', 'ticketOpenNow',
            // invoice
            'invoiceUnpaid', 'invoicePaidToday', 'invoicePaidMonth', 'revenueMonth', 'revenueToday',
            // pelanggan
            'custActive', 'custBlock', 'custInactive', 'custPotential', 'custNewMonth',
            // absensi
            'attHadir', 'attLate', 'attCuti', 'attBelum',
            'leavePending', 'overtimePending', 'leaveMonth',
            'leavePendingList', 'overtimePendingList',
            // wa
            'waTodayTotal', 'waTodaySent', 'waTodayFailed', 'waTodayPending',
            'waMonthTotal', 'waMonthFailed', 'waSuccessRate', 'waRecentFailed',
            // payment gateway
            'paymentGateways', 'pgEnabled', 'pgDisabled',
            // network
            'oltCount', 'distrouterCount', 'mikrotikFailures', 'mikrotikPending', 'mikrotikResolved',
            // accounting
            'accountingMonth', 'accountingTotal',
            // alerts
            'alertCount'
        ));
    }
}
