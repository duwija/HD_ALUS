<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Walog;


class WaGatewayHelper
{
    public static function wa_payment($phone, $message)
    {
        try {
            $gatewayUrl = env('WAGATEWAY_URL', 'http://localhost:3001/api');
            $response = Http::timeout(15)->get($gatewayUrl . '/health');
            $data = $response->json();
            $sessions = $data['sessions'] ?? [];

            if (empty($sessions)) {
                return [
                    'status'  => 'error',
                    'message' => 'Tidak ada session aktif.'
                ];
            }


            // Round-robin dengan cache untuk sesi terakhir
            $lastSession = Cache::get('wa_last_session');
            $nextIndex = 0;

            if ($lastSession && in_array($lastSession, $sessions)) {
                $lastIndex = array_search($lastSession, $sessions);
                $nextIndex = ($lastIndex + 1) % count($sessions);
            }

            // Rotasi urutan session berdasarkan round-robin
            $rotatedSessions = array_merge(
                array_slice($sessions, $nextIndex),
                array_slice($sessions, 0, $nextIndex)
            );

            $hp = preg_replace('/^(\+62|0)/', '62', trim($phone));
            $maxRetries = 3;
            $attempts = 0;
            $success = false;

            foreach ($rotatedSessions as $session) {
                if ($attempts >= $maxRetries) break;
                $attempts++;

                try {
                    $send = Http::timeout(15)->post("$gatewayUrl/$session/send", [
                        'number' => $hp,
                        'message' => $message,
                    ]);

                    $result = $send->json();

                    if (isset($result['status']) && in_array($result['status'], ['sent','success'], true)) {
                       Cache::put('wa_last_session', $session, now()->addMinutes(20));
                       Log::info("WA terkirim via session: $session");

                        // Simpan log ke DB
                       Walog::create([
                        'session' => $session,
                        'number' => $hp,
                        'message' => $message,
                        'status' => 'sent',
                        'created_at' => now(),
                    ]);

                       return [
                        'status'  => 'success',
                        'session' => $session,
                        'message' => 'Terkirim via session: '.$session,
                    ];
                } else {
                    Log::warning("WA session $session gagal kirim: " . json_encode($result));
                    Walog::create([
                        'session' => $session,
                        'number' => $hp,
                        'message' => $message,
                        'status' => $result['status'] ?? 'failed',
                        'error' => $result['message'] ?? 'Unknown error',
                        'created_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning("WA session $session error: " . $e->getMessage());
                Walog::create([
                    'session' => $session,
                    'number' => $hp,
                    'message' => $message,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'created_at' => now(),
                ]);
            }
        }

        return [
            'status'  => 'error',
            'message' => 'Semua session gagal mengirim pesan setelah '.$attempts.' percobaan'
        ];
    } catch (\Exception $e) {
        Log::error("WA Gateway Error: " . $e->getMessage());
        return [
            'status'  => 'error',
            'message' => 'Error mengirim pesan: '.$e->getMessage()
        ];
    }
}
public static function countSentMessagesBySession($session)
{
    return DB::table('walogs')
    ->where('session', $session)
    ->where('status', 'sent')
    ->whereBetween('created_at', [
        now()->startOfMonth(),
        now()->endOfMonth()
    ])->count();
}

}
