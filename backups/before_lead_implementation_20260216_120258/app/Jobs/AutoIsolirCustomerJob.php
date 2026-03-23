<?php

namespace App\Jobs;

use App\Customer;
use App\Jobs\IsolirJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoIsolirCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $isolirdate = now()->day; // Misalnya isolir_date = tanggal hari ini

        $customers = Customer::select('customers.id', 'customers.customer_id', 'customers.name', 'customers.phone', 'customers.id_status', 'customers.isolir_date', 'suminvoices.payment_status')
        ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
        ->where("suminvoices.payment_status", "=", 0)
        ->where(function ($query) use ($isolirdate) {
            $query->where("customers.id_status", "=", 2)
            ->where("customers.isolir_date", "=", $isolirdate);
        })
        ->groupBy('customers.id')
        ->get();

        $start = Carbon::now();
        $count = 0;

        foreach ($customers as $cust) {
            $count++;
            IsolirJob::dispatch($cust->id, $cust->id_status)->delay($start->addSeconds(5));
            Log::channel('isolir')->info("Auto Isolir: {$cust->customer_id} | {$cust->name}");
        }

        Log::channel('isolir')->info("✅ Total customer processed for isolir: {$count}");
    }
}
