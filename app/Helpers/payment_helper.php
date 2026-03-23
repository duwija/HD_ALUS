<?php

use App\Suminvoice;
use App\Customer;
use App\Jurnal;
use App\Customerlog;
use App\Distrouter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Crypt;

if (!function_exists('process_payment_success')) {
    /**
     * Handle payment success (Winpay, Tripay, Manual)
     *
     * @param  \App\Suminvoice  $invoice
     * @param  array $payload
     * @param  int|float $amount_received
     * @param  int|float $nett
     * @param  string $date
     * @param  string $updatedBy (WINPAY/TRIPAY/MANUAL)
     * @param  string $channel (misalnya: BCA VA, QRIS, Kasir)
     */
    function process_payment_success($invoice, $payload, $amount_received, $nett, $date, $updatedBy, $channel)
    {
        $customers = Customer::find($invoice->id_customer);
        if (!$customers) {
            Log::channel('payment')->error("Customer tidak ditemukan untuk invoice {$invoice->number}");
            return;
        }

        $oldStatus = $customers->status_name->name ?? 'Unknown';
        $changes = [];
        $msg = '';

        // --- WhatsApp ---
        try {
            qontak_whatsapp_helper_receive_payment_confirmation(
                $customers->phone,
                $customers->name,
                $invoice->number,
                $customers->customer_id,
                $amount_received,
                "/invoice/cst/" . Crypt::encryptString($customers->id)
            );
        } catch (\Exception $e) {
            Log::channel('payment')->warning("WA gagal: " . $e->getMessage());
        }

        // --- Telegram ---
        if (tenant_config('phyton_dir') && tenant_config('telegram_group_payment', env("TELEGRAM_GROUP_PAYMENT"))) {
            $notif_group = "[ONLINE PAYMENT]\n\n" .
            "CID: {$customers->customer_id}\n" .
            "Nama: {$customers->name}\n" .
            "SUDAH DITERIMA\n" .
            "Jumlah: Rp " . number_format($amount_received, 0, ',', '.') . "\n" .
            "Oleh: {$updatedBy} | {$channel}\n" .
            "👉 " . url("/suminvoice/" . $invoice->tempcode) . "\n\n" .
            "Terima kasih\n~ " . tenant_config('signature', env("SIGNATURE")) . " ~";

            $scriptPath = rtrim(tenant_config('phyton_dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "telegram_send_to_group.py";
            $process = new Process([
                "python3", $scriptPath,
                tenant_config('telegram_group_payment', env("TELEGRAM_GROUP_PAYMENT")), $notif_group
            ]);
            $process->setTimeout(20);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::channel('payment')->error("Telegram gagal", [
                    'stderr' => $process->getErrorOutput(),
                    'stdout' => $process->getOutput(),
                ]);
            }
        }

        // --- Jurnal ---
        try {
            Jurnal::create([
                'date' => $date,
                'reff' => $invoice->tempcode . 'receive',
                'type' => 'jumum',
                'description' => "Receive Payment #{$invoice->number} | {$customers->name}",
                'note' => "Receive Payment {$updatedBy} #{$invoice->number} | {$customers->customer_id} | {$customers->name}",
                'id_akun' => '1-10040',
                'debet' => $nett,
                'contact_id' => $customers->id
            ]);

            if ($nett < $invoice->total_amount) {
                Jurnal::create([
                    'date' => $date,
                    'reff' => $invoice->tempcode . 'receive',
                    'type' => 'jumum',
                    'description' => "Receive Payment #{$invoice->number} | {$customers->name}",
                    'note' => "Selisih fee",
                    'id_akun' => '6-60249',
                    'debet' => $invoice->total_amount - $nett,
                    'contact_id' => $customers->id
                ]);
            }

            Jurnal::create([
                'date' => $date,
                'reff' => $invoice->tempcode . 'receive',
                'type' => 'jumum',
                'description' => "Receive Payment #{$invoice->number} | {$customers->name}",
                'note' => "Kredit Online Payment",
                'id_akun' => '1-10100',
                'kredit' => $invoice->total_amount,
                'contact_id' => $customers->id
            ]);
        } catch (\Exception $e) {
            Log::channel('payment')->error("Gagal entri jurnal: " . $e->getMessage());
        }

        // --- Update status customer ---
        try {
            if ($customers->id_status == 4 && Suminvoice::where('payment_status', 0)->where('id_customer', $invoice->id_customer)->count() <= 0) {
                Customer::where('id', $invoice->id_customer)->update(['id_status' => 2]);
                if ($distrouter = Distrouter::withTrashed()->where('id', $customers->id_distrouter)->first()) {
                    try {
                        Distrouter::mikrotik_enable(
                            $distrouter->ip,
                            $distrouter->user,
                            $distrouter->password,
                            $distrouter->port,
                            $customers->pppoe
                        );
                    } catch (\Exception $e) {
                        Log::channel('payment')->warning("Gagal mikrotik_enable untuk {$customers->customer_id}: " . $e->getMessage());
                    }
                }
                $msg = "Diaktifkan kembali karena tidak ada invoice unpaid.";
                $changes = ['Status' => ['old' => $oldStatus, 'new' => 'Active']];
            }
        } catch (\Exception $e) {
            Log::channel('payment')->error("Gagal update status customer: " . $e->getMessage());
        }

        // --- Customer Log ---
        if (!empty($changes)) {
            Customerlog::create([
                'id_customer' => $customers->id,
                'date' => now(),
                'updated_by' => $updatedBy,
                'topic' => 'payment',
                'updates' => json_encode($changes),
            ]);
            Log::channel('payment')->info("[{$updatedBy}] Pelanggan ID: {$customers->customer_id} | INV: {$invoice->number} | {$msg}");
        }
    }
}
