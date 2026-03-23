@extends('layout.main')
@section('title','Transaction List')
@section('content')
<section class="content-header">

{{-- ═══ OUTER CARD ══════════════════════════════════════════════════════════ --}}
<div class="card" style="border:1px solid var(--border)">

  {{-- ─── Header ──────────────────────────────────────────────────────────── --}}
  <div class="card-header" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
    <h5 class="mb-0 font-weight-bold" style="color:var(--text-primary)">
      <i class="fas fa-exchange-alt mr-2" style="color:var(--brand)"></i>Transaction List
    </h5>
  </div>

  <div class="card-body" style="background:var(--bg-surface)">

    {{-- ─── Top Stats + Chart ──────────────────────────────────────────────── --}}
    <div class="row mb-3">

      {{-- Stat cards --}}
      <div class="col-lg-6 mb-3 mb-lg-0">
        <div class="row">

          <div class="col-6 mb-2">
            <div class="p-3 rounded h-100" style="background:#e9ecef;border:1px solid #dee2e6">
              <div class="small mb-1" style="color:#495057">
                <i class="fas fa-wallet mr-1"></i>Total Receivable
              </div>
              <div class="font-weight-bold" style="font-size:1.1rem;color:#343a40">
                Rp {{ number_format($totalReceivable,0,',','.') }}
              </div>
            </div>
          </div>

          <div class="col-6 mb-2">
            <div class="p-3 rounded h-100" style="background:#d4edda;border:1px solid #c3e6cb">
              <div class="small mb-1" style="color:#155724">
                <i class="fas fa-university mr-1"></i>This Month
              </div>
              <div class="font-weight-bold" style="font-size:1.1rem;color:#155724">
                Rp {{ number_format($totalTransactionThisMonth,0,',','.') }}
              </div>
            </div>
          </div>

          <div class="col-6 mb-2">
            <div class="p-3 rounded h-100" style="background:#d1ecf1;border:1px solid #bee5eb">
              <div class="small mb-1" style="color:#0c5460">
                <i class="fas fa-chart-line mr-1"></i>This Week
              </div>
              <div class="font-weight-bold" style="font-size:1.1rem;color:#0c5460">
                Rp {{ number_format($totalTransactionThisWeek,0,',','.') }}
              </div>
            </div>
          </div>

          <div class="col-6 mb-2">
            <div class="p-3 rounded h-100" style="background:#f8d7da;border:1px solid #f5c6cb">
              <div class="small mb-1" style="color:#721c24">
                <i class="fas fa-chart-bar mr-1"></i>Today
              </div>
              <div class="font-weight-bold" style="font-size:1.1rem;color:#721c24">
                Rp {{ number_format($totalPaymentToday,0,',','.') }}
              </div>
            </div>
          </div>

        </div>
      </div>

      {{-- Chart --}}
      <div class="col-lg-6">
        <div class="card mb-0" style="border:1px solid var(--border);background:var(--bg-surface-2)">
          <div class="card-body p-2">
            <canvas id="dailyTransactionChart" style="width:100%;height:220px"></canvas>
          </div>
        </div>
      </div>

    </div>

    {{-- ─── Filter Bar ───────────────────────────────────────────────────────── --}}
    <div class="card mb-3" style="border:1px solid var(--border);background:var(--bg-surface-2)">
      <div class="card-header py-2" style="border-bottom:1px solid var(--border)">
        <span class="font-weight-bold small" style="color:var(--text-secondary)">
          <i class="fas fa-filter mr-1"></i>Filter Transaksi
        </span>
      </div>
      <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-end" style="gap:.5rem">

          <div>
            <label class="small text-muted d-block mb-0">Transaction Start</label>
            <div class="input-group input-group-sm date" id="dp-txStart" data-target-input="nearest">
              <input type="text" name="dateStart" class="form-control datetimepicker-input"
                data-target="#dp-txStart" value="{{ date('Y-m-d') }}" style="width:120px">
              <div class="input-group-append" data-target="#dp-txStart" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar fa-sm"></i></div>
              </div>
            </div>
          </div>

          <div>
            <label class="small text-muted d-block mb-0">Transaction End</label>
            <div class="input-group input-group-sm date" id="dp-txEnd" data-target-input="nearest">
              <input type="text" name="dateEnd" class="form-control datetimepicker-input"
                data-target="#dp-txEnd" value="{{ date('Y-m-d') }}" style="width:120px">
              <div class="input-group-append" data-target="#dp-txEnd" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar fa-sm"></i></div>
              </div>
            </div>
          </div>

          <div>
            <label class="small text-muted d-block mb-0">Merchant</label>
            <select name="id_merchant" id="id_merchant" class="form-control form-control-sm select2" style="width:140px">
              <option value="">All Merchant</option>
              @foreach ($merchant as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="small text-muted d-block mb-0">Receive By</label>
            <select name="updatedBy" id="updatedBy" class="form-control form-control-sm select2" style="width:150px">
              <option value="">All</option>
              @foreach($user as $transaction)
                @if(is_numeric($transaction->updated_by))
                  <option value="{{ $transaction->updated_by }}">{{ $transaction->user->name }}</option>
                @else
                  <option value="{{ $transaction->updated_by }}">{{ $transaction->updated_by }}</option>
                @endif
              @endforeach
            </select>
          </div>

          <div>
            <label class="small text-muted d-block mb-0">Kas Bank</label>
            <select name="kasbank" id="kasbank" class="form-control form-control-sm select2" style="width:150px">
              <option value="">All</option>
              @foreach ($kasbank as $akun)
              <option value="{{ $akun->akun_code }}">{{ $akun->name }}</option>
              @endforeach
            </select>
          </div>

          <div style="flex:1;min-width:180px">
            <label class="small text-muted d-block mb-0">Search</label>
            <input type="text" name="parameter" id="parameter"
              class="form-control form-control-sm"
              placeholder="Invoice No | CID | Name">
          </div>

          <div>
            <label class="d-block mb-0" style="visibility:hidden">x</label>
            <button type="button" class="btn btn-primary btn-sm" id="transaction_filter">
              <i class="fas fa-search mr-1"></i>Filter
            </button>
          </div>

        </div>
      </div>
    </div>

    {{-- ─── Summary Tables ──────────────────────────────────────────────────── --}}
    <div class="row mb-3">

      {{-- By Recipient --}}
      <div class="col-lg-4 col-md-6 mb-3">
        <div class="card mb-0 h-100" style="border:1px solid var(--border)">
          <div class="card-header py-2" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
            <span class="font-weight-bold small" style="color:var(--text-primary)">
              <i class="fas fa-user mr-1" style="color:var(--brand)"></i>By Recipient
            </span>
          </div>
          <div class="table-responsive" style="max-height:300px;overflow-y:auto">
            <table class="table table-bordered table-striped table-sm mb-0">
              <thead class="thead-light">
                <tr>
                  <th>#</th>
                  <th>Received By</th>
                  <th class="text-right">Amount*</th>
                  <th class="text-right">Fee</th>
                  <th class="text-right">Payment</th>
                </tr>
              </thead>
              <tbody id="groupedTransactionsUser"></tbody>
              <tfoot>
                <tr class="font-weight-bold">
                  <th colspan="2" class="text-right">Total</th>
                  <th id="totalAmount" class="text-right">0</th>
                  <th id="totalFee" class="text-right">0</th>
                  <th id="totalPayment" class="text-right">0</th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      {{-- By Kasbank --}}
      <div class="col-lg-4 col-md-6 mb-3">
        <div class="card mb-0 h-100" style="border:1px solid var(--border)">
          <div class="card-header py-2" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
            <span class="font-weight-bold small" style="color:var(--text-primary)">
              <i class="fas fa-piggy-bank mr-1" style="color:var(--brand)"></i>By Kas Bank
            </span>
          </div>
          <div class="table-responsive" style="max-height:300px;overflow-y:auto">
            <table class="table table-bordered table-striped table-sm mb-0">
              <thead class="thead-light">
                <tr>
                  <th>#</th>
                  <th>Kas Bank</th>
                  <th class="text-right">Total Payment</th>
                </tr>
              </thead>
              <tbody id="groupedTransactionsKasbank"></tbody>
              <tfoot>
                <tr class="font-weight-bold">
                  <th colspan="2" class="text-right">Total</th>
                  <th id="totalPaymentKasbank" class="text-right">0</th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      {{-- By Merchant --}}
      <div class="col-lg-4 col-md-6 mb-3">
        <div class="card mb-0 h-100" style="border:1px solid var(--border)">
          <div class="card-header py-2" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
            <span class="font-weight-bold small" style="color:var(--text-primary)">
              <i class="fas fa-store mr-1" style="color:var(--brand)"></i>By Merchant
            </span>
          </div>
          <div class="table-responsive" style="max-height:300px;overflow-y:auto">
            <table class="table table-bordered table-striped table-sm mb-0">
              <thead class="thead-light">
                <tr>
                  <th>#</th>
                  <th>Merchant</th>
                  <th class="text-right">Total Payment</th>
                </tr>
              </thead>
              <tbody id="groupedTransactionsMerchant"></tbody>
              <tfoot>
                <tr class="font-weight-bold">
                  <th colspan="2" class="text-right">Total</th>
                  <th id="totalPaymentMerchant" class="text-right">0</th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

    </div>

    {{-- ─── Transaction Summary Stat Bars ──────────────────────────────────────── --}}
    <div class="row mb-3">
      <div class="col-md-4 mb-2">
        <div class="p-3 rounded" style="background:#d4edda;border:1px solid #c3e6cb">
          <div class="small mb-1" style="color:#155724"><i class="fas fa-coins mr-1"></i>Total Amount</div>
          <div class="font-weight-bold" style="font-size:1.1rem;color:#155724">
            Rp <span id="total_paid">0</span>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-2">
        <div class="p-3 rounded" style="background:#d1ecf1;border:1px solid #bee5eb">
          <div class="small mb-1" style="color:#0c5460"><i class="fas fa-percent mr-1"></i>Total Payment Point Fee</div>
          <div class="font-weight-bold" style="font-size:1.1rem;color:#0c5460">
            Rp <span id="fee_counter">0</span>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-2">
        <div class="p-3 rounded" style="background:var(--bg-surface-2);border:1px solid var(--border)">
          <div class="small mb-1" style="color:var(--text-secondary)"><i class="fas fa-money-bill-wave mr-1"></i>Total Payment</div>
          <div class="font-weight-bold" style="font-size:1.1rem;color:var(--text-primary)">
            Rp <span id="total_payment">0</span>
          </div>
        </div>
      </div>
    </div>

    {{-- ─── Main DataTable ───────────────────────────────────────────────────── --}}
    <div class="table-responsive">
      <table id="table-transaction-list" class="table table-bordered table-striped table-sm text-xs">
        <thead class="thead-light">
          <tr>
            <th>#</th>
            <th>Invoice Date</th>
            <th>Invoice No</th>
            <th class="text-center">CID</th>
            <th>Name</th>
            <th>Merchant</th>
            <th>Address</th>
            <th>Note</th>
            <th>Period</th>
            <th class="text-right">Total Amount</th>
            <th class="text-right">Payment Fee</th>
            <th class="text-center">Status</th>
            <th class="text-center">Kasbank</th>
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
@include('script.transaction_list')
@endsection
