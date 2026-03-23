@extends('app.layout')

<!-- @section('title', 'Beranda') -->
<!-- @section('page-title', 'Beranda') -->

@push('styles')
<style>
    .greeting-card {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        border-radius: 16px;
        padding: 20px;
        color: #fff;
        margin-bottom: 16px;
        position: relative;
        overflow: hidden;
    }
    .greeting-card::after {
        content: '';
        position: absolute;
        right: -20px;
        top: -20px;
        width: 120px;
        height: 120px;
        background: rgba(255,255,255,.1);
        border-radius: 50%;
    }
    .greeting-card h2 { font-size: 18px; margin-bottom: 4px; }
    .greeting-card p  { font-size: 13px; opacity: .85; }
    .greeting-card .tagihan-summary {
        margin-top: 14px;
        background: rgba(255,255,255,.15);
        border-radius: 10px;
        padding: 10px 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .greeting-card .tagihan-summary .label { font-size: 12px; opacity: .85; }
    .greeting-card .tagihan-summary .amount { font-size: 20px; font-weight: 700; }

    .section-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--gray-700);
        margin: 16px 0 10px;
    }

    .customer-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid var(--gray-100);
        cursor: pointer;
    }
    .customer-item:last-child { border-bottom: none; }
    .customer-item:active { opacity: .7; }
    .customer-avatar {
        width: 42px; height: 42px;
        background: var(--primary);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 16px; flex-shrink: 0;
    }
    .customer-info { flex: 1; min-width: 0; }
    .customer-info .name { font-size: 14px; font-weight: 600; }
    .customer-info .sub  { font-size: 12px; color: var(--gray-500); }
    /* bottom sheet overlay */
    .sheet-overlay {
        display: none;
        position: fixed; inset: 0; z-index: 999;
        background: rgba(0,0,0,.45);
    }
    .sheet-overlay.open { display: block; }
    .sheet-box {
        position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;
        background: #fff;
        border-radius: 20px 20px 0 0;
        max-height: 80vh; overflow-y: auto;
        padding: 0 0 24px;
        transform: translateY(100%);
        transition: transform .28s ease;
    }
    .sheet-box.open { transform: translateY(0); }
    .sheet-handle { text-align: center; padding: 12px 0 4px; }
    .sheet-handle span { display: inline-block; width: 36px; height: 4px; background: #e5e7eb; border-radius: 2px; }
    .sheet-head { text-align: center; padding: 8px 16px 12px; border-bottom: 1px solid #f1f1f1; }
    .sheet-avatar { width: 52px; height: 52px; background: linear-gradient(135deg,#4f46e5,#7c3aed); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; font-weight: 700; margin: 0 auto 8px; }
    .sheet-head h3 { font-size: 16px; font-weight: 700; margin: 0 0 4px; }
    .sheet-body { padding: 4px 16px; }
    .drow { display: flex; padding: 11px 0; border-bottom: 1px solid #f9f9f9; font-size: 14px; gap: 12px; }
    .drow:last-child { border-bottom: none; }
    .drow .dl { width: 110px; flex-shrink: 0; color: #6b7280; }
    .drow .dv { flex: 1; font-weight: 600; color: #111; }

    .promo-card {
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 12px;
        background: #fff;
        box-shadow: 0 1px 4px rgba(0,0,0,.07);
    }
    .promo-card img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        display: block;
    }
    .promo-card .promo-body { padding: 14px; }
    .promo-card .promo-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 8px;
        background: #ede9fe;
        color: #4c1d95;
        display: inline-block;
        margin-bottom: 6px;
    }
    .promo-card .promo-title { font-size: 15px; font-weight: 700; margin-bottom: 6px; }
    .promo-card .promo-content { font-size: 13px; color: var(--gray-500); line-height: 1.6; }
    .promo-card .promo-content img  { max-width: 100%; border-radius: 8px; height: auto; display: block; margin: 6px 0; }
    .promo-card .promo-content video { max-width: 100%; border-radius: 8px; display: block; margin: 6px 0; }
    .promo-card .promo-content p  { margin: 0 0 6px; }
    .promo-card .promo-content ul, .promo-card .promo-content ol { padding-left: 18px; margin: 4px 0 8px; }
    .promo-card .promo-content a  { color: var(--primary); }
    .promo-date { font-size: 11px; color: var(--gray-500); margin-top: 8px; }

    .empty-state {
        text-align: center;
        padding: 32px 16px;
        color: var(--gray-500);
    }
    .empty-state svg { width: 48px; height: 48px; margin: 0 auto 12px; display: block; opacity: .4; }
    .empty-state p { font-size: 14px; }
</style>
@endpush

@section('content')

{{-- Greeting Card --}}
<div class="greeting-card">
    <h2>Halo, {{ Auth::guard('customer')->user()->name ?? 'Pelanggan' }} 👋</h2>
    <p>Selamat datang di Portal Pelanggan</p>
    <div class="tagihan-summary">
        <div>
            <div class="label">Total Tagihan Belum Bayar</div>
            <div class="amount">Rp {{ number_format($unpaidTotal, 0, ',', '.') }}</div>
        </div>
        <a href="{{ route('app.tagihan') }}"
           style="background:#fff;color:var(--primary);padding:8px 14px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;">
            Lihat
        </a>
    </div>
</div>

{{-- Daftar Akun --}}
<div class="card">
    <div class="card-title">Akun Saya</div>
    @foreach($customers as $cust)
        @php
            $sName = strtolower($cust->status_name->name ?? '');
            $sBadge = match($sName) {
                'active'           => 'badge-success',
                'potensial'        => 'badge-info',
                'inactive'         => 'badge-secondary',
                'block'            => 'badge-danger',
                'company_property' => 'badge-warning',
                default            => 'badge-secondary',
            };
        @endphp
        <div class="customer-item" onclick="openSheet('{{ $cust->id }}')">
            <div class="customer-avatar">{{ strtoupper(substr($cust->name, 0, 1)) }}</div>
            <div class="customer-info">
                <div class="name">{{ $cust->name }}</div>
                <div class="sub">ID: {{ $cust->customer_id }} &nbsp;·&nbsp; {{ $cust->pppoe }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <span class="badge {{ $sBadge }}">{{ $cust->status_name->name ?? '-' }}</span>
                <i class="fas fa-chevron-right" style="color:#9ca3af;font-size:11px;"></i>
            </div>
        </div>
    @endforeach
</div>

{{-- Bottom Sheet Detail Akun (vanilla JS — no Bootstrap required) --}}
<div class="sheet-overlay" id="sheet-overlay" onclick="closeSheet()"></div>

@foreach($customers as $cust)
@php
    $sName2 = strtolower($cust->status_name->name ?? '');
    $sBadge2 = match($sName2) {
        'active'           => 'badge-success',
        'potensial'        => 'badge-info',
        'inactive'         => 'badge-secondary',
        'block'            => 'badge-danger',
        'company_property' => 'badge-warning',
        default            => 'badge-secondary',
    };
@endphp
<div class="sheet-box" id="sheet-{{ $cust->id }}">
    <div class="sheet-handle"><span></span></div>
    <div class="sheet-head">
        <div class="sheet-avatar">{{ strtoupper(substr($cust->name, 0, 1)) }}</div>
        <h3>{{ $cust->name }}</h3>
        <span class="badge {{ $sBadge2 }}">{{ $cust->status_name->name ?? '-' }}</span>
    </div>
    <div class="sheet-body">
        <div class="drow">
            <span class="dl"><i class="fas fa-id-badge"></i> ID</span>
            <span class="dv">{{ $cust->customer_id ?? '-' }}</span>
        </div>
        <div class="drow">
            <span class="dl"><i class="fas fa-user"></i> Nama</span>
            <span class="dv">{{ $cust->name ?? '-' }}</span>
        </div>
        <div class="drow">
            <span class="dl"><i class="fas fa-map-marker-alt"></i> Alamat</span>
            <span class="dv">{{ $cust->address ?? '-' }}</span>
        </div>
        <div class="drow">
            <span class="dl"><i class="fas fa-phone"></i> Telepon</span>
            <span class="dv">{{ $cust->phone ?? '-' }}</span>
        </div>
    </div>
    <div style="padding:16px 16px 0;">
        <a href="{{ route('app.tagihan') }}"
           style="display:block;text-align:center;background:var(--primary);color:#fff;padding:12px;border-radius:10px;font-weight:600;text-decoration:none;margin-bottom:10px;">
            <i class="fas fa-file-invoice" style="margin-right:6px;"></i>Lihat Tagihan
        </a>
        <button onclick="closeSheet()"
            style="display:block;width:100%;background:#f3f4f6;color:#374151;padding:11px;border-radius:10px;font-weight:600;border:none;cursor:pointer;">
            Tutup
        </button>
    </div>
</div>
@endforeach

{{-- Promo & Info --}}
<div class="section-title">Promo & Pengumuman</div>

@forelse($promos as $promo)
    <div class="promo-card">
        @if($promo->image_url)
            <img src="{{ $promo->image_url }}" alt="{{ $promo->title }}" loading="lazy">
        @endif
        <div class="promo-body">
            @if($promo->badge)
                <span class="promo-badge">{{ $promo->badge }}</span>
            @endif
            <div class="promo-title">{{ $promo->title }}</div>
            <div class="promo-content">{!! $promo->content !!}</div>
            @if($promo->end_date)
                <div class="promo-date">Berlaku s/d {{ $promo->end_date->translatedFormat('d M Y') }}</div>
            @endif
        </div>
    </div>
@empty
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
        <p>Belum ada promo atau pengumuman</p>
    </div>
@endforelse

@endsection

@push('scripts')
<script>
var _activeSheet = null;
function openSheet(id) {
    closeSheet();
    var s = document.getElementById('sheet-' + id);
    var o = document.getElementById('sheet-overlay');
    if (!s) return;
    s.classList.add('open');
    o.classList.add('open');
    document.body.style.overflow = 'hidden';
    _activeSheet = s;
}
function closeSheet() {
    if (_activeSheet) { _activeSheet.classList.remove('open'); _activeSheet = null; }
    var o = document.getElementById('sheet-overlay');
    if (o) o.classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeSheet(); });
</script>
@endpush
