<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerAgeReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Collection $rows;
    protected array $deletedCategories;
    protected $categoryResolver;

    public function __construct(Collection $rows, array $deletedCategories, callable $categoryResolver)
    {
        $this->rows = $rows;
        $this->deletedCategories = $deletedCategories;
        $this->categoryResolver = $categoryResolver;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Customer ID', 'Nama', 'Plan', 'Merchant', 'Status', 'Billing Start', 'Umur (hari)',
            'Tanggal Dihapus', 'Alasan Hapus', 'Invoice Belum Paid', 'Total Tunggakan (Rp)',
        ];
    }

    public function map($row): array
    {
        $statusName = $row->status_name ? $row->status_name->name : '-';
        if ($row->deleted_at !== null) {
            $category = call_user_func($this->categoryResolver, $row->deletion_type ?? null);
            $statusName = $this->deletedCategories[$category] . ' (' . $statusName . ')';
        }

        return [
            $row->customer_id,
            $row->name,
            $row->plan_name ? $row->plan_name->name : '-',
            $row->merchant_name ? $row->merchant_name->name : '-',
            $statusName,
            $row->billing_start ?: 'Belum billing',
            $row->age_days,
            $row->deleted_at ? $row->deleted_at->format('Y-m-d') : '',
            $row->deletion_reason ?? '',
            (int) $row->unpaid_count,
            (float) $row->unpaid_total,
        ];
    }
}
