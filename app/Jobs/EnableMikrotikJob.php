<?php

namespace App\Jobs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Customer;
use App\Distrouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use App\Helpers\WaGatewayHelper;
use Symfony\Component\Process\Exception\ProcessFailedException;

class EnableMikrotikJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customerId;
    protected $tenantDomain;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
        $tenant = app('tenant');
        $this->tenantDomain = $tenant['domain'] ?? null;
    }

    public function handle()
    {
        $this->restoreTenantContext();

        $customer = Customer::withTrashed()->find($this->customerId);
        if (!$customer) {
            \Log::error("Customer not found with ID {$this->customerId}");
            return;
        }

        $distrouter = Distrouter::withTrashed()->find($customer->id_distrouter);
        if (!$distrouter) {
            \Log::error("Distrouter not found with ID {$customer->id_distrouter}");
            return;
        }

        $maxRetries = 3;
        $success = false;

        for ($i = 1; $i <= $maxRetries; $i++) {
            try {
                Distrouter::mikrotik_enable(
                    $distrouter->ip,
                    $distrouter->user,
                    $distrouter->password,
                    $distrouter->port,
                    $customer->pppoe
                );
                $success = true;
                break;
            } catch (\Exception $e) {
                \Log::warning("Enable Mikrotik failed (try $i) for Customer ID {$customer->id}: " . $e->getMessage());
                sleep(10); // Tunggu 5 detik sebelum mencoba lagi
            }
        }

        if ($success) {
            // Update status customer jadi aktif normal
            $customer->id_status = 2;
            $customer->save();

            
        } else {
            \Log::error("Enable Mikrotik failed permanently for Customer ID {$customer->id}");

            $messages = "\u274c Enable PPPOE Mikrotik GAGAL untuk Customer ID {$customer->id} ({$customer->name}) setelah {$maxRetries} percobaan, silahakan info ke NOC untuk melakuakn Action MANUAL.";
            Log::channel('payment')->error( $messages);

            // Simpan ke tabel mikrotik_sync_failures untuk penanganan manual
            \App\MikrotikSyncFailure::record(
                (int) $customer->id,
                $customer->name,
                $customer->customer_id,
                'enable',
                $customer->pppoe,
                (int) $distrouter->id,
                $distrouter->ip,
                'Gagal enable PPPoE setelah ' . $maxRetries . ' percobaan internal',
                $maxRetries
            );
        }
    }

    private function restoreTenantContext(): void
    {
        if (empty($this->tenantDomain)) return;

        try {
            $tenantModel = \App\Tenant::on('isp_master')->where('domain', $this->tenantDomain)->first();
            if (!$tenantModel) return;

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

            \Log::info("[TENANT] EnableMikrotikJob context restored: domain={$this->tenantDomain} db={$tenant['db_database']}");
        } catch (\Exception $e) {
            \Log::error('[TENANT] EnableMikrotikJob gagal restore context: ' . $e->getMessage());
        }
    }

    protected function sendTelegramNotification($message)
    {
        $process = new Process([
            "python3",
            env("PHYTON_DIR") . "telegram_send_to_group.py",
            env("TELEGRAM_GROUP_PAYMENT"),
            $message
        ]);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            \Log::info("Telegram notification sent: " . $message);
        } catch (\Exception $e) {
            \Log::error("Failed to send Telegram notification: " . $e->getMessage());
        }
    }
}
