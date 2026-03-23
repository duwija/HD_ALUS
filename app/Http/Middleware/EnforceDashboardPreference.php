<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnforceDashboardPreference
{
    /**
     * Jika user sudah di-set preference dashboard tertentu,
     * larang akses ke dashboard lain melalui URL manual.
     * Berlaku untuk semua user (termasuk admin) yang sudah punya preference.
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $versionedDashboards = ['home-v2', 'home-v3', 'home-v4', 'home-v5', 'home-admin'];
        $currentPath = $request->path(); // e.g. "home-v3"

        // Hanya berlaku saat user membuka salah satu URL dashboard versi
        if (!in_array($currentPath, $versionedDashboards)) {
            return $next($request);
        }

        $pref = Auth::user()->dashboard_preference;

        // Jika user punya preference DAN berbeda dengan yang dibuka → tolak
        if ($pref && in_array($pref, $versionedDashboards) && $pref !== $currentPath) {
            return redirect('/' . $pref)
                ->with('warning', 'Akses ditolak. Dashboard Anda adalah ' . strtoupper($pref) . '.');
        }

        return $next($request);
    }
}
