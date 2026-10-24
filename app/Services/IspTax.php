<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Kewajiban pajak & pungutan ISP dari pendapatan (pembayaran invoice diterima).
 * Dipakai bersama oleh dashboard superadmin dan menu ISP di tiap tenant,
 * supaya rumus dan sumber datanya selalu sama.
 */
class IspTax
{
    const PPN_RATE = 0.11;
    const BHP_RATE = 0.005;
    const USO_RATE = 0.0125;

    /**
     * Nilai pembayaran yang diterima ($amount) sudah termasuk PPN.
     * BHP Telekomunikasi & USO dihitung dari DPP (bukan dari total pembayaran).
     */
    public static function compute(float $amount): array
    {
        $dpp = round($amount / (1 + self::PPN_RATE));
        $ppn = round($amount) - $dpp;
        $bhp = round($dpp * self::BHP_RATE);
        $uso = round($dpp * self::USO_RATE);

        return [
            'dpp' => $dpp,
            'ppn' => $ppn,
            'bhp' => $bhp,
            'uso' => $uso,
            'total_kewajiban' => $ppn + $bhp + $uso,
            'ppn_rate' => self::PPN_RATE * 100,
            'bhp_rate' => self::BHP_RATE * 100,
            'uso_rate' => self::USO_RATE * 100,
        ];
    }

    /**
     * Pendapatan dalam rentang tanggal (inklusif), dipecah per bulan kalender:
     * invoice lunas (payment_status = 1) menurut payment_date, jumlah
     * recieve_payment. Bulan pertama/terakhir dipotong sesuai rentang.
     *
     * @return array<int, array{from: Carbon, to: Carbon, revenue: float}>
     */
    public static function revenueByMonth(Carbon $from, Carbon $to, ?string $connection = null): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $sums = DB::connection($connection)->table('suminvoices')
            ->where('payment_status', 1)
            ->whereBetween('payment_date', [$from, $to])
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(recieve_payment) AS total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $out = [];
        for ($cursor = $from->copy()->startOfMonth(); $cursor->lte($to); $cursor->addMonth()) {
            $out[] = [
                'from' => $cursor->lt($from) ? $from->copy() : $cursor->copy(),
                'to' => $cursor->copy()->endOfMonth()->gt($to) ? $to->copy() : $cursor->copy()->endOfMonth(),
                'revenue' => (float) ($sums[$cursor->format('Y-m')] ?? 0),
            ];
        }

        return $out;
    }

    /** Tanggal YYYY-MM-DD yang valid (tanpa overflow seperti 2026-02-31), atau null. */
    public static function parseDate($value): ?Carbon
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);

            return $date->format('Y-m-d') === $value ? $date : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // Basis query pembayaran yang dihitung sebagai pendapatan dalam periode.
    private static function paidInPeriod(Carbon $from, Carbon $to, ?string $connection)
    {
        return DB::connection($connection)->table('suminvoices')
            ->where('suminvoices.payment_status', 1)
            ->whereBetween('suminvoices.payment_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    /**
     * Pendapatan dalam periode per kas/bank tujuan (suminvoices.payment_point
     * = akuns.akun_code), urut dari pendapatan terbesar.
     *
     * @return array<int, array{code: string, name: string, count: int, revenue: float}>
     */
    public static function revenueByKas(Carbon $from, Carbon $to, ?string $connection = null): array
    {
        $sums = self::paidInPeriod($from, $to, $connection)
            ->selectRaw('payment_point AS code, COUNT(*) AS n, SUM(recieve_payment) AS total')
            ->groupBy('payment_point')
            ->orderByDesc('total')
            ->get();

        $names = DB::connection($connection)->table('akuns')
            ->whereIn('akun_code', $sums->pluck('code')->filter()->all())
            ->whereNull('deleted_at')
            ->pluck('name', 'akun_code');

        return $sums->map(function ($r) use ($names) {
            $code = (string) $r->code;

            return [
                'code' => $code,
                'name' => $names[$code] ?? ($code === '' ? 'Tanpa kas' : 'Akun ' . $code),
                'count' => (int) $r->n,
                'revenue' => (float) $r->total,
            ];
        })->all();
    }

    /** Daftar pembayaran dalam periode untuk satu kas (kode akun). */
    public static function kasTransactions(Carbon $from, Carbon $to, string $kasCode, ?string $connection = null): array
    {
        return self::paidInPeriod($from, $to, $connection)
            ->where(function ($q) use ($kasCode) {
                $kasCode === '' ? $q->whereNull('suminvoices.payment_point')->orWhere('suminvoices.payment_point', '') : $q->where('suminvoices.payment_point', $kasCode);
            })
            ->leftJoin('customers', 'customers.id', '=', 'suminvoices.id_customer')
            ->orderBy('suminvoices.payment_date')
            ->get([
                'suminvoices.id', 'suminvoices.number', 'suminvoices.payment_date', 'suminvoices.recieve_payment',
                'suminvoices.id_customer', 'customers.customer_id AS customer_code', 'customers.name AS customer_name',
            ])
            ->all();
    }
}
