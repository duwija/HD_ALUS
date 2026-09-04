<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class PruneTenantLogFiles extends Command
{
    protected $signature   = 'logs:prune-tenants {--days=7 : Retention in days}';
    protected $description = 'Delete storage/logs/tenant_*/*.log files older than N days (default 7)';

    public function handle()
    {
        $days   = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days)->getTimestamp();

        $dirs = glob(storage_path('logs/tenant_*'), GLOB_ONLYDIR) ?: [];
        $totalDeleted = 0;

        foreach ($dirs as $dir) {
            foreach (glob($dir . '/*.log') as $file) {
                if (filemtime($file) < $cutoff) {
                    unlink($file);
                    $totalDeleted++;
                }
            }
        }

        $this->info("Deleted {$totalDeleted} tenant log file(s) older than {$days} day(s).");
        return 0;
    }
}
