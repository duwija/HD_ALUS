<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPrivilege
{
    public function handle($request, Closure $next, ...$privileges)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        $userPrivilege = strtolower(trim((string) $user->privilege));
        $allowedPrivileges = array_map(function ($privilege) {
            return strtolower(trim((string) $privilege));
        }, $privileges);

        if (!in_array($userPrivilege, $allowedPrivileges, true)) {
            abort(403, 'Anda tidak memiliki hak akses ke halaman ini.');
        }

        return $next($request);
    }
}