<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Exception\GuzzleException;
Use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Xendit\Xendit;
use Illuminate\Database\Eloquent\SoftDeletes;
class Suminvoice extends Model
{
    //
    //use softDeletes;
    protected $fillable =['id','id_customer','number','date','tax','pph','verify','tempcode','payment_status','updated_by','created_at','deleted_at','file','total_amount','recieve_payment','payment_point','payment_id','note','payment_date','due_date','merchant_fee'];
    public static  function countinv($id)
    {


        return static::where('id_customer', $id)
        ->where('payment_status', 0)
        ->count();

    }
    public static  function balanceinv($id_customer)
    {
     // $balance = $this->select(\DB::raw('SUM(total_amount) as total'))
     // ->where('id_customer', '=', $id_customer)
     // ->where('payment_status', '=', 0)
     // ->get();

     //     //return $balance[0];


     // return($balance);

        return static::where('id_customer', $id_customer)
        ->where('payment_status', 0)
        ->sum('total_amount');
    }
    public function customer()
    {
        return $this->belongsTo('\App\Customer', 'id_customer')->withTrashed();
    }

    public function invoice()
    {
     return $this->hasMany(Invoice::class, 'tempcode', 'tempcode');
 }


 public function bank()
 {
    return $this->belongsTo('\App\Bank', 'payment_point');

}
public function kasbank()
{
    return $this->belongsTo(\App\Akun::class, 'payment_point', 'akun_code');
}
public function user()
{
    return $this->belongsTo('\App\User', 'updated_by')->withTrashed();
}
public static function wa_payment_g($phone, $message)
{
 if (env('WAPISENDER_STATUS')!="disable")
 {

    $client = new Client(); 
    $result = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
        'form_params' => [
                // 'api_key' => env('WAPISENDER_KEY'),
                // 'device_key' => env('WAPISENDER_PAYMENT'),
                // 'group_id' =>env('WAPISENDER_GROUPPAYMENT'),

         'token' => env('WAPISENDER_KEY'),
         'number' =>env('WAPISENDER_GROUPPAYMENT'),

         'message' => $message,
     ]
 ]);

       // echo $result->getStatusCode();
        // 200
    $result= $result->getBody();
    $array = json_decode($result, true);
//        return ( $array['status'].' - '.$array['message']);
    return ( $array['status']);
}
else
{
    return "WA Disabled";
}
}

public static function wa_payment($phone, $message)
{
 if (env('WAPISENDER_STATUS')!="disable")
 {

    $client = new Client();
    $nohp = $phone;
    if(substr(trim($nohp), 0, 2)=="62"){
        $hp    =trim($nohp);
    }
            // cek apakah no hp karakter ke 1 adalah angka 0
    else if(substr(trim($nohp), 0, 1)=="0"){
        $hp    ="62".substr(trim($nohp), 1);
    } 
    else if(substr(trim($nohp), 0, 3)=="+62"){
        $hp="62".substr(trim($nohp), 1);
    }
    else
    {
        $hp=$nohp;
    }
    $result = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
        'form_params' => [
                // 'api_key' => env('WAPISENDER_KEY'),
                // 'device_key' => env('WAPISENDER_PAYMENT'),
                // 'destination' => $hp,
         'token' => env('WAPISENDER_KEY'),
         'number' => $hp,
         'message' => $message,
     ]
 ]);

       // echo $result->getStatusCode();
        // 200
    $result= $result->getBody();
    $array = json_decode($result, true);
//        return ( $array['status'].' - '.$array['message']);
    return ( $array['status']);
}
else
{
    return "WA Disabled";
}
}

public static function wa_payment_qontak($phone, $message)
{
    if (env('WAPISENDER_STATUS') !== "disable") {
        try {
            $client = new Client();

            // Format nomor HP ke internasional (hapus + jika ada)
            $phone = ltrim(trim($phone), '+');

            // Data yang dikirim ke API WhatsApp Resmi
            $payload = [
                "to_number" => $phone,
                "to_name" => $name,
                "message_template_id" => env('WHATSAPP_TEMPLATE_ID'),
                "channel_integration_id" => env('WHATSAPP_CHANNEL_ID'),
                "language" => ["code" => "id"],
                "parameters" => [
                    "body" => [
                        ["key" => "1", "value" => "name", "value_text" => $name],
                        ["key" => "2", "value" => "customer_id", "value_text" => $customer_id],
                        ["key" => "3", "value" => "invoice_no", "value_text" => $invoice_no],
                        ["key" => "4", "value" => "amount", "value_text" => "" . number_format($amount, 0, ',', '.')],
                        ["key" => "5", "value" => "billing_month", "value_text" => $billing_month],
                        ["key" => "6", "value" => "due_date", "value_text" => $due_date],
                        ["key" => "7", "value" => "payment_deadline", "value_text" => $payment_deadline]
                    ]
                ]
            ];

            // Kirim request ke API WhatsApp Resmi
            $response = $client->post(env('WHATSAPP_API_URL'), [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('WHATSAPP_ACCESS_TOKEN'),
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);

            // Decode hasil respons
            $result = json_decode($response->getBody(), true) ?? [];

            // Logging hasil untuk debug (opsional)
            Log::info("WA API Response: ", $result);

            return $result['status'] ?? 'Unknown Status';
        } catch (\Exception $e) {
            Log::error("WA API Error: " . $e->getMessage());
            return 'Error: ' . $e->getMessage();
        }
    }

    return "WA Disabled";
}

public static function xendit_create_invoice($id_customer, $cid, $email, $description, $amount)
{


    Xendit::setApiKey(env('XENDIT_KEY'));

    $params = [
        'external_id' => $cid,
        'payer_email' => 'test@gmail.com',
        'description' => $description,
        'amount' => $amount
    ];
//dd ($params);
    $createInvoice = \Xendit\Invoice::create($params);
    $array = json_decode(json_encode($createInvoice, true));
    //dd ($id_customer);
    \App\Suminvoice::where('id', $id_customer)
    ->update([

        'payment_id' => $array->id,


    ]);


}


}

