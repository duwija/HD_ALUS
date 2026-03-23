@extends('layout.main')
@section('title','Laporan Laba Rugi')

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title font-weight-bold m-0">LABA RUGI</h3>
    </div>

    <div class="container col-md-8 pt-3">
      <form action="{{ url('/jurnal/laba-rugi') }}" method="GET" class="mb-4">
        <div class="row align-items-end">
          <div class="col-md-3">
            <div class="form-group">
              <label for="tanggal_awal">Tanggal Awal</label>
              <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control" value="{{ $tanggalAwal }}">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label for="tanggal_akhir">Tanggal Akhir</label>
              <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="{{ $tanggalAkhir }}">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <button type="submit" class="btn btn-primary">Filter</button>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group mb-0 text-md-right mt-3 mt-md-0">
              <a href="{{ url('/jurnal/laba-rugi/export/excel') . '?' . http_build_query(request()->all()) }}"
               class="btn btn-success">
               <i class="fas fa-file-excel"></i> Export Excel
             </a>
             <a href="{{ url('/jurnal/laba-rugi/export/pdf') . '?' . http_build_query(request()->all()) }}"
               class="btn btn-danger">
               <i class="fas fa-file-pdf"></i> Export PDF
             </a>
           </div>
         </div>
       </div>
     </form>
   </div>

   @php
   $fmt = fn($n) => number_format((float)$n, 2, ',', '.');
   $p   = fn($n) => $n < 0 ? '(' . $fmt(abs($n)) . ')' : $fmt($n);
   $line = '--------------------';
   @endphp

   <div class="card-body">
    <div class="row justify-content-center">
      <div class="col-md-8 text-center">
        <h3 class="font-weight-bold m-0">{{ config('app.company', env('COMPANY','Perusahaan')) }}</h3>
        <div><strong>Laba Rugi</strong></div>
        <small class="d-block">
          {{ \Carbon\Carbon::parse($tanggalAwal)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}
        </small>
        <hr>
      </div>

      <div class="col-md-8">
        {{-- Pendapatan --}}
        <h5 class="mb-2"><strong>Pendapatan</strong></h5>
        <table class="table table-sm">
          <tbody>
            @foreach ($pendapatan['rows'] as $r)
            <tr>
              <td style="width:18%">{{ $r['kode'] }}</td>
              <td>{{ $r['nama'] }}</td>
              
              <td class="text-right" style="width:20%">{!! $p($r['nilai']) !!}</td>
            </tr>
            @endforeach
            <tr class="font-weight-bold">
              <td></td><td>Total dari Pendapatan</td>
              
              <td class="text-right">{!! $p($pendapatan['subtotal']) !!}</td>
            </tr>
          </tbody>
        </table>

        {{-- Beban Pokok --}}
        <h5 class="mb-2"><strong>Beban Pokok Pendapatan</strong></h5>
        <table class="table table-sm">
          <tbody>
            @foreach ($cogs['rows'] as $r)
            <tr>
              <td style="width:18%">{{ $r['kode'] }}</td>
              <td>{{ $r['nama'] }}</td>
              
              <td class="text-right" style="width:20%">{!! $p($r['nilai']) !!}</td>
            </tr>
            @endforeach
            <tr class="font-weight-bold">
              <td></td><td>Total dari Beban Pokok Pendapatan</td>
              
              <td class="text-right">{!! $p($cogs['subtotal']) !!}</td>
            </tr>
          </tbody>
        </table>

        {{-- Laba Kotor --}}
        <table class="table table-sm">
          <tr class="font-weight-bold bg-light">
            <td style="width:18%"></td>
            <td>Laba Kotor</td>
            
            <td class="text-right" style="width:20%">{!! $p($grossProfit) !!}</td>
          </tr>
        </table>

        {{-- Beban Operasional --}}
        <h5 class="mb-2"><strong>Beban Operasional</strong></h5>
        @foreach ($opex as $judul => $bagian)
        @if (!empty($bagian['rows']))
        <h6 class="mt-3">{{ $judul }}</h6>
        <table class="table table-sm">
          <tbody>
            @foreach ($bagian['rows'] as $r)
            <tr>
              <td style="width:18%">{{ $r['kode'] }}</td>
              <td>{{ $r['nama'] }}</td>
              
              <td class="text-right" style="width:20%">{!! $p($r['nilai']) !!}</td>
            </tr>
            @endforeach
            <tr class="font-weight-bold">
              <td></td><td>Total {{ $judul }}</td>
              
              <td class="text-right">{!! $p($bagian['subtotal']) !!}</td>
            </tr>
          </tbody>
        </table>
        @endif
        @endforeach

        {{-- Laba Operasional --}}
        <table class="table table-sm">
          <tr class="font-weight-bold bg-light">
            <td style="width:18%"></td>
            <td>Laba Operasional</td>
            
            <td class="text-right" style="width:20%">{!! $p($operatingProfit) !!}</td>
          </tr>
        </table>

        {{-- Lain-lain --}}
        <h5 class="mb-2"><strong>Pendapatan (Beban) Lain-lain</strong></h5>

        @if (!empty($otherIncome['rows']))
        <h6 class="mt-3">Pendapatan Lain-Lain</h6>
        <table class="table table-sm">
          <tbody>
            @foreach ($otherIncome['rows'] as $r)
            <tr>
              <td style="width:18%">{{ $r['kode'] }}</td>
              <td>{{ $r['nama'] }}</td>
              
              <td class="text-right" style="width:20%">{!! $p($r['nilai']) !!}</td>
            </tr>
            @endforeach
            <tr class="font-weight-bold">
              <td></td><td>Total Pendapatan Lain-Lain</td>
              
              <td class="text-right">{!! $p($otherIncome['subtotal']) !!}</td>
            </tr>
          </tbody>
        </table>
        @endif

        @if (!empty($otherExpense['rows']))
        <h6 class="mt-3">Beban Lain-Lain</h6>
        <table class="table table-sm">
          <tbody>
            @foreach ($otherExpense['rows'] as $r)
            <tr>
              <td style="width:18%">{{ $r['kode'] }}</td>
              <td>{{ $r['nama'] }}</td>
              
              <td class="text-right" style="width:20%">{!! $p($r['nilai']) !!}</td>
            </tr>
            @endforeach
            <tr class="font-weight-bold">
              <td></td><td>Total Beban Lain-Lain</td>
              
              <td class="text-right">{!! $p($otherExpense['subtotal']) !!}</td>
            </tr>
          </tbody>
        </table>
        @endif

        {{-- Total Lain-lain --}}
        <table class="table table-sm">
          <tr class="font-weight-bold bg-light">
            <td style="width:18%"></td>
            <td>Total dari Pendapatan (Beban) Lain-lain</td>
            
            <td class="text-right" style="width:20%">{!! $p($otherNet) !!}</td>
          </tr>
        </table>

        {{-- Laba (Rugi) --}}
        <table class="table table-sm">
          <tr class="font-weight-bold bg-secondary text-white">
            <td style="width:18%"></td>
            <td>Laba (Rugi)</td>
            
            <td class="text-right" style="width:20%">{!! $p($netProfit) !!}</td>
          </tr>
        </table>

      </div>
    </div>
  </div>
</div>
</section>
@endsection
