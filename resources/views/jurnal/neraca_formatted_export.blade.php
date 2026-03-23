@php
use Carbon\Carbon;
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');

// Header tanggal: jika ada $tanggalAwal gunakan rentang, kalau tidak pakai "Per <tanggalAkhir>"
  $headerTanggal = (isset($tanggalAwal) && $tanggalAwal)
  ? 'Periode: ' . Carbon::parse($tanggalAwal)->format('d/m/Y') . ' – ' . Carbon::parse($tanggalAkhir)->format('d/m/Y')
  : 'Per ' . Carbon::parse($tanggalAkhir)->format('d/m/Y');
  @endphp
  <!DOCTYPE html>
  <html lang="id">
  <head>
    <meta charset="UTF-8">
    <title>Neraca</title>
    <style>
      /* PDF-friendly (DomPDF) dan tetap oke untuk Excel (FromView) */
      @page { size: A4 portrait; margin: 15mm; }
      html, body { margin:10; padding:5; font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 11px; line-height:1.3; color:#000; }
      h3 { margin:0 0 4px 0; }
      .small { color:#444; margin-bottom:10px; }
      .col { width: 49%; display:inline-block; vertical-align: top; }
      table { width:100%; border-collapse: collapse; }
      td { padding: 3px 4px; }
      .text-right { text-align:right; }
      .text-center { text-align: center; }
      .font-bold { font-weight:bold; }
      .bg-light { background:#f5f5f5; }
      .section { margin-top: 10px; }
      .title { font-weight:bold; margin: 6px 0 4px; }
      .line { width:25%; }
      .amt  { width:20%; }
      .code { width:18%; }
    </style>
  </head>
  <body>
    <h3 class="text-center">{{ $company ?? config('app.company', env('COMPANY','Perusahaan')) }}</h3>
    <div class="small text-center ">Neraca (Balance Sheet) — {{ $headerTanggal }}</div>

    <div class="col">
      <div class="title">Aset</div>

      @foreach ($aset as $judul => $bagian)
      @if (!empty($bagian['rows']))
      <div class="section"><strong>{{ $judul }}</strong></div>
      <table>
        <tbody>
          @foreach ($bagian['rows'] as $r)
          <tr>
            <td class="code">{{ $r['kode'] }}</td>
            <td>{{ $r['nama'] }}</td>

            <td class="text-right amt">{!! $r['nilai'] < 0 ? '(' . $fmt(abs($r['nilai'])) . ')' : $fmt($r['nilai']) !!}</td>
          </tr>
          @endforeach
          <tr class="font-bold bg-light">
            <td></td>
            <td>Total {{ $judul }}</td>

            <td class="text-right amt">{!! $bagian['subtotal'] < 0 ? '(' . $fmt(abs($bagian['subtotal'])) . ')' : $fmt($bagian['subtotal']) !!}</td>
          </tr>
        </tbody>
      </table>
      @endif
      @endforeach

      <table>
        <tr class="font-bold bg-light">
          <td class="code"></td>
          <td>Total Aset</td>

          <td class="text-right amt">{!! ($totalAset ?? 0) < 0 ? '(' . $fmt(abs($totalAset ?? 0)) . ')' : $fmt($totalAset ?? 0) !!}</td>
        </tr>
      </table>
    </div>

    <div class="col" style="margin-left:2%">
      <div class="title">Liabilitas dan Modal</div>

      @foreach ($liabilitas as $judul => $bagian)
      @if (!empty($bagian['rows']))
      <div class="section"><strong>{{ $judul }}</strong></div>
      <table>
        <tbody>
          @foreach ($bagian['rows'] as $r)
          <tr>
            <td class="code">{{ $r['kode'] }}</td>
            <td>{{ $r['nama'] }}</td>

            <td class="text-right amt">{!! $r['nilai'] < 0 ? '(' . $fmt(abs($r['nilai'])) . ')' : $fmt($r['nilai']) !!}</td>
          </tr>
          @endforeach
          <tr class="font-bold bg-light">
            <td></td>
            <td>Total {{ $judul }}</td>

            <td class="text-right amt">{!! $bagian['subtotal'] < 0 ? '(' . $fmt(abs($bagian['subtotal'])) . ')' : $fmt($bagian['subtotal']) !!}</td>
          </tr>
        </tbody>
      </table>
      @endif
      @endforeach

      @foreach ($ekuitas as $judul => $bagian)
      @if (!empty($bagian['rows']))
      <div class="section"><strong>{{ $judul }}</strong></div>
      <table>
        <tbody>
          @foreach ($bagian['rows'] as $r)
          <tr>
            <td class="code">{{ $r['kode'] }}</td>
            <td>{{ $r['nama'] }}</td>

            <td class="text-right amt">{!! $r['nilai'] < 0 ? '(' . $fmt(abs($r['nilai'])) . ')' : $fmt($r['nilai']) !!}</td>
          </tr>
          @endforeach
          <tr class="font-bold bg-light">
            <td></td>
            <td>Total {{ $judul }}</td>

            <td class="text-right amt">{!! $bagian['subtotal'] < 0 ? '(' . $fmt(abs($bagian['subtotal'])) . ')' : $fmt($bagian['subtotal']) !!}</td>
          </tr>
        </tbody>
      </table>
      @endif
      @endforeach

      @php $totalRight = ($totalLiabilitas ?? 0) + ($totalEkuitas ?? 0); @endphp
      <table>
        <tr class="font-bold bg-light">
          <td class="code"></td>
          <td>Total Liabilitas dan Modal</td>

          <td class="text-right amt">{!! $totalRight < 0 ? '(' . $fmt(abs($totalRight)) . ')' : $fmt($totalRight) !!}</td>
        </tr>
      </table>
    </div>
  </body>
  </html>
