<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Authenticated;

class LogAuthToFile
{
    public function handle($event): void
    {
        $req = request();
        $ctx = [
            'ip'         => $req?->ip(),
            'user_agent' => $req?->userAgent(),
            'route'      => $req?->path(),
            'session'    => $req?->session()?->getId(),
            'guard'      => method_exists($event, 'guard') ? $event->guard : null,
        ];

        if ($event instanceof Login) {
            Log::channel('auth')->info('login', [
                'user_id' => $event->user->id ?? null,
                'email'   => $event->user->email ?? null,
            ] + $ctx);
            return;
        }

        // Auto-login via "remember me"
        if ($event instanceof Authenticated) {
            Log::channel('auth')->info('authenticated', [
                'user_id' => $event->user->id ?? null,
                'email'   => $event->user->email ?? null,
            ] + $ctx);
            return;
        }

        if ($event instanceof Logout) {
            Log::channel('auth')->info('logout', [
                'user_id' => optional(auth()->user())->id,
                'email'   => optional(auth()->user())->email,
            ] + $ctx);
            return;
        }

        if ($event instanceof Failed) {
            Log::channel('auth')->warning('login_failed', [
                'email' => $event->credentials['email'] ?? null,
            ] + $ctx);
            return;
        }

        if ($event instanceof Lockout) {
            Log::channel('auth')->warning('login_lockout', $ctx);
        }
    }
}