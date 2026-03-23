<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Sale;
use App\Customer;

class SalesAuthController extends Controller
{
    /**
     * Show sales dashboard with customer list
     */
    public function index()
    {
        $sales = Auth::guard('sales')->user();
        
        // Get customer counts for statistics
        $totalCustomers = Customer::where('id_sale', $sales->id)->count();
        $potensialCustomers = Customer::where('id_sale', $sales->id)->where('id_status', 1)->count();
        $activeCustomers = Customer::where('id_sale', $sales->id)->where('id_status', 2)->count();
        $blockCustomers = Customer::where('id_sale', $sales->id)->where('id_status', 4)->count();
        $inactiveCustomers = Customer::where('id_sale', $sales->id)->where('id_status', 3)->count();
        
        // Calculate conversion rate
        $totalConverted = Customer::where('id_sale', $sales->id)
            ->whereNotNull('converted_at')
            ->count();
        $totalLeads = Customer::where('id_sale', $sales->id)
            ->where(function($query) {
                $query->where('id_status', 1) // Current Potensial
                      ->orWhereNotNull('converted_at'); // Previously converted
            })
            ->count();
        $conversionRate = $totalLeads > 0 ? round(($totalConverted / $totalLeads) * 100, 1) : 0;

        // Get customer counts by status for chart
        $statusCounts = Customer::where('id_sale', $sales->id)
            ->selectRaw('id_status, count(*) as count')
            ->groupBy('id_status')
            ->with('status_name')
            ->get();

        // Prepare data for status chart
        $statusLabels = [];
        $statusData = [];
        $statusColors = [
            1 => '#ffc107', // Pending
            2 => '#28a745', // Active  
            3 => '#6c757d', // Inactive
            4 => '#dc3545', // Block
            5 => '#17a2b8', // Suspend
        ];
        
        foreach ($statusCounts as $status) {
            $statusLabels[] = $status->status_name ? $status->status_name->name : 'Unknown';
            $statusData[] = $status->count;
        }

        // Get customer growth data (current year - Jan to Dec)
        $monthlyGrowth = [];
        $monthLabels = [];
        $currentYear = \Carbon\Carbon::now()->year;
        
        for ($month = 1; $month <= 12; $month++) {
            $monthLabel = \Carbon\Carbon::create($currentYear, $month, 1)->format('M Y');
            
            // Count customers created in this month (from billing_start)
            $newCustomers = Customer::where('id_sale', $sales->id)
                ->whereYear('billing_start', $currentYear)
                ->whereMonth('billing_start', $month)
                ->count();
            
            // Count customers deleted in this month (from deleted_at)
            $lostCustomers = Customer::onlyTrashed()
                ->where('id_sale', $sales->id)
                ->whereYear('deleted_at', $currentYear)
                ->whereMonth('deleted_at', $month)
                ->count();
            
            $monthLabels[] = $monthLabel;
            $monthlyGrowth['new'][] = $newCustomers;
            $monthlyGrowth['lost'][] = $lostCustomers;
            $monthlyGrowth['net'][] = $newCustomers - $lostCustomers;
        }

        // Get status and plan lists for filter
        $status = \App\Statuscustomer::pluck('name', 'id');
        $plan = \App\Plan::pluck('name', 'id');

        return view('sales.dashboard', [
            'sales' => $sales,
            'totalCustomers' => $totalCustomers,
            'potensialCustomers' => $potensialCustomers,
            'activeCustomers' => $activeCustomers,
            'blockCustomers' => $blockCustomers,
            'inactiveCustomers' => $inactiveCustomers,
            'conversionRate' => $conversionRate,
            'statusLabels' => json_encode($statusLabels),
            'statusData' => json_encode($statusData),
            'statusColors' => json_encode(array_values($statusColors)),
            'monthLabels' => json_encode($monthLabels),
            'monthlyNew' => json_encode($monthlyGrowth['new']),
            'monthlyLost' => json_encode($monthlyGrowth['lost']),
            'monthlyNet' => json_encode($monthlyGrowth['net']),
            'status' => $status,
            'plan' => $plan
        ]);
    }

    /**
     * Show login form
     */
    public function showLogin()
    {
        // If already logged in, redirect to dashboard
        if (Auth::guard('sales')->check()) {
            return redirect('/sales');
        }

        return view('sales.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $credentials = [
            'email' => $request->email,
            'password' => $request->password
        ];

        if (Auth::guard('sales')->attempt($credentials, $request->filled('remember'))) {
            // Update last login
            $sales = Auth::guard('sales')->user();
            $sales->update(['last_login_at' => now()]);

            return redirect()->intended('/sales')->with('success', 'Login berhasil!');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->withInput($request->only('email'));
    }

    /**
     * Logout sales
     */
    public function logout()
    {
        Auth::guard('sales')->logout();
        return redirect('/sales/login')->with('success', 'Anda telah logout.');
    }

    /**
     * Show customer details
     */
    public function showCustomer($id)
    {
        $sales = Auth::guard('sales')->user();
        
        // Verify this customer belongs to this sales
        $customer = Customer::where('id', $id)
            ->where('id_sale', $sales->id)
            ->with(['plan_name', 'status_name', 'distrouter', 'merchant_name'])
            ->firstOrFail();

        $workflowStages = \App\LeadWorkflow::orderBy('order')->get();

        return view('sales.customer-detail', [
            'sales'          => $sales,
            'customer'       => $customer,
            'workflowStages' => $workflowStages,
        ]);
    }

    /**
     * Update lead workflow stage from sales portal
     */
    public function updateCustomerStage(Request $request, $id)
    {
        $sales = Auth::guard('sales')->user();

        $customer = Customer::where('id', $id)
            ->where('id_sale', $sales->id)
            ->firstOrFail();

        $request->validate([
            'workflow_stage_id' => 'required|exists:lead_workflows,id',
            'lead_notes'        => 'nullable|string',
        ]);

        $customer->update([
            'workflow_stage_id' => $request->workflow_stage_id,
            'lead_notes'        => $request->lead_notes ?? $customer->lead_notes,
        ]);

        return redirect('/sales/customer/' . $id)->with('success', 'Stage workflow berhasil diperbarui.');
    }

    /**
     * Show activation form for first-time users
     */
    public function showActivate()
    {
        return view('sales.activate');
    }

    /**
     * DataTable for customer list
     */
    public function table_customer_sales(Request $request)
    {
        $sales = Auth::guard('sales')->user();
        
        $customers = Customer::select('id', 'customer_id', 'name', 'address', 'phone', 'billing_start', 'id_plan', 'id_status')
            ->where('id_sale', $sales->id);

        // Apply filters
        if (!empty($request->filter) && !empty($request->parameter)) {
            $customers->where($request->filter, 'LIKE', '%' . $request->parameter . '%');
        }

        if (!empty($request->id_status)) {
            $customers->where('id_status', $request->id_status);
        }

        if (!empty($request->id_plan)) {
            $customers->where('id_plan', $request->id_plan);
        }

        // Filter by billing_start date range
        if (!empty($request->billing_start_from)) {
            $customers->where('billing_start', '>=', $request->billing_start_from);
        }

        if (!empty($request->billing_start_to)) {
            $customers->where('billing_start', '<=', $request->billing_start_to);
        }

        $customers->orderBy('id', 'DESC');

        return \DataTables::of($customers)
            ->addIndexColumn()
            ->editColumn('customer_id', function ($customer) {
                return '<strong>' . $customer->customer_id . '</strong>';
            })
            ->editColumn('billing_start', function ($customer) {
                return $customer->billing_start ? \Carbon\Carbon::parse($customer->billing_start)->format('d/m/Y') : '-';
            })
            ->addColumn('plan', function ($customer) {
                return $customer->plan_name ? $customer->plan_name->name : '-';
            })
            ->addColumn('status_cust', function ($customer) {
                $statusConfig = [
                    1 => ['name' => 'Potensial', 'color' => '#3bacd9'],
                    2 => ['name' => 'Active', 'color' => '#2bd93a'],
                    3 => ['name' => 'Inactive', 'color' => '#959c9a'],
                    4 => ['name' => 'Block', 'color' => '#e32510'],
                    5 => ['name' => 'Company_Properti', 'color' => '#8866aa']
                ];
                $status = $statusConfig[$customer->id_status] ?? ['name' => 'Unknown', 'color' => '#999'];
                return '<span class="badge" style="background-color: ' . $status['color'] . '; color: #fff">' . $status['name'] . '</span>';
            })
            ->addColumn('action', function ($customer) {
                return '<a href="' . url('/sales/customer/' . $customer->id) . '" class="btn btn-sm btn-primary mr-1"><i class="fas fa-eye"></i> Detail</a>'
                     . '<a href="' . url('/sales/customer/' . $customer->id . '/edit') . '" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>';
            })
            ->rawColumns(['customer_id', 'status_cust', 'action'])
            ->make(true);
    }

    /**
     * Handle activation (set password for first-time login)
     */
    public function activate(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        // Find sales by email and phone
        $sales = Sale::where('email', $request->email)
            ->where('phone', $request->phone)
            ->first();

        if (!$sales) {
            return back()->withErrors([
                'email' => 'Email atau nomor telepon tidak ditemukan.',
            ])->withInput();
        }

        // Update password
        $sales->update([
            'password' => Hash::make($request->password)
        ]);

        return redirect('/sales/login')->with('success', 'Akun berhasil diaktifkan! Silakan login.');
    }

    public function showEditCustomer($id)
    {
        $sales = Auth::guard('sales')->user();

        $customer = Customer::where('id', $id)
            ->where('id_sale', $sales->id)
            ->with('addons')
            ->firstOrFail();

        $plan     = \App\Plan::pluck('name', 'id');
        $merchant = \App\Merchant::pluck('name', 'id');
        $addons   = \App\Addon::orderBy('name')->get();

        return view('sales.edit-customer', [
            'sales'    => $sales,
            'customer' => $customer,
            'plan'     => $plan,
            'merchant' => $merchant,
            'addons'   => $addons,
        ]);
    }

    public function updateCustomer(Request $request, $id)
    {
        $sales = Auth::guard('sales')->user();

        $customer = Customer::where('id', $id)
            ->where('id_sale', $sales->id)
            ->firstOrFail();

        $request->validate([
            'name'                   => 'required|string|max:191',
            'contact_name'           => 'nullable|string|max:191',
            'address'                => 'required|string',
            'phone'                  => 'required|string|max:191',
            'date_of_birth'          => 'nullable|date',
            'email'                  => 'nullable|email|max:191',
            'npwp'                   => 'nullable|string|max:191',
            'id_plan'                => 'required|exists:plans,id',
            'id_merchant'            => 'nullable|exists:merchants,id',
            'coordinate'             => 'nullable|string|max:191',
            'note'                   => 'nullable|string',
            'addons'                 => 'nullable|array',
            'addons.*'               => 'exists:addons,id',
            'lead_source'            => 'nullable|string|max:100',
            'lead_notes'             => 'nullable|string',
            'expected_close_date'    => 'nullable|date',
            'conversion_probability' => 'nullable|integer|min:0|max:100',
        ]);

        $customer->update([
            'name'                   => $request->name,
            'contact_name'           => $request->contact_name,
            'address'                => $request->address,
            'phone'                  => $request->phone,
            'date_of_birth'          => $request->date_of_birth,
            'email'                  => $request->email,
            'npwp'                   => $request->npwp,
            'id_plan'                => $request->id_plan,
            'id_merchant'            => $request->id_merchant,
            'coordinate'             => $request->coordinate,
            'note'                   => $request->note,
            'lead_source'            => $request->lead_source,
            'lead_notes'             => $request->lead_notes,
            'expected_close_date'    => $request->expected_close_date,
            'conversion_probability' => $request->conversion_probability,
        ]);

        if ($request->has('addons')) {
            $customer->addons()->sync($request->addons ?? []);
        }

        return redirect('/sales/customer/' . $id)->with('success', 'Data customer berhasil diperbarui.');
    }

    /**
     * Show form to create new customer
     */
    public function showCreateCustomer()
    {
        $sales = Auth::guard('sales')->user();
        $plan     = \App\Plan::pluck('name', 'id');
        $merchant = \App\Merchant::pluck('name', 'id');
        $addons   = \App\Addon::orderBy('name')->get();
        $status   = \App\Statuscustomer::pluck('name', 'id');

        return view('sales.create-customer', [
            'sales'    => $sales,
            'plan'     => $plan,
            'merchant' => $merchant,
            'addons'   => $addons,
            'status'   => $status,
        ]);
    }

    /**
     * Store new customer
     */
    public function storeCustomer(Request $request)
    {
        $request->validate([
            'name'                    => 'required|string|max:191',
            'contact_name'            => 'nullable|string|max:191',
            'address'                 => 'required|string',
            'phone'                   => 'required|string|max:191',
            'date_of_birth'           => 'nullable|date',
            'email'                   => 'nullable|email|max:191',
            'npwp'                    => 'nullable|string|max:191',
            'id_plan'                 => 'required|exists:plans,id',
            'id_merchant'             => 'nullable|exists:merchants,id',
            'coordinate'              => 'nullable|string|max:191',
            'note'                    => 'nullable|string',
            'id_status'               => 'nullable|integer',
            'addons'                  => 'nullable|array',
            'addons.*'                => 'exists:addons,id',
            'lead_source'             => 'nullable|string|max:100',
            'lead_notes'              => 'nullable|string',
            'expected_close_date'     => 'nullable|date',
            'conversion_probability'  => 'nullable|integer|min:0|max:100',
        ]);

        $sales = Auth::guard('sales')->user();

        // Generate customer_id with format: RESCODEYYMMDXXX (same as admin)
        $rescode = config("app.rescode");
        $year = date('Y', time()) - 2000;
        $md = date('md', time());
        $ran = substr(str_shuffle("0123456789"), 0, 3);
        
        // Combine to create customer_id
        $customerId = $rescode . $year . $md . $ran;
        
        // Check if customer_id already exists, if yes, regenerate
        while (Customer::where('customer_id', $customerId)->exists()) {
            $ran = substr(str_shuffle("0123456789"), 0, 3);
            $customerId = $rescode . $year . $md . $ran;
        }

        $idStatus = (int)($request->id_status ?? 1);

        // Create customer
        $customer = Customer::create([
            'customer_id'             => $customerId,
            'name'                    => $request->name,
            'contact_name'            => $request->contact_name,
            'address'                 => $request->address,
            'phone'                   => $request->phone,
            'date_of_birth'           => $request->date_of_birth,
            'email'                   => $request->email,
            'npwp'                    => $request->npwp,
            'id_plan'                 => $request->id_plan,
            'id_merchant'             => $request->id_merchant,
            'coordinate'              => $request->coordinate,
            'note'                    => $request->note,
            'id_status'               => $idStatus,
            'id_sale'                 => $sales->id,
            'pppoe'                   => $customerId,
            'password'                => $customerId,
            // Lead fields (only relevant when status = Potensial)
            'lead_source'             => $idStatus == 1 ? $request->lead_source : null,
            'lead_notes'              => $idStatus == 1 ? $request->lead_notes : null,
            'expected_close_date'     => $idStatus == 1 ? $request->expected_close_date : null,
            'conversion_probability'  => $idStatus == 1 ? $request->conversion_probability : null,
        ]);

        // Sync addons if provided
        if ($request->has('addons') && !empty($request->addons)) {
            $customer->addons()->sync($request->addons);
        }

        return redirect('/sales/customer/' . $customer->id)->with('success', 'Customer baru berhasil ditambahkan dengan ID: ' . $customerId);
    }
}
