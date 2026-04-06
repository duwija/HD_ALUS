<?php

namespace App\Jobs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;


class IsolirJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $tries = 3;
    public $timeout = 300;
    public $backoff = 900;
    protected $id;
    protected $status;
    protected $tenantDomain;

    public function __construct($id, $status)
    {
        $this->id = $id;
        $this->status = $status;
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

        $attempt = $this->attempts(); // Dapatkan jumlah percobaan



        $customers = \App\Customer::Where('id',$this->id)->first();
        if (!$customers) {
            Log::channel('isolir')->warning("Customer ID {$this->id} not found.");
            return;
        }

        if ($attempt > 1) {
            Log::channel('isolir')->notice("Retry Attempt #{$attempt} untuk isolir customer: {$customers->name} (ID: {$customers->id})");
        }


        $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();

        if (!$distrouter) {
            Log::channel('isolir')->error("Distrouter tidak ditemukan untuk customer {$customers->name} (id_distrouter: {$customers->id_distrouter}). Job dihentikan.");
            return; // Tidak perlu retry — data memang tidak ada
        }

        $oldStatus = optional($customers->status_name)->name ?? 'Unknown';


        DB::beginTransaction();

        try {
            \App\Customer::where('id', $this->id)->update([
                'id_status' => 4,
            ]);

            // Ambil nama customer untuk log
            //$customerName = $customer->name ?? "Unknown";

        // Perubahan status
            $changes = [
                'Status' => [
                'old' => $oldStatus ?? 'Unknown',  // Status lama, misal: Active
                'new' => 'Blocked',  // Status baru
            ],
        ];

        // Tentukan siapa yang mengubah status (karena ini job, kita anggap "System Job")
        $updatedBy = 'System Job';

        // File log untuk customer
      //  $logFile = "customers/customer_{$this->id}.log";

        // Membuat log message
        $logMessage = now() . " - {$customers->name} updated by {$updatedBy} - Changes: " . json_encode($changes) . PHP_EOL;

        \App\Customerlog::create([
            'id_customer' => $customers->id,
            'date' => now(),
            'updated_by' => $updatedBy,
            'topic' => 'isolir',
            'updates' => json_encode($changes),
        ]);

        


        \App\Distrouter::mikrotik_disable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);



        DB::commit();
        \Log::channel('isolir')->info('Set Customer :'.$customers->customer_id. ' | ' .$customers->name." |".$logMessage);
    } catch (\Exception $e) {
        DB::rollback();
        \Log::channel('isolir')->error(' Attempt #'.$attempt.' Set Customer :'.$customers->customer_id. ' | ' .$customers->name." to Rollback | Canceled Blocking WARNING !!!  ". $e->getMessage()); 

        throw $e; // <-- WAJIB agar Laravel retry
    }

}

    /**
     * Dipanggil setelah semua retry habis (3x gagal).
     * Catat sebagai critical error — perlu penanganan manual.
     */
    public function failed(\Throwable $exception)
    {
        $customers = \App\Customer::find($this->id);
        $name = $customers->name ?? 'ID:'.$this->id;

        \Log::channel('isolir')->critical(
            "[FAILED] Isolir GAGAL setelah {$this->tries}x retry untuk customer: {$name} | Error: " . $exception->getMessage()
        );

        // Simpan ke tabel mikrotik_sync_failures untuk penanganan manual
        $distrouter = $customers ? \App\Distrouter::withTrashed()->find($customers->id_distrouter) : null;

        \App\MikrotikSyncFailure::record(
            $customers ? (int) $customers->id : null,
            $customers->name          ?? ('ID:' . $this->id),
            $customers->customer_id   ?? null,
            'disable',
            $customers->pppoe         ?? null,
            $distrouter               ? (int) $distrouter->id   : null,
            $distrouter               ? $distrouter->ip         : null,
            $exception->getMessage(),
            $this->tries
        );
    }

    private function restoreTenantContext(): void
    {
        if (empty($this->tenantDomain)) return;

        try {
            $tenantModel = \App\Tenant::on('isp_master')->where('domain', $this->tenantDomain)->first();
            if (!$tenantModel) return;

            $tenant = $tenantModel->toTenantArray();
            app()->instance('tenant', $tenant);

            // Re-arahkan log channel ke folder tenant yang benar
            $this->switchTenantLogChannels($tenant['db_database'] ?? env('DB_DATABASE', 'default'));

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

            Log::channel('isolir')->info("[TENANT] Context restored: domain={$this->tenantDomain} db={$tenant['db_database']}");
        } catch (\Exception $e) {
            Log::channel('isolir')->error('[TENANT] Gagal restore context: ' . $e->getMessage());
        }
    }

    /**
     * Update path channel log ke folder tenant yang aktif dan reset cache Monolog.
     */
    private function switchTenantLogChannels(string $dbDatabase): void
    {
        $base = storage_path("logs/tenant_{$dbDatabase}");
        if (!is_dir($base)) {
            @mkdir($base, 0775, true);
        }
        foreach (['notif', 'isolir', 'invoice', 'payment', 'auth'] as $ch) {
            Config::set("logging.channels.{$ch}.path", "{$base}/{$ch}.log");
            Log::forgetChannel($ch);
        }
    }

}
