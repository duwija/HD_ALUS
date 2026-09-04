<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\RotatingFileHandler;

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
        $logger = new Logger('tenant');
        $logger->pushHandler(new TenantAwareStreamHandler());

        return $logger;
    }
}

/**
 * Resolves the tenant (and therefore the log file) on every write instead of
 * once when the channel is constructed.
 *
 * Laravel's LogManager caches the resolved channel for the lifetime of the
 * application container. In HTTP requests that's fine (fresh container per
 * request), but the queue worker here runs `queue:work --max-jobs=500` across
 * several tenants' queues in one long-lived process, reusing the same
 * container across jobs. A handler built once at construction time would keep
 * writing every subsequent job's logs (any tenant) to whichever tenant
 * resolved first, since restoring tenant context mid-worker doesn't rebuild
 * the channel.
 */
class TenantAwareStreamHandler extends AbstractProcessingHandler
{
    /** @var RotatingFileHandler[] */
    protected $handlers = [];

    protected function write(array $record): void
    {
        $tenantId = $this->resolveTenantId();

        if (!isset($this->handlers[$tenantId])) {
            $logPath = storage_path("logs/tenant_{$tenantId}/laravel.log");
            $logDir = dirname($logPath);
            if (!file_exists($logDir)) {
                mkdir($logDir, 0755, true);
            }
            // RotatingFileHandler writes to a dated file (laravel-YYYY-MM-DD.log) and
            // prunes files beyond maxFiles on rotation, same as config/logging.php's
            // 'daily' driver channels — plain StreamHandler never rotated or pruned,
            // so this file grew unbounded (tens of MB per tenant) forever.
            $this->handlers[$tenantId] = new RotatingFileHandler($logPath, 7, Logger::DEBUG);
        }

        $this->handlers[$tenantId]->handle($record);
    }

    /**
     * Get current tenant ID from the bound tenant instance, session, or config
     *
     * @return string
     */
    protected function resolveTenantId()
    {
        // Prefer the 'tenant' app instance. It's set both by TenantMiddleware
        // (HTTP requests) and by queue jobs that restore tenant context
        // (e.g. EnableMikrotikJob, IsolirJob) via app()->instance('tenant', ...),
        // so it's the only signal that's reliable from inside a queue worker.
        if (app()->bound('tenant')) {
            $tenant = app('tenant');
            $domain = is_array($tenant) ? ($tenant['domain'] ?? null) : null;
            if ($domain) {
                return explode('.', $domain)[0];
            }
        }

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
