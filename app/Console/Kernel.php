<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\InvoiceCron::class,
        Commands\IsolirAuto::class,
        Commands\PruneTenantLogFiles::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('isolir:auto')->dailyAt('01:00');
        $schedule->command('pppoe:collect-stats-all')->everyThreeMinutes();
        $schedule->command('pppoe:sync-sessions-all')->everyFiveMinutes();
        $schedule->command('pings:prune-all')->dailyAt('02:00');
        $schedule->command('alerts:prune-all')->dailyAt('02:10');
        $schedule->command('logs:prune-tenants')->dailyAt('02:20');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
     $this->load(__DIR__.'/Commands');

     require base_path('routes/console.php');
  }
}
