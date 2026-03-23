<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use \RouterOS\Client;
use \RouterOS\Query;
Use GuzzleHttp\Clients;
use \App\Customer;
use \App\Suminvoice;
use DataTables;
use Exception;
use App\Plan;
use App\Statuscustomer;
use App\Distrouter;


use App\Customerlog;
use App\LeadUpdate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Helpers\WaGatewayHelper;

class CustomerController extends Controller
{
    // Update workflow stage
    public function updateWorkflow(Request $request, $id)
    {
        $request->validate([
            'workflow_stage_id' => 'required|exists:lead_workflows,id',
        ]);
        $customer = \App\Customer::findOrFail($id);
        $customer->workflow_stage_id = $request->workflow_stage_id;
        $customer->save();
        // Optionally log to LeadUpdate
        \App\LeadUpdate::create([
            'id_customer' => $customer->id,
            'updated_by' => auth()->id() ?? 0,
            'field_changed' => 'workflow_stage_id',
            'old_value' => $customer->getOriginal('workflow_stage_id'),
            'new_value' => $request->workflow_stage_id,
            'notes' => 'Update workflow stage',
        ]);
        return redirect()->back()->with('success', 'Workflow stage updated!');
    }
    /**
     * Show lead conversion summary for marketing menu
     */
    public function leadSummary(Request $request)
    {
        $today         = now()->toDateString();
        $firstOfMonth  = now()->startOfMonth()->toDateString();
        $start         = $request->input('start_date', $firstOfMonth);
        $end           = $request->input('end_date', $today);
        $filterSale    = $request->input('id_sale', '');

        // ── Definisi 3 kategori MUTUALLY EXCLUSIVE ──────────────────────────────────
        // id_status adalah sumber kebenaran:
        //
        // Sukses   : id_status=2 (Active) AND converted_at IS NOT NULL
        //            → benar-benar sudah jadi pelanggan aktif
        // Gagal    : id_status=1 AND lost_at IS NOT NULL
        //            → masih potensial, ditandai gagal (converted_at diabaikan)
        // In Prog  : id_status=1 AND lost_at IS NULL
        //            → masih aktif dikejar
        //
        // Filter tanggal berdasarkan created_at (kapan lead masuk)
        // ──────────────────────────────────────────────────────────────────────────────

        $periodFilter = function($q) use ($start, $end, $filterSale) {
            $q->whereBetween('customers.created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            if ($filterSale) $q->where('id_sale', $filterSale);
        };

        // Sukses: id_status berhasil berubah ke 2 (Active) DAN converted_at tercatat
        $totalConverted  = \App\Customer::where('id_status', 2)
            ->whereNotNull('converted_at')
            ->tap($periodFilter)->count();

        // Gagal: masih id_status=1, sudah ditandai lost_at
        $totalLost       = \App\Customer::where('id_status', 1)
            ->whereNotNull('lost_at')
            ->tap($periodFilter)->count();

        // In Progress: masih id_status=1, belum ditandai gagal
        $totalInprogress = \App\Customer::where('id_status', 1)
            ->whereNull('lost_at')
            ->tap($periodFilter)->count();

        // Total = jumlah 3 kategori → selalu konsisten
        $totalLeads     = $totalConverted + $totalLost + $totalInprogress;

        $pctConverted   = $totalLeads > 0 ? round($totalConverted  / $totalLeads * 100, 1) : 0;
        $pctLost        = $totalLeads > 0 ? round($totalLost       / $totalLeads * 100, 1) : 0;
        $pctInprogress  = $totalLeads > 0 ? round($totalInprogress / $totalLeads * 100, 1) : 0;

        // ── Sales performance ──────────────────────────────────────────────────────────
        $allSales = \App\Sale::orderBy('name')->get();

        $salesPerf = $allSales->map(function($sale) use ($start, $end) {
            $period = function($q) use ($start, $end, $sale) {
                $q->whereBetween('customers.created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                  ->where('id_sale', $sale->id);
            };

            $converted  = \App\Customer::where('id_status', 2)->whereNotNull('converted_at')->tap($period)->count();
            $lost       = \App\Customer::where('id_status', 1)->whereNotNull('lost_at')->tap($period)->count();
            $inprogress = \App\Customer::where('id_status', 1)->whereNull('lost_at')->tap($period)->count();
            $total      = $converted + $lost + $inprogress;
            $convRate   = $total > 0 ? round($converted / $total * 100, 1) : 0;

            return [
                'id'         => $sale->id,
                'name'       => $sale->name,
                'total'      => $total,
                'inprogress' => $inprogress,
                'converted'  => $converted,
                'lost'       => $lost,
                'conv_rate'  => $convRate,
            ];
        })->filter(fn($s) => $s['total'] > 0)->sortByDesc('total')->values();

        // ── In-progress leads (with workflow % + last update) ─────────────────────────
        $inprogressLeads = \App\Customer::where('id_status', 1)
            ->whereNull('lost_at')
            ->tap($periodFilter)
            ->with([
                'sale_name',
                'customerSteps',
                'leadUpdates' => fn($q) => $q->orderBy('created_at', 'desc'),
            ])
            ->orderBy('customers.created_at', 'desc')
            ->get();

        $inprogressLeads->each(function($lead) {
            $steps   = $lead->customerSteps;
            $total   = $steps->count();
            $curId   = $lead->current_step_id;
            $curIdx  = $steps->search(fn($s) => $s->id == $curId);
            $passed  = ($curIdx !== false) ? $curIdx : 0;
            $pct     = $total > 0 ? round($passed / $total * 100) : 0;
            if ($curIdx !== false && $curIdx === $total - 1) $pct = 100;
            $lead->workflow_pct     = $pct;
            $lead->workflow_current = ($curIdx !== false) ? $steps[$curIdx]->name : ($steps->first() ? $steps->first()->name : '-');
            $lead->workflow_total   = $total;
            $lead->workflow_passed  = $passed;
        });

        // ── Lost leads ────────────────────────────────────────────────────────────────
        $lostLeads = \App\Customer::where('id_status', 1)
            ->whereNotNull('lost_at')
            ->tap($periodFilter)
            ->with(['sale_name'])
            ->orderBy('lost_at', 'desc')
            ->get();

        return view('marketing.lead-summary', compact(
            'start', 'end', 'filterSale', 'allSales',
            'totalLeads', 'totalInprogress', 'totalConverted', 'totalLost',
            'pctInprogress', 'pctConverted', 'pctLost',
            'salesPerf', 'inprogressLeads', 'lostLeads'
        ));
    }
    /**
     * Return the lead update history timeline partial for AJAX loading
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function leadHistory($id)
    {
        $customer = Customer::with('status_name')->findOrFail($id);
        $leadUpdates = $customer->id_status == 1 ? \App\LeadUpdate::where('id_customer', $id)->orderBy('created_at', 'desc')->get() : collect([]);
        return view('customer.lead-history', compact('customer', 'leadUpdates'))->render();
    }


    /**
     * Display a listing of the resource.s
     *
     * @return \Illuminate\Http\Response

     */
    public function __construct()
    {
      $this->middleware('auth');

    // Semua role ini boleh akses semua method termasuk customermerchant
      $this->middleware('checkPrivilege:admin,accounting,marketing,payment,noc,user,merchant');

    // Tapi merchant hanya boleh akses customermerchant (blokir selain itu)
      $this->middleware(function ($request, $next) {
        $user = auth()->user();
        $allowed = ['customermerchant', 'table_customermerchant','filter'];

        if ($user->privilege === 'merchant' && !in_array($request->route()->getActionMethod(), $allowed)) {
            abort(403, 'Merchant tidak diizinkan mengakses halaman ini.');
        }
        return $next($request);
    });
  }
  public function search(Request $request)
  {
      $request ->validate([
        'search' => 'required|min:4',
    ]);
      $val =$request->search ;
      $customer = \App\Customer::orderBy('id', 'DESC')
      ->where('name', 'LIKE', "%".$val."%") 
      ->orWhere('customer_id', 'LIKE', "%".$val."%") 
      ->orWhere('address', 'LIKE', "%".$val."%") 
      ->orWhere('pppoe', 'LIKE', "%".$val."%") 
      ->orWhere('phone', 'LIKE', "%".$val."%") 
      ->get();


      return view ('customer/search',['customer' =>$customer]);
  }


  public function ajaxRouterStatus($id)
  {
    try {
        $customer = \App\Customer::with('distrouter')->findOrFail($id);

        if (!$customer->distrouter) {
            return response()->json(['success' => false, 'message' => 'Router tidak ditemukan.'], 404);
        }

        $cacheKey = "mikrotik_status_{$customer->distrouter->id}_{$customer->pppoe}";
        $status = \Cache::remember($cacheKey, now()->addSeconds(30), function () use ($customer) {
            return $customer->distrouter->mikrotik_status(
                $customer->distrouter->ip,
                $customer->distrouter->user,
                $customer->distrouter->password,
                $customer->distrouter->port,
                $customer->pppoe
            );
        });

        $btn_status = match ($status['user'] ?? '') {
            'Enable' => 'btn-success',
            'Disable' => 'btn-secondary',
            default => 'btn-warning',
        };

        $btn_online = match ($status['online'] ?? '') {
            'Online' => 'btn-success',
            'Offline' => 'btn-secondary',
            default => 'btn-warning',
        };

        return response()->json([
            'success' => true,
            'status_user' => $status['user'] ?? 'Unknown',
            'online'     => $status['online'] ?? 'Unknown',
            'ip'         => $status['ip'] ?? '-',
            'uptime'     => $status['uptime'] ?? '-',
            'ip_count'   => $status['ip_count'] ?? 0,
            'btn_status' => $btn_status,
            'btn_online' => $btn_online,
        ]);
    } catch (\Throwable $e) {
        \Log::error("ajaxRouterStatus error: {$e->getMessage()}");

        return response()->json(['success' => false, 'message' => 'Gagal ambil status Mikrotik'], 500);
    }
}


// CustomerController.php
public function mapData(Request $request)
{
    $bounds = $request->input('bounds');
    if (!$bounds) {
        return response()->json([]);
    }

    $bounds = json_decode($bounds, true); // <-- Tambahkan ini untuk konversi dari JSON string ke array

    // Ambil koordinat sudut
    $southWest = $bounds['southWest'];
    $northEast = $bounds['northEast'];

    $customers = \App\Customer::whereNotNull('coordinate')
    ->get()
    ->filter(function ($c) use ($southWest, $northEast) {
        $parts = explode(',', $c->coordinate);
        if (count($parts) !== 2) return false;

        [$lat, $lng] = $parts;
        return $lat >= $southWest['lat'] && $lat <= $northEast['lat']
        && $lng >= $southWest['lng'] && $lng <= $northEast['lng'];
    })
    ->map(function ($c) {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'coordinate' => $c->coordinate,
        ];
    });

    return response()->json($customers->values());
}





public function subscribeform($id)
{
        $customer = Customer::find($id); // Ubah sesuai kebutuhan
        return view('customer.subscribeform', compact('customer'));
    }

    public function generatePDF(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:customers,id'
        ]);

        $customer = Customer::findOrFail($request->id);
        $encryptedurl = config('app.url') . "/invoice/cst/" . Crypt::encryptString($customer->id);

        $data = $request->except('_token');
        $data['tanggal'] = now()->format('d-m-Y');


        $qrcode = base64_encode(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(150)->generate($encryptedurl)
        );
    // Kirim data ke view termasuk QR Code
        $pdf = PDF::loadView('customer.pdf', compact('data', 'customer', 'qrcode'));

        return $pdf->stream('formulir-pendaftaran-' . now()->timestamp . '.pdf');
        
    }

    public function filter(Request $request){
        $filter =$request->filter ;
        $parameter =$request->parameter ;
        $id_status =$request->id_status ;
        $id_plan =$request->id_plan ;
        $customer = \App\Customer::orderBy('id', 'DESC')
        ->where($filter, 'LIKE', "%".$parameter."%") 
        ->Where('id_status', 'LIKE', "%".$id_status."%") 
        ->Where('id_plan', 'LIKE', "%".$id_plan."%") 
        ->get();

        return DataTables::of($customer)
        ->editColumn('customer_id',function($customer){
            return '<a href="/customer/'.$customer->id.'" class="btn btn-primary">'.$customer->customer_id.'</a>';
        })
        ->addIndexColumn()
        ->addColumn('select', function($customer)
        {
          if (($customer->status_name->name == 'Active')Or ($customer->status_name->name == 'Block'))

          {
             return '<input   type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'"></td>';
         }

         else
         {}

 })
        ->addColumn('plan', function($customer){

          return '<a class="text-center">'.$customer->plan_name->name.'('.$customer->plan_name->price.') </a>';

      })
        ->addColumn('status_cust', function($customer){
          if ($customer->status_name->name == 'Active')
            {$badge_sts = "badge-success";}
        elseif ($customer->status_name->name == 'Inactive')
         {  $badge_sts = "badge-secondary";}
     elseif ($customer->status_name->name == 'Block')
        {     $badge_sts = "badge-danger";}
    elseif ($customer->status_name->name == 'Company_Properti')
       {$badge_sts = "badge-primary";}
   else
       {$badge_sts = "badge-warning";}

   return '<a class="badge text-white text-center  '.$badge_sts.'">'.$customer->status_name->name.'</a>';


})
        ->addColumn('invoice',function($customer)
        {
          $count_inv = new \App\Suminvoice();
          $result = $count_inv->countinv($customer->id);
          if ( $result >= 1)
          {

              return ' <a href="/invoice/'.$customer->id.'" title="Invoice" class="btn btn-warning btn-sm   "> '.$result. '</a>';
          }

      })
        ->addColumn('action',function($customer){
            $create_ticket = url('/ticket/'.$customer->id.'/create');







        })
        ->rawColumns(['DT_RowIndex','customer_id','plan','status_cust','select','invoice','action'])
        ->make(true);
    }


    public function searchforjurnal(Request $request) {
    // Log::info('Data yang diterima:', $request->all());

    // Ambil data customer berdasarkan pencarian
        $customers = Customer::where('name', 'LIKE', "%{$request->q}%")
        ->orWhere('customer_id', 'LIKE', "%{$request->q}%")
        ->limit(100)
        ->get();

        return response()->json($customers);
    }

    public function index()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $status = \App\Statuscustomer::pluck('name', 'id');
        $merchant = \App\Merchant::pluck('name', 'id');
        $plan = \App\Plan::pluck('name', 'id');
        // Jumlah pelanggan baru per hari (status aktif id_status = 2)
        $dailyNewCustomers = \App\Customer::whereBetween('billing_start', [$startOfMonth, $endOfMonth])
            ->where('id_status', 2)
            ->selectRaw('DATE(billing_start) as date, COUNT(*) as new_count')
            ->groupBy(DB::raw('DATE(billing_start)'))
            ->orderBy('date')
            ->get();
        $totalNewCustomers = $dailyNewCustomers->sum('new_count');

        // Jumlah pelanggan yang berhenti (deleted) per hari bulan ini
        $dailyDeletedCustomers = \App\Customer::onlyTrashed()
            ->whereBetween('deleted_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(deleted_at) as date, COUNT(*) as deleted_count')
            ->groupBy(DB::raw('DATE(deleted_at)'))
            ->orderBy('date')
            ->get();
        $totalDeletedCustomers = $dailyDeletedCustomers->sum('deleted_count');

        $tags = \App\Tag::pluck('name', 'id');

        return view('customer/index', [
            'totalNewCustomers' => $totalNewCustomers,
            'dailyNewCustomers' => $dailyNewCustomers,
            'totalDeletedCustomers' => $totalDeletedCustomers,
            'dailyDeletedCustomers' => $dailyDeletedCustomers,
            'status' => $status,
            'plan' => $plan,
            'merchant' => $merchant,
            'tags' => $tags,
        ]);
    }


    public function customermerchant()
    {
        //

        $status = \App\Statuscustomer::pluck('name', 'id');
    // $merchant = \App\Merchant::pluck('name', 'id');
    // $plan = \App\Plan::pluck('name', 'id');

        return view ('customer/customermerchant',['status'=>$status]);
    }
    public function unpaid()
    {
        //
      $status = \App\Statuscustomer::pluck('name', 'id');
      $plan = \App\Plan::pluck('name', 'id');
      $merchant = \App\Merchant::pluck('name', 'id');
      return view ('customer/unpaid',['status'=>$status, 'plan'=>$plan,'merchant'=>$merchant]);
  }
  public function isolir()
  {
        //
      $status = \App\Statuscustomer::pluck('name', 'id');
      $plan = \App\Plan::pluck('name', 'id');
      $customer_count = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at', DB::raw('COUNT(customers.id) as customers.id'))
      ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
      ->where("suminvoices.payment_status", "=", 0)
      ->where("customers.id_status", "=", 2)
      ->groupBy('customers.id')
      ->get();

      return view ('customer/isolir',['status'=>$status, 'plan'=>$plan, 'customer_count'=>$customer_count]);
  }


  public function table_customer(Request $request)
  {
    // Start building the query
    $customerQuery = \App\Customer::select('id', 'customer_id', 'name', 'address', 'id_merchant', 'billing_start', 'isolir_date', 'id_plan', 'id_status', 'id_sale','id_distrouter','notification','fcm_token');
// Hitung jumlah pelanggan berdasarkan status
    $customerCounts = \App\Customer::select('id_status', \DB::raw('count(*) as total'))
    ->when(!empty($request->filter), function ($query) use ($request) {

        if ($request->filter === 'isolir_date' && !empty($request->parameter)) { 
            $query->where($request->filter, $request->parameter);
        } else {
            $query->where($request->filter, 'LIKE', "%{$request->parameter}%");
        }


    })
    ->when(!empty($request->id_status), function ($query) use ($request) {
        $query->where('id_status', $request->id_status);
    })
    ->when(!empty($request->id_plan), function ($query) use ($request) {
        $query->where('id_plan', $request->id_plan);
    })
    ->when(!empty($request->id_merchant), function ($query) use ($request) {
        $query->where('id_merchant', $request->id_merchant);
    })
    ->when(!empty($request->id_tag), function ($query) use ($request) {
        $tagIds = (array) $request->id_tag;
        foreach ($tagIds as $tagId) {
            $query->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        }
    })
    ->groupBy('id_status')
    ->pluck('total', 'id_status');

    $potensial = $customerCounts[1] ?? 0;
    $active = $customerCounts[2] ?? 0;
    $inactive = $customerCounts[3] ?? 0;
    $block = $customerCounts[4] ?? 0;
    $company_Properti = $customerCounts[5] ?? 0;
    $unknown = array_sum($customerCounts->toArray()) - ($potensial + $active + $inactive + $block + $company_Properti);

    // Apply filtering if the filter is not empty
    if (!empty($request->filter)) {
        $filter = $request->filter;
        $parameter = $request->parameter;

        
        if ($filter === 'isolir_date' && !empty($parameter)) {
            $customerQuery->where("customers." . $filter, $parameter);
        } else {
            $customerQuery->where("customers." . $filter, 'LIKE', "%" . $parameter . "%");
        }

        // Apply status, plan, and merchant filters if provided
        if (!empty($request->id_status)) {
            $customerQuery->where('id_status', $request->id_status);
        }

        if (!empty($request->id_plan)) {
            $customerQuery->where('id_plan', $request->id_plan);
        }

        if (!empty($request->id_merchant)) {
            $customerQuery->where('id_merchant', $request->id_merchant);
        }
    }

    // Filter by tag (AND: customer must have ALL selected tags)
    if (!empty($request->id_tag)) {
        $tagIds = (array) $request->id_tag;
        foreach ($tagIds as $tagId) {
            $customerQuery->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        }
    }

    // Order the results
    $customerQuery->orderBy('id', 'DESC');

    // Get the results

    return DataTables::of($customerQuery)
    ->editColumn('customer_id', function ($customer) {
        return '<a href="/customer/'.$customer->id.'" class="btn btn-primary">'.$customer->customer_id.'</a>';
    })
    ->addColumn('billing_start', function ($customer) {
        return '<a>'.$customer->billing_start.'</a>';
    })
    ->addIndexColumn()
    // ->addColumn('select', function ($customer) {
    //     if (in_array($customer->status_name->name, ['Active', 'Block'])) {
    //         return '<input type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'">';
    //     }
    //         return ''; // Return empty string if not Active or Block
    //     })
    ->addColumn('plan', function ($customer) {
        return '<a class="text-center">'.$customer->plan_name->name.'('.$customer->plan_name->price.')</a>';
    })
    ->editColumn('id_merchant', function ($customer) {
        return $customer->merchant_name ? 
        '<a class="text-center">' . $customer->merchant_name->name . '</a>' : 
        '<a class="text-center">No Merchant</a>';
    })
    ->addColumn('status_cust', function ($customer) {


        $badgeClass = match ($customer->status_name->name) {
            'Active' => 'badge-success',
            'Inactive' => 'badge-secondary',
            'Block' => 'badge-danger',
            'Company_Properti' => 'badge-primary',
            default => 'badge-warning',
        };
        return '<a class="badge text-white text-center '.$badgeClass.'">'.$customer->status_name->name.'</a>';
    })
    ->addColumn('invoice', function ($customer) {
        $count_inv = new \App\Suminvoice();
        $result = $count_inv->countinv($customer->id);
        return $result >= 1 ? '<a href="/invoice/'.$customer->id.'" title="Invoice" class="btn btn-warning btn-sm">'.$result.'</a>' : '';
    })

    ->editColumn('notification', function ($customer) {
        $label = match ((int) $customer->notification) {
            1       => '<i class="fab fa-whatsapp text-success" title="WhatsApp"></i>',
            2       => '<i class="fas fa-envelope text-primary" title="Email"></i>',
            3       => '<i class="fas fa-mobile-alt text-warning" title="Mobile App"></i>',
            default => '<i class="fas fa-ban text-muted" title="None"></i>',
        };

        return '<a class="text-center">' . $label . '</a>';
    })
    ->addColumn('app_status', function ($customer) {
        if (!empty($customer->fcm_token)) {
            return '<span class="badge badge-success" title="FCM Token: ' . e(substr($customer->fcm_token, 0, 20)) . '..."><i class="fas fa-mobile-alt mr-1"></i>Installed</span>';
        }
        return '<span class="badge badge-secondary"><i class="fas fa-times mr-1"></i>Belum</span>';
    })
    ->rawColumns(['customer_id', 'id_merchant', 'plan', 'billing_start', 'status_cust', 'invoice', 'notification','app_status'])
    ->with('potensial',$potensial)
    ->with('active',$active)
    ->with('inactive',$inactive)
    ->with('block',$block)
    ->with('company_Properti',$company_Properti)
    ->with('unknown',$unknown)
    ->make(true);
    






    
}

public function table_customermerchant(Request $request)
{
    // Start building the query
$id_merchant = \Auth::user()->id_merchant; // Perbaiki nama variabel dari id_idmerchant ke id_merchant

$customerQuery = \App\Customer::select('id', 'customer_id', 'name', 'address', 'id_merchant', 'billing_start', 'isolir_date', 'id_plan', 'id_status', 'id_sale');

if (!is_null($id_merchant)) {
    $customerQuery->where('id_merchant', $id_merchant);
}
    // Apply filtering if the filter is not empty
if (!empty($request->filter)) {
    $filter = $request->filter;
    $parameter = $request->parameter;

    $customerQuery->where($filter, 'LIKE', "%{$parameter}%");

        // Apply status, plan, and merchant filters if provided
    if (!empty($request->id_status)) {
        $customerQuery->where('id_status', $request->id_status);
    }


}

    // Order the results
$customerQuery->orderBy('id', 'DESC');

    // Get the results
return DataTables::of($customerQuery)
->editColumn('customer_id', function ($customer) {
    return '<a>'.$customer->customer_id.'</a>';
})

->addIndexColumn()
->addColumn('select', function ($customer) {
    if (in_array($customer->status_name->name, ['Active', 'Block'])) {
        return '<input type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'">';
    }
            return ''; // Return empty string if not Active or Block
        })

->editColumn('id_merchant', function ($customer) {
    return $customer->merchant_name ? 
    '<a class="text-center">' . $customer->merchant_name->name . '</a>' : 
    '<a class="text-center">No Merchant</a>';
})
->addColumn('status_cust', function ($customer) {
    $badgeClass = match ($customer->status_name->name) {
        'Active' => 'badge-success',
        'Inactive' => 'badge-secondary',
        'Block' => 'badge-danger',
        'Company_Properti' => 'badge-primary',
        default => 'badge-warning',
    };
    return '<a class="badge text-white text-center '.$badgeClass.'">'.$customer->status_name->name.'</a>';
})


->rawColumns(['customer_id', 'id_merchant', 'plan', 'status_cust'])
->make(true);
}


public function table_plan_group(Request $request)
{
    // Start building the query
 // Start building the query
    $customerQuery = \App\Customer::select('id_plan', \DB::raw('count(*) as count'))
    ->groupBy('id_plan');

// Apply filtering if the filter is not empty
    if (!empty($request->filter)) {
        $filter = $request->filter;
        $parameter = $request->parameter;

        if ($filter === 'isolir_date' && !empty($parameter)) {
            $customerQuery->where("customers." . $filter, $parameter);
        } else {
            $customerQuery->where("customers." . $filter, 'LIKE', "%" . $parameter . "%");
        }
    }

    if (!empty($request->id_status)) {
        $customerQuery->where('id_status', $request->id_status);
    }

    if (!empty($request->id_plan)) {
        $customerQuery->where('id_plan', $request->id_plan);
    }

    if (!empty($request->id_merchant)) {
        $customerQuery->where('id_merchant', $request->id_merchant);
    }


$customerQuery->orderBy('id_plan', 'DESC'); // Change 'id' to 'id_plan' since you're grouping by id_plan

// Execute the query and get the results
$results = $customerQuery->get();

    // Get the results
return DataTables::of($customerQuery)
->editColumn('id_plan', function ($customer) {
    return '<a>'.$customer->plan_name->name.'</a>';
})
    // ->addColumn('billing_start', function ($customer) {
    //     return '<a>'.$customer->billing_start.'</a>';
    // })
->addIndexColumn()
    // ->addColumn('select', function ($customer) {
    //     if (in_array($customer->status_name->name, ['Active', 'Block'])) {
    //         return '<input type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'">';
    //     }
    //         return ''; // Return empty string if not Active or Block
    //     })
    // ->addColumn('plan', function ($customer) {
    //     return '<a class="text-center">'.$customer->plan_name->name.'('.$customer->plan_name->price.')</a>';
    // })
    // ->editColumn('id_merchant', function ($customer) {
    //     return $customer->merchant_name ? 
    //     '<a class="text-center">' . $customer->merchant_name->name . '</a>' : 
    //     '<a class="text-center">No Merchant</a>';
    // })
    // ->addColumn('status_cust', function ($customer) {
    //     $badgeClass = match ($customer->status_name->name) {
    //         'Active' => 'badge-success',
    //         'Inactive' => 'badge-secondary',
    //         'Block' => 'badge-danger',
    //         'Company_Properti' => 'badge-primary',
    //         default => 'badge-warning',
    //     };
    //     return '<a class="badge text-white text-center '.$badgeClass.'">'.$customer->status_name->name.'</a>';
    // })
    // ->addColumn('invoice', function ($customer) {
    //     $count_inv = new \App\Suminvoice();
    //     $result = $count_inv->countinv($customer->id);
    //     return $result >= 1 ? '<a href="/invoice/'.$customer->id.'" title="Invoice" class="btn btn-warning btn-sm">'.$result.'</a>' : '';
    // })
    // ->addColumn('action', function ($customer) {
    //     $create_ticket = url('/ticket/'.$customer->id.'/create');
    //     return '<a href="'.$create_ticket.'" class="btn btn-success">Create Ticket</a>';
    // })
->rawColumns(['id_plan','count'])
->make(true);
}

public function trash()
{
    $customers = \App\Customer::onlyTrashed()
        ->with(['merchant_name', 'plan_name', 'status_name', 'tags'])
        ->get();
    $status = \App\Statuscustomer::pluck('name', 'id');
    $merchant = \App\Merchant::pluck('name', 'id');
    $plan = \App\Plan::pluck('name', 'id');
    $tags = \App\Tag::pluck('name', 'id');
    
    // Chart data - deleted customers per day (last 30 days)
    $startDate = Carbon::now()->subDays(30)->startOfDay();
    $endDate = Carbon::now()->endOfDay();
    
    $dailyDeletedCustomers = \App\Customer::onlyTrashed()
        ->whereBetween('deleted_at', [$startDate, $endDate])
        ->selectRaw('DATE(deleted_at) as date, COUNT(*) as count')
        ->groupBy(DB::raw('DATE(deleted_at)'))
        ->orderBy('date')
        ->get();
    
    $totalDeletedCustomers = $dailyDeletedCustomers->sum('count');
    
    // Plan chart data - deleted customers grouped by plan
    $planChartData = \App\Customer::onlyTrashed()
        ->select('id_plan', DB::raw('COUNT(*) as count'))
        ->groupBy('id_plan')
        ->with('plan_name')
        ->get();
    
    $planLabels = [];
    $planData = [];
    
    foreach ($planChartData as $item) {
        $planLabels[] = $item->plan_name ? $item->plan_name->name : 'No Plan';
        $planData[] = $item->count;
    }
    
    // Merchant chart data - deleted customers grouped by merchant
    $merchantChartData = \App\Customer::onlyTrashed()
        ->select('id_merchant', DB::raw('COUNT(*) as count'))
        ->groupBy('id_merchant')
        ->with('merchant_name')
        ->get();
    
    $merchantLabels = [];
    $merchantData = [];
    
    foreach ($merchantChartData as $item) {
        $merchantLabels[] = $item->merchant_name ? $item->merchant_name->name : 'No Merchant';
        $merchantData[] = $item->count;
    }
    
    return view ('customer/trash', compact('customers', 'status', 'plan', 'merchant', 'tags', 'dailyDeletedCustomers', 'totalDeletedCustomers', 'planLabels', 'planData', 'merchantLabels', 'merchantData'));
}

public function trashData(Request $request)
{
    $query = \App\Customer::onlyTrashed();

    // Apply filters
    if ($request->has('filter') && $request->has('parameter') && $request->parameter != '') {
        $filter = $request->filter;
        $parameter = $request->parameter;
        $query->where($filter, 'like', '%' . $parameter . '%');
    }

    if ($request->has('id_merchant') && $request->id_merchant != '') {
        $query->where('id_merchant', $request->id_merchant);
    }

    if ($request->has('id_status') && $request->id_status != '') {
        $query->where('id_status', $request->id_status);
    }

    if ($request->has('id_plan') && $request->id_plan != '') {
        $query->where('id_plan', $request->id_plan);
    }

    return DataTables::of($query)
        ->addIndexColumn()
        ->addColumn('customer_id', function($row) {
            return '<span class="badge badge-secondary">' . $row->customer_id . '</span>';
        })
        ->addColumn('address', function($row) {
            return '<small class="text-muted">' . $row->address . '</small>';
        })
        ->addColumn('merchant', function($row) {
            if ($row->id_merchant) {
                return $row->merchant_name->name;
            }
            return '<span class="badge badge-light">No Merchant</span>';
        })
        ->addColumn('plan', function($row) {
            if ($row->id_plan) {
                return $row->plan_name->name . ' <small class="text-muted">(Rp ' . number_format($row->plan_name->price) . ')</small>';
            }
            return '<span class="badge badge-light">No Plan</span>';
        })
        ->addColumn('status', function($row) {
            if ($row->id_status) {
                $badge_sts = "badge-secondary";
                if ($row->status_name->name == 'Active') $badge_sts = "badge-success";
                elseif ($row->status_name->name == 'Inactive') $badge_sts = "badge-secondary";
                elseif ($row->status_name->name == 'Block') $badge_sts = "badge-danger";
                elseif ($row->status_name->name == 'Company_Properti') $badge_sts = "badge-primary";
                
                return '<span class="badge ' . $badge_sts . '">' . $row->status_name->name . '</span>';
            }
            return '<span class="badge badge-light">No Status</span>';
        })
        ->addColumn('deleted_at', function($row) {
            return '<small class="text-danger">' . $row->deleted_at->diffForHumans() . '</small>';
        })
        ->addColumn('action', function($row) {
            $merchant = $row->id_merchant ? $row->merchant_name->name : 'No Merchant';
            $status = $row->id_status ? $row->status_name->name : 'No Status';
            $plan = $row->id_plan ? $row->plan_name->name : 'No Plan';
            $price = $row->id_plan ? number_format($row->plan_name->price) : '0';
            
            return '
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-info btn-sm view-detail" 
                      data-id="' . $row->id . '"
                      data-customer-id="' . $row->customer_id . '"
                      data-name="' . $row->name . '"
                      data-email="' . ($row->email ?? '-') . '"
                      data-phone="' . ($row->phone ?? '-') . '"
                      data-address="' . $row->address . '"
                      data-coordinate="' . ($row->coordinate ?? '') . '"
                      data-merchant="' . $merchant . '"
                      data-status="' . $status . '"
                      data-plan="' . $plan . '"
                      data-price="' . $price . '"
                      data-deleted="' . $row->deleted_at->format('d M Y H:i') . '"
                      data-toggle="modal" 
                      data-target="#detailModal"
                      title="View Detail">
                <i class="fas fa-eye"></i>
              </button>
              
              <form action="/customer/restore/' . $row->id . '" method="POST" class="d-inline item-restore">
                ' . method_field('patch') . '
                ' . csrf_field() . '
                <button title="Restore Customer" type="submit" class="btn btn-warning btn-sm">
                  <i class="fas fa-undo"></i>
                </button>
              </form>
            </div>';
        })
        ->rawColumns(['customer_id', 'address', 'merchant', 'plan', 'status', 'deleted_at', 'action'])
        ->make(true);
}

public function restore($id)
{


        //
  $userdata = \App\Customer::onlyTrashed()->findOrFail($id);

  if (!is_null($userdata)) {

      $result=   $userdata->restore();
  }

  if($result)
  {

   return redirect ('/customer/'.$id)->with('success','Item restore successfully!'); 
}
else
{
 return redirect ('/customer/trash')->with('error','Item restore failed!'); 
}
}




public function createinv()
{
        //


  $status = \App\Statuscustomer::pluck('name', 'id');
  $plan = \App\Plan::pluck('name', 'id');
  $search_var ='';
  return view ('invoice/index',['status'=>$status, 'plan'=>$plan, 'search_var'=>$search_var]);

      //
      // $customer = \App\Customer::orderBy('id','DESC')
      //       ->where('id_status', '2')
      //       ->orWhere('id_status', '4') 
      //  ->get();



      //   return view ('invoice/createinv',['customer' =>$customer]);



}







public function table_invoice(Request $request)
{
    $month = now()->format('mY');

    $customers = \App\Customer::select(
        'customers.id',
        'customers.customer_id',
        'customers.name',
        'customers.address',
        'customers.billing_start',
        'customers.id_plan',
        'customers.id_status'
    )
    ->with(['plan_name', 'status_name', 'invoices']) // Tambahkan eager loading invoices
    ->where(function ($q) {
        $q->where('id_status', '2')->orWhere('id_status', '4'); // Active & Blocked
    });

    // ✅ Filter: apakah sudah punya invoice bulan ini?
    if ($request->has('has_invoice')) {
        $hasInvoice = $request->has_invoice;

        if ($hasInvoice == 'yes') {
            $customers->whereHas('invoices', function ($query) use ($month) {
                $query->where('periode', $month)
                ->where('monthly_fee', 1)
                ->where('payment_status', '!=', 5);
            });
        } elseif ($hasInvoice == 'no') {
            $customers->whereDoesntHave('invoices', function ($query) use ($month) {
                $query->where('periode', $month)
                ->where('monthly_fee', 1)
                ->where('payment_status', '!=', 5);
            });
        }
    }

    // ✅ Filter opsional lain
    if (!empty($request->filter) && !empty($request->parameter)) {
        $customers->where($request->filter, 'LIKE', '%' . $request->parameter . '%');
    }

    if (!empty($request->id_status)) {
        $customers->where('id_status', $request->id_status);
    }

    if (!empty($request->id_plan)) {
        $customers->where('id_plan', $request->id_plan);
    }

    return DataTables::of($customers)
    ->addIndexColumn()
    ->editColumn('customer_id', fn($c) =>
        '<a href="/customer/' . $c->id . '" class="btn btn-primary">' . $c->customer_id . '</a>'
    )
    ->addColumn('plan', fn($c) =>
        $c->plan_name ? $c->plan_name->name . ' (' . $c->plan_name->price . ')' : 'N/A'
    )
    ->addColumn('status_cust', function ($c) {
        $badge = match($c->status_name->name) {
            'Active' => 'badge-success',
            'Inactive' => 'badge-secondary',
            'Block' => 'badge-danger',
            'Company_Properti' => 'badge-primary',
            default => 'badge-warning'
        };
        return '<a class="badge text-white text-center ' . $badge . '">' . $c->status_name->name . '</a>';
    })
    ->addColumn('invoice', function ($c) use ($month) {
        $hasInvoice = $c->invoices
        ->where('periode', $month)
        ->where('monthly_fee', 1)
        ->where('payment_status', '!=', 5)
        ->isNotEmpty();

        // return $hasInvoice
        // ? '<a class="badge text-white text-center badge-secondary"> Created</a>'
        // : '<div id="inv' . $c->id . '"><button type="button" onclick="myFunction(' . $c->id . ')" class="btn btn-success">Create</button></div>';

        return $hasInvoice
        ? '<a class="badge text-white text-center badge-secondary">Created</a>'
        : '<a href="/invoice/' . $c->id . '/create" class="btn btn-success">Create</a>';
    })
    ->rawColumns(['customer_id', 'plan', 'status_cust', 'invoice'])
    ->make(true);
}



// public function table_invoice(Request $request){

//    $month = now()->format('mY');



//    if (empty($request->filter))
//    {
//       $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')
//       ->where('id_status', '2')
//       ->orWhere('id_status', '4') 
//       ->orderBy('id','DESC');


//   }
//   elseif ((empty($request->id_status))and (empty($request->id_plan)))
//   {
//     $filter =$request->filter ;
//     $parameter =$request->parameter ;

//     $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')

//     ->where($filter, 'LIKE', "%".$parameter."%") 
//     ->where('id_status', '2')
//     ->orWhere('id_status', '4') 

//     ->orderBy('id', 'DESC');


// }
// elseif ((empty($request->id_status))and (!empty($request->id_plan)))
// {
//     $filter =$request->filter ;
//     $parameter =$request->parameter ;

//     $id_plan =$request->id_plan ;
//     $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')

//     ->where($filter, 'LIKE', "%".$parameter."%") 
//     ->where('id_status', '2')
//     ->orWhere('id_status', '4') 

//     ->Where('id_plan', $id_plan) 
//     ->orderBy('id', 'DESC');
// }
// elseif ((!empty($request->id_status))and (empty($request->id_plan)))
// {
//     $filter =$request->filter ;
//     $parameter =$request->parameter ;
//     $id_status =$request->id_status ;

//     $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')

//     ->where($filter, 'LIKE', "%".$parameter."%") 
//     ->Where('id_status', $id_status) 
//     ->where('id_status', '2')
//     ->orWhere('id_status', '4') 
//     ->orderBy('id', 'DESC');
// }
// else
// {
//     $filter =$request->filter ;
//     $parameter =$request->parameter ;
//     $id_status =$request->id_status ;
//     $id_plan =$request->id_plan ;
//     $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')

//     ->where($filter, 'LIKE', "%".$parameter."%") 
//     ->Where('id_status', $id_status) 
//     ->Where('id_plan', $id_plan) 
// ->whereIn('id_status', [2, 4])
//     ->orderBy('id', 'DESC');
// }








// return DataTables::of($customer)


// ->editColumn('customer_id',function($customer){
//     return '<a href="/customer/'.$customer->id.'" class="btn btn-primary">'.$customer->customer_id.'</a>';
// })
// ->addIndexColumn()

// ->addColumn('plan', function($customer){

//   return '<a class="text-center">'.$customer->plan_name->name.'('.$customer->plan_name->price.') </a>';

// })
// ->addColumn('status_cust', function($customer){
//   if ($customer->status_name->name == 'Active')
//     {$badge_sts = "badge-success";}
// elseif ($customer->status_name->name == 'Inactive')
//    {  $badge_sts = "badge-secondary";}
// elseif ($customer->status_name->name == 'Block')
//     {     $badge_sts = "badge-danger";}
// elseif ($customer->status_name->name == 'Company_Properti')
//  {$badge_sts = "badge-primary";}
// else
//  {$badge_sts = "badge-warning";}

// return ' <a class="badge text-white text-center  '.$badge_sts.'">'.$customer->status_name->name.'</a>';


// })
// ->addColumn('invoice',function($customer)
// {
//   $check_inv = new \App\Invoice();
//   $result = $check_inv->checkinv($customer->id);
//   if ( $result >= 1)
//   {

//       return '<a class="badge text-white text-center  badge-secondary"> Created</a>';
//   }else
//   {





//       return '<div id=inv'.$customer->id.'><button type="button" onclick="myFunction('.$customer->id.')" class="btn btn-success">Create</button></div>';




//   }

// })

// ->rawColumns(

//   ['DT_RowIndex','customer_id','plan','billing_start','status_cust','invoice'])
// ->make(true);
// }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $status = \App\Statuscustomer::pluck('name', 'id');
        $distpoint = \App\Distpoint::pluck('name', 'id');
        $distrouter = \App\Distrouter::pluck('name', 'id');
        $olt = \App\Olt::pluck('name', 'id');
        $sale = \App\Sale::pluck('name', 'id');
        $merchant = \App\Merchant::pluck('name', 'id');
        $plan = \App\Plan::select('name', 'id', 'price')
        ->orderBy('price', 'ASC')
        ->get();
        //
//         $config['center'] = env('COORDINATE_CENTER');
//         $config['zoom'] = '13';
// //$this->googlemaps->initialize($config);

//         $marker = array();
//         $marker['position'] = env('COORDINATE_CENTER');
//         $marker['draggable'] = true;
//         $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

//         app('map')->initialize($config);
        
//         app('map')->add_marker($marker);
//         $map = app('map')->create_map();

        return view ('customer/create',['status' => $status, 'sale'=>$sale,'distpoint' => $distpoint, 'distrouter' => $distrouter, 'olt' => $olt, 'plan' => $plan, 'merchant'=>$merchant, 'addons' => \App\Addon::orderBy('name')->get()  ] );
        // return view ('customer/create',['map' => $map, 'status' => $status, 'sale'=>$sale,'distpoint' => $distpoint, 'distrouter' => $distrouter, 'olt' => $olt, 'plan' => $plan, 'merchant'=>$merchant  ] );

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //dd($request);
        
        // Conditional validation based on status
        $isPotensial = $request->id_status == 1;
        
        $request ->validate([

            'customer_id' => 'required|unique:customers',
            'name' => 'required',
            'id_card'  => 'nullable',
            'contact_name' => 'required',
            'phone' => 'required|digits_between:6,15',
            'address' => 'required',
            'npwp'  => 'nullable',
            'tax' => $isPotensial ? 'nullable|numeric' : 'required|numeric',
            'billing_start' => $isPotensial ? 'nullable|date' : 'required|date',
            'id_olt' => $isPotensial ? 'nullable|integer' : 'required|integer',
            'id_distpoint' => $isPotensial ? 'nullable|integer' : 'required|integer',
            'id_distrouter' => $isPotensial ? 'nullable|integer' : 'required|integer',
            'email' => 'nullable|email',
            'merchant'  => 'nullable',
            'lead_source' => 'nullable|string|max:50',
            'lead_notes' => 'nullable|string',
            'expected_close_date' => 'nullable|date',
            'conversion_probability' => 'nullable|integer|min:0|max:100',
        ], [
            'phone.digits_between' => 'Nomor telepon hanya boleh berisi angka (6-15 digit), tanpa tanda + atau spasi.',
            'phone.required'       => 'Nomor telepon wajib diisi.',
            'email.email'          => 'Format email tidak valid.',
        ]);

        // Set default values for optional fields (only for Potensial status)
        if ($isPotensial) {
            if (empty($request->tax)) {
                $request->merge(['tax' => 0]);
            }
            if (empty($request->billing_start)) {
                $request->merge(['billing_start' => date('Y-m-d')]);
            }
        }

        $messege ='';
        try
        {

            $status = \App\Statuscustomer::Where('id',$request->id_status)->first();
            if ((!empty($request->id_distrouter)) and (!empty($request->id_plan)) and ($status->name == 'Active')) 
            {

              $distrouter = \App\Distrouter::Where('id',$request->id_distrouter)->first();
              $plan = \App\Plan::Where('id',$request->id_plan)->first();


              \App\Distrouter::mikrotik_addprofile($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$plan->name,$plan->speed,$plan->description);
              \App\Distrouter::mikrotik_addsecreate($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$request->pppoe,$request->password,$plan->name,$request->name);




          }              
      } catch (Exception $e)

      { 
        $messege =" Field to Access to Router, please check connection between System and Router";

    } finally {


        try{
         \App\Customer::create($request->all());
     }
     catch (Exeption $e)
     {
         $messege .=" * Field add user on system, please remove manually user on Router";
         return redirect ('/home')->with('warning',$messege);
     }finally {

        $messege .=" *  User Successfuly added on system ";
        $customer_check = \App\Customer::where('customer_id', $request->customer_id) 
        
        ->first();
        // Sync add-ons
        if ($customer_check) {
            $customer_check->addons()->sync($request->input('addons', []));
        }
        return redirect ('/customer/'.$customer_check->id)->with('success',$messege);
    }

}


     //  else
     //  {
     //     $messege ='Item created successfully ** User In Distribution Router NOT created ** !!';
     // }


       // $this->mikrotik($request->name,$request->customer_id,$request->password);
       // \App\Distrouter::mikrotik_addsecreate($ip,$user,$pass,$port,$request->customer_id,$pass,$request->name);



   // return redirect ('/customer/')->with('success',$messege);
        // return view ('customer/'.$customer_check->id);





}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // Override customer attribute with lists for conversion modal
    private function addListsToCustomer($customer)
    {
        $customer->plan_list = \App\Plan::select('name', 'id', 'price')
            ->orderBy('price', 'ASC')
            ->get();
        $customer->router_list = \App\Distrouter::pluck('name', 'id');
        $customer->olt_list = \App\Olt::pluck('name', 'id');
        $customer->distpoint_list = \App\Distpoint::pluck('name', 'id');
        return $customer;
    }

    public function show($id)
    {
       $customer = Customer::with([
        'device',
        'status_name',
        'merchant_name',
        'sale_name',
        'plan_name',
        'addons',
        'distrouter',
        'olt_name',
        'distpoint_name',
        'file',
        'tags'
    ])->findOrFail($id);

       // Add lists for conversion modal
       $customer = $this->addListsToCustomer($customer);

       $countpppoe = \App\Customer::where('pppoe', $customer->pppoe)->count();

       $countpppoe = ($countpppoe > 1) ? $countpppoe : 1;

       if  (\App\Customer::findOrFail($id) ->coordinate == null)
       {
        $coordinate = tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));
    }
    else
    {
        $coordinate =\App\Customer::findOrFail($id) ->coordinate;
    }


    $config['center'] = $coordinate;
    $config['zoom'] = '13';

    $center = [
        'coordinate' => $coordinate,
        'name' => $customer->name,
        'zoom' => 13
    ];
    $locations = [
        ['customer' => $customer->coordinate, 'name' => $customer->name],
    ];

    // Auto-init workflow steps dari template jika customer Potensial belum punya steps
    if ($customer->id_status == 1) {
        $hasSteps = \App\CustomerStep::where('customer_id', $id)->exists();
        if (!$hasSteps) {
            $templates = \App\LeadWorkflow::orderBy('order')->get();
            foreach ($templates as $i => $tpl) {
                \App\CustomerStep::create([
                    'customer_id' => $id,
                    'name'        => $tpl->name,
                    'position'    => $i + 1,
                ]);
            }
            // Set step pertama sebagai aktif
            $first = \App\CustomerStep::where('customer_id', $id)->orderBy('position')->first();
            if ($first) {
                \App\Customer::where('id', $id)->update(['current_step_id' => $first->id]);
                $customer->current_step_id = $first->id;
            }
        }
    }

    // Tags untuk customer
    $customerTags = $customer->tags->pluck('name', 'id')->toArray();
    $alltags = \App\Tag::pluck('name', 'id');

    return view('customer.show', [
        'customer' => $customer,
        'countpppoe' => $countpppoe,
        'locations' => $locations,
        'center' => $center,
        'customerTags' => $customerTags,
        'alltags' => $alltags,
        'leadUpdates' => $customer->id_status == 1 ? \App\LeadUpdate::where('id_customer', $id)->orderBy('created_at', 'desc')->get() : collect([]),
        'workflowStages' => $customer->id_status == 1 ? \App\LeadWorkflow::orderBy('order')->get() : collect([]),
        'customerSteps' => $customer->id_status == 1 ? \App\CustomerStep::where('customer_id', $id)->orderBy('position')->get() : collect([]),
        'unpaidInvoiceCount' => \App\Invoice::where('id_customer', $id)->where('payment_status', 0)->count(),
    ]);
}

    public function updateTags(Request $request, $id)
    {
        $customer = \App\Customer::findOrFail($id);
        $tags = $request->input('tags', []);
        $customer->tags()->sync($tags);
        return redirect()->back()->with('success', 'Tags customer berhasil diperbarui.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $status = \App\Statuscustomer::pluck('name', 'id');
        $distpoint = \App\Distpoint::pluck('name', 'id');
        $distrouter = \App\Distrouter::pluck('name', 'id');
        $sale = \App\Sale::pluck('name', 'id');
        $plan = \App\Plan::select('name', 'id', 'price')
        ->orderBy('price', 'ASC')
        ->get();
        $merchant = \App\Merchant::pluck('name', 'id');
        $olt = \App\Olt::pluck('name', 'id');

        // $topologycustomer = \App\topologycustomer::findOrFail($id);
       //  $customer_coordinate = \App\Customer::findOrFail($id);


        if  (\App\Customer::findOrFail($id)->coordinate == null)
        {
            $coordinate = tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));
        }
        else
        {
            $coordinate =\App\Customer::findOrFail($id)->coordinate;
        }
        //
        $config['center'] =  $coordinate;
        $config['zoom'] = '13';
//$this->googlemaps->initialize($config);

        $marker = array();
        $marker['position'] = $coordinate;
        $marker['draggable'] = true;
        $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

        app('map')->initialize($config);
        
        app('map')->add_marker($marker);
        $map = app('map')->create_map();

        
        return view ('customer/edit',['customer' => \App\Customer::findOrFail($id),'map' => $map, 'status' => $status, 'distpoint' => $distpoint,'sale' =>$sale, 'distrouter' => $distrouter, 'plan' => $plan, 'olt' =>$olt, 'merchant'=>$merchant, 'addons' => \App\Addon::orderBy('name')->get(), 'customerAddons' => \App\Customer::findOrFail($id)->addons->pluck('id')->toArray() ] );

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */





//======================================================================================

//     public function table_unpaid_customer(Request $request){


//         if (empty($request->filter))
//         {
//          $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
//          ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
//          ->where("suminvoices.payment_status", "=", 0)
//          ->where("customers.id_status", "=", 2)
//          ->groupBy('customers.id');
//      }
//      else
//      {

//         $filter = $request->filter;
//         $parameter = $request->parameter;
//         $id_status = $request->id_status;
//         // $id_plan = $request->id_plan;
//         $deleted_at = $request->deleted_at;

//         if($deleted_at == "yes")

//         {
//             if(empty($id_status)){
//                 $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status','customers.deleted_at')
//                 ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
//                 ->where("suminvoices.payment_status", "=", 0)
//                 ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
//                 ->whereNotNull("customers.deleted_at")
//                 ->groupBy('customers.id')
//                 ->withTrashed();

//             } 
//             else
//             {
//                 $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status','customers.deleted_at')
//                 ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
//                 ->where("suminvoices.payment_status", "=", 0)
//                 ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
//                 ->where("customers.id_status", '=', $id_status)
//                 ->whereNotNull("customers.deleted_at")
//                 ->groupBy('customers.id')
//                 ->withTrashed();
//             }

//         }
//         else
//         {
//             if (empty($id_status)) {
//               $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
//               ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
//               ->where("suminvoices.payment_status", "=", 0)
//               ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
//               ->groupBy('customers.id'); 
//           }
//           else
//           {
//              $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
//              ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
//              ->where("suminvoices.payment_status", "=", 0)
//              ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
//              ->where("customers.id_status", "=", $id_status)
//         // ->where("customers.id_status", "=", $id_status)
//         // ->where("customers.id_plan", "=", $id_plan)
//              ->groupBy('customers.id');   
//          }



//      }



//  }




//  return DataTables::of($customer)

//  ->editColumn('customer_id',function($customer){
//     if (empty($customer->deleted_at)) {
//         return '<a href="/customer/'.$customer->id.'" class="btn btn-primary">'.$customer->customer_id.'</a>';
//     }
//     else
//     {
//         return '<a href="/customer/'.$customer->id.'" class="btn btn-secondary">'.$customer->customer_id.'</br>'.$customer->deleted_at.'</a>';
//     }

// })
//  ->addColumn('billing_start',function($customer){
//     return '<a>'.$customer->billing_start.'</a>';
// })  
//  ->addIndexColumn()
//  ->addColumn('select', function($customer)
//  {
//   if (($customer->status_name->name == 'Active')Or ($customer->status_name->name == 'Block'))

//   {
//      return '<input   type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'"></td>';
//  }

//  else
//  {}

// })
//  ->addColumn('plan', function($customer){

//   return '<a class="text-center">'.$customer->plan_name->name.'('.$customer->plan_name->price.') </a>';

// })
//  ->addColumn('status_cust', function($customer){
//   if ($customer->status_name->name == 'Active')
//     {$badge_sts = "badge-success";}
// elseif ($customer->status_name->name == 'Inactive')
//  {  $badge_sts = "badge-secondary";}
// elseif ($customer->status_name->name == 'Block')
//     {     $badge_sts = "badge-danger";}
// elseif ($customer->status_name->name == 'Company_Properti')
//    {$badge_sts = "badge-primary";}
// else
//    {$badge_sts = "badge-warning";}

// return '<a class="badge text-white text-center  '.$badge_sts.'">'.$customer->status_name->name.'</a>';


// })
//  ->addColumn('invoice',function($customer)
//  {
//   $count_inv = new \App\Suminvoice();
//   $result = $count_inv->countinv($customer->id);
//   if ( $result >= 1)
//   {

//     return ' <a href="/invoice/'.$customer->id.'" title="Invoice" class="btn btn-warning btn-sm text-center  "> '.$result. '</a>';
// }

// })
//  ->addColumn('Total Inv',function($customer){
//     $create_ticket = url('/ticket/'.$customer->id.'/create');

//     $button = '<a href="'.$create_ticket.'" class="btn btn-success">Create Ticket</a>';
//     $balance_inv = new \App\Suminvoice();
//     $result = $balance_inv->balanceinv($customer->id);
//     $balance = json_decode($result);
//     return ($balance[0]->total);


// })
//  ->rawColumns(['DT_RowIndex','customer_id','plan','billing_start','status_cust','select','invoice','action'])
//  ->make(true);
// }



    public function table_unpaid_customer(Request $request) {
        // $customerQuery = \App\Customer::select(
        //     'customers.id',
        //     'customers.customer_id',
        //     'customers.name',
        //     'customers.id_merchant',
        //     'customers.address',
        //     'customers.billing_start',
        //     'isolir_date',
        //     'customers.id_plan',
        //     'customers.tax',
        //     'customers.id_status',
        //     'customers.deleted_at'
        // )
        // ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
        // ->where("suminvoices.payment_status", "=", 0)
        // ->groupBy('customers.id');

       $customerQuery = \App\Customer::select(
        'customers.id',
        'customers.customer_id',
        'customers.name',
        'customers.id_merchant',
        'customers.address',
        'customers.billing_start',
        'isolir_date',
        'customers.id_plan',
        'customers.tax',
        'customers.id_status',
        'customers.deleted_at',
        DB::raw('COUNT(suminvoices.id) as unpaid_invoice_count'),
        DB::raw('SUM(suminvoices.total_amount) as unpaid_invoice_total')
    )
       ->join('suminvoices', function ($join) {
        $join->on('suminvoices.id_customer', '=', 'customers.id')
        ->where('suminvoices.payment_status', 0);
    })
       ->groupBy(
        'customers.id',
        'customers.customer_id',
        'customers.name',
        'customers.id_merchant',
        'customers.address',
        'customers.billing_start',
        'isolir_date',
        'customers.id_plan',
        'customers.tax',
        'customers.id_status',
        'customers.deleted_at'
    );



    // Apply filters if provided
       if (!empty($request->filter)) {
        $filter = $request->filter;
        $parameter = $request->parameter;
        $id_status = $request->id_status;
        $id_plan = $request->id_plan;
        $deleted_at = $request->deleted_at;
        $countinv=$request->countinv;

        if ($filter === 'isolir_date' && !empty($parameter) ) {
            $customerQuery->where("customers." . $filter, $parameter);
        } else {
            $customerQuery->where("customers." . $filter, 'LIKE', "%" . $parameter . "%");
        }

        if ($deleted_at == "yes") {
            $customerQuery->whereNotNull("customers.deleted_at");
        } else {
            $customerQuery->whereNull("customers.deleted_at");
        }

        if (!empty($id_status)) {
            $customerQuery->where("customers.id_status", "=", $id_status);
        }
        if (!empty($id_plan)) {
            $customerQuery->where("customers.id_plan", "=", $id_plan);
        }
        if (!empty($request->id_merchant)) {
            $customerQuery->where('id_merchant', $request->id_merchant);
        }
    //         if (!empty($countinv)) {
    //             $customerQuery->whereHas('suminvoices', function ($query) use ($countinv) {
    // $query->where('payment_status', 0)  // Menambahkan kondisi untuk status invoice 0
    //       ->havingRaw('COUNT(*) = ?', [$countinv]);  // Menghitung jumlah invoice
    //   });
    //         }

        if (!empty($countinv)) {
            $customerQuery->having('unpaid_invoice_count', '=', $countinv);
        }
    } else {
        // Default condition for id_status if no filter is applied
        $customerQuery->where("customers.id_status", "=", 2);
    }

    // Execute the query
    $customers = $customerQuery->withTrashed()->get();

    return DataTables::of($customers)
    ->editColumn('customer_id', function($customer) {
        $btnClass = empty($customer->deleted_at) ? 'btn-primary' : 'btn-secondary';
        return '<a href="/customer/' . $customer->id . '" class="btn ' . $btnClass . '">' . $customer->customer_id . '</a>';
    })
    ->addColumn('billing_start', function($customer) {
        return '<a>' . $customer->billing_start . '</a>';
    })
    ->addIndexColumn()

    ->editColumn('id_merchant', function ($customer) {
        return $customer->merchant_name ? 
        $customer->merchant_name->name : 
        'No Merchant';
    })
    ->addColumn('plan', function($customer) {
        return '<a class="text-center">' . $customer->plan_name->name . '(' . $customer->plan_name->price . ')</a>';
    })
    ->addColumn('status_cust', function($customer) {
        $badgeClass = match ($customer->status_name->name) {
            'Active' => 'badge-success',
            'Inactive' => 'badge-secondary',
            'Block' => 'badge-danger',
            'Company_Properti' => 'badge-primary',
            default => 'badge-warning',
        };
        return '<a class="badge text-white text-center ' . $badgeClass . '">' . $customer->status_name->name . '</a>';
    })


//         ->addColumn('invoice', function($customer) {
//             $count_inv = new \App\Suminvoice();
//             $result = $count_inv->countinv($customer->id);
//             if ($result < 1) {
//                 return '';
//             }

//     $btnClass = 'btn-info'; // Default untuk $result = 1

//     if ($result == 2) {
//         $btnClass = 'btn-warning';
//     } elseif ($result >= 3) {
//         $btnClass = 'btn-danger';
//     }

//     return '<a href="/invoice/' . $customer->id . '" title="Invoice" class="btn '.$btnClass.'  btn-sm text-center">' . $result . '</a>';

// })


//         ->addColumn('Total Inv', function($customer) {
//             $create_ticket = url('/ticket/' . $customer->id . '/create');
//             $balance_inv = new \App\Suminvoice();
//             $result = $balance_inv->balanceinv($customer->id);
//             $balance = json_decode($result);
//             return number_format(($balance[0]->total ?? 0), 2, ',', '.'); // Default to 0 if balance is null
//         })



        // ->addColumn('invoice', function ($customer) {
        //     $result = \App\Suminvoice::countinv($customer->id);

        //     if ($result < 1) {
        //         return '';
        //     }

        //     $btnClass = match (true) {
        //         $result == 1 => 'btn-info',
        //         $result == 2 => 'btn-warning',
        //         $result >= 3 => 'btn-danger',
        //         default => 'btn-secondary'
        //     };

        //     return '<a href="/invoice/' . $customer->id . '" title="Invoice" class="btn ' . $btnClass . ' btn-sm text-center">' . $result . '</a>';
        // })

    ->addColumn('invoice', function ($customer) {
        $result = $customer->unpaid_invoice_count ?? 0;

        $btnClass = match (true) {
            $result == 1 => 'btn-info',
            $result == 2 => 'btn-warning',
            $result >= 3 => 'btn-danger',
            default => 'btn-secondary'
        };

        return '<a href="/invoice/' . $customer->id . '" title="Invoice" class="btn ' . $btnClass . ' btn-sm text-center">' . $result . '</a>';
    })
    ->addColumn('Total Inv', function ($customer) {
       return number_format($customer->unpaid_invoice_total ?? 0, 2, ',', '.');
   })
    ->rawColumns(['customer_id', 'plan', 'billing_start', 'status_cust', 'select', 'invoice'])
    ->make(true);
}


//======================================================================================

//======================================================================================

public function table_isolir_customer(Request $request){


    if (empty($request->filter))
    {
     $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
     ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
     ->where("suminvoices.payment_status", "=", 0)
     ->where("customers.id_status", "=", 2)
     ->groupBy('customers.id');
 }
 else
 {

    $filter = $request->filter;
    $parameter = $request->parameter;
    $id_status = $request->id_status;
        // $id_plan = $request->id_plan;
    $deleted_at = $request->deleted_at;

    if($deleted_at == "yes")

    {
        if(empty($id_status)){
            $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status','customers.deleted_at')
            ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
            ->where("suminvoices.payment_status", "=", 0)
            ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
                // ->whereNotNull("customers.deleted_at")
            ->groupBy('customers.id');
                // ->withTrashed();

        } 
        else
        {
            $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status','customers.deleted_at')
            ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
            ->where("suminvoices.payment_status", "=", 0)
            ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
            ->where("customers.id_status", '=', $id_status)
                // ->whereNotNull("customers.deleted_at")
            ->groupBy('customers.id');
                // ->withTrashed();
        }

    }
    else
    {
        if (empty($id_status)) {
          $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
          ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
          ->where("suminvoices.payment_status", "=", 0)
          ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
          ->groupBy('customers.id'); 
      }
      else
      {
         $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
         ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
         ->where("suminvoices.payment_status", "=", 0)
         ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
         ->where("customers.id_status", "=", $id_status)
        // ->where("customers.id_status", "=", $id_status)
        // ->where("customers.id_plan", "=", $id_plan)
         ->groupBy('customers.id');   
     }



 }



}




return DataTables::of($customer)

->editColumn('customer_id',function($customer){
    if (empty($customer->deleted_at)) {
        return '<a href="/customer/'.$customer->id.'" class="btn btn-primary">'.$customer->customer_id.'</a>';
    }
    else
    {
        return '<a href="/customer/'.$customer->id.'" class="btn btn-secondary">'.$customer->customer_id.'</br>'.$customer->deleted_at.'</a>';
    }

})
->addColumn('billing_start',function($customer){
    return '<a>'.$customer->billing_start.'</a>';
})  
->addIndexColumn()
->addColumn('select', function($customer)
{
  if (($customer->status_name->name == 'Active')Or ($customer->status_name->name == 'Block'))

  {
     return '<input   type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'"></td>';
 }

 else
 {}

})
->addColumn('plan', function($customer){

  return '<a class="text-center">'.$customer->plan_name->name.'('.$customer->plan_name->price.') </a>';

})
->addColumn('status_cust', function($customer){
  if ($customer->status_name->name == 'Active')
    {$badge_sts = "badge-success";}
elseif ($customer->status_name->name == 'Inactive')
 {  $badge_sts = "badge-secondary";}
elseif ($customer->status_name->name == 'Block')
    {     $badge_sts = "badge-danger";}
elseif ($customer->status_name->name == 'Company_Properti')
   {$badge_sts = "badge-primary";}
else
   {$badge_sts = "badge-warning";}

return '<a class="badge text-white text-center  '.$badge_sts.'">'.$customer->status_name->name.'</a>';


})
->addColumn('invoice',function($customer)
{
  $count_inv = new \App\Suminvoice();
  $result = $count_inv->countinv($customer->id);
  if ( $result >= 1)
  {

    return ' <a href="/invoice/'.$customer->id.'" title="Invoice" class="btn btn-warning btn-sm text-center  "> '.$result. '</a>';
}

})
->addColumn('Total Inv',function($customer){
    $create_ticket = url('/ticket/'.$customer->id.'/create');

    $button = '<a href="'.$create_ticket.'" class="btn btn-success">Create Ticket</a>';
    $balance_inv = new \App\Suminvoice();
    $result = $balance_inv->balanceinv($customer->id);
    $balance = json_decode($result);
    return ($balance[0]->total);


})
->rawColumns(['DT_RowIndex','customer_id','plan','billing_start','status_cust','select','invoice','action'])
->make(true);
}




//======================================================================================

public function isolir_customer($id, $status)
{
    try
    {

      if($status==2)
      {
          foreach ($id as $id) 
          {

              $customers = \App\Customer::Where('id',$id)->first();
              $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
              \App\Customer::where('id', $id)->update([
                'id_status' => 4,

            ]);
              \App\Distrouter::mikrotik_disable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

          }
      }
      if ($status==1) {
        foreach ($id as $id) 
        {

          $customers = \App\Customer::Where('id',$id)->first();
          $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
          \App\Customer::where('id', $id)->update([
            'id_status' => 2,

        ]);
          \App\Distrouter::mikrotik_enable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

      }

  }
  return redirect ('/customer/')->with('success','Item Updates successfully!'); 
}
catch (Exception $ex)
{
  return redirect ('/customer/')->with('warning','Item Updates FIELD!'); 
}




}

//======================================================================================
public function update_status(Request $request)
{
    try
    {
      $id = $request->id;
      $status = $request->status;
      if($status==0)
      {
          foreach ($id as $id) 
          {

              $customers = \App\Customer::Where('id',$id)->first();
              $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
              \App\Customer::where('id', $id)->update([
                'id_status' => 4,

            ]);
              \App\Distrouter::mikrotik_disable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

          }
      }
      if ($status==1) {
        foreach ($id as $id) 
        {

          $customers = \App\Customer::Where('id',$id)->first();
          $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
          \App\Customer::where('id', $id)->update([
            'id_status' => 2,

        ]);
          \App\Distrouter::mikrotik_enable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

      }

  }
  return redirect ('/customer/')->with('success','Item Updates successfully!'); 
}
catch (Exception $ex)
{
  return redirect ('/customer/')->with('warning','Item Updates FIELD!'); 
}




}
    //===============================================================================

public function update_status_2(Request $request)
{
    try
    {
      $id = $request->id;
      $status = $request->status;
      if($status==0)
      {
          foreach ($id as $id) 
          {

              $customers = \App\Customer::Where('id',$id)->first();
              $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
              \App\Customer::where('id', $id)->update([
                'id_status' => 4,

            ]);
              \App\Distrouter::mikrotik_disable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

          }
      }
      if ($status==1) {
        foreach ($id as $id) 
        {

          $customers = \App\Customer::Where('id',$id)->first();
          $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
          \App\Customer::where('id', $id)->update([
            'id_status' => 2,

        ]);
          \App\Distrouter::mikrotik_enable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

      }

  }
  return redirect ('/customer/unpaid')->with('success','Item Updates successfully!'); 
}
catch (Exception $ex)
{
  return redirect ('/customer/unpaid')->with('warning','Item Updates FIELD!'); 
}




}
    //===============================================================================
// public function update(Request $request, $id)
// {

//     $request ->validate([
//         'name' => 'required',
//         'id_card'  => 'nullable',
//         'contact_name' => 'required',
//         'phone' => 'required|numeric',
//         'address' => 'required',
//         'isolir_date' => 'required|numeric',
//         'npwp'  => 'nullable',
//         'tax' => 'nullable|numeric',
//         'email' => 'nullable|email',
//         'merchant'  => 'nullable',
//         'ip' => 'nullable|ipv4',
//     ]);

//     $customers = \App\Customer::Where('id',$id)->first();
//     $oldData = $customers->toArray();
//     $plan = \App\Plan::withTrashed()->Where('id',$customers->id_plan)->first();

//     $status = \App\Statuscustomer::withTrashed()->Where('id',$request->id_status)->first();
//     $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
//     $distrouter_new = \App\distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();





//     if ((($status->name == 'Active') OR ($status->name == 'Company_Properti')) and ($plan->id == $request->id_plan) and ($distrouter->id == $request->id_distrouter))
//     {
//         try
//         {
//           $plan_new = \App\Plan::Where('id',$request->id_plan)->first();
//           $distrouter_new = \App\Distrouter::Where('id',$request->id_distrouter)->first();
//           \App\Distrouter::mikrotik_addprofile($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$plan_new->name,$plan_new->speed,$plan_new->description);
//           \App\Distrouter::mikrotik_addsecreate($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$request->pppoe,$request->password,$plan_new->name,$request->name );
//       }catch (Exception $ex) {

//       }
//   }

//   else if ((($status->name != 'Active') OR ($status->name == 'Company_Properti')) and ($plan->id == $request->id_plan) and ($distrouter->id == $request->id_distrouter))
//   {
//     try {
//       echo '2';

//       \App\Distrouter::mikrotik_disable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);
//   }

//   catch (Exception $ex) {

//   }
// }
// else if ((($status->name == 'Active') OR ($status->name == 'Company_Properti')) and (($plan->id != $request->id_plan) or ($distrouter->id != $request->id_distrouter) or ($customers->pppoe != $request->pppoe) ))
// {

//     try
//     {
//       echo '3';
//       $plan_new = \App\Plan::Where('id',$request->id_plan)->first();
//       $distrouter_new = \App\Distrouter::Where('id',$request->id_distrouter)->first();
//       \App\Distrouter::mikrotik_remove($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);
//       \App\Distrouter::mikrotik_addprofile($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$plan_new->name,$plan_new->speed,$plan_new->description);
//       \App\Distrouter::mikrotik_addsecreate($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$customers->pppoe,$request->password,$plan_new->name,$request->name);
//       \App\Distrouter::mikrotik_remove_active_connection($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

//   }
//   catch (Exception $ex) {

//   }
// }

// else if ((($status->name != 'Active') OR ($status->name == 'Company_Properti')) and (($plan->id != $request->id_plan) or ($distrouter->id != $request->id_distrouter) or ($customers->pppoe != $request->pppoe) ))
// {

//     try{
//       echo '4';

//       $plan_new = \App\Plan::Where('id',$request->id_plan)->first();
//       $distrouter_new = \App\Distrouter::Where('id',$request->id_distrouter)->first();
//       \App\Distrouter::mikrotik_remove($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);
//       \App\Distrouter::mikrotik_addprofile($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$plan_new->name,$plan_new->speed,$plan_new->description);
//       \App\Distrouter::mikrotik_addsecreate($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$customers->customer_id,$request->password,$plan_new->name,$request->name);
//       \App\Distrouter::mikrotik_disable($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$customers->pppoe);

//   }
//   catch (Exception $ex) {

//   }
// }

// try
// {



//     $newData = [

//         'name' => $request->name,
//         'id_card' => $request->id_card,
//         'date_of_birth' => $request->date_of_birth,
//         'pppoe' => $request->pppoe,
//         'password' => $request->password,
//         'contact_name' => $request->contact_name,
//         'phone' => $request->phone,
//         'address' => $request->address,
//         'id_merchant' => $request->id_merchant,
//         'npwp'  => $request->npwp,
//         'tax' => $request->tax,
//         'billing_start' => $request->billing_start,
//         'isolir_date' => $request->isolir_date,
//         'email' => $request->email,
//         'id_sale' => $request->id_sale,
//         'id_plan' => $request->id_plan,
//         'id_distpoint' => $request->id_distpoint,
//         'id_distrouter' => $request->id_distrouter,
//         'id_status' => $request->id_status,
//         'coordinate' => $request->coordinate,
//         'id_olt' => $request->id_olt,
//         'id_onu' => $request->id_onu,
//         'note' => $request->note,
//         'updated_by' => $request->updated_by,
//         'updated_at' => $request->updated_at,
//         'notification' => $request->notification,
//         'ip' => $request->ip,



//     ];


//     \App\Customer::where('id', $id)
//     ->update(($newData));


//     $changes = [];
//     foreach ($newData as $key => $value) {
//         if ($oldData[$key] != $value) {
//             switch ($key) {
//                 case 'id_plan':
//                 $oldName = \App\Plan::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
//                 $newName = \App\Plan::withTrashed()->find($value)->name ?? 'Unknown';
//                 $changes['Plan'] = ['old' => $oldName, 'new' => $newName];
//                 break;

//                 case 'id_status':
//                 $oldName = \App\Statuscustomer::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
//                 $newName = \App\Statuscustomer::withTrashed()->find($value)->name ?? 'Unknown';
//                 $changes['Status'] = ['old' => $oldName, 'new' => $newName];
//                 break;

//                 case 'id_distrouter':
//                 $oldName = \App\Distrouter::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
//                 $newName = \App\Distrouter::withTrashed()->find($value)->name ?? 'Unknown';
//                 $changes['Router'] = ['old' => $oldName, 'new' => $newName];
//                 break;

//                 case 'id_distpoint':
//                 $oldName = \App\Distpoint::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
//                 $newName = \App\Distpoint::withTrashed()->find($value)->name ?? 'Unknown';
//                 $changes['Distpoint'] = ['old' => $oldName, 'new' => $newName];
//                 break;

//                 case 'id_merchant':
//                 $oldName = \App\Merchant::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
//                 $newName = \App\Merchant::withTrashed()->find($value)->name ?? 'Unknown';
//                 $changes['Merchant'] = ['old' => $oldName, 'new' => $newName];
//                 break;

//                 case 'id_olt':
//                 $oldName = \App\Olt::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
//                 $newName = \App\Olt::withTrashed()->find($value)->name ?? 'Unknown';
//                 $changes['OLT'] = ['old' => $oldName, 'new' => $newName];
//                 break;


//                 default:
//                 $changes[$key] = [
//                     'old' => $oldData[$key],
//                     'new' => $value
//                 ];
//             }
//         }
//     }

// // Ambil nama customer untuk log
//     $customerName = \App\Customer::find($id)->name ?? "Unknown";

// // Cek apakah perubahan dilakukan oleh user atau job
//     $updatedBy = Auth::user() ? Auth::user()->name : 'System';

//   //  $logFile = "customers/customer_{$id}.log";
//     $logMessage = now() . " - {$customerName} updated by {$updatedBy} - Changes: " . json_encode($changes) . PHP_EOL;

// // Simpan ke file
//    // Storage::append($logFile, $logMessage);

//     \App\Customerlog::create([
//         'id_customer' => $id,
//         'date' => now(),
//         'updated_by' => $updatedBy,
//         'topic' => 'customerdata',
//         'updates' => json_encode($changes),
//     ]);


//     return redirect ('/customer/'.$id)->with('success','Item Updates successfully!'); 
// }
// catch (Exception $ex) {
//     return redirect ('/customer/'.$id)->with('success','Item Updates FIELD!!'.$ex); 
// }
// }



public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required',
        'id_card' => 'nullable',
        'contact_name' => 'required',
        'phone' => 'required|digits_between:6,15',
        'address' => 'required',
        'isolir_date' => 'required|numeric',
        'npwp' => 'nullable',
        'tax' => 'nullable|numeric',
        'email' => 'nullable|email',
        'merchant' => 'nullable',
        'ip' => 'nullable|ipv4',
    ], [
        'phone.digits_between' => 'Nomor telepon hanya boleh berisi angka (6-15 digit), tanpa tanda + atau spasi.',
        'phone.required'       => 'Nomor telepon wajib diisi.',
        'email.email'          => 'Format email tidak valid.',
    ]);

    $customer = Customer::findOrFail($id);
    $oldData = $customer->toArray();

    // Guard: Lead Potensial tidak bisa langsung diubah ke Active via form edit
    if ($customer->id_status == 1 && in_array((int)$request->id_status, [2, 5])) {
        return redirect()->back()
            ->with('error', 'Lead Potensial tidak dapat diubah langsung ke status Aktif melalui form edit. Gunakan tombol "Convert to Active" yang tersedia di halaman detail lead.')
            ->withInput();
    }

    $plan = Plan::withTrashed()->find($customer->id_plan);
    $status = Statuscustomer::withTrashed()->find($request->id_status);
    $distrouter = Distrouter::withTrashed()->find($customer->id_distrouter);
    $distrouter_new = Distrouter::withTrashed()->find($request->id_distrouter);

    // Check if distrouter exists before proceeding with mikrotik operations
    if ($distrouter && $distrouter_new && $plan && $status) {
        if ((($status->name == 'Active') || ($status->name == 'Company_Properti')) &&
            ($plan->id == $request->id_plan) &&
            ($distrouter->id == $request->id_distrouter)) {
            try {
                $plan_new = Plan::find($request->id_plan);
                Distrouter::mikrotik_addprofile($distrouter_new->ip, $distrouter_new->user, $distrouter_new->password, $distrouter_new->port, $plan_new->name, $plan_new->speed, $plan_new->description);
                Distrouter::mikrotik_addsecreate($distrouter_new->ip, $distrouter_new->user, $distrouter_new->password, $distrouter_new->port, $request->pppoe, $request->password, $plan_new->name, $request->name);
            } catch (\Exception $ex) {
                \Log::error($ex);
            }
        } elseif ((($status->name != 'Active') || ($status->name == 'Company_Properti')) &&
            ($plan->id == $request->id_plan) &&
            ($distrouter->id == $request->id_distrouter)) {
            try {
                Distrouter::mikrotik_disable($distrouter->ip, $distrouter->user, $distrouter->password, $distrouter->port, $customer->pppoe);
            } catch (\Exception $ex) {
                \Log::error($ex);
            }
        } elseif ((($status->name == 'Active') || ($status->name == 'Company_Properti')) &&
            (($plan->id != $request->id_plan) || ($distrouter->id != $request->id_distrouter) || ($customer->pppoe != $request->pppoe))) {
            try {
                $plan_new = Plan::find($request->id_plan);
                Distrouter::mikrotik_remove($distrouter->ip, $distrouter->user, $distrouter->password, $distrouter->port, $customer->pppoe);
                Distrouter::mikrotik_addprofile($distrouter_new->ip, $distrouter_new->user, $distrouter_new->password, $distrouter_new->port, $plan_new->name, $plan_new->speed, $plan_new->description);
                Distrouter::mikrotik_addsecreate($distrouter_new->ip, $distrouter_new->user, $distrouter_new->password, $distrouter_new->port, $request->pppoe, $request->password, $plan_new->name, $request->name);
                Distrouter::mikrotik_remove_active_connection($distrouter->ip, $distrouter->user, $distrouter->password, $distrouter->port, $customer->pppoe);
            } catch (\Exception $ex) {
                \Log::error($ex);
            }
        } elseif ((($status->name != 'Active') || ($status->name == 'Company_Properti')) &&
            (($plan->id != $request->id_plan) || ($distrouter->id != $request->id_distrouter) || ($customer->pppoe != $request->pppoe))) {
            try {
                $plan_new = Plan::find($request->id_plan);
                Distrouter::mikrotik_remove($distrouter->ip, $distrouter->user, $distrouter->password, $distrouter->port, $customer->pppoe);
                Distrouter::mikrotik_addprofile($distrouter_new->ip, $distrouter_new->user, $distrouter_new->password, $distrouter_new->port, $plan_new->name, $plan_new->speed, $plan_new->description);
                Distrouter::mikrotik_addsecreate($distrouter_new->ip, $distrouter_new->user, $distrouter_new->password, $distrouter_new->port, $request->pppoe, $request->password, $plan_new->name, $request->name);
                Distrouter::mikrotik_disable($distrouter_new->ip, $distrouter_new->user, $distrouter_new->password, $distrouter_new->port, $customer->pppoe);
            } catch (\Exception $ex) {
                \Log::error($ex);
            }
        }
    }

    $newData = $request->only([
        'name', 'id_card', 'date_of_birth', 'pppoe', 'password', 'contact_name', 'phone', 'address',
        'id_merchant', 'npwp', 'tax', 'billing_start', 'isolir_date', 'email', 'id_sale',
        'id_plan', 'id_distpoint', 'id_distrouter', 'id_status', 'coordinate', 'id_olt', 'id_onu',
        'note', 'updated_by', 'updated_at', 'notification', 'ip'
    ]);

    // $changes = [];
    // foreach ($newData as $key => $value) {
    //     if ($oldData[$key] != $value) {
    //         $changes[$key] = ['old' => $oldData[$key], 'new' => $value];
    //     }
    // }


    $changes = [];
    foreach ($newData as $key => $value) {
        // Skip password fields (hidden from toArray)
        if ($key === 'password' || $key === 'portal_password') {
            continue;
        }
        
        // Check if key exists in oldData before comparing
        if (!array_key_exists($key, $oldData)) {
            continue;
        }
        
        if ($oldData[$key] != $value) {
            switch ($key) {
                case 'id_plan':
                $oldName = \App\Plan::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
                $newName = \App\Plan::withTrashed()->find($value)->name ?? 'Unknown';
                $changes['Plan'] = ['old' => $oldName, 'new' => $newName];
                break;

                case 'id_status':
                $oldName = \App\Statuscustomer::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
                $newName = \App\Statuscustomer::withTrashed()->find($value)->name ?? 'Unknown';
                $changes['Status'] = ['old' => $oldName, 'new' => $newName];
                break;

                case 'id_distrouter':
                $oldName = \App\Distrouter::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
                $newName = \App\Distrouter::withTrashed()->find($value)->name ?? 'Unknown';
                $changes['Router'] = ['old' => $oldName, 'new' => $newName];
                break;

                case 'id_distpoint':
                $oldName = \App\Distpoint::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
                $newName = \App\Distpoint::withTrashed()->find($value)->name ?? 'Unknown';
                $changes['Distpoint'] = ['old' => $oldName, 'new' => $newName];
                break;

                case 'id_merchant':
                $oldName = \App\Merchant::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
                $newName = \App\Merchant::withTrashed()->find($value)->name ?? 'Unknown';
                $changes['Merchant'] = ['old' => $oldName, 'new' => $newName];
                break;

                case 'id_olt':
                $oldName = \App\Olt::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
                $newName = \App\Olt::withTrashed()->find($value)->name ?? 'Unknown';
                $changes['OLT'] = ['old' => $oldName, 'new' => $newName];
                break;


                default:
                $changes[$key] = [
                    'old' => $oldData[$key],
                    'new' => $value
                ];
            }
        }
    }


    

        // Cek perubahan IP
    if (array_key_exists('ip', $changes)) {
        try {
            $isDisabled = Distrouter::mikrotik_is_secret_disabled($distrouter->ip, $distrouter->user, $distrouter->password, $distrouter->port, $customer->pppoe);

            Distrouter::mikrotik_remove($distrouter->ip, $distrouter->user, $distrouter->password, $distrouter->port, $customer->pppoe);

            $plan_new = Plan::find($request->id_plan);
            Distrouter::mikrotik_addsecreate(
                $distrouter_new->ip, $distrouter_new->user, $distrouter_new->password,
                $distrouter_new->port, $request->pppoe, $request->password,
                $plan_new->name, $request->name, $request->ip
            );

            if ($isDisabled) {
                Distrouter::mikrotik_disable($distrouter_new->ip, $distrouter_new->user, $distrouter_new->password, $distrouter_new->port, $request->pppoe);
            }
        } catch (\Exception $ex) {
            \Log::error($ex);
        }
    }

    // Jika status diubah ke Potensial, reset converted_at agar data valid
    if ((int)$request->id_status === 1 && $customer->id_status != 1) {
        $newData['converted_at'] = null;
        $newData['converted_by'] = null;
    }

    Customer::where('id', $id)->update($newData);

    // Track addon changes
    $oldAddonIds = $customer->addons->pluck('id')->toArray();
    $newAddonIds = $request->input('addons', []);
    sort($oldAddonIds);
    sort($newAddonIds);
    if ($oldAddonIds != $newAddonIds) {
        $oldAddonNames = \App\Addon::whereIn('id', $oldAddonIds)->pluck('name')->toArray();
        $newAddonNames = \App\Addon::whereIn('id', $newAddonIds)->pluck('name')->toArray();
        $changes['Add-on'] = [
            'old' => count($oldAddonNames) ? implode(', ', $oldAddonNames) : 'Tidak ada',
            'new' => count($newAddonNames) ? implode(', ', $newAddonNames) : 'Tidak ada',
        ];
    }

    // Sync add-ons
    $customer->addons()->sync($request->input('addons', []));

    $customerName = $customer->name ?? "Unknown";
    $updatedBy = Auth::user() ? Auth::user()->name : 'System';

    Customerlog::create([
        'id_customer' => $id,
        'date' => now(),
        'updated_by' => $updatedBy,
        'topic' => 'customerdata',
        'updates' => json_encode($changes),
    ]);

    return redirect("/customer/{$id}")->with('success', 'Item Updates successfully!');
}


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function log($id)
    {
        $customers = \App\Customer::Where('id',$id)->first();
    // Ambil data log dari database berdasarkan customer_id
        $logEntries = \App\Customerlog::where('id_customer', $id)
        ->orderBy('date', 'desc')
        ->get();

        if ($logEntries->isEmpty()) {
            return back()->with('error', 'Log tidak ditemukan');
        }

        return view('/customer/log', compact('logEntries', 'id', 'customers'));
    }
    public function destroy($id)
    {
      try
      {
          $customers = \App\Customer::Where('id',$id)->first();

          if (!$customers) {
              return redirect('/home/')->with('error', 'Customer tidak ditemukan.');
          }

          // Blok hapus jika status bukan Inactive atau Potensial
          if (!in_array($customers->id_status, [1, 3])) {
              return redirect()->back()->with('error', 'Customer hanya dapat dihapus jika berstatus Inactive atau Potensial.');
          }

          // Blok hapus jika masih ada invoice yang belum dibayar (payment_status=0)
          $unpaidCount = \App\Invoice::where('id_customer', $id)
              ->where('payment_status', 0)
              ->count();
          if ($unpaidCount > 0) {
              return redirect()->back()->with('error',
                  "Tidak dapat menghapus customer. Terdapat {$unpaidCount} invoice yang belum dibayar. "
                  . "Harap lunasi atau batalkan invoice tersebut terlebih dahulu."
              );
          }

          $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
          \App\Customer::destroy($id);

          if ($distrouter && $customers->pppoe) {
              try {
                  \App\Distrouter::mikrotik_remove($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);
              } catch (\Exception $ex) {
                  \Log::error('Mikrotik remove error on customer delete: ' . $ex->getMessage());
              }
          }

          return redirect('/home/')->with('success','Customer berhasil dihapus.');
      }
      catch (\Exception $ex) {
        return redirect('/home/')->with('error','Gagal menghapus customer: ' . $ex->getMessage());
    }
}

//   public function mikrotik($name, $customerid, $password)
//   {


//     $client = new Client([
//         'host' => '202.169.255.3',
//         'user' => 'duwija',
//         'pass' => 'rh4ps0dy',
//         'port' =>  8728
//     ]);


// // Create "where" Query object for RouterOS
//     $query =
//     // (new Query('/ip/hotspot/ip-binding/print'))
//     //     ->where('mac-address', 'B0:4E:26:44:B5:35');


//     (new Query('/ppp/secret/add '))
//     ->equal('name', $customerid)
//     ->equal('password', $password)
//     ->equal('comment', $name);

// // (new Query('/ppp/secret/print'))

// //  ->where('name', 'mikrotikApi');



// // $secrets = $client->query($query)->read();

// // echo "Before update" . PHP_EOL;


// //        foreach ($secrets as $secret) {

// //     // Change password
// //     $query = (new Query('/ppp/secret/set'))
// //         ->equal('.id', $secret['.id'])
// //         ->equal('disabled', 'false');

// //     // Update query ordinary have no return
// //     $client->query($query)->read();
// //     echo "User Was  Update" . PHP_EOL;
// //     print_r($secret['disabled']);

// // }

// // Send query and read response from RouterOS
//     $response = $client->query($query)->read();

//  // var_dump($response);
// }



public function wa_customer(Request $request)
{
  if (env('WAPISENDER_STATUS')!="disable")
  {

    $msgresult = WaGatewayHelper::wa_payment($request->phone, $request->message);
    return redirect()->back()->with('success',$msgresult['message']);

}
else
{
  return redirect ('/customer/'.$request->id_customer)->with('error','WA Disabled');
}

}


public function allow()
{
}

public function createtunnel(Request $request)
{
    $Customer_id = $request->IdCustomer;
    $customer = \App\Customer::where('id', $Customer_id)->first();

    if (!$customer || !$customer->id_distrouter) {
        return response()->json([
            'success' => false,
            'message' => 'Customer or router information not found.'
        ], 404);
    }

    $distrouter = \App\Distrouter::withTrashed()->where('id', $customer->id_distrouter)->first();

    $remoteIp = $request->remoteIp;
    $localPort = rand(9120, 9200);

    $mikrotikHost = $distrouter->ip;
    $mikrotikUser = $distrouter->user;
    $mikrotikPassword = $distrouter->password;
    $mikrotikPort = $distrouter->port ?? 8728;

    $natComment = "Forwarding_" . $customer->customer_id;
    $schedulerName = "Auto Delete Tunnel " . $customer->customer_id;

    try {
        $client = new \RouterOS\Client([
            'host' => $mikrotikHost,
            'user' => $mikrotikUser,
            'pass' => $mikrotikPassword,
            'port' => $mikrotikPort,
            'timeout' => 5,
        ]);

        // --- 1. Hapus NAT lama ---
        $queryFindNAT = (new \RouterOS\Query('/ip/firewall/nat/print'))
        ->where('comment', $natComment);
        $existingNAT = $client->query($queryFindNAT)->read();

        if (!empty($existingNAT)) {
            foreach ($existingNAT as $nat) {
                $queryRemoveNAT = (new \RouterOS\Query('/ip/firewall/nat/remove'))
                ->equal('.id', $nat['.id']);
                $client->query($queryRemoveNAT)->read();
            }
        }

        // --- 2. Hapus Scheduler lama ---
        $queryFindScheduler = (new \RouterOS\Query('/system/scheduler/print'))
        ->where('name', $schedulerName);
        $existingScheduler = $client->query($queryFindScheduler)->read();

        if (!empty($existingScheduler)) {
            foreach ($existingScheduler as $scheduler) {
                $queryRemoveScheduler = (new \RouterOS\Query('/system/scheduler/remove'))
                ->equal('.id', $scheduler['.id']);
                $client->query($queryRemoveScheduler)->read();
            }
        }

        // --- 3. Buat NAT baru ---
        $queryAddNAT = (new \RouterOS\Query('/ip/firewall/nat/add'))
        ->equal('chain', 'dstnat')
        ->equal('protocol', 'tcp')
        ->equal('dst-port', $localPort)
        ->equal('dst-address', $mikrotikHost)
        ->equal('action', 'dst-nat')
        ->equal('to-addresses', $remoteIp)
        ->equal('to-ports', 80)
        ->equal('comment', $natComment);
        $client->query($queryAddNAT)->read();

        // --- 4. Buat Scheduler auto-hapus NAT ---
        $queryScheduler = (new \RouterOS\Query('/system/scheduler/add'))
        ->equal('name', $schedulerName)
        ->equal('start-time', date('H:i:s', strtotime('+30 minutes')))
        ->equal('on-event',
            ":foreach i in=[/ip/firewall/nat find where comment=\"{$natComment}\"] do={/ip/firewall/nat remove \$i}; /system/scheduler remove [find where name=\"{$schedulerName}\"];"
        )
        ->equal('disabled', 'no');
        $client->query($queryScheduler)->read();

        return response()->json([
            'success' => true,
            'message' => "Port forwarding created on port {$localPort} and will auto delete in 30 minutes.",
            'host' => $mikrotikHost,
            'port' => $localPort
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Update lead information and progress
 * 
 * @param  Request  $request
 * @param  int  $id
 * @return \Illuminate\Http\Response
 */
public function updateLead(Request $request, $id)
{
    try {
        $customer = Customer::findOrFail($id);

        // Verify customer is in Potensial status
        if ($customer->id_status != 1) {
            return redirect()->back()->with('error', 'Only leads (Potensial status) can be updated using this form.');
        }

        // Validate input
        $validatedData = $request->validate([
            'lead_source' => 'nullable|string|max:50',
            'expected_close_date' => 'nullable|date',
            'conversion_probability' => 'nullable|integer|min:0|max:100',
            'lead_notes' => 'nullable|string',
        ]);

        // Track changes untuk setiap field
        $trackableFields = ['lead_source', 'expected_close_date', 'conversion_probability', 'lead_notes'];
        $hasChanges = false;
        
        foreach ($trackableFields as $field) {
            if (array_key_exists($field, $validatedData)) {
                $oldValue = $customer->$field;
                $newValue = $validatedData[$field];
                
                // Check if value actually changed
                if ($oldValue != $newValue) {
                    $hasChanges = true;
                    
                    // Save to lead_updates history
                    \App\LeadUpdate::create([
                        'id_customer' => $customer->id,
                        'updated_by' => Auth::user()->name,
                        'field_changed' => $field,
                        'old_value' => $oldValue,
                        'new_value' => $newValue,
                        'notes' => $request->input('update_notes'), // Optional notes dari form
                    ]);
                }
            }
        }

        // Update lead information
        $customer->update($validatedData);

        // Create summary log in customerlog
        if ($hasChanges) {
            $logUpdates = [];
            if (isset($validatedData['conversion_probability'])) {
                $logUpdates[] = "Conversion Probability: {$validatedData['conversion_probability']}%";
            }
            if (isset($validatedData['lead_source'])) {
                $logUpdates[] = "Source: {$validatedData['lead_source']}";
            }
            if (isset($validatedData['expected_close_date'])) {
                $logUpdates[] = "Expected Close: {$validatedData['expected_close_date']}";
            }
            
            $log = new Customerlog();
            $log->id_customer = $customer->id;
            $log->date = now();
            $log->topic = 'Lead Progress Update';
            $log->updates = implode(' | ', $logUpdates);
            $log->updated_by = Auth::user()->name;
            $log->save();
        }

        return redirect('/customer/' . $customer->id)
            ->with('success', 'Lead progress has been updated successfully!');

    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'Failed to update lead: ' . $e->getMessage());
    }
}

/**
 * Convert lead (Potensial) to active customer
 * 
 * @param  int  $id
 * @return \Illuminate\Http\Response
 */
public function convertToActive(Request $request, $id)
{
    $request->validate([
        'id_plan' => 'required|integer|exists:plans,id',
        'id_distrouter' => 'required|integer|exists:distrouters,id',
        'id_olt' => 'nullable|integer|exists:olts,id',
        'id_distpoint' => 'nullable|integer|exists:distpoints,id',
    ]);

    try {
        $customer = Customer::findOrFail($id);

        // Verify customer is in Potensial status
        if ($customer->id_status != 1) {
            return redirect()->back()->with('warning', 'Customer is not in Potensial status!');
        }

        // Update customer with new data
        $customer->id_status = 2; // Active
        $customer->id_plan = $request->id_plan;
        $customer->id_distrouter = $request->id_distrouter;
        
        if ($request->id_olt) {
            $customer->id_olt = $request->id_olt;
        }
        if ($request->id_distpoint) {
            $customer->id_distpoint = $request->id_distpoint;
        }

        // Set conversion tracking
        $customer->converted_at = now();
        $customer->converted_by = auth()->id();

        // Configure MikroTik PPPoE Secret
        $message = '';
        try {
            $distrouter = \App\Distrouter::findOrFail($request->id_distrouter);
            $plan = \App\Plan::findOrFail($request->id_plan);

            \App\Distrouter::mikrotik_addprofile(
                $distrouter->ip,
                $distrouter->user,
                $distrouter->password,
                $distrouter->port,
                $plan->name,
                $plan->speed,
                $plan->description
            );

            \App\Distrouter::mikrotik_addsecreate(
                $distrouter->ip,
                $distrouter->user,
                $distrouter->password,
                $distrouter->port,
                $customer->pppoe,
                $customer->password,
                $plan->name,
                $customer->name
            );

            $message = 'Lead successfully converted to Active customer and configured on MikroTik!';
        } catch (\Exception $e) {
            $message = 'Lead converted to Active, but failed to configure MikroTik: ' . $e->getMessage();
        }

        // Save customer — pakai update() agar tidak memicu masalah virtual attribute
        $updateData = [
            'id_status'    => 2,
            'id_plan'      => $request->id_plan,
            'id_distrouter'=> $request->id_distrouter,
            'converted_at' => now(),
            'converted_by' => auth()->id(),
            // bersihkan lost jika pernah ditandai gagal
            'lost_at'      => null,
            'lost_reason'  => null,
            'lost_notes'   => null,
            'lost_by'      => null,
        ];
        if ($request->id_olt) $updateData['id_olt'] = $request->id_olt;
        if ($request->id_distpoint) $updateData['id_distpoint'] = $request->id_distpoint;
        Customer::where('id', $id)->update($updateData);

        // Log the conversion
        $planName     = \App\Plan::find($request->id_plan)->name ?? $request->id_plan;
        $routerName   = \App\Distrouter::find($request->id_distrouter)->name ?? $request->id_distrouter;
        \App\Customerlog::create([
            'id_customer' => $customer->id,
            'date'        => now(),
            'updated_by'  => auth()->user()->name,
            'topic'       => 'convert_to_active',
            'updates'     => json_encode([
                'Status'  => ['old' => 'Potensial', 'new' => 'Active'],
                'Plan'    => ['old' => '-', 'new' => $planName],
                'Router'  => ['old' => '-', 'new' => $routerName],
            ]),
        ]);

        return redirect('/customer/' . $customer->id)->with('success', $message);

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Conversion failed: ' . $e->getMessage());
    }
}

/**
 * Mark a Potensial lead as Lost.
 */
public function markAsLost(Request $request, $id)
{
    $request->validate([
        'lost_reason' => 'required|string|max:100',
        'lost_notes'  => 'nullable|string|max:1000',
    ]);

    try {
        $customer = Customer::findOrFail($id);

        if ($customer->id_status != 1) {
            return redirect()->back()->with('warning', 'Hanya lead dengan status Potensial yang bisa ditandai gagal!');
        }

        Customer::where('id', $id)->update([
            'lost_at'     => now(),
            'lost_reason' => $request->lost_reason,
            'lost_notes'  => $request->lost_notes,
            'lost_by'     => auth()->id(),
        ]);

        // Pindahkan workflow ke step "Batal" jika ada
        $batalStep = \App\CustomerStep::where('customer_id', $id)
            ->whereRaw('LOWER(name) LIKE ?', ['%batal%'])
            ->first();
        if ($batalStep) {
            Customer::where('id', $id)->update(['current_step_id' => $batalStep->id]);
        }

        \App\Customerlog::create([
            'id_customer' => $id,
            'date'        => now(),
            'updated_by'  => auth()->user()->name,
            'topic'       => 'mark_as_lost',
            'updates'     => json_encode([
                'Status'      => ['old' => 'Potensial (In Progress)', 'new' => 'Lost/Gagal'],
                'lost_reason' => $request->lost_reason,
                'lost_notes'  => $request->lost_notes ?? '-',
            ]),
        ]);

        return redirect('/customer/' . $id)->with('success', 'Lead berhasil ditandai sebagai Gagal.');

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
    }
}

/**
 * Reset customer portal password.
 */
public function resetPassword(Request $request, $id)
{
    $request->validate([
        'new_password' => 'required|string|min:6|max:64',
    ]);

    try {
        $customer = Customer::findOrFail($id);

        $plain = $request->new_password;

        Customer::where('id', $id)->update([
            'portal_password' => bcrypt($plain),
        ]);

        \App\Customerlog::create([
            'id_customer' => $id,
            'date'        => now(),
            'updated_by'  => auth()->user()->name,
            'topic'       => 'reset_password',
            'updates'     => json_encode([
                'action' => 'Portal password direset oleh ' . auth()->user()->name,
            ]),
        ]);

        return redirect()->back()->with('success', 'Password portal customer berhasil direset.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Gagal reset password: ' . $e->getMessage());
    }
}

/**
 * Logout aplikasi mobile customer (hapus FCM token dari DB).
 */
public function appLogout($id)
{
    try {
        $customer = Customer::findOrFail($id);

        // Cek semua akun dengan email yang sama (multi-account)
        // karena app_token disimpan di record yang digunakan untuk login,
        // bukan selalu di customer yang sedang dilihat
        $allAccounts = Customer::where('email', $customer->email)->get();
        $hasActiveSession = $allAccounts->contains(function ($c) {
            return !empty($c->fcm_token) || !empty($c->app_token);
        });

        if (!$hasActiveSession) {
            return redirect()->back()->with('warning', 'Customer tidak sedang login di aplikasi.');
        }

        // Hapus token di semua akun dengan email yang sama
        Customer::where('email', $customer->email)
                ->update(['fcm_token' => null, 'app_token' => null]);

        \App\Customerlog::create([
            'id_customer' => $id,
            'date'        => now(),
            'updated_by'  => auth()->user()->name,
            'topic'       => 'app_logout',
            'updates'     => json_encode([
                'action' => 'Logout aplikasi mobile oleh ' . auth()->user()->name,
                'affected_accounts' => $allAccounts->pluck('customer_id')->join(', '),
            ]),
        ]);

        return redirect()->back()->with('success', 'Customer berhasil di-logout dari aplikasi mobile.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Gagal logout aplikasi: ' . $e->getMessage());
    }
}

/**
 * Reopen a Lost lead (clear lost status).
 */
public function reopenLead(Request $request, $id)
{
    try {
        $customer = Customer::findOrFail($id);

        if ($customer->id_status != 1) {
            return redirect()->back()->with('warning', 'Hanya lead Potensial yang bisa dibuka kembali!');
        }

        Customer::where('id', $id)->update([
            'lost_at'     => null,
            'lost_reason' => null,
            'lost_notes'  => null,
            'lost_by'     => null,
        ]);

        // Kembalikan ke step pertama yang bukan Batal
        $firstStep = \App\CustomerStep::where('customer_id', $id)
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%batal%'])
            ->orderBy('position')
            ->first();
        if ($firstStep) {
            Customer::where('id', $id)->update(['current_step_id' => $firstStep->id]);
        }

        \App\Customerlog::create([
            'id_customer' => $id,
            'date'        => now(),
            'updated_by'  => auth()->user()->name,
            'topic'       => 'reopen_lead',
            'updates'     => json_encode([
                'Status' => ['old' => 'Lost/Gagal', 'new' => 'Potensial (In Progress)'],
            ]),
        ]);

        return redirect('/customer/' . $id)->with('success', 'Lead berhasil dibuka kembali.');

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Gagal reopen: ' . $e->getMessage());
    }
}


}
