<?php

namespace App\Http\Controllers;

use App\ShortUrl;
use Illuminate\Http\Request;

class ShortUrlController extends Controller
{
    /**
     * Resolve short code and redirect to original URL.
     * Accessible without auth (for customers clicking invoice links).
     */
    public function redirect(string $code)
    {
        $short = ShortUrl::where('code', $code)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$short) {
            abort(404, 'Link tidak ditemukan atau sudah kadaluarsa.');
        }

        $short->increment('clicks');

        return redirect($short->original_url, 301);
    }
}
