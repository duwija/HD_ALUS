<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use App\Customer;
use App\AppPromo;
use App\AppCustomerNotification;
use App\Suminvoice;
use App\Ticket;

/**
 * Controller untuk halaman-halaman khusus Android App
 * Semua route: /tagihan/app/*
 * Hanya bisa diakses setelah SSO bridge (auth:customer)
 */
class AppPortalController extends Controller
{
    // =====================================================================
    // Helper: ambil data customer milik user yang login
    // =====================================================================
    private function getCustomers(): \Illuminate\Database\Eloquent\Collection
    {
        $email = Auth::guard('customer')->user()->email;
        return Customer::where('email', $email)->get();
    }

    private function getCustomer(int $customerId): ?Customer
    {
        $email = Auth::guard('customer')->user()->email;
        return Customer::where('id', $customerId)
            ->where('email', $email)
            ->first();
    }

    // =====================================================================
    // HOME — Info pelanggan + promo
    // =====================================================================
    public function home(Request $request)
    {
        if (!Auth::guard('customer')->check()) {
            return redirect('/tagihan/login');
        }

        $customers = $this->getCustomers();
        $promos    = AppPromo::active()->latest()->get();

        // Total tagihan belum bayar (semua customer email ini)
        $unpaidTotal = Suminvoice::whereIn('id_customer', $customers->pluck('id'))
            ->where('payment_status', 0)
            ->sum('total_amount');

        // Unread notif count
        $unreadCount = AppCustomerNotification::whereIn('customer_id', $customers->pluck('id'))
            ->where('is_read', false)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return view('app.home', compact('customers', 'promos', 'unpaidTotal', 'unreadCount'));
    }

    // =====================================================================
    // TAGIHAN — Daftar tagihan semua customer
    // =====================================================================
    public function tagihan(Request $request)
    {
        if (!Auth::guard('customer')->check()) {
            return redirect('/tagihan/login');
        }

        $customers = $this->getCustomers();

        // Ambil semua invoice (maks 50 terbaru) dari seluruh customer
        $invoices = Suminvoice::whereIn('id_customer', $customers->pluck('id'))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($inv) {
                // Tambah encrypted customer id untuk link bayar
                $inv->encrypted_customer_id = Crypt::encryptString($inv->id_customer);
                return $inv;
            });

        $customerMap = $customers->keyBy('id');
        $unreadCount = AppCustomerNotification::whereIn('customer_id', $customers->pluck('id'))
            ->where('is_read', false)->where('created_at', '>=', now()->subDays(30))->count();

        return view('app.tagihan', compact('invoices', 'customerMap', 'unreadCount'));
    }

    // =====================================================================
    // LAPORAN — Tiket + status workflow
    // =====================================================================
    public function laporan(Request $request)
    {
        if (!Auth::guard('customer')->check()) {
            return redirect('/tagihan/login');
        }

        $customers = $this->getCustomers();

        $tickets = Ticket::with(['categorie', 'currentStep', 'steps'])
            ->whereIn('id_customer', $customers->pluck('id'))
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $customerMap = $customers->keyBy('id');
        $unreadCount = AppCustomerNotification::whereIn('customer_id', $customers->pluck('id'))
            ->where('is_read', false)->where('created_at', '>=', now()->subDays(30))->count();

        return view('app.laporan', compact('tickets', 'customerMap', 'unreadCount'));
    }

    // =====================================================================
    // NOTIF — Daftar notifikasi 30 hari terakhir
    // =====================================================================
    public function notif(Request $request)
    {
        if (!Auth::guard('customer')->check()) {
            return redirect('/tagihan/login');
        }

        $customers   = $this->getCustomers();
        $customerIds = $customers->pluck('id');

        $notifications = AppCustomerNotification::whereIn('customer_id', $customerIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('created_at')
            ->get();

        $unreadCount = $notifications->where('is_read', false)->count();

        // Mark semua sebagai sudah dibaca saat halaman dibuka
        AppCustomerNotification::whereIn('customer_id', $customerIds)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('app.notif', compact('notifications', 'unreadCount'));
    }

    // =====================================================================
    // API: badge count (dipanggil Android untuk update angka notif)
    // GET /tagihan/app/notif-badge?token={bearer}
    // =====================================================================
    public function notifBadge(Request $request)
    {
        $token    = $request->query('token');
        $redirect = $request->query('redirect', '');

        if (!$token) {
            return response()->json(['count' => 0]);
        }

        try {
            $plain = Crypt::decryptString($token);
            if (!str_starts_with($plain, 'customer:')) {
                return response()->json(['count' => 0]);
            }
            $customerId = (int) substr($plain, 9);
            $customer   = Customer::find($customerId);
            if (!$customer) {
                return response()->json(['count' => 0]);
            }

            // Ambil semua customer dengan email yang sama
            $customerIds = Customer::where('email', $customer->email)->pluck('id');
            $count = AppCustomerNotification::whereIn('customer_id', $customerIds)
                ->where('is_read', false)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['count' => 0]);
        }
    }

}
