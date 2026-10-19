<?php

namespace App\Console\Commands;

use App\Mail\DailyReportMail;
use App\Services\DailyReportService;
use App\Services\TenantDatabaseSwitcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Kirim laporan harian (data kemarin) ke email penerima tiap tenant.
 * Penerima diatur admin tenant di /settings/auto-isolir → isp_master.tenants.features.daily_report_emails.
 */
class DailyReport extends Command
{
    protected $signature   = 'report:daily
                              {--date= : Tanggal data Y-m-d, default kemarin}
                              {--tenant= : Hanya untuk domain tenant ini}
                              {--dry-run : Susun laporan tanpa mengirim email}';
    protected $description = 'Kirim email laporan harian ke penerima tiap tenant';

    public function handle(): int
    {
        $date   = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
        $dryRun = (bool) $this->option('dry-run');

        $tenants = \App\Tenant::on('isp_master')->where('is_active', true)
            ->when($this->option('tenant'), fn ($q, $d) => $q->where('domain', $d))
            ->get();

        foreach ($tenants as $tenantModel) {
            $emails = static::normalizeEmails(((array) ($tenantModel->features ?? []))['daily_report_emails'] ?? []);
            if (empty($emails)) {
                continue;
            }

            $tenant = $tenantModel->toTenantArray();
            try {
                if (!static::applyTenantContext($tenant)) {
                    $this->error("  [{$tenant['domain']}] gagal switch DB, skip.");
                    continue;
                }

                $report = (new DailyReportService)->build($date);
                $s = $report['summary'];
                $line = "{$s['payments_count']} bayar, {$s['issued_count']} terbit, {$s['cancelled_count']} batal, "
                      . "{$s['new_subs']} baru, {$s['terminated']} terminate, {$s['isolated']} isolir";

                if ($dryRun) {
                    $this->line("  [{$tenant['domain']}] dry-run {$date->toDateString()}: {$line} → " . implode(', ', $emails));
                    continue;
                }

                Mail::to($emails)->send(new DailyReportMail($report, (string) ($tenant['app_name'] ?? $tenant['domain'])));

                Log::channel('notif')->info("[DailyReport] {$date->toDateString()} terkirim ke " . implode(', ', $emails) . " — {$line}");
                $this->info("  [{$tenant['domain']}] terkirim ke " . implode(', ', $emails) . " — {$line}");
            } catch (\Throwable $e) {
                Log::error("[DailyReport] Error tenant={$tenant['domain']}: " . $e->getMessage());
                $this->error("  [{$tenant['domain']}] ERROR: " . $e->getMessage());
            }
        }

        return 0;
    }

    /** Email unik & valid, maksimal 10. */
    public static function normalizeEmails($emails): array
    {
        $emails = array_map(fn ($e) => strtolower(trim((string) $e)), (array) $emails);
        $emails = array_filter($emails, fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL));

        return array_slice(array_values(array_unique($emails)), 0, 10);
    }

    /**
     * Pasang konteks tenant untuk proses CLI: DB, tenant(), log channel, nama aplikasi & konfigurasi email
     * (pemetaan sama dengan CreateInvJob::restoreTenantContext, plus fallback mail_from).
     */
    public static function applyTenantContext(array $tenant): bool
    {
        if (!TenantDatabaseSwitcher::switchTo($tenant)) {
            return false;
        }
        app()->instance('tenant', $tenant);

        $db = $tenant['db_database'] ?? 'unknown';
        foreach (['isolir', 'notif', 'invoice', 'payment', 'auth'] as $ch) {
            Config::set("logging.channels.{$ch}.path", storage_path("logs/tenant_{$db}/{$ch}.log"));
            Log::forgetChannel($ch);
        }

        // Kembalikan dulu ke konfigurasi mail dasar agar nilai tenant sebelumnya tidak tertinggal
        // (mis. tenant ini tidak punya mail_host sendiri).
        static $baseMail = null;
        $baseMail ??= config('mail');
        Config::set('mail', $baseMail);

        $mailMap = [
            'mail_host'       => 'mail.mailers.smtp.host',
            'mail_port'       => 'mail.mailers.smtp.port',
            'mail_username'   => 'mail.mailers.smtp.username',
            'mail_password'   => 'mail.mailers.smtp.password',
            'mail_encryption' => 'mail.mailers.smtp.encryption',
            'mail_from_name'  => 'mail.from.name',
        ];
        foreach ($mailMap as $key => $configKey) {
            if (!empty($tenant[$key])) {
                Config::set($configKey, $key === 'mail_port' ? (int) $tenant[$key] : $tenant[$key]);
            }
        }
        if (!empty($tenant['mail_from_address']) || !empty($tenant['mail_from'])) {
            Config::set('mail.from.address', $tenant['mail_from_address'] ?? $tenant['mail_from']);
        }
        if (empty($tenant['mail_from_name'])) {
            Config::set('mail.from.name', $tenant['app_name'] ?? config('app.name'));
        }
        if (!empty($tenant['mail_mailer'])) {
            Config::set('mail.default', $tenant['mail_mailer']);
        }
        Config::set('app.name', $tenant['app_name'] ?? config('app.name'));
        Config::set('app.url', 'https://' . ($tenant['domain'] ?? ''));

        // Mailer di-cache per proses — buang agar tenant berikutnya tidak memakai SMTP tenant sebelumnya.
        Mail::purge();

        return true;
    }
}
