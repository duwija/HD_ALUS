@extends('layout.main')
@section('title','Jadwal Shift')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1><i class="fas fa-calendar-alt mr-2 text-purple"></i>Jadwal Shift Karyawan</h1></div>
    </div>
  </div>
</section>

<section class="content"><div class="container-fluid">

  {{-- Filter bulan --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" class="form-inline">
        <label class="mr-2">Bulan:</label>
        <input type="month" name="month" class="form-control mr-2" value="{{ $month }}">
        <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-search mr-1"></i>Tampilkan</button>
      </form>
    </div>
  </div>

  {{-- Legend --}}
  <div class="mb-2">
    @foreach($shifts as $sh)
      <span class="badge mr-1 px-2 py-1" style="background:{{ $sh->color }};color:#fff">{{ $sh->name }}</span>
    @endforeach
    <span class="badge badge-light mr-1">OFF</span>
    <span class="badge badge-warning mr-1">Libur</span>
    <span class="badge badge-info mr-1">Izin</span>
    <span class="ml-3 mr-1" style="display:inline-flex;align-items:center;gap:4px;font-size:11px">
      <span style="background:#FFC107;display:inline-block;width:8px;height:8px;border-radius:50%"></span> Sudah absen masuk
    </span>
    <span class="mr-1" style="display:inline-flex;align-items:center;gap:4px;font-size:11px">
      <span style="background:#1565C0;display:inline-block;width:8px;height:8px;border-radius:50%"></span> Sudah absen pulang
    </span>
  </div>

  {{-- Jadwal grid per karyawan --}}
  @php
    $daysInMonth = \Carbon\Carbon::createFromDate($year, $m, 1)->daysInMonth;
  @endphp

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0" style="font-size:12px;white-space:nowrap">
          <thead class="bg-secondary text-white">
            <tr>
              <th style="min-width:130px">Karyawan</th>
              @for($d=1; $d<=$daysInMonth; $d++)
                @php $dt = \Carbon\Carbon::createFromDate($year,$m,$d); @endphp
                <th class="text-center {{ in_array($dt->dayOfWeek,[0,6]) ? 'table-danger' : '' }}" style="min-width:36px">
                  {{ $d }}<br><small>{{ $dt->format('D') }}</small>
                </th>
              @endfor
            </tr>
          </thead>
          <tbody>
            @foreach($employees as $emp)
            <tr>
              <td><strong>{{ $emp->name }}</strong></td>
              @for($d=1; $d<=$daysInMonth; $d++)
                @php
                  $ds     = \Carbon\Carbon::createFromDate($year,$m,$d)->format('Y-m-d');
                  $sched  = $schedules->get($emp->id)?->firstWhere('date', $ds) ?? null;
                  $isWE   = in_array(\Carbon\Carbon::parse($ds)->dayOfWeek,[0,6]);
                  $att    = $attendances->get($emp->id)?->get($ds) ?? null;
                @endphp
                <td class="text-center p-0 {{ $isWE ? 'table-danger' : '' }}"
                    style="cursor:pointer"
                    title="{{ $ds }}"
                    data-user="{{ $emp->id }}"
                    data-date="{{ $ds }}"
                    data-shift="{{ $sched?->shift_id }}"
                    data-type="{{ $sched?->day_type ?? 'work' }}"
                    onclick="openScheduleModal(this)">
                  @if($sched)
                    <div style="position:relative;display:inline-block">
                      @if($sched->day_type === 'off')
                        <span class="badge badge-light" style="font-size:9px">OFF</span>
                      @elseif($sched->day_type === 'holiday')
                        <span class="badge badge-warning" style="font-size:9px">LBR</span>
                      @elseif($sched->day_type === 'leave')
                        <span class="badge badge-info" style="font-size:9px">IZN</span>
                      @elseif($sched->shift)
                        <span class="badge" style="background:{{ $sched->shift->color }};color:#fff;font-size:9px">
                          {{ substr($sched->shift->name,0,3) }}
                        </span>
                      @endif
                      @if($att)
                        @php $dotColor = $att->clock_out ? '#1565C0' : '#FFC107'; @endphp
                        <span style="position:absolute;top:-4px;right:-5px;width:7px;height:7px;border-radius:50%;background:{{ $dotColor }};border:1px solid rgba(0,0,0,.2);display:block"
                              title="{{ $att->clock_out ? 'Absen masuk '.$att->clock_in.' | Pulang '.$att->clock_out : 'Absen masuk '.$att->clock_in.' (belum pulang)' }}"></span>
                      @endif
                    </div>
                  @else
                    <small class="text-muted">-</small>
                    @if($att)
                      @php $dotColor = $att->clock_out ? '#1565C0' : '#FFC107'; @endphp
                      <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:{{ $dotColor }};border:1px solid rgba(0,0,0,.2);vertical-align:middle"
                            title="{{ $att->clock_out ? 'Absen masuk '.$att->clock_in.' | Pulang '.$att->clock_out : 'Absen masuk '.$att->clock_in.' (belum pulang)' }}"></span>
                    @endif
                  @endif
                </td>
              @endfor
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div></section>

{{-- Modal jadwal --}}
<div class="modal fade" id="modal-schedule" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title"><i class="fas fa-calendar-check mr-1"></i>Set Jadwal</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p id="modal-sched-label" class="font-weight-bold"></p>
        <div class="form-group">
          <label>Tipe Hari</label>
          <select id="sched-day-type" class="form-control">
            <option value="work">Kerja</option>
            <option value="off">Off</option>
            <option value="holiday">Libur</option>
            <option value="leave">Izin/Sakit</option>
          </select>
        </div>
        <div id="sched-shift-wrap" class="form-group">
          <label>Shift</label>
          <select id="sched-shift" class="form-control">
            <option value="">-- Pilih Shift --</option>
            @foreach($shifts as $sh)
              <option value="{{ $sh->id }}" data-color="{{ $sh->color }}">{{ $sh->name }} ({{ $sh->start_time }}-{{ $sh->end_time }})</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label>Catatan</label>
          <input type="text" id="sched-note" class="form-control" placeholder="Opsional">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary btn-sm" id="btn-save-sched"><i class="fas fa-save mr-1"></i>Simpan</button>
        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('footer-scripts')
<script>
var _schedUserId, _schedDate;

function openScheduleModal(el){
  _schedUserId = $(el).data('user');
  _schedDate   = $(el).data('date');
  var type     = $(el).data('type') || 'work';
  var shiftId  = $(el).data('shift') || '';

  $('#modal-sched-label').text(_schedDate + ' — Karyawan ID: ' + _schedUserId);
  $('#sched-day-type').val(type);
  $('#sched-shift').val(shiftId);
  toggleShift(type);
  $('#modal-schedule').modal('show');
}

function toggleShift(type){
  $('#sched-shift-wrap').toggle(type === 'work');
}

$(function(){
  $('#sched-day-type').on('change', function(){ toggleShift(this.value); });

  $('#btn-save-sched').on('click', function(){
    $.post('/attendance/schedule', {
      _token:     '{{ csrf_token() }}',
      user_id:    _schedUserId,
      date:       _schedDate,
      shift_id:   $('#sched-day-type').val() === 'work' ? $('#sched-shift').val() : null,
      day_type:   $('#sched-day-type').val(),
      note:       $('#sched-note').val(),
    }, function(res){
      if(res.success){ location.reload(); }
    });
  });
});
</script>
@endsection
