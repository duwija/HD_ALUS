<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Laba Rugi - Export</title>
  <style>
    *{ font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
    h1,h2,h3,h4,h5{ margin: 0 0 6px 0; }
    .text-right{ text-align:right; }
    .fw-bold{ font-weight:700; }
    .mt-2{ margin-top:8px; } .mt-3{ margin-top:12px; }
    table{ width:100%; border-collapse:collapse; }
    td{ padding:4px 6px; vertical-align:top; }
    .line{ border-bottom:1px solid #000; width:100%; display:inline-block; transform: translateY(-3px); }
    .bg-light{ background:#f4f4f4; }
    .bg-dark{ background:#333; color:#fff; }
  </style>
</head>
<body>
  @php
  $fmt = fn($n) => number_format((float)$n, 2, ',', '.');
  $p   = fn($n) => $n < 0 ? '(' . $fmt(abs($n)) . ')' : $fmt($n);
  @endphp

  <div style="text-align:center; margin-bottom:10px;">
    <h3>{{ $company ?? config('app.company', env('COMPANY','Perusahaan')) }}</h3>
    <div class="fw-bold">Laba Rugi</div>
    <div>{{ \Carbon\Carbon::parse($tanggalAwal)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}</div>
  </div>

  {{-- Pendapatan --}}
  <h5 class="mt-3">Pendapatan</h5>
  <table>
    @foreach ($pendapatan['rows'] as $r)
    <tr>
      <td style="width:18%">{{ $r['kode'] }}</td>
      <td>{{ $r['nama'] }}</td>

      <td class="text-right" style="width:20%">{{ $p($r['nilai']) }}</td>
    </tr>
    @endforeach
    <tr class="fw-bold">
      <td></td><td>Total dari Pendapatan</td>
      <td class="text-right">{{ $p($pendapatan['subtotal']) }}</td>
    </tr>
  </table>

  {{-- Beban Pokok --}}
  <h5 class="mt-3">Beban Pokok Pendapatan</h5>
  <table>
    @foreach ($cogs['rows'] as $r)
    <tr>
      <td style="width:18%">{{ $r['kode'] }}</td>
      <td>{{ $r['nama'] }}</td>

      <td class="text-right" style="width:20%">{{ $p($r['nilai']) }}</td>
    </tr>
    @endforeach
    <tr class="fw-bold">
      <td></td><td>Total dari Beban Pokok Pendapatan</td>
      <td class="text-right">{{ $p($cogs['subtotal']) }}</td>
    </tr>
  </table>

  {{-- Laba Kotor --}}
  <table class="mt-2">
    <tr class="fw-bold bg-light">
      <td style="width:18%"></td>
      <td>Laba Kotor</td>
      
      <td class="text-right" style="width:20%">{{ $p($grossProfit) }}</td>
    </tr>
  </table>

  {{-- Beban Operasional --}}
  <h5 class="mt-3">Beban Operasional</h5>
  @foreach ($opex as $judul => $bagian)
  @if (!empty($bagian['rows']))
  <h6 class="mt-2">{{ $judul }}</h6>
  <table>
    @foreach ($bagian['rows'] as $r)
    <tr>
      <td style="width:18%">{{ $r['kode'] }}</td>
      <td>{{ $r['nama'] }}</td>

      <td class="text-right" style="width:20%">{{ $p($r['nilai']) }}</td>
    </tr>
    @endforeach
    <tr class="fw-bold">
      <td></td><td>Total {{ $judul }}</td>
      <td class="text-right">{{ $p($bagian['subtotal']) }}</td>
    </tr>
  </table>
  @endif
  @endforeach

  {{-- Laba Operasional --}}
  <table class="mt-2">
    <tr class="fw-bold bg-light">
      <td style="width:18%"></td>
      <td>Laba Operasional</td>
      
      <td class="text-right" style="width:20%">{{ $p($operatingProfit) }}</td>
    </tr>
  </table>

  {{-- Lain-lain --}}
  <h5 class="mt-3">Pendapatan (Beban) Lain-lain</h5>

  @if (!empty($otherIncome['rows']))
  <h6 class="mt-2">Pendapatan Lain-Lain</h6>
  <table>
    @foreach ($otherIncome['rows'] as $r)
    <tr>
      <td style="width:18%">{{ $r['kode'] }}</td>
      <td>{{ $r['nama'] }}</td>

      <td class="text-right" style="width:20%">{{ $p($r['nilai']) }}</td>
    </tr>
    @endforeach
    <tr class="fw-bold">
      <td></td><td>Total Pendapatan Lain-Lain</td>
      <td class="text-right">{{ $p($otherIncome['subtotal']) }}</td>
    </tr>
  </table>
  @endif

  @if (!empty($otherExpense['rows']))
  <h6 class="mt-2">Beban Lain-Lain</h6>
  <table>
    @foreach ($otherExpense['rows'] as $r)
    <tr>
      <td style="width:18%">{{ $r['kode'] }}</td>
      <td>{{ $r['nama'] }}</td>

      <td class="text-right" style="width:20%">{{ $p($r['nilai']) }}</td>
    </tr>
    @endforeach
    <tr class="fw-bold">
      <td></td><td>Total Beban Lain-Lain</td>
      <td class="text-right">{{ $p($otherExpense['subtotal']) }}</td>
    </tr>
  </table>
  @endif

  {{-- Total Lain-lain --}}
  <table class="mt-2">
    <tr class="fw-bold bg-light">
      <td style="width:18%"></td>
      <td>Total dari Pendapatan (Beban) Lain-lain</td>
      
      <td class="text-right" style="width:20%">{{ $p($otherNet) }}</td>
    </tr>
  </table>

  {{-- Laba (Rugi) --}}
  <table class="mt-2">
    <tr class="fw-bold bg-dark">
      <td style="width:18%"></td>
      <td>Laba (Rugi)</td>
      
      <td class="text-right" style="width:20%">{{ $p($netProfit) }}</td>
    </tr>
  </table>
</body>
</html>
