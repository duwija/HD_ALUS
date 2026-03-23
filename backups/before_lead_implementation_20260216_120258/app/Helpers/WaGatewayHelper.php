<?php


namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Walog;

class WaGatewayHelper
{
    public static function countSentMessagesBySession($session)
    {
        return Walog::where('session', $session)
        ->where('direction', 'out')
        ->count();
    }

    public static function getSessionStats($session)
    {
        return [
            'total_sent' => Walog::where('session', $session)
            ->where('direction', 'out')
            ->count(),
            'total_received' => Walog::where('session', $session)
            ->where('direction', 'in')
            ->count(),
            'pending' => Walog::where('session', $session)
            ->where('status', 'pending')
            ->count(),
            'delivered' => Walog::where('session', $session)
            ->where('status', 'delivered')
            ->count(),
            'read' => Walog::where('session', $session)
            ->where('status', 'read')
            ->count(),
        ];
    }

    public static function wa_payment($phone, $message = null, $filePath = null, $caption = null)
    {
        $baseUrl = rtrim(tenant_config('wa_gateway_url', env('WA_GATEWAY_URL', 'http://127.0.0.1:3005')), '/');

        // 🔍 Cek apakah gateway pakai prefix /api
        $hasApiPrefix = false;
        try {
            $health = Http::timeout(5)->get("$baseUrl/health");
            if (!$health->successful()) {
                $testApi = Http::timeout(5)->get("$baseUrl/api/health");
                if ($testApi->successful()) {
                    $hasApiPrefix = true;
                }
            }
        } catch (\Exception $e) {
            // fallback default ke /api
            $hasApiPrefix = true;
        }

        $gatewayUrl = $hasApiPrefix ? $baseUrl . '/api' : $baseUrl;

        // Format nomor WA
       $hp = trim($phone);

// hilangkan semua spasi, dash, dll kalau perlu
$hp = preg_replace('/[\s\-]/', '', $hp);

// hilangkan tanda +
$hp = ltrim($hp, '+');

// kalau diawali 0, ganti menjadi 62
$hp = preg_replace('/^0/', '62', $hp);

// validasi: hanya angka dan panjang wajar
if (!preg_match('/^\d{8,15}$/', $hp)) {
    return [
        'status'  => 'error',
        'message' => 'Nomor WhatsApp tidak valid: ' . $hp
    ];
}

        try {
            // 🔁 Ambil daftar session aktif
            $health = Http::timeout(10)->get("$gatewayUrl/health");
            $sessions = $health->json()['sessions'] ?? [];

            if (empty($sessions)) {
                return [
                    'status' => 'error',
                    'message' => 'Tidak ada session aktif di gateway.'
                ];
            }

            // 🔄 Round-robin session
            $lastSession = Cache::get('wa_last_session');
            $startIndex = 0;
            if ($lastSession && in_array($lastSession, $sessions)) {
                $lastIndex = array_search($lastSession, $sessions);
                $startIndex = ($lastIndex + 1) % count($sessions);
            }
            $rotated = array_merge(
                array_slice($sessions, $startIndex),
                array_slice($sessions, 0, $startIndex)
            );

            $maxRetries = 3;
            $attempt = 0;

            foreach ($rotated as $session) {
                if ($attempt >= $maxRetries) break;
                $attempt++;

                try {
                    if ($filePath && file_exists($filePath)) {
                        // Kirim MEDIA
                        $sendUrl = "$gatewayUrl/$session/send-media";
                        $response = Http::timeout(30)
                        ->attach('file', file_get_contents($filePath), basename($filePath))
                        ->post($sendUrl, [
                            'number' => $hp,
                            'caption' => $caption ?? $message ?? '',
                        ]);
                    } else {
                        // Kirim TEKS
                        $sendUrl = "$gatewayUrl/$session/send";
                        $response = Http::timeout(15)->post($sendUrl, [
                            'number' => $hp,
                            'message' => $message,
                        ]);
                    }

                    if (!$response->successful()) {
                        Log::warning("[WA] HTTP error ($session): {$response->status()} - {$response->body()}");
                        self::logWalog($session, $hp, $message, 'http_error', $response->body());
                        continue;
                    }

                    $result = $response->json();

                    if (isset($result['status']) && $result['status'] === 'sent') {
                        Cache::put('wa_last_session', $session, now()->addMinutes(30));
                        self::logWalog($session, $hp, $message, 'sent');
                        Log::info("[WA] Pesan terkirim ke $hp via session $session");

                        return [
                            'status'  => 'success',
                            'session' => $session,
                            'message' => 'Pesan terkirim via session: ' . $session
                        ];
                    }

                    $err = $result['error'] ?? $result['message'] ?? 'Unknown gateway error';
                    Log::warning("[WA] Gagal kirim via $session: $err");
                    self::logWalog($session, $hp, $message, 'failed', $err);
                } catch (\Throwable $e) {
                    Log::error("[WA] Exception ($session): " . $e->getMessage());
                    self::logWalog($session, $hp, $message, 'error', $e->getMessage());
                }
            }

            return [
                'status'  => 'error',
                'message' => "Semua session gagal setelah $attempt percobaan"
            ];

        } catch (\Throwable $e) {
            Log::error("[WA] Gateway fatal: " . $e->getMessage());
            return [
                'status'  => 'error',
                'message' => 'Gagal mengirim pesan: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Simpan log ke tabel walog
     */
    protected static function logWalog($session, $number, $message, $status, $error = null)
    {
        try {
            Walog::create([
                'session'    => $session,
                'number'     => $number,
                'message'    => $message,
                'status'     => $status,
                'direction'  => 'out',
                'error'      => $error,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[WA] Gagal simpan log Walog: ' . $e->getMessage());
        }
    }
}