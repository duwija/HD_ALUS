@extends('layout.main')
@section('title', 'Dashboard Marketing')
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

/* ── Lead / Marketing styles ───────────────────────────────── */
.lead-stat {
  border-radius: 10px;
  padding: 12px 14px;
  color: #fff;
  position: relative;
  overflow: hidden;
  display: block;
}
.lead-stat .ls-ghost { position: absolute; right: 8px; bottom: -6px; font-size: 36px; opacity: .14; }
.lead-stat .ls-label { font-size: 9px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; opacity: .85; }
.lead-stat .ls-val   { font-size: 28px; font-weight: 900; line-height: 1.1; }
.lead-stat .ls-sub   { font-size: 9px; opacity: .72; margin-top: 1px; }

.lead-row {
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 6px;
  border-left: 4px solid;
  background: var(--bg-surface, #fff);
  transition: box-shadow .15s;
}
.lead-row:hover { box-shadow: 0 3px 12px rgba(0,0,0,.1); }
.lead-stage-badge {
  font-size: 10px;
  padding: 2px 8px;
  border-radius: 12px;
  font-weight: 700;
  color: #fff;
  white-space: nowrap;
}
.prob-bar-wrap { height: 6px; background: #eee; border-radius: 3px; overflow: hidden; margin-top: 4px; }
.prob-bar-fill { height: 6px; border-radius: 3px; transition: width .4s; }

.activity-row {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding: 8px 0;
  border-bottom: 1px solid var(--border-color, #eee);
}
.activity-row:last-child { border-bottom: none; }
.activity-dot {
  width: 28px; height: 28px; border-radius: 50%;
  background: #e3f0fd;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  font-size: 11px; color: #1e88e5;
}
.activity-text { font-size: 11px; color: var(--text-primary,#333); line-height: 1.4; }
.activity-time { font-size: 10px; color: #9ca3af; margin-top: 2px; }
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
        {{ Auth::user()->job_title ?? 'Marketing' }}
        &nbsp;·&nbsp;
        {{ now()->isoFormat('dddd, D MMMM Y') }}
        &nbsp;·&nbsp;
        <span id="live-clock">{{ now()->format('H:i:s') }}</span>
      </p>
    </div>
    <div class="v2-greet-actions">
      <a href="{{ url('myticket') }}" class="v2-link-btn"><i class="fas fa-ticket-alt mr-1"></i>My Ticket</a>
      <a href="{{ url('my-attendance') }}" class="v2-link-btn"><i class="fas fa-calendar-check mr-1"></i>Absen</a>
      <a href="{{ url('lead-workflow') }}" class="v2-link-btn"><i class="fas fa-funnel-dollar mr-1"></i>Pipeline Lead</a>
    </div>
    <i class="fas fa-chart-line v2-greet-wave"></i>
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

  {{-- ══════════════════════════════════════════════════════════════════ --}}
  {{-- ██████████  LEAD MARKETING SECTION  ██████████                    --}}
  {{-- ══════════════════════════════════════════════════════════════════ --}}

  <div class="d-flex align-items-center mb-2" style="gap:10px">
    <div class="tk-sec mb-0" style="flex:1"><i class="fas fa-funnel-dollar mr-1 text-success"></i>Lead &amp; Pipeline Marketing</div>
    <a href="{{ url('lead-workflow') }}" class="btn btn-sm btn-outline-success" style="font-size:11px">
      <i class="fas fa-external-link-alt mr-1"></i>Buka Pipeline Board
    </a>
  </div>

  {{-- Lead summary stats --}}
  <div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
      <div class="lead-stat shadow" style="background:linear-gradient(135deg,#00897b,#004d40)">
        <div class="ls-label">Total Lead Aktif</div>
        <div class="ls-val">{{ $leadsTotal }}</div>
        <div class="ls-sub">di semua stage</div>
        <i class="fas fa-users ls-ghost"></i>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
      <div class="lead-stat shadow" style="background:linear-gradient(135deg,#1e88e5,#0d47a1)">
        <div class="ls-label">Lead Saya</div>
        <div class="ls-val">{{ $leadsMyCount }}</div>
        <div class="ls-sub">di-assign ke saya</div>
        <i class="fas fa-user-tag ls-ghost"></i>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
      <div class="lead-stat shadow" style="background:linear-gradient(135deg,#43a047,#1b5e20)">
        <div class="ls-label">Konversi Bulan Ini</div>
        <div class="ls-val">{{ $leadsConverted }}</div>
        <div class="ls-sub">berhasil closing</div>
        <i class="fas fa-trophy ls-ghost"></i>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
      <div class="lead-stat shadow" style="background:linear-gradient(135deg,#e53935,#b71c1c)">
        <div class="ls-label">Lost Bulan Ini</div>
        <div class="ls-val">{{ $leadsLost }}</div>
        <div class="ls-sub">tidak berhasil</div>
        <i class="fas fa-times-circle ls-ghost"></i>
      </div>
    </div>
  </div>

  {{-- Pipeline Funnel --}}
  <div class="card tk-card shadow-sm mb-3">
    <div class="card-header bg-transparent border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
      <span class="tk-sec mb-0"><i class="fas fa-layer-group mr-1 text-success"></i>Distribusi Per Stage</span>
      <a href="{{ url('lead-workflow') }}" class="btn btn-sm btn-outline-success" style="font-size:11px">
        <i class="fas fa-columns mr-1"></i>Board
      </a>
    </div>
    <div class="card-body p-3">
      @php
        $stageIcons = ['fa-user-plus','fa-map-marker-alt','fa-handshake','fa-calendar-alt','fa-tools','fa-bolt','fa-check-double'];
        $maxCnt = max(1, max(array_values($leadsByStage) ?: [1]));
        $si = 0;
      @endphp

      {{-- Horizontal funnel bars --}}
      <div class="row">
        @foreach($leadStages as $stage)
        @php
          $stageColor = $stage->color ?: '#1e88e5';
          $cnt  = $leadsByStage[$stage->id] ?? 0;
          $pct  = $leadsTotal > 0 ? round($cnt / $leadsTotal * 100) : 0;
          $barW = round($cnt / $maxCnt * 100);
          $icon = $stageIcons[$si % count($stageIcons)];
          $si++;
        @endphp
        <div class="col-md-6 mb-2">
          <div style="display:flex;align-items:center;gap:10px">
            {{-- Colored circle icon --}}
            <div style="width:32px;height:32px;border-radius:50%;background:{{ $stageColor }}20;border:2px solid {{ $stageColor }};
                        display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="fas {{ $icon }}" style="font-size:11px;color:{{ $stageColor }}"></i>
            </div>
            {{-- Label + bar --}}
            <div style="flex:1;min-width:0">
              <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:4px">
                <span style="font-size:12px;font-weight:700;color:var(--text-primary,#333);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px">
                  {{ $stage->name }}
                </span>
                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;margin-left:6px">
                  <span style="font-size:16px;font-weight:900;color:{{ $stageColor }};line-height:1">{{ $cnt }}</span>
                  <span style="font-size:10px;color:#aaa">{{ $pct }}%</span>
                </div>
              </div>
              <div style="height:8px;background:{{ $stageColor }}20;border-radius:4px;overflow:hidden">
                <div style="height:8px;width:{{ $barW }}%;background:{{ $stageColor }};border-radius:4px;transition:width .6s"></div>
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>

      {{-- Summary total --}}
      <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between" style="font-size:12px;color:#888">
        <span><i class="fas fa-filter mr-1"></i>{{ $leadStages->count() }} stage aktif</span>
        <span><b style="color:var(--text-primary,#333);font-size:14px">{{ $leadsTotal }}</b> total lead di pipeline</span>
      </div>
    </div>
  </div>

  {{-- Active Leads List + Recent Activity --}}
  <div class="row mb-3">

    {{-- All Active Leads --}}
    <div class="col-md-7 mb-2">
      <div class="card tk-card shadow-sm h-100">
        <div class="card-header bg-transparent border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
          <span class="tk-sec mb-0">
            <i class="fas fa-list-alt mr-1 text-success"></i>Daftar Lead Aktif
          </span>
          <span class="badge" style="background:#00897b;color:#fff;font-size:11px">{{ $allActiveLeads->count() }}</span>
        </div>
        <div class="card-body p-2" style="max-height:380px;overflow-y:auto">
          @forelse($allActiveLeads as $lead)
          @php
            $stageColor = $lead->workflowStage->color ?? '#1e88e5';
            $stageName  = $lead->workflowStage->name ?? '—';
            $prob       = (int)($lead->conversion_probability ?? 0);
            $probColor  = $prob >= 70 ? '#43a047' : ($prob >= 40 ? '#fb8c00' : '#e53935');
            $closeDate  = $lead->expected_close_date ? \Carbon\Carbon::parse($lead->expected_close_date) : null;
            $isOverdue  = $closeDate && $closeDate->isPast();
          @endphp
          <div class="lead-row" style="border-left-color:{{ $stageColor }}">
            <div class="d-flex align-items-start justify-content-between">
              <div class="flex-fill" style="min-width:0">
                <div class="d-flex align-items-center" style="gap:6px;margin-bottom:3px">
                  <span class="lead-stage-badge" style="background:{{ $stageColor }}">{{ $stageName }}</span>
                  <a href="{{ url('customer/'.$lead->id) }}" style="font-size:12px;font-weight:700;color:var(--text-primary,#222);text-decoration:none" class="text-truncate">
                    {{ $lead->name }}
                  </a>
                </div>
                <div style="font-size:11px;color:#888;display:flex;flex-wrap:wrap;gap:8px">
                  @if($lead->lead_source)
                    <span><i class="fas fa-tag mr-1"></i>{{ $lead->lead_source }}</span>
                  @endif
                  @if($closeDate)
                    <span class="{{ $isOverdue ? 'text-danger font-weight-bold' : '' }}">
                      <i class="fas fa-calendar-alt mr-1"></i>Target: {{ $closeDate->format('d/m/Y') }}
                      @if($isOverdue) <span class="badge badge-danger" style="font-size:9px">Overdue</span> @endif
                    </span>
                  @endif
                </div>
                @if($prob > 0)
                <div class="prob-bar-wrap mt-1">
                  <div class="prob-bar-fill" style="width:{{ $prob }}%;background:{{ $probColor }}"></div>
                </div>
                @endif
              </div>
              @if($prob > 0)
              <span style="font-size:14px;font-weight:900;color:{{ $probColor }};margin-left:10px;flex-shrink:0">{{ $prob }}%</span>
              @endif
            </div>
          </div>
          @empty
          <div class="empty-state">
            <i class="fas fa-seedling d-block text-success"></i>
            <p class="mb-0" style="font-size:13px">Belum ada lead aktif</p>
            <a href="{{ url('lead-workflow') }}" class="btn btn-sm btn-outline-success mt-2" style="font-size:11px">
              <i class="fas fa-plus mr-1"></i> Tambah Lead Baru
            </a>
          </div>
          @endforelse
        </div>
        <div class="card-footer bg-transparent border-top p-2">
          <a href="{{ url('lead-workflow') }}" class="btn btn-sm btn-outline-success btn-block" style="font-size:11px">
            <i class="fas fa-external-link-alt mr-1"></i> Buka Pipeline Board
          </a>
        </div>
      </div>
    </div>

    {{-- Right column: My Leads + Recent Activity --}}
    <div class="col-md-5 mb-2 d-flex flex-column" style="gap:12px">

      {{-- My Leads --}}
      @if($leadsMyActive->isNotEmpty())
      <div class="card tk-card shadow-sm">
        <div class="card-header bg-transparent border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
          <span class="tk-sec mb-0">
            <i class="fas fa-user-tag mr-1 text-primary"></i>Lead Saya
          </span>
          <span class="badge badge-primary" style="font-size:11px">{{ $leadsMyActive->count() }}</span>
        </div>
        <div class="card-body p-2" style="max-height:220px;overflow-y:auto">
          @foreach($leadsMyActive as $lead)
          @php
            $stageColor = $lead->workflowStage->color ?? '#1e88e5';
            $stageName  = $lead->workflowStage->name ?? '—';
            $prob       = (int)($lead->conversion_probability ?? 0);
            $probColor  = $prob >= 70 ? '#43a047' : ($prob >= 40 ? '#fb8c00' : '#e53935');
            $closeDate  = $lead->expected_close_date ? \Carbon\Carbon::parse($lead->expected_close_date) : null;
            $isOverdue  = $closeDate && $closeDate->isPast();
          @endphp
          <div class="lead-row" style="border-left-color:{{ $stageColor }}">
            <div class="d-flex align-items-center justify-content-between">
              <div style="min-width:0;flex:1">
                <div class="d-flex align-items-center" style="gap:5px;margin-bottom:2px">
                  <span class="lead-stage-badge" style="background:{{ $stageColor }};font-size:9px;padding:1px 6px">{{ $stageName }}</span>
                  <a href="{{ url('customer/'.$lead->id) }}" style="font-size:12px;font-weight:700;color:var(--text-primary,#222);text-decoration:none" class="text-truncate">
                    {{ $lead->name }}
                  </a>
                </div>
                @if($closeDate)
                  <div style="font-size:10px;color:{{ $isOverdue ? '#e53935' : '#888' }}">
                    <i class="fas fa-calendar-alt mr-1"></i>{{ $closeDate->format('d/m/Y') }}
                    @if($isOverdue) <span class="text-danger font-weight-bold">• Overdue</span> @endif
                  </div>
                @endif
                @if($prob > 0)
                <div class="prob-bar-wrap">
                  <div class="prob-bar-fill" style="width:{{ $prob }}%;background:{{ $probColor }}"></div>
                </div>
                @endif
              </div>
              @if($prob > 0)
              <span style="font-size:13px;font-weight:900;color:{{ $probColor }};margin-left:8px;flex-shrink:0">{{ $prob }}%</span>
              @endif
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @endif

      {{-- Recent Lead Activity --}}
      <div class="card tk-card shadow-sm flex-fill">
        <div class="card-header bg-transparent border-bottom py-2 px-3">
          <span class="tk-sec mb-0">
            <i class="fas fa-history mr-1 text-secondary"></i>Aktivitas Lead Terbaru
          </span>
        </div>
        <div class="card-body p-2" style="max-height:260px;overflow-y:auto">
          @forelse($recentLeadActivity as $act)
          @php
            $fieldIcons = [
              'lead_source'           => 'fa-tag',
              'expected_close_date'   => 'fa-calendar-alt',
              'conversion_probability'=> 'fa-percent',
              'lead_notes'            => 'fa-sticky-note',
              'workflow_stage_id'     => 'fa-layer-group',
            ];
            $actIcon = $fieldIcons[$act->field_changed] ?? 'fa-edit';
          @endphp
          <div class="activity-row">
            <div class="activity-dot">
              <i class="fas {{ $actIcon }}"></i>
            </div>
            <div style="flex:1;min-width:0">
              <div class="activity-text">
                @if($act->customer)
                  <a href="{{ url('customer/'.$act->customer->id) }}" style="font-weight:700;color:var(--text-primary,#333);text-decoration:none">{{ $act->customer->name }}</a>
                @else
                  <span class="font-weight-bold">—</span>
                @endif
                &nbsp;·&nbsp;<span style="color:#888">{{ $act->formattedChange }}</span>
              </div>
              <div class="activity-time">
                <i class="fas fa-clock mr-1"></i>{{ \Carbon\Carbon::parse($act->created_at)->diffForHumans() }}
                @if($act->updated_by)
                  &nbsp;·&nbsp;<i class="fas fa-user-edit mr-1"></i>{{ $act->updated_by }}
                @endif
              </div>
            </div>
          </div>
          @empty
          <div class="empty-state py-3">
            <i class="fas fa-inbox d-block"></i>
            <p class="mb-0" style="font-size:12px">Belum ada aktivitas lead</p>
          </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- Conversion Funnel Chart --}}
  <div class="row mb-3">
    <div class="col-md-6 mb-2">
      <div class="card tk-card shadow-sm">
        <div class="card-header bg-transparent border-bottom py-2 px-3">
          <span class="tk-sec mb-0"><i class="fas fa-chart-bar mr-1 text-success"></i>Jumlah Lead per Stage</span>
        </div>
        <div class="card-body p-3">
          <div style="height:180px;position:relative">
            <canvas id="leadFunnelChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-2">
      <div class="card tk-card shadow-sm h-100">
        <div class="card-header bg-transparent border-bottom py-2 px-3">
          <span class="tk-sec mb-0"><i class="fas fa-calculator mr-1 text-info"></i>Ringkasan Pipeline</span>
        </div>
        <div class="card-body p-3">
          @php
            $convRate = $leadsTotal + $leadsConverted > 0
              ? round($leadsConverted / max($leadsTotal + $leadsConverted, 1) * 100)
              : 0;
            $lostRate = $leadsTotal + $leadsLost > 0
              ? round($leadsLost / max($leadsTotal + $leadsLost, 1) * 100)
              : 0;
          @endphp
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span style="font-size:12px;color:#666"><i class="fas fa-users mr-1"></i>Total Lead Aktif</span>
            <span style="font-size:16px;font-weight:900;color:#00897b">{{ $leadsTotal }}</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span style="font-size:12px;color:#666"><i class="fas fa-user-tag mr-1"></i>Lead di-assign ke Saya</span>
            <span style="font-size:16px;font-weight:900;color:#1e88e5">{{ $leadsMyCount }}</span>
          </div>
          <hr class="my-2">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span style="font-size:12px;color:#666"><i class="fas fa-trophy mr-1 text-warning"></i>Konversi bulan ini</span>
            <span style="font-size:15px;font-weight:900;color:#43a047">{{ $leadsConverted }}</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span style="font-size:12px;color:#666"><i class="fas fa-times-circle mr-1 text-danger"></i>Lost bulan ini</span>
            <span style="font-size:15px;font-weight:900;color:#e53935">{{ $leadsLost }}</span>
          </div>
          <hr class="my-2">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span style="font-size:11px;color:#888">Conversion Rate (bulan ini)</span>
            <span style="font-weight:700;color:#43a047">{{ $convRate }}%</span>
          </div>
          <div style="height:6px;background:#f0f0f0;border-radius:3px;overflow:hidden">
            <div style="height:6px;width:{{ $convRate }}%;background:#43a047;border-radius:3px"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ══ JADWAL HARI INI ══════════════════════════════════════ --}}
  <div class="card tk-card shadow-sm mt-3 mb-2">
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
                    if ($wfFinish || strtolower($step->name) === 'finish') { $wfc = 'wf-done'; }
                    elseif ($t->current_step_id == $step->id) { $wfc = 'wf-active'; }
                    elseif ($wfCurIdx !== false && $si < $wfCurIdx) { $wfc = 'wf-done'; }
                    else { $wfc = 'wf-pending'; }
                  @endphp
                  <div class="wf-step">
                    <div class="wf-dot {{ $wfc }}"><i class="fas {{ $wfc === 'wf-done' ? 'fa-check' : 'fa-circle' }}" style="font-size:7px"></i></div>
                    <span class="wf-lbl wf-lbl-{{ str_replace('wf-','',$wfc) }}" title="{{ $step->name }}">{{ \Illuminate\Support\Str::limit($step->name, 10) }}</span>
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

  // Ticket history bar chart
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

  // Lead funnel bar chart
  const funnelCtx = document.getElementById('leadFunnelChart');
  if (funnelCtx) {
    const funnelLabels = {!! json_encode($leadStages->pluck('name')) !!};
    const funnelData   = {!! json_encode($leadStages->map(fn($s) => $leadsByStage[$s->id] ?? 0)) !!};
    const funnelColors = {!! json_encode($leadStages->map(fn($s) => $s->color ?: '#1e88e5')) !!};

    new Chart(funnelCtx, {
      type: 'bar',
      data: {
        labels: funnelLabels,
        datasets: [{
          label: 'Lead',
          data: funnelData,
          backgroundColor: funnelColors.map(c => c + 'cc'),
          borderColor: funnelColors,
          borderWidth: 1,
          borderRadius: 4,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { mode: 'index', callbacks: {
            label: ctx => ` ${ctx.parsed.y} lead`
          }}
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: chartTextColor(), font: { size: 10 } } },
          y: { beginAtZero: true, ticks: { color: chartTextColor(), font: { size: 10 }, stepSize: 1 }, grid: { color: chartGridColor() } }
        }
      }
    });
  }

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
</script>
@endsection
