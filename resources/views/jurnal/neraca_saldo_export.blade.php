@php
// Formatter angka: 1.234,56
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Neraca Saldo</title>
  <style>
    /* ==== Layout kertas untuk PDF (DomPDF) ==== */
    @page {
      size: A4 landscape;      /* Lebar besar biar tabel tidak terpotong */
      margin: 8mm;             /* Margin kecil supaya area cetak maksimal */
    }
    html, body {
      margin: 5;
      padding: 2;
      font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
      font-size: 7px;         /* Boleh turunkan ke 10px jika masih melebar */
      line-height: 1.25;
      color: #000;
    }

    h3 { margin: 0 0 6px 0; }
    .small { color: #444; margin-bottom: 8px; }

    /* ==== Tabel ==== */
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { border: 1px solid #777; padding: 4px 6px; }
    thead { display: table-header-group; }  /* Header akan diulang di setiap halaman */
    tfoot { display: table-footer-group; }
    thead th { background: #f0f0f0; }
    .group-head th { background: #e9ecef; text-align: left; }
    .subtotal td { font-weight: bold; background: #fafafa; }
    .total td { font-weight: bold; background: #f5f5f5; }

    .text-right { text-align: right; }
    .text-left  { text-align: left; }
    .text-center { text-align: center; }
    .nowrap     { white-space: nowrap; }
    .wrap       { word-break: break-word; white-space: normal; }

    /* Hindari row penting terputus */
    tr.group-head, tr.subtotal, tr.total { page-break-inside: avoid; }

    /* ==== Lebar Kolom (9 kolom total) ==== 
       2 kolom teks (kode + nama) + 6 kolom angka + 1 kolom balance */
       .w-code { width: 12%; }    /* Kode Akun */
       .w-name { width: 24%; }    /* Nama Akun */
       .w-num  { width: 8.5%; }   /* 6 angka x 8.5% = 51% */
       .w-bal  { width: 13%; }    /* Balance */
       /* Total: 12 + 24 + 51 + 13 = 100% */
     </style>
   </head>
   <body>
    <h3 class="text-center" >{{ env('COMPANY') }}</h3>
    <h3 class="text-center">Trial Balance</h3>
    <h5 class="text-center">
      Periode:
      {{ \Carbon\Carbon::parse($tanggalAwal)->format('d/m/Y') }}
      &ndash;
      {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}
    </h5>

    <table>
      <thead>
        <tr>
          <th class="text-left  w-code" rowspan="2">Kode Akun</th>
          <th class="text-left  w-name" rowspan="2">Nama Akun</th>
          <th class="text-right w-num" colspan="2">Saldo Awal</th>
          <th class="text-right w-num" colspan="2">Pergerakan</th>
          <th class="text-right w-num" colspan="2">Saldo Akhir</th>
          <th class="text-right w-bal" rowspan="2">Balance</th>
        </tr>
        <tr>
          <th class="text-right w-num">Debit</th>
          <th class="text-right w-num">Kredit</th>
          <th class="text-right w-num">Debit</th>
          <th class="text-right w-num">Kredit</th>
          <th class="text-right w-num">Debit</th>
          <th class="text-right w-num">Kredit</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($grouped as $groupName => $section)
        <!-- Header Grup -->
        <tr class="group-head">
          <th colspan="9">{{ $groupName }}</th>
        </tr>

        <!-- Baris per akun -->
        @foreach ($section['rows'] as $item)
        @php
        $balance = $item['akhir_debit'] - $item['akhir_kredit'];
        @endphp
        <tr>
          <td class="text-left  w-code nowrap">{{ $item['kode'] }}</td>
          <td class="text-left  w-name wrap">{{ $item['nama'] }}</td>
          <td class="text-right w-num nowrap">{{ $fmt($item['awal_debit']) }}</td>
          <td class="text-right w-num nowrap">{{ $fmt($item['awal_kredit']) }}</td>
          <td class="text-right w-num nowrap">{{ $fmt($item['gerak_debit']) }}</td>
          <td class="text-right w-num nowrap">{{ $fmt($item['gerak_kredit']) }}</td>
          <td class="text-right w-num nowrap">{{ $fmt($item['akhir_debit']) }}</td>
          <td class="text-right w-num nowrap">{{ $fmt($item['akhir_kredit']) }}</td>
          <td class="text-right w-bal  nowrap">
            {!! $balance < 0 ? '(' . $fmt(abs($balance)) . ')' : $fmt($balance) !!}
          </td>
        </tr>
        @endforeach

        <!-- Subtotal Grup -->
        @php
        $s = $section['subtotal'];
        $balSec = $s['akhir_debit'] - $s['akhir_kredit'];
        @endphp
        <tr class="subtotal">
          <td class="text-right" colspan="2">Subtotal {{ $groupName }}</td>
          <td class="text-right">{{ $fmt($s['awal_debit']) }}</td>
          <td class="text-right">{{ $fmt($s['awal_kredit']) }}</td>
          <td class="text-right">{{ $fmt($s['gerak_debit']) }}</td>
          <td class="text-right">{{ $fmt($s['gerak_kredit']) }}</td>
          <td class="text-right">{{ $fmt($s['akhir_debit']) }}</td>
          <td class="text-right">{{ $fmt($s['akhir_kredit']) }}</td>
          <td class="text-right">
            {!! $balSec < 0 ? '(' . $fmt(abs($balSec)) . ')' : $fmt($balSec) !!}
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" class="text-center">Tidak ada data untuk periode ini.</td>
        </tr>
        @endforelse
      </tbody>

      <!-- GRAND TOTAL -->
      @php
      $grandBalance = $grand['akhir_debit'] - $grand['akhir_kredit'];
      @endphp
      <tfoot>
        <tr class="total">
          <td class="text-right" colspan="2">TOTAL</td>
          <td class="text-right">{{ $fmt($grand['awal_debit']) }}</td>
          <td class="text-right">{{ $fmt($grand['awal_kredit']) }}</td>
          <td class="text-right">{{ $fmt($grand['gerak_debit']) }}</td>
          <td class="text-right">{{ $fmt($grand['gerak_kredit']) }}</td>
          <td class="text-right">{{ $fmt($grand['akhir_debit']) }}</td>
          <td class="text-right">{{ $fmt($grand['akhir_kredit']) }}</td>
          <td class="text-right">
            {!! $grandBalance < 0 ? '(' . $fmt(abs($grandBalance)) . ')' : $fmt($grandBalance) !!}
          </td>
        </tr>
      </tfoot>
    </table>
  </body>
  </html>
