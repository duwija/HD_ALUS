<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tenant;

class TenantMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate-from-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing tenants from config file to database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Migrating tenants from config/tenants.php to database...');
        $this->line('');

        $tenants = config('tenants.list');
        
        if (empty($tenants)) {
            $this->error('No tenants found in config file!');
            return 1;
        }

        $migrated = 0;
        $skipped = 0;

        foreach ($tenants as $domain => $config) {
            // Check if tenant already exists
            $existing = Tenant::where('domain', $domain)->first();
            
            if ($existing) {
                $this->warn("  ⊘ Skipped: {$domain} (already exists)");
                $skipped++;
                continue;
            }

            // Create new tenant
            try {
                Tenant::create([
                    'domain' => $domain,
                    'app_name' => $config['app_name'] ?? 'ISP',
                    'signature' => $config['signature'] ?? $config['app_name'],
                    'rescode' => $config['rescode'] ?? 'XX',
                    'db_host' => $config['db_host'] ?? '127.0.0.1',
                    'db_port' => $config['db_port'] ?? '3306',
                    'db_database' => $config['db_database'],
                    'db_username' => $config['db_username'] ?? 'root',
                    'db_password' => $config['db_password'] ?? '',
                    'mail_from' => $config['mail_from'] ?? null,
                    'whatsapp_token' => $config['whatsapp_token'] ?? null,
                    'xendit_key' => $config['xendit_key'] ?? null,
                    'features' => $config['features'] ?? [],
                    'is_active' => true,
                ]);

                $this->info("  ✓ Migrated: {$domain} ({$config['rescode']})");
                $migrated++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$domain} - " . $e->getMessage());
            }
        }

        $this->line('');
        $this->info("Migration completed!");
        $this->line("  Migrated: {$migrated}");
        $this->line("  Skipped:  {$skipped}");
        $this->line('');
        $this->comment('Run: php artisan cache:clear');

        return 0;
    }
}
