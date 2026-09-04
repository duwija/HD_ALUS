<?php

namespace App\Http\Controllers\Api;

use App\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Endpoint internal dipanggil oleh mobile app resolver (lihat
 * /var/www/mobileappresolver) untuk menentukan apakah tenant ini yang
 * punya customer dengan email+phone tertentu.
 *
 * TIDAK untuk dipanggil langsung dari app/browser — hanya dari resolver,
 * diverifikasi lewat header X-Resolver-Secret. TenantMiddleware sudah
 * men-scope request ini ke DB tenant yang benar berdasarkan domain yang
 * dipanggil (persis seperti route lain), jadi endpoint ini tidak perlu
 * loop tenant lain sama sekali.
 */
class InternalResolverController extends Controller
{
    public function resolveCustomer(Request $request)
    {
        $expected = (string) env('RESOLVER_SHARED_SECRET', '');
        $given = (string) $request->header('X-Resolver-Secret', '');

        if ($expected === '' || !hash_equals($expected, $given)) {
            return response()->json(['exists' => false], 401);
        }

        $email = trim((string) $request->input('email', ''));
        $phone = trim((string) $request->input('phone', ''));

        if ($email === '' || $phone === '') {
            return response()->json(['exists' => false], 422);
        }

        $phoneVariants = Customer::phoneVariants($phone);
        if (empty($phoneVariants)) {
            return response()->json(['exists' => false]);
        }

        $exists = Customer::withTrashed()
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->whereIn('phone', $phoneVariants)
            ->exists();

        if (!$exists) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'domain' => tenant_config('domain_name', env('DOMAIN_NAME', '')),
        ]);
    }
}
