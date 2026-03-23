<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class TenantLogger
{
    /**
     * Create a custom Monolog instance for tenant logging
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $tenantId = $this->getTenantId();
        
        // Create tenant-specific log path
        $logPath = storage_path("logs/tenant_{$tenantId}/laravel.log");
        
        // Ensure directory exists
        $logDir = dirname($logPath);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logger = new Logger('tenant');
        $logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
        
        return $logger;
    }
    
    /**
     * Get current tenant ID from session or config
     *
     * @return string
     */
    protected function getTenantId()
    {
        // Try to get tenant from session
        if (session()->has('tenant_id')) {
            return session('tenant_id');
        }
        
        // Try to get from config (set by middleware)
        if (config('app.current_tenant_id')) {
            return config('app.current_tenant_id');
        }
        
        // Try to get from environment variable
        if (env('TENANT_ID')) {
            return env('TENANT_ID');
        }
        
        // Try to get from auth user
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }
        
        // Default tenant
        return 'default';
    }
}
