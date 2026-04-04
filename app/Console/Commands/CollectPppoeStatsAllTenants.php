<?php

namespace App\Console\Commands;

use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RouterOS\Client;
use RouterOS\Query;
use Carbon\Carbon;

class CollectPppoeStatsAllTenants extends Command
{
    protected $signature   = 'pppoe:collect-stats-all
                            {--tenant= : Filter by tenant domain (optional)}';
    protected $description = 'Collect PPPoE stats for ALL active tenants';

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

            $this->collectForCurrentTenant();

            $this->line('');
        }

        // Restore default connection
        DB::purge('mysql');
        DB::reconnect('mysql');

        $this->info('Done.');
        return 0;
    }

    protected function switchDatabase(Tenant $tenant)
    {
        $dbUser = $tenant->db_username ?: env('DB_USERNAME');
        $dbPass = $tenant->db_password ?: env('DB_PASSWORD');

        Config::set('database.connections.mysql.host',     $tenant->db_host     ?: env('DB_HOST'));
        Config::set('database.connections.mysql.port',     $tenant->db_port     ?: env('DB_PORT'));
        Config::set('database.connections.mysql.database', $tenant->db_database);
        Config::set('database.connections.mysql.username', $dbUser);
        Config::set('database.connections.mysql.password', $dbPass);

        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    protected function collectForCurrentTenant()
    {
        $routers = \App\Distrouter::all();

        if ($routers->isEmpty()) {
            $this->line('  No routers found for this tenant.');
            return;
        }

        $now       = Carbon::now();
        $collected = 0;

        foreach ($routers as $router) {
            try {
                $client = new Client([
                    'host'    => $router->ip,
                    'user'    => $router->user,
                    'pass'    => $router->password,
                    'port'    => (int) $router->port,
                    'timeout' => 5,
                ]);

                // Active PPPoE sessions
                $active      = 0;
                $onlineNames = [];
                try {
                    $result      = $client->query(new Query('/ppp/active/print'))->read();
                    $active      = count($result);
                    $onlineNames = collect($result)->pluck('name')->toArray();
                } catch (\Exception $e) {
                    // leave as 0
                }

                // All secrets
                $total    = 0;
                $offline  = 0;
                $disabled = 0;
                try {
                    $secrets = $client->query(new Query('/ppp/secret/print'))->read();
                    $total   = count($secrets);
                    foreach ($secrets as $s) {
                        if (isset($s['disabled']) && $s['disabled'] === 'true') {
                            $disabled++;
                        } elseif (!in_array($s['name'], $onlineNames)) {
                            $offline++;
                        }
                    }
                } catch (\Exception $e) {
                    // leave as 0
                }

                \App\PppoeStat::create([
                    'distrouter_id' => $router->id,
                    'total'         => $total,
                    'active'        => $active,
                    'offline'       => $offline,
                    'disabled'      => $disabled,
                    'collected_at'  => $now,
                ]);

                $collected++;
                $this->line("  ✓ {$router->name} — total:{$total} active:{$active} offline:{$offline} disabled:{$disabled}");

            } catch (\Exception $e) {
                \Log::warning("[PppoeStats] Router {$router->name} ({$router->ip}) error: " . $e->getMessage());
                $this->warn("  ✗ {$router->name}: " . $e->getMessage());
            }
        }

        // Bersihkan data > 7 hari untuk tenant ini
        \App\PppoeStat::where('collected_at', '<', Carbon::now()->subDays(7))->delete();

        $this->line("  Collected: {$collected}/{$routers->count()} routers.");
    }
}
