<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Setelah login berhasil, arahkan langsung ke dashboard preference user.
     * Ini mencegah Laravel menggunakan redirect()->intended() ke URL lain
     * yang menyebabkan preference tidak teraplikasi.
     */
    protected function authenticated(Request $request, $user)
    {
        $pref = $user->dashboard_preference;
        if ($pref && in_array($pref, ['home-v2', 'home-v3', 'home-v4', 'home-v5'])
            && !in_array($user->privilege, ['vendor', 'merchant'])) {
            return redirect('/' . $pref);
        }
        // vendor & merchant sudah ditangani di HomeController
        return redirect(RouteServiceProvider::HOME);
    }

    /**
     * Tambahkan is_active = 1 ke credentials agar user nonaktif tidak bisa login.
     */
    protected function credentials(Request $request): array
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['is_active' => 1]
        );
    }

    /**
     * Override pesan gagal login agar lebih informatif untuk user nonaktif.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $user = User::where($this->username(), $request->{$this->username()})->first();

        if ($user && !$user->is_active) {
            throw ValidationException::withMessages([
                $this->username() => ['Akun Anda telah dinonaktifkan. Hubungi administrator.'],
            ]);
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
