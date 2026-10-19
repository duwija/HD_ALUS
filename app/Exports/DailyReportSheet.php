<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Satu sheet sederhana (judul + header + baris) untuk DailyReportExport. */
class DailyReportSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize
{
    private string $title;
    private array $headings;
    private array $rows;

    public function __construct(string $title, array $headings, array $rows)
    {
        $this->title = $title;
        $this->headings = $headings;
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows ?: [['(tidak ada data)']];
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->title;
    }
}
