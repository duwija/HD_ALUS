<?php



namespace App\Http\Controllers;
use App\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use DataTables;

Use GuzzleHttp\Clients;

use Xendit\Xendit;
use Exception; 
use Illuminate\Support\Carbon;
class InvoiceController extends Controller
{
 public function __construct()
 {
  //  $this->middleware('auth');
  $this->middleware('auth', ['except' => ['custinv']]); 
  $this->middleware('checkPrivilege:admin,accounting,payment,noc', ['except' => ['custinv']]);

}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if ((Auth::user()->privilege)=="admin")

        {
// Mendapatkan tanggal hari ini
            $today = Carbon::today();

// Mendapatkan tanggal 6 bulan yang lalu
            $sixMonthsAgo = Carbon::today()->subMonths(6);

            $suminvoice = \App\Suminvoice::orderBy('id', 'DESC')
            ->whereBetween('date',[(date('y-m-1')), (date('y-m-d'))])
            ->get();
            $status = \App\Statuscustomer::pluck('name', 'id');
            $merchant = \App\Merchant::pluck('name', 'id');
            $groupedTransactions = \App\Suminvoice::whereBetween('payment_date', [$sixMonthsAgo, $today])
            ->groupBy('updated_by')
            ->get();


            return view ('suminvoice/invoice_list',['suminvoice' =>$suminvoice, 'status'=>$status,'groupedTransactions' =>$groupedTransactions, 'merchant'=>$merchant]);






        }
        else
        {
          return redirect()->back()->with('error','Sorry, You Are Not Allowed to Access Destination page !!');
      }
  }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */



//======================================================================================

//     public function table_invoice_list(Request $request){





//         $dateStart = $request->input('dateStart');
//         $dateEnd = $request->input('dateEnd');
//         $paymentDateStart = $request->input('paymentDateStart');
//         $paymentDateEnd = $request->input('paymentDateEnd');
//         $paymentStatus = $request->input('paymentStatus');
//         $updatedBy = $request->input('updatedBy');
//         $parameter = $request->input('parameter');
//         $id_merchant = $request->input('id_merchant');

//         $monthly_fee =$request->input('invoicetype');



//         if ($monthly_fee == 1) {
//             $suminvoice = \App\Suminvoice::orderBy('suminvoices.date', 'DESC')
//             ->leftJoin('customers', 'suminvoices.id_customer', '=', 'customers.id')
//             ->leftJoin('invoices', 'suminvoices.tempcode', '=', 'invoices.tempcode')
//             ->select(
//                 'suminvoices.*',
//                 'customers.name',
//                 'customers.customer_id',
//                 'customers.id_merchant',
//                 'invoices.monthly_fee',
//                 'invoices.periode',
//                 'invoices.amount'
//             )
//             ->where('invoices.monthly_fee', 1);

//             if (!empty($dateStart) && !empty($dateEnd)) {
//                 $suminvoice->whereBetween('suminvoices.date', [$dateStart, $dateEnd]);
//             }

//             if (!empty($parameter)) {
//                 $suminvoice->where(function($query) use ($parameter) {
//                     $query->where('customers.name', 'like', "%$parameter%")
//                     ->orWhere('customers.customer_id', 'like', "%$parameter%")
//                     ->orWhere('suminvoices.number', 'like', "%$parameter%");
//                 });
//             }

//             if (!empty($paymentDateStart) && !empty($paymentDateEnd)) {
//                 $suminvoice->whereBetween('suminvoices.payment_date', [$paymentDateStart, $paymentDateEnd]);
//             }

//             if (!empty($paymentStatus)) {
//                 $suminvoice->where('suminvoices.payment_status', $paymentStatus);
//             }

//             if (!empty($updatedBy)) {
//                 $suminvoice->where('suminvoices.updated_by', $updatedBy);
//             }

//             if (!empty($id_merchant)) {
//                 $suminvoice->where('customers.id_merchant', $id_merchant);
//             }

//             $suminvoice = $suminvoice->groupBy('suminvoices.id');
//             $suminvoiceData = $suminvoice->get();

//             $total = $suminvoiceData->map(function($item) {
//                 return ($item->tax * $item->amount / 100) + $item->amount;
//             })->sum();
//             $recieve_payment = $suminvoiceData->where('payment_status', 1)->map(function($item_recieve) {
//                 return ($item_recieve->tax * $item_recieve->amount / 100) + $item_recieve->amount;
//             })->sum();
//             $paid_payment = $suminvoiceData->where('payment_status', 1)->map(function($item_payment) {
//                 return ($item_payment->tax * $item_payment->amount / 100) + $item_payment->amount;
//             })->sum();
//             $unpaid_payment = $suminvoiceData->where('payment_status', 0)->map(function($item_unpaid) {
//                 return ($item_unpaid->tax * $item_unpaid->amount / 100) + $item_unpaid->amount;
//             })->sum();
//             $cancel_payment = $suminvoiceData->where('payment_status', 2)->map(function($item_cancel) {
//                 return ($item_cancel->tax * $item_cancel->amount / 100) + $item_cancel->amount;
//             })->sum();
//             $fee_counter ='*Exclude Payment point fee : '.number_format($recieve_payment - $paid_payment, 0, ',', '.'); 


//         } 


//         else {

//         // $suminvoice = \App\Suminvoice::orderBy('date', 'DESC');
//             $suminvoice = \App\Suminvoice::orderBy('date', 'DESC')
//             ->leftJoin('customers', 'suminvoices.id_customer', '=', 'customers.id')
//             ->select('suminvoices.*', 'customers.name','customers.customer_id','customers.id_merchant');


//             if (!empty($dateStart) && !empty($dateEnd)) {
//                 $suminvoice->whereBetween('suminvoices.date', [$dateStart, $dateEnd]);
//             }
//             if (!empty($parameter)) {
//                 $suminvoice->where(function($query) use ($parameter) {
//                     $query->where('customers.name', 'like', "%$parameter%")
//                     ->orWhere('customers.customer_id', 'like', "%$parameter%")
//                     ->orWhere('suminvoices.number', 'like', "%$parameter%"); 
//                 });
//             }

//             if (!empty($paymentDateStart) && !empty($paymentDateEnd)) {
//                 $suminvoice->whereBetween('suminvoices.payment_date', [$paymentDateStart, $paymentDateEnd]);
//             }

//             if ($paymentStatus!='') {
//                 $suminvoice->where('suminvoices.payment_status', $paymentStatus);
//             }

//             if (!empty($updatedBy)) {
//                 $suminvoice->where('suminvoices.updated_by', $updatedBy);
//             }

//             if (!empty($id_merchant)) {
//                 $suminvoice->where('customers.id_merchant', $id_merchant);
//             }





//             $suminvoiceData = $suminvoice->get();

//             $total = $suminvoiceData->sum('total_amount');
//             $recieve_payment = $suminvoiceData->where('payment_status', 1)->sum('recieve_payment');
//             $paid_payment = $suminvoiceData->where('payment_status', 1)->sum('total_amount');
//             $unpaid_payment = $suminvoiceData->where('payment_status', 0)->sum('total_amount');
//             $cancel_payment = $suminvoiceData->where('payment_status', 2)->sum('total_amount');
//             $fee_counter ='*Exclude Payment point fee : '.number_format($recieve_payment - $paid_payment, 0, ',', '.'); 
//         }




//         return DataTables::of($suminvoice)
//         ->addIndexColumn()
//         ->editColumn('number',function($suminvoice)
//         {

//             return ' <a href="/suminvoice/'.$suminvoice->tempcode.'" title="INV Number" class="badge badge-primary text-center  "> '.$suminvoice->number. '</a>';
//         })

//         ->addColumn('cid',function($suminvoice)
//         {

//            $status = $suminvoice->customer->id_status;
//            if ( $status == 2)
//               $badge_sts = "badge-success";
//           elseif ( $status == 3)
//               $badge_sts = "badge-secondary";
//           elseif ( $status== 4)
//               $badge_sts = "badge-danger";
//           elseif ( $status== 5)
//               $badge_sts = "badge-primary";
//           else
//               $badge_sts = "badge-warning";




//           return '<a class="badge '.$badge_sts .'" href="/customer/'.$suminvoice->customer->id.'">'.$suminvoice->customer->customer_id .' </a>';

//       })

//         ->addColumn('name',function($suminvoice)
//         {

//             return '<a>'.$suminvoice->customer->name. '</a>';
//         })
//         ->addColumn('merchant', function($suminvoice) {
//     // Check if customer relationship exists
//             if ($suminvoice->customer) {
//         // Check if merchant_name relationship exists
//                 if ($suminvoice->customer->merchant_name) {
//                     return $suminvoice->customer->merchant_name->name ;
//                 } else {
//             return 'No Merchant'; // Or any default value you want
//         }
//     } else {
//         return 'No Customer'; // Or any default value you want
//     }
// })
//         ->addColumn('address',function($suminvoice)
//         {

//             return '<a>'.$suminvoice->customer->address. '</a>';
//         })

//         ->addColumn('period', function ($suminvoice) {
//             $invoices = \App\Invoice::where('tempcode', $suminvoice->tempcode)->get();

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



//         ->editColumn('total_amount', function($suminvoice) use ($monthly_fee) {
//             $amount = $monthly_fee == 1 ? (($suminvoice->amount * $suminvoice->tax)/100) + $suminvoice->amount : $suminvoice->total_amount;
//             return '<a>' . number_format(($amount ?? 0), 2, ',', '.') . '</a>';
//         })
//         ->addColumn('status',function($suminvoice)
//         {
//             if ($suminvoice->payment_status == 1)
//               { $badge_sts = "badge-success";
//           $status = "PAID";}
//           elseif ($suminvoice->payment_status == 2 )
//               { $badge_sts = "badge-secondary";
//           $status = "CANCEL";}
//           elseif ($suminvoice->payment_status == 0)
//               {$badge_sts = "badge-danger";
//           $status = "UNPAID";}
//           else
//               {$badge_sts = "badge-warning";
//           $status = "UNKNOW";}
//           return '<a class="badge '.$badge_sts .'">'.$status.' </a>';
//       })
//         ->addColumn('updated_by', function($suminvoice) {
//             if (is_numeric($suminvoice->updated_by)) {
//                 return '<a>' . $suminvoice->user->name . '</a>';
//             } else {
//                 return '<a>' . $suminvoice->updated_by . '</a>';
//             }
//         })


//         ->rawColumns(['DT_RowIndex','date','number','cid','name','address','due_date','total_amount','status','updated_by','payment_date','period' ])
//         ->with('total', $total)
//         ->with('total_paid', $paid_payment)
//         ->with('unpaid_payment', $unpaid_payment)
//         ->with('cancel_payment', $cancel_payment)
//         ->with('fee_counter', $fee_counter)
//         ->make(true);
//     }


    public function table_invoice_list(Request $request)
    {
        $dateStart        = $request->dateStart;
        $dateEnd          = $request->dateEnd;
        $paymentDateStart= $request->paymentDateStart;
        $paymentDateEnd  = $request->paymentDateEnd;
        $paymentStatus  = $request->paymentStatus;
        $updatedBy      = $request->updatedBy;
        $parameter      = $request->parameter;
        $id_merchant    = $request->id_merchant;
    $monthly_fee    = $request->invoicetype; // 1 = monthly, else general


    /** ================= BASE QUERY ================= **/
    $baseQuery = \App\Suminvoice::query()
    ->orderBy('suminvoices.date','DESC')
    ->leftJoin('customers','suminvoices.id_customer','=','customers.id')
    ->select(
        'suminvoices.*',
        'customers.name',
        'customers.customer_id',
        'customers.id_merchant'
    )
    ->with([
        'customer.merchant_name',
        'user'
    ]);

    /** ================= FILTER ================= **/
    if($dateStart && $dateEnd){
        $baseQuery->whereBetween('suminvoices.date', [$dateStart, $dateEnd]);
    }

    if($parameter){
        $baseQuery->where(function($q) use ($parameter){
            $q->where('customers.name','like',"%$parameter%")
            ->orWhere('customers.customer_id','like',"%$parameter%")
            ->orWhere('suminvoices.number','like',"%$parameter%");
        });
    }

    if($paymentDateStart && $paymentDateEnd){
        $baseQuery->whereBetween('suminvoices.payment_date', [$paymentDateStart, $paymentDateEnd]);
    }

    if($paymentStatus !== '' && $paymentStatus !== null){
        $baseQuery->where('suminvoices.payment_status', $paymentStatus);
    }

    if($updatedBy){
        $baseQuery->where('suminvoices.updated_by', $updatedBy);
    }

    if($id_merchant){
        $baseQuery->where('customers.id_merchant', $id_merchant);
    }


    /** ================= LOAD INVOICE DETAIL 1x ================= **/
    $invoiceMap = \App\Invoice::select('tempcode','periode','monthly_fee','amount')
    ->get()
    ->groupBy('tempcode');


    /** ================= SUMMARY TOTAL ================= **/
    if($monthly_fee == 1){

        // Hitung total berdasarkan invoice amount + tax
        $summary = (clone $baseQuery)->get()->map(function($row) use ($invoiceMap){
            $sum = 0;
            if(isset($invoiceMap[$row->tempcode])){
                foreach($invoiceMap[$row->tempcode] as $inv){
                    $sum += (($row->tax * $inv->amount)/100) + $inv->amount;
                }
            }
            return [
                'status' => $row->payment_status,
                'amount' => $sum
            ];
        });

        $total            = $summary->sum('amount');
        $paid_payment     = $summary->where('status',1)->sum('amount');
        $unpaid_payment   = $summary->where('status',0)->sum('amount');
        $cancel_payment  = $summary->where('status',2)->sum('amount');
        $recieve_payment = $paid_payment;
        $fee_counter     = '*Exclude Payment point fee : '.number_format(0,0,',','.');

    } else {

        $summary = (clone $baseQuery)
        ->selectRaw('
            SUM(total_amount) as total,
            SUM(CASE WHEN payment_status=1 THEN total_amount ELSE 0 END) as paid,
            SUM(CASE WHEN payment_status=0 THEN total_amount ELSE 0 END) as unpaid,
            SUM(CASE WHEN payment_status=2 THEN total_amount ELSE 0 END) as cancel,
            SUM(recieve_payment) as receive
            ')->first();

        $total            = $summary->total ?? 0;
        $paid_payment     = $summary->paid ?? 0;
        $unpaid_payment   = $summary->unpaid ?? 0;
        $cancel_payment  = $summary->cancel ?? 0;
        $recieve_payment = $summary->receive ?? 0;
        $fee_counter     = '*Exclude Payment point fee : '.number_format($recieve_payment - $paid_payment,0,',','.');
    }


    /** ================= DATATABLE ================= **/
    return DataTables::of($baseQuery)
    ->addIndexColumn()

    ->editColumn('number', fn($s)=>
        '<a href="/suminvoice/'.$s->tempcode.'" class="badge badge-primary">'.$s->number.'</a>'
    )

    ->addColumn('cid', function($s){
        $status = $s->customer->id_status ?? 0;
        $map = [2=>'success',3=>'secondary',4=>'danger',5=>'primary'];
        $badge = $map[$status] ?? 'warning';
        return '<a class="badge '.$badge.'" href="/customer/'.$s->customer->id.'">'.$s->customer->customer_id.'</a>';
    })

    ->addColumn('name', fn($s)=> $s->customer->name ?? '-')

    ->addColumn('merchant', fn($s)=> optional($s->customer->merchant_name)->name ?? '-')

    ->addColumn('address', fn($s)=> $s->customer->address ?? '-')

    ->addColumn('period', function($s) use ($invoiceMap){
        if(!isset($invoiceMap[$s->tempcode])) return '-';
        $out=[];
        foreach($invoiceMap[$s->tempcode] as $inv){
            $type = $inv->monthly_fee ? 'M':'G';
            $out[] = $type.' '.$inv->periode;
        }
        return implode('<br>',$out);
    })

    ->editColumn('total_amount', function($s) use ($monthly_fee,$invoiceMap){
        if($monthly_fee==1 && isset($invoiceMap[$s->tempcode])){
            $sum=0;
            foreach($invoiceMap[$s->tempcode] as $inv){
                $sum += (($s->tax * $inv->amount)/100) + $inv->amount;
            }
            return number_format($sum,2,',','.');
        }
        return number_format($s->total_amount,2,',','.');
    })

    ->addColumn('status', function($s){
        if ($s->payment_status == 1){ $b="success"; $t="PAID";}
        elseif ($s->payment_status == 2){ $b="secondary"; $t="CANCEL";}
        elseif ($s->payment_status == 0){ $b="danger"; $t="UNPAID";}
        else { $b="warning"; $t="UNKNOWN";}
        return '<span class="badge badge-'.$b.'">'.$t.'</span>';
    })

    ->addColumn('updated_by', function($s){
        return is_numeric($s->updated_by)
        ? (optional($s->user)->name ?? '-')
        : $s->updated_by;
    })

    ->rawColumns(['number','cid','period','status'])

    ->with('total', $total)
    ->with('total_paid', $paid_payment)
    ->with('unpaid_payment', $unpaid_payment)
    ->with('cancel_payment', $cancel_payment)
    ->with('fee_counter', $fee_counter)

    ->make(true);
}


public function create($id)
{
        //
 $mount = now()->format('mY');

   // // Mengubah input menjadi Carbon instance
   //   $invoiceDate = Carbon::parse( now()->format('y-m-d'));

   //  // Mendapatkan tanggal 20 dari bulan yang sama
   //   $dueDate = $invoiceDate->copy()->day(20);

   //  // Jika invoice dibuat pada tanggal 20 atau lebih
   //   if ($invoiceDate->day >= 20) {
   //      // Tambahkan satu bulan untuk due date
   //      $dueDate->addMonth();
   //  }



 $invoice = \App\Invoice::where('id_customer', $id)
 ->where('payment_status', '=', 0)
 ->get(); 
 $pajak = \App\Akun::where('tax', 1)
 ->where('tax_value', '!=', 0)
 ->get();

 $customer = \App\Customer::where('customers.id', $id)
 ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
 ->Join('plans', 'customers.id_plan', '=', 'plans.id')
 ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')->first();
 $isolirDate =$customer->isolir_date - 1;
  // Convert input to Carbon instance
$invoiceDate = Carbon::now()->startOfDay(); // Get the current date without time

// Get the due date based on the customer's isolir_date
$dueDate = $invoiceDate->copy()->day($isolirDate);

// If the invoice is created on the 20th or later
if ($invoiceDate->day >= $isolirDate) {
    // Add one month to the due date

    $dueDate->addMonth();
}


if (empty($customer)){

   return abort(404);
}
else
{


    //$customer = \App\customer::findOrFail($id);
   // dd($invoice);

    return view ('invoice/create',['invoice' =>$invoice, 'customer'=>$customer,'duedate'=>$dueDate, 'pajak'=>$pajak]);

}
}

/**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
public function createinvoice(Request $request)
{
        //dd($request);
    $id = $request->invoice_item;


    $tempcode=sha1(time());

    foreach ($id as $id) 
    {

        \App\Invoice::where('id', $id)->update([
            'tempcode' => $tempcode,
        ]);

    }

    $invoice =\App\Invoice::where('tempcode', $tempcode)
    ->where('id_customer', '=', $request->id_customer)
    ->get();
         //  dd($invoice);
    $customer = \App\Customer::where('customers.id',$request->id_customer )

    ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
    ->Join('plans', 'customers.id_plan', '=', 'plans.id')
    ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')->first();
    //$customer = \App\customer::findOrFail($id);


    return view ('invoice/invoicesum',['invoice' =>$invoice, 'customer'=>$customer]);



}
public function store(Request $request)
{
        //

   $periode_month= $request->input('periode_month');
   $periode_year=$request->input('periode_year');
   $periode = $periode_month.$periode_year;
   if (($request['monthly_fee'])==1)
   {

    $check_invoice = \App\Invoice::where('id_customer', $request['id_customer'])->Where('periode', $periode)->Where('monthly_fee','1')->first();
        //dd($check_invoice);

    if (!$check_invoice)
    {




        $request ->validate([
            'id_customer' => 'required',
            'monthly_fee' => 'required',
            'description' => 'required',
            'qty' => 'required',
            'amount' => 'required',

        ]);

        // $tax = $request['tax_total'] ?? 0;


        \App\Invoice::create([
            'id_customer' => ($request['id_customer']),
            'monthly_fee' => ($request['monthly_fee']),
            'periode' => $periode, 
            'description' => ($request['description']),
            'qty' => ($request['qty']),
            'amount' => ($request['amount']),
            // 'tax' => $tax,
        ]);

            // \Log::channel('invoice')->info('INVOICE CREATED to '.$customer["name"].' with amount = '.$customer["price"].' MANUALLY' );      
        return redirect ('/invoice/'.$request['id_customer'].'/create')->with('success','Item created successfully!');
    }
    else
    {

        return redirect ('/invoice/'.$request['id_customer'].'/create')->with('error','Item Monthly Fee with the same period already exist!!');
    }
}
else
{



    $request ->validate([
        'id_customer' => 'required',
        'monthly_fee' => 'required',
        'description' => 'required',
        'qty' => 'required',
        'amount' => 'required',

    ]);



    \App\Invoice::create([
        'id_customer' => ($request['id_customer']),
        'monthly_fee' => ($request['monthly_fee']),
        'periode' => $periode, 
        'description' => ($request['description']),
        'qty' => ($request['qty']),
        'amount' => ($request['amount']),
        'tax' => ($request['tax_total']),
    ]);


    return redirect ('/invoice/'.$request['id_customer'].'/create')->with('success','Item created successfully!');
}


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



           //Sumary Invoice

    $suminvoice = \App\Suminvoice::where('id_customer', $id)
         //   ->where('periode', '=', $mount)
             //->where('monthly_fee', '=', 1)

          // ->where('payment_status', '!=', 1)
    ->orderBy('date', 'DESC')

    ->get();

    $encryptedurl = Crypt::encryptString($id);

    $customer = \App\Customer::where('customers.id', $id)

    ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
    ->Join('plans', 'customers.id_plan', '=', 'plans.id')
    ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')
    ->withTrashed()
    ->first();
    //$customer = \App\customer::findOrFail($id);
          // dd ($invoice);

    return view ('invoice/show',['suminvoice' =>$suminvoice, 'customer'=>$customer,  'encryptedurl'=>$encryptedurl]);
}



/**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */



public function custinv($encrypted)
{
  $companyName = config('app.name', 'INTERNET SERVICE PROVIDER');
  $companyAddress1 = tenant_config('company_address1', env('COMPANY_ADDRESS1'));
  $companyAddress2 = tenant_config('company_address2', env('COMPANY_ADDRESS2'));
  $invNote = tenant_config('inv_note', env('INV_NOTE')) ?? '';
  try {
  //  $decrypted = decrypt($encryptedValue);

    $id = Crypt::decryptString($encrypted);


           //Sumary Invoice

    $suminvoice = \App\Suminvoice::where('id_customer', $id)
         //   ->where('periode', '=', $mount)
             //->where('monthly_fee', '=', 1)

          // ->where('payment_status', '!=', 1)
    ->limit(10)
    ->orderBy('id', 'DESC')
    ->get();



    $customer = \App\Customer::where('customers.id', $id)

    ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
    ->Join('plans', 'customers.id_plan', '=', 'plans.id')
    ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')->first();
    //$customer = \App\customer::findOrFail($id);
         //  dd ($customer);

    return view ('invoice/custinv',['suminvoice' =>$suminvoice, 'customer'=>$customer, 'companyName'=>$companyName, 'companyAddress1'=>$companyAddress1, 'companyAddress2'=>$companyAddress2, 'invNote'=>$invNote ]);

} catch (DecryptException $e) {

    abort(403, ' Customer Bill not found !!');

    //
}



}
public function edit($tempcode)
{
    $suminvoice = \App\Suminvoice::with('invoice')->where('tempcode', $tempcode)->firstOrFail();
    $invoice = \App\Invoice::with('suminvoice')->where('tempcode', $tempcode)->get();

    // dd($invoice);
   // dd($invoice->customer->id);
    return view('invoice.edit',['suminvoice' =>$suminvoice, 'invoice'=>$invoice]);
}
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, $cid)
    {
        $id=\App\Invoice::dencrypt($id);
        \App\Invoice::destroy($id);
        return redirect ('/invoice/'.$cid.'/create')->with('success','Item deleted successfully!');
    }


    public function createmounthlyinv(Request $request)

    {


            // \Log::channel('invoice')->info('==== START INVOICE CREATE BY SYSTEM. ===');

        $customer = \App\Customer::where('customers.id', $request->id)
        ->Join('plans', 'customers.id_plan', '=', 'plans.id')
        ->select('customers.*','plans.name as plan','plans.price as price')->first();


        $no=0;
        $month = now()->format('mY');

        $msg="";
        $latest_number=uniqid();


        $check_invoice = \App\Invoice::where('id_customer', $customer->id)->Where('periode', $month)->Where('monthly_fee','1')->first();

        if (!$check_invoice)
        {
           $no =$no +1;
           $msg .="\n ".$no." : ".$customer->id." ( ".$customer->name." ) \n [ ";

            // $invoice = $invoice +1;
           $tempcode=sha1(time().rand());



           \App\Invoice::create([
               'id_customer' => ($customer->id),
               'monthly_fee' => '1',
               'periode' => $month, 
               'description' => 'Monthly Fee / Biaya Bulanan ',
               'qty' => '1',
               'amount' => ($customer->price),
               'payment_status' => 3,
               'tax' => '0',
               'tempcode' => $tempcode,
               'created_by' => 'System',

           ]);


           if (!empty($customer->email))
           {
             $email =$customer->email;

         }

         else

         { 
          $email ="return@trikamedia.com";
      }

      if(empty($customer->tax))
      {
        $tax=0;
    }
    else
    {
        $tax=$customer->tax;
    }

    $total_amount =$customer->price + ($customer->price * $tax / 100);
    $total_amount_wfee = $total_amount + env('XENDIT_FEE') ;
    $customer_name = $customer->customer_id .' ( '. $customer->name .' )';

    try
    { 
        Xendit::setApiKey(env('XENDIT_KEY'));

        $params = [
            'external_id' => $customer->customer_id,
            'payer_email' => $email,
            'description' => $customer->name,
            'amount' => $total_amount_wfee,
            'customer'=> [


                'given_names' => $customer_name,
                'mobile_number' => $customer->phone,
                'address' => $customer->address,

            ],

            'items' => [
                [

                    'name' => 'Invoice Trikamedia no #'.$latest_number,
                    'quantity' => 1 ,
                    'price'=> $total_amount,
                ],
                [

                    'name' => 'Biaya transaksi',
                    'quantity' => 1 ,
                    'price'=> env('XENDIT_FEE'),
                ],


            ],



        ];


        $createInvoice = \Xendit\Invoice::create($params);

        $array = json_decode(json_encode($createInvoice, true))->id;  
        if ($array)
        {
         $msg .="\nSuccess create invoice on Payment Gateway with id ".$array;
     }
     else
         {$msg .="\n  <a style='color:red'> Error create invoice on Payment Gateway </a>";}

 }
 catch ( Exception $e)
 {
    $msg .="\n Error create invoice on Payment Gateway ";
}
finally {

    try
    {
     \App\Suminvoice::create([
         'id_customer' => ($customer->id),
         'number' => $latest_number,
         'date' => date("Y-m-d"), 
         'payment_status' => 0,
         'tax' => $tax,
         'tempcode' => $tempcode,
         'payment_id' => $array,
         'total_amount' => $total_amount,

     ]);



     $msg .="\nSuccess create invoice on Helpdesk System to ".$customer->name." with amount = ".$customer->price."";

 }    
 catch (Exception $e)
 {
    $msg .="\nError create invoice on Helpdesk System";
}
finally {

    try
    {

       $message ="Yth. ".$customer->name." ";
       $message .="\n";
       $message .="\nTagihan Customer dengan CID *".$customer->customer_id."* sudah kami Terbitkan sebesar *Rp.". $total_amount."*";
       $message .="\nSilahkan melakukan pembayaran sebelum tanggal 20-".date("m-Y", time());
       $message .="\nUntuk info lebih lengkap silahkan klik link berikut";
       $message .="\nhttp://".tenant_config('domain_name', env("DOMAIN_NAME"))."/suminvoice/".$tempcode."/print";
       $message .="\n";

       $message .="\nUntuk pembayaran non-tunai, Mohon mengirimkan bukti transfer ke nomor ini karena nomor sebelumnya sudah tidak aktif.";
       $message .="\n";

       $message .="\nAbaikan pesan ini jika sudah melakukan pembayaran";
       $message .="\n";
       $message .="\n~ ".config("app.signature")." ~";


//disable WA
     // $msgresult= \App\Suminvoice::wa_payment($customer->phone,$message);
     // $msg .="\n Whatsapp : ".$msgresult;

   }
   catch (Exception $e)
   {
      $msg .="\nError sent  invoice notification to Customer";
  }
  finally {



  }

}
}

} //end if
\Log::channel('invoice')->info($msg." \n ]" );  

//} // end foreach

// \Log::channel('invoice')->info('==== END INVOICE CREATE BY SYSTEM ===');
       // $this->info('Demo:Cron Cummand Run successfully!');
        //



//});

ini_set('memory_limit','256M');
$date = date('Y-m-d');
$path = base_path()."/storage/logs/invoice-".$date.".log"; //get the apache.log file in root
$logs = \File::get($path);


return view ('invoice/report', ['logs' => $logs ]);

}



//JOB










public function invoicehandle()
{


    DB::transaction(function() {


        \Log::channel('invoice')->info('==== START INVOICE CREATE BY SYSTEM. ===');

        $active_customer = \App\Customer::where('customers.id_status', '2')
        ->orWhere('customers.id_status', '4') 

        ->Join('plans', 'customers.id_plan', '=', 'plans.id')
        ->select('customers.*','plans.name as plan','plans.price as price')->get();
        $latest = \App\Suminvoice::latest('id')->first();

        if (!$latest)
        {
            $latest_number=1;
        }
        else
        {
         $latest_number = $latest->number;
     }


     $no=0;
     $month = now()->format('mY');
     foreach($active_customer as $customer) 

     {
        $msg="";
        $latest_number = $latest_number+1;

        $check_invoice = \App\Invoice::where('id_customer', ($customer['id']))->Where('periode', $month)->Where('monthly_fee','1')->first();

        if (!$check_invoice)
        {
           $no =$no +1;
           $msg .="\n ".$no." : ".$customer['customer_id']." ( ".$customer['name']." ) \n [ ";


           $tempcode=(sha1(time())).rand();



           \App\Invoice::create([
            'id_customer' => ($customer['id']),
            'monthly_fee' => '1',
            'periode' => $month, 
            'description' => 'Montly fee package '. ($customer['plan']),
            'qty' => '1',
            'amount' => ($customer['price']),
            'payment_status' => 3,
            'tax' => '0',
            'tempcode' => $tempcode,
            'created_by' => 'System',

        ]);


           if (!empty($customer->email))
           {
             $email =$customer->email;

         }

         else

         { 
          $email ="return@trikamedia.com";
      }

      if(empty($customer['tax']))
      {
        $tax=0;
    }
    else
    {
        $tax=$customer['tax'];
    }

    $total_amount =$customer['price'] + ($customer['price'] * $tax / 100);
    $total_amount_wfee = $total_amount + env('XENDIT_FEE') ;
    $customer_name = $customer->customer_id .' ( '. $customer->name .' )';

    try
    { 
        Xendit::setApiKey(env('XENDIT_KEY'));

        $params = [
            'external_id' => $customer->customer_id,
            'payer_email' => $email,
            'description' => $customer->name,
            'amount' => $total_amount_wfee,
            'customer'=> [


                'given_names' => $customer_name,
                'mobile_number' => $customer->phone,
                'address' => $customer->address,

            ],

            'items' => [
              [

                'name' => 'Invoice Trikamedia no #'.$latest_number,
                'quantity' => 1 ,
                'price'=> $total_amount,
            ],
            [

                'name' => 'Biaya transaksi',
                'quantity' => 1 ,
                'price'=> env('XENDIT_FEE'),
            ],


        ],



    ];


    $createInvoice = \Xendit\Invoice::create($params);

    $array = json_decode(json_encode($createInvoice, true))->id;  
    if ($array)
    {
     $msg .="\nSuccess create invoice on Payment Gateway with id ".$array;
 }
 else
    {$msg .="\n  <a style='color:red'> Error create invoice on Payment Gateway </a>";}

}
catch ( Exception $e)
{
    $msg .="\n Error create invoice on Payment Gateway ";
}
finally {

    try
    {
     \App\Suminvoice::create([
        'id_customer' => ($customer['id']),
        'number' => $latest_number,
        'date' => date("Y-m-d"), 
        'payment_status' => 0,
        'tax' => $tax,
        'tempcode' => $tempcode,
        'payment_id' => $array,
        'total_amount' => $total_amount,

    ]);



     $msg .="\nSuccess create invoice on Helpdesk System to ".$customer['name']." with amount = ".$customer['price']."";

 }    
 catch (Exception $e)
 {
    $msg .="\nError create invoice on Helpdesk System";
}
finally {

    try
    {

       $message ="Yth. ".$customer->name." ";
       $message .="\n";
       $message .="\nTagihan Customer dengan CID *".$customer->customer_id."* sudah kami Terbitkan sebesar *Rp.". $total_amount."*";
       $message .="\nSilahakan melakukan pembayaran sebelum tanggal 20-".date("m-Y", time());
       $message .="\nUntuk info lebih lengkap silahkan klik link berikut";
       $message .="\nhttp://".tenant_config('domain_name', env("DOMAIN_NAME"))."/suminvoice/".$tempcode."/print";
       $message .="\n";

       $message .="\nUntuk pembayaran non-tunai, Mohon mengirimkan bukti transfer karena nomor sebelumnya sudah tidak aktif.";
       $message .="\n";
       $message .="\n~ ".config("app.signature")." ~";

//Disable WA
     // $msgresult= \App\Suminvoice::wa_payment($customer->phone,$message);
     // $msg .="\n Whatsapp : ".$msgresult;

   }
   catch (Exception $e)
   {
      $msg .="\nError sent  invoice notification to Customer";
  }
  finally {



  }

}
}

} //end if
\Log::channel('invoice')->info($msg." \n ]" );  

} // end foreach

\Log::channel('invoice')->info('==== END INVOICE CREATE BY SYSTEM ===');




});

ini_set('memory_limit','256M');
$date = date('Y-m-d');
$path = base_path()."/storage/logs/invoice-".$date.".log"; //get the apache.log file in root
$logs = \File::get($path);


return view ('invoice/report', ['logs' => $logs ]);

}



}
