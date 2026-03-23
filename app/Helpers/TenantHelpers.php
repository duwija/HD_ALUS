<?php

if (!function_exists('tenant')) {
    /**
     * Get current tenant instance
     *
     * @param string|null $key
     * @return mixed
     */
    function tenant($key = null)
    {
        // Check if tenant instance exists in app container
        if (!app()->bound('tenant')) {
            return $key ? null : [];
        }
        
        $tenant = app('tenant');
        
        if ($key) {
            return $tenant[$key] ?? null;
        }
        
        return $tenant;
    }
}

if (!function_exists('tenant_id')) {
    /**
     * Get current tenant ID
     *
     * @return int|null
     */
    function tenant_id()
    {
        return tenant('tenant_id');
    }
}

if (!function_exists('tenant_domain')) {
    /**
     * Get current tenant domain
     *
     * @return string|null
     */
    function tenant_domain()
    {
        return tenant('domain');
    }
}

if (!function_exists('tenant_name')) {
    /**
     * Get current tenant name
     *
     * @return string|null
     */
    function tenant_name()
    {
        return tenant('app_name');
    }
}

if (!function_exists('tenant_rescode')) {
    /**
     * Get current tenant rescode
     *
     * @return string|null
     */
    function tenant_rescode()
    {
        return tenant('rescode');
    }
}

if (!function_exists('tenant_has_feature')) {
    /**
     * Check if tenant has specific feature enabled
     *
     * @param string $feature
     * @return bool
     */
    function tenant_has_feature($feature)
    {
        $features = tenant('features') ?? [];
        return $features[$feature] ?? false;
    }
}

if (!function_exists('tenant_config')) {
    /**
     * Get tenant config value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function tenant_config($key, $default = null)
    {
        // Try from tenant array - check both lowercase and uppercase for backward compatibility
        $value = tenant(strtolower($key));
        if ($value !== null) {
            return $value;
        }
        
        // Try uppercase version (for old data)
        $value = tenant(strtoupper($key));
        if ($value !== null) {
            return $value;
        }
        
        // Try from Laravel config (set by middleware)
        $configKey = strtolower($key);
        $value = config('tenant.' . $configKey);
        if ($value !== null) {
            return $value;
        }
        
        // Try from $_ENV (set by middleware)
        $envKey = strtoupper($key);
        if (isset($_ENV[$envKey])) {
            return $_ENV[$envKey];
        }
        
        // Return default
        return $default;
    }
}

if (!function_exists('tenant_upload_path')) {
    /**
     * Get tenant upload path (relative to public)
     *
     * @param string $subpath
     * @return string
     */
    function tenant_upload_path($subpath = '')
    {
        $basePath = config('tenant.paths.upload', '/upload');
        return $subpath ? $basePath . '/' . ltrim($subpath, '/') : $basePath;
    }
}

if (!function_exists('tenant_upload_full_path')) {
    /**
     * Get tenant upload full system path
     *
     * @param string $subpath
     * @return string
     */
    function tenant_upload_full_path($subpath = '')
    {
        $basePath = config('tenant.full_paths.upload', public_path('upload'));
        return $subpath ? $basePath . '/' . ltrim($subpath, '/') : $basePath;
    }
}

if (!function_exists('tenant_backup_path')) {
    /**
     * Get tenant backup path (relative to public)
     *
     * @param string $subpath
     * @return string
     */
    function tenant_backup_path($subpath = '')
    {
        $basePath = config('tenant.paths.backup', '/backup');
        return $subpath ? $basePath . '/' . ltrim($subpath, '/') : $basePath;
    }
}

if (!function_exists('tenant_users_path')) {
    /**
     * Get tenant users path (relative to public)
     *
     * @param string $subpath
     * @return string
     */
    function tenant_users_path($subpath = '')
    {
        $basePath = config('tenant.paths.users', '/users');
        return $subpath ? $basePath . '/' . ltrim($subpath, '/') : $basePath;
    }
}

if (!function_exists('tenant_wa_uploads_path')) {
    /**
     * Get tenant wa_uploads path (relative to public)
     *
     * @param string $subpath
     * @return string
     */
    function tenant_wa_uploads_path($subpath = '')
    {
        $basePath = config('tenant.paths.wa_uploads', '/wa_uploads');
        return $subpath ? $basePath . '/' . ltrim($subpath, '/') : $basePath;
    }
}

if (!function_exists('tenant_env')) {
    /**
     * Get tenant-specific environment variable
     * Priority: 1) Tenant JSON env_variables, 2) Global .env file, 3) Default value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function tenant_env($key, $default = null)
    {
        // Try from tenant's custom env_variables (JSON) first
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        
        if ($tenant && isset($tenant['env_variables'][$key])) {
            return $tenant['env_variables'][$key];
        }
        
        // Try from tenant config
        $value = config('tenant.' . strtolower($key));
        
        if ($value !== null) {
            return $value;
        }
        
        // Fallback to global .env file
        return env(strtoupper($key), $default);
    }
}

if (!function_exists('tenant_mail_config')) {
    /**
     * Get tenant mail configuration
     *
     * @param string $key (host, port, username, password, encryption)
     * @return mixed
     */
    function tenant_mail_config($key)
    {
        return config('mail.' . $key);
    }
}

if (!function_exists('tenant_db_config')) {
    /**
     * Get tenant database configuration
     *
     * @return array
     */
    function tenant_db_config()
    {
        return [
            'host' => tenant('db_host'),
            'port' => tenant('db_port'),
            'database' => tenant('db_database'),
            'username' => tenant('db_username'),
        ];
    }
}

if (!function_exists('tenant_asset')) {
    /**
     * Get tenant-specific asset URL
     * Assets are stored in public/tenants/{RESCODE}/
     *
     * @param string $path Path relative to tenant directory (e.g., 'img/logo.png')
     * @param string|null $fallback Fallback path if tenant asset not found
     * @return string
     */
    function tenant_asset($path, $fallback = null)
    {
        $rescode = tenant_rescode();
        
        if (!$rescode) {
            return $fallback ? asset($fallback) : asset($path);
        }
        
        $tenantPath = "tenants/{$rescode}/" . ltrim($path, '/');
        $fullPath = public_path($tenantPath);
        
        // Check if tenant-specific file exists
        if (file_exists($fullPath)) {
            return asset($tenantPath);
        }
        
        // Fallback to default asset
        return $fallback ? asset($fallback) : asset($path);
    }
}

if (!function_exists('tenant_img')) {
    /**
     * Get tenant-specific image URL
     * Shorthand for tenant_asset('img/...')
     *
     * @param string $filename Image filename (e.g., 'logo.png')
     * @param string|null $fallback Fallback path if tenant image not found
     * @return string
     */
    function tenant_img($filename, $fallback = null)
    {
        return tenant_asset('img/' . ltrim($filename, '/'), $fallback);
    }
}

