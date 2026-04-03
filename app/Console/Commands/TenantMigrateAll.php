<?php

namespace App\Console\Commands;

use App\Tenant;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TenantMigrateAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate-all
                            {--tenant= : Filter by tenant domain, rescode, or id}
                            {--include-inactive : Include inactive tenants}
                            {--path= : Run only a specific migration path}
                            {--pretend : Dump SQL queries without executing migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Laravel migrations for all registered tenant databases';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenants = Tenant::query()
            ->when(!$this->option('include-inactive'), function ($query) {
                $query->where('is_active', true);
            })
            ->when($this->option('tenant'), function ($query, $tenantFilter) {
                $query->where(function ($subQuery) use ($tenantFilter) {
                    $subQuery->where('domain', $tenantFilter)
                        ->orWhere('rescode', $tenantFilter);

                    if (is_numeric($tenantFilter)) {
                        $subQuery->orWhere('id', (int) $tenantFilter);
                    }
                });
            })
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found for migration.');
            return 1;
        }

        $this->info('Starting tenant migrations...');
        $this->line('');

        $successCount = 0;
        $failedCount  = 0;
        $php          = PHP_BINARY;
        $artisan      = base_path('artisan');

        foreach ($tenants as $tenant) {
            $label = sprintf('%s | %s | %s', $tenant->id, $tenant->domain, $tenant->db_database);
            $this->info('Migrating tenant: ' . $label);

            if (empty($tenant->db_database) || empty($tenant->db_username)) {
                $this->error('  Skipped: missing DB configuration.');
                $failedCount++;
                $this->line('');
                continue;
            }

            // Build command array
            $cmd = [$php, $artisan, 'migrate', '--database=mysql', '--force', '--no-interaction'];

            if ($this->option('path')) {
                $cmd[] = '--path=' . $this->option('path');
            }

            if ($this->option('pretend')) {
                $cmd[] = '--pretend';
            }

            // Pass tenant DB credentials as env overrides so each subprocess
            // runs in its own PHP process — avoids class redeclaration across tenants.
            $env = array_merge($_ENV, [
                'DB_HOST'     => $tenant->db_host     ?: '127.0.0.1',
                'DB_PORT'     => (string) ($tenant->db_port ?: '3306'),
                'DB_DATABASE' => $tenant->db_database,
                'DB_USERNAME' => $tenant->db_username,
                'DB_PASSWORD' => $tenant->db_password ?? '',
            ]);

            $process = new Process($cmd, base_path(), $env, null, 120);

            try {
                $process->run();
                $output = trim($process->getOutput() . $process->getErrorOutput());

                if (!$process->isSuccessful()) {
                    throw new \RuntimeException($output ?: 'Exit code ' . $process->getExitCode());
                }

                if ($output !== '') {
                    $this->line($this->indentOutput($output));
                }

                $this->info('  OK');
                $successCount++;
            } catch (\Throwable $e) {
                $this->error('  FAILED: ' . $e->getMessage());
                $failedCount++;
            }

            $this->line('');
        }

        $this->info('Tenant migration finished.');
        $this->line('Success : ' . $successCount);
        $this->line('Failed  : ' . $failedCount);

        return $failedCount > 0 ? 1 : 0;
    }

    private function indentOutput(string $output): string
    {
        return preg_replace('/^/m', '  ', $output);
    }
}
