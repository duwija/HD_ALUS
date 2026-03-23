<?php

namespace App\Http\Middleware;

use Closure;
use Opcodes\LogViewer\Facades\Cache;

class FilterTenantLogs
{
    /**
     * Handle an incoming request for log viewer
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Only filter on log-viewer routes
        if ($request->is('log-viewer*')) {
            $tenantId = session('tenant_id', config('app.current_tenant_id', env('TENANT_ID', 'default')));
            
            // Set runtime configuration for log viewer
            config([
                'log-viewer.include_files' => [
                    "tenant_{$tenantId}/*.log",
                    "tenant_{$tenantId}/**/*.log",
                ]
            ]);
        }
        
        return $next($request);
    }
}
