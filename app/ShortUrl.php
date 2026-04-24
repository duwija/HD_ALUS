<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShortUrl extends Model
{
    protected $fillable = ['code', 'original_url', 'tenant', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    /**
     * Generate or retrieve a short URL code for the given original URL.
     *
    * Returns the full short URL string: https://domain/s/CODE
     */
    public static function shorten(string $originalUrl, ?int $expiryDays = 365): string
    {
        $tenant = tenant_config('domain_name', env('DOMAIN_NAME', parse_url(config('app.url'), PHP_URL_HOST)));

        // Re-use existing short code if same URL + tenant
        $existing = static::where('original_url', $originalUrl)
            ->where('tenant', $tenant)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existing) {
            return static::buildShortLink($existing->code, $tenant);
        }

        // Generate a unique 7-char code
        do {
            $code = Str::upper(Str::random(3)) . Str::lower(Str::random(4));
        } while (static::where('code', $code)->exists());

        static::create([
            'code'         => $code,
            'original_url' => $originalUrl,
            'tenant'       => $tenant,
            'expires_at'   => $expiryDays ? now()->addDays($expiryDays) : null,
        ]);

        return static::buildShortLink($code, $tenant);
    }

    protected static function buildShortLink(string $code, string $tenant): string
    {
        $domain = str_contains($tenant, '://') ? $tenant : 'https://' . $tenant;
        $domain = preg_replace('/^http:\/\//i', 'https://', $domain);
        return rtrim($domain, '/') . '/s/' . $code;
    }
}
