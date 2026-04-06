<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \RouterOS\Client;
use \RouterOS\Query;
use Carbon\Carbon;
class DistrouterController extends Controller
{
 public function __construct()
 {
    $this->middleware('auth');
    $this->middleware('checkPrivilege:admin,noc,user');
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
        return view('distrouter.pppoe-map', compact('routers'));
    }

    public function pppoeMapData(Request $request)
    {
        $routerId = $request->input('router_id'); // optional filter

        $routers = $routerId
            ? \App\Distrouter::where('id', $routerId)->get()
            : \App\Distrouter::all();

        $markers = [];

        foreach ($routers as $router) {
            try {
                $client = new Client([
                    'host'    => $router->ip,
                    'user'    => $router->user,
                    'pass'    => $router->password,
                    'port'    => (int) $router->port,
                    'timeout' => 5,
                ]);

                // Get active sessions first
                $onlineNames = [];
                try {
                    $q = new Query('/ppp/active/print');
                    $active = $client->query($q)->read();
                    $onlineNames = collect($active)->pluck('name')->toArray();
                } catch (\Exception $e) {}

                // Get all secrets, find offline ones + capture last-logged-out
                $offlineNames = [];
                $lastLoggedOut = []; // name => timestamp string
                try {
                    $q = new Query('/ppp/secret/print');
                    $secrets = $client->query($q)->read();
                    $onlineIndex = array_flip($onlineNames); // O(1) lookup
                    foreach ($secrets as $s) {
                        $isDisabled = isset($s['disabled']) && $s['disabled'] === 'true';
                        if (!$isDisabled && !isset($onlineIndex[$s['name']])) {
                            $offlineNames[] = $s['name'];
                        }
                        // capture last-logged-out for all secrets
                        if (!empty($s['last-logged-out']) && $s['last-logged-out'] !== 'never') {
                            $lastLoggedOut[$s['name']] = $s['last-logged-out'];
                        }
                    }
                } catch (\Exception $e) {}

                if (empty($offlineNames)) continue;

                // Match against customers who have coordinates
                $customers = \App\Customer::with('distpoint_name')
                    ->whereIn('pppoe', $offlineNames)
                    ->whereNotNull('coordinate')
                    ->where('coordinate', '!=', '')
                    ->get(['id', 'name', 'pppoe', 'coordinate', 'phone', 'address', 'customer_id', 'id_distpoint']);

                foreach ($customers as $c) {
                    $coords = array_map('trim', explode(',', $c->coordinate));
                    if (count($coords) < 2) continue;
                    $lat = (float) $coords[0];
                    $lng = (float) $coords[1];
                    if ($lat === 0.0 && $lng === 0.0) continue;

                    // Distpoint (ODP) coordinate
                    $odpLat = null; $odpLng = null; $odpName = null;
                    if ($c->distpoint_name && $c->distpoint_name->coordinate) {
                        $dc = array_map('trim', explode(',', $c->distpoint_name->coordinate));
                        if (count($dc) >= 2) {
                            $dl = (float)$dc[0]; $dn = (float)$dc[1];
                            if (!($dl === 0.0 && $dn === 0.0)) {
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
                        'router'       => $router->name,
                        'last_offline' => $lastLoggedOut[$c->pppoe] ?? null,
                        'odp_id'       => $c->distpoint_name ? $c->distpoint_name->id : null,
                        'odp_lat'      => $odpLat,
                        'odp_lng'      => $odpLng,
                        'odp_name'     => $odpName,
                    ];
                }

            } catch (\Exception $e) {
                \Log::warning("[PppoeMap] Router {$router->name}: " . $e->getMessage());
            }
        }

        // --- Build ODP info (id + total customer count) ---
        $odpInfo = [];
        try {
            $allDistpoints = \App\Distpoint::withCount('customer')
                ->whereNotNull('coordinate')->where('coordinate', '!=', '')
                ->get(['id', 'name', 'description']);
            foreach ($allDistpoints as $dp) {
                $odpInfo[$dp->id] = [
                    'id'             => $dp->id,
                    'name'           => $dp->name,
                    'description'    => $dp->description,
                    'customer_count' => $dp->customer_count,
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('[PppoeMap] ODP info build: ' . $e->getMessage());
        }

        // --- Build ODP parent-child links ---
        // Collect all unique distpoint IDs seen in markers
        $odpIds = [];
        foreach ($markers as $m) {
            // We need the original id_distpoint; store it temporarily in marker
        }
        // Re-collect from customers query results - gather all distpoints that appeared
        // We'll do a fresh query: all distpoints that have coordinates AND have a parent with coordinates
        $odpLinks = [];
        try {
            // Load all distpoints that have coordinates and a parent
            $distpoints = \App\Distpoint::with('parentDistPoint')
                ->whereNotNull('coordinate')->where('coordinate', '!=', '')
                ->whereNotNull('parrent')->where('parrent', '!=', 0)
                ->get(['id', 'name', 'coordinate', 'parrent']);

            foreach ($distpoints as $dp) {
                $parent = $dp->parentDistPoint;
                if (!$parent || !$parent->coordinate) continue;

                $cc = array_map('trim', explode(',', $dp->coordinate));
                if (count($cc) < 2) continue;
                $clat = (float)$cc[0]; $clng = (float)$cc[1];
                if ($clat === 0.0 && $clng === 0.0) continue;

                $pc = array_map('trim', explode(',', $parent->coordinate));
                if (count($pc) < 2) continue;
                $plat = (float)$pc[0]; $plng = (float)$pc[1];
                if ($plat === 0.0 && $plng === 0.0) continue;

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

        return response()->json([
            'count'     => count($markers),
            'markers'   => $markers,
            'odp_links' => $odpLinks,
            'odp_info'  => $odpInfo,
        ]);
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
        $result = 'unknow';


        try {

            $client = new Client([


                'host' => $request->ip,
                'user' => $request->user,
                'pass' => $request->password,
                // 'port' => intval($request->port)
                'port' => $request->filled('port') ? intval($request->port) : 8728,
            ]);



            $query =
            (new Query('/interface/monitor-traffic'))
            ->equal('interface',$request->interface)
            ->equal('once');
            $rows = array(); $rows2 = array();

            $getinterfacetraffic= $client->query($query)->read();
            $ftx = $getinterfacetraffic[0]['tx-bits-per-second'];
            $frx = $getinterfacetraffic[0]['rx-bits-per-second'];

            $rows['name'] = 'Tx';
            $rows['data'][] = $ftx;
            $rows2['name'] = 'Rx';
            $rows2['data'][] = $frx;
// Ask for monitoring details
            $result = array();

            array_push($result,$rows);
            array_push($result,$rows2);
            print json_encode($result);



        }


        // catch (Exception $ex) {
        //     $result = 'Unknow';
        // }

        catch (\RouterOS\Exceptions\ConnectException $ex) {
            $result = 'Connection Timeout';
        } catch (\Exception $ex) {
            $result = 'Unknown Error';
        }




        

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
