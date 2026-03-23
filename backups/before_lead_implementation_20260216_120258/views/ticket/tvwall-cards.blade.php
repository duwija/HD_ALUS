@foreach($tickets as $t)
@php
$statusClass = match(strtolower($t->status)) {
  'open'        => 'ticket-open',
  'inprogress'  => 'ticket-inprogress',
  'pending'     => 'ticket-pending',
  'solve'       => 'ticket-solve',
  'close'       => 'ticket-close',
  default       => 'ticket-open'
};
$currentIndex = $t->steps->search(fn($s) => $s->id == $t->current_step_id);
$totalSteps = $t->steps->count();
$progressPercent = $totalSteps > 1 ? round(($currentIndex / ($totalSteps - 1)) * 100, 0) : 0;
@endphp

<div class="ticket-card {{ $statusClass }}">
  <div class="ticket-status">{{ strtoupper($t->status) }}</div>

  <div>
    <h5 style="margin:0;">#{{ $t->id }} — {{ $t->customer->name ?? 'Unknown' }}</h5>
    <small>📞 {{ $t->called_by }} | {{ $t->phone }}</small>
  </div>

  <div class="workflow-wrapper">
    <div class="workflow-line"></div>
    <div class="workflow-progress" style="width: {{ $progressPercent }}%;"></div>

    <div class="workflow-steps">
      @foreach($t->steps as $i => $step)
      @php
      $class = $i < $currentIndex ? 'done' : ($i == $currentIndex ? 'active' : '');
      @endphp
      <div class="step-item">
        <div class="step-dot {{ $class }}">
          <i class="fas {{ ($class == 'done' || strtolower($step->name) === 'finish') ? 'fa-check' : 'fa-circle' }}"></i>
        </div>
        <div class="step-label">{{ ucfirst($step->name) }}</div>
      </div>
      @endforeach
    </div>
    <div class="progress-percent">{{ $progressPercent }}%</div>
  </div>

  <div class="ticket-footer mt-1 position-relative">
    <div class="fw-bold text-center"><strong>{{ $t->tittle }}</strong></div>
    <div class="small">👤 {{ $t->user->name ?? '-' }}</div>
    <div class="time-row">
      <div class="time-left">🕒 {{ \Carbon\Carbon::parse($t->created_at)->format('d M Y H:i') }}</div>
      <div class="time-right">📅 <strong>{{ \Carbon\Carbon::parse($t->date)->format('d M Y') }} {{ $t->time }}</strong></div>
    </div>
  </div>
</div>
@endforeach
