<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tenant;

class FixTenantPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:fix-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix permissions for all tenant storage and public directories';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Fixing tenant permissions...');
        $this->info('');

        $tenants = Tenant::all();
        $basePath = base_path();

        foreach ($tenants as $tenant) {
            $rescode = $tenant->rescode;
            $this->info("Processing tenant: {$tenant->app_name} ({$rescode})");

            // Define directories
            $directories = [
                "{$basePath}/storage/tenants/{$rescode}",
                "{$basePath}/storage/tenants/{$rescode}/logs",
                "{$basePath}/storage/tenants/{$rescode}/app",
                "{$basePath}/storage/tenants/{$rescode}/app/public",
                "{$basePath}/public/tenants/{$rescode}",
                "{$basePath}/public/tenants/{$rescode}/storage",
                "{$basePath}/public/tenants/{$rescode}/upload",
                "{$basePath}/public/tenants/{$rescode}/backup",
                "{$basePath}/public/tenants/{$rescode}/users",
            ];

            foreach ($directories as $dir) {
                // Create if not exists
                if (!is_dir($dir)) {
                    if (mkdir($dir, 0775, true)) {
                        $this->line("  ✓ Created: {$dir}");
                    } else {
                        $this->error("  ✗ Failed to create: {$dir}");
                        continue;
                    }
                }

                // Fix permissions
                chmod($dir, 0775);
                $this->line("  ✓ Permissions set: {$dir}");
            }

            // Create and fix log file
            $logFile = "{$basePath}/storage/tenants/{$rescode}/logs/laravel.log";
            if (!file_exists($logFile)) {
                touch($logFile);
                $this->line("  ✓ Created log file: {$logFile}");
            }
            chmod($logFile, 0664);
            $this->line("  ✓ Log file permissions set");

            $this->info('');
        }

        $this->info('Done! Now run as root:');
        $this->warn("chown -R nginx:nginx {$basePath}/storage/tenants/");
        $this->warn("chown -R nginx:nginx {$basePath}/public/tenants/");

        return 0;
    }
}
