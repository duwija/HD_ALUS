<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF;
use \Auth;
Use GuzzleHttp\Clients;
use App\Jobs\NotifInvJob;
use App\Traits\SendsCustomerNotification;
use App\Jobs\IsolirJob;
use App\Jobs\CreateInvJob;
use App\Jobs\EnableMikrotikJob; 
use App\User;
use Xendit\Xendit;

use App\Suminvoice;
use App\Invoice;
use App\Jurnal;
use Exception;   
use DB;
use DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Mail\EmailNotification;
use App\Mail\EmailReceivePayment;
use App\AppCustomerNotification;
use App\Services\FcmService;
use App\Helpers\WaGatewayHelper;
use Illuminate\Support\Facades\Mail;


use Illuminate\Support\Facades\Http;

class SuminvoiceController extends Controller
{

    private function getDuitkuConfig(string $provider = 'duitku'): array
    {
        $provider = in_array($provider, ['duitku', 'duitku2'], true) ? $provider : 'duitku';
        $gw = \App\PaymentGateway::findForCurrentTenant($provider);
        $settings = $gw ? ($gw->settings ?? []) : [];

        if ($provider === 'duitku' && $gw) {
            $legacySettings = [
                'merchant_code' => trim((string) tenant_config('DUITKU_MERCHANT_CODE', env('DUITKU_MERCHANT_CODE'))),
                'api_key'       => trim((string) tenant_config('DUITKU_API_KEY', env('DUITKU_API_KEY'))),
                'sandbox'       => (bool) tenant_config('DUITKU_SANDBOX', env('DUITKU_SANDBOX', false)),
            ];

            $shouldSyncLegacy = false;
            foreach (['merchant_code', 'api_key'] as $key) {
                if (empty($settings[$key]) && !empty($legacySettings[$key])) {
                    $settings[$key] = $legacySettings[$key];
                    $shouldSyncLegacy = true;
                }
            }
            if (!array_key_exists('sandbox', $settings)) {
                $settings['sandbox'] = $legacySettings['sandbox'];
                $shouldSyncLegacy = true;
            }

            if ($shouldSyncLegacy) {
                $gw->settings = $settings;
                $gw->save();
            }
        }

        if (empty($settings['merchant_code']) || empty($settings['api_key'])) {
            throw new \RuntimeException('Konfigurasi ' . strtoupper($provider) . ' belum lengkap. Isi Merchant Code dan API Key di menu Payment Gateway tenant.');
        }

        return [
            'merchant_code' => trim((string) ($settings['merchant_code'] ?? '')),
            'api_key'       => trim((string) ($settings['api_key'] ?? '')),
            'sandbox'       => (bool) ($settings['sandbox'] ?? false),
        ];
    }

    private function getWinpayErrorMessage(?array $responseData, string $rawResponse = '', ?int $httpCode = null): string
    {
        $message = $responseData['responseMessage']
            ?? $responseData['message']
            ?? $responseData['error']
            ?? ($responseData['errors']['message'] ?? null);

        if (!empty($message)) {
            return $message;
        }

        if ($httpCode) {
            return 'HTTP ' . $httpCode . ': ' . substr($rawResponse, 0, 200);
        }

        return 'Unknown error';
    }

    private function getWinpayConfig(string $provider = 'winpay'): array
    {
        $provider = in_array($provider, ['winpay', 'winpay2'], true) ? $provider : 'winpay';
        $winpayGw = \App\PaymentGateway::findForCurrentTenant($provider);
        $settings = $winpayGw ? ($winpayGw->settings ?? []) : [];

        if ($provider === 'winpay' && $winpayGw) {
            $legacySettings = [
                'endpoint'   => trim((string) tenant_config('WINPAY_ENDPOINT', env('WINPAY_ENDPOINT'))),
                'api_key'    => trim((string) tenant_config('WINPAY_KEY', env('WINPAY_KEY'))),
                'secret_key' => trim((string) tenant_config('WINPAY_SECRET', env('WINPAY_SECRET'))),
            ];

            $shouldSyncLegacy = false;
            foreach (['endpoint', 'api_key', 'secret_key'] as $key) {
                if (empty($settings[$key]) && !empty($legacySettings[$key])) {
                    $settings[$key] = $legacySettings[$key];
                    $shouldSyncLegacy = true;
                }
            }

            if ($shouldSyncLegacy) {
                $winpayGw->settings = $settings;
                $winpayGw->save();
            }
        }

        if ($provider === 'winpay2' && empty($settings['endpoint'])) {
            $mainWinpay = \App\PaymentGateway::findForCurrentTenant('winpay');
            $mainSettings = $mainWinpay ? ($mainWinpay->settings ?? []) : [];
            if (!empty($mainSettings['endpoint'])) {
                $settings['endpoint'] = $mainSettings['endpoint'];
            }
        }

        if (empty($settings['endpoint']) || empty($settings['api_key']) || empty($settings['secret_key'])) {
            throw new \RuntimeException('Konfigurasi ' . strtoupper($provider) . ' belum lengkap. Isi Endpoint, API Key, dan Secret Key di menu Payment Gateway tenant.');
        }

        return [
            'endpoint'   => trim((string) ($settings['endpoint'] ?? '')),
            'api_key'    => trim((string) ($settings['api_key'] ?? '')),
            'secret_key' => trim((string) ($settings['secret_key'] ?? '')),
        ];
    }

    use SendsCustomerNotification;

   public function __construct()
   {
        //$this->middleware('auth');
    $this->middleware('auth', ['except' => ['print', 'notifinvJob', 'tripay','createWinpayVA','deleteWinpayVA','findWinpayVA','createDuitkuVA','resetDuitkuVA','createBundlePayment','resetPaymentPending','cancelBundle']]); 
    $this->middleware('checkPrivilege:admin,accounting,payment,noc', ['except' => ['print', 'notifinvJob', 'tripay','createWinpayVA','deleteWinpayVA','findWinpayVA','createDuitkuVA','resetDuitkuVA','createBundlePayment','resetPaymentPending','cancelBundle']]);
}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function createinvmonthlyJob(Request $request)
    {
        $inv_date = $request->inv_date;
        $id_merchant = $request->id_merchant;

    // Query pelanggan dengan status 2 atau 4
        $customer = \App\Customer::where(function ($query) {
            $query->where('customers.id_status', '2')
            ->orWhere('customers.id_status', '4');
        });

    // Jika id_merchant diberikan, tambahkan filter
        if (!empty($id_merchant)) {
            $customer->where('id_merchant', $id_merchant);
        }

    // Eksekusi query untuk mendapatkan data pelanggan
        $customers = $customer->get();

    // Mulai eksekusi job dengan delay
        $start = Carbon::now();
        $count = 0;

        $tenantQueue = app('tenant')['domain'] ?? 'default';
        foreach ($customers as $cust) {
            $count++;
            CreateInvJob::dispatch($cust->id, $inv_date)
            ->onQueue($tenantQueue)
            ->delay($start->addSeconds(10));
        }

    // Pesan sukses
        $msg = 'Processing create ' . $count . ' Invoice(s)';

        return redirect('suminvoice/notification')->with('info', $msg);
    }




    public function tripay(Request $request)
    {



        $endpoint     = tenant_config('tripay_endpoint', env("TRIPAY_ENDPOINT"));
        $apiKey       = tenant_config('tripay_apikey', env("TRIPAY_APIKEY"));
        $privateKey   = tenant_config('tripay_privatekey', env("TRIPAY_PRIVATEKEY"));
        $merchantCode = tenant_config('tripay_merchantcode', env("TRIPAY_MERCHANTCODE"));
        $merchantRef  = $request->number;
        $amount       = round($request->amount);
        $tempcode       = $request->tempcode;
        $return_url = url('suminvoice/' . $tempcode . '/print');
        $hash = (hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey));
        $email = $request->email;
        $customer_id = $request->customer_id;
        $name = $request->name;
        $phone = $request->phone;
        if (empty($phone))
        {
            $phone = '0818000000';
        }
        if (empty($email))
        {
            $email = 'billing@alus.co.id';
        }

        $data = [
            'method'         => $request->method,
            'merchant_ref'   => $merchantRef,
            'amount'         => $amount,
            'customer_name'  => $name.' | '.$customer_id,
            'customer_email' => $email,
            'customer_phone' => $phone,

            'order_items'    => [
                [
                    'sku'         => $request->description,
                    'name'        => 'Invoice ' . tenant_config('APP_NAME', config('app.name', 'ISP')) . ' No #'. $merchantRef,
                    'price'       => $amount,
                    'quantity'    => 1,

                ]

            ],
            'return_url'     => $return_url,
    'expired_time' => (time() + (24 * 60 * 60)), // 24 jam
    'signature'    => $hash,

];
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_FRESH_CONNECT  => true,
    CURLOPT_URL            => $endpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => false,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
    CURLOPT_FAILONERROR    => false,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($data),
    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
]);

$response = curl_exec($curl);
$error = curl_error($curl);
if(curl_errno($curl)){
    echo 'Request Error:' . curl_error($curl);
}
else
    $data = json_decode($response, true);
if(empty($data['data']['reference']))
{
    echo $data['message'];
}
else

{
    $start = Carbon::now();
    $reference = $data['data']['reference'];
    $merchant_ref = $data['data']['merchant_ref'];
    $query = \App\Suminvoice::where('number', $merchant_ref)
    ->update([
        'payment_id' =>$reference ,
    ]);

    $payment_name = $data['data']['payment_name'];
// $tagihan = $data['data']['amount_received'];
// $admin_fee = $data['data']['total_fee'];
// $total = $data['data']['amount'];


    $expired_time = date('Y-m-d H:i:s', $data['data']['expired_time']);

    // $message = "*🔔 Informasi Pembayaran*";
    // $message .= "\n\nHalo, *" . $name . "*";
    // $message .= "\nCustomer ID / CID: *" . $customer_id . "*";
    // $message .= "\n\nTerima kasih telah memilih metode pembayaran *" . $payment_name . "*.";
    // $message .= "\n\n💳 *Tagihan No:* #" . $merchant_ref;
    // $message .= "\n⏳ *Batas Pembayaran:* " . $expired_time . " WITA";
    // $message .= "\n\nSilakan selesaikan pembayaran sebelum batas waktu agar transaksi dapat diproses dengan lancar.";
    // $message .= "\n\nJika pembayaran melewati batas waktu tersebut, harap buka kembali halaman tagihan untuk mendapatkan kode pembayaran terbaru.";
    // $message .= "\n\n📌 *Panduan pembayaran & informasi lebih lanjut:*";
    // $message .= "\n👉 " . url("suminvoice/" . $tempcode . "/print");
    // $message .= "\n\nTerima kasih atas kepercayaan Anda! 😊";
    // $message .= "\n\nSalam,";
    // $message .= "\n*" . config("app.signature") . "*";

//Disable WA
    // NotifInvJob::dispatch($request->phone, $message)->delay($start->addSeconds(5));
    return redirect ('https://tripay.co.id/checkout/'.$reference);
}

curl_close($curl);




}



public function winpay()
{


   return view ('suminvoice/winpay');
}


// public function createWinpayVA(Request $request)
// {
//     try {

//         $privateKeyPem = storage_path('app/rsa_private_key.pem');
//         $privateKey = file_get_contents($privateKeyPem);
//         $key= 'HV0AB11MJBQB';
//         $secretkey= '0130ada7c736403e6c60087f8c8ca62b30d8dd83';


//         $endpointUrl = '/v1.0/transfer-va/create-va';
//         $baseUrl = 'https://sandbox-api.bmstaging.id/snap';
//         $httpMethod = 'POST';
//         $timestamp = now()->setTimezone('Asia/Jakarta')->format('c');
//         $expired = now()->addDay()->setTimezone('Asia/Jakarta')->format('c');


//         $partnerId = 'be2c392b-15d8-4ebc-a7b8-68f0a1d32988';
//         $channelId = 'BSI';
//         $externalId = '00002';


//         $payload = '
//         {
//             "customerNo": "08123456789",
//             "virtualAccountName": "CHUS PANDI",
//             "trxId": "INV-000000001",
//             "totalAmount": {
//                 "value": "10000.00",
//                 "currency": "IDR"
//                 },
//                 "virtualAccountTrxType": "c",
//                 "expiredDate": "'.$expired.'",
//                 "additionalInfo": {
//                     "channel": "BSI"
//                 }
//             }
//             ';


//             $body = json_decode($payload);
//             $hashedBody = strtolower(bin2hex(hash('sha256', json_encode($body, JSON_UNESCAPED_SLASHES), true)));


//             $stringToSign =  [
//                 $httpMethod,
//                 $endpointUrl,
//                 $hashedBody,
//                 $timestamp
//             ];


//             $signature = '';
//             $stringToSign = implode(':', $stringToSign);

//             $privKey = openssl_pkey_get_private($privateKey);

//             openssl_sign($stringToSign, $signature, $privKey, OPENSSL_ALGO_SHA256);
//             $encodedSignature = base64_encode($signature);





//             $headers = [
//                 'Content-Type'     => 'application/json',
//                 'X-TIMESTAMP'      => $timestamp,
//                 'X-SIGNATURE'      => $encodedSignature,
//                 'X-PARTNER-ID'     => $partnerId,
//                 'X-EXTERNAL-ID'    => $externalId,
//                 'CHANNEL-ID'       => $channelId,
//             ];


//             Log::debug('Winpay Signature Debug', [
//                 'stringToSign' => $stringToSign,
//                 'hashedBody' => $hashedBody,
//                 'signatureBase64' => $encodedSignature,
//                 'timestamp' => $timestamp,
//                 'jsonPayload' => $hashedBody,
//                 'headers' => $headers
//             ]);


//             $response = Http::withHeaders($headers)
//             ->withBody($payload, 'application/json')
//             ->post($baseUrl . $endpointUrl);


//             if ($response->failed()) {
//                 Log::error('Winpay response error', [
//                     'status' => $response->status(),
//                     'body' => $response->body(),
//                 ]);
//                 return response()->json([
//                     'error' => 'Gagal request ke Winpay',
//                     'status' => $response->status(),
//                     'response' => $response->json()
//                 ], 500);
//             }


//             return response()->json($response->json());

//         } catch (\Exception $e) {

//             Log::error('Exception saat membuat VA Winpay', [
//                 'message' => $e->getMessage(),
//                 'trace' => $e->getTraceAsString()
//             ]);
//             return response()->json([
//                 'error' => 'Terjadi exception',
//                 'message' => $e->getMessage()
//             ], 500);
//         }
//     }



    /**
     * ─────────────────────────────────────────────────────────────────────────
     * TEMPLATE PROVIDER BARU — Duitku
     * Cara pakai:
     *  1. Isi implementasi sesuai dokumentasi API Duitku
     *  2. Simpan merchant_code & api_key di kolom settings tabel payment_gateways
     *     atau di .env sebagai DUITKU_MERCHANT_CODE / DUITKU_API_KEY
     *  3. Set enabled = 1 di tabel payment_gateways via SQL atau admin panel
     * ─────────────────────────────────────────────────────────────────────────
     */
    public function createDuitkuVA(Request $request)
    {
        try {
            $provider = $request->input('provider', 'duitku');
            if (!in_array($provider, ['duitku', 'duitku2'], true)) {
                $provider = 'duitku';
            }

            $suminvoice = \App\Suminvoice::findOrFail($request->id);
            $customer   = \App\Customer::findOrFail($suminvoice->id_customer);

            // Baca konfigurasi dari settings kolom payment_gateways atau fallback ke .env
            $gw           = \App\PaymentGateway::findForCurrentTenant($provider);
            $duitkuConfig = $this->getDuitkuConfig($provider);
            $merchantCode = $duitkuConfig['merchant_code'];
            $apiKey       = $duitkuConfig['api_key'];
            $isSandbox    = $duitkuConfig['sandbox'];

            $baseAmount      = (float) $suminvoice->total_amount;
            $fee             = $gw ? $gw->calculateFee($baseAmount) : 0;
            $totalAmount     = (int) ($baseAmount + $fee);
            $merchantOrderId = $suminvoice->number;

            // POP API: Signature = SHA256(merchantCode + timestamp_ms + apiKey) — tanpa separator
            $timestamp = round(microtime(true) * 1000);
            $signature = hash('sha256', $merchantCode . $timestamp . $apiKey);

            $callbackUrl = url('/duitku/callback');
            $returnUrl   = url('suminvoice/' . $suminvoice->tempcode . '/print');

            // Format nomor HP ke internasional untuk Duitku
            $rawPhone = $customer->phone ?? '';
            $intlPhone = preg_replace('/^0/', '+62', preg_replace('/[^0-9]/', '', $rawPhone));

            $nameParts = explode(' ', trim($customer->name), 2);
            $firstName = $nameParts[0] ?? mb_substr($customer->name, 0, 50);
            $lastName  = $nameParts[1] ?? '';

            $params = [
                'paymentAmount'   => $totalAmount,
                'merchantOrderId' => $merchantOrderId,
                'productDetails'  => 'Invoice #' . $customer->customer_id.'|'.mb_substr($customer->name, 0, 20),
                'customerVaName'  => mb_substr($customer->name, 0, 20),
                'email'           => !empty($customer->email) ? $customer->email : 'billing@noreply.com',
                'phoneNumber'     => $intlPhone,
                'callbackUrl'     => $callbackUrl,
                'returnUrl'       => $returnUrl,
                'expiryPeriod'    => 1440,
                'customerDetail'  => [
                    'firstName'   => $firstName,
                    'lastName'    => $lastName,
                    'email'       => !empty($customer->email) ? $customer->email : 'billing@noreply.com',
                    'phoneNumber' => $intlPhone,
                    'billingAddress' => [
                        'firstName'   => $firstName,
                        'lastName'    => $lastName,
                        'address'     => mb_substr($customer->address ?? '', 0, 200),
                        'city'        => '',
                        'postalCode'  => '',
                        'phone'       => $intlPhone,
                        'countryCode' => 'ID',
                    ],
                ],
            ];

            $endpoint = $isSandbox
                ? 'https://api-sandbox.duitku.com/api/merchant/createInvoice'
                : 'https://api-prod.duitku.com/api/merchant/createInvoice';

            $paramsStr = json_encode($params);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $endpoint,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $paramsStr,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'x-duitku-signature: ' . $signature,
                    'x-duitku-timestamp: ' . $timestamp,
                    'x-duitku-merchantcode: ' . $merchantCode,
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \Exception('cURL error: ' . $curlError);
            }

            $result = json_decode($response, true);
            \Log::channel('payment')->debug('Duitku createInvoice response', [
                'provider'        => $provider,
                'merchantOrderId' => $merchantOrderId,
                'httpCode'        => $httpCode,
                'statusCode'      => $result['statusCode'] ?? null,
                'statusMessage'   => $result['statusMessage'] ?? null,
                'paymentUrl'      => $result['paymentUrl'] ?? null,
                'rawBody'         => substr($response, 0, 500),
            ]);

            if ($httpCode === 200 && isset($result['statusCode']) && $result['statusCode'] === '00' && !empty($result['paymentUrl'])) {
                $suminvoice->update([
                    'payment_id'      => 'duitku:' . $result['reference'] . '|' . $result['paymentUrl'],
                    'payment_gateway' => $provider,
                ]);
                return redirect($result['paymentUrl']);
            }

            $errMsg = $result['statusMessage'] ?? ($result['Message'] ?? ($result['message'] ?? 'Error HTTP ' . $httpCode . ': ' . substr($response, 0, 200)));
            return redirect()->back()->with('error', strtoupper($provider) . ': ' . $errMsg);

        } catch (\Exception $e) {
            \Log::error('Duitku VA Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat transaksi Duitku: ' . $e->getMessage());
        }
    }

    /**
     * Reset data pembayaran Duitku pada invoice agar pelanggan bisa
     * membuat link pembayaran baru (link Duitku kadaluarsa setelah 24 jam).
     * Duitku POP API tidak menyediakan endpoint cancel, cukup reset lokal.
     */
    public function resetDuitkuVA(Request $request)
    {
        try {
            $suminvoice = \App\Suminvoice::findOrFail($request->id);

            if ($suminvoice->payment_status == 1) {
                return redirect()->back()->with('error', 'Invoice sudah lunas, tidak bisa direset.');
            }

            $suminvoice->update([
                'payment_id'      => null,
                'payment_gateway' => null,
            ]);

            \Log::channel('payment')->info('Duitku reset: payment_id cleared for invoice ' . $suminvoice->number);

            return redirect()->back()->with('success', 'Link pembayaran Duitku berhasil direset. Silakan buat transaksi baru.');

        } catch (\Exception $e) {
            \Log::error('Duitku Reset Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal reset transaksi Duitku: ' . $e->getMessage());
        }
    }

    /**
     * Reset pembayaran pending untuk single invoice (Winpay, dll).
     * Hanya menghapus payment_id dan payment_gateway secara lokal.
     */
    public function resetPaymentPending(Request $request)
    {
        try {
            $suminvoice = \App\Suminvoice::findOrFail($request->id);

            if ($suminvoice->payment_status == 1) {
                return redirect()->back()->with('error', 'Invoice sudah lunas, tidak bisa direset.');
            }

            $suminvoice->update([
                'payment_id'      => null,
                'payment_gateway' => null,
            ]);

            \Log::channel('payment')->info('Payment pending reset: invoice ' . $suminvoice->number);

            return redirect()->back()->with('success', 'Metode pembayaran berhasil direset. Silakan pilih metode lain.');

        } catch (\Exception $e) {
            \Log::error('Payment Reset Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal reset: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan bundle payment yang masih pending.
     * Menghapus record di payment_bundles & payment_bundle_items.
     */
    public function cancelBundle(Request $request)
    {
        try {
            $bundleRef = $request->bundle_ref;
            $bundle    = \DB::table('payment_bundles')->where('bundle_ref', $bundleRef)->first();

            if (!$bundle) {
                return redirect()->back()->with('error', 'Bundle tidak ditemukan.');
            }

            if ((int)$bundle->status === 1) {
                return redirect()->back()->with('error', 'Bundle sudah lunas, tidak bisa dibatalkan.');
            }

            // Ambil invoice ids sebelum menghapus items
            $invoiceIds = \DB::table('payment_bundle_items')
                ->where('bundle_ref', $bundleRef)
                ->pluck('suminvoice_id');

            \DB::transaction(function () use ($bundleRef, $invoiceIds) {
                \DB::table('payment_bundle_items')->where('bundle_ref', $bundleRef)->delete();
                \DB::table('payment_bundles')->where('bundle_ref', $bundleRef)->delete();
                // Bersihkan payment_id/payment_gateway pada invoice agar tidak tampil pending
                if ($invoiceIds->count() > 0) {
                    \App\Suminvoice::whereIn('id', $invoiceIds)
                        ->where('payment_status', 0)
                        ->update(['payment_id' => null, 'payment_gateway' => null]);
                }
            });

            \Log::channel('payment')->info('Bundle cancelled: ' . $bundleRef);

            return redirect()->back()->with('success', 'Transaksi bundle berhasil dibatalkan. Silakan pilih metode pembayaran baru.');

        } catch (\Exception $e) {
            \Log::error('Bundle Cancel Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membatalkan bundle: ' . $e->getMessage());
        }
    }

    // ============================================================
    //  BUNDLE PAYMENT — bayar beberapa invoice sekaligus (1 fee)
    // ============================================================
    public function createBundlePayment(Request $request)
    {
        try {
            $invoiceIds  = $request->input('invoice_ids', []);
            $gateway     = $request->input('gateway');
            $returnPath  = $request->input('return_path');   // encrypted customer id
            $tripayMethod = $request->input('tripay_method'); // hanya untuk gateway = tripay

            if (empty($invoiceIds) || !is_array($invoiceIds)) {
                return redirect()->back()->with('error', 'Pilih minimal satu tagihan.');
            }

            // Ambil invoices yang belum bayar
            $invoices = \App\Suminvoice::whereIn('id', $invoiceIds)
                ->where('payment_status', 0)
                ->get();

            if ($invoices->count() !== count($invoiceIds)) {
                return redirect()->back()->with('error', 'Beberapa tagihan tidak valid atau sudah lunas.');
            }

            // Semua harus milik customer yang sama
            $customerIds = $invoices->pluck('id_customer')->unique();
            if ($customerIds->count() > 1) {
                return redirect()->back()->with('error', 'Tagihan harus milik pelanggan yang sama.');
            }

            // Wajib urut dari invoice terlama (berlaku untuk centang 1 atau banyak).
            $customerId = (int) $customerIds->first();
            $selectedIds = collect($invoiceIds)->map(fn ($id) => (int) $id)->unique()->values();

            $pendingBundleInvoiceIds = \DB::table('payment_bundle_items')
                ->join('payment_bundles', 'payment_bundle_items.bundle_ref', '=', 'payment_bundles.bundle_ref')
                ->join('suminvoices', 'suminvoices.id', '=', 'payment_bundle_items.suminvoice_id')
                ->where('payment_bundles.status', 0)
                ->where('suminvoices.id_customer', $customerId)
                ->pluck('payment_bundle_items.suminvoice_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $eligibleUnpaidIds = \App\Suminvoice::where('id_customer', $customerId)
                ->where('payment_status', 0)
                ->where(function ($q) {
                    $q->whereNull('payment_id')
                        ->orWhere('payment_id', '')
                        ->orWhere(function ($q2) {
                            $q2->where('payment_id', 'not like', 'duitku:%')
                                ->where('payment_id', 'not like', 'winpay:%');
                        });
                })
                ->when($pendingBundleInvoiceIds->count() > 0, function ($q) use ($pendingBundleInvoiceIds) {
                    $q->whereNotIn('id', $pendingBundleInvoiceIds->all());
                })
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();

            $requiredPrefixIds = $eligibleUnpaidIds->take($selectedIds->count())->sort()->values();
            $selectedSortedIds = $selectedIds->sort()->values();
            if ($selectedSortedIds->all() !== $requiredPrefixIds->all()) {
                $oldestRequiredId = $eligibleUnpaidIds->first();
                $oldestRequiredNo = $oldestRequiredId
                    ? (\App\Suminvoice::find($oldestRequiredId)->number ?? $oldestRequiredId)
                    : null;
                return redirect()->back()->with(
                    'error',
                    'Centang dan bayar harus berurutan dari invoice terlama' . ($oldestRequiredNo ? ': #' . $oldestRequiredNo : '.')
                );
            }

            $customer    = \App\Customer::findOrFail($customerIds->first());
            $totalAmount = (int) $invoices->sum('total_amount');

            // Validasi gateway
            $gw = \App\PaymentGateway::findForCurrentTenant($gateway);
            if (!$gw || !$gw->enabled) {
                return redirect()->back()->with('error', 'Gateway pembayaran tidak aktif.');
            }

            $fee         = (int) $gw->calculateFee($totalAmount);
            $chargeAmount = $totalAmount + $fee;

            // Bundle ref: MULTI-{customer_id}-{random}
            $bundleRef = 'MULTI-' . $customer->customer_id . '-' . substr(md5(uniqid('', true)), 0, 8);

            // Simpan bundle ke DB (atomic)
            \DB::transaction(function () use ($bundleRef, $gateway, $customer, $totalAmount, $invoices, $tripayMethod) {
                \DB::table('payment_bundles')->insert([
                    'bundle_ref'      => $bundleRef,
                    'gateway'         => $gateway,
                    'id_customer'     => $customer->id,
                    'total_amount'    => $totalAmount,
                    'paid_amount'     => 0,
                    'status'          => 0,
                    'tripay_method'   => $tripayMethod,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                foreach ($invoices as $inv) {
                    \DB::table('payment_bundle_items')->insert([
                        'bundle_ref'    => $bundleRef,
                        'suminvoice_id' => $inv->id,
                    ]);
                }
            });

            $returnUrl = !empty($returnPath)
                ? url('/invoice/cst/' . $returnPath)
                : url('/');

            \Log::channel('payment')->info('Bundle payment created', [
                'bundle_ref'   => $bundleRef,
                'gateway'      => $gateway,
                'total_amount' => $totalAmount,
                'fee'          => $fee,
                'invoices'     => $invoices->pluck('number')->toArray(),
                'customer'     => $customer->customer_id,
            ]);

            // ---- Routing per gateway ----

            // DUITKU — checkout page milih sendiri metodenya
            if (in_array($gateway, ['duitku', 'duitku2'], true)) {
                $duitkuConfig = $this->getDuitkuConfig($gateway);
                $merchantCode = $duitkuConfig['merchant_code'];
                $apiKey       = $duitkuConfig['api_key'];
                $isSandbox    = $duitkuConfig['sandbox'];

                $timestamp = round(microtime(true) * 1000);
                $signature = hash('sha256', $merchantCode . $timestamp . $apiKey);

                $rawPhone  = $customer->phone ?? '';
                $intlPhone = preg_replace('/^0/', '+62', preg_replace('/[^0-9]/', '', $rawPhone));
                $nameParts = explode(' ', trim($customer->name), 2);
                $firstName = $nameParts[0] ?? mb_substr($customer->name, 0, 50);
                $lastName  = $nameParts[1] ?? '';

                $invoiceNumbers = $invoices->pluck('number')->implode(', ');
                $params = [
                    'paymentAmount'   => $chargeAmount,
                    'merchantOrderId' => $bundleRef,
                    'productDetails'  => 'Tagihan #' . $customer->customer_id . ' | ' . $invoices->count() . ' invoice',
                    'customerVaName'  => mb_substr($customer->name, 0, 20),
                    'email'           => !empty($customer->email) ? $customer->email : 'billing@noreply.com',
                    'phoneNumber'     => $intlPhone,
                    'callbackUrl'     => url('/duitku/callback'),
                    'returnUrl'       => $returnUrl,
                    'expiryPeriod'    => 1440,
                    'customerDetail'  => [
                        'firstName'   => $firstName,
                        'lastName'    => $lastName,
                        'email'       => !empty($customer->email) ? $customer->email : 'billing@noreply.com',
                        'phoneNumber' => $intlPhone,
                        'billingAddress' => [
                            'firstName'   => $firstName,
                            'lastName'    => $lastName,
                            'address'     => mb_substr($customer->address ?? '', 0, 200),
                            'city'        => '',
                            'postalCode'  => '',
                            'phone'       => $intlPhone,
                            'countryCode' => 'ID',
                        ],
                    ],
                ];

                $endpoint = $isSandbox
                    ? 'https://api-sandbox.duitku.com/api/merchant/createInvoice'
                    : 'https://api-prod.duitku.com/api/merchant/createInvoice';

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => $endpoint,
                    CURLOPT_CUSTOMREQUEST  => 'POST',
                    CURLOPT_POSTFIELDS     => json_encode($params),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => [
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'x-duitku-signature: ' . $signature,
                        'x-duitku-timestamp: ' . $timestamp,
                        'x-duitku-merchantcode: ' . $merchantCode,
                    ],
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $response  = curl_exec($ch);
                $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) throw new \Exception('cURL error: ' . $curlError);

                $result = json_decode($response, true);
                \Log::channel('payment')->debug('Duitku bundle createInvoice', [
                    'provider'    => $gateway,
                    'bundle_ref'  => $bundleRef, 'httpCode' => $httpCode,
                    'statusCode'  => $result['statusCode'] ?? null, 'paymentUrl' => $result['paymentUrl'] ?? null,
                    'rawBody'     => substr($response, 0, 500),
                ]);

                if ($httpCode === 200 && ($result['statusCode'] ?? null) === '00' && !empty($result['paymentUrl'])) {
                    \DB::table('payment_bundles')->where('bundle_ref', $bundleRef)
                        ->update(['payment_url' => $result['paymentUrl'], 'updated_at' => now()]);
                    return redirect($result['paymentUrl']);
                }

                $errMsg = $result['statusMessage'] ?? ($result['Message'] ?? ($result['message'] ?? 'Error HTTP ' . $httpCode . ': ' . substr($response, 0, 200)));
                return redirect()->back()->with('error', strtoupper($gateway) . ': ' . $errMsg);
            }

            // WINPAY — langsung ke VA
            if (in_array($gateway, ['winpay', 'winpay2'], true)) {
                $winpayConfig = $this->getWinpayConfig($gateway);
                $key          = $winpayConfig['api_key'];
                $secretKey    = $winpayConfig['secret_key'];

                $timestamp = (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d\TH:i:sP');
                $signature = hash_hmac('sha256', $timestamp, $secretKey);

                $products = [['name' => 'Tagihan ' . $invoices->count() . ' invoice | ' . $customer->customer_id, 'qty' => 1, 'price' => $totalAmount]];
                if ($fee > 0) {
                    $products[] = ['name' => $gw->fee_label ?: 'Biaya Transaksi', 'qty' => 1, 'price' => $fee];
                }

                $data = [
                    'customer' => ['name' => $customer->customer_id . ' ' . $customer->name, 'email' => $customer->email, 'phone' => $customer->phone, 'interval' => 144000],
                    'invoice'  => ['ref' => $bundleRef, 'products' => $products],
                    'back_url' => $returnUrl,
                    'interval' => 43200,
                ];

                $headers = ['Content-Type: application/json', 'X-Winpay-Key: ' . $key, 'X-Winpay-Signature: ' . $signature, 'X-Winpay-Timestamp: ' . $timestamp];
                $url = rtrim($winpayConfig['endpoint'], '/') . '/api/create';
                $ch  = curl_init($url);
                curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($data), CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true]);
                $response = curl_exec($ch);
                $curlErr  = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($curlErr) throw new \Exception('Winpay cURL error: ' . $curlErr);

                $responseData = json_decode($response, true);
                \Log::channel('payment')->info('Winpay bundle response', ['bundle_ref' => $bundleRef, 'httpCode' => $httpCode, 'response' => $responseData, 'rawBody' => substr((string) $response, 0, 500)]);

                if (isset($responseData['responseCode']) && $responseData['responseCode'] === '2010300' && isset($responseData['responseData']['redirect_url'])) {
                    $winpayBundleUrl = $responseData['responseData']['redirect_url'];
                    \DB::table('payment_bundles')->where('bundle_ref', $bundleRef)
                        ->update(['payment_url' => $winpayBundleUrl, 'updated_at' => now()]);
                    return redirect()->away($winpayBundleUrl);
                }
                return redirect()->back()->with('error', 'Gagal membuat transaksi Winpay: ' . $this->getWinpayErrorMessage($responseData, (string) $response, $httpCode));
            }

            // TRIPAY — butuh method (channel) dari form
            if ($gateway === 'tripay') {
                if (empty($tripayMethod)) {
                    return redirect()->back()->with('error', 'Pilih metode pembayaran Tripay.');
                }

                $endpoint     = tenant_config('tripay_endpoint',    env('TRIPAY_ENDPOINT'));
                $apiKey       = tenant_config('tripay_apikey',       env('TRIPAY_APIKEY'));
                $privateKey   = tenant_config('tripay_privatekey',   env('TRIPAY_PRIVATEKEY'));
                $merchantCode = tenant_config('tripay_merchantcode', env('TRIPAY_MERCHANTCODE'));

                $hash = hash_hmac('sha256', $merchantCode . $bundleRef . $chargeAmount, $privateKey);

                $rawPhone = $customer->phone ?? '';
                if (empty($rawPhone)) $rawPhone = '0818000000';

                $data = [
                    'method'         => $tripayMethod,
                    'merchant_ref'   => $bundleRef,
                    'amount'         => $chargeAmount,
                    'customer_name'  => $customer->name . ' | ' . $customer->customer_id,
                    'customer_email' => !empty($customer->email) ? $customer->email : 'billing@alus.co.id',
                    'customer_phone' => $rawPhone,
                    'order_items'    => [[
                        'sku'      => 'BUNDLE',
                        'name'     => 'Tagihan ' . $invoices->count() . ' invoice | ' . $customer->customer_id,
                        'price'    => $chargeAmount,
                        'quantity' => 1,
                    ]],
                    'return_url'   => $returnUrl,
                    'expired_time' => (time() + (24 * 60 * 60)),
                    'signature'    => $hash,
                ];

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_FRESH_CONNECT  => true,
                    CURLOPT_URL            => $endpoint,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER         => false,
                    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
                    CURLOPT_FAILONERROR    => false,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => http_build_query($data),
                    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                ]);
                $response  = curl_exec($ch);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) throw new \Exception('Tripay cURL error: ' . $curlError);

                $result = json_decode($response, true);
                \Log::channel('payment')->info('Tripay bundle response', ['bundle_ref' => $bundleRef, 'response' => $result]);

                if (!empty($result['data']['reference'])) {
                    $tripayBundleUrl = $result['data']['payment_url'] ?? '';
                    if ($tripayBundleUrl) {
                        \DB::table('payment_bundles')->where('bundle_ref', $bundleRef)
                            ->update(['payment_url' => $tripayBundleUrl, 'updated_at' => now()]);
                    }
                    return redirect($tripayBundleUrl ?: $returnUrl);
                }
                return redirect()->back()->with('error', 'Tripay: ' . ($result['message'] ?? 'Gagal membuat transaksi.'));
            }

            return redirect()->back()->with('error', 'Gateway tidak dikenali: ' . $gateway);

        } catch (\Exception $e) {
            \Log::error('Bundle Payment Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

public function createWinpayVA(Request $request)
{ 

    try {
        $provider = $request->input('provider', 'winpay');
        if (!in_array($provider, ['winpay', 'winpay2'], true)) {
            $provider = 'winpay';
        }

        $winpayConfig = $this->getWinpayConfig($provider);
        $key = $winpayConfig['api_key'];
        $secretKey = $winpayConfig['secret_key'];

        $suminvoice = \App\Suminvoice::findOrFail($request->id);


        $customer = \App\Customer::findOrFail($suminvoice->id_customer);
        // Timestamp format ISO8601 (Asia/Jakarta)
        $timestamp = (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))
        ->format('Y-m-d\TH:i:sP');

        // Generate signature
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        // Data payload

        $encryptedurl = Crypt::encryptString($customer->id);
        // Prepare products array
        $products = [
            [
                "name" => 'Invoice ' . tenant_config('APP_NAME', config('app.name', 'ISP')) . ' No #'. $suminvoice->number,
                "qty" => 1,
                "price" => $suminvoice->total_amount
            ]
        ];

        // Biaya tambahan diambil dari tabel payment_gateways (per-tenant, fleksibel)
        $winpayGw  = \App\PaymentGateway::findForCurrentTenant($provider);
        $winpayFee = $winpayGw ? $winpayGw->calculateFee((float) $suminvoice->total_amount) : 0;
        if ($winpayFee > 0) {
            $products[] = [
                'name'  => $winpayGw->fee_label ?: 'Biaya Transaksi',
                'qty'   => 1,
                'price' => (int) $winpayFee,
            ];
        }

        $data = [
            "customer" => [
                "name" => $customer->customer_id."  ".$customer->name,
                "email" => $customer->email,
                "phone" => $customer->phone,
                'interval' => 144000,
            ],
            "invoice" => [
                "ref" => $suminvoice->number,
                "products" => $products
            ],
            "back_url" => env('APP_URL')."/invoice/cst/". $encryptedurl ,
            "interval" => 43200
        ];



        // Headers
        $headers = [
            'Content-Type: application/json',
            'X-Winpay-Key: ' . $key,
            'X-Winpay-Signature: ' . $signature,
            'X-Winpay-Timestamp: ' . $timestamp
        ];

        // Winpay API endpoint
        $url = rtrim($winpayConfig['endpoint'], '/').'/api/create';

        // cURL init
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            Log::error("Winpay cURL Error: " . $error);
            return response()->json(['success' => false, 'message' => 'Connection error: ' . $error], 500);
        }

        curl_close($ch);

        Log::info('Winpay Response', ['provider' => $provider, 'httpCode' => $httpCode, 'rawBody' => substr((string) $response, 0, 500)]);
        $responseData = json_decode($response, true);

// Pastikan parsing berhasil
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON decode error: ' . json_last_error_msg());
            return response()->json(['success' => false, 'message' => 'Invalid JSON']);
        }

// Cek jika invoice berhasil
        if (
            isset($responseData['responseCode']) &&
            $responseData['responseCode'] === '2010300' &&
            isset($responseData['responseData']['redirect_url'])
        ) {


            $reference   = $responseData['responseData']['ref'];
            $redirectUrl = $responseData['responseData']['redirect_url'];

            \App\Suminvoice::where('number', $suminvoice->number)
                ->update([
                    'payment_id'      => 'winpay:' . $reference . '|' . $redirectUrl,
                    'payment_gateway' => $provider,
                ]);

            return redirect()->away($redirectUrl);
        }

// Jika tidak ada URL atau kode gagal
        return response()->json(['success' => false, 'message' => 'Gagal membuat transaksi Winpay: ' . $this->getWinpayErrorMessage($responseData, (string) $response, $httpCode)]);


    } catch (\Exception $e) {
        Log::error('Winpay Exception: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
    }
}

public function deleteWinpayVA($merchantRef)
{
    try {
        $winpayConfig = $this->getWinpayConfig('winpay');
        $key = $winpayConfig['api_key'];
        $secretKey = $winpayConfig['secret_key'];

        // Format timestamp sesuai GMT+7 (Asia/Jakarta)
        $timestamp = (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))
        ->format('Y-m-d\TH:i:sP');

        // Generate signature
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        // Headers untuk permintaan DELETE
        $headers = [
            'Content-Type: application/json',
            'X-Winpay-Key: ' . $key,
            'X-Winpay-Signature: ' . $signature,
            'X-Winpay-Timestamp: ' . $timestamp,
        ];

        // Endpoint API untuk menghapus invoice berdasarkan merchantRef
        $url = rtrim($winpayConfig['endpoint'], '/').'/api/deleteByRef/' . $merchantRef;

        // Inisialisasi cURL untuk DELETE request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            Log::error("Winpay Delete Error: " . $error);
            return response()->json(['success' => false, 'message' => 'Connection error: ' . $error], 500);
        }

        curl_close($ch);

        Log::info('Winpay Delete Response: ' . $response);
        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON decode error (delete): ' . json_last_error_msg());
            return response()->json(['success' => false, 'message' => 'Invalid JSON response'], 500);
        }

        // Cek apakah berhasil dihapus
        if (isset($responseData['responseCode']) && $responseData['responseCode'] === '2000300') {
            return response()->json(['success' => true, 'message' => 'Invoice berhasil dihapus.']);
        }

        return response()->json([
            'success' => false,
            'message' => $responseData['responseMessage'] ?? 'Gagal menghapus invoice.',
        ]);

    } catch (\Exception $e) {
        Log::error('Exception saat delete invoice: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
    }
}


public function findWinpayVA($id)
{
    try {
        $winpayConfig = $this->getWinpayConfig('winpay');
        $key = $winpayConfig['api_key'];
        $secretKey = $winpayConfig['secret_key'];

        // Ambil data invoice dari database
        $suminvoice = \App\Suminvoice::findOrFail($id);

        // Format timestamp sesuai dengan GMT+7 (Asia/Jakarta)
        $timestamp = (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))
        ->format('Y-m-d\TH:i:sP');

        // Generate signature menggunakan HMAC-SHA256
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        // Siapkan header sesuai dengan dokumentasi Winpay
        $headers = [
            'Content-Type: application/json',
            'X-Winpay-Key: ' . $key,
            'X-Winpay-Signature: ' . $signature,
            'X-Winpay-Timestamp: ' . $timestamp,
        ];

        // Endpoint API untuk mencari invoice berdasarkan merchantRef
        $url = rtrim($winpayConfig['endpoint'], '/').'/api/findByRef/' . $suminvoice->number;

        // Inisialisasi cURL untuk melakukan GET request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            Log::error("Winpay cURL Error: " . $error);
            return response()->json(['success' => false, 'message' => 'Connection error: ' . $error], 500);
        }

        curl_close($ch);

        Log::info('Winpay Response: ' . $response);
        $responseData = json_decode($response, true);

        // Pastikan parsing JSON berhasil
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON decode error: ' . json_last_error_msg());
            return response()->json(['success' => false, 'message' => 'Invalid JSON response']);
        }

        // Cek jika invoice ditemukan
        if (
            isset($responseData['responseCode']) &&
            $responseData['responseCode'] === '2000300' &&
            isset($responseData['responseData']['redirect_url'])
        ) {
            return response()->json([
                'success' => true,
                'data' => $responseData['responseData'],
            ]);
        }

        // Jika invoice tidak ditemukan atau response code tidak sesuai
        return response()->json([
            'success' => false,
            'message' => $responseData['responseMessage'] ?? 'Gagal menemukan invoice',
        ]);

    } catch (\Exception $e) {
        Log::error('Winpay Exception: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
    }
}


public function customerblockednotifJob()
{
    \Log::channel('notif')->info('==== START Notification to customers with Blocked Status. ===');

    $id_merchant = request('id_merchant_block');

    $query = \App\Customer::where('customers.id_status', 4)
        ->where('customers.notification', '!=', 0);

    if (!empty($id_merchant)) {
        $query->where('customers.id_merchant', $id_merchant);
    }

    $customers = $query->get();

    \Log::channel('notif')->info('Total blocked customers to notify: ' . $customers->count());

    $start         = Carbon::now();
    $longPauseEvery = (int) tenant_config('NOTIF_LONG_PAUSE_EVERY', rand(18, 27));
    $index = 1;
    $count = 0;

    foreach ($customers as $customer) {
        $encryptedurl = '/invoice/cst/' . Crypt::encryptString($customer->id);
        $index++;
        $count++;
        $this->dispatchCustomerNotif($customer, $encryptedurl, $start, $index, $longPauseEvery);
    }

    // Simpan total blast ke cache untuk tracking progress
    \Illuminate\Support\Facades\Cache::put('queue_blast_total',   $count,         now()->addHours(24));
    \Illuminate\Support\Facades\Cache::put('queue_blast_started', now()->timestamp, now()->addHours(24));

    $msg = 'Processing Sent ' . $count . ' messages';

    return redirect('suminvoice/notification')->with('info', $msg);
}

public function isolirData(Request $request)
{
 // Mengambil parameter 'isolirdate' dari request
    $isolirdate = (int)$request->query('isolirdate');

    $customer_count = \App\Customer::leftJoin('suminvoices', 'suminvoices.id_customer', '=', 'customers.id')
    ->where('suminvoices.payment_status', '=', 0)
    ->where(function ($query) use ($isolirdate) {
        $query->where('customers.id_status', '=', 2)
        ->where('customers.isolir_date', '=', $isolirdate);
    })
    ->distinct('customers.id') // Menambahkan DISTINCT berdasarkan customers.id
    ->count('customers.id'); // Menghitung jumlah pelanggan dengan DISTINCT

    if ($customer_count >0 )
    {

            // Mengembalikan hasil dalam format JSON
        return response()->json([
            'message' => 'ready to be blocked on date :  '.$isolirdate ,
            'customercount' => $customer_count.' Customer'
        ]);

    }
    else
    {
        return response()->json([
            'message' => 'ready to be blocked on date :  '.$isolirdate ,
            'customercount' => 'No Customer' 
        ]);
    }
}



public function getSelectedcustomermerchant(Request $request)
{
    // Ambil parameter 'id_merchant' dan 'inv_date' dari request
    $id_merchant = $request->id_merchant;
    $inv_date = $request->inv_date; // Format: YYYY-MM-DD

    // Konversi $inv_date ke format bulantahun (MMYYYY)
    $periode = Carbon::parse($inv_date)->format('mY'); 

    // Query customers: Jika id_merchant NULL, ambil semua customers
    $query = \App\Customer::whereIn('id_status', [2, 4]);
    
    if (!empty($id_merchant)) {
        $query->where('id_merchant',$id_merchant);
    }

    $customers = $query->get(['id']); // Ambil hanya kolom id

    // Total pelanggan yang memenuhi syarat
    $customer_count_all = $customers->count();

    // Ambil daftar ID pelanggan
    $customer_ids = $customers->pluck('id')->toArray();

    // Jika tidak ada customer yang ditemukan, langsung return 0
    if (empty($customer_ids)) {
        return response()->json([
          'customercount' => "0 of $customer_count_all"
      ]);
    }

    // Hitung jumlah customer yang memiliki invoice dengan monthly_fee = 1 dan periode = 'MMYYYY'
    $customer_count = \App\Invoice::whereIn('id_customer', $customer_ids)
    ->where('monthly_fee', 1)
    ->where('periode', $periode)
    ->where('payment_status', '!=', 5)
    ->count();
    $month = Carbon::parse($inv_date)->translatedFormat('F Y');
    $result =$customer_count_all-$customer_count;
    return response()->json([
        'customercount' => " $result of $customer_count_all",
        'month' => "in $month"
    ]);
}

public function getSelectedblocknotif(Request $request)
{
    // Ambil parameter 'id_merchant' 
    $id_merchant = $request->id_merchant_block;

    // Query customers yang statusnya 4 (diblokir)
    $query = \App\Customer::where('id_status', 4);
    
    if (!empty($id_merchant)) {
        $query->where('id_merchant', $id_merchant);
    }

    $blocked_customer = $query->count(); // Hitung jumlah pelanggan yang diblokir

    // Query semua customers (tanpa filter status)
    $query_all = \App\Customer::query();
    
    if (!empty($id_merchant)) {
        $query_all->where('id_merchant', $id_merchant);
    }

    $customer_count_all = $query_all->count(); // Hitung total pelanggan

    return response()->json([
        'customercount' => "$blocked_customer of $customer_count_all <p><h4> Customers </h4></p>",
    ]);
}

public function getSelectedunpaidnotif(Request $request)
{
    // Ambil parameter 'id_merchant'
    $id_merchant = $request->id_merchant_unpaid;

    // Query semua customers (tanpa filter status)
    $query_all = \App\Customer::query();

    if (!empty($id_merchant)) {
        $query_all->where('id_merchant', $id_merchant);
    }

    $customer_count_all = $query_all->count(); // Hitung total pelanggan

    // Query pelanggan yang memiliki invoice dengan status unpaid (payment_status = 0)
    $custactiveinv = \App\Customer::select(
        'customers.id', 'customers.customer_id', 'customers.name',
        'customers.phone', 'customers.address', 'customers.billing_start',
        'customers.id_plan', 'customers.tax', 'customers.id_status',
        'suminvoices.payment_status', 'customers.deleted_at'
    )
    ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
    ->where("suminvoices.payment_status", 0)
    ->where(function ($query) {
        $query->where("customers.id_status", 2)
        ->orWhere("customers.id_status", 4);
    });

    if (!empty($id_merchant)) {
        $custactiveinv->where('customers.id_merchant', $id_merchant);
    }

    $custactiveinv_count = $custactiveinv->groupBy('customers.id')->get()->count();

    return response()->json([
        'customercount' => "$custactiveinv_count of $customer_count_all <p><h4> Customers </h4></p>",
    ]);
}





public function customerisolirJob(Request $request)
{

    $isolirdate = (int)$request->isolir_date;

       //dd($isolirdate);

   //   $customer = \App\Customer::select ('customers.id','customers.customer_id','customers.name', 'customers.phone','customers.id_status','customers.isolir_date', 'suminvoices.payment_status', 'customers.deleted_at')
   //   ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
   //   ->where("suminvoices.payment_status", "=", 0)

   //   ->where(function ($query) {
   //     $query ->where("customers.id_status", "=", 2)
   //     ->Where("customers.isolir_date", "=", $isolirdate);
   // })

   //   ->groupBy('customers.id')
   //   ->get();




 // Fetch customers based on conditions
    $customer = \App\Customer::select('customers.id', 'customers.customer_id', 'customers.name', 'customers.phone', 'customers.id_status', 'customers.isolir_date', 'suminvoices.payment_status', 'customers.deleted_at')
    ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
    ->where("suminvoices.payment_status", "=", 0)
    ->where(function ($query) use ($isolirdate) {
        $query->where("customers.id_status", "=", 2)
        ->where("customers.isolir_date", "=", $isolirdate);
    })
    ->groupBy('customers.id')
    ->get();



    $start = Carbon::now();

    $count =0;
    foreach($customer as $customer) {




     $count = $count +1;


     IsolirJob::dispatch($customer->id, $customer->id_status)->delay($start->addSeconds(30));
     \Log::channel('isolir')->info('Set Customer :'.$customer->customer_id. ' | ' .$customer->name." to Blocked | Isolir"); 



 }


 $msg = 'Processing Sent '. $count .' messages';

 return redirect ('suminvoice/notification')->with('info',$msg);


}


public function notifinvJob(Request $request)
{

$id_merchant = $request->id_merchant_unpaid; // Ambil ID Merchant dari request

\Log::channel('notif')->info('==== START Notification to customers who still have invoice. ==='); 

$customers = \App\Customer::select(
    'customers.id', 'customers.customer_id', 'customers.name', 
    'customers.phone', 'customers.address', 'customers.billing_start',
    'customers.id_plan', 'customers.tax', 'customers.id_status',
    'customers.notification', 'customers.email', 'customers.fcm_token',
    'suminvoices.payment_status', 'customers.deleted_at'
)
->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
->where("suminvoices.payment_status", 0)
->where(function ($query) {
    $query->where("customers.id_status", 2)
    ->orWhere("customers.id_status", 4);
});

// Tambahkan filter berdasarkan ID Merchant jika ada
if (!empty($id_merchant)) {
    $customers->where('customers.id_merchant', $id_merchant);
}
$customers->where('customers.notification', '!=', 0);
$customers = $customers->groupBy('customers.id')->get();

// Tambahkan log untuk melihat hasilnya
\Log::channel('notif')->info('Total customers found: ' . $customers->count());





$start = Carbon::now();

$count =0;

$longPauseEvery = (int) tenant_config('NOTIF_LONG_PAUSE_EVERY', rand(18, 27));
$index = 1;
foreach($customers as $customer) {


    $encryptedurl = '/invoice/cst/'.Crypt::encryptString($customer->id);

    // $message = "*🔔 Pengingat Tagihan!*";
    // $message .= "\n\nHalo, *" . $customer->name . "*,";
    // $message .= "\nKami ingin mengingatkan bahwa tagihan Anda sudah tersedia.";
    // $message .= "\n\n💡 Agar tetap menikmati layanan tanpa gangguan, mohon untuk menyelesaikan pembayaran tepat waktu.";
    // $message .= "\n\n🔗 Lihat detail tagihan Anda di sini:";
    // $message .= "\n👉 " . url("/invoice/cst/" . $encryptedurl);
    // $message .= "\n\nJika sudah melakukan pembayaran, abaikan pesan ini.";
    // $message .= "\n\nTerima kasih atas kepercayaan Anda. Semoga hari Anda menyenangkan! 😊";
    // $message .= "\n\nSalam,";
    // $message .= "\n*" . config("app.signature") . "*";

    $index++;
    $count++;

    $this->dispatchCustomerNotif($customer, $encryptedurl, $start, $index, $longPauseEvery);


}

 // Simpan total blast ke cache untuk tracking progress
 \Illuminate\Support\Facades\Cache::put('queue_blast_total',   $count,         now()->addHours(24));
 \Illuminate\Support\Facades\Cache::put('queue_blast_started', now()->timestamp, now()->addHours(24));

 //NotifInv::dispatch($phone, $message);
$msg = 'Processing Sent '. $count .' messages';

return redirect ('suminvoice/notification')->with('info',$msg);

     //return 'Processing Send'. $count .' message';
}


public function index()
{
    $date_from      = date('Y-m-01');
    $date_end       = date('Y-m-d');
    $payment_status = '';

    $suminvoice = \App\Suminvoice::orderBy('id', 'DESC')
        ->whereBetween('date', [$date_from, $date_end])
        ->get();

    return view('suminvoice/index', [
        'suminvoice'     => $suminvoice,
        'date_from'      => $date_from,
        'date_end'       => $date_end,
        'payment_status' => $payment_status,
    ]);
}
public function transaction()
{
        //
 $today = Carbon::today();

 $startOfWeek = Carbon::now()->startOfWeek();
 $endOfWeek = Carbon::now()->endOfWeek();
 $startOfMonth = Carbon::now()->startOfMonth();
 $endOfMonth = Carbon::now()->endOfMonth();
 $sixMonthsAgo = Carbon::today()->subMonths(6);
 $groupedTransactionsUser = \App\Suminvoice::whereBetween('payment_date', [$startOfMonth, $today->addDay()])
 ->groupBy('updated_by')
 ->get();


// 1) Laporan volume transaksi per hari (hanya yang dibayar)
 $dailyTransactions = \App\Suminvoice::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
 ->where('payment_status', 1)
 ->selectRaw('DATE(payment_date) as date, COUNT(*) as volume, SUM(recieve_payment) as total_paid')
 ->groupBy(DB::raw('DATE(payment_date)'))
 ->orderBy('date')
 ->get();

 $user= \App\User::pluck('name', 'id');
 $totalPaymentToday = \App\Suminvoice::whereDate('payment_date', Carbon::today())
 ->sum('recieve_payment');
 $totalTransactionThisWeek = \App\Suminvoice::whereBetween('payment_date', [$startOfWeek, $endOfWeek])
 ->sum('recieve_payment');
    // Menghitung total transaksi bulan ini
 $totalTransactionThisMonth = \App\Suminvoice::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
 ->sum('recieve_payment');
 $totalReceivable = \App\Suminvoice::where('payment_status', 0)
 ->sum('total_amount');
 $groupedTransactions = \App\Suminvoice::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
 ->select('updated_by', DB::raw('SUM(recieve_payment) as total_amount'))
 ->groupBy('updated_by')
 ->get();

 $suminvoice = \App\Suminvoice::orderBy('updated_at', 'DESC')
 ->whereNotNull('updated_by')
 ->whereBetween('payment_date',[(date('Y-m-d', strtotime("-1 week"))), (date('Y-m-d'))])
 ->get();
 $merchant = \App\Merchant::pluck('name', 'id');
 $parentAkuns = \App\Akun::whereNotNull('parent')->pluck('parent');

 $kasbank = \App\Akun::where('category', 'kas & bank')
 ->whereNotIn('akun_code', $parentAkuns)
 ->get();

 return view ('suminvoice/transaction',['dailyTransactions' => $dailyTransactions,'suminvoice' =>$suminvoice, 'user'=>$groupedTransactionsUser, 'totalPaymentToday'=>$totalPaymentToday, 'totalTransactionThisWeek'=>$totalTransactionThisWeek, 'totalTransactionThisMonth'=>$totalTransactionThisMonth, 'totalReceivable'=>$totalReceivable, 'groupedTransactions' => $groupedTransactions,'merchant'=>$merchant, 'kasbank'=>$kasbank]);
}
//======================================================================================

// public function table_transaction_list(Request $request){


//         // if (empty($request->filter))
//         // {
//     $dateStart = Carbon::createFromFormat('Y-m-d', $request->input('dateStart'))->setTime(0, 0); 
//     $dateEnd =  Carbon::createFromFormat('Y-m-d', $request->input('dateEnd'))->endOfDay();
//     $parameter = $request->input('parameter');
//     $updatedBy = $request->input('updatedBy');
//     $id_merchant = $request->input('id_merchant');
//     $kasbank = $request->input('kasbank');
//     $today = Carbon::today();
//     $sixMonthsAgo = Carbon::today()->subMonths(6);
//     $groupedTransactionsUser = \App\Suminvoice::whereBetween('payment_date', [$dateStart, $dateEnd])
//     ->where('payment_status', 1)
//     ->select(
//         'suminvoices.updated_by',
//         DB::raw('SUM(suminvoices.recieve_payment) as total_payment'),
//         DB::raw('SUM(suminvoices.total_amount) as total_amount')
//     )
//     ->join('customers', 'customers.id', '=', 'suminvoices.id_customer'); // Adjust foreign key

//     if (!empty($updatedBy)) {
//         $groupedTransactionsUser->where('suminvoices.updated_by', $updatedBy);
//     }

//     if (!empty($id_merchant)) {
//         $groupedTransactionsUser->where('customers.id_merchant', $id_merchant);
//     }
//     if (!empty($kasbank)) {
//         $groupedTransactionsUser->where('suminvoices.payment_point', $kasbank);
//     }

//     $groupedTransactionsUser = $groupedTransactionsUser
//     ->groupBy('suminvoices.updated_by')
//     ->with('user')
//     ->get();

//     $groupedTransactionsUser->transform(function ($transaction) {
//     // Mengecek apakah updated_by adalah bilangan numerik
//         if (is_numeric($transaction->updated_by)) {
//             $transaction->updated_by = $transaction->user ? $transaction->user->name : null;
//         } else {
//         $transaction->updated_by = $transaction->updated_by; // Biarkan seperti data awal
//     }
//     $payment_fee = $transaction->total_payment - $transaction->total_amount;

//     // Mengubah total_amount menjadi format currency
//     $transaction->total_amount = $transaction->total_amount; // Format: 1.234,56
//     $transaction->payment_fee = $payment_fee;
//     return $transaction;




// });

//     $groupedTransactionsMerchant = \App\Suminvoice::whereBetween('suminvoices.payment_date', [$dateStart, $dateEnd])
//     ->where('suminvoices.payment_status', 1)
//     ->join('customers', 'suminvoices.id_customer', '=', 'customers.id')
//     ->select(
//         'customers.id_merchant',
//         DB::raw('SUM(suminvoices.recieve_payment) as total_payment'),
//         DB::raw('SUM(suminvoices.total_amount) as total_amount')
//     );

//     if (!empty($updatedBy)) {
//         $groupedTransactionsMerchant->where('suminvoices.updated_by', $updatedBy);
//     }

//     if (!empty($id_merchant)) {
//         $groupedTransactionsMerchant->where('customers.id_merchant', $id_merchant);
//     }
//     if (!empty($kasbank)) {
//         $groupedTransactionsMerchant->where('suminvoices.payment_point', $kasbank);
//     }

//     $groupedTransactionsMerchant = $groupedTransactionsMerchant
//     ->groupBy('customers.id_merchant')
//     ->get();

//    // Assuming you have the Merchant model defined
//     $merchant = \App\Merchant::orderby('id','DESC')
//     ->get();

//     $kasbanks = \App\Akun::orderby('akun_code','DESC')
//     ->get();




//     $groupedTransactionsKasbank = \App\Suminvoice::whereBetween('suminvoices.payment_date', [$dateStart, $dateEnd])
//     ->where('suminvoices.payment_status', 1)
//     ->join('akuns', 'suminvoices.payment_point', '=', 'akuns.akun_code') // Join dengan tabel akuns
//     ->select(
//         'suminvoices.payment_point',
//         'akuns.name as akun_name',
//         DB::raw('COUNT(suminvoices.id) as total_transactions'), // Menghitung jumlah transaksi
//         DB::raw('SUM(suminvoices.recieve_payment) as total_payment'),
//         DB::raw('SUM(suminvoices.total_amount) as total_amount')
//     );

//     if (!empty($updatedBy)) {
//         $groupedTransactionsKasbank->where('suminvoices.updated_by', $updatedBy);
//     }

//     if (!empty($id_merchant)) {
//         $groupedTransactionsKasbank->join('customers', 'suminvoices.id_customer', '=', 'customers.id')
//         ->where('customers.id_merchant', $id_merchant);
//     }

//     if (!empty($kasbank)) {
//         $groupedTransactionsKasbank->where('suminvoices.payment_point', $kasbank);
//     }

//     $groupedTransactionsKasbank = $groupedTransactionsKasbank
//     ->groupBy('suminvoices.payment_point', 'akuns.name') // Kelompokkan berdasarkan payment_point
//     ->get();

// // Now $groupedTransactionsMerchant has merchant_name property

//   // return $groupedTransactionsMerchant;



// // Buat query dengan filter yang diperlukan
//     $suminvoice = \App\Suminvoice::orderBy('payment_date', 'DESC')
//     ->leftJoin('customers', 'suminvoices.id_customer', '=', 'customers.id')
//     ->select('suminvoices.*', 'customers.name','customers.customer_id','customers.id_merchant'); // Add any other fields you need from customers

//     if (!empty($dateStart) && !empty($dateEnd)) {
//         $suminvoice->whereBetween('payment_date', [$dateStart, $dateEnd]);
//     }

//     if (!empty($parameter)) {
//         $suminvoice->where(function($query) use ($parameter) {
//             $query->where('customers.name', 'like', "%$parameter%")
//             ->orWhere('customers.customer_id', 'like', "%$parameter%")
//             ->orWhere('suminvoices.number', 'like', "%$parameter%"); 
//         });
//     }

//     if (!empty($updatedBy)) {
//         $suminvoice->where('suminvoices.updated_by', $updatedBy);
//     }
//     if (!empty($id_merchant)) {
//         $suminvoice->where('customers.id_merchant', $id_merchant);
//     }
//     if (!empty($kasbank)) {
//         $suminvoice->where('suminvoices.payment_point', $kasbank);
//     }

//     $suminvoice->where('suminvoices.payment_status', 1);
//     // $suminvoice->orderby('updated_at', 'DESC');

//     $results = $suminvoice->get();
//     $sql = $suminvoice->toSql();


//     $suminvoiceData = $suminvoice->get();

//     $total = $suminvoiceData->sum('total_amount');
//     $recieve_payment = $suminvoiceData->sum('recieve_payment');
//     $fee_counter = $recieve_payment-$total;


//     return DataTables::of($suminvoice)
//     ->addIndexColumn()
//     ->editColumn('number',function($suminvoice)
//     {

//         return ' <a href="/suminvoice/'.$suminvoice->tempcode.'" title="INV Number" class="badge badge-primary text-center  "> '.$suminvoice->number. '</a>';
//     })

//     ->addColumn('cid',function($suminvoice)
//     {

//        $status = $suminvoice->customer->id_status;
//        if ( $status == 2)
//           $badge_sts = "badge-success";
//       elseif ( $status == 3)
//           $badge_sts = "badge-secondary";
//       elseif ( $status== 4)
//           $badge_sts = "badge-danger";
//       elseif ( $status== 5)
//           $badge_sts = "badge-primary";
//       else
//           $badge_sts = "badge-warning";




//       return '<a class="badge '.$badge_sts .'" href="/customer/'.$suminvoice->customer->id.'">'.$suminvoice->customer->customer_id .' </a>';

//   })
//     ->addColumn('period', function ($suminvoice) {
//         $invoices = \App\Invoice::where('tempcode', $suminvoice->tempcode)->get();

//     $periods = []; // Array untuk menampung semua periode

//     foreach ($invoices as $invoice) {
//         if ($invoice->monthly_fee == 1) {
//             $type = "M";
//         } else {
//             $type = "G";
//         }

//         $periods[] = '<a>' . $type . " " . $invoice->periode . '</a>';
//     }

//     return implode("<br>", $periods); // Menggabungkan hasil dengan koma
// })

//     ->addColumn('name',function($suminvoice)
//     {

//         return '<a>'.$suminvoice->customer->name. '</a>';
//     })

//     ->addColumn('merchant', function($suminvoice) {
//     // Check if customer relationship exists
//         if ($suminvoice->customer) {
//         // Check if merchant_name relationship exists
//             if ($suminvoice->customer->merchant_name) {
//                 return $suminvoice->customer->merchant_name->name ;
//             } else {
//             return 'No Merchant'; // Or any default value you want
//         }
//     } else {
//         return 'No Customer'; // Or any default value you want
//     }
// })
//     ->addColumn('address',function($suminvoice)
//     {

//         return '<a>'.$suminvoice->customer->address. '</a>';
//     })
//     ->editColumn('total_amount',function($suminvoice)
//     {

//         return '<a>'.number_format($suminvoice->total_amount, 2, '.', ','). '</a>';
//     })
//     ->addColumn('payment_fee',function($suminvoice)
//     {
//         $payment_fee = $suminvoice->recieve_payment -$suminvoice->total_amount;
//         return '<a>'.number_format($payment_fee, 2, '.', ','). '</a>';
//     })
//     ->addColumn('status',function($suminvoice)
//     {
//         if ($suminvoice->payment_status == 1)
//           { $badge_sts = "badge-success";
//       $status = "PAID";}
//       elseif ($suminvoice->payment_status == 2 )
//           { $badge_sts = "badge-secondary";
//       $status = "CANCEL";}
//       elseif ($suminvoice->payment_status == 0)
//           {$badge_sts = "badge-danger";
//       $status = "UNPAID";}
//       else
//           {$badge_sts = "badge-warning";
//       $status = "UNKNOW";}
//       return '<a class="badge '.$badge_sts .'">'.$status.' </a>';
//   })
//     ->addColumn('updated_by', function($suminvoice) {
//         if (is_numeric($suminvoice->updated_by)) {
//             return '<a>' . $suminvoice->user->name . '</a>';
//         } else {
//             return '<a>' . $suminvoice->updated_by . '</a>';
//         }
//     })
//     ->addColumn('kasbank', function($suminvoice) {

//       return '<a>'.($suminvoice->kasbank ? $suminvoice->kasbank->name : '-').'</a>';
//   })

//     ->addColumn('payment_date', function($suminvoice) {

//         return '<a>'.$suminvoice->payment_date.'</a>';
//     })



//    // ->rawColumns(['DT_RowIndex','date','number'])

//     ->rawColumns(['DT_RowIndex','date','number','cid','name','address','note','total_amount','payment_fee','status','updated_by','kasbank','payment_date','merchant','period' ])
//     ->with('total', $total)
//     ->with('fee_counter', $fee_counter)
//     ->with('groupedTransactionsUser', $groupedTransactionsUser)
//     ->with('groupedTransactionsMerchant', $groupedTransactionsMerchant)
//     ->with('groupedTransactionsKasbank', $groupedTransactionsKasbank)
//     ->with('merchants', $merchant)
//     ->with('kasbanks', $kasbanks)
//     ->make(true);
// }

public function table_transaction_list(Request $request)
{
    $dateStart = Carbon::createFromFormat('Y-m-d', $request->dateStart)->startOfDay();
    $dateEnd   = Carbon::createFromFormat('Y-m-d', $request->dateEnd)->endOfDay();

    $parameter    = $request->parameter;
    $updatedBy   = $request->updatedBy;
    $id_merchant = $request->id_merchant;
    $kasbank     = $request->kasbank;

    /** ================= BASE QUERY ================= **/
    $baseQuery = \App\Suminvoice::query()
    ->whereBetween('suminvoices.payment_date', [$dateStart, $dateEnd])
    ->where('suminvoices.payment_status', 1)
    ->leftJoin('customers', 'suminvoices.id_customer', '=', 'customers.id')
    ->select(
        'suminvoices.*',
        'customers.name',
        'customers.customer_id',
        'customers.id_merchant'
    )
    ->with([
        'customer.merchant_name',
        'user',
        'kasbank'
    ]);

    if ($updatedBy) {
        $baseQuery->where('suminvoices.updated_by', $updatedBy);
    }

    if ($id_merchant) {
        $baseQuery->where('customers.id_merchant', $id_merchant);
    }

    if ($kasbank) {
        $baseQuery->where('suminvoices.payment_point', $kasbank);
    }

    if ($parameter) {
        $baseQuery->where(function ($q) use ($parameter) {
            $q->where('customers.name', 'like', "%$parameter%")
            ->orWhere('customers.customer_id', 'like', "%$parameter%")
            ->orWhere('suminvoices.number', 'like', "%$parameter%");
        });
    }

    /** ================= HITUNG TOTAL ================= **/
    $summary = (clone $baseQuery)
    ->selectRaw('SUM(suminvoices.total_amount) as total, SUM(suminvoices.recieve_payment) as receive')
    ->first();

    $total = $summary->total ?? 0;
    $fee_counter = ($summary->receive ?? 0) - $total;


    /** ================= GROUP USER ================= **/
    $groupedTransactionsUser = (clone $baseQuery)
    ->select(
        'suminvoices.updated_by',
        DB::raw('SUM(suminvoices.recieve_payment) as total_payment'),
        DB::raw('SUM(suminvoices.total_amount) as total_amount')
    )
    ->groupBy('suminvoices.updated_by')
    ->get()
    ->transform(function ($t) {
        if (is_numeric($t->updated_by)) {
            $t->updated_by = optional($t->user)->name;
        }
        $t->payment_fee = $t->total_payment - $t->total_amount;
        return $t;
    });


    /** ================= GROUP MERCHANT ================= **/
    $groupedTransactionsMerchant = (clone $baseQuery)
    ->select(
        'customers.id_merchant',
        DB::raw('SUM(suminvoices.recieve_payment) as total_payment'),
        DB::raw('SUM(suminvoices.total_amount) as total_amount')
    )
    ->groupBy('customers.id_merchant')
    ->get();


    /** ================= GROUP KASBANK ================= **/
    $groupedTransactionsKasbank = (clone $baseQuery)
    ->leftJoin('akuns', 'suminvoices.payment_point', '=', 'akuns.akun_code')
    ->select(
        'suminvoices.payment_point',
        'akuns.name as akun_name',
        DB::raw('COUNT(suminvoices.id) as total_transactions'),
        DB::raw('SUM(suminvoices.recieve_payment) as total_payment'),
        DB::raw('SUM(suminvoices.total_amount) as total_amount')
    )
    ->groupBy('suminvoices.payment_point', 'akuns.name')
    ->get();


    /** ================= LOAD INVOICE PERIOD (1x QUERY) ================= **/
    $invoicePeriods = \App\Invoice::select('tempcode','periode','monthly_fee')
    ->get()
    ->groupBy('tempcode');


    /** ================= MERCHANT & KASBANK LIST ================= **/
    $merchant = \App\Merchant::orderBy('id','DESC')->get();
    $kasbanks = \App\Akun::orderBy('akun_code','DESC')->get();


    /** ================= DATATABLE ================= **/
    return DataTables::of($baseQuery)
    ->addIndexColumn()

    ->editColumn('number', function($s) {
        return '<a href="/suminvoice/'.$s->tempcode.'" class="badge badge-primary">'.$s->number.'</a>';
    })

    ->addColumn('cid', function($s) {
        $status = $s->customer->id_status ?? 0;
        $map = [2=>'success',3=>'secondary',4=>'danger',5=>'primary'];
        $badge = $map[$status] ?? 'warning';

        return '<a class="badge '.$badge.'" href="/customer/'.$s->customer->id.'">'.$s->customer->customer_id.'</a>';
    })

    ->addColumn('period', function($s) use ($invoicePeriods) {
        if (!isset($invoicePeriods[$s->tempcode])) return '-';

        $out = [];
        foreach ($invoicePeriods[$s->tempcode] as $i) {
            $type = $i->monthly_fee ? 'M' : 'G';
            $out[] = $type.' '.$i->periode;
        }
        return implode('<br>', $out);
    })

    ->addColumn('name', fn($s)=> $s->customer->name ?? '-')

    ->addColumn('merchant', fn($s)=> optional($s->customer->merchant_name)->name ?? '-')

    ->addColumn('address', fn($s)=> $s->customer->address ?? '-')

    ->editColumn('total_amount', fn($s)=> number_format($s->total_amount, 2, '.', ','))

    ->addColumn('payment_fee', fn($s)=> number_format($s->recieve_payment - $s->total_amount, 2, '.', ','))

    ->addColumn('status', function($s){
        if ($s->payment_status == 1){ $badge="success"; $text="PAID"; }
        elseif ($s->payment_status == 2){ $badge="secondary"; $text="CANCEL"; }
        elseif ($s->payment_status == 0){ $badge="danger"; $text="UNPAID"; }
        else { $badge="warning"; $text="UNKNOWN"; }

        return '<span class="badge badge-'.$badge.'">'.$text.'</span>';
    })

    ->addColumn('updated_by', function($s){
        return is_numeric($s->updated_by)
        ? (optional($s->user)->name ?? '-')
        : $s->updated_by;
    })

    ->addColumn('kasbank', fn($s)=> optional($s->kasbank)->name ?? '-')

    ->addColumn('payment_date', fn($s)=> $s->payment_date)

    ->rawColumns(['number','cid','period','status'])

    ->with('total', $total)
    ->with('fee_counter', $fee_counter)
    ->with('groupedTransactionsUser', $groupedTransactionsUser)
    ->with('groupedTransactionsMerchant', $groupedTransactionsMerchant)
    ->with('groupedTransactionsKasbank', $groupedTransactionsKasbank)
    ->with('merchants', $merchant)
    ->with('kasbanks', $kasbanks)

    ->make(true);
}


public function mytransaction()
{
        //
    $suminvoice = \App\Suminvoice::orderBy('updated_at', 'ASC')
    ->where('updated_by', \Auth::user()->id)
    ->whereBetween('payment_date', [
        date('Y-m-01 00:01:00'), 
        date('Y-m-d 23:59:59')
    ])
    ->get();


    return view ('suminvoice/mytransaction',['suminvoice' =>$suminvoice]);
}
public function searchmytransaction(Request $request)
{
    $date_from = $request['date_from'] . ' 00:01:00';
    $date_end  = $request['date_end']  . ' 23:59:59';

    $suminvoice = \App\Suminvoice::orderBy('payment_date', 'ASC')
    ->where('updated_by','=',  \Auth::user()->id)
    ->whereBetween('payment_date', [$date_from, $date_end])
    ->get();

    // Kirim nilai date_from dan date_end asli (tanpa jam) ke view jika perlu filter form
    return view('suminvoice/mytransaction', [
        'suminvoice' => $suminvoice,
        'date_from'  => $request['date_from'],
        'date_end'   => $request['date_end']
    ]);
}
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    public function xendit(Request $request)
    {

      if($request->header("X-CALLBACK-TOKEN") == "myoOCdWvUWsXWfmffsOy0DpfepvwNg6K1Bxw02uXKK4UuRYX"){
        return ($request);
    }
    return response()->json($request);
}
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

     $request->validate([
        'id_customer' => 'required|exists:customers,id',
        'invoice_date' => 'required|date',
        'due_date' => 'required|date|after_or_equal:invoice_date',
        'subtotal' => 'required|numeric|min:0',

        'invoice_item' => 'required|array|min:1',
    ]);

     $msg="";  
        //$array="";  
     $tempcode=sha1(time().rand());
     $id = $request->invoice_item;
     $latest_number=uniqid();
     $code = substr(md5(uniqid('', true)), 0, 10);
     $customers = \App\Customer::Where('id',$request['id_customer'])->withTrashed()->first();

     $email = !empty($customers->email) ? $customers->email : "return@alus.co.id";

   // try
   // { 
     $tax = $customers->tax ?? 0;

     $date=date("Y-m-d");

     if (session()->has('invoice_locked')) {
        return redirect()->back()->with('info', 'Sedang memproses invoice. Mohon tunggu...');
    }

    session(['invoice_locked' => true]);
    DB::beginTransaction();

    try{
            // Step 1 : Set current invoice item to parent Suminvoice
        \App\Invoice::whereIn('id', $id)->update([
            'payment_status' => 3,
            'tempcode' => $tempcode,
        ]);


            //Step 2 : Create Suminvoice

        \App\Suminvoice::create([
            'id_customer' => ($request['id_customer']),
            'number' => $latest_number,
            'date' => ($request['invoice_date']), 
            'payment_status' => 0,
            'tax' => ($request['tax']),
            'total_amount' =>($request['subtotal']+ $request['tax_total']),
            'payment_id' => 'empty',
            'tempcode' => $tempcode,
            'due_date' => ($request['due_date']), 


        ]);

 //Step 3: Sent Message



        $data = [
            'tax_total' => $request['tax_total'],
            'date' => $date,
            'reff' => $tempcode,
            'type' => 'jumum',
            'description' => 'Invoice #'.$latest_number.' | '.$customers->customer_id .' | '.$customers->name,
            'note' => 'Invoice #'.$latest_number.' | '.$customers->customer_id .' | '.$customers->name,
            'contact_id' => $customers->id,
            'code' => $code,
        ];

// Create debit entry
        $data['id_akun'] = '1-10100';
        $data['debet'] = $request['subtotal']+ $request['tax_total'];
        \App\Jurnal::create($data);
unset($data['debet']); // Remove debet key for the credit entry

// Create credit entry
$data['id_akun'] = '4-40000';
$data['kredit'] = $request['subtotal'];
\App\Jurnal::create($data);

if (!empty($request['tax']) && $request['tax'] != 0) {
    $data['id_akun'] = '2-20500';
    $data['kredit'] = $request['tax_total'];
    \App\Jurnal::create($data);
}




$sumamount =$request['subtotal'] + $request['tax_total'];

$encryptedurl = Crypt::encryptString($customers->id);
if($customers->notification == 1)
{
    // $response = qontak_whatsapp_helper_info_new_inv(
    //     $customers->phone,
    //     $customers->name,
    //     $customers->customer_id,
    //     $sumamount,
    //     $request->due_date,
    //     "/invoice/cst/" . $encryptedurl
    // );


 $message = "*[Informasi Pembayaran Internet]*";
 $message .= "\n\n";
 $message .= "Yth. " . $customers->name . ",";
 $message .= "\n\n";
 $message .= "Tagihan Anda dengan Customer ID (CID) *" . $customers->customer_id . "* telah diterbitkan.";
 $message .= "\n*Total Tagihan:* Rp." . number_format($sumamount, 0, ',', '.') . "";
 $message .= "\n*Batas Pembayaran:* " . $request->due_date;
 $message .= "\n\n";
 $message .= "Untuk informasi lebih lanjut, silakan klik link berikut:";
 $message .= "\n" . "http://" . tenant_config('domain_name', env("DOMAIN_NAME")) . "/invoice/cst/" . $encryptedurl;
 $message .= "\n\n";
 $message .= "Jika sudah melakukan pembayaran, abaikan pesan ini.";
 $message .= "\nJika ada pertanyaan, hubungi CS kami di ".tenant_config('payment_wa', env("PAYMENT_WA"));
 $message .= "\n\n";
 $message .= "".config("app.signature")."";
 $msgresult = WaGatewayHelper::wa_payment($customers->phone, $message);

} elseif ($customers->notification == 2) {


   if (!empty($customers->email)) {
    $data = [
        'phone' => $customers->phone,
        'name' => $customers->name,
        'customer_id' => $customers->customer_id,
        'number' => "#" . $latest_number,
        'total_amount' => $sumamount,
        'date' => $date,
        'due_date' => $request->due_date,
        'url' => "/invoice/cst/" . $encryptedurl,
    ];

    try {
        Mail::to($customers->email)->send(new EmailNotification($data));
    } catch (\Exception $e) {
        \Log::error("Gagal kirim email ke {$customers->email}: " . $e->getMessage());
    }
}


}






DB::commit();
$msg = "Success create invoice.";
//Disable WA
$msgresult="";
// $msgresult= \App\Suminvoice::wa_payment($customers->phone,$message);
// $msg .="\n Whatsapp : ".$response;
session()->forget('invoice_locked');
return redirect ('invoice/'.$request->id_customer)->with('info',$msg);

}catch(\Exception $e){

    DB::rollback();
    session()->forget('invoice_locked');
    return redirect ('invoice/'.$request->id_customer)->with('info','Failed to Create Invoice'.$e);
}





}

public function testwa()
{

    $response = qontak_whatsapp_helper_info_new_inv(
        '6281805360534',
        'duwija',
        '225655541',
        '200000',

        '22-22-22',
        '/invoice/cst/eyJpdiI6Ik9SanRpRUtsMGR1MngzRVlkZlBZdHc9PSIsInZhbHVlIjoiWVJwdlI0elZOQXpQejlJUUVkY2laQT09IiwibWFjIjoiM2M2ZDUzZjcwMGRkYjFhNzRjNDQ2YzM2ZGE5Mjc0ZGE4Nzg2ZDk3M2M4YWMxZGRkZGU3Yzc3ODI4Y2MzMTdjNyIsInRhZyI6IiJ9'
    );
    return $response;
}

public function search(Request $request)
{
 $date_from = ($request['date_from']);
 $date_end = ($request['date_end']);

 $suminvoice = \App\Suminvoice::orderBy('recieve_payment', 'asc')
 ->where('updated_by','=',  \Auth::user()->id)
 ->whereBetween('date',[($request['date_from']), ($request['date_end'])])
 ->get();


 return view ('suminvoice/mytransaction',['suminvoice' =>$suminvoice, 'date_from' =>$date_from, 'date_end'  =>$date_end]);



}

public function queueCount()
{
    $tenantQueue = app('tenant')['domain'] ?? 'default';
    $remaining = \DB::connection('mysql_queue')->table('jobs')->where('queue', $tenantQueue)->count();
    $total     = (int) \Illuminate\Support\Facades\Cache::get('queue_blast_total', 0);
    $startedAt = (int) \Illuminate\Support\Facades\Cache::get('queue_blast_started', 0);

    $processed = ($total > 0 && $remaining <= $total) ? ($total - $remaining) : 0;
    $percent   = ($total > 0) ? round($processed / $total * 100) : ($remaining > 0 ? 0 : 100);

    // Estimasi waktu selesai
    $eta = null;
    if ($remaining > 0 && $startedAt > 0 && $processed > 0) {
        $elapsed = time() - $startedAt;
        $rate    = $processed / max($elapsed, 1); // jobs per second
        $seconds = (int) round($remaining / $rate);
        if ($seconds < 60) {
            $eta = $seconds . ' detik';
        } elseif ($seconds < 3600) {
            $eta = round($seconds / 60) . ' menit';
        } else {
            $eta = round($seconds / 3600, 1) . ' jam';
        }
    }

    return response()->json([
        'count'     => $remaining,
        'total'     => $total,
        'processed' => $processed,
        'percent'   => $percent,
        'eta'       => $eta,
    ]);
}

public function cancelJobs()
{
    $tenantQueue = app('tenant')['domain'] ?? 'default';
    $count = \DB::connection('mysql_queue')->table('jobs')->where('queue', $tenantQueue)->count();
    \DB::connection('mysql_queue')->table('jobs')->where('queue', $tenantQueue)->delete();
    \Illuminate\Support\Facades\Cache::forget('queue_blast_total');
    \Illuminate\Support\Facades\Cache::forget('queue_blast_started');
    return response()->json(['success' => true, 'deleted' => $count, 'message' => "$count job(s) berhasil dihapus dari antrian."]);
}

public function notification()
{


    $custactiveinv = \App\Customer::select ('customers.id','customers.customer_id','customers.name', 'customers.phone','customers.address','customers.billing_start','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
    ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
    ->where("suminvoices.payment_status", "=", 0)

    ->where(function ($query) {
     $query ->where("customers.id_status", "=", 2)
     ->orWhere("customers.id_status", "=", 4);
 })

    ->groupBy('customers.id')
    ->get()
    ->count('customers.id');

    $custblocked = \App\Customer::where("customers.id_status", "=", 4)

    ->get()
    ->count();

    $customerinv = \App\Customer::where('customers.id_status', '2')
    ->orWhere('customers.id_status', '4')
    ->count() ;

    // Jobs difilter berdasarkan queue nama tenant — setiap tenant hanya lihat job miliknya
    $tenantQueue = app('tenant')['domain'] ?? 'default';
    $queue = \DB::connection('mysql_queue')->table('jobs')->where('queue', $tenantQueue)->count();


    $merchant = \App\Merchant::pluck('name', 'id');
    
    $custisolirdate = \App\Customer::select ('customers.id','customers.customer_id','customers.name', 'customers.phone','customers.address','customers.billing_start','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
    ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
    ->where("suminvoices.payment_status", "=", 0)

    ->where(function ($query) {
     $query ->where("customers.id_status", "=", 2)
     ->Where("customers.isolir_date", "=", date('d'));
 })

    ->groupBy('customers.id')
    ->get()
    ->count('customers.id');

    
    return view ('suminvoice/notification',['custactiveinv' =>$custactiveinv,'custblocked' =>$custblocked, 'custisolirdate' =>$custisolirdate, 'customerinv' =>$customerinv, 'queue' =>$queue, 'merchant' => $merchant ]);



}

public function searchinv(Request $request)
{
    $date_from = ($request['date_from']);
    $date_end = ($request['date_end']);
    $payment_status = ($request['payment_status']);
        // dd($payment_status);
    if ($payment_status=="")
    {
          //  dd($payment_status);
        $suminvoice = \App\Suminvoice::orderBy('id', 'DESC')
        ->whereBetween('date',[($request['date_from']), ($request['date_end'])])

        ->get(); 
    }
    else
    {
        $suminvoice = \App\Suminvoice::orderBy('id', 'DESC')
        ->whereBetween('date',[($request['date_from']), ($request['date_end'])])
        ->where('payment_status','=',  $payment_status)
        ->get(); 

    }



    return view ('suminvoice/index',['suminvoice'=>$suminvoice, 'date_from'=>$date_from, 'date_end'=>$date_end,'payment_status' =>$payment_status]);
}




    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        //dd($id);
        $current_inv_status =0;
        $user = \App\User::with('akuns')->find(\Auth::user()->id);
$bank = $user->akuns; // Mengembalikan koleksi akunbn

    // $bank = \App\Akun::where('category', 'kasbank')->get();
$mount = now()->format('mY');
$invoice = \App\Invoice::where('tempcode', $id)

// ->where('payment_status', '=', 3)
// ->orWhere('payment_status', 5)
->whereIn('payment_status', [3, 5])
->get();

if (empty($invoice[0])){

   return abort(404);
}
else
{
    $invoice_code = \App\Invoice::where('tempcode', $id)->first();
    $suminvoice_number = \App\Suminvoice::where('tempcode', $id)->first();
    $customer = \App\Customer::where('customers.id', $invoice_code->id_customer)

    ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
    ->Join('plans', 'customers.id_plan', '=', 'plans.id')
    ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')
    ->withTrashed()
    ->first();


    $active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
    ->where ('id_customer', '=', $invoice_code->id_customer )
    ->count();
    if ($active_invoice > 1)
    {

        $last_active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
        ->where ('id_customer', '=', $invoice_code->id_customer )
        ->orderBy('date', 'asc')->first();
        if ($id==$last_active_invoice->tempcode)
        {
            $current_inv_status =0;


//result jika ada inv sebelumnya

        }
        else
        {
            $current_inv_status =1;



        }
    }




    return view ('suminvoice/show',['invoice' =>$invoice, 'customer'=>$customer, 'bank'=>$bank, 'suminvoice_number' => $suminvoice_number, 'current_inv_status'=>$current_inv_status]);
}

}




public function countershow($id)
{
        //
        //dd($id);
    $current_inv_status =0;
    $user = \App\User::with('akuns')->find(\Auth::user()->id);
$bank = $user->akuns; // Mengembalikan koleksi akunbn

    // $bank = \App\Akun::where('category', 'kasbank')->get();
$mount = now()->format('mY');
$invoice = \App\Invoice::where('tempcode', $id)

// ->where('payment_status', '=', 3)
// ->orWhere('payment_status', 5)
->whereIn('payment_status', [3, 5])
->get();

if (empty($invoice[0])){

   return abort(404);
}
else
{
    $invoice_code = \App\Invoice::where('tempcode', $id)->first();
    $suminvoice_number = \App\Suminvoice::where('tempcode', $id)->first();
    $customer = \App\Customer::where('customers.id', $invoice_code->id_customer)

    ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
    ->Join('plans', 'customers.id_plan', '=', 'plans.id')
    ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')
    ->withTrashed()
    ->first();


    $active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
    ->where ('id_customer', '=', $invoice_code->id_customer )
    ->count();
    if ($active_invoice > 1)
    {

        $last_active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
        ->where ('id_customer', '=', $invoice_code->id_customer )
        ->orderBy('date', 'asc')->first();
        if ($id==$last_active_invoice->tempcode)
        {
            $current_inv_status =0;


//result jika ada inv sebelumnya

        }
        else
        {
            $current_inv_status =1;



        }
    }




    return view ('suminvoice/countershow',['invoice' =>$invoice, 'customer'=>$customer, 'bank'=>$bank, 'suminvoice_number' => $suminvoice_number, 'current_inv_status'=>$current_inv_status]);
}

}



    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function print($id)
    {
        //
        $result = "";
        $resultwinpay="";
        $companyName = config('app.name', 'INTERNET SERVICE PROVIDER');
        $companyAddress1 = tenant_config('company_address1', env('COMPANY_ADDRESS1'));
        $companyAddress2 = tenant_config('company_address2', env('COMPANY_ADDRESS2'));
        $signature = config('app.signature') ?? '';
        $invNote = tenant_config('inv_note', env('INV_NOTE')) ?? '';
        
        $current_inv_status =0;
        $bank = \App\Bank::pluck('name', 'id');
        $mount = now()->format('mY');
        $invoice = \App\Invoice::where('tempcode', $id)

        ->whereIn('payment_status', [3, 5])
        ->get();
        if (empty($invoice[0])){

           return abort(404);
       }
       else
       {

         $invoice_code = \App\Invoice::where('tempcode', $id)->first();
         $suminvoice_number = \App\Suminvoice::where('tempcode', $id)->first();
         $merchants = \App\Merchant::where('payment_point', 1)->get();
         $payment_id = $suminvoice_number->payment_id;
         $customer = \App\Customer::where('customers.id', $invoice_code->id_customer)


         ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
         ->Join('plans', 'customers.id_plan', '=', 'plans.id')
         ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')
         ->withTrashed()
         ->first();
         $encryptedurl = Crypt::encryptString($invoice_code->id_customer);
         $active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
         ->where ('id_customer', '=', $invoice_code->id_customer )
         ->count();
         if ($active_invoice > 1)
         {

            $last_active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
            ->where ('id_customer', '=', $invoice_code->id_customer )
            ->orderBy('date', 'asc')->first();
            if ($id==$last_active_invoice->tempcode)
            {
                $current_inv_status =0;


//result jika ada inv sebelumnya

            }
            else
            {
                $current_inv_status =1;



            }
        }
        if (!empty($payment_id) && $payment_id !== "winpay") 
        {

            $deleteResponse = $this->deleteWinpayVA($suminvoice_number->number);

      //  Log::info($deleteResponse);
            $endpoint     = tenant_config('tripay_endpoint', env("TRIPAY_ENDPOINT"));
            $apiKey       = tenant_config('tripay_apikey', env("TRIPAY_APIKEY"));
            $privateKey   = tenant_config('tripay_privatekey', env("TRIPAY_PRIVATEKEY"));
            $merchantCode = tenant_config('tripay_merchantcode', env("TRIPAY_MERCHANTCODE"));

//$hash = (hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey));



            $data = [
                'reference'         =>$payment_id,

            ];



            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_FRESH_CONNECT  => true,
                CURLOPT_URL            => 'https://tripay.co.id/api/transaction/detail?'.http_build_query($data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => false,
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
                CURLOPT_FAILONERROR    => false,
                CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);
            $result = json_decode($response, true);
            $tripayinv_status = 0;
//dd($result);
            if(curl_errno($curl)){

            }
            else
            {
                if (!empty($result['data']['status']) AND ($result['data']['status']=="UNPAID") ) {
                    $tripayinv_status = 1;

       // dd('UNPAIDww');
                }
                else
                {

                }





            }

            curl_close($curl);
        } elseif ($payment_id === "winpay") {
            $response = $this->findWinpayVA($suminvoice_number->id);

            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $resultwinpay = json_decode(json_encode($response->getData()), true);





            } else {
                $resultwinpay = $response;
            }
            Log::info(json_encode($resultwinpay));

        }
        else
        {

        }





        $suminvoice_amountdue = \App\Suminvoice::where('id_customer','=', $invoice_code->id_customer )
        ->where('payment_status', '=', 0)
        ->sum('total_amount');

        // Ambil konfigurasi gateway dari tabel payment_gateways (fleksibel per-tenant)
        // Satu query, mendukung berapapun provider tanpa perlu ubah kode ini lagi.
        $gateways = \App\PaymentGateway::activeForCurrentTenant();

        // Backward-compat array agar kode/view lama yang masih pakai $paymentConfig['xxx_enabled'] tetap bekerja
        $paymentConfig = $gateways
            ->mapWithKeys(fn($gw) => [$gw->provider . '_enabled' => (int) $gw->enabled])
            ->all();

        return view ('suminvoice/print',['invoice' =>$invoice, 'suminvoice_amountdue'=>$suminvoice_amountdue, 'customer'=>$customer, 'bank'=>$bank, 'suminvoice_number' => $suminvoice_number, 'current_inv_status' => $current_inv_status, 'encryptedurl'=>$encryptedurl, 'result'=>$result,'resultwinpay'=>$resultwinpay, 'merchants'=>$merchants, 'companyAddress1'=>$companyAddress1, 'companyAddress2'=>$companyAddress2, 'companyName' => $companyName, 'signature' =>$signature, 'invNote'=>$invNote, 'paymentConfig'=>$paymentConfig, 'gateways'=>$gateways]);   
    }
}
public function dotmatrix($id)
{
    $bank = \App\Bank::pluck('name', 'id');

    $companyName  = env('COMPANY_NAME') ?: env('APP_NAME') ?: 'ISP';
    $companyLegal = env('COMPANY')      ?: '';
    $address1     = env('COMPANY_ADDRESS1') ?: '';
    $address2     = env('COMPANY_ADDRESS2') ?: '';
    $signature    = config('app.signature') ?: '';
    $invNote      = env('INV_NOTE') ?: '';

    $invoice = \App\Invoice::where('tempcode', $id)
        ->whereIn('payment_status', [3, 5])
        ->get();

    if (empty($invoice[0])) {
        return abort(404);
    }

    $invoice_code      = \App\Invoice::where('tempcode', $id)->first();
    $suminvoice_number = \App\Suminvoice::where('tempcode', $id)->first();

    $customer = \App\Customer::where('customers.id', $invoice_code->id_customer)
        ->join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
        ->join('plans', 'customers.id_plan', '=', 'plans.id')
        ->select('customers.*', 'statuscustomers.name as status_name', 'plans.name as plan_name', 'plans.price as plan_price')
        ->withTrashed()
        ->first();

    return view('suminvoice/dotmatrix', compact(
        'invoice', 'customer', 'bank', 'suminvoice_number',
        'companyName', 'companyLegal', 'address1', 'address2',
        'signature', 'invNote'
    ));
}
public function edit($id)
{
        //
}

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function verify($id)
    {

     $query = \App\Suminvoice::where('id', $id)
     ->update([
        'verify' =>'1']);
     return redirect ('/suminvoice/transaction')->with('success','Transaction was verified');
 }


 public function update(Request $request, $id)
 {
     DB::beginTransaction();
     $merchant_fee = $request->merchant_fee ?? 0;

     try {
       $code = substr(md5(uniqid('', true)), 0, 10);

 // Setelah transaksi mulai, baru lock invoice untuk update
       $invoice = \App\Suminvoice::where('id', $id)->lockForUpdate()->first();

       if (!$invoice) {
        return redirect()->back()->with('warning', 'Invoice tidak ditemukan.');
    }

    if ($invoice->payment_status == 1) {
            DB::rollBack(); // Jangan lupa rollback kalau invoice sudah paid
            return redirect('/suminvoice/' . $invoice->tempcode)->with('warning', 'Invoice sudah dibayar sebelumnya.');
        }



        
        $date=date("Y-m-d H:i:s");
        $msg="";
        // $query = \App\Suminvoice::where('id', $id)
        // ->update([
        //     'recieve_payment' => $request->recieve_payment,
        //     'payment_point' => $request->payment_point,
        //     'note' => $request->note,
        //     'updated_by' => $request->updated_by,
        //     'payment_status' =>1,
        //     'payment_date' =>now()->toDateTimeString(),


        // ]);

        $customers = \App\Customer::where('id', $invoice->id_customer)->first();
        if (!$customers) {
            DB::rollBack();
            throw new \Exception("Customer tidak ditemukan");
        }

        $invoice->update([
            'recieve_payment' => $request->recieve_payment,
            'payment_point' => $request->payment_point,
            'note' => $request->note,
            'updated_by' => $request->updated_by,
            'payment_status' => 1,
            'payment_date' => now(),
            'merchant_fee' =>$merchant_fee,
            
        ]);

        $reff = $invoice->tempcode.'receive';
        if (!\App\Jurnal::where('reff', $reff)->exists()) {
            $data = [

                'date' => $date,
                'reff' =>  $reff,
                'type' => 'jumum',
                'description' => 'Receive Payment  #'.$request->number.' | '. $request->customer_name,
                'note' => 'Receive Payment OFFLINE  #'.$request->number.' | '.$request->customer_id. ' | '.$request->customer_name,
                'contact_id' => $customers->id,
                'code' => $code

            ];


            $data['id_akun'] = $request->payment_point;
            $data['debet'] = $request->recieve_payment;
            \App\Jurnal::create($data);
            $invstatus="";

    unset($data['debet']); // Remove debet key for the credit entry

// Create credit entry
    $data['id_akun'] = '1-10100';
    $data['kredit'] = $request->recieve_payment;
    \App\Jurnal::create($data);
}

Xendit::setApiKey(env('XENDIT_KEY'));
$id = $request->payment_id;
$oldStatus =$customers->status_name->name;

$updatedBy = Auth::check() ? 'Payment by ' . Auth::user()->name : 'System';

$logMessage = now() . " - {$customers->name} updated by {$updatedBy}";

// Hitung jumlah invoice yang masih unpaid
$active_invoice = \App\Suminvoice::where('payment_status', '=', '0')
->where('id_customer', '=', $request->id_customer)
->count();

// Jika status pelanggan = 4 dan tidak ada invoice unpaid, aktifkan kembali layanan
if ($customers->id_status == 4 && $active_invoice <= 0) {
    $distrouter = \App\Distrouter::withTrashed()->where('id', $customers->id_distrouter)->first();

    \App\Customer::where('id', $request->id_customer)->update(['id_status' => 2]);

        // \App\Distrouter::mikrotik_enable(
        //     $distrouter->ip, 
        //     $distrouter->user, 
        //     $distrouter->password, 
        //     $distrouter->port, 
        //     $customers->pppoe
        // );

    EnableMikrotikJob::dispatch($customers->id)->delay(now()->addSeconds(3));

        // Perubahan status

    $changes = [
        'Status' => [
                'old' => $oldStatus ?? 'Unknown',  // Status lama, misal: Active
                'new' => 'Active',  // Status baru
            ],
        ];

        // Tentukan siapa yang mengubah status (karena ini job, kita anggap "System Job")
        $updatedBy = Auth::check() ? 'Payment by ' . Auth::user()->name : 'System';


        // File log untuk customer
       // $logFile = "customers/customer_{$customers->id}.log";

        // Membuat log message
        $logMessage = now() . " - {$customers->name} updated by {$updatedBy} - Changes: " . json_encode($changes) . PHP_EOL;

        // Simpan log ke files
        
        \App\Customerlog::create([
            'id_customer' => $customers->id,
            'date' => now(),
            'updated_by' => $updatedBy,
            'topic' => 'payment',
            'updates' => json_encode($changes),
        ]);

        // Log::channel('payment')->info("Pelanggan ID: {$customers->customer_id} diaktifkan kembali karena tidak ada invoice unpaid. |".$logMessage);

        $invstatus ='Diaktifkan kembali karena tidak ada invoice unpaid.';

    } 
// Jika status pelanggan = 4 dan masih ada invoice unpaid
    elseif ($customers->id_status == 4 && $active_invoice > 0) {
    // Ambil invoice unpaid dengan due_date terdekat
        $active_invoice = \App\Suminvoice::where('payment_status', '=', '0')
        ->where('id_customer', '=', $request->id_customer)
        ->orderBy('due_date', 'asc')
        ->first();

    // Periksa apakah invoice masih dalam batas waktu jatuh tempo
        if ($active_invoice && Carbon::parse($active_invoice->due_date)->greaterThan(Carbon::today())) {
            $distrouter = \App\Distrouter::withTrashed()->where('id', $customers->id_distrouter)->first();

            \App\Customer::where('id', $request->id_customer)->update(['id_status' => 2]);

            // \App\Distrouter::mikrotik_enable(
            //     $distrouter->ip, 
            //     $distrouter->user, 
            //     $distrouter->password, 
            //     $distrouter->port, 
            //     $customers->pppoe
            // );
              // Perubahan status
            EnableMikrotikJob::dispatch($customers->id)->delay(now()->addSeconds(3));

            $changes = [
                'Status' => [
                'old' => $oldStatus ?? 'Unknown',  // Status lama, misal: Active
                'new' => 'Active',  // Status baru
            ],
        ];

        // Tentukan siapa yang mengubah status (karena ini job, kita anggap "System Job")
        $updatedBy = Auth::check() ? 'Payment by ' . Auth::user()->name : 'System';

        // File log untuk customer
        // $logFile = "customers/customer_{$customers->id}.log";

        // Membuat log message
        $logMessage = now() . " - {$customers->name} updated by {$updatedBy} - Changes: " . json_encode($changes) . PHP_EOL;

        // Simpan log ke files
        
        \App\Customerlog::create([
            'id_customer' => $customers->id,
            'date' => now(),
            'updated_by' => $updatedBy,
            'topic' => 'payment',
            'updates' => json_encode($changes),
        ]);
        $invstatus ='Diaktifkan kembali karena invoice unpaid masih dalam masa jatuh tempo.';
        // Log::channel('payment')->info("Pelanggan ID: {$customers->customer_id} diaktifkan kembali karena invoice unpaid masih dalam masa jatuh tempo | ".$logMessage);
    }
}






$customers = \App\Customer::Where('id',$request->id_customer)->withTrashed()->first();
$users = \App\User::Where('id',$request->updated_by)->withTrashed()->first();
$jumlah = $request->recieve_payment; 



$msg ='';
$jumlah = $request->recieve_payment; // Ambil jumlah dari request
$jumlah_rupiah = number_format($jumlah, 0, ',', '.'); // Format menjadi rupiah




$encryptedurl = Crypt::encryptString($customers->id);

if($customers->notification == 1)
{
    $message  = "\n\n";
    $message .= "\nPelanggan Yth. ";
    $message .= "\n\n";
    $message .= "\nNama : " . $customers->name;
    $message .= "\nCID : " . $customers->customer_id ;
    $message .= "\nKami ingin menginformasikan bahwa Tagihan no #".$request->number;
    $message .= "\nSejumlah Rp.".$jumlah ." Sudah kami TERIMA";
    $message .= "\n\n";
    $message .= "Untuk informasi lebih lanjut, silakan klik link berikut:";
    $message .= "\n" . "http://" . tenant_config('domain_name', env("DOMAIN_NAME")) . "/invoice/cst/" . $encryptedurl;
    $message .= "\n\n";
    $message .= "Jika sudah melakukan pembayaran, abaikan pesan ini.";
    $message .= "\nJika ada pertanyaan, hubungi CS kami di ".tenant_config('payment_wa', env("PAYMENT_WA"));
    $message .= "\n\n";
    $message .= "".config("app.signature")."";

    $msgresult = WaGatewayHelper::wa_payment($customers->phone, $message);


} elseif ($customers->notification == 2) {


   if (!empty($customers->email)) {

    $data = [
        'phone' => $customers->phone,
        'name' => $customers->name,
        'number' => "#".$request->number,
        'customer_id' => $customers->customer_id,
        'total_amount' => $jumlah,
        'url' => "/invoice/cst/" .$encryptedurl
    ];
    try {
        Mail::to($customers->email)->send(new EmailReceivePayment($data));
    } catch (\Exception $e) {
        \Log::error("Gagal kirim email ke {$customers->email}: " . $e->getMessage());
    }

}

} elseif ($customers->notification == 3) {

    // ── FCM Push Notification (Mobile App) ──
    $fcmTitle = '✅ Pembayaran Diterima';
    $fcmBody  = 'Tagihan #' . $request->number . ' sebesar Rp.' . $jumlah_rupiah . ' telah kami terima. Terima kasih!';
    $openUrl  = '/invoice/cst/' . $encryptedurl;

    if (!empty($customers->fcm_token)) {
        try {
            \App\Services\FcmService::send(
                $customers->fcm_token,
                $fcmTitle,
                $fcmBody,
                [
                    'type'        => 'payment_received',
                    'customer_id' => $customers->customer_id,
                    'open_url'    => $openUrl,
                ]
            );
        } catch (\Exception $eFcm) {
            \Log::error('[FCM] Payment notif error for ' . $customers->customer_id . ': ' . $eFcm->getMessage());
        }
    }

    // Simpan ke riwayat notifikasi app
    \App\AppCustomerNotification::record(
        (int) $customers->id,
        $fcmTitle,
        $fcmBody,
        'payment_received',
        $openUrl
    );

}







$notif_group = "[OFFLINE PAYMENT]";
$notif_group .= "\n\nPembayaran dari pelanggan ";
$notif_group .= "\nCID :" . $customers->customer_id. "" ;
$notif_group .= "\nNama :" . $customers->name."" ;
$notif_group .= "\nSUDAH DITERIMA";
$notif_group .= "\nJumlah: Rp " . $jumlah_rupiah;
$notif_group .= "\nOleh : ". $users->name."";
$notif_group .= "\n\nUntuk melihat detail pembayaran, silakan klik tautan berikut:";
$notif_group .= "\n👉 " . url("/suminvoice/" . $request->tempcode);
$notif_group .= "\n\nTerima kasih";
$notif_group .= "\n~ " . config("app.signature") . " ~";






DB::commit();
Log::channel('payment')->info("[OFFLINE PAYMENT ] Pelanggan ID: {$customers->customer_id}  |  INV no: ".$request->number." | ".$invstatus." | ".$logMessage);
 //Disable WA
// $msg .="\n Wa to Customer : ".$response;





$process = new Process(["python3", env("PHYTON_DIR")."telegram_send_to_group.py", 
   env("TELEGRAM_GROUP_PAYMENT"), $notif_group]);
try {
            // Menjalankan proses
    $process->run();

            // Memeriksa apakah proses berhasil
    if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);
    }

            // Mendapatkan output dari proses
    $output = $process->getOutput();
    $msg .="\n Send messages to Payment Group : success";


          //  return redirect ('/ticket/view/'.$request['id_customer'])->with('success', $output);
} catch (ProcessFailedException $e) {
            // Jika proses gagal, kembalikan pesan kesalahan
    $errorMessage = $e->getMessage();
          //  return redirect()->back()->with('error', $errorMessage);
}



  // $msg .="\n Wa to Payment Group :" .\App\Suminvoice::wa_payment_g($customers->phone,$notif_group);
return redirect ('/suminvoice/'. $request->tempcode)->with('info',$msg);


} catch (\Exception $e) {
        // Rollback the transaction if something went wrong
    DB::rollBack();

        // Log the error message for debugging purposes
    \Log::error('Update Suminvoice Error: ' . $e->getMessage());

        // Redirect back with a warning message
    return redirect('/suminvoice/' . $request->tempcode)->with('warning', 'Update failed: ' . $e->getMessage());
}
}

/**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
//     public function destroy(Request $request, $id)
//     {
//      $query = \App\Suminvoice::where('id', $id)
//      ->update([

//         'updated_by' => $request->updated_by,
//         'payment_status' =>2,
//         'payment_date' =>now()->toDateTimeString(),


//     ]);

//      if ($query)
//      {

//         $msg = "\n Item updated successfully!";

//         return redirect ('/suminvoice/'. $request->tempcode)->with('info',$msg);
//     }
//     else
//     {
//         return redirect ('/suminvoice/'. $request->tempcode)->with('warning','Item updated Failed');
//     }

// }



//     public function destroy(Request $request, $id)
//     {
//     DB::beginTransaction(); // Mulai transaksi

//     try {
//         // Ambil data suminvoice berdasarkan ID
//         $suminvoice = \App\Suminvoice::findOrFail($id);

//         // Update data di suminvoice
//         $suminvoice->update([
//             'updated_by' => $request->updated_by,
//             'payment_status' => 2,
//             'payment_date' => now()->toDateTimeString(),
//         ]);

//         // Update semua invoice yang memiliki tempcode yang sama
//         \App\Invoice::where('tempcode', $suminvoice->tempcode)
//         ->update(['monthly_fee' => 0]);

//         DB::commit(); // Jika semua query sukses, commit transaksi

//         return redirect('/suminvoice/' . $suminvoice->tempcode)
//         ->with('info', 'Item updated successfully!');
//     } catch (\Exception $e) {
//         DB::rollBack(); // Jika terjadi error, rollback transaksi

//         return redirect('/suminvoice/' . $request->tempcode)
//         ->with('warning', 'Item update failed: ' . $e->getMessage());
//     }
// }





public function send_reminder_inv(Request $request, $id)
{
    try {

        $type = $request->input('type');

        if (!in_array($type, ['wa', 'email', 'fcm'])) {
            return redirect()->back()->with('error', 'Invalid notification type.');
        }

        $suminvoice = \App\Suminvoice::find($id);
        if (!$suminvoice) {
            return redirect()->back()->with('error', 'Invoice not found.');
        }

        $customer = \App\Customer::withTrashed()->find($suminvoice->id_customer);
        if (!$customer) {
            return redirect()->back()->with('error', 'Customer not found.');
        }
        $duedate = $suminvoice->due_date ?: 'N/A';
        $encryptedurl = '/invoice/cst/' . Crypt::encryptString($customer->id);
        $formattedDate = Carbon::parse($suminvoice->date)->translatedFormat('M Y');

        // if ($type == 'wa') {
        //     $response = qontak_whatsapp_helper_remainder_inv(
        //         $customer->phone,
        //         $customer->name,
        //         $customer->customer_id,
        //         "#".$suminvoice->number,
        //         $suminvoice->total_amount,
        //         $formattedDate,
        //         $duedate,
        //         $encryptedurl
        //     );


        //     if ($response === 'success') {
        //         return response()->json(['message' => 'WhatsApp notification sent successfully.']);
        //     } else {
        //         return response()->json(['message' => 'Failed to send WhatsApp notification.'], 400);
        //     }





        if ($type == 'wa') {


            if ($suminvoice->payment_status == 1)
            {
                $message = "*[Informasi Pembayaran Internet]*";
                $message .= "\n\n";
                $message .= "Yth. " . $customer->name . ",";
                $message .= "\n\n";
                $message .= "Tagihan Anda dengan Customer ID (CID) *" . $customer->customer_id . "* telah DIBAYAR.";
                $message .= "\n*Total Tagihan:* Rp." . number_format($suminvoice->total_amount, 0, ',', '.') . "";
                $message .= "\n*Batas Pembayaran:* " . $duedate;
                $message .= "\n\n";
                $message .= "Untuk informasi lebih lanjut, silakan klik link berikut:";
                $message .= "\n" . "http://" . tenant_config('domain_name', env("DOMAIN_NAME")) . "" . $encryptedurl;
                $message .= "\n\n";
                $message .= "".config("app.signature")."";
            }
            else
            {

                $message = "*[Informasi Pembayaran Internet]*";
                $message .= "\n\n";
                $message .= "Yth. " . $customer->name . ",";
                $message .= "\n\n";
                $message .= "Tagihan Anda dengan Customer ID (CID) *" . $customer->customer_id . "* telah diterbitkan.";
                $message .= "\n*Total Tagihan:* Rp." . number_format($suminvoice->total_amount, 0, ',', '.') . "";
                $message .= "\n*Batas Pembayaran:* " . $duedate;
                $message .= "\n\n";
                $message .= "Untuk informasi lebih lanjut, silakan klik link berikut:";
                $message .= "\n" . "http://" . tenant_config('domain_name', env("DOMAIN_NAME")) . "" . $encryptedurl;
                $message .= "\n\n";
                $message .= "Jika sudah melakukan pembayaran, abaikan pesan ini.";
                $message .= "\nJika ada pertanyaan, hubungi CS kami di ".tenant_config('payment_wa', env("PAYMENT_WA"));
                $message .= "\n\n";
                $message .= "".config("app.signature")."";
            }

            // $msgresult = \App\Suminvoice::wa_payment($customer->phone, $message);
            $msgresult = WaGatewayHelper::wa_payment($customer->phone, $message);

            if (isset($msgresult['status']) && $msgresult['status'] === 'success') {
                return response()->json([
                    'message' => $msgresult['message'] ?? 'WhatsApp notification sent successfully.'
                ]);
            }

            return response()->json([
                'message' => $msgresult['message'] ?? 'Failed to send WhatsApp notification.'
            ], 400);


        } elseif ($type == 'email') {
            if (!empty($customer->email)){
                $data = [
                    'phone' => $customer->phone,
                    'name' => $customer->name,
                    'customer_id' => $customer->customer_id,
                    'number' => "#".$suminvoice->number,
                    'total_amount' => $suminvoice->total_amount,
                    'date' => $formattedDate,
                    'due_date' => $suminvoice->due_date,
                    'url' => $encryptedurl
                ];

                Mail::to($customer->email)->send(new EmailNotification($data));
                return response()->json(['message' => 'Email notification sent successfully.']);
            }
        }

        // ----------------------------------------------------------------
        // FCM: Push notification ke aplikasi Android
        // ----------------------------------------------------------------
        if ($type === 'fcm') {
            if (empty($customer->fcm_token)) {
                return response()->json(['message' => 'Pelanggan belum install / login aplikasi Android.'], 400);
            }

            if ($suminvoice->payment_status == 1) {
                $title = 'Pembayaran Diterima ✅';
                $body  = 'Tagihan #' . $suminvoice->number . ' sebesar Rp.' .
                         number_format($suminvoice->total_amount, 0, ',', '.') . ' telah LUNAS.';
            } else {
                $title = '🔔 Pengingat Tagihan';
                $body  = 'Tagihan #' . $suminvoice->number . ' sebesar Rp.' .
                         number_format($suminvoice->total_amount, 0, ',', '.') .
                         ' jatuh tempo ' . $duedate . '. Mohon segera lakukan pembayaran';
            }

            $sent = FcmService::send($customer->fcm_token, $title, $body, [
                'type'       => 'reminder_invoice',
                'invoice_id' => (string) $suminvoice->id,
                'open_url'   => $encryptedurl,
            ]);

            // Simpan ke riwayat notifikasi app (halaman Notif di app)
            AppCustomerNotification::record(
                $customer->id, $title, $body, 'reminder_invoice', $encryptedurl
            );

            if ($sent) {
                return response()->json(['message' => 'Push notification berhasil dikirim ke aplikasi Android.']);
            } else {
                return response()->json(['message' => 'Gagal kirim push notification. Cek log FCM.'], 400);
            }
        }

        return response()->json(['message' => 'Invalid notification type.'], 400);

    } catch (\Exception $e) {
        \Log::error('Error sending reminder: ' . $e->getMessage());
        return response()->json(['message' => 'Server error while sending notification.'], 500);
    }
}


// public function destroy(Request $request, $id)
// {
//     DB::beginTransaction(); // Mulai transaksi

//     try {
//         // Ambil data suminvoice berdasarkan ID
//         $suminvoice = \App\Suminvoice::findOrFail($id);

//         // Update data di suminvoice
//         $suminvoice->update([
//             'updated_by' => $request->updated_by,
//             'payment_status' => 2,
//             'payment_date' => "",
//         'note' => $request->cancel_reason // Simpan alasan pembatalan
//     ]);

//         // Update semua invoice yang memiliki tempcode yang sama
//         \App\Invoice::where('tempcode', $suminvoice->tempcode)
//         ->update([
//             'monthly_fee' => 0,
//             'payment_status' => 5
//         ]);                                                                                                                                 

//         // Soft delete pada tabel jurnals berdasarkan reff = tempcode atau tempcode + 'receive'
//         \App\Jurnal::where('reff', $request->tempcode)
//         ->orWhere('reff', $request->tempcode . 'receive')
//         ->delete(); // Soft delete jika model menggunakan `SoftDeletes`

//         DB::commit(); // Jika semua query sukses, commit transaksi

//         return redirect()->back()
//         ->with('success', 'Invoice updated and related journal entries deleted successfully!');
//     } catch (\Exception $e) {
//             DB::rollBack(); // Jika terjadi error, rollback transaksi

//             return redirect()->back()
//             ->with('error', 'Invoice update failed: ' . $e->getMessage());
//         }
//     }


public function cancelInvoice(Request $request, $id)
{
    DB::beginTransaction();

    try {
        $suminvoice = Suminvoice::findOrFail($id);
        $cancelDate = Carbon::parse($request->cancel_date ?? now());
        $code = substr(md5(uniqid('', true)), 0, 10);
        $contact_id = $suminvoice->id_customer;
        $cancel_reason = $request->cancel_reason;
        $refundAccount = $request->input('refund_account', config('akun.kas_bank'));

        // Simpan status awal (sebelum update)
        $isPaid = $suminvoice->payment_status == 1;

        // Ambil jurnal
        $jurnals = Jurnal::where('reff', $suminvoice->tempcode)->get();
        if ($jurnals->isEmpty()) {
            throw new \Exception("Tidak ada jurnal ditemukan untuk invoice ini.");
        }
        // dd($jurnals->first());
        //return $jurnals;
        // Deteksi PPN
        $ppnAccount = config('akun.ppn_keluaran');
        // return $contact_id;
        $hasPPN = $ppnAccount ? $jurnals->contains(fn($j) => $j->id_akun == $ppnAccount) : false;
        $ppnAmount = $hasPPN ? $jurnals->where('id_akun', $ppnAccount)->sum('kredit') : 0;
        
        // Update status invoice
        $suminvoice->update([
            'updated_by'    => $request->updated_by,
            'payment_status'=> 2, // cancelled
            'payment_date'  => null,
            'note'          => $cancel_reason,
        ]);

        Invoice::where('tempcode', $suminvoice->tempcode)
        ->update([
            'monthly_fee'   => 0,
            'payment_status'=> 5
        ]);

        // Buat jurnal sesuai kondisi
        if ($isPaid) {
            $this->createRefundJournal($jurnals, $cancelDate, $hasPPN, $ppnAmount, $code, $contact_id, $cancel_reason, $refundAccount);
        } else {
            if ($cancelDate->isSameMonth(Carbon::parse($suminvoice->date))) {
                $this->createReversalJournal($jurnals, $cancelDate, $code, $contact_id, $cancel_reason);
            } else {
                $this->createWriteOffJournal($jurnals, $cancelDate, $hasPPN, $ppnAmount, $code, $contact_id, $cancel_reason);
            }
        }

        DB::commit();
        return back()->with('success', 'Invoice berhasil dibatalkan dan jurnal sudah dibuat!');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal membatalkan invoice: '.$e->getMessage());
    }
}


    /**
     * Buat jurnal reversal untuk pembatalan di bulan yang sama
     */
    protected function createReversalJournal($originalJurnals, $cancelDate, $code, $contact_id, $cancel_reason)
    {
        foreach ($originalJurnals as $jurnal) {
            Jurnal::create([
                'date'       => $cancelDate,
                'id_akun'    => $jurnal->id_akun,
                'debet'      => $jurnal->kredit, // dibalik
                'kredit'     => $jurnal->debet,
                'reff'       => $jurnal->reff.'-reversal',
                'description'=> '[REVERSAL] '.$jurnal->description,
                'created_by' => auth()->id(),
                'code' => $code,
                'note' => 'REVERSAL '.$jurnal->description. ' | '. $cancel_reason,
                'type'        => 'reversal',
                'contact_id' => $contact_id
            ]);
        }
    }

    /**
     * Buat jurnal write-off untuk pembatalan setelah tutup buku
     */
    protected function createWriteOffJournal($originalJurnals, $cancelDate, $hasPPN, $ppnAmount, $code, $contact_id, $cancel_reason)
    {
        $piutangAccount = config('akun.piutang_usaha'); // 10100
        $pendapatanAccount = config('akun.pendapatan'); // 40000
        $bebanTakTertagihAccount = config('akun.beban_taktertagih'); // 6-60100
        $note = 'WRITEOFF '.$originalJurnals->first()->description. ' | '. $cancel_reason;
        $desc = $originalJurnals->first()->description;
        $piutangAmount = $originalJurnals->where('id_akun', $piutangAccount)->sum('debet');

        // Dr Beban Piutang Tak Tertagih
        // Cr Piutang Usaha
        Jurnal::create([
            'date'       => $cancelDate,
            'id_akun'    => $bebanTakTertagihAccount,
            'debet'      => $piutangAmount,
            'kredit'     => 0,
            'reff'       => $originalJurnals->first()->reff.'-writeoff',
            'description'=> '[WRITE-OFF] Pembatalan Invoice & penghapusan piutang |'.$desc,
            'created_by' => auth()->id(),
            'code' => $code,
            'note' => $note,
            'type'        => 'writeoff',
            'contact_id' => $contact_id
        ]);

        Jurnal::create([
            'date'       => $cancelDate,
            'id_akun'    => $piutangAccount,
            'debet'      => 0,
            'kredit'     => $piutangAmount,
            'reff'       => $originalJurnals->first()->reff.'-writeoff',
            'description'=> '[WRITE-OFF] Penghapusan piutang |'.$desc,
            'created_by' => auth()->id(),
            'type'        => 'writeoff',
            'code' => $code,
            'note' => $note,
            'contact_id' => $contact_id
        ]);

        if ($hasPPN && $ppnAmount > 0) {
            Jurnal::create([
                'date'       => $cancelDate,
                'id_akun'    => config('akun.ppn_keluaran'),
                'debet'      => $ppnAmount,
                'kredit'     => 0,
                'reff'       => $originalJurnals->first()->reff.'-writeoff',
                'description'=> '[WRITE-OFF] Pembetulan PPN keluaran |'.$desc,
                'created_by' => auth()->id(),
                'type'        => 'writeoff',
                'code' => $code,
                'note' => $note,
                'contact_id' => $contact_id
            ]);
        }
    }

    /**
     * Buat jurnal refund jika invoice sudah dibayar
     */
    protected function createRefundJournal($originalJurnals, $cancelDate, $hasPPN, $ppnAmount, $code, $contact_id, $cancel_reason, $refundAccount)
    {
        $pendapatanAccount = config('akun.pendapatan'); // 40000
        $ppnAccount = config('akun.ppn_keluaran');
        $kasBankAccount = $refundAccount; // 1-10200 misalnya

        $pendapatanAmount = $originalJurnals->where('id_akun', $pendapatanAccount)->sum('kredit');
        $note = 'REFUND '.$originalJurnals->first()->description. ' | '. $cancel_reason;
        // Dr Pendapatan
        Jurnal::create([
            'date'       => $cancelDate,
            'id_akun'    => $pendapatanAccount,
            'debet'      => $pendapatanAmount,
            'kredit'     => 0,
            'reff'       => $originalJurnals->first()->reff.'-refund',
            'description'=> '[REFUND] Pembatalan & pengembalian pendapatan',
            'created_by' => auth()->id(),
            'type'        => 'refund',
            'code' => $code,
            'note' => $note,
            'contact_id' => $contact_id
        ]);

        // Dr PPN Keluaran jika ada
        if ($hasPPN && $ppnAmount > 0) {
            Jurnal::create([
                'date'       => $cancelDate,
                'id_akun'    => $ppnAccount,
                'debet'      => $ppnAmount,
                'kredit'     => 0,
                'reff'       => $originalJurnals->first()->reff.'-refund',
                'description'=> '[REFUND] Pembetulan PPN keluaran',
                'created_by' => auth()->id(),
                'code' => $code,
                'note' => $note,
                'type'        => 'refund',
                'contact_id' => $contact_id
            ]);
        }

        // Cr Kas/Bank (uang keluar)
        Jurnal::create([
            'date'       => $cancelDate,
            'id_akun'    => $kasBankAccount,
            'debet'      => 0,
            'kredit'     => $pendapatanAmount + $ppnAmount,
            'reff'       => $originalJurnals->first()->reff.'-refund',
            'description'=> '[REFUND] Pengembalian uang ke customer',
            'created_by' => auth()->id(),
            'code' => $code,
            'note' => $note,
            'type'        => 'refund',
            'contact_id' => $contact_id
        ]);
    }
    public function invoicenotif()
    {
        $phone = '081805360534';
// \Log::channel('invoice')->info('==== START INVOICE CREATE BY SYSTEM. ===');
        $unpaidinv =\App\Suminvoice::orderBy('id', 'DESC')
        ->where('payment_status','=', '0')
        ->limit (3)
        ->get();



        foreach($unpaidinv as $inv) {
          $customer = \App\Customer::Where('id',$inv->id_customer)->withTrashed()->first();
      // $message ="Yth. ".$customer->name." ";
      // $message .="\n";
      // $message .="\nTagihan Customer dengan CID *".$customer->customer_id."* sudah kami Terbitkan sebesar *Rp.". $inv->total_amount."*";
      // $message .="\nSilahakan melakukan pembayaran sebelum tanggal 20-".date("m-Y", time());
      // $message .="\nUntuk info lebih lengkap silahkan klik link berikut";
      // $message .="\nhttp://".env("DOMAIN_NAME")."/suminvoice/".$inv->tempcode."/print";
      // $message .="\n";
      // $message .="\n~ ".config("app.signature")." ~";
          $message = "Halo *" . $customer->name . "*,";
          $message .= "\n\nTagihan dengan CID *" . $customer->customer_id . "* telah diterbitkan sebesar *Rp. " . number_format($inv->total_amount, 0, ',', '.') . "*.";
          $message .= "\nMohon melakukan pembayaran sebelum *20-" . date("m-Y") . "*.";
          $message .= "\n\nUntuk informasi lebih lanjut, silakan klik tautan berikut:";
          $message .= "\n👉 " . url("/suminvoice/" . $inv->tempcode . "/print");
          $message .= "\n\nTerima kasih atas perhatian Anda.";
          $message .= "\n~ *" . config("app.signature") . "* ~";


//Disable WA
      // $msgresult=\App\Suminvoice::wa_payment($phone,$message);


          sleep(2); 


      }


  }



public function faktur (Request $request, $id)
{
  $request->validate([
    'file' => 'required'
]); 

  if($request->file('file')) {
   $file = $request->file('file');
   $name = $file->getClientOriginalName();
   $filename = time().'_'.str_replace(' ', '_',$name);

         // File upload location
   $location = 'upload/tax';

         // Upload file
   $file->move($location,$filename);

   $id_customer = ($request['id_customer']);

   $tempcode = ($request['tempcode']);

   \App\Suminvoice::where('id', $id)
   ->update([

    'file' => $filename


]);


   return redirect ('/suminvoice/'.$tempcode)->with('success','file Updated successfully!');
}else{
    return redirect ('/suminvoice'.$tempcode)->with('success','File Not Uploaded!');
}
}
}
