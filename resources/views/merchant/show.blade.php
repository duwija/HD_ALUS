@extends('layout.main')
@section('title', 'Merchant — ' . $merchant->name)

@section('content')
<section class="content-header">
<div class="row justify-content-center">
<div class="col-xl-10 col-lg-11 col-12">

  {{-- ── OUTER CARD ──────────────────────────────────────────────── --}}
  <div class="card" style="border:1px solid var(--border)">

    {{-- Card Header = Page Header --}}
    <div class="card-header d-flex align-items-center justify-content-between" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
      <div>
        <h5 class="mb-0 font-weight-bold" style="color:var(--text-primary)">
          <i class="fas fa-store mr-2" style="color:var(--brand)"></i>{{ $merchant->name }}
        </h5>
        <small class="text-muted">Merchant Detail</small>
      </div>
      <div>
        <a href="/merchant/{{ $merchant->id }}/edit" class="btn btn-sm btn-primary">
          <i class="fas fa-pen mr-1"></i>Edit
        </a>
        <form action="/merchant/{{ $merchant->id }}" method="POST" class="d-inline item-delete">
          @method('delete')
          @csrf
          <button type="submit" class="btn btn-sm btn-outline-danger ml-1">
            <i class="fas fa-trash mr-1"></i>Delete
          </button>
        </form>
        <a href="/merchant" class="btn btn-sm btn-outline-secondary ml-1">
          <i class="fas fa-arrow-left mr-1"></i>Back
        </a>
      </div>
    </div>

    <div class="card-body" style="background:var(--bg-surface)">

    {{-- ── ROW 1 : Info + Account + Stats ─────────────────────────── --}}
  <div class="row">

    {{-- Merchant Info Card --}}
    <div class="col-lg-5 col-md-6 mb-3">
      <div class="card h-100" style="border:1px solid var(--border);border-top:3px solid var(--brand)">
        <div class="card-header" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
          <h6 class="mb-0 font-weight-bold" style="color:var(--text-primary)">
            <i class="fas fa-info-circle mr-1" style="color:var(--brand)"></i>Merchant Information
          </h6>
        </div>
        <div class="card-body p-0" style="background:var(--bg-surface)">
          <table class="table table-sm table-borderless mb-0">
            <tbody>
              <tr>
                <td style="width:38%;padding-left:1rem;color:var(--text-secondary);white-space:nowrap">
                  <i class="fas fa-building fa-fw text-muted mr-1"></i>Name
                </td>
                <td style="color:var(--text-primary);font-weight:600">{{ $merchant->name }}</td>
              </tr>
              <tr>
                <td style="padding-left:1rem;color:var(--text-secondary)">
                  <i class="fas fa-user fa-fw text-muted mr-1"></i>Contact
                </td>
                <td style="color:var(--text-primary)">{{ $merchant->contact_name ?? '-' }}</td>
              </tr>
              <tr>
                <td style="padding-left:1rem;color:var(--text-secondary)">
                  <i class="fas fa-phone fa-fw text-muted mr-1"></i>Phone
                </td>
                <td>
                  @if($merchant->phone)
                    <a href="https://wa.me/{{ $merchant->phone }}" target="_blank" class="text-success">
                      <i class="fab fa-whatsapp mr-1"></i>{{ $merchant->phone }}
                    </a>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              </tr>
              <tr>
                <td style="padding-left:1rem;color:var(--text-secondary)">
                  <i class="fas fa-map-marker-alt fa-fw text-muted mr-1"></i>Address
                </td>
                <td style="color:var(--text-primary)">{{ $merchant->address ?? '-' }}</td>
              </tr>
              <tr>
                <td style="padding-left:1rem;color:var(--text-secondary)">
                  <i class="fas fa-align-left fa-fw text-muted mr-1"></i>Description
                </td>
                <td style="color:var(--text-primary)">{{ $merchant->description ?? '-' }}</td>
              </tr>
              <tr>
                <td style="padding-left:1rem;padding-bottom:.75rem;color:var(--text-secondary)">
                  <i class="fas fa-cash-register fa-fw text-muted mr-1"></i>Payment Point
                </td>
                <td style="padding-bottom:.75rem">
                  @if($merchant->payment_point == 1)
                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Yes</span>
                  @else
                    <span class="badge badge-secondary">No</span>
                  @endif
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Account Balance + Customer Stats --}}
    <div class="col-lg-7 col-md-6 mb-3">
      <div class="d-flex flex-column h-100">

        {{-- Account Balance --}}
        <div class="card mb-3" style="border:1px solid var(--border);border-top:3px solid #28a745">
          <div class="card-header" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
            <h6 class="mb-0 font-weight-bold" style="color:var(--text-primary)">
              <i class="fas fa-university mr-1" style="color:#28a745"></i>Account Balance
            </h6>
          </div>
          <div class="card-body d-flex align-items-center" style="background:var(--bg-surface)">
            <div>
              <div class="text-muted small mb-1">Account</div>
              <div class="font-weight-bold" style="color:var(--text-primary)">
                @if($merchant->akun_name?->name && $merchant->akun_name?->akun_code)
                  <i class="fas fa-hashtag mr-1 text-muted"></i>{{ $merchant->akun_name->akun_code }}
                  <span class="text-muted mx-1">|</span>{{ $merchant->akun_name->name }}
                @else
                  <span class="text-muted">None</span>
                @endif
              </div>
            </div>
            <div class="ml-auto text-right">
              <div class="text-muted small mb-1">Balance</div>
              <div id="sum_akun" class="font-weight-bold" style="font-size:1.25rem;color:#28a745">
                <i class="fas fa-spinner fa-spin text-muted"></i>
              </div>
            </div>
          </div>
        </div>

        {{-- Customer Statistics (filled via AJAX) --}}
        <div class="card flex-fill" style="border:1px solid var(--border);border-top:3px solid #17a2b8">
          <div class="card-header" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
            <h6 class="mb-0 font-weight-bold" style="color:var(--text-primary)">
              <i class="fas fa-users mr-1" style="color:#17a2b8"></i>Customer Statistics
            </h6>
          </div>
          <div class="card-body" style="background:var(--bg-surface)">
            <div id="spinner" class="text-center py-3" style="display:none">
              <i class="fas fa-spinner fa-spin text-muted fa-lg"></i>
              <div class="text-muted small mt-1">Loading...</div>
            </div>
            <div id="merchant-info"></div>
          </div>
        </div>

      </div>
    </div>

  </div>{{-- end row 1 --}}


  {{-- ── ROW 2 : Customer List ──────────────────────────────────────── --}}
  <div class="card" style="border:1px solid var(--border);border-top:3px solid #007bff">
    <div class="card-header" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
      <h6 class="mb-0 font-weight-bold" style="color:var(--text-primary)">
        <i class="fas fa-list mr-1" style="color:#007bff"></i>Customer List
      </h6>
    </div>
    <div class="card-body" style="background:var(--bg-surface)">

      {{-- Filters --}}
      <input type="hidden" name="id_merchant" id="id_merchant" value="{{ $merchant->id }}">
      <div class="row align-items-end mb-3">
        <div class="col-md-2 col-sm-6 mb-2">
          <label class="small font-weight-bold text-muted mb-1">Filter By</label>
          <select name="filter" id="filter" class="form-control form-control-sm">
            <option value="name">Name</option>
            <option value="customer_id">Customer ID</option>
            <option value="address">Address</option>
            <option value="phone">Phone</option>
            <option value="id_card">Id Card</option>
            <option value="billing_start">Billing Start</option>
            <option value="isolir_date">Isolir Date</option>
          </select>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
          <label class="small font-weight-bold text-muted mb-1">Parameter</label>
          <input class="form-control form-control-sm" type="text" id="parameter" name="parameter" placeholder="Leave blank for all">
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
          <label class="small font-weight-bold text-muted mb-1">Status</label>
          <select name="id_status" id="id_status" class="form-control form-control-sm">
            <option value="">All</option>
            @foreach ($status as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
          <label class="small font-weight-bold text-muted mb-1">Plan</label>
          <select name="id_plan" id="id_plan" class="form-control form-control-sm">
            <option value="">All</option>
            @foreach ($plan as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-1 col-sm-6 mb-2">
          <label class="d-block small mb-1">&nbsp;</label>
          <button type="button" id="customer_filter" class="btn btn-warning btn-sm w-100">
            <i class="fas fa-search mr-1"></i>Filter
          </button>
        </div>
      </div>

      {{-- Plan Group Summary --}}
      <p class="text-muted small font-weight-bold text-uppercase mb-1">
        <i class="fas fa-chart-bar mr-1"></i>Customer by Plan
      </p>
      <table id="table-plan-group" class="table table-sm table-bordered table-striped mb-4">
        <thead class="thead-light">
          <tr>
            <th>#</th>
            <th>Plan Name</th>
            <th class="text-center">Customer Count</th>
          </tr>
        </thead>
      </table>

      {{-- Customer Detail Table --}}
      <p class="text-muted small font-weight-bold text-uppercase mb-1">
        <i class="fas fa-users mr-1"></i>Customer Detail
      </p>
      <table id="table-customer" class="table table-bordered table-striped table-sm">
        <thead class="thead-light">
          <tr>
            <th>#</th>
            <th>Customer ID</th>
            <th>Name</th>
            <th>Address</th>
            <th>Merchant</th>
            <th>Plan</th>
            <th class="text-center">Billing Start</th>
            <th class="text-center">Isolir Date</th>
            <th class="text-center">Status</th>
            <th>Invoice</th>
          </tr>
        </thead>
      </table>

    </div>
  </div>{{-- /card-body --}}
  </div>{{-- /outer card --}}

</div>{{-- col --}}
</div>{{-- row --}}
</section>

@endsection
@section('footer-scripts')
@include('script.merchant')
@endsection
