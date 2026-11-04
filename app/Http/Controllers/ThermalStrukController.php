<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

/**
 * Struk thermal lewat link bertanda tangan (signed URL, berlaku sebentar).
 *
 * Android WebView (aplikasi) tidak bisa window.print(), jadi struk dibuka di Chrome.
 * Chrome tidak punya sesi login admin, maka halaman struk dibuka lewat link ini:
 * dibuat dari sesi yang sudah login, berisi id user pembuatnya, dan ditolak bila
 * tanda tangannya salah / kedaluwarsa (middleware 'signed').
 */
class ThermalStrukController extends Controller
{
    /** Hak akses sama dengan route asli masing-masing struk. */
    private const PRIVILEGES = [
        'suminvoice' => ['admin', 'accounting', 'payment', 'noc', 'marketing', 'merchant'],
        'payment'    => ['admin', 'accounting', 'payment', 'merchant'],
    ];

    private const TTL_MINUTES = 30;

    /** Link struk untuk dibuka di browser luar; null bila belum login. */
    public static function signedUrl(string $type, string $tempcode): ?string
    {
        $user = Auth::user();
        if (!$user || !isset(self::PRIVILEGES[$type])) {
            return null;
        }

        return URL::temporarySignedRoute('struk.thermal', now()->addMinutes(self::TTL_MINUTES), [
            'type' => $type,
            'id'   => $tempcode,
            'u'    => $user->id,
        ]);
    }

    public function show(Request $request, string $type, string $id)
    {
        $allowed = self::PRIVILEGES[$type] ?? null;
        $user = \App\User::find((int) $request->query('u'));

        if (!$allowed || !$user
            || !in_array(strtolower(trim((string) $user->privilege)), $allowed, true)) {
            abort(403, 'Link struk tidak berlaku.');
        }

        // Hanya untuk request ini (tanpa sesi) — dotmatrix memakai Auth::user() untuk admin_fee.
        Auth::onceUsingId($user->id);

        return $type === 'payment'
            ? app(PaymentController::class)->dotmatrix($id)
            : app(SuminvoiceController::class)->dotmatrix($id);
    }
}
