<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Must be in register() so it fires before SanctumServiceProvider::boot()
        // which calls loadMigrationsFrom(). Calling it in boot() is too late.
        Sanctum::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        config(['app.locale' => 'id']);
        Carbon::setLocale('id');
        Schema::defaultStringLength(191);
        // unmask(0002);
#	resolve(\Illuminate\Routing\UrlGenerator::class)->forceScheme('https');

 #       parent::boot();
    }
}
