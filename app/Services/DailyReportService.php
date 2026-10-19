<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun data laporan harian satu tenant untuk satu tanggal (koneksi DB tenant harus sudah aktif).
 *
 * Sumber "hari itu":
 *  - Pembayaran     : suminvoices.payment_date, payment_status = 1
 *  - Invoice terbit : suminvoices.created_at
 *  - Invoice batal  : payment_status = 2 dan updated_at hari itu (tidak ada kolom tanggal batal tersendiri)
 *  - Langganan baru : customers.converted_at (Potensial → Active), atau dibuat hari itu langsung non-Potensial
 *  - Terminate/isolir: customerlogs.updates JSON {"Status":{"old":..,"new":..}}
 * Angka "saat ini" (total aktif/blokir/potensial, tagihan belum lunas) = kondisi saat laporan dibuat.
 */
class DailyReportService
{
    public function build(Carbon $date): array
    {
        $start = (clone $date)->startOfDay();
        $end   = (clone $date)->endOfDay();

        $payments  = $this->payments($start, $end);
        $issued    = $this->issuedInvoices($start, $end);
        $cancelled = $this->cancelledInvoices($start, $end);

        $newSubs    = $this->newSubscriptions($start, $end);
        $terminated = $this->statusChanges($start, $end, ['Inactive']);
        $isolated   = $this->statusChanges($start, $end, ['Blocked', 'Block']);
        $newLeads   = $this->newPotentials($start, $end);

        $statusCount = DB::table('customers')->whereNull('deleted_at')
            ->select('id_status', DB::raw('COUNT(*) AS n'))->groupBy('id_status')->pluck('n', 'id_status');

        $unpaid = DB::table('suminvoices')->whereNull('deleted_at')->where('payment_status', 0)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(total_amount), 0) AS total')->first();

        return [
            'date' => $date,
            'summary' => [
                'payments_count'   => $payments->count(),
                'payments_total'   => (int) $payments->sum('amount'),
                'issued_count'     => $issued->count(),
                'issued_total'     => (int) $issued->sum('total_amount'),
                'cancelled_count'  => $cancelled->count(),
                'cancelled_total'  => (int) $cancelled->sum('total_amount'),
                'unpaid_count'     => (int) $unpaid->n,
                'unpaid_total'     => (int) $unpaid->total,
                'new_subs'         => $newSubs->count(),
                'terminated'       => $terminated->count(),
                'isolated'         => $isolated->count(),
                'new_leads'        => $newLeads->count(),
                'active_now'       => (int) ($statusCount[2] ?? 0),
                'blocked_now'      => (int) ($statusCount[4] ?? 0),
                'potential_now'    => (int) ($statusCount[1] ?? 0),
                'inactive_now'     => (int) ($statusCount[3] ?? 0),
            ],
            'payments_by_account' => $payments->groupBy('account')
                ->map(fn ($rows, $account) => ['name' => $account, 'count' => $rows->count(), 'total' => (int) $rows->sum('amount')])
                ->sortByDesc('total')->values(),
            'payments_by_method' => $payments->groupBy('method')
                ->map(fn ($rows, $method) => ['name' => $method, 'count' => $rows->count(), 'total' => (int) $rows->sum('amount')])
                ->sortByDesc('total')->values(),
            'payments'   => $payments,
            'issued'     => $issued,
            'cancelled'  => $cancelled,
            'new_subs'   => $newSubs,
            'terminated' => $terminated,
            'isolated'   => $isolated,
            'new_leads'  => $newLeads,
        ];
    }

    private function payments(Carbon $start, Carbon $end): Collection
    {
        return DB::table('suminvoices AS s')
            ->leftJoin('customers AS c', 'c.id', '=', 's.id_customer')
            ->leftJoin('akuns AS a', function ($j) {
                $j->on('a.akun_code', '=', 's.payment_point')->whereNull('a.deleted_at');
            })
            ->whereNull('s.deleted_at')
            ->where('s.payment_status', 1)
            ->whereBetween('s.payment_date', [$start, $end])
            ->orderBy('s.payment_date')
            ->get([
                's.payment_date', 's.number', 'c.customer_id', 'c.name', 's.total_amount',
                DB::raw('COALESCE(NULLIF(s.recieve_payment, 0), s.total_amount) AS amount'),
                DB::raw("COALESCE(CONCAT(s.payment_point, ' - ', a.name), NULLIF(s.payment_point, ''), '(tanpa akun)') AS account"),
                DB::raw("COALESCE(NULLIF(UPPER(s.payment_gateway), ''), 'MANUAL / KASIR') AS method"),
                's.updated_by',
            ]);
    }

    private function issuedInvoices(Carbon $start, Carbon $end): Collection
    {
        return DB::table('suminvoices AS s')
            ->leftJoin('customers AS c', 'c.id', '=', 's.id_customer')
            ->whereNull('s.deleted_at')
            ->whereBetween('s.created_at', [$start, $end])
            ->orderBy('s.created_at')
            ->get(['s.created_at', 's.number', 'c.customer_id', 'c.name', 's.date', 's.due_date', 's.total_amount', 's.payment_status', 's.created_by']);
    }

    private function cancelledInvoices(Carbon $start, Carbon $end): Collection
    {
        return DB::table('suminvoices AS s')
            ->leftJoin('customers AS c', 'c.id', '=', 's.id_customer')
            ->whereNull('s.deleted_at')
            ->where('s.payment_status', 2)
            ->whereBetween('s.updated_at', [$start, $end])
            ->orderBy('s.updated_at')
            ->get(['s.updated_at', 's.number', 'c.customer_id', 'c.name', 's.date', 's.total_amount', 's.updated_by', 's.note']);
    }

    private function newSubscriptions(Carbon $start, Carbon $end): Collection
    {
        return DB::table('customers AS c')
            ->leftJoin('plans AS p', 'p.id', '=', 'c.id_plan')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('c.converted_at', [$start, $end])
                  ->orWhere(function ($q) use ($start, $end) {
                      $q->whereNull('c.converted_at')->whereBetween('c.created_at', [$start, $end])->where('c.id_status', '!=', 1);
                  });
            })
            ->orderBy('c.customer_id')
            ->get(['c.customer_id', 'c.name', 'p.name AS plan', 'c.address', 'c.phone', DB::raw('COALESCE(c.converted_at, c.created_at) AS since')]);
    }

    private function newPotentials(Carbon $start, Carbon $end): Collection
    {
        return DB::table('customers AS c')
            ->whereNull('c.deleted_at')
            ->where('c.id_status', 1)
            ->whereBetween('c.created_at', [$start, $end])
            ->orderBy('c.created_at')
            ->get(['c.customer_id', 'c.name', 'c.address', 'c.phone', 'c.created_at', 'c.lead_source']);
    }

    /** Pelanggan yang statusnya berubah menjadi salah satu $newStatuses pada tanggal itu (satu baris per pelanggan). */
    private function statusChanges(Carbon $start, Carbon $end, array $newStatuses): Collection
    {
        $placeholders = implode(',', array_fill(0, count($newStatuses), '?'));

        return DB::table('customerlogs AS l')
            ->join('customers AS c', 'c.id', '=', 'l.id_customer')
            ->whereNull('l.deleted_at')
            ->whereBetween('l.created_at', [$start, $end])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(l.updates, '$.Status.new')) IN ({$placeholders})", $newStatuses)
            ->groupBy('l.id_customer')
            ->orderBy('c.customer_id')
            ->get([
                'c.customer_id', 'c.name', 'c.address',
                DB::raw("MAX(JSON_UNQUOTE(JSON_EXTRACT(l.updates, '$.Status.old'))) AS old_status"),
                DB::raw('MAX(l.created_at) AS changed_at'),
                DB::raw('MAX(l.updated_by) AS changed_by'),
            ]);
    }
}
