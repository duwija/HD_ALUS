<?php

namespace App\Http\Controllers;
use \Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use \RouterOS\Client;
use \RouterOS\Query;
Use GuzzleHttp\Clients;
use \App\Customer;
use \App\Suminvoice;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Hash;
class SaleController extends Controller
{
   public function __construct()
   {
    $this->middleware('auth');
    $this->middleware('checkPrivilege:admin,noc,marketing');
}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
       if ((Auth::user()->privilege)=="admin" OR (Auth::user()->privilege)=="noc" OR (Auth::user()->privilege)=="marketing"  )
       {     

    $from = $request->filled('date_from') ? $request->date_from : date('Y-m-01');
    $to = $request->filled('date_end') ? $request->date_end : date('Y-m-d');

    $query = \App\Sale::query()->select('sales.*');

    if ($request->filled('keyword')) {
        $keyword = '%' . $request->keyword . '%';
        $query->where(function ($q) use ($keyword) {
            $q->where('sales.name', 'like', $keyword)
              ->orWhere('sales.full_name', 'like', $keyword)
              ->orWhere('sales.email', 'like', $keyword)
              ->orWhere('sales.phone', 'like', $keyword)
              ->orWhere('sales.address', 'like', $keyword);
        });
    }

    if ($request->filled('sale_type')) {
        $query->where('sales.sale_type', $request->sale_type);
    }

    // Jumlah customer per sales: aktif saat ini, baru & hilang pada periode filter
    $query->selectSub(Customer::selectRaw('COUNT(*)')->whereColumn('customers.id_sale', 'sales.id'), 'total_customers')
        ->selectSub(Customer::selectRaw('COUNT(*)')->whereColumn('customers.id_sale', 'sales.id')->where('id_status', 2), 'active_customers')
        ->selectSub(Customer::selectRaw('COUNT(*)')->whereColumn('customers.id_sale', 'sales.id')
            ->whereBetween('billing_start', [$from, $to]), 'new_customers')
        ->selectSub(Customer::onlyTrashed()->selectRaw('COUNT(*)')->whereColumn('customers.id_sale', 'sales.id')
            ->whereDate('customers.deleted_at', '>=', $from)->whereDate('customers.deleted_at', '<=', $to), 'lost_customers')
        ->selectSub(Customer::onlyTrashed()->join('plans', 'customers.id_plan', '=', 'plans.id')
            ->selectRaw('COALESCE(SUM(plans.price),0)')->whereColumn('customers.id_sale', 'sales.id')
            ->whereDate('customers.deleted_at', '>=', $from)->whereDate('customers.deleted_at', '<=', $to), 'lost_revenue');

    $sale = $query->orderBy('sales.name')->get();
    $saleTypes = \App\Sale::whereNotNull('sale_type')->where('sale_type', '!=', '')
        ->distinct()->orderBy('sale_type')->pluck('sale_type');

        return view ('sale/index',['sale' =>$sale, 'saleTypes' => $saleTypes, 'from' => $from, 'to' => $to]);
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
    public function create()
    {
        //
         if ((Auth::user()->privilege)=="admin" OR (Auth::user()->privilege)=="noc" OR (Auth::user()->privilege)=="marketing")
       {     

        return view ('sale/create');
         }
    else
    {
      return redirect()->back()->with('error','Sorry, You Are Not Allowed to Access Destination page !!');
  }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

       $request ->validate([
        'name' => 'required',
        'date_of_birth' => 'required',
        'full_name' => 'required',
        'email' => ['required', 'string', 'email', 'max:255', 'unique:sales'],
        'password' => 'required',
        'join_date' => 'required',
        'sale_type' => 'required',
        'address' => 'required',
        'phone' => 'required',
           'photo' => ['mimes:jpg, png, jpeg, gif'],
    ]);


       if (($request['photo'])==null) 
       {

           \App\Sale::create([
            'name' => ($request['name']),
            'full_name' => ($request['full_name']),
            'date_of_birth' => ($request['date_of_birth']),
            'email' => ($request['email']), 
            'password' => Hash::make($request['password']),
            'join_date' => ($request['join_date']),
            'address' => ($request['address']),
            'sale_type' => ($request['sale_type']),
            'description' => ($request['description']),
            'phone' => ($request['phone']),
//             'photo' => $imageName,
        ]);
       }
       else
       {

           $imageName = time().'.'.$request->photo->getClientOriginalExtension();

           $request->photo->move(public_path('storage/sales'), $imageName);



           \App\Sale::create([
            'name' => ($request['name']),
            'full_name' => ($request['full_name']),
            'date_of_birth' => ($request['date_of_birth']),
            'email' => ($request['email']), 
            'password' => Hash::make($request['password']),
            // 'job_title' => ($request['job_title']),
            'sale_type' => ($request['sale_type']),
            'address' => ($request['address']),
           'description' => ($request['description']),
            'phone' => ($request['phone']),
            'photo' => $imageName,


        ]);

       }





        // $photoName = $request->photo->extension();  





       return redirect ('/sale')->with('success','Item created successfully!');
   }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $sale = \App\Sale::findOrFail($id);
        
        // Get date range from request or use current year as default
        $dateFrom = $request->input('date_from', \Carbon\Carbon::now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', \Carbon\Carbon::now()->endOfYear()->format('Y-m-d'));
        
        // Parse dates
        $startDate = \Carbon\Carbon::parse($dateFrom);
        $endDate = \Carbon\Carbon::parse($dateTo);
        
        // Get customer statistics for this sales
        $totalCustomers = Customer::where('id_sale', $id)->count();
        $activeCustomers = Customer::where('id_sale', $id)->where('id_status', 2)->count();
        $blockCustomers = Customer::where('id_sale', $id)->where('id_status', 4)->count();
        $inactiveCustomers = Customer::where('id_sale', $id)->where('id_status', 3)->count();

        // Get customer counts by status for chart
        $statusCounts = Customer::where('id_sale', $id)
            ->selectRaw('id_status, count(*) as count')
            ->groupBy('id_status')
            ->with('status_name')
            ->get();

        // Prepare data for status chart
        $statusLabels = [];
        $statusData = [];
        
        foreach ($statusCounts as $status) {
            $statusLabels[] = $status->status_name ? $status->status_name->name : 'Unknown';
            $statusData[] = $status->count;
        }

        // Get customer growth data based on date range
        $monthlyGrowth = [];
        $monthlyRevenue = [];
        $monthLabels = [];
        
        // Generate month range from start to end date
        $currentDate = $startDate->copy()->startOfMonth();
        $endDateMonth = $endDate->copy()->endOfMonth();
        
        while ($currentDate <= $endDateMonth) {
            $monthLabel = $currentDate->format('M Y');
            $year = $currentDate->year;
            $month = $currentDate->month;
            
            // Count customers created in this month (from billing_start)
            $newCustomers = Customer::where('id_sale', $id)
                ->whereYear('billing_start', $year)
                ->whereMonth('billing_start', $month)
                ->count();
            
            // Count customers deleted in this month (from deleted_at)
            $lostCustomers = Customer::onlyTrashed()
                ->where('id_sale', $id)
                ->whereYear('customers.deleted_at', $year)
                ->whereMonth('customers.deleted_at', $month)
                ->count();
            
            // Calculate revenue from new customers (billing_start)
            $newRevenue = Customer::where('id_sale', $id)
                ->whereYear('billing_start', $year)
                ->whereMonth('billing_start', $month)
                ->join('plans', 'customers.id_plan', '=', 'plans.id')
                ->sum('plans.price');
            
            // Calculate lost revenue from terminated customers (deleted_at)
            $lostRevenue = Customer::onlyTrashed()
                ->where('id_sale', $id)
                ->whereYear('customers.deleted_at', $year)
                ->whereMonth('customers.deleted_at', $month)
                ->join('plans', 'customers.id_plan', '=', 'plans.id')
                ->sum('plans.price');
            
            $monthLabels[] = $monthLabel;
            $monthlyGrowth['new'][] = $newCustomers;
            $monthlyGrowth['lost'][] = $lostCustomers;
            $monthlyGrowth['net'][] = $newCustomers - $lostCustomers;
            
            $monthlyRevenue['new'][] = (int)$newRevenue;
            $monthlyRevenue['lost'][] = (int)$lostRevenue;
            $monthlyRevenue['net'][] = (int)($newRevenue - $lostRevenue);
            
            $currentDate->addMonth();
        }
        
        // Get revenue by plan category
        $revenueByPlan = Customer::where('id_sale', $id)
            ->join('plans', 'customers.id_plan', '=', 'plans.id')
            ->selectRaw('plans.name as plan_name, SUM(plans.price) as total_revenue, COUNT(customers.id) as customer_count')
            ->groupBy('plans.id', 'plans.name')
            ->get();
        
        $planLabels = [];
        $planRevenue = [];
        
        foreach ($revenueByPlan as $plan) {
            $planLabels[] = $plan->plan_name . ' (' . $plan->customer_count . ')';
            $planRevenue[] = (int)$plan->total_revenue;
        }

        // Get status and plan lists for filter
        $status = \App\Statuscustomer::pluck('name', 'id');
        $plan = \App\Plan::pluck('name', 'id');
        
        return view('sale/show', [
            'sale' => $sale,
            'totalCustomers' => $totalCustomers,
            'activeCustomers' => $activeCustomers,
            'blockCustomers' => $blockCustomers,
            'inactiveCustomers' => $inactiveCustomers,
            'statusLabels' => json_encode($statusLabels),
            'statusData' => json_encode($statusData),
            'monthLabels' => json_encode($monthLabels),
            'monthlyNew' => json_encode($monthlyGrowth['new']),
            'monthlyLost' => json_encode($monthlyGrowth['lost']),
            'monthlyNet' => json_encode($monthlyGrowth['net']),
            'monthlyRevenueNew' => json_encode($monthlyRevenue['new']),
            'monthlyRevenueLost' => json_encode($monthlyRevenue['lost']),
            'monthlyRevenueNet' => json_encode($monthlyRevenue['net']),
            'planLabels' => json_encode($planLabels),
            'planRevenue' => json_encode($planRevenue),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'status' => $status,
            'plan' => $plan
        ]);
    }

    /**
     * Customer milik sales yang terhapus (lost revenue): berhenti berlangganan
     * maupun tidak jadi berlangganan, difilter menurut tanggal dihapus.
     */
    private function lostCustomerQuery(Request $request)
    {
        $query = Customer::onlyTrashed()
            ->where('customers.id_sale', $request->id_sale)
            ->leftJoin('plans', 'customers.id_plan', '=', 'plans.id');

        if ($request->filled('lost_from')) {
            $query->whereDate('customers.deleted_at', '>=', $request->lost_from);
        }
        if ($request->filled('lost_to')) {
            $query->whereDate('customers.deleted_at', '<=', $request->lost_to);
        }

        if (\Schema::hasColumn('customers', 'deletion_type') && $request->filled('deletion_type')) {
            if ($request->deletion_type === 'terminate') {
                $query->whereIn('customers.deletion_type', ['terminate', 'berhenti_berlangganan']);
            } elseif ($request->deletion_type === 'cancel') {
                $query->whereIn('customers.deletion_type', ['cancel', 'tidak_jadi_berlangganan']);
            } elseif ($request->deletion_type === 'none') {
                $query->where(function ($q) {
                    $q->whereNull('customers.deletion_type')
                      ->orWhereNotIn('customers.deletion_type', ['terminate', 'berhenti_berlangganan', 'cancel', 'tidak_jadi_berlangganan']);
                });
            }
        }

        return $query;
    }

    public function table_sale_lost_customer(Request $request)
    {
        $hasDeletionType = \Schema::hasColumn('customers', 'deletion_type');

        $query = $this->lostCustomerQuery($request)
            ->select('customers.id', 'customers.customer_id', 'customers.name', 'customers.address',
                'customers.billing_start', 'customers.deleted_at', 'plans.name as plan', 'plans.price as price')
            ->selectRaw('DATEDIFF(DATE(customers.deleted_at), customers.billing_start) as age_days');
        if ($hasDeletionType) {
            $query->addSelect('customers.deletion_type', 'customers.deletion_reason');
        }

        // Ringkasan untuk header tabel (mengikuti filter yang sama)
        $summaryQuery = $this->lostCustomerQuery($request);
        $summary = $hasDeletionType
            ? $summaryQuery->selectRaw("
                COUNT(*) as total_count,
                COALESCE(SUM(plans.price),0) as total_revenue,
                SUM(customers.deletion_type IN ('terminate','berhenti_berlangganan')) as terminate_count,
                COALESCE(SUM(CASE WHEN customers.deletion_type IN ('terminate','berhenti_berlangganan') THEN plans.price END),0) as terminate_revenue,
                SUM(customers.deletion_type IN ('cancel','tidak_jadi_berlangganan')) as cancel_count,
                COALESCE(SUM(CASE WHEN customers.deletion_type IN ('cancel','tidak_jadi_berlangganan') THEN plans.price END),0) as cancel_revenue")
                ->first()
            : $summaryQuery->selectRaw('COUNT(*) as total_count, COALESCE(SUM(plans.price),0) as total_revenue')->first();

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('customer_id', function ($row) {
                return '<span class="badge badge-secondary">' . e($row->customer_id) . '</span>';
            })
            ->editColumn('address', function ($row) {
                return '<small class="text-muted">' . e($row->address) . '</small>';
            })
            ->editColumn('plan', function ($row) {
                return e($row->plan ?? '-');
            })
            ->editColumn('price', function ($row) {
                return number_format((int) $row->price, 0, ',', '.');
            })
            ->editColumn('deleted_at', function ($row) {
                return $row->deleted_at ? $row->deleted_at->format('d M Y H:i') : '-';
            })
            ->editColumn('age_days', function ($row) {
                if ($row->age_days === null) {
                    return '<small class="text-muted">Belum billing</small>';
                }
                $days = max(0, (int) $row->age_days);
                $html = number_format($days, 0, ',', '.') . ' hari';
                if ($days >= 30) {
                    $years = intdiv($days, 365);
                    $months = intdiv($days % 365, 30);
                    $parts = [];
                    if ($years) $parts[] = $years . ' thn';
                    if ($months) $parts[] = $months . ' bln';
                    if ($parts) $html .= '<br><small class="text-muted">± ' . implode(' ', $parts) . '</small>';
                }
                return $html;
            })
            ->addColumn('deletion_type', function ($row) {
                $type = $row->deletion_type ?? null;
                if (in_array($type, ['terminate', 'berhenti_berlangganan'])) {
                    return '<span class="badge badge-danger">Berhenti Berlangganan</span>';
                }
                if (in_array($type, ['cancel', 'tidak_jadi_berlangganan'])) {
                    return '<span class="badge badge-warning text-dark">Tidak Jadi Berlangganan</span>';
                }
                return $type ? e($type) : '<span class="badge badge-light">-</span>';
            })
            ->addColumn('deletion_reason', function ($row) {
                return empty($row->deletion_reason) ? '<small class="text-muted">-</small>' : '<small>' . e($row->deletion_reason) . '</small>';
            })
            ->filterColumn('plan', function ($q, $keyword) {
                $q->where('plans.name', 'like', "%{$keyword}%");
            })
            ->orderColumn('plan', 'plans.name $1')
            ->orderColumn('price', 'plans.price $1')
            ->orderColumn('age_days', 'age_days $1')
            ->with('summary', [
                'total_count' => (int) ($summary->total_count ?? 0),
                'total_revenue' => (int) ($summary->total_revenue ?? 0),
                'terminate_count' => (int) ($summary->terminate_count ?? 0),
                'terminate_revenue' => (int) ($summary->terminate_revenue ?? 0),
                'cancel_count' => (int) ($summary->cancel_count ?? 0),
                'cancel_revenue' => (int) ($summary->cancel_revenue ?? 0),
            ])
            ->rawColumns(['customer_id', 'address', 'age_days', 'deletion_type', 'deletion_reason'])
            ->make(true);
    }

    public function table_sale_customer(Request $request){
      $id_sale  = $request->id_sale;
        if (empty($request->filter))
        {
          $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')
          ->where ('id_sale', $id_sale)
          ->orderBy('id','DESC');
        }
        elseif ((empty($request->id_status))and (empty($request->id_plan)))
        {
        $filter =$request->filter ;
        $parameter =$request->parameter ;
        // $id_status =$request->id_status ;
        // $id_plan =$request->id_plan ;
        $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')
        ->where ('id_sale', $id_sale)
        ->where($filter, 'LIKE', "%".$parameter."%") 
        // ->Where('id_status', $id_status) 
        // ->Where('id_plan', $id_plan) 
        ->orderBy('id', 'DESC');
        }
        elseif ((empty($request->id_status))and (!empty($request->id_plan)))
        {
        $filter =$request->filter ;
        $parameter =$request->parameter ;
        // $id_status =$request->id_status ;
        $id_plan =$request->id_plan ;
        $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')
        ->where ('id_sale', $id_sale)
        ->where($filter, 'LIKE', "%".$parameter."%") 
        // ->Where('id_status', $id_status) 
        ->Where('id_plan', $id_plan) 
        ->orderBy('id', 'DESC');
        }
        elseif ((!empty($request->id_status))and (empty($request->id_plan)))
        {
        $filter =$request->filter ;
        $parameter =$request->parameter ;
        $id_status =$request->id_status ;
       // $id_plan =$request->id_plan ;
        $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')
        ->where ('id_sale', $id_sale)
        ->where($filter, 'LIKE', "%".$parameter."%") 
         ->Where('id_status', $id_status) 
        // ->Where('id_plan', $id_plan) 
        ->orderBy('id', 'DESC');
        }
       else
        {
        $filter =$request->filter ;
        $parameter =$request->parameter ;
        $id_status =$request->id_status ;
        $id_plan =$request->id_plan ;
        $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')
        ->where ('id_sale', $id_sale)
        ->where($filter, 'LIKE', "%".$parameter."%") 
        ->Where('id_status', $id_status) 
        ->Where('id_plan', $id_plan) 
        ->orderBy('id', 'DESC');
        }
       
        return DataTables::of($customer)
        ->editColumn('customer_id',function($customer){
            return '<a href="/customer/'.$customer->id.'" class="btn btn-primary">'.$customer->customer_id.'</a>';
        })
      ->addColumn('billing', function($customer)
      {
        
      })
        ->addIndexColumn()
        // ->addColumn('select', function($customer)
        // {
        //   if (($customer->status_name->name == 'Active')Or ($customer->status_name->name == 'Block'))
          
        //   {
        //    return '<input   type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'"></td>';
        //   }
          
        //   else
        //   {}
          
        // })
        ->addColumn('plan', function($customer){

          return '<a class="text-center">'.$customer->plan_name->name.' </a>';

        })
                ->addColumn('price', function($customer){

          return '<a class="text-center">'.$customer->plan_name->price.' </a>';

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
        // ->addColumn('action',function($customer){
        //     $create_ticket = url('/ticket/'.$customer->id.'/create');
            
        //     $button = '<a href="'.$create_ticket.'" class="btn btn-success">Create Ticket kkkk</a>';
          
        //     return $button;
        //   })
        ->rawColumns(['DT_RowIndex','customer_id','plan','billing_start','status_cust','price','invoice'])
        ->make(true);
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
         if ((Auth::user()->privilege)=="admin")
       {     
        return view ('sale.edit',['sale' => \App\Sale::findOrFail($id)]);
        }
    else
    {
      return redirect()->back()->with('error','Sorry, You Are Not Allowed to Access Destination page !!');
  }
    }
    public function myprofile($id)
    {
     
     if ($id == Auth::user()->id)
     {
        return view ('user/myprofile',['user' => \App\User::findOrFail($id)]);
    }
    else
    {
        abort(404, 'You dont have permision to view this page');
    }
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


       $request ->validate([
        'name' => 'required',
        'full_name' => 'required',
        'date_of_birth' => 'required',
        'password' => 'required',
        // 'job_title' => 'required',
        // 'sale_type' => 'required',
        // // 'address' => 'required',
        // // 'join_date' => 'required',
        // // 'phone' => 'required',

          //  'photo' => ['mimes:jpg, png, jpeg, gif'],
    ]);


       if (strlen($request['password']) >= 50){
        $password = $request['password'];
    }
    else
    {
        $password = Hash::make($request['password']);
    }

    if (($request['photo'])==null) 
    {


      \App\Sale::where('id', $id)
      ->update([
        'name' => ($request['name']),
        'full_name' => ($request['full_name']),
        'date_of_birth' => ($request['date_of_birth']),
            // 'email' => ($request['email']), 
        'password' => $password,
        // 'job_title' => ($request['job_title']),
        'sale_type' => ($request['sale_type']),
        'join_date' => ($request['join_date']),
        'address' => ($request['address']),
        'phone' => ($request['phone']),
        'description' => ($request['description']),
        // 'privilege' => ($request['privilege']),
            //'photo' => $imageName,


    ]);
  }
  else
  {

   $imageName = time().'.'.$request->photo->getClientOriginalExtension();
   
   $request->photo->move(public_path('storage/sales'), $imageName);





   \App\Sale::where('id', $id)
   ->update([
    'name' => ($request['name']),
    'full_name' => ($request['full_name']),
    'date_of_birth' => ($request['date_of_birth']),
    'password' => $password,
    // 'job_title' => ($request['job_title']),
    'sale_type' => ($request['sale_type']),
    'address' => ($request['address']),
    'join_date' => ($request['join_date']),
    'phone' => ($request['phone']),
    'photo' => $imageName,
    'description' => ($request['description']),


]);

}


return redirect ('/sale')->with('success','Item created successfully!');
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
      $result=  \App\Sale::destroy($id);
      if($result)
      {
        return redirect ('/sale')->with('success','Item Deleted successfully!');
      }
      else
      {
        return redirect ('/sale')->with('error','Field!');
      }
    }
}
