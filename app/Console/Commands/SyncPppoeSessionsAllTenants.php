<?php

namespace App\Console\Commands;

use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncPppoeSessionsAllTenants extends Command
{
    protected $signature   = 'pppoe:sync-sessions-all
                            {--tenant= : Filter by tenant domain (optional)}';
    protected $description = 'Sync PPPoE session online/offline status for ALL active tenants';

    public function handle()
    {
        $tenants = Tenant::on('isp_master')
            ->where('is_active', true)
            ->when($this->option('tenant'), function ($q, $domain) {
                $q->where('domain', $domain);
            })
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');
            return 0;
        }

        $this->info("Found {$tenants->count()} active tenant(s).");
        $this->line('');

        foreach ($tenants as $tenant) {
            $this->info("=== Tenant: {$tenant->domain} | DB: {$tenant->db_database} ===");

            if (empty($tenant->db_database)) {
                $this->warn('  Skipped: no db_database configured.');
                $this->line('');
                continue;
            }

            try {
                $this->switchDatabase($tenant);
            } catch (\Exception $e) {
                $this->error("  Cannot connect to DB: " . $e->getMessage());
                $this->line('');
                continue;
            }

            // Delegate to the per-tenant sync command
            $this->call('pppoe:sync-sessions');

            $this->line('');
        }

        // Restore default connection
        DB::purge('mysql');
        DB::reconnect('mysql');

        $this->info('All tenant sessions synced.');
        return 0;
    }

    protected function switchDatabase(Tenant $tenant)
    {
        $dbUser = $tenant->db_username ?: env('DB_USERNAME');
        $dbPass = $tenant->db_password ?: env('DB_PASSWORD');
        $dbHost = $tenant->db_host     ?: env('DB_HOST', '127.0.0.1');
        $dbPort = $tenant->db_port     ?: env('DB_PORT', '3306');

        Config::set('database.connections.mysql.host',     $dbHost);
        Config::set('database.connections.mysql.port',     $dbPort);
        Config::set('database.connections.mysql.database', $tenant->db_database);
        Config::set('database.connections.mysql.username', $dbUser);
        Config::set('database.connections.mysql.password', $dbPass);

        DB::purge('mysql');
        DB::reconnect('mysql');

        // Verify connection
        DB::connection('mysql')->getPdo();
    }
}
