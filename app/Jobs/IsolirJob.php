<?php

namespace App\Jobs;
use Illuminate\Support\Facades\DB;
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
    public $tries = 3; // Job akan dicoba maksimal 3 kali
    public $timeout = 300; // Timeout per eksekusi job (dalam detik)
    public $backoff = 900; // 900 detik = 15 menit
    protected $id;
    protected $status;
    public function __construct($id, $status)
    {
        //


        $this->id = $id;

        $this->status = $status;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {


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

}
