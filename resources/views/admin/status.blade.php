@extends('layout.main')
@section('title', 'System Status')

@section('content')
<style>
  /* =====================================================
     ADMIN STATUS DASHBOARD — Custom Styles
  ===================================================== */
  .as-hero {
    border-radius: 12px;
    padding: 16px 18px;
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
  }
  .as-hero .as-ghost {
    position: absolute;
    right: -10px;
    bottom: -10px;
    font-size: 56px;
    opacity: 0.15;
  }
  .as-hero .as-label { font-size: 11px; font-weight: 600; letter-spacing: 1.2px; text-transform: uppercase; opacity: 0.85; }
  .as-hero .as-number { font-size: 28px; font-weight: 800; line-height: 1.2; margin: 2px 0 4px; }
  .as-hero .as-sub    { font-size: 11px; opacity: 0.75; }

  .g-blue   { background: linear-gradient(135deg, #1e88e5, #1565c0); }
  .g-green  { background: linear-gradient(135deg, #43a047, #2e7d32); }
  .g-red    { background: linear-gradient(135deg, #e53935, #b71c1c); }
  .g-orange { background: linear-gradient(135deg, #fb8c00, #e65100); }
  .g-purple { background: linear-gradient(135deg, #8e24aa, #4a148c); }
  .g-teal   { background: linear-gradient(135deg, #00897b, #00574b); }
  .g-indigo { background: linear-gradient(135deg, #3949ab, #1a237e); }
  .g-pink   { background: linear-gradient(135deg, #d81b60, #880e4f); }

  /* Module section cards */
  .as-module .card-header {
    background: transparent;
    border-bottom: 2px solid var(--border, #e5e7eb);
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }
  .as-module .card-header i { margin-right: 7px; }

  /* Stat mini tiles */
  .stat-tile {
    border-radius: 8px;
    padding: 10px 14px;
    text-align: center;
    border: 1px solid var(--border, #e5e7eb);
    background: var(--bg-surface, #fff);
  }
  .stat-tile .st-num { font-size: 22px; font-weight: 800; line-height: 1.1; }
  .stat-tile .st-lbl { font-size: 10px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase; color: var(--text-muted, #9ca3af); margin-top: 2px; }

  /* Status dot */
  .dot-ok   { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; display: inline-block; margin-right: 5px; }
  .dot-warn { width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; display: inline-block; margin-right: 5px; }
  .dot-err  { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; display: inline-block; margin-right: 5px; }
  .dot-off  { width: 8px; height: 8px; border-radius: 50%; background: #9ca3af; display: inline-block; margin-right: 5px; }

  /* Gateway pill */
  .gw-pill {
    display: flex; align-items: center;
    padding: 8px 12px; border-radius: 8px;
    border: 1px solid var(--border, #e5e7eb);
    background: var(--bg-surface-2, #f9fafb);
    font-size: 13px; font-weight: 600;
    gap: 8px;
  }

  /* Privilege badge */
  .priv-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700; letter-spacing: 0.5px;
    margin: 2px;
  }
  .priv-admin   { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
  .priv-noc     { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
  .priv-user    { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
  .priv-marketing { background: #fce7f3; color: #9d174d; border: 1px solid #fbcfe8; }
  .priv-payment { background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; }
  .priv-accounting { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
  .priv-other   { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }

  /* Timestamp strip */
  .as-timestamp {
    font-size: 11px;
    color: var(--text-muted, #9ca3af);
    background: var(--bg-surface-2, #f9fafb);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 6px;
    padding: 4px 10px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }

  /* Table helpers */
  .table-xs td, .table-xs th { padding: 5px 8px; font-size: 12px; }

  /* Progress bar thin */
  .progress-xs { height: 5px; }

  /* Section header divider */
  .as-section-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-muted, #9ca3af);
    border-left: 3px solid var(--brand, #e53935);
    padding-left: 8px;
    margin-bottom: 12px;
  }

  body.dark-mode .stat-tile { background: var(--bg-surface-2) !important; }
  body.dark-mode .gw-pill   { background: var(--bg-surface-2) !important; }
</style>

{{-- ===================== HEADER ===================== --}}
<div class="container-fluid">
  <div class="row align-items-center mb-3">
    <div class="col-auto">
      <h5 class="mb-0"><i class="fas fa-shield-alt mr-2 text-danger"></i>Admin — System Status</h5>
    </div>
    <div class="col-auto ml-auto">
      <span class="as-timestamp">
        <i class="fas fa-sync-alt"></i>
        Last refresh: {{ now()->format('d M Y, H:i:s') }}
        &nbsp;|&nbsp;
        <a href="{{ url('admin-status') }}" style="color:inherit"><i class="fas fa-redo"></i> Refresh</a>
      </span>
    </div>
  </div>

  {{-- ======================================================
       ROW 1 — KPI HERO CARDS (ringkasan cepat)
  ====================================================== --}}
  <div class="row">
    {{-- Total Users --}}
    <div class="col-6 col-md-2 mb-3">
      <div class="as-hero g-indigo">
        <div class="as-label">Total Users</div>
        <div class="as-number">{{ $totalUsers }}</div>
        <div class="as-sub">{{ $usersByPrivilege->count() }} privilege</div>
        <i class="fas fa-users as-ghost"></i>
      </div>
    </div>
    {{-- Open Tickets --}}
    <div class="col-6 col-md-2 mb-3">
      <div class="as-hero g-red">
        <div class="as-label">Tiket Aktif</div>
        <div class="as-number">{{ $ticketOpenNow }}</div>
        <div class="as-sub">Open + Pending + Inprogress</div>
        <i class="fas fa-ticket-alt as-ghost"></i>
      </div>
    </div>
    {{-- Invoice Unpaid --}}
    <div class="col-6 col-md-2 mb-3">
      <div class="as-hero g-orange">
        <div class="as-label">Invoice Belum Bayar</div>
        <div class="as-number">{{ $invoiceUnpaid }}</div>
        <div class="as-sub">Transaksi hari ini: {{ $invoicePaidToday }}</div>
        <i class="fas fa-file-invoice-dollar as-ghost"></i>
      </div>
    </div>
    {{-- Active Customers --}}
    <div class="col-6 col-md-2 mb-3">
      <div class="as-hero g-green">
        <div class="as-label">Pelanggan Aktif</div>
        <div class="as-number">{{ $custActive }}</div>
        <div class="as-sub">Baru bulan ini: +{{ $custNewMonth }}</div>
        <i class="fas fa-wifi as-ghost"></i>
      </div>
    </div>
    {{-- WA Gateway --}}
    <div class="col-6 col-md-2 mb-3">
      <div class="as-hero g-teal">
        <div class="as-label">WA Success Rate</div>
        <div class="as-number">{{ $waSuccessRate }}%</div>
        <div class="as-sub">Bulan ini: {{ $waMonthTotal }} pesan</div>
        <i class="fab fa-whatsapp as-ghost"></i>
      </div>
    </div>
    {{-- HRD Approval --}}
    <div class="col-6 col-md-2 mb-3">
      <div class="as-hero g-purple">
        <div class="as-label">Approval HRD</div>
        <div class="as-number">{{ $leavePending + $overtimePending }}</div>
        <div class="as-sub">Izin: {{ $leavePending }} | Lembur: {{ $overtimePending }}</div>
        <i class="fas fa-clock as-ghost"></i>
      </div>
    </div>
  </div>

  {{-- ======================================================
       ROW 2 — TIKET & KEUANGAN
  ====================================================== --}}
  <div class="row">
    {{-- TIKET --}}
    <div class="col-md-5 mb-3">
      <div class="card as-module shadow-sm h-100">
        <div class="card-header">
          <i class="fas fa-ticket-alt text-danger"></i> Tiket
        </div>
        <div class="card-body p-2">
          <div class="row no-gutters mb-2">
            <div class="col px-1">
              <div class="stat-tile">
                <div class="st-num text-danger">{{ $ticketByStatus['Open'] }}</div>
                <div class="st-lbl">Open</div>
              </div>
            </div>
            <div class="col px-1">
              <div class="stat-tile">
                <div class="st-num text-warning">{{ $ticketByStatus['Pending'] }}</div>
                <div class="st-lbl">Pending</div>
              </div>
            </div>
            <div class="col px-1">
              <div class="stat-tile">
                <div class="st-num text-info">{{ $ticketByStatus['Inprogress'] }}</div>
                <div class="st-lbl">Inprogress</div>
              </div>
            </div>
            <div class="col px-1">
              <div class="stat-tile">
                <div class="st-num text-success">{{ $ticketByStatus['Solve'] }}</div>
                <div class="st-lbl">Solve</div>
              </div>
            </div>
            <div class="col px-1">
              <div class="stat-tile">
                <div class="st-num text-secondary">{{ $ticketByStatus['Close'] }}</div>
                <div class="st-lbl">Close</div>
              </div>
            </div>
          </div>
          @php
            $total = array_sum($ticketByStatus) ?: 1;
            $colors = ['Open'=>'#ef4444','Pending'=>'#f59e0b','Inprogress'=>'#3b82f6','Solve'=>'#22c55e','Close'=>'#9ca3af'];
          @endphp
          <div class="d-flex w-100" style="height:8px; border-radius:4px; overflow:hidden;">
            @foreach($ticketByStatus as $st => $cnt)
              <div style="width:{{ round($cnt/$total*100,1) }}%; background:{{ $colors[$st] }};" title="{{ $st }}: {{ $cnt }}"></div>
            @endforeach
          </div>
          <div class="d-flex justify-content-between mt-2 px-1" style="font-size:11px; color:var(--text-muted,#9ca3af);">
            <span>Hari ini: <b class="text-body">{{ $ticketToday }}</b></span>
            <span>Bulan ini: <b class="text-body">{{ $ticketThisMonth }}</b></span>
            <span>Total aktif: <b class="text-danger">{{ $ticketOpenNow }}</b></span>
          </div>
          <a href="{{ url('ticket') }}" class="btn btn-sm btn-outline-danger btn-block mt-2" style="font-size:11px;">
            <i class="fas fa-external-link-alt mr-1"></i> Kelola Tiket
          </a>
        </div>
      </div>
    </div>

    {{-- KEUANGAN --}}
    <div class="col-md-4 mb-3">
      <div class="card as-module shadow-sm h-100">
        <div class="card-header">
          <i class="fas fa-money-check-alt text-success"></i> Keuangan
        </div>
        <div class="card-body p-2">
          <div class="row no-gutters mb-2">
            <div class="col-6 px-1 mb-2">
              <div class="stat-tile">
                <div class="st-num text-danger">{{ $invoiceUnpaid }}</div>
                <div class="st-lbl">Invoice Belum Bayar</div>
              </div>
            </div>
            <div class="col-6 px-1 mb-2">
              <div class="stat-tile">
                <div class="st-num text-success">{{ $invoicePaidToday }}</div>
                <div class="st-lbl">Transaksi Hari Ini</div>
              </div>
            </div>
            <div class="col-6 px-1">
              <div class="stat-tile">
                <div class="st-num text-primary">{{ $invoicePaidMonth }}</div>
                <div class="st-lbl">Lunas Bulan Ini</div>
              </div>
            </div>
            <div class="col-6 px-1">
              <div class="stat-tile">
                <div class="st-num text-info" style="font-size:14px;">
                  Rp {{ number_format($revenueMonth, 0, ',', '.') }}
                </div>
                <div class="st-lbl">Revenue Bulan Ini</div>
              </div>
            </div>
          </div>
          <div class="text-center mt-1" style="font-size:11px; color:var(--text-muted,#9ca3af);">
            Revenue hari ini: <b class="text-body">Rp {{ number_format($revenueToday, 0, ',', '.') }}</b>
            &nbsp;|&nbsp; Akuntansi: <b class="text-body">{{ $accountingTotal }}</b> transaksi
          </div>
          <div class="d-flex gap-2 mt-2">
            <a href="{{ url('suminvoice') }}" class="btn btn-sm btn-outline-success flex-fill" style="font-size:11px;">Invoice</a>
            <a href="{{ url('suminvoice/transaction') }}" class="btn btn-sm btn-outline-info flex-fill" style="font-size:11px;">Transaksi</a>
          </div>
        </div>
      </div>
    </div>

    {{-- PELANGGAN --}}
    <div class="col-md-3 mb-3">
      <div class="card as-module shadow-sm h-100">
        <div class="card-header">
          <i class="fas fa-users text-primary"></i> Pelanggan
        </div>
        <div class="card-body p-2">
          <div class="d-flex align-items-center mb-2">
            <span class="dot-ok"></span>
            <span style="font-size:12px; flex:1;">Aktif</span>
            <b>{{ $custActive }}</b>
          </div>
          <div class="progress progress-xs mb-2">
            <div class="progress-bar bg-success" style="width:{{ $custActive / max(1,$custActive+$custBlock+$custInactive+$custPotential)*100 }}%"></div>
          </div>
          <div class="d-flex align-items-center mb-1">
            <span class="dot-err"></span>
            <span style="font-size:12px; flex:1;">Diblokir</span>
            <b>{{ $custBlock }}</b>
          </div>
          <div class="d-flex align-items-center mb-1">
            <span class="dot-off"></span>
            <span style="font-size:12px; flex:1;">Tidak Aktif</span>
            <b>{{ $custInactive }}</b>
          </div>
          <div class="d-flex align-items-center mb-2">
            <span class="dot-warn"></span>
            <span style="font-size:12px; flex:1;">Potensial</span>
            <b>{{ $custPotential }}</b>
          </div>
          <div class="text-center" style="font-size:11px; color:var(--text-muted,#9ca3af);">
            Baru bulan ini: <b class="text-body">+{{ $custNewMonth }}</b>
          </div>
          <a href="{{ url('customer') }}" class="btn btn-sm btn-outline-primary btn-block mt-2" style="font-size:11px;">
            <i class="fas fa-external-link-alt mr-1"></i> Kelola Pelanggan
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- ======================================================
       ROW 3 — HRD & ABSENSI
  ====================================================== --}}
  <div class="row">
    {{-- ABSENSI TODAY --}}
    <div class="col-md-4 mb-3">
      <div class="card as-module shadow-sm h-100">
        <div class="card-header">
          <i class="fas fa-user-clock text-warning"></i> Absensi Hari Ini
        </div>
        <div class="card-body p-2">
          <div class="row no-gutters">
            <div class="col-6 px-1 mb-2">
              <div class="stat-tile">
                <div class="st-num text-success">{{ $attHadir }}</div>
                <div class="st-lbl">Hadir</div>
              </div>
            </div>
            <div class="col-6 px-1 mb-2">
              <div class="stat-tile">
                <div class="st-num text-danger">{{ $attBelum }}</div>
                <div class="st-lbl">Belum Absen</div>
              </div>
            </div>
            <div class="col-6 px-1">
              <div class="stat-tile">
                <div class="st-num text-warning">{{ $attLate }}</div>
                <div class="st-lbl">Terlambat</div>
              </div>
            </div>
            <div class="col-6 px-1">
              <div class="stat-tile">
                <div class="st-num text-info">{{ $attCuti }}</div>
                <div class="st-lbl">Cuti/Izin/Off</div>
              </div>
            </div>
          </div>
          <a href="{{ url('attendance/dashboard') }}" class="btn btn-sm btn-outline-warning btn-block mt-2" style="font-size:11px;">
            <i class="fas fa-external-link-alt mr-1"></i> Dashboard Absensi
          </a>
        </div>
      </div>
    </div>

    {{-- IZIN & LEMBUR PENDING --}}
    <div class="col-md-4 mb-3">
      <div class="card as-module shadow-sm h-100">
        <div class="card-header">
          <i class="fas fa-inbox text-danger"></i>
          Approval Pending
          @if(($leavePending + $overtimePending) > 0)
            <span class="badge badge-danger ml-1">{{ $leavePending + $overtimePending }}</span>
          @endif
        </div>
        <div class="card-body p-2">
          <div class="as-section-title">Izin / Cuti / Sakit ({{ $leavePending }})</div>
          @forelse($leavePendingList as $lv)
            <div class="d-flex align-items-center py-1 border-bottom" style="font-size:12px;">
              <span class="mr-2">
                <span class="badge badge-{{ $lv->type === 'sakit' ? 'info' : ($lv->type === 'cuti' ? 'success' : 'warning') }} badge-sm">
                  {{ ucfirst($lv->type) }}
                </span>
              </span>
              <span class="flex-fill">{{ $lv->user->name ?? '-' }}</span>
              <small class="text-muted">{{ Carbon\Carbon::parse($lv->start_date)->format('d/m') }} – {{ Carbon\Carbon::parse($lv->end_date)->format('d/m') }}</small>
            </div>
          @empty
            <p class="text-muted text-center py-1" style="font-size:12px;">Tidak ada pengajuan pending</p>
          @endforelse

          <div class="as-section-title mt-2">Lembur ({{ $overtimePending }})</div>
          @forelse($overtimePendingList as $ov)
            <div class="d-flex align-items-center py-1 border-bottom" style="font-size:12px;">
              <span class="flex-fill">{{ $ov->user->name ?? '-' }}</span>
              <small class="text-muted">{{ Carbon\Carbon::parse($ov->date)->format('d/m/Y') }}</small>
            </div>
          @empty
            <p class="text-muted text-center py-1" style="font-size:12px;">Tidak ada pengajuan pending</p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- HRD BULAN INI --}}
    <div class="col-md-4 mb-3">
      <div class="card as-module shadow-sm h-100">
        <div class="card-header">
          <i class="fas fa-calendar-check text-success"></i> HRD Bulan Ini
        </div>
        <div class="card-body p-2">
          <div class="d-flex align-items-center mb-2 p-2 rounded" style="background:var(--bg-surface-2,#f9fafb);">
            <i class="fas fa-umbrella-beach text-info mr-2"></i>
            <span style="font-size:12px; flex:1;">Cuti disetujui</span>
            <b>{{ $leaveMonth['cuti'] ?? 0 }}</b>
          </div>
          <div class="d-flex align-items-center mb-2 p-2 rounded" style="background:var(--bg-surface-2,#f9fafb);">
            <i class="fas fa-notes-medical text-warning mr-2"></i>
            <span style="font-size:12px; flex:1;">Sakit disetujui</span>
            <b>{{ $leaveMonth['sakit'] ?? 0 }}</b>
          </div>
          <div class="d-flex align-items-center mb-2 p-2 rounded" style="background:var(--bg-surface-2,#f9fafb);">
            <i class="fas fa-door-open text-secondary mr-2"></i>
            <span style="font-size:12px; flex:1;">Izin lainnya</span>
            <b>{{ $leaveMonth['izin_lainnya'] ?? 0 }}</b>
          </div>
          <div class="d-flex align-items-center mb-2 p-2 rounded" style="background:var(--bg-surface-2,#f9fafb);">
            <i class="fas fa-business-time text-purple mr-2"></i>
            <span style="font-size:12px; flex:1;">Lembur disetujui</span>
            <b>{{ \App\OvertimeRequest::where('status','approved')->whereBetween('date',[date('Y-m-01'),date('Y-m-t')])->count() }}</b>
          </div>
          <a href="{{ url('izin') }}" class="btn btn-sm btn-outline-success btn-block mt-2" style="font-size:11px;">
            <i class="fas fa-external-link-alt mr-1"></i> Kelola Izin / Cuti
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- ======================================================
       ROW 4 — GATEWAY (WA + PAYMENT)
  ====================================================== --}}
  <div class="row">
    {{-- WA GATEWAY --}}
    <div class="col-md-5 mb-3">
      <div class="card as-module shadow-sm h-100">
        <div class="card-header">
          <i class="fab fa-whatsapp text-success"></i> WhatsApp Gateway
        </div>
        <div class="card-body p-2">
          <div class="row no-gutters mb-2">
            <div class="col-3 px-1">
              <div class="stat-tile">
                <div class="st-num">{{ $waTodayTotal }}</div>
                <div class="st-lbl">Total Hari Ini</div>
              </div>
            </div>
            <div class="col-3 px-1">
              <div class="stat-tile">
                <div class="st-num text-success">{{ $waTodaySent }}</div>
                <div class="st-lbl">Terkirim</div>
              </div>
            </div>
            <div class="col-3 px-1">
              <div class="stat-tile">
                <div class="st-num text-warning">{{ $waTodayPending }}</div>
                <div class="st-lbl">Pending</div>
              </div>
            </div>
            <div class="col-3 px-1">
              <div class="stat-tile">
                <div class="st-num text-danger">{{ $waTodayFailed }}</div>
                <div class="st-lbl">Gagal</div>
              </div>
            </div>
          </div>
          {{-- Success rate bar --}}
          <div class="d-flex justify-content-between mb-1" style="font-size:11px; color:var(--text-muted,#9ca3af);">
            <span>Success rate bulan ini</span>
            <b class="{{ $waSuccessRate >= 90 ? 'text-success' : ($waSuccessRate >= 70 ? 'text-warning' : 'text-danger') }}">{{ $waSuccessRate }}%</b>
          </div>
          <div class="progress" style="height:6px; border-radius:3px;">
            <div class="progress-bar {{ $waSuccessRate >= 90 ? 'bg-success' : ($waSuccessRate >= 70 ? 'bg-warning' : 'bg-danger') }}"
                 style="width:{{ $waSuccessRate }}%"></div>
          </div>
          @if($waRecentFailed->count() > 0)
            <div class="mt-2">
              <div class="as-section-title">Pesan Gagal Terakhir</div>
              @foreach($waRecentFailed as $wf)
                <div class="border-bottom py-1" style="font-size:11px;">
                  <span class="text-danger mr-1"><i class="fas fa-exclamation-circle"></i></span>
                  <b>{{ $wf->number }}</b>
                  <small class="text-muted float-right">{{ Carbon\Carbon::parse($wf->created_at)->diffForHumans() }}</small>
                </div>
              @endforeach
            </div>
          @else
            <div class="text-center py-2" style="font-size:12px; color:var(--text-muted,#9ca3af);">
              <i class="fas fa-check-circle text-success mr-1"></i> Tidak ada pesan gagal hari ini
            </div>
          @endif
          <a href="{{ url('wa/dashboard') }}" class="btn btn-sm btn-outline-success btn-block mt-2" style="font-size:11px;">
            <i class="fas fa-external-link-alt mr-1"></i> WA Dashboard
          </a>
        </div>
      </div>
    </div>

    {{-- PAYMENT GATEWAY --}}
    <div class="col-md-4 mb-3">
      <div class="card as-module shadow-sm h-100">
        <div class="card-header">
          <i class="fas fa-credit-card text-primary"></i> Payment Gateway
          <span class="badge badge-success ml-1">{{ $pgEnabled }} Aktif</span>
          @if($pgDisabled > 0)<span class="badge badge-secondary ml-1">{{ $pgDisabled }} Non-aktif</span>@endif
        </div>
        <div class="card-body p-2">
          @foreach($paymentGateways as $pg)
            <div class="gw-pill mb-2">
              @if($pg->enabled)
                <span class="dot-ok"></span>
              @else
                <span class="dot-off"></span>
              @endif
              <span class="flex-fill" style="font-size:13px;">{{ $pg->label }}</span>
              <span class="badge {{ $pg->enabled ? 'badge-success' : 'badge-secondary' }}" style="font-size:10px;">
                {{ $pg->enabled ? 'Aktif' : 'Non-aktif' }}
              </span>
              @if($pg->fee_amount)
                <small class="text-muted" style="font-size:10px;">
                  Fee: {{ $pg->fee_type === 'percent' ? $pg->fee_amount.'%' : 'Rp'.number_format($pg->fee_amount,0,',','.') }}
                </small>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- USERS & PRIVILEGE --}}
    <div class="col-md-3 mb-3">
      <div class="card as-module shadow-sm h-100">
        <div class="card-header">
          <i class="fas fa-id-badge text-indigo"></i> Users per Privilege
        </div>
        <div class="card-body p-2">
          @php
            $privColors = [
              'admin'=>'priv-admin','noc'=>'priv-noc','user'=>'priv-user',
              'marketing'=>'priv-marketing','payment'=>'priv-payment',
              'accounting'=>'priv-accounting'
            ];
          @endphp
          @foreach($usersByPrivilege as $priv => $cnt)
            <div class="d-flex align-items-center mb-2">
              <span class="priv-badge {{ $privColors[$priv] ?? 'priv-other' }}">
                {{ ucfirst($priv) }}
              </span>
              <div class="flex-fill mx-2">
                <div class="progress progress-xs">
                  <div class="progress-bar {{ $priv === 'admin' ? 'bg-warning' : ($priv === 'noc' ? 'bg-info' : 'bg-success') }}"
                       style="width:{{ $cnt / max(1,$totalUsers) * 100 }}%"></div>
                </div>
              </div>
              <b style="font-size:13px;">{{ $cnt }}</b>
            </div>
          @endforeach
          <div class="border-top pt-2 mt-1 text-center" style="font-size:11px; color:var(--text-muted,#9ca3af);">
            Total: <b class="text-body">{{ $totalUsers }}</b> pengguna aktif
          </div>
          <a href="{{ url('user') }}" class="btn btn-sm btn-outline-secondary btn-block mt-2" style="font-size:11px;">
            <i class="fas fa-users-cog mr-1"></i> User Management
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- ======================================================
       ROW 5 — INFRASTRUKTUR NETWORK
  ====================================================== --}}
  <div class="row">
    {{-- NETWORK DEVICES --}}
    <div class="col-md-4 mb-3">
      <div class="card as-module shadow-sm h-100">
        <div class="card-header">
          <i class="fas fa-network-wired text-info"></i> Infrastruktur Jaringan
        </div>
        <div class="card-body p-2">
          <div class="row no-gutters mb-3">
            <div class="col-6 px-1">
              <div class="stat-tile text-center">
                <div class="st-num text-info">{{ $oltCount }}</div>
                <div class="st-lbl">OLT</div>
              </div>
            </div>
            <div class="col-6 px-1">
              <div class="stat-tile text-center">
                <div class="st-num text-primary">{{ $distrouterCount }}</div>
                <div class="st-lbl">Distrouter</div>
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center mb-2 p-2 rounded {{ $mikrotikPending > 0 ? 'border border-danger' : '' }}" style="background:var(--bg-surface-2,#f9fafb);">
            <i class="fas fa-exclamation-triangle {{ $mikrotikPending > 0 ? 'text-danger' : 'text-success' }} mr-2"></i>
            <span style="font-size:12px; flex:1;">Mikrotik Sync Gagal</span>
            <b class="{{ $mikrotikPending > 0 ? 'text-danger' : 'text-success' }}">{{ $mikrotikPending }}</b>
          </div>
          <div class="d-flex align-items-center p-2 rounded" style="background:var(--bg-surface-2,#f9fafb);">
            <i class="fas fa-check-circle text-success mr-2"></i>
            <span style="font-size:12px; flex:1;">Sync Resolved</span>
            <b class="text-success">{{ $mikrotikResolved }}</b>
          </div>
        </div>
      </div>
    </div>

    {{-- MIKROTIK SYNC FAILURES TABLE --}}
    <div class="col-md-8 mb-3">
      <div class="card as-module shadow-sm h-100">
        <div class="card-header d-flex align-items-center">
          <span><i class="fas fa-robot text-danger"></i> Mikrotik Sync Failures</span>
          @if($mikrotikPending > 0)
            <span class="badge badge-danger ml-2">{{ $mikrotikPending }} pending</span>
          @else
            <span class="badge badge-success ml-2">✓ All Resolved</span>
          @endif
        </div>
        <div class="card-body p-0">
          @if($mikrotikFailures->count() > 0)
            <div style="max-height:220px; overflow-y:auto;">
              <table class="table table-xs table-bordered table-hover mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Pelanggan</th>
                    <th>Action</th>
                    <th>Router</th>
                    <th>Error</th>
                    <th>Status</th>
                    <th>Waktu</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($mikrotikFailures as $mf)
                    <tr>
                      <td>{{ $mf->customer_name }}</td>
                      <td><span class="badge badge-sm badge-{{ $mf->action === 'create' ? 'primary' : ($mf->action === 'delete' ? 'danger' : 'warning') }}">{{ $mf->action }}</span></td>
                      <td>{{ $mf->distrouter_ip }}</td>
                      <td style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $mf->error_message }}">{{ $mf->error_message }}</td>
                      <td>
                        @if($mf->status === 'resolved')
                          <span class="dot-ok"></span><span style="font-size:11px">Resolved</span>
                        @else
                          <span class="dot-err"></span><span style="font-size:11px">Pending</span>
                        @endif
                      </td>
                      <td>{{ Carbon\Carbon::parse($mf->created_at)->format('d/m H:i') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center py-4" style="color:var(--text-muted,#9ca3af);">
              <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
              <p class="mb-0" style="font-size:13px;">Tidak ada sync failure</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- ======================================================
       ROW 6 — QUICK LINKS & SYSTEM INFO
  ====================================================== --}}
  <div class="row">
    <div class="col-md-12 mb-3">
      <div class="card as-module shadow-sm">
        <div class="card-header">
          <i class="fas fa-th text-secondary"></i> Quick Access — Semua Modul
        </div>
        <div class="card-body p-2">
          <div class="d-flex flex-wrap gap-2">
            @php
              $modules = [
                ['url'=>'ticket',            'icon'=>'fa-ticket-alt',          'label'=>'Tiket',            'color'=>'btn-outline-danger'],
                ['url'=>'customer',          'icon'=>'fa-users',                'label'=>'Pelanggan',        'color'=>'btn-outline-primary'],
                ['url'=>'suminvoice',        'icon'=>'fa-file-invoice-dollar',  'label'=>'Invoice',          'color'=>'btn-outline-warning'],
                ['url'=>'suminvoice/transaction','icon'=>'fa-cash-register',    'label'=>'Transaksi',        'color'=>'btn-outline-success'],
                ['url'=>'attendance',        'icon'=>'fa-user-clock',           'label'=>'Absensi',          'color'=>'btn-outline-info'],
                ['url'=>'attendance/dashboard','icon'=>'fa-tachometer-alt',     'label'=>'Dashboard HRD',    'color'=>'btn-outline-info'],
                ['url'=>'izin',              'icon'=>'fa-clipboard-check',      'label'=>'Izin/Cuti',        'color'=>'btn-outline-secondary'],
                ['url'=>'lembur',            'icon'=>'fa-business-time',        'label'=>'Lembur',           'color'=>'btn-outline-secondary'],
                ['url'=>'wa/dashboard',      'icon'=>'fa-whatsapp fab',         'label'=>'WA Gateway',       'color'=>'btn-outline-success'],
                ['url'=>'user',              'icon'=>'fa-users-cog',            'label'=>'User Mgmt',        'color'=>'btn-outline-dark'],
                ['url'=>'akun',              'icon'=>'fa-book',                 'label'=>'Akuntansi',        'color'=>'btn-outline-primary'],
                ['url'=>'sale',              'icon'=>'fa-handshake',            'label'=>'Sales',            'color'=>'btn-outline-pink'],
                ['url'=>'file/backup',       'icon'=>'fa-hdd',                  'label'=>'Backup',           'color'=>'btn-outline-secondary'],
                ['url'=>'user/log',          'icon'=>'fa-list-alt',             'label'=>'Activity Log',     'color'=>'btn-outline-secondary'],
                ['url'=>'merchant',          'icon'=>'fa-store',                'label'=>'Merchant',         'color'=>'btn-outline-secondary'],
                ['url'=>'distrouter',        'icon'=>'fa-server',               'label'=>'Distrouter',       'color'=>'btn-outline-info'],
                ['url'=>'olt',               'icon'=>'fa-network-wired',        'label'=>'OLT',              'color'=>'btn-outline-info'],
              ];
            @endphp
            @foreach($modules as $mod)
              <a href="{{ url($mod['url']) }}" class="btn btn-sm {{ $mod['color'] }}" style="font-size:11px; margin:2px;">
                <i class="fas {{ $mod['icon'] }} mr-1"></i>{{ $mod['label'] }}
              </a>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
