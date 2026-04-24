<?php


namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Walog;

class WaGatewayHelper
{
    protected static ?bool $hasSuppressionsTable = null;

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

        $suppressed = self::getActiveSuppression($hp);
        if ($suppressed) {
            $until = \Illuminate\Support\Carbon::parse($suppressed->suppress_until)->format('Y-m-d H:i:s');
            $reason = $suppressed->reason ?: 'auto_suppressed';
            $msg = "Nomor sedang disuppress sampai {$until} ({$reason})";
            Log::warning("[WA] Suppressed number {$hp}: {$msg}");
            self::logWalog('system', $hp, $message, 'suppressed', $msg);
            return [
                'status' => 'error',
                'message' => $msg,
            ];
        }

        try {
            // 🔁 Ambil daftar session aktif
            $health = Http::timeout(10)->get("$gatewayUrl/health");
            $sessions = $health->json()['sessions'] ?? [];
            // Normalize: if sessions is object {WA_01: {...}}, convert to ["WA_01", ...]
            if (is_array($sessions) && !array_is_list($sessions)) {
                $sessions = array_keys($sessions);
            }

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
            $lastError = null;

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
                        $lastError = "HTTP {$response->status()}: {$response->body()}";
                        Log::warning("[WA] HTTP error ($session): {$response->status()} - {$response->body()}");
                        self::logWalog($session, $hp, $message, 'http_error', $response->body());
                        continue;
                    }

                    $result = $response->json();

                    if (isset($result['status']) && $result['status'] === 'sent') {
                        Cache::put('wa_last_session', $session, now()->addMinutes(30));
                        self::markSendSuccess($hp);
                        self::logWalog($session, $hp, $message, 'sent');
                        Log::info("[WA] Pesan terkirim ke $hp via session $session");

                        return [
                            'status'  => 'success',
                            'session' => $session,
                            'message' => 'Pesan terkirim via session: ' . $session
                        ];
                    }

                    $err = $result['error'] ?? $result['message'] ?? 'Unknown gateway error';
                    $lastError = $err;
                    Log::warning("[WA] Gagal kirim via $session: $err");
                    self::logWalog($session, $hp, $message, 'failed', $err);
                } catch (\Throwable $e) {
                    $lastError = $e->getMessage();
                    Log::error("[WA] Exception ($session): " . $e->getMessage());
                    self::logWalog($session, $hp, $message, 'error', $e->getMessage());
                }
            }

            self::markSendFailure($hp, $lastError ?: "Semua session gagal setelah {$attempt} percobaan");

            return [
                'status'  => 'error',
                'message' => "Semua session gagal setelah $attempt percobaan"
            ];

        } catch (\Throwable $e) {
            Log::error("[WA] Gateway fatal: " . $e->getMessage());
            self::markSendFailure($hp, $e->getMessage());
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

    protected static function suppressionsEnabled(): bool
    {
        return (bool) tenant_config('WA_SUPPRESS_ENABLED', env('WA_SUPPRESS_ENABLED', true));
    }

    protected static function hasSuppressionsTable(): bool
    {
        if (self::$hasSuppressionsTable !== null) {
            return self::$hasSuppressionsTable;
        }

        try {
            self::$hasSuppressionsTable = Schema::hasTable('wa_suppressions');
        } catch (\Throwable $e) {
            self::$hasSuppressionsTable = false;
        }

        return self::$hasSuppressionsTable;
    }

    protected static function tenantKey(): string
    {
        $tenant = (string) tenant_config('domain_name', env('DOMAIN_NAME', 'default'));
        return $tenant !== '' ? $tenant : 'default';
    }

    protected static function getActiveSuppression(string $number)
    {
        if (!self::suppressionsEnabled() || !self::hasSuppressionsTable()) {
            return null;
        }

        return DB::table('wa_suppressions')
            ->where('tenant', self::tenantKey())
            ->where('number', $number)
            ->whereNotNull('suppress_until')
            ->where('suppress_until', '>', now())
            ->first();
    }

    protected static function markSendSuccess(string $number): void
    {
        if (!self::suppressionsEnabled() || !self::hasSuppressionsTable()) {
            return;
        }

        $tenant = self::tenantKey();
        $now = now();

        DB::table('wa_suppressions')->updateOrInsert(
            ['tenant' => $tenant, 'number' => $number],
            [
                'consecutive_failures' => 0,
                'suppress_until' => null,
                'reason' => null,
                'last_error' => null,
                'last_sent_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    protected static function markSendFailure(string $number, string $error): void
    {
        if (!self::suppressionsEnabled() || !self::hasSuppressionsTable()) {
            return;
        }

        $tenant = self::tenantKey();
        $now = now();

        $threshold = max(2, (int) tenant_config('WA_SUPPRESS_FAIL_THRESHOLD', env('WA_SUPPRESS_FAIL_THRESHOLD', 4)));
        $step2Threshold = max($threshold + 1, (int) tenant_config('WA_SUPPRESS_STEP2_THRESHOLD', env('WA_SUPPRESS_STEP2_THRESHOLD', 6)));
        $step3Threshold = max($step2Threshold + 1, (int) tenant_config('WA_SUPPRESS_STEP3_THRESHOLD', env('WA_SUPPRESS_STEP3_THRESHOLD', 8)));

        $daysStep1 = max(1, (int) tenant_config('WA_SUPPRESS_DAYS_STEP1', env('WA_SUPPRESS_DAYS_STEP1', 7)));
        $daysStep2 = max($daysStep1, (int) tenant_config('WA_SUPPRESS_DAYS_STEP2', env('WA_SUPPRESS_DAYS_STEP2', 14)));
        $daysStep3 = max($daysStep2, (int) tenant_config('WA_SUPPRESS_DAYS_STEP3', env('WA_SUPPRESS_DAYS_STEP3', 30)));

        DB::transaction(function () use (
            $tenant,
            $number,
            $now,
            $error,
            $threshold,
            $step2Threshold,
            $step3Threshold,
            $daysStep1,
            $daysStep2,
            $daysStep3
        ) {
            $row = DB::table('wa_suppressions')
                ->where('tenant', $tenant)
                ->where('number', $number)
                ->lockForUpdate()
                ->first();

            $consecutiveFailures = ((int) ($row->consecutive_failures ?? 0)) + 1;
            $totalFailures = ((int) ($row->total_failures ?? 0)) + 1;

            $suppressUntil = null;
            $reason = null;
            if ($consecutiveFailures >= $threshold) {
                $days = $daysStep1;
                if ($consecutiveFailures >= $step3Threshold) {
                    $days = $daysStep3;
                } elseif ($consecutiveFailures >= $step2Threshold) {
                    $days = $daysStep2;
                }
                $suppressUntil = $now->copy()->addDays($days);
                $reason = 'auto_fail_streak_' . $consecutiveFailures;
            }

            $payload = [
                'total_failures' => $totalFailures,
                'consecutive_failures' => $consecutiveFailures,
                'last_error' => mb_substr($error, 0, 1000),
                'last_failed_at' => $now,
                'suppress_until' => $suppressUntil,
                'reason' => $reason,
                'updated_at' => $now,
                'created_at' => $row->created_at ?? $now,
            ];

            DB::table('wa_suppressions')->updateOrInsert(
                ['tenant' => $tenant, 'number' => $number],
                $payload
            );

            if ($suppressUntil) {
                Log::warning('[WA] Number auto-suppressed', [
                    'tenant' => $tenant,
                    'number' => $number,
                    'consecutive_failures' => $consecutiveFailures,
                    'suppress_until' => $suppressUntil,
                    'reason' => $reason,
                ]);
            }
        });
    }
}