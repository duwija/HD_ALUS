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

class NeracaExport implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        $rows->push(['Neraca (Balance Sheet)']);
        $rows->push(['Per ' . \Carbon\Carbon::parse($data['tanggalAkhir'])->translatedFormat('d F Y')]);
        $rows->push(['']); // Empty row

        // ASET
        $rows->push(['ASET', '']);
        
        // Aset Lancar
        $rows->push(['Aset Lancar', '']);
        foreach ($data['data']['aset_lancar'] ?? [] as $item) {
            $rows->push(['  ' . $item['name'], $item['saldo']]);
        }
        $rows->push(['Total Aset Lancar', $data['totals']['aset_lancar']]);
        $rows->push(['']); // Empty row
        
        // Aset Tetap
        $rows->push(['Aset Tetap', '']);
        foreach ($data['data']['aset_tetap'] ?? [] as $item) {
            $rows->push(['  ' . $item['name'], $item['saldo']]);
        }
        $rows->push(['Total Aset Tetap', $data['totals']['aset_tetap']]);
        $rows->push(['']); // Empty row
        
        // Total Aset
        $rows->push(['TOTAL ASET', $data['totalAset']]);
        $rows->push(['']); // Empty row
        $rows->push(['']); // Empty row

        // KEWAJIBAN & EKUITAS
        $rows->push(['KEWAJIBAN & EKUITAS', '']);
        
        // Kewajiban Lancar
        $rows->push(['Kewajiban Lancar', '']);
        foreach ($data['data']['kewajiban_lancar'] ?? [] as $item) {
            $rows->push(['  ' . $item['name'], $item['saldo']]);
        }
        $rows->push(['Total Kewajiban Lancar', $data['totals']['kewajiban_lancar']]);
        $rows->push(['']); // Empty row
        
        // Ekuitas
        $rows->push(['Ekuitas', '']);
        foreach ($data['data']['ekuitas'] ?? [] as $item) {
            $rows->push(['  ' . $item['name'], $item['saldo']]);
        }
        $rows->push(['  Laba (Rugi) Ditahan', $data['labaRugi']]);
        $rows->push(['Total Ekuitas', $data['totalEkuitas']]);
        $rows->push(['']); // Empty row
        
        // Total Kewajiban & Ekuitas
        $rows->push(['TOTAL KEWAJIBAN & EKUITAS', $data['totalKewajibanEkuitas']]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['Keterangan', 'Jumlah']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(50);
        $sheet->getColumnDimension('B')->setWidth(20);

        // Format number columns
        $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode('#,##0');

        // Header - Company name (row 1)
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->mergeCells('A1:B1');

        // Title - Neraca (row 2)
        $sheet->getStyle('A2:B2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->mergeCells('A2:B2');

        // Subtitle - date (row 3)
        $sheet->getStyle('A3:B3')->applyFromArray([
            'font' => ['size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->mergeCells('A3:B3');

        // Column headers (row 5)
        $sheet->getStyle('A5:B5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);

        // Bold for section headers and totals
        $lastRow = $sheet->getHighestRow();
        for ($i = 6; $i <= $lastRow; $i++) {
            $cellValue = $sheet->getCell('A' . $i)->getValue();
            
            // Section headers (ASET, KEWAJIBAN, etc)
            if (in_array($cellValue, ['ASET', 'KEWAJIBAN & EKUITAS', 'Aset Lancar', 'Aset Tetap', 'Kewajiban Lancar', 'Ekuitas'])) {
                $sheet->getStyle('A' . $i . ':B' . $i)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F5F5F5']
                    ]
                ]);
            }
            
            // Totals
            if (strpos($cellValue, 'Total') === 0 || strpos($cellValue, 'TOTAL') === 0) {
                $sheet->getStyle('A' . $i . ':B' . $i)->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_THIN],
                        'bottom' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);
            }
        }

        // Right align amounts
        $sheet->getStyle('B6:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function title(): string
    {
        return 'Neraca';
    }
}
