<?php

namespace App\Console\Commands;

use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class PrunePingsAllTenants extends Command
{
    protected $signature   = 'pings:prune-all
                            {--tenant= : Filter by tenant domain (optional)}
                            {--days=7 : Retention in days}
                            {--chunk=5000 : Rows deleted per batch}';
    protected $description = 'Delete pings records older than N days (default 7) for ALL active tenants';

    public function handle()
    {
        $days  = (int) $this->option('days');
        $chunk = (int) $this->option('chunk');

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

        $this->info("Found {$tenants->count()} active tenant(s). Retention: {$days} day(s).");
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
                $this->error('  Cannot connect to DB: ' . $e->getMessage());
                $this->line('');
                continue;
            }

            $this->pruneForCurrentTenant($days, $chunk);

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

    protected function pruneForCurrentTenant(int $days, int $chunk)
    {
        if (!Schema::hasTable('pings')) {
            $this->line('  No pings table for this tenant.');
            return;
        }

        // idx_polled_at bikin WHERE polled_at < ... dan DELETE-nya pakai range scan,
        // bukan full table scan — penting karena tabel ini terus di-insert live oleh probe.
        try {
            DB::statement('ALTER TABLE pings ADD INDEX IF NOT EXISTS idx_polled_at (polled_at)');
        } catch (\Exception $e) {
            $this->warn('  Gagal memastikan index polled_at: ' . $e->getMessage());
        }

        $cutoff = Carbon::now()->subDays($days);
        $totalDeleted = 0;

        // Hapus per batch supaya tidak mengunci tabel lama di satu transaksi besar.
        while (true) {
            $deleted = DB::table('pings')
                ->where('polled_at', '<', $cutoff)
                ->limit($chunk)
                ->delete();

            $totalDeleted += $deleted;

            if ($deleted < $chunk) {
                break;
            }

            usleep(100000); // 100ms jeda antar batch
        }

        $this->line("  Deleted: {$totalDeleted} row(s) older than {$cutoff->toDateTimeString()}.");
    }
}
