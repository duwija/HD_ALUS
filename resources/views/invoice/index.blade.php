@extends('layout.main')
@section('title','Create Monthly Invoice')
@section('content')
@inject('suminvoice', 'App\Suminvoice')
<section class="content-header">

{{-- ═══ OUTER CARD ══════════════════════════════════════════════════════════ --}}
<div class="card" style="border:1px solid var(--border)">

  {{-- ─── Header ──────────────────────────────────────────────────────────── --}}
  <div class="card-header" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
    <h5 class="mb-0 font-weight-bold" style="color:var(--text-primary)">
      <i class="fas fa-file-invoice mr-2" style="color:var(--brand)"></i>Monthly Invoice
    </h5>
  </div>

  <div class="card-body" style="background:var(--bg-surface)">

    {{-- ─── Info Banner ──────────────────────────────────────────────────────── --}}
    <div class="alert mb-3" style="background:#fff3cd;border:1px solid #ffc107;border-left:4px solid #e0a800;border-radius:6px">
      <div class="d-flex align-items-start" style="gap:.75rem">
        <i class="fas fa-info-circle mt-1" style="color:#856404;font-size:1.1rem;flex-shrink:0"></i>
        <div style="color:#856404">
          <strong>Tentang halaman ini:</strong><br>
          Halaman ini menampilkan daftar customer berstatus <strong>Active</strong> dan <strong>Blocked</strong>
          yang dapat dibuatkan <strong>invoice bulanan</strong>.
          Gunakan filter <em>Invoice Status</em> untuk memilah:
          <ul class="mb-0 mt-1 pl-3">
            <li><span class="badge badge-success">✔ Has Invoice</span> &nbsp;— customer yang <strong>sudah</strong> memiliki invoice bulan ini</li>
            <li><span class="badge badge-danger">✘ No Invoice</span> &nbsp;— customer yang <strong>belum</strong> memiliki invoice bulan ini (perlu dibuat)</li>
          </ul>
        </div>
      </div>
    </div>

    {{-- ─── Filter Bar ───────────────────────────────────────────────────────── --}}
    <div class="card mb-3" style="border:1px solid var(--border);background:var(--bg-surface-2)">
      <div class="card-header py-2" style="border-bottom:1px solid var(--border)">
        <span class="font-weight-bold small" style="color:var(--text-secondary)">
          <i class="fas fa-filter mr-1"></i>Filter Customer
        </span>
      </div>
      <div class="card-body py-2">
        <input type="hidden" id="search_var" name="search_var" value="{{ $search_var }}">
        <div class="d-flex flex-wrap align-items-end" style="gap:.5rem">

          <div>
            <label class="small text-muted d-block mb-0">Filter By</label>
            <select name="filter" id="filter" class="form-control form-control-sm" style="width:140px">
              <option value="name">Name</option>
              <option value="customer_id">Customer ID</option>
              <option value="address">Address</option>
              <option value="phone">Phone</option>
              <option value="id_card">Id Card</option>
              <option value="infra">Infrastructure</option>
              <option value="link_type">Link Type</option>
              <option value="snote">Tag</option>
            </select>
          </div>

          <div style="flex:1;min-width:180px">
            <label class="small text-muted d-block mb-0">Parameter</label>
            <input class="form-control form-control-sm" type="text" id="parameter" name="parameter"
              placeholder="Kosongkan untuk semua">
          </div>

          <div>
            <label class="small text-muted d-block mb-0">Status</label>
            <select name="id_status" id="id_status" class="form-control form-control-sm" style="width:130px">
              <option value="">All</option>
              @foreach ($status as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="small text-muted d-block mb-0">Plan</label>
            <select name="id_plan" id="id_plan" class="form-control form-control-sm" style="width:130px">
              <option value="">All</option>
              @foreach ($plan as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="small text-muted d-block mb-0">Invoice Status</label>
            <select name="has_invoice" id="has_invoice" class="form-control form-control-sm" style="width:150px">
              <option value="">All</option>
              <option value="yes">✔ Has Invoice</option>
              <option value="no">✘ No Invoice</option>
            </select>
          </div>

          <div>
            <label class="d-block mb-0" style="visibility:hidden">x</label>
            <button type="button" id="invoice_filter" name="invoice_filter" class="btn btn-primary btn-sm">
              <i class="fas fa-search mr-1"></i>Filter
            </button>
          </div>

        </div>
      </div>
    </div>

    {{-- ─── Table ────────────────────────────────────────────────────────────── --}}
    <div class="table-responsive">
      <table id="table-invoice" class="table table-bordered table-striped table-sm">
        <thead class="thead-light">
          <tr>
            <th>#</th>
            <th>Customer ID</th>
            <th>Name</th>
            <th>Address</th>
            <th>Plan</th>
            <th class="text-center">Start Billing</th>
            <th class="text-center">Status</th>
            <th class="text-center">Invoice Bulan Ini</th>
          </tr>
        </thead>
      </table>
    </div>

  </div>{{-- /card-body --}}
</div>{{-- /card --}}

</section>
@endsection
@section('footer-scripts')
@include('script.invoice')
@endsection
