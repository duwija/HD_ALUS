@extends('layout.main')

@section('title', 'Laporan Neraca Saldo')

@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
   <div class="card-header">
    <h3 class="card-title font-weight-bold">NERACA SALDO  </h3>
  </div>
  <div class="container pt-3">
    <form action="/jurnal/neracasaldo" method="GET" class="mb-4">
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
            <label class="d-block">&nbsp;</label>
            <button type="submit" class="btn btn-primary w-100">Filter</button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table id="example1" class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Daftar Akun</th>
            <th colspan="2" class="text-center">Saldo Awal</th>
            <th colspan="2" class="text-center">Pergerakan</th>
            <th colspan="3" class="text-center">Saldo Akhir</th>
          </tr>
          <tr>
            <th>Nama Akun</th>
            <th>Debit</th>
            <th>Kredit</th>
            <th>Debit</th>
            <th>Kredit</th>
            <th>Debit</th>
            <th>Kredit</th>
            <th>Balance</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($data as $item)
          <tr>
            <td>{{ $item['kode'] }} - {{ $item['nama'] }}</td>
            <td>{{ number_format($item['saldo_awal_debit'], 2) }}</td>
            <td>{{ number_format($item['saldo_awal_kredit'], 2) }}</td>
            <td>{{ number_format($item['pergerakan_debit'], 2) }}</td>
            <td>{{ number_format($item['pergerakan_kredit'], 2) }}</td>
            <td>{{ number_format($item['saldo_akhir_debit'], 2) }}</td>
            <td>{{ number_format($item['saldo_akhir_debit'], 2) }}</td>
            <th>
              <strong>
                @php
                $balance = $item['saldo_akhir_debit'] - $item['saldo_akhir_kredit'];
                @endphp
                {{ $balance < 0 ? '(' . number_format(abs($balance), 2) . ')' : number_format($balance, 2) }}
              </strong>
            </th>

          </tr>
          @endforeach
        </tbody>
<!--     <tfoot>
      <tr>
        <th>Total</th>
        <th>{{ number_format($data->sum('saldo_awal_debit'), 2) }}</th>
        <th>{{ number_format($data->sum('saldo_awal_kredit'), 2) }}</th>
        <th>{{ number_format($data->sum('pergerakan_debit'), 2) }}</th>
        <th>{{ number_format($data->sum('pergerakan_kredit'), 2) }}</th>
        <th>{{ number_format($data->sum('saldo_akhir_debit'), 2) }}</th>
        <th>{{ number_format($data->sum('saldo_akhir_kredit'), 2) }}</th>

      </tr>
    </tfoot> -->
  </table>
</div>
</div>
</div>
</section>
@endsection
