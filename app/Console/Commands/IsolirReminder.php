<?php

namespace App\Console\Commands;

use App\Traits\SendsCustomerNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Kirim pengingat tagihan H-x sebelum tanggal isolir pelanggan.
 *
 * Hari H-x diatur admin tenant di /settings/auto-isolir dan disimpan di
 * isp_master.tenants.features.isolir_reminder_days (mis. [3, 1]).
 * Tetap jalan walaupun auto_isolir tenant dimatikan.
 * Pesan memakai jalur reminder yang sudah ada (NotifInvJob → template WA_TAMPLATE_ID_4 / email / FCM).
 */
class IsolirReminder extends Command
{
    use SendsCustomerNotification;

    protected $signature   = 'isolir:reminder
                              {--date= : Tanggal acuan Y-m-d, default hari ini}
                              {--dry-run : Hanya tampilkan pelanggan yang akan dikirimi, tanpa dispatch}';
    protected $description = 'Kirim pengingat tagihan H-x sebelum isolir_date untuk semua tenant aktif';

    public function handle(): int
    {
        $baseDate = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $dryRun   = (bool) $this->option('dry-run');

        $this->info("[IsolirReminder] tanggal acuan {$baseDate->toDateString()}" . ($dryRun ? ' (DRY RUN)' : ''));
        Log::info("[IsolirReminder] Mulai — tanggal acuan {$baseDate->toDateString()}" . ($dryRun ? ' (dry-run)' : ''));

        $tenants = \App\Tenant::on('isp_master')->where('is_active', true)->get();

        foreach ($tenants as $tenantModel) {
            $days = static::normalizeDays(((array) ($tenantModel->features ?? []))['isolir_reminder_days'] ?? []);
            if (empty($days)) {
                continue;
            }

            $this->processTenant($tenantModel->toTenantArray(), $baseDate, $days, $dryRun);
        }

        $this->info('[IsolirReminder] Selesai.');
        return 0;
    }

    /**
     * Bersihkan daftar hari: integer unik 1..28, urut menurun (H-7 lebih dulu dari H-1).
     */
    public static function normalizeDays($days): array
    {
        $days = array_map('intval', (array) $days);
        $days = array_filter($days, fn ($d) => $d >= 1 && $d <= 28);
        $days = array_values(array_unique($days));
        rsort($days);

        return $days;
    }

    private function processTenant(array $tenant, Carbon $baseDate, array $days, bool $dryRun): void
    {
        $db = $tenant['db_database'] ?? 'unknown';

        try {
            Config::set('database.connections.mysql.host',     $tenant['db_host']     ?? env('DB_HOST'));
            Config::set('database.connections.mysql.port',     $tenant['db_port']     ?? env('DB_PORT'));
            Config::set('database.connections.mysql.database', $db);
            Config::set('database.connections.mysql.username', $tenant['db_username'] ?? env('DB_USERNAME'));
            Config::set('database.connections.mysql.password', $tenant['db_password'] ?? env('DB_PASSWORD'));
            DB::purge('mysql');
            DB::reconnect('mysql');

            app()->instance('tenant', $tenant);

            $base = storage_path("logs/tenant_{$db}");
            if (!is_dir($base)) {
                @mkdir($base, 0775, true);
            }
            foreach (['isolir', 'notif', 'invoice', 'payment', 'auth'] as $ch) {
                Config::set("logging.channels.{$ch}.path", "{$base}/{$ch}.log");
                Log::forgetChannel($ch);
            }

            // Sama dengan IsolirAuto: pencocokan isolir_date persis dengan tanggal (hari ini + x).
            $targetDays = [];
            foreach ($days as $x) {
                $targetDays[(int) (clone $baseDate)->addDays($x)->format('j')] = $x;
            }

            $customers = \App\Customer::select(
                    'customers.id',
                    'customers.customer_id',
                    'customers.name',
                    'customers.phone',
                    'customers.isolir_date',
                    'customers.notification'
                )
                ->leftJoin('suminvoices', 'suminvoices.id_customer', '=', 'customers.id')
                ->where('suminvoices.payment_status', 0)
                ->where('customers.id_status', 2)
                ->where('customers.notification', '!=', 0)
                ->whereIn('customers.isolir_date', array_keys($targetDays))
                ->groupBy('customers.id')
                ->get();

            $label = implode(',', array_map(fn ($x) => "H-{$x}", $days));

            if ($customers->isEmpty()) {
                Log::channel('notif')->info("[IsolirReminder] tenant={$db} {$label} — tidak ada pelanggan.");
                $this->line("  [{$db}] {$label}: tidak ada pelanggan.");
                return;
            }

            $start          = Carbon::now();
            $longPauseEvery = (int) tenant_config('NOTIF_LONG_PAUSE_EVERY', 1000);
            $index          = 0;

            foreach ($customers as $cust) {
                $x = $targetDays[(int) $cust->isolir_date];

                if ($dryRun) {
                    $this->line("  [{$db}] H-{$x} CID {$cust->customer_id} | {$cust->name} | isolir tgl {$cust->isolir_date}");
                    continue;
                }

                $index++;
                $encryptedurl = '/invoice/cst/' . Crypt::encryptString($cust->id);
                $this->dispatchCustomerNotif($cust, $encryptedurl, $start, $index, $longPauseEvery);

                Log::channel('notif')->info(
                    "[IsolirReminder] H-{$x} CID {$cust->customer_id} | {$cust->name} | isolir tgl {$cust->isolir_date}"
                );
            }

            Log::channel('notif')->info(
                "[IsolirReminder] tenant={$db} {$label} — " . ($dryRun ? 'dry-run ' : 'dispatched ') . $customers->count() . ' pengingat'
            );
            $this->info("  [{$db}] {$label}: " . $customers->count() . ' pelanggan' . ($dryRun ? ' (dry-run)' : ' dikirimi pengingat') . '.');

        } catch (\Exception $e) {
            Log::error("[IsolirReminder] Error tenant={$db}: " . $e->getMessage());
            $this->error("  [{$db}] ERROR: " . $e->getMessage());
        }
    }
}
