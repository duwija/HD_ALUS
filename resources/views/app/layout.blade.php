<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'Portal Pelanggan')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }

        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #3730a3;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            min-height: 100vh;
            padding-bottom: calc(64px + var(--safe-bottom));
        }

        /* ── Top Bar ─────────────────────────────────────────────── */
        .app-topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--primary);
            color: #fff;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(79,70,229,.25);
        }
        .app-topbar h1 { font-size: 17px; font-weight: 600; flex: 1; }
        .app-topbar .badge-unread {
            background: var(--danger);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 12px;
        }

        /* ── Scrollable content ──────────────────────────────────── */
        .app-content { padding: 16px; }

        /* ── Bottom Nav ─────────────────────────────────────────── */
        .app-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid var(--gray-200);
            display: flex;
            z-index: 100;
            padding-bottom: var(--safe-bottom);
        }
        .app-bottom-nav a {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 10px 4px;
            text-decoration: none;
            color: var(--gray-500);
            font-size: 10px;
            font-weight: 500;
            position: relative;
            transition: color .15s;
        }
        .app-bottom-nav a.active { color: var(--primary); }
        .app-bottom-nav a svg { width: 22px; height: 22px; }
        .app-bottom-nav .nav-badge {
            position: absolute;
            top: 6px;
            right: calc(50% - 18px);
            background: var(--danger);
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Card ────────────────────────────────────────────────── */
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,.07);
        }
        .card-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 10px;
        }

        /* ── Badge Label ─────────────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success  { background: #d1fae5; color: #065f46; }
        .badge-danger   { background: #fee2e2; color: #991b1b; }
        .badge-warning  { background: #fef3c7; color: #92400e; }
        .badge-info     { background: #dbeafe; color: #1e40af; }
        .badge-primary  { background: #ede9fe; color: #4c1d95; }
        .badge-gray     { background: var(--gray-200); color: var(--gray-700); }
    </style>
    @stack('styles')
</head>
<body>

<!-- <div class="app-topbar">
    <h1>@yield('page-title', 'Portal Pelanggans')</h1>
    @yield('topbar-right')
</div> -->

<div class="app-content">
    @yield('content')
</div>

{{-- Bottom Navigation --}}
@php
    $unreadNotif = $unreadCount ?? 0;
    $currentRoute = request()->route()->getName();
@endphp
<!-- <nav class="app-bottom-nav">
    <a href="{{ route('app.home') }}" class="{{ $currentRoute === 'app.home' ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        Beranda
    </a>
    <a href="{{ route('app.tagihan') }}" class="{{ $currentRoute === 'app.tagihan' ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <line x1="2" y1="10" x2="22" y2="10"/>
        </svg>
        Tagihan
    </a>
    <a href="{{ route('app.laporan') }}" class="{{ $currentRoute === 'app.laporan' ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
            <polyline points="10 9 9 9 8 9"/>
        </svg>
        Laporan
    </a>
    <a href="{{ route('app.notif') }}" class="{{ $currentRoute === 'app.notif' ? 'active' : '' }}">
        @if($unreadNotif > 0)
            <span class="nav-badge">{{ $unreadNotif > 99 ? '99+' : $unreadNotif }}</span>
        @endif
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        Notifikasi
    </a>
</nav> -->

<script src="{{ url('dashboard/plugins/sweetalert2/sweetalert2.all.js') }}"></script>
@stack('scripts')
</body>
</html>
