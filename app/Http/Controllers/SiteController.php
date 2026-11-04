<?php

namespace App\Http\Controllers;
//use GeneaLabs\LaravelMaps\Map;

use Illuminate\Http\Request;

class SiteController extends Controller
{
 public function __construct()
 {
    $this->middleware('auth');
    $this->middleware('checkPrivilege:admin,noc,user')->except(['index']);
    // admin_billing gets read-only access to the site list (view only, no create/edit/delete)
    $this->middleware('checkPrivilege:admin,noc,user,admin_billing')->only(['index']);
}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function null()
    {
        return abort(404);
    }
    public function index()
    {
        //

        // $site = \App\Site::all();
     $site = \App\Site::WhereNotIn('id',[0])->get();

     return view ('site/index',['site' =>$site]);
 }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    { 
        //return view ('site/create');
        //
        $config['center'] = tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));
        $config['zoom'] = '13';
//$this->googlemaps->initialize($config);

        $marker = array();
        $marker['position'] = tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));
        $marker['draggable'] = true;
        $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

        app('map')->initialize($config);
        
        app('map')->add_marker($marker);
        $map = app('map')->create_map();

        // echo "<html><head><script type=text/javascript>var centreGot = false;</script>".$map['js']."</head><body>".$map['html']."</body></html>";

        return view ('site/create',['map' => $map] );

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

     $request ->validate([
        'name' => 'required|unique:sites',
        'location' => 'required',
        'coordinate' => 'required',
        'create_at' => 'required',
            // 'description' => 'required'
    ]);


     \App\Site::create($request->all());

     return redirect ('/site')->with('success','Item created successfully!');
 }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $site = \App\Site::findOrFail($id);
        
// Mengatur koordinat pusat
        $coordinate = $site->coordinate ?? tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));

        $center = [
            'coordinate' => $coordinate,
            'zoom' => 12
        ];

$locations = []; // Inisialisasi variabel locations

// Tambahkan lokasi untuk titik distribusi utama (POP)
$popCoord = self::parseLatLng($site->coordinate);
if ($popCoord) {
    $locations[] = [
        'type'        => 'pop',
        'name'        => $site->name,
        'lat'         => $popCoord[0],
        'lng'         => $popCoord[1],
        'description' => $site->description,
        'location'    => $site->location,
    ];
}

// Ambil semua titik distribusi (parent & jumlah pelanggan dimuat sekaligus, tanpa query per ODP)
$distpoints = \App\Distpoint::where('id_site', $id)->orderBy('name')->get();
$parentIds  = $distpoints->pluck('parrent')->filter()->unique()->values();
$parents    = \App\Distpoint::withTrashed()->whereIn('id', $parentIds)->get(['id', 'name', 'coordinate'])->keyBy('id');
$custCounts = \App\Customer::whereIn('id_distpoint', $distpoints->pluck('id'))
    ->selectRaw('id_distpoint, count(*) as n')
    ->groupBy('id_distpoint')
    ->pluck('n', 'id_distpoint');

$childParentIds = $distpoints->pluck('parrent')->filter()->flip(); // ODP yang punya anak
$odpCount = 0;
$odpNoCoord = 0;
foreach ($distpoints as $distpoint) {
    $coord = self::parseLatLng($distpoint->coordinate);
    if (!$coord) {
        $odpNoCoord++;
        continue;
    }
    $odpCount++;
    $parent = ($distpoint->parrent && $distpoint->parrent != 1) ? $parents->get($distpoint->parrent) : null;

    $locations[] = [
        'type'        => 'odp',
        'id'          => $distpoint->id,
        'name'        => $distpoint->name,
        'lat'         => $coord[0],
        'lng'         => $coord[1],
        'port'        => $distpoint->ip,
        'description' => $distpoint->description,
        'customers'   => (int) ($custCounts[$distpoint->id] ?? 0),
        'is_parent'   => isset($childParentIds[$distpoint->id]),
        'parent_name' => $parent ? $parent->name : null,
        'parent'      => $parent ? self::parseLatLng($parent->coordinate) : null,
    ];
}

$stats = [
    'odp'        => $odpCount,
    'odp_nocoord'=> $odpNoCoord,
    'customers'  => (int) $custCounts->sum(),
];


return view('site.show', [
    'distpoint' => $distpoints,

    'site' => $site,
    'center' => $center,
    'locations' => $locations,
    'stats'     => $stats,
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
        //
    $config['center'] = tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));
    $config['zoom'] = '13';
//$this->googlemaps->initialize($config);

    $marker = array();
    $marker['position'] = tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER'));
    $marker['draggable'] = true;
    $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

    app('map')->initialize($config);

    app('map')->add_marker($marker);
    $map = app('map')->create_map();

    return view ('site.edit',['site' => \App\Site::findOrFail($id)],['map' => $map] );


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
        //dd($request);

    $request ->validate([
            // 'name' => 'required|unique:sites',
        'location' => 'required',
        'coordinate' => 'required',
            // 'description' => 'required'
    ]);

    \App\Site::where('id', $id)
    ->update([
                // 'name' => $request->name,
        'location' => $request->location,
        'coordinate' => $request->coordinate,
        'description' => $request->description


    ]);
    return redirect ('/site')->with('success','Item updated successfully!');
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

    \App\Site::destroy($id);
    return redirect ('/site')->with('success','Item deleted successfully!');
}
public function maps()
{



    $config['center'] = env('COORDINATE_CENTER');
    $config['zoom'] = '13';
//$this->googlemaps->initialize($config);

    $marker = array();
    $marker['position'] = env('COORDINATE_CENTER');
    $marker['draggable'] = true;
    $marker['ondragend'] = 'alert(\'You just dropped me at: \' + event.latLng.lat() + \', \' + event.latLng.lng());';

    app('map')->initialize($config);

    app('map')->add_marker($marker);
    $map = app('map')->create_map();

        // echo "<html><head><script type=text/javascript>var centreGot = false;</script>".$map['js']."</head><body>".$map['html']."</body></html>";

    return view ('site/view_file',['map' => $map] );
}

/** "lat,lng" → [lat, lng] atau null bila tidak valid. */
private static function parseLatLng($value): ?array
{
    $p = array_map('trim', explode(',', (string) $value));
    if (count($p) < 2 || !is_numeric($p[0]) || !is_numeric($p[1])) {
        return null;
    }
    $lat = (float) $p[0];
    $lng = (float) $p[1];
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0.0 && $lng == 0.0)) {
        return null;
    }

    return [$lat, $lng];
}
}
