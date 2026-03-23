<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RouterOS\Client;
use RouterOS\Query;
use App\Distrouter;
use App\PppoeStat;
use Carbon\Carbon;

class CollectPppoeStats extends Command
{
    protected $signature   = 'pppoe:collect-stats';
    protected $description = 'Collect PPPoE stats (total/active/offline/disabled) from all Distrouters and store to DB';

    public function handle()
    {
        $routers = Distrouter::all();

        if ($routers->isEmpty()) {
            $this->info('No routers found.');
            return 0;
        }

        $now = Carbon::now();
        $collected = 0;

        foreach ($routers as $router) {
            try {
                $client = new Client([
                    'host'    => $router->ip,
                    'user'    => $router->user,
                    'pass'    => $router->password,
                    'port'    => (int) $router->port,
                    'timeout' => 5,
                ]);

                // Active PPPoE sessions
                $active = 0;
                try {
                    $q      = new Query('/ppp/active/print');
                    $result = $client->query($q)->read();
                    $active = count($result);
                    $onlineNames = collect($result)->pluck('name')->toArray();
                } catch (\Exception $e) {
                    $onlineNames = [];
                }

                // All secrets
                $total    = 0;
                $offline  = 0;
                $disabled = 0;
                try {
                    $q       = new Query('/ppp/secret/print');
                    $secrets = $client->query($q)->read();
                    $total   = count($secrets);
                    foreach ($secrets as $s) {
                        if (isset($s['disabled']) && $s['disabled'] === 'true') {
                            $disabled++;
                        } elseif (!in_array($s['name'], $onlineNames)) {
                            $offline++;
                        }
                    }
                } catch (\Exception $e) {
                    // leave as 0
                }

                PppoeStat::create([
                    'distrouter_id' => $router->id,
                    'total'         => $total,
                    'active'        => $active,
                    'offline'       => $offline,
                    'disabled'      => $disabled,
                    'collected_at'  => $now,
                ]);

                $collected++;
                $this->line("  ✓ {$router->name} — total:{$total} active:{$active} offline:{$offline} disabled:{$disabled}");

            } catch (\Exception $e) {
                \Log::warning("[PppoeStats] Router {$router->name} ({$router->ip}) error: " . $e->getMessage());
                $this->warn("  ✗ {$router->name}: " . $e->getMessage());
            }
        }

        // Clean up data older than 7 days to keep DB lean
        PppoeStat::where('collected_at', '<', Carbon::now()->subDays(7))->delete();

        $this->info("Collected stats for {$collected}/{$routers->count()} routers.");
        return 0;
    }
}
