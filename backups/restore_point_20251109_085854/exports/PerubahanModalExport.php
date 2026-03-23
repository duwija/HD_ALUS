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

class PerubahanModalExport implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        $rows->push(['Laporan Perubahan Modal']);
        $rows->push(['Periode ' . \Carbon\Carbon::parse($data['tanggalAwal'])->translatedFormat('d F Y') . ' s/d ' . \Carbon\Carbon::parse($data['tanggalAkhir'])->translatedFormat('d F Y')]);
        $rows->push(['', '']); // Empty row

        // MODAL AWAL PERIODE
        $rows->push(['MODAL AWAL PERIODE', '']);
        $rows->push(['Modal Awal', number_format($data['modalAwal'], 0, ',', '.')]);
        $rows->push(['', '']); // Empty row

        // PENAMBAHAN
        $rows->push(['PENAMBAHAN', '']);
        $rows->push(['Penambahan Modal', number_format($data['penambahanModal'], 0, ',', '.')]);
        
        // Laba/Rugi
        if ($data['labaBersih'] >= 0) {
            $rows->push(['Laba Bersih Periode Berjalan', number_format($data['labaBersih'], 0, ',', '.')]);
        } else {
            $rows->push(['Rugi Bersih Periode Berjalan', number_format($data['labaBersih'], 0, ',', '.')]);
        }
        
        $totalPenambahan = $data['penambahanModal'] + $data['labaBersih'];
        $rows->push(['Total Penambahan', number_format($totalPenambahan, 0, ',', '.')]);
        $rows->push(['', '']); // Empty row

        // PENGURANGAN
        $rows->push(['PENGURANGAN', '']);
        $rows->push(['Prive/Penarikan Modal', number_format($data['prive'], 0, ',', '.')]);
        $rows->push(['Total Pengurangan', number_format($data['prive'], 0, ',', '.')]);
        $rows->push(['', '']); // Empty row

        // MODAL AKHIR PERIODE
        $rows->push(['MODAL AKHIR PERIODE', number_format($data['modalAkhir'], 0, ',', '.')]);
        $rows->push(['', '']); // Empty row

        // Perubahan Modal
        $perubahanModal = $data['modalAkhir'] - $data['modalAwal'];
        $rows->push(['Perubahan Modal', number_format($perubahanModal, 0, ',', '.')]);

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
        // Merge cells for header
        $sheet->mergeCells('A1:B1'); // Company name
        $sheet->mergeCells('A2:B2'); // Report title
        $sheet->mergeCells('A3:B3'); // Period

        // Center align headers
        $sheet->getStyle('A1:B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Bold headers
        $sheet->getStyle('A1:B3')->getFont()->setBold(true);
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(20);

        // Style section headers (MODAL AWAL PERIODE, PENAMBAHAN, etc.)
        $sectionRows = [];
        $rowNum = 5; // Starting after main header
        
        foreach ($this->collection() as $index => $row) {
            $currentRow = $index + 5; // Offset for headers
            
            // Check if row is section header
            if (isset($row[0]) && in_array($row[0], [
                'MODAL AWAL PERIODE',
                'PENAMBAHAN',
                'PENGURANGAN',
                'MODAL AKHIR PERIODE'
            ])) {
                $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FF4a90e2'); // Soft blue
                
                $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)
                    ->getFont()
                    ->setBold(true)
                    ->getColor()
                    ->setARGB('FFFFFFFF'); // White text
            }
            
            // Style totals
            if (isset($row[0]) && in_array($row[0], [
                'Total Penambahan',
                'Total Pengurangan',
                'MODAL AKHIR PERIODE'
            ])) {
                $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FF343a40'); // Dark background
                
                $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)
                    ->getFont()
                    ->setBold(true)
                    ->getColor()
                    ->setARGB('FFFFFFFF'); // White text
            }
        }

        // Add borders to all cells with data
        $lastRow = 5 + $this->collection()->count() - 1;
        $sheet->getStyle('A5:B' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        // Right align amounts column
        $sheet->getStyle('B5:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function title(): string
    {
        return 'Perubahan Modal';
    }
}
