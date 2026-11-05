<?php

namespace App\Console\Commands;

use App\Jobs\CreateInvJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Buat invoice bulanan otomatis — cadangan kalau admin lupa menjalankan menu Jobs.
 *
 * Diatur admin tenant di /settings/auto-isolir, disimpan di isp_master.tenants.features:
 *   auto_invoice_day    : 1..28 atau "last" (hari terakhir bulan); kosong = mati
 *   auto_invoice_period : "current" (bulan berjalan) | "next" (bulan berikutnya)
 *   auto_invoice_last   : periode (Y-m) terakhir yang sudah di-dispatch — cegah dispatch ganda
 *   auto_invoice_mode   : "date" (default, tanggal tetap di atas) | "isolir" (per pelanggan, H-x sebelum isolir)
 *   auto_invoice_before_isolir : 1..28 — x untuk mode "isolir"
 *
 * Mode "isolir": tiap hari, pelanggan Aktif/Blokir yang tanggal isolir berikutnya tinggal <= x hari
 * dan belum punya invoice bulanan untuk bulan isolir itu dibuatkan invoice (tanggal invoice = tgl 1
 * bulan itu, jatuh tempo = isolir_date - 1 seperti biasa). Karena memakai jendela "<= x hari",
 * hari yang terlewat (server mati, dsb.) otomatis tersusul keesokan harinya.
 *
 * Cara kerja sama dengan SuminvoiceController::createinvmonthlyJob (menu Jobs, tanpa filter merchant):
 * satu CreateInvJob per pelanggan Aktif/Blokir, jeda 10 detik. CreateInvJob sendiri melewati
 * pelanggan yang sudah punya invoice bulanan periode itu, jadi aman walau admin sudah membuat manual.
 */
class InvoiceAuto extends Command
{
    protected $signature   = 'invoice:auto
                              {--date= : Tanggal acuan Y-m-d, default hari ini}
                              {--dry-run : Hanya tampilkan tenant & jumlah pelanggan, tanpa dispatch}';
    protected $description = 'Buat invoice bulanan otomatis untuk tenant yang tanggal eksekusinya hari ini';

    public function handle(): int
    {
        $today  = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $dryRun = (bool) $this->option('dry-run');

        $this->info("[InvoiceAuto] tanggal acuan {$today->toDateString()}" . ($dryRun ? ' (DRY RUN)' : ''));

        $tenants = \App\Tenant::on('isp_master')->where('is_active', true)->get();

        foreach ($tenants as $tenantModel) {
            $features = (array) ($tenantModel->features ?? []);

            if (static::normalizeMode($features['auto_invoice_mode'] ?? null) === 'isolir') {
                $before = static::normalizeBeforeIsolir($features['auto_invoice_before_isolir'] ?? null);
                if ($before !== null) {
                    $this->processTenantByIsolir($tenantModel, $today, $before, $dryRun);
                }
                continue;
            }

            $day = static::normalizeDay($features['auto_invoice_day'] ?? null);

            if ($day === null || !static::isRunDay($day, $today)) {
                continue;
            }

            $invDate = static::invoiceDate($features['auto_invoice_period'] ?? 'current', $today);
            $periodKey = $invDate->format('Y-m');

            if (($features['auto_invoice_last'] ?? null) === $periodKey) {
                $this->line("  [{$tenantModel->domain}] periode {$periodKey} sudah dijalankan, skip.");
                continue;
            }

            $this->processTenant($tenantModel, $invDate, $dryRun);
        }

        $this->info('[InvoiceAuto] Selesai.');
        return 0;
    }

    /** 1..28 → int, "last" → "last", selain itu → null (mati). */
    public static function normalizeDay($day)
    {
        if ($day === 'last') {
            return 'last';
        }
        $day = (int) $day;

        return ($day >= 1 && $day <= 28) ? $day : null;
    }

    public static function normalizeMode($mode): string
    {
        return $mode === 'isolir' ? 'isolir' : 'date';
    }

    /** H-x sebelum isolir: 1..28, selain itu null (mati). */
    public static function normalizeBeforeIsolir($days): ?int
    {
        $days = (int) $days;

        return ($days >= 1 && $days <= 28) ? $days : null;
    }

    /**
     * Tanggal isolir berikutnya (> $today) untuk tanggal isolir $isolirDay.
     * Bulan yang lebih pendek (mis. isolir 29-31 di Februari) memakai hari terakhir bulan itu.
     */
    public static function nextIsolirDate(int $isolirDay, Carbon $today): Carbon
    {
        $month = (clone $today)->startOfMonth();
        $candidate = (clone $month)->day(min($isolirDay, $month->daysInMonth));
        if ($candidate->lte($today)) {
            $month->addMonthNoOverflow();
            $candidate = (clone $month)->day(min($isolirDay, $month->daysInMonth));
        }

        return $candidate->startOfDay();
    }

    public static function isRunDay($day, Carbon $date): bool
    {
        return $day === 'last'
            ? $date->day === $date->daysInMonth
            : $date->day === $day;
    }

    /** Tanggal invoice selalu tgl 1 bulan tagihan (sesuai praktik menu Jobs selama ini). */
    public static function invoiceDate(string $period, Carbon $date): Carbon
    {
        $first = (clone $date)->startOfMonth();

        return $period === 'next' ? $first->addMonthNoOverflow() : $first;
    }

    /** Pindah koneksi + channel log ke tenant ini. Mengembalikan array tenant. */
    private function connectTenant(\App\Tenant $tenantModel): array
    {
        $tenant = $tenantModel->toTenantArray();
        $db = $tenant['db_database'] ?? 'unknown';

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

        return $tenant;
    }

    /** Mode "isolir": invoice per pelanggan, H-$before sebelum tanggal isolir berikutnya. */
    private function processTenantByIsolir(\App\Tenant $tenantModel, Carbon $today, int $before, bool $dryRun): void
    {
        $db = $tenantModel->toTenantArray()['db_database'] ?? 'unknown';

        try {
            $tenant = $this->connectTenant($tenantModel);
            $today = (clone $today)->startOfDay();
            $limit = (clone $today)->addDays($before);

            $customers = \App\Customer::whereIn('id_status', [2, 4])
                ->whereBetween('isolir_date', [1, 31])
                ->get(['id', 'isolir_date']);

            // Bulan target hanya bisa bulan ini atau bulan depan; ambil invoice bulanan yang sudah ada
            // dengan kriteria yang sama dengan CreateInvJob (payment_status != 0).
            $periods = [(clone $today)->format('mY'), (clone $today)->startOfMonth()->addMonthNoOverflow()->format('mY')];
            $existing = \App\Invoice::where('monthly_fee', '1')
                ->whereIn('periode', $periods)
                ->where('payment_status', '!=', 0)
                ->get(['id_customer', 'periode'])
                ->mapWithKeys(fn ($i) => [$i->id_customer . '|' . $i->periode => true]);

            $todo = [];
            foreach ($customers as $c) {
                $isolir = static::nextIsolirDate((int) $c->isolir_date, $today);
                if ($isolir->gt($limit)) {
                    continue;
                }
                $invDate = (clone $isolir)->startOfMonth();
                if (isset($existing[$c->id . '|' . $invDate->format('mY')])) {
                    continue;
                }
                $todo[] = [$c->id, $invDate->toDateString()];
            }

            $byDate = collect($todo)->countBy(fn ($t) => $t[1])->all();
            $summary = empty($byDate) ? 'tidak ada' : collect($byDate)->map(fn ($n, $d) => "$n (tgl invoice $d)")->implode(', ');

            if ($dryRun) {
                $this->info("  [{$db}] dry-run H-{$before} isolir: " . count($todo) . " pelanggan perlu invoice — {$summary}.");
                return;
            }
            if (empty($todo)) {
                $this->line("  [{$db}] H-{$before} isolir: tidak ada invoice baru.");
                return;
            }

            $start = Carbon::now();
            $tenantQueue = $tenant['domain'] ?? 'default';
            foreach ($todo as [$customerId, $invDateStr]) {
                CreateInvJob::dispatch($customerId, $invDateStr)
                    ->onQueue($tenantQueue)
                    ->delay($start->addSeconds(10));
            }

            Log::channel('invoice')->info("[InvoiceAuto] tenant={$db} mode=H-{$before} isolir — dispatch " . count($todo) . " CreateInvJob: {$summary}");
            $this->info("  [{$db}] H-{$before} isolir: dispatch " . count($todo) . " invoice — {$summary}.");

        } catch (\Exception $e) {
            Log::error("[InvoiceAuto] Error tenant={$db} (mode isolir): " . $e->getMessage());
            $this->error("  [{$db}] ERROR: " . $e->getMessage());
        }
    }

    private function processTenant(\App\Tenant $tenantModel, Carbon $invDate, bool $dryRun): void
    {
        $tenant = $tenantModel->toTenantArray();
        $db = $tenant['db_database'] ?? 'unknown';

        try {
            $tenant = $this->connectTenant($tenantModel);

            // Sama dengan menu Jobs (createinvmonthlyJob) tanpa filter merchant.
            $customerIds = \App\Customer::where(function ($query) {
                    $query->where('customers.id_status', '2')
                        ->orWhere('customers.id_status', '4');
                })
                ->pluck('id');

            $invDateStr = $invDate->toDateString();

            if ($dryRun) {
                $this->info("  [{$db}] dry-run: {$customerIds->count()} pelanggan, tanggal invoice {$invDateStr}.");
                return;
            }

            // Tandai dulu sebelum dispatch: kalau command terpanggil ulang, periode ini tidak di-dispatch dua kali.
            DB::connection('isp_master')->table('tenants')
                ->where('id', $tenantModel->id)
                ->update([
                    'features' => DB::raw("JSON_SET(COALESCE(NULLIF(features, ''), '{}'), '$.auto_invoice_last', " . DB::connection('isp_master')->getPdo()->quote($invDate->format('Y-m')) . ')'),
                ]);

            $start = Carbon::now();
            $tenantQueue = $tenant['domain'] ?? 'default';
            foreach ($customerIds as $customerId) {
                CreateInvJob::dispatch($customerId, $invDateStr)
                    ->onQueue($tenantQueue)
                    ->delay($start->addSeconds(10));
            }

            Log::channel('invoice')->info("[InvoiceAuto] tenant={$db} — dispatch {$customerIds->count()} CreateInvJob, tanggal invoice {$invDateStr}");
            $this->info("  [{$db}] dispatch {$customerIds->count()} invoice, tanggal invoice {$invDateStr}.");

        } catch (\Exception $e) {
            Log::error("[InvoiceAuto] Error tenant={$db}: " . $e->getMessage());
            $this->error("  [{$db}] ERROR: " . $e->getMessage());
        }
    }
}
