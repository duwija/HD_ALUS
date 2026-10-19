<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Lampiran Excel laporan harian: satu sheet per bagian (data dari DailyReportService::build()).
 */
class DailyReportExport implements WithMultipleSheets
{
    private array $report;

    public function __construct(array $report)
    {
        $this->report = $report;
    }

    public function sheets(): array
    {
        $r = $this->report;
        $s = $r['summary'];
        $status = [0 => 'Belum bayar', 1 => 'Lunas', 2 => 'Batal'];

        return [
            new DailyReportSheet('Ringkasan', ['Bagian', 'Jumlah', 'Nominal (Rp)'], [
                ['Pembayaran diterima', $s['payments_count'], $s['payments_total']],
                ['Invoice diterbitkan', $s['issued_count'], $s['issued_total']],
                ['Invoice dibatalkan', $s['cancelled_count'], $s['cancelled_total']],
                ['Tagihan belum lunas (saat ini)', $s['unpaid_count'], $s['unpaid_total']],
                ['Langganan baru', $s['new_subs'], null],
                ['Terminate', $s['terminated'], null],
                ['Diisolir / blokir', $s['isolated'], null],
                ['Calon pelanggan (potensial) baru', $s['new_leads'], null],
                ['Total pelanggan aktif (saat ini)', $s['active_now'], null],
                ['Total pelanggan blokir (saat ini)', $s['blocked_now'], null],
                ['Total potensial (saat ini)', $s['potential_now'], null],
            ]),
            new DailyReportSheet('Per Akun & Metode', ['Akun / Metode', 'Jumlah', 'Total (Rp)'], array_merge(
                [['PER AKUN', null, null]],
                $r['payments_by_account']->map(fn ($a) => [$a['name'], $a['count'], $a['total']])->all(),
                [[null, null, null], ['PER METODE', null, null]],
                $r['payments_by_method']->map(fn ($a) => [$a['name'], $a['count'], $a['total']])->all()
            )),
            new DailyReportSheet('Pembayaran',
                ['Waktu bayar', 'No. Invoice', 'CID', 'Nama', 'Tagihan (Rp)', 'Diterima (Rp)', 'Akun', 'Metode', 'Diproses oleh'],
                $r['payments']->map(fn ($p) => [$p->payment_date, $p->number, $p->customer_id, $p->name, (int) $p->total_amount, (int) $p->amount, $p->account, $p->method, $p->updated_by])->all()),
            new DailyReportSheet('Invoice Terbit',
                ['Dibuat', 'No. Invoice', 'CID', 'Nama', 'Tgl Invoice', 'Jatuh Tempo', 'Total (Rp)', 'Status', 'Dibuat oleh'],
                $r['issued']->map(fn ($i) => [$i->created_at, $i->number, $i->customer_id, $i->name, $i->date, $i->due_date, (int) $i->total_amount, $status[$i->payment_status] ?? $i->payment_status, $i->created_by])->all()),
            new DailyReportSheet('Invoice Batal',
                ['Dibatalkan', 'No. Invoice', 'CID', 'Nama', 'Tgl Invoice', 'Total (Rp)', 'Oleh', 'Catatan'],
                $r['cancelled']->map(fn ($i) => [$i->updated_at, $i->number, $i->customer_id, $i->name, $i->date, (int) $i->total_amount, $i->updated_by, $i->note])->all()),
            new DailyReportSheet('Langganan Baru',
                ['Sejak', 'CID', 'Nama', 'Paket', 'Alamat', 'Telepon'],
                $r['new_subs']->map(fn ($c) => [$c->since, $c->customer_id, $c->name, $c->plan, $c->address, $c->phone])->all()),
            new DailyReportSheet('Terminate',
                ['Waktu', 'CID', 'Nama', 'Status Sebelumnya', 'Alamat', 'Oleh'],
                $r['terminated']->map(fn ($c) => [$c->changed_at, $c->customer_id, $c->name, $c->old_status, $c->address, $c->changed_by])->all()),
            new DailyReportSheet('Isolir',
                ['Waktu', 'CID', 'Nama', 'Status Sebelumnya', 'Alamat', 'Oleh'],
                $r['isolated']->map(fn ($c) => [$c->changed_at, $c->customer_id, $c->name, $c->old_status, $c->address, $c->changed_by])->all()),
            new DailyReportSheet('Potensial Baru',
                ['Dibuat', 'CID', 'Nama', 'Alamat', 'Telepon', 'Sumber'],
                $r['new_leads']->map(fn ($c) => [$c->created_at, $c->customer_id, $c->name, $c->address, $c->phone, $c->lead_source])->all()),
        ];
    }
}
