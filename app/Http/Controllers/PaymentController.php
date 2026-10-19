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
use Illuminate\Support\Facades\DB;
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
        $this->middleware('checkPrivilege:admin,accounting,payment,merchant');
    }
    public function show(Request $request)
    {
        // Hanya kolom yang ada di form pencarian — nama kolom tidak boleh diambil mentah dari input.
        $filter = in_array($request->filter, ['customer_id', 'name', 'phone'], true) ? $request->filter : 'customer_id';
        $val = trim((string) $request->parameter);

        // No. telepon dicocokkan tanpa peduli format (0 / 62 / +62 / spasi / strip), kolom lain persis.
        $applyFilter = function ($query) use ($filter, $val) {
            return $filter === 'phone'
                ? $query->wherePhone($val)
                : $query->where('customers.' . $filter, $val);
        };

        $customer_count = $applyFilter(\App\Customer::query())->get();

        if ($customer_count->count() > 1)
        {
            return view ('payment/index',['customer' =>$customer_count]);
        }
        else
        {

            $customer = $applyFilter(\App\Customer::query())

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
                
                



                // Invoice belum bayar yang terbit paling awal — hanya ini yang boleh dibayar lebih dulu
                $nextUnpaid = \App\Suminvoice::where('id_customer', $customer->id)
                    ->where('payment_status', 0)
                    ->orderBy('date', 'asc')->orderBy('id', 'asc')
                    ->first();

                // Ringkasan semua tagihan belum dibayar (tabel hanya menampilkan 15 invoice terakhir)
                $unpaidSummary = \App\Suminvoice::where('id_customer', $customer->id)
                    ->where('payment_status', 0)
                    ->selectRaw('COUNT(*) AS n, COALESCE(SUM(total_amount), 0) AS total')
                    ->first();

                return view ('payment/show',['suminvoice' =>$suminvoice, 'customer'=>$customer, 'nextUnpaid' => $nextUnpaid, 'unpaidSummary' => $unpaidSummary]);
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
        ->orderBy('date', 'asc')->orderBy('id', 'asc')->first();
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
        ->orderBy('date', 'asc')->orderBy('id', 'asc')->first();
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
    $suminvoice_amountdue = \App\Suminvoice::where('id_customer','=', $invoice_code->id_customer )
    ->where('payment_status', '=', 0)
    ->sum('total_amount');


    return view ('payment/print',['invoice' =>$invoice, 'suminvoice_amountdue'=>$suminvoice_amountdue, 'customer'=>$customer, 'bank'=>$bank, 'suminvoice_number' => $suminvoice_number, 'current_inv_status' => $current_inv_status, 'encryptedurl'=>$encryptedurl, 'companyAddress1'=>$companyAddress1, 'companyAddress2'=>$companyAddress2,'companyName'=>$companyName, 'appUrl' =>$appUrl, 'signature' =>$signature, 'admin_fee' =>$admin_fee, 'invNote'=>$invNote]);   
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

    $transactionCount = (int) $suminvoices->count();
    $totalReceivePayment = (float) $suminvoices->sum('recieve_payment');
    $totalMerchantFee = (float) $suminvoices->sum('merchant_fee');

    $merchantCashValue = 0.0;
    $merchantCashAccounts = collect();
    $merchantCashBreakdown = collect();
    $merchantLiabilityBreakdown = collect();
    $merchantLiabilityValue = 0.0;

    $user = auth()->user();
    if ($user) {
        $merchantCashAccounts = $user->akuns()
            ->where('category', 'kas & bank')
            ->orderBy('akun_code', 'asc')
            ->get(['akuns.akun_code', 'akuns.name']);

        $cashAccountCodes = $merchantCashAccounts->pluck('akun_code')->filter()->values();
        if ($cashAccountCodes->isNotEmpty()) {
            $saldoByAccount = DB::table('jurnals')
                ->whereIn('id_akun', $cashAccountCodes)
                ->select('id_akun', DB::raw('COALESCE(SUM(debet - kredit), 0) as saldo'))
                ->groupBy('id_akun')
                ->pluck('saldo', 'id_akun');

            $merchantCashBreakdown = $merchantCashAccounts->map(function ($account) use ($saldoByAccount) {
                $saldo = (float) ($saldoByAccount[$account->akun_code] ?? 0);

                return [
                    'akun_code' => $account->akun_code,
                    'akun_name' => $account->name,
                    'saldo' => $saldo,
                ];
            });

            $merchantCashValue = (float) DB::table('jurnals')
                ->whereIn('id_akun', $cashAccountCodes)
                ->selectRaw('COALESCE(SUM(debet - kredit), 0) as saldo')
                ->value('saldo');
        }

        $liabilityCodes = collect();

        if (!empty($user->hutang_akun_code)) {
            $liabilityCodes->push($user->hutang_akun_code);
        }

        if (!empty($user->id_merchant)) {
            $merchant = \App\Merchant::find($user->id_merchant);
            if (!empty($merchant?->hutang_akun_code)) {
                $liabilityCodes->push($merchant->hutang_akun_code);
            }
        }

        $liabilityCodes = $liabilityCodes->filter()->unique()->values();
        if ($liabilityCodes->isNotEmpty()) {
            $liabilityAccounts = DB::table('akuns')
                ->whereIn('akun_code', $liabilityCodes)
                ->select('akun_code', 'name')
                ->orderBy('akun_code', 'asc')
                ->get();

            $liabilitySaldoByAccount = DB::table('jurnals')
                ->whereIn('id_akun', $liabilityCodes)
                ->select('id_akun', DB::raw('COALESCE(SUM(kredit - debet), 0) as saldo_hutang'))
                ->groupBy('id_akun')
                ->pluck('saldo_hutang', 'id_akun');

            $merchantLiabilityBreakdown = collect($liabilityAccounts)->map(function ($account) use ($liabilitySaldoByAccount, $user) {
                $source = (!empty($user->hutang_akun_code) && $user->hutang_akun_code === $account->akun_code)
                    ? 'Kasir User'
                    : 'Merchant';

                return [
                    'akun_code' => $account->akun_code,
                    'akun_name' => $account->name,
                    'saldo' => (float) ($liabilitySaldoByAccount[$account->akun_code] ?? 0),
                    'source' => $source,
                ];
            });

            $merchantLiabilityValue = (float) $merchantLiabilityBreakdown->sum('saldo');
        }
    }

    // Data grafik tren volume pembayaran harian (jumlah transaksi per hari)
    $chartData = $suminvoices->groupBy(function ($item) {
        return Carbon::parse($item->payment_date)->format('Y-m-d');
    })->map(function ($group) {
        return $group->count();
    });

    $chartLabels = [];
    $chartVolumes = [];
    for ($date = $date_from->copy(); $date->lte($date_end); $date->addDay()) {
        $label = $date->format('Y-m-d');
        $chartLabels[] = $label;
        $chartVolumes[] = (int) ($chartData[$label] ?? 0);
    }

    return view('payment.mytransaction', compact(
        'suminvoices',
        'date_from',
        'date_end',
        'chartLabels',
        'chartVolumes',
        'transactionCount',
        'totalReceivePayment',
        'totalMerchantFee',
        'merchantCashValue',
        'merchantCashAccounts',
        'merchantCashBreakdown',
        'merchantLiabilityBreakdown',
        'merchantLiabilityValue'
    ));
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
