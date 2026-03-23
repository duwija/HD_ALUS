@extends('layout.main')
@section('title', 'Dashboard Teknisi')
@section('content')

<style>
/* ── Reset & base ─────────────────────────────────────────── */
.v2-wrap { padding: 0 4px; }
.v2-card {
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  box-shadow: none;
  margin-bottom: 16px;
}
.v2-card-hd {
  padding: 10px 16px;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.v2-card-hd-title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: #888;
  margin: 0;
}
.v2-card-body { padding: 14px 16px; }

/* ── Greeting card ───────────────────────────────────────── */
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
  font-size: 11px;
  font-weight: 600;
  color: #fff;
  border: 1px solid rgba(255,255,255,.35);
  border-radius: 6px;
  padding: 5px 12px;
  text-decoration: none;
  background: rgba(255,255,255,.15);
  transition: background .15s;
  white-space: nowrap;
}
.v2-link-btn:hover { background: rgba(255,255,255,.28); color: #fff; text-decoration: none; }

/* ── Stat mini-cards ─────────────────────────────────────── */
.v2-stats { display: flex; gap: 6px; flex-wrap: nowrap; margin-bottom: 12px; }
.v2-stat-pill {
  flex: 1;
  min-width: 0;
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 10px 6px 8px;
  text-align: center;
  text-decoration: none;
  transition: box-shadow .15s, transform .15s;
  display: block;
  position: relative;
  overflow: hidden;
}
.v2-stat-pill::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  border-radius: 8px 8px 0 0;
}
.v2-stat-pill:hover { box-shadow: 0 3px 12px rgba(0,0,0,.10); transform: translateY(-1px); text-decoration: none; }
.v2-stat-icon { font-size: 14px; margin-bottom: 4px; }
.v2-stat-val { font-size: 20px; font-weight: 900; line-height: 1; }
.v2-stat-lbl { font-size: 8.5px; color: #999; margin-top: 3px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }

.sp-red    { color: #e53935; } .sp-red::before    { background: #e53935; }
.sp-orange { color: #fb8c00; } .sp-orange::before { background: #fb8c00; }
.sp-blue   { color: #1e88e5; } .sp-blue::before   { background: #1e88e5; }
.sp-green  { color: #43a047; } .sp-green::before  { background: #43a047; }
.sp-gray   { color: #78909c; } .sp-gray::before   { background: #78909c; }

/* ── Section label ────────────────────────────────────────── */
.v2-sec {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: #aaa;
  font-weight: 700;
  margin-bottom: 8px;
}

/* ── Ticket row ───────────────────────────────────────────── */
.v2-tkt {
  border-left: 3px solid #e0e0e0;
  border-radius: 0 6px 6px 0;
  padding: 8px 12px;
  margin-bottom: 6px;
  background: #fafafa;
  transition: background .12s;
}
.v2-tkt:hover { background: #f4f7fb; }
.v2-tkt-title { font-size: 13px; font-weight: 600; color: #1a1a2e; }
.v2-tkt-meta  { font-size: 11px; color: #9e9e9e; margin-top: 2px; }

/* ── Small badge ──────────────────────────────────────────── */
.v2-badge {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 4px;
  white-space: nowrap;
}

/* ── Progress bar ─────────────────────────────────────────── */
.v2-prog-bar {
  height: 6px;
  background: #f0f0f0;
  border-radius: 3px;
  overflow: hidden;
  margin: 6px 0;
}
.v2-prog-fill { height: 100%; border-radius: 3px; transition: width 1s; }

/* ── Workflow ─────────────────────────────────────────────── */
.wf-wrap     { position: relative; margin-top: 8px; padding: 6px 0 2px; }
.wf-track    { position: absolute; top: 10px; left: 0; right: 0; height: 2px; background: #e9ecef; border-radius: 2px; }
.wf-progress { position: absolute; top: 10px; left: 0; height: 2px; background: #1e88e5; border-radius: 2px; transition: width .4s; }
.wf-steps    { display: flex; justify-content: space-between; position: relative; }
.wf-step     { display: flex; flex-direction: column; align-items: center; flex: 1; }
.wf-dot {
  width: 18px; height: 18px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 8px; z-index: 1; border: 2px solid #e0e0e0; background: #fff;
}
.wf-dot.wf-done    { background: #1e88e5; border-color: #1e88e5; color: #fff; }
.wf-dot.wf-active  { background: #fff; border-color: #1e88e5; color: #1e88e5; box-shadow: 0 0 0 2px rgba(30,136,229,.2); }
.wf-dot.wf-pending { background: #fff; border-color: #ddd; color: #ccc; }
.wf-lbl { font-size: 9px; color: #bbb; margin-top: 3px; text-align: center; max-width: 50px; line-height: 1.2; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.wf-lbl.wf-lbl-done   { color: #1e88e5; }
.wf-lbl.wf-lbl-active { color: #1e88e5; font-weight: 700; }

/* ── Empty state ──────────────────────────────────────────── */
.v2-empty { text-align: center; padding: 30px 12px; color: #bbb; }
.v2-empty i { font-size: 28px; display: block; margin-bottom: 8px; opacity: .4; }

/* ── Dark mode ────────────────────────────────────────────── */
body.dark-mode .v2-card,
body.dark-mode .v2-stat-pill { background: var(--bg-surface-2, #1e2332); border-color: #2e3348; }
body.dark-mode .v2-card-hd { border-color: #2e3348; }
body.dark-mode .v2-tkt { background: #242840; }
body.dark-mode .v2-tkt:hover { background: #2a2f4a; }
body.dark-mode .v2-tkt-title { color: #dde2f0; }
body.dark-mode .wf-track { background: #333; }
body.dark-mode .wf-dot.wf-pending { background: #1e2332; border-color: #444; }
body.dark-mode .v2-greet-name { color: #e0e6f5; }
body.dark-mode .v2-prog-bar { background: #2e3348; }
</style>

<div class="container-fluid v2-wrap pb-4">

  {{-- ══ GREETING ════════════════════════════════════════════════════ --}}
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
  </div>

  {{-- ══ STATUS TIKET ════════════════════════════════════════════════ --}}
  <div class="v2-sec"><i class="fas fa-tasks mr-1"></i>Status Tiket Saya</div>
  <div class="v2-stats">
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

  {{-- Completion rate --}}
  @php
    $done   = ($myTicketsByStatus['Solve']??0) + ($myTicketsByStatus['Close']??0);
    $active = ($myTicketsByStatus['Open']??0) + ($myTicketsByStatus['Pending']??0) + ($myTicketsByStatus['Inprogress']??0);
    $total  = $done + $active;
    $pct    = $total > 0 ? round($done / $total * 100) : 0;
    $pctColor = $pct >= 80 ? '#43a047' : ($pct >= 50 ? '#fb8c00' : '#e53935');
  @endphp
  <div class="v2-card mb-3">
    <div class="v2-card-body" style="padding:8px 14px">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span style="font-size:11px;font-weight:700;color:#888">
          <i class="fas fa-trophy mr-1 text-warning"></i>Completion Rate
        </span>
        <span style="font-size:15px;font-weight:900;color:{{ $pctColor }}">{{ $pct }}%
          <small style="font-size:10px;color:#bbb;font-weight:400">Selesai {{ $done }} / {{ $total }}</small>
        </span>
      </div>
      <div class="v2-prog-bar" style="height:4px">
        <div class="v2-prog-fill" style="width:{{ $pct }}%;background:{{ $pctColor }}"></div>
      </div>
    </div>
  </div>

  {{-- ══ TIKET AKTIF ═══════════════════════════════════════════════ --}}
  <div class="v2-card">
        <div class="v2-card-hd">
          <span class="v2-card-hd-title"><i class="fas fa-fire mr-1 text-danger"></i>Tiket Aktif Saya</span>
          @php $activeCount = $myActiveTickets->count(); @endphp
          @if($activeCount > 0)
            <span class="v2-badge" style="background:#feeceb;color:#e53935">{{ $activeCount }}</span>
          @endif
        </div>
        <div class="v2-card-body" style="max-height:300px;overflow-y:auto;padding:10px 12px">
          @forelse($myActiveTickets as $t)
            @php
              $bc = $t->status === 'Open' ? '#e53935' : ($t->status === 'Pending' ? '#fb8c00' : '#1e88e5');
              $bg = $t->status === 'Open' ? '#feeceb' : ($t->status === 'Pending' ? '#fff3e0' : '#e3f2fd');
            @endphp
            <div class="v2-tkt" style="border-left-color:{{ $bc }}">
              <div class="d-flex align-items-start justify-content-between">
                <div class="flex-fill" style="min-width:0">
                  <div class="v2-tkt-title text-truncate">
                    <a href="{{ url('ticket/'.$t->id) }}" style="color:inherit;text-decoration:none">
                      #{{ $t->id }} — {{ $t->tittle ?? $t->description ?? 'Tiket #'.$t->id }}
                    </a>
                  </div>
                  <div class="v2-tkt-meta">
                    <i class="fas fa-user mr-1"></i>{{ $t->customer->name ?? $t->called_by ?? '-' }}
                    @if($t->date)
                      &nbsp;·&nbsp;<i class="fas fa-calendar-alt mr-1"></i>{{ \Carbon\Carbon::parse($t->date)->format('d/m/Y') }}
                    @endif
                    @if($t->time)
                      &nbsp;·&nbsp;<i class="fas fa-clock mr-1"></i>{{ substr($t->time,0,5) }}
                    @endif
                  </div>
                </div>
                <span class="v2-badge ml-2 flex-shrink-0"
                      style="background:{{ $bg }};color:{{ $bc }}">
                  {{ $t->status }}
                </span>
              </div>
            </div>
          @empty
            <div class="v2-empty">
              <i class="fas fa-check-double text-success"></i>
              <small>Tidak ada tiket aktif saat ini 🎉</small>
            </div>
          @endforelse
        </div>
        @if($activeCount > 0)
        <div style="padding:8px 12px;border-top:1px solid #f0f0f0">
          <a href="{{ url('myticket') }}" class="v2-link-btn d-block text-center" style="font-size:11px">
            <i class="fas fa-list mr-1"></i> Lihat Semua Tiket
          </a>
        </div>
        @endif
  </div>

  {{-- ══ JADWAL HARI INI ════════════════════════════════════════════ --}}
  <div class="v2-card">
    <div class="v2-card-hd">
      <span class="v2-card-hd-title">
        <i class="fas fa-calendar-day mr-1 text-info"></i>
        Jadwal Hari Ini — {{ now()->isoFormat('D MMMM Y') }}
      </span>
      <span class="v2-badge" style="background:{{ $myTicketsToday > 0 ? '#e3f2fd' : '#f5f5f5' }};color:{{ $myTicketsToday > 0 ? '#1e88e5' : '#aaa' }}">
        {{ $myTicketsToday }} tiket
      </span>
    </div>
    <div class="v2-card-body">
      @if($myTicketsTodayList->count() > 0)
        <div class="row">
          @foreach($myTicketsTodayList as $t)
          @php
            $stColors = [
              'Open'       => ['bc'=>'#e53935','bg'=>'#feeceb','lbl'=>'Open'],
              'Pending'    => ['bc'=>'#fb8c00','bg'=>'#fff3e0','lbl'=>'Pending'],
              'Inprogress' => ['bc'=>'#1e88e5','bg'=>'#e3f2fd','lbl'=>'In Progress'],
              'Solve'      => ['bc'=>'#43a047','bg'=>'#e8f5e9','lbl'=>'Solved'],
              'Close'      => ['bc'=>'#78909c','bg'=>'#f5f5f5','lbl'=>'Closed'],
            ];
            $sc = $stColors[$t->status] ?? ['bc'=>'#9e9e9e','bg'=>'#f9f9f9','lbl'=>$t->status];
          @endphp
          <div class="col-md-6 mb-2">
            <div style="border-left:3px solid {{ $sc['bc'] }};border-radius:0 6px 6px 0;padding:8px 12px;background:#fafafa">
              <div class="d-flex align-items-start justify-content-between mb-1">
                <div class="d-flex align-items-center" style="gap:6px;min-width:0;flex:1">
                  <span class="v2-badge" style="background:{{ $sc['bg'] }};color:{{ $sc['bc'] }};white-space:nowrap">
                    {{ $sc['lbl'] }}
                  </span>
                  <a href="{{ url('ticket/'.$t->id) }}" style="font-size:12px;font-weight:600;color:#1a1a2e;text-decoration:none" class="text-truncate">
                    #{{ $t->id }} — {{ $t->tittle ?? 'Tiket #'.$t->id }}
                  </a>
                </div>
                @if($t->time)
                  <span style="font-size:11px;color:#999;white-space:nowrap;margin-left:6px">
                    <i class="fas fa-clock mr-1"></i>{{ substr($t->time,0,5) }}
                  </span>
                @endif
              </div>
              <div style="font-size:11px;color:#aaa;display:flex;flex-wrap:wrap;gap:6px">
                @if($t->customer)
                  <span><i class="fas fa-user mr-1"></i>
                    <a href="{{ url('customer/'.$t->customer->id) }}" style="color:#888;text-decoration:none">{{ $t->customer->name }}</a>
                  </span>
                @elseif($t->called_by)
                  <span><i class="fas fa-user mr-1"></i>{{ $t->called_by }}</span>
                @endif
                @if($t->phone)
                  <span><i class="fas fa-phone mr-1"></i>{{ $t->phone }}</span>
                @endif
              </div>
              {{-- Workflow --}}
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
                    <div class="wf-dot {{ $wfc }}">
                      <i class="fas {{ $wfc === 'wf-done' ? 'fa-check' : 'fa-circle' }}" style="font-size:6px"></i>
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
        <div class="v2-empty">
          <i class="fas fa-clipboard-check text-muted"></i>
          <small>Tidak ada tiket dijadwalkan hari ini</small><br>
          <a href="{{ url('ticket') }}" class="v2-link-btn d-inline-block mt-2" style="font-size:11px">
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
  const chartTextColor = () => isDark() ? '#9ba3b2' : '#9e9e9e';
  const chartGridColor = () => isDark() ? '#2e3348' : '#f0f0f0';

  // Live clock
  (function tick() {
    const el = document.getElementById('live-clock');
    if (el) el.textContent = new Date().toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    setTimeout(tick, 1000);
  })();

  const dmBtn = document.getElementById('toggleDarkMode');
  if (dmBtn) dmBtn.addEventListener('click', () => {});
</script>
@endsection
