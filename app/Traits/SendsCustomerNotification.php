<?php

namespace App\Traits;

use App\Jobs\NotifInvJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Trait SendsCustomerNotification
 *
 * Modul pengiriman notifikasi pelanggan yang dapat dipakai di controller manapun.
 * Routing otomatis berdasarkan nilai customer->notification:
 *
 *   0 = None     → tidak dikirim
 *   1 = WhatsApp → kirim WA via gateway
 *   2 = Email    → kirim email
 *   3 = Mobile App (FCM) → kirim push notification Android
 *
 * Digunakan oleh: SuminvoiceController (dan controller lain yang perlu notif massal).
 */
trait SendsCustomerNotification
{
    /**
     * Dispatch notifikasi ke satu customer dengan delay queue.
     *
     * @param  \App\Customer  $customer         Model customer (harus punya: phone, name, customer_id, notification, email, fcm_token)
     * @param  string         $encryptedurl     URL terenkripsi halaman invoice/tagihan
     * @param  Carbon         $start            Base waktu start (Carbon — dimutasi setiap panggilan agar delay kumulatif)
     * @param  int            $index            Index loop (untuk pola delay)
     * @param  int            $longPauseEvery   Long pause setiap N pesan
     * @return void
     */
    public function dispatchCustomerNotif(
        $customer,
        string $encryptedurl,
        Carbon $start,
        int $index,
        int $longPauseEvery
    ): void {
        // Skip jika pelanggan tidak mau menerima notifikasi
        if ((int) $customer->notification === 0) {
            return;
        }

        $delay     = $this->messageDelay($index, $longPauseEvery);
        $notifType = $this->notifTypeLabel($customer->notification);

        // Gunakan nama queue = domain tenant agar setiap tenant hanya melihat job miliknya
        $tenantQueue = app('tenant')['domain'] ?? 'default';

        NotifInvJob::dispatch(
            $customer->phone,
            $customer->name,
            $customer->customer_id,
            $encryptedurl
        )->onQueue($tenantQueue)->delay($start->addSeconds($delay));

        Log::channel('notif')->info(
            "Queued [{$notifType}] CID {$customer->customer_id} | {$customer->name} | delay +{$delay}s"
        );
    }

    /**
     * Hitung delay (detik) untuk sebuah pesan berdasarkan index-nya.
     * Parameter min/max/long-pause dibaca dari ENV tenant (dapat di-set per tenant
     * via Admin → Tenant ENV: NOTIF_DELAY_MIN, NOTIF_DELAY_MAX,
     * NOTIF_LONG_PAUSE_EVERY, NOTIF_LONG_PAUSE_EXTRA).
     *
     * @param  int  $index          Index pesan saat ini (mulai dari 1)
     * @param  int  $longPauseEvery Long pause setiap N pesan
     * @return int  Detik delay
     */
    public function messageDelay(int $index, int $longPauseEvery = 20): int
    {
        // Batas delay dibaca dari tenant ENV agar bisa dikonfigurasi per-tenant
        $delayMin  = max(10,              (int) tenant_config('NOTIF_DELAY_MIN',        180));
        $delayMax  = max($delayMin + 10,  (int) tenant_config('NOTIF_DELAY_MAX',        360));
        $longExtra = max(60,              (int) tenant_config('NOTIF_LONG_PAUSE_EXTRA', 600));

        // 1. Base delay acak dalam range konfigurasi
        $base = rand($delayMin, $delayMax);

        // 2. Micro-variasi agar pola tidak terdeteksi bot
        $variance = rand(0, 40);
        $base += (rand(0, 1) ? $variance : -$variance);

        // 3. Perlambatan di setiap kelipatan 10
        if ($index % 10 === 0) {
            $base += rand(20, 60);
        }

        // 4. Long pause setiap longPauseEvery pesan
        if ($index > 0 && $index % $longPauseEvery === 0) {
            $base += rand((int) ($longExtra * 0.5), $longExtra * 2);
        }

        // 5. Minimum safety = 80% dari delayMin
        return max((int) ($delayMin * 0.8), $base);
    }

    /**
     * Kembalikan label tipe notifikasi untuk logging.
     */
    private function notifTypeLabel(int $type): string
    {
        return match ($type) {
            1 => 'WA',
            2 => 'Email',
            3 => 'FCM',
            default => "Unknown({$type})",
        };
    }
}
