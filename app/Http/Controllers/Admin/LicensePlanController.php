<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\LicensePlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicensePlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * List all license plans
     */
    public function index()
    {
        $plans = LicensePlan::orderBy('sort_order')->orderBy('id')->get();
        return view('admin.license-plans.index', compact('plans'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.license-plans.create');
    }

    /**
     * Store new license plan
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:100|unique:isp_master.license_plans,name',
            'max_customers' => 'required|integer|min:-1',
            'price_monthly' => 'nullable|numeric|min:0',
            'description'   => 'nullable|string|max:500',
            'sort_order'    => 'nullable|integer|min:0',
            'is_active'     => 'nullable|boolean',
        ], [
            'name.required'          => 'Nama plan wajib diisi.',
            'name.unique'            => 'Nama plan sudah ada.',
            'max_customers.required' => 'Maksimum pelanggan wajib diisi.',
            'max_customers.min'      => 'Minimal -1 (untuk unlimited).',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        LicensePlan::create([
            'name'          => $request->name,
            'max_customers' => $request->max_customers,
            'price_monthly' => $request->price_monthly ?? 0,
            'description'   => $request->description,
            'sort_order'    => $request->sort_order ?? 0,
            'is_active'     => $request->has('is_active'),
        ]);

        return redirect()->route('admin.license-plans.index')
            ->with('success', "License plan \"{$request->name}\" berhasil dibuat!");
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $plan = LicensePlan::findOrFail($id);
        return view('admin.license-plans.edit', compact('plan'));
    }

    /**
     * Update license plan
     */
    public function update(Request $request, $id)
    {
        $plan = LicensePlan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:100|unique:isp_master.license_plans,name,' . $id,
            'max_customers' => 'required|integer|min:-1',
            'price_monthly' => 'nullable|numeric|min:0',
            'description'   => 'nullable|string|max:500',
            'sort_order'    => 'nullable|integer|min:0',
            'is_active'     => 'nullable|boolean',
        ], [
            'name.required'          => 'Nama plan wajib diisi.',
            'name.unique'            => 'Nama plan sudah ada.',
            'max_customers.required' => 'Maksimum pelanggan wajib diisi.',
            'max_customers.min'      => 'Minimal -1 (untuk unlimited).',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $plan->update([
            'name'          => $request->name,
            'max_customers' => $request->max_customers,
            'price_monthly' => $request->price_monthly ?? 0,
            'description'   => $request->description,
            'sort_order'    => $request->sort_order ?? 0,
            'is_active'     => $request->has('is_active'),
        ]);

        return redirect()->route('admin.license-plans.index')
            ->with('success', "License plan \"{$plan->name}\" berhasil diupdate!");
    }

    /**
     * Delete license plan
     */
    public function destroy($id)
    {
        $plan = LicensePlan::findOrFail($id);

        // Prevent deletion if tenants are using this plan
        $tenantCount = $plan->tenants()->count();
        if ($tenantCount > 0) {
            return redirect()->back()
                ->with('error', "Tidak bisa menghapus plan \"{$plan->name}\" karena masih digunakan oleh {$tenantCount} tenant.");
        }

        $name = $plan->name;
        $plan->delete();

        return redirect()->route('admin.license-plans.index')
            ->with('success', "License plan \"{$name}\" berhasil dihapus!");
    }
}
