<?php

namespace App\Console\Commands;

use App\Services\MikrotikConfigBackup;
use App\Services\TenantDatabaseSwitcher;
use Illuminate\Console\Command;

/** Backup konfigurasi semua router MikroTik (distrouter) di semua tenant aktif. */
class MikrotikBackupAll extends Command
{
    protected $signature = 'mikrotik:backup
                            {--tenant= : Hanya domain tenant ini}
                            {--router= : Hanya distrouter dengan ID ini (butuh --tenant)}';

    protected $description = 'Backup konfigurasi MikroTik (export) lewat API ke storage terenkripsi; hapus backup router & dump database tenant > 7 hari';

    public function handle()
    {
        $tenants = \App\Tenant::on('isp_master')->where('is_active', true)
            ->when($this->option('tenant'), fn ($q, $d) => $q->where('domain', $d))
            ->orderBy('app_name')->get();

        $service = new MikrotikConfigBackup;
        $summary = ['ok' => 0, 'unchanged' => 0, 'failed' => 0, 'unsupported' => 0];

        foreach ($tenants as $tenantModel) {
            $tenant = $tenantModel->toTenantArray();
            if (!TenantDatabaseSwitcher::switchTo($tenant)) {
                $this->error("[{$tenant['domain']}] gagal switch DB, dilewati");
                continue;
            }
            app()->instance('tenant', $tenant);

            $routers = \App\Distrouter::query()
                ->when($this->option('router'), fn ($q, $id) => $q->where('id', $id))
                ->orderBy('id')->get();

            foreach ($routers as $router) {
                $b = $service->backup($router, (int) $tenantModel->id);
                $summary[$b->status] = ($summary[$b->status] ?? 0) + 1;
                $this->line(sprintf('[%s] #%d %s: %s%s', $tenant['domain'], $router->id, $router->name,
                    strtoupper($b->status), $b->message ? ' — ' . $b->message : ''));
            }
        }

        // Retensi 7 hari: backup konfigurasi router + dump database tenant (TenantBackupStorage).
        $pruned = MikrotikConfigBackup::prune();
        $prunedDumps = \App\Services\TenantBackupStorage::pruneAll();
        $this->info(sprintf('%s selesai: %d tersimpan, %d tidak berubah, %d gagal, %d perlu upgrade RouterOS; dihapus (>%d hari): %d backup router, %d dump database',
            now()->format('Y-m-d H:i'), $summary['ok'], $summary['unchanged'], $summary['failed'], $summary['unsupported'],
            MikrotikConfigBackup::RETENTION_DAYS, $pruned, $prunedDumps));

        return 0;
    }
}
