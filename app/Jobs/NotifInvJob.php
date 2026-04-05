<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\EmailReminderInvJob;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Exception;
use App\Helpers\WaGatewayHelper;
use App\Services\WaService;

class NotifInvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum number of attempts before failing */
    public $tries = 3;

    /** Maximum seconds the job may run */
    public $timeout = 120;

    /** Seconds to wait before retrying (per attempt) */
    public $backoff = [30, 60];

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $phone;
    protected $name;
    protected $cid;
    protected $encryptedurl;
    protected $tenantDomain;


    public function __construct($phone, $name, $cid, $encryptedurl)
    {
        //
        $this->phone = $phone;
        $this->name = $name;
        $this->cid = $cid;
        $this->encryptedurl = $encryptedurl;

        // Simpan tenant domain saat job dibuat (HTTP context — tenant sudah di-resolve middleware)
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
        // Restore tenant context — queue worker berjalan tanpa HTTP/middleware
        $this->restoreTenantContext();

        //
       $response = null;
       $customer = \App\Customer::where('customer_id', $this->cid)->first();

       if (!$customer) {
        Log::channel('notif')->warning("Customer not found with CID: {$this->cid}");
        return;
    }

    if ($customer->notification == 1) {

        // Kirim via WA — provider dipilih otomatis dari tenant ENV: WA_PROVIDER
        // Pilihan: gateway (default) | qontak | fonnte | wablas
        $response = WaService::sendReminder(
            $customer->phone,
            $customer->name,
            $this->cid,
            $this->encryptedurl
        );

    } elseif ($customer->notification == 2) {
        // Email Notification
        if (!empty($customer->email)) {
            $data = [
                'phone' => $this->phone,
                'name'  => $this->name,
                'cid'   => $this->cid,
                'url'   => $this->encryptedurl,
            ];

            try {
                Mail::to($customer->email)->send(new EmailReminderInvJob($data));
                $response = 'Email sent';
            } catch (\Exception $e) {
                Log::channel('notif')->error("Gagal kirim email ke {$customer->email}: " . $e->getMessage());
            }
        } else {
            Log::channel('notif')->warning("Email kosong untuk customer {$this->name}");
        }

    } elseif ($customer->notification == 3) {
        // ── FCM Push Notification (Mobile App) ──
        if (empty($customer->fcm_token)) {
            Log::channel('notif')->warning("[FCM] CID {$this->cid} | {$this->name} — fcm_token kosong, skip push.");
            $response = 'FCM skipped: no token';
        } else {
            $fcmTitle = '🔔 Pengingat Tagihan';
            $fcmBody  = 'Halo ' . $customer->name . ', Anda masih memiliki tagihan yang belum dibayar. Segera selesaikan pembayaran agar layanan tetap dapat berjalan.';
            try {
                \App\Services\FcmService::send(
                    $customer->fcm_token,
                    $fcmTitle,
                    $fcmBody,
                    [
                        'type'        => 'reminder_invoice',
                        'customer_id' => $this->cid,
                        'url'         => $this->encryptedurl,
                    ]
                );

                // Simpan ke riwayat notifikasi aplikasi mobile
                \App\AppCustomerNotification::record(
                    (int) $customer->id,
                    $fcmTitle,
                    $fcmBody,
                    'reminder_invoice',
                    $this->encryptedurl
                );

                $response = 'FCM sent';
            } catch (\App\Exceptions\FcmTokenUnregisteredException $eFcmUnreg) {
                // Token tidak valid (app di-reinstall / uninstall) — hapus dari DB
                $customer->fcm_token = null;
                $customer->save();
                Log::channel('notif')->warning(
                    "[FCM] Token UNREGISTERED — dihapus dari DB | CID {$this->cid} | {$this->name}"
                );
                $response = 'FCM token cleared (UNREGISTERED)';
            } catch (\Exception $eFcm) {
                Log::channel('notif')->error('[FCM] NotifInvJob error: ' . $eFcm->getMessage());
                $response = 'FCM error: ' . $eFcm->getMessage();
            }
        }

    } else {
        Log::channel('notif')->info("CID {$this->cid} | notification=0, skip.");
        return;
    }

\Log::channel('notif')->info('Sent Remainder message to  CID '.$this->cid. ' | ' .$this->name . ' | '. $response); 

}

    /**
     * Re-apply tenant mail SMTP config di dalam job.
     * Queue worker berjalan tanpa HTTP request sehingga TenantMiddleware tidak dipanggil.
     * Method ini membaca config tenant dan set ulang ke Laravel mail config sebelum kirim email.
     */
    private function applyTenantMailConfig(): void
    {
        $map = [
            'mail_host'         => 'mail.mailers.smtp.host',
            'mail_port'         => 'mail.mailers.smtp.port',
            'mail_username'     => 'mail.mailers.smtp.username',
            'mail_password'     => 'mail.mailers.smtp.password',
            'mail_encryption'   => 'mail.mailers.smtp.encryption',
            'mail_from_address' => 'mail.from.address',
            'mail_from_name'    => 'mail.from.name',
        ];

        foreach ($map as $tenantKey => $configKey) {
            $value = tenant_config($tenantKey);
            if (!empty($value)) {
                Config::set($configKey, $value);
            }
        }

        // Pastikan mailer default adalah smtp
        Config::set('mail.default', tenant_config('mail_mailer', 'smtp'));

        Log::channel('notif')->info('[MAIL] Tenant mail config applied: host=' . tenant_config('mail_host', '-') . ' user=' . tenant_config('mail_username', '-'));
    }

    /**
     * Restore tenant context di dalam queue job.
     * Queue worker tidak melalui HTTP → TenantMiddleware tidak dijalankan.
     * Method ini me-load tenant dari DB master berdasarkan domain yang disimpan saat job dibuat,
     * lalu switch koneksi DB + set semua config tenant (mail, app, dsb).
     */
    private function restoreTenantContext(): void
    {
        if (empty($this->tenantDomain)) {
            Log::channel('notif')->warning('[TENANT] tenantDomain tidak tersimpan di job, skip restore.');
            return;
        }

        try {
            $tenantModel = \App\Tenant::on('isp_master')->where('domain', $this->tenantDomain)->first();
            if (!$tenantModel) {
                Log::channel('notif')->warning("[TENANT] Tenant '{$this->tenantDomain}' tidak ditemukan di isp_master.");
                return;
            }

            $tenant = $tenantModel->toTenantArray();

            // Set tenant ke app instance (agar tenant_config() berfungsi)
            app()->instance('tenant', $tenant);

            // Switch database connection ke tenant DB
            Config::set('database.connections.mysql.host',     $tenant['db_host']     ?? env('DB_HOST'));
            Config::set('database.connections.mysql.port',     $tenant['db_port']     ?? env('DB_PORT'));
            Config::set('database.connections.mysql.database', $tenant['db_database'] ?? env('DB_DATABASE'));
            Config::set('database.connections.mysql.username', $tenant['db_username'] ?? env('DB_USERNAME'));
            Config::set('database.connections.mysql.password', $tenant['db_password'] ?? env('DB_PASSWORD'));
            \DB::purge('mysql');
            \DB::reconnect('mysql');

            // Set mail config dari tenant
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

            // Set app config dari tenant
            Config::set('app.name',      $tenant['app_name']  ?? 'ISP Management');
            Config::set('app.signature', $tenant['signature'] ?? $tenant['app_name'] ?? '');

            Log::channel('notif')->info("[TENANT] Context restored: domain={$this->tenantDomain} db={$tenant['db_database']} mail=" . ($tenant['mail_username'] ?? '-'));

        } catch (\Exception $e) {
            Log::channel('notif')->error("[TENANT] Gagal restore context: " . $e->getMessage());
        }
    }
}
