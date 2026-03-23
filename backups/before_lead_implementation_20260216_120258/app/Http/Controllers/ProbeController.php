<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\{Probe, Ping, Alert};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProbeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['push', 'pushPost','alerts','detectAlert']]);
    }

//   public function push(Request $req)

//   {
//     Log::info($req);
//     $this->authorizeProbe($req);

//     $probeId = trim($req->get('probe_id', 'unknown'));
//     $host    = trim($req->get('host', ''));
//     $statusRaw = strtolower(trim($req->get('status', 'unknown')));
//     $rtt     = floatval($req->get('rtt', 0));

//     if ($host === '') {
//         return response()->json(['error' => 'host required'], 422);
//     }

//     // ✅ pastikan hanya 'up' dianggap true, lainnya false
//     $isUp = $statusRaw === 'up';

//     // ✅ Buat / temukan probe
//     $probe = Probe::firstOrCreate(['probe_id' => $probeId]);

//     // ✅ Simpan ping result
//     Ping::create([
//         'probe_id'     => $probe->id,
//         'host'         => $host,
//         'is_up'        => $isUp ? 1 : 0,  // 💡 pastikan eksplisit jadi integer
//         'rtt_avg_ms'   => $rtt ?: null,
//         'loss_percent' => $isUp ? 0 : 100,
//         'polled_at'    => now(),
//     ]);

//     $this->detectAlert($probe, $host, $isUp);

//     return response()->json(['ok' => true]);
// }
    public function push(Request $req)
    {
    // Log seluruh request agar bisa dicek di storage/logs/laravel.log
        // \Log::info('[ProbePush] Incoming request', $req->all());

    // ✅ Otorisasi probe
        $this->authorizeProbe($req);

    // Ambil parameter dari request
        $probeId   = trim($req->get('probe_id', 'unknown'));
        $host      = trim($req->get('host', ''));
    $hostName  = trim($req->get('host_name', '')); // 🆕 ambil host_name manual dari URL
    $statusRaw = strtolower(trim($req->get('status', 'unknown')));
    $rtt       = floatval($req->get('rtt', 0));

    if ($host === '') {
        return response()->json(['error' => 'host required'], 422);
    }

    // ✅ status up = true
    $isUp = $statusRaw === 'up';

    // ✅ Buat atau temukan probe
    $probe = \App\Probe::firstOrCreate(['probe_id' => $probeId]);

    // ✅ Simpan hasil ping
    \App\Ping::create([
        'probe_id'     => $probe->id,
        'host'         => $host,
        'host_name'    => $hostName ?: $host,  // 🆕 fallback ke host jika kosong
        'is_up'        => $isUp ? 1 : 0,
        'rtt_avg_ms'   => $rtt ?: null,
        'loss_percent' => $isUp ? 0 : 100,
        'polled_at'    => now(),
    ]);

    // ✅ Deteksi alert (fungsi kamu sebelumnya)
    $this->detectAlert($probe, $host, $hostName, $isUp);

    return response()->json(['ok' => true]);
}


public function pushPost(Request $req)
{
    $this->authorizeProbe($req);
    $data = $req->all();
    $probe = Probe::firstOrCreate(['probe_id'=>$data['probe_id'] ?? 'unknown']);
    $rows = [];

    foreach ($data['pings'] ?? [] as $p) {
        $rows[] = [
            'probe_id'     => $probe->id,
            'host'         => $p['ip'],
            'is_up'        => (bool)$p['is_up'],
            'rtt_avg_ms'   => $p['rtt_avg_ms'] ?? null,
            'loss_percent' => $p['loss_percent'] ?? null,
            'polled_at'    => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
        $this->detectAlert($probe, $p['ip'], (bool)$p['is_up']);
    }

    if ($rows) DB::table('pings')->insert($rows);

    return response()->json(['ok'=>true,'count'=>count($rows)]);
}



    // public function status()
    // {
    //     $latest = DB::table('pings')
    //     ->select('host', DB::raw('MAX(polled_at) as last_polled'))
    //     ->groupBy('host');

    //     $pings = DB::table('pings')
    //     ->joinSub($latest, 'L', function ($j) {
    //         $j->on('pings.host','=','L.host')
    //         ->on('pings.polled_at','=','L.last_polled');
    //     })
    //     ->leftJoin('probes','probes.id','=','pings.probe_id')
    //     ->orderBy('pings.host')
    //     ->get([
    //         'pings.host','pings.is_up','pings.rtt_avg_ms','pings.loss_percent',
    //         'pings.polled_at','probes.probe_id','probes.name'
    //     ]);

    //     return response()->json($pings);
    // }
public function index()
{
    return view('probe.index');
}

// public function data()
// {
//     $now = now('Asia/Makassar');

//         // Ambil kombinasi unik probe_id + host
//     $pairs = Ping::select('probe_id', 'host')
//     ->distinct()
//     ->orderBy('probe_id')
//     ->orderBy('host')
//     ->get();

//     $result = [];

//     foreach ($pairs as $pair) {
//         $items = Ping::with('probe')
//         ->where('probe_id', $pair->probe_id)
//         ->where('host', $pair->host)
//         ->orderByDesc('polled_at')
//         ->take(30)
//         ->get()
//         ->sortBy('polled_at');

//         if ($items->isEmpty()) continue;

//         $latest = $items->last();
//         $lastUpdate = Carbon::parse($latest->polled_at);
//             $isOffline = $now->diffInSeconds($lastUpdate) > 300; // 5 menit

//             if ($isOffline) {
//                 $status = 'offline';
//             } elseif (!$latest->is_up) {
//                 $status = 'down';
//             } else {
//                 $status = 'online';
//             }

//             $result[] = [
//                'probe_id'     => $latest->probe->id ?? $latest->probe_id,              // 🔸 numeric ID
//     'probe_name'   => $latest->probe->probe_id ?? "Probe#{$latest->probe_id}", // 🔸 readable name
//     'host'         => $latest->host,
//     'rtt_avg_ms'   => (float) $latest->rtt_avg_ms,
//     'is_up'        => (bool) $latest->is_up,
//     'probe_status' => $status,
//     'polled_at'    => $latest->polled_at->toDateTimeString(),
//     'history'      => $items->pluck('rtt_avg_ms')->values(),
//     'time_labels'  => $items->pluck('polled_at')->map(fn($t) => $t->format('H:i:s'))->values(),
// ];
// }


// public function data()
// {
//     $now = now('Asia/Makassar');

//     // Ambil semua kombinasi unik probe_id + host
//     $pairs = Ping::select('probe_id', 'host')
//     ->distinct()
//     ->orderBy('probe_id')
//     ->orderBy('host')
//     ->get();

//     $result = [];

//     foreach ($pairs as $pair) {
//         // 🔹 Ambil 30 data terakhir (tanpa batas waktu)
//         $items = Ping::with('probe')
//         ->where('probe_id', $pair->probe_id)
//         ->where('host', $pair->host)
//         ->orderByDesc('polled_at')
//         ->take(30)
//         ->get()
//         ->sortBy('polled_at');

//         if ($items->isEmpty()) continue;

//         // 🔹 Tentukan status probe berdasarkan data terbaru
//         $latest = $items->last();
//         $lastUpdate = \Carbon\Carbon::parse($latest->polled_at);
//         $isOffline = $now->diffInSeconds($lastUpdate) > 300; // 5 menit

//         if ($isOffline) {
//             $status = 'offline';
//         } elseif (!$latest->is_up) {
//             $status = 'down';
//         } else {
//             $status = 'online';
//         }

//         // 🔹 Ambil data 1 jam terakhir untuk grafik detail
//         $oneHourAgo = $now->copy()->subHour();

//         $detailItems = Ping::where('probe_id', $pair->probe_id)
//         ->where('host', $pair->host)
//         ->where('polled_at', '>=', $oneHourAgo)
//         ->orderBy('polled_at')
//         ->get();

//         // 🔸 Jika probe offline dan tidak ada data 1 jam terakhir,
//         // tambahkan data dummy biar tetap tampil di grafik popup
//         if ($detailItems->isEmpty()) {
//             $detailItems = collect([
//                 (object)[
//                     'polled_at' => $lastUpdate,
//                     'rtt_avg_ms' => 0,
//                     'is_up' => false,
//                 ]
//             ]);
//         }

//         $result[] = [
//             'probe_id'     => $latest->probe->id ?? $latest->probe_id,
//             'probe_name'   => $latest->probe->probe_id ?? "Probe#{$latest->probe_id}",
//             'host'         => $latest->host,
//             'rtt_avg_ms'   => (float) $latest->rtt_avg_ms,
//             'is_up'        => (bool) $latest->is_up,
//             'probe_status' => $status,
//             'polled_at'    => $latest->polled_at->toDateTimeString(),

//             // 🔹 Data untuk sparkline (mini chart)
//             'history'      => $items->pluck('rtt_avg_ms')->values(),
//             'time_labels'  => $items->pluck('polled_at')->map(fn($t) => $t->format('H:i:s'))->values(),

//             // 🔹 Data detail 1 jam terakhir (untuk grafik popup)
//             'history_detail' => $detailItems->map(fn($i) => [
//                 'time'  => \Carbon\Carbon::parse($i->polled_at)->format('H:i:s'),
//                 'rtt'   => $i->rtt_avg_ms ? (float) $i->rtt_avg_ms : 0,
//                 'is_up' => (bool) $i->is_up,
//             ])->values(),
//         ];
//     }

//     return response()->json($result);
// }



public function data()
{
    $now = now('Asia/Makassar');

    // Ambil semua kombinasi unik probe_id + host
    $pairs = \App\Ping::select('probe_id', 'host')
    ->distinct()
    ->orderBy('probe_id')
    ->orderBy('host')
    ->get();

    $result = [];

    foreach ($pairs as $pair) {
        // 🔹 Ambil 30 data terakhir
        $items = \App\Ping::with('probe')
        ->where('probe_id', $pair->probe_id)
        ->where('host', $pair->host)
        ->orderByDesc('polled_at')
        ->take(30)
        ->get()
        ->sortBy('polled_at');

        if ($items->isEmpty()) continue;

        $latest = $items->last();
        $lastUpdate = \Carbon\Carbon::parse($latest->polled_at);
        $isOffline = $now->diffInSeconds($lastUpdate) > 300; // >5 menit tidak update = offline

        if ($isOffline) {
            $status = 'offline';
        } elseif (!$latest->is_up) {
            $status = 'down';
        } else {
            $status = 'online';
        }

        // 🔹 Ambil data 1 jam terakhir untuk popup grafik detail
        $oneHourAgo = $now->copy()->subHour();

        $detailItems = \App\Ping::where('probe_id', $pair->probe_id)
        ->where('host', $pair->host)
        ->where('polled_at', '>=', $oneHourAgo)
        ->orderBy('polled_at')
        ->get();

        // 🔸 Jika offline dan tidak ada data dalam 1 jam terakhir, buat dummy
        if ($detailItems->isEmpty()) {
            $detailItems = collect([
                (object)[
                    'polled_at' => $lastUpdate,
                    'rtt_avg_ms' => 0,
                    'is_up' => false,
                ]
            ]);
        }

        // 🔹 Simpan hasil ke array respon JSON
        $result[] = [
            'probe_id'     => $latest->probe->id ?? $latest->probe_id,
            'probe_name'   => $latest->probe->probe_id ?? "Probe#{$latest->probe_id}",
            'host'         => $latest->host,
            'host_name'    => $latest->host_name ?? $latest->host, // 🆕 tambahkan nama host
            'rtt_avg_ms'   => (float) $latest->rtt_avg_ms,
            'is_up'        => (bool) $latest->is_up,
            'probe_status' => $status,
            'polled_at'    => $latest->polled_at->toDateTimeString(),

            // 🔹 Mini sparkline di card
            'history'      => $items->pluck('rtt_avg_ms')->values(),
            'time_labels'  => $items->pluck('polled_at')->map(fn($t) => $t->format('H:i:s'))->values(),

            // 🔹 History detail untuk popup grafik
            'history_detail' => $detailItems->map(fn($i) => [
                'time'  => \Carbon\Carbon::parse($i->polled_at)->format('H:i:s'),
                'rtt'   => $i->rtt_avg_ms ? (float) $i->rtt_avg_ms : 0,
                'is_up' => (bool) $i->is_up,
            ])->values(),
        ];
    }

    return response()->json($result);
}


// return response()->json($result);
// }
// public function delete(Request $request)
// {
//     $probeId = $request->query('probe');
//     $host = $request->query('host');

//     if (!$probeId || !$host) {
//         return response()->json(['error' => 'Parameter tidak lengkap'], 400);
//     }

//     $deleted = \App\Ping::where('probe_id', $probeId)
//     ->where('host', $host)
//     ->delete();

//     if ($deleted) {
//         return response()->json(['success' => true, 'message' => "Host $host dihapus dari probe $probeId"]);
//     } else {
//         return response()->json(['success' => false, 'message' => 'Tidak ada data yang dihapus'], 404);
//     }
// }




public function delete(Request $request)
{
    // Log detail request masuk
    Log::info('[ProbeDelete] Incoming request', [
        'method'  => $request->method(),
        'url'     => $request->fullUrl(),
        'ip'      => $request->ip(),
        'headers' => [
            'x-requested-with' => $request->header('X-Requested-With'),
            'referer'          => $request->header('referer'),
            'user-agent'       => $request->header('user-agent'),
        ],
        'payload' => collect($request->all())->except(['_token'])->toArray(),
        'query'   => $request->query(),
    ]);

    try {
        // Dukung pengiriman lewat FormData (body) maupun query string
        $probeId = $request->input('probe_id') ?? $request->input('probe') ?? $request->query('probe_id') ?? $request->query('probe');
        $host    = $request->input('host') ?? $request->query('host');

        if (!$probeId || !$host) {
            Log::warning('[ProbeDelete] Missing parameter', ['probe_id' => $probeId, 'host' => $host]);
            return response()->json(['success' => false, 'error' => 'Parameter tidak lengkap'], 400);
        }

        // Proses delete
        $deleted = Ping::where('probe_id', $probeId)
        ->where('host', $host)
        ->delete();

        Log::info('[ProbeDelete] Delete result', [
            'probe_id' => $probeId,
            'host'     => $host,
            'deleted'  => $deleted,
        ]);

        if ($deleted > 0) {
            return response()->json([
                'success' => true,
                'message' => "Host {$host} dihapus dari probe {$probeId}",
                'deleted' => $deleted
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak ada data yang dihapus'
        ], 404);

    } catch (\Throwable $e) {
        Log::error('[ProbeDelete] Exception', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'error'   => 'Terjadi kesalahan server'
        ], 500);
    }
}



public function status()
{
    $latest = DB::table('pings')
    ->select('probe_id', 'host', DB::raw('MAX(polled_at) as last_polled'))
    ->groupBy('probe_id', 'host');

    $pings = DB::table('pings')
    ->joinSub($latest, 'L', function ($j) {
        $j->on('pings.host', '=', 'L.host')
        ->on('pings.probe_id', '=', 'L.probe_id')
        ->on('pings.polled_at', '=', 'L.last_polled');
    })
    ->leftJoin('probes', 'probes.id', '=', 'pings.probe_id')
    ->orderBy('probes.probe_id')
    ->orderBy('pings.host')
    ->get([
        'pings.host',
        'pings.is_up',
        'pings.rtt_avg_ms',
        'pings.loss_percent',
        'pings.polled_at',
        'probes.probe_id',
        'probes.name',
    ])
    ->map(function ($item) {
        $diffMinutes = now()->diffInMinutes(\Carbon\Carbon::parse($item->polled_at));
        $item->probe_status = $diffMinutes > 3 ? 'offline' : 'online';
        $item->age_minutes = $diffMinutes;
        return $item;
    });

    return response()->json($pings);
}


public function alerts()
{
    $alerts = Alert::with('probe:id,probe_id,name')
    ->latest()->limit(100)->get();
    return response()->json($alerts);
}

private function authorizeProbe(Request $req): void
{
    $key = $req->header('X-Probe-Key') ?? $req->get('key');
    $probeKey = tenant_config('probe_key', config('app.probe_key'));
    abort_unless($key && hash_equals($key, $probeKey), 401, 'unauthorized');
}

private function detectAlert(Probe $probe, string $host, string $host_name, bool $status): void
{
    $last = Ping::where('host',$host)
    ->where('probe_id',$probe->id)
    ->latest('polled_at')->skip(1)->first();

    if ($last && $last->is_up !== $status) {
        $state = $status ? 'up' : 'down';
        Alert::create([
            'probe_id' => $probe->id,
            'host'     => $host,
            'host_name'  => $host_name,
            'status'   => $state,
            'message'  => "Host {$host} changed to {$state}",
        ]);
    }
}
}
