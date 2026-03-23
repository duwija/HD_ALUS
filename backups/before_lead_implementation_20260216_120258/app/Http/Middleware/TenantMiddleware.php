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
        // Skip tenant detection for admin routes
        if ($request->is('admin') || $request->is('admin/*')) {
            // Let AdminMiddleware handle database connection for admin routes
            return $next($request);
        }
        
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
        // Try from database first
        $tenant = \App\Tenant::getByDomain($domain);
        
        if ($tenant) {
            return $tenant->toTenantArray();
        }
        
        // Fallback to config file (for backward compatibility)
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
        
        // Set Mail Configuration
        if (isset($tenant['mail_host'])) {
            Config::set('mail.host', $tenant['mail_host']);
        }
        if (isset($tenant['mail_port'])) {
            Config::set('mail.port', $tenant['mail_port']);
        }
        if (isset($tenant['mail_username'])) {
            Config::set('mail.username', $tenant['mail_username']);
        }
        if (isset($tenant['mail_password'])) {
            Config::set('mail.password', $tenant['mail_password']);
        }
        if (isset($tenant['mail_encryption'])) {
            Config::set('mail.encryption', $tenant['mail_encryption']);
        }
        
        // Set WhatsApp config jika ada
        if (isset($tenant['whatsapp_token'])) {
            Config::set('services.whatsapp.token', $tenant['whatsapp_token']);
        }
        if (isset($tenant['whatsapp_url'])) {
            Config::set('services.whatsapp.url', $tenant['whatsapp_url']);
        }
        
        // Set Payment Gateway jika ada
        if (isset($tenant['xendit_key'])) {
            Config::set('services.xendit.secret_key', $tenant['xendit_key']);
        }
        if (isset($tenant['xendit_callback_token'])) {
            Config::set('services.xendit.callback_token', $tenant['xendit_callback_token']);
        }
        
        // Set Google Maps API Key
        if (isset($tenant['google_maps_api_key'])) {
            Config::set('services.google_maps.key', $tenant['google_maps_api_key']);
        }
        
        // Set custom ENV variables
        $this->setCustomEnvVariables($tenant);
    }
    
    /**
     * Set custom environment variables from tenant config
     */
    protected function setCustomEnvVariables($tenant)
    {
        // List of custom env keys that can be set per tenant
        $customEnvKeys = [
            'pppoe_password',
            'router_host',
            'router_username', 
            'router_password',
            'sms_gateway_url',
            'sms_gateway_key',
            'telegram_bot_token',
            'telegram_chat_id',
            'telegram_group_payment',
            'wa_session_name',
            'backup_path',
            'report_email',
            'coordinate_center',
            'coordinate_zoom',
            // Mail configuration
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
            // Payment gateway - Tripay
            'tripay_endpoint',
            'tripay_apikey',
            'tripay_privatekey',
            'tripay_merchantcode',
            // WhatsApp gateway
            'wa_gateway_url',
            'wa_group_payment',
            'wa_group_support',
            'wa_group_vendor',
            // Company information
            'company_name',
            'company',
            'company_address1',
            'company_address2',
            'inv_note',
            'domain_name',
            'payment_wa',
            'signature',
            // System paths
            'phyton_dir',
            'ftp_user',
            'ftp_password',
        ];
        
        foreach ($customEnvKeys as $key) {
            if (isset($tenant[$key])) {
                // Set to config using dot notation
                // Example: pppoe_password becomes config('tenant.pppoe_password')
                Config::set('tenant.' . $key, $tenant[$key]);
                
                // Also set as uppercase ENV style for compatibility
                $envKey = strtoupper($key);
                putenv("{$envKey}={$tenant[$key]}");
                $_ENV[$envKey] = $tenant[$key];
                $_SERVER[$envKey] = $tenant[$key];
                
                // Special handling for mail config - set to Laravel mail config
                if (str_starts_with($key, 'mail_')) {
                    $mailKey = str_replace('mail_', '', $key);
                    if ($mailKey === 'host' || $mailKey === 'port' || $mailKey === 'encryption' || $mailKey === 'username' || $mailKey === 'password') {
                        Config::set('mail.mailers.smtp.' . $mailKey, $tenant[$key]);
                    } elseif ($mailKey === 'from_address') {
                        Config::set('mail.from.address', $tenant[$key]);
                    } elseif ($mailKey === 'from_name') {
                        Config::set('mail.from.name', $tenant[$key]);
                    }
                }
            }
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
