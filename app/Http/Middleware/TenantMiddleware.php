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

        // Cek tenant berdasarkan domain hanya dari database master/config
        $tenant = $this->getTenantByDomainMasterOnly($host);

        if (!$tenant) {
            abort(404, 'Tenant not found for domain: ' . $host);
        }

        // Cek status aktif tenant sebelum apapun!
        if (array_key_exists('is_active', $tenant) && ($tenant['is_active'] === false || $tenant['is_active'] === 0 || $tenant['is_active'] === '0')) {
            // Hapus session cookie agar saat tenant di-aktifkan kembali,
            // browser tidak membawa session stale yang menyebabkan 500 error.
            // (TenantMiddleware jalan sebelum StartSession, jadi session belum
            //  di-load — cookie harus dihapus langsung dari response header.)
            $sessionCookie = config('session.cookie', 'laravel_session');
            $response = response()->view('errors.tenant_inactive', [], 403);
            $response->headers->setCookie(
                \Symfony\Component\HttpFoundation\Cookie::create($sessionCookie, '', 1, '/')
            );
            return $response;
        }

        // Set tenant ke app instance untuk digunakan globally
        app()->instance('tenant', $tenant);

        // Switch database connection dengan error handling
        try {
            $this->setDatabaseConnection($tenant);
        } catch (\Exception $e) {
            // Jika gagal koneksi ke database tenant, tampilkan pesan error custom
            return response()->view('errors.db_connection', ['error_message' => $e->getMessage()], 500);
        }

        // Set app configuration berdasarkan tenant
        $this->setTenantConfig($tenant);

        return $next($request);
    }
    
    /**
     * Get tenant by domain
     */
        /**
         * Get tenant by domain ONLY from master database or config, never from tenant DB
         */
        protected function getTenantByDomainMasterOnly($domain)
        {
            // Query ke database master saja
            $tenant = null;
            try {
                $tenant = \App\Tenant::on('isp_master')->where('domain', $domain)->first();
            } catch (\Exception $e) {
                // Jika gagal query ke master, fallback ke config
            }
            if ($tenant) {
                $arr = $tenant->toTenantArray();
                // Pastikan is_active selalu ada
                if (!array_key_exists('is_active', $arr)) {
                    $arr['is_active'] = 1;
                }
                return $arr;
            }
            // Fallback ke config file (untuk backward compatibility)
            $tenants = config('tenants.list');
            $arr = $tenants[$domain] ?? null;
            if ($arr && !array_key_exists('is_active', $arr)) {
                $arr['is_active'] = 1;
            }
            return $arr;
        }
    
    /**
     * Set database connection untuk tenant
     */
    protected function setDatabaseConnection($tenant)
    {
        // Cek user/password di table tenants pada database isp_master
        $tenantDb = null;
        try {
            $tenantDb = \DB::connection('isp_master')->table('tenants')
                ->where('domain', $tenant['domain'])
                ->first();
        } catch (\Exception $e) {
            // Jika gagal query, biarkan tenantDb null (fallback ke default)
           
        }

        $dbUser = $tenant['db_username'] ?? env('DB_USERNAME');
        $dbPass = $tenant['db_password'] ?? env('DB_PASSWORD');
        if ($tenantDb && !empty($tenantDb->db_username) && !empty($tenantDb->db_password)) {
            $dbUser = $tenantDb->db_username;
            $dbPass = $tenantDb->db_password;
        }

        Config::set('database.connections.mysql.host', $tenant['db_host'] ?? env('DB_HOST'));
        Config::set('database.connections.mysql.port', $tenant['db_port'] ?? env('DB_PORT'));
        Config::set('database.connections.mysql.database', $tenant['db_database']);
        Config::set('database.connections.mysql.username', $dbUser);
        Config::set('database.connections.mysql.password', $dbPass);

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
        if (!empty($tenant['mail_from_address'])) {
            Config::set('mail.from.address', $tenant['mail_from_address']);
        } elseif (!empty($tenant['mail_from'])) {
            Config::set('mail.from.address', $tenant['mail_from']);
        }
        if (!empty($tenant['mail_from_name'])) {
            Config::set('mail.from.name', $tenant['mail_from_name']);
        } else {
            Config::set('mail.from.name', $tenant['app_name'] ?? config('app.name'));
        }

        // Set Mail SMTP Configuration (Laravel 8+ path: mail.mailers.smtp.*)
        if (!empty($tenant['mail_host'])) {
            Config::set('mail.mailers.smtp.host', $tenant['mail_host']);
        }
        if (!empty($tenant['mail_port'])) {
            Config::set('mail.mailers.smtp.port', (int) $tenant['mail_port']);
        }
        if (!empty($tenant['mail_username'])) {
            Config::set('mail.mailers.smtp.username', $tenant['mail_username']);
        }
        if (!empty($tenant['mail_password'])) {
            Config::set('mail.mailers.smtp.password', $tenant['mail_password']);
        }
        if (!empty($tenant['mail_encryption'])) {
            Config::set('mail.mailers.smtp.encryption', $tenant['mail_encryption']);
        }
        if (!empty($tenant['mail_mailer'])) {
            Config::set('mail.default', $tenant['mail_mailer']);
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

        // Purge cached mail manager so new Config values take effect
        try {
            app('mail.manager')->purge('smtp');
        } catch (\Exception $e) {
            // Ignore if mail.manager not yet instantiated
        }
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
            'marketing_email',
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
            // WhatsApp provider selector & credentials
            'wa_provider',           // gateway | qontak | fonnte | wablas
            'wa_fonnte_token',       // Fonnte API token
            'wa_wablas_token',       // Wablas API token
            'wa_wablas_url',         // Wablas server URL (default: https://my.wablas.com)
            'wa_qontak_token',       // Qontak Bearer access token
            'wa_qontak_api_url',     // Qontak API endpoint (opsional override)
            'wa_qontak_template_id', // Qontak message_template_id
            'wa_qontak_channel_id',  // Qontak channel_integration_id
            // Company information
            'company_name',
            'company',
            'company_address1',
            'company_address2',
            'inv_note',
            'domain_name',
            'payment_wa',
            'whatsapp_noc',   // Nomor WA NOC/Support untuk tombol "Buat Laporan" di portal pelanggan
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
