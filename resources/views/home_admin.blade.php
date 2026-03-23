@extends('layout.main')
@section('title', 'Admin Dashboard')
@section('content')

<style>
  .info-box {
    background: var(--bg-surface, #fff) !important;
    border: 1px solid var(--border, #e9ecef) !important;
    border-radius: 10px !important;
    box-shadow: none !important;
    color: var(--text-primary, #333) !important;
    transition: box-shadow .15s;
  }
  .info-box:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08) !important; }
  .info-box-content { color: var(--text-primary, #333) !important; }
  .info-box-text    { color: var(--text-secondary, #666) !important; }
  .info-box-number  { color: var(--text-primary, #333) !important; font-weight: 700; }
  body.dark-mode .info-box.bg-light {
    background: var(--bg-surface-2, #252535) !important;
    border-color: var(--border, #2d2d3a) !important;
  }
  body.dark-mode .info-box .text-muted { color: var(--text-muted, #777) !important; }
  /* progress bar height fix for job title cards */
  .info-box .progress { margin-top: 4px; }

  .adm-card {
    background: var(--bg-surface, #fff);
    border: 1px solid var(--border, #e9ecef);
    border-radius: 10px;
    margin-bottom: 16px;
    overflow: hidden;
  }
  body.dark-mode .adm-card { background: var(--card-bg, #1e1e2d); border-color: var(--border, #2d2d3a); }
  .adm-card-hd {
    padding: 9px 14px;
    border-bottom: 1px solid var(--border, #f0f0f0);
    display: flex; align-items: center; justify-content: space-between;
  }
  body.dark-mode .adm-card-hd { border-color: var(--border, #2d2d3a); }
  .adm-card-title {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #777; margin: 0;
    display: flex; align-items: center; gap: 6px;
  }
  body.dark-mode .adm-card-title { color: #aaa; }
  .adm-card-body { padding: 12px 14px; }

  .adm-tbl { width: 100%; border-collapse: collapse; font-size: 11px; }
  .adm-tbl th { padding: 5px 8px; border-bottom: 1px solid #eee; font-weight: 700; font-size: 10px; letter-spacing: .3px; color: #888; text-transform: uppercase; text-align: left; }
  .adm-tbl td { padding: 6px 8px; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
  .adm-tbl tr:last-child td { border-bottom: none; }
  body.dark-mode .adm-tbl th { border-color: var(--border,#2d2d3a); color: #777; }
  body.dark-mode .adm-tbl td { border-color: #2a2a38; color: #ccc; }

  .adm-kpi-strip { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
  .adm-kpi-block {
    flex: 1; min-width: 90px;
    background: var(--bg-surface, #fff);
    border: 1px solid var(--border, #eaecf0);
    border-radius: 8px;
    padding: 8px 10px;
    text-align: center;
  }
  body.dark-mode .adm-kpi-block { background: var(--card-bg,#1e1e2d); border-color: var(--border,#2d2d3a); }
  .adm-kpi-val { font-size: 18px; font-weight: 900; display: block; line-height: 1.1; color: #333; }
  body.dark-mode .adm-kpi-val { color: #e0e0e0; }
  .adm-kpi-lbl { font-size: 9px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #999; display: block; margin-top: 2px; }

  .adm-funnel-row { display: flex; flex-direction: column; gap: 5px; }
  .adm-funnel-item { display: flex; align-items: center; gap: 8px; }
  .adm-funnel-label { width: 98px; font-size: 11px; color: #555; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex-shrink: 0; }
  body.dark-mode .adm-funnel-label { color: #bbb; }
  .adm-funnel-bar-wrap { flex: 1; background: #f0f0f0; border-radius: 4px; height: 13px; overflow: hidden; }
  body.dark-mode .adm-funnel-bar-wrap { background: #333; }
  .adm-funnel-bar { height: 100%; border-radius: 4px; }
  .adm-funnel-count { width: 26px; text-align: right; font-size: 11px; font-weight: 700; color: #333; flex-shrink: 0; }
  body.dark-mode .adm-funnel-count { color: #ddd; }

  .adm-inv-bar { display: flex; height: 10px; border-radius: 6px; overflow: hidden; margin-bottom: 8px; }
  .adm-inv-legend { display: flex; gap: 10px; flex-wrap: wrap; font-size: 11px; color: #555; }
  body.dark-mode .adm-inv-legend { color: #bbb; }
  .adm-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0; }

  .adm-chips { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
  .adm-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
    text-decoration: none; color: #555;
    border: 1px solid #dde1e7;
    background: #fff;
    transition: background .15s, color .15s;
  }
  .adm-chip:hover { background: #f0f4ff; color: #1e88e5; border-color: #c5d5f9; text-decoration: none; }
  body.dark-mode .adm-chip { background: #1e1e2d; border-color: #333; color: #ccc; }
  body.dark-mode .adm-chip:hover { background: #252540; color: #90b8ff; }
  h5.mb-0 { color: var(--text-primary, #333) !important; }
</style>

<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-12 col-md-11">

      <div class="adm-chips">
        <a href="{{ url('home-v2') }}" class="adm-chip"><i class="fas fa-tools text-primary"></i> Teknisi</a>
        <a href="{{ url('home-v3') }}" class="adm-chip"><i class="fas fa-network-wired" style="color:#00897b"></i> Network</a>
        <a href="{{ url('home-v4') }}" class="adm-chip"><i class="fas fa-funnel-dollar text-warning"></i> Marketing</a>
        <a href="{{ url('home-v5') }}" class="adm-chip"><i class="fas fa-file-invoice-dollar text-success"></i> Accounting</a>
      </div>

      {{-- ROW 1: 3-column --}}
      <div class="row">

        {{-- LEFT col --}}
        <div class="col-12 col-md-3 d-flex flex-column">

          <div class="info-box">
            <span class="info-box-icon bg-info elevation-1">
              <a href="{{ url('ticket') }}" style="color:#fff"><i class="fas fa-ticket-alt"></i></a>
            </span>
            <div class="info-box-content">
              <span class="info-box-text mb-1"><strong>Tickets</strong></span>
              <span>
                <span class="badge badge-danger mr-1">Open: <b>{{ $ticket_count_per_status['Open'] ?? 0 }}</b></span>
                <span class="badge badge-warning mr-1">Pending: <b>{{ $ticket_count_per_status['Pending'] ?? 0 }}</b></span>
                <span class="badge badge-info mr-1">Inprogress: <b>{{ $ticket_count_per_status['Inprogress'] ?? 0 }}</b></span>
                <span class="badge badge-success mr-1">Solve: <b>{{ $ticket_count_per_status['Solve'] ?? 0 }}</b></span>
                <span class="badge badge-secondary">Close: <b>{{ $ticket_count_per_status['Close'] ?? 0 }}</b></span>
              </span>
            </div>
          </div>

          <div class="info-box">
            <span class="info-box-icon bg-danger elevation-1">
              <a href="{{ url('suminvoice') }}" style="color:#fff"><i class="fas fa-money-check-alt"></i></a>
            </span>
            <div class="info-box-content">
              <span class="info-box-text">Pending Invoice</span>
              <span class="info-box-number">{{ $invoice_count }}</span>
            </div>
          </div>

          <div class="info-box">
            <span class="info-box-icon bg-success elevation-1">
              <a href="{{ url('suminvoice/transaction') }}" style="color:#fff"><i class="fas fa-cash-register"></i></a>
            </span>
            <div class="info-box-content">
              <span class="info-box-text">Transaction</span>
              <span class="info-box-number">{{ $invoice_paid }}</span>
            </div>
          </div>

          <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1">
              <a href="{{ url('customer') }}" style="color:#fff"><i class="fas fa-users"></i></a>
            </span>
            <div class="info-box-content" style="font-size:14px">
              <span class="info-box-text">Active: <b>{{ $cust_active }}</b></span>
              <span class="info-box-text">Blocked: <b>{{ $cust_block }}</b></span>
              <span class="info-box-text">Inactive: <b>{{ $cust_inactive }}</b></span>
              <span class="info-box-text">Potential: <b>{{ $cust_potensial }}</b></span>
            </div>
          </div>

          {{-- Kehadiran Karyawan (tren 14 hari) --}}
          @if(isset($attendanceTrend) && count($attendanceTrend))
          <div class="adm-card mt-auto" style="flex:1;display:flex;flex-direction:column">
            <div class="adm-card-hd">
              <span class="adm-card-title"><i class="fas fa-user-clock text-success"></i> Kehadiran Karyawan</span>
              <a href="{{ route('attendance.dashboard') }}" style="font-size:10px;color:#1e88e5;font-weight:600">detail &rarr;</a>
            </div>
            @php
              $totalPresent = array_sum(array_column($attendanceTrend, 'present'));
              $totalAbsent  = array_sum(array_column($attendanceTrend, 'absent'));
              $totalLate    = array_sum(array_column($attendanceTrend, 'late'));
            @endphp
            <div class="adm-card-body" style="padding:6px 12px 8px;flex:1;display:flex;flex-direction:column">
              <div style="display:flex;gap:6px;margin-bottom:6px">
                <span class="badge badge-success" style="font-size:10px">Hadir: {{ $totalPresent }}</span>
                <span class="badge badge-warning" style="font-size:10px">Telat: {{ $totalLate }}</span>
                <span class="badge badge-danger"  style="font-size:10px">Absen: {{ $totalAbsent }}</span>
              </div>
              <div style="position:relative;flex:1;min-height:80px">
                <canvas id="admAttendanceTrendChart"></canvas>
              </div>
            </div>
          </div>
          @endif

        </div>{{-- /left --}}

        {{-- MIDDLE col --}}
        <div class="col-12 col-md-5 d-flex flex-column">

          <div class="adm-card">
            <div class="adm-card-hd">
              <span class="adm-card-title"><i class="fas fa-users text-warning"></i> Customer Status</span>
              <span class="badge badge-light" style="font-size:10px">Total {{ $cust_active + $cust_block + $cust_inactive + $cust_potensial }}</span>
            </div>
            <div class="adm-card-body" style="padding-bottom:8px">
              <div style="position:relative;height:215px">
                <canvas id="admCustStatusChart"></canvas>
              </div>
            </div>
          </div>

          <div class="adm-card mt-auto" style="flex:1;display:flex;flex-direction:column">
            <div class="adm-card-hd">
              <span class="adm-card-title"><i class="fas fa-chart-line text-success"></i> Daily Transactions</span>
            </div>
            <div class="adm-card-body" style="padding-bottom:8px;flex:1;display:flex;flex-direction:column">
              <div style="position:relative;flex:1;min-height:80px">
                <canvas id="admDailyTrxChart"></canvas>
              </div>
            </div>
          </div>

        </div>{{-- /middle --}}

        {{-- RIGHT col --}}
        <div class="col-12 col-md-4 d-flex flex-column">

          <div class="adm-card">
            <div class="adm-card-hd">
              <span class="adm-card-title"><i class="fas fa-user-plus text-primary"></i> New &amp; Terminated Customers</span>
              @if(isset($custNewMonthly))
              <span class="badge badge-pill badge-info" style="font-size:10px">New: {{ array_sum($custNewMonthly) }}</span>
              @endif
              @if(isset($custBlockMonthly))
              <span class="badge badge-pill badge-danger" style="font-size:10px">Term: {{ array_sum($custBlockMonthly) }}</span>
              @endif
            </div>
            <div class="adm-card-body" style="padding-bottom:8px">
              <div style="position:relative;height:215px">
                <canvas id="admNewCustChart"></canvas>
              </div>
            </div>
          </div>

          <div class="adm-card mt-auto" style="flex:1;display:flex;flex-direction:column">
            <div class="adm-card-hd">
              <span class="adm-card-title"><i class="fas fa-file-invoice-dollar text-success"></i> Accounting</span>
              <a href="{{ url('suminvoice') }}" style="font-size:10px;color:#aaa">detail &rarr;</a>
            </div>
            <div class="adm-card-body" style="padding:10px 12px;flex:1;display:flex;flex-direction:column;justify-content:space-between">
              <div style="font-size:11px;color:#555;margin-bottom:5px">
                <i class="fas fa-circle" style="font-size:7px;color:#e53935"></i>
                Receivable &mdash; <b style="color:#e53935">Rp {{ number_format($acctTotalReceivable,0,',','.') }}</b>
              </div>
              <div style="font-size:11px;color:#555;margin-bottom:5px">
                <i class="fas fa-circle" style="font-size:7px;color:#43a047"></i>
                Bulan ini &mdash; <b style="color:#43a047">Rp {{ number_format($acctPaymentThisMonth,0,',','.') }}</b>
              </div>
              <div style="font-size:11px;color:#555;margin-bottom:5px">
                <i class="fas fa-circle" style="font-size:7px;color:#00897b"></i>
                Minggu ini &mdash; <b style="color:#00897b">Rp {{ number_format($acctPaymentThisWeek,0,',','.') }}</b>
              </div>
              <div style="font-size:11px;color:#555;margin-bottom:10px">
                <i class="fas fa-circle" style="font-size:7px;color:#fb8c00"></i>
                Hari ini &mdash; <b style="color:#fb8c00">Rp {{ number_format($acctPaymentToday,0,',','.') }}</b>
              </div>
              @php
                $acctTotalSafe = $acctTotalCount > 0 ? $acctTotalCount : 1;
                $pctPaid   = round($acctPaidCount   / $acctTotalSafe * 100, 1);
                $pctUnpaid = round($acctUnpaidCount / $acctTotalSafe * 100, 1);
                $pctCancel = round($acctCancelCount / $acctTotalSafe * 100, 1);
              @endphp
              <div class="adm-inv-bar">
                <div style="width:{{ $pctPaid }}%;background:#43a047"></div>
                <div style="width:{{ $pctUnpaid }}%;background:#e53935"></div>
                <div style="width:{{ $pctCancel }}%;background:#9e9e9e"></div>
              </div>
              <div class="adm-inv-legend">
                <span><span class="adm-dot" style="background:#43a047"></span> Lunas <b>{{ $acctPaidCount }}</b></span>
                <span><span class="adm-dot" style="background:#e53935"></span> Belum <b>{{ $acctUnpaidCount }}</b></span>
                <span><span class="adm-dot" style="background:#9e9e9e"></span> Batal <b>{{ $acctCancelCount }}</b></span>
              </div>
            </div>
          </div>

        </div>{{-- /right --}}

      </div>{{-- /row 1 --}}

      {{-- ROW 2 — Lead + Collector + Progress --}}
      <div class="row">

        @if($leadStages->isNotEmpty())
        <div class="col-12 col-md-4">
          <div class="adm-card">
            <div class="adm-card-hd">
              <span class="adm-card-title" style="color:#fb8c00"><i class="fas fa-funnel-dollar"></i> Lead Pipeline</span>
              <span style="font-size:10px;color:#aaa">{{ $leadsTotal }} lead aktif</span>
            </div>
            <div class="adm-card-body">
              @php
                $maxLeadCnt = max(1, max(array_values($leadsByStage) ?: [1]));
                $fCols = ['#1e88e5','#00897b','#fb8c00','#e53935','#7b1fa2','#f4511e','#0097a7','#3949ab'];
              @endphp
              <div class="adm-kpi-strip">
                <div class="adm-kpi-block">
                  <span class="adm-kpi-val" style="color:#fb8c00">{{ $leadsTotal }}</span>
                  <span class="adm-kpi-lbl">Pipeline</span>
                </div>
                <div class="adm-kpi-block">
                  <span class="adm-kpi-val text-success">{{ $leadsConverted }}</span>
                  <span class="adm-kpi-lbl">Konversi</span>
                </div>
                <div class="adm-kpi-block">
                  <span class="adm-kpi-val text-danger">{{ $leadsLost }}</span>
                  <span class="adm-kpi-lbl">Lost</span>
                </div>
                <div class="adm-kpi-block">
                  <span class="adm-kpi-val text-primary">{{ $leadsMyCount }}</span>
                  <span class="adm-kpi-lbl">Saya</span>
                </div>
              </div>
              <div class="adm-funnel-row">
                @foreach($leadStages as $i => $stage)
                  @php
                    $cnt = $leadsByStage[$stage->id] ?? 0;
                    $w   = $maxLeadCnt > 0 ? round($cnt / $maxLeadCnt * 100) : 0;
                    $col = $fCols[$i % count($fCols)];
                  @endphp
                  <div class="adm-funnel-item">
                    <span class="adm-funnel-label" title="{{ $stage->name }}">{{ $stage->name }}</span>
                    <div class="adm-funnel-bar-wrap">
                      <div class="adm-funnel-bar" style="width:{{ $w }}%;background:{{ $col }}"></div>
                    </div>
                    <span class="adm-funnel-count">{{ $cnt }}</span>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
        @endif

        {{-- Tickets by Category --}}
        @if(isset($tagLabels) && count($tagLabels))
        <div class="col-12 col-md-8">
          <div class="adm-card">
            <div class="adm-card-hd">
              <span class="adm-card-title"><i class="fas fa-tags text-primary"></i> Tickets by Category</span>
            </div>
            <div class="adm-card-body" style="padding-bottom:8px">
              <div style="position:relative;height:215px">
                <canvas id="admTicketCatChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        @endif

        @if($acctGroupedByUser->isNotEmpty())
        <div class="col-12 col-md-2">
          <div class="adm-card">
            <div class="adm-card-hd">
              <span class="adm-card-title" style="color:#fb8c00"><i class="fas fa-trophy"></i> Top Kolektor Bulan Ini</span>
            </div>
            <div class="adm-card-body" style="padding:0">
              <table class="adm-tbl">
                <thead><tr><th>#</th><th>User</th><th>Trx</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                  @foreach($acctGroupedByUser->take(8) as $i => $row)
                  <tr>
                    <td style="color:#aaa;width:22px">{{ $i+1 }}</td>
                    <td>{{ $row->user_name }}</td>
                    <td><span class="badge badge-success">{{ $row->trx_count }}</span></td>
                    <td class="text-right" style="font-weight:700;color:#2e7d32">Rp {{ number_format($row->total_payment,0,',','.') }}</td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
        @endif

        @if(isset($jobTitleProgress) && count($jobTitleProgress))
        <div class="col-12 col-md-2">
          <div class="adm-card">
            <div class="adm-card-hd">
              <span class="adm-card-title"><i class="fas fa-chart-bar text-info"></i> Progress Tim</span>
            </div>
            <div class="adm-card-body" style="padding:10px 12px">
              @foreach($jobTitleProgress as $jt)
              <div style="margin-bottom:8px">
                <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:3px">
                  <span style="color:#555;font-weight:600">{{ $jt['job_title'] }}</span>
                  <span style="color:#999">{{ $jt['count'] }} &bull; {{ $jt['percent'] }}%</span>
                </div>
                <div class="progress" style="height:8px;border-radius:4px">
                  <div class="progress-bar progress-bar-striped bg-info"
                       style="width:{{ $jt['percent'] }}%;transition:width 1s"
                       role="progressbar"></div>
                </div>
              </div>
              @endforeach
            </div>
          </div>
        </div>
        @endif

      </div>{{-- /row 2 --}}

      {{-- ROW 3 — Network --}}
      @if($distrouterList->isNotEmpty() || $oltList->isNotEmpty())
      <div class="row">

        @if($distrouterList->isNotEmpty())
        <div class="col-12 col-md-7">
          <div class="adm-card">
            <div class="adm-card-hd">
              <span class="adm-card-title" style="color:#3949ab"><i class="fas fa-project-diagram"></i> Distribution Routers</span>
              <a href="{{ url('distrouter') }}" style="font-size:10px;color:#aaa">{{ $distrouterList->count() }} device &rarr;</a>
            </div>
            <div class="adm-card-body" style="padding:0">
              <table class="adm-tbl">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>IP</th>
                    <th class="text-center" style="color:#43a047">Online</th>
                    <th class="text-center" style="color:#9e9e9e">Offline</th>
                    <th class="text-center" style="color:#e53935">Disable</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($distrouterList as $i => $dr)
                  @php $s = $distrouterStats[$dr->id] ?? null; @endphp
                  <tr>
                    <td style="color:#aaa;width:22px">{{ $i+1 }}</td>
                    <td>
                      <a href="{{ url('distrouter/'.$dr->id) }}" style="color:inherit;font-weight:600">{{ $dr->name }}</a>
                    </td>
                    <td><code style="font-size:10px;background:#f5f5f5;padding:1px 4px;border-radius:3px">{{ $dr->ip ?? '-' }}</code></td>
                    <td class="text-center">
                      <span style="font-weight:700;color:#43a047">{{ $s ? $s->online : 0 }}</span>
                    </td>
                    <td class="text-center">
                      <span style="font-weight:700;color:#9e9e9e">{{ $s ? $s->offline : 0 }}</span>
                    </td>
                    <td class="text-center">
                      <span style="font-weight:700;color:#e53935">{{ $s ? $s->disabled : 0 }}</span>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
        @endif

        @if($oltList->isNotEmpty())
        <div class="col-12 col-md-5">
          <div class="adm-card">
            <div class="adm-card-hd">
              <span class="adm-card-title" style="color:#0097a7"><i class="fas fa-server"></i> OLT</span>
              <a href="{{ url('olt') }}" style="font-size:10px;color:#aaa">{{ $oltList->count() }} unit &rarr;</a>
            </div>
            <div class="adm-card-body" style="padding:0">
              <table class="adm-tbl">
                <thead><tr><th>#</th><th>Nama</th><th>IP</th><th>Vendor</th></tr></thead>
                <tbody>
                  @foreach($oltList->take(8) as $i => $olt)
                  <tr>
                    <td style="color:#aaa;width:24px">{{ $i+1 }}</td>
                    <td>{{ $olt->name }}</td>
                    <td><code style="font-size:10px;background:#f5f5f5;padding:1px 4px;border-radius:3px">{{ $olt->ip ?? '-' }}</code></td>
                    <td style="color:#888">{{ $olt->vendor ?? '-' }}</td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
        @endif

      </div>
      @endif

      {{-- ROW 4 — Tiket per Job Title --}}
      @if(isset($jobTicketsAll) && count($jobTicketsAll))
      <div class="adm-section-title mt-2 mb-2" style="font-size:10px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:#9a9a9a;display:flex;align-items:center;gap:6px">
        <i class="fas fa-user-tie" style="color:#3949ab"></i> Status Tiket per Job Title
        <span style="flex:1;height:1px;background:#eaeaea;display:inline-block"></span>
      </div>
      <div class="row">
        @foreach($jobTicketsAll as $job => $statusList)
        @php
          $progress    = collect($jobTitleProgressAll)->firstWhere('job_title', $job);
          $percent     = $progress['percent'] ?? 0;
          $count       = $progress['count'] ?? 0;
          $bgClass     = ['bg-success','bg-danger','bg-primary','bg-warning','bg-info'];
          $color       = $bgClass[abs(crc32($job)) % count($bgClass)];
          $tooltipText = "Progress {$job}: {$percent}% dari {$count} tiket";
        @endphp
        <div class="col-md-4 col-lg-3 col-6">
          <div class="info-box mb-3 bg-light shadow-sm">
            <span class="info-box-icon {{ $color }} elevation-1">
              <i class="fas fa-user-tie"></i>
            </span>
            <div class="info-box-content">
              <span class="info-box-text">
                <strong>{{ $job }}</strong>
                <span class="text-muted">({{ $count }})</span>
              </span>
              <span class="info-box-number mb-2">
                @foreach(['Open','Pending','Inprogress','Solve','Close'] as $status)
                <span class="badge badge-{{ $status == 'Open' ? 'danger' : ($status == 'Pending' ? 'warning' : ($status == 'Inprogress' ? 'info' : ($status == 'Solve' ? 'success' : 'secondary'))) }} mr-1"
                      data-toggle="tooltip" data-placement="top"
                      title="Tiket {{ strtolower($status) }}: {{ $statusList[$status] ?? 0 }}">
                  {{ $status }}: {{ $statusList[$status] ?? 0 }}
                </span>
                @endforeach
              </span>
              <div class="progress mt-1" style="height:15px;border-radius:4px">
                <div class="progress-bar progress-bar-striped progress-bar-animated {{ $color }}"
                     role="progressbar"
                     style="width:{{ $percent }}%;transition:width 1s"
                     aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"
                     data-toggle="tooltip" data-placement="bottom"
                     title="{{ $tooltipText }}">
                  {{ $percent }}%
                </div>
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>{{-- /row 4 --}}
      @endif

    </div>
  </div>
</div>

@endsection

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(function () {
  $('[data-toggle="tooltip"]').tooltip({ delay: { show: 200, hide: 100 } });
});
</script>
<script>
(function () {
  var isDark      = function () { return document.body.classList.contains('dark-mode'); };
  var textColor   = function () { return isDark() ? '#9ba3b2' : '#6b7280'; };
  var gridColor   = function () { return isDark() ? '#333845' : '#e5e7eb'; };

  Chart.defaults.font.size = 10;

  // 1. Customer Status bar
  var custStatusCtx = document.getElementById('admCustStatusChart');
  if (custStatusCtx) {
    new Chart(custStatusCtx.getContext('2d'), {
      type: 'bar',
      data: {
        labels: ['Potential', 'Active', 'Inactive', 'Blocked'],
        datasets: [{
          data: [{{ $cust_potensial }}, {{ $cust_active }}, {{ $cust_inactive }}, {{ $cust_block }}],
          backgroundColor: ['#FFC107', '#4CAF50', '#9E9E9E', '#F44336'],
          borderRadius: 6,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: ctx => ctx.parsed.y + ' pelanggan' } }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: textColor() } },
          y: { beginAtZero: true, grid: { color: gridColor() }, ticks: { color: textColor(), stepSize: 1 } }
        }
      }
    });
  }

  // 2. New Customers line
  var newCustCtx = document.getElementById('admNewCustChart');
  if (newCustCtx) {
    @if(isset($custMonthLabels) && count($custMonthLabels))
    new Chart(newCustCtx.getContext('2d'), {
      type: 'line',
      data: {
        labels: {!! json_encode($custMonthLabels) !!},
        datasets: [
          {
            label: 'New Customers',
            data: {!! json_encode($custNewMonthly) !!},
            borderColor: '#1e88e5',
            backgroundColor: 'rgba(30,136,229,.12)',
            fill: true, tension: .35, pointRadius: 4, borderWidth: 2
          },
          {
            label: 'Terminated',
            data: {!! json_encode($custBlockMonthly ?? array_fill(0, count($custMonthLabels), 0)) !!},
            borderColor: '#e53935',
            backgroundColor: 'rgba(229,57,53,.10)',
            fill: true, tension: .35, pointRadius: 4, borderWidth: 2,
            borderDash: [5, 4]
          }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: true, position: 'top', labels: { boxWidth: 10, color: textColor() } },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                var suffix = ctx.dataset.label === 'Terminated' ? ' terminate' : ' pelanggan baru';
                return ctx.dataset.label + ': ' + ctx.parsed.y + suffix;
              }
            }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: textColor(), maxTicksLimit: 6 } },
          y: { beginAtZero: true, grid: { color: gridColor() }, ticks: { color: textColor(), stepSize: 1 } }
        }
      }
    });
    @endif
  }

  // 3. Tickets by Category bar
  var tickCatCtx = document.getElementById('admTicketCatChart');
  if (tickCatCtx) {
    @if(isset($tagLabels) && count($tagLabels))
    new Chart(tickCatCtx.getContext('2d'), {
      type: 'bar',
      data: {
        labels: {!! json_encode($tagLabels) !!},
        datasets: [{
          data: {!! json_encode($tagData) !!},
          backgroundColor: 'rgba(54,162,235,.7)',
          borderColor: 'rgba(54,162,235,1)',
          borderWidth: 1, borderRadius: 5, borderSkipped: false
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: ctx => ctx.parsed.y + ' tiket' } }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: textColor() } },
          y: { beginAtZero: true, grid: { color: gridColor() }, ticks: { color: textColor(), stepSize: 1 } }
        }
      }
    });
    @endif
  }

  // 4. Daily Transactions dual-axis
  var dailyTrxCtx = document.getElementById('admDailyTrxChart');
  if (dailyTrxCtx) {
    @if($acctDailyTransactions->isNotEmpty())
    var trxLabels  = {!! json_encode($acctDailyTransactions->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d'))) !!};
    var trxVolumes = {!! json_encode($acctDailyTransactions->pluck('volume')) !!};
    var trxTotals  = {!! json_encode($acctDailyTransactions->pluck('total_paid')->map(fn($v) => (float)$v)) !!};
    new Chart(dailyTrxCtx.getContext('2d'), {
      type: 'bar',
      data: {
        labels: trxLabels,
        datasets: [
          {
            type: 'bar',
            label: 'Jumlah Transaksi',
            data: trxVolumes,
            backgroundColor: 'rgba(100,149,237,.65)',
            borderRadius: 3,
            yAxisID: 'yVol'
          },
          {
            type: 'line',
            label: 'Total Pembayaran',
            data: trxTotals,
            borderColor: '#e53935',
            backgroundColor: 'transparent',
            pointRadius: 2, borderWidth: 1.5,
            borderDash: [3,3],
            yAxisID: 'yAmt'
          }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: true, position: 'top', labels: { boxWidth: 10, color: textColor(), font: { size: 10 } } },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                if (ctx.datasetIndex === 1) return 'Rp ' + ctx.parsed.y.toLocaleString('id-ID');
                return ctx.parsed.y + ' trx';
              }
            }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: textColor(), maxTicksLimit: 10 } },
          yVol: { beginAtZero: true, position: 'left', grid: { color: gridColor() }, ticks: { color: textColor(), stepSize: 1 } },
          yAmt: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: {
            color: textColor(),
            callback: v => 'Rp' + (v >= 1e6 ? (v/1e6).toFixed(0)+'jt' : (v/1e3).toFixed(0)+'rb')
          }}
        }
      }
    });
    @endif
  }

  // 5. Attendance Trend — 14 hari terakhir
  var attTrendCtx = document.getElementById('admAttendanceTrendChart');
  if (attTrendCtx) {
    @if(isset($attendanceTrend) && count($attendanceTrend))
    var attData = @json($attendanceTrend);
    new Chart(attTrendCtx.getContext('2d'), {
      type: 'bar',
      data: {
        labels: attData.map(function(d) { return d.date; }),
        datasets: [
          {
            label: 'Hadir',
            data: attData.map(function(d) { return d.present; }),
            backgroundColor: 'rgba(40,167,69,.75)',
            borderRadius: 4, stack: 'att'
          },
          {
            label: 'Terlambat',
            data: attData.map(function(d) { return d.late; }),
            backgroundColor: 'rgba(255,193,7,.85)',
            borderRadius: 4, stack: 'late'
          },
          {
            label: 'Absen',
            data: attData.map(function(d) { return d.absent; }),
            backgroundColor: 'rgba(220,53,69,.70)',
            borderRadius: 4, stack: 'abs'
          }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: true, position: 'top', labels: { boxWidth: 10, color: textColor(), font: { size: 10 } } },
          tooltip: {
            callbacks: {
              label: function(ctx) { return ctx.dataset.label + ': ' + ctx.parsed.y + ' orang'; }
            }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: textColor(), maxRotation: 0 } },
          y: { beginAtZero: true, grid: { color: gridColor() }, ticks: { color: textColor(), stepSize: 1 } }
        }
      }
    });
    @endif
  }

  // Dark mode toggle re-render
  var toggleBtn = document.getElementById('toggleDarkMode');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
      setTimeout(function () {
        Object.values(Chart.instances || {}).forEach(function (ch) {
          try {
            if (ch.options.scales) {
              Object.values(ch.options.scales).forEach(function (sc) {
                if (sc.ticks) sc.ticks.color = textColor();
                if (sc.grid)  sc.grid.color  = gridColor();
              });
            }
            if (ch.options.plugins && ch.options.plugins.legend && ch.options.plugins.legend.labels) {
              ch.options.plugins.legend.labels.color = textColor();
            }
            ch.update();
          } catch(e) {}
        });
      }, 50);
    });
  }
})();
</script>
@endsection
