@extends('layout.main')
@section('title','Accounting')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Ringkasan Invoice &amp; Pembayaran</h3>
    </div>

    @if ($merchantScopeEmpty)
      <div class="alert alert-warning m-3 mb-0">
        <i class="fas fa-exclamation-triangle"></i>
        Belum ada merchant yang di-set untuk akun ini. Hubungi Super Admin untuk mengatur cakupan merchant di halaman Edit Tenant (admin pusat).
      </div>
    @endif

    <div class="card-body">
      <form method="GET" action="{{ route('adminbilling.accounting') }}" class="row mb-4">
        <div class="form-group col-md-3">
          <label>Dari Tanggal</label>
          <input type="date" name="dateStart" class="form-control" value="{{ $dateStart }}">
        </div>
        <div class="form-group col-md-3">
          <label>Sampai Tanggal</label>
          <input type="date" name="dateEnd" class="form-control" value="{{ $dateEnd }}">
        </div>
        <div class="form-group col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-warning btn-block">
            <i class="fas fa-filter"></i> Filter
          </button>
        </div>
      </form>

      <div class="row mb-4">
        <div class="col-md-4 mb-3">
          <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
              <div class="h4 mb-0">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
              <small>Total Tagihan (periode ini)</small>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
              <div class="h4 mb-0">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
              <small>Total Dibayar (periode ini)</small>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card bg-warning text-white h-100">
            <div class="card-body text-center">
              <div class="h4 mb-0">{{ number_format($totalBelumLunas) }}</div>
              <small>Invoice Belum Lunas</small>
            </div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm">
          <thead class="thead-dark">
            <tr>
              <th>Number</th>
              <th>Customer</th>
              <th>Tanggal</th>
              <th>Jatuh Tempo</th>
              <th class="text-right">Total</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($invoices as $inv)
            <tr>
              <td>
                @if ($inv->tempcode)
                  <a href="{{ url('/suminvoice/' . $inv->tempcode . '/viewinvoice') }}" target="_blank" rel="noopener">{{ $inv->number }}</a>
                @else
                  {{ $inv->number }}
                @endif
              </td>
              <td>{{ $inv->customer->name ?? '-' }} <small class="text-muted">({{ $inv->customer->customer_id ?? '-' }})</small></td>
              <td>{{ $inv->date }}</td>
              <td>{{ $inv->due_date }}</td>
              <td class="text-right">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
              <td>
                @if ($inv->payment_status == 1)
                  <span class="badge badge-success">Lunas</span>
                @elseif ($inv->payment_status == 2)
                  <span class="badge badge-secondary">Dibatalkan</span>
                @else
                  <span class="badge badge-warning">Belum Lunas</span>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Tidak ada invoice pada periode ini.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <small class="text-muted">Menampilkan maksimal 200 invoice terbaru pada periode yang dipilih.</small>
    </div>
  </div>

</section>
@endsection
