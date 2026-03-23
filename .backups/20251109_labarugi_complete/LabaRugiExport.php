<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LabaRugiExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $exportData;

    public function __construct($exportData)
    {
        $this->exportData = $exportData;
    }

    public function collection()
    {
        $data = $this->exportData;
        $rows = collect();

        // Header - Nama Perusahaan
        $rows->push([config('app.company', env('COMPANY','Perusahaan'))]);
        $rows->push(['Laporan Laba Rugi']);
        $rows->push(['Periode ' . \Carbon\Carbon::parse($data['tanggalAwal'])->translatedFormat('d F Y') . ' s/d ' . \Carbon\Carbon::parse($data['tanggalAkhir'])->translatedFormat('d F Y')]);
        $rows->push(['', '', '']); // Empty row

        // PENDAPATAN
        $rows->push(['PENDAPATAN', '', '']);
        
        // Pendapatan Usaha
        $rows->push(['Pendapatan Usaha', '', '']);
        foreach ($data['data']['pendapatan'] ?? [] as $item) {
            $rows->push([$item['akun_code'], $item['name'], $item['saldo']]);
        }
        if (!empty($data['data']['pendapatan'])) {
            $rows->push(['', 'Subtotal Pendapatan Usaha', $data['totals']['pendapatan']]);
        }
        
        // Pendapatan Lainnya
        if (!empty($data['data']['pendapatan_lainnya'])) {
            $rows->push(['Pendapatan Lainnya', '', '']);
            foreach ($data['data']['pendapatan_lainnya'] ?? [] as $item) {
                $rows->push([$item['akun_code'], $item['name'], $item['saldo']]);
            }
            $rows->push(['', 'Subtotal Pendapatan Lainnya', $data['totals']['pendapatan_lainnya']]);
        }
        
        // Total Pendapatan
        $rows->push(['', 'TOTAL PENDAPATAN', $data['totalPendapatanUtama']]);
        $rows->push(['', '', '']); // Empty row

        // HARGA POKOK PENJUALAN
        $rows->push(['HARGA POKOK PENJUALAN', '', '']);
        foreach ($data['data']['hpp'] ?? [] as $item) {
            $rows->push([$item['akun_code'], $item['name'], $item['saldo']]);
        }
        $rows->push(['', 'Total Harga Pokok Penjualan', $data['totals']['hpp']]);
        $rows->push(['', '', '']); // Empty row
        
        // LABA KOTOR
        $rows->push(['', 'LABA KOTOR', $data['labaKotor']]);
        $rows->push(['', '', '']); // Empty row

        // BEBAN OPERASIONAL
        $rows->push(['BEBAN OPERASIONAL', '', '']);
        
        // Beban Usaha
        $rows->push(['Beban Usaha', '', '']);
        foreach ($data['data']['beban_operasional'] ?? [] as $item) {
            $rows->push([$item['akun_code'], $item['name'], $item['saldo']]);
        }
        if (!empty($data['data']['beban_operasional'])) {
            $rows->push(['', 'Subtotal Beban Usaha', $data['totals']['beban_operasional']]);
        }
        
        // Beban Lainnya
        if (!empty($data['data']['beban_lainnya'])) {
            $rows->push(['Beban Lainnya', '', '']);
            foreach ($data['data']['beban_lainnya'] ?? [] as $item) {
                $rows->push([$item['akun_code'], $item['name'], $item['saldo']]);
            }
            $rows->push(['', 'Subtotal Beban Lainnya', $data['totals']['beban_lainnya']]);
        }
        
        // Depresiasi
        if (!empty($data['data']['depresiasi'])) {
            $rows->push(['Depresiasi & Amortisasi', '', '']);
            foreach ($data['data']['depresiasi'] ?? [] as $item) {
                $rows->push([$item['akun_code'], $item['name'], $item['saldo']]);
            }
            $rows->push(['', 'Subtotal Depresiasi & Amortisasi', $data['totals']['depresiasi']]);
        }
        
        // Total Beban
        $rows->push(['', 'TOTAL BEBAN OPERASIONAL', $data['totalBeban']]);
        $rows->push(['', '', '']); // Empty row
        
        // LABA/RUGI BERSIH
        $label = $data['labaBersih'] >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH';
        $rows->push(['', $label, $data['labaBersih']]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['Kode Akun', 'Keterangan', 'Jumlah']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(18);
        
        // Header styling (rows 1-3)
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');
        $sheet->mergeCells('A3:C3');
        
        $sheet->getStyle('A1:A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Format numbers in column C as accounting
        $sheet->getStyle('C5:C' . $lastRow)->getNumberFormat()
            ->setFormatCode('#,##0');
        
        // Right align amounts
        $sheet->getStyle('C5:C' . $lastRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Bold for section titles (PENDAPATAN, HPP, BEBAN OPERASIONAL, etc)
        for ($row = 5; $row <= $lastRow; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();
            $cellValueB = $sheet->getCell('B' . $row)->getValue();
            
            // Section titles
            if (in_array($cellValue, ['PENDAPATAN', 'HARGA POKOK PENJUALAN', 'BEBAN OPERASIONAL'])) {
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8E8E8']
                    ],
                    'borders' => [
                        'bottom' => ['borderStyle' => Border::BORDER_MEDIUM]
                    ]
                ]);
            }
            
            // Subsections
            if (in_array($cellValue, ['Pendapatan Usaha', 'Pendapatan Lainnya', 'Beban Usaha', 'Beban Lainnya', 'Depresiasi & Amortisasi'])) {
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F5F5F5']
                    ]
                ]);
            }
            
            // Subtotals and totals
            if (strpos($cellValueB, 'Subtotal') !== false || 
                strpos($cellValueB, 'TOTAL') !== false || 
                strpos($cellValueB, 'LABA') !== false ||
                strpos($cellValueB, 'RUGI') !== false) {
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F5F5F5']
                    ],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_THIN],
                        'bottom' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);
            }
            
            // LABA KOTOR special styling
            if ($cellValueB == 'LABA KOTOR') {
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E3F2FD']
                    ],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_MEDIUM],
                        'bottom' => ['borderStyle' => Border::BORDER_MEDIUM]
                    ]
                ]);
            }
            
            // LABA/RUGI BERSIH special styling
            if ($cellValueB == 'LABA BERSIH' || $cellValueB == 'RUGI BERSIH') {
                $bgColor = $cellValueB == 'LABA BERSIH' ? 'D4EDDA' : 'F8D7DA';
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $bgColor]
                    ],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_DOUBLE],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE]
                    ]
                ]);
            }
        }
        
        return [];
    }

    public function title(): string
    {
        return 'Laba Rugi';
    }
}
