<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tenant;
use Illuminate\Support\Facades\Storage;

class BackupTenantDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:backup-database 
                            {tenant? : Tenant ID or rescode (optional, backup all if not specified)}
                            {--compress : Compress backup file with gzip}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup tenant database(s) automatically';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenantParam = $this->argument('tenant');
        $compress = $this->option('compress');
        
        if ($tenantParam) {
            // Backup single tenant
            $tenant = Tenant::where('id', $tenantParam)
                           ->orWhere('rescode', $tenantParam)
                           ->first();
            
            if (!$tenant) {
                $this->error("Tenant not found: {$tenantParam}");
                return 1;
            }
            
            $this->backupTenant($tenant, $compress);
        } else {
            // Backup all active tenants
            $tenants = Tenant::where('is_active', 1)->get();
            
            $this->info("Starting backup for " . $tenants->count() . " tenant(s)...");
            
            foreach ($tenants as $tenant) {
                $this->backupTenant($tenant, $compress);
            }
        }
        
        $this->info("✓ Backup completed successfully!");
        return 0;
    }
    
    /**
     * Backup single tenant database
     */
    private function backupTenant($tenant, $compress = false)
    {
        $this->info("Backing up: {$tenant->app_name} ({$tenant->rescode})");
        
        $rescode = $tenant->rescode;
        $dbName = $tenant->db_database;
        $dbUser = $tenant->db_username;
        $dbPass = $tenant->db_password;
        $dbHost = $tenant->db_host ?? '127.0.0.1';
        $dbPort = $tenant->db_port ?? '3306';
        
        // Create backup directory if not exists
        $backupDir = public_path("tenants/{$rescode}/backup");
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
            @chown($backupDir, 'apache');
            @chgrp($backupDir, 'apache');
        }
        
        // Generate filename with timestamp
        $timestamp = date('Y-m-d_His');
        $filename = "{$dbName}_{$timestamp}.sql";
        $filepath = "{$backupDir}/{$filename}";
        
        // Build mysqldump command
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s 2>&1',
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbName),
            escapeshellarg($filepath)
        );
        
        // Execute backup
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            $this->error("  ✗ Failed to backup {$dbName}");
            \Log::error("Database backup failed for {$rescode}", [
                'output' => $output,
                'return_code' => $returnVar
            ]);
            return false;
        }
        
        // Fix file permissions
        if (file_exists($filepath)) {
            chmod($filepath, 0644);
            @chown($filepath, 'apache');
            @chgrp($filepath, 'apache');
            
            $filesize = filesize($filepath);
            $filesizeMB = round($filesize / 1024 / 1024, 2);
            
            // Compress if requested
            if ($compress) {
                exec("gzip {$filepath}", $gzipOutput, $gzipReturn);
                if ($gzipReturn === 0) {
                    $filepath .= '.gz';
                    $filesize = filesize($filepath);
                    $filesizeMB = round($filesize / 1024 / 1024, 2);
                    $this->info("  ✓ Compressed: {$filename}.gz ({$filesizeMB} MB)");
                } else {
                    $this->warn("  ! Compression failed, keeping uncompressed file");
                }
            }
            
            $this->info("  ✓ Saved: {$filename} ({$filesizeMB} MB)");
            
            // Clean old backups (keep last 7 days)
            $this->cleanOldBackups($backupDir, 7);
            
            return true;
        }
        
        $this->error("  ✗ Backup file not created");
        return false;
    }
    
    /**
     * Clean old backup files
     */
    private function cleanOldBackups($directory, $daysToKeep = 7)
    {
        $files = glob($directory . '/*.sql*');
        $now = time();
        $deleted = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileAge = $now - filemtime($file);
                $daysOld = $fileAge / 86400; // Convert to days
                
                if ($daysOld > $daysToKeep) {
                    unlink($file);
                    $deleted++;
                }
            }
        }
        
        if ($deleted > 0) {
            $this->info("  ✓ Cleaned {$deleted} old backup(s)");
        }
    }
}
