<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tenant;

class TenantList extends Command
{
    protected $signature = 'tenant:list {--all : Show all tenants including inactive}';
    protected $description = 'List all tenants';

    public function handle()
    {
        $tenants = $this->option('all') 
            ? Tenant::all() 
            : Tenant::getAllActive();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found!');
            return 1;
        }

        $this->table(
            ['ID', 'Domain', 'App Name', 'Rescode', 'Database', 'Active'],
            $tenants->map(function($t) {
                return [
                    $t->id,
                    $t->domain,
                    $t->app_name,
                    $t->rescode,
                    $t->db_database,
                    $t->is_active ? '✓' : '✗'
                ];
            })
        );

        return 0;
    }
}
