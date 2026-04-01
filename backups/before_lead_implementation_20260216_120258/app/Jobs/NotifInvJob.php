<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\EmailReminderInvJob;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Helpers\WaGatewayHelper;

class NotifInvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $phone;
    protected $name;
    protected $cid;
    protected $encryptedurl;


    public function __construct($phone, $name, $cid, $encryptedurl)
    {
        //
        $this->phone = $phone;
        $this->name = $name;
        $this->cid = $cid;
        $this->encryptedurl = $encryptedurl;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
       $response = null;
       $customer = \App\Customer::where('customer_id', $this->cid)->first();

       if (!$customer) {
        Log::channel('notif')->warning("Customer not found with CID: {$this->cid}");
        return;
    }

    if ($customer->notification == 1) {

        // Check WA Provider from tenant config
        $waProvider = tenant_config('wa_provider', 'gateway'); // default: gateway

        if ($waProvider === 'qontak') {
            // Use Qontak WhatsApp API
            $response = qontak_whatsapp_helper_job_remainder_inv(
                $this->phone,
                $this->name,
                $this->cid,
                $this->encryptedurl
            );
        } else {
            // Use Regular WA Gateway
            $message = "*[Pengingat Pembayaran Internet]*";
            $message .= "\n\n";
            $message .= "\nPelanggan Yth. ";
            $message .= "\n\n";
            $message .= "\nNama : " . $customer->name;
            $message .= "\nCID : " . $customer->customer_id ;
            $message .= "\nKami ingin mengingatkan bahwa tagihan Anda sudah tersedia";
            $message .= "\nAgar tetap bisa menikmati Layanan kami, mohon untuk menyelesaikan pembayaran tepat waktu.";
            $message .= "\n\n";
            $message .= "Untuk informasi lebih lanjut, silakan klik link berikut:";
            $message .= "\n" . "http://" . tenant_config('domain_name', env("DOMAIN_NAME")) . $this->encryptedurl;
            $message .= "\n\n";
            $message .= "Jika sudah melakukan pembayaran, abaikan pesan ini.";
            $message .= "\nJika ada pertanyaan, hubungi CS kami di ".tenant_config('payment_wa', env("PAYMENT_WA"));
            $message .= "\n\n";
            $message .= "".tenant_config('signature', env("SIGNATURE"))."";

            $msgresult = WaGatewayHelper::wa_payment($customer->phone, $message);
        }
     




 } elseif ($customer->notification == 2) {
        // Email Notification
    if (!empty($customer->email)) {
        $data = [
            'phone' => $this->phone,
            'name' => $this->name,
            'cid' => $this->cid,
            'url' => $this->encryptedurl,
        ];

        try {
            Mail::to($customer->email)->send(new EmailReminderInvJob($data));
            $response = 'Email sent';
        } catch (\Exception $e) {
            \Log::channel('notif')->error("Gagal kirim email ke {$customer->email}: " . $e->getMessage());
        }
        
    } else {
        Log::channel('notif')->warning("Email kosong untuk customer {$this->name}");
    }
}






\Log::channel('notif')->info('Sent Remainder message to  CID '.$this->cid. ' | ' .$this->name . ' | '. $response); 

}
}
