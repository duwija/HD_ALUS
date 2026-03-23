<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Get tenant ID from various sources
        $tenantId = $this->resolveTenantId($request);
        
        // Store tenant ID in session
        session(['tenant_id' => $tenantId]);
        
        // Store in config for easy access
        Config::set('app.current_tenant_id', $tenantId);
        
        // Add tenant context to logs
        if (app()->bound('log')) {
            app('log')->withContext([
                'tenant_id' => $tenantId,
            ]);
        }
        
        return $next($request);
    }
    
    /**
     * Resolve tenant ID from request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveTenantId($request)
    {
        // 1. Try from config set by TenantMiddleware (highest priority)
        if (config('database.connections.tenant.database')) {
            // Extract tenant ID from database name (e.g., kencana_kencana -> kencana)
            $dbName = config('database.connections.tenant.database');
            $parts = explode('_', $dbName);
            if (count($parts) >= 2) {
                return $parts[1]; // Return second part (tenant name)
            }
        }
        
        // 2. Try from authenticated user
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }
        
        // 3. Try from session
        if (session()->has('tenant_id')) {
            return session('tenant_id');
        }
        
        // 4. Try from environment variable (untuk multi-domain setup)
        if (env('TENANT_ID')) {
            return env('TENANT_ID');
        }
        
        // 5. Try from subdomain (contoh: tenant1.kencana.alus.co.id)
        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) > 2 && $parts[0] !== 'www') {
            return $parts[0];
        }
        
        // 6. Default tenant
        return 'default';
    }
}
