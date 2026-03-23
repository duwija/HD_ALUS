@php
$typeConfig = [
  'workflow_step'          => ['icon'=>'fa-stream',         'color'=>'var(--brand)',  'bg'=>'rgba(163,48,28,.12)',   'label'=>'Workflow Step'],
  'workflow_stage_id'      => ['icon'=>'fa-project-diagram','color'=>'#7c3aed',      'bg'=>'rgba(124,58,237,.12)',  'label'=>'Workflow Stage'],
  'lead_source'            => ['icon'=>'fa-tags',           'color'=>'#17a2b8',      'bg'=>'rgba(23,162,184,.12)', 'label'=>'Lead Source'],
  'expected_close_date'    => ['icon'=>'fa-calendar-alt',   'color'=>'#6610f2',      'bg'=>'rgba(102,16,242,.12)', 'label'=>'Expected Close'],
  'conversion_probability' => ['icon'=>'fa-chart-line',     'color'=>'#fd7e14',      'bg'=>'rgba(253,126,20,.12)', 'label'=>'Probability'],
  'lead_notes'             => ['icon'=>'fa-sticky-note',    'color'=>'#20c997',      'bg'=>'rgba(32,201,151,.12)', 'label'=>'Notes'],
];
$defaultCfg = ['icon'=>'fa-pencil-alt', 'color'=>'var(--text-muted)', 'bg'=>'var(--bg-surface-2)', 'label'=>'Update'];
@endphp

@if($leadUpdates->count() > 0)
<div class="col-md-12 mt-3">
<div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;box-shadow:var(--shadow-sm);">
  {{-- Top accent stripe --}}
  <div style="height:3px;background:linear-gradient(90deg,#17a2b8,var(--brand));"></div>
  {{-- Card header --}}
  <div class="d-flex align-items-center px-3 py-2" style="border-bottom:1px solid var(--border);">
    <div style="width:30px;height:30px;border-radius:50%;background:rgba(23,162,184,.12);display:flex;align-items:center;justify-content:center;margin-right:8px;flex-shrink:0;">
      <i class="fas fa-history" style="color:#17a2b8;font-size:13px;"></i>
    </div>
    <div>
      <div style="font-size:0.9rem;font-weight:700;color:var(--text-primary);">Update History</div>
      <div style="font-size:0.72rem;color:var(--text-muted);">{{ $leadUpdates->count() }} perubahan tercatat</div>
    </div>
  </div>
  {{-- Timeline body --}}
  <div style="max-height:420px;overflow-y:auto;padding:12px 14px;">
    @foreach($leadUpdates as $update)
    @php $cfg = $typeConfig[$update->field_changed] ?? $defaultCfg; @endphp
    <div class="d-flex" style="gap:10px;margin-bottom:10px;align-items:flex-start;">
      {{-- Icon --}}
      <div style="flex-shrink:0;width:30px;height:30px;border-radius:50%;background:{{ $cfg['bg'] }};display:flex;align-items:center;justify-content:center;margin-top:2px;">
        <i class="fas {{ $cfg['icon'] }}" style="font-size:11px;color:{{ $cfg['color'] }};"></i>
      </div>
      {{-- Content card --}}
      <div style="flex:1;min-width:0;background:var(--bg-surface-2);border-radius:8px;padding:8px 10px;border:1px solid var(--border);">
        {{-- Who + what + when --}}
        <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap:4px;margin-bottom:5px;">
          <div style="font-size:0.8rem;">
            <span style="font-weight:700;color:var(--text-primary);">{{ $update->updated_by }}</span>
            <span style="color:var(--text-muted);"> · </span>
            <span style="color:{{ $cfg['color'] }};font-weight:600;">{{ $cfg['label'] }}</span>
          </div>
          <span style="font-size:0.7rem;color:var(--text-muted);white-space:nowrap;flex-shrink:0;">
            <i class="fas fa-clock mr-1"></i>{{ $update->created_at->format('d M Y, H:i') }}
          </span>
        </div>
        {{-- Old → New --}}
        <div class="d-flex align-items-center flex-wrap" style="gap:5px;">
          <div style="font-size:0.77rem;padding:2px 8px;border-radius:4px;background:rgba(0,0,0,.05);color:var(--text-muted);max-width:44%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $update->old_value }}">
            @if($update->field_changed == 'conversion_probability'){{ $update->old_value ?? '0' }}%@else{{ $update->old_value ?: '-' }}@endif
          </div>
          <i class="fas fa-long-arrow-alt-right" style="font-size:10px;color:var(--text-muted);flex-shrink:0;"></i>
          <div style="font-size:0.77rem;padding:2px 8px;border-radius:4px;background:{{ $cfg['bg'] }};color:{{ $cfg['color'] }};font-weight:600;max-width:44%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $update->new_value }}">
            @if($update->field_changed == 'conversion_probability'){{ $update->new_value }}%@else{{ $update->new_value }}@endif
          </div>
        </div>
        {{-- Notes --}}
        @if($update->notes)
        <div style="margin-top:5px;padding-top:5px;border-top:1px dashed var(--border);font-size:0.78rem;color:var(--text-secondary);line-height:1.6;">
          {!! $update->notes !!}
        </div>
        @endif
      </div>
    </div>
    @endforeach
    {{-- Lead created --}}
    <div class="d-flex" style="gap:10px;align-items:flex-start;">
      <div style="flex-shrink:0;width:30px;height:30px;border-radius:50%;background:rgba(40,167,69,.12);display:flex;align-items:center;justify-content:center;margin-top:2px;">
        <i class="fas fa-user-plus" style="font-size:11px;color:#28a745;"></i>
      </div>
      <div style="flex:1;min-width:0;background:var(--bg-surface-2);border-radius:8px;padding:8px 10px;border:1px solid var(--border);">
        <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap:4px;">
          <div style="font-size:0.8rem;">
            <span style="font-weight:700;color:var(--text-primary);">{{ $customer->created_by ?? 'System' }}</span>
            <span style="color:var(--text-muted);"> · </span>
            <span style="color:#28a745;font-weight:600;">Lead Dibuat</span>
          </div>
          <span style="font-size:0.7rem;color:var(--text-muted);white-space:nowrap;">
            <i class="fas fa-clock mr-1"></i>{{ $customer->created_at->format('d M Y, H:i') }}
          </span>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
@else
<div class="col-md-12 mt-2">
  <div style="background:var(--bg-surface-2);border:1px solid var(--border);border-radius:8px;padding:12px 16px;text-align:center;">
    <i class="fas fa-history" style="color:var(--text-muted);font-size:18px;display:block;margin-bottom:4px;"></i>
    <span style="font-size:0.82rem;color:var(--text-muted);">Belum ada update history.</span>
  </div>
</div>
@endif
