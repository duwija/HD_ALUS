@extends('app.layout')

@section('title', 'Notifikasi')
@section('page-title', 'Notifikasi')

@section('topbar-right')
    @if($unreadCount > 0)
        <span class="badge-unread">{{ $unreadCount }} belum dibaca</span>
    @endif
@endsection

@push('styles')
<style>
    .notif-item {
        background: #fff;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,.07);
        display: flex;
        gap: 12px;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        transition: opacity .15s;
        position: relative;
        overflow: hidden;
    }
    .notif-item.unread::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--primary);
        border-radius: 4px 0 0 4px;
    }
    .notif-item:active { opacity: .75; }

    .notif-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 18px;
    }
    .icon-invoice    { background: #dbeafe; }
    .icon-reminder   { background: #fef3c7; }
    .icon-ticket     { background: #d1fae5; }
    .icon-info       { background: #ede9fe; }

    .notif-body { flex: 1; min-width: 0; }
    .notif-title {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .notif-item.unread .notif-title { color: var(--gray-900); }
    .notif-item:not(.unread) .notif-title { color: var(--gray-700); font-weight: 500; }
    .notif-content {
        font-size: 12px;
        color: var(--gray-500);
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .notif-time { font-size: 11px; color: var(--gray-500); margin-top: 5px; }

    .section-date {
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin: 14px 0 8px;
    }

    .empty-state {
        text-align: center;
        padding: 64px 16px;
        color: var(--gray-500);
    }
    .empty-state svg { width: 64px; height: 64px; margin: 0 auto 16px; display: block; opacity: .3; }
    .empty-state h3  { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
    .empty-state p   { font-size: 13px; }

    /* Bottom Sheet */
    .notif-sheet-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.45);
        z-index: 1040;
    }
    .notif-sheet-overlay.open { display: block; }
    .notif-sheet {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        background: #fff;
        border-radius: 20px 20px 0 0;
        z-index: 1050;
        padding: 0 0 env(safe-area-inset-bottom);
        transform: translateY(100%);
        transition: transform .3s cubic-bezier(.32,1,.56,1);
        max-height: 85vh;
        display: flex;
        flex-direction: column;
    }
    .notif-sheet.open { transform: translateY(0); }
    .notif-sheet-handle {
        width: 40px;
        height: 4px;
        background: var(--gray-200, #e5e7eb);
        border-radius: 2px;
        margin: 12px auto 0;
        flex-shrink: 0;
    }
    .notif-sheet-header {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px 18px 12px;
        border-bottom: 1px solid var(--gray-100, #f3f4f6);
        flex-shrink: 0;
    }
    .notif-sheet-header .sh-icon {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 20px;
    }
    .notif-sheet-header .sh-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--gray-900, #111827);
        line-height: 1.4;
        flex: 1;
    }
    .notif-sheet-header .sh-time {
        font-size: 11px;
        color: var(--gray-500, #6b7280);
        margin-top: 3px;
    }
    .notif-sheet-body {
        padding: 16px 18px;
        font-size: 14px;
        line-height: 1.65;
        color: var(--gray-700, #374151);
        overflow-y: auto;
        flex: 1;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .notif-sheet-footer {
        padding: 12px 18px 16px;
        border-top: 1px solid var(--gray-100, #f3f4f6);
        flex-shrink: 0;
    }
    .notif-sheet-footer .btn-open {
        display: block;
        width: 100%;
        padding: 12px;
        background: var(--primary, #4f46e5);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
    }
    .notif-sheet-footer .btn-close-sheet {
        display: block;
        width: 100%;
        padding: 10px;
        background: none;
        border: none;
        font-size: 14px;
        color: var(--gray-500, #6b7280);
        margin-top: 8px;
        cursor: pointer;
    }
</style>
@endpush

@section('content')

@if($notifications->isEmpty())
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <h3>Tidak ada notifikasi</h3>
        <p>Notifikasi 30 hari terakhir akan muncul di sini</p>
    </div>
@else
    @php
        $grouped = $notifications->groupBy(fn($n) => $n->created_at->format('Y-m-d'));
        $today     = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
    @endphp

    @foreach($grouped as $date => $items)
        <div class="section-date">
            @if($date === $today) Hari Ini
            @elseif($date === $yesterday) Kemarin
            @else {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}
            @endif
        </div>

        @foreach($items as $notif)
            @php
                $icon = match($notif->type) {
                    'new_invoice'      => ['🧾', 'icon-invoice'],
                    'reminder_invoice' => ['🔔', 'icon-reminder'],
                    'ticket_update'    => ['📋', 'icon-ticket'],
                    default            => ['ℹ️', 'icon-info'],
                };
            @endphp

            <div class="notif-item {{ !$notif->is_read ? 'unread' : '' }}"
                 data-title="{{ e($notif->title) }}"
                 data-body="{{ e($notif->body) }}"
                 data-time="{{ $notif->created_at->diffForHumans() }}"
                 data-icon="{{ $icon[0] }}"
                 data-icon-class="{{ $icon[1] }}"
                 data-href="{{ $notif->open_url ?: '' }}"
                 onclick="openNotifSheet(this)">
                <div class="notif-icon {{ $icon[1] }}">{{ $icon[0] }}</div>
                <div class="notif-body">
                    <div class="notif-title">{{ $notif->title }}</div>
                    <div class="notif-content">{{ $notif->body }}</div>
                    <div class="notif-time">{{ $notif->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @endforeach
    @endforeach
@endif

{{-- Bottom Sheet Notifikasi --}}
<div class="notif-sheet-overlay" id="notifOverlay" onclick="closeNotifSheet()"></div>
<div class="notif-sheet" id="notifSheet">
    <div class="notif-sheet-handle"></div>
    <div class="notif-sheet-header">
        <div class="notif-icon sh-icon" id="shIcon"></div>
        <div>
            <div class="sh-title" id="shTitle"></div>
            <div class="sh-time" id="shTime"></div>
        </div>
    </div>
    <div class="notif-sheet-body" id="shBody"></div>
    <div class="notif-sheet-footer">
        <a id="shBtn" href="#" class="btn-open" style="display:none;">Buka</a>
        <button class="btn-close-sheet" onclick="closeNotifSheet()">Tutup</button>
    </div>
</div>


@push('scripts')
<script>
function openNotifSheet(el) {
    var title   = el.dataset.title;
    var body    = el.dataset.body;
    var time    = el.dataset.time;
    var icon    = el.dataset.icon;
    var iconCls = el.dataset.iconClass;
    var href    = el.dataset.href;

    document.getElementById('shTitle').textContent = title;
    document.getElementById('shBody').textContent  = body;
    document.getElementById('shTime').textContent  = time;

    var iconEl       = document.getElementById('shIcon');
    iconEl.textContent = icon;
    iconEl.className   = 'notif-icon sh-icon ' + iconCls;

    var btn = document.getElementById('shBtn');
    if (href) {
        btn.href         = href;
        btn.style.display = 'block';
    } else {
        btn.style.display = 'none';
    }

    document.getElementById('notifOverlay').classList.add('open');
    document.getElementById('notifSheet').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeNotifSheet() {
    document.getElementById('notifOverlay').classList.remove('open');
    document.getElementById('notifSheet').classList.remove('open');
    document.body.style.overflow = '';
}
</script>
@endpush

@endsection
