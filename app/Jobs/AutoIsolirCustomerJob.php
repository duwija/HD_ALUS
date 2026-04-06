<?php

namespace App\Jobs;

use App\Jobs\IsolirJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoIsolirCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $isolirdate = now()->day;

        // Ambil semua tenant aktif dari isp_master
        $tenants = \App\Tenant::on('isp_master')->get();

        foreach ($tenants as $tenantModel) {
            $tenant = $tenantModel->toTenantArray();
            $tenantQueue = $tenant['domain'];

            try {
                // Switch koneksi mysql ke DB tenant
                Config::set('database.connections.mysql.host',     $tenant['db_host']     ?? env('DB_HOST'));
                Config::set('database.connections.mysql.port',     $tenant['db_port']     ?? env('DB_PORT'));
                Config::set('database.connections.mysql.database', $tenant['db_database'] ?? env('DB_DATABASE'));
                Config::set('database.connections.mysql.username', $tenant['db_username'] ?? env('DB_USERNAME'));
                Config::set('database.connections.mysql.password', $tenant['db_password'] ?? env('DB_PASSWORD'));
                \DB::purge('mysql');
                \DB::reconnect('mysql');

                $customers = \App\Customer::select(
                    'customers.id', 'customers.customer_id', 'customers.name',
                    'customers.phone', 'customers.id_status', 'customers.isolir_date',
                    'suminvoices.payment_status'
                )
                ->leftJoin('suminvoices', 'suminvoices.id_customer', '=', 'customers.id')
                ->where('suminvoices.payment_status', 0)
                ->where(function ($q) use ($isolirdate) {
                    $q->where('customers.id_status', 2)
                      ->where('customers.isolir_date', $isolirdate);
                })
                ->groupBy('customers.id')
                ->get();

                $start = Carbon::now();
                $count = 0;

                // Set tenant context agar IsolirJob constructor bisa simpan tenantDomain
                app()->instance('tenant', $tenant);

                foreach ($customers as $cust) {
                    $count++;
                    IsolirJob::dispatch($cust->id, $cust->id_status)
                        ->onQueue($tenantQueue)
                        ->delay($start->addSeconds(5));
                    Log::channel('isolir')->info("[{$tenantQueue}] Auto Isolir: {$cust->customer_id} | {$cust->name}");
                }

                Log::channel('isolir')->info("[{$tenantQueue}] ✅ Total customer processed for isolir: {$count}");

            } catch (\Exception $e) {
                Log::channel('isolir')->error("[{$tenantQueue}] Gagal proses isolir: " . $e->getMessage());
            }
        }
    }
}

