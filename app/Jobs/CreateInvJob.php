<?php

namespace App\Jobs;

use App\Customer;
use App\Invoice;
use App\Suminvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use App\Mail\EmailNotification;
use Illuminate\Support\Facades\Mail;
use App\Helpers\WaGatewayHelper;
use Illuminate\Support\Facades\Config;

class CreateInvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [30, 60];

    protected $customerId;
    protected $inv_date;
    protected $tenantDomain;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($customerId, $inv_date)
    {
        $this->customerId = $customerId;
        $this->inv_date = $inv_date;
        $tenant = app('tenant');
        $this->tenantDomain = $tenant['domain'] ?? null;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->restoreTenantContext();

        $customer = Customer::where('customers.id', $this->customerId)
        ->join('plans', 'customers.id_plan', '=', 'plans.id')
        ->select('customers.*', 'plans.name as plan', 'plans.price as price')
        ->lockForUpdate()
        ->first();

        if (!$customer) {
            Log::error('Customer not found: ' . $this->customerId);
            return;
        }

        // Load add-ons for this customer
        $customerModel = \App\Customer::with('addons')->find($this->customerId);
        $addons = $customerModel ? $customerModel->addons : collect();

        $month = Carbon::parse($this->inv_date)->format('mY');
        $period = Carbon::parse($this->inv_date)->format('F Y'); 
        $latest_number = uniqid();
        $invdate = Carbon::parse($this->inv_date)->format("Y-m-d");
        $notif='';
        $code = substr(md5(uniqid('', true)), 0, 10);


        $check_invoice = Invoice::where('id_customer', $customer->id)
        ->where('periode', $month)
        ->where('monthly_fee', '1')
        ->where('payment_status', '!=', 0)
        ->first();

        if ($check_invoice) {

            \Log::channel('invoice')->warning('CID : '. $customer->customer_id. ' |' . $customer->name . ' already have monthly Inv => INFO!! ');
            return;
        }
        else
        {

           $tempcode = sha1(time()) . rand();

           DB::beginTransaction();

           try {
            // Step 1: Create main plan line item
            Invoice::create([
                'id_customer' => $customer->id,
                'monthly_fee' => '1',
                'periode' => $month,
                'description' => 'Monthly fee package ' . $customer->plan,
                'qty' => '1',
                'amount' => $customer->price,
                'payment_status' => 3,
                'tax' => '0',
                'tempcode' => $tempcode,
                'created_by' => 'System',
            ]);

            // Step 1b: Create add-on line items
            $addons_total = 0;
            foreach ($addons as $addon) {
                Invoice::create([
                    'id_customer' => $customer->id,
                    'monthly_fee' => '1',
                    'periode' => $month,
                    'description' => 'Add-on: ' . $addon->name,
                    'qty' => '1',
                    'amount' => $addon->price,
                    'payment_status' => 3,
                    'tax' => '0',
                    'tempcode' => $tempcode,
                    'created_by' => 'System',
                ]);
                $addons_total += $addon->price;
            }

            // Step 2: Create INV
            $due_date_isolir = $customer->isolir_date - 1;
            $due_date = ($due_date_isolir < 1) ? null : Carbon::parse($invdate)->format("Y-m-" . $due_date_isolir);

            $tax = $customer->tax;
            $base_price   = $customer->price + $addons_total;
            $total_amount = $base_price + ($base_price * $tax / 100);
            $result= Suminvoice::create([
                'id_customer' => $customer->id,
                'number' => $latest_number,
                'date' => $invdate,
                'payment_status' => 0,
                'tax' => $tax,
                'tempcode' => $tempcode,
                'due_date' => $due_date,
                'total_amount' => $total_amount,
            ]);



            $total_tax = $base_price * $tax / 100;

// Data dasar jurnal
            $base_data = [
                'tax_total' => $total_tax,
                'date' => $invdate,
                'reff' => $tempcode,
                'type' => 'jumum',
                'description' => 'Invoice #' . $latest_number . ' | ' . $customer->name,
                'note' => 'Invoice #' . $latest_number . ' | ' . $customer->customer_id . ' | ' . $customer->name,
                'contact_id' => $customer->id,
                'code' => $code,
            ];

// 1. Catat KREDIT ke akun Pendapatan
            \App\Jurnal::create(array_merge($base_data, [
                'id_akun' => '4-40000',
                'kredit' => $base_price,
            ]));

// 2. Jika ada pajak, catat KREDIT ke akun Pajak
            if (!empty($tax) && $tax != 0) {
                \App\Jurnal::create(array_merge($base_data, [
                    'id_akun' => '2-20500',
                    'kredit' => $total_tax,
                ]));
            }

//  3. Terakhir, catat DEBIT ke akun Kas/Bank
            \App\Jurnal::create(array_merge($base_data, [
                'id_akun' => '1-10100',
    'debet' => $total_amount, // price + total_tax
]));



            DB::commit();
            if ($result) {

                $encryptedUrl = Crypt::encryptString($customer->id);
                $duedate = $due_date ?: 'N/A';
                if ($customer->notification == 1) {
                    // WhatsApp Notification
                    
                    // Check WA Provider from tenant config
                    $waProvider = tenant_config('wa_provider', 'gateway'); // default: gateway

                    if ($waProvider === 'qontak') {
                        // Use Qontak WhatsApp API
                        $response = qontak_whatsapp_helper_info_new_inv(
                            $customer->phone,
                            $customer->name,
                            $customer->customer_id,
                            $total_amount,
                            $duedate,
                            "/invoice/cst/" . $encryptedUrl
                        );
                    } else {
                        // Use Regular WA Gateway
                        $message = "*[Informasi Pembayaran Internet]*";
                        $message .= "\n\n";
                        $message .= "Yth. " . $customer->name . ",";
                        $message .= "\n\n";
                        $message .= "Tagihan Anda dengan Customer ID (CID) *" . $customer->customer_id . "* telah diterbitkan.";
                        $message .= "\n*Total Tagihan:* Rp." . number_format($total_amount, 0, ',', '.') . "";
                        $message .= "\n*Batas Pembayaran:* " . $due_date;
                        $message .= "\n\n";
                        $message .= "Untuk informasi lebih lanjut, silakan klik link berikut:";
                        $message .= "\n" . "http://" . tenant_config('domain_name', env("DOMAIN_NAME")) . "/invoice/cst/" . $encryptedUrl;
                        $message .= "\n\n";
                        $message .= "Jika sudah melakukan pembayaran, abaikan pesan ini.";
                        $message .= "\nJika ada pertanyaan, hubungi CS kami di ".tenant_config('payment_wa', env("PAYMENT_WA"));
                        $message .= "\n\n";
                        $message .= "".tenant_config('signature', env("SIGNATURE"))."";
                        $msgresult = WaGatewayHelper::wa_payment($customer->phone, $message);
                    }

                    $notif='Notification by WhatsApp';

               } elseif ($customer->notification == 2) {
        // Email Notification
                if (!empty($customer->email)) {
                    $data = [
                        'phone' => $customer->phone,
                        'name' => $customer->name,
                        'customer_id' => $customer->customer_id,
                        'number' => "#" . $latest_number,
                        'total_amount' => $total_amount,
                        'date' => $invdate,
                        'due_date' => $duedate,
                        'url' => "/invoice/cst/" .$encryptedUrl,
                    ];

                    try {
                        Mail::to($customer->email)->send(new EmailNotification($data));
                        $notif='Notification by Email '.$e->getMessage();
                    } catch (\Exception $e) {
                       \Log::error("Gagal kirim email ke {$customer->email} untuk {$customer->name} ({$customer->customer_id}): " . $e->getMessage());
                       $notif='Notification by Email ERROR';

                   }
               }
           }
       }

       // ── FCM Push Notification (selalu dikirim jika ada fcm_token) ──
       if (!empty($customer->fcm_token)) {
           try {
               \App\Services\FcmService::send(
                   $customer->fcm_token,
                   '📄 Tagihan Baru Tersedia',
                   'Tagihan bulan ini untuk ' . $customer->name . ' sebesar Rp ' . number_format($total_amount, 0, ',', '.') . ' telah diterbitkan.',
                   [
                       'type'        => 'new_invoice',
                       'customer_id' => $customer->customer_id,
                       'amount'      => $total_amount,
                       'due_date'    => $due_date ?? '',
                       'url'         => '/tagihan',
                   ]
               );
           } catch (\App\Exceptions\FcmTokenUnregisteredException $eFcmUnreg) {
               $customer->fcm_token = null;
               $customer->save();
               \Log::channel('notif')->warning('[FCM] Token UNREGISTERED — dihapus dari DB | CID ' . $customer->customer_id . ' | ' . $customer->name);
           } catch (\Exception $eFcm) {
               \Log::channel('notif')->error('[FCM] CreateInvJob error: ' . $eFcm->getMessage());
           }
       }

       \Log::channel('invoice')->info('CID : '. $customer->customer_id. ' |' . $customer->name . ' Created monthly Inv |'. $notif);

   } catch (Exception $e) {
    DB::rollback();
    \Log::channel('invoice')->error('Failed to create invoice for CID : ' . $customer->customer_id . ' |' . $customer->name . ' | Error: ' . $e->getMessage());
}
}
}

    private function restoreTenantContext(): void
    {
        if (empty($this->tenantDomain)) {
            \Log::channel('invoice')->warning('[TENANT] tenantDomain tidak tersimpan di job, skip restore.');
            return;
        }

        try {
            $tenantModel = \App\Tenant::on('isp_master')->where('domain', $this->tenantDomain)->first();
            if (!$tenantModel) {
                \Log::channel('invoice')->warning("[TENANT] Tenant '{$this->tenantDomain}' tidak ditemukan di isp_master.");
                return;
            }

            $tenant = $tenantModel->toTenantArray();

            app()->instance('tenant', $tenant);

            $dbConfig = [
                'host'     => $tenant['db_host']     ?? env('DB_HOST'),
                'port'     => $tenant['db_port']     ?? env('DB_PORT'),
                'database' => $tenant['db_database'] ?? env('DB_DATABASE'),
                'username' => $tenant['db_username'] ?? env('DB_USERNAME'),
                'password' => $tenant['db_password'] ?? env('DB_PASSWORD'),
            ];
            foreach ($dbConfig as $key => $value) {
                Config::set('database.connections.mysql.' . $key, $value);
            }
            \DB::purge('mysql');
            \DB::reconnect('mysql');

            $mailMap = [
                'mail_host'         => 'mail.mailers.smtp.host',
                'mail_port'         => 'mail.mailers.smtp.port',
                'mail_username'     => 'mail.mailers.smtp.username',
                'mail_password'     => 'mail.mailers.smtp.password',
                'mail_encryption'   => 'mail.mailers.smtp.encryption',
                'mail_from_address' => 'mail.from.address',
                'mail_from_name'    => 'mail.from.name',
            ];
            foreach ($mailMap as $tenantKey => $configKey) {
                if (!empty($tenant[$tenantKey])) {
                    Config::set($configKey, $tenantKey === 'mail_port' ? (int) $tenant[$tenantKey] : $tenant[$tenantKey]);
                }
            }
            if (!empty($tenant['mail_mailer'])) {
                Config::set('mail.default', $tenant['mail_mailer']);
            }

            Config::set('app.name',      $tenant['app_name']  ?? 'ISP Management');
            Config::set('app.signature', $tenant['signature'] ?? $tenant['app_name'] ?? '');

            \Log::channel('invoice')->info("[TENANT] Context restored: domain={$this->tenantDomain} db={$tenant['db_database']}");

        } catch (\Exception $e) {
            \Log::channel('invoice')->error("[TENANT] Gagal restore context: " . $e->getMessage());
        }
    }
}
