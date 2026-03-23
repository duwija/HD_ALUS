@extends('layout.main')
@section('title','Invoice List')
@section('content')
<section class="content-header">

@php
  $allInv     = $suminvoice ?? collect();
  $total      = $allInv->count();
  $totalAmt   = $allInv->sum('total_amount');

  $paidCol    = $allInv->where('payment_status', 1);
  $unpaidCol  = $allInv->where('payment_status', 0);
  $cancelCol  = $allInv->where('payment_status', 2);

  $cntPaid    = $paidCol->count();
  $cntUnpaid  = $unpaidCol->count();
  $cntCancel  = $cancelCol->count();

  $amtPaid    = $paidCol->sum('total_amount');
  $amtUnpaid  = $unpaidCol->sum('total_amount');
  $amtCancel  = $cancelCol->sum('total_amount');

  $pctPaid    = $total > 0 ? round($cntPaid   / $total * 100, 1) : 0;
  $pctUnpaid  = $total > 0 ? round($cntUnpaid / $total * 100, 1) : 0;
  $pctCancel  = $total > 0 ? round($cntCancel / $total * 100, 1) : 0;

  $dateFrom  = $date_from   ?? date('Y-m-01');
  $dateEnd   = $date_end    ?? date('Y-m-d');
  $curStatus = $payment_status ?? '';
@endphp

{{-- ═══ OUTER CARD ══════════════════════════════════════════════════════════ --}}
<div class="card" style="border:1px solid var(--border)">

  {{-- ─── Header + Filter ─────────────────────────────────────────────────── --}}
  <div class="card-header" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem">
      <div>
        <h5 class="mb-0 font-weight-bold" style="color:var(--text-primary)">
          <i class="fas fa-file-invoice-dollar mr-2" style="color:var(--brand)"></i>Invoice List
        </h5>
        <small class="text-muted">
          Periode: <strong>{{ $dateFrom }}</strong> s/d <strong>{{ $dateEnd }}</strong>
          @if($curStatus !== '')
            &nbsp;·&nbsp;
            @if($curStatus == '0') <span class="badge badge-danger">UNPAID</span>
            @elseif($curStatus == '1') <span class="badge badge-success">PAID</span>
            @else <span class="badge badge-secondary">CANCEL</span>
            @endif
          @endif
        </small>
      </div>
      {{-- Filter form --}}
      <form method="post" action="/suminvoice/find" class="d-flex flex-wrap align-items-end" style="gap:.4rem">
        @csrf
        <div>
          <label class="small text-muted d-block mb-0">Dari</label>
          <div class="input-group input-group-sm date" id="dp-from" data-target-input="nearest">
            <input type="text" name="date_from" class="form-control datetimepicker-input"
              data-target="#dp-from" value="{{ $dateFrom }}" style="width:120px">
            <div class="input-group-append" data-target="#dp-from" data-toggle="datetimepicker">
              <div class="input-group-text"><i class="fa fa-calendar fa-sm"></i></div>
            </div>
          </div>
        </div>
        <div>
          <label class="small text-muted d-block mb-0">Sampai</label>
          <div class="input-group input-group-sm date" id="dp-end" data-target-input="nearest">
            <input type="text" name="date_end" class="form-control datetimepicker-input"
              data-target="#dp-end" value="{{ $dateEnd }}" style="width:120px">
            <div class="input-group-append" data-target="#dp-end" data-toggle="datetimepicker">
              <div class="input-group-text"><i class="fa fa-calendar fa-sm"></i></div>
            </div>
          </div>
        </div>
        <div>
          <label class="small text-muted d-block mb-0">Status</label>
          <select name="payment_status" class="form-control form-control-sm" style="width:110px">
            <option value=""  {{ $curStatus === ''  ? 'selected' : '' }}>All</option>
            <option value="0" {{ $curStatus === '0' ? 'selected' : '' }}>Unpaid</option>
            <option value="1" {{ $curStatus === '1' ? 'selected' : '' }}>Paid</option>
            <option value="2" {{ $curStatus === '2' ? 'selected' : '' }}>Cancel</option>
          </select>
        </div>
        <div style="padding-top:1.3rem">
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-search mr-1"></i>Tampilkan
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="card-body" style="background:var(--bg-surface)">

    {{-- ─── Summary Stats ────────────────────────────────────────────────────── --}}
    <div class="row mb-3">

      {{-- Total --}}
      <div class="col-6 col-md-3 mb-2">
        <div class="p-3 rounded h-100" style="background:var(--bg-surface-2);border:1px solid var(--border)">
          <div class="text-muted small mb-1"><i class="fas fa-file-invoice mr-1"></i>Total Invoice</div>
          <div class="font-weight-bold" style="font-size:1.4rem;color:var(--text-primary)">{{ number_format($total) }}</div>
          <div class="text-muted small">Rp {{ number_format($totalAmt, 0, ',', '.') }}</div>
        </div>
      </div>

      {{-- Paid --}}
      <div class="col-6 col-md-3 mb-2">
        <div class="p-3 rounded h-100" style="background:#d4edda;border:1px solid #c3e6cb">
          <div class="small mb-1" style="color:#155724"><i class="fas fa-check-circle mr-1"></i>Paid</div>
          <div class="font-weight-bold" style="font-size:1.4rem;color:#155724">{{ number_format($cntPaid) }}</div>
          <div class="d-flex align-items-center justify-content-between">
            <div class="small" style="color:#155724">Rp {{ number_format($amtPaid, 0, ',', '.') }}</div>
            <span class="badge badge-success">{{ $pctPaid }}%</span>
          </div>
        </div>
      </div>

      {{-- Unpaid --}}
      <div class="col-6 col-md-3 mb-2">
        <div class="p-3 rounded h-100" style="background:#f8d7da;border:1px solid #f5c6cb">
          <div class="small mb-1" style="color:#721c24"><i class="fas fa-clock mr-1"></i>Unpaid</div>
          <div class="font-weight-bold" style="font-size:1.4rem;color:#721c24">{{ number_format($cntUnpaid) }}</div>
          <div class="d-flex align-items-center justify-content-between">
            <div class="small" style="color:#721c24">Rp {{ number_format($amtUnpaid, 0, ',', '.') }}</div>
            <span class="badge badge-danger">{{ $pctUnpaid }}%</span>
          </div>
        </div>
      </div>

      {{-- Cancel --}}
      <div class="col-6 col-md-3 mb-2">
        <div class="p-3 rounded h-100" style="background:#e9ecef;border:1px solid #dee2e6">
          <div class="small mb-1" style="color:#495057"><i class="fas fa-ban mr-1"></i>Cancel</div>
          <div class="font-weight-bold" style="font-size:1.4rem;color:#495057">{{ number_format($cntCancel) }}</div>
          <div class="d-flex align-items-center justify-content-between">
            <div class="small" style="color:#495057">Rp {{ number_format($amtCancel, 0, ',', '.') }}</div>
            <span class="badge badge-secondary">{{ $pctCancel }}%</span>
          </div>
        </div>
      </div>
    </div>

    {{-- ─── Progress Bar ─────────────────────────────────────────────────────── --}}
    @if($total > 0)
    <div class="mb-3">
      <div class="d-flex justify-content-between small text-muted mb-1">
        <span>Payment Distribution</span>
        <span>{{ $total }} invoices</span>
      </div>
      <div class="progress" style="height:14px;border-radius:7px">
        <div class="progress-bar bg-success"   style="width:{{ $pctPaid }}%"   title="Paid {{ $pctPaid }}%"></div>
        <div class="progress-bar bg-danger"    style="width:{{ $pctUnpaid }}%" title="Unpaid {{ $pctUnpaid }}%"></div>
        <div class="progress-bar bg-secondary" style="width:{{ $pctCancel }}%" title="Cancel {{ $pctCancel }}%"></div>
      </div>
      <div class="d-flex mt-1" style="gap:1.2rem;font-size:.75rem">
        <span><span class="badge badge-success mr-1">&nbsp;</span>Paid {{ $pctPaid }}%</span>
        <span><span class="badge badge-danger  mr-1">&nbsp;</span>Unpaid {{ $pctUnpaid }}%</span>
        <span><span class="badge badge-secondary mr-1">&nbsp;</span>Cancel {{ $pctCancel }}%</span>
      </div>
    </div>
    @endif

    {{-- ─── Table ────────────────────────────────────────────────────────────── --}}
    <div class="table-responsive">
      <table id="example1" class="table table-bordered table-striped table-sm">
        <thead class="thead-light">
          <tr>
            <th>#</th>
            <th>Invoice Date</th>
            <th>Invoice No</th>
            <th>CID</th>
            <th>Name</th>
            <th>Address</th>
            <th class="text-right">Total Amount</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($allInv as $inv)
          @php
            if     ($inv->payment_status == 1) { $badge = 'badge-success';   $sts = 'PAID';   }
            elseif ($inv->payment_status == 2) { $badge = 'badge-secondary'; $sts = 'CANCEL'; }
            elseif ($inv->payment_status == 0) { $badge = 'badge-danger';    $sts = 'UNPAID'; }
            else                               { $badge = 'badge-warning';   $sts = 'UNKNOWN';}
          @endphp
          <tr>
            <td class="text-center">{{ $loop->iteration }}</td>
            <td>{{ $inv->date }}</td>
            <td>
              <a href="/suminvoice/{{ $inv->tempcode }}" style="text-decoration:none;color:white">
                <button class="btn btn-primary btn-sm py-0 px-2"><strong>#{{ $inv->number }}</strong></button>
              </a>
            </td>
            <td>
              <a href="/customer/{{ $inv->customer->id }}">
                <strong>{{ $inv->customer->customer_id }}</strong>
              </a>
            </td>
            <td>{{ $inv->customer->name }}</td>
            <td>{{ $inv->customer->address }}</td>
            <td class="text-right font-weight-bold">
              Rp {{ number_format($inv->total_amount, 0, ',', '.') }}
            </td>
            <td class="text-center">
              <span class="badge text-white {{ $badge }}">{{ $sts }}</span>
              @if($inv->payment_date)
                <div class="text-muted" style="font-size:.7rem">{{ $inv->payment_date }}</div>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="text-center text-muted py-4">
              <i class="fas fa-inbox fa-2x mb-2 d-block"></i>Tidak ada invoice pada rentang waktu ini.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>{{-- /card-body --}}
</div>{{-- /card --}}

</section>
@endsection
