<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Neraca</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      padding: 20px;
    }
    .header {
      text-align: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #333;
    }
    .header h1 {
      font-size: 18px;
      margin-bottom: 5px;
    }
    .header p {
      font-size: 12px;
      color: #666;
    }
    
    /* 2 Column Layout */
    .two-column {
      display: table;
      width: 100%;
      margin-bottom: 20px;
    }
    .column {
      display: table-cell;
      width: 50%;
      vertical-align: top;
      padding: 0 10px;
    }
    .column:first-child {
      padding-left: 0;
      padding-right: 15px;
      border-right: 1px solid #ddd;
    }
    .column:last-child {
      padding-left: 15px;
      padding-right: 0;
    }
    
    .column-header {
      background-color: #f0f0f0;
      padding: 10px;
      font-weight: bold;
      font-size: 13px;
      text-align: center;
      border: 1px solid #ccc;
      margin-bottom: 5px;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    td {
      padding: 6px 8px;
      border: none;
    }
    .subsection {
      background-color: #f8f8f8;
      font-weight: bold;
      border-bottom: 1px solid #ddd;
      padding: 8px !important;
    }
    .item {
      padding-left: 20px !important;
      border-bottom: 1px solid #f0f0f0;
    }
    .subtotal {
      font-weight: bold;
      background-color: #f5f5f5;
      border-top: 1px solid #999;
      border-bottom: 1px solid #999;
      padding: 8px !important;
    }
    .total {
      font-weight: bold;
      font-size: 12px;
      background-color: #e8e8e8;
      border-top: 2px solid #333;
      border-bottom: 2px solid #333;
      padding: 10px 8px !important;
    }
    .amount {
      text-align: right;
      font-family: 'Courier New', monospace;
    }
    
    .balance-summary {
      margin-top: 20px;
      padding-top: 15px;
      border-top: 2px solid #333;
    }
    .balance-row {
      display: table;
      width: 100%;
      margin-bottom: 10px;
    }
    .balance-box {
      display: table-cell;
      width: 50%;
      padding: 15px;
      background-color: #f5f5f5;
      border: 1px solid #ccc;
      text-align: center;
    }
    .balance-box:first-child {
      margin-right: 10px;
    }
    .balance-label {
      font-size: 10px;
      color: #666;
      margin-bottom: 5px;
    }
    .balance-value {
      font-size: 14px;
      font-weight: bold;
      font-family: 'Courier New', monospace;
    }
    
    .balance-check {
      margin-top: 10px;
      padding: 10px;
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
      text-align: center;
      font-size: 11px;
    }
  </style>
</head>
<body>

 
  <div class="header">
    <h3 class="font-weight-bold m-0">
      {{ config('app.company', env('COMPANY','Perusahaan')) }}
    </h3>
    <div><strong>Neraca (Balance Sheet)</strong></div>
    <p>Per {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}</p>
  </div>
  

  <!-- 2 Column Layout -->
  <div class="two-column">
    
    <!-- LEFT COLUMN: ASET -->
    <div class="column">
      <div class="column-header">ASET</div>
      <table>
        
        <!-- Aset Lancar -->
        <tr>
          <td colspan="2" class="subsection">Aset Lancar</td>
        </tr>
        @forelse ($data['aset_lancar'] ?? [] as $item)
        <tr>
          <td class="item">{{ $item['name'] }}</td>
          <td class="amount item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr>
          <td class="item" colspan="2" style="text-align: center; font-style: italic; color: #999;">Tidak ada data</td>
        </tr>
        @endforelse
        <tr>
          <td class="subtotal">Total Aset Lancar</td>
          <td class="amount subtotal">{{ number_format($totals['aset_lancar'], 0, ',', '.') }}</td>
        </tr>

        <!-- Aset Tetap -->
        <tr>
          <td colspan="2" class="subsection">Aset Tetap</td>
        </tr>
        @forelse ($data['aset_tetap'] ?? [] as $item)
        <tr>
          <td class="item">{{ $item['name'] }}</td>
          <td class="amount item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr>
          <td class="item" colspan="2" style="text-align: center; font-style: italic; color: #999;">Tidak ada data</td>
        </tr>
        @endforelse
        <tr>
          <td class="subtotal">Total Aset Tetap</td>
          <td class="amount subtotal">{{ number_format($totals['aset_tetap'], 0, ',', '.') }}</td>
        </tr>

        <!-- Total Aset -->
        <tr>
          <td class="total">TOTAL ASET</td>
          <td class="amount total">{{ number_format($totalAset, 0, ',', '.') }}</td>
        </tr>

      </table>
    </div>

    <!-- RIGHT COLUMN: KEWAJIBAN & EKUITAS -->
    <div class="column">
      <div class="column-header">KEWAJIBAN & EKUITAS</div>
      <table>
        
        <!-- Kewajiban Lancar -->
        <tr>
          <td colspan="2" class="subsection">Kewajiban Lancar</td>
        </tr>
        @forelse ($data['kewajiban_lancar'] ?? [] as $item)
        <tr>
          <td class="item">{{ $item['name'] }}</td>
          <td class="amount item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr>
          <td class="item" colspan="2" style="text-align: center; font-style: italic; color: #999;">Tidak ada data</td>
        </tr>
        @endforelse
        <tr>
          <td class="subtotal">Total Kewajiban Lancar</td>
          <td class="amount subtotal">{{ number_format($totals['kewajiban_lancar'], 0, ',', '.') }}</td>
        </tr>

        <!-- Spacer -->
        <tr>
          <td colspan="2" style="height: 10px;"></td>
        </tr>

        <!-- Ekuitas -->
        <tr>
          <td colspan="2" class="subsection">Ekuitas</td>
        </tr>
        @forelse ($data['ekuitas'] ?? [] as $item)
        <tr>
          <td class="item">{{ $item['name'] }}</td>
          <td class="amount item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
        </tr>
        @empty
        @endforelse
        <tr>
          <td class="item">Laba (Rugi) Ditahan</td>
          <td class="amount item">{{ number_format($labaRugi, 0, ',', '.') }}</td>
        </tr>
        <tr>
          <td class="subtotal">Total Ekuitas</td>
          <td class="amount subtotal">{{ number_format($totalEkuitas, 0, ',', '.') }}</td>
        </tr>

        <!-- Total Kewajiban & Ekuitas -->
        <tr>
          <td class="total">TOTAL KEWAJIBAN & EKUITAS</td>
          <td class="amount total">{{ number_format($totalKewajibanEkuitas, 0, ',', '.') }}</td>
        </tr>

      </table>
    </div>

  </div>

  <!-- Balance Summary -->
  <div class="balance-summary">
    <div class="balance-row">
      <div class="balance-box">
        <div class="balance-label">TOTAL ASET</div>
        <div class="balance-value">Rp {{ number_format($totalAset, 0, ',', '.') }}</div>
      </div>
      <div class="balance-box">
        <div class="balance-label">TOTAL KEWAJIBAN & EKUITAS</div>
        <div class="balance-value">Rp {{ number_format($totalKewajibanEkuitas, 0, ',', '.') }}</div>
      </div>
    </div>
    
    @php
      $selisih = abs($totalAset - $totalKewajibanEkuitas);
      $isBalanced = $selisih < 0.01;
    @endphp
    
    @if($isBalanced)
    <div class="balance-check">
      ✓ Neraca Seimbang - Total Aset = Total Kewajiban + Ekuitas
    </div>
    @endif
  </div>

</body>
</html>
