<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Tagihan Pelanggan</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary:   #667eea;
            --primary-d: #5a67d8;
            --success:   #10b981;
            --warning:   #f59e0b;
            --danger:    #ef4444;
            --gray-50:   #f9fafb;
            --gray-100:  #f3f4f6;
            --gray-200:  #e5e7eb;
            --gray-400:  #9ca3af;
            --gray-600:  #4b5563;
            --gray-800:  #1f2937;
            --radius:    12px;
            --shadow:    0 1px 6px rgba(0,0,0,.08);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--gray-50);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            color: var(--gray-800);
            padding-bottom: 100px; /* space for sticky bars */
        }
        /* Bundle sticky bar */
        .bundle-bar {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 16px 16px;
            box-shadow: 0 -4px 20px rgba(0,0,0,.18);
            display: flex; z-index: 98;
            align-items: center; gap: 12px;
            color: #fff;
        }
        .bundle-bar-info { flex: 1; min-width: 0; }
        .bundle-bar-label { font-size: 11px; opacity: .8; margin-bottom: 2px; }
        .bundle-bar-gw    { font-size: 15px; font-weight: 800; }
        .bundle-bar-count { font-size: 10px; opacity: .7; margin-top: 1px; }
        .bundle-bar-actions { display: flex; gap: 8px; flex-shrink: 0; }
        .btn-bundle-pay {
            background: rgba(255,255,255,.2); border: 2px solid rgba(255,255,255,.4);
            color: #fff; padding: 10px 16px; border-radius: 10px;
            font-size: 13px; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            text-decoration: none; font-family: inherit; white-space: nowrap;
            transition: background .15s;
        }
        .btn-bundle-pay:hover { background: rgba(255,255,255,.3); }
        .btn-bundle-pay.primary {
            background: rgba(255,255,255,.95); color: var(--primary-d);
            border-color: transparent;
        }
        .btn-bundle-pay.primary:hover { background: #fff; }
        /* Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px 16px 28px;
            text-align: center;
            color: #fff;
        }
        .page-header img {
            height: 50px; width: auto;
            margin-bottom: 8px;
        }
        .page-header .company-name { font-size: 13px; opacity: .85; margin-bottom: 2px; }
        .page-header h1 { font-size: 18px; font-weight: 700; }
        /* Wrapper */
        .content-wrap {
            max-width: 1100px;
            margin: -16px auto 0;
            padding: 0 16px;
        }
        /* Customer Card */
        .customer-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 16px;
            margin-bottom: 14px;
        }
        .card-title {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .8px;
            color: var(--primary); margin-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 12px;
        }
        .info-item label {
            display: block; font-size: 10px; color: var(--gray-400);
            font-weight: 600; text-transform: uppercase; letter-spacing: .5px;
            margin-bottom: 2px;
        }
        .info-item span { font-size: 13px; font-weight: 600; color: var(--gray-800); }
        .info-item.full { grid-column: 1 / -1; }
        .addon-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 4px;
        }
        .addon-pill {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            background: var(--gray-100);
            color: var(--gray-600);
            font-size: 11px;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block; padding: 3px 10px;
            border-radius: 20px; font-size: 11px; font-weight: 700;
        }
        .status-active   { background:#d1fae5; color:#065f46; }
        .status-inactive { background:#fee2e2; color:#991b1b; }
        .inv-note {
            margin-top: 10px; padding: 10px 12px;
            background: #fffbeb; border-left: 3px solid var(--warning);
            border-radius: 0 8px 8px 0; font-size: 12px;
            color: var(--gray-600); line-height: 1.5;
        }
        /* Section title */
        .section-title {
            font-size: 13px; font-weight: 700; color: var(--gray-600);
            margin: 16px 0 8px; display: flex; align-items: center; gap: 6px;
        }
        .section-title i { color: var(--primary); }
        /* Buttons */
        .btn-action {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 7px 13px; border-radius: 8px; font-size: 12px;
            font-weight: 600; text-decoration: none; cursor: pointer;
            border: none; transition: opacity .15s, transform .1s;
        }
        .btn-action:active { transform: scale(.96); }
        .btn-show { background: var(--gray-100); color: var(--gray-800); }
        .btn-pay  { background: linear-gradient(135deg, var(--primary), var(--primary-d)); color: #fff; }
        .btn-show:hover { background: var(--gray-200); }
        .btn-pay:hover  { opacity: .9; }
        .btn-pay:disabled { background: var(--gray-200); color: var(--gray-500); cursor: not-allowed; opacity: 1; }
        .inv-actions { display: flex; gap: 6px; flex-wrap: wrap; }
        /* Badge */
        .badge-status {
            display: inline-block; padding: 3px 10px;
            border-radius: 20px; font-size: 11px; font-weight: 700;
        }
        .badge-unpaid { background:#fef3c7; color:#92400e; }
        .badge-paid   { background:#d1fae5; color:#065f46; }
        .badge-cancel { background:#f3f4f6; color:var(--gray-600); }
        /* Mobile: Cards */
        .invoice-list { display: none; flex-direction: column; gap: 12px; }
        .inv-card {
            background: #fff; border-radius: var(--radius); box-shadow: var(--shadow);
            padding: 14px 16px; border-left: 4px solid var(--gray-200);
        }
        .inv-card.unpaid { border-left-color: var(--warning); }
        .inv-card.paid   { border-left-color: var(--success); }
        .inv-card.cancel { border-left-color: var(--gray-400); }
        .inv-top {
            display: flex; justify-content: space-between;
            align-items: flex-start; margin-bottom: 10px;
        }
        .inv-number { font-size: 13px; font-weight: 700; }
        .inv-date   { font-size: 11px; color: var(--gray-400); margin-top: 2px; }
        .inv-bottom {
            display: flex; justify-content: space-between;
            align-items: center; margin-top: 6px;
        }
        .inv-total { font-size: 17px; font-weight: 800; color: var(--primary-d); }
        /* Desktop: Table */
        .invoice-table-wrap {
            background: #fff; border-radius: var(--radius);
            box-shadow: var(--shadow); overflow: hidden;
        }
        .invoice-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .invoice-table thead tr {
            background: var(--gray-50); border-bottom: 2px solid var(--gray-200);
        }
        .invoice-table th {
            padding: 10px 14px; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
            color: var(--gray-600); text-align: left;
        }
        .invoice-table tbody tr { border-bottom: 1px solid var(--gray-100); }
        .invoice-table tbody tr:last-child { border-bottom: none; }
        .invoice-table tbody tr:hover { background: var(--gray-50); }
        .invoice-table td { padding: 10px 14px; vertical-align: middle; }
        .invoice-table .amount { font-weight: 700; color: var(--primary-d); }
        /* Responsive switch */
        @media (max-width: 599px) {
            .invoice-list       { display: flex; flex-direction: column; }
            .invoice-table-wrap { display: none; }
        }
        @media (min-width: 600px) {
            .invoice-list       { display: flex; flex-direction: column; gap: 14px; }
            .invoice-table-wrap { display: none; }
            /* Bigger cards on desktop */
            .inv-card           { padding: 20px 24px; border-left-width: 5px; border-radius: 14px; }
            .inv-number         { font-size: 15px; }
            .inv-date           { font-size: 13px; }
            .inv-total          { font-size: 22px; }
            .badge-status       { font-size: 12px; padding: 4px 14px; }
            .btn-action         { padding: 9px 18px; font-size: 13px; }
            .info-grid          { grid-template-columns: repeat(3, 1fr); }
            .customer-card      { padding: 20px 24px; }
            .content-wrap       { padding: 0 24px; }
        }
        /* Footer */
        .page-footer {
            max-width: 720px; margin: 24px auto 0;
            padding: 0 12px; text-align: center;
            font-size: 11px; color: var(--gray-400); line-height: 1.6;
        }
        /* Multi-select: checkbox */
        .inv-checkbox {
            width: 20px; height: 20px; margin-right: 8px;
            accent-color: var(--primary); cursor: pointer; flex-shrink: 0;
        }
        .inv-card-header { display: flex; align-items: flex-start; }
        .inv-card.selected {
            border-left-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(102,126,234,.3), var(--shadow);
        }
        /* Select-all bar */
        .select-bar {
            background: #fff; border-radius: var(--radius); box-shadow: var(--shadow);
            padding: 10px 14px; margin-bottom: 10px;
            display: flex; align-items: center; justify-content: space-between;
            border: 1px dashed var(--primary);
        }
        .select-bar label {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 600; color: var(--primary); cursor: pointer;
        }
        .select-bar span { font-size: 12px; color: var(--gray-400); }
        /* Sticky payment bar */
        .pay-bar {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: #fff; border-top: 2px solid var(--primary);
            padding: 12px 16px 16px;
            box-shadow: 0 -4px 20px rgba(0,0,0,.12);
            display: none; z-index: 100;
            align-items: center; gap: 12px;
        }
        .pay-bar.visible { display: flex; }
        .pay-bar-info { flex: 1; }
        .pay-bar-count { font-size: 11px; color: var(--gray-400); margin-bottom: 2px; }
        .pay-bar-total { font-size: 18px; font-weight: 800; color: var(--primary-d); }
        .pay-bar-fee   { font-size: 10px; color: var(--gray-400); margin-top: 1px; }
        .btn-pay-now {
            background: linear-gradient(135deg, var(--primary), var(--primary-d));
            color: #fff; border: none; padding: 12px 22px;
            border-radius: 10px; font-size: 14px; font-weight: 700;
            cursor: pointer; display: flex; align-items: center; gap: 6px;
            white-space: nowrap; font-family: inherit;
        }
        /* Modal */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.55); z-index: 200;
            align-items: flex-end; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-sheet {
            background: #fff; border-radius: 20px 20px 0 0;
            width: 100%; max-width: 600px;
            padding: 20px 20px 32px; max-height: 90vh; overflow-y: auto;
        }
        .modal-handle {
            width: 40px; height: 4px; background: var(--gray-200);
            border-radius: 2px; margin: 0 auto 16px;
        }
        .modal-title { font-size: 15px; font-weight: 800; color: var(--gray-800); margin-bottom: 4px; }
        .modal-subtitle { font-size: 12px; color: var(--gray-400); margin-bottom: 14px; }
        .summary-box { background: var(--gray-50); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; }
        .summary-row { display: flex; justify-content: space-between; font-size: 12px; color: var(--gray-600); margin-bottom: 4px; }
        .summary-row.total { font-size: 15px; font-weight: 800; color: var(--gray-800); border-top: 1px solid var(--gray-200); padding-top: 8px; margin-top: 4px; }
        .summary-row.total span:last-child { color: var(--primary-d); }
        .gw-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: var(--gray-400); margin-bottom: 10px; }
        .gw-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .gw-btn {
            flex: 1 1 calc(50% - 5px); min-width: 130px;
            padding: 12px 14px; border-radius: 10px;
            border: 1.5px solid var(--gray-200);
            background: #fff; cursor: pointer; text-align: left;
            transition: border-color .15s, box-shadow .15s; font-family: inherit;
        }
        .gw-btn:hover { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(102,126,234,.12); }
        .gw-btn .gw-name { font-size: 13px; font-weight: 700; color: var(--gray-800); }
        .gw-btn .gw-sub  { font-size: 11px; color: var(--gray-400); margin-top: 2px; }
        .gw-btn .gw-icon { font-size: 20px; margin-bottom: 4px; }
        .tripay-channels { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .ch-btn {
            flex: 1 1 calc(33% - 6px); min-width: 90px;
            padding: 10px 8px; border-radius: 8px;
            border: 1.5px solid var(--gray-200);
            background: #fff; cursor: pointer; text-align: center;
            font-size: 11px; font-weight: 700; color: var(--gray-700);
            font-family: inherit; transition: border-color .15s;
        }
        .ch-btn:hover { border-color: #10b981; background: #f0fdf4; color: #065f46; }
        .ch-back {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 600; color: var(--primary);
            cursor: pointer; margin-bottom: 12px; border: none; background: none;
            font-family: inherit; padding: 0;
        }
        @media (min-width: 600px) {
            .modal-overlay { align-items: center; }
            .modal-sheet   { border-radius: 16px; max-height: 80vh; }
        }
        /* Empty */
        .empty-state {
            background: #fff; border-radius: var(--radius);
            box-shadow: var(--shadow); padding: 40px 16px;
            text-align: center; color: var(--gray-400);
        }
        .empty-state i { font-size: 36px; margin-bottom: 10px; display: block; }
        /* Pagination */
        .pager {
            display: flex; align-items: center; justify-content: center;
            gap: 6px; margin-top: 14px; flex-wrap: wrap;
        }
        .pager-btn {
            min-width: 34px; height: 34px; padding: 0 10px;
            border-radius: 8px; border: 1.5px solid var(--gray-200);
            background: #fff; font-size: 13px; font-weight: 600;
            color: var(--gray-600); cursor: pointer; font-family: inherit;
            display: flex; align-items: center; justify-content: center;
            transition: border-color .15s, background .15s;
        }
        .pager-btn:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); }
        .pager-btn.active { background: var(--primary); border-color: var(--primary); color: #fff; }
        .pager-btn:disabled { opacity: .35; cursor: default; }
        .pager-info { font-size: 11px; color: var(--gray-400); text-align: center; margin-top: 6px; }
    </style>
</head>
<body>

@if(session('error'))
<div style="background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;padding:12px 16px;margin:8px;border-radius:6px;font-size:14px;position:sticky;top:0;z-index:9999;">
    <strong>&#9888; Gagal:</strong> {{ session('error') }}
</div>
@endif
@if(session('success'))
<div style="background:#d4edda;color:#155724;border:1px solid #c3e6cb;padding:12px 16px;margin:8px;border-radius:6px;font-size:14px;position:sticky;top:0;z-index:9999;">
    <strong>&#10003;</strong> {{ session('success') }}
</div>
@endif

{{-- Header --}}
<div class="page-header">
    <img src="{{ tenant_img('logoinv.png', 'dashboard/dist/img/logoinv.png') }}" alt="Logo">
    <div class="company-name">{{ $companyName }}</div>
    <h1>Data Tagihan</h1>
</div>

<div class="content-wrap">

    {{-- Customer Info --}}
    <div class="customer-card">
        <div class="card-title"><i class="fas fa-user-circle"></i> Info Pelanggan</div>
        <div class="info-grid">
            <div class="info-item">
                <label>ID Pelanggan</label>
                <span>{{ $customer->customer_id }}</span>
            </div>
            <div class="info-item">
                <label>Status</label>
                <span class="status-badge {{ strtolower($customer->status_name ?? '') === 'active' ? 'status-active' : 'status-inactive' }}">
                    {{ $customer->status_name ?? '-' }}
                </span>
            </div>
            <div class="info-item full">
                <label>Nama</label>
                <span>{{ $customer->name }}</span>
            </div>
            <div class="info-item full">
                <label>Plan Aktif</label>
                <span>
                    {{ $customer->plan_name ?? '-' }}
                    @if(isset($customer->plan_price))
                        · Rp {{ number_format($customer->plan_price, 0, ',', '.') }}
                    @endif
                </span>
            </div>
            <div class="info-item full">
                <label>Add-on Aktif</label>
                @if(isset($customerAddons) && $customerAddons->count() > 0)
                    <div class="addon-list">
                        @foreach($customerAddons as $addon)
                            <span class="addon-pill">
                                {{ $addon->name }} · Rp {{ number_format($addon->price ?? 0, 0, ',', '.') }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <span>-</span>
                @endif
            </div>
            <div class="info-item">
                <label>Telepon</label>
                <span>{{ $customer->phone ?: '-' }}</span>
            </div>
            <div class="info-item full">
                <label>Alamat</label>
                <span style="font-weight:400;">{{ $customer->address ?: '-' }}</span>
            </div>
        </div>
        @if(!empty($invNote))
            <div class="inv-note">{!! $invNote !!}</div>
        @endif
    </div>

    {{-- Invoice section --}}
    @php
        $unpaidCount     = $suminvoice->where('payment_status', 0)->count();
        $selectableCount = $suminvoice->where('payment_status', 0)->filter(function($inv) use ($pendingBundleByInvoice) {
            if (str_starts_with($inv->payment_id ?? '', 'duitku:')) return false;
            if (str_starts_with($inv->payment_id ?? '', 'winpay:')) return false;
            if ($pendingBundleByInvoice->has($inv->id)) return false;
            return true;
        })->count();

        $earliestSelectableInvoiceId = $suminvoice
            ->where('payment_status', 0)
            ->filter(function($inv) use ($pendingBundleByInvoice) {
                if (str_starts_with($inv->payment_id ?? '', 'duitku:')) return false;
                if (str_starts_with($inv->payment_id ?? '', 'winpay:')) return false;
                if ($pendingBundleByInvoice->has($inv->id)) return false;
                return true;
            })
            ->sortBy([
                ['date', 'asc'],
                ['id', 'asc'],
            ])
            ->pluck('id')
            ->first();

        $selectableOrderMap = $suminvoice
            ->where('payment_status', 0)
            ->filter(function($inv) use ($pendingBundleByInvoice) {
                if (str_starts_with($inv->payment_id ?? '', 'duitku:')) return false;
                if (str_starts_with($inv->payment_id ?? '', 'winpay:')) return false;
                if ($pendingBundleByInvoice->has($inv->id)) return false;
                return true;
            })
            ->sortBy([
                ['date', 'asc'],
                ['id', 'asc'],
            ])
            ->pluck('id')
            ->values()
            ->flip();
    @endphp

    <div class="section-title">
        <i class="fas fa-file-invoice"></i>
        Riwayat Tagihan
        <span style="margin-left:auto;font-weight:400;font-size:12px;color:var(--gray-400);">
            {{ count($suminvoice) }} tagihan
        </span>
    </div>

    @if(count($suminvoice) === 0)
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            Belum ada tagihan
        </div>
    @else

    {{-- Select-all bar — hanya tampil jika ada >1 yang bisa dipilih (bukan Duitku pending) --}}
    @if($selectableCount > 1)
    <div class="select-bar">
        <label>
            <input type="checkbox" id="selectAll" class="inv-checkbox">
            Pilih Semua Tagihan Belum Lunas
        </label>
        <span id="selectHint">0 dipilih</span>
    </div>
    @endif

    {{-- Invoice Cards --}}
    <div class="invoice-list" id="invoiceList">
        @foreach($suminvoice as $inv)
        @php
            $isUnpaid      = (int)$inv->payment_status === 0;
            $dkpPending    = $isUnpaid && str_starts_with($inv->payment_id ?? '', 'duitku:');
            $dkpUrl        = '';
            if ($dkpPending) {
                $dkpParts = explode('|', substr($inv->payment_id, strlen('duitku:')), 2);
                $dkpUrl   = $dkpParts[1] ?? '';
            }
            $wpPending     = $isUnpaid && str_starts_with($inv->payment_id ?? '', 'winpay:');
            $wpUrl         = '';
            if ($wpPending) {
                $wpParts = explode('|', substr($inv->payment_id, strlen('winpay:')), 2);
                $wpUrl   = $wpParts[1] ?? '';
            }
            $bundlePending = $pendingBundleByInvoice->get($inv->id);
            $isSelectable  = $isUnpaid && !$dkpPending && !$wpPending && ($bundlePending === null);
            $isEarliestSelectable = $isSelectable && ((int) $inv->id === (int) $earliestSelectableInvoiceId);
            $sc = match((int)$inv->payment_status) { 1=>'paid', 2=>'cancel', default=>'unpaid' };
            $bc = match((int)$inv->payment_status) { 1=>'badge-paid', 2=>'badge-cancel', default=>'badge-unpaid' };
            $sl = match(true) {
                (int)$inv->payment_status === 1 => 'LUNAS',
                (int)$inv->payment_status === 2 => 'BATAL',
                $dkpPending || $wpPending || ($bundlePending !== null) => 'MENUNGGU',
                default => 'BELUM BAYAR',
            };
        @endphp
        <div class="inv-card {{ $sc }}" id="card-{{ $inv->id }}">
            <div class="inv-card-header">
                @if($isSelectable)
                <input type="checkbox"
                    class="inv-checkbox inv-select"
                    data-id="{{ $inv->id }}"
                    data-order="{{ $selectableOrderMap->get($inv->id, '') }}"
                    data-amount="{{ $inv->total_amount }}"
                    data-number="{{ $inv->number }}"
                    onchange="updateSelection()">
                @endif
                <div style="flex:1">
                    <div class="inv-top">
                        <div>
                            <div class="inv-number">#{{ $inv->number }}</div>
                            <div class="inv-date">{{ $inv->date }}</div>
                        </div>
                        <span class="badge-status {{ $bc }}">{{ $sl }}</span>
                    </div>
                    <div class="inv-bottom">
                        <div class="inv-total">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</div>
                        <div class="inv-actions">
                            <a href="/suminvoice/{{ $inv->tempcode }}/viewinvoice" class="btn-action btn-show">
                                <i class="fas fa-eye"></i> Lihat
                            </a>
                            @if($dkpPending)
                                @if(!empty($dkpUrl))
                                <a href="{{ $dkpUrl }}" target="_blank" class="btn-action btn-pay">
                                    <i class="fas fa-credit-card"></i> Lanjutkan
                                </a>
                                @endif
                                <form method="POST" action="{{ url('/duitku/reset') }}" style="display:inline;" class="js-confirm-change-payment">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $inv->id }}">
                                    <button type="submit" class="btn-action btn-show" title="Ganti metode pembayaran">
                                        <i class="fas fa-rotate-left"></i> Ganti Metode Bayar
                                    </button>
                                </form>
                            @elseif($wpPending)
                                @if(!empty($wpUrl))
                                <a href="{{ $wpUrl }}" target="_blank" class="btn-action btn-pay">
                                    <i class="fas fa-credit-card"></i> Lanjutkan
                                </a>
                                @endif
                                <form method="POST" action="{{ url('/payment/reset') }}" style="display:inline;" class="js-confirm-change-payment">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $inv->id }}">
                                    <button type="submit" class="btn-action btn-show" title="Ganti metode pembayaran">
                                        <i class="fas fa-rotate-left"></i> Ganti Metode Bayar
                                    </button>
                                </form>
                            @elseif($bundlePending)
                                {{-- kosong, tombol ditampilkan di footer card di bawah --}}
                            @elseif($isUnpaid)
                            @if($isEarliestSelectable)
                            <button type="button" class="btn-action btn-pay btn-pay-single"
                                onclick="quickPay({{ $inv->id }}, {{ $inv->total_amount }}, '{{ $inv->number }}')">
                                <i class="fas fa-credit-card"></i> Bayar
                            </button>
                            @else
                            <button type="button" class="btn-action btn-pay btn-pay-single" disabled
                                title="Bayar invoice terlama terlebih dahulu">
                                <i class="fas fa-credit-card"></i> Bayar
                            </button>
                            @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div id="invPager" class="pager"></div>
    <div id="invPagerInfo" class="pager-info"></div>

    @endif

</div>

<div class="page-footer">
    {{ $companyAddress1 }}<br>
    {{ $companyAddress2 }}
</div>

@php
    $firstBundle      = $pendingBundleByInvoice->unique('bundle_ref')->first();
    $bundleInvCount   = $firstBundle ? $pendingBundleByInvoice->where('bundle_ref', $firstBundle->bundle_ref)->count() : 0;
@endphp

@if($firstBundle)
{{-- Sticky Bundle Bar --}}
<div class="bundle-bar" id="bundleBar">
    <div class="bundle-bar-info">
        <div class="bundle-bar-label">Transaksi menunggu pembayaran</div>
        <div class="bundle-bar-gw">{{ strtoupper($firstBundle->gateway) }}@if($firstBundle->tripay_method) &middot; {{ $firstBundle->tripay_method }}@endif</div>
        <div class="bundle-bar-count">{{ $bundleInvCount }} invoice dalam 1 transaksi</div>
    </div>
    <div class="bundle-bar-actions">
        <form method="POST" action="{{ url('/bundle/cancel') }}" style="margin:0;" class="js-confirm-change-payment">
            @csrf
            <input type="hidden" name="bundle_ref" value="{{ $firstBundle->bundle_ref }}">
            <button type="submit" class="btn-bundle-pay">
                <i class="fas fa-rotate-left"></i> Ganti Metode Bayar
            </button>
        </form>
        @if(!empty($firstBundle->payment_url))
        <a href="{{ $firstBundle->payment_url }}" target="_blank" class="btn-bundle-pay primary">
            <i class="fas fa-credit-card"></i> Lanjutkan Pembayaran
        </a>
        @endif
    </div>
</div>
@endif

{{-- Sticky Payment Bar --}}
<div class="pay-bar" id="payBar">
    <div class="pay-bar-info">
        <div class="pay-bar-count" id="barCount">0 tagihan dipilih</div>
        <div class="pay-bar-total" id="barTotal">Rp 0</div>
        <div class="pay-bar-fee" id="barFee">+ biaya transaksi gateway</div>
    </div>
    <button class="btn-pay-now" onclick="openPayModal()">
        <i class="fas fa-credit-card"></i> Bayar Sekarang
    </button>
</div>

{{-- Payment Modal --}}
<div class="modal-overlay" id="payModal" onclick="closeModalOutside(event)">
    <div class="modal-sheet">
        <div class="modal-handle"></div>

        {{-- Main gateway selection --}}
        <div id="viewMain">
            <div class="modal-title">Pilih Metode Pembayaran</div>
            <div class="modal-subtitle">Tagihan yang dipilih digabung dalam satu transaksi</div>

            <div class="summary-box" id="summaryBox"></div>

            <div class="gw-section-title">Pilih gateway pembayaran:</div>

            @if(isset($gateways) && $gateways->count() > 0)
            <div class="gw-grid">
                @foreach($gateways as $gw)
                @switch($gw->provider)
                    @case('duitku')
                        <button type="button" class="gw-btn" onclick="submitGateway('duitku')">
                            <div class="gw-icon"><i class="{{ $gw->icon }}"></i></div>
                            <div class="gw-name">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                            <div class="gw-sub">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? 'VA, E-Wallet, QRIS' }}</div>
                            @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                            <div class="gw-sub" style="color:#ef4444;margin-top:4px;">{{ $gw->feeDescription() }}</div>
                            @endif
                        </button>
                    @break
                    @case('duitku2')
                        <button type="button" class="gw-btn" onclick="submitGateway('duitku2')">
                            <div class="gw-icon"><i class="{{ $gw->icon }}"></i></div>
                            <div class="gw-name">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                            <div class="gw-sub">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? 'VA, E-Wallet, QRIS' }}</div>
                            @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                            <div class="gw-sub" style="color:#ef4444;margin-top:4px;">{{ $gw->feeDescription() }}</div>
                            @endif
                        </button>
                    @break
                    @case('winpay')
                        <button type="button" class="gw-btn" onclick="submitGateway('winpay')">
                            <div class="gw-icon"><i class="{{ $gw->icon }}"></i></div>
                            <div class="gw-name">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                            <div class="gw-sub">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? 'Bank VA' }}</div>
                            @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                            <div class="gw-sub" style="color:#ef4444;margin-top:4px;">{{ $gw->feeDescription() }}</div>
                            @endif
                        </button>
                    @break
                    @case('winpay2')
                        <button type="button" class="gw-btn" onclick="submitGateway('winpay2')">
                            <div class="gw-icon"><i class="{{ $gw->icon }}"></i></div>
                            <div class="gw-name">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                            <div class="gw-sub">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? 'QRIS & E-Wallet' }}</div>
                            @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                            <div class="gw-sub" style="color:#ef4444;margin-top:4px;">{{ $gw->feeDescription() }}</div>
                            @endif
                        </button>
                    @break
                    @case('tripay')
                        <button type="button" class="gw-btn" onclick="showTripayChannels()">
                            <div class="gw-icon"><i class="{{ $gw->icon }}"></i></div>
                            <div class="gw-name">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                            <div class="gw-sub">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? 'Pilih saluran bayar' }}</div>
                            @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                            <div class="gw-sub" style="color:#ef4444;margin-top:4px;">{{ $gw->feeDescription() }}</div>
                            @endif
                        </button>
                    @break
                @endswitch
                @endforeach
            </div>
            @else
            <p style="color:var(--gray-400);font-size:13px;text-align:center;padding:20px 0;">
                Tidak ada metode pembayaran aktif.
            </p>
            @endif

            <button type="button" style="margin-top:16px;width:100%;padding:12px;border:1.5px solid var(--gray-200);border-radius:10px;background:#fff;font-size:13px;color:var(--gray-600);cursor:pointer;font-family:inherit;" onclick="closeModal()">
                Batal
            </button>
        </div>

        {{-- Tripay channel selection --}}
        <div id="viewTripay" style="display:none;">
            <button class="ch-back" onclick="showMainView()">
                <i class="fas fa-arrow-left"></i> Kembali
            </button>
            <div class="modal-title">Pilih Saluran Tripay</div>
            <div class="modal-subtitle">Pilih bank atau metode pembayaran</div>
            <div class="tripay-channels">
                <button type="button" class="ch-btn" onclick="submitGateway('tripay','BCAVA')"><i class="fas fa-building-columns"></i><br>BCA VA</button>
                <button type="button" class="ch-btn" onclick="submitGateway('tripay','BRIVA')"><i class="fas fa-building-columns"></i><br>BRI VA</button>
                <button type="button" class="ch-btn" onclick="submitGateway('tripay','BNIVA')"><i class="fas fa-building-columns"></i><br>BNI VA</button>
                <button type="button" class="ch-btn" onclick="submitGateway('tripay','MANDIRIVA')"><i class="fas fa-building-columns"></i><br>Mandiri VA</button>
                <button type="button" class="ch-btn" onclick="submitGateway('tripay','PERMATAVA')"><i class="fas fa-building-columns"></i><br>Permata VA</button>
                <button type="button" class="ch-btn" onclick="submitGateway('tripay','QRIS')"><i class="fas fa-qrcode"></i><br>QRIS</button>
            </div>
        </div>
    </div>
</div>

{{-- Hidden form for bundle payment --}}
<form id="bundleForm" method="POST" action="{{ url('/invoice/bundle-pay') }}">
    @csrf
    <input type="hidden" name="gateway"       id="f_gateway">
    <input type="hidden" name="tripay_method" id="f_tripay_method">
    <input type="hidden" name="return_path"   value="{{ $encrypted }}">
    <div id="f_invoice_ids"></div>
</form>

<script>
    let selectedIds     = [];
    let selectedAmounts = {};
    let selectedNumbers = {};

    function getOrderedSelectableCheckboxes() {
        return Array.from(document.querySelectorAll('.inv-select'))
            .sort((a, b) => Number(a.dataset.order || 0) - Number(b.dataset.order || 0));
    }

    function enforceSequentialChecks() {
        const ordered = getOrderedSelectableCheckboxes();
        let firstGap = 0;
        while (firstGap < ordered.length && ordered[firstGap].checked) firstGap++;

        ordered.forEach((cb, idx) => {
            const allow = idx <= firstGap;
            cb.disabled = !allow;
            if (!allow) cb.checked = false;
        });
    }

    function updateSelection() {
        enforceSequentialChecks();
        selectedIds = []; selectedAmounts = {}; selectedNumbers = {};
        document.querySelectorAll('.inv-select:checked').forEach(cb => {
            selectedIds.push(cb.dataset.id);
            selectedAmounts[cb.dataset.id] = parseFloat(cb.dataset.amount);
            selectedNumbers[cb.dataset.id] = cb.dataset.number;
        });
        document.querySelectorAll('.inv-select').forEach(cb => {
            const card = document.getElementById('card-' + cb.dataset.id);
            if (card) card.classList.toggle('selected', cb.checked);
        });
        const all = document.querySelectorAll('.inv-select');
        const sa  = document.getElementById('selectAll');
        if (sa) {
            sa.indeterminate = selectedIds.length > 0 && selectedIds.length < all.length;
            sa.checked = selectedIds.length === all.length && all.length > 0;
        }
        const hint = document.getElementById('selectHint');
        if (hint) hint.textContent = selectedIds.length + ' dipilih';

        // Sembunyikan tombol Bayar hanya pada invoice yang sedang dicentang
        document.querySelectorAll('.inv-select').forEach(cb => {
            const btn = document.querySelector('#card-' + cb.dataset.id + ' .btn-pay-single');
            if (btn) btn.style.display = cb.checked ? 'none' : '';
        });

        refreshPayBar();
    }

    const saCb = document.getElementById('selectAll');
    if (saCb) saCb.addEventListener('change', () => {
        const ordered = getOrderedSelectableCheckboxes();
        ordered.forEach(cb => cb.checked = saCb.checked);
        updateSelection();
    });

    function getTotal() { return Object.values(selectedAmounts).reduce((a,b)=>a+b, 0); }
    function fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

    function refreshPayBar() {
        const bar = document.getElementById('payBar');
        if (selectedIds.length === 0) { bar.classList.remove('visible'); return; }
        bar.classList.add('visible');
        document.getElementById('barCount').textContent = selectedIds.length + ' tagihan dipilih';
        document.getElementById('barTotal').textContent = fmt(getTotal());
    }

    function quickPay(id, amount, number) {
        // Pastikan tombol bayar muncul kembali sebelum update agar tidak hilang saat modal terbuka
        document.querySelectorAll('.btn-pay-single').forEach(btn => btn.style.display = '');
        document.querySelectorAll('.inv-select').forEach(cb => cb.checked = (cb.dataset.id == id));
        updateSelection();
        openPayModal();
    }

    function openPayModal() {
        if (selectedIds.length === 0) return;
        buildSummary(); showMainView();
        document.getElementById('payModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        document.getElementById('payModal').classList.remove('open');
        document.body.style.overflow = '';
    }
    function closeModalOutside(e) { if (e.target === document.getElementById('payModal')) closeModal(); }
    function showMainView()    { document.getElementById('viewMain').style.display = ''; document.getElementById('viewTripay').style.display = 'none'; }
    function showTripayChannels() { document.getElementById('viewMain').style.display = 'none'; document.getElementById('viewTripay').style.display = ''; }

    function buildSummary() {
        const total = getTotal();
        let rows = '';
        Object.keys(selectedNumbers).forEach(id => {
            rows += `<div class="summary-row"><span>#${selectedNumbers[id]}</span><span>${fmt(selectedAmounts[id])}</span></div>`;
        });
        rows += `<div class="summary-row total"><span>Total</span><span>${fmt(total)}</span></div>`;
        document.getElementById('summaryBox').innerHTML = rows;
    }

    function submitGateway(gateway, tripayMethod) {
        document.getElementById('f_gateway').value       = gateway;
        document.getElementById('f_tripay_method').value = tripayMethod || '';
        const container = document.getElementById('f_invoice_ids');
        container.innerHTML = '';
        selectedIds.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'invoice_ids[]'; inp.value = id;
            container.appendChild(inp);
        });
        document.getElementById('bundleForm').submit();
    }

    function bindChangePaymentConfirmations() {
        document.querySelectorAll('.js-confirm-change-payment').forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                Swal.fire({
                    title: 'Ganti metode bayar',
                    text: 'Ganti metode bayar akan membatalkan transaksi saat ini.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, batalkan transaksi ini',
                    cancelButtonText: 'Kembali',
                    reverseButtons: true,
                    focusCancel: true,
                }).then(result => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    }

    // ── Pagination ──
    const PER_PAGE = 5;
    let currentPage = 1;

    function initPager() {
        const cards  = document.querySelectorAll('#invoiceList .inv-card');
        const total  = cards.length;
        const pages  = Math.ceil(total / PER_PAGE);
        if (pages <= 1) return; // tidak perlu pager kalau ≤5
        goToPage(1);
    }

    function goToPage(page) {
        const cards  = document.querySelectorAll('#invoiceList .inv-card');
        const total  = cards.length;
        const pages  = Math.ceil(total / PER_PAGE);
        currentPage  = Math.max(1, Math.min(page, pages));

        cards.forEach((card, i) => {
            const inPage = i >= (currentPage - 1) * PER_PAGE && i < currentPage * PER_PAGE;
            card.style.display = inPage ? '' : 'none';
        });

        // Build pager buttons
        const pager = document.getElementById('invPager');
        if (!pager) return;
        let html = `<button class="pager-btn" onclick="goToPage(${currentPage-1})" ${currentPage===1?'disabled':''}>&#8249;</button>`;
        for (let p = 1; p <= pages; p++) {
            html += `<button class="pager-btn ${p===currentPage?'active':''}" onclick="goToPage(${p})">${p}</button>`;
        }
        html += `<button class="pager-btn" onclick="goToPage(${currentPage+1})" ${currentPage===pages?'disabled':''}>&#8250;</button>`;
        pager.innerHTML = html;

        const info = document.getElementById('invPagerInfo');
        if (info) {
            const from = (currentPage - 1) * PER_PAGE + 1;
            const to   = Math.min(currentPage * PER_PAGE, total);
            info.textContent = `Menampilkan ${from}–${to} dari ${total} tagihan`;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        initPager();
        enforceSequentialChecks();
        adjustPayBarOffset();
        bindChangePaymentConfirmations();
    });

    function adjustPayBarOffset() {
        const bundleBar = document.getElementById('bundleBar');
        const payBar    = document.getElementById('payBar');
        if (!payBar) return;
        const h = bundleBar ? bundleBar.offsetHeight : 0;
        payBar.style.bottom = h + 'px';
        // Extra body padding so content isn't hidden behind both bars
        document.body.style.paddingBottom = (100 + h) + 'px';
    }
</script>

</body>
</html>
