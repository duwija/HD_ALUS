<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantMiddleware
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
        // Get domain dari request
        $host = $request->getHost();
        
        // Cek tenant berdasarkan domain
        $tenant = $this->getTenantByDomain($host);
        
        if (!$tenant) {
            abort(404, 'Tenant not found for domain: ' . $host);
        }
        
        // Set tenant ke app instance untuk digunakan globally
        app()->instance('tenant', $tenant);
        
        // Switch database connection
        $this->setDatabaseConnection($tenant);
        
        // Set app configuration berdasarkan tenant
        $this->setTenantConfig($tenant);
        
        return $next($request);
    }
    
    /**
     * Get tenant by domain
     */
    protected function getTenantByDomain($domain)
    {
        // Opsi 1: Dari database master
        // return DB::connection('master')->table('tenants')->where('domain', $domain)->first();
        
        // Opsi 2: Dari config file (lebih cepat)
        $tenants = config('tenants.list');
        
        return $tenants[$domain] ?? null;
    }
    
    /**
     * Set database connection untuk tenant
     */
    protected function setDatabaseConnection($tenant)
    {
        // Set default database connection
        Config::set('database.connections.mysql.host', $tenant['db_host'] ?? env('DB_HOST'));
        Config::set('database.connections.mysql.port', $tenant['db_port'] ?? env('DB_PORT'));
        Config::set('database.connections.mysql.database', $tenant['db_database']);
        Config::set('database.connections.mysql.username', $tenant['db_username'] ?? env('DB_USERNAME'));
        Config::set('database.connections.mysql.password', $tenant['db_password'] ?? env('DB_PASSWORD'));
        
        // Reconnect database
        DB::purge('mysql');
        DB::reconnect('mysql');
    }
    
    /**
     * Set tenant-specific configuration
     */
    protected function setTenantConfig($tenant)
    {
        // Set APP NAME
        Config::set('app.name', $tenant['app_name'] ?? 'ISP Management');
        
        // Set APP URL
        Config::set('app.url', 'https://' . $tenant['domain']);
        
        // Set RESCODE
        Config::set('app.rescode', $tenant['rescode'] ?? 'ISP');
        
        // Set SIGNATURE
        Config::set('app.signature', $tenant['signature'] ?? $tenant['app_name']);
        
        // Set tenant-specific storage paths
        $this->setTenantStorage($tenant);
        
        // Set MAIL FROM
        if (isset($tenant['mail_from'])) {
            Config::set('mail.from.address', $tenant['mail_from']);
            Config::set('mail.from.name', $tenant['app_name']);
        }
        
        // Set WhatsApp config jika ada
        if (isset($tenant['whatsapp_token'])) {
            Config::set('services.whatsapp.token', $tenant['whatsapp_token']);
        }
        
        // Set Payment Gateway jika ada
        if (isset($tenant['xendit_key'])) {
            Config::set('services.xendit.secret_key', $tenant['xendit_key']);
        }
    }
    
    /**
     * Set tenant-specific storage and log paths
     */
    protected function setTenantStorage($tenant)
    {
        $tenantId = $tenant['rescode'] ?? $tenant['tenant_id'];
        $basePath = storage_path();
        $publicBasePath = public_path();
        
        // Create tenant-specific directories if not exist
        $tenantStoragePath = $basePath . '/tenants/' . $tenantId;
        $tenantLogPath = $tenantStoragePath . '/logs';
        $tenantAppPath = $tenantStoragePath . '/app';
        
        // Public folders per tenant
        $tenantPublicPath = $publicBasePath . '/tenants/' . $tenantId;
        $tenantUploadPath = $tenantPublicPath . '/upload';
        $tenantBackupPath = $tenantPublicPath . '/backup';
        $tenantUsersPath = $tenantPublicPath . '/users';
        $tenantWaUploadsPath = $tenantPublicPath . '/wa_uploads';
        
        // Create storage directories
        if (!is_dir($tenantLogPath)) {
            @mkdir($tenantLogPath, 0755, true);
        }
        if (!is_dir($tenantAppPath . '/public')) {
            @mkdir($tenantAppPath . '/public', 0755, true);
        }
        
        // Create public directories
        $publicDirs = [
            $tenantUploadPath,
            $tenantBackupPath,
            $tenantUsersPath,
            $tenantWaUploadsPath,
        ];
        
        foreach ($publicDirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
        
        // Set logging path
        Config::set('logging.channels.daily.path', $tenantLogPath . '/laravel.log');
        Config::set('logging.channels.single.path', $tenantLogPath . '/laravel.log');
        
        // Set storage disk paths untuk tenant
        Config::set('filesystems.disks.local.root', $tenantAppPath);
        Config::set('filesystems.disks.public.root', $tenantAppPath . '/public');
        
        // Set public storage URL path dengan tenant prefix
        $publicUrl = env('APP_URL') . '/storage/' . $tenantId;
        Config::set('filesystems.disks.public.url', $publicUrl);
        
        // Update symbolic link configuration
        Config::set('filesystems.links', [
            public_path('storage/' . $tenantId) => $tenantAppPath . '/public',
        ]);
        
        // Set tenant public paths (untuk digunakan di helper/controller)
        Config::set('tenant.paths.upload', '/tenants/' . $tenantId . '/upload');
        Config::set('tenant.paths.backup', '/tenants/' . $tenantId . '/backup');
        Config::set('tenant.paths.users', '/tenants/' . $tenantId . '/users');
        Config::set('tenant.paths.wa_uploads', '/tenants/' . $tenantId . '/wa_uploads');
        
        // Set tenant full paths
        Config::set('tenant.full_paths.upload', $tenantUploadPath);
        Config::set('tenant.full_paths.backup', $tenantBackupPath);
        Config::set('tenant.full_paths.users', $tenantUsersPath);
        Config::set('tenant.full_paths.wa_uploads', $tenantWaUploadsPath);
    }
}
