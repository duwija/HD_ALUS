<?php

namespace App\Http\Controllers;

use App\Services\IspTax;
use Carbon\Carbon;
use Illuminate\Http\Request;

// Menu ISP > Pajak & Kewajiban: rincian DPP, PPN, BHP Tel, USO untuk periode
// tanggal yang dipilih (dipecah per bulan) pada tenant yang sedang login —
// rumus & sumber data sama dengan dashboard superadmin.
class IspTaxController extends Controller
{
    const MAX_MONTHS = 36;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkPrivilege:admin,accounting');
    }

    public function index(Request $request)
    {
        $today = Carbon::today();
        [$from, $to, $limited] = $this->period($request);

        $rows = [];
        $totals = ['revenue' => 0, 'tax' => IspTax::compute(0)];
        foreach (IspTax::revenueByMonth($from, $to) as $period) {
            $tax = IspTax::compute($period['revenue']);
            $rows[] = $period + ['tax' => $tax];

            $totals['revenue'] += $period['revenue'];
            foreach (['dpp', 'ppn', 'bhp', 'uso', 'total_kewajiban'] as $k) {
                $totals['tax'][$k] += $tax[$k];
            }
        }

        // Rincian per kas/bank tujuan pembayaran untuk seluruh periode.
        $kasRows = [];
        $kasTotals = ['count' => 0, 'revenue' => 0, 'tax' => IspTax::compute(0)];
        foreach (IspTax::revenueByKas($from, $to) as $kas) {
            $tax = IspTax::compute($kas['revenue']);
            $kasRows[] = $kas + ['tax' => $tax];

            $kasTotals['count'] += $kas['count'];
            $kasTotals['revenue'] += $kas['revenue'];
            foreach (['dpp', 'ppn', 'bhp', 'uso', 'total_kewajiban'] as $k) {
                $kasTotals['tax'][$k] += $tax[$k];
            }
        }

        return view('isp.pajak', [
            'from' => $from,
            'to' => $to,
            'limited' => $limited,
            'rows' => $rows,
            'totals' => $totals,
            'kasRows' => $kasRows,
            'kasTotals' => $kasTotals,
            'presets' => [
                'Bulan ini' => [$today->copy()->startOfMonth(), $today->copy()],
                'Bulan lalu' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
                'Tahun ini' => [$today->copy()->startOfYear(), $today->copy()],
                'Tahun lalu' => [$today->copy()->subYear()->startOfYear(), $today->copy()->subYear()->endOfYear()],
            ],
        ]);
    }

    // AJAX: daftar pembayaran satu kas dalam periode (dipakai tombol "Lihat transaksi").
    public function kasTransactions(Request $request)
    {
        [$from, $to] = $this->period($request);
        $kas = (string) $request->query('kas', '');

        $data = array_map(function ($t) {
            return [
                'id' => $t->id,
                'number' => $t->number,
                'payment_date' => Carbon::parse($t->payment_date)->format('Y-m-d H:i'),
                'amount' => (float) $t->recieve_payment,
                'customer_id' => $t->id_customer,
                'customer_code' => $t->customer_code,
                'customer_name' => $t->customer_name,
            ];
        }, IspTax::kasTransactions($from, $to, $kas));

        return response()->json(['data' => $data]);
    }

    /**
     * Periode dari ?from=YYYY-MM-DD&to=YYYY-MM-DD. Default awal bulan berjalan
     * s/d hari ini; tanggal terbalik ditukar; maksimal MAX_MONTHS bulan.
     *
     * @return array{0: Carbon, 1: Carbon, 2: bool} [from, to, limited]
     */
    private function period(Request $request): array
    {
        $today = Carbon::today();
        $from = IspTax::parseDate($request->query('from')) ?? $today->copy()->startOfMonth();
        $to = IspTax::parseDate($request->query('to')) ?? $today->copy();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        $limited = false;
        if ($from->copy()->addMonths(self::MAX_MONTHS)->lte($to)) {
            $from = $to->copy()->subMonths(self::MAX_MONTHS)->addDay();
            $limited = true;
        }

        return [$from, $to, $limited];
    }
}
