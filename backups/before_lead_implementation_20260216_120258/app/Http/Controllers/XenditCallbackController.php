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
use App\Jobs\EnableMikrotikJob; 


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
            'recieve_payment' => $request->paid_amount,
            'payment_point' => $request->bank_code,
            'note' => $request->payment_method,
            'updated_by' => $request->merchant_name,
            'payment_status' =>1,
            'payment_date' =>now()->toDateTimeString(),


        ]);
         if($query)
         {

            $invoice =\App\Suminvoice::where('payment_id', $id_xendit)->first();
            
            $customers = \App\Customer::Where('id',$invoice->id_customer)->first();
            
            // Check WA Provider from tenant config
            $waProvider = tenant_config('wa_provider', 'gateway');
            $oldStatus = $customers->status_name->name ?? 'Unknown';
            $totalamountandfee = $request->paid_amount;
            
            if ($waProvider === 'qontak') {
                // Use Qontak WhatsApp API
                qontak_whatsapp_helper_receive_payment_confirmation(
                    $customers->phone, 
                    $customers->name, 
                    $invoice->number, 
                    $customers->customer_id, 
                    $totalamountandfee, 
                    "/invoice/cst/" . Crypt::encryptString($customers->id)
                );
            } else {
                // Use Regular WA Gateway
                $message ="Yth. ".$customers->name." ";
                $message .="\n";
                $message .="\nTerimakasih, Pembayaran tagihan Customer dengan CID ".$customers->customer_id." sudah kami *TERIMA* ";
                $message .="\nUntuk info lebih lengkap silahkan klik link";
                $message .="\nhttp://".tenant_config('domain_name', env("DOMAIN_NAME"))."/suminvoice/".$invoice->tempcode."/print";
                $message .="\n".tenant_config('signature', env('SIGNATURE', 'Payment System'))."";
                
                $msg = \App\Suminvoice::wa_payment($customers->phone,$message);
            }



            $active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
            ->where ('id_customer', '=', $invoice->id_customer )
            ->count();
            
            if (($customers->id_status ==4 ) AND ($active_invoice <= 0 ))
            {

             $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
             \App\Customer::where('id', $invoice->id_customer)->update([
                'id_status' => 2 ]);
               // \App\Distrouter::mikrotik_enable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->customer_id);
             EnableMikrotikJob::dispatch($customers->id)->delay(now()->addSeconds(2));

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
                'payment_point'   => '1-10039',
                'note'            => $request->payment_method . ' (' . $request->amount_received . '+' . $request->total_fee . '->' . $request->total_amount . ')',
                'updated_by'      => 'TRIPAY',
                'payment_status'  => 1,
                'payment_date'    => now()->toDateTimeString(),
            ]);

            $oldStatus = $customers->status_name->name ?? 'Unknown';
            $totalamountandfee = $request->amount_received + $request->fee_merchant;
            
            // Check WA Provider from tenant config
            $waProvider = tenant_config('wa_provider', 'gateway');
            
            if ($waProvider === 'qontak') {
                // Use Qontak WhatsApp API
                qontak_whatsapp_helper_receive_payment_confirmation(
                    $customers->phone, 
                    $customers->name, 
                    $number, 
                    $customers->customer_id, 
                    $totalamountandfee, 
                    "/invoice/cst/" . Crypt::encryptString($customers->id)
                );
            }
            // Note: Untuk gateway biasa, notifikasi sudah dikirim via helper function process_payment_success

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
               EnableMikrotikJob::dispatch($customers->id)->delay(now()->addSeconds(2));
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
           EnableMikrotikJob::dispatch($customers->id)->delay(now()->addSeconds(2));
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
                'payment_point' => '1-10040',
                'note' => $channel. ' | ' . $amount_received - 2500 . '+ 2500 - ( Fee ' . $payload['fee'].')',
                'updated_by' => 'WINPAY',
                'payment_status' => 1,
                'payment_date' => now()->toDateTimeString(),
            ]);



            $oldStatus = $customers->status_name->name ?? 'Unknown';
            $totalamountandfee = $amount_received;
            
            // Check WA Provider from tenant config
            $waProvider = tenant_config('wa_provider', 'gateway');
            
            if ($waProvider === 'qontak') {
                // Use Qontak WhatsApp API
                qontak_whatsapp_helper_receive_payment_confirmation(
                    $customers->phone, 
                    $customers->name, 
                    $number, 
                    $customers->customer_id, 
                    $amount_received, 
                    "/invoice/cst/" . Crypt::encryptString($customers->id)
                );
            }
            // Note: Untuk gateway biasa, notifikasi sudah dikirim via helper function process_payment_success

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
               
               EnableMikrotikJob::dispatch($customers->id)->delay(now()->addSeconds(2));
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

           EnableMikrotikJob::dispatch($customers->id)->delay(now()->addSeconds(2));
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
