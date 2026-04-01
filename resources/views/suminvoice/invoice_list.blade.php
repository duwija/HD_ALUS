@extends('layout.main')
@section('title','Invoice List')
@section('content')
<section class="content-header">

{{-- ═══ OUTER CARD ══════════════════════════════════════════════════════════ --}}
<div class="card" style="border:1px solid var(--border)">

  {{-- ─── Header ──────────────────────────────────────────────────────────── --}}
  <div class="card-header" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
    <h5 class="mb-0 font-weight-bold" style="color:var(--text-primary)">
      <i class="fas fa-file-invoice-dollar mr-2" style="color:var(--brand)"></i>Invoice List
    </h5>
  </div>

  <div class="card-body" style="background:var(--bg-surface)">

    {{-- ─── Filter Bar ───────────────────────────────────────────────────────── --}}
    <div class="card mb-3" style="border:1px solid var(--border);background:var(--bg-surface-2)">
      <div class="card-header py-2" style="border-bottom:1px solid var(--border)">
        <span class="font-weight-bold small" style="color:var(--text-secondary)">
          <i class="fas fa-filter mr-1"></i>Filter
        </span>
      </div>
      <div class="card-body py-2">

        {{-- Row 1: Invoice date, merchant, status, type, parameter --}}
        <div class="d-flex flex-wrap align-items-end" style="gap:.5rem">

          <div>
            <label class="small text-muted d-block mb-0">Invoice Date Start</label>
            <div class="input-group input-group-sm date" id="dp-dateStart" data-target-input="nearest">
              <input type="text" name="dateStart" class="form-control datetimepicker-input"
                data-target="#dp-dateStart" value="{{ date('Y-m-01') }}" style="width:120px">
              <div class="input-group-append" data-target="#dp-dateStart" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar fa-sm"></i></div>
              </div>
            </div>
          </div>

          <div>
            <label class="small text-muted d-block mb-0">Invoice Date End</label>
            <div class="input-group input-group-sm date" id="dp-dateEnd" data-target-input="nearest">
              <input type="text" name="dateEnd" class="form-control datetimepicker-input"
                data-target="#dp-dateEnd" value="{{ date('Y-m-d') }}" style="width:120px">
              <div class="input-group-append" data-target="#dp-dateEnd" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar fa-sm"></i></div>
              </div>
            </div>
          </div>

          <div>
            <label class="small text-muted d-block mb-0">Merchant</label>
            <select name="id_merchant" id="id_merchant" class="form-control form-control-sm" style="width:140px">
              <option value="">All Merchant</option>
              @foreach ($merchant as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="small text-muted d-block mb-0">Status</label>
            <select name="paymentStatus" id="paymentStatus" class="form-control form-control-sm" style="width:110px">
              <option value="">All</option>
              <option value="0">Unpaid</option>
              <option value="1">Paid</option>
              <option value="2">Cancel</option>
            </select>
          </div>

          <div>
            <label class="small text-muted d-block mb-0">Invoice Type</label>
            <select name="invoicetype" id="invoicetype" class="form-control form-control-sm" style="width:130px">
              <option value="0">All</option>
              <option value="1">Monthly Fee</option>
            </select>
          </div>

          <div style="flex:1;min-width:200px">
            <label class="small text-muted d-block mb-0">Search</label>
            <input type="text" name="parameter" id="parameter"
              class="form-control form-control-sm"
              placeholder="Invoice No | CID | Name">
          </div>

          <div>
            <label class="d-block mb-0" style="visibility:hidden">x</label>
            <button type="button" class="btn btn-primary btn-sm" id="invoice_filter">
              <i class="fas fa-search mr-1"></i>Filter
            </button>
          </div>

        </div>

        {{-- Row 2: Payment date & received by (shown only when status = PAID) --}}
        <div class="d-flex flex-wrap align-items-end mt-2" id="updatedByLabel" style="gap:.5rem">
          <div>
            <label class="small text-muted d-block mb-0">Payment Date Start</label>
            <div class="input-group input-group-sm date" id="dp-payDateStart" data-target-input="nearest">
              <input type="text" name="paymentDateStart" class="form-control datetimepicker-input"
                data-target="#dp-payDateStart" style="width:120px">
              <div class="input-group-append" data-target="#dp-payDateStart" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar fa-sm"></i></div>
              </div>
            </div>
          </div>
          <div>
            <label class="small text-muted d-block mb-0">Payment Date End</label>
            <div class="input-group input-group-sm date" id="dp-payDateEnd" data-target-input="nearest">
              <input type="text" name="paymentDateEnd" class="form-control datetimepicker-input"
                data-target="#dp-payDateEnd" style="width:120px">
              <div class="input-group-append" data-target="#dp-payDateEnd" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar fa-sm"></i></div>
              </div>
            </div>
          </div>
          <div>
            <label class="small text-muted d-block mb-0">Received By</label>
            <select name="updatedBy" id="updatedBy" class="form-control form-control-sm" style="width:160px">
              <option value="">All</option>
              @foreach($groupedTransactions as $transaction)
                @if(is_numeric($transaction->updated_by))
                  <option value="{{ $transaction->updated_by }}">{{ $transaction->user->name }}</option>
                @else
                  <option value="{{ $transaction->updated_by }}">{{ $transaction->updated_by }}</option>
                @endif
              @endforeach
            </select>
          </div>
        </div>

      </div>
    </div>

    {{-- ─── Summary Stat Cards ───────────────────────────────────────────────── --}}
    <div class="row mb-3">

      {{-- Total --}}
      <div class="col-6 col-md-3 mb-2">
        <div class="p-3 rounded h-100" style="background:var(--bg-surface-2);border:1px solid var(--border)">
          <div class="text-muted small mb-1"><i class="fas fa-file-invoice mr-1"></i>Total Invoice</div>
          <div class="font-weight-bold" style="font-size:1.1rem;color:var(--text-primary)">
            Rp <span id="total">0</span>
          </div>
        </div>
      </div>

      {{-- Paid --}}
      <div class="col-6 col-md-3 mb-2">
        <div class="p-3 rounded h-100" style="background:#d4edda;border:1px solid #c3e6cb">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small" style="color:#155724"><i class="fas fa-check-circle mr-1"></i>Total Paid</span>
            <span id="pct-paid" class="badge badge-success">0%</span>
          </div>
          <div class="font-weight-bold" style="font-size:1.1rem;color:#155724">
            Rp <span id="total_paid">0</span>
          </div>
          <div id="fee_counter" class="text-muted" style="font-size:.75rem"></div>
        </div>
      </div>

      {{-- Unpaid --}}
      <div class="col-6 col-md-3 mb-2">
        <div class="p-3 rounded h-100" style="background:#f8d7da;border:1px solid #f5c6cb">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small" style="color:#721c24"><i class="fas fa-clock mr-1"></i>Total Unpaid</span>
            <span id="pct-unpaid" class="badge badge-danger">0%</span>
          </div>
          <div class="font-weight-bold" style="font-size:1.1rem;color:#721c24">
            Rp <span id="unpaid_payment">0</span>
          </div>
        </div>
      </div>

      {{-- Cancel --}}
      <div class="col-6 col-md-3 mb-2">
        <div class="p-3 rounded h-100" style="background:#e9ecef;border:1px solid #dee2e6">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small" style="color:#495057"><i class="fas fa-ban mr-1"></i>Total Cancel</span>
            <span id="pct-cancel" class="badge badge-secondary">0%</span>
          </div>
          <div class="font-weight-bold" style="font-size:1.1rem;color:#495057">
            Rp <span id="cancel_payment">0</span>
          </div>
        </div>
      </div>

    </div>

    {{-- ─── Progress Bar ─────────────────────────────────────────────────────── --}}
    <div class="mb-3" id="inv-progress-wrap" style="display:none">
      <div class="d-flex justify-content-between small text-muted mb-1">
        <span>Payment Distribution (by amount)</span>
      </div>
      <div class="progress" style="height:14px;border-radius:7px">
        <div id="bar-paid"   class="progress-bar bg-success"   style="width:0%"></div>
        <div id="bar-unpaid" class="progress-bar bg-danger"    style="width:0%"></div>
        <div id="bar-cancel" class="progress-bar bg-secondary" style="width:0%"></div>
      </div>
      <div class="d-flex mt-1" style="gap:1.2rem;font-size:.75rem">
        <span><span class="badge badge-success mr-1">&nbsp;</span>Paid <span id="pct-paid-leg">0</span>%</span>
        <span><span class="badge badge-danger  mr-1">&nbsp;</span>Unpaid <span id="pct-unpaid-leg">0</span>%</span>
        <span><span class="badge badge-secondary mr-1">&nbsp;</span>Cancel <span id="pct-cancel-leg">0</span>%</span>
      </div>
    </div>

    {{-- ─── Table ────────────────────────────────────────────────────────────── --}}
    <div class="table-responsive">
      <table id="table-invoice-list" class="table table-bordered table-striped table-sm text-xs">
        <thead class="thead-light">
          <tr>
            <th>#</th>
            <th>Invoice Date</th>
            <th>Invoice No</th>
            <th class="text-center">CID</th>
            <th>Name</th>
            <th>Merchant</th>
            <th>Address</th>
            <th class="text-center">Period</th>
            <th>Due Date</th>
            <th class="text-center">Tax</th>
            <th class="text-right">Total Amount</th>
            <th class="text-center">Status</th>
            <th>Received By</th>
            <th>Transaction Date</th>
          </tr>
        </thead>
      </table>
    </div>

  </div>{{-- /card-body --}}
</div>{{-- /card --}}

</section>
@endsection
@section('footer-scripts')
@include('script.invoice_list')
@endsection
