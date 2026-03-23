<?php

namespace App\Http\Controllers;

use App\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Suminvoice;
use App\Invoice;
use \Auth;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\MyTransactionExport;
use Illuminate\Support\Facades\Log;
class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkPrivilege:admin,accounting,payment');
    }
    public function show(Request $request)
    {
        $filter ='customers.'.$request->filter;
        $val =$request->parameter ;
        $customer_count = \App\Customer::where($filter,$val)->get();

        if ($customer_count->count() > 1)
        {
            return view ('payment/index',['customer' =>$customer_count]);
        }
        else
        {

            $customer = \App\Customer::where($filter,$val)

            ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
            ->Join('plans', 'customers.id_plan', '=', 'plans.id')
            ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')->first();

            if ($customer == null)
            {
                return redirect ('/payment')->with('error',' Data client '.$val.' Tidak Ditemukan !!');
            }
            else
            {
                $suminvoice = \App\Suminvoice::where('id_customer', $customer->id)
                ->orderBy('date','DESC')
                ->limit(15)
                ->get();
                
                



                return view ('payment/show',['suminvoice' =>$suminvoice, 'customer'=>$customer]);
            }
        }
    }
    public function search()
    {
        return view ('payment/search');
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
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */


    public function countershow($id)
    {
        //

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




    return view ('payment/countershow',['invoice' =>$invoice, 'customer'=>$customer, 'bank'=>$bank, 'suminvoice_number' => $suminvoice_number, 'current_inv_status'=>$current_inv_status]);
}

}
public function edit(Payment $payment)
{
        //
}

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        //
    }


    public function print($id)
    {
        //
        $result = "";
        $resultwinpay="";
        $companyName = tenant_config('APP_NAME', config('app.name', 'INTERNET SERVICE PROVIDER'));
        $companyAddress1 = tenant_config('COMPANY_ADDRESS1', env('COMPANY_ADDRESS1', ''));
        $companyAddress2 = tenant_config('COMPANY_ADDRESS2', env('COMPANY_ADDRESS2', ''));
        $invNote = tenant_config('inv_note', env('INV_NOTE', ''));
        $appUrl = tenant_config('APP_URL', config('app.url', 'https://google'));
        $signature = tenant_config('SIGNATURE', config('app.signature', ''));
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
       if ($suminvoice_number->payment_status == 0 && !is_null(Auth::user()->admin_fee))
       {
         $admin_fee  = Auth::user()->admin_fee      ?? 0;
     }
     else
        {$admin_fee  = $suminvoice_number->merchant_fee ?? 0;}

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

        $deleteResponse = app(\App\Http\Controllers\SuminvoiceController::class)->deleteWinpayVA($suminvoice_number->number);

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
        $response = app(\App\Http\Controllers\SuminvoiceController::class)->findWinpayVA($suminvoice_number->id);

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


    return view ('payment/print',['invoice' =>$invoice, 'suminvoice_amountdue'=>$suminvoice_amountdue, 'customer'=>$customer, 'bank'=>$bank, 'suminvoice_number' => $suminvoice_number, 'current_inv_status' => $current_inv_status, 'encryptedurl'=>$encryptedurl, 'result'=>$result,'resultwinpay'=>$resultwinpay, 'merchants'=>$merchants, 'companyAddress1'=>$companyAddress1, 'companyAddress2'=>$companyAddress2,'companyName'=>$companyName, 'appUrl' =>$appUrl, 'signature' =>$signature, 'admin_fee' =>$admin_fee, 'invNote'=>$invNote]);   
}
}


public function dotmatrix($id)
{
        //
    $bank = \App\Bank::pluck('name', 'id');
    $mount = now()->format('mY');
    $companyName = tenant_config('APP_NAME', env('COMPANY', 'INTERNET SERVICE PROVIDER'));
    $appUrl = tenant_config('APP_URL', env('APP_URL', 'https://google'));
    $signature = tenant_config('SIGNATURE', config('app.signature', ''));
    $invNote = tenant_config('inv_note', env('INV_NOTE', ''));
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

     if ($suminvoice_number->payment_status == 0 && !is_null(Auth::user()->admin_fee))
     {
       $admin_fee  = Auth::user()->admin_fee      ?? 0;
   }
   else
     {$admin_fee  = $suminvoice_number->merchant_fee ?? 0;}


 $customer = \App\Customer::where('customers.id', $invoice_code->id_customer)

 ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
 ->Join('plans', 'customers.id_plan', '=', 'plans.id')
 ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')
 ->withTrashed()
 ->first();
 return view ('payment/dotmatrix',['invoice' =>$invoice, 'customer'=>$customer, 'bank'=>$bank, 'suminvoice_number' => $suminvoice_number, 'admin_fee' => $admin_fee, 'companyName' => $companyName, 'appUrl' => $appUrl, 'signature'=>$signature, 'invNote'=>$invNote]);
}
}



public function mytransaction(Request $request)
{
    $date_from = $request->input('date_from')
    ? Carbon::parse($request->input('date_from'))->startOfDay()
    : Carbon::now()->startOfMonth()->startOfDay();

    $date_end = $request->input('date_end')
    ? Carbon::parse($request->input('date_end'))->endOfDay()
    : Carbon::now()->endOfDay();

    $suminvoices = Suminvoice::with(['user', 'customer', 'kasbank'])
    ->where('updated_by', auth()->id())
    ->whereBetween('payment_date', [$date_from, $date_end])
    ->orderBy('payment_date', 'ASC')
    ->get();

        // Data untuk grafik (group by tanggal)
    $chartData = $suminvoices->groupBy(function ($item) {
        return Carbon::parse($item->payment_date)->format('Y-m-d');
    })->map(function ($group) {
        return $group->sum('recieve_payment');
    });

    return view('payment.mytransaction', compact('suminvoices', 'date_from', 'date_end', 'chartData'));
}

// ✅ Export PDF (mengikuti filter tanggal)
public function exportPdf(Request $request)
{
    $date_from = $request->query('date_from') 
    ? Carbon::parse($request->query('date_from'))->startOfDay() 
    : Carbon::now()->startOfMonth();

    $date_end = $request->query('date_end') 
    ? Carbon::parse($request->query('date_end'))->endOfDay() 
    : Carbon::now()->endOfDay();

    $data = Suminvoice::with(['user', 'customer', 'kasbank'])
    ->where('updated_by', auth()->id())
    ->whereBetween('payment_date', [$date_from, $date_end])
    ->orderBy('payment_date', 'ASC')
    ->get();

    $pdf = Pdf::loadView('payment.export-pdf', compact('data', 'date_from', 'date_end'))
    ->setPaper('a4', 'landscape');
    return $pdf->download('MyTransaction_' . now()->format('Ymd_His') . '.pdf');
}

    // ✅ Export Excel (mengikuti filter tanggal)
public function exportExcel(Request $request)
{
    $date_from = $request->query('date_from');
    $date_end = $request->query('date_end');
    return Excel::download(new MyTransactionExport($date_from, $date_end), 'MyTransaction_' . now()->format('Ymd_His') . '.xlsx');
}



}
