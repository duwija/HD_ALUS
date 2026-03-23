<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi</title>
    <style>
        @page {
            margin: 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .report-title {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .period {
            font-size: 9pt;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        td {
            padding: 5px 8px;
            font-size: 9pt;
        }
        .section-title {
            background-color: #e8e8e8;
            font-weight: bold;
            font-size: 10pt;
            padding: 8px !important;
            border-bottom: 2px solid #999;
        }
        .subsection {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 9pt;
            padding: 6px 8px !important;
            border-bottom: 1px solid #ccc;
        }
        .item-code {
            color: #666;
            font-family: 'Courier New', monospace;
            font-size: 8pt;
            width: 80px;
        }
        .item-name {
            padding-left: 5px;
        }
        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
            width: 120px;
        }
        .subtotal {
            font-weight: bold;
            background-color: #f5f5f5;
            border-top: 1px solid #999;
            border-bottom: 1px solid #999;
            padding: 7px 8px !important;
        }
        .laba-kotor {
            font-weight: bold;
            font-size: 10pt;
            background-color: #e3f2fd;
            border-top: 2px solid #1976d2;
            border-bottom: 2px solid #1976d2;
            padding: 8px !important;
        }
        .laba-bersih {
            font-weight: bold;
            font-size: 11pt;
            padding: 10px 8px !important;
            border-top: 3px double #333;
            border-bottom: 3px double #333;
        }
        .laba-bersih.profit {
            background-color: #d4edda;
            color: #155724;
        }
        .laba-bersih.loss {
            background-color: #f8d7da;
            color: #721c24;
        }
        .spacer {
            height: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.company', env('COMPANY','Perusahaan')) }}</div>
        <div class="report-title">LAPORAN LABA RUGI</div>
        <div class="period">
            Periode {{ \Carbon\Carbon::parse($tanggalAwal)->translatedFormat('d F Y') }} 
            s/d {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}
        </div>
    </div>

    <table>
        <!-- PENDAPATAN -->
        <tr>
            <td colspan="3" class="section-title">PENDAPATAN</td>
        </tr>
        <tr>
            <td colspan="3" class="subsection">Pendapatan Usaha</td>
        </tr>
        @forelse ($data['pendapatan'] ?? [] as $item)
        <tr>
            <td class="item-code">{{ $item['akun_code'] }}</td>
            <td class="item-name">{{ $item['name'] }}</td>
            <td class="amount">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="3" style="text-align: center; color: #999; font-style: italic;">Tidak ada data</td>
        </tr>
        @endforelse
        @if(!empty($data['pendapatan']))
        <tr>
            <td colspan="2" class="subtotal" style="padding-left: 30px;">Subtotal Pendapatan Usaha</td>
            <td class="amount subtotal">{{ number_format($totals['pendapatan'], 0, ',', '.') }}</td>
        </tr>
        @endif

        <!-- Pendapatan Lainnya -->
        @if(!empty($data['pendapatan_lainnya']))
        <tr>
            <td colspan="3" class="subsection">Pendapatan Lainnya</td>
        </tr>
        @forelse ($data['pendapatan_lainnya'] ?? [] as $item)
        <tr>
            <td class="item-code">{{ $item['akun_code'] }}</td>
            <td class="item-name">{{ $item['name'] }}</td>
            <td class="amount">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
        </tr>
        @empty
        @endforelse
        <tr>
            <td colspan="2" class="subtotal" style="padding-left: 30px;">Subtotal Pendapatan Lainnya</td>
            <td class="amount subtotal">{{ number_format($totals['pendapatan_lainnya'], 0, ',', '.') }}</td>
        </tr>
        @endif

        <!-- Total Pendapatan -->
        <tr>
            <td colspan="2" class="subtotal">TOTAL PENDAPATAN</td>
            <td class="amount subtotal">{{ number_format($totalPendapatanUtama, 0, ',', '.') }}</td>
        </tr>

        <!-- Spacer -->
        <tr>
            <td colspan="3" class="spacer"></td>
        </tr>

        <!-- HARGA POKOK PENJUALAN -->
        <tr>
            <td colspan="3" class="section-title">HARGA POKOK PENJUALAN</td>
        </tr>
        @forelse ($data['hpp'] ?? [] as $item)
        <tr>
            <td class="item-code">{{ $item['akun_code'] }}</td>
            <td class="item-name">{{ $item['name'] }}</td>
            <td class="amount">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="3" style="text-align: center; color: #999; font-style: italic;">Tidak ada data</td>
        </tr>
        @endforelse
        <tr>
            <td colspan="2" class="subtotal">Total Harga Pokok Penjualan</td>
            <td class="amount subtotal">{{ number_format($totals['hpp'], 0, ',', '.') }}</td>
        </tr>

        <!-- LABA KOTOR -->
        <tr>
            <td colspan="2" class="laba-kotor">LABA KOTOR</td>
            <td class="amount laba-kotor">{{ number_format($labaKotor, 0, ',', '.') }}</td>
        </tr>

        <!-- Spacer -->
        <tr>
            <td colspan="3" class="spacer"></td>
        </tr>

        <!-- BEBAN OPERASIONAL -->
        <tr>
            <td colspan="3" class="section-title">BEBAN OPERASIONAL</td>
        </tr>

        <!-- Beban Usaha -->
        <tr>
            <td colspan="3" class="subsection">Beban Usaha</td>
        </tr>
        @forelse ($data['beban_operasional'] ?? [] as $item)
        <tr>
            <td class="item-code">{{ $item['akun_code'] }}</td>
            <td class="item-name">{{ $item['name'] }}</td>
            <td class="amount">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="3" style="text-align: center; color: #999; font-style: italic;">Tidak ada data</td>
        </tr>
        @endforelse
        @if(!empty($data['beban_operasional']))
        <tr>
            <td colspan="2" class="subtotal" style="padding-left: 30px;">Subtotal Beban Usaha</td>
            <td class="amount subtotal">{{ number_format($totals['beban_operasional'], 0, ',', '.') }}</td>
        </tr>
        @endif

        <!-- Beban Lainnya -->
        @if(!empty($data['beban_lainnya']))
        <tr>
            <td colspan="3" class="subsection">Beban Lainnya</td>
        </tr>
        @forelse ($data['beban_lainnya'] ?? [] as $item)
        <tr>
            <td class="item-code">{{ $item['akun_code'] }}</td>
            <td class="item-name">{{ $item['name'] }}</td>
            <td class="amount">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
        </tr>
        @empty
        @endforelse
        <tr>
            <td colspan="2" class="subtotal" style="padding-left: 30px;">Subtotal Beban Lainnya</td>
            <td class="amount subtotal">{{ number_format($totals['beban_lainnya'], 0, ',', '.') }}</td>
        </tr>
        @endif

        <!-- Depresiasi -->
        @if(!empty($data['depresiasi']))
        <tr>
            <td colspan="3" class="subsection">Depresiasi & Amortisasi</td>
        </tr>
        @forelse ($data['depresiasi'] ?? [] as $item)
        <tr>
            <td class="item-code">{{ $item['akun_code'] }}</td>
            <td class="item-name">{{ $item['name'] }}</td>
            <td class="amount">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
        </tr>
        @empty
        @endforelse
        <tr>
            <td colspan="2" class="subtotal" style="padding-left: 30px;">Subtotal Depresiasi & Amortisasi</td>
            <td class="amount subtotal">{{ number_format($totals['depresiasi'], 0, ',', '.') }}</td>
        </tr>
        @endif

        <!-- Total Beban -->
        <tr>
            <td colspan="2" class="subtotal">TOTAL BEBAN OPERASIONAL</td>
            <td class="amount subtotal">{{ number_format($totalBeban, 0, ',', '.') }}</td>
        </tr>

        <!-- Spacer -->
        <tr>
            <td colspan="3" class="spacer"></td>
        </tr>

        <!-- LABA BERSIH -->
        <tr>
            <td colspan="2" class="laba-bersih {{ $labaBersih >= 0 ? 'profit' : 'loss' }}">
                {{ $labaBersih >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}
            </td>
            <td class="amount laba-bersih {{ $labaBersih >= 0 ? 'profit' : 'loss' }}">
                {{ number_format($labaBersih, 0, ',', '.') }}
            </td>
        </tr>
    </table>
</body>
</html>
