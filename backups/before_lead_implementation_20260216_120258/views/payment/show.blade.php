@extends('layout.main')
@section('title','Invoice List')

@section('content')
@inject('invoicecalc', 'App\Invoice')

<style>
  .modern-input {
    border-radius: 10px;
  }

  .modern-input:focus {
    box-shadow: 0 0 8px rgba(25, 135, 84, 0.5);
    border-color: #198754;
  }

  .modern-btn {
    border-radius: 10px;
    transition: all 0.3s ease-in-out;
  }

  .modern-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(25, 135, 84, 0.4);
  }
</style>

<section class="content-header">
  <div class="row justify-content-center">

    {{-- FORM CARI --}}
    <div class="col-lg-5 col-md-8 col-sm-10 col-12">
      <h2 class="text-center mt-4">Cari Data Pelanggan</h2>
      <form role="form" method="post" action="/payment/show" enctype="multipart/form-data" class="mt-4">
        @csrf

        <div class="row g-2">
          <div class="col-md-4 col-12 mb-2 mb-md-0">
            <select name="filter" id="filter" class="form-control form-control-lg modern-input" required>
              <option value="customer_id">CID / Kode Pelanggan</option>
              <option value="name">Nama sesuai KTP</option>
              <option value="phone">No Tlp</option>
            </select>
          </div>

          <div class="col-md-6 col-12 mb-2 mb-md-0">
            <input name="parameter" id="parameter" type="search" class="form-control form-control-lg modern-input" placeholder="Masukkan kata kunci" required>
          </div>

          <div class="col-md-2 col-12 d-grid">
            <button type="submit" class="btn btn-success btn-lg modern-btn">
              <i class="fa fa-search"></i>
            </button>
          </div>
        </div>
      </form>
    </div>

    {{-- BIODATA PELANGGAN --}}
    <div class="card card-primary card-outline  col-11 mt-5">
      <div class="card-header">
        <h3 class="card-title">Profil Pelanggan</h3>
      </div>

      <div class="card-body row">
        <div class="col-md-6 col-12 mb-3">
          <table class="table table-borderless table-sm">
            <tbody>
              <tr><th class="text-end" width="40%">CID / Kode:</th><td><strong>{{ $customer->customer_id }}</strong></td></tr>
              <tr><th class="text-end">Nama:</th><td><strong>{{ $customer->name }}</strong></td></tr>
              <tr><th class="text-end">No Tlp:</th><td>{{ $customer->phone }}</td></tr>
              <tr><th class="text-end">Alamat:</th><td>{{ $customer->address }}</td></tr>
            </tbody>
          </table>
        </div>

        <div class="col-md-6 col-12 mb-3">
          <table class="table table-borderless table-sm">
            <tbody>
              <tr><th class="text-end" width="30%">Status:</th><td><strong>{{ $customer->status_name }}</strong></td></tr>
              <tr><th class="text-end">Paket:</th><td><strong>{{ $customer->plan_name }}</strong></td></tr>
              <tr><th class="text-end">NPWP:</th><td><strong>{{ strtoupper($customer->npwp) }}</strong></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      {{-- TABEL INVOICE --}}
      <div class="card-body table-responsive">
        <table id="example1" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>No Invoice</th>
              <th>Tanggal</th>
              <th>Total</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($suminvoice as $suminvoice)
            @php
            $sub_total = $invoicecalc->balanceinv($suminvoice->tempcode, $customer->id);
            $tax = $suminvoice->tax;
            $pph = $sub_total * $suminvoice->pph / 100;
            $sum_total = ($sub_total * $tax / 100) + $sub_total - $pph;
            @endphp

            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>{{ $suminvoice->number }}</td>
              <td>{{ $suminvoice->date }}</td>
              <td>{{ number_format($sum_total, 0, ',', '.') }}</td>
              <td>
                @if($suminvoice->payment_status == 0)
                <span class="badge bg-danger">Belum Dibayar</span>
                @elseif($suminvoice->payment_status == 1)
                <span class="badge bg-secondary">Sudah Dibayar</span>
                @elseif($suminvoice->payment_status == 2)
                <span class="badge bg-warning">Dibatalkan</span>
                @endif
              </td>
              <td>
                <a href="/payment/{{ $suminvoice->tempcode }}" class="btn btn-primary btn-sm">
                  <i class="fa fa-list-ul"></i> Tampilkan
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
@endsection
