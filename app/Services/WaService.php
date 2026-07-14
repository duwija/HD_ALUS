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
 *   titiwa   → Titiwa / WA Hub API               → config: wa_titiwa_host, wa_titiwa_api_key, wa_titiwa_template_1..4
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
    public static function sendReminder(
        string $phone,
        string $name,
        string $cid,
        string $encryptedurl,
        string $templateKey = 'WA_TAMPLATE_ID_4',
        array $context = []
    ): string
    {
        $provider = static::resolveReminderProvider($templateKey);

        Log::channel('notif')->info("[WA:{$provider}] Kirim reminder CID {$cid} | {$name} → {$phone}");

        try {
            return match ($provider) {
                'qontak'  => static::sendViaQontak($phone, $name, $cid, $encryptedurl, $templateKey, $context),
                'titiwa'  => static::sendViaTitiwaReminder($phone, $name, $cid, $encryptedurl, $templateKey, $context),
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
        $provider = static::getConfiguredProvider();

        try {
            return match ($provider) {
                // Titiwa mendukung template message, tidak cocok untuk plain text bebas.
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

    /**
     * Kirim konfirmasi pembayaran.
     * Prioritas otomatis ke Qontak jika WA_TAMPLATE_ID_3 tersedia.
     */
    public static function sendPaymentConfirmation(
        string $phone,
        string $name,
        string $invoiceNo,
        string $cid,
        $amount,
        string $openUrl,
        string $source = ''
    ): string
    {
        $provider = static::resolveReminderProvider('WA_TAMPLATE_ID_3');

        try {
            return match ($provider) {
                'qontak'  => static::sendViaQontakPaymentConfirmation($phone, $name, $invoiceNo, $cid, (string) $amount, $openUrl),
                'titiwa'  => static::sendViaTitiwaPaymentConfirmation($phone, $name, $invoiceNo, $cid, (string) $amount, $openUrl),
                'fonnte'  => static::sendViaFonnte($phone, static::buildPaymentConfirmationMessage($name, $invoiceNo, $cid, (string) $amount, $openUrl, $source)),
                'wablas'  => static::sendViaWablas($phone, static::buildPaymentConfirmationMessage($name, $invoiceNo, $cid, (string) $amount, $openUrl, $source)),
                default   => static::sendViaGateway($phone, static::buildPaymentConfirmationMessage($name, $invoiceNo, $cid, (string) $amount, $openUrl, $source)),
            };
        } catch (\Throwable $e) {
            Log::channel('notif')->error("[WA:{$provider}] Payment confirmation error CID {$cid}: " . $e->getMessage());
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Cek apakah konfigurasi Qontak (ACCESS_TOKEN + WA_CHANNEL_INTEGRATION_ID + template) lengkap.
     * Dipakai caller untuk memilih route Qontak vs WA gateway tanpa harus duplikasi logic.
     */
    public static function hasQontakConfig(string $templateKey = 'WA_TAMPLATE_ID_3'): bool
    {
        return static::getQontakToken() !== ''
            && static::getQontakChannelId() !== ''
            && static::getQontakApiUrl() !== ''
            && static::getQontakTemplateId($templateKey) !== '';
    }

    /**
     * Kirim konfirmasi pembayaran dengan fallback ke pesan gateway custom.
     * - Jika Qontak config lengkap → kirim via template Qontak.
     * - Jika tidak → kirim $gatewayMessage apa adanya via WA gateway.
     *
     * Return value mirip WaGatewayHelper::wa_payment: array ['status'=>..,'message'=>..] untuk gateway,
     * atau string status untuk Qontak (dibungkus ke array agar konsisten).
     *
     * @return array{status:string,message:string}
     */
    public static function sendPaymentConfirmationOrGateway(
        string $phone,
        string $name,
        string $invoiceNo,
        string $cid,
        $amount,
        string $openUrl,
        string $gatewayMessage,
        string $templateKey = 'WA_TAMPLATE_ID_3'
    ): array
    {
        if (static::hasQontakConfig($templateKey)) {
            try {
                $result = static::sendViaQontakPaymentConfirmation(
                    $phone,
                    $name,
                    $invoiceNo,
                    $cid,
                    (string) $amount,
                    $openUrl
                );

                $status = (is_string($result) && stripos($result, 'error') === false && $result !== '')
                    ? 'success'
                    : 'failed';

                return ['status' => $status, 'message' => is_string($result) ? $result : json_encode($result)];
            } catch (\Throwable $e) {
                Log::channel('notif')->error("[WA:qontak] Payment confirmation error CID {$cid}: " . $e->getMessage());
                // Fallback ke gateway jika Qontak error
            }
        }

        if (static::hasTitiwaConfig($templateKey)) {
            try {
                $result = static::sendViaTitiwaPaymentConfirmation(
                    $phone,
                    $name,
                    $invoiceNo,
                    $cid,
                    (string) $amount,
                    $openUrl,
                    $templateKey
                );

                $status = (is_string($result) && stripos($result, 'error') === false && $result !== '')
                    ? 'success'
                    : 'failed';

                return ['status' => $status, 'message' => is_string($result) ? $result : json_encode($result)];
            } catch (\Throwable $e) {
                Log::channel('notif')->error("[WA:titiwa] Payment confirmation error CID {$cid}: " . $e->getMessage());
                // Fallback ke gateway jika Titiwa error
            }
        }

        $result = WaGatewayHelper::wa_payment($phone, $gatewayMessage);

        if (is_array($result)) {
            return [
                'status'  => $result['status']  ?? 'failed',
                'message' => $result['message'] ?? '',
            ];
        }

        return ['status' => 'failed', 'message' => (string) $result];
    }

    /**
     * Gunakan short URL hanya untuk provider WA gateway.
     */
    public static function maybeShortenUrlForGateway(string $url): string
    {
        $provider = strtolower((string) tenant_config('WA_PROVIDER', tenant_config('wa_provider', 'gateway')));

        if ($provider !== 'gateway') {
            return $url;
        }

        try {
            return \App\ShortUrl::shorten($url);
        } catch (\Throwable $e) {
            Log::channel('notif')->warning('[ShortUrl] fallback to original URL', [
                'provider' => $provider,
                'tenant' => tenant_config('domain_name', env('DOMAIN_NAME')),
                'original_url' => $url,
                'error' => $e->getMessage(),
            ]);

            return $url;
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
    protected static function sendViaQontak(
        string $phone,
        string $name,
        string $cid,
        string $encryptedurl,
        string $templateKey = 'WA_TAMPLATE_ID_4',
        array $context = []
    ): string
    {
        // Format nomor
        $hp = static::formatPhone($phone);

        $apiUrl     = static::getQontakApiUrl();
        $token      = static::getQontakToken();
        $channelId  = static::getQontakChannelId();
        $templateId = static::getQontakTemplateId($templateKey);

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
            'parameters'              => static::buildQontakReminderParameters(
                $templateKey,
                $name,
                $cid,
                $encryptedurl,
                $context
            ),
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ])->post($apiUrl, $payload);

        $body = $response->json() ?? [];
        Log::channel('notif')->info('[WA:qontak] Response: ' . json_encode($body));

        if (($body['status'] ?? '') === 'error') {
            $messages = $body['error']['messages'] ?? [];
            if (is_array($messages) && !empty($messages)) {
                return 'error: ' . implode('; ', $messages);
            }
            return 'error';
        }

        return $body['status'] ?? 'sent';
    }

    /**
     * Bentuk parameter Qontak berdasarkan template yang dipakai.
     */
    protected static function buildQontakReminderParameters(
        string $templateKey,
        string $name,
        string $cid,
        string $encryptedurl,
        array $context = []
    ): array
    {
        $buttonPath = ltrim($encryptedurl, '/');

        if ($templateKey === 'WA_TAMPLATE_ID_1') {
            $invoiceNo = (string) ($context['invoice_no'] ?? '-');
            $amountRaw = $context['amount'] ?? 0;
            $amount = 'Rp. ' . number_format((float) $amountRaw, 0, ',', '.');
            $billingMonth = (string) ($context['billing_month'] ?? '-');
            $dueDate = (string) ($context['due_date'] ?? '-');

            return [
                'body' => [
                    ['key' => '1', 'value' => 'name', 'value_text' => $name],
                    ['key' => '2', 'value' => 'customer_id', 'value_text' => $cid],
                    ['key' => '3', 'value' => 'invoice_no', 'value_text' => $invoiceNo],
                    ['key' => '4', 'value' => 'amount', 'value_text' => $amount],
                    ['key' => '5', 'value' => 'billing_month', 'value_text' => $billingMonth],
                    ['key' => '6', 'value' => 'due_date', 'value_text' => $dueDate],
                ],
                'buttons' => [
                    ['index' => '0', 'type' => 'url', 'value' => $buttonPath],
                ],
            ];
        }

        return [
            'body' => [
                ['key' => '1', 'value' => 'name', 'value_text' => $name],
                ['key' => '2', 'value' => 'customer_id', 'value_text' => $cid],
            ],
            'buttons' => [
                ['index' => '0', 'type' => 'url', 'value' => $buttonPath],
            ],
        ];
    }

    /**
     * Qontak template konfirmasi pembayaran (WA_TAMPLATE_ID_3).
     */
    protected static function sendViaQontakPaymentConfirmation(
        string $phone,
        string $name,
        string $invoiceNo,
        string $cid,
        string $amount,
        string $openUrl
    ): string
    {
        $hp         = static::formatPhone($phone);
        $apiUrl     = static::getQontakApiUrl();
        $token      = static::getQontakToken();
        $channelId  = static::getQontakChannelId();
        $templateId = static::getQontakTemplateId('WA_TAMPLATE_ID_3');

        if (empty($token) || empty($templateId) || empty($channelId)) {
            Log::channel('notif')->warning('[WA:qontak] Konfigurasi payment confirmation tidak lengkap (token/template/channel kosong).');
            return 'qontak: config incomplete';
        }

        $buttonPath = ltrim($openUrl, '/');

        $payload = [
            'to_number'              => $hp,
            'to_name'                => $name,
            'message_template_id'    => $templateId,
            'channel_integration_id' => $channelId,
            'language'               => ['code' => 'id'],
            'parameters'             => [
                'body' => [
                    ['key' => '1', 'value' => 'name', 'value_text' => $name],
                    ['key' => '2', 'value' => 'inv_no', 'value_text' => $invoiceNo],
                    ['key' => '3', 'value' => 'customer_id', 'value_text' => $cid],
                    ['key' => '4', 'value' => 'amount', 'value_text' => number_format((float) $amount, 0, ',', '.')],
                ],
                'buttons' => [
                    ['index' => '0', 'type' => 'url', 'value' => $buttonPath],
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ])->post($apiUrl, $payload);

        $body = $response->json() ?? [];
        Log::channel('notif')->info('[WA:qontak:payment_confirmation] Response: ' . json_encode($body));

        return $body['status'] ?? 'sent';
    }

    /**
     * Titiwa / WA Hub API (template based).
     * Endpoint: {host}/api/v1/messages/send
     * Auth: X-API-Key
     */
    protected static function sendViaTitiwaReminder(
        string $phone,
        string $name,
        string $cid,
        string $encryptedurl,
        string $templateKey = 'WA_TAMPLATE_ID_4',
        array $context = []
    ): string
    {
        $template = static::getTitiwaTemplateName($templateKey);
        if ($template === '') {
            Log::channel('notif')->warning("[WA:titiwa] Template kosong untuk {$templateKey}.");
            return 'titiwa: template not set';
        }

        $domain = tenant_config('domain_name', env('DOMAIN_NAME', ''));
        $fullUrl = (str_starts_with($encryptedurl, 'http://') || str_starts_with($encryptedurl, 'https://'))
            ? $encryptedurl
            : 'https://' . $domain . $encryptedurl;

        $variables = static::buildTitiwaReminderVariables($templateKey, $name, $cid, $fullUrl, $context);

        return static::sendViaTitiwaTemplate($phone, $template, $variables);
    }

    protected static function sendViaTitiwaPaymentConfirmation(
        string $phone,
        string $name,
        string $invoiceNo,
        string $cid,
        string $amount,
        string $openUrl,
        string $templateKey = 'WA_TAMPLATE_ID_3'
    ): string
    {
        $template = static::getTitiwaTemplateName($templateKey);
        if ($template === '') {
            Log::channel('notif')->warning("[WA:titiwa] Template payment kosong untuk {$templateKey}.");
            return 'titiwa: template not set';
        }

        $domain = tenant_config('domain_name', env('DOMAIN_NAME', ''));
        $fullUrl = (str_starts_with($openUrl, 'http://') || str_starts_with($openUrl, 'https://'))
            ? $openUrl
            : 'https://' . $domain . $openUrl;

        $variables = [
            $name,
            $invoiceNo,
            $cid,
            'Rp ' . number_format((float) $amount, 0, ',', '.'),
            $fullUrl,
        ];

        return static::sendViaTitiwaTemplate($phone, $template, $variables);
    }

    protected static function sendViaTitiwaTemplate(string $phone, string $template, array $variables): string
    {
        $host = static::getTitiwaHost();
        $apiKey = static::getTitiwaApiKey();

        if ($host === '' || $apiKey === '') {
            Log::channel('notif')->warning('[WA:titiwa] Konfigurasi tidak lengkap (host/api key kosong).');
            return 'titiwa: config incomplete';
        }

        $hp = static::formatPhone($phone);
        $endpoint = rtrim($host, '/') . '/api/v1/messages/send';

        $response = Http::withHeaders([
            'X-API-Key'   => $apiKey,
            'Accept'      => 'application/json',
            'Content-Type'=> 'application/json',
        ])->timeout(30)->post($endpoint, [
            'template'  => $template,
            'to'        => $hp,
            'variables' => array_values($variables),
        ]);

        $body = $response->json() ?? [];
        Log::channel('notif')->info('[WA:titiwa] Response: ' . json_encode($body));

        if (!$response->successful()) {
            $message = (string) ($body['message'] ?? 'error');
            return 'error: ' . $message;
        }

        if (($body['success'] ?? false) !== true) {
            return 'error: ' . (string) ($body['message'] ?? 'send failed');
        }

        return (string) (($body['data']['status'] ?? '') ?: 'sent');
    }

    /**
     * Pilih provider untuk reminder:
     * - Qontak diprioritaskan jika template + token + channel tersedia
     * - Titiwa diprioritaskan setelah Qontak jika host + api key + template tersedia
     * - Jika tidak, gunakan provider tenant (default gateway)
     */
    protected static function resolveReminderProvider(string $templateKey): string
    {
        $configuredProvider = static::getConfiguredProvider();

        if (static::hasQontakConfig($templateKey)) {
            return 'qontak';
        }

        if (static::hasTitiwaConfig($templateKey)) {
            return 'titiwa';
        }

        if ($configuredProvider === 'qontak') {
            Log::channel('notif')->warning("[WA:qontak] Konfigurasi tidak lengkap untuk {$templateKey}, fallback ke gateway.");
            return 'gateway';
        }

        if ($configuredProvider === 'titiwa') {
            Log::channel('notif')->warning("[WA:titiwa] Konfigurasi tidak lengkap untuk {$templateKey}, fallback ke gateway.");
            return 'gateway';
        }

        return $configuredProvider;
    }

    protected static function hasTitiwaConfig(string $templateKey = 'WA_TAMPLATE_ID_4'): bool
    {
        return static::getTitiwaHost() !== ''
            && static::getTitiwaApiKey() !== ''
            && static::getTitiwaTemplateName($templateKey) !== '';
    }

    protected static function getTitiwaHost(): string
    {
        $host = static::tenantScopedConfig('WA_TITIWA_HOST');
        if ($host !== '') {
            return rtrim($host, '/');
        }

        return rtrim(static::tenantScopedConfig('WAHUB_HOST'), '/');
    }

    protected static function getTitiwaApiKey(): string
    {
        $apiKey = static::tenantScopedConfig('WA_TITIWA_API_KEY');
        if ($apiKey !== '') {
            return $apiKey;
        }

        return static::tenantScopedConfig('WAHUB_API_KEY');
    }

    protected static function getTitiwaTemplateName(string $templateKey): string
    {
        if (preg_match('/(\d+)$/', $templateKey, $match)) {
            $candidate = static::tenantScopedConfig('WA_TITIWA_TEMPLATE_' . $match[1]);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $candidate = static::tenantScopedConfig('WA_TITIWA_TEMPLATE_2');
        if ($candidate !== '') {
            return $candidate;
        }

        // Fallback ke key template existing jika mapping khusus Titiwa belum diisi.
        return static::tenantScopedConfig($templateKey);
    }

    protected static function buildTitiwaReminderVariables(
        string $templateKey,
        string $name,
        string $cid,
        string $url,
        array $context = []
    ): array
    {
        if ($templateKey === 'WA_TAMPLATE_ID_1') {
            return [
                $name,
                $context['invoice_no'] ?? '-',
                'Rp ' . number_format((float) ($context['amount'] ?? 0), 0, ',', '.'),
                $context['due_date'] ?? '-',
                $url,
            ];
        }

        return [$name, $cid, $url];
    }

    protected static function getQontakApiUrl(): string
    {
        $apiUrl = static::tenantScopedConfig('WA_QONTAK_API_URL');
        if ($apiUrl !== '') {
            return $apiUrl;
        }

        $apiUrl = static::tenantScopedConfig('WHATSAPP_API_URL');
        if ($apiUrl !== '') {
            return $apiUrl;
        }

        return 'https://service-chat.qontak.com/api/open/v1/broadcasts/whatsapp/direct';
    }

    protected static function getQontakToken(): string
    {
        $token = static::tenantScopedConfig('WA_QONTAK_TOKEN');
        if ($token !== '') {
            return $token;
        }

        return static::tenantScopedConfig('ACCESS_TOKEN');
    }

    protected static function getQontakChannelId(): string
    {
        $channelId = static::tenantScopedConfig('WA_QONTAK_CHANNEL_ID');
        if ($channelId !== '') {
            return $channelId;
        }

        return static::tenantScopedConfig('WA_CHANNEL_INTEGRATION_ID');
    }

    protected static function getQontakTemplateId(string $templateKey): string
    {
        $templateId = static::tenantScopedConfig($templateKey);
        if ($templateId !== '') {
            return $templateId;
        }

        $templateId = static::tenantScopedConfig('WA_QONTAK_TEMPLATE_ID');
        if ($templateId !== '') {
            return $templateId;
        }

        return static::tenantScopedConfig('WA_TAMPLATE_ID_4');
    }

    protected static function getConfiguredProvider(): string
    {
        $provider = strtolower(static::tenantScopedConfig('WA_PROVIDER'));
        if ($provider === '') {
            $provider = strtolower(static::tenantScopedConfig('wa_provider'));
        }

        return $provider !== '' ? $provider : 'gateway';
    }

    protected static function tenantScopedConfig(string $key): string
    {
        $tenant = app()->bound('tenant') ? app('tenant') : [];
        if (is_array($tenant)) {
            foreach ([$key, strtolower($key), strtoupper($key)] as $k) {
                if (array_key_exists($k, $tenant) && $tenant[$k] !== null) {
                    return trim((string) $tenant[$k]);
                }
            }
        }

        $value = config('tenant.' . strtolower($key));
        if ($value !== null) {
            return trim((string) $value);
        }

        return '';
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

        $originalUrl = "https://{$domain}{$encryptedurl}";
        $link = static::maybeShortenUrlForGateway($originalUrl);

        // Template rotation ringan (3 varian) agar pesan tidak identik di setiap blast.
        try {
            $variant = random_int(1, 3);
        } catch (\Throwable $e) {
            $variant = mt_rand(1, 3);
        }

        if ($variant === 1) {
            $msg  = "*[Pengingat Pembayaran Internet]*\n\n";
            $msg .= "Pelanggan Yth.\n\n";
            $msg .= "Nama : {$name}\n";
            $msg .= "CID  : {$cid}\n";
            $msg .= "Kami ingin mengingatkan bahwa tagihan Anda sudah tersedia.\n";
            $msg .= "Agar tetap bisa menikmati layanan kami, mohon selesaikan pembayaran tepat waktu.\n\n";
            $msg .= "Informasi lebih lanjut, klik link berikut:\n";
            $msg .= "{$link}\n\n";
            $msg .= "Jika sudah melakukan pembayaran, abaikan pesan ini.\n";
            $msg .= "Jika ada pertanyaan, hubungi CS kami di {$paymentWa}\n\n";
            $msg .= "{$signature}";
        } elseif ($variant === 2) {
            $msg  = "*[Info Tagihan Internet]*\n\n";
            $msg .= "Halo {$name},\n";
            $msg .= "Tagihan untuk CID *{$cid}* sudah tersedia.\n";
            $msg .= "Mohon lakukan pembayaran sebelum jatuh tempo agar layanan tetap aktif.\n\n";
            $msg .= "Cek detail tagihan di sini:\n";
            $msg .= "{$link}\n\n";
            $msg .= "Jika sudah membayar, pesan ini bisa diabaikan.\n";
            $msg .= "Bantuan CS: {$paymentWa}\n\n";
            $msg .= "{$signature}";
        } else {
            $msg  = "*[Reminder Pembayaran]*\n\n";
            $msg .= "Yth. {$name} (CID: {$cid}),\n";
            $msg .= "Ini pengingat bahwa masih ada tagihan internet yang perlu dibayarkan.\n";
            $msg .= "Silakan selesaikan pembayaran agar layanan tetap berjalan lancar.\n\n";
            $msg .= "Link tagihan:\n";
            $msg .= "{$link}\n\n";
            $msg .= "Jika pembayaran sudah dilakukan, mohon abaikan pesan ini.\n";
            $msg .= "Hubungi CS {$paymentWa} untuk pertanyaan lebih lanjut.\n\n";
            $msg .= "{$signature}";
        }

        return $msg;
    }

    /**
     * Pesan fallback konfirmasi pembayaran untuk non-qontak.
     */
    public static function buildPaymentConfirmationMessage(
        string $name,
        string $invoiceNo,
        string $cid,
        string $amount,
        string $openUrl,
        string $source = ''
    ): string
    {
        $domain    = tenant_config('domain_name', env('DOMAIN_NAME', ''));
        $signature = tenant_config('signature', env('SIGNATURE', config('app.signature', '')));
        $amountRp  = number_format((float) $amount, 0, ',', '.');

        $fullUrl = (str_starts_with($openUrl, 'http://') || str_starts_with($openUrl, 'https://'))
            ? $openUrl
            : 'https://' . $domain . $openUrl;

        $fullUrl = static::maybeShortenUrlForGateway($fullUrl);

        $msg  = "Yth. {$name}\n";
        $msg .= "\nTerimakasih, pembayaran tagihan Customer dengan CID *{$cid}* sudah kami *TERIMA*";
        $msg .= "\nTagihan  : *#{$invoiceNo}*";
        $msg .= "\nJumlah   : *Rp.{$amountRp}*";
        if ($source !== '') {
            $msg .= "\nVia      : {$source}";
        }
        $msg .= "\n\nUntuk info lebih lengkap silahkan klik link:";
        $msg .= "\n{$fullUrl}";
        $msg .= "\n\n{$signature}";

        return $msg;
    }
}
