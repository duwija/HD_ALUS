<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Encryption\DecryptException;
use Exception; 
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Jobs\EnableMikrotikJob;
use App\Services\FcmService;
use App\AppCustomerNotification;
use App\Mail\EmailReceivePayment;
use App\Helpers\WaGatewayHelper;


class XenditCallbackController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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
    public function update(Request $request)
    {
        //
      // $data=response()->json($request);
      $id_xendit= $request->id;
      if ($request->status == 'PAID')

      {

        try{

         $query = \App\Suminvoice::where('payment_id', $id_xendit)
         ->update([
            'recieve_payment'  => $request->paid_amount,
            'payment_point'    => $request->bank_code,
            'note'             => $request->payment_method,
            'updated_by'       => $request->merchant_name,
            'payment_status'   => 1,
            'payment_date'     => now()->toDateTimeString(),
            'payment_gateway'  => 'xendit',
        ]);
         if($query)
         {

            $invoice =\App\Suminvoice::where('payment_id', $id_xendit)->first();
            
            $customers = \App\Customer::Where('id',$invoice->id_customer)->first();
            
            // Check WA Provider from tenant config
            $waProvider = tenant_config('wa_provider', 'gateway');
            $oldStatus = $customers->status_name->name ?? 'Unknown';
            $totalamountandfee = $request->paid_amount;

            $this->sendPaymentNotification($customers, $invoice->number, $totalamountandfee, 'XENDIT');



            $active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
            ->where ('id_customer', '=', $invoice->id_customer )
            ->count();
            
            if (($customers->id_status ==4 ) AND ($active_invoice <= 0 ))
            {

             $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
             \App\Customer::where('id', $invoice->id_customer)->update([
                'id_status' => 2 ]);
               // \App\Distrouter::mikrotik_enable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->customer_id);
             EnableMikrotikJob::dispatch($customers->id)->onQueue(app('tenant')['domain'] ?? 'default')->delay(now()->addSeconds(2));

         }






     }
 }
 catch (Exception $e)
 {
    return $e;
}

}

}



public function update_tripay(Request $request)
{
    $date = now()->toDateTimeString();
    $number = $request->merchant_ref;
    $updatedBy ='TRIPAY' ;
    $msg='';
    $changes = [];
    $code = substr(md5(uniqid('', true)), 0, 10);
    // 

    \Log::channel('payment')->debug("=== Callback received from Tripay | STATUS : ".$request->status." ===", [
        'merchant_ref' => $number,
        'status' => $request->status,
        'amount_received' => $request->amount_received,
    ]);

    if ($request->status !== 'PAID') {
        return response()->json(['success' => false, 'message' => 'Payment not completed'], 400);
    }

    $cekstatus = \App\Suminvoice::where('number', $number)->first();
    if (!$cekstatus) {
        // Cek apakah ini bundle payment
        $bundle = \DB::table('payment_bundles')->where('bundle_ref', $number)->first();
        if ($bundle) {
            $noteStr = ($request->payment_method ?? '') . ' | ' . ($request->amount_received ?? '');
            return $this->processBundleCallback($bundle, (float)($request->amount_received ?? 0), 'TRIPAY', $noteStr);
        }
        \Log::channel('payment')->error("Invoice tidak ditemukan: $number");
        return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
    }

    if ($cekstatus->payment_status == 1) {

        \Log::channel('payment')->info("[ONLINE PAYMENT ]  INV no: ".$number." | Status Invoice sudah dibayar, proses tidak dijalankan ulang. ");
        return response()->json(['success' => true]);
    }
    if ($cekstatus->payment_status == 0) {
        DB::beginTransaction();
        try {


            $invoice = \App\Suminvoice::where('number', $number)
            ->lockForUpdate()
            ->first();



            // $invoice = \App\Suminvoice::where('number', $number)->first();


            if (!$invoice) {
                throw new \Exception("Invoice tidak ditemukan setelah update");
            }

            $customers = \App\Customer::where('id', $invoice->id_customer)->first();
            if (!$customers) {
                DB::rollBack();
                throw new \Exception("Customer tidak ditemukan");
            }

            // lalu update
            $invoice->update([
                'recieve_payment' => $request->amount_received,
                'payment_point'   => tenant_config('PAYMENT_POINT_TRIPAY', '1-10039'),
                'note'            => $request->payment_method . ' (' . $request->amount_received . '+' . $request->total_fee . '->' . $request->total_amount . ')',
                'updated_by'      => 'TRIPAY',
                'payment_status'  => 1,
                'payment_date'    => now()->toDateTimeString(),
                'payment_gateway' => 'tripay',
            ]);

            $oldStatus = $customers->status_name->name ?? 'Unknown';
            $totalamountandfee = $request->amount_received + $request->fee_merchant;

            $this->sendPaymentNotification($customers, $number, $totalamountandfee, 'TRIPAY');

        // Kirim Notifikasi Telegram
          //   if (env("PHYTON_DIR") && env("TELEGRAM_GROUP_PAYMENT")) {
          //       $notif_group = "[ONLINE PAYMENT]\n\n" .
          //       "CID: {$customers->customer_id}\n" .
          //        "Nama: {$customers->name}\n" .
          //       "SUDAH DITERIMA\n" .
          //       "Jumlah: Rp " . number_format($request->amount_received, 0, ',', '.') . "\n" .
          //       "Oleh: TRIPAY | {$request->payment_method}\n" .
          //       "👉 " . url("/suminvoice/" . $invoice->tempcode) . "\n\n" .
          //       "Terima kasih\n~ " . config("app.signature") . " ~";

          //       $process = new Process([
          //           "python3", env("PHYTON_DIR") . "telegram_send_to_group.py",
          //           env("TELEGRAM_GROUP_PAYMENT"), $notif_group
          //       ]);
          //       $process->run();

          //       if (!$process->isSuccessful()) {
          //   // throw new ProcessFailedException($process);
          //         \Log::channel('payment')->error("Telegram gagal Mengirim pesan");
          //     }
          // }

        // Entri Jurnal Keuangan

            $reff = $invoice->tempcode.'receive';
            if (!\App\Jurnal::where('reff', $reff)->exists()) {
              \App\Jurnal::create([
                'date' => $date, 'reff' => $reff,
                'type' => 'jumum', 'description' => 'Receive Payment #' . $number . ' | ' . $customers->name,
                'note' => 'Receive Payment ONLINE #' . $number . ' | ' . $customers->customer_id. ' | ' . $customers->name,
                'id_akun' => '1-10039', 'debet' => $request->amount_received,
                'contact_id' => $customers->id,
                'code' => $code
            ]);

              if($request->amount_received < $invoice->total_amount )
              {
                 \App\Jurnal::create([
                    'date' => $date, 'reff' => $reff,
                    'type' => 'jumum', 'description' => 'Receive Payment #' . $number . ' | ' . $customers->name,
                    'note' => 'Receive Payment ONLINE #' . $number . ' | ' . $customers->customer_id. ' | ' . $customers->name,
                    'id_akun' => '6-60249', 'debet' => $invoice->total_amount-$request->amount_received,
                    'contact_id' => $customers->id,
                    'code' => $code
                ]);
             }

             \App\Jurnal::create([
                'date' => $date, 'reff' => $reff,
                'type' => 'jumum', 'description' => 'Receive Payment #' . $number . ' | ' . $customers->name,
                'note' => 'Receive Payment ONLINE #' . $number . ' | ' . $customers->customer_id. ' | ' . $customers->name,
        // 'id_akun' => '1-10100', 'kredit' => $request->amount_received
                'id_akun' => '1-10100', 'kredit' => $invoice->total_amount,
                'contact_id' => $customers->id,
                'code' => $code
            ]);
         }

        // Cek jika tidak ada invoice unpaid, update status customer
         if ($customers->id_status == 4 && \App\Suminvoice::where('payment_status', 0)->where('id_customer', $invoice->id_customer)->count() <= 0) {
            \App\Customer::where('id', $invoice->id_customer)->update(['id_status' => 2]);
            if ($distrouter = \App\Distrouter::withTrashed()->where('id', $customers->id_distrouter)->first()) {
             try {
            // \App\Distrouter::mikrotik_enable(
            //     $distrouter->ip,
            //     $distrouter->user,
            //     $distrouter->password,
            //     $distrouter->port,
            //     $customers->pppoe
            // );
               EnableMikrotikJob::dispatch($customers->id)->onQueue(app('tenant')['domain'] ?? 'default')->delay(now()->addSeconds(2));
           } catch (\Exception $e) {
            \Log::channel('payment')->warning("Gagal mikrotik_enable untuk {$customers->customer_id}: " . $e->getMessage());
        }




        $changes = [
            'Status' => [
                'old' => $oldStatus ?? 'Unknown',  // Status lama, misal: Active
                'new' => 'Active',  // Status baru
            ],
        ];

        // Tentukan siapa yang mengubah status (karena ini job, kita anggap "System Job")
        $updatedBy ='TRIPAY' ;
        $msg='Diaktifkan kembali karena tidak ada invoice unpaid.';

    }
}




elseif ($customers->id_status == 4 && \App\Suminvoice::where('payment_status', 0)->where('id_customer', $invoice->id_customer)->count() > 0) {
    // Ambil invoice unpaid dengan due_date terdekat
    $active_invoice = \App\Suminvoice::where('payment_status', '=', '0')
    ->where('id_customer', '=', $invoice->id_customer)
    ->orderBy('due_date', 'asc')
    ->first();

    // Periksa apakah invoice masih dalam batas waktu jatuh tempo
    if ($active_invoice && Carbon::parse($active_invoice->due_date)->greaterThan(Carbon::today())) {
        $distrouter = \App\Distrouter::withTrashed()->where('id', $customers->id_distrouter)->first();

        \App\Customer::where('id', $invoice->id_customer)->update(['id_status' => 2]);

        try {
            // \App\Distrouter::mikrotik_enable(
            //     $distrouter->ip,
            //     $distrouter->user,
            //     $distrouter->password,
            //     $distrouter->port,
            //     $customers->pppoe
            // );
           EnableMikrotikJob::dispatch($customers->id)->onQueue(app('tenant')['domain'] ?? 'default')->delay(now()->addSeconds(2));
       } catch (\Exception $e) {
        \Log::channel('payment')->warning("Gagal mikrotik_enable untuk {$customers->customer_id}: " . $e->getMessage());
    }
        // Perubahan status

    $changes = [
        'Status' => [
                'old' => $oldStatus ?? 'Unknown',  // Status lama, misal: Active
                'new' => 'Active',  // Status baru
            ],
        ];

        // Tentukan siapa yang mengubah status (karena ini job, kita anggap "System Job")
        $updatedBy ='TRIPAY ' ;


        // File log untuk customer



        $msg ="Diaktifkan kembali karena invoice unpaid masih dalam masa jatuh tempo.";

    }
}








DB::commit();
$logMessage = now() . " - {$customers->name} updated by {$updatedBy} - Changes: " . json_encode($changes) . PHP_EOL;





if (!empty($changes)) {
    \App\Customerlog::create([
        'id_customer' => $customers->id,
        'date' => now(),
        'updated_by' => $updatedBy,
        'topic' => 'payment',
        'updates' => json_encode($changes),
    ]);

    \Log::channel('payment')->info("[ONLINE PAYMENT ] Pelanggan ID: {$customers->customer_id}  | INV no: ".$number." | ".$msg." |".$logMessage);
}
else
{
    $changes = [];
    \Log::channel('payment')->info("[ONLINE PAYMENT ] Pelanggan ID: {$customers->customer_id}  | INV no: ".$number." | ".$msg." |".$logMessage);
}

return response()->json(['success' => true]);
} catch (\Exception $e) {
    DB::rollBack();
    \Log::channel('payment')->error("Error in update_tripay: " . $e->getMessage());
    return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
}


}


}


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */





//     public function update_tripay(Request $request)
//     {
//         $date = now()->toDateTimeString();
//         $number = $request->merchant_ref;

//         \Log::channel('payment')->debug("=== Callback received from Tripay | STATUS : {$request->status} ===", [
//             'merchant_ref'    => $number,
//             'status'          => $request->status,
//             'amount_received' => $request->amount_received,
//         ]);

//     // Kalau belum PAID, balas error
//         if ($request->status !== 'PAID') {
//             return response()->json(['success' => false, 'message' => 'Payment not completed'], 400);
//         }

//         DB::beginTransaction();
//         try {
//         // Lock invoice biar tidak double update
//             $invoice = \App\Suminvoice::where('number', $number)
//             ->lockForUpdate()
//             ->first();

//             if (!$invoice) {
//                 DB::rollBack();
//                 \Log::channel('payment')->error("Invoice tidak ditemukan: $number");
//                 return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
//             }

//         // Sudah dibayar? jangan ulangi
//             if ($invoice->payment_status == 1) {
//                 DB::rollBack();
//                 \Log::channel('payment')->warning("[ONLINE PAYMENT] INV no: $number | Sudah DIBAYAR, Proses tidak dijalankan Ulang. |TRIPAY|");

//                 return response()->json(['success' => true]);
//             }

//         // Update invoice
//             $invoice->update([
//                 'recieve_payment' => $request->amount_received,
//                 'payment_point'   => '1-10039',
//                 'note'            => $request->payment_method . ' (' . $request->amount_received . '+' . $request->total_fee . '->' . $request->total_amount . ')',
//                 'updated_by'      => 'TRIPAY',
//                 'payment_status'  => 1,
//                 'payment_date'    => $date,
//             ]);

//             DB::commit();

//         // ✅ Balas cepat ke Tripay biar tidak retry
//             response()->json(['success' => true])->send();
//             flush();

//         // Hitung nilai bersih (nett)
//         $nett = $request->amount_received; // di Tripay biasanya amount_received sudah termasuk fee merchant terpisah

//         // Jalankan helper untuk notifikasi + jurnal + update customer
//         process_payment_success(
//             $invoice,
//             $request->all(),
//             $request->amount_received + $request->fee_merchant, // total yang diterima user (amount + fee_merchant)
//             $nett,
//             $date,
//             'TRIPAY',
//             $request->payment_method
//         );

//         return;

//     } catch (\Exception $e) {
//         DB::rollBack();
//         \Log::channel('payment')->error("Error in update_tripay: " . $e->getMessage());
//         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
//     }
// }






    public function update_winpayxx(Request $request)
    {
        $date = now()->toDateTimeString();
        $number = $request->merchant_ref;
        $updatedBy ='WINPAY' ;
        $msg='';
        $changes = [];
    // 

        \Log::channel('payment')->info("=== Callback received from winpay | STATUS : ".$request);
    }





    public function update_winpay(Request $request)
    {
        $payload = $request->all();
        $amount_received=$payload['amount'];
        $nett_amount=$payload['nett_amount'];
        $channel=$payload['channel'];
        $nett=$nett_amount-$payload['fee'];
        $date = now()->toDateTimeString();
        $number = $payload['invoice']['ref'] ?? null;
        $updatedBy ='WINPAY' ;
        $msg='';
        $changes = [];
        $code = substr(md5(uniqid('', true)), 0, 10);
    // 

        \Log::channel('payment')->debug("=== Callback received from WINPAY ", [
            'merchant_ref' => $number,

            'amount_received' =>$amount_received,
        ]);

        // if ($request->status !== 'PAID') {
        //     return response()->json(['success' => false, 'message' => 'Payment not completed'], 400);
        // }

        $cekstatus = \App\Suminvoice::where('number', $number)->first();
        if (!$cekstatus) {
            // Cek apakah ini bundle payment
            $bundle = \DB::table('payment_bundles')->where('bundle_ref', $number)->first();
            if ($bundle) {
                $noteStr = ($payload['channel'] ?? '') . ' | ' . ($payload['amount'] ?? '');
                return $this->processBundleCallback($bundle, (float)($payload['amount'] ?? 0), 'WINPAY', $noteStr);
            }
            \Log::channel('payment')->error("Invoice tidak ditemukan: $number");
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        if ($cekstatus->payment_status == 1) {
           \Log::channel('payment')->info("[ONLINE PAYMENT ]  INV no: ".$number." | Status Invoice sudah dibayar, proses tidak dijalankan ulang. ");
           return("ACCEPTED");
       }

       if ($cekstatus->payment_status == 0) {



        DB::beginTransaction();
        try {

            $invoice = \App\Suminvoice::where('number', $number)
            ->lockForUpdate()
            ->first();
            if (!$invoice) {
                throw new \Exception("Invoice tidak ditemukan setelah update");
            }

            $customers = \App\Customer::where('id', $invoice->id_customer)->first();
            if (!$customers) {
                throw new \Exception("Customer tidak ditemukan");
            }
            $invoice->update([
                'recieve_payment' => $nett,
                'payment_point'   => tenant_config('PAYMENT_POINT_WINPAY', '1-10040'),
                'note'            => $channel. ' | ' . $amount_received - 2500 . '+ 2500 - ( Fee ' . $payload['fee'].')',
                'updated_by'      => 'WINPAY',
                'payment_status'  => 1,
                'payment_date'    => now()->toDateTimeString(),
                'payment_gateway' => 'winpay',
            ]);



            $oldStatus = $customers->status_name->name ?? 'Unknown';
            $totalamountandfee = $amount_received;

            $this->sendPaymentNotification($customers, $number, $amount_received, 'WINPAY');

        // Kirim Notifikasi Telegram
            // if (env("PHYTON_DIR") && env("TELEGRAM_GROUP_PAYMENT")) {
            //     $notif_group = "[ONLINE PAYMENT]\n\n" .
            //     "CID: {$customers->customer_id}\n" .
            //     "Nama: {$customers->name}\n" .
            //     "SUDAH DITERIMA\n" .
            //     "Jumlah: Rp " . number_format($amount_received, 0, ',', '.') . "\n" .
            //     "Oleh: ".$updatedBy." | {$payload['channel']}\n" .
            //     "👉 " . url("/suminvoice/" . $invoice->tempcode) . "\n\n" .
            //     "Terima kasih\n~ " . config("app.signature") . " ~";

            //     $process = new Process([
            //         "python3", env("PHYTON_DIR") . "telegram_send_to_group.py",
            //         env("TELEGRAM_GROUP_PAYMENT"), $notif_group
            //     ]);
            //     $process->run();

            //     if (!$process->isSuccessful()) {
            //     // throw new ProcessFailedException($process);

            //         \Log::channel('payment')->error("Telegram gagal Mengirim pesan");
            //     }
            // } else {
            //     \Log::channel('payment')->warning("Telegram notifikasi tidak dikirim: env belum diset (PHYTON_DIR / TELEGRAM_GROUP_PAYMENT)");
            // }
        // Entri Jurnal Keuangan
            $reff = $invoice->tempcode.'receive';
            if (!\App\Jurnal::where('reff', $reff)->exists()) {
                \App\Jurnal::create([
                    'date' => $date, 'reff' => $reff,
                    'type' => 'jumum', 'description' => 'Receive Payment #' . $number . ' | ' . $customers->name,
                    'note' => 'Receive Payment ONLINE #' . $number . ' | ' . $customers->customer_id. ' | ' . $customers->name,
                    'id_akun' => '1-10040', 'debet' => $nett,
                    'contact_id' => $customers->id,
                    'code' => $code
                ]);




                if($nett < $invoice->total_amount )
                {
                 \App\Jurnal::create([
                    'date' => $date, 'reff' => $reff,
                    'type' => 'jumum', 'description' => 'Receive Payment #' . $number . ' | ' . $customers->name,
                    'note' => 'Receive Payment ONLINE #' . $number . ' | ' . $customers->customer_id. ' | ' . $customers->name,
                    'id_akun' => '6-60249', 'debet' => $invoice->total_amount-$nett,
                    'contact_id' => $customers->id,
                    'code' => $code
                ]);
             }



             \App\Jurnal::create([
                'date' => $date, 'reff' => $reff,
                'type' => 'jumum', 'description' => 'Receive Payment #' . $number . ' | ' . $customers->name,
                'note' => 'Receive Payment ONLINE #' . $number . ' | ' . $customers->customer_id. ' | ' . $customers->name,
                'id_akun' => '1-10100', 'kredit' => $invoice->total_amount,
                'contact_id' => $customers->id,
                'code' => $code
            ]);
         }

        // Cek jika tidak ada invoice unpaid, update status customer
         if ($customers->id_status == 4 && \App\Suminvoice::where('payment_status', 0)->where('id_customer', $invoice->id_customer)->count() <= 0) {
            \App\Customer::where('id', $invoice->id_customer)->update(['id_status' => 2]);
            if ($distrouter = \App\Distrouter::withTrashed()->where('id', $customers->id_distrouter)->first()) {
             try {
               
               EnableMikrotikJob::dispatch($customers->id)->onQueue(app('tenant')['domain'] ?? 'default')->delay(now()->addSeconds(2));
           } catch (\Exception $e) {
            \Log::channel('payment')->warning("Gagal mikrotik_enable untuk {$customers->customer_id}: " . $e->getMessage());
        }

        $changes = [
            'Status' => [
                'old' => $oldStatus ?? 'Unknown',  // Status lama, misal: Active
                'new' => 'Active',  // Status baru
            ],
        ];

        // Tentukan siapa yang mengubah status (karena ini job, kita anggap "System Job")
        $updatedBy ='WINPAY' ;
        $msg='Diaktifkan kembali karena tidak ada invoice unpaid.';

    }
}




elseif ($customers->id_status == 4 && \App\Suminvoice::where('payment_status', 0)->where('id_customer', $invoice->id_customer)->count() > 0) {
    // Ambil invoice unpaid dengan due_date terdekat
    $active_invoice = \App\Suminvoice::where('payment_status', '=', '0')
    ->where('id_customer', '=', $invoice->id_customer)
    ->orderBy('due_date', 'asc')
    ->first();

    // Periksa apakah invoice masih dalam batas waktu jatuh tempo
    if ($active_invoice && Carbon::parse($active_invoice->due_date)->greaterThan(Carbon::today())) {
        $distrouter = \App\Distrouter::withTrashed()->where('id', $customers->id_distrouter)->first();

        \App\Customer::where('id', $invoice->id_customer)->update(['id_status' => 2]);

        try {
            // \App\Distrouter::mikrotik_enable(
            //     $distrouter->ip,
            //     $distrouter->user,
            //     $distrouter->password,
            //     $distrouter->port,
            //     $customers->pppoe
            // );

           EnableMikrotikJob::dispatch($customers->id)->onQueue(app('tenant')['domain'] ?? 'default')->delay(now()->addSeconds(2));
       } catch (\Exception $e) {
        \Log::channel('payment')->warning("Gagal mikrotik_enable untuk {$customers->customer_id}: " . $e->getMessage());
    }

        // Perubahan status

    $changes = [
        'Status' => [
                'old' => $oldStatus ?? 'Unknown',  // Status lama, misal: Active
                'new' => 'Active',  // Status baru
            ],
        ];

        // Tentukan siapa yang mengubah status (karena ini job, kita anggap "System Job")
        $updatedBy ='WINPAY ' ;


        // File log untuk customer



        $msg ="Diaktifkan kembali karena invoice unpaid masih dalam masa jatuh tempo.";

    }
}








DB::commit();
$logMessage = now() . " - {$customers->name} updated by {$updatedBy} - Changes: " . json_encode($changes) . PHP_EOL;




if (!empty($changes)) {
    \App\Customerlog::create([
        'id_customer' => $customers->id,
        'date' => now(),
        'updated_by' => $updatedBy,
        'topic' => 'payment',
        'updates' => json_encode($changes),
    ]);

    \Log::channel('payment')->info("[ONLINE PAYMENT ] Pelanggan ID: {$customers->customer_id}  | INV no: ".$number." | ".$msg." |".$logMessage);
}
else
{
    $changes = [];
    \Log::channel('payment')->info("[ONLINE PAYMENT ] Pelanggan ID: {$customers->customer_id}  | INV no: ".$number." | ".$msg." |".$logMessage);
}

return("ACCEPTED");
} catch (\Exception $e) {
    DB::rollBack();
    \Log::channel('payment')->error("Error in update_winpay: " . $e->getMessage());
    return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
}
}
}


    // ============================================================
    //  BUNDLE CALLBACK — dipakai oleh tripay, duitku, winpay
    // ============================================================
    private function processBundleCallback($bundle, float $paidAmount, string $source, string $noteStr)
    {
        $date = now()->toDateTimeString();
        $code = substr(md5(uniqid('', true)), 0, 10);

        \Log::channel('payment')->info("[{$source}] Bundle callback | bundle_ref={$bundle->bundle_ref} | paid={$paidAmount}");

        if ($bundle->status == 1) {
            \Log::channel('payment')->info("[{$source}] Bundle sudah dibayar: {$bundle->bundle_ref}");
            return response()->json(['success' => true, 'message' => 'Already paid']);
        }

        $items = \DB::table('payment_bundle_items')
            ->where('bundle_ref', $bundle->bundle_ref)
            ->pluck('suminvoice_id');

        if ($items->isEmpty()) {
            \Log::channel('payment')->error("[{$source}] Bundle items kosong: {$bundle->bundle_ref}");
            return response()->json(['success' => false, 'message' => 'Bundle items not found'], 404);
        }

        $invoices = \App\Suminvoice::whereIn('id', $items)->lockForUpdate()->get();
        if ($invoices->isEmpty()) {
            \Log::channel('payment')->error("[{$source}] Invoices tidak ditemukan untuk bundle: {$bundle->bundle_ref}");
            return response()->json(['success' => false, 'message' => 'Invoices not found'], 404);
        }

        $customer = \App\Customer::where('id', $bundle->id_customer)->first();
        if (!$customer) {
            \Log::channel('payment')->error("[{$source}] Customer tidak ditemukan: id={$bundle->id_customer}");
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $paymentPoint = match($source) {
            'TRIPAY' => tenant_config('PAYMENT_POINT_TRIPAY', '1-10039'),
            'WINPAY' => tenant_config('PAYMENT_POINT_WINPAY', '1-10040'),
            'DUITKU' => tenant_config('PAYMENT_POINT_DUITKU', '1-10039'),
            default  => '1-10039',
        };

        DB::beginTransaction();
        try {
            foreach ($invoices as $invoice) {
                $invoice->update([
                    'recieve_payment' => $invoice->total_amount,
                    'payment_point'   => $paymentPoint,
                    'note'            => $noteStr . ' | bundle:' . $bundle->bundle_ref,
                    'updated_by'      => $source,
                    'payment_status'  => 1,
                    'payment_date'    => $date,
                    'payment_gateway' => strtolower($source),
                ]);

                $reff = $invoice->tempcode . 'receive';
                if (!\App\Jurnal::where('reff', $reff)->exists()) {
                    \App\Jurnal::create([
                        'date'        => $date,
                        'reff'        => $reff,
                        'type'        => 'jumum',
                        'description' => 'Receive Payment #' . $invoice->number . ' | ' . $customer->name,
                        'note'        => 'Receive Payment ' . $source . ' BUNDLE #' . $invoice->number . ' | ' . $customer->customer_id . ' | ' . $customer->name,
                        'id_akun'     => $paymentPoint,
                        'debet'       => $invoice->total_amount,
                        'contact_id'  => $customer->id,
                        'code'        => $code,
                    ]);
                    \App\Jurnal::create([
                        'date'        => $date,
                        'reff'        => $reff,
                        'type'        => 'jumum',
                        'description' => 'Receive Payment #' . $invoice->number . ' | ' . $customer->name,
                        'note'        => 'Receive Payment ' . $source . ' BUNDLE #' . $invoice->number . ' | ' . $customer->customer_id . ' | ' . $customer->name,
                        'id_akun'     => '1-10100',
                        'kredit'      => $invoice->total_amount,
                        'contact_id'  => $customer->id,
                        'code'        => $code,
                    ]);
                }
            }

            // Update bundle status
            \DB::table('payment_bundles')->where('bundle_ref', $bundle->bundle_ref)->update([
                'status'      => 1,
                'paid_amount' => $paidAmount,
                'updated_at'  => now(),
            ]);

            // Re-aktifkan customer jika tidak ada unpaid invoice
            if ($customer->id_status == 4 && \App\Suminvoice::where('payment_status', 0)->where('id_customer', $customer->id)->count() <= 0) {
                \App\Customer::where('id', $customer->id)->update(['id_status' => 2]);
                if ($distrouter = \App\Distrouter::withTrashed()->where('id', $customer->id_distrouter)->first()) {
                    try {
                        EnableMikrotikJob::dispatch($customer->id)->onQueue(app('tenant')['domain'] ?? 'default')->delay(now()->addSeconds(2));
                    } catch (\Exception $e) {
                        \Log::channel('payment')->warning("Gagal mikrotik_enable bundle untuk {$customer->customer_id}: " . $e->getMessage());
                    }
                }
            }

            DB::commit();

            // Kirim notifikasi (gabung semua nomor invoice)
            $invoiceNumbers = $invoices->pluck('number')->implode(', ');
            $this->sendPaymentNotification($customer, $invoiceNumbers, $paidAmount, $source . '/BUNDLE');

            \Log::channel('payment')->info("[{$source}] Bundle berhasil diproses: {$bundle->bundle_ref} | " . $invoices->count() . " invoices");
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::channel('payment')->error("[{$source}] Bundle callback error: {$bundle->bundle_ref} | " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Kirim notifikasi pembayaran ke customer sesuai preferensi (notification field).
     * 0=none, 1=WA, 2=Email, 3=FCM (Mobile App)
     */
    private function sendPaymentNotification($customers, $invoiceNumber, $amount, $source = '')
    {
        try {
            $encryptedurl  = Crypt::encryptString($customers->id);
            $jumlah_rupiah = number_format($amount, 0, ',', '.');
            $openUrl       = '/invoice/cst/' . $encryptedurl;

            if ($customers->notification == 1) {
                // ── WhatsApp ──────────────────────────────────────────────
                $waProvider = tenant_config('wa_provider', 'gateway');

                if ($waProvider === 'qontak') {
                    qontak_whatsapp_helper_receive_payment_confirmation(
                        $customers->phone,
                        $customers->name,
                        $invoiceNumber,
                        $customers->customer_id,
                        $amount,
                        $openUrl
                    );
                } else {
                    $message  = "Yth. " . $customers->name . "\n";
                    $message .= "\nTerimakasih, Pembayaran tagihan Customer dengan CID *" . $customers->customer_id . "* sudah kami *TERIMA*";
                    $message .= "\nTagihan  : *#" . $invoiceNumber . "*";
                    $message .= "\nJumlah   : *Rp." . $jumlah_rupiah . "*";
                    $message .= "\nVia      : " . $source;
                    $message .= "\n\nUntuk info lebih lengkap silahkan klik link:";
                    $message .= "\nhttp://" . tenant_config('domain_name', env("DOMAIN_NAME")) . $openUrl;
                    $message .= "\n\n" . config("app.signature");
                    WaGatewayHelper::wa_payment($customers->phone, $message);
                }

            } elseif ($customers->notification == 2) {
                // ── Email ─────────────────────────────────────────────────
                if (!empty($customers->email)) {
                    $data = [
                        'phone'        => $customers->phone,
                        'name'         => $customers->name,
                        'number'       => '#' . $invoiceNumber,
                        'customer_id'  => $customers->customer_id,
                        'total_amount' => $amount,
                        'url'          => $openUrl,
                    ];
                    Mail::to($customers->email)->send(new EmailReceivePayment($data));
                }

            } elseif ($customers->notification == 3) {
                // ── FCM Push Notification (Mobile App) ────────────────────
                $fcmTitle = '✅ Pembayaran Diterima';
                $fcmBody  = 'Tagihan #' . $invoiceNumber . ' sebesar Rp.' . $jumlah_rupiah . ' telah kami terima. Terima kasih!';

                if (!empty($customers->fcm_token)) {
                    try {
                        FcmService::send(
                            $customers->fcm_token,
                            $fcmTitle,
                            $fcmBody,
                            [
                                'type'        => 'payment_received',
                                'customer_id' => $customers->customer_id,
                                'open_url'    => $openUrl,
                            ]
                        );
                    } catch (\App\Exceptions\FcmTokenUnregisteredException $eFcmUnreg) {
                        $customers->fcm_token = null;
                        $customers->save();
                        Log::warning('[FCM] Token UNREGISTERED — dihapus dari DB | CID ' . $customers->customer_id);
                    }
                }

                // Simpan ke riwayat notifikasi app
                AppCustomerNotification::record(
                    (int) $customers->id,
                    $fcmTitle,
                    $fcmBody,
                    'payment_received',
                    $openUrl
                );
            }
            // notification == 0 → tidak ada notifikasi

        } catch (\Exception $e) {
            Log::error("[{$source}] sendPaymentNotification error for {$customers->customer_id}: " . $e->getMessage());
        }
    }

    /**
     * Callback dari Duitku.
     * Dipanggil server Duitku setelah pembayaran berhasil.
     * Docs: https://docs.duitku.com/api/id/#callback
     */
    public function update_duitku(Request $request)
    {
        $date        = now()->toDateTimeString();
        $number      = $request->merchantOrderId;  // ini = suminvoice.number
        $amount      = $request->amount;
        $merchantCode= $request->merchantCode;
        $resultCode  = $request->resultCode;
        $reference   = $request->reference;
        $signature   = $request->signature;
        $paymentCode = $request->paymentCode ?? '';
        $updatedBy   = 'DUITKU';
        $msg         = '';
        $changes     = [];
        $code        = substr(md5(uniqid('', true)), 0, 10);

        \Log::channel('payment')->debug('=== Callback received from Duitku ===', [
            'merchantOrderId' => $number,
            'resultCode'      => $resultCode,
            'amount'          => $amount,
            'reference'       => $reference,
        ]);

        // Verifikasi signature
        $gw     = \App\PaymentGateway::findForCurrentTenant('duitku');
        $apiKey = ($gw->settings['api_key'] ?? null) ?: tenant_config('DUITKU_API_KEY', env('DUITKU_API_KEY'));

        $calcSig = md5($merchantCode . $amount . $number . $apiKey);
        if ($signature !== $calcSig) {
            \Log::channel('payment')->warning('Duitku callback: Bad signature', ['number' => $number]);
            return response()->json(['error' => 'Bad Signature'], 401);
        }

        // Hanya proses jika resultCode 00 (success)
        if ($resultCode !== '00') {
            \Log::channel('payment')->info('Duitku callback: resultCode bukan 00, diabaikan.', ['resultCode' => $resultCode, 'number' => $number]);
            return response()->json(['success' => false, 'message' => 'Payment not completed']);
        }

        $cekstatus = \App\Suminvoice::where('number', $number)->first();
        if (!$cekstatus) {
            // Cek apakah ini bundle payment
            $bundle = \DB::table('payment_bundles')->where('bundle_ref', $number)->first();
            if ($bundle) {
                $noteStr = 'DUITKU ' . ($paymentCode) . ' | ref:' . ($reference) . ' | Rp' . number_format($amount, 0, ',', '.');
                return $this->processBundleCallback($bundle, (float)$amount, 'DUITKU', $noteStr);
            }
            \Log::channel('payment')->error('Duitku: Invoice tidak ditemukan: ' . $number);
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        if ($cekstatus->payment_status == 1) {
            \Log::channel('payment')->info('[DUITKU] INV no: ' . $number . ' | Sudah dibayar, tidak diproses ulang.');
            return response()->json(['success' => true]);
        }

        DB::beginTransaction();
        try {
            $invoice = \App\Suminvoice::where('number', $number)->lockForUpdate()->first();
            if (!$invoice) throw new \Exception('Invoice tidak ditemukan setelah lock: ' . $number);

            $customers = \App\Customer::where('id', $invoice->id_customer)->first();
            if (!$customers) throw new \Exception('Customer tidak ditemukan untuk invoice: ' . $number);

            $oldStatus = $customers->status_name->name ?? 'Unknown';

            $invoice->update([
                'recieve_payment' => $amount,
                'payment_point'   => tenant_config('PAYMENT_POINT_DUITKU', '1-10039'),
                'note'            => 'DUITKU ' . $paymentCode . ' | ref: ' . $reference . ' | Rp' . number_format($amount, 0, ',', '.'),
                'updated_by'      => 'DUITKU',
                'payment_status'  => 1,
                'payment_date'    => $date,
                'payment_gateway' => 'duitku',
            ]);

            $this->sendPaymentNotification($customers, $number, $amount, 'DUITKU');

            // Entri Jurnal Keuangan
            $reff = $invoice->tempcode . 'receive';
            if (!\App\Jurnal::where('reff', $reff)->exists()) {
                \App\Jurnal::create([
                    'date' => $date, 'reff' => $reff,
                    'type' => 'jumum', 'description' => 'Receive Payment #' . $number . ' | ' . $customers->name,
                    'note' => 'Receive Payment DUITKU #' . $number . ' | ' . $customers->customer_id . ' | ' . $customers->name,
                    'id_akun' => '1-10039', 'debet' => $amount,
                    'contact_id' => $customers->id, 'code' => $code,
                ]);

                if ((float)$amount < (float)$invoice->total_amount) {
                    \App\Jurnal::create([
                        'date' => $date, 'reff' => $reff,
                        'type' => 'jumum', 'description' => 'Receive Payment #' . $number . ' | ' . $customers->name,
                        'note' => 'Receive Payment DUITKU #' . $number . ' | ' . $customers->customer_id . ' | ' . $customers->name,
                        'id_akun' => '6-60249', 'debet' => $invoice->total_amount - $amount,
                        'contact_id' => $customers->id, 'code' => $code,
                    ]);
                }

                \App\Jurnal::create([
                    'date' => $date, 'reff' => $reff,
                    'type' => 'jumum', 'description' => 'Receive Payment #' . $number . ' | ' . $customers->name,
                    'note' => 'Receive Payment DUITKU #' . $number . ' | ' . $customers->customer_id . ' | ' . $customers->name,
                    'id_akun' => '1-10100', 'kredit' => $invoice->total_amount,
                    'contact_id' => $customers->id, 'code' => $code,
                ]);
            }

            // Re-aktifkan customer jika tidak ada unpaid invoice
            if ($customers->id_status == 4 && \App\Suminvoice::where('payment_status', 0)->where('id_customer', $invoice->id_customer)->count() <= 0) {
                \App\Customer::where('id', $invoice->id_customer)->update(['id_status' => 2]);
                if ($distrouter = \App\Distrouter::withTrashed()->where('id', $customers->id_distrouter)->first()) {
                    try {
                        \App\Jobs\EnableMikrotikJob::dispatch($customers->id)->onQueue(app('tenant')['domain'] ?? 'default')->delay(now()->addSeconds(2));
                    } catch (\Exception $e) {
                        \Log::channel('payment')->warning('Duitku: Gagal mikrotik_enable untuk ' . $customers->customer_id . ': ' . $e->getMessage());
                    }
                }
                $changes = ['Status' => ['old' => $oldStatus, 'new' => 'Active']];
                $msg = 'Diaktifkan kembali karena tidak ada invoice unpaid.';
            }

            DB::commit();

            $logMessage = now() . ' - ' . $customers->name . ' updated by ' . $updatedBy . ' - Changes: ' . json_encode($changes) . PHP_EOL;

            if (!empty($changes)) {
                \App\Customerlog::create([
                    'id_customer' => $customers->id, 'date' => now(),
                    'updated_by' => $updatedBy, 'topic' => 'payment',
                    'updates' => json_encode($changes),
                ]);
            }

            \Log::channel('payment')->info('[DUITKU] Payment OK | CID: ' . $customers->customer_id . ' | INV: ' . $number . ' | ' . $msg);
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::channel('payment')->error('Error in update_duitku: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */



    public function destroy($id)
    {
        //
    }




}
