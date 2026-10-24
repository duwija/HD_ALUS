<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Verifikasi callback Winpay Checkout Page.
 *
 * Callback Checkout Page tidak membawa signature (lihat docs.winpay.id, Checkout Page > Callback),
 * jadi isi callback tidak bisa dipercaya begitu saja: siapa pun bisa POST ke /winpay/callback.
 * Setiap callback dikonfirmasi ulang ke API Winpay (GET /api/findByRef/{ref}) dengan kredensial
 * tenant sendiri. Callback hanya diproses bila Winpay menyatakan invoice dengan ref itu PAID dan
 * nominal tagihannya sama dengan `amount` di callback.
 *
 * Catatan nominal: `amount` di callback = total tagihan yang kita buat (jumlah products),
 * bukan payment.paid_amount (yang sudah termasuk biaya bank).
 */
class WinpayCallbackVerifier
{
    const FOUND = '2000300';

    /**
     * @param string        $ref       merchant ref dari callback (invoice.ref)
     * @param string[]      $providers akun yang dicoba berurutan ('winpay' / 'winpay2')
     * @param mixed         $amount    `amount` dari callback
     * @return array{ok: bool, retry: bool, reason: string, provider: ?string, invoice: ?array}
     *         retry = true bila gagal karena Winpay tidak bisa dihubungi (minta Winpay kirim ulang).
     */
    public function verify(string $ref, array $providers, $amount): array
    {
        $lastReason = 'invoice tidak ditemukan di Winpay';
        $unreachable = false;

        foreach (array_unique($providers) as $provider) {
            $config = (new WinpayVaCleaner)->config($provider);
            if (!$config) {
                continue;
            }

            try {
                $timestamp = (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d\TH:i:sP');
                $response = Http::withHeaders([
                    'X-Winpay-Key'       => $config['api_key'],
                    'X-Winpay-Signature' => hash_hmac('sha256', $timestamp, $config['secret_key']),
                    'X-Winpay-Timestamp' => $timestamp,
                ])->timeout(15)->get(rtrim($config['endpoint'], '/') . '/api/findByRef/' . rawurlencode($ref));
            } catch (\Throwable $e) {
                $unreachable = true;
                $lastReason = "Winpay ({$provider}) tidak bisa dihubungi: " . $e->getMessage();
                continue;
            }

            if ($response->serverError()) {
                $unreachable = true;
                $lastReason = "Winpay ({$provider}) HTTP " . $response->status();
                continue;
            }

            $body = $response->json() ?? [];
            if (($body['responseCode'] ?? null) !== self::FOUND) {
                continue; // tidak ada di akun ini, coba akun berikutnya
            }

            $inv = $body['responseData'] ?? [];
            if (($inv['ref'] ?? null) !== $ref) {
                return $this->fail("ref dari Winpay ({$provider}) tidak cocok: " . ($inv['ref'] ?? '-'), $provider, $inv);
            }
            if (strtoupper((string) ($inv['status'] ?? '')) !== 'PAID') {
                return $this->fail("status di Winpay ({$provider}) = " . ($inv['status'] ?? '-') . ', bukan PAID', $provider, $inv);
            }

            $billed = 0;
            foreach ((array) ($inv['products'] ?? []) as $p) {
                $billed += (float) ($p['price'] ?? 0) * (float) ($p['qty'] ?? 1);
            }
            if ((int) round($billed) !== (int) round((float) $amount)) {
                return $this->fail("nominal callback {$amount} ≠ tagihan di Winpay {$billed}", $provider, $inv);
            }

            return ['ok' => true, 'retry' => false, 'reason' => 'PAID terkonfirmasi', 'provider' => $provider, 'invoice' => $inv];
        }

        return ['ok' => false, 'retry' => $unreachable, 'reason' => $lastReason, 'provider' => null, 'invoice' => null];
    }

    private function fail(string $reason, string $provider, array $inv): array
    {
        return ['ok' => false, 'retry' => false, 'reason' => $reason, 'provider' => $provider, 'invoice' => $inv];
    }
}
