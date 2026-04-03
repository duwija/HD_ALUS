<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Tenant;

/**
 * Middleware: CheckLicense
 *
 * Mencegah penambahan customer baru jika:
 * 1. Lisensi tenant expired/suspended
 * 2. Jumlah customer aktif sudah mencapai batas plan
 *
 * Cara pakai di routes:
 *   Route::post('/customers')->middleware('check.license');
 */
class CheckLicense
{
    public function handle(Request $request, Closure $next)
    {
        // Ambil tenant dari app instance yang sudah di-set oleh TenantMiddleware
        $tenantData = app('tenant');
        if (!$tenantData || empty($tenantData['tenant_id'])) {
            return $next($request);
        }

        $tenantId = $tenantData['tenant_id'];

        $tenant = Tenant::find($tenantId);
        if (!$tenant || !$tenant->licensePlan) {
            // Belum ada plan → lewati (backward compat)
            return $next($request);
        }

        // Cek status lisensi
        if (!$tenant->isLicenseActive()) {
            $msg = match ($tenant->license_status) {
                'expired'   => 'Lisensi telah kedaluwarsa. Hubungi admin untuk perpanjangan.',
                'suspended' => 'Akun telah dinonaktifkan. Hubungi admin.',
                default     => 'Lisensi tidak aktif.',
            };

            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Cek kuota customer aktif (id_status = 2)
        if (!$tenant->licensePlan->isUnlimited()) {
            $activeCount = \DB::table('customers')
                ->where('id_status', 2)
                ->whereNull('deleted_at')
                ->count();

            if (!$tenant->canAddCustomer($activeCount)) {
                $msg = 'Batas pelanggan aktif plan ' . $tenant->licensePlan->name
                     . ' (' . number_format($tenant->licensePlan->max_customers) . ' pelanggan) telah tercapai. '
                     . 'Upgrade plan untuk menambah lebih banyak pelanggan.';

                if ($request->expectsJson()) {
                    return response()->json(['error' => $msg], 403);
                }
                return redirect()->back()->with('error', $msg);
            }
        }

        return $next($request);
    }
}
