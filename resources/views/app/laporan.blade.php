@extends('app.layout')

<!-- @section('title', 'Laporan Tiket')
@section('page-title', 'Laporan Tiket') -->

@push('styles')
<style>
    .ticket-item {
        background: #fff;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,.07);
    }
    .ticket-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
    }
    .ticket-id    { font-size: 12px; color: var(--gray-500); }
    .ticket-title { font-size: 15px; font-weight: 700; margin-top: 2px; line-height: 1.3; }
    .ticket-customer { font-size: 12px; color: var(--gray-500); margin-top: 4px; }

    /* Workflow Timeline */
    .workflow-timeline {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--gray-100);
        overflow-x: auto;
        scrollbar-width: none;
    }
    .workflow-timeline::-webkit-scrollbar { display: none; }
    .workflow-steps {
        display: flex;
        align-items: center;
        gap: 0;
        min-width: max-content;
    }
    .workflow-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
    }
    .workflow-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 12px;
        left: calc(50% + 12px);
        width: calc(100% - 4px);
        height: 2px;
        background: var(--gray-200);
        z-index: 0;
    }
    .workflow-step.done::after  { background: var(--success); }
    .workflow-step .step-dot {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--gray-200);
        color: var(--gray-500);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        position: relative;
        z-index: 1;
        flex-shrink: 0;
    }
    .workflow-step.done    .step-dot { background: var(--success); color: #fff; }
    .workflow-step.current .step-dot { background: var(--primary); color: #fff; box-shadow: 0 0 0 3px rgba(79,70,229,.2); }
    .workflow-step .step-label {
        font-size: 10px;
        color: var(--gray-500);
        margin-top: 4px;
        text-align: center;
        white-space: nowrap;
        width: 60px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .workflow-step.done    .step-label { color: var(--success); }
    .workflow-step.current .step-label { color: var(--primary); font-weight: 700; }
    .workflow-step + .workflow-step { margin-left: 30px; }

    .ticket-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
    }
    .ticket-date { font-size: 11px; color: var(--gray-500); }

    .status-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 10px;
    }
    .status-open     { background: #dbeafe; color: #1e40af; }
    .status-progress { background: #fef3c7; color: #92400e; }
    .status-done     { background: #d1fae5; color: #065f46; }
    .status-closed   { background: var(--gray-100); color: var(--gray-700); }

    .empty-state {
        text-align: center;
        padding: 48px 16px;
        color: var(--gray-500);
    }
    .empty-state svg { width: 56px; height: 56px; margin: 0 auto 14px; display: block; opacity: .35; }

    .btn-wa-noc {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: #25D366;
        color: #fff;
        padding: 14px 20px;
        border-radius: 14px;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 4px 14px rgba(37,211,102,.35);
    }
    .btn-wa-noc:active { opacity: .85; color: #fff; }
    .btn-wa-noc svg { flex-shrink: 0; }
    .noc-wrap {
        margin-top: 8px;
        padding: 12px 0 20px;
        border-top: 1px solid var(--gray-100);
    }
    .noc-label {
        font-size: 13px;
        color: var(--gray-500);
        text-align: center;
        margin-bottom: 10px;
    }

    /* ── Pagination compact ── */
    .pagination { margin: 0; gap: 3px; display: flex; flex-wrap: wrap; justify-content: center; list-style: none; padding: 0; }
    .pagination li a,
    .pagination li span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 24px;
        padding: 0 6px;
        font-size: 11px;
        line-height: 1;
        border-radius: 5px;
        border: 1px solid var(--gray-200);
        color: var(--gray-700);
        background: #fff;
        text-decoration: none;
    }
    .pagination li.active span,
    .pagination li a:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
    .pagination li.disabled span { color: var(--gray-300); cursor: not-allowed; }
</style>
@endpush

@section('content')

@forelse($tickets as $ticket)
    @php
        $cust = $customerMap->get($ticket->id_customer);
        $statusClass = match($ticket->status ?? '') {
            'open'        => 'status-open',
            'in_progress', 'progress' => 'status-progress',
            'done', 'resolved' => 'status-done',
            'closed'      => 'status-closed',
            default       => 'status-open',
        };
        $statusLabel = match($ticket->status ?? '') {
            'open'         => 'Terbuka',
            'in_progress'  => 'Dikerjakan',
            'progress'     => 'Dikerjakan',
            'done'         => 'Selesai',
            'resolved'     => 'Selesai',
            'closed'       => 'Ditutup',
            default        => ucfirst($ticket->status ?? 'Terbuka'),
        };
    @endphp

    <div class="ticket-item">
        <div class="ticket-header">
            <div style="flex:1">
                <div class="ticket-id">#TKT-{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }}</div>
                <div class="ticket-title">{{ $ticket->tittle ?? $ticket->title ?? 'Tiket' }}</div>
                <div class="ticket-customer">{{ $cust->name ?? '-' }}</div>
            </div>
            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>

        @if($ticket->categorie)
            <span class="badge badge-info" style="font-size:11px;">{{ $ticket->categorie->name ?? '' }}</span>
        @endif

        {{-- Workflow Steps --}}
        @if($ticket->steps && $ticket->steps->count() > 0)
            <div class="workflow-timeline">
                <div class="workflow-steps">
                    @foreach($ticket->steps as $i => $step)
                        @php
                            $isDone    = $step->status === 'done' || $step->completed_at !== null;
                            $isCurrent = $ticket->current_step_id == $step->id;
                            $cls = $isDone ? 'done' : ($isCurrent ? 'current' : '');
                        @endphp
                        <div class="workflow-step {{ $cls }}">
                            <div class="step-dot">
                                @if($isDone) ✓ @else {{ $i + 1 }} @endif
                            </div>
                            <div class="step-label" title="{{ $step->name ?? $step->title ?? '' }}">
                                {{ $step->name ?? $step->title ?? ('Step '.($i+1)) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="ticket-footer">
            <div class="ticket-date">
                Dibuat: {{ $ticket->created_at ? $ticket->created_at->format('d M Y') : '-' }}
            </div>
            @if($ticket->currentStep)
                <div style="font-size:12px;color:var(--gray-500);">
                    Saat ini: <strong>{{ $ticket->currentStep->name ?? $ticket->currentStep->title ?? '-' }}</strong>
                </div>
            @endif
        </div>
    </div>
@empty
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
        </svg>
        <p>Belum ada tiket</p>
    </div>
@endforelse

@if(method_exists($tickets, 'lastPage') && $tickets->lastPage() > 1)
    <div style="margin:10px 0 14px; display:flex; justify-content:center;">
        <ul class="pagination">
            @foreach($tickets->getUrlRange(1, $tickets->lastPage()) as $page => $url)
                @if($page == $tickets->currentPage())
                    <li class="active"><span>{{ $page }}</span></li>
                @else
                    <li><a href="{{ $url }}">{{ $page }}</a></li>
                @endif
            @endforeach
        </ul>
    </div>
@endif

@php
    // Prioritas: whatsapp_noc → payment_wa → kosong
    $waRaw   = tenant_env('whatsapp_noc', '') ?: tenant_env('payment_wa', '');
    $waClean = preg_replace('/[^0-9]/', '', $waRaw);
    if ($waClean && str_starts_with($waClean, '0')) {
        $waClean = '62' . substr($waClean, 1);
    }
    $customerName = Auth::guard('customer')->user()->name ?? 'Pelanggan';
     $customerEmail = Auth::guard('customer')->user()->email ?? 'email';
    $waText = urlencode("Halo, saya {$customerName} ({$customerEmail}). Saya ingin membuat laporan terkait internet saya.");
    $waUrl  = $waClean ? "https://wa.me/{$waClean}?text={$waText}" : null;
@endphp

@if($waUrl)
<div class="noc-wrap">
    <div class="noc-label">Ada gangguan? Hubungi tim NOC kami.</div>
    <a href="{{ $waUrl }}" target="_blank" class="btn-wa-noc">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
            <path d="M20.52 3.48A11.84 11.84 0 0 0 12 0C5.37 0 0 5.37 0 12c0 2.12.55 4.19 1.6 6.02L0 24l6.18-1.57A11.93 11.93 0 0 0 12 24c6.63 0 12-5.37 12-12 0-3.21-1.25-6.23-3.48-8.52zm-8.52 18.4a9.88 9.88 0 0 1-5.04-1.38l-.36-.21-3.74.98 1-3.64-.24-.38A9.9 9.9 0 0 1 2.1 12c0-5.46 4.44-9.9 9.9-9.9a9.82 9.82 0 0 1 6.99 2.9A9.82 9.82 0 0 1 21.9 12c0 5.46-4.44 9.88-9.9 9.88zm5.42-7.42c-.3-.15-1.76-.87-2.03-.97s-.47-.15-.67.15-.77.97-.94 1.17-.35.22-.64.07a8.12 8.12 0 0 1-2.39-1.48 8.98 8.98 0 0 1-1.65-2.06c-.17-.3-.02-.46.13-.61.13-.13.3-.35.44-.52s.2-.3.3-.5.05-.37-.03-.52-.67-1.61-.91-2.2c-.24-.58-.49-.5-.67-.51l-.57-.01c-.2 0-.52.07-.79.37s-1.04 1.02-1.04 2.48 1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.27.49 1.7.63.72.23 1.37.2 1.88.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.3.17-1.41-.07-.12-.27-.19-.57-.34z"/>
        </svg>
        Buat Laporan
    </a>
</div>
@endif

@endsection
