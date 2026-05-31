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

        // Read-only ONT info + reboot bisa diakses accounting dari halaman customer.
        $this->middleware('checkPrivilege:admin,noc')->except(['ont_status', 'onu_detail', 'onureboot']);
        $this->middleware('checkPrivilege:admin,noc,accounting,marketing,user')->only(['ont_status']);
        $this->middleware('checkPrivilege:admin,noc,accounting')->only(['onu_detail', 'onureboot']);
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
    
    /**
     * Parse HSGQ ONU label from different firmware formats.
     * Supported examples: "ONU2/1", "ONT01/001".
     */
    private function parseHsgqOnuLabel($label)
    {
        $text = trim(str_replace(['STRING: ', '"'], '', (string) $label));

        if (preg_match('/ONU\s*(\d+)\s*\/(\d+)/i', $text, $m)) {
            return ['pon' => (int) $m[1], 'onu' => (int) $m[2]];
        }

        if (preg_match('/ONT\s*0*(\d+)\s*\/\s*0*(\d+)/i', $text, $m)) {
            return ['pon' => (int) $m[1], 'onu' => (int) $m[2]];
        }

        return null;
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

            $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip . ':' . ($olt->snmp_port ?? 161), $olt->community_ro);

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
            $hsgqUsingLegacyOnuSource = false;
            try {
                $onuNameRaw = $snmp->walk($onuName);
                $onuNameValue = is_array($onuNameRaw) ? array_map(function ($val) {
                    return str_replace(['STRING: ', '"'], "", $val);
                }, $onuNameRaw) : [];

                if ($isHSGQ) {
                    $hasParsedOnu = false;
                    foreach ($onuNameValue as $label) {
                        if ($this->parseHsgqOnuLabel($label)) {
                            $hasParsedOnu = true;
                            break;
                        }
                    }

                    if (!$hasParsedOnu) {
                        $legacyOnuNameOid = config('hsgq_oid.oidOnuName') ?: '.1.3.6.1.4.1.50224.3.12.2.1.2';
                        $legacyOnuWalk = @$snmp->walk($legacyOnuNameOid);
                        if (is_array($legacyOnuWalk) && count($legacyOnuWalk) > 0) {
                            $onuNameValue = array_map(function ($val) {
                                return str_replace(['STRING: ', '"'], "", $val);
                            }, $legacyOnuWalk);
                            $hsgqUsingLegacyOnuSource = true;
                        }
                    }
                }
            } catch (\Exception $e) {
                $onuNameValue = [];
            }

            $onuCount = count($onuNameValue);

            // Ambil SN + Model untuk ditampilkan di list dyinggasp/los/offline.
            // Keyed by tail "{encodedIndex}.{onuId}" untuk ZTE (HSGQ pakai ifIndex, skip).
            $snByTail    = [];
            $modelByTail = [];
            if (!$isHSGQ) {
                try {
                    $snOid  = $zteoid['oidOnuSn']    ?? null;
                    $mdlOid = $zteoid['oidOnuModel'] ?? null;
                    if ($snOid) {
                        $snRaw = @$snmp->walk($snOid) ?: [];
                        foreach ($snRaw as $k => $v) {
                            $p = explode('.', $k);
                            $tail = implode('.', array_slice($p, -2)); // {encodedIndex}.{onuId}
                            $hex  = preg_replace('/^(Hex-STRING|STRING|OCTET STRING):\s*/i', '', (string) $v);
                            $snByTail[$tail] = $this->decodeOnuSnHex($hex);
                        }
                    }
                    if ($mdlOid) {
                        $mdlRaw = @$snmp->walk($mdlOid) ?: [];
                        foreach ($mdlRaw as $k => $v) {
                            $p = explode('.', $k);
                            $tail = implode('.', array_slice($p, -2));
                            $val  = preg_replace('/^(STRING|OCTET STRING):\s*/i', '', (string) $v);
                            $modelByTail[$tail] = trim($val, " \t\"'");
                        }
                    }
                } catch (\Exception $e) {
                    // best-effort, ignore
                }
            }

        // Proses status masing-masing ONU
            foreach ($onuNameValue as $key => $onuNameEntry) {
                $components = explode('.', $key);
                
                if ($isHSGQ) {
                    $parsedOnu = $this->parseHsgqOnuLabel($onuNameEntry);
                    if (!$parsedOnu) {
                        continue;
                    }

                    $ponNum = $parsedOnu['pon'];
                    $onuId = $parsedOnu['onu'];

                    // Get ifIndex from OID key (works for IF-MIB based sources)
                    $ifIndex = (int)end($components);

                    // Read IF-MIB status when available (legacy .50224 source usually has no matching ifIndex)
                    $statusValue = '';
                    if (!$hsgqUsingLegacyOnuSource) {
                        $statusOid = $onuStatus . '.' . $ifIndex;
                        $statusValue = $this->safeSnmpGet($snmp, $statusOid);
                    }

                    $encodedIndex = encode_hsgq_index($ponNum, $onuId);
                    $hsgqMetricOid = $hsgqUsingLegacyOnuSource ? (config('hsgq_oid') ?: $zteoid) : $zteoid;
                    $rxPowerOid = ($hsgqMetricOid['oidOnuRxPower'] ?? $zteoid['oidOnuRxPower']) . '.' . $encodedIndex . '.0.0';
                    $txPowerOid = ($hsgqMetricOid['oidOnuTxPowerOnu'] ?? $zteoid['oidOnuTxPowerOnu']) . '.' . $encodedIndex . '.0.0';

                    $rxPowerValue = $this->safeSnmpGet($snmp, $rxPowerOid);
                    $txPowerValue = $this->safeSnmpGet($snmp, $txPowerOid);

                    $rxValid = $rxPowerValue && !str_contains($rxPowerValue, 'No Such') && !str_contains($rxPowerValue, 'N/A');
                    $txValid = $txPowerValue && !str_contains($txPowerValue, 'No Such') && !str_contains($txPowerValue, 'N/A');

                    $rxNumeric = null;
                    if ($rxValid && preg_match('/-?\d+/', $rxPowerValue, $rxMatch)) {
                        $rxNumeric = (int)$rxMatch[0];
                    }

                    if ($hsgqUsingLegacyOnuSource) {
                        // Legacy .50224 source: derive state from optics only.
                        if (!$rxValid && !$txValid) {
                            $statusValue = '5'; // powerdown
                        } elseif (!$rxValid || !$txValid) {
                            $statusValue = '4'; // los
                        } elseif ($rxNumeric !== null && $rxNumeric < -2800) {
                            $statusValue = '4'; // los (too weak)
                        } else {
                            $statusValue = '3'; // working
                        }
                    } elseif (preg_match('/\b(1|up)\b/', (string) $statusValue)) {
                        if (!$rxValid && !$txValid) {
                            $statusValue = '5';
                        } elseif (!$rxValid || !$txValid) {
                            $statusValue = '4';
                        } elseif ($rxNumeric !== null && $rxNumeric < -2800) {
                            $statusValue = '4';
                        } else {
                            $statusValue = '3';
                        }
                    } else {
                        if (!$rxValid && !$txValid) {
                            $statusValue = '5';
                        } elseif (!$rxValid || !$txValid) {
                            $statusValue = '4';
                        } elseif ($rxNumeric !== null && $rxNumeric < -2800) {
                            $statusValue = '4';
                        } else {
                            $statusValue = '6';
                        }
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


                // Tail key untuk lookup SN/Model dari walk hasil sebelumnya (ZTE saja).
                $tailKey = '';
                if (!$isHSGQ) {
                    if ($isC600Series && isset($encodedIndex, $onuId)) {
                        $tailKey = $encodedIndex . '.' . $onuId;
                    } elseif (!$isC600Series && isset($lastTwo[0], $lastTwo[1])) {
                        $tailKey = $lastTwo[0] . '.' . $lastTwo[1];
                    }
                }
                $snVal    = $snByTail[$tailKey]    ?? '';
                $modelVal = $modelByTail[$tailKey] ?? '';

                $entryBase = [
                    'onuName' => $onuNameEntry,
                    'Id'      => str_replace('\\', '', $onuid),
                    'sn'      => $snVal,
                    'model'   => $modelVal,
                ];

                switch ($result_status) {
                    case "working":
                    $working++;
                    break;
                    case "los":
                    $los++;
                    $loslist[] = $entryBase;
                    break;
                    case "powerdown":
                    $powerdown++;
                    $powerdownlist[] = $entryBase;
                    break;
                    case "dyinggasp":
                    $dyinggasp++;
                    $dyinggasplist[] = $entryBase;
                    break;
                    case "logging":
                    $logging++;
                    break;
                    case "offline":
                    $offline++;
                    $offlinelist[] = $entryBase;
                    break;
                    default:
                    $unknow++;
                    $unknowlist[] = $entryBase;
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

    // Detect C600 / C620 / C650 family (CLI interface naming differs from C300).
    $oltType = strtolower((string) ($olt->type ?? ''));
    $oltName = strtolower((string) ($olt->name ?? ''));
    $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
                    str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');

    if ($isC600Series) {
        // C600 CLI: gpon_olt-F/S/P  &  gpon_onu-F/S/P
        $pon_int = 'gpon_olt-' . $parts_int[0];
        $onu_int = 'gpon_onu-' . $parts_int[0];
    } else {
        // C300 CLI: gpon-olt_F/S/P  &  gpon-onu_F/S/P
        $pon_int = 'gpon-olt_' . $parts_int[0];
        $onu_int = 'gpon-onu_' . $parts_int[0];
    }
    $name = $request->customer_id.' '.$request->customer_name;
    $onu_num = $request->onu_id;
    $sn = $parts_int[1];
    $onutype = $request->onu_type;

    $parts_vlan = explode(':', $request->onu_profile);
    $vlanname = $parts_vlan[0];
    $vlan = strval($parts_vlan[1]);

    $username_pppoe = $request->pppoe;
    $password_pppoe = $request->password;
    $description = 'Config by System';

    $tconprofile = $request->tcon_profile;
    $gemportprofileup = $request->gemport_profile;
    $gemportprofiledown = $request->gemport_profile;




    $scriptName = $isC600Series ? 'addontconf_c600.py' : 'addontconf.py';
    $process = new Process(["python3", env("PHYTON_DIR") . $scriptName,
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

    $username_pppoe = $request->pppoe;
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

    // Detect C600 / C620 / C650 — uses gpon_olt-F/S/P naming and slash-format PON path.
    $oltType = strtolower((string) ($olt->type ?? ''));
    $oltName = strtolower((string) ($olt->name ?? ''));
    $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
                    str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');

    // C600 PON index sent with underscores via URL (to avoid Laravel route slash splitting).
    if ($isC600Series && str_contains($oltPonIndex, '_') && !str_contains($oltPonIndex, '/')) {
        $oltPonIndex = str_replace('_', '/', $oltPonIndex);
    }

    $onu_num = $onuId;

    if ($isC600Series) {
        // For C600 the PON path is already "frame/slot/port"; pass as-is to script.
        $pon_int = $oltPonIndex;
        $script = 'delontconf_c600.py';
    } else {
        // C300: map numeric oltPonIndex -> "frame/slot/port" via config.
        $frameslotportid = config('zteframeslotportid');
        $pon_int = array_search($oltPonIndex, $frameslotportid);
        $script = 'delontconf.py';
    }




    $process = new Process(["python3", env("PHYTON_DIR") . $script,
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
    \Log::info('[REBOOT] onureboot called', ['oltId' => $oltId, 'oltPonIndex' => $oltPonIndex, 'onuId' => $onuId]);

    $olt = \App\Olt::findOrFail($oltId);
    
    // Detect vendor type
    $oltVendor = strtolower($olt->vendor ?? '');
    $oltType = strtolower($olt->type ?? '');
    $oltName = strtolower($olt->name ?? '');
    
    $isHSGQ = str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq');
    $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
                    str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');

    // C600/C620/C650 PON index may be sent with underscores to avoid route slash splitting.
    if ($isC600Series && str_contains($oltPonIndex, '_') && !str_contains($oltPonIndex, '/')) {
        $oltPonIndex = str_replace('_', '/', $oltPonIndex);
    }

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
            $port = (string)($olt->port ?? 23);
            $timeout = '10';
            
            // Execute Python script for HSGQ ONU reboot via Telnet
            $scriptPath = env("PHYTON_DIR")."reboot_hsgq_ont.py";
            \Log::info('[REBOOT] Running python script', ['script' => $scriptPath, 'ip' => $ip, 'pon' => $ponNum, 'onu' => $onuNum]);
            
            $processReboot = new Process(["python3", $scriptPath, 
                $ip, $login, $password, $port, $timeout, 
                (string)$ponNum, (string)$onuNum]);
            
            // Set timeout to 60 seconds
            $processReboot->setTimeout(60);
            
            $processReboot->run();
            
            \Log::info('[REBOOT] Python script finished', [
                'exitCode' => $processReboot->getExitCode(),
                'output' => $processReboot->getOutput(),
                'errorOutput' => $processReboot->getErrorOutput(),
            ]);
            
            if (!$processReboot->isSuccessful()) {
                throw new ProcessFailedException($processReboot);
            }
            
            $message = trim($processReboot->getOutput());
            // Get only the last meaningful line (result line), not debug/timestamp lines
            $lines = array_filter(array_map('trim', explode("\n", $message)));
            $lastLine = end($lines);
            $parts = explode(":", $lastLine, 2);
            if (count($parts) < 2) {
                return redirect()->back()->with('warning', 'Reboot command sent: ' . $lastLine);
            }
            return redirect()->back()->with(trim($parts[0]), trim($parts[1]));
            
        } catch (ProcessFailedException $e) {
            return redirect()->back()->with('error', 'Reboot error: ' . $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Reboot error: ' . $e->getMessage());
        }
    }
    
    // ZTE: Use Telnet/CLI method
    $ip = $olt->ip;
    $login = $olt->user;
    $password = $olt->password;
    $port = (string)($olt->port ?? 23);
    $timeout = 10;

    if ($isC600Series) {
        try {
            $ponParts = explode('/', $oltPonIndex);
            if (count($ponParts) !== 3) {
                return redirect()->back()->with('error', 'Invalid C600 PON format: ' . $oltPonIndex);
            }

            [$shelf, $slot, $ponPort] = $ponParts;

            $processRebootC600 = new Process([
                "python3",
                env("PHYTON_DIR") . "reboot_zte_c600_ont.py",
                $ip,
                $login,
                $password,
                $port,
                (string)$timeout,
                (string)$shelf,
                (string)$slot,
                (string)$ponPort,
                (string)$onuId,
            ]);

            $processRebootC600->setTimeout(90);
            $processRebootC600->run();

            if (!$processRebootC600->isSuccessful()) {
                throw new ProcessFailedException($processRebootC600);
            }

            $output = trim((string)$processRebootC600->getOutput());
            $lines = array_values(array_filter(array_map('trim', explode("\n", $output))));

            $resultLine = '';
            foreach ($lines as $line) {
                if (stripos($line, 'success:') === 0 || stripos($line, 'error:') === 0) {
                    $resultLine = $line;
                }
            }

            if ($resultLine === '' && !empty($lines)) {
                $resultLine = end($lines);
            }

            $parts = explode(":", $resultLine, 2);
            if (count($parts) < 2) {
                return redirect()->back()->with('warning', 'Reboot command sent: ' . ($resultLine ?: 'No response from script'));
            }

            return redirect()->back()->with(trim($parts[0]), trim($parts[1]));
        } catch (ProcessFailedException $e) {
            $stderr = trim($e->getProcess()->getErrorOutput());
            $stdout = trim($e->getProcess()->getOutput());
            $detail = $stderr ?: ($stdout ?: $e->getMessage());
            return redirect()->back()->with('error', 'Reboot C600 error: ' . $detail);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Reboot C600 error: ' . $e->getMessage());
        }
    }

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

    // Detect vendor type
    $oltVendor = strtolower($olt->vendor ?? '');
    $oltType = strtolower($olt->type ?? '');
    $oltName = strtolower($olt->name ?? '');
    $isHSGQ = str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq');

    if ($isHSGQ) {
        // HSGQ: Use SNMP to get ONU detail (no CLI command available for detail)
        try {
            $zteoid = get_olt_oid_config($olt);
            $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip . ':' . ($olt->snmp_port ?? 161), $olt->community_ro);
            
            // Parse id_onu format "PON:ONU" (e.g., "2:1")
            list($ponNum, $onuIdNum) = explode(':', $id_onu);
            $ponNum = (int)$ponNum;
            $onuIdNum = (int)$onuIdNum;
            $encodedIndex = encode_hsgq_index($ponNum, $onuIdNum);
            $powerDivisor = $zteoid['powerDivisor'] ?? 100;
            
            // Fetch all ONU info via SNMP
            $onuNameVal = str_replace(['STRING: ', '"'], '', $this->safeSnmpGet($snmp, $zteoid['oidOnuName'] . ".$encodedIndex"));
            $onuVendorVal = str_replace(['STRING: ', '"'], '', $this->safeSnmpGet($snmp, $zteoid['oidOnuVendor'] . ".$encodedIndex"));
            $onuModelVal = str_replace(['STRING: ', '"'], '', $this->safeSnmpGet($snmp, $zteoid['oidOnuModel'] . ".$encodedIndex"));
            $onuSnRaw = str_replace(['Hex-STRING: ', 'STRING: ', '"'], '', $this->safeSnmpGet($snmp, $zteoid['oidOnuSn'] . ".$encodedIndex"));
            $onuSnVal = $this->convertMacToAscii($onuSnRaw);
            $onuUptimeRaw = str_replace(['Timeticks:', '"'], '', $this->safeSnmpGet($snmp, $zteoid['oidOnuUptime'] . ".$encodedIndex"));
            // Format timeticks: extract "(NNNN) H:MM:SS.xx" -> convert to readable
            $onuUptimeVal = '-';
            if (preg_match('/\((\d+)\)/', $onuUptimeRaw, $tm)) {
                $totalSeconds = (int)($tm[1] / 100);
                $days = intdiv($totalSeconds, 86400);
                $hours = intdiv($totalSeconds % 86400, 3600);
                $mins = intdiv($totalSeconds % 3600, 60);
                $secs = $totalSeconds % 60;
                $onuUptimeVal = ($days > 0 ? "{$days}d " : '') . "{$hours}h {$mins}m {$secs}s";
            }
            $onuLastRegVal = str_replace(['STRING: ', 'Timeticks:', '"'], '', $this->safeSnmpGet($snmp, $zteoid['oidOnuLastReg'] . ".$encodedIndex"));
            
            // Optics
            $rxPowerRaw = $this->safeSnmpGet($snmp, $zteoid['oidOnuRxPower'] . ".$encodedIndex.0.0");
            $txPowerRaw = $this->safeSnmpGet($snmp, $zteoid['oidOnuTxPowerOnu'] . ".$encodedIndex.0.0");
            $distanceRaw = str_replace(['INTEGER: ', '"'], '', $this->safeSnmpGet($snmp, $zteoid['oidOnuDistance'] . ".$encodedIndex.0.0"));
            $voltageRaw = str_replace(['INTEGER: ', '"'], '', $this->safeSnmpGet($snmp, ($zteoid['oidOnuVoltage'] ?? '') . ".$encodedIndex.0.0"));
            $tempRaw = str_replace(['INTEGER: ', '"'], '', $this->safeSnmpGet($snmp, ($zteoid['oidOnuTemperature'] ?? '') . ".$encodedIndex.0.0"));
            
            // Parse power values
            $rxDbm = '-';
            if ($rxPowerRaw && !str_contains($rxPowerRaw, 'No Such')) {
                if (preg_match('/-?\d+/', $rxPowerRaw, $m)) {
                    $rxDbm = round((int)$m[0] / $powerDivisor, 2) . ' dBm';
                }
            }
            $txDbm = '-';
            if ($txPowerRaw && !str_contains($txPowerRaw, 'No Such')) {
                if (preg_match('/-?\d+/', $txPowerRaw, $m)) {
                    $txDbm = round((int)$m[0] / $powerDivisor, 2) . ' dBm';
                }
            }
            
            // Get IF-MIB status
            $ifDescrWalk = @$snmp->walk($zteoid['oidIfDescr']);
            $ifStatus = 'Unknown';
            if ($ifDescrWalk) {
                foreach ($ifDescrWalk as $oid => $val) {
                    $desc = str_replace(['STRING: ', '"'], '', $val);
                    if (preg_match('/ONU' . $ponNum . '\/' . $onuIdNum . '$/', $desc)) {
                        $parts = explode('.', $oid);
                        $ifIdx = (int)end($parts);
                        $statusRaw = $this->safeSnmpGet($snmp, $zteoid['oidIfOperStatus'] . '.' . $ifIdx);
                        if (preg_match('/\b(1|up)\b/', $statusRaw)) {
                            $ifStatus = '<span class="badge badge-success">Online</span>';
                        } else {
                            $ifStatus = '<span class="badge badge-danger">Offline</span>';
                        }
                        break;
                    }
                }
            }
            
            // Build HTML output
            $html = '<div class="table-responsive">';
            $html .= '<table class="table table-sm table-bordered">';
            $html .= '<tr><th colspan="2" class="bg-primary text-white">ONU Information - HSGQ GPON</th></tr>';
            $html .= '<tr><td><strong>ONU ID</strong></td><td>PON' . $ponNum . '/' . $onuIdNum . '</td></tr>';
            $html .= '<tr><td><strong>Status</strong></td><td>' . $ifStatus . '</td></tr>';
            $html .= '<tr><td><strong>Name/Description</strong></td><td>' . ($onuNameVal ?: '-') . '</td></tr>';
            $html .= '<tr><td><strong>Vendor</strong></td><td>' . ($onuVendorVal ?: '-') . '</td></tr>';
            $html .= '<tr><td><strong>Model</strong></td><td>' . ($onuModelVal ?: '-') . '</td></tr>';
            $html .= '<tr><td><strong>Serial Number</strong></td><td>' . ($onuSnVal ?: '-') . '</td></tr>';
            $html .= '<tr><th colspan="2" class="bg-info text-white">Optical Information</th></tr>';
            $html .= '<tr><td><strong>ONU RX Power</strong></td><td>' . $rxDbm . '</td></tr>';
            $html .= '<tr><td><strong>ONU TX Power</strong></td><td>' . $txDbm . '</td></tr>';
            $html .= '<tr><td><strong>Distance</strong></td><td>' . ($distanceRaw ?: '-') . ' m</td></tr>';
            $html .= '<tr><td><strong>Voltage</strong></td><td>' . ($voltageRaw ?: '-') . '</td></tr>';
            $html .= '<tr><td><strong>Temperature</strong></td><td>' . ($tempRaw ?: '-') . '</td></tr>';
            $html .= '<tr><th colspan="2" class="bg-secondary text-white">Registration</th></tr>';
            $html .= '<tr><td><strong>Uptime</strong></td><td>' . ($onuUptimeVal ?: '-') . '</td></tr>';
            $html .= '<tr><td><strong>Last Registration</strong></td><td>' . ($onuLastRegVal ?: '-') . '</td></tr>';
            $html .= '</table>';
            $html .= '</div>';
            
            echo $html;
            return;
            
        } catch (\Exception $e) {
            echo '<div class="alert alert-danger">Error fetching HSGQ ONU detail: ' . htmlspecialchars($e->getMessage()) . '</div>';
            return;
        }
    }

    // Detect if C600 or C300
    $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
            str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');
    $oltTypeParam = $isC600Series ? 'c600' : 'c300';

    $processreboot = new Process(["python3", env("PHYTON_DIR")."onudetail.py", 
        $ip, $login, $password, $port, $timeout, 
        $id_onu, $oltTypeParam]);
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
        $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip . ':' . ($olt->snmp_port ?? 161), $olt->community_ro);

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
                                        $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip . ':' . ($olt->snmp_port ?? 161), $olt->community_ro);

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
                                            $hasParsedOnu = false;
                                            foreach ($result as $key => $onuName) {
                // Memisahkan kunci OID berdasarkan titik (.)
                                                $parts = explode('.', $key);

                                                if ($isHSGQ) {
                                                    $parsedOnu = $this->parseHsgqOnuLabel($onuName);
                                                    if ($parsedOnu) {
                                                        $ponNum = $parsedOnu['pon'];
                                                        $suffix = $ponNum; // Use PON number as suffix
                                                        $oltPon = $ponNum;  // Direct PON number (1-16)
                                                        $portKey = null;    // Not used for HSGQ
                                                        $hasParsedOnu = true;
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
                                                        
                                                        // Use full frame/slot/port so each PON port appears
                                                        // as a distinct combobox option.
                                                        $suffix = $portKey;
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

                                            // Fallback: some HSGQ devices expose ONU list on enterprise OID (.50224)
                                            if ($isHSGQ && !$hasParsedOnu) {
                                                $legacyOnuNameOid = config('hsgq_oid.oidOnuName') ?: '.1.3.6.1.4.1.50224.3.12.2.1.2';
                                                $legacyResult = @$snmp->walk($legacyOnuNameOid);
                                                if (is_array($legacyResult)) {
                                                    foreach ($legacyResult as $legacyOnuName) {
                                                        $parsedOnu = $this->parseHsgqOnuLabel($legacyOnuName);
                                                        if (!$parsedOnu) {
                                                            continue;
                                                        }

                                                        $ponNum = $parsedOnu['pon'];
                                                        $suffix = $ponNum;
                                                        if (!in_array($suffix, $processedSuffixes)) {
                                                            $processedSuffixes[] = $suffix;
                                                            $data[] = [
                                                                'olt_pon' => $ponNum,
                                                                'suffix' => $suffix,
                                                            ];
                                                        }
                                                    }
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





    /**
     * Search ONU by Name/SN across all PONs of a single OLT.
     * Walk SNMP oidOnuName + oidOnuSn once, decode, filter, return matches.
     *
     * Supported vendors: ZTE C300/C320 dan C600/C620/C650 series.
     * Untuk HSGQ akan return notice "belum didukung".
     *
     * POST params:
     *   - olt_id : OLT primary key
     *   - q      : search keyword (matched against SN, Name, ONU ID, PON)
     *
     * Response: JSON { success, data: [{pon, onu_id, sn, name, status}], total, message }
     */
    public function searchOnu(Request $request)
    {
        $oltId = $request->input('olt_id');
        $q     = trim((string) $request->input('q', ''));

        if ($oltId === null || $q === '') {
            return response()->json([
                'success' => false,
                'message' => 'olt_id dan q (keyword) wajib diisi.',
                'data'    => [],
                'total'   => 0,
            ], 422);
        }

        if (mb_strlen($q) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Keyword minimal 2 karakter.',
                'data'    => [],
                'total'   => 0,
            ], 422);
        }

        try {
            $olt    = \App\Olt::findOrFail($oltId);
            $zteoid = get_olt_oid_config($olt);
            $ontStatuses = get_olt_status_config($olt);

            $oltVendor = strtolower($olt->vendor ?? '');
            $oltType   = strtolower($olt->type ?? '');
            $oltName   = strtolower($olt->name ?? '');

            $isHSGQ = str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq');
            $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650')
                         || str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');
            $isC300Series = !$isC600Series && !$isHSGQ
                         && (str_contains($oltType, 'c300') || str_contains($oltType, 'c320')
                          || str_contains($oltName, 'c300') || str_contains($oltName, 'c320')
                          || $oltVendor === 'zte');

            if (!$isC600Series && !$isC300Series) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search by Name/SN saat ini hanya didukung untuk OLT seri ZTE C300/C320/C600/C620/C650.',
                    'data'    => [],
                    'total'   => 0,
                ], 200);
            }

            @ini_set('max_execution_time', 120);

            $host = $olt->ip . ':' . ($olt->snmp_port ?? 161);
            $snmp = new \SNMP(\SNMP::VERSION_2c, $host, $olt->community_ro);
            // NOTE: do NOT use SNMP_VALUE_PLAIN — we need Hex-STRING format for SN decoding.
            $snmp->oid_output_format = SNMP_OID_OUTPUT_NUMERIC;

            $names = @$snmp->walk($zteoid['oidOnuName']) ?: [];
            $sns   = @$snmp->walk($zteoid['oidOnuSn']) ?: [];
            // Walk RX/TX power so we can show them when ONU is 'working'.
            // Key format: "{oidOnuRxPower}.{encodedIndex}.{onuId}.1" (same for TX).
            $rxWalk = !empty($zteoid['oidOnuRxPower']) ? (@$snmp->walk($zteoid['oidOnuRxPower']) ?: []) : [];
            $txWalk = !empty($zteoid['oidOnuTxPower']) ? (@$snmp->walk($zteoid['oidOnuTxPower']) ?: []) : [];

            // For C300, build reverse lookup encoded → frame/slot/port
            $frameslotportid    = $isC300Series ? (get_olt_frameslotport_config($olt) ?: []) : [];
            $encodedToPon       = $isC300Series ? array_flip($frameslotportid) : [];

            $qLower      = mb_strtolower($q);
            $qUpperHex   = strtoupper(preg_replace('/[^a-fA-F0-9]/', '', $q));
            $customers   = \App\Customer::where('id_olt', $olt->id)->get();

            $rows = [];

            // Iterate names (each key ends with ".<encodedIndex>.<onuId>")
            foreach ($names as $oidKey => $rawName) {
                $parts = explode('.', $oidKey);
                if (count($parts) < 2) continue;

                $onuId         = end($parts);
                $encodedIndex  = (string) prev($parts);
                $tail          = $encodedIndex . '.' . $onuId;

                // Strip SNMP prefix + quotes from name
                $nameVal = preg_replace('/^(STRING|OCTET STRING):\s*/i', '', (string) $rawName);
                $nameVal = trim($nameVal, " \t\"'");

                // Decode SN hex → ascii prefix + hex serial (e.g. ZTEG + 4-byte hex)
                $snRaw = $sns[$zteoid['oidOnuSn'] . '.' . $tail] ?? '';
                $snRaw = preg_replace('/^(Hex-STRING|STRING|OCTET STRING):\s*/i', '', (string) $snRaw);
                $snDisplay = $this->decodeOnuSnHex($snRaw);

                // Decode PON
                if ($isC600Series) {
                    $idx   = (int) $encodedIndex;
                    $port  = ($idx >> 0)  & 0xFF;
                    $slot  = ($idx >> 8)  & 0xFF;
                    $frame = ($idx >> 16) & 0xFF;
                    $pon   = $frame . '/' . $slot . '/' . $port;
                } else {
                    // C300: use lookup table
                    $pon = $encodedToPon[$encodedIndex] ?? null;
                    if (!$pon) continue; // unknown port encoding
                }

                // Match filter
                $hay = mb_strtolower(implode(' ', [
                    $nameVal,
                    $snDisplay,
                    $snRaw,
                    $pon,
                    (string) $onuId,
                ]));

                $matched = (mb_strpos($hay, $qLower) !== false);
                if (!$matched && $qUpperHex !== '' && strlen($qUpperHex) >= 4) {
                    $matched = (strpos(strtoupper($snDisplay), $qUpperHex) !== false);
                }
                if (!$matched) continue;

                // Status (best-effort, no fail).
                // ontStatuses keys may be "INTEGER: 4" (full prefix) or plain "4" depending on OLT type.
                // For C300, status OID indexing is .{encoded}.{onuId}; for C600 same pattern.
                $statusRaw  = (string) @$snmp->get($zteoid['oidOnuStatus'] . '.' . $tail);
                $statusText = 'Unknown';
                if ($statusRaw !== '') {
                    if (isset($ontStatuses[$statusRaw])) {
                        $statusText = $ontStatuses[$statusRaw];
                    } elseif (preg_match('/(\d+)/', $statusRaw, $m)) {
                        $statusText = $ontStatuses[$m[1]]
                            ?? $ontStatuses[(int) $m[1]]
                            ?? $ontStatuses['INTEGER: ' . $m[1]]
                            ?? 'Unknown';
                    }
                }

                $customer = $customers->firstWhere('id_onu', "$pon:$onuId");

                // If ONU is working, fetch RX/TX from the walk (avoid extra SNMP gets).
                $rxDbm = null;
                $txDbm = null;
                if (stripos($statusText, 'working') !== false || stripos($statusText, 'online') !== false) {
                    $rxKey = ($zteoid['oidOnuRxPower'] ?? '') . '.' . $tail . '.1';
                    $txKey = ($zteoid['oidOnuTxPower'] ?? '') . '.' . $tail . '.1';
                    $rxRaw = $rxWalk[$rxKey] ?? ($rxWalk[($zteoid['oidOnuRxPower'] ?? '') . '.' . $tail] ?? '');
                    $txRaw = $txWalk[$txKey] ?? ($txWalk[($zteoid['oidOnuTxPower'] ?? '') . '.' . $tail] ?? '');
                    if ($rxRaw !== '' && preg_match('/-?\d+/', (string) $rxRaw, $mr)) {
                        $r = (int) $mr[0];
                        if ($r > 0 && $r < 65535) $rxDbm = round(($r * 0.002) - 30.0, 2);
                    }
                    if ($txRaw !== '' && preg_match('/-?\d+/', (string) $txRaw, $mt)) {
                        $t = (int) $mt[0];
                        if ($t > 0 && $t < 65535) $txDbm = round(($t * 0.002) - 30.0, 2);
                    }
                }

                $rows[] = [
                    'pon'         => $pon,
                    'onu_id'      => $onuId,
                    'sn'          => $snDisplay,
                    'name'        => $nameVal,
                    'status'      => $statusText,
                    'rx_dbm'      => $rxDbm,
                    'tx_dbm'      => $txDbm,
                    'customer'    => $customer ? ($customer->name ?? '') : '',
                    'customer_id' => $customer ? ($customer->id ?? null) : null,
                ];

                if (count($rows) >= 200) break; // hard cap
            }

            $snmp->close();

            return response()->json([
                'success' => true,
                'message' => count($rows) . ' ONU ditemukan.',
                'data'    => $rows,
                'total'   => count($rows),
            ]);

        } catch (\Throwable $e) {
            \Log::error('[OLT searchOnu] ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data'    => [],
                'total'   => 0,
            ], 500);
        }
    }

    /**
     * Decode SN ZTE dari hex bytes → display string.
     * Input contoh: "5A 54 45 47 D7 75 4B F7" atau "5A:54:45:47:D7:75:4B:F7" atau raw 8 bytes.
     * Output contoh: "ZTEGD7754BF7" (4 char ASCII vendor + 8 char hex serial).
     * Jika tidak dapat di-parse, kembalikan input apa adanya (uppercase, no spasi).
     */
    private function decodeOnuSnHex(string $hex): string
    {
        $clean = strtoupper(preg_replace('/[^a-fA-F0-9]/', '', $hex));
        if (strlen($clean) !== 16) {
            return $clean !== '' ? $clean : $hex;
        }

        $bytes = str_split($clean, 2);
        // First 4 bytes: ASCII vendor (e.g., 5A 54 45 47 = "ZTEG")
        $vendor = '';
        for ($i = 0; $i < 4; $i++) {
            $byte = hexdec($bytes[$i]);
            $vendor .= ($byte >= 0x20 && $byte <= 0x7E) ? chr($byte) : '';
        }
        // Last 4 bytes: hex serial
        $serial = $bytes[4] . $bytes[5] . $bytes[6] . $bytes[7];

        return $vendor !== '' ? ($vendor . $serial) : $clean;
    }

    /**
     * Health Dashboard: Top-N ONU dengan RX Power terburuk (paling kecil dBm).
     * Vendor: ZTE C300/C320 dan C600/C620/C650.
     *
     * GET /olt/health/top-rx/{id}?limit=10
     *
     * Response: JSON {
     *   success, generated_at, total_scanned, threshold_warn,
     *   data: [{ pon, onu_id, name, sn, rx_dbm, customer, customer_id }],
     * }
     */
    public function oltTopWorstRx($id, Request $request)
    {
        try {
            $olt    = \App\Olt::findOrFail($id);
            $zteoid = get_olt_oid_config($olt);
            $limit  = max(1, min(50, (int) $request->input('limit', 10)));

            $oltVendor = strtolower($olt->vendor ?? '');
            $oltType   = strtolower($olt->type ?? '');
            $oltName   = strtolower($olt->name ?? '');

            $isHSGQ = str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq');
            $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650')
                         || str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');
            $isC300Series = !$isC600Series && !$isHSGQ
                         && (str_contains($oltType, 'c300') || str_contains($oltType, 'c320')
                          || str_contains($oltName, 'c300') || str_contains($oltName, 'c320')
                          || $oltVendor === 'zte');

            if (!$isC600Series && !$isC300Series) {
                return response()->json([
                    'success' => false,
                    'message' => 'Top RX dashboard saat ini hanya didukung untuk OLT seri ZTE C300/C320/C600/C620/C650.',
                    'data'    => [],
                ], 200);
            }

            @ini_set('max_execution_time', 120);

            $host = $olt->ip . ':' . ($olt->snmp_port ?? 161);
            // 5s per request, 2 retries — needed for C300 with many ONUs.
            $snmp = new \SNMP(\SNMP::VERSION_2c, $host, $olt->community_ro, 5000000, 2);
            $snmp->oid_output_format = SNMP_OID_OUTPUT_NUMERIC;

            $rxWalk    = @$snmp->walk($zteoid['oidOnuRxPower']) ?: [];
            $names     = @$snmp->walk($zteoid['oidOnuName']) ?: [];
            $sns       = @$snmp->walk($zteoid['oidOnuSn']) ?: [];

            // For C300, build reverse lookup encoded → frame/slot/port
            $frameslotportid = $isC300Series ? (get_olt_frameslotport_config($olt) ?: []) : [];
            $encodedToPon    = $isC300Series ? array_flip($frameslotportid) : [];

            $customers = \App\Customer::where('id_olt', $olt->id)->get();

            $rows         = [];
            $totalScanned = 0;

            foreach ($rxWalk as $oidKey => $rawRx) {
                // RX OID tail format: "{...}.{encodedIndex}.{onuId}.1"
                $parts = explode('.', $oidKey);
                if (count($parts) < 3) continue;

                // Strip trailing ".1" subindex if present
                $tailParts = array_slice($parts, -3);
                if ((string) end($tailParts) === '1') {
                    $onuId        = (string) $tailParts[1];
                    $encodedIndex = (string) $tailParts[0];
                } else {
                    // Some firmwares may already omit the trailing .1
                    $onuId        = (string) end($tailParts);
                    $encodedIndex = (string) prev($tailParts);
                }
                $tailKey = $encodedIndex . '.' . $onuId;

                // Decode raw → dBm. ZTE formula: (raw * 0.002) - 30
                if (!preg_match('/-?\d+/', (string) $rawRx, $m)) continue;
                $raw = (int) $m[0];
                // Invalid / offline markers
                if ($raw >= 65535 || $raw <= 0) {
                    continue;
                }
                $rxDbm = ($raw * 0.002) - 30.0;
                $totalScanned++;

                // Decode PON label
                if ($isC600Series) {
                    $idx   = (int) $encodedIndex;
                    $port  = ($idx >> 0)  & 0xFF;
                    $slot  = ($idx >> 8)  & 0xFF;
                    $frame = ($idx >> 16) & 0xFF;
                    $pon   = $frame . '/' . $slot . '/' . $port;
                } else {
                    $pon = $encodedToPon[$encodedIndex] ?? null;
                    if (!$pon) continue;
                }

                // Lookup name + SN by tailKey
                $nameRaw = $names[$zteoid['oidOnuName'] . '.' . $tailKey] ?? '';
                $name    = trim(preg_replace('/^(STRING|OCTET STRING):\s*/i', '', (string) $nameRaw), " \t\"'");

                $snRawFull = $sns[$zteoid['oidOnuSn'] . '.' . $tailKey] ?? '';
                $snHex     = preg_replace('/^(Hex-STRING|STRING|OCTET STRING):\s*/i', '', (string) $snRawFull);
                $snDisplay = $this->decodeOnuSnHex($snHex);

                $customer = $customers->firstWhere('id_onu', "$pon:$onuId");

                $rows[] = [
                    'pon'         => $pon,
                    'onu_id'      => $onuId,
                    'name'        => $name,
                    'sn'          => $snDisplay,
                    'rx_dbm'      => round($rxDbm, 2),
                    'customer'    => $customer ? ($customer->name ?? '') : '',
                    'customer_id' => $customer ? ($customer->id ?? null) : null,
                ];
            }

            // Sort ascending by rx_dbm (most negative = worst first)
            usort($rows, function ($a, $b) {
                return $a['rx_dbm'] <=> $b['rx_dbm'];
            });

            $top = array_slice($rows, 0, $limit);

            $snmp->close();

            return response()->json([
                'success'        => true,
                'generated_at'   => now()->toDateTimeString(),
                'total_scanned'  => $totalScanned,
                'threshold_warn' => -25.0, // dBm — anything ≤ -25 dBm = perlu perhatian
                'threshold_crit' => -27.0, // dBm — ≤ -27 dBm = kritis (mendekati LOS)
                'data'           => $top,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'OLT Not Found.'], 404);
        } catch (\SNMPException $e) {
            return response()->json(['success' => false, 'message' => 'SNMP error: ' . $e->getMessage()], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 200);
        }
    }

    /**
     * ONU Distance Map: distance (meter) vs RX power (dBm) per ONU.
     * Untuk visualisasi scatter plot — identifikasi splitter loss abnormal.
     *
     * GET /olt/health/distance-map/{id}
     *
     * Response: JSON {
     *   success, generated_at, total, avg_rx, avg_dist,
     *   counts: { healthy, warning, critical },
     *   data: [{ pon, onu_id, name, sn, distance_m, rx_dbm, customer, customer_id }]
     * }
     */
    public function oltDistanceMap($id)
    {
        try {
            $olt    = \App\Olt::findOrFail($id);
            $zteoid = get_olt_oid_config($olt);

            $oltVendor = strtolower($olt->vendor ?? '');
            $oltType   = strtolower($olt->type ?? '');
            $oltName   = strtolower($olt->name ?? '');

            $isHSGQ = str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq');
            $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650')
                         || str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');
            $isC300Series = !$isC600Series && !$isHSGQ
                         && (str_contains($oltType, 'c300') || str_contains($oltType, 'c320')
                          || str_contains($oltName, 'c300') || str_contains($oltName, 'c320')
                          || $oltVendor === 'zte');

            if (!$isC600Series && !$isC300Series) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distance Map saat ini hanya didukung untuk OLT seri ZTE C300/C320/C600/C620/C650.',
                    'data'    => [],
                ], 200);
            }

            @ini_set('max_execution_time', 180);

            $host = $olt->ip . ':' . ($olt->snmp_port ?? 161);
            $snmp = new \SNMP(\SNMP::VERSION_2c, $host, $olt->community_ro, 5000000, 2);
            $snmp->oid_output_format = SNMP_OID_OUTPUT_NUMERIC;

            $rxWalk   = @$snmp->walk($zteoid['oidOnuRxPower']) ?: [];
            $distWalk = @$snmp->walk($zteoid['oidOnuDistance']) ?: [];
            $names    = @$snmp->walk($zteoid['oidOnuName']) ?: [];
            $sns      = @$snmp->walk($zteoid['oidOnuSn']) ?: [];

            // For C300, build reverse lookup encoded → frame/slot/port
            $frameslotportid = $isC300Series ? (get_olt_frameslotport_config($olt) ?: []) : [];
            $encodedToPon    = $isC300Series ? array_flip($frameslotportid) : [];

            // Build distance lookup by tailKey ("encodedIndex.onuId")
            // C300 distance OID = .1.3.6.1.4.1.3902.1012.3.11.4.1.2.{encoded}.{onuId}
            // C600 distance OID = .1.3.6.1.4.1.3902.1082.500.10.2.3.10.1.2.{encoded}.{onuId}
            $distByKey = [];
            foreach ($distWalk as $oidKey => $rawDist) {
                $parts = explode('.', $oidKey);
                $count = count($parts);
                if ($count < 2) continue;
                $onuId = $parts[$count - 1];
                $enc   = $parts[$count - 2];
                if (!preg_match('/-?\d+/', (string) $rawDist, $m)) continue;
                $distByKey[$enc . '.' . $onuId] = (int) $m[0];
            }

            $customers = \App\Customer::where('id_olt', $olt->id)->get();

            $rows         = [];
            $sumRx        = 0.0;
            $sumDist      = 0;
            $countHealthy = 0;
            $countWarn    = 0;
            $countCrit    = 0;

            foreach ($rxWalk as $oidKey => $rawRx) {
                $parts = explode('.', $oidKey);
                if (count($parts) < 3) continue;

                $tailParts = array_slice($parts, -3);
                if ((string) end($tailParts) === '1') {
                    $onuId        = (string) $tailParts[1];
                    $encodedIndex = (string) $tailParts[0];
                } else {
                    $onuId        = (string) end($tailParts);
                    $encodedIndex = (string) prev($tailParts);
                }
                $tailKey = $encodedIndex . '.' . $onuId;

                if (!preg_match('/-?\d+/', (string) $rawRx, $m)) continue;
                $raw = (int) $m[0];
                if ($raw >= 65535 || $raw <= 0) continue;
                $rxDbm = ($raw * 0.002) - 30.0;

                // Distance
                $distM = $distByKey[$tailKey] ?? null;
                if ($distM === null || $distM <= 0) continue;

                // Decode PON label
                if ($isC600Series) {
                    $idx   = (int) $encodedIndex;
                    $port  = ($idx >> 0)  & 0xFF;
                    $slot  = ($idx >> 8)  & 0xFF;
                    $frame = ($idx >> 16) & 0xFF;
                    $pon   = $frame . '/' . $slot . '/' . $port;
                } else {
                    $pon = $encodedToPon[$encodedIndex] ?? null;
                    if (!$pon) continue;
                }

                // Lookup name + SN
                $nameRaw = $names[$zteoid['oidOnuName'] . '.' . $tailKey] ?? '';
                $name    = trim(preg_replace('/^(STRING|OCTET STRING):\s*/i', '', (string) $nameRaw), " \t\"'");

                $snRawFull = $sns[$zteoid['oidOnuSn'] . '.' . $tailKey] ?? '';
                $snHex     = preg_replace('/^(Hex-STRING|STRING|OCTET STRING):\s*/i', '', (string) $snRawFull);
                $snDisplay = $this->decodeOnuSnHex($snHex);

                $customer = $customers->firstWhere('id_onu', "$pon:$onuId");

                $rows[] = [
                    'pon'         => $pon,
                    'onu_id'      => $onuId,
                    'name'        => $name,
                    'sn'          => $snDisplay,
                    'distance_m'  => $distM,
                    'rx_dbm'      => round($rxDbm, 2),
                    'customer'    => $customer ? ($customer->name ?? '') : '',
                    'customer_id' => $customer ? ($customer->id ?? null) : null,
                ];

                $sumRx   += $rxDbm;
                $sumDist += $distM;
                if ($rxDbm <= -27)      $countCrit++;
                elseif ($rxDbm <= -25)  $countWarn++;
                else                    $countHealthy++;
            }

            $snmp->close();

            $total = count($rows);

            return response()->json([
                'success'        => true,
                'generated_at'   => now()->toDateTimeString(),
                'total'          => $total,
                'avg_rx'         => $total > 0 ? round($sumRx / $total, 2) : null,
                'avg_dist'       => $total > 0 ? (int) round($sumDist / $total) : null,
                'counts'         => [
                    'healthy'  => $countHealthy,
                    'warning'  => $countWarn,
                    'critical' => $countCrit,
                ],
                'threshold_warn' => -25.0,
                'threshold_crit' => -27.0,
                'data'           => $rows,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'OLT Not Found.'], 404);
        } catch (\SNMPException $e) {
            return response()->json(['success' => false, 'message' => 'SNMP error: ' . $e->getMessage()], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 200);
        }
    }

                            public function getOltOnu(Request $request)
                            {
                                try {
                                    $olt = \App\Olt::findOrFail($request->input('olt_id'));
                                    $customers = \App\Customer::where('id_olt', $olt->id)->get();
                                                $oltPonIndex = $request->input('olt_pon'); // Diterima dari request (frame/slot/port untuk C620)

                                $zteoid = get_olt_oid_config($olt);
                                $ontStatuses = get_olt_status_config($olt);
                                
                                // Detect vendor type
                                $oltVendor = strtolower($olt->vendor ?? '');
                                $oltType = strtolower($olt->type ?? '');
                                $oltName = strtolower($olt->name ?? '');
                                
                                $isHSGQ = str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq');
                                $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
                                                str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');

                                // For C600/C620/C650 the PON index is "frame/slot/port" which contains slashes
                                // and breaks Laravel route segments. We convert it to underscores for URL building
                                // (delete/reboot/reset). Controllers convert it back to slashes.
                                $oltPonIndexUrl = ($isC600Series && str_contains((string) $oltPonIndex, '/'))
                                    ? str_replace('/', '_', (string) $oltPonIndex)
                                    : $oltPonIndex;
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
                                                $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip . ':' . ($olt->snmp_port ?? 161), $olt->community_ro);
                                                $result = $snmp->walk($oidOnuName);

                                                $hsgqUsingLegacyOnuSource = false;
                                                if ($isHSGQ) {
                                                    $hasParsedOnu = false;
                                                    if (is_array($result)) {
                                                        foreach ($result as $label) {
                                                            if ($this->parseHsgqOnuLabel($label)) {
                                                                $hasParsedOnu = true;
                                                                break;
                                                            }
                                                        }
                                                    }

                                                    if (!$hasParsedOnu) {
                                                        $legacyOnuNameOid = config('hsgq_oid.oidOnuName') ?: '.1.3.6.1.4.1.50224.3.12.2.1.2';
                                                        $legacyResult = @$snmp->walk($legacyOnuNameOid);
                                                        if (is_array($legacyResult) && count($legacyResult) > 0) {
                                                            $result = $legacyResult;
                                                            $hsgqUsingLegacyOnuSource = true;
                                                        }
                                                    }
                                                }

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
                                                         
                                                         $parsedOnu = $this->parseHsgqOnuLabel($ifDescr);
                                                         if (!$parsedOnu) {
                                                             continue; // Skip non-ONU interfaces
                                                         }
                                                         $ponNum = $parsedOnu['pon'];
                                                         $onuId = $parsedOnu['onu'];
                                                         
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
                                                         $decodedPortKey = $frame . '/' . $slot . '/' . $port;

                                                         // Filter: only process ONUs on selected PON port
                                                         if ($decodedPortKey !== $oltPonIndex) {
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
                                                         // HSGQ: if source is legacy .50224, IF-MIB ifIndex usually doesn't match ONU rows.
                                                         $hasilStatusRaw = '';
                                                         if (!$hsgqUsingLegacyOnuSource) {
                                                             $hasilStatusRaw = $this->safeSnmpGet($snmp, $oidOnuStatus.'.'.$ifIndex);
                                                         }

                                                         $hsgqMetricOid = $hsgqUsingLegacyOnuSource ? (config('hsgq_oid') ?: $zteoid) : $zteoid;
                                                         
                                                         // Check power values to determine actual ONU state
                                                         $rxPowerCheckOid = ($hsgqMetricOid['oidOnuRxPower'] ?? $zteoid['oidOnuRxPower']).".$encodedIndex.0.0";
                                                         $txPowerCheckOid = ($hsgqMetricOid['oidOnuTxPowerOnu'] ?? $zteoid['oidOnuTxPowerOnu']).".$encodedIndex.0.0";
                                                         $rxPwrVal = $this->safeSnmpGet($snmp, $rxPowerCheckOid);
                                                         $txPwrVal = $this->safeSnmpGet($snmp, $txPowerCheckOid);
                                                         
                                                         $rxPwrValid = $rxPwrVal && !str_contains($rxPwrVal, 'No Such') && !str_contains($rxPwrVal, 'N/A');
                                                         $txPwrValid = $txPwrVal && !str_contains($txPwrVal, 'No Such') && !str_contains($txPwrVal, 'N/A');
                                                         
                                                         $rxPwrNum = null;
                                                         if ($rxPwrValid && preg_match('/-?\d+/', $rxPwrVal, $rxM)) {
                                                             $rxPwrNum = (int)$rxM[0];
                                                         }
                                                         
                                                         if ($hsgqUsingLegacyOnuSource) {
                                                             // Legacy .50224 source: classify by optics only.
                                                             if (!$rxPwrValid && !$txPwrValid) {
                                                                 $hasilStatus = '5'; // powerdown
                                                             } elseif (!$rxPwrValid || !$txPwrValid) {
                                                                 $hasilStatus = '4'; // los
                                                             } elseif ($rxPwrNum !== null && $rxPwrNum < -2800) {
                                                                 $hasilStatus = '4'; // los - RX too weak
                                                             } else {
                                                                 $hasilStatus = '3'; // working
                                                             }
                                                         } elseif (preg_match('/\b(1|up)\b/', (string) $hasilStatusRaw)) {
                                                             // ifOperStatus = UP
                                                             if (!$rxPwrValid && !$txPwrValid) {
                                                                 $hasilStatus = '5'; // powerdown
                                                             } elseif (!$rxPwrValid || !$txPwrValid) {
                                                                 $hasilStatus = '4'; // los
                                                             } elseif ($rxPwrNum !== null && $rxPwrNum < -2800) {
                                                                 $hasilStatus = '4'; // los - RX power too low
                                                             } else {
                                                                 $hasilStatus = '3'; // working
                                                             }
                                                         } else {
                                                             // ifOperStatus = DOWN - check power to determine cause
                                                             if (!$rxPwrValid && !$txPwrValid) {
                                                                 $hasilStatus = '5'; // powerdown (dying gasp - no power)
                                                             } elseif (!$rxPwrValid || !$txPwrValid) {
                                                                 $hasilStatus = '4'; // los - fiber issue
                                                             } elseif ($rxPwrNum !== null && $rxPwrNum < -2800) {
                                                                 $hasilStatus = '4'; // los - RX power too low
                                                             } else {
                                                                 $hasilStatus = '6'; // dyinggasp - interface down but power present
                                                             }
                                                         }
                                                     } elseif ($isC600Series) {
                                                         // C620: Status OID uses encoded index (branch .500)
                                                         $hasilStatus = $this->safeSnmpGet($snmp, $oidOnuStatus.'.'.$encodedIndex.'.'.$onuId);
                                                     } else {
                                                         // C300/C320: Use old format
                                                         $hasilStatus = $this->safeSnmpGet($snmp, $oidOnuStatus.'.'.$onuId);
                                                     }
                                                     $result_status = $ontStatuses[$hasilStatus] ?? 'Unknown';

                                                     $modalId = preg_replace('/[^A-Za-z0-9_-]/', '_', $oltPonIndex."-".$onuId);

                                                     // Build OIDs based on OLT type
                                                     if ($isHSGQ) {
                                                         // HSGQ GPON: Enterprise OIDs
                                                         // Details: .50224.3.12.2.1.{field}.{hexIndex}
                                                         // Optics: .50224.3.12.3.1.{field}.{hexIndex}.0.0 (active) or .65535.65535 (offline)
                                                         $hsgqDetailOid = $hsgqUsingLegacyOnuSource ? (config('hsgq_oid') ?: $zteoid) : $zteoid;
                                                         $onuUptime = ($hsgqDetailOid['oidOnuUptime'] ?? $zteoid['oidOnuUptime']).".$encodedIndex"; // Field 21: Timeticks
                                                         $rxPowerOid = ($hsgqDetailOid['oidOnuRxPower'] ?? $zteoid['oidOnuRxPower']).".$encodedIndex.0.0"; // Add subindex .0.0
                                                         $txPowerOid = ($hsgqDetailOid['oidOnuTxPowerOnu'] ?? $zteoid['oidOnuTxPowerOnu']).".$encodedIndex.0.0"; // TX Power ONU
                                                         $onuLastOffline = null; // Not available
                                                         $onuLastOnline = null; // Not available
                                                         $onuModel = ($hsgqDetailOid['oidOnuModel'] ?? $zteoid['oidOnuModel']).".$encodedIndex";
                                                         $onuDistance = ($hsgqDetailOid['oidOnuDistance'] ?? $zteoid['oidOnuDistance']).".$encodedIndex.0.0"; // Add subindex
                                                         $onuSn = ($hsgqDetailOid['oidOnuSn'] ?? $zteoid['oidOnuSn']).".$encodedIndex";
                                                         $onuNameOid = ($hsgqDetailOid['oidOnuName'] ?? $zteoid['oidOnuName']).".$encodedIndex"; // Enterprise OID for name
                                                         $onuVendorOid = ($hsgqDetailOid['oidOnuVendor'] ?? $zteoid['oidOnuVendor']).".$encodedIndex" ?? null;
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
                                                         // C300/C320: Use pon_composite_index.onuId for OLT RX Power (sesuai dokumen ZTE)
                                                         $onuUptime = $zteoid['oidOnuUptime'].".$oltPonIndex.$onuId";
                                                         $rxPowerOid =$zteoid['oidOnuRxPower'].".$oltPonIndex.$onuId.1";
                                                         $txPowerOid = $zteoid['oidOnuTxPower'].".$oltPonIndex.$onuId.1";
                                                         $onuLastOffline = $zteoid['oidOnuLastOffline'].".$oltPonIndex.$onuId";
                                                         $onuLastOnline = $zteoid['oidOnuLastOnline'].".$oltPonIndex.$onuId";
                                                         $onuModel = $zteoid['oidOnuModel'].".$oltPonIndex.$onuId";
                                                         $onuDistance = $zteoid['oidOnuDistance'].".$oltPonIndex.$onuId";
                                                         $onuSn = $zteoid['oidOnuSn'].".$oltPonIndex.$onuId";
                                                         $onuNameOid = $zteoid['oidOnuName'].".$oltPonIndex.$onuId";
                                                         // OLT RX Power pakai pon_composite_index.onuId
                                                         $ponCompositeIndex = $pon_int ?? $oltPonIndex; // pastikan $pon_int sudah di-resolve dari mapping
                                                         $OltRxPowerOid =$zteoid['oidOltRxPower'].".$ponCompositeIndex.$onuId";
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
                                                         $onuLastOfflineRaw = $this->safeSnmpGet($snmp,$onuLastOffline);
                                                         // Handle both STRING: (already formatted) and Hex-STRING: (needs conversion)
                                                         if (strpos($onuLastOfflineRaw, 'STRING:') !== false && strpos($onuLastOfflineRaw, 'Hex-STRING:') === false) {
                                                             // Already formatted: STRING: 2026-05-04 12:04:10
                                                             $onuLastOfflineValue = trim(str_replace(['STRING: ', '"'], "", $onuLastOfflineRaw));
                                                         } else {
                                                             // Hex format: Hex-STRING: 07 EA 01 1B 12 15 06 00
                                                             $onuLastOfflineRaw = str_replace(['Hex-STRING: ', '"'], "", $onuLastOfflineRaw);
                                                             $onuLastOfflineValue = $this->convertSnmpDateTime($onuLastOfflineRaw);
                                                         }
                                                     } else {
                                                         $onuLastOfflineValue = 'N/A';
                                                     }
                                                     
                                                     if ($onuLastOnline) {
                                                         $onuLastOnlineRaw = $this->safeSnmpGet($snmp,$onuLastOnline);
                                                         // Handle both STRING: (already formatted) and Hex-STRING: (needs conversion)
                                                         if (strpos($onuLastOnlineRaw, 'STRING:') !== false && strpos($onuLastOnlineRaw, 'Hex-STRING:') === false) {
                                                             // Already formatted: STRING: 2026-05-04 12:05:06
                                                             $onuLastOnlineValue = trim(str_replace(['STRING: ', '"'], "", $onuLastOnlineRaw));
                                                         } else {
                                                             // Hex format: Hex-STRING: 07 EA 01 1B 12 15 06 00
                                                             $onuLastOnlineRaw = str_replace(['Hex-STRING: ', '"'], "", $onuLastOnlineRaw);
                                                             $onuLastOnlineValue = $this->convertSnmpDateTime($onuLastOnlineRaw);
                                                         }
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
                                                            // ZTE C300/C320/C600: parse standard Timeticks output.
                                                            $onUptimeValue = trim((string)preg_replace('/Timeticks:\s*\(\d+\)\s*/i', '', (string)$uptimeRaw));
                                                            if (empty($onUptimeValue)) {
                                                                if (preg_match('/\((\d+)\)/', (string)$uptimeRaw, $m)) {
                                                                    $ticks = (int)$m[1]; // hundredths of seconds
                                                                    $totalSeconds = intdiv($ticks, 100);
                                                                    $days = intdiv($totalSeconds, 86400);
                                                                    $hours = intdiv($totalSeconds % 86400, 3600);
                                                                    $minutes = intdiv($totalSeconds % 3600, 60);
                                                                    $seconds = $totalSeconds % 60;
                                                                    $onUptimeValue = $days > 0
                                                                        ? sprintf('%d day%s, %d:%02d:%02d.00', $days, $days > 1 ? 's' : '', $hours, $minutes, $seconds)
                                                                        : sprintf('%d:%02d:%02d.00', $hours, $minutes, $seconds);
                                                                } else {
                                                                    $onUptimeValue = 'Unknown';
                                                                }
                                                            }
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
                                                                                                        $modalBody = '<p id="rxPower">Onu Name : '.str_replace('"', '', $this->cleanSnmpValue($onuNameValue)).'</p>
                                                                                                        <p id="rxPower">Onu Model : '.$onuModelValue.' </p>
                                                                                                        <p id="rxPower">Onu Sn : '.$onuSnAscii.' </p>
                                                                                                        <p id="rxPower">Onu Rx Power : N/A dBm</p>
                                                                                                        <p id="txPower">Onu Tx Power : N/A dBm</p>
                                                                                                        <p id="txPower">Onu Cable Length  : '. $this->cleanSnmpValue($onuDistanceValue).' </p>';

                                                                                                        if (!$isHSGQ) {
                                                                                                                $modalBody .= '<p id="txPower">Olt Rx Power : N/A dBm</p>
                                                                                                                <p id="rxPower">Onu Last Offline : '.$onuLastOfflineValue.' </p>
                                                                                                                <p id="txPower">Onu Last Online : '.$onuLastOnlineValue.' </p>
                                                                                                                <p id="txPower">Onu Uptime : '.$onUptimeValue.' </p>';
                                                                                                        } else {
                                                                                                                $modalBody .= '<p id="rxPower">Interface Status : LOS</p>';
                                                                                                        }

                                                                                                        $onu_ststus= '<button id="powerButton" class="btn badge-danger btn-sm pb-1" data-toggle="modal" data-target="#powerModal'.$modalId.'">LOS</button>
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
                                                    $onu_delete =  ' <form onsubmit="confirmSubmit(event, \'Delete This ONU!\')" action="/olt/delete/' . $olt->id . '/' . $oltPonIndexUrl . '/' . $onuId . '" method="POST">
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <input type="hidden" name="_token" value="' . csrf_token() . '">
                                                    <button type="submit" class="btn btn-danger btn-sm m-1" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                    </form>';
                                                }
                                                elseif ($result_status == "powerdown")
                                                {
                                                    $modalBody = '<p id="rxPower">Onu Name : '.str_replace('"', '', $this->cleanSnmpValue($onuNameValue)).'</p>
                                                    <p id="rxPower">Onu Model : '.$onuModelValue.' </p>
                                                    <p id="rxPower">Onu Sn : '.$onuSnAscii.' </p>
                                                    <p id="rxPower">Onu Rx Power : N/A dBm</p>
                                                    <p id="txPower">Onu Tx Power : N/A dBm</p>
                                                    <p id="txPower">Onu Cable Length  : '. $this->cleanSnmpValue($onuDistanceValue).' </p>';

                                                    if (!$isHSGQ) {
                                                        $modalBody .= '<p id="txPower">Olt Rx Power : N/A dBm</p>
                                                        <p id="rxPower">Onu Last Offline : '.$onuLastOfflineValue.' </p>
                                                        <p id="txPower">Onu Last Online : '.$onuLastOnlineValue.' </p>
                                                        <p id="txPower">Onu Uptime : '.$onUptimeValue.' </p>';
                                                    } else {
                                                        $modalBody .= '<p id="rxPower">Interface Status : Power Down</p>';
                                                    }

                                                    $onu_ststus= '<button id="powerButton" class="btn badge-warning btn-sm pb-1" data-toggle="modal" data-target="#powerModal'.$modalId.'">PWR DOWN</button>
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
                                                    $onu_delete =  ' <form onsubmit="confirmSubmit(event, \'Delete This ONU!\')" action="/olt/delete/' . $olt->id . '/' . $oltPonIndexUrl . '/' . $onuId . '" method="POST">
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
                                                <form onsubmit="confirmSubmit(event, \'Delete This ONU!\')" action="/olt/delete/' . $olt->id . '/' . $oltPonIndexUrl . '/' . $onuId . '" method="POST">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <input type="hidden" name="_token" value="' . csrf_token() . '">
                                                <button type="submit" class="btn btn-danger btn-sm m-1" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                                </button>
                                                </form>

                                                <form onsubmit="confirmSubmit(event, \'Reboot This ONU!\')" action="/olt/reboot/' . $olt->id . '/' . $oltPonIndexUrl . '/' . $onuId . '" method="POST">
                                                <input type="hidden" name="_method" value="POST"> <!-- Gunakan POST untuk reboot -->
                                                <input type="hidden" name="_token" value="' . csrf_token() . '">
                                                <button type="submit" class="btn btn-warning btn-sm m-1" title="Reboot">
                                                <i class="fas fa-sync-alt"></i>
                                                </button>
                                                </form>

                                                <form onsubmit="confirmSubmit(event, \'Factory Reset This ONU!\')" action="/olt/reset/' . $olt->id . '/' . $oltPonIndexUrl . '/' . $onuId . '" method="POST">
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
                                                $modalBody = '<p id="rxPower">Onu Name : '.str_replace('"', '', $this->cleanSnmpValue($onuNameValue)).'</p>
                                                <p id="rxPower">Onu Model : '.$onuModelValue.' </p>
                                                <p id="rxPower">Onu Sn : '.$onuSnAscii.' </p>
                                                <p id="rxPower">Onu Rx Power : N/A dBm</p>
                                                <p id="txPower">Onu Tx Power : N/A dBm</p>
                                                <p id="txPower">Onu Cable Length  : '. $this->cleanSnmpValue($onuDistanceValue).' </p>';

                                                if (!$isHSGQ) {
                                                    $modalBody .= '<p id="txPower">Olt Rx Power : N/A dBm</p>
                                                    <p id="rxPower">Onu Last Offline : '.$onuLastOfflineValue.' </p>
                                                    <p id="txPower">Onu Last Online : '.$onuLastOnlineValue.' </p>
                                                    <p id="txPower">Onu Uptime : '.$onUptimeValue.' </p>';
                                                } else {
                                                    $modalBody .= '<p id="rxPower">Interface Status : '.$result_status.'</p>';
                                                }

                                                $onu_ststus= '<button id="powerButton" class="btn badge-warning btn-sm pb-1" data-toggle="modal" data-target="#powerModal'.$modalId.'">'.$result_status.'</button>
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
                                                $onu_delete = '<form onsubmit="confirmSubmit(event, \'Delete This ONU!\')" action="/olt/delete/' . $olt->id . '/' . $oltPonIndexUrl . '/' . $onuId . '" method="POST">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <input type="hidden" name="_token" value="' . csrf_token() . '">
                                                <button type="submit" class="btn btn-danger btn-sm m-1" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                                </button>
                                                </form>';
                                            }
                                            else
                                            {
                                                $modalBody = '<p id="rxPower">Onu Name : '.str_replace('"', '', $this->cleanSnmpValue($onuNameValue)).'</p>
                                                <p id="rxPower">Onu Model : '.$onuModelValue.' </p>
                                                <p id="rxPower">Onu Sn : '.$onuSnAscii.' </p>
                                                <p id="rxPower">Onu Rx Power : N/A dBm</p>
                                                <p id="txPower">Onu Tx Power : N/A dBm</p>
                                                <p id="txPower">Onu Cable Length  : '. $this->cleanSnmpValue($onuDistanceValue).' </p>';

                                                if (!$isHSGQ) {
                                                    $modalBody .= '<p id="txPower">Olt Rx Power : N/A dBm</p>
                                                    <p id="rxPower">Onu Last Offline : '.$onuLastOfflineValue.' </p>
                                                    <p id="txPower">Onu Last Online : '.$onuLastOnlineValue.' </p>
                                                    <p id="txPower">Onu Uptime : '.$onUptimeValue.' </p>';
                                                } else {
                                                    $modalBody .= '<p id="rxPower">Interface Status : '.$result_status.'</p>';
                                                }

                                                $onu_ststus= '<button id="powerButton" class="btn badge-warning btn-sm pb-1" data-toggle="modal" data-target="#powerModal'.$modalId.'">'.$result_status.'</button>
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
                                                $onu_delete =  '<form onsubmit="confirmSubmit(event, \'Delete This ONU!\')" action="/olt/delete/' . $olt->id . '/' . $oltPonIndexUrl . '/' . $onuId . '" method="POST">
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

                            /**
                             * Hapus prefiks SNMP standar ("STRING: ", "Hex-STRING: ", "INTEGER: ", dll.)
                             * dan tanda kutip pembungkus, lalu trim. Cocok untuk normalisasi value walk/get.
                             */
                            private function stripSnmpPrefix($value): string
                            {
                                $value = (string) $value;
                                // urutkan dari yang paling spesifik dulu agar substring "STRING: " tidak menggigit lebih dulu
                                $prefixes = [
                                    'Hex-STRING: ',
                                    'OCTET STRING: ',
                                    'STRING: ',
                                    'INTEGER: ',
                                    'Gauge32: ',
                                    'Counter32: ',
                                    'Counter64: ',
                                    'Timeticks: ',
                                    'IpAddress: ',
                                ];
                                foreach ($prefixes as $p) {
                                    if (stripos($value, $p) === 0) {
                                        $value = substr($value, strlen($p));
                                        break;
                                    }
                                }
                                return trim($value, " \t\n\r\0\x0B\"");
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
                                $onuUncfgSn = $zteoid['oidOnuUncfgSn'] ?? null;
                                $onuUncfgtype = $zteoid['oidOnuUncfgType'] ?? null;
                                $oidOltName = $zteoid['oidOltName'] ?? null;

                                // Deteksi seri C600/C620/C650 (encoded index berbeda dari C300/C320).
                                $oltType = strtolower($olt->type ?? '');
                                $oltName = strtolower($olt->name ?? '');
                                $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
                                    str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');

                                // Inisialisasi data view agar tidak undefined ketika SNMP error / kosong.
                                $onu = [];
                                $vlanList = [];
                                $oidOltGmportProfile = [];
                                $oidOltTconProfile = [];

                                if (empty($onuUncfgSn)) {
                                    return redirect('/customer/' . $customer->id . '/edit')
                                        ->with('warning', 'Vendor OLT belum didukung untuk listing Unconfigure ONU.');
                                }

                                try {
                                    $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip . ':' . ($olt->snmp_port ?? 161), $olt->community_ro);
                                    $result = $snmp->walk($onuUncfgSn);

                                    if (empty($result)) {
                                        \Log::info('SNMP Walk uncfg returned no results for OID ' . $onuUncfgSn);
                                        return redirect('/customer/' . $customer->id . '/edit')
                                            ->with('warning', 'Tidak ada ONU unconfigure terdeteksi pada OLT ini.');
                                    }

                                    $oltNameValue = $olt->name ?: $this->stripSnmpPrefix((string) $snmp->get($oidOltName));

                                    foreach ($result as $key => $onuUconfg) {
                                        $oidParts = explode('.', $key);
                                        // Index terakhir = {ponIndex}.{discoveryOrder} (atau {encoded}.{order} untuk C600)
                                        $identifier = implode('.', array_slice($oidParts, -2));
                                        $desiredValue = (int) $oidParts[count($oidParts) - 2];

                                        $onuTypeRaw = $snmp->get($onuUncfgtype . '.' . $identifier);
                                        $onuType = $this->stripSnmpPrefix((string) $onuTypeRaw);
                                        $onuMac = $this->stripSnmpPrefix((string) $onuUconfg);

                                        if ($isC600Series) {
                                            // C600: encoded = frame*16777216 + slot*65536 + port*256 (+ onuid)
                                            $decodedFrame = ($desiredValue >> 16) & 0xFF;
                                            $decodedSlot  = ($desiredValue >> 8)  & 0xFF;
                                            $decodedPort  = $desiredValue & 0xFF;
                                            $ponPath = $decodedFrame . '/' . $decodedSlot . '/' . $decodedPort;
                                        } else {
                                            // C300/C320: pakai mapping baseIndex/slotGaps via getPonCode().
                                            $ponPath = $this->getPonCode($desiredValue);
                                            if (is_object($ponPath)) {
                                                $ponPath = '';
                                            }
                                        }

                                        // SN ZTE format Hex-STRING "5A 54 45 47 ..." → "ZTEG..." (decode untuk semua seri).
                                        $snDecoded = (strpos($onuMac, ' ') !== false)
                                            ? $this->convertMacToAscii($onuMac)
                                            : $onuMac;

                                        $onu[] = [
                                            'oltName'    => $oltNameValue,
                                            'oid'        => $ponPath,
                                            'identifier' => $this->cleanSnmpValue($onuType),
                                            'value'      => $snDecoded,
                                            'ponid'      => ($zteoid['oidOnuName'] ?? '') . '.' . $desiredValue,
                                        ];
                                    }

                                    // VLAN & Profile dropdowns (best-effort, jangan gagal kalau OID tidak ada).
                                    try {
                                        $oidGm = $zteoid['oidOltGmportProfile'] ?? null;
                                        if ($oidGm) {
                                            $walkGm = $snmp->walk($oidGm);
                                            if (is_array($walkGm)) {
                                                $vals = array_map(fn($v) => trim($this->stripSnmpPrefix((string) $v), " \t\n\r\0\x0B\""), $walkGm);
                                                $vals = array_values(array_unique(array_filter($vals, fn($v) => $v !== '')));
                                                $oidOltGmportProfile = $vals;
                                            }
                                        }
                                    } catch (\Throwable $e) {
                                        \Log::warning('Walk GmportProfile failed: ' . $e->getMessage());
                                    }

                                    try {
                                        $oidTcon = $zteoid['oidOltTconProfile'] ?? null;
                                        if ($oidTcon) {
                                            $walkTcon = $snmp->walk($oidTcon);
                                            if (is_array($walkTcon)) {
                                                $vals = array_map(fn($v) => trim($this->stripSnmpPrefix((string) $v), " \t\n\r\0\x0B\""), $walkTcon);
                                                $vals = array_values(array_unique(array_filter($vals, fn($v) => $v !== '')));
                                                $oidOltTconProfile = $vals;
                                            }
                                        }
                                    } catch (\Throwable $e) {
                                        \Log::warning('Walk TconProfile failed: ' . $e->getMessage());
                                    }

                                    try {
                                        $oidVlan = $zteoid['oidOltVlanId'] ?? null;
                                        if ($oidVlan) {
                                            $walkVlan = $snmp->walk($oidVlan);
                                            if (is_array($walkVlan)) {
                                                foreach ($walkVlan as $oid => $vlanName) {
                                                    $parts = explode('.', $oid);
                                                    $vid = end($parts);
                                                    if ($vid !== false && $vid !== '' && ctype_digit((string) $vid)) {
                                                        $vlanList[] = $vid;
                                                    }
                                                }
                                                $vlanList = array_values(array_unique($vlanList));
                                            }
                                        }
                                    } catch (\Throwable $e) {
                                        \Log::warning('Walk VlanId failed: ' . $e->getMessage());
                                    }

                                    $snmp->close();
                                } catch (\Exception $e) {
                                    \Log::warning('SNMP addonu failed for ' . $olt->ip . ': ' . $e->getMessage());
                                    return redirect('/customer/' . $customer->id . '/edit')
                                        ->with('warning', 'Unconfigure Onu Not Found');
                                }

                                // Fallback: pada ZTE C600 kolom Gemport US/DS Traffic Profile name
                                // sering kosong via SNMP. Pakai daftar TCont sebagai opsi agar
                                // dropdown tidak kosong (operator masih bisa pilih atau ganti manual).
                                if (empty($oidOltGmportProfile) && !empty($oidOltTconProfile)) {
                                    $oidOltGmportProfile = $oidOltTconProfile;
                                }

                                return view('olt/addonu', [
                                    'customer' => $customer,
                                    'olt' => $olt,
                                    'onutype' => $onutype,
                                    'vlanList' => $vlanList,
                                    'onuprofile' => $onuprofile,
                                    'onu' => $onu,
                                    'oidOltGmportProfile' => $oidOltGmportProfile,
                                    'oidOltTconProfile' => $oidOltTconProfile,
                                ]);
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
    $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip . ':' . ($olt->snmp_port ?? 161), $olt->community_ro);

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

        $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip . ':' . ($olt->snmp_port ?? 161), $olt->community_ro);
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
    $host = $request->olt;
    $community = $request->community;
    $oltId = $request->input('olt_id');

    // Get OLT from database to detect type (C600 vs C300)
    // Prioritaskan lookup by ID karena akurat dan punya snmp_port.
    $olt = null;
    if (!empty($oltId)) {
        $olt = \App\Olt::find($oltId);
    }
    if (!$olt && !empty($host)) {
        $olt = \App\Olt::where('ip', $host)->first();
    }

    if (!$olt) {
        // Fallback to default config if OLT not found in DB
        $zteoid = config('zteoid');
        $isC600Series = false;
        $snmpHostWithPort = $host;
        $snmpCommunity = $community;
    } else {
        $zteoid = get_olt_oid_config($olt);
        $oltType = strtolower($olt->type ?? '');
        $oltName = strtolower($olt->name ?? '');
        $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
                str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');

        // Pakai IP + snmp_port dari DB agar OLT dengan port non-default (mis. 1612) tetap reachable.
        $snmpHostWithPort = $olt->ip . ':' . ($olt->snmp_port ?? 161);
        $snmpCommunity = $olt->community_ro ?: $community;
    }

    $onuUncfgSn = $zteoid['oidOnuUncfgSn'];
    $onuUncfgtype = $zteoid['oidOnuUncfgType'];
    $oidOltName = $zteoid['oidOltName'];

    // Validasi input request
    if (empty($snmpHostWithPort) || empty($snmpCommunity)) {
        return response()->json(['error' => 'Invalid OLT or community'], 400);
    }

    // C600+ tidak punya OID uncfg yang reliable via SNMP — fallback ke CLI / tidak didukung.
    if (empty($onuUncfgSn)) {
        return DataTables::of([])->addIndexColumn()->make(true);
    }

    $processedResults = [];

    try {
        // Inisialisasi SNMP
        $snmp = new \SNMP(\SNMP::VERSION_2c, $snmpHostWithPort, $snmpCommunity);

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
        foreach ($result as $key => $onuUconfg) {
            $oidParts = explode('.', $key);
            // Untuk C600 (.500.2.2.11.2.1.2.{ponIndex}.{discoveryOrder}) ambil 2 segmen terakhir.
            $identifier = implode('.', array_slice($oidParts, -2));
            $desiredValue = (int)$oidParts[count($oidParts) - 2];

            $onuTypeRaw = $snmp->get($onuUncfgtype . '.' . $identifier);
            $onuType = $this->stripSnmpPrefix((string)$onuTypeRaw);
            $onuSn = $this->stripSnmpPrefix((string)$onuUconfg);

            if ($isC600Series) {
                $decodedFrame = ($desiredValue >> 16) & 0xFF;
                $decodedSlot = ($desiredValue >> 8) & 0xFF;
                $decodedPort = $desiredValue & 0xFF;
                $slotLabel = $decodedFrame . '/' . $decodedSlot . '/' . $decodedPort;
            } else {
                $slotLabel = $this->getPonCode($desiredValue);
                if (is_object($slotLabel)) {
                    $slotLabel = '';
                }
            }

            $oltNameValue = $olt ? $olt->name : $this->stripSnmpPrefix((string)$snmp->get($oidOltName));
            $snValue = $onuSn !== '' ? $onuSn : '-';
            $modelValue = $this->cleanSnmpValue($onuType);
            if ($modelValue === '') {
                $modelValue = '-';
            }
            // Decode SN hex ("5A 54 45 47 D3 8F FE 67") → "ZTEGD38FFE67" untuk semua vendor ZTE.
            if ($snValue !== '-' && strpos($snValue, ' ') !== false) {
                $snValue = $this->convertMacToAscii($snValue);
            }

            // Simpan hasil ke array
            $processedResults[] = [
                'oltName' => $oltNameValue,
                'oid' => $slotLabel,
                'identifier' => $snValue,
                'value' => $modelValue,
            ];
        }

        // Tutup sesi SNMP
        $snmp->close();

        // Return hasil dalam bentuk DataTables
        

    } catch (\Exception $e) {
        \Log::warning('SNMP Walk uncfg failed for ' . $snmpHostWithPort . ' OID ' . $onuUncfgSn . ': ' . $e->getMessage());
        // Jangan push baris kosong — biarkan tabel kosong saja agar UI tahu "tidak ada data".
        $processedResults = [];
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
        $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip . ':' . ($olt->snmp_port ?? 161), $olt->community_ro);
 // Validasi id_onu format
        if (!strpos($request->id_onu, ':')) {
            return response()->json(['error' => 'Invalid ONT ID format'], 400);
        }

        list($frameSlotPort, $ontId) = explode(":", $request->id_onu);

        // Detect vendor type
        $oltVendor = strtolower($olt->vendor ?? '');
        $oltType = strtolower($olt->type ?? '');
        $oltName = strtolower($olt->name ?? '');
        $isHSGQ = str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq');
        $isC600Series = str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
                str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650');

        if ($isHSGQ) {
            // HSGQ: id_onu format is "PON:ONU_ID" (e.g., "2:1")
            $ponNum = (int)$frameSlotPort;
            $onuIdNum = (int)$ontId;
            $encodedIndex = encode_hsgq_index($ponNum, $onuIdNum);
            $powerDivisor = $zteoid['powerDivisor'] ?? 100;

            // Build OIDs using HSGQ enterprise format
            $onuName = $zteoid['oidOnuName'] . ".$encodedIndex";
            $onuModel = $zteoid['oidOnuModel'] . ".$encodedIndex";
            $onuSn = $zteoid['oidOnuSn'] . ".$encodedIndex";
            $onuUptime = $zteoid['oidOnuUptime'] . ".$encodedIndex";
            $onuDistance = $zteoid['oidOnuDistance'] . ".$encodedIndex.0.0";
            $rxPowerOid = $zteoid['oidOnuRxPower'] . ".$encodedIndex.0.0";
            $txPowerOid = $zteoid['oidOnuTxPowerOnu'] . ".$encodedIndex.0.0";

            $modalId = $ponNum . "-" . $onuIdNum;

            // Get ifIndex by walking ifDescr to find matching ONU
            $ifDescrWalk = @$snmp->walk($zteoid['oidIfDescr']);
            $ifIndex = null;
            if ($ifDescrWalk) {
                foreach ($ifDescrWalk as $oid => $val) {
                    $desc = str_replace(['STRING: ', '"'], '', $val);
                    if (preg_match('/(ONU|ONT)\s*0*' . $ponNum . '\/\s*0*' . $onuIdNum . '$/i', $desc)) {
                        $parts = explode('.', $oid);
                        $ifIndex = (int)end($parts);
                        break;
                    }
                }
            }

            // Get status via ifOperStatus
            $statusRaw = $ifIndex ? $this->safeSnmpGet($snmp, $zteoid['oidIfOperStatus'] . '.' . $ifIndex) : '';
            
            // Check power values to determine actual status
            $rxPowerValue = $this->safeSnmpGet($snmp, $rxPowerOid);
            $txPowerValue = $this->safeSnmpGet($snmp, $txPowerOid);
            
            $rxValid = $rxPowerValue && !str_contains($rxPowerValue, 'No Such') && !str_contains($rxPowerValue, 'N/A');
            $txValid = $txPowerValue && !str_contains($txPowerValue, 'No Such') && !str_contains($txPowerValue, 'N/A');
            
            $rxNumeric = null;
            if ($rxValid && preg_match('/-?\d+/', $rxPowerValue, $rxM)) {
                $rxNumeric = (int)$rxM[0];
            }
            $txNumeric = null;
            if ($txValid && preg_match('/-?\d+/', $txPowerValue, $txM)) {
                $txNumeric = (int)$txM[0];
            }

            if (preg_match('/\b(1|up)\b/', $statusRaw)) {
                if (!$rxValid && !$txValid) {
                    $result_status = 'powerdown';
                } elseif (!$rxValid || !$txValid) {
                    $result_status = 'los';
                } elseif ($rxNumeric !== null && $rxNumeric < -2800) {
                    $result_status = 'los';
                } else {
                    $result_status = 'working';
                }
            } else {
                if (!$rxValid && !$txValid) {
                    $result_status = 'powerdown';
                } elseif (!$rxValid || !$txValid) {
                    $result_status = 'los';
                } elseif ($rxNumeric !== null && $rxNumeric < -2800) {
                    $result_status = 'los';
                } else {
                    $result_status = 'dyinggasp';
                }
            }

            // Fetch ONU details
            $onuNameValue = str_replace(['STRING: ', '"'], "", $this->safeSnmpGet($snmp, $onuName));
            $onuModelValue = str_replace(['STRING: ', '"'], "", $this->safeSnmpGet($snmp, $onuModel));
            $onuSnRaw = str_replace(['Hex-STRING: ', 'STRING: ', '"'], "", $this->safeSnmpGet($snmp, $onuSn));
            $onuSnAscii = $this->convertMacToAscii($onuSnRaw);
            $onuDistanceValue = str_replace(['INTEGER: ', '"'], "", $this->safeSnmpGet($snmp, $onuDistance));
            $onUptimeValue = str_replace(['Timeticks:', '"'], "", $this->safeSnmpGet($snmp, $onuUptime));

            // Fallback for HSGQ variants that still store ONU details on legacy .50224 branch.
            if (empty($onuNameValue) || strtoupper($onuNameValue) === 'N/A' || str_contains($onuNameValue, 'No Such')) {
                $legacyHsgq = config('hsgq_oid') ?: [];
                if (!empty($legacyHsgq)) {
                    if (!empty($legacyHsgq['oidOnuName'])) {
                        $onuNameValue = str_replace(['STRING: ', '"'], "", $this->safeSnmpGet($snmp, $legacyHsgq['oidOnuName'] . "." . $encodedIndex));
                    }
                    if (!empty($legacyHsgq['oidOnuModel'])) {
                        $onuModelValue = str_replace(['STRING: ', '"'], "", $this->safeSnmpGet($snmp, $legacyHsgq['oidOnuModel'] . "." . $encodedIndex));
                    }
                    if (!empty($legacyHsgq['oidOnuSn'])) {
                        $onuSnRaw = str_replace(['Hex-STRING: ', 'STRING: ', '"'], "", $this->safeSnmpGet($snmp, $legacyHsgq['oidOnuSn'] . "." . $encodedIndex));
                        $onuSnAscii = $this->convertMacToAscii($onuSnRaw);
                    }

                    if ((!$rxPowerValue || str_contains($rxPowerValue, 'No Such')) && !empty($legacyHsgq['oidOnuRxPower'])) {
                        $rxPowerValue = $this->safeSnmpGet($snmp, $legacyHsgq['oidOnuRxPower'] . "." . $encodedIndex . '.0.0');
                        if ($rxPowerValue && preg_match('/-?\d+/', $rxPowerValue, $rxM)) {
                            $rxNumeric = (int)$rxM[0];
                        }
                    }
                    if ((!$txPowerValue || str_contains($txPowerValue, 'No Such')) && !empty($legacyHsgq['oidOnuTxPowerOnu'])) {
                        $txPowerValue = $this->safeSnmpGet($snmp, $legacyHsgq['oidOnuTxPowerOnu'] . "." . $encodedIndex . '.0.0');
                        if ($txPowerValue && preg_match('/-?\d+/', $txPowerValue, $txM)) {
                            $txNumeric = (int)$txM[0];
                        }
                    }
                }
            }

            // Calculate power in dBm (HSGQ: value / 100)
            $rxDbm = ($rxNumeric !== null) ? round($rxNumeric / $powerDivisor, 2) : null;
            $txDbm = ($txNumeric !== null) ? round($txNumeric / $powerDivisor, 2) : null;

            if ($result_status == 'working') {
                $displayStatus = 'Rx: ' . ($rxDbm ?? '-') . ' | Tx: ' . ($txDbm ?? '-');
                echo '<button id="powerButton" class="btn bg-success btn-sm pb-1" data-toggle="modal" data-target="#powerModal' . $modalId . '">' . $displayStatus . '</button>
                <div class="modal fade" id="powerModal' . $modalId . '">
                <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title"><strong>Status ONU ' . $olt->name . ' ' . $frameSlotPort . ':' . $ontId . '</strong></h5>
                </div>
                <div class="modal-body">
                <p>Onu Name : ' . $onuNameValue . '</p>
                <p>Onu Model : ' . $onuModelValue . '</p>
                <p>Onu Sn : ' . $onuSnAscii . '</p>
                <p>Onu Rx Power : ' . ($rxDbm ?? '-') . ' dBm</p>
                <p>Onu Tx Power : ' . ($txDbm ?? '-') . ' dBm</p>
                <p>Onu Cable Length : ' . $onuDistanceValue . ' m</p>
                <p>Olt Rx Power : N/A</p>
                <p>Onu Last Offline : N/A</p>
                <p>Onu Last Online : N/A</p>
                <p>Onu Uptime : ' . $onUptimeValue . '</p>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
                </div>
                </div>
                </div>';
            } elseif ($result_status == 'los') {
                echo '<a class="badge-danger badge btn-sm p-2 ml-2 mr-2 text-white">' . $result_status . '</a>
                <div class="modal fade" id="powerModal' . $modalId . '">
                <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title"><strong>Status ONU ' . $olt->name . ' ' . $frameSlotPort . ':' . $ontId . '</strong></h5>
                </div>
                <div class="modal-body">
                <p>Onu Name : ' . $onuNameValue . '</p>
                <p>Onu Model : ' . $onuModelValue . '</p>
                <p>Onu Sn : ' . $onuSnAscii . '</p>
                <p>Onu Rx Power : - dBm</p>
                <p>Onu Tx Power : - dBm</p>
                <p>Onu Cable Length : ' . $onuDistanceValue . ' m</p>
                <p>Olt Rx Power : N/A</p>
                <p>Onu Last Offline : N/A</p>
                <p>Onu Last Online : N/A</p>
                <p>Onu Uptime : -</p>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
                </div>
                </div>
                </div>';
            } elseif ($result_status == 'dyinggasp') {
                echo '<button id="powerButton" class="btn bg-warning btn-sm pb-1" data-toggle="modal" data-target="#powerModal' . $modalId . '">' . $result_status . '</button>
                <div class="modal fade" id="powerModal' . $modalId . '">
                <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title"><strong>Status ONU ' . $olt->name . ' ' . $frameSlotPort . ':' . $ontId . '</strong></h5>
                </div>
                <div class="modal-body">
                <p>Onu Name : ' . $onuNameValue . '</p>
                <p>Onu Model : ' . $onuModelValue . '</p>
                <p>Onu Sn : ' . $onuSnAscii . '</p>
                <p>Onu Rx Power : - dBm</p>
                <p>Onu Tx Power : - dBm</p>
                <p>Onu Cable Length : ' . $onuDistanceValue . ' m</p>
                <p>Olt Rx Power : N/A</p>
                <p>Onu Last Offline : N/A</p>
                <p>Onu Last Online : N/A</p>
                <p>Onu Uptime : -</p>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
                </div>
                </div>
                </div>';
            } elseif ($result_status == 'powerdown') {
                echo '<a class="badge-secondary badge btn-sm p-2 ml-2 mr-2 text-white">' . $result_status . '</a>';
            } else {
                echo '<a class="badge-warning btn btn-sm ml-2 mr-2 text-white">' . $result_status . '</a>';
            }

            return; // HSGQ handled, exit early
        }

        if ($isC600Series) {
            $fspParts = explode('/', $frameSlotPort);
            if (count($fspParts) !== 3) {
                echo '<a class="badge-warning btn btn-sm ml-2 mr-2 text-white">Unknown</a>';
                return;
            }

            [$shelf, $slot, $ponPort] = $fspParts;
            $shelf = (int)$shelf;
            $slot = (int)$slot;
            $ponPort = (int)$ponPort;
            $onuIdNum = (int)$ontId;

            $statusRaw = null;
            $encodedIndex = null;
            // Samakan dengan alur ONU list: walk branch status .500 lalu decode index.
            $statusWalk = @$snmp->walk($zteoid['oidOnuStatus']);
            if (is_array($statusWalk)) {
                foreach ($statusWalk as $oid => $val) {
                    $parts = explode('.', $oid);
                    if (count($parts) < 2) {
                        continue;
                    }

                    $encoded = (int)$parts[count($parts) - 2];
                    $onuSuffix = (int)$parts[count($parts) - 1];

                    $decodedShelf = ($encoded >> 16) & 0xFF;
                    $decodedSlot = ($encoded >> 8) & 0xFF;
                    $decodedPort = $encoded & 0xFF;
                    $decodedPortKey = $decodedShelf . '/' . $decodedSlot . '/' . $decodedPort;

                    if ($decodedPortKey === $frameSlotPort && $onuSuffix === $onuIdNum) {
                        $rawStr = trim((string)$val);
                        if ($rawStr !== '' && stripos($rawStr, 'No Such') === false && strtoupper($rawStr) !== 'N/A') {
                            $statusRaw = $rawStr;
                            $encodedIndex = $encoded;
                            break;
                        }
                    }
                }
            }

            if ($statusRaw === null) {
                echo '<a class="badge-warning btn btn-sm ml-2 mr-2 text-white">Unknown</a>';
                return;
            }

            $statusInt = null;
            if (preg_match('/(-?\d+)/', $statusRaw, $m)) {
                $statusInt = (int)$m[1];
            }

            $result_status = $ontStatuses[$statusRaw]
                ?? ($statusInt !== null ? ($ontStatuses['INTEGER: ' . $statusInt] ?? 'Unknown') : 'Unknown');

            $onuNameValue = str_replace(['STRING: ', '"'], '', (string)$this->safeSnmpGet($snmp, $zteoid['oidOnuName'] . ".{$encodedIndex}.{$onuIdNum}"));
            $onuModelValue = str_replace(['STRING: ', '"'], '', (string)$this->safeSnmpGet($snmp, $zteoid['oidOnuModel'] . ".{$encodedIndex}.{$onuIdNum}"));
            $onuSnRaw = str_replace(['Hex-STRING: ', 'STRING: ', '"'], '', (string)$this->safeSnmpGet($snmp, $zteoid['oidOnuSn'] . ".{$encodedIndex}.{$onuIdNum}"));
            $onuSnAscii = $this->convertMacToAscii($onuSnRaw);

            $onuDistanceRaw = $this->safeSnmpGet($snmp, $zteoid['oidOnuDistance'] . ".{$encodedIndex}.{$onuIdNum}");
            $onuDistanceValue = str_replace(['INTEGER: ', '"'], '', (string)$onuDistanceRaw);
            if ($onuDistanceValue === '' || strtoupper($onuDistanceValue) === 'N/A') {
                $onuDistanceValue = '-';
            }

            $onuLastOfflineRaw = (string)$this->safeSnmpGet($snmp, $zteoid['oidOnuLastOffline'] . ".{$encodedIndex}.{$onuIdNum}");
            if (strpos($onuLastOfflineRaw, 'STRING:') !== false && strpos($onuLastOfflineRaw, 'Hex-STRING:') === false) {
                $onuLastOfflineValue = trim(str_replace(['STRING: ', '"'], '', $onuLastOfflineRaw));
            } else {
                $onuLastOfflineValue = $this->convertSnmpDateTime(str_replace(['Hex-STRING: ', '"'], '', $onuLastOfflineRaw));
            }
            if (empty($onuLastOfflineValue)) {
                $onuLastOfflineValue = '-';
            }

            $onuLastOnlineRaw = (string)$this->safeSnmpGet($snmp, $zteoid['oidOnuLastOnline'] . ".{$encodedIndex}.{$onuIdNum}");
            if (strpos($onuLastOnlineRaw, 'STRING:') !== false && strpos($onuLastOnlineRaw, 'Hex-STRING:') === false) {
                $onuLastOnlineValue = trim(str_replace(['STRING: ', '"'], '', $onuLastOnlineRaw));
            } else {
                $onuLastOnlineValue = $this->convertSnmpDateTime(str_replace(['Hex-STRING: ', '"'], '', $onuLastOnlineRaw));
            }
            if (empty($onuLastOnlineValue)) {
                $onuLastOnlineValue = '-';
            }

            $onuUptimeRaw = (string)$this->safeSnmpGet($snmp, $zteoid['oidOnuUptime'] . ".{$encodedIndex}.{$onuIdNum}");
            $onUptimeValue = trim((string)preg_replace('/Timeticks:\s*\(\d+\)\s*/i', '', $onuUptimeRaw));
            if ($onUptimeValue === '') {
                $onUptimeValue = '-';
            }

            $rxRaw = $this->safeSnmpGet($snmp, $zteoid['oidOnuRxPower'] . ".{$encodedIndex}.{$onuIdNum}.1");
            $txRaw = $this->safeSnmpGet($snmp, $zteoid['oidOnuTxPower'] . ".{$encodedIndex}.{$onuIdNum}.1");

            $rxInt = null;
            $txInt = null;
            if (preg_match('/(-?\d+)/', (string)$rxRaw, $rxM)) {
                $rxInt = (int)$rxM[1];
            }
            if (preg_match('/(-?\d+)/', (string)$txRaw, $txM)) {
                $txInt = (int)$txM[1];
            }

            $convertPowerFn = $zteoid['convertOpticalPower'] ?? null;
            if (is_callable($convertPowerFn)) {
                $rxDbm = $rxInt !== null ? $convertPowerFn($rxInt, 'onu_rx') : null;
                $txDbm = $txInt !== null ? $convertPowerFn($txInt, 'onu_tx') : null;
            } else {
                $rxDbm = $rxInt !== null ? (($rxInt * 0.002) - 30) : null;
                $txDbm = $txInt !== null ? (($txInt * 0.002) - 30) : null;
            }

            $rxLabel = $rxDbm !== null ? round($rxDbm, 2) : '-';
            $txLabel = $txDbm !== null ? round($txDbm, 2) : '-';

            // Fetch OLT Rx Power using same encoded index method as getOltOnu
            $OltRxPowerOid = $zteoid['oidOltRxPower'] . ".{$encodedIndex}.{$onuIdNum}";
            $OltRxPowerRaw = $this->safeSnmpGet($snmp, $OltRxPowerOid);
            $OltRxInt = null;
            if (preg_match('/(-?\d+)/', (string)$OltRxPowerRaw, $oltRxM)) {
                $OltRxInt = (int)$oltRxM[1];
            }
            $convertPowerFn = $zteoid['convertOpticalPower'] ?? null;
            if ($OltRxInt !== null) {
                if (is_callable($convertPowerFn)) {
                    $OltRxDbm = $convertPowerFn($OltRxInt, 'olt_rx');
                } else {
                    $OltRxDbm = $OltRxInt / 1000;
                }
            } else {
                $OltRxDbm = null;
            }
            $oltRxLabel = $OltRxDbm !== null ? round($OltRxDbm, 2) : '-';

            $modalId = preg_replace('/[^A-Za-z0-9_-]/', '_', $frameSlotPort . '-' . $ontId . '-c600');
            $buttonText = $result_status === 'working' ? ('Rx: ' . $rxLabel . ' | Tx: ' . $txLabel) : $result_status;
            $buttonClass = 'bg-warning';
            if ($result_status === 'working') {
                $buttonClass = 'bg-success';
            } elseif ($result_status === 'los') {
                $buttonClass = 'badge-danger';
            } elseif ($result_status === 'offline') {
                $buttonClass = 'badge-secondary';
            } elseif ($result_status === 'syncMib') {
                $buttonClass = 'badge-info';
            }

            echo '<button id="powerButton" class="btn ' . $buttonClass . ' btn-sm pb-1 ml-2 mr-2 text-white" data-toggle="modal" data-target="#powerModal' . $modalId . '">' . $buttonText . '</button>
            <div class="modal fade" id="powerModal' . $modalId . '">
            <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title"><strong>Status ONU ' . $olt->name . ' ' . $frameSlotPort . ':' . $ontId . '</strong></h5>
            </div>
            <div class="modal-body">
            <p>Onu Name : ' . ($onuNameValue !== '' ? $onuNameValue : '-') . '</p>
            <p>Onu Model : ' . ($onuModelValue !== '' ? $onuModelValue : '-') . '</p>
            <p>Onu Sn : ' . ($onuSnAscii !== '' ? $onuSnAscii : '-') . '</p>
            <p>Onu Rx Power : ' . $rxLabel . ' dBm</p>
            <p>Onu Tx Power : ' . $txLabel . ' dBm</p>
            <p>Onu Cable Length : ' . $onuDistanceValue . ' m</p>
            <p>Olt Rx Power : ' . $oltRxLabel . ' dBm</p>
            <p>Onu Last Offline : ' . $onuLastOfflineValue . '</p>
            <p>Onu Last Online : ' . $onuLastOnlineValue . '</p>
            <p>Onu Uptime : ' . $onUptimeValue . '</p>
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
            </div>
            </div>
            </div>';
            return;
        }

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
