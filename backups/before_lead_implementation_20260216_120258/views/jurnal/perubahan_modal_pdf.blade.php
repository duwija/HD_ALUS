<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Perubahan Modal</title>
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
            padding: 8px 10px;
            font-size: 10pt;
        }
        .section-header {
            background-color: #4a90e2;
            color: white;
            font-weight: bold;
            font-size: 11pt;
            padding: 10px !important;
            border-bottom: 2px solid #357abd;
        }
        .item-label {
            padding-left: 25px;
            color: #555;
        }
        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
            width: 150px;
        }
        .subtotal {
            font-weight: bold;
            background-color: #f0f0f0;
            border-top: 1px solid #999;
            border-bottom: 1px solid #999;
            padding: 9px 10px !important;
        }
        .modal-akhir {
            font-weight: bold;
            font-size: 11pt;
            background-color: #343a40;
            color: white;
            padding: 12px 10px !important;
            border-top: 3px double #333;
            border-bottom: 3px double #333;
        }
        .spacer {
            height: 10px;
        }
        .profit {
            color: #28a745;
        }
        .loss {
            color: #dc3545;
        }
        .perubahan-modal {
            margin-top: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 5px;
            text-align: center;
        }
        .perubahan-modal.increase {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .perubahan-modal.decrease {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        }
        .perubahan-label {
            font-size: 10pt;
            font-weight: normal;
            margin-bottom: 5px;
        }
        .perubahan-amount {
            font-size: 16pt;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.company', env('COMPANY','Perusahaan')) }}</div>
        <div class="report-title">LAPORAN PERUBAHAN MODAL</div>
        <div class="period">
            Periode {{ \Carbon\Carbon::parse($tanggalAwal)->translatedFormat('d F Y') }} 
            s/d {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}
        </div>
    </div>

    <table>
        <!-- MODAL AWAL PERIODE -->
        <tr>
            <td colspan="2" class="section-header">MODAL AWAL PERIODE</td>
        </tr>
        <tr>
            <td class="item-label">Modal Awal</td>
            <td class="amount">{{ number_format($modalAwal, 0, ',', '.') }}</td>
        </tr>
        <tr><td colspan="2" class="spacer"></td></tr>

        <!-- PENAMBAHAN -->
        <tr>
            <td colspan="2" class="section-header">PENAMBAHAN</td>
        </tr>
        <tr>
            <td class="item-label">Penambahan Modal</td>
            <td class="amount">{{ number_format($penambahanModal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="item-label">
                @if($labaBersih >= 0)
                    Laba Bersih Periode Berjalan
                @else
                    Rugi Bersih Periode Berjalan
                @endif
            </td>
            <td class="amount {{ $labaBersih >= 0 ? 'profit' : 'loss' }}">
                {{ number_format($labaBersih, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="subtotal">Total Penambahan</td>
            <td class="amount subtotal">{{ number_format($penambahanModal + $labaBersih, 0, ',', '.') }}</td>
        </tr>
        <tr><td colspan="2" class="spacer"></td></tr>

        <!-- PENGURANGAN -->
        <tr>
            <td colspan="2" class="section-header">PENGURANGAN</td>
        </tr>
        <tr>
            <td class="item-label">Prive/Penarikan Modal</td>
            <td class="amount">{{ number_format($prive, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="subtotal">Total Pengurangan</td>
            <td class="amount subtotal">{{ number_format($prive, 0, ',', '.') }}</td>
        </tr>
        <tr><td colspan="2" class="spacer"></td></tr>

        <!-- MODAL AKHIR PERIODE -->
        <tr>
            <td class="modal-akhir">MODAL AKHIR PERIODE</td>
            <td class="amount modal-akhir">{{ number_format($modalAkhir, 0, ',', '.') }}</td>
        </tr>
    </table>

    <!-- Perubahan Modal Summary -->
    @php
        $perubahanModal = $modalAkhir - $modalAwal;
        $perubahanClass = $perubahanModal > 0 ? 'increase' : ($perubahanModal < 0 ? 'decrease' : '');
    @endphp
    <div class="perubahan-modal {{ $perubahanClass }}">
        <div class="perubahan-label">
            @if($perubahanModal > 0)
                <i class="fas fa-arrow-up"></i> Peningkatan Modal
            @elseif($perubahanModal < 0)
                <i class="fas fa-arrow-down"></i> Penurunan Modal
            @else
                <i class="fas fa-minus"></i> Tidak Ada Perubahan Modal
            @endif
        </div>
        <div class="perubahan-amount">
            Rp {{ number_format(abs($perubahanModal), 0, ',', '.') }}
        </div>
    </div>
</body>
</html>
