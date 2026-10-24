<?php

namespace App\Services;

use App\Suminvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Menghapus VA/checkout Winpay yang masih aktif setelah invoice selesai lewat jalur lain
 * (dibayar di kasir / gateway lain, dibatalkan) atau saat bundle dibatalkan — supaya
 * pelanggan tidak bisa membayar VA lama dan tertagih dua kali.
 *
 * Dipicu dari event Suminvoice (payment_status 0 → 1/2) dan dari cancelBundle.
 * Pembersihan dijalankan setelah respons terkirim (kasir & callback gateway tidak menunggu),
 * dan membaca ulang database saat itu: bundle yang sudah lunas (mis. bundle yang justru
 * sedang dibayar) tidak disentuh.
 */
class WinpayVaCleaner
{
    /** Kode respons Winpay untuk hapus berhasil. */
    private const DELETED = '2000300';

    /**
     * Jadwalkan pembersihan untuk satu invoice yang baru lunas/batal.
     *
     * @param  string|null  $singleProvider  'winpay'/'winpay2' kalau invoice ini pernah dibuatkan
     *                                        checkout Winpay satuan (ref = nomor invoice), selain itu null
     */
    public static function scheduleForInvoice(int $suminvoiceId, ?string $singleProvider): void
    {
        static::defer(fn () => (new static)->cleanupInvoice($suminvoiceId, $singleProvider));
    }

    /** Jadwalkan penghapusan checkout bundle di Winpay (bundle dibatalkan). */
    public static function scheduleForBundle(string $bundleRef, string $provider): void
    {
        static::defer(fn () => (new static)->deleteByRef($bundleRef, $provider, 'bundle dibatalkan'));
    }

    public function cleanupInvoice(int $suminvoiceId, ?string $singleProvider): void
    {
        $invoice = Suminvoice::find($suminvoiceId);
        if (!$invoice || (int) $invoice->payment_status === 0) {
            return; // tidak ditemukan / dibuka lagi → VA masih boleh dipakai
        }

        $reason = (int) $invoice->payment_status === 2 ? 'invoice dibatalkan' : 'invoice sudah lunas';

        // 1) Checkout Winpay satuan (ref = nomor invoice). Kalau invoice dilunasi lewat checkout itu sendiri,
        //    Winpay menolak hapus — tidak masalah, cukup tercatat di log.
        if ($singleProvider) {
            $this->deleteByRef((string) $invoice->number, $singleProvider, $reason);
        }

        // 2) Bundle yang masih pending dan berisi invoice ini: batalkan lokal (seperti cancelBundle)
        //    dan hapus checkout-nya di Winpay, supaya total bundle tidak bisa dibayar lagi.
        $bundles = DB::table('payment_bundles AS b')
            ->join('payment_bundle_items AS i', 'i.bundle_ref', '=', 'b.bundle_ref')
            ->where('i.suminvoice_id', $suminvoiceId)
            ->where('b.status', 0)
            ->whereIn('b.gateway', ['winpay', 'winpay2'])
            ->select('b.bundle_ref', 'b.gateway')
            ->distinct()
            ->get();

        foreach ($bundles as $bundle) {
            DB::transaction(function () use ($bundle) {
                $updated = DB::table('payment_bundles')
                    ->where('bundle_ref', $bundle->bundle_ref)
                    ->where('status', 0) // jangan sentuh kalau keburu lunas
                    ->update(['status' => 2, 'payment_url' => null, 'updated_at' => now()]);

                if ($updated) {
                    $ids = DB::table('payment_bundle_items')->where('bundle_ref', $bundle->bundle_ref)->pluck('suminvoice_id');
                    Suminvoice::whereIn('id', $ids)->where('payment_status', 0)
                        ->update(['payment_id' => null, 'payment_gateway' => null]);
                }
            });

            Log::channel('payment')->info("[WinpayVaCleaner] Bundle {$bundle->bundle_ref} dibatalkan karena invoice #{$invoice->number} {$reason}");
            $this->deleteByRef($bundle->bundle_ref, $bundle->gateway, "invoice #{$invoice->number} {$reason}");
        }
    }

    /**
     * DELETE {endpoint}/api/deleteByRef/{ref}. Tidak pernah melempar exception — kegagalan dicatat di log.
     */
    public function deleteByRef(string $ref, string $provider = 'winpay', string $reason = ''): bool
    {
        try {
            $config = $this->config($provider);
            if (!$config) {
                Log::channel('payment')->warning("[WinpayVaCleaner] Konfigurasi {$provider} tidak lengkap, VA {$ref} tidak dihapus");
                return false;
            }

            $timestamp = (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d\TH:i:sP');

            $response = Http::withHeaders([
                'Content-Type'        => 'application/json',
                'X-Winpay-Key'        => $config['api_key'],
                'X-Winpay-Signature'  => hash_hmac('sha256', $timestamp, $config['secret_key']),
                'X-Winpay-Timestamp'  => $timestamp,
            ])->timeout(15)->delete(rtrim($config['endpoint'], '/') . '/api/deleteByRef/' . rawurlencode($ref));

            $body = $response->json() ?? [];
            $ok = ($body['responseCode'] ?? null) === self::DELETED;

            Log::channel('payment')->info(sprintf(
                '[WinpayVaCleaner] %s ref=%s (%s)%s → HTTP %d %s %s',
                $ok ? 'Dihapus' : 'Tidak dihapus',
                $ref,
                $provider,
                $reason !== '' ? " karena {$reason}" : '',
                $response->status(),
                $body['responseCode'] ?? '-',
                $body['responseMessage'] ?? substr($response->body(), 0, 150)
            ));

            return $ok;
        } catch (\Throwable $e) {
            Log::channel('payment')->warning("[WinpayVaCleaner] Gagal hapus ref={$ref} ({$provider}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Endpoint & kunci Winpay — urutan fallback sama dengan SuminvoiceController::getWinpayConfig().
     * Dipakai juga oleh WinpayCallbackVerifier.
     */
    public function config(string $provider): ?array
    {
        $provider = $provider === 'winpay2' ? 'winpay2' : 'winpay';
        $gw = \App\PaymentGateway::findForCurrentTenant($provider);
        $settings = $gw ? (array) ($gw->settings ?? []) : [];

        if ($provider === 'winpay') {
            $settings['endpoint']   = ($settings['endpoint'] ?? '')   ?: tenant_config('WINPAY_ENDPOINT', env('WINPAY_ENDPOINT'));
            $settings['api_key']    = ($settings['api_key'] ?? '')    ?: tenant_config('WINPAY_KEY', env('WINPAY_KEY'));
            $settings['secret_key'] = ($settings['secret_key'] ?? '') ?: tenant_config('WINPAY_SECRET', env('WINPAY_SECRET'));
        } elseif (empty($settings['endpoint'])) {
            $main = \App\PaymentGateway::findForCurrentTenant('winpay');
            $settings['endpoint'] = $main ? (($main->settings['endpoint'] ?? '') ?: null) : null;
        }

        $config = [
            'endpoint'   => trim((string) ($settings['endpoint'] ?? '')),
            'api_key'    => trim((string) ($settings['api_key'] ?? '')),
            'secret_key' => trim((string) ($settings['secret_key'] ?? '')),
        ];

        return in_array('', $config, true) ? null : $config;
    }

    /**
     * Web: jalankan setelah respons terkirim. CLI/queue worker: jalankan langsung
     * (callback "terminating" baru berjalan saat proses worker berhenti).
     */
    private static function defer(\Closure $task): void
    {
        $safe = function () use ($task) {
            try {
                $task();
            } catch (\Throwable $e) {
                Log::channel('payment')->warning('[WinpayVaCleaner] Pembersihan gagal: ' . $e->getMessage());
            }
        };

        if (app()->runningInConsole()) {
            $safe();
            return;
        }

        app()->terminating($safe);
    }
}
