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
     * Get tenant specific configuration
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function tenant_config($key, $default = null)
    {
        return tenant($key) ?? $default;
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

