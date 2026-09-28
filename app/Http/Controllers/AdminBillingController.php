<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Suminvoice;
use App\Tenant;
use DataTables;
use Illuminate\Http\Request;

/**
 * Privilege "admin_billing": a tenant-app user who should only see data
 * belonging to the merchant(s) configured for this tenant on the central
 * admin panel's Edit Tenant page (tenants.reported_merchant_ids), rather
 * than a single merchant tied to their own user record (unlike the
 * existing "merchant" privilege).
 */
class AdminBillingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkPrivilege:admin,admin_billing');
    }

    /**
     * Merchant IDs this tenant has configured for the admin_billing scope.
     */
    private function merchantScope(): array
    {
        return Tenant::currentReportedMerchantIds();
    }

    public function customers()
    {
        $status = \App\Statuscustomer::pluck('name', 'id');

        return view('adminbilling.customers', [
            'merchantScopeEmpty' => empty($this->merchantScope()),
            'status' => $status,
        ]);
    }

    public function customersData(Request $request)
    {
        $merchantIds = $this->merchantScope();

        $customerQuery = Customer::select('id', 'customer_id', 'name', 'address', 'id_merchant', 'billing_start', 'isolir_date', 'id_plan', 'id_status', 'id_sale')
            ->whereIn('id_merchant', $merchantIds);

        if (!empty($request->filter) && !empty($request->parameter)) {
            $filter = $request->filter;
            $parameter = $request->parameter;

            if ($filter === 'isolir_date') {
                $customerQuery->where("customers.{$filter}", $parameter);
            } else {
                $customerQuery->where("customers.{$filter}", 'LIKE', "%{$parameter}%");
            }
        }

        if (!empty($request->id_status)) {
            $customerQuery->where('id_status', $request->id_status);
        }

        $customerQuery->orderBy('id', 'DESC');

        return DataTables::of($customerQuery)
            ->addIndexColumn()
            ->editColumn('customer_id', function ($customer) {
                return '<a href="/customer/' . $customer->id . '" class="btn btn-primary btn-sm">' . $customer->customer_id . '</a>';
            })
            ->editColumn('id_merchant', function ($customer) {
                return $customer->merchant_name
                    ? '<a class="text-center">' . $customer->merchant_name->name . '</a>'
                    : '<a class="text-center">No Merchant</a>';
            })
            ->addColumn('status_cust', function ($customer) {
                $badgeClass = match ($customer->status_name->name ?? null) {
                    'Active' => 'badge-success',
                    'Inactive' => 'badge-secondary',
                    'Block' => 'badge-danger',
                    'Company_Properti' => 'badge-primary',
                    default => 'badge-warning',
                };
                return '<a class="badge text-white text-center ' . $badgeClass . '">' . ($customer->status_name->name ?? '-') . '</a>';
            })
            ->rawColumns(['customer_id', 'id_merchant', 'status_cust'])
            ->make(true);
    }

    /**
     * Read-only invoice/payment recap for the merchant scope — not the full
     * accounting module (chart of accounts / general journal are not tied
     * to any merchant, so they're intentionally out of scope here).
     */
    public function accounting(Request $request)
    {
        $merchantIds = $this->merchantScope();

        $dateStart = $request->filled('dateStart')
            ? \Carbon\Carbon::parse($request->input('dateStart'))->startOfDay()
            : \Carbon\Carbon::now()->startOfMonth();
        $dateEnd = $request->filled('dateEnd')
            ? \Carbon\Carbon::parse($request->input('dateEnd'))->endOfDay()
            : \Carbon\Carbon::now()->endOfDay();

        $customerIds = Customer::whereIn('id_merchant', $merchantIds)->pluck('id');

        $invoicesQuery = Suminvoice::with('customer')
            ->whereIn('id_customer', $customerIds)
            ->whereBetween('date', [$dateStart, $dateEnd]);

        $totalTagihan = (clone $invoicesQuery)->sum('total_amount');
        $totalDibayar = (clone $invoicesQuery)->where('payment_status', 1)->sum('recieve_payment');
        $totalBelumLunas = (clone $invoicesQuery)->where('payment_status', 0)->count();

        $invoices = $invoicesQuery->orderBy('date', 'desc')->limit(200)->get();

        return view('adminbilling.accounting', [
            'merchantScopeEmpty' => empty($merchantIds),
            'invoices' => $invoices,
            'totalTagihan' => $totalTagihan,
            'totalDibayar' => $totalDibayar,
            'totalBelumLunas' => $totalBelumLunas,
            'dateStart' => $dateStart->format('Y-m-d'),
            'dateEnd' => $dateEnd->format('Y-m-d'),
        ]);
    }
}
