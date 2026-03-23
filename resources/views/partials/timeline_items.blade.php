@foreach($tickets as $t)
@php
switch ($t->status) {
  case 'Open':     $color = 'bg-danger'; break;
  case 'Close':    $color = 'bg-secondary'; break;
  case 'Pending':  $color = 'bg-warning'; break;
  case 'Solve':    $color = 'bg-info'; break;
  default:         $color = 'bg-primary'; break;
}
@endphp
<div class="timeline-item">
  <!-- Status Badge di pojok kanan atas -->
  <span class="status-badge {{ str_replace('bg-', 'badge-', $color) }}">
    {{ $t->status }}
  </span>
  
  <div class="row">
    <div class="col-12">
      <span class="time">🕒 {{ date('H:i', strtotime($t->time)) }}</span>
      <strong><i class="fas fa-user-friends ml-3"></i> {{ $t->user->name ?? '-' }}</strong> | {{ $t->member ?? '-' }}  
      <span class="small ml-2">#Created at : {{ $t->created_at }}</span>

      <!-- <hr class="bg-info"> -->
      {{-- Progress Workflow --}}
      <!-- Workflow progress -->
      @if($t->steps->count() > 0)
      @php
      $totalSteps = $t->steps->count();
      $currentIndex = $t->steps->search(fn($s) => $s->id == $t->current_step_id);
      $progressPercent = $currentIndex !== false && $totalSteps > 1
      ? ($currentIndex / ($totalSteps - 1)) * 100
      : 0;

      $currentStep = $t->steps->firstWhere('id', $t->current_step_id);
      $isFinishStep = $currentStep && strtolower($currentStep->name) === 'finish';
      if ($isFinishStep) $progressPercent = 100;
      @endphp

      <div class="workflow-wrapper position-relative">
        <!-- Garis dasar -->
        <div class="base-line position-absolute w-100"></div>

        <!-- Progress line -->
        <div class="progress-line position-absolute" style="width: {{ $progressPercent }}%;"></div>

        <!-- Step item -->
        <div class="d-flex justify-content-between">
          @foreach($t->steps as $i => $step)
          @php
          // tentukan status step
          if ($isFinishStep) {
            $class = 'done';
          } else {
            $class = $t->current_step_id == $step->id
            ? 'active'
            : ($currentIndex !== false && $i < $currentIndex ? 'done' : 'pending');
          }

          // jika nama step Finish → otomatis done
          if (strtolower($step->name) === 'finish') {
            $class = 'done';
          }
          @endphp

          <div class="text-center flex-fill">
            <div class="step-dot {{ $class }}">
              <i class="fas {{ $class === 'done' ? 'fa-check' : 'fa-circle-notch' }}"></i>
            </div>
            <span class="step-label small d-block text-truncate"
            data-toggle="tooltip"
            data-trigger="click"
            title="{{ ucfirst($step->name) }}">
            {{ \Illuminate\Support\Str::limit(ucfirst($step->name), 12) }}
          </span>

        </div>
        @endforeach
      </div>
    </div>
    @else
    <hr class='bg-info'>
    @endif
    </div>
  </div>
  
  <div class="row mt-2">
    <div class="col-md-7">
      <span class="timeline-header">
        <a href="/ticket/{{ $t->id }}"><span class="badge-modern badge-{{ $t->status }}">{{ $t->id }}</span></a> <br>
        @if($t->customer)
        <a href="/customer/{{ $t->customer->id }}">{{ $t->customer->customer_id }} ({{ $t->customer->name }})</a>
        @else
        -
        @endif
      </span>
    </div>
    <div class="col-md-5">
      <span class="small">📞 : {{ $t->called_by }} | {{ $t->phone }}<br>✍ : {{ $t->create_by }}</span>
    </div>
  </div>
  
  <div class="row mt-2">
    <div class="col-12">
      <div class="timeline-body">
        <strong>{{ $t->tittle }}</strong>
      </div>
    </div>
  </div>
</div>
@endforeach
