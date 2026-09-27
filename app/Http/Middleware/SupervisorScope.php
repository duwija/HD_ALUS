<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Restricts 'supervisor' admin accounts to a simplified, read-only slice of
 * the admin panel: the (filtered) tenant list, and the Customers/Transactions
 * pages of only the tenants they've been assigned to.
 *
 * Super admins are unaffected — this middleware is a no-op for them.
 */
class SupervisorScope
{
    /**
     * Route names a supervisor account is allowed to visit.
     */
    private const ALLOWED_ROUTES = [
        'admin.dashboard',
        'admin.tenants.index',
        'admin.tenants.customers',
        'admin.tenants.customers.data',
        'admin.tenants.customers.show',
        'admin.tenants.customers.invoices.show',
        'admin.tenants.transactions',
        'admin.tenants.transactions.data',
    ];

    public function handle($request, Closure $next)
    {
        $admin = auth('admin')->user();

        if (!$admin || !$admin->isSupervisor()) {
            return $next($request);
        }

        $routeName = optional($request->route())->getName();

        if (!in_array($routeName, self::ALLOWED_ROUTES, true)) {
            abort(403, 'Akun supervisor tidak memiliki akses ke halaman ini.');
        }

        $tenantId = $request->route('id');
        if ($tenantId !== null && !in_array((int) $tenantId, $admin->assignedTenantIds(), true)) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        return $next($request);
    }
}
