<?php

namespace App\Http\Controllers;

use App\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tab "Hasil Konversi" di Lead Summary.
 *
 * Hanya lead yang punya converted_at (dikonversi lewat tombol Convert),
 * termasuk yang sudah dihapus kecuali dihapus "tidak jadi". Status dibaca
 * apa adanya sekarang untuk melihat nasib lead setelah dikonversi.
 * Company_Properti tidak dihitung, hanya dicatat jumlahnya.
 */
class LeadConversionController extends Controller
{
    const STATUS_ACTIVE = 2;
    const STATUS_INACTIVE = 3;
    const STATUS_BLOCK = 4;
    const STATUS_COMPANY_PROPERTY = 5;

    const CANCEL_TYPES = ['cancel', 'tidak_jadi_berlangganan'];
    const TERMINATE_TYPES = ['terminate', 'berhenti_berlangganan'];

    public function __construct()
    {
        $this->middleware('auth');
        // Sama dengan akses Lead Summary (CustomerController tanpa merchant)
        $this->middleware('checkPrivilege:admin,accounting,marketing,payment,noc,user');
    }

    public function index(Request $request)
    {
        $start = $this->validDate($request->input('start_date')) ?? now()->startOfMonth()->toDateString();
        $end = $this->validDate($request->input('end_date')) ?? now()->toDateString();
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }
        $basis = $request->input('basis') === 'billing' ? 'billing' : 'created';
        $filterSale = $request->input('id_sale', '');
        $filterStatus = (string) $request->input('status', '');
        $filterUnpaid = (string) $request->input('unpaid', '');
        $hasDeletionType = Schema::hasColumn('customers', 'deletion_type');
        $hasTags = $this->hasTagTables();

        $query = Customer::withTrashed()
            ->whereNotNull('converted_at')
            ->with(array_merge(['sale_name', 'status_name', 'plan_name', 'merchant_name'], $hasTags ? ['tags'] : []));

        if ($basis === 'billing') {
            $query->whereBetween('billing_start', [$start, $end]);
        } else {
            $query->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
        }
        if ($filterSale) {
            $query->where('id_sale', $filterSale);
        }
        if ($request->filled('id_plan')) {
            $query->where('id_plan', $request->input('id_plan'));
        }
        if ($request->filled('id_merchant')) {
            $query->where('id_merchant', $request->input('id_merchant'));
        }
        if ($hasTags && $request->filled('id_tag')) {
            foreach ((array) $request->input('id_tag') as $tagId) {
                $query->whereHas('tags', function ($q) use ($tagId) {
                    $q->whereKey($tagId);
                });
            }
        }
        if ($hasDeletionType) {
            $query->where(function ($q) {
                $q->whereNull('deletion_type')->orWhereNotIn('deletion_type', self::CANCEL_TYPES);
            });
        }

        $all = $query->orderBy('converted_at', 'desc')->get();

        // Catatan Company_Properti mengikuti semua filter kecuali status
        $companyPropertyCount = $all->where('id_status', self::STATUS_COMPANY_PROPERTY)->count();
        $leads = $all->where('id_status', '!=', self::STATUS_COMPANY_PROPERTY)->values();

        $leads->each(function ($lead) {
            $lead->outcome = $this->outcome($lead);
        });
        if ($filterStatus !== '') {
            // "stopped" = sudah dihapus; angka lain = id_status customer yang belum dihapus
            $leads = $leads->filter(function ($lead) use ($filterStatus) {
                return $filterStatus === 'stopped'
                    ? $lead->outcome === 'stopped'
                    : $lead->outcome !== 'stopped' && (string) $lead->id_status === $filterStatus;
            })->values();
        }

        $invoiceCounts = $this->invoiceCounts($leads->pluck('id')->all());

        $leads->each(function ($lead) use ($invoiceCounts) {
            $lead->invoice_total = (int) ($invoiceCounts[$lead->id]->total ?? 0);
            $lead->invoice_unpaid = (int) ($invoiceCounts[$lead->id]->unpaid ?? 0);
            // converted_at tidak di-cast di model Customer
            $lead->converted_on = Carbon::parse($lead->converted_at);
            $lead->days_to_convert = $lead->created_at
                ? max(0, $lead->created_at->copy()->startOfDay()->diffInDays($lead->converted_on->copy()->startOfDay(), false))
                : null;
        });

        if (in_array($filterUnpaid, ['0', '1', '2', '3plus'], true)) {
            $leads = $leads->filter(function ($lead) use ($filterUnpaid) {
                return $filterUnpaid === '3plus'
                    ? $lead->invoice_unpaid >= 3
                    : $lead->invoice_unpaid === (int) $filterUnpaid;
            })->values();
        }

        $counts = [
            'active'   => $leads->where('outcome', 'active')->count(),
            'inactive' => $leads->where('outcome', 'inactive')->count(),
            'stopped'  => $leads->where('outcome', 'stopped')->count(),
            'other'    => $leads->where('outcome', 'other')->count(),
        ];
        $total = $leads->count();
        // Retensi = belum berhenti (Inactive/Block masih tercatat sebagai pelanggan)
        $retention = $total > 0 ? round(($total - $counts['stopped']) / $total * 100, 1) : null;
        $avgDays = $total > 0 ? round($leads->avg('days_to_convert'), 1) : null;

        $chart = $this->buildChart($leads, $start, $end, $basis);

        return view('marketing.lead-conversion', [
            'chart'                => $chart,
            'start'                => $start,
            'end'                  => $end,
            'basis'                => $basis,
            'filterSale'           => $filterSale,
            'filterStatus'         => $filterStatus,
            'filterUnpaid'         => $filterUnpaid,
            'hasTagTables'         => $hasTags,
            'filters'              => $request->only(['id_plan', 'id_merchant', 'id_tag']),
            'allSales'             => \App\Sale::orderBy('name')->get(),
            'plans'                => \App\Plan::pluck('name', 'id'),
            'merchants'            => \App\Merchant::pluck('name', 'id'),
            'tags'                 => $hasTags ? \App\CustomerTag::pluck('name', 'id') : collect(),
            'statusOptions'        => [
                (string) self::STATUS_ACTIVE   => 'Active',
                (string) self::STATUS_INACTIVE => 'Inactive',
                (string) self::STATUS_BLOCK    => 'Block',
                'stopped'                      => 'Berhenti (dihapus)',
            ],
            'leads'                => $leads,
            'total'                => $total,
            'counts'               => $counts,
            'retention'            => $retention,
            'avgDays'              => $avgDays,
            'companyPropertyCount' => $companyPropertyCount,
        ]);
    }

    /**
     * Jumlah per periode menurut tanggal basis filter (lead masuk / billing start).
     * Range <= 1 bulan (kurang dari 31 hari) harian, lebih dari itu bulanan.
     */
    private function buildChart($leads, string $start, string $end, string $basis): array
    {
        $from = Carbon::parse($start)->startOfDay();
        $to = Carbon::parse($end)->endOfDay();
        $daily = $from->diffInDays($to) < 31;
        $keyFormat = $daily ? 'Y-m-d' : 'Y-m';

        $periods = [];
        $cursor = $daily ? $from->copy() : $from->copy()->startOfMonth();
        while ($cursor <= $to) {
            $periods[$cursor->format($keyFormat)] = $cursor->locale('id')->translatedFormat($daily ? 'd M' : 'M Y');
            $daily ? $cursor->addDay() : $cursor->addMonthNoOverflow();
        }

        $series = [];
        foreach (['total', 'inactive', 'stopped'] as $name) {
            $series[$name] = array_fill_keys(array_keys($periods), 0);
        }
        foreach ($leads as $lead) {
            $date = $basis === 'billing' ? $lead->billing_start : $lead->created_at;
            if (!$date) {
                continue;
            }
            $key = Carbon::parse($date)->format($keyFormat);
            if (!isset($series['total'][$key])) {
                continue;
            }
            $series['total'][$key]++;
            if (isset($series[$lead->outcome])) {
                $series[$lead->outcome][$key]++;
            }
        }

        return [
            'granularity' => $daily ? 'harian' : 'bulanan',
            'periods'     => $periods,
            'series'      => $series,
        ];
    }

    /**
     * Nasib lead setelah dikonversi, berdasarkan kondisi sekarang.
     */
    private function outcome($lead): string
    {
        if ($lead->deleted_at !== null) {
            return 'stopped';
        }
        if ((int) $lead->id_status === self::STATUS_ACTIVE) {
            return 'active';
        }
        if (in_array((int) $lead->id_status, [self::STATUS_INACTIVE, self::STATUS_BLOCK], true)) {
            return 'inactive';
        }
        return 'other';
    }

    /**
     * Jumlah invoice per customer (tanpa yang cancel) dan yang belum dibayar.
     */
    private function invoiceCounts(array $customerIds)
    {
        if (empty($customerIds)) {
            return collect();
        }

        return DB::table('suminvoices')
            ->select('id_customer')
            ->selectRaw('SUM(payment_status <> 2) as total')
            ->selectRaw('SUM(payment_status = 0) as unpaid')
            ->whereIn('id_customer', $customerIds)
            ->whereNull('deleted_at')
            ->groupBy('id_customer')
            ->get()
            ->keyBy('id_customer');
    }

    private function hasTagTables(): bool
    {
        return Schema::hasTable('customer_tag_definitions') && Schema::hasTable('customer_tag_map');
    }

    private function validDate($value)
    {
        if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return null;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
    }
}
