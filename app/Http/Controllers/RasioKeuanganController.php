<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RasioKeuanganExport;

class RasioKeuanganController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->generateRasioData($request);
        return view('laporan.rasio_keuangan', $data);
    }

    public function exportPdf(Request $request)
    {
        $data = $this->generateRasioData($request);
        $pdf = Pdf::loadView('laporan.rasio_keuangan_pdf', $data)->setPaper('A4','portrait');
        return $pdf->download('Laporan_Rasio_Keuangan.pdf');
    }

    public function exportExcel(Request $request)
    {
        $data = $this->generateRasioData($request);
        return Excel::download(new RasioKeuanganExport($data), 'Laporan_Rasio_Keuangan.xlsx');
    }

    private function generateRasioData(Request $request): array
    {
        // Ambil data laba rugi (periode)
        $jurnalCtrl = app(\App\Http\Controllers\JurnalController::class);
        [
            $tanggalAwal,$tanggalAkhir,$company,
            $pendapatan,$cogs,$grossProfit,
            $opex,$operatingProfit,
            $otherIncome,$otherExpense,$otherNet,
            $netProfit
        ] = $jurnalCtrl->getLabaRugiData($request);

        // Ambil data neraca
        $kasBank = $this->saldoKategori('kas & bank','<=',$tanggalAkhir,'debet - kredit');
        $piutang = $this->saldoKategori('akun piutang','<=',$tanggalAkhir,'debet - kredit');
        $persediaan = $this->saldoKategori('persediaan','<=',$tanggalAkhir,'debet - kredit');
        $asetTetap = $this->saldoKategori('aktiva tetap','<=',$tanggalAkhir,'debet - kredit');
        $akumulasiPenyusutan = $this->saldoKategori('depresiasi dan amortisasi','<=',$tanggalAkhir,'kredit - debet');

        $asetLancar = $kasBank + $piutang + $persediaan;
        $totalAset = $asetLancar + ($asetTetap - $akumulasiPenyusutan);

        $hutangLancar = $this->saldoKategoriMultiple(['akun hutang','kewajiban jangka pendek'],'<=',$tanggalAkhir,'kredit - debet');
        $totalHutang = $this->saldoKategoriMultiple(['akun hutang','kewajiban jangka pendek','kewajiban jangka panjang'],'<=',$tanggalAkhir,'kredit - debet');
        $modal = $this->saldoKategori('ekuitas','<=',$tanggalAkhir,'kredit - debet');

        $pendapatanTotal = $pendapatan['subtotal'];
        $hppTotal = $cogs['subtotal'];
        $labaKotor = $grossProfit;
        $labaBersih = $netProfit;

        // Rasio Posisi
        $rasioPosisi = [
            ['nama'=>'Current Ratio','nilai'=>$cur=$hutangLancar>0?$asetLancar/$hutangLancar:0,'status'=>$this->evaluateRasio('current',$cur)],
            ['nama'=>'Quick Ratio','nilai'=>$quick=$hutangLancar>0?($asetLancar-$persediaan)/$hutangLancar:0,'status'=>$this->evaluateRasio('quick',$quick)],
            ['nama'=>'Cash Ratio','nilai'=>$cash=$hutangLancar>0?$kasBank/$hutangLancar:0,'status'=>$this->evaluateRasio('cash',$cash)],
            ['nama'=>'Debt to Equity','nilai'=>$de=$modal>0?$totalHutang/$modal:0,'status'=>$this->evaluateRasio('debt_equity',$de)],
            ['nama'=>'Debt to Asset','nilai'=>$da=$totalAset>0?$totalHutang/$totalAset:0,'status'=>$this->evaluateRasio('debt_asset',$da)],
        ];

        // Rasio Kinerja
        $rasioKinerja = [
            ['nama'=>'Gross Profit Margin','nilai'=>$gm=$pendapatanTotal>0?$labaKotor/$pendapatanTotal:0,'status'=>$this->evaluateRasio('gross_margin',$gm)],
            ['nama'=>'Net Profit Margin','nilai'=>$nm=$pendapatanTotal>0?$labaBersih/$pendapatanTotal:0,'status'=>$this->evaluateRasio('net_margin',$nm)],
            ['nama'=>'ROA','nilai'=>$roa=$totalAset>0?$labaBersih/$totalAset:0,'status'=>$this->evaluateRasio('roa',$roa)],
            ['nama'=>'ROE','nilai'=>$roe=$modal>0?$labaBersih/$modal:0,'status'=>$this->evaluateRasio('roe',$roe)],
            ['nama'=>'Perputaran Piutang','nilai'=>$piutang>0?$pendapatanTotal/$piutang:0,'status'=>''],
            ['nama'=>'Perputaran Persediaan','nilai'=>$persediaan>0?$hppTotal/$persediaan:0,'status'=>''],
        ];

        return compact(
            'tanggalAwal','tanggalAkhir','company',
            'rasioPosisi','rasioKinerja',
            'totalAset','totalHutang','modal','labaKotor','labaBersih'
        );
    }

    private function evaluateRasio(string $key, float $value): string
    {
        switch ($key) {
            case 'current': return $value>=1.5?'✅ Sehat':($value>=1?'⚠ Cukup':'❌ Kurang Likuid');
            case 'quick': return $value>=1?'✅ Aman':'⚠ Risiko Tinggi';
            case 'cash': return $value>=0.5?'✅ Likuid':'⚠ Kas Tipis';
            case 'debt_equity': return $value<2?'✅ Sehat':($value<=3?'⚠ Tinggi':'❌ Berisiko');
            case 'debt_asset': return $value<0.5?'✅ Aman':($value<=0.7?'⚠ Tinggi':'❌ Terlalu Tinggi');
            case 'gross_margin': return $value>0.5?'✅ Sangat Baik':($value>=0.3?'⚠ Normal':'❌ Rendah');
            case 'net_margin': return $value>0.2?'✅ Bagus':($value>=0.1?'⚠ Cukup':'❌ Rendah');
            case 'roa': return $value>0.05?'✅ Baik':'⚠ Rendah';
            case 'roe': return $value>0.1?'✅ Bagus':'⚠ Rendah';
            default: return '';
        }
    }

    private function saldoKategori($kategori,$operator,$tanggal,$rumus)
    {
        return DB::table('jurnals')
        ->join('akuns','jurnals.id_akun','=','akuns.akun_code')
        ->where('akuns.category',$kategori)
        ->whereNull('jurnals.deleted_at')
        ->when($operator==='<=',fn($q)=>$q->where('jurnals.date','<=',$tanggal))
        ->selectRaw("SUM($rumus) as total")
        ->value('total') ?? 0;
    }

    private function saldoKategoriMultiple(array $kategori,$operator,$tanggal,$rumus)
    {
        return DB::table('jurnals')
        ->join('akuns','jurnals.id_akun','=','akuns.akun_code')
        ->whereIn('akuns.category',$kategori)
        ->whereNull('jurnals.deleted_at')
        ->when($operator==='<=',fn($q)=>$q->where('jurnals.date','<=',$tanggal))
        ->selectRaw("SUM($rumus) as total")
        ->value('total') ?? 0;
    }
}
