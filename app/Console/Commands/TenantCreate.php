<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tenant;
use Illuminate\Support\Facades\DB;

class TenantCreate extends Command
{
    protected $signature = 'tenant:create 
        {domain : Tenant domain}
        {--app-name= : Application name}
        {--rescode= : Tenant rescode (2-3 chars)}
        {--db-name= : Database name}
        {--db-user=root : Database username}
        {--db-pass= : Database password}
        {--create-db : Create database automatically}';
    
    protected $description = 'Create a new tenant';

    public function handle()
    {
        $domain = $this->argument('domain');
        
        // Check if tenant exists
        if (Tenant::where('domain', $domain)->exists()) {
            $this->error("Tenant with domain '{$domain}' already exists!");
            return 1;
        }

        // Get or ask for required info
        $appName = $this->option('app-name') ?: $this->ask('Application name');
        $rescode = $this->option('rescode') ?: $this->ask('Rescode (2-3 chars)');
        $dbName = $this->option('db-name') ?: $this->ask('Database name');
        $dbUser = $this->option('db-user');
        $dbPass = $this->option('db-pass') ?: $this->secret('Database password');

        // Validate rescode
        if (Tenant::where('rescode', $rescode)->exists()) {
            $this->error("Rescode '{$rescode}' already used!");
            return 1;
        }

        // Create database if requested
        if ($this->option('create-db')) {
            try {
                DB::statement("CREATE DATABASE IF NOT EXISTS {$dbName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->info("✓ Database '{$dbName}' created");
            } catch (\Exception $e) {
                $this->error("Failed to create database: " . $e->getMessage());
                return 1;
            }
        }

        // Create tenant
        try {
            $tenant = Tenant::create([
                'domain' => $domain,
                'app_name' => $appName,
                'signature' => $appName,
                'rescode' => strtoupper($rescode),
                'db_host' => '127.0.0.1',
                'db_port' => '3306',
                'db_database' => $dbName,
                'db_username' => $dbUser,
                'db_password' => $dbPass,
                'mail_from' => 'admin@' . $domain,
                'features' => [
                    'accounting' => true,
                    'ticketing' => true,
                    'whatsapp' => true,
                    'payment_gateway' => true,
                ],
                'is_active' => true,
            ]);

            $this->info("✓ Tenant created successfully!");
            $this->line("");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Domain', $tenant->domain],
                    ['App Name', $tenant->app_name],
                    ['Rescode', $tenant->rescode],
                    ['Database', $tenant->db_database],
                ]
            );
            
            $this->line("");
            $this->comment("Next steps:");
            $this->line("1. Create storage dirs: mkdir -p storage/tenants/{$tenant->rescode}/{logs,app/public}");
            $this->line("2. Create public dirs: mkdir -p public/tenants/{$tenant->rescode}/{storage,upload,backup,users}");
            $this->line("3. Setup nginx config");
            $this->line("4. Setup SSL certificate");
            $this->line("5. Point DNS to server");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create tenant: " . $e->getMessage());
            return 1;
        }
    }
}
