<?php


namespace App\Http\Controllers;
use App\Akun;
use App\Customer;
use App\Contact;
use App\Jurnal;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Exports\NeracaSaldoExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ArusKasExport;

use App\Helper\LabaRugiService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class JurnalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
      $this->middleware('auth');
    }

    public function contactJurnal($contact_id)
    {
      $contact = Contact::where('contact_id', $contact_id)->firstOrFail();
    // Ambil semua jurnal, lalu group by code
      $jurnals = Jurnal::with('akun')
      ->where('contact_id', $contact->contact_id)
      ->orderBy('date', 'asc')
      ->get()
        ->groupBy('code'); // 🔑 Kelompokkan berdasarkan code

    // Total keseluruhan
        $totalDebet  = $jurnals->flatten()->sum('debet');
        $totalKredit = $jurnals->flatten()->sum('kredit');

        return view('jurnal.contact', [
          'contact'    => $contact,
          'jurnals'     => $jurnals,
          'totalDebet'  => $totalDebet,
          'totalKredit' => $totalKredit,
        ]);
      }

      public function customerJurnal($id)
      {
       $customer = Customer::findOrFail($id);

    // Ambil semua jurnal yang berhubungan dengan customer ini:
    // 1. Jurnal dari invoice/jumum (contact_id = customer->id, category null)
    // 2. Jurnal dari kas masuk/keluar/general (category = 'customer', contact_id = customer->id)
       $jurnals = Jurnal::with('akun')
       ->where('contact_id', $customer->id)
       ->orderBy('date', 'asc')
       ->orderBy('code', 'asc')
       ->get()
        ->groupBy('code'); // 🔑 Kelompokkan berdasarkan code

    // Total keseluruhan
        $totalDebet  = $jurnals->flatten()->sum('debet');
        $totalKredit = $jurnals->flatten()->sum('kredit');

        return view('jurnal.customer', [
          'customer'    => $customer,
          'jurnals'     => $jurnals,
          'totalDebet'  => $totalDebet,
          'totalKredit' => $totalKredit,
        ]);
      }

      public function laporanArusKas(Request $request)
      {
        $data = $this->generateArusKasData($request);
        return view('jurnal.arus_kas', $data);
      }

      public function exportArusKasPdf(Request $request)
      {
        $data = $this->generateArusKasData($request);
        $pdf = Pdf::loadView('jurnal.arus_kas_pdf', $data)->setPaper('A4', 'portrait');
        return $pdf->download('Laporan_Arus_Kas.pdf');
      }

      public function exportArusKasExcel(Request $request)
      {
        $data = $this->generateArusKasData($request);
        return Excel::download(new ArusKasExport($data), 'Laporan_Arus_Kas.xlsx');
      }

      private function generateArusKasData(Request $request)
      {
        $tanggalAwal  = $request->input('tanggal_awal', now()->startOfMonth()->toDateString());
        $tanggalAkhir = $request->input('tanggal_akhir', now()->endOfMonth()->toDateString());
        $mode         = $request->input('mode', 'direct');

    // Saldo Awal
        $saldoAwal = DB::table('jurnals')
        ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
        ->where('akuns.category', 'kas & bank')
        ->where('jurnals.date', '<', $tanggalAwal)
        ->whereNull('jurnals.deleted_at')
        ->whereNull('akuns.deleted_at')
        ->selectRaw('SUM(debet - kredit) as saldo')
        ->value('saldo') ?? 0;

    // Saldo Akhir
        $saldoAkhir = DB::table('jurnals')
        ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
        ->where('akuns.category', 'kas & bank')
        ->where('jurnals.date', '<=', $tanggalAkhir)
        ->whereNull('jurnals.deleted_at')
        ->whereNull('akuns.deleted_at')
        ->selectRaw('SUM(debet - kredit) as saldo')
        ->value('saldo') ?? 0;

    // Ambil SEMUA transaksi jurnal periode untuk analisis lawan akun
        $transaksiKas = DB::table('jurnals as j1')
        ->join('akuns as a1', 'j1.id_akun', '=', 'a1.akun_code')
        ->leftJoin('jurnals as j2', function($join) {
            $join->on('j1.code', '=', 'j2.code')
                 ->on('j1.id_akun', '!=', 'j2.id_akun')
                 ->whereNotNull('j1.code');
        })
        ->leftJoin('akuns as a2', 'j2.id_akun', '=', 'a2.akun_code')
        ->where('a1.category', 'kas & bank')
        ->whereBetween('j1.date', [$tanggalAwal, $tanggalAkhir])
        ->whereNull('j1.deleted_at')
        ->whereNull('a1.deleted_at')
        ->select(
            'j1.*',
            'a1.name as akun_kas',
            'a1.category as kas_category',
            'a2.akun_code as lawan_akun_code',
            'a2.name as lawan_akun_name',
            'a2.category as lawan_category',
            'a2.group as lawan_group'
        )
        ->orderBy('j1.date')
        ->get();

    // Kategorisasi transaksi yang lebih detail
        $detailOperasional = [];
        $detailInvestasi = [];
        $detailPendanaan = [];
        
        $totalOperasional = 0;
        $totalInvestasi = 0;
        $totalPendanaan = 0;

        foreach ($transaksiKas as $trx) {
            $nilai = $trx->debet - $trx->kredit;
            $kategori = $this->kategorikanArusKas($trx->lawan_category, $trx->lawan_group, $trx->description);
            
            $item = [
                'date' => $trx->date,
                'no_ref' => $trx->code,
                'description' => $trx->description,
                'akun_kas' => $trx->akun_kas,
                'lawan_akun' => $trx->lawan_akun_name ?? 'Tidak ada lawan',
                'lawan_category' => $trx->lawan_category,
                'nilai' => $nilai
            ];
            
            if ($kategori === 'operasional') {
                $detailOperasional[] = $item;
                $totalOperasional += $nilai;
            } elseif ($kategori === 'investasi') {
                $detailInvestasi[] = $item;
                $totalInvestasi += $nilai;
            } else {
                $detailPendanaan[] = $item;
                $totalPendanaan += $nilai;
            }
        }

    // Metode Langsung dengan detail
        $arusKasDirect = [
            'operasional' => $totalOperasional,
            'investasi' => $totalInvestasi,
            'pendanaan' => $totalPendanaan
        ];

    // Metode Tidak Langsung
        $labaPendapatan = DB::table('jurnals')
        ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
        ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
        ->whereIn('akuns.category', ['pendapatan', 'pendapatan lainnya'])
        ->whereNull('jurnals.deleted_at')
        ->whereNull('akuns.deleted_at')
        ->selectRaw('SUM(kredit - debet) as total')
        ->value('total') ?? 0;

        $labaBeban = DB::table('jurnals')
        ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
        ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
        ->whereIn('akuns.category', ['beban', 'beban lainnya', 'harga pokok penjualan'])
        ->whereNull('jurnals.deleted_at')
        ->whereNull('akuns.deleted_at')
        ->selectRaw('SUM(debet - kredit) as total')
        ->value('total') ?? 0;

        $labaBersih = $labaPendapatan - $labaBeban;

        $penyusutan = DB::table('jurnals')
        ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
        ->where(function($q) {
            $q->where('akuns.name', 'like', '%penyusutan%')
              ->orWhere('akuns.name', 'like', '%depresiasi%')
              ->orWhere('akuns.name', 'like', '%amortisasi%');
        })
        ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
        ->whereNull('jurnals.deleted_at')
        ->whereNull('akuns.deleted_at')
        ->selectRaw('SUM(debet - kredit) as nilai')
        ->value('nilai') ?? 0;

        // Perubahan modal kerja (piutang, hutang, persediaan)
        $perubahanPiutang = DB::table('jurnals')
        ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
        ->where('akuns.category', 'akun piutang')
        ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
        ->whereNull('jurnals.deleted_at')
        ->whereNull('akuns.deleted_at')
        ->selectRaw('SUM(debet - kredit) as nilai')
        ->value('nilai') ?? 0;

        $perubahanHutang = DB::table('jurnals')
        ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
        ->where('akuns.category', 'akun hutang')
        ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
        ->whereNull('jurnals.deleted_at')
        ->whereNull('akuns.deleted_at')
        ->selectRaw('SUM(kredit - debet) as nilai')
        ->value('nilai') ?? 0;

        $perubahanPersediaan = DB::table('jurnals')
        ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
        ->where('akuns.category', 'persediaan')
        ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
        ->whereNull('jurnals.deleted_at')
        ->whereNull('akuns.deleted_at')
        ->selectRaw('SUM(debet - kredit) as nilai')
        ->value('nilai') ?? 0;

        $kasOperasionalIndirect = $labaBersih + $penyusutan - $perubahanPiutang + $perubahanHutang - $perubahanPersediaan;

        $arusKasIndirect = [
          'operasional' => $kasOperasionalIndirect,
          'investasi'   => $totalInvestasi,
          'pendanaan'   => $totalPendanaan,
        ];

        return compact(
            'mode',
            'arusKasDirect',
            'arusKasIndirect',
            'detailOperasional',
            'detailInvestasi',
            'detailPendanaan',
            'saldoAwal',
            'saldoAkhir',
            'tanggalAwal',
            'tanggalAkhir',
            'labaBersih',
            'penyusutan',
            'perubahanPiutang',
            'perubahanHutang',
            'perubahanPersediaan'
        );
      }

      /**
       * Kategorikan transaksi arus kas berdasarkan akun lawan
       */
      private function kategorikanArusKas($lawaCategory, $lawanGroup, $description)
      {
          // Aktivitas Investasi
          if (in_array(strtolower($lawaCategory ?? ''), ['aktiva tetap', 'aset tetap', 'investasi'])) {
              return 'investasi';
          }
          
          // Aktivitas Pendanaan
          if (in_array(strtolower($lawaCategory ?? ''), ['ekuitas', 'akun hutang', 'kewajiban lancar lainnya', 'kewajiban jangka panjang'])) {
              // Kecuali hutang dagang/supplier (ini operasional)
              if (stripos($description, 'supplier') !== false || 
                  stripos($description, 'vendor') !== false ||
                  stripos($description, 'hutang dagang') !== false) {
                  return 'operasional';
              }
              return 'pendanaan';
          }
          
          // Aktivitas Operasional (default)
          // Meliputi: pendapatan, beban, piutang, hutang dagang, persediaan
          return 'operasional';
      }





    // public function laporanRugiLaba(Request $request)
    // {
    // // Ambil tanggal awal dan akhir dari request
    //   $tanggalAwal = $request->input('tanggal_awal', now()->startOfMonth()->format('Y-m-d'));
    //   $tanggalAkhir = $request->input('tanggal_akhir', now()->endOfMonth()->format('Y-m-d'));

    // // Ambil data pendapatan
    //   $pendapatan = \App\Akun::with(['transactions' => function ($query) use ($tanggalAwal, $tanggalAkhir) {
    //     $query->whereBetween('date', [$tanggalAwal, $tanggalAkhir]);
    //   }])
    //   ->where('category', 'LIKE', '%pendapatan%')
    //   ->get();

    // // Hitung saldo awal untuk pendapatan
    //   foreach ($pendapatan as $akun) {
    //     $akun->saldo_awal = $akun->transactions()
    //     ->where('date', '<', $tanggalAwal)
    //     ->sum(DB::raw('kredit - debet'));
    //   }

    // // Ambil data beban
    //   $beban = \App\Akun::with(['transactions' => function ($query) use ($tanggalAwal, $tanggalAkhir) {
    //     $query->whereBetween('date', [$tanggalAwal, $tanggalAkhir]);
    //   }])
    //   ->where('category', 'LIKE', '%beban%')
    //   ->orWhere('category', 'LIKE', '%harga pokok%')
    //   ->orWhere('category', 'LIKE', '%depresiasi%')
    //   ->get();

    // // Hitung saldo awal untuk beban
    //   foreach ($beban as $akun) {
    //     $akun->saldo_awal = $akun->transactions()
    //     ->where('date', '<', $tanggalAwal)
    //     ->sum(DB::raw('debet - kredit'));
    //   }

    // // Hitung total pendapatan
    //   $totalPendapatan = $pendapatan->reduce(function ($carry, $item) {
    //     $saldoTransaksi = $item->transactions->sum('kredit') - $item->transactions->sum('debet');
    //     return $carry + ($item->saldo_awal + $saldoTransaksi);
    //   }, 0);

    // // Hitung total beban
    //   $totalBeban = $beban->reduce(function ($carry, $item) {
    //     $saldoTransaksi = $item->transactions->sum('debet') - $item->transactions->sum('kredit');
    //     return $carry + ($item->saldo_awal + $saldoTransaksi);
    //   }, 0);

    // // Hitung laba/rugi
    //   $labaRugi = $totalPendapatan - $totalBeban;

    // // Return data ke view
    //   return view('jurnal.rugi_laba', compact('pendapatan', 'beban', 'totalPendapatan', 'totalBeban', 'labaRugi', 'tanggalAwal', 'tanggalAkhir'));
    // }
      public function kasbank(Request $request)
      {
        // Ambil transaksi hari ini
       $date_from = $request->input('date_from', \Carbon\Carbon::today()->toDateString());
       $date_to   = $request->input('date_to', \Carbon\Carbon::today()->toDateString());
       $transactions = DB::table('jurnals')
       ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
        ->where('akuns.category', 'kas & bank') // Filter hanya kategori Kas & Bank
        ->whereBetween('jurnals.date', [$date_from, $date_to])
        ->select('jurnals.*') // Ambil semua kolom dari jurnals
        ->get();

        $transactionsByAccount = DB::table('jurnals')
        ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
        ->where('akuns.category', 'kas & bank')
        ->whereBetween('jurnals.date', [$date_from, $date_to])
        ->selectRaw('
          jurnals.id_akun, 
          akuns.name AS akun_name,
          SUM(jurnals.debet) AS total_debit, 
          SUM(jurnals.kredit) AS total_kredit,
          (SUM(jurnals.debet) - SUM(jurnals.kredit)) AS saldo
          ')
        ->groupBy('jurnals.id_akun', 'akuns.name')
        ->get();

        // Hitung posisi kas bank (total debit - total kredit)
        $totalDebit = $transactions->sum('debet');
        $totalKredit = $transactions->sum('kredit');
        $saldo = $totalDebit - $totalKredit;
        
        // Data untuk Chart.js
        $chartData = [
          'labels' => ['Debit', 'Kredit'],
          'datasets' => [
            [
              'label' => 'Posisi Kas Bank',
              'data' => [$totalDebit, $totalKredit],
              'backgroundColor' => ['#28a745', '#dc3545'],
            ],
          ],
        ];

        return view('jurnal/kasbank', compact('transactions', 'saldo', 'chartData','transactionsByAccount' ,'date_from', 'date_to'));
      }





      public function index()
      {
        //
        $from=date('Y-m-1');
        $to=date('y-m-d');
      // $jurnal = \App\jurnal::orderBy('id','ASC')
      // ->Where('type','jumum')
      // ->orWhere('type','general')
      // ->get();
        $akuntransaction = \App\Akuntransaction::pluck('name', 'id', 'debet');

        //$accounting = \App\accounting::orderBy('id','ASC')->get();
       //$acccategory = \App\Accountingcategorie::pluck('name', 'id');
        $nsaldo = \App\Jurnal::groupBy('id_akun')->select('id_akun', \DB::raw('sum(debet) as debet'), \DB::raw('sum(kredit) as kredit') )
        ->Where('type','jumum')
        ->orWhere('type','closed')
        ->get();

//       $nrugilaba = \App\Jurnal::groupBy('jurnals.id_akun')->join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
//       ->select('jurnals.id_akun','akuns.name', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit') )
//       ->where(function($query)
//     {
// $query->Where('akuns.group','pendapatan');
//  $query->orWhere('akuns.group','beban');
//      })
//       ->where(function($query)
//      {

//        $query->Where('jurnals.type','jumum');
//        $query->orWhere('jurnals.type','closed');
//      })


//        ->get();
        //  dd($nrugilaba);
        $neraca = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
        ->where(function($query)
        {
         $query->Where('akuns.group','aktiva');
         $query->orWhere('akuns.group','utang');
         $query->orWhere('akuns.group','modal');
       })
        ->where(function($query)
        {
         $query->Where('jurnals.type','jumum');
         $query->orWhere('jurnals.type','closed');
       })


        ->groupBy('jurnals.id_akun')->select('jurnals.id_akun', 'akuns.name', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit') )
        ->get();


       // return view ('jurnal/index',['jurnal' =>$jurnal,'akuntransaction' =>$akuntransaction, 'nsaldo' =>$nsaldo, 'nrugilaba' => $nrugilaba, 'neraca' => $neraca]);

         // return view ('jurnal/index',['akuntransaction' =>$akuntransaction, 'nsaldo' =>$nsaldo, 'nrugilaba' => $nrugilaba, 'neraca' => $neraca]);
        return view ('jurnal/index',['akuntransaction' =>$akuntransaction, 'nsaldo' =>$nsaldo,  'neraca' => $neraca]);
      }

     /**
      * Show the form for creating a new resource.
      *
      * @return \Illuminate\Http\Response
      */


     public function getjurnaldata(Request $request)
     {
      $date_from = $request->input('date_from');
      $date_end = $request->input('date_end');
    $start = $request->start; // Offset awal
    $length = $request->length; // Jumlah data per halaman
    $totalDebet = 0;
    $totalKredit = 0;
    // Query data dari database
   $query = \App\Jurnal::with('akun_name') // Pastikan relasi "akun" sesuai dengan model
   ->whereBetween('date', [$date_from, $date_end])
   ->orderBy('id', 'desc');
   // \Log::info($date_from);
   // \Log::info($date_end);
   // \Log::info($query->toSql());
   // \Log::info($query->getBindings());


    $allTransactions = $query->get()->groupBy('reff'); // Kelompokkan berdasarkan reff

    // Total group sebelum paginasi
    $recordsTotal = $allTransactions->count();

    // Terapkan paginasi (ambil hanya subset data berdasarkan start dan length)
    $paginatedTransactions = $allTransactions->slice($start, $length);
    $totalDebet = $query->sum('debet');
    $totalKredit = $query->sum('kredit');

    $data = [];
    
    $index=$start;

    foreach ($paginatedTransactions as $reff => $transactions) {
        // Header group
      $description = !empty($transactions[0]->reff)
      ? '<a href="/suminvoice/' . $transactions[0]->reff . '">' . $transactions[0]->note . '</a>'
      : $transactions[0]->description;

      $description = str_replace('receive', '', $description);
      $data[] = [
        'index' =>++$index,
        'is_group' => true,
        'reff' => '',
        'code' => $transactions[0]->code ?? '',
        'description' => $description. '</br><small>'.$transactions[0]->memo.'</small>',
        'user_name' => $transactions[0]->user_name,
        'date' => '',
        'akun_name' => '',
        'debet' => '',
        'kredit' => '',
      ];

        // Detail rows
      foreach ($transactions as $transaction) {
        $data[] = [
          'is_group' => false,
          'reff' => '',
          'description' => '',
          'user_name' => '',
          'date' => $transaction->date,
          'akun_name' => $transaction->akun_name 
          ? $transaction->akun_name->akun_code . ' | ' . $transaction->akun_name->name. '</br><small>'.$transaction->description.'</small>'
          : '',

          'debet' => number_format($transaction->debet, 2, ',', '.'),
          'kredit' => number_format($transaction->kredit, 2, ',', '.'),
        ];
              // $totalDebet += $transaction->debet;
              // $totalKredit += $transaction->kredit;
      }

        // Subtotal row
      $data[] = [
        'is_group' => false,
        'reff' => '',
        'description' => '',
        'user_name' => '',
        'date' => '',
        'akun_name' => 'Subtotal',
        'debet' => number_format($transactions->sum('debet'), 2, ',', '.'),
        'kredit' => number_format($transactions->sum('kredit'), 2, ',', '.'),
      ];
    }

    // Total data setelah filter (sesuaikan jika ada filter tambahan)
    $recordsFiltered = $recordsTotal;

    // Kembalikan JSON respons
    return response()->json([
      'data' => $data,
      'totals' => [
        'debet' => number_format($totalDebet, 2, ',', '.'),
        'kredit' => number_format($totalKredit, 2, ',', '.'),
      ],
      'recordsTotal' => $recordsTotal,
      'recordsFiltered' => $recordsFiltered,
    ]);
  }


  public function getBukubesarData(Request $request)
  {
    $date_from   = $request->input('date_from');
    $date_end    = $request->input('date_end');
    $akun_filter = $request->input('akun_filter');
    $start       = $request->start ?? 0;
    $length      = $request->length ?? 1000;

    // fungsi bantu format saldo
    function formatSaldo($value)
    {
      return $value < 0
      ? '(' . number_format(abs($value), 2, ',', '.') . ')'
      : number_format($value, 2, ',', '.');
    }

    // ========== QUERY DASAR (periode dipilih) ==========
    $query = \App\Jurnal::with('akun_name')
    ->whereBetween('date', [$date_from, $date_end])
    ->whereNull('deleted_at');

    if (!empty($akun_filter)) {
      $query->where('id_akun', $akun_filter);
    }

    $allTransactions = $query
    ->orderBy('id_akun', 'asc')
    ->orderBy('date', 'asc')
    ->get()
    ->groupBy('id_akun');

    $recordsTotal = $allTransactions->count();
    $totalDebet   = $query->sum('debet');
    $totalKredit  = $query->sum('kredit');

    // =========================================================
    // 1️⃣ SALDO AWAL — dengan join ke akuns untuk menentukan arah normal
    // =========================================================
    // PENTING: Saldo dihitung sesuai arah normal akun:
    // - NORMAL DEBET (aktiva, beban): debet menambah, kredit mengurangi → debet - kredit
    // - NORMAL KREDIT (kewajiban, ekuitas, pendapatan): kredit menambah, debet mengurangi → kredit - debet
    // 
    // Contoh:
    // - Kas (aktiva): Debet 116,236,000 - Kredit 0 = Saldo 116,236,000 (positif = ada uang)
    // - Hutang (kewajiban): Kredit 50,000,000 - Debet 11,417,360 = Saldo 38,582,640 (positif = masih berhutang)
    // - Pendapatan: Kredit 335,710,000 - Debet 0 = Saldo 335,710,000 (positif = sudah dapat untung)
    $saldoAwalQuery = DB::table('jurnals as j')
    ->join('akuns as a', 'j.id_akun', '=', 'a.akun_code')
    ->select(
      'j.id_akun',
      DB::raw("
        CASE 
          WHEN LOWER(a.group) IN ('kewajiban', 'ekuitas', 'pendapatan') 
          THEN SUM(j.kredit - j.debet)
          ELSE SUM(j.debet - j.kredit)
        END AS saldo_awal
      ")
    )
    ->where('j.date', '<', $date_from)
    ->whereNull('j.deleted_at');

    if (!empty($akun_filter)) {
      $saldoAwalQuery->where('j.id_akun', $akun_filter);
    }

    $saldoAwalQuery = $saldoAwalQuery
    ->groupBy('j.id_akun')
    ->pluck('saldo_awal', 'id_akun');

    // =========================================================
    // 2️⃣ KELOMPOK PER AKUN
    // =========================================================
    $data  = [];
    $index = $start;

    foreach ($allTransactions as $id_akun => $transactions) {
      $akunObj = $transactions->first()->akun_name;
      $akunName = $akunObj
      ? $akunObj->akun_code . ' | ' . $akunObj->name
      : 'Akun Tidak Ditemukan';
      $group = strtolower($akunObj->group ?? '');
      $isKreditNormal = in_array($group, ['kewajiban', 'ekuitas', 'pendapatan']);

        // Saldo awal sudah dihitung dengan arah yang benar di query
      $saldoAwal = (float)($saldoAwalQuery[$id_akun] ?? 0);
      $saldo = $saldoAwal;

        // ========== HEADER AKUN (Saldo Awal) ==========
            $data[] = [
              'index'       => ++$index,
              'is_group'    => true,
              'reff'        => '',
              'code'        => '',
              'description' => $akunName . ' (' . ucfirst($group) . ')',
              'user_name'   => '',
              'date'        => '',
              'akun_name'   => 'Saldo Awal',
              'debet'       => '',
              'kredit'      => '',
              'saldo'       => formatSaldo($saldoAwal),
            ];

        // ========== DETAIL TRANSAKSI ==========
            foreach ($transactions as $t) {
              if ($isKreditNormal) {
                $saldo = $saldo - $t->debet + $t->kredit;
              } else {
                $saldo = $saldo + $t->debet - $t->kredit;
              }

              $data[] = [
                'is_group'   => false,
                'reff'       => $t->reff,
                'code'       => $t->code,
                'description'=> $t->description,
                'user_name'  => $t->user_name,
                'date'       => $t->date,
                'akun_name'  => $t->description,
                'debet'      => number_format($t->debet, 2, ',', '.'),
                'kredit'     => number_format($t->kredit, 2, ',', '.'),
                'saldo'      => formatSaldo($saldo),
              ];
            }

        // ========== SUBTOTAL / SALDO AKHIR ==========
            $sumDebet  = $transactions->sum('debet');
            $sumKredit = $transactions->sum('kredit');

            $data[] = [
              'is_group'    => false,
              'reff'        => '',
              'code'        => '',
              'description' => '',
              'user_name'   => '',
              'date'        => '',
              'akun_name'   => 'Saldo Akhir',
              'debet'       => '<b>' . number_format($sumDebet, 2, ',', '.') . '</b>',
              'kredit'      => '<b>' . number_format($sumKredit, 2, ',', '.') . '</b>',
              'saldo'       => '<b>' . formatSaldo($saldo) . '</b>',
            ];
          }

    // =========================================================
    // 3️⃣ KIRIM RESPONSE KE DATATABLES
    // =========================================================
          return response()->json([
            'data' => $data,
            'totals' => [
              'debet'  => number_format($totalDebet, 2, ',', '.'),
              'kredit' => number_format($totalKredit, 2, ',', '.'),
            ],
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
          ]);
        }

    //                   public function laporanNeraca(Request $request, bool $asArray = false)
    //                   {
    //                     $tanggalAwal = $request->input('tanggal_awal', now()->startOfYear()->format('Y-m-d'));
    //                     $tanggalAkhir = $request->input('tanggal_akhir', now()->format('Y-m-d'));

    //                     $groups = ['aktiva', 'kewajiban', 'ekuitas'];
    //                     $data = [];
    //                     $total = [
    //                       'aktiva' => 0,
    //                       'kewajiban' => 0,
    //                       'ekuitas' => 0,
    //                     ];

    //                     foreach ($groups as $group) {
    //                       $akuns = \App\Akun::where('group', $group)
    //                       ->whereDoesntHave('children')
    //                       ->get();

    //                       $data[$group] = [];

    //                       foreach ($akuns as $akun) {
    //                         $saldo = \App\Jurnal::where('id_akun', $akun->id)
    //                         ->whereBetween('date', [$tanggalAwal, $tanggalAkhir])
    //                         ->sum(DB::raw('debet - kredit'));

    //                         $data[$group][] = [
    //                           'akun' => $akun,
    //                           'saldo' => $saldo,
    //                         ];

    //                         $total[$group] += $saldo;
    //                       }
    //                     }

    //                     $total_kewajiban_ekuitas = $total['kewajiban'] + $total['ekuitas'];

    // // Jika dipanggil dari controller lain, return data array
    //                     if ($asArray) {
    //                       return [
    //                         'tanggalAwal' => $tanggalAwal,
    //                         'tanggalAkhir' => $tanggalAkhir,
    //                         'data' => $data,
    //                         'total' => $total,
    //                         'total_kewajiban_ekuitas' => $total_kewajiban_ekuitas,
    //                       ];
    //                     }

    // // Jika dipanggil dari route normal, return view
    //                     return view('jurnal.laporan_neraca', [
    //                       'data' => $data,
    //                       'total' => $total,
    //                       'total_kewajiban_ekuitas' => $total_kewajiban_ekuitas,
    //                       'tanggalAwal' => $tanggalAwal,
    //                       'tanggalAkhir' => $tanggalAkhir,
    //                     ]);
    //                   }



        public function laporanNeraca(Request $request, bool $asArray = false)
        {
          $tanggalAwal = $request->input('tanggal_awal', now()->startOfYear()->format('Y-m-d'));
          $tanggalAkhir = $request->input('tanggal_akhir', now()->format('Y-m-d'));

          $groups = ['aktiva', 'kewajiban', 'ekuitas'];
          $data = [];
          $total = [
            'aktiva' => 0,
            'kewajiban' => 0,
            'ekuitas' => 0,
          ];

          foreach ($groups as $group) {
            $akuns = \App\Akun::where('group', $group)
            ->whereDoesntHave('children')
            ->select('akun_code','name','group','category')
            ->get();

            $data[$group] = [];

            foreach ($akuns as $akun) {
            // gunakan akun_code karena jurnals.id_akun menyimpan akun_code
              $saldo = \App\Jurnal::where('id_akun', $akun->akun_code)
              ->whereBetween('date', [$tanggalAwal, $tanggalAkhir])
              ->whereNull('deleted_at') 
              ->sum(DB::raw('debet - kredit'));

              $data[$group][] = [
                'akun' => $akun,
                'saldo' => $saldo,
              ];

              $total[$group] += $saldo;
            }
          }

          $total_kewajiban_ekuitas = $total['kewajiban'] + $total['ekuitas'];

          if ($asArray) {
            return [
              'tanggalAwal' => $tanggalAwal,
              'tanggalAkhir' => $tanggalAkhir,
              'data' => $data,
              'total' => $total,
              'total_kewajiban_ekuitas' => $total_kewajiban_ekuitas,
            ];
          }

          return view('jurnal.laporan_neraca', [
            'data' => $data,
            'total' => $total,
            'total_kewajiban_ekuitas' => $total_kewajiban_ekuitas,
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
          ]);
        }

        public function jurnal(Request $request)
        {
          // $date_from = $request->date_from ?? date('Y-m-1');
          // $date_end = $request->date_end ?? date('Y-m-d');
          // $date_msg = "Show Data From $date_from to $date_end";

          $akuntransaction = \App\Akuntransaction::pluck('name', 'id');

          return view('jurnal/jumum', compact('akuntransaction'));
        }

        public function bukubesar(Request $request)
        {
    // Default tanggal dan akun
    $from = $request->date_from ?? date('Y-m-01'); // Awal bulan
    $to = $request->date_end ?? date('Y-m-d'); // Hari ini
    $akun_id = $request->akun ?? 1; // Default akun ID = 1

    // Tanggal sebelum periode untuk menghitung saldo awal
    $date_before_from = date('Y-m-d', strtotime('-1 day', strtotime($from)));

    // Pesan tanggal untuk ditampilkan di tampilan
    $date_msg = "Menampilkan data dari $from sampai $to";

    // Ambil saldo awal sebelum periode
    $saldo_awal = \App\Jurnal::where('id_akun', $akun_id)
    ->where('date', '<=', $date_before_from)
    ->whereIn('type', ['jumum', 'closed'])
    ->selectRaw('SUM(debet) - SUM(kredit) AS saldo')
    ->value('saldo') ?? 0;

    // Ambil transaksi selama periode
    $jurnal = \App\Jurnal::where('id_akun', $akun_id)
    ->whereBetween('date', [$from, $to])
    ->whereIn('type', ['jumum', 'closed'])
    ->orderBy('date', 'ASC')
    ->orderBy('id', 'ASC')
    ->get();

    // Ambil daftar akun untuk dropdown dengan struktur parent-child
    $akunList = $this->getAkunHierarchy();

    // Ambil daftar akun transaksi (opsional jika digunakan di tampilan)
    $akuntransaction = \App\Akuntransaction::pluck('name', 'id');

    // Return ke view
    return view('jurnal/bukubesar', [
      'jurnal' => $jurnal,
      'saldo_awal' => $saldo_awal,
      'akun' => $akunList,
      'akuntransaction' => $akuntransaction,
      'date_msg' => $date_msg,
      'date_from' => $from,
      'date_to' => $to,
      'selected_akun' => $akun_id,
    ]);
  }

  /**
   * Get akun hierarchy with parent-child structure
   * IMPORTANT: 
   * - Parent WITH children (Header) = disabled, bold, gray background, RATA KIRI
   * - Child (has parent) = enabled, indented (menjorok kanan)
   * - Standalone (no parent, no children) = enabled, indented (menjorok kanan)
   */
  private function getAkunHierarchy()
  {
    // Get all accounts ordered by code
    $allAkuns = \App\Akun::orderBy('akun_code', 'asc')->get();
    
    // Get accounts without parent (potential parents or standalone)
    $withoutParent = $allAkuns->whereNull('parent');
    
    // Group children by their parent code
    $children = $allAkuns->whereNotNull('parent')->groupBy('parent');
    
    $hierarchy = [];
    
    foreach ($withoutParent as $akun) {
      // Check if this account has children
      $hasChildren = isset($children[$akun->akun_code]);
      
      if ($hasChildren) {
        // This is a PARENT (Header) - has children, should be disabled, RATA KIRI
        $hierarchy[$akun->akun_code] = $akun->akun_code . ' | ' . $akun->name . ' (Header)';
        
        // Add children under this parent with indentation (MENJOROK KANAN)
        foreach ($children[$akun->akun_code] as $child) {
          $hierarchy[$child->akun_code] = '     ↳ ' . $child->akun_code . ' | ' . $child->name;
        }
      } else {
        // This is a STANDALONE account - MENJOROK KANAN seperti child
        $hierarchy[$akun->akun_code] = '     • ' . $akun->akun_code . ' | ' . $akun->name;
      }
    }
    
    return $hierarchy;
  }

  /**
   * Laporan Perubahan Modal (Statement of Changes in Equity)
   * Menampilkan perubahan ekuitas pemilik selama periode tertentu
   */
  public function perubahanModal(Request $request)
  {
    $tanggalAwal = $request->input('tanggal_awal', now()->startOfYear()->format('Y-m-d'));
    $tanggalAkhir = $request->input('tanggal_akhir', now()->format('Y-m-d'));

    // Tanggal sebelum periode untuk saldo awal
    $tanggalSebelumAwal = date('Y-m-d', strtotime('-1 day', strtotime($tanggalAwal)));

    // 1. MODAL AWAL (Saldo Ekuitas sebelum periode)
    $modalAwal = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->where('akuns.group', 'ekuitas')
      ->where('jurnals.date', '<=', $tanggalSebelumAwal)
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.kredit - jurnals.debet) as total')
      ->value('total') ?? 0;

    // 2. PENAMBAHAN MODAL (Setoran modal selama periode)
    // Biasanya dari akun "Modal Disetor" atau sejenisnya
    $penambahanModal = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->where('akuns.group', 'ekuitas')
      ->where('akuns.category', 'modal')
      ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.kredit - jurnals.debet) as total')
      ->value('total') ?? 0;

    // 3. LABA/RUGI PERIODE BERJALAN
    // Pendapatan
    $pendapatan = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->whereIn('akuns.category', ['pendapatan', 'pendapatan lainnya'])
      ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.kredit - jurnals.debet) as total')
      ->value('total') ?? 0;

    // Beban
    $beban = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->whereIn('akuns.category', ['beban', 'beban lainnya', 'harga pokok penjualan'])
      ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.debet - jurnals.kredit) as total')
      ->value('total') ?? 0;

    $labaBersih = $pendapatan - $beban;

    // 4. PENGAMBILAN PRIVE (Prive/Withdrawal)
    $prive = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->where(function($q) {
        $q->where('akuns.name', 'LIKE', '%prive%')
          ->orWhere('akuns.name', 'LIKE', '%penarikan%')
          ->orWhere('akuns.name', 'LIKE', '%withdrawal%');
      })
      ->where('akuns.group', 'ekuitas')
      ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.debet - jurnals.kredit) as total')
      ->value('total') ?? 0;

    // 5. MODAL AKHIR
    $modalAkhir = $modalAwal + $penambahanModal + $labaBersih - $prive;

    return view('jurnal.perubahan_modal', compact(
      'tanggalAwal',
      'tanggalAkhir',
      'modalAwal',
      'penambahanModal',
      'labaBersih',
      'prive',
      'modalAkhir'
    ));
  }

  public function perubahanModalPdf(Request $request)
  {
    $tanggalAwal = $request->input('tanggal_awal', now()->startOfYear()->format('Y-m-d'));
    $tanggalAkhir = $request->input('tanggal_akhir', now()->format('Y-m-d'));
    $tanggalSebelumAwal = date('Y-m-d', strtotime('-1 day', strtotime($tanggalAwal)));

    // Modal Awal
    $modalAwal = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->where('akuns.group', 'ekuitas')
      ->where('jurnals.date', '<=', $tanggalSebelumAwal)
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.kredit - jurnals.debet) as total')
      ->value('total') ?? 0;

    // Penambahan Modal
    $penambahanModal = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->where('akuns.group', 'ekuitas')
      ->where('akuns.category', 'modal')
      ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.kredit - jurnals.debet) as total')
      ->value('total') ?? 0;

    // Laba Bersih
    $pendapatan = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->whereIn('akuns.category', ['pendapatan', 'pendapatan lainnya'])
      ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.kredit - jurnals.debet) as total')
      ->value('total') ?? 0;

    $beban = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->whereIn('akuns.category', ['beban', 'beban lainnya', 'harga pokok penjualan'])
      ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.debet - jurnals.kredit) as total')
      ->value('total') ?? 0;

    $labaBersih = $pendapatan - $beban;

    // Prive
    $prive = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->where(function($q) {
        $q->where('akuns.name', 'LIKE', '%prive%')
          ->orWhere('akuns.name', 'LIKE', '%penarikan%')
          ->orWhere('akuns.name', 'LIKE', '%withdrawal%');
      })
      ->where('akuns.group', 'ekuitas')
      ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.debet - jurnals.kredit) as total')
      ->value('total') ?? 0;

    // Modal Akhir
    $modalAkhir = $modalAwal + $penambahanModal + $labaBersih - $prive;

    $pdf = Pdf::loadView('jurnal.perubahan_modal_pdf', [
      'tanggalAwal' => $tanggalAwal,
      'tanggalAkhir' => $tanggalAkhir,
      'modalAwal' => $modalAwal,
      'penambahanModal' => $penambahanModal,
      'labaBersih' => $labaBersih,
      'prive' => $prive,
      'modalAkhir' => $modalAkhir,
    ])->setPaper('A4', 'portrait');

    return $pdf->download('Perubahan_Modal_' . $tanggalAwal . '_' . $tanggalAkhir . '.pdf');
  }

  public function perubahanModalExcel(Request $request)
  {
    $tanggalAwal = $request->input('tanggal_awal', now()->startOfYear()->format('Y-m-d'));
    $tanggalAkhir = $request->input('tanggal_akhir', now()->format('Y-m-d'));
    $tanggalSebelumAwal = date('Y-m-d', strtotime('-1 day', strtotime($tanggalAwal)));

    // Modal Awal
    $modalAwal = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->where('akuns.group', 'ekuitas')
      ->where('jurnals.date', '<=', $tanggalSebelumAwal)
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.kredit - jurnals.debet) as total')
      ->value('total') ?? 0;

    // Penambahan Modal
    $penambahanModal = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->where('akuns.group', 'ekuitas')
      ->where('akuns.category', 'modal')
      ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.kredit - jurnals.debet) as total')
      ->value('total') ?? 0;

    // Laba Bersih
    $pendapatan = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->whereIn('akuns.category', ['pendapatan', 'pendapatan lainnya'])
      ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.kredit - jurnals.debet) as total')
      ->value('total') ?? 0;

    $beban = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->whereIn('akuns.category', ['beban', 'beban lainnya', 'harga pokok penjualan'])
      ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.debet - jurnals.kredit) as total')
      ->value('total') ?? 0;

    $labaBersih = $pendapatan - $beban;

    // Prive
    $prive = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->where(function($q) {
        $q->where('akuns.name', 'LIKE', '%prive%')
          ->orWhere('akuns.name', 'LIKE', '%penarikan%')
          ->orWhere('akuns.name', 'LIKE', '%withdrawal%');
      })
      ->where('akuns.group', 'ekuitas')
      ->whereBetween('jurnals.date', [$tanggalAwal, $tanggalAkhir])
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(jurnals.debet - jurnals.kredit) as total')
      ->value('total') ?? 0;

    // Modal Akhir
    $modalAkhir = $modalAwal + $penambahanModal + $labaBersih - $prive;

    return Excel::download(new \App\Exports\PerubahanModalExport([
      'tanggalAwal' => $tanggalAwal,
      'tanggalAkhir' => $tanggalAkhir,
      'modalAwal' => $modalAwal,
      'penambahanModal' => $penambahanModal,
      'labaBersih' => $labaBersih,
      'prive' => $prive,
      'modalAkhir' => $modalAkhir,
    ]), 'Perubahan_Modal_' . $tanggalAwal . '_' . $tanggalAkhir . '.xlsx');
  }



  public function kasmasuk()
  {



   $parentAkuns = \App\Akun::whereNotNull('parent')->distinct()->pluck('parent')->toArray();
   if (empty($parentAkuns)) {
    $parentAkuns = [null]; // Set default agar tidak error pada whereNotIn
  }

//    dd($parentAkuns);
  $akunkredit = \App\Akun::whereNotIn('akun_code', $parentAkuns)
  ->whereIn('category', [
    'kas & bank',
    'akun piutang',
    'pendapatan',
    'pendapatan lainnya',
    'ekuitas',
    'akun hutang',
    'kewajiban lancar lainnya',
    'kewajiban jangka panjang'
  ])
  ->get();


  $akundebet = \App\Akun::whereNotIn('akun_code', $parentAkuns)
  ->Where('category','kas & bank')

  ->get();




  return view('jurnal.kasmasuk',['akunkredit'=>$akunkredit,'akundebet'=>$akundebet]);
}

public function kaskeluar()
{
  $parentAkuns = \App\Akun::whereNotNull('parent')->distinct()->pluck('parent')->toArray();
  if (empty($parentAkuns)) {
            $parentAkuns = [null]; // Set default agar tidak error pada whereNotIn
          }

          $akundebet = \App\Akun::whereNotIn('akun_code', $parentAkuns)
          ->whereIn('group', ['beban', 'kewajiban', 'aktiva'])
          ->where(function ($query) {
            $query->whereNull('category')
            ->orWhere('category', '!=', 'kas & bank');
          })
          ->get();


          $akunkredit = \App\Akun::whereNotIn('akun_code', $parentAkuns)
          ->where('category', 'kas & bank')
          ->get();

          return view('jurnal.kaskeluar', [
            'akunkredit' => $akunkredit,
            'akundebet' => $akundebet
          ]);
        }




        public function transferkas()
        {

          $parentAkuns = \App\Akun::whereNotNull('parent')->distinct()->pluck('parent')->toArray();
          if (empty($parentAkuns)) {
            $parentAkuns = [null]; // Set default agar tidak error pada whereNotIn
          }
          $akunkredit = \App\Akun::whereNotIn('akun_code', $parentAkuns)
          ->where('category', 'kas & bank')
          ->get();
          $akundebet = $akunkredit;
          return view('jurnal.tranferkas', [
            'akunkredit' => $akunkredit,
            'akundebet' => $akundebet
          ]);

        }

        public function general()
        {


          $parentAkuns = \App\Akun::whereNotNull('parent')->distinct()->pluck('parent')->toArray();
          if (empty($parentAkuns)) {
            $parentAkuns = [null]; // Set default agar tidak error pada whereNotIn
          }


          $akundk = \App\Akun::whereNotIn('akun_code', $parentAkuns)
          ->get();


          return view ('jurnal/general',['akunkredit' =>$akundk, 'akundebet'=>$akundk]);

        }
// public function kastransaction(Request $request)
// {



//  $request->validate([
//   'akundebet'    => 'required',
//   'type' => 'required|string',
//   'date' => 'required|date',
//   'category' =>'required|string',
//   'contact_id' =>'required|string',
//   'akunkredit'   => 'required|array',
//   'akunkredit.*' => 'required|string', 
//     // 'description_d'  => 'required|string',
//   'description'  => 'required|array',
//   'description.*' => 'nullable|string',
//   'kredit'       => 'required|array',
//   'kredit.*'     => 'required|numeric|min:0',
//   'debet'        => 'required|numeric|min:0',

// ]);
//  $totalKredit = array_sum($request->kredit);
//  if ($totalKredit != $request->debet) {
//   return back()->withErrors(['msg' => 'Total debet dan kredit harus sama!'])->withInput();
// }
// $tempcode=sha1(time().rand());

// $note = $request->type. ' | '.$request->name  ;
// foreach ($request->akunkredit as $index => $akun_kredit) {
//   \App\Jurnal::create([
//     'reff'  => $tempcode,
//     'date' => $request->date,
//     'type'      => $request->type,
//     'id_akun'    => $akun_kredit,
//     'description'     => $request->description[$index] ?? '',
//     'kredit'        => $request->kredit[$index],
//     'note'          => $request->description_d,
//     'category'          => $request->category,
//     'memo'          => $request->memo,
//     'created_by' => \Auth::user()->id,
//     'created_at'    => now(),
//     'updated_at'    => now(),
//   ]);
//   $note = $note. ' | '.$request->description[$index] ?? '';
// }

// \App\Jurnal::create([
//   'reff'  => $tempcode,
//   'date' => $request->date,
//   'id_akun'     => $request->akundebet,
//   'description'     => $request->type,
//   'debet'        => $request->debet,
//   'note'          => $note,
//   'category'          => $request->category,
//   'type'      => $request->type,
//   'created_by' => \Auth::user()->id,
//   'memo'          => $request->memo,
//   'created_at'    => now(),
//   'updated_at'    => now(),
// ]);

// return redirect()->back()->with('success', 'transaction created successfully!');
// }

        public function kasmasuktransaction(Request $request)
        {
          $request->validate([
           'name' => 'required',
           'akundebet'    => 'required',
           'type' => 'required|string',
           'date' => 'required|date',
           'category' =>'required|string',
           'contact_id' =>'required|string',
           'akunkredit'   => 'required|array',
           'akunkredit.*' => 'required|string', 
           'description'  => 'required|array',
           'description.*' => 'nullable|string',
           'kredit'       => 'required|array',
           'kredit.*'     => 'required|numeric|min:0',
           'debet'        => 'required|numeric|min:0',
         ]);
          $code = substr(md5(uniqid('', true)), 0, 10);
          $totalKredit = array_sum($request->kredit);
          if ($totalKredit != $request->debet) {
            return back()->withErrors(['msg' => 'Total debet dan kredit harus sama!'])->withInput();
          }

          $tempcode = sha1(time() . rand());
          $note = $request->type . ' | ' . $request->name;

          DB::beginTransaction();
          try {
            foreach ($request->akunkredit as $index => $akun_kredit) {
              \App\Jurnal::create([
                'reff'  => $tempcode,
                'date' => $request->date,
                'type' => $request->type,
                'id_akun' => $akun_kredit,
                'description' => $request->description[$index] ?? '',
                'kredit' => $request->kredit[$index],
                'note' => $request->description_d,
                'category' => $request->category,
                'memo' => $request->memo,
                'created_by' => \Auth::user()->id,
                'created_at' => now(),
                'updated_at' => now(),
                'code' => $code,
                'contact_id' => $request->contact_id,
              ]);
              $note .= ' | ' . ($request->description[$index] ?? '');
            }

            \App\Jurnal::create([
              'reff'  => $tempcode,
              'date' => $request->date,
              'id_akun' => $request->akundebet,
              'description' => $request->type,
              'debet' => $request->debet,
              'note' => $note,
              'category' => $request->category,
              'type' => $request->type,
              'created_by' => \Auth::user()->id,
              'memo' => $request->memo,
              'created_at' => now(),
              'updated_at' => now(),
              'code' => $code,
              'contact_id' => $request->contact_id,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Transaction created successfully!');
          } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Transaction failed: ' . $e->getMessage()])->withInput();
          }
        }


        public function kaskeluartransaction(Request $request)
        {
        // dd($request);
          $request->validate([
           'name' => 'required',
           'akunkredit'   => 'required',
           'type' => 'required|string',
           'date' => 'required|date',
           'category' =>'required|string',
           'contact_id' =>'required|string',
           'akundebet'   => 'required|array',
           'akundebet.*' => 'required|string', 
           'description'  => 'required|array',
           'description.*' => 'nullable|string',
           'debet'       => 'required|array',
           'debet.*'     => 'required|numeric|min:0',
           'kredit'        => 'required|numeric|min:0',
         ]);
          $code = substr(md5(uniqid('', true)), 0, 10);
          $totalDebet = array_sum($request->debet);
          if ($totalDebet != $request->kredit) {
            return back()->withErrors(['msg' => 'Total debet dan kredit harus sama!'])->withInput();
          }

          $tempcode = sha1(time() . rand());
          $note = $request->type . ' | ' . $request->name;

          DB::beginTransaction();
          try {
            foreach ($request->akundebet as $index => $akun_debet) {
              \App\Jurnal::create([
                'reff'  => $tempcode,
                'date' => $request->date,
                'type' => $request->type,
                'contact_id' => $request->contact_id,
                'id_akun' => $akun_debet,
                'description' => $request->description[$index] ?? '',
                'debet' => $request->debet[$index],
                'note' => $request->description_d,
                'category' => $request->category,
                'memo' => $request->memo,
                'created_by' => \Auth::user()->id,
                'created_at' => now(),
                'updated_at' => now(),
                'code' => $code,
                'contact_id' => $request->contact_id,
              ]);
              $note .= ' | ' . ($request->description[$index] ?? '');
            }

            \App\Jurnal::create([
              'reff'  => $tempcode,
              'date' => $request->date,
              'id_akun' => $request->akunkredit,
              'description' => $request->type,
              'kredit' => $request->kredit,
              'note' => $note,
              'category' => $request->category,
              'type' => $request->type,
              'contact_id' => $request->contact_id,
              'created_by' => \Auth::user()->id,
              'memo' => $request->memo,
              'created_at' => now(),
              'updated_at' => now(),
              'code' => $code,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Transaction created successfully!');
          } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Transaction failed: ' . $e->getMessage()])->withInput();
          }
        }

        public function transferkastransaction(Request $request)
        {

          $request->validate([
            'akunkredit'   => 'required',
            'type' => 'required|string',
            'date' => 'required|date',
            'akundebet' => [
              'required',
              'string',
                 Rule::notIn([$request->akunkredit]), // Tidak boleh sama dengan akunkredit
               ],

               'amount'       => 'required|numeric',
               'memo'         => 'nullable|string'

             ]);
         // Ambil akun debet dan kredit dari database
          $akundebet = \App\Akun::where('akun_code', $request->akundebet)->first();
          $akunkredit = \App\Akun::where('akun_code', $request->akunkredit)->first();
          if (!$akundebet || !$akunkredit) {
            return back()->withErrors(['msg' => 'Akun tidak ditemukan'])->withInput();
          }
          $code = substr(md5(uniqid('', true)), 0, 10);
          $note = 'Transfer kasbank '.$akunkredit->name.' to '. $akundebet->name;
          $tempcode = sha1(time() . rand());

          DB::beginTransaction();
          try {

            \App\Jurnal::create([
              'reff'  => $tempcode,
              'date' => $request->date,
              'type' => $request->type,

              'id_akun' => $request->akundebet,
              'description' => 'Tranfer from '. $akunkredit->name,
              'debet' => $request->amount,
              'note' => $note,
              'category' => 'internal',
              'memo' => $request->memo,
              'created_by' => \Auth::user()->id,
              'created_at' => now(),
              'updated_at' => now(),
              'code' => $code,
              'contact_id' => '0000000001',

            ]);

            \App\Jurnal::create([
             'reff'  => $tempcode,
             'date' => $request->date,
             'type' => $request->type,

             'id_akun' => $request->akunkredit,
             'description' => 'Transfer to '. $akundebet->name,
             'kredit' => $request->amount,
             'note' => $note,
             'category' => 'internal',
             'memo' => $request->memo,
             'created_by' => \Auth::user()->id,
             'created_at' => now(),
             'updated_at' => now(),
             'code' => $code,
             'contact_id' => '0000000001',
           ]);

            DB::commit();
            return redirect()->back()->with('success', 'Transaction created successfully!');
          } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Transaction failed: ' . $e->getMessage()])->withInput();
          }

        }


        public function generaltransaction(Request $request)
        {

         $request->validate([
          'name' => 'required',
          'date' => 'required|date',
          'akun'   => 'required|array',
          'akun.*' => 'required|string', 
          'description'  => 'required|array',
          'description.*' => 'nullable|string',
          'debet'       => 'required|array',
          'kredit'       => 'required|array',
          'debet.*'     => 'required|numeric|min:0',
          'kredit.*'        => 'required|numeric|min:0',
          'memo'         => 'nullable|string'

        ]);
         $code = substr(md5(uniqid('', true)), 0, 10);
         $type ='general';
         $tempcode = sha1(time() . rand());
         $note = $type . ' TRansaction | ' . $request->name;

         DB::beginTransaction();
         try {
           foreach ($request->akun as $index => $akun_g) {
            \App\Jurnal::create([
              'reff'  => $tempcode,
              'date' => $request->date,
              'type' => $type,
              'contact_id' => $request->contact_id,
              'id_akun' => $akun_g,
              'description' => $request->description[$index] ?? '',
              'debet' => $request->debet[$index],
              'kredit' => $request->kredit[$index],
              'note' => $note,
              'category' => $request->category,
              'memo' => $request->memo,
              'created_by' => \Auth::user()->id,
              'created_at' => now(),
              'updated_at' => now(),
              'code' => $code,
            ]);

          }



          DB::commit();
          return redirect()->back()->with('success', 'Transaction created successfully!');
        } catch (\Exception $e) {
          DB::rollBack();
          return back()->withErrors(['msg' => 'Transaction failed: ' . $e->getMessage()])->withInput();
        }
      }

      public function jpenutup()
      {

        $pendapatan = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
        ->where(function($query)
        {
          $query->Where('akuns.group','pendapatan');
      // $query->orWhere('akuns.group','utang');
      // $query->orWhere('akuns.group','modal');
        })
        ->where(function($query)
        {
          $query->Where('jurnals.type','jumum');
          $query->orWhere('jurnals.type','closed');
        })


        ->groupBy('jurnals.id_akun')->select('jurnals.id_akun', 'akuns.name', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit') )
        ->get();



        $beban = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
        ->where(function($query)
        {
          $query->Where('akuns.group','beban');
      // $query->orWhere('akuns.group','utang');
      // $query->orWhere('akuns.group','modal');
        })
        ->where(function($query)
        {
          $query->Where('jurnals.type','jumum');
          $query->orWhere('jurnals.type','closed');
        })


        ->groupBy('jurnals.id_akun')->select('jurnals.id_akun', 'akuns.name', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit') )
        ->get();




        $nrugilaba = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
        ->where(function($query)
        {
          $query->Where('akuns.group','pendapatan');
          $query->orWhere('akuns.group','beban');
        })
        ->where(function($query)
        {

          $query->Where('jurnals.type','jumum');
          $query->orWhere('jurnals.type','closed');
        })

        ->groupBy('jurnals.debet')->select('jurnals.id_akun', 'akuns.name', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit') )
        ->get();

//dd($nrugilaba);

        $deviden = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
        ->where(function($query)
        {
          $query->Where('akuns.name','deviden');
// $query->orWhere('akuns.group','beban');
        })
        ->where(function($query)
        {

          $query->Where('jurnals.type','jumum');
          $query->orWhere('jurnals.type','closed');
        })

        ->groupBy('jurnals.debet')->select('jurnals.id_akun', 'akuns.name', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit') )
        ->get();



        $akun = \App\Akun::pluck('name', 'id');
        $akuntransaction = \App\Akuntransaction::pluck('name', 'id', 'debet');
        return view ('jurnal/jpenutup',['pendapatan' =>$pendapatan,'beban' =>$beban, 'akuntransaction' =>$akuntransaction,'akun'=>$akun, 'nrugilaba'=>$nrugilaba, 'deviden'=>$deviden ]);



      }
      public function penutup(Request $request)
      {
       $code = substr(md5(uniqid('', true)), 0, 10);


       DB::beginTransaction();
       try {

        $id=0;
        foreach ($request->akun_id as $akun) {



         \App\Jurnal::create([
          'date' => (date('Y-m-d h:i:sa')),
          'id_akun' => ($akun),
          'debet' => ($request->akun_debet[$id]), 
          'kredit' =>  ($request->akun_kredit[$id]),
          'reff' => uniqid(),
          'type' => ('closed'),
          'description' => ('Jurnal Penutup'),
          'code' => $code,
          'contact_id' => '0000000001',
        ]);


         $id=$id+1;

       }
       DB::commit();
       return redirect ('/jurnal/jpenutup')->with('success','Item created successfully!');

     } catch (Exception $e) {
       // Rollback Transaction
       DB::rollback();
       return redirect ('/jurnal/jpenutup')->with('error','Process Failed!');

       // ada yang error
     }



   }



   public function transaksi(Request $request)
   {
    $utang = "";
    $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
    $transactionname = $request->akuntransaction ? \App\Akuntransaction::where('id', $request->akuntransaction)->first() : '';

    if (!$transactionname) {
      $akundebet = collect();
      $akunkredit = collect();
      return view('jurnal/create', compact('utang', 'akuntransaction', 'akundebet', 'akunkredit', 'transactionname'));
    }

    $akundebet = collect();
    $akunkredit = collect();
    $akundebet = collect();
    $akunkredit = collect();

    switch ($transactionname->name) {
      case "pemasukkan":
      $akunkredit = \App\Akun::where('category', 'pendapatan')->get();
      $akundebet = \App\Akun::whereIn('category', ['kas & bank', 'akun piutang', 'persediaan', 'aktiva lancar lainnya', 'aktiva tetap', 'aktiva lainnya'])->get();
      break;

      case "pengeluaran":
      $akundebet = \App\Akun::whereIn('category', ['harga pokok penjualan', 'beban', 'beban lainnya', 'kas & bank', 'aktiva tetap'])->get();
      $akunkredit = \App\Akun::whereIn('category', ['kas & bank', 'aktiva lancar lainnya'])->get();
      break;

      case "utang":
      $akundebet = \App\Akun::whereIn('category', ['harga pokok penjualan', 'beban', 'beban lainnya', 'aktiva lancar lainnya', 'aktiva tetap'])->get();
      $akunkredit = \App\Akun::whereIn('category', ['akun hutang', 'kewajiban jangka panjang'])->get();
      break;

      case "piutang":
      $akundebet = \App\Akun::where('category', 'akun piutang')->get();
      $akunkredit = \App\Akun::whereIn('category', ['pendapatan', 'pendapatan lainnya', 'kas & bank'])->get();
      break;

      case "bayar utang":
      $utang = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
      ->where('akuns.group', 'utang')
      ->select('jurnals.id_akun', 'jurnals.reff', 'jurnals.description', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit'))
      ->groupBy('jurnals.reff')
      ->get();
      $akundebet = \App\Akun::whereIn('category', ['akun hutang', 'kewajiban jangka panjang'])->get();
      $akunkredit = \App\Akun::whereIn('category', ['kas & bank', 'aktiva tetap'])->get();
      break;

      case "dibayar piutang":
      $utang = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
      ->where('akuns.name', 'piutang usaha')
      ->select('jurnals.id_akun', 'jurnals.reff', 'jurnals.description', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit'))
      ->groupBy('jurnals.reff')
      ->get();
      $akunkredit = \App\Akun::where('name', 'piutang usaha')->get();
      $akundebet = \App\Akun::whereIn('category', ['harga pokok penjualan', 'beban', 'beban lainnya', 'pendapatan', 'kas & bank'])->get();
      break;

      case "tambah modal":
      $akundebet = \App\Akun::whereIn('category', ['kas & bank', 'aktiva tetap'])->get();
      $akunkredit = \App\Akun::where('category', 'ekuitas')->get();
      break;

      case "tarik modal":
      $akunkredit = \App\Akun::whereIn('category', ['kas & bank', 'aktiva tetap'])->get();
      $akundebet = \App\Akun::where('category', 'ekuitas')->get();
      break;

      default:
      $jurnal = \App\Jurnal::where('category', 'general')->get();
      $akun = \App\Akun::all();
      return view('jurnal/create', ['jurnal' => $jurnal, 'akun' => $akun, 'akuntransaction' => $akuntransaction]);
    }

    return view('jurnal/create', [
      'utang' => $utang,
      'akundebet' => $akundebet,
      'akunkredit' => $akunkredit,
      'akuntransaction' => $akuntransaction,
      'transactionname' => $transactionname
    ]);
  }



// public function create(Request $request)
// {
//         //
//   $utang ="";
//   $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
//   $transactionname = \App\Akuntransaction::Where('id',$request->akuntransaction)->first();

//   if ($transactionname->name == "pemasukkan"){


//     $akunkredit = \App\Akun::Where('type','pendapatan')->get();
//     $akundebet = \App\Akun::Where('type','aktiva lancar')
//     ->orWhere('type','aktiva tetap')
//            // ->orWhere('type','aktiva lancar')
//     ->get();

//     return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);
//   }
//   elseif ($transactionname->name == "pengeluaran"){

//    $akundebet = \App\Akun::Where('type','biaya admin dan umum')
//    ->orWhere('type','aktiva lancar')
//    ->orWhere('type','aktiva tetap')
//    ->orWhere('type', 'utang jangka panjang')
//    ->orWhere('type', 'utang jangka pendek')
//    ->get();
//    $akunkredit = \App\Akun::Where('type','aktiva lancar')->get();

//    return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);

//  }
//  elseif ($transactionname->name == "utang"){

//    $akundebet = \App\Akun::Where('type','biaya admin dan umum')
//    ->orWhere('type','aktiva lancar')
//    ->orWhere('type','aktiva tetap')
//    ->get();
//    $akunkredit = \App\Akun::Where('type','utang jangka pendek')
//    ->orWhere('type','utang jangka panjang')
//    ->get();

//    return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);

//  }

//  elseif ($transactionname->name == "piutang"){

//    $akundebet = \App\Akun::Where('type','aktiva lancar')
//            // ->orWhere('type','aktiva lancar')
//            // ->orWhere('type','aktiva tetap')
//    ->get();
//    $akunkredit = \App\Akun::Where('type','pendapatan')
//    ->orWhere('type','modal')
//    ->orWhere('type','aktiva lancar')
//    ->get();

//    return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);

//  }
//  elseif ($transactionname->name == "bayar utang"){

//   $akunutang = \App\Akun::Where('group','utang')->first();
//              // $utang = \App\jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
//              // ->Where('akuns.group','utang')
//              // ->select('jurnals.*')

//   $utang = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
//   ->Where('akuns.group','utang')
//   ->select('jurnals.id_akun', 'jurnals.reff', 'jurnals.description', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit'))
//   ->groupBy('jurnals.reff')


//            //  SELECT  id_akun,description, sum(debet) as debet, sum(kredit) as kredit FROM `jurnals` where id_akun=18 group by reff

//               // ->orWhere('type','utang jangka panjang')
//   ->get();
//              //dd($utang);
//   $akundebet = \App\Akun::Where('type','utang jangka pendek')
//   ->orWhere('type','utang jangka panjang')
//   ->get();
//   $akunkredit = \App\Akun::Where('type','biaya admin dan umum')
//   ->orWhere('type','aktiva lancar')
//   ->orWhere('type','aktiva tetap')
//   ->get();

//   return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);
// }
// elseif ($transactionname->name == "dibayar piutang"){

//   $akunutang = \App\Akun::Where('name','piutang usaha')->first();
//              // $utang = \App\jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
//              // ->Where('akuns.group','utang')
//              // ->select('jurnals.*')

//   $utang = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
//   ->Where('akuns.name','piutang usaha')
//   ->select('jurnals.id_akun', 'jurnals.reff', 'jurnals.description', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit'))
//   ->groupBy('jurnals.reff')


//            //  SELECT  id_akun,description, sum(debet) as debet, sum(kredit) as kredit FROM `jurnals` where id_akun=18 group by reff

//               // ->orWhere('type','utang jangka panjang')
//   ->get();
//              //dd($utang);
//   $akunkredit = \App\Akun::Where('name','piutang usaha')
//               // ->orWhere('type','utang jangka panjang')
//   ->get();
//   $akundebet = \App\Akun::Where('type','biaya admin dan umum')
//   ->orWhere('type','aktiva lancar')
//   ->orWhere('type','aktiva tetap')
//   ->orWhere('type','pendapatan')
//   ->get();

//   return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);
// }
// elseif ($transactionname->name == "tambah modal"){

//  $akundebet = \App\Akun::Where('type','aktiva lancar')
//            // ->orWhere('type','aktiva lancar')
//  ->orWhere('type','aktiva tetap')
//  ->get();
//  $akunkredit = \App\Akun::Where('type','modal')->get();

//  return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);

// }
// elseif ($transactionname->name == "tarik modal"){

//  $akunkredit = \App\Akun::Where('type','aktiva lancar')
//            // ->orWhere('type','aktiva lancar')
//  ->orWhere('type','aktiva tetap')
//  ->get();
//  $akundebet = \App\Akun::Where('type','modal')->get();

//  return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);
// }
// else {

//           //$transactionname = \App\akuntransaction::Where('id',$request->akuntransaction)->first();
//  $jurnal =\App\Jurnal::Where('type','general')->get();
//          //dd($cjurnal);
//  $akun = \App\Akun::all();
//  return view ('jurnal/general',['jurnal' => $jurnal,'akun' => $akun, 'akuntransaction' =>$akuntransaction]);

// }
// }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function closed()
    {
      $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
      $jurnal =\App\Jurnal::Where('type','preclosed')->get();

      $akun = \App\Akun::all();
      return view ('jurnal/closed',['jurnal' => $jurnal,'akun' => $akun, 'akuntransaction' =>$akuntransaction]);



    }


    public function closestore(Request $request)
    {
      $code = substr(md5(uniqid('', true)), 0, 10);
      $request ->validate([

        'date' => 'required',
        'akun' => 'required|numeric',
        'debetkredit' => 'required',
        'amount' => 'required|numeric',
        'description' => 'required',

      ]);
      if(!empty($request['reff']))
      {
        $reff = $request['reff'];
      }
      else
      {
       $reff = uniqid();
     }

     if($request['debetkredit']=='d')
     {

      \App\Jurnal::create([
        'date' => ($request['date']),
        'id_akun' => ($request['akun']),
        'debet' => ($request['amount']), 
        'reff' => $reff,
        'type' => ($request['type']),
        'description' => ($request['description']),
        'code' => $code,
        'contact_id' => '0000000001',
      ]);
    }
    else
    {
     \App\Jurnal::create([
      'date' => ($request['date']),
      'id_akun' => ($request['akun']),
      'kredit' => ($request['amount']), 
      'reff' => $reff,
      'type' => ($request['type']),
      'description' => ($request['description']),
      'code' => $code,
      'contact_id' => '0000000001',
    ]);

   }


   return redirect ('/jurnal/closed')->with('success','Item created successfully!');


        //
 }




 public function closeupdate(Request $request)
 {
  $id = $request->jurnalid;
  $reff = uniqid();


  DB::beginTransaction();
  try {

    foreach ($id as $id) 
    {

      \App\Jurnal::where('id', $id)->update([
        'type' => 'closed',

        'reff' => $reff,
      ]);

    }
    DB::commit();
    return redirect ('/jurnal')->with('success','Item created successfully!');

  } catch (Exception $e) {
       // Rollback Transaction
   DB::rollback();
   return redirect ('/jurnal')->with('error','Process Failed!');

       // ada yang error
 }




}


public function ccreate()
{

 $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
         //$transactionname = \App\akuntransaction::Where('id',$request->akuntransaction)->first();
 $cjurnal =\App\Jurnal::Where('type','jcustom')->get();
         //dd($cjurnal);
 $akun = \App\Akun::all();
 return view ('jurnal/custom',['cjurnal' => $cjurnal,'akun' => $akun, 'akuntransaction' =>$akuntransaction]);

}
// Di app/Models/Akun.php
public function getSaldoAttribute()
{
  $debit = $this->transactions->sum('debet');
  $kredit = $this->transactions->sum('kredit');
  return $debit - $kredit;
}


public function neraca(Request $request)
{
  // Neraca adalah posisi kumulatif sampai tanggal tertentu (tidak pakai tanggal awal)
  $tanggalAkhir = $request->input('tanggal_akhir', now()->toDateString());

  // Mapping kategori akun berdasarkan database struktur
  $groups = [
    'aset_lancar' => ['kas & bank', 'akun piutang', 'persediaan', 'aktiva lainnya'],
    'aset_tetap' => ['aktiva tetap'],
    'kewajiban_lancar' => ['akun hutang', 'kewajiban lancar lainnya', 'kewajiban jangka pendek'],
    'ekuitas' => ['ekuitas']
  ];

  $data = [];
  $totals = [
    'aset_lancar' => 0,
    'aset_tetap' => 0,
    'kewajiban_lancar' => 0,
    'ekuitas' => 0
  ];

  // Loop setiap kategori
  foreach ($groups as $groupName => $categories) {
    $akuns = \App\Akun::whereIn('category', $categories)
                      ->whereNull('deleted_at')
                      ->get();

    $groupData = [];
    $groupTotal = 0;

    foreach ($akuns as $akun) {
      // FIX 1: Pakai akun_code (bukan id)
      // FIX 2: Kumulatif dari awal sampai tanggal akhir (bukan whereBetween)
      $saldo = DB::table('jurnals')
              ->where('id_akun', $akun->akun_code)
              ->where('date', '<=', $tanggalAkhir)
              ->whereNull('deleted_at')
              ->selectRaw('SUM(debet) as total_debet, SUM(kredit) as total_kredit')
              ->first();

      $debet = $saldo->total_debet ?? 0;
      $kredit = $saldo->total_kredit ?? 0;

      // FIX 3: Formula berbeda berdasarkan kategori akun
      if (str_contains($groupName, 'aset')) {
        // Aset: Normal Debit (Debet - Kredit)
        $saldoAkhir = $debet - $kredit;
      } else {
        // Kewajiban & Ekuitas: Normal Kredit (Kredit - Debet)
        $saldoAkhir = $kredit - $debet;
      }

      // Hanya tampilkan akun yang ada saldonya
      if ($saldoAkhir != 0) {
        $groupData[] = [
          'akun_code' => $akun->akun_code,
          'name' => $akun->name,
          'category' => $akun->category,
          'saldo' => $saldoAkhir
        ];
        $groupTotal += $saldoAkhir;
      }
    }

    $data[$groupName] = $groupData;
    $totals[$groupName] = $groupTotal;
  }

  // Hitung Laba Rugi untuk masuk ke Ekuitas
  $labaRugi = $this->hitungLabaRugiUntukNeraca($tanggalAkhir);

  // Total
  $totalAset = $totals['aset_lancar'] + $totals['aset_tetap'];
  $totalKewajiban = $totals['kewajiban_lancar'];
  $totalEkuitas = $totals['ekuitas'] + $labaRugi;
  $totalKewajibanEkuitas = $totalKewajiban + $totalEkuitas;

  return view('jurnal.neraca', [
    'data' => $data,
    'totals' => $totals,
    'labaRugi' => $labaRugi,
    'totalAset' => $totalAset,
    'totalKewajiban' => $totalKewajiban,
    'totalEkuitas' => $totalEkuitas,
    'totalKewajibanEkuitas' => $totalKewajibanEkuitas,
    'tanggalAkhir' => $tanggalAkhir,
  ]);
}

/**
 * Helper: Hitung Laba Rugi untuk Neraca
 * Laba rugi dihitung dari awal waktu sampai tanggal tertentu
 */
private function hitungLabaRugiUntukNeraca($tanggalAkhir)
{
  // Pendapatan (kredit - debet)
  $pendapatan = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->whereIn('akuns.category', ['pendapatan', 'pendapatan lainnya'])
      ->where('jurnals.date', '<=', $tanggalAkhir)
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(kredit - debet) as total')
      ->value('total') ?? 0;

  // Beban (debet - kredit)
  $beban = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
      ->whereIn('akuns.category', ['beban', 'beban lainnya', 'harga pokok penjualan'])
      ->where('jurnals.date', '<=', $tanggalAkhir)
      ->whereNull('jurnals.deleted_at')
      ->whereNull('akuns.deleted_at')
      ->selectRaw('SUM(debet - kredit) as total')
      ->value('total') ?? 0;

  return $pendapatan - $beban;
}

/**
 * Export Neraca ke PDF
 */
public function neracaPdf(Request $request)
{
  $tanggalAkhir = $request->input('tanggal_akhir', now()->toDateString());
  
  // Mapping kategori akun berdasarkan database struktur
  $groups = [
    'aset_lancar' => ['kas & bank', 'akun piutang', 'persediaan', 'aktiva lainnya'],
    'aset_tetap' => ['aktiva tetap'],
    'kewajiban_lancar' => ['akun hutang', 'kewajiban lancar lainnya', 'kewajiban jangka pendek'],
    'ekuitas' => ['ekuitas']
  ];

  $data = [];
  $totals = [
    'aset_lancar' => 0,
    'aset_tetap' => 0,
    'kewajiban_lancar' => 0,
    'ekuitas' => 0
  ];

  // Loop setiap kategori
  foreach ($groups as $groupName => $categories) {
    $akuns = \App\Akun::whereIn('category', $categories)
                      ->whereNull('deleted_at')
                      ->get();

    $groupData = [];
    $groupTotal = 0;

    foreach ($akuns as $akun) {
      $saldo = DB::table('jurnals')
              ->where('id_akun', $akun->akun_code)
              ->where('date', '<=', $tanggalAkhir)
              ->whereNull('deleted_at')
              ->selectRaw('SUM(debet) as total_debet, SUM(kredit) as total_kredit')
              ->first();

      $debet = $saldo->total_debet ?? 0;
      $kredit = $saldo->total_kredit ?? 0;

      if (str_contains($groupName, 'aset')) {
        $saldoAkhir = $debet - $kredit;
      } else {
        $saldoAkhir = $kredit - $debet;
      }

      if ($saldoAkhir != 0) {
        $groupData[] = [
          'akun_code' => $akun->akun_code,
          'name' => $akun->name,
          'category' => $akun->category,
          'saldo' => $saldoAkhir
        ];
        $groupTotal += $saldoAkhir;
      }
    }

    $data[$groupName] = $groupData;
    $totals[$groupName] = $groupTotal;
  }

  $labaRugi = $this->hitungLabaRugiUntukNeraca($tanggalAkhir);
  $totalAset = $totals['aset_lancar'] + $totals['aset_tetap'];
  $totalKewajiban = $totals['kewajiban_lancar'];
  $totalEkuitas = $totals['ekuitas'] + $labaRugi;
  $totalKewajibanEkuitas = $totalKewajiban + $totalEkuitas;

  $pdf = Pdf::loadView('jurnal.neraca_pdf', [
    'data' => $data,
    'totals' => $totals,
    'labaRugi' => $labaRugi,
    'totalAset' => $totalAset,
    'totalKewajiban' => $totalKewajiban,
    'totalEkuitas' => $totalEkuitas,
    'totalKewajibanEkuitas' => $totalKewajibanEkuitas,
    'tanggalAkhir' => $tanggalAkhir,
  ])->setPaper('A4', 'portrait');

  return $pdf->download('Neraca_' . $tanggalAkhir . '.pdf');
}

/**
 * Export Neraca ke Excel
 */
public function neracaExcel(Request $request)
{
  $tanggalAkhir = $request->input('tanggal_akhir', now()->toDateString());
  
  // Mapping kategori akun berdasarkan database struktur
  $groups = [
    'aset_lancar' => ['kas & bank', 'akun piutang', 'persediaan', 'aktiva lainnya'],
    'aset_tetap' => ['aktiva tetap'],
    'kewajiban_lancar' => ['akun hutang', 'kewajiban lancar lainnya', 'kewajiban jangka pendek'],
    'ekuitas' => ['ekuitas']
  ];

  $data = [];
  $totals = [
    'aset_lancar' => 0,
    'aset_tetap' => 0,
    'kewajiban_lancar' => 0,
    'ekuitas' => 0
  ];

  // Loop setiap kategori
  foreach ($groups as $groupName => $categories) {
    $akuns = \App\Akun::whereIn('category', $categories)
                      ->whereNull('deleted_at')
                      ->get();

    $groupData = [];
    $groupTotal = 0;

    foreach ($akuns as $akun) {
      $saldo = DB::table('jurnals')
              ->where('id_akun', $akun->akun_code)
              ->where('date', '<=', $tanggalAkhir)
              ->whereNull('deleted_at')
              ->selectRaw('SUM(debet) as total_debet, SUM(kredit) as total_kredit')
              ->first();

      $debet = $saldo->total_debet ?? 0;
      $kredit = $saldo->total_kredit ?? 0;

      if (str_contains($groupName, 'aset')) {
        $saldoAkhir = $debet - $kredit;
      } else {
        $saldoAkhir = $kredit - $debet;
      }

      if ($saldoAkhir != 0) {
        $groupData[] = [
          'akun_code' => $akun->akun_code,
          'name' => $akun->name,
          'category' => $akun->category,
          'saldo' => $saldoAkhir
        ];
        $groupTotal += $saldoAkhir;
      }
    }

    $data[$groupName] = $groupData;
    $totals[$groupName] = $groupTotal;
  }

  $labaRugi = $this->hitungLabaRugiUntukNeraca($tanggalAkhir);
  $totalAset = $totals['aset_lancar'] + $totals['aset_tetap'];
  $totalKewajiban = $totals['kewajiban_lancar'];
  $totalEkuitas = $totals['ekuitas'] + $labaRugi;
  $totalKewajibanEkuitas = $totalKewajiban + $totalEkuitas;

  return Excel::download(new \App\Exports\NeracaExport([
    'data' => $data,
    'totals' => $totals,
    'labaRugi' => $labaRugi,
    'totalAset' => $totalAset,
    'totalKewajiban' => $totalKewajiban,
    'totalEkuitas' => $totalEkuitas,
    'totalKewajibanEkuitas' => $totalKewajibanEkuitas,
    'tanggalAkhir' => $tanggalAkhir,
  ]), 'Neraca_' . $tanggalAkhir . '.xlsx');
}

/**
 * Laporan Laba Rugi (Income Statement)
 */
public function labaRugi(Request $request)
{
  $tanggalAwal = $request->input('tanggal_awal', now()->startOfMonth()->toDateString());
  $tanggalAkhir = $request->input('tanggal_akhir', now()->toDateString());

  // Kategori akun untuk Laba Rugi dengan breakdown per sub-kategori
  $categories = [
    'pendapatan' => ['pendapatan'],
    'pendapatan_lainnya' => ['pendapatan lainnya'],
    'hpp' => ['harga pokok penjualan'],
    'beban_operasional' => ['beban'],
    'beban_lainnya' => ['beban lainnya'],
    'depresiasi' => ['depresiasi dan amortisasi']
  ];

  $data = [];
  $totals = [
    'pendapatan' => 0,
    'pendapatan_lainnya' => 0,
    'hpp' => 0,
    'beban_operasional' => 0,
    'beban_lainnya' => 0,
    'depresiasi' => 0
  ];

  // Loop setiap kategori
  foreach ($categories as $groupName => $cats) {
    $akuns = \App\Akun::whereIn('category', $cats)
                      ->whereNull('deleted_at')
                      ->orderBy('akun_code')
                      ->get();

    $groupData = [];
    $groupTotal = 0;

    foreach ($akuns as $akun) {
      // Ambil transaksi dalam periode
      $saldo = DB::table('jurnals')
              ->where('id_akun', $akun->akun_code)
              ->whereBetween('date', [$tanggalAwal, $tanggalAkhir])
              ->whereNull('deleted_at')
              ->selectRaw('SUM(debet) as total_debet, SUM(kredit) as total_kredit')
              ->first();

      $debet = $saldo->total_debet ?? 0;
      $kredit = $saldo->total_kredit ?? 0;

      // Formula berdasarkan kategori
      if (in_array($groupName, ['pendapatan', 'pendapatan_lainnya'])) {
        // Pendapatan: Normal Kredit (Kredit - Debet)
        $saldoPeriode = $kredit - $debet;
      } else {
        // HPP & Beban: Normal Debet (Debet - Kredit)
        $saldoPeriode = $debet - $kredit;
      }

      // Hanya tampilkan akun yang ada saldonya
      if ($saldoPeriode != 0) {
        $groupData[] = [
          'akun_code' => $akun->akun_code,
          'name' => $akun->name,
          'category' => $akun->category,
          'saldo' => $saldoPeriode
        ];
        $groupTotal += $saldoPeriode;
      }
    }

    $data[$groupName] = $groupData;
    $totals[$groupName] = $groupTotal;
  }

  // Perhitungan Laba Rugi dengan breakdown
  $totalPendapatanUtama = $totals['pendapatan'] + $totals['pendapatan_lainnya'];
  $labaKotor = $totalPendapatanUtama - $totals['hpp'];
  $totalBeban = $totals['beban_operasional'] + $totals['beban_lainnya'] + $totals['depresiasi'];
  $labaBersih = $labaKotor - $totalBeban;

  return view('jurnal.laba_rugi', [
    'data' => $data,
    'totals' => $totals,
    'totalPendapatanUtama' => $totalPendapatanUtama,
    'labaKotor' => $labaKotor,
    'totalBeban' => $totalBeban,
    'labaBersih' => $labaBersih,
    'tanggalAwal' => $tanggalAwal,
    'tanggalAkhir' => $tanggalAkhir,
  ]);
}

/**
 * Export Laba Rugi ke PDF
 */
public function labaRugiPdf(Request $request)
{
  $tanggalAwal = $request->input('tanggal_awal', now()->startOfMonth()->toDateString());
  $tanggalAkhir = $request->input('tanggal_akhir', now()->toDateString());

  $categories = [
    'pendapatan' => ['pendapatan'],
    'pendapatan_lainnya' => ['pendapatan lainnya'],
    'hpp' => ['harga pokok penjualan'],
    'beban_operasional' => ['beban'],
    'beban_lainnya' => ['beban lainnya'],
    'depresiasi' => ['depresiasi dan amortisasi']
  ];

  $data = [];
  $totals = [
    'pendapatan' => 0,
    'pendapatan_lainnya' => 0,
    'hpp' => 0,
    'beban_operasional' => 0,
    'beban_lainnya' => 0,
    'depresiasi' => 0
  ];

  foreach ($categories as $groupName => $cats) {
    $akuns = \App\Akun::whereIn('category', $cats)->whereNull('deleted_at')->get();
    $groupData = [];
    $groupTotal = 0;

    foreach ($akuns as $akun) {
      $saldo = DB::table('jurnals')
              ->where('id_akun', $akun->akun_code)
              ->whereBetween('date', [$tanggalAwal, $tanggalAkhir])
              ->whereNull('deleted_at')
              ->selectRaw('SUM(debet) as total_debet, SUM(kredit) as total_kredit')
              ->first();

      $debet = $saldo->total_debet ?? 0;
      $kredit = $saldo->total_kredit ?? 0;

      if (in_array($groupName, ['pendapatan', 'pendapatan_lainnya'])) {
        $saldoPeriode = $kredit - $debet;
      } else {
        $saldoPeriode = $debet - $kredit;
      }

      if ($saldoPeriode != 0) {
        $groupData[] = [
          'akun_code' => $akun->akun_code,
          'name' => $akun->name,
          'category' => $akun->category,
          'saldo' => $saldoPeriode
        ];
        $groupTotal += $saldoPeriode;
      }
    }

    $data[$groupName] = $groupData;
    $totals[$groupName] = $groupTotal;
  }

  $totalPendapatanUtama = $totals['pendapatan'] + $totals['pendapatan_lainnya'];
  $labaKotor = $totalPendapatanUtama - $totals['hpp'];
  $totalBeban = $totals['beban_operasional'] + $totals['beban_lainnya'] + $totals['depresiasi'];
  $labaBersih = $labaKotor - $totalBeban;

  $pdf = Pdf::loadView('jurnal.laba_rugi_pdf', [
    'data' => $data,
    'totals' => $totals,
    'totalPendapatanUtama' => $totalPendapatanUtama,
    'labaKotor' => $labaKotor,
    'totalBeban' => $totalBeban,
    'labaBersih' => $labaBersih,
    'tanggalAwal' => $tanggalAwal,
    'tanggalAkhir' => $tanggalAkhir,
  ])->setPaper('A4', 'portrait');

  return $pdf->download('Laba_Rugi_' . $tanggalAwal . '_' . $tanggalAkhir . '.pdf');
}

/**
 * Export Laba Rugi ke Excel
 */
public function labaRugiExcel(Request $request)
{
  $tanggalAwal = $request->input('tanggal_awal', now()->startOfMonth()->toDateString());
  $tanggalAkhir = $request->input('tanggal_akhir', now()->toDateString());

  $categories = [
    'pendapatan' => ['pendapatan'],
    'pendapatan_lainnya' => ['pendapatan lainnya'],
    'hpp' => ['harga pokok penjualan'],
    'beban_operasional' => ['beban'],
    'beban_lainnya' => ['beban lainnya'],
    'depresiasi' => ['depresiasi dan amortisasi']
  ];

  $data = [];
  $totals = [
    'pendapatan' => 0,
    'pendapatan_lainnya' => 0,
    'hpp' => 0,
    'beban_operasional' => 0,
    'beban_lainnya' => 0,
    'depresiasi' => 0
  ];

  foreach ($categories as $groupName => $cats) {
    $akuns = \App\Akun::whereIn('category', $cats)->whereNull('deleted_at')->get();
    $groupData = [];
    $groupTotal = 0;

    foreach ($akuns as $akun) {
      $saldo = DB::table('jurnals')
              ->where('id_akun', $akun->akun_code)
              ->whereBetween('date', [$tanggalAwal, $tanggalAkhir])
              ->whereNull('deleted_at')
              ->selectRaw('SUM(debet) as total_debet, SUM(kredit) as total_kredit')
              ->first();

      $debet = $saldo->total_debet ?? 0;
      $kredit = $saldo->total_kredit ?? 0;

      if (in_array($groupName, ['pendapatan', 'pendapatan_lainnya'])) {
        $saldoPeriode = $kredit - $debet;
      } else {
        $saldoPeriode = $debet - $kredit;
      }

      if ($saldoPeriode != 0) {
        $groupData[] = [
          'akun_code' => $akun->akun_code,
          'name' => $akun->name,
          'category' => $akun->category,
          'saldo' => $saldoPeriode
        ];
        $groupTotal += $saldoPeriode;
      }
    }

    $data[$groupName] = $groupData;
    $totals[$groupName] = $groupTotal;
  }

  $totalPendapatanUtama = $totals['pendapatan'] + $totals['pendapatan_lainnya'];
  $labaKotor = $totalPendapatanUtama - $totals['hpp'];
  $totalBeban = $totals['beban_operasional'] + $totals['beban_lainnya'] + $totals['depresiasi'];
  $labaBersih = $labaKotor - $totalBeban;

  return Excel::download(new \App\Exports\LabaRugiExport([
    'data' => $data,
    'totals' => $totals,
    'totalPendapatanUtama' => $totalPendapatanUtama,
    'labaKotor' => $labaKotor,
    'totalBeban' => $totalBeban,
    'labaBersih' => $labaBersih,
    'tanggalAwal' => $tanggalAwal,
    'tanggalAkhir' => $tanggalAkhir,
  ]), 'Laba_Rugi_' . $tanggalAwal . '_' . $tanggalAkhir . '.xlsx');
}

public function neracaSaldo(Request $request)
{
  [$grouped, $grand, $tanggalAwal, $tanggalAkhir] = $this->getNeracaSaldoData($request);

  return view('jurnal.neraca_saldo', compact(
    'grouped', 'grand', 'tanggalAwal', 'tanggalAkhir'
  ));
}

public function exportExcel(Request $request)
{
  [$grouped, $grand, $tanggalAwal, $tanggalAkhir] = $this->getNeracaSaldoData($request);

  return Excel::download(
    new NeracaSaldoExport($grouped, $grand, $tanggalAwal, $tanggalAkhir),
    'NeracaSaldo.xlsx'
  );
}

public function exportPDF(Request $request)
{
  [$grouped, $grand, $tanggalAwal, $tanggalAkhir] = $this->getNeracaSaldoData($request);

  $pdf = Pdf::loadView('jurnal.neraca_saldo_export', compact(
    'grouped', 'grand', 'tanggalAwal', 'tanggalAkhir'
  ))->setPaper('a3', 'landscape');

  return $pdf->download('NeracaSaldo.pdf');
}

/**
 * Hitung data Neraca Saldo yang sudah dikelompokkan per grup + subtotal & grand total.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return array [$grouped, $grand, $tanggalAwal, $tanggalAkhir]
 */
private function getNeracaSaldoData($request)
{
  $tanggalAwal  = $request->input('tanggal_awal', now()->startOfMonth()->format('Y-m-d'));
  $tanggalAkhir = $request->input('tanggal_akhir', now()->endOfMonth()->format('Y-m-d'));

    // Label grup agar rapi
  $labelMap = [
    'aktiva'     => 'Assets',
    'aset'       => 'Assets',
    'kewajiban'  => 'Liabilities',
    'hutang'     => 'Liabilities',
    'ekuitas'    => 'Equity',
    'pendapatan' => 'Income',
    'beban'      => 'Expenses',
  ];

    // Cek apakah kolom id_akun di jurnals berisi ID angka atau kode akun
  $sampleAkun = \App\Jurnal::value('id_akun');
  $useCode = !is_numeric($sampleAkun);

    // Ambil daftar akun leaf (non-parent)
  $parentAkuns = \App\Akun::whereNotNull('parent')->distinct()->pluck('parent')->toArray();
  $akunLeaf = \App\Akun::select('id', 'akun_code', 'name', 'group')
  ->when(!empty($parentAkuns), function ($q) use ($parentAkuns) {
    $q->whereNotIn('akun_code', $parentAkuns);
  })
  ->orderBy('group')
  ->orderBy('akun_code')
  ->get();

    // ========== SALDO AWAL (tanpa JOIN, ringan) ==========
  $awalRows = DB::table('jurnals')
  ->select('id_akun', DB::raw('SUM(debet - kredit) AS saldo_awal'))
  ->where('date', '<', $tanggalAwal)
  ->whereNull('deleted_at')
  ->groupBy('id_akun')
  ->pluck('saldo_awal', 'id_akun');

    // ========== PERGERAKAN ==========
  $gerakRows = DB::table('jurnals')
  ->select('id_akun',
    DB::raw('SUM(debet) AS gerak_debit'),
    DB::raw('SUM(kredit) AS gerak_kredit')
  )
  ->whereBetween('date', [$tanggalAwal, $tanggalAkhir])
  ->whereNull('deleted_at')
  ->groupBy('id_akun')
  ->get()
  ->keyBy('id_akun');

    // ========== VARIABEL HASIL ==========
  $grouped = [];
  $grand = [
    'awal_debit'   => 0.0,
    'awal_kredit'  => 0.0,
    'gerak_debit'  => 0.0,
    'gerak_kredit' => 0.0,
    'akhir_debit'  => 0.0,
    'akhir_kredit' => 0.0,
  ];

  foreach ($akunLeaf as $a) {
    $keyLookup = $useCode ? $a->akun_code : $a->id;
    $group     = strtolower($a->group);
    $label     = $labelMap[$group] ?? ucfirst($a->group);
    $isKreditNormal = in_array($group, ['kewajiban', 'ekuitas', 'pendapatan']);

        // ambil data saldo awal dan pergerakan
    $rawAwal = (float)($awalRows[$keyLookup] ?? 0);
    $gerakD  = (float)($gerakRows[$keyLookup]->gerak_debit  ?? 0);
    $gerakK  = (float)($gerakRows[$keyLookup]->gerak_kredit ?? 0);

        // ========== PENYESUAIAN ARAH SALDO SESUAI BUKU BESAR ==========
        // di buku besar: akun normal kredit → saldoAwal = negatif
    $saldoAwal = $isKreditNormal ? -1 * $rawAwal : $rawAwal;

        // saldo akhir identik formula buku besar
    if ($isKreditNormal) {
      $saldoAkhir = $saldoAwal - $gerakD + $gerakK;
    } else {
      $saldoAkhir = $saldoAwal + $gerakD - $gerakK;
    }

        // ========== PEMECAHAN SALDO DEBIT/KREDIT ==========
        // saldo awal
    $awalDebit  = $saldoAwal > 0 ? $saldoAwal : 0;
    $awalKredit = $saldoAwal < 0 ? abs($saldoAwal) : 0;

        // saldo akhir
    if ($saldoAkhir >= 0) {
      $akhirDebit  = $isKreditNormal ? 0 : $saldoAkhir;
      $akhirKredit = $isKreditNormal ? $saldoAkhir : 0;
    } else {
      $akhirDebit  = $isKreditNormal ? abs($saldoAkhir) : 0;
      $akhirKredit = $isKreditNormal ? 0 : abs($saldoAkhir);
    }

        // inisialisasi grup
    if (!isset($grouped[$label])) {
      $grouped[$label] = [
        'rows' => [],
        'subtotal' => [
          'awal_debit'   => 0.0,
          'awal_kredit'  => 0.0,
          'gerak_debit'  => 0.0,
          'gerak_kredit' => 0.0,
          'akhir_debit'  => 0.0,
          'akhir_kredit' => 0.0,
        ],
      ];
    }

        // masukkan ke array hasil
    $grouped[$label]['rows'][] = [
      'kode'         => $a->akun_code,
      'nama'         => $a->name,
      'awal_debit'   => $awalDebit,
      'awal_kredit'  => $awalKredit,
      'gerak_debit'  => $gerakD,
      'gerak_kredit' => $gerakK,
      'akhir_debit'  => $akhirDebit,
      'akhir_kredit' => $akhirKredit,
    ];

        // subtotal per grup
    foreach (['awal_debit', 'awal_kredit', 'gerak_debit', 'gerak_kredit', 'akhir_debit', 'akhir_kredit'] as $col) {
      $grouped[$label]['subtotal'][$col] += $grouped[$label]['rows'][array_key_last($grouped[$label]['rows'])][$col];
      $grand[$col] += $grouped[$label]['rows'][array_key_last($grouped[$label]['rows'])][$col];
    }
  }

  return [$grouped, $grand, $tanggalAwal, $tanggalAkhir];
}



public function store(Request $request)
{
       // dd ($request);
  $type="jumum";
  $request ->validate([

    'date' => 'required',
    'debet' => 'required|numeric',
    'kredit' => 'required|numeric',
    'amount' => 'required|numeric',
    'description' => 'required',

  ]);
  if(!empty($request['reff']))
  {
    $reff = $request['reff'];
  }
  else
  {
   $reff = uniqid();
 }
 $code = substr(md5(uniqid('', true)), 0, 10);
 \App\Jurnal::create([
  'date' => ($request['date']),
  'id_akun' => ($request['debet']),
  'debet' => ($request['amount']), 
  'reff' => $reff,
  'type' => $type,
  'description' => ($request['description']),
  'code' => $code,
  'contact_id' => '0000000001',
]);
 \App\Jurnal::create([
  'date' => ($request['date']),
  'id_akun' => ($request['kredit']),
  'kredit' => ($request['amount']), 
  'reff' => $reff,
  'type' => $type,
  'description' => ($request['description']),
  'code' => $code,
  'contact_id' => '0000000001',
]);

 $jurnal = \App\Jurnal::orderBy('date','ASC')->get();
 $akuntransaction = \App\Akuntransaction::pluck('name', 'id', 'debet');

 return redirect ('/jurnal')->with('success','Item created successfully!');


        //
}

public function trxstore(Request $request)
{
    //   dd ($request);
 // $type="jcustom";
  $request ->validate([

    'date' => 'required',
    'akun' => 'required|numeric',
    'debetkredit' => 'required',
    'amount' => 'required|numeric',
    'description' => 'required',

  ]);
  if(!empty($request['reff']))
  {
    $reff = $request['reff'];
  }
  else
  {
   $reff = uniqid();
 }
 $code = substr(md5(uniqid('', true)), 0, 10);
 if($request['debetkredit']=='d')
 {

  \App\Jurnal::create([
    'date' => ($request['date']),
    'id_akun' => ($request['akun']),
    'debet' => ($request['amount']), 
    'reff' => $reff,
    'type' => ($request['type']),
    'description' => ($request['description']),
    'code' => $code,
    'contact_id' => '0000000001',
  ]);
}
else
{
 \App\Jurnal::create([
  'date' => ($request['date']),
  'id_akun' => ($request['akun']),
  'kredit' => ($request['amount']), 
  'reff' => $reff,
  'type' => ($request['type']),
  'description' => ($request['description']),
  'code' => $code,
  'contact_id' => '0000000001',
]);

}


return redirect ('/jurnal/trxcreate')->with('success','Item created successfully!');


        //
}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function trxupdate(Request $request)
    {
      $id = $request->jurnalid;
      $reff = uniqid();


      DB::beginTransaction();
      try {

        foreach ($id as $id) 
        {

          \App\Jurnal::where('id', $id)->update([
            'type' => 'jumum',

            'reff' => $reff,
          ]);

        }
        DB::commit();
        return redirect ('/jurnal')->with('success','Item created successfully!');

      } catch (Exception $e) {
       // Rollback Transaction
       DB::rollback();
       return redirect ('/jurnal')->with('error','Process Failed!');

       // ada yang error
     }




   }
   public function generaldel($id)
   {
    try{
      \App\Jurnal::destroy($id);
      return redirect ('/jurnal/trxcreate')->with('success','Item deleted successfully!');
    }catch (Exception $e) {

     return redirect ('/jurnal/trxcreate')->with('error','Process Failed!');
   }
 }
 public function cupdate(Request $request)
 {
  $id = $request->jcustomid;
  $reff = uniqid();

  foreach ($id as $id) 
  {

    \App\Jurnal::where('id', $id)->update([
      'type' => 'jumum',

      'reff' => $reff,
    ]);

  }

}
public function show($code)
{
  $jurnals = \App\Jurnal::with('akun')
  ->where('code', $code)
  ->orderBy('id', 'asc')
  ->get();

  $totalDebet  = $jurnals->sum('debet');
  $totalKredit = $jurnals->sum('kredit');

  $akunList = \App\Akun::orderBy('akun_code', 'asc')
    ->select('akun_code', 'name', 'group')
    ->whereNull('parent')
    ->orWhereNotNull('parent')
    ->get();

  return view('jurnal.show', [
   'note'        => optional($jurnals->first())->note,
   'memo'        => optional($jurnals->first())->memo,
   'code'        => $code,
   'jurnals'     => $jurnals,
   'totalDebet'  => $totalDebet,
   'totalKredit' => $totalKredit,
   'akunList'    => $akunList,
 ]);
}

public function updateByCode(Request $request, $code)
{
  $request->validate([
    'date'               => 'required|date',
    'rows'               => 'required|array|min:1',
    'rows.*.id'          => 'nullable|string',
    'rows.*.id_akun'     => 'required|string',
    'rows.*.description' => 'nullable|string',
    'rows.*.debet'       => 'required|numeric|min:0',
    'rows.*.kredit'      => 'required|numeric|min:0',
  ]);

  // Get first existing row to copy shared fields (type, contact_id, category, reff, created_by)
  $template = \App\Jurnal::where('code', $code)->whereNull('deleted_at')->first();
  if (!$template) {
    return response()->json(['success' => false, 'message' => 'Jurnal tidak ditemukan.'], 404);
  }

  DB::beginTransaction();
  try {
    // Update shared date/memo for all existing rows
    \App\Jurnal::where('code', $code)->whereNull('deleted_at')->update([
      'date' => $request->date,
      'memo' => $request->memo ?? '',
    ]);

    // Collect submitted IDs (non-empty = existing rows)
    $submittedIds = collect($request->rows)
      ->pluck('id')
      ->filter(fn($id) => !empty($id))
      ->map(fn($id) => (int)$id)
      ->toArray();

    // Soft-delete rows that were removed
    \App\Jurnal::where('code', $code)
      ->whereNull('deleted_at')
      ->when(!empty($submittedIds), fn($q) => $q->whereNotIn('id', $submittedIds))
      ->delete();

    // Update existing rows / create new rows
    foreach ($request->rows as $row) {
      if (!empty($row['id'])) {
        // Update existing
        \App\Jurnal::where('id', (int)$row['id'])
          ->where('code', $code)
          ->update([
            'id_akun'     => $row['id_akun'],
            'description' => $row['description'] ?? '',
            'debet'       => $row['debet'],
            'kredit'      => $row['kredit'],
          ]);
      } else {
        // Create new row, copy shared fields from template
        \App\Jurnal::create([
          'code'        => $code,
          'date'        => $request->date,
          'type'        => $template->type,
          'reff'        => $template->reff,
          'contact_id'  => $template->contact_id,
          'category'    => $template->category,
          'memo'        => $request->memo ?? '',
          'note'        => $template->note,
          'created_by'  => $template->created_by,
          'id_akun'     => $row['id_akun'],
          'description' => $row['description'] ?? '',
          'debet'       => $row['debet'],
          'kredit'      => $row['kredit'],
        ]);
      }
    }

    DB::commit();
    return response()->json(['success' => true, 'message' => 'Jurnal berhasil diperbarui.']);
  } catch (\Exception $e) {
    DB::rollBack();
    return response()->json(['success' => false, 'message' => 'Gagal update: ' . $e->getMessage()], 500);
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
        //
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
      try{
        \App\Jurnal::destroy($id);
        return redirect ('/jurnal')->with('success','Item deleted successfully!');
      }catch (Exception $e) {

       return redirect ('/jurnal')->with('error','Process Failed!');
     }
   }

   public function post(Request $request){
    $response = array(
      'status' => 'success',
      'msg' => $request->message,
    );

    return response()->json($response); 
  }





  public function terimakas()
  {

   $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
         //$transactionname = \App\akuntransaction::Where('id',$request->akuntransaction)->first();
   $cjurnal =\App\Jurnal::Where('type','jcustom')->get();
         //dd($cjurnal);
   $akun = \App\Akun::all();
   return view ('jurnal/terimakas',['cjurnal' => $cjurnal,'akun' => $akun, 'akuntransaction' =>$akuntransaction]);

 }
 public function kirimkas()
 {

   $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
         //$transactionname = \App\akuntransaction::Where('id',$request->akuntransaction)->first();
   $cjurnal =\App\Jurnal::Where('type','jcustom')->get();
         //dd($cjurnal);
   $akun = \App\Akun::all();
   return view ('jurnal/kirimkas',['cjurnal' => $cjurnal,'akun' => $akun, 'akuntransaction' =>$akuntransaction]);

 }

// ================== TAMPILAN ==================
 public function neracaFormatted(Request $request)
 {
  [$tanggalAkhir, $company, $aset, $liabilitas, $ekuitas, $totalAset, $totalLiabilitas, $totalEkuitas]
  = $this->getNeracaFormattedData($request);

  return view('jurnal.neraca_formatted', compact(
    'tanggalAkhir','company','aset','liabilitas','ekuitas',
    'totalAset','totalLiabilitas','totalEkuitas'
  ));
}

// ================== EXPORT PDF ==================
public function neracaFormattedPDF(Request $request)
{
  [$tanggalAkhir, $company, $aset, $liabilitas, $ekuitas, $totalAset, $totalLiabilitas, $totalEkuitas]
  = $this->getNeracaFormattedData($request);

  $pdf = Pdf::loadView('jurnal.neraca_formatted_export', compact(
    'tanggalAkhir','company','aset','liabilitas','ekuitas',
    'totalAset','totalLiabilitas','totalEkuitas'
  ))->setPaper('a3','landscape');

  return $pdf->download('Neraca.pdf');
}

// ================== EXPORT EXCEL ==================
public function neracaFormattedExcel(Request $request)
{
  [$tanggalAkhir, $company, $aset, $liabilitas, $ekuitas, $totalAset, $totalLiabilitas, $totalEkuitas]
  = $this->getNeracaFormattedData($request);

  return Excel::download(new class(
    $tanggalAkhir,$company,$aset,$liabilitas,$ekuitas,$totalAset,$totalLiabilitas,$totalEkuitas
  ) implements \Maatwebsite\Excel\Concerns\FromView {
    public function __construct(
      public $tanggalAkhir, public $company, public $aset, public $liabilitas,
      public $ekuitas, public $totalAset, public $totalLiabilitas, public $totalEkuitas
    ) {}
    public function view(): \Illuminate\Contracts\View\View {
      return view('jurnal.neraca_formatted_export', [
        'tanggalAkhir'=>$this->tanggalAkhir,
        'company'=>$this->company,
        'aset'=>$this->aset,
        'liabilitas'=>$this->liabilitas,
        'ekuitas'=>$this->ekuitas,
        'totalAset'=>$this->totalAset,
        'totalLiabilitas'=>$this->totalLiabilitas,
        'totalEkuitas'=>$this->totalEkuitas,
      ]);
    }
  }, 'Neraca.xlsx');
}

// ================== DATA BUILDER (UTUH, TANPA buildAset/…) ==================
private function getNeracaFormattedData(\Illuminate\Http\Request $request)
{
    // Neraca: posisi per 1 tanggal
  $tanggalAkhir = $request->input('tanggal_akhir', now()->toDateString());
  $company = config('app.company', env('COMPANY', 'Perusahaan'));

    // ====== MAPPING kategori → bagian Neraca (SESUAIKAN dg isi kolom akuns.category anda) ======
  $mapAset = [
    'Aset Lancar' => ['kas & bank','akun piutang','persediaan','prepaid tax','beban dibayar dimuka','ppn masukan','pajak dibayar di muka'],
    'Aset Tetap'  => ['aktiva tetap'],
        'Depresiasi & Amortisasi' => ['akumulasi penyusutan','akumulasi amortisasi'], // kontra-aset (ditampilkan minus)
        'Lain-lain'   => ['aktiva lainnya','aktiva lancar lainnya'],
      ];
      $mapLia = [
        'Liabilitas Jangka Pendek'  => ['akun hutang','hutang usaha','hutang dagang','kewajiban lancar lainnya','hutang pajak','titipan','deposit','ppn keluaran','hutang pph','fee','intercompany','contra'],
        'Liabilitas Jangka Panjang' => ['kewajiban jangka panjang'],
      ];
      $mapEquity = [
        'Modal Pemilik' => ['ekuitas','modal','laba ditahan','deviden','prive'],
      ];

    // ====== Ambil akun leaf (bukan parent) ======
      $parentAkuns = \App\Akun::whereNotNull('parent')->distinct()->pluck('parent')->toArray();
      $akun = \App\Akun::select('id','akun_code','name','group','category')
      ->when(!empty($parentAkuns), fn($q)=>$q->whereNotIn('akun_code',$parentAkuns))
      ->orderBy('group')->orderBy('akun_code')->get();

    // ====== SALDO KUMULATIF s/d tanggalAkhir ======
    // CATATAN: Asumsi jurnals.id_akun menyimpan akun_code.
    // Jika di DB anda jurnals.id_akun = akuns.id, ubah $code = $a->id; dan pencarian keyBy tetap id_akun.
      $saldoRows = DB::table('jurnals')
      ->select('id_akun', DB::raw('SUM(debet) AS d'), DB::raw('SUM(kredit) AS k'))
      ->where('date','<=',$tanggalAkhir)
       ->whereNull('jurnals.deleted_at')     // <— penting
       ->groupBy('id_akun')
       ->get()->keyBy('id_akun');

    // ====== Laba rugi split (s.d 31/12 thn lalu, & periode berjalan thn ini) ======
       [$labaSampaiTahunLalu, $labaPeriodeIni] = $this->hitungLabaRugiSplit($tanggalAkhir);

    // ====== SUSUN: ASET ======
       $aset = []; $totalAset = 0.0;
       foreach ($mapAset as $judul => $kategoriList) {
        $rows = []; $subtotal = 0.0;

        foreach ($akun->where('group','aktiva') as $a) {
          $kat = strtolower(trim($a->category ?? ''));
          $match = collect($kategoriList)->first(fn($k)=>str_contains($kat, strtolower($k)));
          if (!$match) continue;

            $code = $a->akun_code; // ganti ke $a->id jika jurnals.id_akun = akuns.id
            $d = isset($saldoRows[$code]) ? (float)$saldoRows[$code]->d : 0.0;
            $k = isset($saldoRows[$code]) ? (float)$saldoRows[$code]->k : 0.0;
            $nilai = $d - $k; // saldo normal aset: debit

            if ($judul === 'Depresiasi & Amortisasi') {
                // kontra-aset → tampilkan negatif
              $nilai = -abs($nilai ?: 0.0);
            }
            if (abs($nilai) < 0.0000001) continue;

            $rows[] = ['kode'=>$a->akun_code, 'nama'=>$a->name, 'nilai'=>$nilai];
            $subtotal += $nilai;
          }

          $aset[$judul] = ['rows'=>$rows,'subtotal'=>$subtotal];
          $totalAset += $subtotal;
        }

    // ====== SUSUN: LIABILITAS ======
        $liabilitas = []; $totalLiabilitas = 0.0;
        foreach ($mapLia as $judul => $kategoriList) {
          $rows = []; $subtotal = 0.0;

          foreach ($akun->where('group','kewajiban') as $a) {
            $kat = strtolower(trim($a->category ?? ''));
            $match = collect($kategoriList)->first(fn($k)=>str_contains($kat, strtolower($k)));
            if (!$match) continue;

            $code = $a->akun_code; // ganti ke $a->id jika perlu
            $d = isset($saldoRows[$code]) ? (float)$saldoRows[$code]->d : 0.0;
            $k = isset($saldoRows[$code]) ? (float)$saldoRows[$code]->k : 0.0;
            $nilai = $k - $d; // saldo normal kewajiban: kredit

            if (abs($nilai) < 0.0000001) continue;

            $rows[] = ['kode'=>$a->akun_code, 'nama'=>$a->name, 'nilai'=>$nilai];
            $subtotal += $nilai;
          }

          $liabilitas[$judul] = ['rows'=>$rows,'subtotal'=>$subtotal];
          $totalLiabilitas += $subtotal;
        }

    // ====== SUSUN: EKUITAS (+ Laba Rugi) ======
        $ekuitas = []; $totalEkuitas = 0.0;
        foreach ($mapEquity as $judul => $kategoriList) {
          $rows = []; $subtotal = 0.0;

          foreach ($akun->where('group','ekuitas') as $a) {
            $kat = strtolower(trim($a->category ?? ''));
            $match = collect($kategoriList)->first(fn($k)=>str_contains($kat, strtolower($k)));
            if (!$match) continue;

            $code = $a->akun_code; // ganti ke $a->id jika perlu
            $d = isset($saldoRows[$code]) ? (float)$saldoRows[$code]->d : 0.0;
            $k = isset($saldoRows[$code]) ? (float)$saldoRows[$code]->k : 0.0;
            $nilai = $k - $d; // saldo normal ekuitas: kredit

            // prive / deviden → tampil sebagai negatif modal
            if (str_contains($kat,'prive') || str_contains($kat,'deviden')) {
              $nilai = -abs($nilai ?: 0.0);
            }
            if (abs($nilai) < 0.0000001) continue;

            $rows[] = ['kode'=>$a->akun_code, 'nama'=>$a->name, 'nilai'=>$nilai];
            $subtotal += $nilai;
          }

        // Tambahkan laba rugi sebagai baris khusus (seperti di contoh)
          if (abs($labaSampaiTahunLalu) > 0.0) {
            $rows[] = ['kode'=>'', 'nama'=>'Pendapatan sampai Tahun lalu', 'nilai'=>$labaSampaiTahunLalu];
            $subtotal += $labaSampaiTahunLalu;
          }
          if (abs($labaPeriodeIni) > 0.0) {
            $rows[] = ['kode'=>'', 'nama'=>'Pendapatan Periode ini', 'nilai'=>$labaPeriodeIni];
            $subtotal += $labaPeriodeIni;
          }

          $ekuitas[$judul] = ['rows'=>$rows,'subtotal'=>$subtotal];
          $totalEkuitas += $subtotal;
        }

        return [$tanggalAkhir, $company, $aset, $liabilitas, $ekuitas, $totalAset, $totalLiabilitas, $totalEkuitas];
      }

// ====== LABA RUGI SPLIT & HELPER ======
      private function hitungLabaRugiSplit(string $tanggalAkhir): array
      {
        $tahun = (int)date('Y', strtotime($tanggalAkhir));
        $awalTahunIni = date('Y-01-01', strtotime($tanggalAkhir));
        $akhirTahunLalu = date(($tahun-1).'-12-31');

    // Pendapatan: kredit - debet
        $pendapatanLalu = $this->sumKelompok(['pendapatan','pendapatan lainnya'], 'kredit','debet', null, $akhirTahunLalu);
        $bebanLalu      = $this->sumKelompok(['beban','harga pokok','depresiasi'], 'debet','kredit', null, $akhirTahunLalu);
        $lalu = $pendapatanLalu - $bebanLalu;

        $pendapatanIni = $this->sumKelompok(['pendapatan','pendapatan lainnya'], 'kredit','debet', $awalTahunIni, $tanggalAkhir);
        $bebanIni      = $this->sumKelompok(['beban','harga pokok','depresiasi'], 'debet','kredit', $awalTahunIni, $tanggalAkhir);
        $ini = $pendapatanIni - $bebanIni;

        return [$lalu, $ini];
      }

      private function sumKelompok(array $cats, string $sideMain, string $sideContra, ?string $from, ?string $to): float
      {
    // Ambil akun berdasarkan category (mengandung teks)
        $akunCodes = \App\Akun::where(function($q) use ($cats) {
          foreach ($cats as $c) {
            $q->orWhere('category','like','%'.$c.'%');
          }
    })->pluck('akun_code')->toArray();  // ganti ke 'id' jika jurnals.id_akun = akuns.id

        if (empty($akunCodes)) return 0.0;

        $q = DB::table('jurnals')
        ->select(DB::raw("SUM($sideMain) AS sm"), DB::raw("SUM($sideContra) AS sc"))
        ->whereIn('id_akun', $akunCodes);

        if ($from) $q->where('date','>=',$from);
        if ($to)   $q->where('date','<=',$to);

        $row = $q->first();
        $sm = (float)($row->sm ?? 0);
        $sc = (float)($row->sc ?? 0);
        return $sm - $sc;
      }


 // public function transferkas()
 // {

 //   $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
 //         //$transactionname = \App\akuntransaction::Where('id',$request->akuntransaction)->first();
 //   $cjurnal =\App\Jurnal::Where('type','jcustom')->get();
 //         //dd($cjurnal);
 //   $akun = \App\Akun::all();
 //   return view ('jurnal/transferkas',['cjurnal' => $cjurnal,'akun' => $akun, 'akuntransaction' =>$akuntransaction]);

 // }


      public function labaRugiFormatted(Request $request)
      {
        [
          $tanggalAwal,$tanggalAkhir,$company,
          $pendapatan,$cogs,$grossProfit,
          $opex,$operatingProfit,
          $otherIncome,$otherExpense,$otherNet,
          $netProfit
        ] = $this->getLabaRugiData($request);

        return view('jurnal.laba_rugi_formatted', compact(
          'tanggalAwal','tanggalAkhir','company',
          'pendapatan','cogs','grossProfit',
          'opex','operatingProfit',
          'otherIncome','otherExpense','otherNet',
          'netProfit'
        ));
      }

      public function labaRugiFormattedPDF(Request $request)
      {
        [
          $tanggalAwal,$tanggalAkhir,$company,
          $pendapatan,$cogs,$grossProfit,
          $opex,$operatingProfit,
          $otherIncome,$otherExpense,$otherNet,
          $netProfit
        ] = $this->getLabaRugiData($request);

        $pdf = Pdf::loadView('jurnal.laba_rugi_formatted_export', compact(
          'tanggalAwal','tanggalAkhir','company',
          'pendapatan','cogs','grossProfit',
          'opex','operatingProfit',
          'otherIncome','otherExpense','otherNet',
          'netProfit'
        ))->setPaper('a4','portrait');

        return $pdf->download('Laba_Rugi.pdf');
      }

      public function labaRugiFormattedExcel(Request $request)
      {
        [
          $tanggalAwal,$tanggalAkhir,$company,
          $pendapatan,$cogs,$grossProfit,
          $opex,$operatingProfit,
          $otherIncome,$otherExpense,$otherNet,
          $netProfit
        ] = $this->getLabaRugiData($request);

        return Excel::download(new class(
          $tanggalAwal,$tanggalAkhir,$company,
          $pendapatan,$cogs,$grossProfit,
          $opex,$operatingProfit,
          $otherIncome,$otherExpense,$otherNet,
          $netProfit
        ) implements \Maatwebsite\Excel\Concerns\FromView {
          public function __construct(
            public $tanggalAwal, public $tanggalAkhir, public $company,
            public $pendapatan, public $cogs, public $grossProfit,
            public $opex, public $operatingProfit,
            public $otherIncome, public $otherExpense, public $otherNet,
            public $netProfit
          ) {}
          public function view(): \Illuminate\Contracts\View\View {
            return view('jurnal.laba_rugi_formatted_export', [
              'tanggalAwal'=>$this->tanggalAwal,
              'tanggalAkhir'=>$this->tanggalAkhir,
              'company'=>$this->company,
              'pendapatan'=>$this->pendapatan,
              'cogs'=>$this->cogs,
              'grossProfit'=>$this->grossProfit,
              'opex'=>$this->opex,
              'operatingProfit'=>$this->operatingProfit,
              'otherIncome'=>$this->otherIncome,
              'otherExpense'=>$this->otherExpense,
              'otherNet'=>$this->otherNet,
              'netProfit'=>$this->netProfit,
            ]);
          }
        }, 'Laba_Rugi.xlsx');
      }

      /** ====== DATA BUILDER (berbasis group/category) ====== */
      public function getLabaRugiData(Request $request)
      {
    // Periode (inklusif awal & akhir hari)
        $tanggalAwal  = $request->input('tanggal_awal', now()->startOfMonth()->format('Y-m-d'));
        $tanggalAkhir = $request->input('tanggal_akhir', now()->endOfMonth()->format('Y-m-d'));
        $start = \Carbon\Carbon::parse($tanggalAwal)->startOfDay();
        $end   = \Carbon\Carbon::parse($tanggalAkhir)->endOfDay();

    // Nama perusahaan (opsional)
        $company = config('app.company', env('COMPANY','Perusahaan'));

    // =======================
    // Agregasi jurnal per akun_code (SATU query)
    // jurnals.id_akun MENYIMPAN akun_code
    // =======================
        $sums = \App\Jurnal::whereBetween('date', [$start, $end])
        ->groupBy('id_akun')
        ->selectRaw('id_akun, COALESCE(SUM(debet),0) AS deb, COALESCE(SUM(kredit),0) AS kre')
        ->get()
        ->keyBy('id_akun')
        ->map(fn($r) => ['deb' => (float)$r->deb, 'kre' => (float)$r->kre])
        ->toArray();

    // Helper ambil nilai per akun_code
    // - Revenue / Other income: kredit - debet
    // - Expense / HPP / Other expense: debet - kredit
        $val = function (string $akunCode, bool $isExpense) use ($sums) {
          $deb = $sums[$akunCode]['deb'] ?? 0.0;
          $kre = $sums[$akunCode]['kre'] ?? 0.0;
          return $isExpense ? ($deb - $kre) : ($kre - $deb);
        };

    // Ambil semua akun yang diperlukan
        $akuns = \App\Akun::select('akun_code','name','group','category','type','parent')->get();

    // Helper membuat section (rows + subtotal) dari koleksi akun
        $makeSection = function($akunList, bool $isExpense) use ($val) {
          $rows = [];
          foreach ($akunList as $a) {
            $amount = $val($a->akun_code, $isExpense);
            // Jika ingin sembunyikan nol: if (abs($amount) < 0.005) continue;
            $rows[] = [
              'kode'  => $a->akun_code,
              'nama'  => $a->name,
              'nilai' => $amount,
            ];
          }
          usort($rows, fn($x,$y) => strcmp($x['kode'],$y['kode']));
          $subtotal = array_sum(array_column($rows, 'nilai'));
          return ['rows' => $rows, 'subtotal' => $subtotal];
        };

    // =======================
    // Definisi dinamis per-bagian (tanpa mapping manual)
    // =======================

    // 1) Pendapatan (utama)
        $pendapatanAkuns = $akuns->filter(function($a){
          if ($a->group !== 'pendapatan') return false;
        if (\Illuminate\Support\Str::startsWith($a->akun_code, '4-')) return true; // guard natural
        return \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($a->category ?? ''), 'pendapatan');
      });
        $pendapatan = $makeSection($pendapatanAkuns, false);

    // 2) HPP / COGS
        $hppKeys = ['harga pokok','beban pembelian','beban cctv','beban pengiriman','bandwidth','bandwith'];
        $cogsAkuns = $akuns->filter(function($a) use ($hppKeys){
          if ($a->group !== 'beban') return false;
        if (\Illuminate\Support\Str::startsWith($a->akun_code, '5-')) return true; // natural 5-xxxxx
        $cat = \Illuminate\Support\Str::lower($a->category ?? '');
        foreach ($hppKeys as $kw) if (\Illuminate\Support\Str::contains($cat, $kw)) return true;
        return false;
      });
        $cogs = $makeSection($cogsAkuns, true);

    // 3) Beban Operasional (semua beban kode 6-xxxxx), dibagi ke bucket
        $opexAll = $akuns->filter(fn($a) => $a->group === 'beban' && \Illuminate\Support\Str::startsWith($a->akun_code, '6-'));

        $salesKeys = ['penjualan','marketing','promosi','iklan','komisi','fee'];
        $gandaKeys = [
          'gaji','bpjs','kesehatan','ketenagakerjaan','konsumsi','lembur','hiburan','perbaikan','pemeliharaan',
          'perizinan','listrik','software','telephone','telepon','transport','perlengkapan','sewa','penyusutan',
          'kendaraan','kebersihan','konsultan','suka duka','tripay','winpay','bonus'
        ];

        $isSales = function($a) use ($salesKeys) {
          $t = \Illuminate\Support\Str::lower(($a->category ?? '').' '.$a->name);
          foreach ($salesKeys as $kw) if (\Illuminate\Support\Str::contains($t, $kw)) return true;
        // fallback: 6-600xx sering dipakai biaya penjualan
          return \Illuminate\Support\Str::startsWith($a->akun_code, '6-600');
        };
        $isGanda = function($a) use ($gandaKeys) {
          $t = \Illuminate\Support\Str::lower(($a->category ?? '').' '.$a->name);
          foreach ($gandaKeys as $kw) if (\Illuminate\Support\Str::contains($t, $kw)) return true;
        return true; // default masuk G&A bila bukan sales
      };

      $opex = [];
      $opexSubtotal = 0;

      $opexBuckets = [
        'Biaya Penjualan'            => $opexAll->filter($isSales),
        'Biaya Umum & Administratif' => $opexAll->reject($isSales)->filter($isGanda),
        'Lainnya'                    => collect(), // sisa (opsional)
      ];

      foreach ($opexBuckets as $judul => $list) {
        if ($list->isEmpty()) { $opex[$judul] = ['rows'=>[], 'subtotal'=>0]; continue; }
        $sec = $makeSection($list, true);
        $opex[$judul] = $sec;
        $opexSubtotal += $sec['subtotal'];
      }

    // 4) Other Income (7-xxxxx atau kategori "pendapatan lainnya"/"bunga")
      $otherIncomeKeys = ['pendapatan lainnya','bunga'];
      $otherIncomeAkuns = $akuns->filter(function($a) use ($otherIncomeKeys){
        if ($a->group !== 'pendapatan') return false;
        if (\Illuminate\Support\Str::startsWith($a->akun_code, '7-')) return true;
        $cat = \Illuminate\Support\Str::lower($a->category ?? '');
        foreach ($otherIncomeKeys as $kw) if (\Illuminate\Support\Str::contains($cat, $kw)) return true;
        return false;
      });
      $otherIncome = $makeSection($otherIncomeAkuns, false);

    // 5) Other Expense (8-xxxxx atau kategori "beban bunga/lainnya/jasa giro")
      $otherExpenseKeys = ['beban bunga','beban lainnya','beban jasa giro'];
      $otherExpenseAkuns = $akuns->filter(function($a) use ($otherExpenseKeys){
        if ($a->group !== 'beban') return false;
        if (\Illuminate\Support\Str::startsWith($a->akun_code, '8-')) return true;
        $cat = \Illuminate\Support\Str::lower($a->category ?? '');
        foreach ($otherExpenseKeys as $kw) if (\Illuminate\Support\Str::contains($cat, $kw)) return true;
        return false;
      });
      $otherExpense = $makeSection($otherExpenseAkuns, true);

    // =======================
    // Ringkasan
    // =======================
      $grossProfit     = $pendapatan['subtotal'] - $cogs['subtotal'];
      $operatingProfit = $grossProfit - $opexSubtotal;
      $otherNet        = $otherIncome['subtotal'] - $otherExpense['subtotal'];
      $netProfit       = $operatingProfit + $otherNet;

    // Urutan return sesuai yang dipakai di controller pemanggil
      return [
        $tanggalAwal,
        $tanggalAkhir,
        $company,
        $pendapatan,        // ['rows'=>[...], 'subtotal'=>x]
        $cogs,              // idem
        $grossProfit,
        $opex,              // ['Biaya Penjualan'=>['rows'=>..,'subtotal'=>..], 'Biaya Umum & Administratif'=>..., 'Lainnya'=>...]
        $operatingProfit,
        $otherIncome,       // ['rows'=>..,'subtotal'=>..]
        $otherExpense,      // idem
        $otherNet,
        $netProfit,
      ];
    }

    /** Map seksi L/R via group + category/nama */
    private function mapSectionByGroupCategory(
      ?string $group, ?string $category, ?string $name,
      array $catCogs, array $catOpexPenjualan, array $catOpexUmum,
      array $catOtherIncome, array $catOtherExpense
    ): ?string {
      $g = strtolower(trim($group ?? ''));
      $c = strtolower(trim($category ?? ''));
      $n = strtolower(trim($name ?? ''));

  // Neraca tidak tampil di L/R
      if (in_array($g, ['aktiva','kewajiban','ekuitas'])) return null;

      if ($g === 'pendapatan') {
        if ($this->containsAny([$c,$n], $catOtherIncome)) return 'other_income';
        return 'pendapatan';
      }

      if ($g === 'beban') {
        if ($this->containsAny([$c,$n], $catCogs))          return 'cogs';
        if ($this->containsAny([$c,$n], $catOtherExpense))  return 'other_expense';
        if ($this->containsAny([$c,$n], $catOpexPenjualan)) return 'opex_penjualan';
        if ($this->containsAny([$c,$n], $catOpexUmum))      return 'opex_umum';
        return 'opex_umum';
      }

  // fallback bila group kosong
      if ($this->containsAny([$c,$n], $catOtherIncome))  return 'other_income';
      if ($this->containsAny([$c,$n], $catOtherExpense)) return 'other_expense';
      if ($this->containsAny([$c,$n], $catCogs))         return 'cogs';
      if ($this->containsAny([$c,$n], $catOpexPenjualan))return 'opex_penjualan';
      if ($this->containsAny([$c,$n], $catOpexUmum))     return 'opex_umum';
      if (str_contains($c,'pendapatan') || str_contains($n,'pendapatan')) return 'pendapatan';

      return null;
    }

    private function containsAny(array $texts, array $needles): bool
    {
      foreach ($texts as $t) {
        $t = strtolower($t ?? '');
        foreach ($needles as $n) {
          $n = strtolower($n);
          if ($n !== '' && str_contains($t, $n)) return true;
        }
      }
      return false;
    }

    
  }
