<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use DataTables;
// use Graze\TelnetClient\TelnetClient;
// use Graze\TelnetClient\TelnetSocket;
// use  Graze\TelnetClient\TelnetClient;
// use  Graze\TelnetClient\TelnetResponse;
// use Graze\TelnetClient\Exception\TelnetException;
// use phpseclib3\Net\SSH2;
// use phpseclib3\Exception\UnableToConnectException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class OltController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkPrivilege:admin,noc');
    }


    private $baseIndex = 268501248;

    // Slot gaps
    private $slotGaps = [
        1 => 0,
        2 => 65536,
        3 => 131072,
        4 => 196608,
        5 => 262144,
        6 => 327680,
        7 => 393216,
        8 => 458752,
        9 => 524288,
        10 => 589824,
        11 => 655360,
        12 => 720896,
        13 => 786432,
        14 => 851968,
        15 => 917504,
        16 => 983040,
        17 => 1048576,
        18 => 1114112,
        19 => 1179648,
        20 => 1245184
    ];

    public function getPonCode($id)
    {
        // Menghitung slot
        $slot = $this->findSlot($id);
        if ($slot === null) {
            return response()->json(['error' => 'Invalid ID'], 400);
        }

        // Menghitung ONU
        $onu = $this->findOnu($id, $slot);


        return '1/'.$slot.'/'.$onu;
        // return response()->json([
        //     'shelf' => 1,
        //     'slot' => $slot,
        //     'onu' => $onu
        // ]);
    }

    private function findSlot($id)
    {
        foreach ($this->slotGaps as $slot => $gap) {
            $nextSlotBase = $this->baseIndex + $gap;
            $nextSlotLimit = $nextSlotBase + 256 * 128; // 128 ONU per slot, dengan gap 256 per ONU
            
            if ($id >= $nextSlotBase && $id < $nextSlotLimit) {
                return $slot;
            }
        }
        return null; // Jika tidak menemukan slot
    }

    private function findOnu($id, $slot)
    {
        $gap = $this->slotGaps[$slot];
        $onuIndex = ($id - ($this->baseIndex + $gap));
        return ($onuIndex / 256) + 1;
    }


    //=================================================//
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       $olt = \App\Olt::orderby('id','DESC')
       ->get();


       return view ('olt/index',['olt' =>$olt]);
   }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function table_olt_list(Request $request){


        $olt = \App\Olt::orderBy('name', 'DESC');
        $olt->get();
        return DataTables::of($olt)
        ->addIndexColumn()
        ->editColumn('name',function($olt)
        {

            return ' <a href="/olt/'.$olt->id.'" title="olt" class="badge badge-primary text-center  "> '.$olt->name. '</a>';
        })
        ->editColumn('vendor',function($olt)
        {
            $vendorBadges = [
                'zte' => 'badge-info',
                'cdata' => 'badge-success',
                'hsgq' => 'badge-warning',
                'huawei' => 'badge-danger',
                'fiberhome' => 'badge-primary',
                'vsol' => 'badge-secondary',
                'other' => 'badge-dark',
            ];
            $badge = $vendorBadges[$olt->vendor ?? 'other'] ?? 'badge-secondary';
            $vendorName = strtoupper($olt->vendor ?? 'N/A');
            return '<span class="badge '.$badge.'">'.$vendorName.'</span>';
        })



        ->rawColumns(['DT_RowIndex','name','vendor','type','ip','port','user','password','community_ro','community_rw','snmp_port'])

        ->make(true);
    }


    public function create()
    {
        //
        return view ('olt/create');
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
        'name' => ['required', 'string', 'max:255', 'unique:olts,name'], // Corrected the 'unique' rule to target the 'olts' table and 'name' column
        'vendor' => 'required|string|max:50', // Added vendor validation
        'type' => 'required|string|max:255', // Added string validation for 'type' and a maximum length
        'ip' => 'required|ip', // Added IP validation for the 'ip' field
        'port' => 'required|integer|min:1|max:65535', // Added integer validation and port range
        'user' => 'required|string|max:255', // Added string validation and max length for 'user'
        'password' => 'required|string|max:255', // Added string validation and max length for 'password'
        'community_ro' => 'required|string|max:255', // Added string validation and max length for 'community_ro'
        'community_rw' => 'required|string|max:255', // Added string validation and max length for 'community_rw'
        'snmp_port' => 'required|integer|min:1|max:65535', // Added integer validation and port range for SNMP port
    ]);

        try {
        // Create a new Olt record
            \App\Olt::create([
                'name' => $validatedData['name'],
                'vendor' => $validatedData['vendor'],
                'type' => $validatedData['type'],
                'ip' => $validatedData['ip'],
                'port' => $validatedData['port'],
                'user' => $validatedData['user'],
                'password' => $validatedData['password'],
                'community_ro' => $validatedData['community_ro'],
                'community_rw' => $validatedData['community_rw'],
                'snmp_port' => $validatedData['snmp_port'],
            'created_at' => now(), // Use current timestamp for created_at
        ]);

            return redirect('/olt')->with('success', 'Item created successfully!');
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
    public function show($id)
    {
        // Temukan Olt berdasarkan ID
        $olt = \App\Olt::findOrFail($id);

        // Tampilkan halaman dengan informasi dasar OLT, AJAX akan mengambil detail lainnya
        return view('olt.show', ['olt' => $olt]);
    }

//     public function getOltInfo($id)
//     {
//         try {
//             // Temukan Olt berdasarkan ID atau lempar error 404 jika tidak ditemukan
//             $olt = \App\Olt::findOrFail($id);

//             // Ambil SNMP OID dari konfigurasi
//             $zteoid = config('zteoid');
//             $ontStatuses = config('zteontstatus');
//             // Inisialisasi koneksi SNMP
//             $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip, $olt->community_ro);

//             // OID untuk mendapatkan informasi

//             $logging = 0;
//             $los = 0;
//             $loslist = [];
//             $synMib = 0;
//             $working = 0;
//             $dyinggasp = 0;
//             $dyinggasplist = [];
//             $authFailed = 0;
//             $offline = 0;
//             $offlinelist = [];
//             $unknowlist = [];
//             $onuNameValue =0;
//             $onuUncfgValue=0;
//             $unknow=0;



//             $oidOltName = $zteoid['oidOltName'];
//             $oidOltUptime = $zteoid['oidOltUptime'];
//             $oidOltVersion = $zteoid['oidOltVersion'];
//             $oidOltDesc = $zteoid['oidOltDesc'];
//             $onuUncfgSn = $zteoid['oidOnuUncfgSn'];
//             $onuName = $zteoid['oidOnuName'];
//             $onuStatus = $zteoid['oidOnuStatus'];
//             try
//             {
//                $onuUncfgValue = count($snmp->walk($onuUncfgSn));

//            } catch (\Exception $e) {

//             $onuUncfgValue = 0;

//         }



//         $onuNameValue = array_map(function ($val) {
//             return str_replace(['STRING: ', '"'], "", $val);
//         }, $snmp->walk($onuName));
//         $onuCount=count($onuNameValue);

//         $frameslotportid = config('zteframeslotportid');

//         $processedResults = [];

//         foreach ($onuNameValue as $key => $onuName) {

//     // Mengambil status ONT
//             $components = explode('.', $key);
//             $lastTwoComponents = array_slice($components, -2);
//             $result = implode('.', $lastTwoComponents);
//             $oid = $onuStatus.'.'.$result;
//             $statusValue = $snmp->get($oid);
//             $pon_int = array_search($lastTwoComponents[0], $frameslotportid);
//             $pon_int = $pon_int !== false ? $pon_int : 'unknown';
//             $onuid = $pon_int.':'.$lastTwoComponents[1];
//     // Jika status tidak dapat diambil, lewati ONT ini
//             if ($statusValue === false) 
//             {
//                 continue;
//             }

//     // Mengambil status ONT dari array $ontStatuses atau 'Unknown' jika tidak ditemukan
//             $result_status = $ontStatuses[$statusValue] ?? 'Unknown';

//     // Jika tidak ada data status, tampilkan pesan "No data"
//             if (empty($result_status)) {
//                 echo "No data";
//             } else {
//         // Memeriksa status ONT dan melakukan increment sesuai dengan status
//                 switch ($result_status) {
//                     case "working":
//                     $working++;
//                     break;
//                     case "los":
//                     $los++;
//                     $loslist[] = [
//                         'onuName' => $onuName,
//                         'Id' => str_replace('\\', '',$onuid),
//                     ];
//                     break;
//                     case "dyinggasp":
//                     $dyinggasp++;
//                     $dyinggasplist[] = [
//                         'onuName' => $onuName,
//                         'Id' => str_replace('\\', '',$onuid),
//                     ];
//                     break;
//                     case "logging":
//                     $logging++;
//                     break;

//                     case "offline":
//         // Handle other cases or do nothing
//                     $offline++;
//                     $offlinelist[] = [
//                         'onuName' => $onuName,
//                         'Id' => str_replace('\\', '',$onuid),
//                     ];
//                     break;
//                     default:
//         // Handle other cases or do nothing
//                     $unknow++;
//                     $unknowlist[] = [
//                         'onuName' => $onuName,
//                         'Id' => str_replace('\\', '',$onuid),
//                     ];
//                     break;
//                 }
//             }
//         }




















//             // Mengambil informasi OLT melalui SNMP
//         $oltInfo = [
//             'oltName' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltName)),
//             'oltUptime' => str_replace(['Timeticks: ', '"'], "", $snmp->get($oidOltUptime)),
//             'oltVersion' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltVersion)),
//             'oltDesc' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltDesc)),
//             'onuUnConfg' => $onuUncfgValue,
//             'onuCount' => $onuCount,
//             'logging' => $logging,
//             'los' => $los,
//             'synMib' => $synMib,
//             'working' => $working,
//             'dyinggasp' => $dyinggasp,
//             'authFailed' =>$authFailed,
//             'offline' =>  $offline,


//         ];

//             // Tutup koneksi SNMP
//         $snmp->close();

//             // Kembalikan data dalam bentuk JSON
//         return response()->json(['success' => true, 'oltInfo' => $oltInfo, 'dyinggasplist' => $dyinggasplist, 'loslist' => $loslist,'offlinelist' => $offlinelist]);

//     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
//         return response()->json(['success' => false, 'error' => 'OLT Not Found.']);
//     } catch (\SNMPException $e) {
//         return response()->json(['success' => false, 'error' => 'Failed to retrieve OLT information ' . $e->getMessage()]);
//     } catch (\Exception $e) {
//         return response()->json(['success' => false, 'error' => 'Failed to retrieve OLT information ' . $e->getMessage()]);
//     }
// }

    public function getOltInfo($id)
    {
        try {
            $olt = \App\Olt::findOrFail($id);
            $zteoid = get_olt_oid_config($olt);
            $ontStatuses = get_olt_status_config($olt);
            $frameslotportid = get_olt_frameslotport_config($olt);

            $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip, $olt->community_ro);

        // Inisialisasi data awal
            $logging = 0;
            $los = 0;
            $loslist = [];
            $powerdown = 0;
            $powerdownlist = [];
            $synMib = 0;
            $working = 0;
            $dyinggasp = 0;
            $dyinggasplist = [];
            $authFailed = 0;
            $offline = 0;
            $offlinelist = [];
            $unknowlist = [];
            $onuUncfgValue = 0;
            $onuCount = 0;
            $unknow = 0;

            $oidOltName = $zteoid['oidOltName'];
            $oidOltUptime = $zteoid['oidOltUptime'];
            $oidOltVersion = $zteoid['oidOltVersion'];
            $oidOltDesc = $zteoid['oidOltDesc'];
            $onuUncfgSn = $zteoid['oidOnuUncfgSn'];
            
            // Detect vendor type
            $oltVendor = strtolower($olt->vendor ?? '');
            $oltType = strtolower($olt->type ?? '');
            $oltName = strtolower($olt->name ?? '');
            
            $isHSGQ = str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq');
            $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
                            str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');
            
            // Select OID based on vendor
            if ($isHSGQ) {
                $onuName = $zteoid['oidIfDescr'];       // IF-MIB for HSGQ
                $onuStatus = $zteoid['oidIfOperStatus']; // IF-MIB status
            } else {
                $onuName = $zteoid['oidOnuName'];
                $onuStatus = $zteoid['oidOnuStatus'];
            }

        // Ambil jumlah ONU belum terkonfigurasi
            try {
                // Skip query if OID is null (disabled for some models like C620)
                if ($onuUncfgSn === null || empty($onuUncfgSn)) {
                    $onuUncfgValue = 0;
                } else {
                    $uncfg = $snmp->walk($onuUncfgSn);
                    $onuUncfgValue = is_array($uncfg) ? count($uncfg) : 0;
                }
            } catch (\Exception $e) {
                $onuUncfgValue = 0;
            }

        // Ambil daftar nama ONU
            try {
                $onuNameRaw = $snmp->walk($onuName);
                $onuNameValue = is_array($onuNameRaw) ? array_map(function ($val) {
                    return str_replace(['STRING: ', '"'], "", $val);
                }, $onuNameRaw) : [];
            } catch (\Exception $e) {
                $onuNameValue = [];
            }

            $onuCount = count($onuNameValue);

        // Proses status masing-masing ONU
            foreach ($onuNameValue as $key => $onuNameEntry) {
                $components = explode('.', $key);
                
                if ($isHSGQ) {
                    // HSGQ: Parse IF-MIB ifDescr "ONU{PON}/{ID}"
                    // Only count actual ONUs, skip non-ONU interfaces
                    if (!preg_match('/ONU(\d+)\/(\d+)/', $onuNameEntry, $matches)) {
                        continue; // Skip non-ONU interfaces
                    }
                    
                    $ponNum = (int)$matches[1];
                    $onuId = (int)$matches[2];
                    
                    // Get ifIndex from OID key
                    $ifIndex = (int)end($components);
                    
                    // Get ifOperStatus (1=up, 2=down)
                    $statusOid = $onuStatus . '.' . $ifIndex;
                    $statusValue = $this->safeSnmpGet($snmp, $statusOid);
                    
                    // Map IF-MIB to HSGQ status codes using regex (handles "INTEGER: 1", "INTEGER: up(1)", etc.)
                    if (preg_match('/\b(1|up)\b/', $statusValue)) {
                        $statusValue = '3'; // working (initially)
                        
                        // Verify with power values to detect offline ONUs and determine cause
                        $encodedIndex = encode_hsgq_index($ponNum, $onuId);
                        $rxPowerOid = $zteoid['oidOnuRxPower'] . '.' . $encodedIndex . '.0.0';
                        $txPowerOid = $zteoid['oidOnuTxPowerOnu'] . '.' . $encodedIndex . '.0.0';
                        
                        $rxPowerValue = $this->safeSnmpGet($snmp, $rxPowerOid);
                        $txPowerValue = $this->safeSnmpGet($snmp, $txPowerOid);
                        
                        // Check if power values are valid
                        $rxValid = $rxPowerValue && !str_contains($rxPowerValue, 'No Such') && !str_contains($rxPowerValue, 'N/A');
                        $txValid = $txPowerValue && !str_contains($txPowerValue, 'No Such') && !str_contains($txPowerValue, 'N/A');
                        
                        // Determine offline cause based on power status
                        if (!$rxValid && !$txValid) {
                            $statusValue = '5'; // powerdown - Both powers lost
                        } elseif (!$rxValid || !$txValid) {
                            $statusValue = '4'; // los - Laser out (one power missing)
                        }
                    } else {
                        $statusValue = '0'; // offline
                    }
                    
                    $pon_int = $ponNum;
                    $onuid = $ponNum . ':' . $onuId;
                } elseif ($isC600Series) {
                    // C600/C620/C650: Branch .500 uses encoded index
                    // Format: .500.10.2.3.3.1.1.{encodedIndex}.{onuId}
                    $lastTwo = array_slice($components, -2);
                    if (count($lastTwo) < 2) continue;
                    
                                                    $encodedIndex = (int)$lastTwo[0];
                                                    $onuId = $lastTwo[1];
                                                    
                                                    // Decode: (17 << 24) + (frame << 16) + (cardIndex << 8) + port
                                                    $port = ($encodedIndex >> 0) & 0xFF;
                                                    $cardIndex = ($encodedIndex >> 8) & 0xFF;
                                                    $frame = ($encodedIndex >> 16) & 0xFF;
                                                    
                                                    $slot = $cardIndex; // FIX: slot = cardIndex
                                                    $shelfSlot = $frame . '.' . $slot;                    // Find port from config
                    $portKey = $frame . '/' . $slot . '/' . $port;
                    $pon_int = $portKey;
                    $onuid = $pon_int . ':' . $onuId;
                    
                    // Build OID for status check (branch .500 uses encoded index)
                    $oid = $onuStatus . '.' . $encodedIndex . '.' . $onuId;
                    $statusValue = $snmp->get($oid);
                } else {
                    // C300/C320: use last 2 components
                    $lastTwo = array_slice($components, -2);
                    $result = implode('.', $lastTwo);
                    $oid = $onuStatus . '.' . $result;
                    $statusValue = $snmp->get($oid);
                    
                    $pon_int = array_search($lastTwo[0], $frameslotportid);
                    $pon_int = $pon_int !== false ? $pon_int : 'unknown';
                    $onuid = $pon_int . ':' . $lastTwo[1];
                }

                if ($statusValue === false) continue;

                $result_status = $ontStatuses[$statusValue] ?? 'Unknown';

                switch ($result_status) {
                    case "working":
                    $working++;
                    break;
                    case "los":
                    $los++;
                    $loslist[] = ['onuName' => $onuNameEntry, 'Id' => str_replace('\\', '', $onuid)];
                    break;
                    case "powerdown":
                    $powerdown++;
                    $powerdownlist[] = ['onuName' => $onuNameEntry, 'Id' => str_replace('\\', '', $onuid)];
                    break;
                    case "dyinggasp":
                    $dyinggasp++;
                    $dyinggasplist[] = ['onuName' => $onuNameEntry, 'Id' => str_replace('\\', '', $onuid)];
                    break;
                    case "logging":
                    $logging++;
                    break;
                    case "offline":
                    $offline++;
                    $offlinelist[] = ['onuName' => $onuNameEntry, 'Id' => str_replace('\\', '', $onuid)];
                    break;
                    default:
                    $unknow++;
                    $unknowlist[] = ['onuName' => $onuNameEntry, 'Id' => str_replace('\\', '', $onuid)];
                    break;
                }
            }

            $oltInfo = [
                'oltName' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltName)),
                'oltUptime' => str_replace(['Timeticks: ', '"'], "", $snmp->get($oidOltUptime)),
                'oltVersion' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltVersion)),
                'oltDesc' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltDesc)),
                'onuUnConfg' => $onuUncfgValue,
                'onuCount' => $onuCount,
                'logging' => $logging,
                'los' => $los,
                'powerdown' => $powerdown,
                'synMib' => $synMib,
                'working' => $working,
                'dyinggasp' => $dyinggasp,
                'authFailed' => $authFailed,
                'offline' => $offline,
                'unknown' => $unknow,
            ];

            $snmp->close();

            return response()->json([
                'success' => true,
                'oltInfo' => $oltInfo,
                'dyinggasplist' => $dyinggasplist,
                'loslist' => $loslist,
                'powerdownlist' => $powerdownlist,
                'offlinelist' => $offlinelist,
                'unknowlist' => $unknowlist,
            ]);

            return response()->json([
                'success' => true,
                'oltInfo' => $oltInfo,
                'dyinggasplist' => $dyinggasplist,
                'loslist' => $loslist,
                'offlinelist' => $offlinelist,
                'unknowlist' => $unknowlist,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'OLT Not Found.']);
        } catch (\SNMPException $e) {
            return response()->json(['success' => false, 'error' => 'SNMP error: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
        }
    }


    public function unconfig()
    {
     // Note: This method needs $olt object to detect vendor
     // Currently hardcoded to 202.169.255.10, should get from database
     $zteoid = config('zteoid'); // Keep default for now
     $onuUncfgValue = [];


     $snmp = new \SNMP(\SNMP::VERSION_2c, '202.169.255.10', 'public_ro');

     $onuUncfgSn = $zteoid['oidOnuUncfgSn'];
     try
     {
        $onuUncfgValue = $snmp->walk($onuUncfgSn);

    } catch (\Exception $e) {

    }

//dd($onuUncfgValue);
}


public function configure(Request $request)
{
   // dd($request);

    $olt = \App\Olt::findOrFail($request->olt);

    $ip = $olt->ip;
    $login = $olt->user;
    $password = $olt->password;
    $port = $olt->port;
    $timeout = 10;
    $parts_int = explode(':', $request->onu_sn);

    $pon_int = 'gpon-olt_'.$parts_int[0];
    $onu_int = 'gpon-onu_'.$parts_int[0];
    $name = $request->customer_id.' '.$request->customer_name;
    $onu_num = $request->onu_id;
    $sn = $parts_int[1];
    $onutype = $request->onu_type;

    $parts_vlan = explode(':', $request->onu_profile);
    $vlanname = $parts_vlan[0];
    $vlan = strval($parts_vlan[1]);

    $username_pppoe = $request->customer_id;
    $password_pppoe = $request->password;
    $description = 'Config by System';

    $tconprofile = $request->tcon_profile;
    $gemportprofileup = $request->gemport_profile;
    $gemportprofiledown = $request->gemport_profile;




    $process = new Process(["python3", env("PHYTON_DIR")."addontconf.py", 
        $ip, $login, $password, $port, $timeout, 
        $pon_int, $onu_int, $onu_num, $sn, $onutype, 
        $vlan, $username_pppoe, $password_pppoe, $description, 
        $vlanname, $tconprofile, $gemportprofileup, $gemportprofiledown, $name]);
    try {
    // Start the process and wait for it to finish
     $result = $process->run();


    // Check if the process was successful
     if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);

    }



    // Get the output from the process
        // $output = $process->getOutput();
        // return response()->json(['output' => $output]);
    \App\Customer::where('customer_id', $request->customer_id)->update([
        'id_onu' => $parts_int[0].':'.$request->onu_id,


    ]);

    $messege =$process->getOutput();
    $parts = explode(":", $messege);
    return redirect ('/customer/'.$request->id_customer.'/edit')->with($parts[0],$parts[1]);

} catch (ProcessFailedException $e) {
    // If the process fails, return an error response
       // return response()->json(['error' => $e->getMessage()]);
   $messege =$e->getMessage();
   $parts = explode(":", $messege);
   return redirect ('/customer/'.$request->id_customer.'/edit')->with($parts[0],$parts[1]);
}
}

public function configurecst(Request $request)
{
   // dd($request);

    $olt = \App\Olt::findOrFail($request->olt);

    $ip = $olt->ip;
    $login = $olt->user;
    $password = $olt->password;
    $port = $olt->port;
    $timeout = 10;
    $parts_int = explode(':', $request->onu_sn);

    $pon_int = 'gpon-olt_'.$parts_int[0];
    $onu_int = 'gpon-onu_'.$parts_int[0];
    $name = $request->customer_id.' '.$request->customer_name;
    $onu_num = $request->onu_id;
    $sn = $parts_int[1];
    $onutype = $request->onu_type;

    $parts_vlan = explode(':', $request->onu_profile);
    $vlanname = $parts_vlan[0];
    $vlan = strval($parts_vlan[1]);

    $username_pppoe = $request->customer_id;
    $password_pppoe = $request->password;
    $description = 'Config by System';

    $tconprofile = $request->tcon_profile;
    $gemportprofileup = $request->gemport_profile;
    $gemportprofiledown = $request->gemport_profile;




    $process = new Process(["python3", env("PHYTON_DIR")."addontcstconf.py", 
        $ip, $login, $password, $port, $timeout, 
        $pon_int, $onu_int, $onu_num, $sn, $onutype, 
        $vlan, $username_pppoe, $password_pppoe, $description, 
        $vlanname, $tconprofile, $gemportprofileup, $gemportprofiledown, $name]);
    try {
    // Start the process and wait for it to finish
     $result = $process->run();


    // Check if the process was successful
     if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);

    }



    // Get the output from the process
        // $output = $process->getOutput();
        // return response()->json(['output' => $output]);
    \App\Customer::where('customer_id', $request->customer_id)->update([
        'id_onu' => $parts_int[0].':'.$request->onu_id,


    ]);

    $messege =$process->getOutput();
    $parts = explode(":", $messege);
    return redirect ('/customer/'.$request->id_customer.'/edit')->with($parts[0],$parts[1]);

} catch (ProcessFailedException $e) {
    // If the process fails, return an error response
       // return response()->json(['error' => $e->getMessage()]);
   $messege =$e->getMessage();
   $parts = explode(":", $messege);
   return redirect ('/customer/'.$request->id_customer.'/edit')->with($parts[0],$parts[1]);
}
}

// public function configurecst(Request $request)
// {
//     //dd($request);

//     $olt = \App\Olt::findOrFail($request->olt);

//     $ip = $olt->ip;
//     $login = $olt->user;
//     $password = $olt->password;
//     $port = $olt->port;
//     $timeout = 10;
//     $parts_int = explode(':', $request->onu_sn);

//     $pon_int = 'gpon-olt_'.$parts_int[0];
//     $onu_int = 'gpon-onu_'.$parts_int[0];
//     $name = $request->onu_name;
//     $onu_num = $request->onu_id;
//     $sn = $parts_int[1];
//     $onutype = $request->onu_type;

//     $parts_vlan = explode(':', $request->onu_profile);
//     //$vlanname = $parts_vlan[0];
//     $vlan = strval($parts_vlan[1]);

//     // $username_pppoe = $request->customer_id;
//     // $password_pppoe = $request->password;
//     $description = 'Config by System';

//     $tconprofile = $request->tcon_profile;
//     $gemportprofileup = $request->gemport_profile;
//     $gemportprofiledown = $request->gemport_profile;


//     $process = new Process(["python3", env("PHYTON_DIR")."addontcstconf.py", 
//         $ip, $login, $password, $port, $timeout, 
//         $pon_int, $onu_int, $onu_num, $sn, $onutype, 
//         $vlan, $description,$tconprofile, $gemportprofileup, $gemportprofiledown, $name]);
//     try {
//     // Start the process and wait for it to finish
//         $process->run();

//     // Check if the process was successful
//         if (!$process->isSuccessful()) {
//             throw new ProcessFailedException($process);
//         }

//     // Get the output from the process
//         // $output = $process->getOutput();
//         // return response()->json(['output' => $output]);


//         $messege =$process->getOutput();
//         $parts = explode(":", $messege);
//         return redirect ('/olt/'.$olt->id)->with($parts[0],$parts[1]);

//     } catch (ProcessFailedException $e) {
//     // If the process fails, return an error response
//        // return response()->json(['error' => $e->getMessage()]);
//      $messege =$e->getMessage();
//      $parts = explode(":", $messege);
//      return redirect ('/olt/'.$olt->id)->with($parts[0],$parts[1]);
//  }
// }



public function onudelete($oltId, $oltPonIndex, $onuId)
{
   // dd($request);

    $olt = \App\Olt::findOrFail($oltId);

    $ip = $olt->ip;
    $login = $olt->user;
    $password = $olt->password;
    $port = $olt->port;
    $timeout = 10;

    $frameslotportid = config('zteframeslotportid');
    $pon_int = array_search($oltPonIndex, $frameslotportid);

    $onu_num = $onuId;


    //dd($ip, $login, $password, $port, $pon_int, $onu_num, $command );




    $process = new Process(["python3", env("PHYTON_DIR")."delontconf.py", 
        $ip, $login, $password, $port, $timeout, 
        $pon_int, $onu_num]);
    try {
    // Start the process and wait for it to finish
        $process->run();

    // Check if the process was successful
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

    // // Get the output from the process
    //     // $output = $process->getOutput();
    //     // return response()->json(['output' => $output]);
    //     \App\Customer::where('customer_id', $request->customer_id)->update([
    //         'id_onu' => $parts_int[0].':'.$request->onu_id,


    //     ]);

        $messege =$process->getOutput();
        $parts = explode(":", $messege);
        return redirect ('/olt/'.$oltId)->with($parts[0],$parts[1]);

    } catch (ProcessFailedException $e) {
    // If the process fails, return an error response
       // return response()->json(['error' => $e->getMessage()]);
       $messege =$e->getMessage();
       return redirect ('/olt/'.$oltId)->with('error',$messege);
   }
}
public function onureboot($oltId, $oltPonIndex, $onuId)
{
   // dd($request);

    $olt = \App\Olt::findOrFail($oltId);
    
    // Detect vendor type
    $oltVendor = strtolower($olt->vendor ?? '');
    $oltType = strtolower($olt->type ?? '');
    $oltName = strtolower($olt->name ?? '');
    
    $isHSGQ = str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq');

    if ($isHSGQ) {
        // HSGQ: Use CLI/Telnet method for reboot
        // SNMP SET method doesn't actually trigger reboot on HSGQ hardware
        try {
            // Parse PON and ONU from oltPonIndex format (e.g., "6:8" -> PON 6, ONU 8)
            $parts = explode(':', $oltPonIndex);
            if (count($parts) == 2) {
                $ponNum = (int)$parts[0];
                $onuNum = (int)$onuId;
            } else {
                // Format PON number
                $ponNum = (int)$oltPonIndex;
                $onuNum = (int)$onuId;
            }
            
            $ip = $olt->ip;
            $login = $olt->user;
            $password = $olt->password;
            $port = $olt->port ?? 23;
            $timeout = 10;
            
            // Execute Python script for HSGQ ONU reboot via Telnet
            $processReboot = new Process(["python3", env("PHYTON_DIR")."reboot_hsgq_ont.py", 
                $ip, $login, $password, $port, $timeout, 
                $ponNum, $onuNum]);
            
            // Set timeout to 45 seconds to prevent gateway timeout
            $processReboot->setTimeout(45);
            
            $processReboot->run();
            
            if (!$processReboot->isSuccessful()) {
                throw new ProcessFailedException($processReboot);
            }
            
            $message = $processReboot->getOutput();
            $parts = explode(":", $message);
            return redirect()->back()->with($parts[0], $parts[1]);
            
        } catch (ProcessFailedException $e) {
            return redirect('/olt/'.$oltId)->with('error', 'Reboot error: ' . $e->getMessage());
        } catch (\Exception $e) {
            return redirect('/olt/'.$oltId)->with('error', 'Reboot error: ' . $e->getMessage());
        }
    }
    
    // ZTE: Use Telnet/CLI method
    $ip = $olt->ip;
    $login = $olt->user;
    $password = $olt->password;
    $port = $olt->port;
    $timeout = 10;

    $frameslotportid = config('zteframeslotportid');
    $pon_int = array_search($oltPonIndex, $frameslotportid);

    $onu_num = $onuId;



    //dd($ip, $login, $password, $port, $pon_int, $onu_num);




    $processreboot = new Process(["python3", env("PHYTON_DIR")."rebootontconf.py", 
        $ip, $login, $password, $port, $timeout, 
        $pon_int, $onu_num]);
    try {
    // Start the process and wait for it to finish
        $processreboot->run();

    // Check if the process was successful
        if (!$processreboot->isSuccessful()) {
            throw new ProcessFailedException($processreboot);
        }

    // // Get the output from the process
    //     // $output = $process->getOutput();
    //     // return response()->json(['output' => $output]);
    //     \App\Customer::where('customer_id', $request->customer_id)->update([
    //         'id_onu' => $parts_int[0].':'.$request->onu_id,


    //     ]);

        $messege =$processreboot->getOutput();
        $parts = explode(":", $messege);
        return redirect()->back()->with($parts[0],$parts[1]);

    } catch (ProcessFailedException $e) {
    // If the process fails, return an error response
       // return response()->json(['error' => $e->getMessage()]);
       $messege =$e->getMessage();
       return redirect ('/olt/'.$oltId)->with('error',$messege);
   }
}

public function onu_detail(Request $request)
{
   // dd($request);

    $olt = \App\Olt::findOrFail($request->id_olt);

    $ip = $olt->ip;
    $login = $olt->user;
    $password = $olt->password;
    $port = $olt->port;
    $timeout = 10;
    $id_onu = $request->id_onu;



    //dd($ip, $login, $password, $port, $pon_int, $onu_num);




    $processreboot = new Process(["python3", env("PHYTON_DIR")."onudetail.py", 
        $ip, $login, $password, $port, $timeout, 
        $id_onu]);
    try {
    // Start the process and wait for it to finish
        $processreboot->run();

    // Check if the process was successful
        if (!$processreboot->isSuccessful()) {
            throw new ProcessFailedException($processreboot);
        }


        $messege =$processreboot->getOutput();
        echo $messege;


    } catch (ProcessFailedException $e) {

       $messege =$e->getMessage();

   }
}

public function onureset($oltId, $oltPonIndex, $onuId)
{
   // dd($request);

    $olt = \App\Olt::findOrFail($oltId);
    
    // Detect vendor type
    $oltVendor = strtolower($olt->vendor ?? '');
    $oltType = strtolower($olt->type ?? '');
    $oltName = strtolower($olt->name ?? '');
    
    $isHSGQ = str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq');

    if ($isHSGQ) {
        // HSGQ: Use CLI/Telnet method for factory reset
        // SNMP SET method doesn't actually trigger reset on HSGQ hardware
        try {
            // Parse PON and ONU from oltPonIndex format
            $parts = explode(':', $oltPonIndex);
            if (count($parts) == 2) {
                $ponNum = (int)$parts[0];
                $onuNum = (int)$onuId;
            } else {
                $ponNum = (int)$oltPonIndex;
                $onuNum = (int)$onuId;
            }
            
            $ip = $olt->ip;
            $login = $olt->user;
            $password = $olt->password;
            $port = $olt->port ?? 23;
            $timeout = 10;
            
            // Execute Python script for HSGQ ONU factory reset via Telnet
            $processReset = new Process(["python3", env("PHYTON_DIR")."reset_hsgq_ont.py", 
                $ip, $login, $password, $port, $timeout, 
                $ponNum, $onuNum]);
            
            // Set timeout to 45 seconds to prevent gateway timeout
            $processReset->setTimeout(45);
            
            $processReset->run();
            
            if (!$processReset->isSuccessful()) {
                throw new ProcessFailedException($processReset);
            }
            
            $message = $processReset->getOutput();
            $parts = explode(":", $message);
            return redirect('/olt/'.$oltId)->with($parts[0], $parts[1]);
            
        } catch (ProcessFailedException $e) {
            return redirect('/olt/'.$oltId)->with('error', 'Reset error: ' . $e->getMessage());
        } catch (\Exception $e) {
            return redirect('/olt/'.$oltId)->with('error', 'Reset error: ' . $e->getMessage());
        }
    }
    
    // ZTE: Use Telnet/CLI method
    $ip = $olt->ip;
    $login = $olt->user;
    $password = $olt->password;
    $port = $olt->port;
    $timeout = 10;

    $frameslotportid = config('zteframeslotportid');
    $pon_int = array_search($oltPonIndex, $frameslotportid);

    $onu_num = $onuId;



    //dd($ip, $login, $password, $port, $pon_int, $onu_num);




    $processreset = new Process(["python3", env("PHYTON_DIR")."resetontconf.py", 
        $ip, $login, $password, $port, $timeout, 
        $pon_int, $onu_num]);
    try {
    // Start the process and wait for it to finish
        $processreset->run();

    // Check if the process was successful
        if (!$processreset->isSuccessful()) {
            throw new ProcessFailedException($processreset);
        }

    // // Get the output from the process
    //     // $output = $process->getOutput();
    //     // return response()->json(['output' => $output]);
    //     \App\Customer::where('customer_id', $request->customer_id)->update([
    //         'id_onu' => $parts_int[0].':'.$request->onu_id,


    //     ]);

        $messege =$processreset->getOutput();
        $parts = explode(":", $messege);
        return redirect ('/olt/'.$oltId)->with($parts[0],$parts[1]);

    } catch (ProcessFailedException $e) {
    // If the process fails, return an error response
       // return response()->json(['error' => $e->getMessage()]);
       $messege =$e->getMessage();
       return redirect ('/olt/'.$oltId)->with('error',$messege);
   }
}
public function delete(Request $request)
{
   // dd($request);

    $olt = \App\Olt::findOrFail($request->olt);

    $ip = $olt->ip;
    $login = $olt->user;
    $password = $olt->password;
    $port = $olt->port;
    $timeout = 10;
    $parts_int = explode(':', $request->onu_sn);

    $pon_int = 'gpon-olt_'.$parts_int[0];
    $onu_num = $request->onu_id;



 //   dd($ip, $login, $password, $port, $pon_int, $onu_int, $name, $onu_num, $sn, $onutype, $vlanname, $vlan, $username_pppoe, $password_pppoe, $description, $tconprofile, $gemportprofileup, $gemportprofiledown );




    $process = new Process(["python3", env("PHYTON_DIR")."addontconf.py", 
        $ip, $login, $password, $port, $timeout, 
        $pon_int, $onu_int, $onu_num, $sn, $onutype, 
        $vlan, $username_pppoe, $password_pppoe, $description, 
        $vlanname, $tconprofile, $gemportprofileup, $gemportprofiledown, $name]);
    try {
    // Start the process and wait for it to finish
        $process->run();

    // Check if the process was successful
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

    // Get the output from the process
        // $output = $process->getOutput();
        // return response()->json(['output' => $output]);
        \App\Customer::where('customer_id', $request->customer_id)->update([
            'id_onu' => $parts_int[0].':'.$request->onu_id,


        ]);

        $messege =$process->getOutput();
        return redirect ('/customer/'.$request->id_customer)->with('info',$messege);

    } catch (ProcessFailedException $e) {
    // If the process fails, return an error response
       // return response()->json(['error' => $e->getMessage()]);
       $messege =$e->getMessage();
       return redirect ('/customer/'.$request->id_customer.'/edit')->with('error',$messege);
   }
}

public function executeSSH($ip, $login, $password, $commands)
{
    $ssh = new SSH2($ip);

    // Coba login dan tangani jika login gagal
    if (!$ssh->login($login, $password)) {
        throw new \Exception('Login failed for user ' . $login);
    }

    // Eksekusi perintah satu per satu
    $output = '';
    foreach ($commands as $command) {
        $response = $ssh->exec($command);
        $output .= $response . "\n";

        // Cek apakah output menunjukkan kesalahan
        if (strpos($response, 'error') !== false || strpos($response, 'failed') !== false) {
            throw new \Exception('Command execution failed: ' . $response);
        }
    }

    return $output;
}


public function getOltOnuPower($id)
{
    try {
            // Temukan Olt berdasarkan ID atau lempar error 404 jika tidak ditemukan
        $olt = \App\Olt::findOrFail($id);

            // Ambil SNMP OID dari konfigurasi (auto-detect vendor)
        $zteoid = get_olt_oid_config($olt);

            // Inisialisasi koneksi SNMP
        $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip, $olt->community_ro,$olt->snmp_port);

            // OID untuk mendapatkan informasi
        $oidOltName = $zteoid['oidOltName'];
        $oidOltUptime = $zteoid['oidOltUptime'];
        $oidOltVersion = $zteoid['oidOltVersion'];
        $oidOltDesc = $zteoid['oidOltDesc'];

            // Mengambil informasi OLT melalui SNMP
        $oltInfo = [
            'oltName' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltName)),
            'oltUptime' => str_replace(['Timeticks: ', '"'], "", $snmp->get($oidOltUptime)),
            'oltVersion' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltVersion)),
            'oltDesc' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltDesc)),
        ];

            // Tutup koneksi SNMP
        $snmp->close();

            // Kembalikan data dalam bentuk JSON
        return response()->json(['success' => true, 'oltInfo' => $oltInfo]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['success' => false, 'error' => 'OLT Not Found.']);
    } catch (\SNMPException $e) {
        return response()->json(['success' => false, 'error' => 'Failed to retrieve OLT information ' . $e->getMessage()]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => 'Failed to retrieve OLT information ' . $e->getMessage()]);
    }
}



public function getOltPon($id)
{
    try {
        // Mengambil data OLT berdasarkan ID
        $olt = \App\Olt::findOrFail($id);
        // Ambil SNMP OID dari konfigurasi (auto-detect vendor)
        $zteoid = get_olt_oid_config($olt);
        $frameslotportid = get_olt_frameslotport_config($olt);

        // Inisialisasi koneksi SNMP
                                        $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip, $olt->community_ro);

        // Detect vendor type
        $oltVendor = strtolower($olt->vendor ?? '');
        $oltType = strtolower($olt->type ?? '');
        $oltName = strtolower($olt->name ?? '');
        
        $isHSGQ = str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq');
        $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
                        str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');

        // OID yang digunakan untuk mengambil nama ONU
        if ($isHSGQ) {
            // HSGQ: Use IF-MIB ifDescr instead
            $oidOnuName = $zteoid['oidIfDescr'];
        } else {
            $oidOnuName = $zteoid['oidOnuName'];
        }

        // Mengatur waktu eksekusi maksimum
                                        ini_set('max_execution_time', 300);

        // Mengambil data SNMP walk
                                        $result = $snmp->walk($oidOnuName);

                                        $data = [];
                                        $processedSuffixes = [];

        // Memeriksa apakah hasil 'oidOnuName' ada dan memprosesnya
                                        if ($result) {
                                            foreach ($result as $key => $onuName) {
                // Memisahkan kunci OID berdasarkan titik (.)
                                                $parts = explode('.', $key);

                                                if ($isHSGQ) {
                                                    // HSGQ: Parse IF-MIB ifDescr format "ONU{PON}/{ID}"
                                                    $ifDescr = str_replace(['STRING: ', '"'], '', $onuName);
                                                    
                                                    // Parse "ONU2/1" format to extract PON number
                                                    if (preg_match('/ONU(\d+)\/(\d+)/', $ifDescr, $matches)) {
                                                        $ponNum = (int)$matches[1];
                                                        $suffix = $ponNum; // Use PON number as suffix
                                                        $oltPon = $ponNum;  // Direct PON number (1-16)
                                                        $portKey = null;    // Not used for HSGQ
                                                    } else {
                                                        continue; // Skip non-ONU interfaces
                                                    }
                                                } elseif ($isC600Series) {
                                                    // C600/C620/C650: Branch .500 uses encoded index
                                                    // Format: .500.10.2.3.3.1.1.{encodedIndex}.{onuId}
                                                    // Get last 2 components
                                                    $lastTwo = array_slice($parts, -2);
                                                    if (count($lastTwo) >= 2) {
                                                        $encodedIndex = (int)$lastTwo[0];
                                                        
                                                        // Decode: (17 << 24) + (frame << 16) + (cardIndex << 8) + port
                                                        // Extract components
                                                        $port = ($encodedIndex >> 0) & 0xFF;
                                                        $cardIndex = ($encodedIndex >> 8) & 0xFF;
                                                        $frame = ($encodedIndex >> 16) & 0xFF;
                                                        $magic = ($encodedIndex >> 24) & 0xFF;
                                                        
                                                        // FIX: slot = cardIndex (not cardIndex-1)
                                                        $slot = $cardIndex;
                                                        
                                                        // Build frame/slot/port format
                                                        $portKey = $frame . '/' . $slot . '/' . $port;
                                                        
                                                        // Get shelf.slot from config
                                                        $suffix = $frameslotportid[$portKey] ?? null;
                                                        
                                                        if (!$suffix) continue;
                                                    } else {
                                                        continue;
                                                    }
                                                } else {
                                                    // C300/C320: Mengambil nilai kedua dari akhir sebagai suffix
                                                    $suffix = $parts[count($parts) - 2];
                                                    $portKey = array_search($suffix, $frameslotportid);
                                                }

                // Jika suffix belum diproses, maka lanjutkan
                                                if (!in_array($suffix, $processedSuffixes)) {
                    // Tambahkan suffix ke dalam daftar yang sudah diproses
                                                    $processedSuffixes[] = $suffix;

                    // Determine oltPon based on vendor type
                                                    if ($isHSGQ) {
                                                        // Already set above: $oltPon = $ponNum
                                                    } elseif ($isC600Series) {
                                                        $oltPon = $portKey;
                                                    } else {
                                                        $oltPon = $portKey !== false ? $portKey : 'unknown';
                                                    }

                    // Masukkan data yang sudah diproses ke dalam array
                                                    $data[] = [
                                                    'olt_pon' => $oltPon,
                                                    'suffix' => $suffix,
                        // Tambahkan elemen lainnya yang diperlukan
                                                ];
                                            }
                                        }
                                        $snmp->close();
                                    } else {
                                        return response()->json(['error' => 'Data OLT tidak ditemukan atau tidak tersedia.'], 500);
                                    }
       // dd($data);
                                    return response()->json(['data' => $data]);

                                } catch (\Exception $e) {
                                    return response()->json(['error' => 'Terjadi kesalahan saat mengambil Data: ' . $e->getMessage()], 500);
                                }
                            }





                            public function getOltOnu(Request $request)
                            {
                                try {
                                    $olt = \App\Olt::findOrFail($request->input('olt_id'));
                                    $customers = \App\Customer::where('id_olt', $olt->id)->get();
                                                $oltPonIndex = $request->input('olt_pon'); // Diterima dari request (shelf.slot untuk C620)

                                $zteoid = get_olt_oid_config($olt);
                                $ontStatuses = get_olt_status_config($olt);
                                
                                // Detect vendor type
                                $oltVendor = strtolower($olt->vendor ?? '');
                                $oltType = strtolower($olt->type ?? '');
                                $oltName = strtolower($olt->name ?? '');
                                
                                $isHSGQ = str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq');
                                $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
                                                str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');
                                
                                if ($isHSGQ) {
                                    // For HSGQ: Walk IF-MIB ifDescr to discover ONUs
                                    // ifDescr shows "ONU{PON}/{ID}" format (e.g., "ONU2/1")
                                    $oidOnuName = $zteoid['oidIfDescr']; // Use IF-MIB
                                    $oidOnuStatus = $zteoid['oidIfOperStatus']; // IF-MIB status
                                } elseif ($isC600Series) {
                                    // For C620: oidOnuName uses encoded index, can't append shelf.slot directly
                                    // We need to walk all ONUs and filter by selected port
                                    $oidOnuName = $zteoid['oidOnuName']; // No suffix
                                    $oidOnuStatus = $zteoid['oidOnuStatus']; // No suffix
                                } else {
                                    // For C300/C320: append index directly
                                    $oidOnuName = $zteoid['oidOnuName'].'.'.$oltPonIndex;
                                    $oidOnuStatus = $zteoid['oidOnuStatus'].'.'.$oltPonIndex;
                                }        // Mengatur waktu eksekusi maksimum
                                                ini_set('max_execution_time', 300);

        // Mengambil data SNMP walk
                                                $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip, $olt->community_ro);
                                                $result = $snmp->walk($oidOnuName);

                                                $data = [];

        // Memeriksa apakah hasil 'oidOnuName' ada
                                                if (!empty($result)) {
            // Iterasi melalui hasil SNMP walk berdasarkan kunci array
                                                    foreach ($result as $key => $onuName) {
                // Mengambil index dari hasil SNMP walk untuk digunakan dalam OID lainnya
                                                     $parts = explode('.', $key);
                                                     
                                                     if ($isHSGQ) {
                                                         // HSGQ GPON: Parse ifDescr format "ONU{PON}/{ID}"
                                                         $ifIndex = (int)end($parts); // IF-MIB ifIndex (last component of OID)
                                                         
                                                         // Get ifDescr to extract PON/ONU
                                                         $ifDescr = str_replace(['STRING: ', '"'], '', $onuName);
                                                         
                                                         // Parse "ONU2/1" format
                                                         if (preg_match('/ONU(\d+)\/(\d+)/', $ifDescr, $matches)) {
                                                             $ponNum = (int)$matches[1];
                                                             $onuId = (int)$matches[2];
                                                         } else {
                                                             continue; // Skip non-ONU interfaces
                                                         }
                                                         
                                                         // Filter: only process ONUs on selected PON
                                                         if ($ponNum != $oltPonIndex) {
                                                             continue;
                                                         }
                                                         
                                                         // Calculate hex index for enterprise OIDs
                                                         $encodedIndex = encode_hsgq_index($ponNum, $onuId);
                                                         $pon_int = $ponNum; // Just PON number for display
                                                     } elseif ($isC600Series) {
                                                         // C620: Extract encoded index and decode
                                                         $lastTwo = array_slice($parts, -2);
                                                         if (count($lastTwo) < 2) continue;
                                                         
                                                         $encodedIndex = (int)$lastTwo[0];
                                                         $onuId = $lastTwo[1];
                                                         
                                                         // Decode to get frame/slot/port
                                                         $port = ($encodedIndex >> 0) & 0xFF;
                                                         $cardIndex = ($encodedIndex >> 8) & 0xFF;
                                                         $frame = ($encodedIndex >> 16) & 0xFF;
                                                         $slot = $cardIndex; // FIX: slot = cardIndex (not cardIndex-1)
                                                         $shelfSlot = $frame . '.' . $slot;
                                                         
                                                         // Filter: only process ONUs on selected port
                                                         if ($shelfSlot !== $oltPonIndex) {
                                                             continue; // Skip ONUs not on selected port
                                                         }
                                                         
                                                         $pon_int = $frame . '/' . $slot . '/' . $port;
                                                     } else {
                                                         // C300/C320: Use old logic
                                                         $onuId = end($parts);
                                                         $frameslotportid = config('zteframeslotportid');
                                                         $lastTwoComponents = array_slice($parts, -2);
                                                         $pon_int = array_search($lastTwoComponents[0], $frameslotportid);
                                                     }
                                                     
                                                     $onuDistanceValue='Unknown';
                                                     $onUptimeValue ='Unknown';
         //$oidOnuStatusId = $oidOnuStatus.'.'.$onuId;

         //$oidRx = '.1.3.6.1.4.1.3902.1012.3.50.12.1.1.10.268501248.'.$onuId.'.1';

                // Mengambil data SNMP untuk Status dan RX Power
                                                     $customer = $customers->firstWhere('id_onu', "$pon_int:$onuId");

                                                     if ($isHSGQ) {
                                                         // HSGQ: Use IF-MIB ifOperStatus (1=up, 2=down)
                                                         $hasilStatusRaw = $this->safeSnmpGet($snmp, $oidOnuStatus.'.'.$ifIndex);
                                                         
                                                         // Parse status: can be "INTEGER: 1", "INTEGER: up(1)", or just "1"
                                                         // Extract numeric value
                                                         if (preg_match('/\b(1|up)\b/', $hasilStatusRaw)) {
                                                             $hasilStatus = '3'; // working (map IF-MIB up(1) to HSGQ working(3))
                                                         } else {
                                                             $hasilStatus = '0'; // offline (map IF-MIB down(2) to HSGQ offline(0))
                                                         }
                                                     } elseif ($isC600Series) {
                                                         // C620: Status OID uses encoded index (branch .500)
                                                         $hasilStatus = $this->safeSnmpGet($snmp, $oidOnuStatus.'.'.$encodedIndex.'.'.$onuId);
                                                     } else {
                                                         // C300/C320: Use old format
                                                         $hasilStatus = $this->safeSnmpGet($snmp, $oidOnuStatus.'.'.$onuId);
                                                     }
                                                     $result_status = $ontStatuses[$hasilStatus] ?? 'Unknown';

                                                     $modalId=$oltPonIndex."-".$onuId;

                                                     // Build OIDs based on OLT type
                                                     if ($isHSGQ) {
                                                         // HSGQ GPON: Enterprise OIDs
                                                         // Details: .50224.3.12.2.1.{field}.{hexIndex}
                                                         // Optics: .50224.3.12.3.1.{field}.{hexIndex}.0.0 (active) or .65535.65535 (offline)
                                                         $onuUptime = $zteoid['oidOnuUptime'].".$encodedIndex"; // Field 21: Timeticks
                                                         $rxPowerOid = $zteoid['oidOnuRxPower'].".$encodedIndex.0.0"; // Add subindex .0.0
                                                         $txPowerOid = $zteoid['oidOnuTxPowerOnu'].".$encodedIndex.0.0"; // TX Power ONU
                                                         $onuLastOffline = null; // Not available
                                                         $onuLastOnline = null; // Not available
                                                         $onuModel = $zteoid['oidOnuModel'].".$encodedIndex";
                                                         $onuDistance = $zteoid['oidOnuDistance'].".$encodedIndex.0.0"; // Add subindex
                                                         $onuSn = $zteoid['oidOnuSn'].".$encodedIndex";
                                                         $onuNameOid = $zteoid['oidOnuName'].".$encodedIndex"; // Enterprise OID for name
                                                         $onuVendorOid = $zteoid['oidOnuVendor'].".$encodedIndex" ?? null;
                                                         $OltRxPowerOid = null; // Not available
                                                     } elseif ($isC600Series) {
                                                         // C620: Branch .500 uses encoded index
                                                         // For details like Distance, SN, etc.
                                                         $onuUptime = $zteoid['oidOnuUptime'].".$encodedIndex.$onuId";
                                                         $rxPowerOid =$zteoid['oidOnuRxPower'].".$encodedIndex.$onuId.1";
                                                         $txPowerOid = $zteoid['oidOnuTxPower'].".$encodedIndex.$onuId.1";
                                                         $onuLastOffline = $zteoid['oidOnuLastOffline'].".$encodedIndex.$onuId";
                                                         $onuLastOnline = $zteoid['oidOnuLastOnline'].".$encodedIndex.$onuId";
                                                         $onuModel = $zteoid['oidOnuModel'].".$encodedIndex.$onuId";
                                                         $onuDistance = $zteoid['oidOnuDistance'].".$encodedIndex.$onuId";
                                                         $onuSn = $zteoid['oidOnuSn'].".$encodedIndex.$onuId";
                                                         $onuNameOid = $zteoid['oidOnuName'].".$encodedIndex.$onuId";
                                                         $OltRxPowerOid =$zteoid['oidOltRxPower'].".$encodedIndex.$onuId";
                                                     } else {
                                                         // C300/C320: Use old format with oltPonIndex
                                                         $onuUptime = $zteoid['oidOnuUptime'].".$oltPonIndex.$onuId";
                                                         $rxPowerOid =$zteoid['oidOnuRxPower'].".$oltPonIndex.$onuId.1";
                                                         $txPowerOid = $zteoid['oidOnuTxPower'].".$oltPonIndex.$onuId.1";
                                                         $onuLastOffline = $zteoid['oidOnuLastOffline'].".$oltPonIndex.$onuId";
                                                         $onuLastOnline = $zteoid['oidOnuLastOnline'].".$oltPonIndex.$onuId";
                                                         $onuModel = $zteoid['oidOnuModel'].".$oltPonIndex.$onuId";
                                                         $onuDistance = $zteoid['oidOnuDistance'].".$oltPonIndex.$onuId";
                                                         $onuSn = $zteoid['oidOnuSn'].".$oltPonIndex.$onuId";
                                                         $onuNameOid = $zteoid['oidOnuName'].".$oltPonIndex.$onuId";
                                                         $OltRxPowerOid =$zteoid['oidOltRxPower'].".$oltPonIndex.$onuId";
                                                     }

                                                     $onuDistanceValue = $this->safeSnmpGet($snmp,$onuDistance).'m';
                                                     $onuModelValue = $onuModel ? str_replace(['STRING: ', '"'], "", $this->safeSnmpGet($snmp,$onuModel)) : 'N/A';
                                                     
                                                     // Clean Serial Number - different format for different vendors
                                                     $onuSnRaw = $this->safeSnmpGet($snmp,$onuSn);
                                                     if ($isHSGQ) {
                                                         // HSGQ: Returns "STRING: ZTEGcf052b18" format
                                                         $onuSnValue = trim(str_replace(['STRING: ', 'Hex-STRING: ', '"'], "", $onuSnRaw));
                                                         $onuSnAscii = $onuSnValue; // Already ASCII for HSGQ
                                                     } else {
                                                         // ZTE: Returns "Hex-STRING: XX XX XX XX..." format
                                                         $onuSnValue = trim(str_replace(['Hex-STRING: ', '"'], "", $onuSnRaw));
                                                         $onuSnAscii = $this->convertMacToAscii($onuSnValue);
                                                     }
                                                     
                                                     $onuNameValue = str_replace(['STRING: ', '"'], "", $this->safeSnmpGet($snmp,$onuNameOid));
                                                     
                                                     // Convert Last Offline/Online from Hex-STRING to DateTime
                                                     if ($onuLastOffline) {
                                                         $onuLastOfflineRaw = str_replace(['Hex-STRING: ', '"'], "", $this->safeSnmpGet($snmp,$onuLastOffline));
                                                         $onuLastOfflineValue = $this->convertSnmpDateTime($onuLastOfflineRaw);
                                                     } else {
                                                         $onuLastOfflineValue = 'N/A';
                                                     }
                                                     
                                                     if ($onuLastOnline) {
                                                         $onuLastOnlineRaw = str_replace(['Hex-STRING: ', '"'], "", $this->safeSnmpGet($snmp,$onuLastOnline));
                                                         $onuLastOnlineValue = $this->convertSnmpDateTime($onuLastOnlineRaw);
                                                     } else {
                                                         $onuLastOnlineValue = 'N/A';
                                                     }
                                                     
                                                     // Get ONU Uptime
                                                    if ($isC600Series && $onuLastOnline && $result_status === 'online') {
                                                        // C620: Calculate uptime from LastOnline because oidOnuUptime doesn't update after reboot
                                                        $lastOnlineRaw = $this->safeSnmpGet($snmp, $onuLastOnline);
                                                        if ($lastOnlineRaw && strpos($lastOnlineRaw, 'Hex-STRING:') !== false) {
                                                            // Parse hex datetime: 07 EA 01 1B 12 15 06 00
                                                            $hexStr = str_replace(['Hex-STRING:', ' ', '-'], '', $lastOnlineRaw);
                                                            if (strlen($hexStr) >= 14) {
                                                                $year = hexdec(substr($hexStr, 0, 4));
                                                                $month = hexdec(substr($hexStr, 4, 2));
                                                                $day = hexdec(substr($hexStr, 6, 2));
                                                                $hour = hexdec(substr($hexStr, 8, 2));
                                                                $minute = hexdec(substr($hexStr, 10, 2));
                                                                $second = hexdec(substr($hexStr, 12, 2));
                                                                
                                                                $lastOnlineTime = mktime($hour, $minute, $second, $month, $day, $year);
                                                                $uptimeSec = time() - $lastOnlineTime;
                                                                
                                                                if ($uptimeSec > 0) {
                                                                    $days = floor($uptimeSec / 86400);
                                                                    $hours = floor(($uptimeSec % 86400) / 3600);
                                                                    $minutes = floor(($uptimeSec % 3600) / 60);
                                                                    $seconds = $uptimeSec % 60;
                                                                    
                                                                    if ($days > 0) {
                                                                        $onUptimeValue = sprintf("%d day%s, %d:%02d:%02d.00", $days, $days > 1 ? 's' : '', $hours, $minutes, $seconds);
                                                                    } else {
                                                                        $onUptimeValue = sprintf("%d:%02d:%02d.00", $hours, $minutes, $seconds);
                                                                    }
                                                                } else {
                                                                    $onUptimeValue = '0:00:00.00';
                                                                }
                                                            } else {
                                                                $onUptimeValue = 'N/A';
                                                            }
                                                        } else {
                                                            $onUptimeValue = 'N/A';
                                                        }
                                                    } elseif ($onuUptime) {
                                                        $uptimeRaw = $this->safeSnmpGet($snmp,$onuUptime);
                                                        if ($isHSGQ) {
                                                            // HSGQ: Timeticks format "Timeticks: (14002200) 1 day, 14:53:42.00"
                                                            $onUptimeValue = trim(preg_replace('/Timeticks: \(\d+\)\s*/', '', $uptimeRaw));
                                                            if (empty($onUptimeValue) || $onUptimeValue == '0:00:00.00') {
                                                                $onUptimeValue = 'Offline';
                                                            }
                                                        } else {
                                                            // ZTE C300/C320: Same Timeticks format
                                                         }
                                                     } else {
                                                         $onUptimeValue = 'Unknown';
                                                     }

                                                     $customerLink = $customer ? '<a href="/customer/'.$customer->id.'" class="btn btn-primary btn-sm">'.$onuSnAscii.'</a>' : $onuSnAscii;

                                                     // PRE-CHECK: If status is "working", verify with power values
                                                     if ($result_status == "working") {
                                                         $rxPowerValue = $this->safeSnmpGet($snmp,$rxPowerOid);
                                                         $txPowerValue = $this->safeSnmpGet($snmp,$txPowerOid);
                                                         
                                                         // Check if power values are valid
                                                         $rxValid = $rxPowerValue && !str_contains($rxPowerValue, 'No Such') && !str_contains($rxPowerValue, 'N/A');
                                                         $txValid = $txPowerValue && !str_contains($txPowerValue, 'No Such') && !str_contains($txPowerValue, 'N/A');
                                                         
                                                         // Determine offline cause for HSGQ
                                                         if ($isHSGQ && (!$rxValid || !$txValid)) {
                                                             if (!$rxValid && !$txValid) {
                                                                 $result_status = 'powerdown'; // Both powers lost = Power Down
                                                             } elseif (!$rxValid && $txValid) {
                                                                 $result_status = 'los'; // No RX but has TX = Laser out (LOS)
                                                             } elseif ($rxValid && !$txValid) {
                                                                 $result_status = 'los'; // Has RX but no TX = Laser issue
                                                             }
                                                         } elseif (!$rxValid && !$txValid) {
                                                             // Non-HSGQ: generic offline
                                                             $result_status = 'los';
                                                         }
                                                     }

                                                     if (empty($result_status))
                                                     {
                                                      $onu_ststus= "No data";
                                                  }
                                                  elseif ($result_status == "los")
                                                  {
                                                    $onu_ststus= '<a class="badge-danger badge btn-sm p-2 ml-2 mr-2 text-white" title="Laser Out - No Signal">LOS</a>';
                                                    $onu_delete =  ' <form onsubmit="confirmSubmit(event, \'Delete This ONU!\')" action="/olt/delete/' . $olt->id . '/' . $oltPonIndex . '/' . $onuId . '" method="POST">
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <input type="hidden" name="_token" value="' . csrf_token() . '">
                                                    <button type="submit" class="btn btn-danger btn-sm m-1" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                    </form>';
                                                }
                                                elseif ($result_status == "powerdown")
                                                {
                                                    $onu_ststus= '<a class="badge-warning badge btn-sm p-2 ml-2 mr-2 text-dark" title="Power Down - ONU Off">PWR DOWN</a>';
                                                    $onu_delete =  ' <form onsubmit="confirmSubmit(event, \'Delete This ONU!\')" action="/olt/delete/' . $olt->id . '/' . $oltPonIndex . '/' . $onuId . '" method="POST">
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <input type="hidden" name="_token" value="' . csrf_token() . '">
                                                    <button type="submit" class="btn btn-danger btn-sm m-1" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                    </form>';
                                                }
                                                elseif ($result_status == "working")
                                                {
                                                   // Power values already fetched in pre-check above
                                                   $rxPowerValue = $this->safeSnmpGet($snmp,$rxPowerOid);
                                                   $txPowerValue = $this->safeSnmpGet($snmp,$txPowerOid);


       // $oltRxPowerValue = @$snmp->get($OltRxPowerOid);



       // $onuSnValue = str_replace(['Hex-STRING: ', '"'], "", @$snmp->get($onuSn));
       // $onuSnAscii = $this->convertMacToAscii($onuSnValue);

       // $onUptimeValue = str_replace(['Timeticks:', '"'], "", @$snmp->get($onuUptime));

                                                   // Check if power values are valid (not empty, not "No Such Object", etc.)
                                                   $rxValid = $rxPowerValue && !str_contains($rxPowerValue, 'No Such') && !str_contains($rxPowerValue, 'N/A');
                                                   $txValid = $txPowerValue && !str_contains($txPowerValue, 'No Such') && !str_contains($txPowerValue, 'N/A');

                                                   if ($isHSGQ) {
                                                       // HSGQ: Power in dBm*100 format (e.g., -2100 = -21.00 dBm, 218 = 2.18 dBm)
                                                       if ($rxValid) {
                                                           $RX = explode(' ', $rxPowerValue);
                                                           $rxPowerValue = ((int)end($RX)) / 100.0; // Convert from dBm*100 to dBm
                                                       } else {
                                                           $rxPowerValue = 'N/A';
                                                       }
                                                       
                                                       // TX Power ONU (field 5) same format as RX Power
                                                       if ($txValid) {
                                                           $TX = explode(' ', $txPowerValue);
                                                           $txPowerValue = ((int)end($TX)) / 100.0; // Convert from dBm*100 to dBm
                                                       } else {
                                                           $txPowerValue = 'N/A';
                                                       }
                                                       
                                                       $oltRxPowerValue = 'N/A'; // Not available
                                                   } else {
                                                       // ZTE: Convert from raw value to dBm
                                                       if ($rxValid) {
                                                           $RX = explode(' ', $rxPowerValue);
                                                           $rxPowerValue = ((int)end($RX) * 0.002) - 30;
                                                       } else {
                                                           $rxPowerValue = 'N/A';
                                                       }
                                                       
                                                       if ($txValid) {
                                                           $TX = explode(' ', $txPowerValue);
                                                           $txPowerValue = ((int)end($TX) * 0.002) - 30;
                                                       } else {
                                                           $txPowerValue = 'N/A';
                                                       }

                                                       if ($OltRxPowerOid) {
                                                           $oltRxPowerValue = $this->safeSnmpGet($snmp,$OltRxPowerOid);
                                                           if ($oltRxPowerValue && !str_contains($oltRxPowerValue, 'No Such')) {
                                                               $OltRx = explode(' ', $oltRxPowerValue);
                                                               $oltRxPowerValue = ((int)end($OltRx) * 0.002) + 30;
                                                           } else {
                                                               $oltRxPowerValue = 'N/A';
                                                           }
                                                       } else {
                                                           $oltRxPowerValue = 'N/A';
                                                       }
                                                   }

                                                   // Set badge color based on RX power value
                                                   if(is_numeric($rxPowerValue) && $rxPowerValue < -29)
                                                   {
                                                    $bg="badge-danger";
                                                }
                                                elseif (is_numeric($rxPowerValue) && $rxPowerValue < -27) {
                                                    $bg="badge-warning";
                                                } elseif(is_numeric($rxPowerValue) && $rxPowerValue < -12) {
                                                    $bg="badge-success";
                                                }
                                                else
                                                {
                                                    $bg="badge-primary"; 
                                                }
                                                
                                                $result_status= 'Rx: '.$rxPowerValue.' | Tx: '.$txPowerValue;

       // $OltRx = explode(' ', $oltRxPowerValue);
       // $oltTxPowerValue = ((int)end($OltRx) * 0.002) + 30;

                                                // Build modal content based on vendor
                                                $modalBody = '<p id="rxPower">Onu Name : '.str_replace('"', '', $this->cleanSnmpValue($onuNameValue)).'</p>
                                                <p id="rxPower">Onu Model : '.$onuModelValue.' </p>
                                                <p id="rxPower">Onu Sn : '.$onuSnAscii.' </p>';
                                                
                                                if ($isHSGQ && $onuVendorOid) {
                                                    $onuVendorValue = str_replace(['STRING: ', '"'], "", $this->safeSnmpGet($snmp,$onuVendorOid));
                                                    $modalBody .= '<p id="rxPower">Onu Vendor : '.$onuVendorValue.' </p>';
                                                }
                                                
                                                $modalBody .= '<p id="rxPower">Onu Rx Power : '.$rxPowerValue.' dBm</p>
                                                <p id="txPower">Onu Tx Power : '.$txPowerValue.' dBm</p>
                                                <p id="txPower">Onu Cable Length  : '. $this->cleanSnmpValue($onuDistanceValue).' </p>';
                                                
                                                if (!$isHSGQ) {
                                                    // ZTE only: OLT RX Power, Last Offline/Online, Uptime
                                                    $modalBody .= '<p id="txPower">Olt Rx Power : '.$oltRxPowerValue.' dBm</p>
                                                    <p id="rxPower">Onu Last Offline : '.$onuLastOfflineValue.' </p>
                                                    <p id="txPower">Onu Last Online : '.$onuLastOnlineValue.' </p>
                                                    <p id="txPower">Onu Uptime : '.$onUptimeValue.' </p>';
                                                } else {
                                                    // HSGQ: Show interface status instead
                                                    $modalBody .= '<p id="rxPower">Interface Status : Online (ifOperStatus: up)</p>';
                                                }

                                                $onu_ststus= '<button id="powerButton" class="btn '.$bg.' btn-sm pb-1" data-toggle="modal" data-target="#powerModal'.$modalId.'">'.$result_status.'</button>

                                                <div class="modal fade" id="powerModal'.$modalId.'">
                                                <div class="modal-dialog">
                                                <div class="modal-content">
                                                <div class="modal-header">
                                                <h5 class="modal-title" id="powerModalLabel"><strong>Detail ONU '.$olt->name .'</strong></h5>

                                                </div>
                                                <div class="modal-body">
                                                '.$modalBody.'
                                                </div>
                                                <div class="modal-footer">

                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                </div>
                                                </div>
                                                </div>
                                                </div>';
                                                $onu_delete =  '
                                                <div class="row flex">
                                                <form onsubmit="confirmSubmit(event, \'Delete This ONU!\')" action="/olt/delete/' . $olt->id . '/' . $oltPonIndex . '/' . $onuId . '" method="POST">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <input type="hidden" name="_token" value="' . csrf_token() . '">
                                                <button type="submit" class="btn btn-danger btn-sm m-1" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                                </button>
                                                </form>

                                                <form onsubmit="confirmSubmit(event, \'Reboot This ONU!\')" action="/olt/reboot/' . $olt->id . '/' . $oltPonIndex . '/' . $onuId . '" method="POST">
                                                <input type="hidden" name="_method" value="POST"> <!-- Gunakan POST untuk reboot -->
                                                <input type="hidden" name="_token" value="' . csrf_token() . '">
                                                <button type="submit" class="btn btn-warning btn-sm m-1" title="Reboot">
                                                <i class="fas fa-sync-alt"></i>
                                                </button>
                                                </form>

                                                <form onsubmit="confirmSubmit(event, \'Factory Reset This ONU!\')" action="/olt/reset/' . $olt->id . '/' . $oltPonIndex . '/' . $onuId . '" method="POST">
                                                <input type="hidden" name="_method" value="POST"> <!-- Gunakan POST untuk factory reset -->
                                                <input type="hidden" name="_token" value="' . csrf_token() . '">
                                                <button type="submit" class="btn btn-info btn-sm m-1" title="Factory Reset">
                                                <i class="fas fa-redo-alt"></i>
                                                </button>
                                                </form>
                                                </div>';


           // $onu_ststus='working';
                                            } 
                                            elseif ($result_status == "dyinggasp")
                                            {
                                                $onu_ststus= '<a class="badge-warning badge btn-sm p-2 ml-2 mr-2 text-white  ">'.$result_status.'</a>';
                                                $onu_delete = '<form onsubmit="confirmSubmit(event, \'Delete This ONU!\')" action="/olt/delete/' . $olt->id . '/' . $oltPonIndex . '/' . $onuId . '" method="POST">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <input type="hidden" name="_token" value="' . csrf_token() . '">
                                                <button type="submit" class="btn btn-danger btn-sm m-1" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                                </button>
                                                </form>';
                                            }
                                            else
                                            {
                                                $onu_ststus= '<a class="badge-warning btn btn-sm  ml-2 mr-2 text-white  ">'.$result_status.'</a>';
                                                $onu_delete =  '<form onsubmit="confirmSubmit(event, \'Delete This ONU!\')" action="/olt/delete/' . $olt->id . '/' . $oltPonIndex . '/' . $onuId . '" method="POST">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <input type="hidden" name="_token" value="' . csrf_token() . '">
                                                <button type="submit" class="btn btn-danger btn-sm m-1" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                                </button>
                                                </form>';
                                            }



                // Memasukkan data yang telah dibersihkan ke dalam array
                                            $data[] = [
                                                'onuId' =>$onuId,
                                                'name' =>str_replace('"', '', $this->cleanSnmpValue($onuNameValue)),
             //'status' =>$this->cleanSnmpValue($hasilStatus),
                                                'status' =>$onu_ststus,
                                                'distance' =>$this->cleanSnmpValue($onuDistanceValue),
                                                'onuModel' =>$onuModelValue,
                                                // 'onuSn' =>$this->cleanSnmpValue($onuSnAscii),
                                                'onuSn' =>$this->cleanSnmpValue($customerLink),
                                                'onuLastOffline' =>$onuLastOfflineValue,
                                                'onuLastOnline' =>$onuLastOnlineValue,
                                                'onuUptime' =>$onUptimeValue,
                                                'onuDelete' =>$onu_delete,


           // 'rx_power' => $this->cleanSnmpValue($hasilRX),
                                            ];
                                        }
                                        $snmp->close();
                                    } else {
                                        return response()->json(['error' => 'Data OLT tidak ditemukan atau tidak tersedia.'], 500);
                                    }

                                    return DataTables::of($data)
                                    ->addIndexColumn()
                                    ->rawColumns(['DT_RowIndex','onuId','onuSn','onuModel', 'name','status','distance','onuLastOffline','onuLastOnline','onuUptime', 'onuDelete'])
                                    ->make(true);

                                } catch (\Exception $e) {
                                    return response()->json(['error' => 'Terjadi kesalahan saat mengambil Data: ' . $e->getMessage()], 500);
                                }
                            }

                            private function safeSnmpGet($snmp, $oid)
                            {
                                try {
                                    $value = @$snmp->get($oid);
                                    if ($value === false || str_contains($value, 'No Such Instance')) {
                                        return null;
                                    }
                                    return $value;
                                } catch (\Exception $e) {
                                    return null;
                                }
                            }
                            private function snmpWalk($host, $community, $oids)
                            {
                                try {
                                    $snmp = new \SNMP(\SNMP::VERSION_2c, $host, $community);
                                    $result = [];

                                    foreach ($oids as $key => $oid) {
                                        $result[$key] = $snmp->walk($oid) ?: [];
                                    }

                                    $snmp->close();

                                    return $result;

                                } catch (\Exception $e) {
                                    throw new \Exception('SNMP Walk failed: ' . $e->getMessage());
                                }
                            }

                            private function cleanSnmpValue($value)
                            {
    // Membersihkan nilai dari prefiks seperti "STRING: " atau spasi ekstra
                                return trim(str_replace(['STRING: ', 'INTEGER: ', 'Gauge32: '], '', $value));
                            }



                            public function addonu($id_customer, $id_olt)
                            {
                                $customer = \App\Customer::findOrFail($id_customer);
                                $olt = \App\Olt::findOrFail($id_olt);
                                $onutype = \App\Oltonutype::where('id_olt', $id_olt)->pluck('name', 'id');

                                $onuprofile = \App\Oltonuprofile::where('id_olt', $id_olt)
                                ->orderBy('vlan', 'asc')
                                ->get(['name', 'id', 'vlan']);

                                $zteoid = get_olt_oid_config($olt);
                                $onuUncfgSn = $zteoid['oidOnuUncfgSn'];
                                $onuUncfgtype = $zteoid['oidOnuUncfgType'];
                                $oidOltName = $zteoid['oidOltName'];
                                try{

                                    $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip, $olt->community_ro);
                                    $result = $snmp->walk($onuUncfgSn);



                                    if (empty($result)) {
                                        \Log::info('SNMP Walk returned no results for OID ' . $onuUncfgSn);
                                        return response()->json(['message' => 'No data found for the specified OID'], 404);
                                    }
                                    else
                                    {

        // Iterasi melalui hasil SNMP walk
                                        $processedResults = [];
                                        foreach ($result as $key => $onuUconfg) {
            // Pisahkan OID berdasarkan titik
                                            $oidParts = explode('.', $key);

            // Ambil dua nilai terakhir dari OID
                                            $lastTwoParts = array_slice($oidParts, -2);

            // Gabungkan dua nilai terakhir dengan titik
                                            $identifier = implode('.', $lastTwoParts);
                                            $desiredValue = $oidParts[count($oidParts) - 2];

                                            $onuType = str_replace(['STRING: ', '"'], "", $snmp->get($onuUncfgtype . '.' . $identifier));
                                            $onuMac = str_replace(['STRING: ', '"'], "", $onuUconfg);

            // Simpan hasil ke array
                                            $onu[] = [
                                                'oltName' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltName)),
                                                'oid' => $this->getPonCode($desiredValue),
                'identifier' => $this->cleanSnmpValue($onuType), // Menyimpan dua bagian terakhir
                'value' => $this->convertMacToAscii($onuMac),
                'ponid' => $zteoid['oidOnuName'].'.'.$desiredValue,
            ];
        }




//         $oidOnuName = $zteoid['oidOnuName'].'.'.$desiredValue;
//         $result_getonuid = $snmp->walk($oidOnuName);

// // Ambil hanya bagian ID terakhir dari array OID
//         $used_ids = [];
//         foreach ($result_getonuid as $key => $value) {
//     // Ambil ID terakhir dari OID
//             $oid_parts = explode('.', $key);
//             $id = end($oid_parts);
//     $used_ids[] = (int)$id; // Ubah ke integer agar bisa dibandingkan
// }

// // Cek ID yang tidak terpakai dari 1 sampai 128
// $max_id = 128;
// $all_ids = range(1, $max_id);
// $empty_ids = array_diff($all_ids, $used_ids);


        $oidOltGmportProfile = $zteoid['oidOltGmportProfile'];
        $result_oidOltGmportProfile = $snmp->walk($oidOltGmportProfile);
        $oidOltGmportProfile = str_replace(['STRING: ', '"'], "", $result_oidOltGmportProfile);

        $oidOltTconProfile = $zteoid['oidOltTconProfile'];
        $result_oidOltTconProfile = $snmp->walk($oidOltTconProfile);
        $oidOltTconProfile = str_replace(['STRING: ', '"'], "", $result_oidOltTconProfile);

        $oidOltVlanId = $zteoid['oidOltVlanId'];
        $result_oidOltVlanId = $snmp->walk($oidOltVlanId);
        $oidOltVlanId = str_replace(['STRING: ', '"'], "", $result_oidOltVlanId);

//dd($oidOltVlanId);




        foreach ($oidOltVlanId as $oid => $vlanName) {
            $parts = explode('.', $oid);
    $lastNumber = end($parts); // Get the last part

    $vlanList[] =$lastNumber;
    
}



}

        // Tutup sesi SNMP
$snmp->close();


}
catch (\Exception $e) {
        // Tangani kesalahan jika terjadi
   // \Log::error('SNMP Walk failed for OID ' . $onuUncfgSn . ': ' . $e->getMessage());
    //return response()->json(['error' => 'SNMP Walk failed', 'details' => $e->getMessage()], 500);
    $messege =" Unconfigure Onu Not Found";
    return redirect ('/customer/'.$customer->id.'/edit')->with('warning',$messege);
}
// return view ('olt/addonu',['customer' =>$customer,'olt' =>$olt, 'onutype' => $onutype,'vlanList' =>$vlanList, 'onuprofile' =>$onuprofile,  'onu' =>$onu, 'empty_ids'=> $empty_ids, 'oidOltGmportProfile' => $oidOltGmportProfile, 'oidOltTconProfile' => $oidOltTconProfile]);
return view ('olt/addonu',['customer' =>$customer,'olt' =>$olt, 'onutype' => $onutype,'vlanList' =>$vlanList, 'onuprofile' =>$onuprofile,  'onu' =>$onu, 'oidOltGmportProfile' => $oidOltGmportProfile, 'oidOltTconProfile' => $oidOltTconProfile]);


}

public function getemptyonuid(Request $request)
{
    // $olt_id = $request->get('olt_id');
    // $onuid = $request->get('onu_sn');

    // $parts = explode(":", $onuid);

    // // Pastikan Anda mendapatkan objek OLT berdasarkan ID
    // $olt = \App\Olt::findOrFail($olt_id);
    // $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip, $olt->community_ro);

    // // Melakukan query SNMP berdasarkan OID dari onu_sn
    // $result_getonuid = $snmp->walk($parts[2]);

    // // Ambil hanya bagian ID terakhir dari array OID
    // $used_ids = [];
    // foreach ($result_getonuid as $key => $value) {
    //     $oid_parts = explode('.', $key);
    //     $id = end($oid_parts);
    //     $used_ids[] = (int)$id; // Ubah ke integer agar bisa dibandingkan
    // }

    // // Cek ID yang tidak terpakai dari 1 sampai 128
    // $max_id = 128;
    // $all_ids = range(1, $max_id);
    // $empty_ids = array_diff($all_ids, $used_ids);

    // // Mengembalikan response dalam format JSON
    // return response()->json(array_values($empty_ids));

  $olt_id = $request->get('olt_id');
  $onuid = $request->get('onu_sn');

  $parts = explode(":", $onuid);

    // Pastikan Anda mendapatkan objek OLT berdasarkan ID
  $olt = \App\Olt::findOrFail($olt_id);
  $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip, $olt->community_ro);

    // Melakukan query SNMP berdasarkan OID dari onu_sn
  $used_ids = [];
  try{
    $result_getonuid = $snmp->walk($parts[2]);
    if ($result_getonuid === false) {
        throw new \ErrorException("SNMP walk failed for OID: $oidOnuName");
    }

    // Ambil hanya bagian ID terakhir dari array OID
    
    foreach ($result_getonuid as $key => $value) {
        $oid_parts = explode('.', $key);
        $id = end($oid_parts);
        $used_ids[] = (int)$id; // Ubah ke integer agar bisa dibandingkan
    }
} catch (\ErrorException $e) {
    // Log the error for debugging purposes
    \Log::error('SNMP Walk Error in OltController: ' . $e->getMessage());

    // Add a default value to indicate an error
    $used_ids[] = 0;
}
    // Cek ID yang tidak terpakai dari 1 sampai 128
$max_id = 128;
$all_ids = range(1, $max_id);
$empty_ids = array_diff($all_ids, $used_ids);

    // Mengembalikan response dalam format JSON
return response()->json(array_values($empty_ids));


}


public function addonucustome($id_olt)
{
    // $customer = \App\Customer::findOrFail($id_customer);
    $olt = \App\Olt::findOrFail($id_olt);
    $onutype = \App\Oltonutype::pluck('name', 'id');
    $onuprofile = \App\Oltonuprofile::get(['name', 'id','vlan']);

    $zteoid = get_olt_oid_config($olt);
    $onuUncfgSn = $zteoid['oidOnuUncfgSn'];
    $onuUncfgtype = $zteoid['oidOnuUncfgType'];
    $oidOltName = $zteoid['oidOltName'];
    $oidOltVlanId = $zteoid['oidOltVlanId'];
    $vlanList = []; // Renaming to avoid conflict
    $onu = []; // Deklarasi array sebelum digunakan
    $empty_ids='';
    $oidOltGmportProfile='';
    $oidOltTconProfile='';


   // dd($oidOltVlanId);
    try{

        $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip, $olt->community_ro);
        $result = $snmp->walk($onuUncfgSn);

     //   dd($result);

        if (empty($result)) {
            \Log::info('SNMP Walk returned no results for OID ' . $onuUncfgSn);
          //  return response()->json(['message' => 'No data found for the specified OID'], 404);
            $messege =" Unconfigure Onu Not Found";
            return redirect ('/customer/'.$olt->id)->with('warning',$messege);
        }
        else

        {



           foreach ($result as $key => $onuUconfg) {
            // Pisahkan OID berdasarkan titik
            $oidParts = explode('.', $key);

            // Ambil dua nilai terakhir dari OID
            $lastTwoParts = array_slice($oidParts, -2);

            // Gabungkan dua nilai terakhir dengan titik
            $identifier = implode('.', $lastTwoParts);
            $desiredValue = $oidParts[count($oidParts) - 2];

            $onuType = str_replace(['STRING: ', '"'], "", $snmp->get($onuUncfgtype . '.' . $identifier));
            $onuMac = str_replace(['STRING: ', '"'], "", $onuUconfg);

            // Simpan hasil ke array
            $onu[] = [
                'oltName' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltName)),
                'oid' => $this->getPonCode($desiredValue),
                'identifier' => $this->cleanSnmpValue($onuType), // Menyimpan dua bagian terakhir
                'value' => $this->convertMacToAscii($onuMac),
                'ponid' => $zteoid['oidOnuName'].'.'.$desiredValue,
            ];
        }




        $oidOnuName = $zteoid['oidOnuName'].'.'.$desiredValue;
        $result_getonuid = $snmp->walk($oidOnuName);
       // dd($onu);

// Ambil hanya bagian ID terakhir dari array OID
        $used_ids = [];
        foreach ($result_getonuid as $key => $value) {
    // Ambil ID terakhir dari OID
            $oid_parts = explode('.', $key);
            $id = end($oid_parts);
    $used_ids[] = (int)$id; // Ubah ke integer agar bisa dibandingkan

}
// Cek ID yang tidak terpakai dari 1 sampai 128
$max_id = 128;
$all_ids = range(1, $max_id);
$empty_ids = array_diff($all_ids, $used_ids);


$oidOltGmportProfile = $zteoid['oidOltGmportProfile'];
$result_oidOltGmportProfile = $snmp->walk($oidOltGmportProfile);
$oidOltGmportProfile = str_replace(['STRING: ', '"'], "", $result_oidOltGmportProfile);

$oidOltTconProfile = $zteoid['oidOltTconProfile'];
$result_oidOltTconProfile = $snmp->walk($oidOltTconProfile);
$oidOltTconProfile = str_replace(['STRING: ', '"'], "", $result_oidOltTconProfile);


$result_oidOltVlanId = $snmp->walk($oidOltVlanId);
$result_oidOltVlanId = str_replace(['STRING: ', '"'], "", $result_oidOltVlanId);


foreach ($result_oidOltVlanId as $oid => $vlanName) {
    $parts = explode('.', $oid);
    $lastNumber = end($parts); // Get the last part

    $vlanList[] =$lastNumber;
    
}

// Dumping the final array
//dd($vlanList);
return view ('olt/addonucst',['olt' =>$olt, 'onutype' => $onutype, 'onuprofile' =>$vlanList,  'onu' =>$onu, 'empty_ids'=> $empty_ids, 'oidOltGmportProfile' => $oidOltGmportProfile, 'oidOltTconProfile' => $oidOltTconProfile]);

}

        // Tutup sesi SNMP
$snmp->close();


}
catch (\Exception $e) {
        // Tangani kesalahan jika terjadi
   // \Log::error('SNMP Walk failed for OID ' . $onuUncfgSn . ': ' . $e->getMessage());
    //return response()->json(['error' => 'SNMP Walk failed', 'details' => $e->getMessage()], 500);
    $messege =" Unconfigure Onu Not Found";
   // return redirect ('/customer/'.$customer->id.'/edit')->with('warning',$messege);
}
$messege =" Unconfigure Onu Not Found";
return redirect ('/olt/'.$olt->id)->with('warning',$messege);

}


public function table_onu_unconfig(Request $request)
{
    $host = $request->olt;  // Ganti dengan alamat host SNMP Anda
    $community = $request->community;
    
    // Note: This method doesn't have $olt object, using default config
    // Should be updated to get OLT from database using IP
    $zteoid = config('zteoid'); // Keep default for now
    $onuUncfgSn = $zteoid['oidOnuUncfgSn'];
    $onuUncfgtype = $zteoid['oidOnuUncfgType'];
    $oidOltName = $zteoid['oidOltName'];

    // Validasi input request
    if (empty($host) || empty($community)) {
        return response()->json(['error' => 'Invalid OLT or community'], 400);
    }

    try {
        // Inisialisasi SNMP
        $snmp = new \SNMP(\SNMP::VERSION_2c, $host, $community);

        // Panggil fungsi SNMP Walk
        $result = $snmp->walk($onuUncfgSn);

        if ($result === false) {
            \Log::warning('SNMP Walk failed: No data returned for OID ' . $onuUncfgSn);
            return response()->json(['message' => 'No data returned from SNMP walk'], 404);
        }

        // Periksa apakah hasilnya kosong
        if (empty($result)) {
            \Log::info('SNMP Walk returned no results for OID ' . $onuUncfgSn);
            return response()->json(['message' => 'No data found for the specified OID'], 404);
        }

        // Iterasi melalui hasil SNMP walk
        $processedResults = [];
        foreach ($result as $key => $onuUconfg) {
            // Pisahkan OID berdasarkan titik
            $oidParts = explode('.', $key);

            // Ambil dua nilai terakhir dari OID
            $lastTwoParts = array_slice($oidParts, -2);

            // Gabungkan dua nilai terakhir dengan titik
            $identifier = implode('.', $lastTwoParts);
            $desiredValue = $oidParts[count($oidParts) - 2];

            $onuType = str_replace(['STRING: ', '"'], "", $snmp->get($onuUncfgtype . '.' . $identifier));
            $onuMac = str_replace(['STRING: ', '"'], "", $onuUconfg);

            // Simpan hasil ke array
            $processedResults[] = [
                'oltName' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltName)),
                'oid' => $this->getPonCode($desiredValue),
                'identifier' => $this->cleanSnmpValue($onuType), // Menyimpan dua bagian terakhir
                'value' => $this->convertMacToAscii($onuMac),
            ];
        }

        // Tutup sesi SNMP
        $snmp->close();

        // Return hasil dalam bentuk DataTables
        

    } catch (\Exception $e) {
        // Tangani kesalahan jika terjadi
        // \Log::error('SNMP Walk failed for OID ' . $onuUncfgSn . ': ' . $e->getMessage());
        // return response()->json(['error' => 'SNMP Walk failed', 'details' => $e->getMessage()], 500);
        $processedResults[] = [
            'oltName' => '',
            'oid' => '',
            'identifier' => '',
            'value' => '',
        ];
    }
    return DataTables::of($processedResults)
    ->addIndexColumn()
    ->rawColumns(['DT_RowIndex', 'oid', 'identifier', 'value', 'name', 'status', 'distance', 'onuLastOffline', 'onuLastOnline', 'onuUptime'])
    ->make(true);
}


public function coba($host, $community)
{
    // Alamat host dan community
    $host = '202.169.255.10';  // Ganti dengan alamat host SNMP Anda
    $community = 'public_ro';
    
    // Note: This is a test method, should get OLT from database
    $zteoid = config('zteoid'); // Keep default for test method
    $onuUncfgSn = $zteoid['oidOnuUncfgSn'];
    $onuUncfgtype = $zteoid['oidOnuUncfgType'];
    $oidOltName = $zteoid['oidOltName'];
    $OltVlanId = $zteoid['oidOltVlanId'];



    try {
        // Inisialisasi SNMP
        $snmp = new \SNMP(\SNMP::VERSION_2c, $host, $community);
        
        // Panggil fungsi SNMP Walk
        $result = $snmp->walk($onuUncfgSn);
        //dd(count($result));
        // $onuName = $zteoid['oidOnuName'];
        $oltVlanId = $snmp->walk($OltVlanId);
        $vlanIdResult = [];
        foreach ($oltVlanId as $vlan => $VlanName) {
            $vlan_value = substr(strrchr($vlan, "."), 1);
            $vlan_name = str_replace(['STRING: ', '"'], "",  $VlanName);

            $vlanIdResult[] = [
                'vlanId' => $vlan_value,
                'vlanName' => $vlan_name,
            ]; 
        }
        //dd($vlanIdResult);
        // dd(count($onuNameValue));
        // Periksa apakah hasilnya false
        if ($result === false) {
            \Log::warning('SNMP Walk failed: No data returned for the specified OID ' . $onuUncfgSn);
            return response()->json(['message' => 'No data returned from SNMP walk'], 404);
        }

        // Periksa apakah hasilnya kosong
        if (empty($result)) {
            \Log::info('SNMP Walk returned no results for OID ' . $onuUncfgSn);
            return response()->json(['message' => 'No data found for the specified OID'], 404);
        }

        // Iterasi melalui hasil SNMP walk
        $processedResults = [];
        foreach ($result as $key => $onuUconfg) {
            // Pisahkan OID berdasarkan titik
            $oidParts = explode('.', $key);

            // Ambil dua nilai terakhir dari OID
            $lastTwoParts = array_slice($oidParts, -2);

            // Gabungkan dua nilai terakhir dengan titik
            $identifier = implode('.', $lastTwoParts);
            $desiredValue = $oidParts[count($oidParts) - 2];
            $onuType =  str_replace(['STRING: ', '"'], "",  $snmp->get($onuUncfgtype.'.'.$identifier));
            $onuMac = str_replace(['STRING: ', '"'], "",  $onuUconfg);
            // Simpan hasil ke array
            $processedResults[] = [
                'oltName' => str_replace(['STRING: ', '"'], "", $snmp->get($oidOltName)),
                'oid' => $this->getPonCode($desiredValue),
                'identifier' =>  $this->cleanSnmpValue($onuType), // Menyimpan dua bagian terakhir
                'value' => $this->convertMacToAscii($onuMac),
            ];
        }


        // Mengembalikan hasil yang sudah diproses dalam bentuk JSON
        return response()->json($processedResults);
     //   dd($processedResults);

    } catch (\Exception $e) {
        // Tangani kesalahan jika terjadi
        \Log::error('SNMP Walk failed for OID ' . $onuUncfgSn . ': ' . $e->getMessage());
        return response()->json(['error' => 'SNMP Walk failed', 'details' => $e->getMessage()], 500);
    }
}




    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

// Temukan Olt berdasarkan ID
        $olt = \App\Olt::findOrFail($id);
        return view ('olt/edit',['olt' =>$olt]);

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


       $validatedData = $request->validate([
     'name' => ['required', 'string', 'max:255', 'unique:olts,name,' . $id], // Corrected the 'unique' rule to target the 'olts' table and 'name' column
     'vendor' => 'required|string|max:50', // Added vendor validation
     'type' => 'required|string|max:255', // Added string validation for 'type' and a maximum length
     'ip' => 'required|ip', // Added IP validation for the 'ip' field
     'port' => 'required|integer|min:1|max:65535', // Added integer validation and port range
     'user' => 'required|string|max:255', // Added string validation and max length for 'user'
     'password' => 'required|string|max:255', // Added string validation and max length for 'password'
     'community_ro' => 'required|string|max:255', // Added string validation and max length for 'community_ro'
     'community_rw' => 'required|string|max:255', // Added string validation and max length for 'community_rw'
     'snmp_port' => 'required|integer|min:1|max:65535', // Added integer validation and port range for SNMP port
 ]);

       $olt = \App\Olt::findOrFail($id);
       $olt->update([
           'name' => $request->input('name'),
           'vendor' => $request->input('vendor'),
           'type' => $request->input('type'),
           'ip' => $request->input('ip'),
     'port' => $request->input('port'), // Pastikan port termasuk dalam update
     'user' => $request->input('user'),
     'password' => $request->input('password'), // Pastikan password termasuk dalam update
     'community_ro' => $request->input('community_ro'), // Pastikan community_ro termasuk dalam update
     'community_rw' => $request->input('community_rw'), // Pastikan community_rw termasuk dalam update
     'snmp_port' => $request->input('snmp_port'),
 ]);

       return redirect('/olt')->with('success', 'OLT updated successfully!');
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
 }

 public function convertMacToAscii($mac) {
     $hexArray = explode(' ', $mac); // Pisahkan pasangan heksadesimal
     $result = '';

     foreach ($hexArray as $index => $hex) {
        if ($index < 4) { // Konversi hanya 4 pasangan pertama
            $decimalValue = hexdec($hex); // Konversi heksadesimal ke desimal
            $asciiChar = chr($decimalValue); // Konversi desimal ke karakter ASCII

            // Cek jika karakter dapat dicetak
            if (ctype_print($asciiChar)) {
                $result .= $asciiChar;
            } else {
                    $result .= $hex; // Jika karakter tidak dapat dicetak, gunakan heksadesimal asli
                }
            } else {
                    $result .= $hex; // Sisanya tetap dalam bentuk asli
                }
            }

            return $result;
        }

 // Convert SNMP DateAndTime format (Hex-STRING) to readable datetime
 // Format: year(2 bytes) month(1) day(1) hour(1) minute(1) second(1) deciseconds(1)
 // Example: "07 EA 01 16 0C 07 11 00" => "2026-01-22 12:07:17"
 public function convertSnmpDateTime($hexString) {
     if (empty($hexString) || strpos($hexString, '00 00 00 00 00 00 00 00') !== false) {
         return '';
     }
     
     $hexArray = explode(' ', trim($hexString));
     if (count($hexArray) < 7) {
         return $hexString; // Return original if invalid format
     }
     
     $year = (hexdec($hexArray[0]) << 8) + hexdec($hexArray[1]);
     $month = hexdec($hexArray[2]);
     $day = hexdec($hexArray[3]);
     $hour = hexdec($hexArray[4]);
     $minute = hexdec($hexArray[5]);
     $second = hexdec($hexArray[6]);
     
     return sprintf("%04d-%02d-%02d %02d:%02d:%02d", $year, $month, $day, $hour, $minute, $second);
 }
//=================================



        public function getGponOnuIndex($selfSlotPortOnu) {
    // Split the input into main and optional parts
   // $parts = explode(':', $selfSlotPortOnu);

    // Further split the first part into shelf, slot, and ONU
            list($shelf, $slot, $onu) = explode('/', $selfSlotPortOnu);

    // The base index for gpon-onu_1/1/1
            $baseIndex = 285278465;

    // The gap when moving between slots
            $slotGaps = [
            1 => 0,    // Slot 1 has no gap
            2 => 256,  // Slot 2 starts with a gap of 256
            3 => 256 * 2,  // Slot 3 starts with a gap of 512
            4 => 256 * 3,  // Slot 4 starts with a gap of 768
            5 => 256 * 4,  // Slot 5 starts with a gap of 1024
            6 => 256 * 5,  // Slot 6 starts with a gap of 1280
            7 => 256 * 6,  // Slot 7 starts with a gap of 1536
            8 => 256 * 7,  // Slot 8 starts with a gap of 1792
            9 => 256 * 8,  // Slot 9 starts with a gap of 2048
            10 => 256 * 9, // Slot 10 starts with a gap of 2304
            11 => 256 * 10, // Slot 11 starts with a gap of 2560
            12 => 256 * 11, // Slot 12 starts with a gap of 2816
            13 => 256 * 12, // Slot 13 starts with a gap of 3072
            14 => 256 * 13, // Slot 14 starts with a gap of 3328
            15 => 256 * 14, // Slot 15 starts with a gap of 3584
            16 => 256 * 15, // Slot 16 starts with a gap of 3840
            17 => 256 * 16, // Slot 17 starts with a gap of 4096
            18 => 256 * 17, // Slot 18 starts with a gap of 4352
            19 => 256 * 18, // Slot 19 starts with a gap of 4608
            20 => 256 * 19  // Slot 20 starts with a gap of 4864
        ];

    // Calculate the index based on the slot and ONU number
        $index = $baseIndex + $slotGaps[$slot] + ($onu - 1);

    // If there is a second part (e.g., :2), use it for additional calculations if needed
        if (isset($parts[1])) {
        // Example: Apply additional logic based on the value in $parts[1] if needed
        }

        return $index;
    }









    public function ont_status(Request $request)
    {

        $olt = \App\Olt::findOrFail($request->id_olt);

            // Ambil SNMP OID dari konfigurasi (auto-detect vendor)
        $zteoid = get_olt_oid_config($olt);
        $frameSlotPortString = get_olt_frameslotport_config($olt);
        $ontStatuses = get_olt_status_config($olt);

            // Inisialisasi koneksi SNMP
        $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip, $olt->community_ro);
 // Validasi id_onu format
        if (!strpos($request->id_onu, ':')) {
            return response()->json(['error' => 'Invalid ONT ID format'], 400);
        }

        list($frameSlotPort, $ontId) = explode(":", $request->id_onu);

    // Validasi frame-slot-port ID
        if (!isset($frameSlotPortString[$frameSlotPort])) {
            return response()->json(['error' => 'Invalid frame-slot-port ID'], 400);
        }

        $frameSlotPortId = $frameSlotPortString[$frameSlotPort] ?? 'Unknown';
        $onuName = $zteoid['oidOnuName'].".$frameSlotPortId.$ontId";
        $onuStatus = $zteoid['oidOnuStatus'].".$frameSlotPortId.$ontId";
        $onuUptime = $zteoid['oidOnuUptime'].".$frameSlotPortId.$ontId";
        $rxPowerOid =$zteoid['oidOnuRxPower'].".$frameSlotPortId.$ontId.1";
        $txPowerOid = $zteoid['oidOnuTxPower'].".$frameSlotPortId.$ontId.1";
        $onuLastOffline = $zteoid['oidOnuLastOffline'].".$frameSlotPortId.$ontId";
        $onuLastOnline = $zteoid['oidOnuLastOnline'].".$frameSlotPortId.$ontId";
        $onuModel = $zteoid['oidOnuModel'].".$frameSlotPortId.$ontId";
        $onuDistance = $zteoid['oidOnuDistance'].".$frameSlotPortId.$ontId";
        $onuSn = $zteoid['oidOnuSn'].".$frameSlotPortId.$ontId";
        $onuUptime = $zteoid['oidOnuUptime'].".$frameSlotPortId.$ontId";


        $oltRxGetId = $this->getGponOnuIndex($frameSlotPort);
        $OltRxPowerOid =$zteoid['oidOltRxPower'].".$oltRxGetId.$ontId";

        $modalId=$frameSlotPortId."-".$ontId;

        $statusValue = @$snmp->get($onuStatus);
        if ($statusValue === false) {
            //continue; // Lewati ONT jika status tidak dapat diambil
        }
        $result_status = $ontStatuses[$statusValue] ?? 'Unknown';
              //$result_status = $ontStatuses[$statusValue] ?? 'Unknown';

        if (empty($result_status))
        {
          echo "No data";
      }
      else
      {
          if ($result_status == "los")
          {
           $onuNameValue = str_replace(['STRING: ', '"'], "", @$snmp->get($onuName));
           $onuModelValue = str_replace(['STRING: ', '"'], "", @$snmp->get($onuModel));
           $onuSnValue = str_replace(['Hex-STRING: ', '"'], "", @$snmp->get($onuSn));
           $onuSnAscii = $this->convertMacToAscii($onuSnValue);
           $onuLastOfflineValue = str_replace(['STRING: ', '"'], "", @$snmp->get($onuLastOffline));
           $onuLastOnlineValue = str_replace(['STRING: ', '"'], "", @$snmp->get($onuLastOnline));
           $onuDistanceValue = str_replace(['INTEGER: ', '"'], "", @$snmp->get($onuDistance));
           echo '<a class="badge-danger badge btn-sm p-2 ml-2 mr-2 text-white  ">'.$result_status.'</a><div class="modal fade" id="powerModal'.$modalId.'">
           <div class="modal-dialog">
           <div class="modal-content">
           <div class="modal-header">
           <h5 class="modal-title" id="powerModalLabel"><strong>Status ONU '.$olt->name .' '.$frameSlotPort.':'.$ontId.'</strong></h5>

           </div>
           <div class="modal-body">
           <p id="rxPower">Onu Name : '.$onuNameValue.'</p>
           <p id="rxPower">Onu Model : '.$onuModelValue.' </p>
           <p id="rxPower">Onu Sn : '.$onuSnAscii.' </p>
           <p id="rxPower">Onu Rx Power : - dBm</p>
           <p id="txPower">Onu Tx Power : - dBm</p>
           <p id="txPower">Onu Cable Length  : '.$onuDistanceValue.' m </p>
           <p id="txPower">Olt Rx Power : - dBm</p>
           <p id="rxPower">Onu Last Offline : '.$onuLastOfflineValue.' </p>
           <p id="txPower">Onu Last Online : '.$onuLastOnlineValue.' </p>
           <p id="txPower">Onu Uptime : - </p>
           </div>
           <div class="modal-footer">
           <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
           </div>
           </div>
           </div>
           </div>';
       }
       elseif ($result_status == "working")
       {

           $rxPowerValue = @$snmp->get($rxPowerOid);
           $txPowerValue = @$snmp->get($txPowerOid);
           $onuDistanceValue = str_replace(['INTEGER: ', '"'], "", @$snmp->get($onuDistance));
           $oltRxPowerValue = @$snmp->get($OltRxPowerOid);

           $onuNameValue = str_replace(['STRING: ', '"'], "", @$snmp->get($onuName));
           $onuModelValue = str_replace(['STRING: ', '"'], "", @$snmp->get($onuModel));
           $onuSnValue = str_replace(['Hex-STRING: ', '"'], "", @$snmp->get($onuSn));
           $onuSnAscii = $this->convertMacToAscii($onuSnValue);
           $onuLastOfflineValue = str_replace(['STRING: ', '"'], "", @$snmp->get($onuLastOffline));
           $onuLastOnlineValue = str_replace(['STRING: ', '"'], "", @$snmp->get($onuLastOnline));
           $onUptimeValue = str_replace(['Timeticks:', '"'], "", @$snmp->get($onuUptime));

           $RX = explode(' ', $rxPowerValue);
           $TX = explode(' ', $txPowerValue);
           $rxPowerValue = ((int)end($RX) * 0.002) - 30;
           $txPowerValue = ((int)end($TX) * 0.002) - 30;
           $OltRx = explode(' ', $oltRxPowerValue);
           $oltTxPowerValue = ((int)end($OltRx) * 0.002) + 30;
           $result_status= 'Rx: '.$rxPowerValue.' | Tx: '.$txPowerValue;

           echo '<button id="powerButton" class="btn bg-success btn-sm pb-1" data-toggle="modal" data-target="#powerModal'.$modalId.'">'.$result_status.'</button>

           <div class="modal fade" id="powerModal'.$modalId.'">
           <div class="modal-dialog">
           <div class="modal-content">
           <div class="modal-header">
           <h5 class="modal-title" id="powerModalLabel"><strong>Status ONU '.$olt->name .' '.$frameSlotPort.':'.$ontId.'</strong></h5>

           </div>
           <div class="modal-body">
           <p id="rxPower">Onu Name : '.$onuNameValue.'</p>
           <p id="rxPower">Onu Model : '.$onuModelValue.' </p>
           <p id="rxPower">Onu Sn : '.$onuSnAscii.' </p>
           <p id="rxPower">Onu Rx Power : '.$rxPowerValue.' dBm</p>
           <p id="txPower">Onu Tx Power : '.$txPowerValue.' dBm</p>
           <p id="txPower">Onu Cable Length  : '.$onuDistanceValue.' m </p>
           <p id="txPower">Olt Rx Power : '.$oltTxPowerValue.' dBm</p>
           <p id="rxPower">Onu Last Offline : '.$onuLastOfflineValue.' </p>
           <p id="txPower">Onu Last Online : '.$onuLastOnlineValue.' </p>
           <p id="txPower">Onu Uptime : '.$onUptimeValue.' </p>
           </div>
           <div class="modal-footer">
           <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
           </div>
           </div>
           </div>
           </div>';
       } 
       elseif ($result_status == "dyinggasp")
       {
           $onuNameValue = str_replace(['STRING: ', '"'], "", @$snmp->get($onuName));
           $onuModelValue = str_replace(['STRING: ', '"'], "", @$snmp->get($onuModel));
           $onuSnValue = str_replace(['Hex-STRING: ', '"'], "", @$snmp->get($onuSn));
           $onuSnAscii = $this->convertMacToAscii($onuSnValue);
           $onuLastOfflineValue = str_replace(['STRING: ', '"'], "", @$snmp->get($onuLastOffline));
           $onuLastOnlineValue = str_replace(['STRING: ', '"'], "", @$snmp->get($onuLastOnline));
           $onuDistanceValue = str_replace(['INTEGER: ', '"'], "", @$snmp->get($onuDistance));
           echo '<button id="powerButton" class="btn bg-warning btn-sm pb-1" data-toggle="modal" data-target="#powerModal'.$modalId.'">'.$result_status.'</button>
           <div class="modal fade" id="powerModal'.$modalId.'">
           <div class="modal-dialog">
           <div class="modal-content">
           <div class="modal-header">
           <h5 class="modal-title" id="powerModalLabel"><strong>Status ONU '.$olt->name .' '.$frameSlotPort.':'.$ontId.'</strong></h5>

           </div>
           <div class="modal-body">
           <p id="rxPower">Onu Name : '.$onuNameValue.'</p>
           <p id="rxPower">Onu Model : '.$onuModelValue.' </p>
           <p id="rxPower">Onu Sn : '.$onuSnAscii.' </p>
           <p id="rxPower">Onu Rx Power : - dBm</p>
           <p id="txPower">Onu Tx Power : - dBm</p>
           <p id="txPower">Onu Cable Length  : '.$onuDistanceValue.' m </p>
           <p id="txPower">Olt Rx Power : - dBm</p>
           <p id="rxPower">Onu Last Offline : '.$onuLastOfflineValue.' </p>
           <p id="txPower">Onu Last Online : '.$onuLastOnlineValue.' </p>
           <p id="txPower">Onu Uptime : - </p>
           </div>
           <div class="modal-footer">
           <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
           </div>
           </div>
           </div>
           </div>';
       }
       else
       {
        echo '<a class="badge-warning btn btn-sm  ml-2 mr-2 text-white  ">'.$result_status.'</a>';
    }



}


}





}
