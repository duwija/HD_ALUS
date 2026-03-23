<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * Ensure admin routes use correct database and session
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Force admin database connection for admin panel
        Config::set('database.default', 'admin');
        
        // Purge only the default connection, not master or admin
        if (DB::connection()->getName() !== 'isp_master' && DB::connection()->getName() !== 'admin') {
            DB::purge(DB::connection()->getName());
        }
        
        // Ensure default connection is admin
        DB::reconnect('admin');
        
        // Set session to admin session
        Config::set('session.cookie', 'admin_session');
        
        return $next($request);
    }
}
