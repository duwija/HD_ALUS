<?php

namespace App\Console\Commands;

use App\Jobs\IsolirJob;
use App\Traits\SendsCustomerNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IsolirAuto extends Command
{
    use SendsCustomerNotification;

    protected $signature   = 'isolir:auto {--date= : Tanggal isolir/bulan, default hari ini (1-31)}';
    protected $description = 'Auto-dispatch IsolirJob untuk semua tenant aktif berdasarkan isolir_date hari ini';

    public function handle(): int
    {
        $today = (int) ($this->option('date') ?? Carbon::now()->format('j'));
        $this->info("[AutoIsolir] isolir_date={$today} — memulai...");
        Log::info("[AutoIsolir] Mulai — isolir_date={$today}");

        $tenants = \App\Tenant::on('isp_master')->where('is_active', true)->get();
        $this->info("[AutoIsolir] Total tenant aktif: " . $tenants->count());

        foreach ($tenants as $tenantModel) {
            $this->processTenant($tenantModel->toTenantArray(), $today);
        }

        $this->info('[AutoIsolir] Selesai.');
        return 0;
    }

    private function processTenant(array $tenant, int $today): void
    {
        $db = $tenant['db_database'] ?? 'unknown';

        try {
            // Switch DB connection to tenant
            Config::set('database.connections.mysql.host',     $tenant['db_host']     ?? env('DB_HOST'));
            Config::set('database.connections.mysql.port',     $tenant['db_port']     ?? env('DB_PORT'));
            Config::set('database.connections.mysql.database', $db);
            Config::set('database.connections.mysql.username', $tenant['db_username'] ?? env('DB_USERNAME'));
            Config::set('database.connections.mysql.password', $tenant['db_password'] ?? env('DB_PASSWORD'));
            DB::purge('mysql');
            DB::reconnect('mysql');

            // Set tenant context (needed by tenant_config() helper)
            app()->instance('tenant', $tenant);

            // Redirect log channels to tenant folder
            $base = storage_path("logs/tenant_{$db}");
            if (!is_dir($base)) {
                @mkdir($base, 0775, true);
            }
            foreach (['isolir', 'notif', 'invoice', 'payment', 'auth'] as $ch) {
                Config::set("logging.channels.{$ch}.path", "{$base}/{$ch}.log");
                Log::forgetChannel($ch);
            }

            // Query customers eligible for isolir today
            $customers = \App\Customer::select(
                    'customers.id',
                    'customers.customer_id',
                    'customers.name',
                    'customers.id_status'
                )
                ->leftJoin('suminvoices', 'suminvoices.id_customer', '=', 'customers.id')
                ->where('suminvoices.payment_status', 0)
                ->where('customers.id_status', 2)
                ->where('customers.isolir_date', $today)
                ->groupBy('customers.id')
                ->get();

            if ($customers->isEmpty()) {
                Log::channel('isolir')->info("[AutoIsolir] tenant={$db} isolir_date={$today} — tidak ada customer eligible.");
                $this->line("  [{$db}] tidak ada customer eligible.");
                return;
            }

            $tenantQueue    = $tenant['domain'] ?? 'default';
            $longPauseEvery = (int) tenant_config('NOTIF_LONG_PAUSE_EVERY', rand(18, 27));
            $start          = Carbon::now();
            $count          = 0;

            foreach ($customers as $cust) {
                $count++;
                $delay = $this->messageDelay($count, $longPauseEvery);
                IsolirJob::dispatch($cust->id, $cust->id_status)
                    ->onQueue($tenantQueue)
                    ->delay((clone $start)->addSeconds($delay));

                Log::channel('isolir')->info(
                    "[AutoIsolir] Queued: CID {$cust->customer_id} | {$cust->name} | delay +{$delay}s"
                );
            }

            Log::channel('isolir')->info(
                "[AutoIsolir] tenant={$db} — dispatched {$count} IsolirJob(s) untuk isolir_date={$today}"
            );
            $this->info("  [{$db}] dispatched {$count} IsolirJob(s).");

        } catch (\Exception $e) {
            Log::error("[AutoIsolir] Error tenant={$db}: " . $e->getMessage());
            $this->error("  [{$db}] ERROR: " . $e->getMessage());
        }
    }
}
