@extends('app.layout')
<!-- 
@section('title', 'Tagihan')
@section('page-title', 'Tagihan') -->

@push('styles')
<style>
    .filter-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 14px;
        overflow-x: auto;
        padding-bottom: 4px;
        scrollbar-width: none;
    }
    .filter-tabs::-webkit-scrollbar { display: none; }
    .filter-tab {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--gray-200);
        background: #fff;
        color: var(--gray-700);
        white-space: nowrap;
        cursor: pointer;
        text-decoration: none;
    }
    .filter-tab.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    /* Customer group card */
    .customer-card {
        background: #fff;
        border-radius: 14px;
        margin-bottom: 14px;
        box-shadow: 0 1px 5px rgba(0,0,0,.08);
        overflow: hidden;
    }
    .customer-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 14px;
        border-bottom: 1px solid var(--gray-100);
        background: #fafafa;
    }
    .customer-card-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--gray-800);
    }
    .customer-card-id {
        font-size: 11px;
        color: var(--gray-500);
        margin-top: 1px;
    }
    .customer-plan-info {
        margin-top: 8px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .customer-plan-line {
        font-size: 11px;
        color: var(--gray-700);
        line-height: 1.4;
    }
    .customer-plan-line strong {
        color: var(--gray-900);
    }
    .addon-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 2px;
    }
    .addon-chip {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 999px;
        background: var(--gray-100);
        color: var(--gray-700);
        font-size: 10px;
        font-weight: 600;
    }

    /* Individual invoice row inside group */
    .inv-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        border-bottom: 1px solid var(--gray-100);
        gap: 8px;
    }
    .inv-row:last-of-type { border-bottom: none; }
    .inv-row-left { flex: 1; min-width: 0; }
    .inv-row-num  { font-size: 12px; font-weight: 700; }
    .inv-row-date { font-size: 11px; color: var(--gray-500); margin-top: 1px; }
    .inv-row-amount { font-size: 13px; font-weight: 700; color: var(--primary); white-space: nowrap; }

    /* Customer card footer with Lihat button */
    .customer-card-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding: 10px 14px;
        border-top: 1px solid var(--gray-100);
        background: #fafafa;
    }
    .btn-lihat {
        background: var(--primary);
        color: #fff;
        padding: 8px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
    }

    .empty-state {
        text-align: center;
        padding: 48px 16px;
        color: var(--gray-500);
    }
    .empty-state svg { width: 56px; height: 56px; margin: 0 auto 14px; display: block; opacity: .35; }
</style>
@endpush

@section('content')

@php
    $filter  = request('filter', 'all');
    $unpaidTotal = (int) ($statusCounts[0] ?? 0);
    $cancelTotal = (int) ($statusCounts[2] ?? 0);

    $visibleCustomers = $customers->filter(function ($cust) use ($customerInvoices) {
        return isset($customerInvoices[$cust->id]) && $customerInvoices[$cust->id]->isNotEmpty();
    });
@endphp

<div class="filter-tabs">
    <a href="?filter=all"    class="filter-tab {{ $filter === 'all'    ? 'active' : '' }}">Semua</a>
    <a href="?filter=unpaid" class="filter-tab {{ $filter === 'unpaid' ? 'active' : '' }}">
        Belum Bayar ({{ $unpaidTotal }})
    </a>
    <a href="?filter=paid"   class="filter-tab {{ $filter === 'paid'   ? 'active' : '' }}">Lunas</a>
    <a href="?filter=cancel" class="filter-tab {{ $filter === 'cancel' ? 'active' : '' }}">
        Dibatalkan ({{ $cancelTotal }})
    </a>
</div>

@forelse($visibleCustomers as $cust)
@php
    $shown          = $customerInvoices[$cust->id] ?? collect();
    $firstInv       = $shown->first();
    $encryptedCstId = $firstInv->encrypted_customer_id ?? null;
    $unpaidCount    = (int) ($unpaidCountsByCustomer[$cust->id] ?? 0);
    $plan           = optional($cust)->plan_name;
    $addons         = optional($cust)->addons ?? collect();
@endphp
<div class="customer-card">
    {{-- Header --}}
    <div class="customer-card-header">
        <div>
            <div class="customer-card-name">{{ $cust->name ?? '-' }}</div>
            <div class="customer-card-id">{{ $cust->customer_id ?? $custId }}</div>
            <div class="customer-plan-info">
                <div class="customer-plan-line">
                    <strong>Plan:</strong>
                    {{ $plan->name ?? 'Belum ada plan' }}
                    @if($plan && isset($plan->price))
                        · Rp {{ number_format($plan->price, 0, ',', '.') }}
                    @endif
                </div>
                <div class="customer-plan-line">
                    <strong>Add-on:</strong>
                    @if($addons->isNotEmpty())
                        <div class="addon-list">
                            @foreach($addons as $addon)
                                <span class="addon-chip">
                                    {{ $addon->name }}
                                    @if(isset($addon->price))
                                        · Rp {{ number_format($addon->price, 0, ',', '.') }}
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @else
                        Tidak ada add-on aktif
                    @endif
                </div>
            </div>
        </div>
        @if($unpaidCount > 0)
            <span class="badge badge-warning">{{ $unpaidCount }} belum bayar</span>
        @else
            <span class="badge badge-success">Lunas</span>
        @endif
    </div>

    {{-- Invoice rows --}}
    @foreach($shown->sortByDesc('id') as $inv)
    @php $isPaid = $inv->payment_status == 1; $isCancelled = $inv->payment_status == 2; @endphp
    <div class="inv-row">
        <div class="inv-row-left">
            <div class="inv-row-num">#{{ $inv->number ?? $inv->id }}</div>
            <div class="inv-row-date">
                {{ $inv->date ? \Carbon\Carbon::parse($inv->date)->format('d M Y') : '-' }}
                @if(!$isPaid && !$isCancelled)
                    &nbsp;·&nbsp;Jatuh tempo: {{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M Y') : '-' }}
                @endif
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="inv-row-amount">Rp {{ number_format($inv->total_amount ?? 0, 0, ',', '.') }}</div>
            <span class="badge {{ $isPaid ? 'badge-success' : ($isCancelled ? 'badge-secondary' : 'badge-warning') }}" style="font-size:10px;">
                {{ $isPaid ? 'Lunas' : ($isCancelled ? 'Batal' : 'Unpaid') }}
            </span>
        </div>
    </div>
    @endforeach

    {{-- Footer: single Lihat button --}}
    @if($encryptedCstId)
    <div class="customer-card-footer">
        <a href="/invoice/cst/{{ $encryptedCstId }}" class="btn-lihat">Lihat Tagihan</a>
    </div>
    @endif
</div>
@empty
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <line x1="2" y1="10" x2="22" y2="10"/>
        </svg>
        <p>Tidak ada tagihan</p>
    </div>
@endforelse

@endsection
