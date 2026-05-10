<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \RouterOS\Client;
use \RouterOS\Query;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
class DistrouterController extends Controller
{
 public function __construct()
 {
    $this->middleware('auth');
    $this->middleware('checkPrivilege:admin,noc,user')->except(['client_monitor']);
    $this->middleware('checkPrivilege:admin,noc,user,accounting')->only(['client_monitor']);
}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */




    
    public function pppoeMonitor()
    {
        $routers = \App\Distrouter::orderBy('name')->get();
        return view('distrouter.pppoe-monitor', compact('routers'));
    }

    public function pppoeMonitorData(Request $request)
    {
        $hours   = (int) ($request->input('hours', 24));
        $routerId = $request->input('router_id');

        $since = \Carbon\Carbon::now()->subHours($hours);

        $query = \App\PppoeStat::with('distrouter')
            ->where('collected_at', '>=', $since)
            ->orderBy('collected_at', 'asc');

        if ($routerId) {
            $query->where('distrouter_id', $routerId);
        }

        $stats = $query->get()->groupBy('distrouter_id');

        $result = [];
        foreach ($stats as $rid => $rows) {
            $router = $rows->first()->distrouter;
            $result[] = [
                'id'     => $rid,
                'name'   => $router ? $router->name : 'Router #'.$rid,
                'labels' => $rows->pluck('collected_at')->map(fn($d) => $d->format('H:i'))->values(),
                'total'  => $rows->pluck('total')->values(),
                'active' => $rows->pluck('active')->values(),
                'offline'=> $rows->pluck('offline')->values(),
                'disabled'=> $rows->pluck('disabled')->values(),
                'latest' => [
                    'total'    => $rows->last()->total,
                    'active'   => $rows->last()->active,
                    'offline'  => $rows->last()->offline,
                    'disabled' => $rows->last()->disabled,
                    'at'       => $rows->last()->collected_at->format('d/m H:i'),
                ],
            ];
        }

        return response()->json($result);
    }

    public function pppoeMap()
    {
        $routers = \App\Distrouter::orderBy('name')->get();
        $coordinateCenter = tenant_env('coordinate_center', tenant_env('COORDINATE_CENTER', '-8.5, 115.2'));
        return view('distrouter.pppoe-map', compact('routers', 'coordinateCenter'));
    }

    public function pppoeMapData(Request $request)
    {
        $routerId = $request->input('router_id'); // optional filter
        $loadOdpInfo = (int) $request->input('load_odp_info', 0) === 1;
        $defaultStatusIds = [2, 3, 4, 5]; // Active, Inactive, Block, Company Properti
        $statusIds = $defaultStatusIds;

        if ($request->has('status_ids')) {
            $statusIds = collect(explode(',', (string) $request->input('status_ids')))
                ->map(function ($v) {
                    return (int) trim($v);
                })
                ->filter(function ($v) {
                    return $v > 0;
                })
                ->unique()
                ->values()
                ->all();
        }

        $routerTimeout = (int) tenant_env('PPPOE_MAP_ROUTER_TIMEOUT', 2);
        if ($routerTimeout < 1) {
            $routerTimeout = 1;
        }
        $skipFailSeconds = (int) tenant_env('PPPOE_MAP_SKIP_FAIL_SECONDS', 300);
        if ($skipFailSeconds < 30) {
            $skipFailSeconds = 30;
        }

        $autoDisableThreshold = (int) tenant_env('PPPOE_MAP_AUTO_DISABLE_THRESHOLD', 5);

        $routers = $routerId
            ? \App\Distrouter::where('id', $routerId)->get()
            : \App\Distrouter::where('is_active', 1)->get();

        $markers   = [];
        $syncedAt  = null;
        $useDbData = false;

        // ── DB-first: check if pppoe_sessions table has recent data ──────────
        // The pppoe:sync-sessions command runs every 5 minutes and keeps this table fresh.
        // If data exists and is fresh (synced within 15 minutes), use it (fast path).
        // Otherwise fall back to live router queries.
        $staleLimitMinutes = 15;
        try {
            $latestSync = \DB::table('pppoe_sessions')->max('synced_at');
            if ($latestSync && Carbon::parse($latestSync)->diffInMinutes(now()) <= $staleLimitMinutes) {
                $syncedAt  = $latestSync;
                $useDbData = true;
            }
        } catch (\Exception $e) {
            // Table may not exist yet (migration pending) – fall through to live queries
            \Log::info('[PppoeMap] pppoe_sessions table unavailable, using live queries: ' . $e->getMessage());
        }

        if ($useDbData) {
            // ── Fast path: read offline PPPoE names from synced DB table ─────
            $dbQuery = \DB::table('pppoe_sessions')
                ->where('is_online', false)
                ->select('pppoe_name', 'distrouter_id', 'last_offline_at');

            if ($routerId) {
                $dbQuery->where('distrouter_id', $routerId);
            }

            $offlineSessions = $dbQuery->get()->keyBy('pppoe_name');
            $offlinePppoeNames = $offlineSessions->keys()->toArray();

            if (!empty($offlinePppoeNames)) {
                // Build router name lookup
                $routerNames = $routers->pluck('name', 'id')->toArray();

                $customers = \App\Customer::with('distpoint_name')
                    ->whereIn('pppoe', $offlinePppoeNames)
                    ->whereIn('id_status', $statusIds)
                    ->whereNotNull('coordinate')
                    ->where('coordinate', '!=', '')
                    ->limit(5000)
                    ->get(['id', 'name', 'pppoe', 'coordinate', 'phone', 'address', 'customer_id', 'id_distpoint', 'id_olt', 'id_onu', 'id_distrouter', 'id_status']);

                foreach ($customers as $c) {
                    $coords = array_map('trim', explode(',', $c->coordinate));
                    if (count($coords) < 2) continue;
                    $lat = (float) $coords[0];
                    $lng = (float) $coords[1];
                    if ($lat === 0.0 || $lng === 0.0) continue;
                    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) continue;

                    $sess   = $offlineSessions->get($c->pppoe);
                    $rName  = $sess ? ($routerNames[$sess->distrouter_id] ?? 'Unknown') : '-';
                    $lastOff= $sess ? $sess->last_offline_at : null;

                    $odpLat = null; $odpLng = null; $odpName = null;
                    if ($c->distpoint_name && $c->distpoint_name->coordinate) {
                        $dc = array_map('trim', explode(',', $c->distpoint_name->coordinate));
                        if (count($dc) >= 2) {
                            $dl = (float)$dc[0]; $dn = (float)$dc[1];
                            if (!($dl === 0.0 || $dn === 0.0) && $dl >= -90 && $dl <= 90 && $dn >= -180 && $dn <= 180) {
                                $odpLat  = $dl;
                                $odpLng  = $dn;
                                $odpName = $c->distpoint_name->name;
                            }
                        }
                    }

                    $markers[] = [
                        'lat'          => $lat,
                        'lng'          => $lng,
                        'id'           => $c->id,
                        'name'         => $c->name,
                        'customer_id'  => $c->customer_id,
                        'pppoe'        => $c->pppoe,
                        'phone'        => $c->phone,
                        'address'      => $c->address,
                        'router'       => $rName,
                        'id_olt'       => $c->id_olt,
                        'id_onu'       => $c->id_onu,
                        'id_distrouter'=> $c->id_distrouter,
                        'id_status'    => $c->id_status,
                        'secret_status'=> 'enable',
                        'last_offline' => $lastOff,
                        'odp_id'       => $c->distpoint_name ? $c->distpoint_name->id : null,
                        'odp_lat'      => $odpLat,
                        'odp_lng'      => $odpLng,
                        'odp_name'     => $odpName,
                    ];
                }
            }

        } else {
            // ── Fallback: live router queries (2-pass approach) ───────────────
            $globalOnlineIndex = [];
            $routerDataCache   = [];

            foreach ($routers as $router) {
                if (empty($router->ip)) continue;

                $skipKey = 'pppoe_map_skip_router_' . $router->id;
                if (!$routerId && Cache::has($skipKey)) continue;

                try {
                    $client = new Client([
                        'host'    => $router->ip,
                        'user'    => $router->user,
                        'pass'    => $router->password,
                        'port'    => (int) $router->port,
                        'timeout' => $routerTimeout,
                    ]);

                    if ($router->fail_count > 0 || !$router->is_active) {
                        $router->fail_count = 0;
                        $router->is_active  = 1;
                        $router->save();
                    }
                    Cache::forget($skipKey);

                    $q           = new Query('/ppp/active/print');
                    $active      = $client->query($q)->read();
                    $onlineNames = collect($active)->pluck('name')->toArray();

                    $q       = new Query('/ppp/secret/print');
                    $secrets = $client->query($q)->read();

                    $routerDataCache[$router->id] = [
                        'router'        => $router,
                        'onlineNames'   => $onlineNames,
                        'secrets'       => $secrets,
                        'lastLoggedOut' => [],
                    ];

                    foreach ($onlineNames as $name) {
                        $globalOnlineIndex[$name] = $router->name;
                    }

                    foreach ($secrets as $s) {
                        if (!empty($s['last-logged-out']) && $s['last-logged-out'] !== 'never') {
                            $routerDataCache[$router->id]['lastLoggedOut'][$s['name']] = $s['last-logged-out'];
                        }
                    }

                } catch (\Exception $e) {
                    if (!$routerId) {
                        Cache::put($skipKey, 1, now()->addSeconds($skipFailSeconds));
                        $router->fail_count = ($router->fail_count ?? 0) + 1;
                        if ($autoDisableThreshold > 0 && $router->fail_count >= $autoDisableThreshold) {
                            $router->is_active = 0;
                            \Log::warning("[PppoeMap] Router {$router->name}: auto-disabled after {$router->fail_count} failures");
                        }
                        $router->save();
                    }
                    \Log::warning("[PppoeMap] Router {$router->name}: " . $e->getMessage());
                }
            }

            foreach ($routerDataCache as $routerData) {
                $router        = $routerData['router'];
                $secrets       = $routerData['secrets'];
                $lastLoggedOut = $routerData['lastLoggedOut'];
                $secretMeta    = [];

                $offlineNames = [];
                foreach ($secrets as $s) {
                    $name = $s['name'] ?? null;
                    if (!$name) continue;

                    $isDisabled = isset($s['disabled']) && $s['disabled'] === 'true';
                    $isOnline   = isset($globalOnlineIndex[$name]);
                    $status     = $isDisabled ? 'disabled' : ($isOnline ? 'online' : 'enable');
                    $secretMeta[$name] = $status;

                    if (!$isDisabled && !$isOnline) {
                        $offlineNames[] = $name;
                    }
                }

                if (empty($offlineNames)) continue;

                $customers = \App\Customer::with('distpoint_name')
                    ->whereIn('pppoe', $offlineNames)
                    ->whereIn('id_status', $statusIds)
                    ->whereNotNull('coordinate')
                    ->where('coordinate', '!=', '')
                    ->get(['id', 'name', 'pppoe', 'coordinate', 'phone', 'address', 'customer_id', 'id_distpoint', 'id_olt', 'id_onu', 'id_distrouter', 'id_status']);

                foreach ($customers as $c) {
                    $coords = array_map('trim', explode(',', $c->coordinate));
                    if (count($coords) < 2) continue;
                    $lat = (float) $coords[0];
                    $lng = (float) $coords[1];
                    if ($lat === 0.0 || $lng === 0.0) continue;
                    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) continue;

                    $odpLat = null; $odpLng = null; $odpName = null;
                    if ($c->distpoint_name && $c->distpoint_name->coordinate) {
                        $dc = array_map('trim', explode(',', $c->distpoint_name->coordinate));
                        if (count($dc) >= 2) {
                            $dl = (float)$dc[0]; $dn = (float)$dc[1];
                            if (!($dl === 0.0 || $dn === 0.0) && $dl >= -90 && $dl <= 90 && $dn >= -180 && $dn <= 180) {
                                $odpLat  = $dl; $odpLng  = $dn; $odpName = $c->distpoint_name->name;
                            }
                        }
                    }

                    $markers[] = [
                        'lat'          => $lat,
                        'lng'          => $lng,
                        'id'           => $c->id,
                        'name'         => $c->name,
                        'customer_id'  => $c->customer_id,
                        'pppoe'        => $c->pppoe,
                        'phone'        => $c->phone,
                        'address'      => $c->address,
                        'router'       => $router->name,
                        'id_olt'       => $c->id_olt,
                        'id_onu'       => $c->id_onu,
                        'id_distrouter'=> $c->id_distrouter,
                        'id_status'    => $c->id_status,
                        'secret_status'=> $secretMeta[$c->pppoe] ?? 'enable',
                        'last_offline' => $lastLoggedOut[$c->pppoe] ?? null,
                        'odp_id'       => $c->distpoint_name ? $c->distpoint_name->id : null,
                        'odp_lat'      => $odpLat,
                        'odp_lng'      => $odpLng,
                        'odp_name'     => $odpName,
                    ];
                }

                if (count($markers) > 5000) {
                    $markers = array_slice($markers, 0, 5000);
                    break;
                }
            }
        }

        // --- Build ODP info (cached) ---
        $odpInfoCacheKey = 'pppoe_map_odp_info_' . md5(tenant_id());
        $odpInfoTtl = (int) tenant_env('PPPOE_MAP_ODP_INFO_CACHE_SECONDS', 300);
        if ($odpInfoTtl < 60) {
            $odpInfoTtl = 60;
        }
        $odpInfo = [];
        if ($loadOdpInfo) {
            $odpInfo = Cache::remember($odpInfoCacheKey, $odpInfoTtl, function() {
                $odpInfo = [];
                try {
                    $odpInfoLimit = (int) tenant_env('PPPOE_MAP_ODP_INFO_LIMIT', 5000);
                    if ($odpInfoLimit < 1000) {
                        $odpInfoLimit = 1000;
                    }
                    // Build accurate ODP totals directly from customers table.
                    $customerCounts = DB::table('customers')
                        ->select('id_distpoint', DB::raw('COUNT(*) as total_count'))
                        ->whereNull('deleted_at')
                        ->whereNotNull('id_distpoint')
                        ->groupBy('id_distpoint')
                        ->get()
                        ->keyBy('id_distpoint');

                    $allDistpoints = \App\Distpoint::query()
                        ->whereNotNull('coordinate')->where('coordinate', '!=', '')
                        ->select(['id', 'name', 'description', 'ip'])
                        ->limit($odpInfoLimit)
                        ->get();
                    foreach ($allDistpoints as $dp) {
                        $capacity = is_numeric($dp->ip) ? (int) $dp->ip : null;
                        $totalCount = 0;
                        if (isset($customerCounts[$dp->id])) {
                            $totalCount = (int) ($customerCounts[$dp->id]->total_count ?? 0);
                        }
                        $odpInfo[$dp->id] = [
                            'id'             => $dp->id,
                            'name'           => $dp->name,
                            'description'    => $dp->description,
                            'customer_count' => $totalCount,
                            'total_customer_count' => $totalCount,
                            'capacity'       => $capacity,
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::warning('[PppoeMap] ODP info build: ' . $e->getMessage());
                }
                return $odpInfo;
            });
        }

        // --- Build ODP parent-child links (cached) ---
        $odpLinksCacheKey = 'pppoe_map_odp_links_' . md5(tenant_id());
        $odpLinks = Cache::remember($odpLinksCacheKey, 3600, function() {
            $odpLinks = [];
            try {
                $odpLinkLimit = (int) tenant_env('PPPOE_MAP_ODP_LINK_LIMIT', 5000);
                if ($odpLinkLimit < 500) {
                    $odpLinkLimit = 500;
                }
                $distpoints = \App\Distpoint::with('parentDistPoint')
                    ->whereNotNull('coordinate')->where('coordinate', '!=', '')
                    ->whereNotNull('parrent')->where('parrent', '!=', 0)
                    ->select(['id', 'name', 'coordinate', 'parrent'])
                    ->limit($odpLinkLimit)
                    ->get();

            foreach ($distpoints as $dp) {
                $parent = $dp->parentDistPoint;
                if (!$parent || !$parent->coordinate) continue;

                $cc = array_map('trim', explode(',', $dp->coordinate));
                if (count($cc) < 2) continue;
                $clat = (float)$cc[0]; $clng = (float)$cc[1];
                if ($clat === 0.0 || $clng === 0.0) continue;
                if ($clat < -90 || $clat > 90 || $clng < -180 || $clng > 180) continue;

                $pc = array_map('trim', explode(',', $parent->coordinate));
                if (count($pc) < 2) continue;
                $plat = (float)$pc[0]; $plng = (float)$pc[1];
                if ($plat === 0.0 || $plng === 0.0) continue;
                if ($plat < -90 || $plat > 90 || $plng < -180 || $plng > 180) continue;

                $odpLinks[] = [
                    'child_id'    => $dp->id,
                    'child_lat'   => $clat, 'child_lng'   => $clng, 'child_name'  => $dp->name,
                    'parent_id'   => $parent->id,
                    'parent_lat'  => $plat, 'parent_lng'  => $plng, 'parent_name' => $parent->name,
                ];
            }
            } catch (\Exception $e) {
                \Log::warning('[PppoeMap] ODP link build: ' . $e->getMessage());
            }
            return $odpLinks;
        });

        return response()->json([
            'count'       => count($markers),
            'markers'     => $markers,
            'odp_links'   => $odpLinks,
            'odp_info'    => $odpInfo,
            'synced_at'   => $syncedAt,
            'data_source' => $useDbData ? 'db' : 'live',
        ]);
    }

    public function pppoeMapOdpInfo(Request $request)
    {
        $odpId = (int) $request->input('id');
        if ($odpId <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid ODP id'], 422);
        }

        $dp = \App\Distpoint::select(['id', 'name', 'description', 'ip'])->find($odpId);
        if (!$dp) {
            return response()->json(['success' => false, 'message' => 'ODP not found'], 404);
        }

        $totalCount = (int) DB::table('customers')
            ->whereNull('deleted_at')
            ->where('id_distpoint', $odpId)
            ->count();

        $capacity = is_numeric($dp->ip) ? (int) $dp->ip : null;

        return response()->json([
            'success' => true,
            'info' => [
                'id' => $dp->id,
                'name' => $dp->name,
                'description' => $dp->description,
                'customer_count' => $totalCount,
                'total_customer_count' => $totalCount,
                'capacity' => $capacity,
            ],
        ]);
    }

    public function pppoeMapSecretStatus(Request $request)
    {
        $pppoe = trim((string) $request->input('pppoe'));
        $routerId = (int) $request->input('router_id');

        if ($pppoe === '') {
            return response()->json(['success' => false, 'message' => 'PPPoE is required'], 422);
        }

        $router = null;
        if ($routerId > 0) {
            $router = \App\Distrouter::find($routerId);
        }

        if (!$router) {
            $customer = \App\Customer::where('pppoe', $pppoe)->first(['id_distrouter']);
            if ($customer && $customer->id_distrouter) {
                $router = \App\Distrouter::find($customer->id_distrouter);
            }
        }

        if (!$router || empty($router->ip)) {
            return response()->json([
                'success'       => true,
                'secret_status' => 'unknown',
                'router'        => $router ? $router->name : null,
                'source'        => 'none',
                'message'       => 'Router not found',
            ]);
        }

        try {
            $client = new Client([
                'host'    => $router->ip,
                'user'    => $router->user,
                'pass'    => $router->password,
                'port'    => (int) $router->port,
                'timeout' => (int) tenant_env('PPPOE_MAP_ROUTER_TIMEOUT', 2),
            ]);

            $secretQuery = (new Query('/ppp/secret/print'))->where('name', $pppoe);
            $secrets = $client->query($secretQuery)->read();
            $secret = !empty($secrets) ? $secrets[0] : null;

            if (!$secret) {
                return response()->json([
                    'success'       => true,
                    'secret_status' => 'not-found',
                    'router'        => $router->name,
                    'source'        => 'router-live',
                ]);
            }

            $isDisabled = isset($secret['disabled']) && $secret['disabled'] === 'true';
            if ($isDisabled) {
                return response()->json([
                    'success'       => true,
                    'secret_status' => 'disabled',
                    'router'        => $router->name,
                    'source'        => 'router-live',
                ]);
            }

            $activeQuery = (new Query('/ppp/active/print'))->where('name', $pppoe);
            $active = $client->query($activeQuery)->read();
            $isOnline = !empty($active);

            return response()->json([
                'success'       => true,
                'secret_status' => $isOnline ? 'online' : 'enable',
                'router'        => $router->name,
                'source'        => 'router-live',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success'       => true,
                'secret_status' => 'unknown',
                'router'        => $router->name,
                'source'        => 'error',
                'message'       => $e->getMessage(),
            ]);
        }
    }

    public function index()
    {
        //
        $distrouter = \App\Distrouter::orderby('id','DESC')
        ->get();


        return view ('distrouter/index',['distrouter' =>$distrouter]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //

        return view ('distrouter/create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {




    // Validate the request data
        $validatedData = $request->validate([
        'name' => ['required', 'string', 'max:255', 'unique:distrouters,name'], // Corrected the 'unique' rule to target the 'olts' table and 'name' column
        'ip' => 'required|ip', // Added IP validation for the 'ip' field
        'port' => 'required|integer|min:1|max:65535', // Added integer validation and port range
        'web' => 'required|integer|min:1|max:65535', // Added integer validation and port range
        'user' => 'required|string|max:255', // Added string validation and max length for 'user'
        'password' => 'required|string|max:255', // Added string validation and max length for 'password'
        'note' => 'required|string|max:255', // Added string validation and max length for 'password'
        
    ]);

        try {
        // Create a new Olt record
            \App\Distrouter::create([
                'name' => $validatedData['name'],
                'ip' => $validatedData['ip'],
                'port' => $validatedData['port'],
                'web' => $validatedData['web'],
                'user' => $validatedData['user'],
                'password' => $validatedData['password'],
                'note' => $validatedData['note'],
                
            'created_at' => now(), // Use current timestamp for created_at
        ]);

            return redirect('/distrouter')->with('success', 'Item created successfully!');
        } catch (\Exception $e) {
        // Handle any exceptions that occur during the creation process
            return redirect()->back()->withErrors(['error' => 'An error occurred while creating the item: ' . $e->getMessage()]);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function backupsconfig($id)
    {
        try {
            $nextDate = Carbon::tomorrow()->format('M/d/Y');

            // Connect to the MikroTik Router
            $distrouter = \App\Distrouter::findOrFail($id);
            $client = new Client([

            //to login to api
                'host' => $distrouter->ip,
                'user' => $distrouter->user,
                'pass' => $distrouter->password,
                'port' => $distrouter->port,
            //data


            ]);

            // Query to get Ethernet interfaces and their traffic statistics
            $queryscript =  (new Query('/system/script/add'))
            ->equal('name', 'BackupConfig')
            ->equal('source',
                ':local sysname [/system identity get name]; :local textfilename; :local backupfilename; :local time [/system clock get time]; :local date [/system clock get date]; :local newdate ""; :for i from=0 to=([:len $date]-1) do={ :local tmp [:pick $date $i]; :if ($tmp !="/") do={ :set newdate "$newdate$tmp" }; :if ($tmp ="/") do={} }; :if ([:find $sysname " "] !=0) do={ :local name $sysname; :local newname ""; :for i from=0 to=([:len $name]-1) do={ :local tmp [:pick $name $i]; :if ($tmp !=" ") do={ :set newname "$newname$tmp" }; :if ($tmp =" ") do={ :set newname "$newname_" } }; :set sysname $newname; }; :set textfilename ($"newdate" . "-" . $"sysname" . ".rsc"); :set backupfilename ($"newdate" . "-" . $"sysname" . ".backup"); :execute [/export file=$"textfilename"]; :execute [/system backup save name=$"backupfilename"]; :delay 2s; tool fetch url="ftp://'.tenant_config('domain_name', env("DOMAIN_NAME")).'/$textfilename" src-path=$textfilename user='.tenant_config('ftp_user', env("FTP_USER")).' password='.tenant_config('ftp_password', env("FTP_PASSWORD")).' port=21 upload=yes; tool fetch url="ftp://'.tenant_config('domain_name', env("DOMAIN_NAME")).'/$backupfilename" src-path=$backupfilename user='.tenant_config('ftp_user', env("FTP_USER")).' password='.tenant_config('ftp_password', env("FTP_PASSWORD")).' port=21 upload=yes; :delay 5s; /file remove $textfilename; /file remove $backupfilename;');


            // Send query to RouterOS
            $backupscript = $client->query($queryscript)->read();

            $queryscheduler =
            (new Query('/system/scheduler/add'))
            ->equal('name', 'BackupConfig_billing')
            ->equal('on-event', 'BackupConfig')
            ->equal('interval', '3d 00:00:00')
            ->equal('start-time', 'startup');

            $response = $client->query($queryscheduler)->read();
            $responseString = json_encode($response); 

            // Return the response as JSON
            //return response()->json(['success' => true, 'backupscript' => $response]);
            return redirect ('/distrouter/' . $id)->with('success', $responseString);
        } catch (\Exception $e) {
           return redirect ('/distrouter/' . $id)->with('error', $responseString);
       }
   }




   public function getrouterinterfaces($id)
   {
    try {
            // Connect to the MikroTik Router
       $distrouter = \App\Distrouter::findOrFail($id);
       $client = new Client([

            //to login to api
        'host' => $distrouter->ip,
        'user' => $distrouter->user,
        'pass' => $distrouter->password,
        'port' => $distrouter->port,
            //data


    ]);

            // Query to get Ethernet interfaces and their traffic statistics
       $query = new Query('/interface/ethernet/print');

            // Send query to RouterOS
       $routerInterfaces = $client->query($query)->read();

            // Return the response as JSON
       return response()->json(['success' => true, 'routerInterfaces' => $routerInterfaces]);
   } catch (\Exception $e) {
    return response()->json(['error' => $e->getMessage()], 500);
}
}


// public function getrouterinfo($id)
// {


//     $result = 'unknow';


//     try {


//         $distrouter = \App\Distrouter::findOrFail($id);
//         $client = new Client([

//             //to login to api
//             'host' => $distrouter->ip,
//             'user' => $distrouter->user,
//             'pass' => $distrouter->password,
//             'port' => $distrouter->port,
//             //data


//         ]);
//            // dd($distrouter);

// // Create a query to get system status
//         $query = new Query('/system/resource/print');

// // Execute the query
//         $routerInfo = $client->query($query)->read();

//         $pppActiveQuery = new Query('/ppp/active/print');
//         $pppActiveQuery->equal('count-only', '');
//         $pppActive = $client->query($pppActiveQuery)->read();
//         $pppActiveCount = $pppActive['after']['ret'];

//         $pppUserQuery = new Query('/ppp/secret/print');
//         $pppUserQuery->equal('count-only', '');
//         $pppUser = $client->query($pppUserQuery)->read();
//         $pppUserCount = $pppUser['after']['ret'];


// // Display the response

//         return response()->json(['success' => true, 'routerInfo' => $routerInfo, 'pppActiveCount' => $pppActiveCount, 'pppUserCount' => $pppUserCount]);


//     }


//     catch (Exception $ex) {
//         $result = 'Unknow';
//     }




// }

public function executeCommand(Request $request)
{
    $command = $request->input('command');
    $id = $request->input('id');

    // Pastikan perintah dan ID tidak kosong
    if (!$command || !$id) {
        return response()->json(['error' => 'Command or ID not specified'], 400);
    }

    try {
        // Cari Distrouter berdasarkan ID
        $distrouter = \App\Distrouter::findOrFail($id);

        // Membuat koneksi ke MikroTik menggunakan RouterosAPI
        $client = new Client([
            'host' => $distrouter->ip,
            'user' => $distrouter->user,
            'pass' => $distrouter->password,
            'port' => $distrouter->port,
            'timeout' => 5,  // Waktu timeout
        ]);
        //$command='/ip/address/print';
        //return response()->json(['error' => $command], 400);
        // Menjalankan perintah dengan Query
        $query = new Query($command);
        $output = $client->query($query)->read();  // Membaca hasil perintah

        
        // Mengembalikan hasil perintah sebagai JSON
        return response()->json([
            'output' => $output // Kirim output langsung dalam bentuk array
        ]);
    } catch (\Exception $e) {
        // Tangani kesalahan dan tampilkan pesan kesalahan
        return response()->json(['error' => 'Error executing command: ' . $e->getMessage()], 500);
    }
}







public function getPppoeUsers($id, $status)
{
    try {
        $distrouter = \App\Distrouter::findOrFail($id);
        $customers = \App\Customer::where('id_distrouter', $id)->get();
        \Log::info($customers);
        $client = new Client([
            'host' => $distrouter->ip,
            'user' => $distrouter->user,
            'pass' => $distrouter->password,
            'port' => $distrouter->port,
            'timeout' => 5,
        ]);

        // Ambil daftar semua pengguna PPPOE
        $pppUserQuery = new Query('/ppp/secret/print');
        $pppActiveQuery = new Query('/ppp/active/print');
        $pppUsers = $client->query($pppUserQuery)->read();
        $pppActive = $client->query($pppActiveQuery)->read();
        $onlineUser = collect($pppActive)->pluck('name')->toArray();
        $color = "badge-info";
        $onlineUser = [];
        foreach ($pppActive as $active) {
            $onlineUser[$active['name']] = [
                'address' => $active['address'] ?? 'Unknown',
                'uptime' => $active['uptime'] ?? 'Unknown',
            ];
        }
        // Pisahkan pengguna berdasarkan status
        $online = [];
        $offline = [];
        $disabled = [];


        foreach ($pppUsers as $user) {
            $customer = $customers->firstWhere('pppoe', $user['name']);

            if (!empty($customer)) {
                if ($customer->id_status == 1) {
                    $color = "badge-warning";
                } elseif ($customer->id_status == 2) {
                    $color = "badge-success";
                } elseif ($customer->id_status == 3) {
                    $color = "badge-secondary";
                } elseif ($customer->id_status == 4) {
            $color = "badge-danger"; // Jika 'badge-dagger' salah, ganti ke 'badge-danger'
        } elseif ($customer->id_status == 5) {
            $color = "badge-primary";
        }

        $customerLink = '<a href="/customer/'.$customer->id.'" class="badge '.$color.'">'.$user['name'].'</a>';
    } else {
        // User belum terdaftar di database - tambahkan data untuk registrasi
        $customerLink = '<span class="text-muted">'.$user['name'].'</span> <button class="btn btn-xs btn-success register-pppoe" data-pppoe="'.$user['name'].'" data-profile="'.($user['profile'] ?? '').'" data-comment="'.($user['comment'] ?? '').'" data-password="'.($user['password'] ?? '').'" data-router-id="'.$id.'" title="Register as Customer"><i class="fas fa-user-plus"></i></button>';
    }
    $userInfo = [


        'name' => $customerLink,
        'description' => $user['comment'] ?? 'No Description',
        'profile' => $user['profile'] ?? 'Unknown',
                // 'local_address' => $user['local-address'] ?? 'Unknown',
                // 'remote_address' => $user['remote-address'] ?? 'Unknown',
        'last_logout' => $user['last-logged-out'] ?? 'N/A',
        'last_disconnect_reason' => $user['last-disconnect-reason'] ?? 'N/A',
        'status' => ''
    ];

    if (isset($user['disabled']) && $user['disabled'] == 'true') {
        $userInfo['status'] = 'Disabled';
        $userInfo['address'] = '';
        $userInfo['uptime'] = '';
        $disabled[] = $userInfo;
    } elseif (array_key_exists($user['name'], $onlineUser)) {
        $userInfo['status'] = 'Online';
        $userInfo['address'] = $onlineUser[$user['name']]['address'];
        $userInfo['uptime'] = $onlineUser[$user['name']]['uptime'];
        $online[] = $userInfo;
    } else {
        $userInfo['status'] = 'Offline';
        $userInfo['address'] = '';
        $userInfo['uptime'] = '';
        $offline[] = $userInfo;
    }
}

        // Filter berdasarkan status
$filteredUsers = match ($status) {
    'online' => $online,
    'offline' => $offline,
    'disabled' => $disabled,
            default => array_merge($online, $offline, $disabled), // Jika status tidak valid, kirim semua data
        };

        return response()->json([
            'success' => true,
            'data' => $filteredUsers,
        ]);
    } catch (\Exception $ex) {
        \Log::error("MikroTik API Error: " . $ex->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching data from RouterOS',
            'error' => $ex->getMessage()
        ], 500);
    }
}










public function getRouterInfo($id)
{
    $routerInfo = [];
    $online = [];
    $offline = [];
    $disabled = [];
    $pppActiveCount = 0;
    $pppUserCount = 0;
    $pppOfflineCount = 0;
    $pppDisabledCount = 0;

    try {
        $distrouter = \App\Distrouter::findOrFail($id);

        $client = new Client([
            'host' => $distrouter->ip,
            'user' => $distrouter->user,
            'pass' => $distrouter->password,
            'port' => $distrouter->port,
            'timeout' => 5, // Timeout agar tidak menggantung
        ]);

        // Ambil informasi sistem
        try {
            $query = new Query('/system/resource/print');
            $routerInfo = $client->query($query)->read();
        } catch (\Exception $e) {
            \Log::warning("Gagal ambil informasi router: " . $e->getMessage());
            $routerInfo = [['error' => 'Router info not available']];
        }

        // Ambil daftar pengguna aktif (online)
        try {
            $pppActiveQuery = new Query('/ppp/active/print');
            $pppActive = $client->query($pppActiveQuery)->read();
            $onlineUsers = collect($pppActive)->pluck('name')->toArray();
            $pppActiveCount = count($pppActive);
        } catch (\Exception $e) {
            \Log::warning("Gagal ambil ppp active: " . $e->getMessage());
            $onlineUsers = [];
            $pppActiveCount = 0;
        }

        // Ambil semua user PPPoE
        try {
            $pppUserQuery = new Query('/ppp/secret/print');
            $pppUsers = $client->query($pppUserQuery)->read();
            $pppUserCount = count($pppUsers);

            foreach ($pppUsers as $user) {
                $userInfo = $user['name'] . ' - ' . ($user['comment'] ?? 'No Description');

                if (isset($user['disabled']) && $user['disabled'] == 'true') {
                    $disabled[] = $userInfo;
                } elseif (in_array($user['name'], $onlineUsers)) {
                    $online[] = $userInfo;
                } else {
                    $offline[] = $userInfo;
                }
            }

            $pppOfflineCount = count($offline);
            $pppDisabledCount = count($disabled);
        } catch (\Exception $e) {
            \Log::warning("Gagal ambil daftar user PPPoE: " . $e->getMessage());
        }

        // Kembalikan semua data meskipun sebagian error
        return response()->json([
            'success' => true,
            'routerInfo' => $routerInfo,
            'pppActiveCount' => $pppActiveCount,
            'pppUserCount' => $pppUserCount,
            'onlineUsers' => $online,
            'offlineUsers' => $offline,
            'disabledUsers' => $disabled,
            'pppOfflineCount' => $pppOfflineCount,
            'pppDisabledCount' => $pppDisabledCount,
        ]);

    } catch (\Exception $ex) {
        // Hanya jika gagal total, misal router tidak bisa dikoneksikan sama sekali
        \Log::error("MikroTik API Error: " . $ex->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Router tidak bisa diakses',
            'error' => $ex->getMessage()
        ], 500);
    }
}


public function show($id)
{
   // Temukan Olt berdasarkan ID
    $distrouter = \App\Distrouter::findOrFail($id);
    $count_user = \App\Customer::where('id_distrouter', $distrouter->id)
    ->where('id_status', '!=', 0)
    ->count();


        // Tampilkan halaman dengan informasi dasar distrouter, AJAX akan mengambil detail lainnya
    return view('distrouter.show', ['distrouter' => $distrouter, 'count_user' => $count_user]);

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
       // dd("tst");
        return view ('distrouter.edit',['distrouter' => \App\Distrouter::findOrFail($id)]);
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


         //
      //  dd($request);

        $validatedData = $request->validate([
            'ip'       => 'required|ip',
            'port'     => 'required|integer|min:1|max:65535',
            'web'      => 'nullable|integer|min:1|max:65535',
            'user'     => 'required|string|max:255',
            'password' => 'nullable|string|max:255',   // kosong = tidak diubah
            'note'     => 'nullable|string|max:1000',
        ]);

        $updateData = [
            'ip'         => $validatedData['ip'],
            'port'       => $validatedData['port'],
            'web'        => $validatedData['web'] ?? null,
            'user'       => $validatedData['user'],
            'note'       => $validatedData['note'] ?? null,
            'updated_at' => now(),
        ];

        // Hanya update password jika diisi
        if (!empty($validatedData['password'])) {
            $updateData['password'] = $validatedData['password'];
        }

        \App\Distrouter::where('id', $id)->update($updateData);
        return redirect ('/distrouter')->with('success','Item updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        \App\Distrouter::destroy($id);
        return redirect ('/distrouter')->with('success','Item deleted successfully!');
    }

    // public function client_monitor($ip,$user,$pass,$port,$cid)
    public function client_monitor(Request $request)
    {
        $tx = 0;
        $rx = 0;
        $rawInterface = trim((string) $request->interface);
        $normalizedInterface = trim($rawInterface, "<> \t\n\r\0\x0B");
        $interfaceCandidates = array_values(array_unique(array_filter([
            $rawInterface,
            $normalizedInterface,
        ])));

        try {
            $client = new Client([
                'host' => $request->ip,
                'user' => $request->user,
                'pass' => $request->password,
                'port' => $request->filled('port') ? intval($request->port) : 8728,
            ]);

            foreach ($interfaceCandidates as $interfaceCandidate) {
                $query = (new Query('/interface/monitor-traffic'))
                    ->equal('interface', $interfaceCandidate)
                    ->equal('once');

                $traffic = $client->query($query)->read();

                if (!empty($traffic) && isset($traffic[0]) && (isset($traffic[0]['tx-bits-per-second']) || isset($traffic[0]['rx-bits-per-second']))) {
                    $tx = (int) ($traffic[0]['tx-bits-per-second'] ?? 0);
                    $rx = (int) ($traffic[0]['rx-bits-per-second'] ?? 0);
                    break;
                }
            }
        } catch (\RouterOS\Exceptions\ConnectException $ex) {
            Log::channel('jobsprocess')->warning('client_monitor timeout: ' . $ex->getMessage());
        } catch (\Exception $ex) {
            Log::channel('jobsprocess')->warning('client_monitor error: ' . $ex->getMessage());
        }

        return response()->json([
            ['name' => 'Tx', 'data' => [$tx]],
            ['name' => 'Rx', 'data' => [$rx]],
        ]);
    }

    public function getMikrotikLogs($id)
    {
        try {
            $distrouter = \App\Distrouter::findOrFail($id);

            $client = new Client([
                'host' => $distrouter->ip,
                'user' => $distrouter->user,
                'pass' => $distrouter->password,
                'port' => $distrouter->port,
                'timeout' => 5,
            ]);

        // Ambil log dari MikroTik
            $logQuery = new Query('/log/print');


            $logs = $client->query($logQuery)->read();

            return response()->json([
                'success' => true,
                'logs' => $logs,
            ]);
        } catch (\Exception $ex) {
            \Log::error("MikroTik API Error: " . $ex->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching logs from RouterOS',
                'error' => $ex->getMessage()
            ], 500);
        }
    }


    public function interfacemonitor($id, Request $request)
    {
        $interface = $request->get('interface');

        try {
            $distrouter = \App\Distrouter::findOrFail($id);

            $client = new Client([
                'host' => $distrouter->ip,
                'user' => $distrouter->user,
                'pass' => $distrouter->password,
                'port' => $distrouter->port,
            ]);

        // Coba koneksi dulu, supaya kalau gagal langsung tertangkap
            $client->connect();

            $query = (new Query('/interface/monitor-traffic'))
            ->equal('interface', $interface)
            ->equal('.proplist', 'rx-bits-per-second,tx-bits-per-second')
            ->equal('once', '');

            $response = $client->query($query)->read();

            if (isset($response[0])) {
                $ftx = $response[0]['tx-bits-per-second'] ?? 0;
                $frx = $response[0]['rx-bits-per-second'] ?? 0;

                return response()->json([
                    ['name' => 'Tx', 'data' => [$ftx]],
                    ['name' => 'Rx', 'data' => [$frx]]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No data received from RouterOS',
            ]);

        } catch (\Exception $e) {
        // Tangani error agar tidak membuat crash
            \Log::channel('mikrotik')->error('Gagal ambil traffic: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengambil data Mikrotik',
                'tx' => 0,
                'rx' => 0
            ]);
        }
    }

    /**
     * Import PPPoE profiles from Mikrotik to plans table
     */
    public function importPppProfiles($id)
    {
        try {
            $distrouter = \App\Distrouter::findOrFail($id);
            
            // Connect to Mikrotik using RouterOS Client
            $client = new Client([
                'host' => $distrouter->ip,
                'user' => $distrouter->user,
                'pass' => $distrouter->password,
                'port' => $distrouter->port,
                'timeout' => 10,
            ]);
            
            // Get PPP profiles from Mikrotik
            $query = new Query('/ppp/profile/print');
            $profiles = $client->query($query)->read();
            
            $imported = 0;
            $skipped = 0;
            
            foreach ($profiles as $profile) {
                $profileName = $profile['name'] ?? '';
                
                // Skip empty names or default profiles
                if (empty($profileName) || in_array($profileName, ['default', 'default-encryption'])) {
                    continue;
                }
                
                // Check if profile already exists in plans table
                $existingPlan = \App\Plan::where('name', $profileName)->first();
                
                if ($existingPlan) {
                    $skipped++;
                    continue;
                }
                
                // Create new plan with profile data
                \App\Plan::create([
                    'name' => $profileName,
                    'speed' => $profile['rate-limit'] ?? '',
                    'price' => 0,
                    'description' => 'Imported from Mikrotik ' . $distrouter->name
                ]);
                
                $imported++;
            }
            
            $message = "Import completed: {$imported} profile(s) imported, {$skipped} skipped (already exists)";
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            \Log::error("Import PPP Profiles Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Prepare PPPoE data for customer registration
     */
    public function preparePppoeForRegistration(Request $request)
    {
        try {
            $validated = $request->validate([
                'pppoe' => 'required|string',
                'profile' => 'nullable|string',
                'comment' => 'nullable|string',
                'password' => 'nullable|string',
                'router_id' => 'required|exists:distrouters,id'
            ]);
            
            // Get router info
            $router = \App\Distrouter::findOrFail($validated['router_id']);
            
            // Get plan by profile name if exists
            $plan = \App\Plan::where('name', $validated['profile'])->first();
            
            // Determine customer name: use comment if exists, otherwise use pppoe username
            $customerName = !empty($validated['comment']) ? $validated['comment'] : $validated['pppoe'];
            
            // Prepare data for customer form
            $data = [
                'pppoe' => $validated['pppoe'],
                'name' => $customerName,
                'password' => $validated['password'] ?? '',
                'id_plan' => $plan ? $plan->id : null,
                'id_distrouter' => $validated['router_id'],
                'source' => 'pppoe_import',
                'profile_name' => $validated['profile'] ?? null,
            ];
            
            \Log::info("Preparing PPPoE data for registration", [
                'pppoe' => $validated['pppoe'],
                'name' => $customerName,
                'plan_id' => $plan ? $plan->id : null
            ]);
            
            // Build query string for redirect
            $queryString = http_build_query($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Data prepared successfully',
                'redirect_url' => url('/customer/create?' . $queryString)
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Prepare PPPoE Registration Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare data: ' . $e->getMessage()
            ], 500);
        }
    }




}
