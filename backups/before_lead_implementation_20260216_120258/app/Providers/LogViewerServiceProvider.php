<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LogViewerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Log viewer configuration will use the config file directly
        // The include_files pattern in config/log-viewer.php will handle tenant filtering
    }
}
