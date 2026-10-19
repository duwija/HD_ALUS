<?php

namespace App\Mail;

use App\Exports\DailyReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Email laporan harian: ringkasan di badan email + lampiran Excel (DailyReportExport).
 * Dikirim langsung (tidak di-queue) dari command report:daily / tombol kirim contoh.
 */
class DailyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $report;
    public string $tenantName;

    public function __construct(array $report, string $tenantName)
    {
        $this->report = $report;
        $this->tenantName = $tenantName;
    }

    public function build()
    {
        $date = $this->report['date'];
        $file = 'laporan-harian-' . $date->format('Y-m-d') . '.xlsx';

        return $this->subject('Laporan Harian ' . $this->tenantName . ' — ' . $date->translatedFormat('l, d F Y'))
            ->view('email.daily_report')
            ->attachData(
                Excel::raw(new DailyReportExport($this->report), ExcelFormat::XLSX),
                $file,
                ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            );
    }
}
