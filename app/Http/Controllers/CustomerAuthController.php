<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use App\Customer;

class CustomerAuthController extends Controller
{
    /**
     * Show customer portal homepage (requires login)
     */
    public function index()
    {
        if (!Auth::guard('customer')->check()) {
            return redirect('/tagihan/login');
        }

        $email = Auth::guard('customer')->user()->email;
        $customers = Customer::where('email', $email)->get();

        return view('tagihan.select-customer', compact('customers', 'email'));
    }

    /**
     * Show login form
     */
    public function showLogin()
    {
        if (Auth::guard('customer')->check()) {
            return redirect('/tagihan');
        }
        
        return view('tagihan.login');
    }

    /**
     * Handle login request
     * 
     * IMPORTANT: Untuk multi-customer dengan email sama, WAJIB menggunakan password yang sama.
     * Saat aktivasi, password akan di-set untuk SEMUA customer dengan email tersebut.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Find all customers with this email
        $customers = Customer::where('email', $request->email)->get();

        if ($customers->isEmpty()) {
            return back()->withErrors(['email' => 'Email tidak terdaftar'])->withInput();
        }

        // Validasi: semua customer dengan email sama harus punya password yang sama
        // Ambil customer pertama yang sudah punya password
        $authenticatedCustomer = null;
        
        foreach ($customers as $cust) {
            if ($cust->portal_password) {
                if (Hash::check($request->password, $cust->portal_password)) {
                    $authenticatedCustomer = $cust;
                    break;
                }
            }
        }

        if (!$authenticatedCustomer) {
            // Cek apakah belum ada yang aktif atau password salah
            $hasPassword = $customers->whereNotNull('portal_password')->count() > 0;
            
            if (!$hasPassword) {
                return back()->withErrors(['email' => 'Akun belum diaktifkan. Silakan aktivasi terlebih dahulu.'])->withInput();
            } else {
                return back()->withErrors(['password' => 'Password salah'])->withInput();
            }
        }

        // Login using authenticated customer
        Auth::guard('customer')->login($authenticatedCustomer, $request->filled('remember'));

        // Update last login untuk customer yang login
        $authenticatedCustomer->update(['last_login_at' => now()]);

        // Redirect to customer portal homepage
        return redirect('/tagihan');
    }

    /**
     * Show customer selection page (for multiple customers with same email)
     */
    public function selectCustomer()
    {
        if (!Auth::guard('customer')->check()) {
            return redirect('/tagihan/login');
        }

        $email = Auth::guard('customer')->user()->email;
        $customers = Customer::where('email', $email)->get();

        // Always show selection page with logout button
        return view('tagihan.select-customer', compact('customers', 'email'));
    }

    /**
     * Redirect to selected customer invoice
     */
    public function viewInvoice($customerId)
    {
        if (!Auth::guard('customer')->check()) {
            return redirect('/tagihan/login');
        }

        // Verify this customer belongs to logged in email
        $email = Auth::guard('customer')->user()->email;
        $customer = Customer::where('id', $customerId)
                           ->where('email', $email)
                           ->first();

        if (!$customer) {
            abort(403, 'Unauthorized access');
        }

        $encryptedId = Crypt::encryptString($customer->id);
        return redirect('/invoice/cst/' . $encryptedId);
    }

    /**
     * Show ticket status monitor for a customer
     */
    public function viewTickets($customerId)
    {
        if (!Auth::guard('customer')->check()) {
            return redirect('/tagihan/login');
        }

        $email = Auth::guard('customer')->user()->email;
        $customer = Customer::where('id', $customerId)
                           ->where('email', $email)
                           ->first();

        if (!$customer) {
            abort(403, 'Unauthorized access');
        }

        $tickets = \App\Ticket::with(['steps', 'currentStep', 'categorie'])
            ->where('id_customer', $customerId)
            ->orderByDesc('id')
            ->get();

        return view('tagihan.tickets', compact('customer', 'tickets'));
    }

    /**
     * Logout
     */
    public function logout()
    {
        Auth::guard('customer')->logout();
        return redirect('/tagihan/login')->with('success', 'Berhasil logout');
    }

    /**
     * SSO Bridge untuk Aplikasi Android / Mobile WebView
     *
     * GET /tagihan/app-login?token={bearer_token}&redirect={path}
     *
     * Menerima API bearer token dari app, validasi, login ke web session, lalu
     * redirect ke halaman tujuan. Ini menghubungkan auth API (Android) dengan
     * auth session (WebView).
     */
    public function appLogin(Request $request)
    {
        $token    = $request->query('token');
        $redirect = $request->query('redirect', '/tagihan');

        // Pastikan redirect hanya ke path internal (hindari open redirect)
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/tagihan';
        }

        // URL khusus yang dideteksi app untuk paksa logout lokal
        // Melalui /app-force-logout agar web session dihapus dulu sebelum redirect ke login
        $forceLogoutUrl = '/tagihan/app-force-logout';

        // Jika tidak ada token, cek apakah sudah login via session
        if (!$token) {
            if (Auth::guard('customer')->check()) {
                return redirect($redirect);
            }
            return redirect('/tagihan/login');
        }

        // ── Coba sistem baru: app_token tersimpan di DB ─────────────────
        $customer = Customer::where('app_token', $token)->first();
        if ($customer) {
            // Token valid dan cocok di DB → login web session
            Auth::guard('customer')->login($customer, true);
            return redirect($redirect);
        }

        // ── Coba sistem lama: Crypt::encryptString("customer:{id}") ─────
        try {
            $plain = Crypt::decryptString($token);
            if (!str_starts_with($plain, 'customer:')) {
                return redirect($forceLogoutUrl);
            }
            $customerId = (int) substr($plain, strlen('customer:'));
        } catch (\Exception $e) {
            return redirect($forceLogoutUrl);
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            return redirect($forceLogoutUrl);
        }

        // Legacy token valid HANYA jika app_token masih null
        // (artinya baru pertama kali / belum pernah force-logout dari admin)
        if ($customer->app_token !== null) {
            // Admin sudah revoke sesi ini → paksa logout di app
            return redirect($forceLogoutUrl);
        }

        // Login ke web session (guard customer)
        Auth::guard('customer')->login($customer, true);

        return redirect($redirect);
    }

    /**
     * Force logout: hapus web session lalu redirect ke halaman login dengan marker.
     * Dipanggil oleh appLogin() saat token sudah direvoke admin.
     * Web session harus dihapus di sini agar showLogin() tidak redirect balik ke /tagihan.
     */
    public function appForceLogout()
    {
        Auth::guard('customer')->logout();
        return redirect('/tagihan/login?app_force_logout=1');
    }

    /**
     * Show activation form (for first time users)
     */
    public function showActivate()
    {
        return view('tagihan.activate');
    }

    /**
     * Handle activation
     */
    public function activate(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $customers = Customer::where('email', $request->email)
                            ->where('phone', $request->phone)
                            ->get();

        if ($customers->isEmpty()) {
            return back()->withErrors(['email' => 'Data tidak ditemukan. Periksa kembali email dan nomor telepon Anda.'])->withInput();
        }

        // Update password for all customers with this email
        foreach ($customers as $customer) {
            $customer->update([
                'portal_password' => Hash::make($request->password)
            ]);
        }

        return redirect('/tagihan/login')->with('success', 'Akun berhasil diaktifkan. Silakan login.');
    }
}
