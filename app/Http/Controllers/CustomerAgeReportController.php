<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Exports\CustomerAgeReportExport;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Report umur customer (Marketing).
 *
 * Umur = billing_start sampai hari ini (customer aktif) atau sampai
 * deleted_at (customer yang sudah dihapus). Mencakup customer yang
 * dihapus (soft delete), dikelompokkan menurut deletion_type.
 */
class CustomerAgeReportController extends Controller
{
    // Kelompok umur untuk tabel ringkasan: [label, min hari, max hari (null = tak terbatas)]
    const AGE_BUCKETS = [
        ['0 - 30 hari',    0,   30],
        ['31 - 60 hari',   31,  60],
        ['61 - 90 hari',   61,  90],
        ['91 - 180 hari',  91,  180],
        ['181 - 365 hari', 181, 365],
        ['> 365 hari',     366, null],
    ];

    const DELETED_CATEGORIES = [
        'del_terminate' => 'Dihapus - Berhenti',
        'del_cancel'    => 'Dihapus - Tidak Jadi',
        'del_other'     => 'Dihapus - Tanpa Keterangan',
    ];

    const STATUS_COMPANY_PROPERTY = 5;

    const AGE_SQL = 'DATEDIFF(COALESCE(DATE(customers.deleted_at), CURDATE()), customers.billing_start)';

    public function __construct()
    {
        $this->middleware('auth');
        // Sama dengan role yang melihat menu Marketing
        $this->middleware('checkPrivilege:admin,accounting,marketing,payment');
    }

    public function index(Request $request)
    {
        $statuses = \App\Statuscustomer::pluck('name', 'id');
        $plans = \App\Plan::pluck('name', 'id');
        $merchants = \App\Merchant::pluck('name', 'id');
        $sales = \App\Sale::orderBy('name')->pluck('name', 'id');
        $tags = $this->hasTagTables() ? \App\CustomerTag::pluck('name', 'id') : collect();

        $tab = $request->input('tab') === 'movement' ? 'movement' : 'age';
        $data = [
            'tab'                => $tab,
            'statuses'           => $statuses,
            'plans'              => $plans,
            'merchants'          => $merchants,
            'sales'              => $sales,
            'tags'               => $tags,
            'deletedCategories'  => self::DELETED_CATEGORIES,
            'filters'            => $this->cleanFilters($request),
        ];

        if ($tab === 'movement') {
            // Default 6 bulan terakhir (termasuk bulan ini)
            $from = $this->dateOrNull($request->input('period_from')) ?? now()->subMonths(5)->startOfMonth()->toDateString();
            $to = $this->dateOrNull($request->input('period_to')) ?? now()->toDateString();
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
            $data['periodFrom'] = $from;
            $data['periodTo'] = $to;
            $data['movement'] = $this->buildMovement($request, $from, $to);
        } else {
            $data['summary'] = $this->buildSummary($request, $statuses);
            $data['pie'] = $this->buildPieData($request, $statuses);
        }

        return view('marketing.customer-age', $data);
    }

    public function data(Request $request)
    {
        $query = $this->listQuery($request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('customer_id', function ($row) {
                return '<a href="' . url('customer/' . $row->id) . '" class="badge badge-secondary">' . e($row->customer_id) . '</a>';
            })
            ->editColumn('name', function ($row) {
                return e($row->name);
            })
            ->addColumn('plan', function ($row) {
                return $row->plan_name ? e($row->plan_name->name) : '-';
            })
            ->addColumn('merchant', function ($row) {
                return $row->merchant_name ? e($row->merchant_name->name) : '-';
            })
            ->addColumn('sale', function ($row) {
                return $row->sale_name ? e($row->sale_name->name) : '-';
            })
            ->addColumn('status', function ($row) {
                $statusName = $row->status_name ? $row->status_name->name : '-';
                if ($row->deleted_at === null) {
                    return '<span class="badge ' . $this->statusBadge($statusName) . '">' . e($statusName) . '</span>';
                }
                $category = $this->deletedCategory($row->deletion_type ?? null);
                $badge = $category === 'del_terminate' ? 'badge-danger'
                    : ($category === 'del_cancel' ? 'badge-warning' : 'badge-dark');
                return '<span class="badge ' . $badge . '">' . e(self::DELETED_CATEGORIES[$category]) . '</span>'
                    . '<br><small class="text-muted">' . e($statusName) . '</small>';
            })
            ->editColumn('billing_start', function ($row) {
                return $row->billing_start ?: '<span class="text-muted">Belum billing</span>';
            })
            ->editColumn('age_days', function ($row) {
                return $row->age_days === null ? '-' : number_format($row->age_days) . ' hari';
            })
            ->editColumn('deleted_at', function ($row) {
                return $row->deleted_at ? $row->deleted_at->format('Y-m-d') : '-';
            })
            ->addColumn('deletion_reason', function ($row) {
                return !empty($row->deletion_reason) ? '<small>' . e($row->deletion_reason) . '</small>' : '-';
            })
            ->editColumn('unpaid_count', function ($row) {
                $count = (int) $row->unpaid_count;
                return $count > 0 ? '<span class="badge badge-danger">' . $count . '</span>' : '0';
            })
            ->editColumn('unpaid_total', function ($row) {
                return 'Rp ' . number_format((float) $row->unpaid_total, 0, ',', '.');
            })
            ->orderColumn('billing_start', function ($query, $order) {
                // Tie-break id agar urutan stabil antar halaman (banyak tanggal sama)
                $query->orderBy('customers.billing_start', $order)->orderBy('customers.id', $order);
            })
            ->orderColumn('age_days', function ($query, $order) {
                $query->orderBy('age_days', $order);
            })
            ->orderColumn('unpaid_count', function ($query, $order) {
                $query->orderBy('unpaid_count', $order);
            })
            ->orderColumn('unpaid_total', function ($query, $order) {
                $query->orderBy('unpaid_total', $order);
            })
            ->rawColumns(['customer_id', 'status', 'billing_start', 'deletion_reason', 'unpaid_count'])
            ->make(true);
    }

    public function export(Request $request)
    {
        $rows = $this->listQuery($request)
            ->orderBy('customers.billing_start', 'desc')
            ->orderBy('customers.id', 'desc')
            ->get();

        return Excel::download(
            new CustomerAgeReportExport($rows, self::DELETED_CATEGORIES, function ($type) {
                return $this->deletedCategory($type);
            }),
            'Customer_Report_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Query daftar customer: semua filter termasuk filter umur.
     */
    private function listQuery(Request $request)
    {
        $hasDeletionColumns = $this->hasDeletionColumns();

        $columns = [
            'customers.id', 'customers.customer_id', 'customers.name', 'customers.id_plan',
            'customers.id_merchant', 'customers.id_sale', 'customers.id_status', 'customers.billing_start', 'customers.deleted_at',
        ];
        if ($hasDeletionColumns) {
            $columns[] = 'customers.deletion_type';
            $columns[] = 'customers.deletion_reason';
        }

        $query = $this->baseQuery($request)
            ->select($columns)
            ->selectRaw(self::AGE_SQL . ' as age_days')
            ->selectRaw('COALESCE(unpaid.unpaid_count, 0) as unpaid_count')
            ->selectRaw('COALESCE(unpaid.unpaid_total, 0) as unpaid_total')
            ->with(['plan_name', 'status_name', 'merchant_name', 'sale_name']);

        return $this->applyAgeFilter($query, $request);
    }

    private function applyAgeFilter($query, Request $request)
    {
        $ageMin = $this->intOrNull($request->input('age_min'));
        $ageMax = $this->intOrNull($request->input('age_max'));
        if ($ageMin !== null || $ageMax !== null) {
            $query->whereNotNull('customers.billing_start');
            if ($ageMin !== null) {
                $query->whereRaw(self::AGE_SQL . ' >= ?', [$ageMin]);
            }
            if ($ageMax !== null) {
                $query->whereRaw(self::AGE_SQL . ' <= ?', [$ageMax]);
            }
        }

        return $query;
    }

    /**
     * Data 2 pie chart, mengikuti semua filter TERMASUK umur:
     * status customer yang belum dihapus, dan kategori customer yang dihapus.
     */
    private function buildPieData(Request $request, $statuses)
    {
        $rows = $this->applyAgeFilter($this->baseQuery($request), $request)
            ->selectRaw($this->categorySql() . ' as category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('category')
            ->toBase()
            ->pluck('total', 'category');

        // Potensial (id 1, lead) tidak termasuk komposisi status pelanggan
        $status = [];
        foreach ($statuses->except(1) as $id => $name) {
            $status[$name] = (int) ($rows['st_' . $id] ?? 0);
        }
        $deleted = [];
        foreach (self::DELETED_CATEGORIES as $key => $label) {
            $deleted[str_replace('Dihapus - ', '', $label)] = (int) ($rows[$key] ?? 0);
        }

        return ['status' => $status, 'deleted' => $deleted];
    }

    private function categorySql(): string
    {
        return $this->hasDeletionColumns()
            ? "CASE WHEN customers.deleted_at IS NULL THEN CONCAT('st_', COALESCE(customers.id_status, 0))
                    WHEN customers.deletion_type IN ('terminate','berhenti_berlangganan') THEN 'del_terminate'
                    WHEN customers.deletion_type IN ('cancel','tidak_jadi_berlangganan') THEN 'del_cancel'
                    ELSE 'del_other' END"
            : "CASE WHEN customers.deleted_at IS NULL THEN CONCAT('st_', COALESCE(customers.id_status, 0))
                    ELSE 'del_other' END";
    }

    /**
     * Query dasar dengan semua filter KECUALI umur (dipakai juga oleh ringkasan).
     * Umur (age_min/age_max) kini hanya datang dari klik angka di tabel ringkasan.
     */
    private function baseQuery(Request $request)
    {
        // Agregasi sekali per customer; suminvoices tidak punya index id_customer,
        // jadi hindari subquery berkorelasi per baris.
        $unpaid = DB::table('suminvoices')
            ->select('id_customer')
            ->selectRaw('COUNT(*) as unpaid_count')
            ->selectRaw('SUM(total_amount) as unpaid_total')
            ->where('payment_status', 0)
            ->whereNull('deleted_at')
            ->groupBy('id_customer');

        $query = Customer::withTrashed()
            ->leftJoinSub($unpaid, 'unpaid', 'unpaid.id_customer', '=', 'customers.id');

        $dateFrom = $this->dateOrNull($request->input('date_from'));
        $dateTo = $this->dateOrNull($request->input('date_to'));
        if ($dateFrom) {
            $query->where('customers.billing_start', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('customers.billing_start', '<=', $dateTo);
        }

        $scope = $request->input('scope', 'all');
        if ($scope === 'active') {
            $query->whereNull('customers.deleted_at');
        } elseif ($scope === 'deleted') {
            $query->whereNotNull('customers.deleted_at');
        }

        $status = (string) $request->input('status', '');
        if ($status !== '') {
            if (ctype_digit($status)) {
                // Status biasa = customer yang belum dihapus dengan status tsb
                $query->whereNull('customers.deleted_at')->where('customers.id_status', (int) $status);
            } elseif (isset(self::DELETED_CATEGORIES[$status])) {
                $query->whereNotNull('customers.deleted_at');
                $this->applyDeletedCategory($query, $status);
            }
        }

        switch ((string) $request->input('unpaid', '')) {
            case '0':
                $query->whereRaw('COALESCE(unpaid.unpaid_count, 0) = 0');
                break;
            case '1':
            case '2':
                $query->whereRaw('COALESCE(unpaid.unpaid_count, 0) = ?', [(int) $request->input('unpaid')]);
                break;
            case '3plus':
                $query->whereRaw('COALESCE(unpaid.unpaid_count, 0) >= 3');
                break;
        }

        return $this->applyCommonFilters($query, $request);
    }

    /**
     * Filter Plan / Merchant / Sales / Tag, dipakai tab umur dan tab pergerakan.
     */
    private function applyCommonFilters($query, Request $request)
    {
        if ($request->filled('id_plan')) {
            $query->where('customers.id_plan', $request->input('id_plan'));
        }
        if ($request->filled('id_merchant')) {
            $query->where('customers.id_merchant', $request->input('id_merchant'));
        }
        if ($request->filled('id_sale')) {
            $query->where('customers.id_sale', $request->input('id_sale'));
        }
        if ($request->filled('id_tag') && $this->hasTagTables()) {
            foreach ((array) $request->input('id_tag') as $tagId) {
                $query->whereHas('tags', function ($q) use ($tagId) {
                    $q->whereKey($tagId);
                });
            }
        }

        return $query;
    }

    /**
     * Pergerakan customer per periode:
     * - Baru      : billing_start dalam periode, kecuali Potensial (belum dihapus,
     *               id_status 1) dan yang dihapus "tidak jadi" (tak pernah jadi pelanggan)
     * - Berhenti / Tidak Jadi / Tanpa Keterangan : deleted_at dalam periode
     * Company_Properti (fasilitas, bukan pelanggan berbayar) tidak dihitung sama sekali.
     * Periode <= 31 hari dikelompokkan harian, lebih dari itu bulanan.
     */
    private function buildMovement(Request $request, string $from, string $to)
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();
        $daily = $start->diffInDays($end) < 31;

        $keySql = function ($column) use ($daily) {
            return $daily ? "DATE_FORMAT($column, '%Y-%m-%d')" : "DATE_FORMAT($column, '%Y-%m')";
        };

        $new = $this->excludeCompanyProperty($this->applyCommonFilters(Customer::withTrashed(), $request))
            ->whereBetween('customers.billing_start', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) {
                $q->whereNotNull('customers.deleted_at')->orWhere('customers.id_status', '!=', 1);
            });
        if ($this->hasDeletionColumns()) {
            $new->where(function ($q) {
                $q->whereNull('customers.deletion_type')
                    ->orWhereNotIn('customers.deletion_type', ['cancel', 'tidak_jadi_berlangganan']);
            });
        }
        $newRows = $new->selectRaw($keySql('customers.billing_start') . ' as period')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('period')
            ->toBase()
            ->pluck('total', 'period');

        $deletedRows = $this->excludeCompanyProperty($this->applyCommonFilters(Customer::onlyTrashed(), $request))
            ->whereBetween('customers.deleted_at', [$start, $end])
            ->selectRaw($keySql('customers.deleted_at') . ' as period')
            ->selectRaw($this->categorySql() . ' as category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('period', 'category')
            ->toBase()
            ->get();

        // Semua titik periode, termasuk yang nol
        $periods = [];
        $cursor = $daily ? $start->copy() : $start->copy()->startOfMonth();
        while ($cursor <= $end) {
            $key = $cursor->format($daily ? 'Y-m-d' : 'Y-m');
            $periods[$key] = $daily
                ? $cursor->locale('id')->translatedFormat('d M')
                : $cursor->locale('id')->translatedFormat('M Y');
            $daily ? $cursor->addDay() : $cursor->addMonthNoOverflow();
        }

        $series = ['new' => [], 'del_terminate' => [], 'del_cancel' => [], 'del_other' => []];
        foreach ($periods as $key => $label) {
            $series['new'][$key] = (int) ($newRows[$key] ?? 0);
            $series['del_terminate'][$key] = 0;
            $series['del_cancel'][$key] = 0;
            $series['del_other'][$key] = 0;
        }
        foreach ($deletedRows as $row) {
            if (isset($series[$row->category][$row->period])) {
                $series[$row->category][$row->period] += $row->total;
            }
        }

        $totals = array_map('array_sum', $series);

        return [
            'granularity' => $daily ? 'harian' : 'bulanan',
            'periods'     => $periods,
            'series'      => $series,
            'totals'      => $totals,
            // Net: baru dikurangi yang berhenti (termasuk tanpa keterangan);
            // "tidak jadi" tidak dihitung karena tidak termasuk customer baru
            'net'         => $totals['new'] - $totals['del_terminate'] - $totals['del_other'],
        ];
    }

    /**
     * Matriks jumlah customer per kelompok umur x status.
     */
    private function buildSummary(Request $request, $statuses)
    {
        $bucketCase = 'CASE WHEN customers.billing_start IS NULL THEN \'nobilling\'';
        foreach (self::AGE_BUCKETS as $i => [$label, $min, $max]) {
            $bucketCase .= $max === null
                ? ' WHEN ' . self::AGE_SQL . ' >= ' . (int) $min . ' THEN \'b' . $i . '\''
                : ' WHEN ' . self::AGE_SQL . ' <= ' . (int) $max . ' THEN \'b' . $i . '\'';
        }
        $bucketCase .= ' ELSE \'b0\' END';

        $rows = $this->baseQuery($request)
            ->selectRaw($bucketCase . ' as bucket')
            ->selectRaw($this->categorySql() . ' as category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('bucket', 'category')
            ->toBase()
            ->get();

        $columns = [];
        foreach ($statuses as $id => $name) {
            $columns['st_' . $id] = $name;
        }
        $columns += self::DELETED_CATEGORIES;

        $bucketLabels = [];
        foreach (self::AGE_BUCKETS as $i => $bucket) {
            $bucketLabels['b' . $i] = $bucket[0];
        }
        $bucketLabels['nobilling'] = 'Belum billing';

        $matrix = [];
        foreach ($bucketLabels as $key => $label) {
            $matrix[$key] = array_fill_keys(array_keys($columns), 0);
        }
        foreach ($rows as $row) {
            if (!isset($columns[$row->category])) {
                $columns[$row->category] = 'Status #' . substr($row->category, 3);
                foreach ($matrix as $key => $cells) {
                    $matrix[$key][$row->category] = 0;
                }
            }
            $matrix[$row->bucket][$row->category] += $row->total;
        }

        return [
            'columns' => $columns,
            'buckets' => $bucketLabels,
            'matrix'  => $matrix,
        ];
    }

    private function excludeCompanyProperty($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('customers.id_status')->orWhere('customers.id_status', '!=', self::STATUS_COMPANY_PROPERTY);
        });
    }

    private function applyDeletedCategory($query, string $category)
    {
        if (!$this->hasDeletionColumns()) {
            // Tanpa kolom deletion_type semua customer terhapus = tanpa keterangan
            if ($category !== 'del_other') {
                $query->whereRaw('1 = 0');
            }
            return;
        }

        if ($category === 'del_terminate') {
            $query->whereIn('customers.deletion_type', ['terminate', 'berhenti_berlangganan']);
        } elseif ($category === 'del_cancel') {
            $query->whereIn('customers.deletion_type', ['cancel', 'tidak_jadi_berlangganan']);
        } else {
            $query->where(function ($q) {
                $q->whereNull('customers.deletion_type')
                    ->orWhereNotIn('customers.deletion_type', ['terminate', 'berhenti_berlangganan', 'cancel', 'tidak_jadi_berlangganan']);
            });
        }
    }

    private function deletedCategory($deletionType): string
    {
        if (in_array($deletionType, ['terminate', 'berhenti_berlangganan'], true)) {
            return 'del_terminate';
        }
        if (in_array($deletionType, ['cancel', 'tidak_jadi_berlangganan'], true)) {
            return 'del_cancel';
        }
        return 'del_other';
    }

    private function statusBadge(string $statusName): string
    {
        switch ($statusName) {
            case 'Active':           return 'badge-success';
            case 'Block':            return 'badge-danger';
            case 'Potensial':        return 'badge-info';
            case 'Company_Properti': return 'badge-primary';
            default:                 return 'badge-secondary';
        }
    }

    private function intOrNull($value)
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }
        return max(0, (int) $value);
    }

    /**
     * Filter untuk view/link: buang tanggal & umur yang tidak valid.
     */
    private function cleanFilters(Request $request): array
    {
        $filters = $request->query();
        foreach (['date_from', 'date_to'] as $key) {
            $filters[$key] = $this->dateOrNull($filters[$key] ?? null) ?? '';
        }
        foreach (['age_min', 'age_max'] as $key) {
            $value = $this->intOrNull($filters[$key] ?? null);
            $filters[$key] = $value === null ? '' : (string) $value;
        }
        return array_filter($filters, function ($v) {
            return $v !== '' && $v !== null;
        });
    }

    private function dateOrNull($value)
    {
        if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return null;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
    }

    private function hasDeletionColumns(): bool
    {
        return Schema::hasColumn('customers', 'deletion_type');
    }

    private function hasTagTables(): bool
    {
        return Schema::hasTable('customer_tag_definitions') && Schema::hasTable('customer_tag_map');
    }
}
