@if($tickets->isEmpty())
  <div class="text-center text-muted py-4">Tidak ada tiket untuk filter yang dipilih.</div>
@else
<div class="schedule-grid">
  @foreach($tickets as $t)
  @php
    $status = strtolower($t->status ?? 'open');
    $statusClass = [
      'open' => 'status-open',
      'inprogress' => 'status-inprogress',
      'pending' => 'status-pending',
      'solve' => 'status-solve',
      'close' => 'status-close',
    ][$status] ?? 'status-open';

    $cardClass = [
      'open' => 'card-open',
      'inprogress' => 'card-inprogress',
      'pending' => 'card-pending',
      'solve' => 'card-solve',
      'close' => 'card-close',
    ][$status] ?? 'card-open';

    $totalSteps = $t->steps->count();
    $currentIndex = $t->steps->search(fn($s) => $s->id == $t->current_step_id);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
    $progressPercent = $totalSteps > 1 ? round(($currentIndex / ($totalSteps - 1)) * 100, 0) : 0;

    $startTime = $t->created_at;
    $endTime = in_array($status, ['solve', 'close']) ? ($t->updated_at ?? now()) : now();
    $durationMinutes = $startTime ? $startTime->diffInMinutes($endTime) : 0;
    $durationDays = intdiv($durationMinutes, 1440);
    $durationHours = intdiv($durationMinutes % 1440, 60);
    $durationRemainMinutes = $durationMinutes % 60;
    $mttrRunning = $durationDays > 0
      ? $durationDays . ' hari ' . $durationHours . ' jam ' . $durationRemainMinutes . ' menit'
      : ($durationHours > 0
        ? $durationHours . ' jam ' . $durationRemainMinutes . ' menit'
        : $durationRemainMinutes . ' menit');
  @endphp
  <div class="schedule-ticket-card {{ $cardClass }}">
    <div class="schedule-ticket-head">
      <div class="d-flex align-items-center" style="gap:8px;">
        <span class="status-pill {{ $statusClass }}">{{ strtoupper($t->status) }}</span>
        <span style="font-size:12px;font-weight:700;">#{{ $t->id }}</span>
      </div>
      <span style="font-size:12px;font-weight:700;color:#46556f;"><i class="far fa-clock"></i> {{ $t->time ?: '-' }}</span>
    </div>

    <div class="ticket-title">{{ $t->tittle ?: '-' }}</div>

    <div class="ticket-meta">
      <span><i class="fas fa-user mr-1"></i>{{ optional($t->customer)->name ?? '-' }}</span>
      <span><i class="fas fa-phone-alt mr-1"></i>{{ $t->phone ?: '-' }}</span>
    </div>

    <div class="ticket-row">
      <span><i class="fas fa-layer-group mr-1"></i>{{ optional($t->categorie)->name ?? '-' }}</span>
      <span><i class="fas fa-user-cog mr-1"></i>{{ optional($t->user)->name ?? '-' }}</span>
    </div>

    <div class="mttr-row">
      <span class="mttr-chip">
        <i class="fas fa-stopwatch"></i>
        MTTR berjalan: {{ $mttrRunning }}
      </span>
    </div>

    @if($totalSteps > 0)
    <div class="workflow-steps">
      @foreach($t->steps as $i => $step)
      @php
        $stepClass = $i < $currentIndex ? 'done' : ($i == $currentIndex ? 'active' : '');
      @endphp
      <div class="workflow-step" title="{{ $step->name }}">
        <div class="workflow-dot {{ $stepClass }}"></div>
        <div class="workflow-label">{{ ucfirst($step->name) }}</div>
      </div>
      @endforeach
    </div>
    @else
    <div class="workflow-info" style="margin-bottom:8px;">
      <span>Workflow belum dibuat</span>
      <span>-</span>
    </div>
    @endif

    <div class="workflow-track">
      <div class="workflow-fill" style="width: {{ $progressPercent }}%"></div>
    </div>
    <div class="workflow-info">
      <span>Workflow {{ $progressPercent }}%</span>
      <a href="{{ url('ticket/'.$t->id) }}" class="btn btn-xs ticket-link-btn" title="Lihat tiket #{{ $t->id }}">#{{ $t->id }}</a>
    </div>
  </div>
  @endforeach
</div>
@endif
