<?php

namespace App\Console\Commands;
use App\Jobs\IsolirJob;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;

class CustomerIsolirCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customerisolir:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \Log::channel('isolir')->info('STARTING ISOLIR CUSTOMER PROCESS');
        $customer = \App\Customer::select ('customers.id','customers.customer_id','customers.name', 'customers.phone','customers.id_status','customers.isolir_date', 'suminvoices.payment_status', 'customers.deleted_at')
        ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
        ->where("suminvoices.payment_status", "=", 0)

        ->where(function ($query) {
         $query ->where("customers.id_status", "=", 2)
         ->Where("customers.isolir_date", "=", date('d'));
     })

        ->groupBy('customers.id')
        ->get();

        \Log::channel('isolir')->info('--------------');
        $start = Carbon::now();

        $count =0;
        foreach($customer as $customer) {

         $count = $count +1;
         \Log::channel('isolir')->info('***************');

         IsolirJob::dispatch($customer->id, $customer->id_status)->delay($start->addSeconds(10));
         \Log::channel('isolir')->info('Set Customer :'.$customer->customer_id. ' | ' .$customer->name." to Blocked"); 
        //return 0;
     }

     \Log::channel('isolir')->info('END ISOLIR CUSTOMER PROCESS');
 }
}
