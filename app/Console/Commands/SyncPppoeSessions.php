<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use RouterOS\Client;
use RouterOS\Query;
use App\Distrouter;
use Carbon\Carbon;

class SyncPppoeSessions extends Command
{
    protected $signature   = 'pppoe:sync-sessions';
    protected $description = 'Sync PPPoE session online/offline status from all routers to pppoe_sessions table (2-pass cross-router check)';

    public function handle()
    {
        $routers = Distrouter::all();

        if ($routers->isEmpty()) {
            $this->info('No routers found.');
            return 0;
        }

        $now            = Carbon::now();
        $skipFailMinutes = 10;

        // ── Pass 1: collect ALL online PPPoE names from all routers ──────────
        $globalOnlineIndex = [];   // pppoe_name => router_id (last seen on)
        $routerDataCache   = [];   // router_id => ['secrets' => [...], 'online' => [...]]

        foreach ($routers as $router) {
            $skipKey = 'pppoe_sync_skip_' . $router->id;
            if (Cache::has($skipKey)) {
                $this->line("  ⏭ {$router->name} — skipped (still in cooldown)");
                continue;
            }

            try {
                $client = new Client([
                    'host'    => $router->ip,
                    'user'    => $router->user,
                    'pass'    => $router->password,
                    'port'    => (int) $router->port,
                    'timeout' => 5,
                ]);

                // Active sessions
                $q       = new Query('/ppp/active/print');
                $active  = $client->query($q)->read();
                $onlineNames = collect($active)->pluck('name')->filter()->toArray();

                foreach ($onlineNames as $name) {
                    $globalOnlineIndex[$name] = $router->id;
                }

                // All secrets for this router
                $q       = new Query('/ppp/secret/print');
                $secrets = $client->query($q)->read();

                $routerDataCache[$router->id] = [
                    'router'  => $router,
                    'secrets' => $secrets,
                    'online'  => $onlineNames,
                ];

                // Reset failure counter on success
                Cache::forget('pppoe_sync_fail_' . $router->id);

            } catch (\Exception $e) {
                // Track failures; skip router after 3 consecutive failures
                $failKey   = 'pppoe_sync_fail_' . $router->id;
                $failCount = (int) Cache::get($failKey, 0) + 1;
                Cache::put($failKey, $failCount, now()->addMinutes(30));

                if ($failCount >= 3) {
                    Cache::put($skipKey, 1, now()->addMinutes($skipFailMinutes));
                    $this->warn("  ✗ {$router->name} — put in cooldown after {$failCount} failures");
                } else {
                    $this->warn("  ✗ {$router->name} — failed (attempt {$failCount}): " . $e->getMessage());
                }
            }
        }

        // ── Pass 2: upsert pppoe_sessions using global online index ──────────
        $upserted = 0;

        foreach ($routerDataCache as $routerId => $data) {
            $router  = $data['router'];
            $secrets = $data['secrets'];

            foreach ($secrets as $secret) {
                $name     = $secret['name'] ?? null;
                if (!$name) continue;

                // Keep map behavior consistent: disabled PPP secrets are not treated as offline customers.
                $isDisabled = isset($secret['disabled']) && $secret['disabled'] === 'true';
                if ($isDisabled) continue;

                // A PPPoE is online if it appears in ANY router's active sessions
                $isOnline = isset($globalOnlineIndex[$name]);

                DB::table('pppoe_sessions')->upsert(
                    [
                        'distrouter_id'  => $routerId,
                        'pppoe_name'     => $name,
                        'is_online'      => $isOnline ? 1 : 0,
                        'last_offline_at'=> $isOnline ? null : $now->toDateTimeString(),
                        'synced_at'      => $now->toDateTimeString(),
                        'created_at'     => $now->toDateTimeString(),
                        'updated_at'     => $now->toDateTimeString(),
                    ],
                    ['distrouter_id', 'pppoe_name'],                     // unique keys
                    ['is_online', 'last_offline_at', 'synced_at', 'updated_at']  // update cols
                );

                $upserted++;
            }

            $this->line("  ✓ {$router->name} — " . count($secrets) . " secrets synced");
        }

        $this->info("Sync done — {$upserted} PPPoE records upserted at {$now}");
        return 0;
    }
}
