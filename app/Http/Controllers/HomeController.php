<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \RouterOS\Client;
use \RouterOS\Query;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
Use GuzzleHttp\Clients;
use Xendit\Xendit;
use \Auth;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function schedule()
    {

       $today =date("Y-m-d");

       $ticket = \App\Ticket::where('date', '=', $today )
       ->inRandomOrder()
       ->get();
       return view ('welcome',['ticket' =>$ticket]);

   }


   public function warestart()
   {

    \App\Whatsapp::wa_restart();
    return redirect()->back()->with('success','Sent Restart Command to WA API ');

}
// public function index()
// {



//     if ((Auth::user()->privilege)=="counter")
//     {   
// 	//	abort(403, 'you do not have permission to access on this serve');
//        return redirect()->to('http://payment.trikamedia.com');
//    }

//    else
//    {





// }
// }


// public function index()
// {
//     $userPrivilege = Auth::user()->privilege;

//     switch ($userPrivilege) {

//         case 'admin':
//             // Data untuk privilege "admin"
//         $today =date("Y-m-d");

//         $ticket = \App\Ticket::orderBy('time', 'ASC')
//         ->where('date', '=', $today )
//         ->get();

//         $ticket_count = \App\Ticket::where('status', '=', 'Open' )
//         ->count();

//         $invoice_count = \App\Suminvoice::where('payment_status', '=', '0' )
//         ->count();

//         $ticket_count_today = \App\Ticket::where('date', '=', $today )
//         ->count();
//         $cust_active = \App\Customer::where('id_status', '=', '2' )

//         ->count();
//         $cust_block = \App\Customer::where('id_status', '=', '4' )

//         ->count();
//         $cust_potensial = \App\Customer::where('id_status', '=', '1' )
//         ->count();
//         $cust_inactive = \App\Customer::where('id_status', '=', '3' )
//         ->count();
//         $invoice_paid = \App\Suminvoice::where('payment_status', '=', '1' )
//         ->where('payment_date', 'like',$today.'%' )
//         ->count();






//         return view ('home',['ticket' =>$ticket, 'ticket_count'=>$ticket_count, 'ticket_count_today'=>$ticket_count_today, 'cust_active'=>$cust_active,'cust_block'=>$cust_block,'cust_potensial'=>$cust_potensial,'cust_inactive'=>$cust_inactive, 'invoice_count' => $invoice_count, 'invoice_paid' => $invoice_paid] );

//         case 'noc':
//             // Data untuk privilege "user"
//                        // Data untuk privilege "admin"
//         $today =date("Y-m-d");

//         $ticket = \App\Ticket::orderBy('time', 'ASC')
//         ->where('date', '=', $today )
//         ->get();

//         $ticket_count = \App\Ticket::where('status', '=', 'Open' )
//         ->count();

//         $invoice_count = \App\Suminvoice::where('payment_status', '=', '0' )
//         ->count();

//         $ticket_count_today = \App\Ticket::where('date', '=', $today )
//         ->count();
//         $cust_active = \App\Customer::where('id_status', '=', '2' )

//         ->count();
//         $cust_block = \App\Customer::where('id_status', '=', '4' )

//         ->count();
//         $cust_potensial = \App\Customer::where('id_status', '=', '1' )
//         ->count();
//         $cust_inactive = \App\Customer::where('id_status', '=', '3' )
//         ->count();
//         $invoice_paid = \App\Suminvoice::where('payment_status', '=', '1' )
//         ->where('payment_date', 'like',$today.'%' )
//         ->count();


//         case 'marketing':
//             // Data untuk privilege "user"
//                        // Data untuk privilege "admin"
//         $today =date("Y-m-d");

//         $ticket = \App\Ticket::orderBy('time', 'ASC')
//         ->where('date', '=', $today )
//         ->get();

//         $ticket_count = \App\Ticket::where('status', '=', 'Open' )
//         ->count();

//         $invoice_count = \App\Suminvoice::where('payment_status', '=', '0' )
//         ->count();

//         $ticket_count_today = \App\Ticket::where('date', '=', $today )
//         ->count();
//         $cust_active = \App\Customer::where('id_status', '=', '2' )

//         ->count();
//         $cust_block = \App\Customer::where('id_status', '=', '4' )

//         ->count();
//         $cust_potensial = \App\Customer::where('id_status', '=', '1' )
//         ->count();
//         $cust_inactive = \App\Customer::where('id_status', '=', '3' )
//         ->count();
//         $invoice_paid = \App\Suminvoice::where('payment_status', '=', '1' )
//         ->where('payment_date', 'like',$today.'%' )
//         ->count();





//         return view ('home',['ticket' =>$ticket, 'ticket_count'=>$ticket_count, 'ticket_count_today'=>$ticket_count_today, 'cust_active'=>$cust_active,'cust_block'=>$cust_block,'cust_potensial'=>$cust_potensial,'cust_inactive'=>$cust_inactive, 'invoice_count' => $invoice_count, 'invoice_paid' => $invoice_paid] );
//         case 'user':
//                        // Data untuk privilege "user"
//                        // Data untuk privilege "admin"
//         $today =date("Y-m-d");

//         $ticket = \App\Ticket::orderBy('time', 'ASC')
//         ->where('date', '=', $today )
//         ->get();

//         $ticket_count = \App\Ticket::where('status', '=', 'Open' )
//         ->count();

//         $invoice_count = \App\Suminvoice::where('payment_status', '=', '0' )
//         ->count();

//         $ticket_count_today = \App\Ticket::where('date', '=', $today )
//         ->count();
//         $cust_active = \App\Customer::where('id_status', '=', '2' )

//         ->count();
//         $cust_block = \App\Customer::where('id_status', '=', '4' )

//         ->count();
//         $cust_potensial = \App\Customer::where('id_status', '=', '1' )
//         ->count();
//         $cust_inactive = \App\Customer::where('id_status', '=', '3' )
//         ->count();
//         $invoice_paid = \App\Suminvoice::where('payment_status', '=', '1' )
//         ->where('payment_date', 'like',$today.'%' )
//         ->count();






//         return view ('home',['ticket' =>$ticket, 'ticket_count'=>$ticket_count, 'ticket_count_today'=>$ticket_count_today, 'cust_active'=>$cust_active,'cust_block'=>$cust_block,'cust_potensial'=>$cust_potensial,'cust_inactive'=>$cust_inactive, 'invoice_count' => $invoice_count, 'invoice_paid' => $invoice_paid] );
//         case 'payment':
//             // Redirect pengguna dengan privilege "counter"
//                         // Data untuk privilege "user"
//                        // Data untuk privilege "admin"
//         $today =date("Y-m-d");

//         $ticket = \App\Ticket::orderBy('time', 'ASC')
//         ->where('date', '=', $today )
//         ->get();

//         $ticket_count = \App\Ticket::where('status', '=', 'Open' )
//         ->count();

//         $invoice_count = \App\Suminvoice::where('payment_status', '=', '0' )
//         ->count();

//         $ticket_count_today = \App\Ticket::where('date', '=', $today )
//         ->count();
//         $cust_active = \App\Customer::where('id_status', '=', '2' )

//         ->count();
//         $cust_block = \App\Customer::where('id_status', '=', '4' )

//         ->count();
//         $cust_potensial = \App\Customer::where('id_status', '=', '1' )
//         ->count();
//         $cust_inactive = \App\Customer::where('id_status', '=', '3' )
//         ->count();
//         $invoice_paid = \App\Suminvoice::where('payment_status', '=', '1' )
//         ->where('payment_date', 'like',$today.'%' )
//         ->count();






//         return view ('home',['ticket' =>$ticket, 'ticket_count'=>$ticket_count, 'ticket_count_today'=>$ticket_count_today, 'cust_active'=>$cust_active,'cust_block'=>$cust_block,'cust_potensial'=>$cust_potensial,'cust_inactive'=>$cust_inactive, 'invoice_count' => $invoice_count, 'invoice_paid' => $invoice_paid] );
//         case 'accounting':
//             // Redirect pengguna dengan privilege "counter"
//                        // Data untuk privilege "user"
//                        // Data untuk privilege "admin"
//         $today =date("Y-m-d");

//         $ticket = \App\Ticket::orderBy('time', 'ASC')
//         ->where('date', '=', $today )
//         ->get();

//         $ticket_count = \App\Ticket::where('status', '=', 'Open' )
//         ->count();

//         $invoice_count = \App\Suminvoice::where('payment_status', '=', '0' )
//         ->count();

//         $ticket_count_today = \App\Ticket::where('date', '=', $today )
//         ->count();
//         $cust_active = \App\Customer::where('id_status', '=', '2' )

//         ->count();
//         $cust_block = \App\Customer::where('id_status', '=', '4' )

//         ->count();
//         $cust_potensial = \App\Customer::where('id_status', '=', '1' )
//         ->count();
//         $cust_inactive = \App\Customer::where('id_status', '=', '3' )
//         ->count();
//         $invoice_paid = \App\Suminvoice::where('payment_status', '=', '1' )
//         ->where('payment_date', 'like',$today.'%' )
//         ->count();






//         return view ('home',['ticket' =>$ticket, 'ticket_count'=>$ticket_count, 'ticket_count_today'=>$ticket_count_today, 'cust_active'=>$cust_active,'cust_block'=>$cust_block,'cust_potensial'=>$cust_potensial,'cust_inactive'=>$cust_inactive, 'invoice_count' => $invoice_count, 'invoice_paid' => $invoice_paid] );

//         case 'vendor':
//         return redirect()->to('/vendorticket');
//         case 'merchant':
//         return redirect()->to('/payment');
//         default:
//             // Default untuk privilege lain
//         abort(403, 'You do not have permission to access this page.');
//     }
// }

public function network(Request $request)
{
    $userPrivilege = strtolower(trim((string) Auth::user()->privilege));

    if ($userPrivilege === 'merchant') {
        return redirect('/payment');
    }

    if ($userPrivilege === 'vendor') {
        return redirect('/vendorticket');
    }

    // Redirect ke dashboard pilihan jika sudah di-set
    if (!in_array($userPrivilege, ['vendor', 'merchant'], true)) {
        $pref = Auth::user()->fresh()->dashboard_preference;
        if ($pref && in_array($pref, ['home-v2', 'home-v3', 'home-v4', 'home-v5', 'home-admin', 'attendance/dashboard'])) {
            return redirect('/' . $pref);
        }
    }

    $date_start = $request->input('date_start') ?? date('Y-m-d');
    $date_end   = $request->input('date_end') ?? date('Y-m-d');
    $dashboardRoles = ['admin', 'noc', 'marketing', 'user', 'payment', 'accounting'];

    // ==========================
    // 🎟️ Tiket per Kategori
    // ==========================
    $ticket_report = \App\Ticket::join('ticketcategories', 'tickets.id_categori', '=', 'ticketcategories.id')
    ->whereBetween('tickets.date', [$date_start, $date_end])
    ->groupBy('ticketcategories.name')
    ->select('ticketcategories.name as name', DB::raw('COUNT(*) as count'))
    ->get();

    // ==========================
    // 💸 Invoice & Customer Stats
    // ==========================
    $invoice_count = \App\Suminvoice::where('payment_status', '0')->count();
    $invoice_paid  = \App\Suminvoice::where('payment_status', '1')
    ->whereBetween('payment_date', [$date_start . ' 00:00:00', $date_end . ' 23:59:59'])
    ->count();

    $cust_active    = \App\Customer::where('id_status', '2')->count();
    $cust_block     = \App\Customer::where('id_status', '4')->count();
    $cust_potensial = \App\Customer::where('id_status', '1')->count();
    $cust_inactive  = \App\Customer::where('id_status', '3')->count();

    // ==========================
    // 🧾 Jumlah Ticket per Status
    // ==========================
    $statuses = ['Open', 'Pending', 'Inprogress', 'Solve', 'Close'];
    $ticket_count_per_status = [];
    foreach ($statuses as $status) {
        $ticket_count_per_status[$status] = \App\Ticket::whereBetween('date', [$date_start, $date_end])
        ->where('status', $status)
        ->count();
    }

    // ==========================
    // 🌐 Network Devices
    // ==========================
    $distrouter = \App\Distrouter::orderBy('name', 'asc')->get();
    $olts = \App\Olt::orderBy('name', 'asc')->get();

    if (!in_array($userPrivilege, $dashboardRoles, true)) {
        return abort(403, 'You do not have permission to access this page.');
    }

    // ==========================
    // 👥 New Customers (This Month)
    // ==========================
    $dailyNewCustomers = \App\Customer::select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('COUNT(*) as new_count')
    )
    ->whereBetween('created_at', [date('Y-m-01'), date('Y-m-t')])
    ->groupBy(DB::raw('DATE(created_at)'))
    ->orderBy('date', 'ASC')
    ->get();

    $totalNewCustomers = $dailyNewCustomers->sum('new_count');

    // ==========================
    // 💰 Daily Transactions (Paid Only)
    // ==========================
    $startOfMonth = date('Y-m-01');
    $endOfMonth   = date('Y-m-t');

    $dailyTransactions = \App\Suminvoice::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
    ->where('payment_status', 1)
    ->selectRaw('DATE(payment_date) as date, COUNT(*) as volume, SUM(recieve_payment) as total_paid')
    ->groupBy(DB::raw('DATE(payment_date)'))
    ->orderBy('date')
    ->get();

    // $ticket = \App\Ticket::orderBy('time', 'ASC')
    // ->whereBetween('tickets.date', [$date_start, $date_end])
    // ->get();
    $ticket = \App\Ticket::orderBy('time', 'ASC')
    ->whereDate('tickets.date', Carbon::today())
    ->get();
    // ==========================
    // 🔚 Kirim ke View
    // ==========================
    return view('home.network', compact(
        'date_start', 'date_end',
        'ticket_report',
        'ticket_count_per_status',
        'invoice_count', 'invoice_paid',
        'cust_active', 'cust_block', 'cust_potensial', 'cust_inactive',
        'distrouter', 'olts',
        'dailyNewCustomers', 'totalNewCustomers',
        'dailyTransactions','ticket'
    ));
}




public function index(Request $request)
{
    $userPrivilege = strtolower(trim((string) Auth::user()->privilege));

    // Redirect ke dashboard preference jika user mengakses /home (default)
    // dan sudah diset preferensinya — kecuali vendor/merchant yang punya aturan sendiri
    if (request()->path() === 'home' && !in_array($userPrivilege, ['vendor','merchant'])) {
        $pref = Auth::user()->fresh()->dashboard_preference;
        if ($pref && in_array($pref, ['home-v2','home-v3','home-v4','home-v5','home-admin','attendance/dashboard'])) {
            return redirect('/' . $pref);
        }
    }

    // Proteksi URL manual ditangani oleh Middleware EnforceDashboardPreference

    $date_start = $request->input('date_start') ?? date('Y-m-d');
    $date_end = $request->input('date_end') ?? date('Y-m-d');
    // Daftar role yang melihat data dashboard yang sama
    $dashboardRoles = ['admin', 'noc', 'marketing', 'user', 'payment', 'accounting'];
    $ticket_report = \App\Ticket::Join('ticketcategories', 'tickets.id_categori', '=', 'ticketcategories.id')
    ->whereBetween('tickets.date', [$date_start, $date_end])
    ->groupBy('id_categori')
    ->select('tickets.id_categori as categori','ticketcategories.name as name', DB::raw("count(tickets.id_categori) as count"))->get();

    $ticket_data = \App\Ticket::join('users', 'tickets.assign_to', '=', 'users.id')
    ->whereBetween('tickets.date', [$date_start, $date_end])
    ->select('users.job_title', 'tickets.status')
    ->get();


// Ambil jumlah tiket per tag pada periode tertentu
    $tags = \App\Tag::withCount(['tickets' => function($q) use ($date_start, $date_end) {
        $q->whereBetween('date', [$date_start, $date_end]);
    }])->get()->map(function($tag) {
        return [
            'name' => $tag->name,
            'total' => $tag->tickets_count,
        ];
    });

// Ambil jumlah tiket tanpa tag pada periode tertentu
    $ticketsWithoutTag = \App\Ticket::whereBetween('date', [$date_start, $date_end])
    ->doesntHave('tags')
    ->count();

    $labels = $tags->pluck('name')->toArray();
    $totals = $tags->pluck('total')->toArray();

    if ($ticketsWithoutTag > 0) {
        $labels[] = 'Tanpa Tag';
        $totals[] = $ticketsWithoutTag;
    }

$tagLabels = $labels; // dari proses sebelumnya
$tagData = $totals;


// Definisikan bobot status
$status_weight = [
    'Open' => 0,
    'Pending' => 0,
    'Inprogress' => 50,
    'Solve' => 90,
    'Close' => 100,
];

// Hitung bobot rata-rata per job_title
$jobTitleScores = [];

foreach ($ticket_data as $ticket) {
    $weight = $status_weight[$ticket->status] ?? 0; // default 0 kalau status tak dikenal
    $job = $ticket->job_title ?? 'Unknown';

    if (!isset($jobTitleScores[$job])) {
        $jobTitleScores[$job] = ['total_weight' => 0, 'count' => 0];
    }

    $jobTitleScores[$job]['total_weight'] += $weight;
    $jobTitleScores[$job]['count']++;
}

// Buat array final dengan persentase rata-rata
$jobTitleProgress = [];

foreach ($jobTitleScores as $job => $data) {
    $avg = $data['count'] > 0 ? round($data['total_weight'] / $data['count']) : 0;
    $jobTitleProgress[] = [
        'job_title' => $job,
        'percent' => $avg,
        'count' => $data['count'],
    ];
}

if (in_array($userPrivilege, $dashboardRoles)) {


    $ticket = \App\Ticket::orderBy('time', 'ASC')
    ->whereBetween('tickets.date', [$date_start, $date_end])
    ->get();

    // $ticket_count = \App\Ticket::where('status', 'Open')->count();
    // $ticket_count_today = \App\Ticket::whereBetween('tickets.date', [$date_start, $date_end])->count();

    $statuses = ['Open', 'Pending', 'Inprogress', 'Solve', 'Close'];
    $ticket_count_per_status = [];

    foreach ($statuses as $status) {
        $ticket_count_per_status[$status] = \App\Ticket::whereBetween('date', [$date_start, $date_end])
        ->where('status', $status)
        ->count();
    }

    $invoice_count = \App\Suminvoice::where('payment_status', '0')->count();
    $invoice_paid = \App\Suminvoice::where('payment_status', '1')
    ->whereBetween('payment_date', [
        $date_start . ' 00:00:00',
        $date_end . ' 23:59:59'
    ])
    ->count();

    $cust_active = \App\Customer::where('id_status', '2')->count();
    $cust_block = \App\Customer::where('id_status', '4')->count();
    $cust_potensial = \App\Customer::where('id_status', '1')->count();
    $cust_inactive = \App\Customer::where('id_status', '3')->count();

    // 📊 Monthly customer growth chart — last 6 months
    // New = id_status 2 (Active) by billing_start
    // Terminate = id_status 4 (Block) by updated_at
    // Inactive  = id_status 3 by updated_at
    $custMonthLabels    = [];
    $custNewMonthly     = [];
    $custBlockMonthly   = [];
    $custInactiveMonthly = [];
    for ($mi = 5; $mi >= 0; $mi--) {
        $m = \Carbon\Carbon::now()->subMonths($mi);
        $custMonthLabels[]     = $m->isoFormat('MMM YY');
        $custNewMonthly[]      = \App\Customer::where('id_status', 2)
                                    ->whereYear('billing_start', $m->year)
                                    ->whereMonth('billing_start', $m->month)->count();
        $custBlockMonthly[]    = \App\Customer::onlyTrashed()
                                    ->whereYear('deleted_at', $m->year)
                                    ->whereMonth('deleted_at', $m->month)->count();
        $custInactiveMonthly[] = \App\Customer::where('id_status', 3)
                                    ->whereYear('updated_at', $m->year)
                                    ->whereMonth('updated_at', $m->month)->count();
    }


// Query semua tiket hari ini beserta job_title dan status-nya
    $ticket_status_per_job = \App\Ticket::join('users', 'tickets.assign_to', '=', 'users.id')
    ->whereBetween('tickets.date', [$date_start, $date_end])
    ->select('users.job_title', 'tickets.status', DB::raw('count(*) as jumlah'))
    ->groupBy('users.job_title', 'tickets.status')
    ->get();

// Bentuk array [job_title][status] = jumlah
    $jobTickets = [];
    foreach ($ticket_status_per_job as $row) {
        $job = $row->job_title ?: 'Unknown';
        $status = $row->status ?: 'Unknown';
        $jobTickets[$job][$status] = $row->jumlah;
    }

    // All-time job title stats (untuk admin overview — tanpa filter tanggal)
    $jobTicketsAllRaw = \App\Ticket::join('users', 'tickets.assign_to', '=', 'users.id')
        ->select('users.job_title', 'tickets.status', DB::raw('count(*) as jumlah'))
        ->groupBy('users.job_title', 'tickets.status')
        ->get();
    $jobTicketsAll = [];
    $jobTicketsAllCounts = [];
    foreach ($jobTicketsAllRaw as $row) {
        $job    = $row->job_title ?: 'Unknown';
        $status = $row->status    ?: 'Unknown';
        $jobTicketsAll[$job][$status] = $row->jumlah;
        $jobTicketsAllCounts[$job] = ($jobTicketsAllCounts[$job] ?? 0) + $row->jumlah;
    }
    $statusWeightAll = ['Close' => 100, 'Solve' => 80, 'Inprogress' => 50, 'Pending' => 20, 'Open' => 0];
    $jobTitleProgressAll = [];
    foreach ($jobTicketsAll as $job => $statuses) {
        $totalWeight = 0;
        $totalCount  = 0;
        foreach ($statuses as $st => $cnt) {
            $totalWeight += ($statusWeightAll[$st] ?? 0) * $cnt;
            $totalCount  += $cnt;
        }
        $jobTitleProgressAll[] = [
            'job_title' => $job,
            'percent'   => $totalCount > 0 ? round($totalWeight / $totalCount) : 0,
            'count'     => $totalCount,
        ];
    }

    // ==========================
    // � Attendance Trend — 14 hari terakhir (untuk admin dashboard)
    // ==========================
    $attendanceTrend = [];
    for ($i = 13; $i >= 0; $i--) {
        $day = Carbon::today()->subDays($i);
        $att = \App\Attendance::whereDate('date', $day->toDateString())->get();
        $attendanceTrend[] = [
            'date'    => $day->isoFormat('D MMM'),
            'present' => $att->whereIn('status', ['present', 'late'])->count(),
            'late'    => $att->where('status', 'late')->count(),
            'absent'  => $att->where('status', 'absent')->count(),
        ];
    }

    // ==========================
    // �👤 My Ticket Data (untuk home-v2 / technician dashboard)
    // ==========================
    $myUserId = Auth::user()->id;
    $myStatuses = ['Open', 'Pending', 'Inprogress', 'Solve', 'Close'];
    $myTicketsByStatus = [];
    foreach ($myStatuses as $st) {
        $myTicketsByStatus[$st] = \App\Ticket::where('assign_to', $myUserId)
            ->where('status', $st)->count();
    }
    $myTicketsTotal     = array_sum($myTicketsByStatus);
    $myTicketsToday     = \App\Ticket::where('assign_to', $myUserId)->whereDate('date', Carbon::today())->count();
    $myTicketsThisWeek  = \App\Ticket::where('assign_to', $myUserId)
        ->whereBetween('date', [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()])->count();
    $myTicketsThisMonth = \App\Ticket::where('assign_to', $myUserId)
        ->whereBetween('date', [date('Y-m-01'), date('Y-m-t')])->count();

    // Tiket aktif saya (belum selesai)
    $myActiveTickets = \App\Ticket::with('customer')
        ->where('assign_to', $myUserId)
        ->whereIn('status', ['Open', 'Pending', 'Inprogress'])
        ->orderBy('date', 'desc')
        ->orderBy('time', 'desc')
        ->limit(20)
        ->get();

    // Timeline hari ini — tiket saya
    $myTicketsTodayList = \App\Ticket::with(['customer', 'steps'])
        ->where('assign_to', $myUserId)
        ->whereDate('date', Carbon::today())
        ->orderBy('time', 'asc')
        ->get();

    // Riwayat tiket saya 7 hari (untuk mini chart)
    $myTicketHistory = \App\Ticket::where('assign_to', $myUserId)
        ->whereBetween('date', [Carbon::now()->subDays(6)->toDateString(), Carbon::today()->toDateString()])
        ->selectRaw('date, count(*) as total')
        ->groupBy('date')
        ->orderBy('date')
        ->pluck('total', 'date');

    $historyDates  = [];
    $historyTotals = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = Carbon::now()->subDays($i)->toDateString();
        $historyDates[]  = Carbon::parse($d)->format('d/m');
        $historyTotals[] = $myTicketHistory[$d] ?? 0;
    }

    // ==========================
    // 👥 Group Ticket Data (untuk home-v3)
    // ==========================
    $myJobTitle   = Auth::user()->job_title;
    $groupUserIds = $myJobTitle
        ? \App\User::where('job_title', $myJobTitle)->pluck('id')
        : collect([$myUserId]);

    $groupTicketsByStatus = [];
    foreach ($myStatuses as $st) {
        $groupTicketsByStatus[$st] = \App\Ticket::whereIn('assign_to', $groupUserIds)
            ->where('status', $st)->count();
    }
    $groupActiveTickets = \App\Ticket::with('customer')
        ->whereIn('assign_to', $groupUserIds)
        ->whereIn('status', ['Open', 'Pending', 'Inprogress'])
        ->orderBy('date', 'desc')
        ->limit(15)
        ->get();
    $groupMemberNames = \App\User::whereIn('id', $groupUserIds)
        ->pluck('name', 'id');

    // OLT & Distrouter list (DB::table to avoid softDeletes bug in models)
    $oltList        = \Illuminate\Support\Facades\DB::table('olts')
        ->whereNull('deleted_at')->orderBy('name')->get();
    $distrouterList = \Illuminate\Support\Facades\DB::table('distrouters')
        ->whereNull('deleted_at')->orderBy('name')->get();

    // Jumlah pelanggan per distrouter berdasarkan status (2=Active/online, 3=Inactive/offline, 4=Block/disabled)
    $distrouterStats = \Illuminate\Support\Facades\DB::table('customers')
        ->whereNull('customers.deleted_at')
        ->whereNotNull('id_distrouter')
        ->selectRaw('id_distrouter,
            SUM(CASE WHEN id_status = 2 THEN 1 ELSE 0 END) as online,
            SUM(CASE WHEN id_status = 3 THEN 1 ELSE 0 END) as offline,
            SUM(CASE WHEN id_status = 4 THEN 1 ELSE 0 END) as disabled')
        ->groupBy('id_distrouter')
        ->get()
        ->keyBy('id_distrouter');

    // 📊 Lead / Marketing Data (untuk home-v4)
    $leadStages = \App\LeadWorkflow::orderBy('order')->get();
    $leadsByStage = [];
    foreach ($leadStages as $stage) {
        $leadsByStage[$stage->id] = \App\Customer::where('workflow_stage_id', $stage->id)->count();
    }
    $leadsTotal     = \App\Customer::whereNotNull('workflow_stage_id')->count();
    $leadsMyCount   = \App\Customer::whereNotNull('workflow_stage_id')
                        ->where('id_sale', $myUserId)->count();
    $leadsConverted = \App\Customer::whereNotNull('converted_at')
                        ->whereMonth('converted_at', date('m'))
                        ->whereYear('converted_at', date('Y'))->count();
    $leadsLost      = \App\Customer::whereNotNull('lost_at')
                        ->whereMonth('lost_at', date('m'))
                        ->whereYear('lost_at', date('Y'))->count();
    $leadsMyActive  = \App\Customer::with('workflowStage')
                        ->where('id_sale', $myUserId)
                        ->whereNotNull('workflow_stage_id')
                        ->whereNull('converted_at')
                        ->whereNull('lost_at')
                        ->orderByRaw('expected_close_date IS NULL, expected_close_date ASC')
                        ->limit(15)->get();
    $allActiveLeads = \App\Customer::with(['workflowStage'])
                        ->whereNotNull('workflow_stage_id')
                        ->whereNull('converted_at')
                        ->whereNull('lost_at')
                        ->orderByRaw('expected_close_date IS NULL, expected_close_date ASC')
                        ->limit(20)->get();
    $recentLeadActivity = \App\LeadUpdate::with('customer')
                        ->orderBy('created_at', 'desc')->limit(10)->get();

    // 💰 Accounting / Transaction Data (for home-v5)
    $acctToday        = \Carbon\Carbon::today();
    $acctStartOfWeek  = \Carbon\Carbon::now()->startOfWeek();
    $acctEndOfWeek    = \Carbon\Carbon::now()->endOfWeek();
    $acctStartOfMonth = \Carbon\Carbon::now()->startOfMonth();
    $acctEndOfMonth   = \Carbon\Carbon::now()->endOfMonth();
    $acctTotalReceivable  = \App\Suminvoice::where('payment_status', 0)->sum('total_amount');
    $acctPaymentToday     = \App\Suminvoice::whereDate('payment_date', $acctToday)->sum('recieve_payment');
    $acctPaymentThisWeek  = \App\Suminvoice::whereBetween('payment_date', [$acctStartOfWeek, $acctEndOfWeek])->sum('recieve_payment');
    $acctPaymentThisMonth = \App\Suminvoice::whereBetween('payment_date', [$acctStartOfMonth, $acctEndOfMonth])->sum('recieve_payment');
    $acctDailyTransactions = \App\Suminvoice::whereBetween('payment_date', [$acctStartOfMonth, $acctEndOfMonth])
        ->where('payment_status', 1)
        ->selectRaw('DATE(payment_date) as date, COUNT(*) as volume, SUM(recieve_payment) as total_paid')
        ->groupBy(DB::raw('DATE(payment_date)'))
        ->orderBy('date')->get();
    $acctGroupedByUser = \App\Suminvoice::whereBetween('payment_date', [$acctStartOfMonth, $acctEndOfMonth])
        ->where('payment_status', 1)
        ->join('users', 'suminvoices.updated_by', '=', 'users.id')
        ->select('users.name as user_name', DB::raw('COUNT(*) as trx_count'), DB::raw('SUM(suminvoices.recieve_payment) as total_payment'))
        ->groupBy('users.name')->orderByDesc('total_payment')->get();
    $acctRecentPaid = \App\Suminvoice::with('customer')
        ->where('payment_status', 1)
        ->orderBy('payment_date', 'desc')
        ->limit(10)->get();
    $acctUnpaidTop = \App\Suminvoice::with('customer')
        ->where('payment_status', 0)
        ->orderByRaw('due_date IS NULL, due_date ASC')
        ->limit(10)->get();

    $acctPaidCount   = \App\Suminvoice::where('payment_status', 1)->count();
    $acctUnpaidCount = \App\Suminvoice::where('payment_status', 0)->count();
    $acctCancelCount = \App\Suminvoice::where('payment_status', 2)->count();
    $acctTotalCount  = $acctPaidCount + $acctUnpaidCount + $acctCancelCount;

    return view(
        request()->is('home-admin') || request()->get('preview') === 'admin' ? 'home_admin' :
        (request()->is('home-v5') || request()->get('preview') === 'v5' ? 'home_v5' :
        (request()->is('home-v4') || request()->get('preview') === 'v4' ? 'home_v4' :
        (request()->is('home-v3') || request()->get('preview') === 'v3' ? 'home_v3' :
        (request()->is('home-v2') || request()->get('preview') === 'v2' ? 'home_v2' : 'home')))),
        compact(
        'tagLabels', 'tagData', 'ticket_count_per_status', 'date_start', 'date_end',
        'ticket',
        'invoice_count', 'invoice_paid',
        'cust_active', 'cust_block', 'cust_potensial', 'cust_inactive',
        'ticket_report', 'ticket_data', 'jobTitleScores', 'jobTitleProgress', 'jobTickets',
        'jobTicketsAll', 'jobTitleProgressAll',
        'attendanceTrend',
        // my ticket data
        'myTicketsByStatus', 'myTicketsTotal', 'myTicketsToday',
        'myTicketsThisWeek', 'myTicketsThisMonth',
        'myActiveTickets', 'myTicketsTodayList',
        'historyDates', 'historyTotals',
        // group / network data (home-v3)
        'myJobTitle', 'groupUserIds', 'groupMemberNames',
        'groupTicketsByStatus', 'groupActiveTickets',
        'oltList', 'distrouterList', 'distrouterStats',
        // lead / marketing data (home-v4)
        'leadStages', 'leadsByStage', 'leadsTotal', 'leadsMyCount',
        'leadsConverted', 'leadsLost', 'leadsMyActive', 'allActiveLeads',
        'recentLeadActivity',
        // customer growth chart
        'custMonthLabels', 'custNewMonthly', 'custBlockMonthly', 'custInactiveMonthly',
        // accounting data (home-v5)
        'acctTotalReceivable', 'acctPaymentToday', 'acctPaymentThisWeek', 'acctPaymentThisMonth',
        'acctDailyTransactions', 'acctGroupedByUser', 'acctRecentPaid', 'acctUnpaidTop',
        'acctPaidCount', 'acctUnpaidCount', 'acctCancelCount', 'acctTotalCount'
    ));
}

    // Redirect khusus
return match ($userPrivilege) {
    'vendor'   => redirect()->to('/vendorticket'),
    'merchant' => redirect()->to('/payment'),
    default    => abort(403, 'You do not have permission to access this page.'),
};
}

public function jobScheduleAjax(Request $request)
{
    $date_start = $request->input('date_start', date('Y-m-d'));
    $date_end = $request->input('date_end', date('Y-m-d'));
    $page = (int) $request->input('page', 1);
    $perPage = 10;

    $tickets = \App\Ticket::with(['user', 'customer', 'steps'])
    ->whereBetween('date', [$date_start, $date_end])
    ->orderBy('time', 'ASC')
    ->skip(($page - 1) * $perPage)
    ->take($perPage + 1)
    ->get();

    $hasMore = $tickets->count() > $perPage;
    $tickets = $tickets->take($perPage);

    $html = view('partials.timeline_items', compact('tickets'))->render();
    return response()->json([
        'html' => $html,
        'hasMore' => $hasMore
    ]);
}


public function mikrotik()
{






    try {

        $client = new Client([
            'host' => '103.156.74.1',
            'user' => 'duwija',
            'pass' => 'rh4ps0dy',
            'port' =>  8787
        ]);



// Create "where" Query object for RouterOS
        $query =
    // (new Query('/ip/hotspot/ip-binding/print'))
    //     ->where('mac-address', 'B0:4E:26:44:B5:35');


// (new Query('/ppp/secret/add '))
//         ->equal('name', 'mikrotikApi')
//         ->equal('password', 'mikrotikapi')
//         ->equal('comment', 'testcomment');

        (new Query('/ppp/secret/print'))

        ->where('name', 'mikrotikApi');


        $secrets = $client->query($query)->read();


        echo "Before update" . PHP_EOL;


        foreach ($secrets as $secret) {

    // Change password
            $query = (new Query('/ppp/secret/set'))
            ->equal('.id', $secret['.id'])
            ->equal('disabled', 'false')
            ->equal('comment', 'enable by');

    // Update query ordinary have no return
            $client->query($query)->read();
            echo "User Was  disabled" . PHP_EOL;
    //print_r($secret['disabled']);



        }

// Send query and read response from RouterOS
// $response = $client->query($query)->read();

// var_dump($response);


    } catch (Exception $ex) {
        abort(404, 'Github Repository not found');
    }









}








// test only



public function mikrotik_addsecreate()
{

 try {

    $client = new Client([
            //to login to api
        'host' => '202.169.255.3',
        'user' => 'duwija',
        'pass' => 'rh4ps0dy',
        'port' => 8728,
            //data


    ]);
    $cid      = 'testing';
    $cidpass   = '1234';
    $comment   = 'comment';




// check user exist 
    $query_check =

    (new Query('/ppp/secret/print'))

    ->where('name',$cid);

    $users = $client->query($query_check)->read();


//var_dump($users);
            // if user exist
    if (!empty($users[0]['.id'])) {
            // set the user enable
       foreach ($users as $user) {

    // enable
        $query_enable = (new Query('/ppp/secret/set'))
        ->equal('.id', $user['.id'])
        ->equal('disabled', 'false');


        $result = $client->query($query_enable)->read();

// echo $result;

    }
}

else
{

    $query_add =

    (new Query('/ppp/secret/add '))
    ->equal('name', $cid)
    ->equal('password', $cidpass)
    ->equal('comment', $comment)
    ->equal('profile', 'UPTO_20MBPS');


    $response = $client->query($query_add)->read();

}

} catch (Exception $ex) {
    abort(404, 'Github Repository not found');
}


}

public function mikrotik_disablesecreate()
{

 try {

    $client = new Client([
            //to login to api
        'host' => '103.156.75.19',
        'user' => 'duwija',
        'pass' => 'rh4ps0dy',
        'port' => 8787,
            //data


    ]);
    $cid      = '121212';





// check user exist 
    $query_check =

    (new Query('/ppp/secret/print'))

    ->where('name',$cid);

    $users = $client->query($query_check)->read();


//var_dump($users);
            // if user exist
    if (!empty($users[0]['.id'])) {
            // set the user enable
       foreach ($users as $user) {

    // enable
        $query_enable = (new Query('/ppp/secret/set'))
        ->equal('.id', $user['.id'])
        ->equal('disabled', 'true');


        $result = $client->query($query_enable)->read();

// echo $result;

    }
}


} catch (Exception $ex) {
    abort(404, 'Github Repository not found');
}


}

public function mikrotik_statussecreate()
{

 try {

    $client = new Client([
            //to login to api
        'host' => '103.156.75.19',
        'user' => 'duwija',
        'pass' => 'rh4ps0dy',
        'port' => 8787,
            //data


    ]);
    $cid      = '121212';





// check user exist 
    $query_check =

    (new Query('/ppp/secret/print'))

    ->where('name',$cid);

    $users = $client->query($query_check)->read();


//var_dump($users);
            // if user exist
    if (!empty($users[0]['.id'])) {
            // set the user enable
       foreach ($users as $user) {

    // enable
        $query_enable = (new Query('/ppp/secret/set'))
        ->equal('.id', $user['.id'])
        ->equal('disabled', 'true');


        $result = $client->query($query_enable)->read();

// echo $result;

    }
}


} catch (Exception $ex) {
    abort(404, 'Github Repository not found');
}


}


public function mikrotik_status()
{
    $result = 'unknow';

    try {

        $client = new Client([
                //to login to api
            'host' => '202.169.245.1',
            'user' => 'duwija',
            'pass' => 'rh4ps0dyv01c3#$',
            'port' => 8787,
        ]);

        $query =
        (new Query('/ppp/active/print'))
        ->where('name', 'novia@fam');


        $response = $client->query($query)->read();
          //  var_dump ($response);

        foreach ($response as $response) {
            $result = $response['uptime'];
        }
        if (!empty($result))
        {
            $status ='Online : '. $response['uptime'];
        }

        else
        {
            $status = 'Offline';
        }


    } catch (Exception $ex) {
        $status = 'Unknow';
    }


    return $status;

}




//++++++++++++++++++++++++++++++++++++++++++


public function mikrotik_addprofile()
{

 try {

    $client = new Client([
            //to login to api
        'host' => '103.156.75.19',
        'user' => 'duwija',
        'pass' => 'rh4ps0dy',
        'port' => 8787,
            //data


    ]);
    $name      = 'UPTO_20M';
    $limit   = '10M/10M 20M/20M 7680k/7680k 16/16 8 5M/5M';
    $comment   = 'comment';




// check user exist 
    $query_check =

    (new Query('/ppp/profile/print'))

    ->where('name',$name);

    $profiles = $client->query($query_check)->read();


//var_dump($users);
            // if user exist
    if (!empty($profiles[0]['.id'])) {
            // set the user enable
       foreach ($profiles as $profile) {

    // enable
        $query_enable = (new Query('/ppp/profile/set'))
        ->equal('.id', $profile['.id'])
        ->equal('disabled', 'false');


        $result = $client->query($query_enable)->read();

// echo $result;

    }
}

else
{

    $query_add =

    (new Query('/ppp/profile/add '))
    ->equal('name',$name)
    ->equal('rate-limit', $limit)
    ->equal('comment', $comment);


    $response = $client->query($query_add)->read();

}

} catch (Exception $ex) {
    abort(404, 'Github Repository not found');
}


}




public function wa()
{
  $client = new Clients(); 
  $result = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
    'form_params' => [
        'api_key' => env('WAPISENDER_KEY'),
        'device_key' => env('WAPISENDER_PAYMENT'),
    ]
]);

  $result= $result->getBody();
  $array = json_decode($result, true);
  $message=$array['message'];
  if ($array['message'] == "Device disconnect")
  {
    $result = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
        'form_params' => [
            'api_key' => env('WAPISENDER_KEY'),
            'device_key' => env('WAPISENDER_PAYMENT'),
        ]
    ]);
    $result= $result->getBody();
    $array = json_decode($result, true);
    $message=$array['data']['connection'];

}

return($message);
}

public function wa_payment($phone, $message)
{

    $client = new Clients(); 
    $result = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
        'form_params' => [
            'api_key' => env('WAPISENDER_KEY'),
            'device_key' => env('WAPISENDER_PAYMENT'),
        // 'group_id' => '3013',
            'destination' => $phone,
            'message' => $message,
        ]
    ]);

    echo $result->getStatusCode();
        // 200
    $result->getHeader('content-type');
        // // 'application/json; charset=utf8'
    echo $result->getBody();

}

public function xendit()
{


    Xendit::setApiKey('xnd_development_jDSCoSFFyJpKTkyIXg7Je6frM1Cz4QnQkJn5pKZv5q6ZEHOqlWazGM5jgAGHL9');

    $params = ['external_id' => '11111111111',
    'payer_email' => 'sample_email@xendit.co',
    'description' => 'Trip to Bali',
    'amount' => 4000000
];

$createInvoice = \Xendit\Invoice::create($params);
$array = json_decode(json_encode($createInvoice, true));
dd ($array);


}


}
