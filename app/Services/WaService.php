<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\WaGatewayHelper;

/**
 * WaService — Abstraksi multi-provider WhatsApp.
 *
 * Pilih provider via tenant ENV: WA_PROVIDER
 *
 *   gateway  → WA gateway self-hosted (default)  → config: wa_gateway_url
 *   qontak   → Qontak Official WA Business API   → config: wa_qontak_token, wa_qontak_template_id, wa_qontak_channel_id, wa_qontak_api_url
 *   fonnte   → Fonnte (https://fonnte.com)        → config: wa_fonnte_token
 *   wablas   → Wablas (https://wablas.com)        → config: wa_wablas_token, wa_wablas_url
 *
 * Usage:
 *   WaService::sendReminder($phone, $name, $cid, $encryptedurl);
 *   WaService::sendText($phone, $message);   // kirim teks bebas ke provider aktif
 */
class WaService
{
    // ──────────────────────────────────────────────────────────────
    //  Public interface
    // ──────────────────────────────────────────────────────────────

    /**
     * Kirim notifikasi pengingat tagihan ke satu nomor.
     * Routing otomatis ke provider sesuai WA_PROVIDER tenant config.
     *
     * @param  string  $phone        Nomor HP pelanggan
     * @param  string  $name         Nama pelanggan
     * @param  string  $cid          Customer ID
     * @param  string  $encryptedurl URL terenkripsi link tagihan
     * @return string  Hasil dari API provider
     */
    public static function sendReminder(string $phone, string $name, string $cid, string $encryptedurl): string
    {
        $provider = strtolower((string) tenant_config('WA_PROVIDER', tenant_config('wa_provider', 'gateway')));

        Log::channel('notif')->info("[WA:{$provider}] Kirim reminder CID {$cid} | {$name} → {$phone}");

        try {
            return match ($provider) {
                'qontak'  => static::sendViaQontak($phone, $name, $cid, $encryptedurl),
                'fonnte'  => static::sendViaFonnte($phone, static::buildReminderMessage($name, $cid, $encryptedurl)),
                'wablas'  => static::sendViaWablas($phone, static::buildReminderMessage($name, $cid, $encryptedurl)),
                default   => static::sendViaGateway($phone, static::buildReminderMessage($name, $cid, $encryptedurl)),
            };
        } catch (\Throwable $e) {
            Log::channel('notif')->error("[WA:{$provider}] Error CID {$cid}: " . $e->getMessage());
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Kirim pesan teks bebas ke nomor tertentu menggunakan provider aktif.
     * Cocok untuk notifikasi custom (ticket, promo, dll).
     *
     * @param  string  $phone    Nomor HP tujuan
     * @param  string  $message  Teks pesan
     * @return string
     */
    public static function sendText(string $phone, string $message): string
    {
        $provider = strtolower((string) tenant_config('WA_PROVIDER', tenant_config('wa_provider', 'gateway')));

        try {
            return match ($provider) {
                'fonnte'  => static::sendViaFonnte($phone, $message),
                'wablas'  => static::sendViaWablas($phone, $message),
                // qontak tidak mendukung plain text (hanya template), fallback ke gateway
                default   => static::sendViaGateway($phone, $message),
            };
        } catch (\Throwable $e) {
            Log::channel('notif')->error("[WA:{$provider}] sendText Error: " . $e->getMessage());
            return 'Error: ' . $e->getMessage();
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  Provider implementations
    // ──────────────────────────────────────────────────────────────

    /**
     * Gateway WA self-hosted (existing WaGatewayHelper).
     * Config: wa_gateway_url
     */
    protected static function sendViaGateway(string $phone, string $message): string
    {
        $result = WaGatewayHelper::wa_payment($phone, $message);
        return is_array($result) ? ($result['message'] ?? json_encode($result)) : (string) $result;
    }

    /**
     * Qontak — Official WhatsApp Business API.
     * Config tenant ENV:
     *   WA_QONTAK_API_URL        (default: https://service-chat.qontak.com/api/open/v1/broadcasts/whatsapp/direct)
     *   WA_QONTAK_TOKEN          (Bearer access token)
     *   WA_QONTAK_TEMPLATE_ID    (message template ID)
     *   WA_QONTAK_CHANNEL_ID     (channel integration ID)
     */
    protected static function sendViaQontak(string $phone, string $name, string $cid, string $encryptedurl): string
    {
        // Format nomor
        $hp = static::formatPhone($phone);

        $apiUrl    = tenant_config('WA_QONTAK_API_URL',     env('WHATSAPP_API_URL',         'https://service-chat.qontak.com/api/open/v1/broadcasts/whatsapp/direct'));
        $token     = tenant_config('WA_QONTAK_TOKEN',       env('ACCESS_TOKEN',              ''));
        $templateId = tenant_config('WA_QONTAK_TEMPLATE_ID', env('WA_TAMPLATE_ID_4',         ''));
        $channelId  = tenant_config('WA_QONTAK_CHANNEL_ID',  env('WA_CHANNEL_INTEGRATION_ID', ''));

        if (empty($token) || empty($templateId) || empty($channelId)) {
            Log::channel('notif')->warning('[WA:qontak] Konfigurasi tidak lengkap (token/template/channel kosong).');
            return 'qontak: config incomplete';
        }

        $payload = [
            'to_number'               => $hp,
            'to_name'                 => $name,
            'message_template_id'     => $templateId,
            'channel_integration_id'  => $channelId,
            'language'                => ['code' => 'id'],
            'parameters'              => [
                'body' => [
                    ['key' => '1', 'value' => 'name',        'value_text' => $name],
                    ['key' => '2', 'value' => 'customer_id', 'value_text' => $cid],
                ],
                'buttons' => [
                    ['index' => '0', 'type' => 'url', 'value' => $encryptedurl],
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ])->post($apiUrl, $payload);

        $body = $response->json() ?? [];
        Log::channel('notif')->info('[WA:qontak] Response: ' . json_encode($body));

        return $body['status'] ?? 'sent';
    }

    /**
     * Fonnte — https://fonnte.com
     * Config tenant ENV:
     *   WA_FONNTE_TOKEN   (API token dari dashboard Fonnte)
     */
    protected static function sendViaFonnte(string $phone, string $message): string
    {
        $token = tenant_config('WA_FONNTE_TOKEN', env('WA_FONNTE_TOKEN', ''));

        if (empty($token)) {
            Log::channel('notif')->warning('[WA:fonnte] WA_FONNTE_TOKEN belum dikonfigurasi.');
            return 'fonnte: token not set';
        }

        $hp = static::formatPhone($phone);

        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->post('https://api.fonnte.com/send', [
            'target'  => $hp,
            'message' => $message,
            'delay'   => '2',
        ]);

        $body = $response->json() ?? [];
        Log::channel('notif')->info('[WA:fonnte] Response: ' . json_encode($body));

        return isset($body['status']) && $body['status'] ? 'sent' : ($body['reason'] ?? 'error');
    }

    /**
     * Wablas — https://wablas.com
     * Config tenant ENV:
     *   WA_WABLAS_TOKEN   (API token dari dashboard Wablas)
     *   WA_WABLAS_URL     (server URL, default: https://my.wablas.com)
     */
    protected static function sendViaWablas(string $phone, string $message): string
    {
        $token     = tenant_config('WA_WABLAS_TOKEN', env('WA_WABLAS_TOKEN', ''));
        $serverUrl = rtrim((string) tenant_config('WA_WABLAS_URL', env('WA_WABLAS_URL', 'https://my.wablas.com')), '/');

        if (empty($token)) {
            Log::channel('notif')->warning('[WA:wablas] WA_WABLAS_TOKEN belum dikonfigurasi.');
            return 'wablas: token not set';
        }

        $hp = static::formatPhone($phone);

        $response = Http::withHeaders([
            'Authorization' => $token,
            'Content-Type'  => 'application/json',
        ])->post($serverUrl . '/api/send-message', [
            'phone'   => $hp,
            'message' => $message,
        ]);

        $body = $response->json() ?? [];
        Log::channel('notif')->info('[WA:wablas] Response: ' . json_encode($body));

        return $body['status'] ?? 'sent';
    }

    // ──────────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Format nomor HP ke format internasional tanpa +.
     */
    public static function formatPhone(string $phone): string
    {
        $hp = preg_replace('/[\s\-\(\)]/', '', trim($phone));
        $hp = ltrim($hp, '+');
        $hp = preg_replace('/^0/', '62', $hp);
        return $hp;
    }

    /**
     * Buat teks pesan pengingat tagihan standar (untuk gateway/fonnte/wablas).
     */
    public static function buildReminderMessage(string $name, string $cid, string $encryptedurl): string
    {
        $domain    = tenant_config('domain_name',  env('DOMAIN_NAME',  ''));
        $paymentWa = tenant_config('payment_wa',   env('PAYMENT_WA',   ''));
        $signature = tenant_config('signature',    env('SIGNATURE',    ''));

        $msg  = "*[Pengingat Pembayaran Internet]*\n\n";
        $msg .= "Pelanggan Yth.\n\n";
        $msg .= "Nama : {$name}\n";
        $msg .= "CID  : {$cid}\n";
        $msg .= "Kami ingin mengingatkan bahwa tagihan Anda sudah tersedia.\n";
        $msg .= "Agar tetap bisa menikmati layanan kami, mohon selesaikan pembayaran tepat waktu.\n\n";
        $msg .= "Informasi lebih lanjut, klik link berikut:\n";
        $msg .= "http://{$domain}{$encryptedurl}\n\n";
        $msg .= "Jika sudah melakukan pembayaran, abaikan pesan ini.\n";
        $msg .= "Jika ada pertanyaan, hubungi CS kami di {$paymentWa}\n\n";
        $msg .= "{$signature}";

        return $msg;
    }
}
