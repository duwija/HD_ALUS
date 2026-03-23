<?php

namespace App\Http\Controllers;
use \App\Distpoint;
use DataTables;

use Illuminate\Http\Request;

class DistpointController extends Controller
{
   public function __construct()
   {
    $this->middleware('auth');
        $this->middleware('checkPrivilege:admin,noc,accounting,payment,user,marketing'); // Daftar privilege
        $this->middleware('checkPrivilege:admin,noc')->only(['create', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */




    public function table_distpoint_list(Request $request){


     $distpoint = \App\Distpoint::with(['group.customers', 'site_name', 'distpoint_name'])
     ->withCount('customer')
     // ->whereNotIn('id', [1])  // Commented out to show all distpoints including MS01
     ->when($request->group, function ($query, $group) {
        $query->whereHas('group', function ($q) use ($group) {
            $q->where('name', 'like', "%$group%");
        });
    })
     ->when($request->site, function ($query, $site) {
        $query->whereHas('site_name', function ($q) use ($site) {
            $q->where('name', 'like', "%$site%");
        });
    })
     ->when($request->name, function ($query, $name) {
        $query->where('name', 'like', "%$name%");
    })
     ->orderBy('name', 'ASC')
     ->get();

     return DataTables::of($distpoint)
     ->addIndexColumn()
     ->editColumn('name',function($distpoint)
     {

        return ' <a href="/distpoint/'.$distpoint->id.'" title="distpoint" class="badge badge-primary text-center  "> '.$distpoint->name. '</a>';
    })
     ->addColumn('site', function($distpoint) {
        $siteName = ($distpoint->site_name->name);

        return ($distpoint->id_site && $distpoint->id_site !== '0' )
        ? '<a class="badge badge-success text-center">'.$siteName.'</a>'
        : '<a class="badge badge-warning text-center">None</a>';
    })
     ->editColumn('group', function ($distpoint) {
        $group = $distpoint->group;
        $name = trim(optional($group)->name);

        if ($name) {
            $customerCount = $group->customers->count();
        $capacity = $group->capacity ?: 1; // hindari pembagian 0
        $percent = ($customerCount / $capacity) * 100;

        if ($percent < 69) {
            $color = 'badge-success';
        } elseif ($percent < 89) {
            $color = 'badge-warning';
        } else {
            $color = 'badge-error';
        }

        return '<a href="/distpointgroup/'.$group->id.'" class="badge badge-primary">'.$name.'</a> 
        <a class="badge '.$color.'">'.$customerCount.'/'.$capacity.'</a>';
    } else {
        return '<a class="badge badge-warning">None</a>';
    }
})

     ->editColumn('parrent', function($distpoint) {
        $parrent = $distpoint->parrent;

        if ($parrent && $parrent !== '0' && $distpoint->distpoint_name) {
            return '<a href="/distpoint/'.$distpoint->parrent.'" class="badge badge-info">'.$distpoint->distpoint_name->name.'</a>';
        } else {
            return '<a class="badge badge-warning">None</a>';
        }
    })
     ->addColumn('customer_count', function($distpoint) {

        $threshold = floor(0.6 * $distpoint->ip);
        if($distpoint->customer_count < $threshold)
        {
            $color='badge-success';
        }
        elseif($distpoint->customer_count >= $threshold)
        {
            $color='badge-warning';
        }
        else
        {
            $color='badge-error';
        }

        return '<a class="badge '.$color.'">'.$distpoint->customer_count.'</a>';
    })

     ->rawColumns(['DT_RowIndex','name','site','ip','security','parrent','description','customer_count','group'])

     ->make(true);
 }
 public function index()
 {
        //
        // $distpoint = \App\Distpoint::all();
   $distpoint = \App\Distpoint::WhereNotIn('id',[1])->get();


   return view ('distpoint/index',['distpoint' =>$distpoint]);
}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $site = \App\Site::pluck('name', 'id');
        $distpoint = \App\Distpoint::pluck('name', 'id');
        $distpointgroup = \App\Distpointgroup::pluck('name', 'id');



        return view ('distpoint/create',['site' => $site, 'distpoint' => $distpoint, 'distpointgroup' => $distpointgroup ] );


    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //protected $fillable =['name','id_site', 'ip', 'security','parrent','coordinate','created_at','description'];
        $request ->validate([
            'name' => 'required|unique:distpoints',
            'id_site' => 'required',
            'parrent' => 'nullable',
            'coordinate' => 'required',
            // 'create_at' => 'required',
            // 'description' => 'required'
        ]);


        \App\Distpoint::create($request->all());

        return redirect ('/distpoint')->with('success','Item created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $distpoint = \App\Distpoint::findOrFail($id);
        $site = \App\Site::findOrFail($distpoint->id_site);
        $distpoint_name = $distpoint->parrent ? \App\Distpoint::find($distpoint->parrent) : null;

    $distpoint_chart = collect(); // Default kosong
    $group_distpoint_count = 0;   // Default 0
    $group_total_capacity = 0;    // Default 0
    $customer_group_count = 0;    // Default 0


    if ($distpoint->distpointgroup_id) {
        $distpoint_chart = \App\Distpoint::where('distpointgroup_id', $distpoint->distpointgroup_id)->get();
        $distpoints_in_group = \App\Distpoint::where('distpointgroup_id', $distpoint->distpointgroup_id)->get();
        $group_distpoint_count = $distpoints_in_group->count();
    $group_total_capacity = $distpoints_in_group->sum('ip'); // pastikan 'ip' adalah kapasitas
} else {
    $distpoint_chart = collect(); // Kosongkan saja, biar tidak error
}



 // Hitung jumlah customer dalam distpoint ini
$customer_count = \App\Customer::where('id_distpoint', $distpoint->id)->count();

    // Hitung jumlah customer dalam seluruh distpoint group ini
$customer_group_count = 0;
if ($distpoint->distpointgroup_id) {
        // Ambil semua distpoint dalam group ini
    $distpoints_in_group = \App\Distpoint::where('distpointgroup_id', $distpoint->distpointgroup_id)->pluck('id');

        // Hitung customer yang id_distpoint-nya masuk dalam distpoint group
    $customer_group_count = \App\Customer::whereIn('id_distpoint', $distpoints_in_group)->count();
}


        // $distpointgroup = \App\Distpointgroup::findOrFail($distpoint->distpointgroup_id);

    // Mengatur koordinat pusat
$coordinate = $distpoint->coordinate ?? tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));


$center = [
    'coordinate' => $coordinate,
    'zoom' => 15
];
$locations = []; // Inisialisasi variabel locations

// Tambahkan lokasi untuk titik distribusi

$distpoints_in_group = \App\Distpoint::where('distpointgroup_id', $distpoint->distpointgroup_id)->get();


foreach ($distpoints_in_group as $dist) {
    if ($dist->coordinate) {
        $locations[] = [
            'coordinate' => $dist->coordinate,
            'name' => $dist->name,
            'type' => 'distpoint',
            'parent_coordinate' => optional($dist->parentDistPoint)->coordinate // relasi `parrentDistpoint`
        ];
    }
}


// Ambil semua pelanggan yang terkait dengan titik distribusi
$customers = \App\Customer::where('id_distpoint', $id)->get();

// Customers
foreach ($customers as $customer) {
    $coordinate = $customer->coordinate;
    $parentCoordinate = optional($customer->distpoint)->coordinate;

    // Validasi format koordinat: harus ada dan terdiri dari dua angka float
    if ($this->isValidCoordinate($coordinate)) {

        $locations[] = [
            'coordinate' => $coordinate,
            'name' => $customer->name,
            'type' => 'customer',
            'parent_coordinate' => $this->isValidCoordinate($parentCoordinate) ? $parentCoordinate : null,

        ];
    }
}


return view('distpoint.show', [
    'distpoint' => $distpoint,
    'site' => $site,
    'center' => $center,
    'locations' => $locations,
    'distpoint_name' => $distpoint_name,
    'distpoint_chart' => $distpoint_chart,
    'customer_count' => $customer_count,
    'customer_group_count' => $customer_group_count,
    'group_distpoint_count' => $group_distpoint_count,
    'group_total_capacity' => $group_total_capacity,
]);





}

private function isValidCoordinate($coordinate)
{
    if (!$coordinate || !is_string($coordinate)) return false;

    $parts = explode(',', $coordinate);
    if (count($parts) !== 2) return false;

    $lat = trim($parts[0]);
    $lng = trim($parts[1]);

    return is_numeric($lat) && is_numeric($lng);
}
public function groupshow($id)
{
    $distpoint = \App\Distpoint::findOrFail($id);
    $site = \App\Site::findOrFail($distpoint->id_site);
    $distpoint_name = \App\Distpoint::findOrFail($distpoint->parrent);
    $distpoint_chart = \App\Distpoint::where('parrent', $id)->get();


    // Mengatur koordinat pusat
    $coordinate = $distpoint->coordinate ?? tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));


    $center = [
        'coordinate' => $coordinate,
        'zoom' => 15
    ];
$locations = []; // Inisialisasi variabel locations

// Tambahkan lokasi untuk titik distribusi
$locations[] = [
    'customer' => $distpoint->coordinate,
    'name' => $distpoint->name, // Pastikan menggunakan $distpoint
    'icon' => url('img/odp1.png') // Ikon untuk titik distribusi
];

// Ambil semua pelanggan yang terkait dengan titik distribusi
$customers = \App\Customer::where('id_distpoint', $id)->get();

// Tambahkan lokasi untuk setiap pelanggan
foreach ($customers as $customer) {
    if ($customer->coordinate) {
        // Tambahkan logika untuk menentukan ikon pelanggan
        // $icon = url('img/default_icon.png'); // Ikon default
        // if ($customer->type === 'premium') {
        //     $icon = url('img/premium_icon.png'); // Ikon untuk pelanggan premium
        // } elseif ($customer->type === 'standard') {
        //     $icon = url('img/standard_icon.png'); // Ikon untuk pelanggan standar
        // }

        $locations[] = [
            'customer' => $customer->coordinate,
            'name' => $customer->name,
            // 'icon' => $icon // Menambahkan ikon khusus
        ];
    }
}



return view('distpoint.groupshow', [
    'distpoint' => $distpoint,
    'site' => $site,
    'center' => $center,
    'locations' => $locations,
    'distpoint_name' => $distpoint_name,
    'distpoint_chart' => $distpoint_chart,

]);

}


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {


        $site = \App\Site::pluck('name', 'id');
        $distpoint_name = \App\Distpoint::pluck('name', 'id');
        $distpoint = \App\Distpoint::findOrFail($id);
        $distpointgroup = \App\Distpointgroup::pluck('name', 'id');




        
        if ($distpoint->coordinate == null)
        {
            $coordinate = tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));
        }
        else
        {
            $coordinate = $distpoint->coordinate;
        }
        //

        $config['center'] = $coordinate;
        $config['zoom'] = '13';

        $marker = array();
        $marker['position'] = $coordinate;
        $marker['draggable'] = true;
        $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

        app('map')->initialize($config);
        
        app('map')->add_marker($marker);
        $map = app('map')->create_map();

        return view ('distpoint.edit',['distpoint' => $distpoint,'site' => $site,'map' => $map, 'distpoint_name' => $distpoint_name,'distpointgroup' => $distpointgroup] );
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
          'id_site' => 'required',
          'parrent' => 'nullable',
          'coordinate' => 'required',
          'distpointgroup_id' => 'nullable',
            // 'description' => 'required'
      ]);


        $check_dispoint = \App\Distpoint::where('name', $request->name)->first();
        
        if ($check_dispoint)

        {
         if  ($id != $check_dispoint->id )
         {

            return redirect ('/distpoint')->with('error','The Name already exists with a different id!');
        }
        else
        {

          \App\Distpoint::where('id', $id)->update([
            'name' => $request->name,
            'id_site' => $request->id_site,
            'parrent' => $request->parrent,
            'ip' => $request->ip,
            'security' => $request->security,
            'coordinate' => $request->coordinate,
            'distpointgroup_id' => $request->distpointgroup_id,
            'description' => $request->description



        ]);
          return redirect ('/distpoint')->with('success','Item updated successfully!');
      }
  }

  else
  {

      \App\Distpoint::where('id', $id)->update([
        'name' => $request->name,
        'id_site' => $request->id_site,
        'parrent' => $request->parrent,
        'ip' => $request->ip,
        'security' => $request->security,
        'coordinate' => $request->coordinate,
        'description' => $request->description



    ]);
      return redirect ('/distpoint')->with('success','Item updated successfully!');
  }




}
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //      <div class="form-group">
      \App\Distpoint::destroy($id);
      return redirect ('distpoint')->with('success','Item deleted successfully!');
  } 



  public function showMap()
  {
    return view('distpoint/map');
}


public function getCustomersByOdp($id)
{
    $customers = \App\Customer::where('id_distpoint', $id)
    ->select('id', 'name', 'coordinate', 'address', 'id_status')
    ->get();

    return response()->json($customers);
}

public function getODPData()
{
    $data = \App\Distpoint::with('parentDistPoint') // Tambahkan eager loading
    ->select('id', 'name', 'coordinate', 'ip', 'parrent','description')
    ->get();

    $odp = $data->map(function ($item) {
        $coordinate = explode(',', $item->coordinate);
        $parentCoordinate = explode(',', optional($item->parentDistPoint)->coordinate);

        if (count($coordinate) !== 2) {
            return null;
        }
        $parentName = (($item->parentDistPoint)->name) ?? null;
        return [
            'id' => $item->id,
            'name' => $item->name,
            'Capacity' => $item->ip,
            'parrent' => $item->parrent,
            'description' => $item->description,
            'parent_name' => $parentName,
            'lat' => (float) trim($coordinate[0]),
            'lng' => (float) trim($coordinate[1]),
            'parent_lat' => isset($parentCoordinate[0]) ? (float) trim($parentCoordinate[0]) : null,
            'parent_lng' => isset($parentCoordinate[1]) ? (float) trim($parentCoordinate[1]) : null,
            'button_link' => url("/distpoint/{$item->id}")
        ];
    })->filter();

    return response()->json($odp);
}

/**
 * Get all saved map layers
 */
public function getMapLayers()
{
    $layers = \App\MapLayer::where('is_visible', true)
        ->orderBy('created_at', 'desc')
        ->get();
    
    return response()->json($layers);
}

/**
 * Save new map layer
 */
public function saveMapLayer(Request $request)
{

    $rules = [
        'type' => 'required|in:polyline,polygon,rectangle,circle,marker,group',
        'color' => 'nullable|string',
        'icon' => 'nullable|string',
        'weight' => 'nullable|integer',
        'opacity' => 'nullable|numeric',
        'name' => 'nullable|string',
        'description' => 'nullable|string',
        'distance' => 'nullable|numeric',
        'area' => 'nullable|numeric'
    ];
    if ($request->type !== 'group') {
        $rules['coordinates'] = 'required';
    }
    $request->validate($rules);

    $layer = \App\MapLayer::create([
        'name' => $request->name,
        'type' => $request->type,
        'coordinates' => $request->type === 'group' ? '[]' : ($request->coordinates ?? ''),
        'color' => $request->color ?? '#3388ff',
        'icon' => $request->icon ?? 'pin',
        'weight' => $request->weight ?? 3,
        'opacity' => $request->opacity ?? 0.6,
        'description' => $request->description,
        'distance' => $request->distance,
        'area' => $request->area,
        'created_by' => auth()->id(),
        'is_visible' => true
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Layer berhasil disimpan',
        'data' => $layer
    ]);
}

/**
 * Update map layer
 */
public function updateMapLayer(Request $request, $id)
{
    $layer = \App\MapLayer::findOrFail($id);

    $request->validate([
        'name' => 'nullable|string',
        'description' => 'nullable|string',
        'color' => 'nullable|string',
        'icon' => 'nullable|string',
        'weight' => 'nullable|integer',
        'opacity' => 'nullable|numeric',
        'is_visible' => 'nullable|boolean',
        'group' => 'nullable|string',
        'coordinates' => 'nullable|string', // JSON string of coordinates
    ]);

    $layer->update($request->only([
        'name', 
        'description', 
        'color', 
        'icon',
        'weight', 
        'opacity', 
        'is_visible',
        'group',
        'coordinates', // CRITICAL: Allow coordinate updates!
    ]));

    return response()->json([
        'success' => true,
        'message' => 'Layer berhasil diupdate',
        'data' => $layer
    ]);
}

/**
 * Delete map layer
 */
public function deleteMapLayer($id)
{
    $layer = \App\MapLayer::findOrFail($id);
    $layer->delete();

    return response()->json([
        'success' => true,
        'message' => 'Layer berhasil dihapus'
    ]);
}



}
