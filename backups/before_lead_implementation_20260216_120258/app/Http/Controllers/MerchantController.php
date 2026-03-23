<?php

namespace App\Http\Controllers;

use App\Merchant;
use DataTables;
use Illuminate\Support\Facades\DB;
use App\Jurnal;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
       // $merchant = \App\Merchant::orderby('id','DESC')
       // ->get();

       // dd($merchant);


        return view ('merchant/index');
    }

    public function gettotalakun($akun_code)
    {
        if (!empty($akun_code)) {
            $data = Jurnal::where('id_akun', $akun_code)
            ->selectRaw('SUM(debet) as total_debet, SUM(kredit) as total_kredit')
            ->first();

            $total = ($data->total_debet ?? 0) - ($data->total_kredit ?? 0);

            return response()->json([
                'success' => true,
                'total' => $total,
                'total_debet' => $data->total_debet ?? 0,
                'total_kredit' => $data->total_kredit ?? 0,
            ]);
        } else {
            return response()->json([
                'success' => true,
                'total' => 0,
                'total_debet' => 0,
                'total_kredit' => 0,
            ]);
        }
    }

    public function table_merchant_list(Request $request){


        $merchant = \App\Merchant::orderBy('name', 'ASC')
        ->get();
        return DataTables::of($merchant)
        ->addIndexColumn()
        ->editColumn('name',function($merchant)
        {

            return ' <a href="/merchant/'.$merchant->id.'" title="merchant" class="badge badge-primary text-center  "> '.$merchant->name. '</a>';
        })


        ->rawColumns(['DT_RowIndex','name','contact_name','phone','address'])

        ->make(true);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $config['center'] = tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));
        $config['zoom'] = '13';

        $marker = array();
        $marker['position'] = tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));
        $marker['draggable'] = true;
        $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

        app('map')->initialize($config);
        
        app('map')->add_marker($marker);
        $map = app('map')->create_map();
        $parentAkuns = \App\Akun::whereNotNull('parent')->distinct()->pluck('parent')->toArray();
        if (empty($parentAkuns)) {
            $parentAkuns = [null]; // Set default agar tidak error pada whereNotIn
        }
        $akuns = \App\Akun::whereNotIn('akun_code', $parentAkuns)
        ->Where('category','kas & bank')

        ->get();

        return view ('merchant/create',['map' => $map, 'akuns'=>$akuns]);
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
        'contact_name' => 'required',
        'phone' => 'required|numeric',
        'address' => 'nullable',
        'coordinate' => 'nullable',
        'description' => 'nullable',

    ]);



       try {

        $result=\App\Merchant::create($request->all());

        return redirect('/merchant')->with('success', 'Item created successfully!');
    } catch (\Exception $e) {
        // Handle any exceptions that occur during the creation process
        return redirect()->back()->withErrors(['error' => 'An error occurred while creating the item: ' . $e->getMessage()]);
    }
}

    /**
     * Display the specified resource.
     *
     * @param  \App\Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //

        $status = \App\Statuscustomer::pluck('name', 'id');
        $plan = \App\Plan::pluck('name', 'id');

        $merchant = \App\Merchant::findOrFail($id);
        if  (\App\Merchant::findOrFail($id) ->coordinate == null)
        {
            $coordinate = tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));
        }
        else
        {
            $coordinate =\App\Merchant::findOrFail($id) ->coordinate;
        }


        $config['center'] = $coordinate;
        $config['zoom'] = '12';
//$this->googlemaps->initialize($config);

        $marker = array();
        $marker['position'] = $coordinate;
        //$marker['draggable'] = true;
        //$marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

        app('map')->initialize($config);

        app('map')->add_marker($marker);
        $map = app('map')->create_map();
        return view ('merchant/show',['merchant' =>$merchant,'map' => $map, 'status'=>$status, 'plan'=>$plan]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Merchant  $merchant
     * @return \Illuminate\Http\Response
     */

    public function getmerchantinfo($id)
    {
     $count_user_potensial = \App\Customer::where('id_merchant', $id)
     ->where('id_status', '=', 1)
     ->count();
     $count_user_active = \App\Customer::where('id_merchant', $id)
     ->where('id_status', '=', 2)
     ->count();
     $count_user_inactive = \App\Customer::where('id_merchant', $id)
     ->where('id_status', '=', 3)
     ->count(); $count_user_block = \App\Customer::where('id_merchant', $id)
     ->where('id_status', '=', 4)
     ->count();
     $count_user_c_properti = \App\Customer::where('id_merchant', $id)
     ->where('id_status', '=', 5)
     ->count();
     return response()->json(['success' => true, 'count_user_potensial' => $count_user_potensial, 'count_user_active' =>$count_user_active,'count_user_block' => $count_user_block, 'count_user_inactive' => $count_user_inactive, 'count_user_c_properti' => $count_user_c_properti ]);


 }
 public function edit($id)
 {
        //

     $config['center'] = tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));
     $config['zoom'] = '13';

     $marker = array();
     $marker['position'] = tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));
     $marker['draggable'] = true;
     $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

     app('map')->initialize($config);

     app('map')->add_marker($marker);
     $map = app('map')->create_map();
     $parentAkuns = \App\Akun::whereNotNull('parent')->distinct()->pluck('parent')->toArray();
     if (empty($parentAkuns)) {
            $parentAkuns = [null]; // Set default agar tidak error pada whereNotIn
        }


        $akuns = \App\Akun::whereNotIn('akun_code', $parentAkuns)
        ->Where('category','kas & bank')

        ->get();


        $merchant = \App\Merchant::findOrFail($id);
        return view ('merchant/edit',['merchant' =>$merchant, 'map' =>$map, 'akuns'=>$akuns]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

    // Validasi input
        $request->validate([
            'name' => 'required',
            'contact_name' => 'required',
            'phone' => 'required|numeric',
            'address' => 'nullable',
            'coordinate' => 'nullable',
            'description' =>'nullable'
        ]);

    // Cek apakah merchant dengan nama yang sama sudah ada
        $check_merchant = \App\Merchant::where('name', $request->name)->first();

    // Jika merchant ditemukan dan ID-nya berbeda
        if ($check_merchant && $id != $check_merchant->id) {
            return redirect('/merchant')->with('error', 'The Name already exists with a different id!');
        }

   // Update data Merchant
        $updateStatus = \App\Merchant::where('id', $id)->update([
            'name' => $request->name,
            'contact_name' => $request->contact_name,
            'phone' => $request->phone,
            'address' => $request->address,
            'coordinate' => $request->coordinate,
            'description' => $request->description,                                   
            'akun_code' => $request->akun_code,
            'payment_point'=> $request->payment_point,
        ]);

// Verifikasi apakah data berhasil diperbarui
        if ($updateStatus) {
            return redirect('/merchant')->with('success', 'Item updated successfully!');
        } else {
            return redirect('/merchant')->with('error', 'Failed to update item. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        try {

         \App\Merchant::destroy($id);
         return redirect ('merchant')->with('success','Item deleted successfully!');

     } catch (\Exception $e) {
        // Handle any exceptions that occur during the creation process
        return redirect()->back()->withErrors(['error' => 'An error occurred while delete the item: ' . $e->getMessage()]);
    }
}

}
