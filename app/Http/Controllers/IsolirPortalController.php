<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Halaman captive portal untuk pelanggan yang terisolir (publik, tanpa login).
 *
 * Router me-redirect HTTP pelanggan di address-list "isolir" ke /isolir
 * (lihat IsolirScriptBuilder). Karena request sampai ke server lewat NAT router,
 * pelanggan tidak bisa dikenali dari IP — jadi pelanggan memasukkan ID pelanggan
 * + 4 digit terakhir nomor HP untuk membuka halaman tagihannya (/invoice/cst/...).
 */
class IsolirPortalController extends Controller
{
    public function show()
    {
        return response()
            ->view('isolir.portal', $this->viewData())
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|string|max:50',
            'phone_last4' => 'required|digits:4',
        ], [
            'customer_id.required' => 'ID pelanggan wajib diisi.',
            'phone_last4.required' => '4 digit terakhir nomor HP wajib diisi.',
            'phone_last4.digits'   => 'Isi tepat 4 digit terakhir nomor HP.',
        ]);

        $cid = trim($request->input('customer_id'));
        $customer = \App\Customer::where('customer_id', $cid)->first();
        $phoneDigits = $customer ? preg_replace('/\D+/', '', (string) $customer->phone) : '';

        if (!$customer || strlen($phoneDigits) < 4 || substr($phoneDigits, -4) !== $request->input('phone_last4')) {
            Log::channel('isolir')->info("[ISOLIR PORTAL] lookup gagal cid={$cid} ip={$request->ip()}");
            return back()->withInput($request->only('customer_id'))
                ->withErrors(['customer_id' => 'ID pelanggan atau nomor HP tidak cocok.']);
        }

        return redirect('/invoice/cst/' . Crypt::encryptString($customer->id));
    }

    private function viewData(): array
    {
        $wa = preg_replace('/\D+/', '', (string) tenant_config('payment_wa', env('PAYMENT_WA')));
        if (str_starts_with($wa, '0')) {
            $wa = '62' . substr($wa, 1);
        }

        return [
            'companyName' => tenant_config('company_name', config('app.name')),
            'waNumber'    => $wa,
        ];
    }
}
