<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Customer;

/**
 * CustomerApiController
 *
 * REST API endpoints untuk aplikasi mobile pelanggan.
 * Authentikasi menggunakan Bearer Token (Sanctum tidak diperlukan
 * karena kita pakai simple encrypted-token strategy).
 */
class CustomerApiController extends Controller
{
    // ------------------------------------------------------------------
    // HELPERS
    // ------------------------------------------------------------------

    private function successResponse($data, int $code = 200)
    {
        return response()->json(['success' => true,  'data' => $data], $code);
    }

    private function errorResponse(string $message, int $code = 400)
    {
        return response()->json(['success' => false, 'message' => $message], $code);
    }

    /**
     * Verifikasi Bearer token: cocokkan token dengan kolom app_token di DB.
     * Token = random 40-char hex, disimpan saat login, dihapus saat logout.
     */
    private function customerFromToken(Request $request): ?Customer
    {
        $bearer = $request->bearerToken();
        if (!$bearer) return null;

        // Coba verifikasi token DB dulu (sistem baru)
        $customer = Customer::where('app_token', $bearer)->first();
        if ($customer) return $customer;

        // Fallback: legacy encrypted token (untuk kompatibilitas backward)
        try {
            $plain = Crypt::decryptString($bearer);
            if (!str_starts_with($plain, 'customer:')) return null;
            $id = (int) substr($plain, strlen('customer:'));
            $c  = Customer::find($id);
            // Legacy token hanya boleh jika app_token masih null (belum login ulang)
            return ($c && $c->app_token === null) ? $c : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ------------------------------------------------------------------
    // PUBLIC: Login
    // ------------------------------------------------------------------

    /**
     * POST /api/customer/login
     * Body: { email, password }
     * Returns: token + customer list
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        $customers = Customer::where('email', $request->email)->get();

        if ($customers->isEmpty()) {
            return $this->errorResponse('Email tidak terdaftar.', 401);
        }

        $authenticated = null;
        foreach ($customers as $c) {
            if ($c->portal_password && Hash::check($request->password, $c->portal_password)) {
                $authenticated = $c;
                break;
            }
        }

        if (!$authenticated) {
            $hasPassword = $customers->whereNotNull('portal_password')->count() > 0;
            $msg = $hasPassword ? 'Password salah.' : 'Akun belum diaktifkan.';
            return $this->errorResponse($msg, 401);
        }

        // Update last login + generate + simpan app_token baru di DB
        $token = bin2hex(random_bytes(20)); // 40-char hex, unik per login
        $authenticated->update([
            'last_login_at' => now(),
            'app_token'     => $token,
        ]);

        // Semua customer dengan email yang sama (multi-account)
        $list = $customers->map(fn($c) => [
            'id'          => $c->id,
            'customer_id' => $c->customer_id,
            'name'        => $c->name,
            'address'     => $c->address,
            'phone'       => $c->phone,
            'email'       => $c->email,
        ]);

        Log::channel('notif')->info('[CustomerAPI] Login: ' . $authenticated->email);

        return $this->successResponse([
            'token'     => $token,
            'customers' => $list,
        ]);
    }

    // ------------------------------------------------------------------
    // PROTECTED: Register FCM token
    // ------------------------------------------------------------------

    /**
     * POST /api/customer/register-token
     * Headers: Authorization: Bearer {token}
     * Body: { customer_id (DB id), fcm_token }
     */
    public function registerToken(Request $request)
    {
        $customer = $this->customerFromToken($request);
        if (!$customer) {
            return $this->errorResponse('Unauthorized.', 401);
        }

        $request->validate([
            'customer_id' => 'required|integer',
            'fcm_token'   => 'required|string',
        ]);

        // Pastikan customer ini milik email yang login
        $target = Customer::where('id', $request->customer_id)
                          ->where('email', $customer->email)
                          ->first();

        if (!$target) {
            return $this->errorResponse('Customer tidak ditemukan.', 404);
        }

        $target->update(['fcm_token' => $request->fcm_token]);

        Log::channel('notif')->info('[CustomerAPI] FCM token terdaftar: CID ' . $target->customer_id);

        return $this->successResponse(['message' => 'FCM token berhasil didaftarkan.']);
    }

    // ------------------------------------------------------------------
    // PROTECTED: Dashboard info
    // ------------------------------------------------------------------

    /**
     * GET /api/customer/dashboard/{customerId}
     * Returns: customer info + unpaid invoice count + active ticket count
     */
    public function dashboard(Request $request, $customerId)
    {
        $customer = $this->customerFromToken($request);
        if (!$customer) {
            return $this->errorResponse('Unauthorized.', 401);
        }

        $target = Customer::with(['plan_name'])
                          ->where('id', $customerId)
                          ->where('email', $customer->email)
                          ->first();

        if (!$target) {
            return $this->errorResponse('Customer tidak ditemukan.', 404);
        }

        $encryptedId    = Crypt::encryptString($target->id);
        $unpaidInvoices = \App\Suminvoice::where('id_customer', $target->id)
                                         ->where('payment_status', 0)
                                         ->count();
        $activeTickets  = \App\Ticket::where('id_customer', $target->id)
                                     ->whereNotIn('status', ['Close'])
                                     ->count();

        return $this->successResponse([
            'customer' => [
                'id'          => $target->id,
                'customer_id' => $target->customer_id,
                'name'        => $target->name,
                'email'       => $target->email,
                'phone'       => $target->phone,
                'address'     => $target->address,
                'plan'        => optional($target->plan_name)->name,
                'invoice_url' => url('/invoice/cst/' . $encryptedId),
            ],
            'summary' => [
                'unpaid_invoices' => $unpaidInvoices,
                'active_tickets'  => $activeTickets,
            ],
        ]);
    }

    // ------------------------------------------------------------------
    // PROTECTED: Ticket list
    // ------------------------------------------------------------------

    /**
     * GET /api/customer/tickets/{customerId}
     */
    public function tickets(Request $request, $customerId)
    {
        $customer = $this->customerFromToken($request);
        if (!$customer) {
            return $this->errorResponse('Unauthorized.', 401);
        }

        $target = Customer::where('id', $customerId)
                          ->where('email', $customer->email)
                          ->first();

        if (!$target) {
            return $this->errorResponse('Customer tidak ditemukan.', 404);
        }

        $tickets = \App\Ticket::with(['steps', 'currentStep', 'categorie'])
            ->where('id_customer', $customerId)
            ->orderByDesc('id')
            ->get()
            ->map(function ($t) {
                $steps   = $t->steps->sortBy('position')->values();
                $total   = $steps->count();
                $current = $t->currentStep;
                $pct     = ($total > 0 && $current)
                    ? round(($current->position / $total) * 100)
                    : 0;

                return [
                    'id'         => $t->id,
                    'title'      => $t->tittle,
                    'status'     => $t->status,
                    'category'   => optional($t->categorie)->name,
                    'created_at' => $t->created_at?->toDateTimeString(),
                    'progress'   => $pct,
                    'current_step' => $current ? [
                        'id'       => $current->id,
                        'name'     => $current->name,
                        'position' => $current->position,
                    ] : null,
                    'steps' => $steps->map(fn($s) => [
                        'id'       => $s->id,
                        'name'     => $s->name,
                        'position' => $s->position,
                        'done'     => $current ? $s->position <= $current->position : false,
                    ]),
                ];
            });

        return $this->successResponse($tickets);
    }

    // ------------------------------------------------------------------
    // PROTECTED: Logout (hapus FCM token)
    // ------------------------------------------------------------------

    /**
     * POST /api/customer/logout
     * Body: { customer_id }
     */
    public function logout(Request $request)
    {
        $customer = $this->customerFromToken($request);
        if (!$customer) {
            return $this->errorResponse('Unauthorized.', 401);
        }

        // Hapus fcm_token + app_token semua akun dengan email yang sama
        Customer::where('email', $customer->email)
                ->update(['fcm_token' => null, 'app_token' => null]);

        return $this->successResponse(['message' => 'Logout berhasil.']);
    }
}
