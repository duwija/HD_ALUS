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

class ArusKasExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = collect();
        $mode = $this->data['mode'];
        $arus = $mode == 'direct' ? $this->data['arusKasDirect'] : $this->data['arusKasIndirect'];

        // Header
        $rows->push([config('app.company', env('COMPANY','Perusahaan'))]);
        $rows->push(['Laporan Arus Kas']);
        $rows->push(['Metode: ' . ($mode == 'direct' ? 'Langsung (Direct)' : 'Tidak Langsung (Indirect)')]);
        $rows->push(['Periode ' . \Carbon\Carbon::parse($this->data['tanggalAwal'])->translatedFormat('d F Y') . ' s/d ' . \Carbon\Carbon::parse($this->data['tanggalAkhir'])->translatedFormat('d F Y')]);
        $rows->push(['', '', '', '', '']); // Empty row

        // Saldo Awal
        $rows->push(['Saldo Awal Kas', '', '', '', $this->data['saldoAwal']]);
        $rows->push(['', '', '', '', '']); // Empty row

        if ($mode == 'direct') {
            // METODE LANGSUNG
            
            // Aktivitas Operasional
            $rows->push(['ARUS KAS DARI AKTIVITAS OPERASIONAL', '', '', '', '']);
            foreach ($this->data['detailOperasional'] ?? [] as $item) {
                $rows->push([
                    \Carbon\Carbon::parse($item['date'])->format('d/m/Y'),
                    $item['no_ref'],
                    $item['description'],
                    $item['lawan_akun'],
                    $item['nilai']
                ]);
            }
            $rows->push(['', '', '', 'Kas Bersih dari Aktivitas Operasional', $arus['operasional']]);
            $rows->push(['', '', '', '', '']); // Empty row

            // Aktivitas Investasi
            $rows->push(['ARUS KAS DARI AKTIVITAS INVESTASI', '', '', '', '']);
            if (count($this->data['detailInvestasi'] ?? []) > 0) {
                foreach ($this->data['detailInvestasi'] as $item) {
                    $rows->push([
                        \Carbon\Carbon::parse($item['date'])->format('d/m/Y'),
                        $item['no_ref'],
                        $item['description'],
                        $item['lawan_akun'],
                        $item['nilai']
                    ]);
                }
            } else {
                $rows->push(['', '', 'Tidak ada transaksi', '', 0]);
            }
            $rows->push(['', '', '', 'Kas Bersih dari Aktivitas Investasi', $arus['investasi']]);
            $rows->push(['', '', '', '', '']); // Empty row

            // Aktivitas Pendanaan
            $rows->push(['ARUS KAS DARI AKTIVITAS PENDANAAN', '', '', '', '']);
            if (count($this->data['detailPendanaan'] ?? []) > 0) {
                foreach ($this->data['detailPendanaan'] as $item) {
                    $rows->push([
                        \Carbon\Carbon::parse($item['date'])->format('d/m/Y'),
                        $item['no_ref'],
                        $item['description'],
                        $item['lawan_akun'],
                        $item['nilai']
                    ]);
                }
            } else {
                $rows->push(['', '', 'Tidak ada transaksi', '', 0]);
            }
            $rows->push(['', '', '', 'Kas Bersih dari Aktivitas Pendanaan', $arus['pendanaan']]);

        } else {
            // METODE TIDAK LANGSUNG
            
            // Aktivitas Operasional
            $rows->push(['ARUS KAS DARI AKTIVITAS OPERASIONAL', '', '', '', '']);
            $rows->push(['Laba Bersih', '', '', '', $this->data['labaBersih']]);
            $rows->push(['Penyesuaian untuk:', '', '', '', '']);
            $rows->push(['  Penyusutan & Amortisasi', '', '', '', $this->data['penyusutan']]);
            $rows->push(['  (Kenaikan) Penurunan Piutang', '', '', '', -$this->data['perubahanPiutang']]);
            $rows->push(['  (Kenaikan) Penurunan Persediaan', '', '', '', -$this->data['perubahanPersediaan']]);
            $rows->push(['  Kenaikan (Penurunan) Hutang', '', '', '', $this->data['perubahanHutang']]);
            $rows->push(['', '', '', 'Kas Bersih dari Aktivitas Operasional', $arus['operasional']]);
            $rows->push(['', '', '', '', '']); // Empty row

            // Aktivitas Investasi
            $rows->push(['ARUS KAS DARI AKTIVITAS INVESTASI', '', '', '', '']);
            if (count($this->data['detailInvestasi'] ?? []) > 0) {
                foreach ($this->data['detailInvestasi'] as $item) {
                    $rows->push([
                        \Carbon\Carbon::parse($item['date'])->format('d/m/Y'),
                        $item['no_ref'],
                        $item['description'],
                        $item['lawan_akun'],
                        $item['nilai']
                    ]);
                }
            } else {
                $rows->push(['', '', 'Tidak ada transaksi', '', 0]);
            }
            $rows->push(['', '', '', 'Kas Bersih dari Aktivitas Investasi', $arus['investasi']]);
            $rows->push(['', '', '', '', '']); // Empty row

            // Aktivitas Pendanaan
            $rows->push(['ARUS KAS DARI AKTIVITAS PENDANAAN', '', '', '', '']);
            if (count($this->data['detailPendanaan'] ?? []) > 0) {
                foreach ($this->data['detailPendanaan'] as $item) {
                    $rows->push([
                        \Carbon\Carbon::parse($item['date'])->format('d/m/Y'),
                        $item['no_ref'],
                        $item['description'],
                        $item['lawan_akun'],
                        $item['nilai']
                    ]);
                }
            } else {
                $rows->push(['', '', 'Tidak ada transaksi', '', 0]);
            }
            $rows->push(['', '', '', 'Kas Bersih dari Aktivitas Pendanaan', $arus['pendanaan']]);
        }

        // Summary
        $rows->push(['', '', '', '', '']); // Empty row
        $kenaikanKas = $arus['operasional'] + $arus['investasi'] + $arus['pendanaan'];
        $rows->push(['', '', '', 'KENAIKAN (PENURUNAN) KAS BERSIH', $kenaikanKas]);
        $rows->push(['', '', '', 'Saldo Awal Kas', $this->data['saldoAwal']]);
        $rows->push(['', '', '', 'SALDO AKHIR KAS', $this->data['saldoAkhir']]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['Tanggal', 'No. Ref', 'Keterangan', 'Akun Lawan', 'Jumlah']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(18);
        
        // Header styling (rows 1-4)
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->mergeCells('A3:E3');
        $sheet->mergeCells('A4:E4');
        
        $sheet->getStyle('A1:A4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Format numbers in column E as accounting
        $sheet->getStyle('E6:E' . $lastRow)->getNumberFormat()
            ->setFormatCode('#,##0');
        
        // Right align amounts
        $sheet->getStyle('E6:E' . $lastRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Bold for section titles
        for ($row = 6; $row <= $lastRow; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();
            
            // Section headers
            if (strpos($cellValue, 'ARUS KAS DARI') !== false) {
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0E0E0']
                    ],
                    'borders' => [
                        'left' => ['borderStyle' => Border::BORDER_THICK]
                    ]
                ]);
            }
            
            // Subtotals
            $cellValueD = $sheet->getCell('D' . $row)->getValue();
            if (strpos($cellValueD, 'Kas Bersih dari') !== false || 
                strpos($cellValueD, 'KENAIKAN') !== false ||
                strpos($cellValueD, 'SALDO AKHIR') !== false) {
                $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E9ECEF']
                    ],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_THIN],
                        'bottom' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);
            }
            
            // Final row
            if (strpos($cellValueD, 'SALDO AKHIR') !== false) {
                $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '333333']
                    ],
                    'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
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
        return 'Arus Kas';
    }
}

