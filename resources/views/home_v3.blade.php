@extends('layout.main')
@section('title', 'Dashboard Teknisi')
@section('content')

<style>
/* ── Base ──────────────────────────────────────────────────── */
.tk-card {
  border-radius: 10px !important;
  border: none !important;
  transition: box-shadow .18s, transform .15s;
}
.tk-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.12) !important; }

/* ── My ticket status hero cards ─────────────────────────── */
.my-stat {
  border-radius: 10px;
  padding: 10px 12px;
  color: #fff;
  position: relative;
  overflow: hidden;
  transition: opacity .15s, transform .15s;
  cursor: pointer;
  text-decoration: none;
  display: block;
}
.my-stat:hover { opacity: .9; transform: translateY(-2px); text-decoration: none; color: #fff; }
.my-stat .ms-ghost { position: absolute; right: 8px; bottom: -6px; font-size: 36px; opacity: .14; }
.my-stat .ms-label { font-size: 9px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; opacity: .85; }
.my-stat .ms-val   { font-size: 24px; font-weight: 900; line-height: 1.1; }
.my-stat .ms-sub   { font-size: 9px; opacity: .72; margin-top: 1px; }

/* ── Stat mini-cards ─────────────────────────────────────── */
.v2-stats { display: flex; gap: 6px; flex-wrap: nowrap; margin-bottom: 12px; }
.v2-stat-pill {
  flex: 1; min-width: 0; background: #fff; border: 1px solid #e9ecef;
  border-radius: 8px; padding: 10px 6px 8px; text-align: center;
  text-decoration: none; transition: box-shadow .15s, transform .15s;
  display: block; position: relative; overflow: hidden;
}
.v2-stat-pill::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0;
  height: 3px; border-radius: 8px 8px 0 0;
}
.v2-stat-pill:hover { box-shadow: 0 3px 12px rgba(0,0,0,.10); transform: translateY(-1px); text-decoration: none; }
.v2-stat-icon { font-size: 14px; margin-bottom: 4px; }
.v2-stat-val  { font-size: 20px; font-weight: 900; line-height: 1; }
.v2-stat-lbl  { font-size: 8.5px; color: #999; margin-top: 3px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.sp-red    { color: #e53935; } .sp-red::before    { background: #e53935; }
.sp-orange { color: #fb8c00; } .sp-orange::before { background: #fb8c00; }
.sp-blue   { color: #1e88e5; } .sp-blue::before   { background: #1e88e5; }
.sp-green  { color: #43a047; } .sp-green::before  { background: #43a047; }
.sp-gray   { color: #78909c; } .sp-gray::before   { background: #78909c; }

/* Group stat cards — simplified (reuses v2-stat-pill) */



/* ── Section title ─────────────────────────────────────────── */
.tk-sec { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #999; font-weight: 700; margin-bottom: 8px; }

/* ── Active ticket rows ─────────────────────────────────────── */
.tkt-row {
  border-radius: 8px;
  padding: 9px 12px;
  margin-bottom: 6px;
  border-left: 4px solid;
  background: var(--bg-surface, #fff);
  transition: box-shadow .15s;
}
.tkt-row:hover { box-shadow: 0 3px 12px rgba(0,0,0,.1); }
.tkt-row .tr-title { font-size: 13px; font-weight: 600; color: var(--text-primary, #111); }
.tkt-row .tr-meta  { font-size: 11px; color: var(--text-muted, #888); margin-top: 2px; }
.tkt-row .tr-badge { font-size: 10px; padding: 2px 8px; border-radius: 20px; font-weight: 700; }

/* Greeting card */
.v2-greet {
  border-radius: 10px;
  background: linear-gradient(135deg, #1e88e5 0%, #0d47a1 100%);
  color: #fff;
  padding: 16px 20px;
  margin-bottom: 16px;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
}
.v2-greet-wave { position: absolute; right: -16px; bottom: -24px; font-size: 80px; opacity: .08; pointer-events: none; }
.v2-greet-name { font-size: 17px; font-weight: 800; color: #fff; margin: 0; }
.v2-greet-sub  { font-size: 12px; color: rgba(255,255,255,.75); margin: 2px 0 0; }
.v2-greet-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.v2-link-btn {
  font-size: 11px; font-weight: 600; color: #fff;
  border: 1px solid rgba(255,255,255,.35); border-radius: 6px;
  padding: 5px 12px; text-decoration: none;
  background: rgba(255,255,255,.15); transition: background .15s;
}
.v2-link-btn:hover { background: rgba(255,255,255,.28); color: #fff; text-decoration: none; }

/* Network cards */
.net-card-wrap {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,.1);
  background: var(--bg-surface, #fff);
  position: relative;
}
.net-card-header {
  padding: 10px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: #fff;
}
.net-card-header .net-name {
  font-size: 14px;
  font-weight: 700;
  color: #fff;
  text-decoration: none;
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.net-card-header .net-name:hover { text-decoration: underline; color: #fff; }
.net-card-body { padding: 10px 12px; }
.net-badges { display: flex; flex-wrap: wrap; gap: 5px; }
.net-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.nb-total    { background: #e3f2fd; color: #1565c0; }
.nb-active   { background: #e8f5e9; color: #2e7d32; }
.nb-online   { background: #e8f5e9; color: #2e7d32; }
.nb-offline  { background: #ffebee; color: #c62828; }
.nb-disabled { background: #f3f4f6; color: #6b7280; }
.nb-los      { background: #fff3e0; color: #e65100; }
.nb-dyingasp { background: #fce4ec; color: #880e4f; }
.nb-err      { background: #ffebee; color: #c62828; }
.net-ip-row  { font-size: 11px; color: var(--text-muted,#888); margin-top: 6px; }
.net-refresh-btn {
  background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.3);
  color: #fff; border-radius: 6px; padding: 3px 8px; cursor: pointer; font-size: 11px;
  transition: background .15s;
}
.net-refresh-btn:hover { background: rgba(255,255,255,.35); }
.net-section-head {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .5px; color: #999; margin-bottom: 10px;
  padding-bottom: 6px; border-bottom: 1px solid #f0f0f0;
}
body.dark-mode .nb-total    { background: #1e293b; color: #7dd3fc; }
body.dark-mode .nb-active   { background: #14321a; color: #86efac; }
body.dark-mode .nb-online   { background: #14321a; color: #86efac; }
body.dark-mode .nb-offline  { background: #3b1515; color: #fca5a5; }
body.dark-mode .nb-disabled { background: #1e2332; color: #9ca3af; }
body.dark-mode .nb-los      { background: #3b2406; color: #fdba74; }
body.dark-mode .nb-dyingasp { background: #3b0a1e; color: #f9a8d4; }
body.dark-mode .net-section-head { border-color: var(--border); }

/* Empty state */
.empty-state { text-align: center; padding: 28px 12px; color: var(--text-muted, #9ca3af); }
.empty-state i { font-size: 32px; margin-bottom: 8px; opacity: .4; }

/* Empty state */
.empty-state { text-align: center; padding: 28px 12px; color: var(--text-muted, #9ca3af); }
.empty-state i { font-size: 32px; margin-bottom: 8px; opacity: .4; }

/* Workflow steps */
.wf-wrap { position: relative; margin-top: 8px; padding: 6px 0 2px; }
.wf-track { position: absolute; top: 10px; left: 0; right: 0; height: 3px; background: #e5e7eb; border-radius: 2px; }
.wf-progress { position: absolute; top: 10px; left: 0; height: 3px; background: #1e88e5; border-radius: 2px; transition: width .4s; }
.wf-steps { display: flex; justify-content: space-between; position: relative; }
.wf-step { display: flex; flex-direction: column; align-items: center; flex: 1; }
.wf-dot {
  width: 20px; height: 20px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 9px; z-index: 1; border: 2px solid #e5e7eb; background: #fff;
  transition: all .2s;
}
.wf-dot.wf-done    { background: #1e88e5; border-color: #1e88e5; color: #fff; }
.wf-dot.wf-active  { background: #fff; border-color: #1e88e5; color: #1e88e5; box-shadow: 0 0 0 3px rgba(30,136,229,.2); }
.wf-dot.wf-pending { background: #fff; border-color: #d1d5db; color: #9ca3af; }
.wf-lbl { font-size: 9px; color: #9ca3af; margin-top: 3px; text-align: center; max-width: 52px; line-height: 1.2; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.wf-lbl.wf-lbl-done   { color: #1e88e5; }
.wf-lbl.wf-lbl-active { color: #1e88e5; font-weight: 700; }
body.dark-mode .wf-track { background: #333; }
body.dark-mode .wf-dot.wf-pending { background: #1e2332; border-color: #444; }

body.dark-mode .tkt-row { background: var(--bg-surface-2) !important; }
body.dark-mode .grp-stat { background: var(--bg-surface-2) !important; }
</style>

<div class="container-fluid pb-4">

  {{-- ══ GREETING ══════════════════════════════════════════════════════ --}}
  <div class="v2-greet">
    <div>
      <p class="v2-greet-name">
        @php
          $hour = now()->hour;
          $greet = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
        @endphp
        {{ $greet }}, {{ Auth::user()->name }} 👋
      </p>
      <p class="v2-greet-sub">
        {{ Auth::user()->job_title ?? 'Teknisi' }}
        @if($myJobTitle && $groupUserIds->count() > 1)
          <span class="ml-1" style="background:rgba(255,255,255,.15);border-radius:4px;padding:1px 6px;font-size:11px">
            <i class="fas fa-users mr-1"></i>Tim: {{ $groupMemberNames->count() }} orang
          </span>
        @endif
        &nbsp;·&nbsp;
        {{ now()->isoFormat('dddd, D MMMM Y') }}
        &nbsp;·&nbsp;
        <span id="live-clock">{{ now()->format('H:i:s') }}</span>
      </p>
    </div>
    <div class="v2-greet-actions">
      <a href="{{ url('myticket') }}" class="v2-link-btn"><i class="fas fa-ticket-alt mr-1"></i>My Ticket</a>
      <a href="{{ url('my-attendance') }}" class="v2-link-btn"><i class="fas fa-calendar-check mr-1"></i>Absen</a>
    </div>
    <i class="fas fa-headset v2-greet-wave"></i>
  </div>

  {{-- ══ STATUS TIKET ══════════════════════════════════════════════ --}}
  <div class="tk-sec"><i class="fas fa-tasks mr-1"></i>Status Tiket Saya</div>
  <div class="v2-stats mb-3">
    @foreach([
      ['sp-red',    'fa-exclamation-circle', 'Open',        $myTicketsByStatus['Open']??0,       'Open'],
      ['sp-orange', 'fa-hourglass-half',     'Pending',     $myTicketsByStatus['Pending']??0,    'Pending'],
      ['sp-blue',   'fa-spinner',            'In Progress', $myTicketsByStatus['Inprogress']??0, 'Inprogress'],
      ['sp-green',  'fa-check-circle',       'Solved',      $myTicketsByStatus['Solve']??0,      'Solve'],
      ['sp-gray',   'fa-archive',            'Closed',      $myTicketsByStatus['Close']??0,      'Close'],
    ] as [$color, $icon, $lbl, $val, $st])
    <a href="{{ url('myticket?status='.$st) }}" class="v2-stat-pill {{ $color }}">
      <div class="v2-stat-icon"><i class="fas {{ $icon }}"></i></div>
      <div class="v2-stat-val">{{ $val }}</div>
      <div class="v2-stat-lbl">{{ $lbl }}</div>
    </a>
    @endforeach
  </div>



  {{-- ══ ACTIVE TICKETS + MINI CHART ═══════════════════════════════════ --}}
  <div class="row mb-3">

    <div class="col-md-7 mb-2">
      <div class="card tk-card shadow-sm h-100">
        <div class="card-header bg-transparent border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
          <span class="tk-sec mb-0">
            <i class="fas fa-fire mr-1 text-danger"></i>Tiket Aktif Saya
          </span>
          @php $activeCount = $myActiveTickets->count(); @endphp
          @if($activeCount > 0)
            <span class="badge badge-danger" style="font-size:11px">{{ $activeCount }}</span>
          @endif
        </div>
        <div class="card-body p-2" style="max-height:320px;overflow-y:auto">
          @forelse($myActiveTickets as $t)
            @php
              $bc = $t->status === 'Open' ? '#e53935' : ($t->status === 'Pending' ? '#fb8c00' : '#1e88e5');
              $bg = $t->status === 'Open' ? '#fff5f5' : ($t->status === 'Pending' ? '#fff8f0' : '#f0f7ff');
            @endphp
            <div class="tkt-row" style="border-left-color:{{ $bc }};background:{{ $bg }}">
              <div class="d-flex align-items-start justify-content-between">
                <div class="flex-fill" style="min-width:0">
                  <div class="tr-title text-truncate">
                    <a href="{{ url('ticket/'.$t->id) }}" style="color:inherit;text-decoration:none">
                      #{{ $t->id }} — {{ $t->tittle ?? $t->description ?? 'Tiket #'.$t->id }}
                    </a>
                  </div>
                  <div class="tr-meta">
                    <i class="fas fa-user mr-1"></i>{{ $t->customer->name ?? $t->called_by ?? '-' }}
                    @if($t->date)
                      &nbsp;·&nbsp;<i class="fas fa-calendar-alt mr-1"></i>{{ \Carbon\Carbon::parse($t->date)->format('d/m/Y') }}
                    @endif
                    @if($t->time)
                      &nbsp;·&nbsp;<i class="fas fa-clock mr-1"></i>{{ substr($t->time,0,5) }}
                    @endif
                  </div>
                </div>
                <span class="tr-badge ml-2 flex-shrink-0"
                      style="background:{{ $bc }}1a;color:{{ $bc }};border:1px solid {{ $bc }}33">
                  {{ $t->status }}
                </span>
              </div>
            </div>
          @empty
            <div class="empty-state">
              <i class="fas fa-check-double d-block text-success"></i>
              <p class="mb-0" style="font-size:13px">Tidak ada tiket aktif saat ini 🎉</p>
            </div>
          @endforelse
        </div>
        @if($activeCount > 0)
        <div class="card-footer bg-transparent border-top p-2">
          <a href="{{ url('myticket') }}" class="btn btn-sm btn-outline-primary btn-block" style="font-size:11px">
            <i class="fas fa-list mr-1"></i> Lihat Semua My Ticket
          </a>
        </div>
        @endif
      </div>
    </div>

    <div class="col-md-5 mb-2 d-flex flex-column" style="gap:12px">
      {{-- Mini chart --}}
      <div class="card tk-card shadow-sm">
        <div class="card-header bg-transparent border-bottom py-2 px-3">
          <span class="tk-sec mb-0"><i class="fas fa-chart-bar mr-1 text-primary"></i>Tiket Saya — 7 Hari Terakhir</span>
        </div>
        <div class="card-body p-2">
          <div style="height:130px;position:relative">
            <canvas id="myHistoryChart"></canvas>
          </div>
        </div>
      </div>
      {{-- Completion rate --}}
      <div class="card tk-card shadow-sm">
        <div class="card-body p-3">
          @php
            $done   = ($myTicketsByStatus['Solve']??0) + ($myTicketsByStatus['Close']??0);
            $active = ($myTicketsByStatus['Open']??0) + ($myTicketsByStatus['Pending']??0) + ($myTicketsByStatus['Inprogress']??0);
            $total  = $done + $active;
            $pct    = $total > 0 ? round($done / $total * 100) : 0;
            $pctColor = $pct >= 80 ? '#43a047' : ($pct >= 50 ? '#fb8c00' : '#e53935');
          @endphp
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span style="font-size:12px;font-weight:700;color:var(--text-primary,#333)">
              <i class="fas fa-trophy mr-1 text-warning"></i>Completion Rate
            </span>
            <span style="font-size:20px;font-weight:900;color:{{ $pctColor }}">{{ $pct }}%</span>
          </div>
          <div style="height:8px;background:#f0f0f0;border-radius:4px;overflow:hidden">
            <div style="height:8px;width:{{ $pct }}%;background:{{ $pctColor }};border-radius:4px;transition:width 1s"></div>
          </div>
          <div class="d-flex justify-content-between mt-1" style="font-size:11px;color:#999">
            <span>Selesai: <b class="text-body">{{ $done }}</b></span>
            <span>Aktif: <b class="text-body">{{ $active }}</b></span>
            <span>Total: <b class="text-body">{{ $total }}</b></span>
          </div>
        </div>
      </div>
    </div>

  </div>

  {{-- ══ GROUP TICKET STATUS CARDS ═══════════════════════════════════ --}}
  <div class="row mb-3">
    <div class="col-7">
      <div class="card shadow-sm" style="border:1px solid #e9ecef;border-radius:10px">
        <div class="card-header py-2 px-3 border-bottom d-flex align-items-center justify-content-between" style="background:#f8f9fa;border-radius:10px 10px 0 0">
          <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#666">
            <i class="fas fa-users mr-1 text-primary" style="font-size:10px"></i>Tiket Tim
            @if($myJobTitle) <span style="text-transform:none;letter-spacing:0;font-weight:400;color:#aaa">— {{ $myJobTitle }}</span>@endif
          </span>
          @if($groupUserIds->count() > 0)
            <span class="badge badge-secondary" style="font-size:9px">{{ $groupUserIds->count() }} anggota</span>
          @endif
        </div>
        <div class="card-body p-2">
          <div style="display:flex;gap:5px">
            @foreach([
              ['#e53935','fa-exclamation-circle','Open',        $groupTicketsByStatus['Open']??0],
              ['#fb8c00','fa-hourglass-half',    'Pending',     $groupTicketsByStatus['Pending']??0],
              ['#1e88e5','fa-spinner',           'In Progress', $groupTicketsByStatus['Inprogress']??0],
              ['#43a047','fa-check-circle',      'Solved',      $groupTicketsByStatus['Solve']??0],
              ['#607d8b','fa-archive',           'Closed',      $groupTicketsByStatus['Close']??0],
            ] as [$c,$icon,$lbl,$val])
            <div style="flex:1;text-align:center;padding:7px 4px 6px;border-radius:7px;border:1px solid #e9ecef;position:relative;overflow:hidden">
              <div style="position:absolute;top:0;left:0;right:0;height:3px;background:{{$c}};border-radius:7px 7px 0 0"></div>
              <div style="font-size:11px;color:{{$c}};margin-bottom:2px"><i class="fas {{$icon}}"></i></div>
              <div style="font-size:16px;font-weight:900;color:{{$c}};line-height:1">{{$val}}</div>
              <div style="font-size:8px;color:#999;margin-top:2px;font-weight:600;text-transform:uppercase;letter-spacing:.3px">{{$lbl}}</div>
            </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ══ GROUP ACTIVE TICKETS ════════════════════════════════════════ --}}
  <div class="card tk-card shadow-sm mb-3">
    <div class="card-header bg-transparent border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
      <span class="tk-sec mb-0">
        <i class="fas fa-users mr-1 text-warning"></i>Tiket Aktif Tim
      </span>
      @php $grpActiveCount = $groupActiveTickets->count(); @endphp
      @if($grpActiveCount > 0)
        <span class="badge badge-warning text-dark" style="font-size:11px">{{ $grpActiveCount }}</span>
      @endif
    </div>
    <div class="card-body p-2" style="max-height:320px;overflow-y:auto">
      @forelse($groupActiveTickets as $t)
        @php
          $bc = $t->status === 'Open' ? '#e53935' : ($t->status === 'Pending' ? '#fb8c00' : '#1e88e5');
          $bg = $t->status === 'Open' ? '#fff5f5' : ($t->status === 'Pending' ? '#fff8f0' : '#f0f7ff');
          $assigneeName = $groupMemberNames[$t->assign_to] ?? ('User #'.$t->assign_to);
          $isMe = $t->assign_to == Auth::user()->id;
          if ($loop->first) echo '<div class="row" style="margin:0 -3px">';
        @endphp
        <div class="col-6" style="padding:0 3px 6px">
          <div class="tkt-row mb-0" style="border-left-color:{{ $bc }};background:{{ $bg }};height:100%">
            <div class="d-flex align-items-start justify-content-between">
              <div class="flex-fill" style="min-width:0">
                <div class="tr-title text-truncate">
                  <a href="{{ url('ticket/'.$t->id) }}" style="color:inherit;text-decoration:none">
                    #{{ $t->id }} — {{ $t->tittle ?? $t->description ?? 'Tiket #'.$t->id }}
                  </a>
                </div>
                <div class="tr-meta">
                  <i class="fas fa-user-cog mr-1"></i>
                  <span style="{{ $isMe ? 'font-weight:700;color:#1e88e5' : '' }}">
                    {{ $assigneeName }}{{ $isMe ? ' (Saya)' : '' }}
                  </span>
                  &nbsp;·&nbsp;
                  <i class="fas fa-user mr-1"></i>{{ $t->customer->name ?? $t->called_by ?? '-' }}
                  @if($t->date)
                    &nbsp;·&nbsp;<i class="fas fa-calendar-alt mr-1"></i>{{ \Carbon\Carbon::parse($t->date)->format('d/m') }}
                  @endif
                </div>
              </div>
              <span class="tr-badge ml-2 flex-shrink-0"
                    style="background:{{ $bc }}1a;color:{{ $bc }};border:1px solid {{ $bc }}33">
                {{ $t->status }}
              </span>
            </div>
          </div>
        </div>
        @php if ($loop->last) echo '</div>'; @endphp
      @empty
        <div class="empty-state">
          <i class="fas fa-users d-block"></i>
          <p class="mb-0" style="font-size:13px">Tidak ada tiket aktif di tim</p>
        </div>
      @endforelse
    </div>
  </div>

  {{-- ══ NETWORK STATUS (OLT + DISTROUTER) ════════════════════════════ --}}
  <div class="row mb-3">

    {{-- Distribution Routers --}}
    <div class="col-md-6 mb-2">
      <div class="net-section-head">
        <i class="fas fa-sitemap mr-1 text-primary"></i>Distribution Routers
        <button id="refreshAllRouters" class="btn btn-sm btn-outline-secondary float-right" style="font-size:10px;padding:1px 8px;border-radius:6px">
          <i class="fas fa-sync-alt mr-1"></i>Refresh All
        </button>
      </div>
      @if($distrouterList->isEmpty())
        <div class="empty-state py-3 small"><i class="fas fa-server mb-1 d-block"></i>Tidak ada Distrouter</div>
      @else
      <div class="row" id="routerCards">
        @foreach($distrouterList as $dr)
        <div class="col-sm-4 mb-2">
          <div class="net-card-wrap">
            <div class="net-card-header" style="background:linear-gradient(135deg,#1e88e5,#0d47a1)">
              <a href="{{ url('distrouter/'.$dr->id) }}" class="net-name" title="{{ $dr->name }}">
                <i class="fas fa-server mr-1" style="font-size:11px"></i>{{ $dr->name }}
              </a>
              <button class="net-refresh-btn refresh-dr" data-id="{{ $dr->id }}" title="Refresh">
                <i class="fas fa-sync-alt"></i>
              </button>
            </div>
            <div class="net-card-body">
              <div id="dr-badges-{{ $dr->id }}" class="net-badges">
                <span class="net-badge nb-total"><i class="fas fa-circle" style="font-size:6px"></i>Loading...</span>
              </div>
              <div class="net-ip-row">
                <i class="fas fa-globe mr-1"></i>
                @if($dr->web)
                  <a href="{{ $dr->web }}" target="_blank">{{ $dr->ip }}</a>
                @else
                  {{ $dr->ip }}
                @endif
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>
      @endif
    </div>

    {{-- OLT --}}
    <div class="col-md-6 mb-2">
      <div class="net-section-head">
        <i class="fas fa-network-wired mr-1 text-info"></i>OLT Devices
        <button id="refreshAllOlts" class="btn btn-sm btn-outline-secondary float-right" style="font-size:10px;padding:1px 8px;border-radius:6px">
          <i class="fas fa-sync-alt mr-1"></i>Refresh All
        </button>
      </div>
      @if($oltList->isEmpty())
        <div class="empty-state py-3 small"><i class="fas fa-server mb-1 d-block"></i>Tidak ada OLT</div>
      @else
      <div class="row" id="oltCards">
        @foreach($oltList as $olt)
        <div class="col-sm-4 mb-2">
          @php
            $oltVendorColors = [
              'zte'       => ['linear-gradient(135deg,#00acc1,#006064)', '#00acc1'],
              'huawei'    => ['linear-gradient(135deg,#e53935,#7b1fa2)', '#e53935'],
              'cdata'     => ['linear-gradient(135deg,#43a047,#1b5e20)', '#43a047'],
              'hsgq'      => ['linear-gradient(135deg,#fb8c00,#e65100)', '#fb8c00'],
              'fiberhome' => ['linear-gradient(135deg,#8e24aa,#4a148c)', '#8e24aa'],
            ];
            $vc = $oltVendorColors[$olt->vendor ?? ''] ?? ['linear-gradient(135deg,#546e7a,#263238)', '#546e7a'];
          @endphp
          <div class="net-card-wrap">
            <div class="net-card-header" style="background:{{ $vc[0] }}">
              <a href="{{ url('olt/'.$olt->id) }}" class="net-name" title="{{ $olt->name }}">
                <i class="fas fa-hdd mr-1" style="font-size:11px"></i>{{ $olt->name }}
              </a>
              <button class="net-refresh-btn refresh-olt" data-id="{{ $olt->id }}" title="Refresh">
                <i class="fas fa-sync-alt"></i>
              </button>
            </div>
            <div class="net-card-body">
              <div id="olt-badges-{{ $olt->id }}" class="net-badges">
                <span class="net-badge nb-total"><i class="fas fa-circle" style="font-size:6px"></i>Loading...</span>
              </div>
              <div class="net-ip-row">
                <i class="fas fa-map-marker-alt mr-1"></i>{{ $olt->ip }}
                @if($olt->vendor)
                  <span class="badge badge-secondary ml-1" style="font-size:9px">{{ strtoupper($olt->vendor) }}</span>
                @endif
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>
      @endif
    </div>

  </div>

  {{-- ══ JADWAL HARI INI ════════════════════════════════════════════════ --}}
  <div class="card tk-card shadow-sm">
    <div class="card-header bg-transparent border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
      <span class="tk-sec mb-0">
        <i class="fas fa-calendar-day mr-1 text-info"></i>
        Jadwal Hari Ini — {{ now()->isoFormat('D MMMM Y') }}
      </span>
      <span class="badge badge-{{ $myTicketsToday > 0 ? 'primary' : 'secondary' }}" style="font-size:11px">
        {{ $myTicketsToday }} tiket
      </span>
    </div>
    <div class="card-body p-2">
      @if($myTicketsTodayList->count() > 0)
        <div class="row">
          @foreach($myTicketsTodayList as $t)
          @php
            $stColors = [
              'Open'       => ['bg'=>'#e53935','light'=>'#fff5f5','label'=>'Open'],
              'Pending'    => ['bg'=>'#fb8c00','light'=>'#fff8f0','label'=>'Pending'],
              'Inprogress' => ['bg'=>'#1e88e5','light'=>'#f0f7ff','label'=>'In Progress'],
              'Solve'      => ['bg'=>'#43a047','light'=>'#f1f8f0','label'=>'Solved'],
              'Close'      => ['bg'=>'#607d8b','light'=>'#f5f6f7','label'=>'Closed'],
            ];
            $sc = $stColors[$t->status] ?? ['bg'=>'#9e9e9e','light'=>'#f9f9f9','label'=>$t->status];
          @endphp
          <div class="col-md-6 mb-2">
            <div style="border-radius:8px;border-left:4px solid {{ $sc['bg'] }};background:{{ $sc['light'] }};padding:10px 12px">
              <div class="d-flex align-items-start justify-content-between mb-1">
                <div class="d-flex align-items-center" style="gap:6px;min-width:0;flex:1">
                  <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:12px;background:{{ $sc['bg'] }};color:#fff;white-space:nowrap">
                    {{ $sc['label'] }}
                  </span>
                  <a href="{{ url('ticket/'.$t->id) }}" style="font-size:12px;font-weight:700;color:#222;text-decoration:none" class="text-truncate">
                    #{{ $t->id }} — {{ $t->tittle ?? 'Tiket #'.$t->id }}
                  </a>
                </div>
                @if($t->time)
                  <span style="font-size:11px;color:#555;white-space:nowrap;margin-left:8px">
                    <i class="fas fa-clock mr-1"></i>{{ substr($t->time,0,5) }}
                  </span>
                @endif
              </div>
              <div style="font-size:11px;color:#666;display:flex;flex-wrap:wrap;gap:6px;margin-top:4px">
                @if($t->customer)
                  <span><i class="fas fa-user mr-1"></i>
                    <a href="{{ url('customer/'.$t->customer->id) }}" style="color:#555;text-decoration:none">{{ $t->customer->name }}</a>
                  </span>
                @elseif($t->called_by)
                  <span><i class="fas fa-user mr-1"></i>{{ $t->called_by }}</span>
                @endif
                @if($t->phone)
                  <span><i class="fas fa-phone mr-1"></i>{{ $t->phone }}</span>
                @endif
                @if($t->create_by)
                  <span><i class="fas fa-pencil-alt mr-1"></i>{{ $t->create_by }}</span>
                @endif
              </div>
              {{-- Workflow progress --}}
              @if($t->steps && $t->steps->count() > 0)
              @php
                $wfTotal   = $t->steps->count();
                $wfCurIdx  = $t->steps->search(fn($s) => $s->id == $t->current_step_id);
                $wfCurStep = $t->steps->firstWhere('id', $t->current_step_id);
                $wfFinish  = $wfCurStep && strtolower($wfCurStep->name) === 'finish';
                $wfPct     = $wfFinish ? 100 : ($wfCurIdx !== false && $wfTotal > 1 ? ($wfCurIdx / ($wfTotal - 1)) * 100 : 0);
              @endphp
              <div class="wf-wrap">
                <div class="wf-track"></div>
                <div class="wf-progress" style="width:{{ $wfPct }}%"></div>
                <div class="wf-steps">
                  @foreach($t->steps as $si => $step)
                  @php
                    if ($wfFinish || strtolower($step->name) === 'finish') {
                      $wfc = 'wf-done';
                    } elseif ($t->current_step_id == $step->id) {
                      $wfc = 'wf-active';
                    } elseif ($wfCurIdx !== false && $si < $wfCurIdx) {
                      $wfc = 'wf-done';
                    } else {
                      $wfc = 'wf-pending';
                    }
                  @endphp
                  <div class="wf-step">
                    <div class="wf-dot {{ $wfc }}">
                      <i class="fas {{ $wfc === 'wf-done' ? 'fa-check' : 'fa-circle' }}" style="font-size:7px"></i>
                    </div>
                    <span class="wf-lbl wf-lbl-{{ str_replace('wf-','',$wfc) }}" title="{{ $step->name }}">
                      {{ \Illuminate\Support\Str::limit($step->name, 10) }}
                    </span>
                  </div>
                  @endforeach
                </div>
              </div>
              @endif
            </div>
          </div>
          @endforeach
        </div>
      @else
        <div class="empty-state py-4">
          <i class="fas fa-clipboard-check d-block"></i>
          <p class="mb-0" style="font-size:13px">Tidak ada tiket yang dijadwalkan hari ini</p>
          <a href="{{ url('ticket') }}" class="btn btn-sm btn-outline-primary mt-3" style="font-size:11px">
            <i class="fas fa-search mr-1"></i> Cek semua tiket
          </a>
        </div>
      @endif
    </div>
  </div>

</div>
@endsection

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const isDark         = () => document.body.classList.contains('dark-mode');
  const chartTextColor = () => isDark() ? '#9ba3b2' : '#6b7280';
  const chartGridColor = () => isDark() ? '#333845' : '#e5e7eb';

  // Live clock
  (function tick() {
    const el = document.getElementById('live-clock');
    if (el) el.textContent = new Date().toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    setTimeout(tick, 1000);
  })();

  // Mini bar chart — 7 hari terakhir
  const histChart = new Chart(document.getElementById('myHistoryChart'), {
    type: 'bar',
    data: {
      labels: {!! json_encode($historyDates) !!},
      datasets: [{
        label: 'Tiket',
        data: {!! json_encode($historyTotals) !!},
        backgroundColor: 'rgba(30,136,229,.65)',
        borderColor: 'rgba(30,136,229,1)',
        borderWidth: 1,
        borderRadius: 4,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { mode: 'index' } },
      scales: {
        x: { grid: { display: false }, ticks: { color: chartTextColor(), font: { size: 10 } } },
        y: { beginAtZero: true, ticks: { color: chartTextColor(), font: { size: 10 }, stepSize: 1 }, grid: { color: chartGridColor() } }
      }
    }
  });

  const dmBtn = document.getElementById('toggleDarkMode');
  if (dmBtn) dmBtn.addEventListener('click', () => {
    setTimeout(() => {
      histChart.options.scales.x.ticks.color = chartTextColor();
      histChart.options.scales.y.ticks.color = chartTextColor();
      histChart.options.scales.y.grid.color  = chartGridColor();
      histChart.update();
    }, 50);
  });

  $(function(){ $('[data-toggle="tooltip"]').tooltip({ delay: { show:300, hide:150 } }); });

  // ── Network live stats ─────────────────────────────────────
  function renderDrBadges(id, data) {
    var t = data.pppUserCount     || 0;
    var a = data.pppActiveCount   || 0;
    var o = data.pppOfflineCount  || 0;
    var d = data.pppDisabledCount || 0;
    var html = '';
    html += '<span class="net-badge nb-total"><i class="fas fa-circle" style="font-size:6px"></i>Total: '+t+'</span>';
    html += '<span class="net-badge nb-active"><i class="fas fa-circle" style="font-size:6px"></i>Active: '+a+'</span>';
    html += '<span class="net-badge nb-offline"><i class="fas fa-circle" style="font-size:6px"></i>Offline: '+o+'</span>';
    html += '<span class="net-badge nb-disabled"><i class="fas fa-circle" style="font-size:6px"></i>Disabled: '+d+'</span>';
    $('#dr-badges-'+id).html(html);
  }

  function fetchDr(id, cb) {
    $('#dr-badges-'+id).html('<span class="net-badge nb-total"><i class="fas fa-spinner fa-spin mr-1" style="font-size:9px"></i>Loading...</span>');
    $.ajax({ url: '/distrouter/getrouterinfo/'+id, method: 'GET', dataType: 'json' })
      .done(function(r) {
        if (r && (r.success || r.pppUserCount !== undefined)) {
          renderDrBadges(id, r);
        } else {
          $('#dr-badges-'+id).html('<span class="net-badge nb-err"><i class="fas fa-exclamation-triangle mr-1"></i>'+(r.error||'Error')+'</span>');
        }
        if (cb) cb();
      })
      .fail(function() {
        $('#dr-badges-'+id).html('<span class="net-badge nb-err"><i class="fas fa-times-circle mr-1"></i>Gagal</span>');
        if (cb) cb();
      });
  }

  function renderOltBadges(id, info) {
    var html = '';
    html += '<span class="net-badge nb-total"><i class="fas fa-circle" style="font-size:6px"></i>Total: '+(info.onuCount||0)+'</span>';
    html += '<span class="net-badge nb-online"><i class="fas fa-circle" style="font-size:6px"></i>Online: '+(info.working||info.online||0)+'</span>';
    if ((info.los||0) > 0)       html += '<span class="net-badge nb-los"><i class="fas fa-circle" style="font-size:6px"></i>LOS: '+info.los+'</span>';
    else                          html += '<span class="net-badge nb-los" style="opacity:.5">LOS: 0</span>';
    if ((info.dyinggasp||0) > 0) html += '<span class="net-badge nb-dyingasp"><i class="fas fa-circle" style="font-size:6px"></i>Dyingasp: '+info.dyinggasp+'</span>';
    else                          html += '<span class="net-badge nb-dyingasp" style="opacity:.5">Dyingasp: 0</span>';
    html += '<span class="net-badge nb-offline"><i class="fas fa-circle" style="font-size:6px"></i>Offline: '+(info.offline||0)+'</span>';
    $('#olt-badges-'+id).html(html);
  }

  function fetchOlt(id, cb) {
    $('#olt-badges-'+id).html('<span class="net-badge nb-total"><i class="fas fa-spinner fa-spin mr-1" style="font-size:9px"></i>Loading...</span>');
    $.ajax({ url: '/olt/getoltinfo/'+id, method: 'GET', dataType: 'json' })
      .done(function(r) {
        if (r && r.success && r.oltInfo) {
          renderOltBadges(id, r.oltInfo);
        } else {
          $('#olt-badges-'+id).html('<span class="net-badge nb-err"><i class="fas fa-exclamation-triangle mr-1"></i>'+(r.error||'Tidak Terhubung')+'</span>');
        }
        if (cb) cb();
      })
      .fail(function() {
        $('#olt-badges-'+id).html('<span class="net-badge nb-err"><i class="fas fa-times-circle mr-1"></i>Gagal</span>');
        if (cb) cb();
      });
  }

  // Auto-fetch on load
  @foreach($distrouterList as $dr)
    fetchDr({{ $dr->id }});
  @endforeach
  @foreach($oltList as $olt)
    fetchOlt({{ $olt->id }});
  @endforeach

  // Refresh individual
  $(document).on('click', '.refresh-dr', function() {
    var id = $(this).data('id');
    $(this).find('i').addClass('fa-spin');
    fetchDr(id, () => $('#dr-badges-'+id).closest('.net-card-wrap').find('.refresh-dr i').removeClass('fa-spin'));
  });
  $(document).on('click', '.refresh-olt', function() {
    var id = $(this).data('id');
    $(this).find('i').addClass('fa-spin');
    fetchOlt(id, () => $('#olt-badges-'+id).closest('.net-card-wrap').find('.refresh-olt i').removeClass('fa-spin'));
  });

  // Refresh all
  $('#refreshAllRouters').on('click', function() {
    var $ic = $(this).find('i').addClass('fa-spin');
    var done = 0, total = {{ $distrouterList->count() }};
    if (!total) { $ic.removeClass('fa-spin'); return; }
    @foreach($distrouterList as $dr)
      fetchDr({{ $dr->id }}, function() { if (++done >= total) $ic.removeClass('fa-spin'); });
    @endforeach
  });
  $('#refreshAllOlts').on('click', function() {
    var $ic = $(this).find('i').addClass('fa-spin');
    var done = 0, total = {{ $oltList->count() }};
    if (!total) { $ic.removeClass('fa-spin'); return; }
    @foreach($oltList as $olt)
      fetchOlt({{ $olt->id }}, function() { if (++done >= total) $ic.removeClass('fa-spin'); });
    @endforeach
  });
</script>
@endsection
