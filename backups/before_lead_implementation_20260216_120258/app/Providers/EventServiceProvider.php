<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \Illuminate\Auth\Events\Login::class         => [\App\Listeners\LogAuthToFile::class],
    // \Illuminate\Auth\Events\Authenticated::class => [\App\Listeners\LogAuthToFile::class], // untuk remember me
    \Illuminate\Auth\Events\Logout::class        => [\App\Listeners\LogAuthToFile::class],
    \Illuminate\Auth\Events\Failed::class        => [\App\Listeners\LogAuthToFile::class],
    \Illuminate\Auth\Events\Lockout::class       => [\App\Listeners\LogAuthToFile::class],
];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
