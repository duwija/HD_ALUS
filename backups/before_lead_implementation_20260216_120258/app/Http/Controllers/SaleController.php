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

    if (($request->date_from == null) or ($request->date_end == null))
       {
       $from=date('Y-m-1');
       $to=date('y-m-d');
     
     }
     else
     {
       $from=$request->date_from;
       $to=$request->date_end;
      
     }
       

    //      $sale = \App\Sale::Join('customers', 'sales.id', '=', 'customers.id_sale')
    // ->whereBetween('customers.billing_start', [$from, $to])
    // ->groupBy('sales.id')
    // ->select('sales.name','sales.email', 'sales.sale_type', 'sales.phone', 'sales.address', DB::raw("count(customers.id_sale) as count"))
    // ->get();


       $sale = \App\Sale::all();

        return view ('sale/index',['sale' =>$sale]);
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
