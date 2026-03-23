<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Arus Kas</title>
    <style>
        @page {
            margin: 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #333;
        }
        .company-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .report-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .period {
            font-size: 8pt;
            color: #666;
        }
        .saldo-box {
            background: #f0f0f0;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #999;
        }
        .saldo-box table {
            width: 100%;
            border: none;
        }
        .saldo-box td {
            border: none;
            padding: 3px;
            font-weight: bold;
        }
        .section-header {
            background: #e0e0e0;
            padding: 6px 8px;
            font-weight: bold;
            font-size: 9pt;
            margin-top: 12px;
            border-left: 4px solid #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8pt;
        }
        table.detail-table th {
            background: #f5f5f5;
            padding: 4px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #999;
            font-size: 8pt;
        }
        table.detail-table td {
            padding: 3px 4px;
            border-bottom: 1px solid #e0e0e0;
        }
        .subtotal-row {
            background: #e9ecef;
            font-weight: bold;
        }
        .total-row {
            background: #dee2e6;
            font-weight: bold;
            font-size: 9pt;
        }
        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        .indirect-detail {
            background: #f8f9fa;
            padding: 8px;
            margin: 5px 0;
        }
        .indirect-detail table td {
            border: none;
            padding: 2px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.company', env('COMPANY','Perusahaan')) }}</div>
        <div class="report-title">LAPORAN ARUS KAS</div>
        <div class="period">
            Metode: {{ $mode == 'direct' ? 'Langsung (Direct)' : 'Tidak Langsung (Indirect)' }}<br>
            Periode {{ \Carbon\Carbon::parse($tanggalAwal)->translatedFormat('d F Y') }} 
            s/d {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}
        </div>
    </div>

    <div class="saldo-box">
        <table>
            <tr>
                <td width="50%">Saldo Awal Kas: <strong>{{ number_format($saldoAwal, 0, ',', '.') }}</strong></td>
                <td width="50%" style="text-align: right;">Saldo Akhir Kas: <strong>{{ number_format($saldoAkhir, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    @if($mode == 'direct')
        <!-- METODE LANGSUNG -->
        
        <!-- Aktivitas Operasional -->
        <div class="section-header">ARUS KAS DARI AKTIVITAS OPERASIONAL</div>
        @if(count($detailOperasional) > 0)
        <table class="detail-table">
            <thead>
                <tr>
                    <th width="10%">Tanggal</th>
                    <th width="12%">No. Ref</th>
                    <th width="35%">Keterangan</th>
                    <th width="28%">Akun Lawan</th>
                    <th width="15%" class="amount">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detailOperasional as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                    <td>{{ $item['no_ref'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td style="font-size: 7pt;">{{ $item['lawan_akun'] }}</td>
                    <td class="amount">{{ number_format($item['nilai'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="4">Kas Bersih dari Aktivitas Operasional</td>
                    <td class="amount">{{ number_format($arusKasDirect['operasional'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <p style="text-align: center; color: #999; padding: 8px;">Tidak ada transaksi operasional</p>
        @endif

        <!-- Aktivitas Investasi -->
        <div class="section-header">ARUS KAS DARI AKTIVITAS INVESTASI</div>
        @if(count($detailInvestasi) > 0)
        <table class="detail-table">
            <thead>
                <tr>
                    <th width="10%">Tanggal</th>
                    <th width="12%">No. Ref</th>
                    <th width="35%">Keterangan</th>
                    <th width="28%">Akun Lawan</th>
                    <th width="15%" class="amount">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detailInvestasi as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                    <td>{{ $item['no_ref'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td style="font-size: 7pt;">{{ $item['lawan_akun'] }}</td>
                    <td class="amount">{{ number_format($item['nilai'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="4">Kas Bersih dari Aktivitas Investasi</td>
                    <td class="amount">{{ number_format($arusKasDirect['investasi'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <p style="text-align: center; color: #999; padding: 8px;">Tidak ada transaksi investasi</p>
        @endif

        <!-- Aktivitas Pendanaan -->
        <div class="section-header">ARUS KAS DARI AKTIVITAS PENDANAAN</div>
        @if(count($detailPendanaan) > 0)
        <table class="detail-table">
            <thead>
                <tr>
                    <th width="10%">Tanggal</th>
                    <th width="12%">No. Ref</th>
                    <th width="35%">Keterangan</th>
                    <th width="28%">Akun Lawan</th>
                    <th width="15%" class="amount">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detailPendanaan as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                    <td>{{ $item['no_ref'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td style="font-size: 7pt;">{{ $item['lawan_akun'] }}</td>
                    <td class="amount">{{ number_format($item['nilai'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="4">Kas Bersih dari Aktivitas Pendanaan</td>
                    <td class="amount">{{ number_format($arusKasDirect['pendanaan'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <p style="text-align: center; color: #999; padding: 8px;">Tidak ada transaksi pendanaan</p>
        @endif

    @else
        <!-- METODE TIDAK LANGSUNG -->
        
        <!-- Aktivitas Operasional -->
        <div class="section-header">ARUS KAS DARI AKTIVITAS OPERASIONAL</div>
        <div class="indirect-detail">
            <table>
                <tr>
                    <td width="75%">Laba Bersih</td>
                    <td class="amount" width="25%">{{ number_format($labaBersih, 0, ',', '.') }}</td>
                </tr>
                <tr style="font-weight: 600;">
                    <td colspan="2" style="padding-top: 6px;">Penyesuaian untuk:</td>
                </tr>
                <tr>
                    <td style="padding-left: 15px;">Penyusutan & Amortisasi</td>
                    <td class="amount">{{ number_format($penyusutan, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 15px;">(Kenaikan) Penurunan Piutang</td>
                    <td class="amount">{{ number_format(-$perubahanPiutang, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 15px;">(Kenaikan) Penurunan Persediaan</td>
                    <td class="amount">{{ number_format(-$perubahanPersediaan, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 15px;">Kenaikan (Penurunan) Hutang</td>
                    <td class="amount">{{ number_format($perubahanHutang, 0, ',', '.') }}</td>
                </tr>
                <tr class="subtotal-row">
                    <td><strong>Kas Bersih dari Aktivitas Operasional</strong></td>
                    <td class="amount"><strong>{{ number_format($arusKasIndirect['operasional'], 0, ',', '.') }}</strong></td>
                </tr>
            </table>
        </div>

        <!-- Aktivitas Investasi -->
        <div class="section-header">ARUS KAS DARI AKTIVITAS INVESTASI</div>
        @if(count($detailInvestasi) > 0)
        <table class="detail-table">
            <thead>
                <tr>
                    <th width="10%">Tanggal</th>
                    <th width="12%">No. Ref</th>
                    <th width="35%">Keterangan</th>
                    <th width="28%">Akun Lawan</th>
                    <th width="15%" class="amount">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detailInvestasi as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                    <td>{{ $item['no_ref'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td style="font-size: 7pt;">{{ $item['lawan_akun'] }}</td>
                    <td class="amount">{{ number_format($item['nilai'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="4">Kas Bersih dari Aktivitas Investasi</td>
                    <td class="amount">{{ number_format($arusKasIndirect['investasi'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <p style="text-align: center; color: #999; padding: 8px;">Tidak ada transaksi investasi</p>
        @endif

        <!-- Aktivitas Pendanaan -->
        <div class="section-header">ARUS KAS DARI AKTIVITAS PENDANAAN</div>
        @if(count($detailPendanaan) > 0)
        <table class="detail-table">
            <thead>
                <tr>
                    <th width="10%">Tanggal</th>
                    <th width="12%">No. Ref</th>
                    <th width="35%">Keterangan</th>
                    <th width="28%">Akun Lawan</th>
                    <th width="15%" class="amount">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detailPendanaan as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                    <td>{{ $item['no_ref'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td style="font-size: 7pt;">{{ $item['lawan_akun'] }}</td>
                    <td class="amount">{{ number_format($item['nilai'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="4">Kas Bersih dari Aktivitas Pendanaan</td>
                    <td class="amount">{{ number_format($arusKasIndirect['pendanaan'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <p style="text-align: center; color: #999; padding: 8px;">Tidak ada transaksi pendanaan</p>
        @endif
    @endif

    <!-- Summary -->
    <table style="margin-top: 15px; border: 1px solid #333;">
        <tbody>
            @php
                $arus = $mode == 'direct' ? $arusKasDirect : $arusKasIndirect;
                $kenaikanKas = $arus['operasional'] + $arus['investasi'] + $arus['pendanaan'];
            @endphp
            <tr class="total-row">
                <td width="70%"><strong>KENAIKAN (PENURUNAN) KAS BERSIH</strong></td>
                <td class="amount" width="30%"><strong>{{ number_format($kenaikanKas, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td>Saldo Awal Kas</td>
                <td class="amount">{{ number_format($saldoAwal, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row" style="background: #333; color: white;">
                <td><strong>SALDO AKHIR KAS</strong></td>
                <td class="amount"><strong>{{ number_format($saldoAkhir, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
