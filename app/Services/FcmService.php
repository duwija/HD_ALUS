<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\FcmTokenUnregisteredException;

class FcmService
{
    // ------------------------------------------------------------------
    // Internal: Base64url encode (RFC 4648)
    // ------------------------------------------------------------------
    private static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // ------------------------------------------------------------------
    // Internal: Get OAuth2 access token via Service Account JWT
    // Cached selama 58 menit (token Google berlaku 1 jam)
    // ------------------------------------------------------------------
    private static function getAccessToken(): ?string
    {
        return Cache::remember('fcm_oauth_access_token', 3480, function () {
            $serviceAccountPath = storage_path('app/firebase-service-account.json');

            if (file_exists($serviceAccountPath)) {
                $sa = json_decode(file_get_contents($serviceAccountPath), true);
            } else {
                $jsonEnv = env('FCM_SERVICE_ACCOUNT_JSON', '');
                $sa = $jsonEnv ? json_decode($jsonEnv, true) : null;
            }

            if (!$sa || empty($sa['private_key']) || empty($sa['client_email'])) {
                Log::channel('notif')->warning(
                    '[FCM] Service account tidak ditemukan. ' .
                    'Letakkan file di: storage/app/firebase-service-account.json'
                );
                return null;
            }

            $now     = time();
            $header  = self::base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = self::base64url(json_encode([
                'iss'   => $sa['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $signingInput = $header . '.' . $payload;
            openssl_sign($signingInput, $rawSignature, $sa['private_key'], 'SHA256');
            $jwt = $signingInput . '.' . self::base64url($rawSignature);

            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $result = curl_exec($ch);
            $err    = curl_error($ch);
            curl_close($ch);

            if ($err) {
                Log::channel('notif')->error('[FCM] OAuth2 cURL error: ' . $err);
                return null;
            }

            $decoded = json_decode($result, true);
            if (empty($decoded['access_token'])) {
                Log::channel('notif')->error('[FCM] Gagal dapat access token: ' . $result);
                return null;
            }

            return $decoded['access_token'];
        });
    }

    // ------------------------------------------------------------------
    // PUBLIC: Kirim notifikasi ke 1 device
    // ------------------------------------------------------------------
    public static function send(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (empty($fcmToken)) return false;

        $projectId = env('FCM_PROJECT_ID', '');
        if (empty($projectId) && function_exists('tenant_config')) {
            $projectId = tenant_config('fcm_project_id', '');
        }

        if (empty($projectId)) {
            Log::channel('notif')->warning('[FCM] FCM_PROJECT_ID belum dikonfigurasi di .env');
            return false;
        }

        $accessToken = self::getAccessToken();
        if (!$accessToken) return false;

        $dataStr = array_map('strval', $data);

        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => ['title' => $title, 'body' => $body],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound'        => 'default',
                        'channel_id'   => 'billing_channel',
                    ],
                ],
                'apns' => [
                    'payload' => ['aps' => ['sound' => 'default']],
                ],
                'data' => $dataStr,
            ],
        ];

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::channel('notif')->error('[FCM] cURL error: ' . $error);
            return false;
        }

        if ($httpCode !== 200) {
            if ($httpCode === 401) {
                Cache::forget('fcm_oauth_access_token');
            }

            // Deteksi token UNREGISTERED (terjadi saat app di-reinstall / token kadaluarsa)
            if ($httpCode === 404) {
                $decoded = json_decode($result, true);
                $errorCode = $decoded['error']['details'][0]['errorCode'] ?? '';
                if ($errorCode === 'UNREGISTERED') {
                    Log::channel('notif')->warning('[FCM] Token UNREGISTERED — akan dihapus dari DB | token: ' . substr($fcmToken, 0, 20) . '...');
                    throw new FcmTokenUnregisteredException($fcmToken);
                }
            }

            Log::channel('notif')->warning('[FCM] Gagal kirim. HTTP ' . $httpCode . ' | ' . $result);
            return false;
        }

        Log::channel('notif')->info('[FCM] Push terkirim | ' . $title);
        return true;
    }

    // ------------------------------------------------------------------
    // PUBLIC: Kirim ke banyak device
    // ------------------------------------------------------------------
    public static function sendMulticast(array $tokens, string $title, string $body, array $data = []): bool
    {
        $tokens = array_values(array_filter($tokens));
        if (empty($tokens)) return false;

        $success = false;
        foreach ($tokens as $token) {
            try {
                if (self::send($token, $title, $body, $data)) $success = true;
            } catch (FcmTokenUnregisteredException $e) {
                // Token tidak valid — lanjutkan ke token berikutnya; caller bertanggung jawab membersihkan DB
                Log::channel('notif')->warning('[FCM] sendMulticast: skip token UNREGISTERED | ' . substr($token, 0, 20) . '...');
            }
        }
        return $success;
    }
}
