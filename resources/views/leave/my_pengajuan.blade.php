@extends('layout.main')
@section('title','Pengajuan Izin & Lembur')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <h1><i class="fas fa-paper-plane mr-2 text-primary"></i>Pengajuan Saya</h1>
  </div>
</section>

<section class="content"><div class="container-fluid">

  {{-- Tabs --}}
  <div class="card card-primary card-tabs shadow-sm">
    <div class="card-header p-0 pt-1">
      <ul class="nav nav-tabs" id="pengajuanTab" role="tablist">
        <li class="nav-item">
          <a class="nav-link {{ request('tab','leave') === 'leave' ? 'active' : '' }}"
             id="tab-leave" data-toggle="pill" href="#pane-leave" role="tab">
            <i class="fas fa-umbrella-beach mr-1"></i>Izin / Cuti / Sakit
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request('tab') === 'overtime' ? 'active' : '' }}"
             id="tab-overtime" data-toggle="pill" href="#pane-overtime" role="tab">
            <i class="fas fa-business-time mr-1"></i>Lembur
          </a>
        </li>
      </ul>
    </div>
    <div class="card-body">
      <div class="tab-content" id="pengajuanTabContent">

        {{-- ──── TAB IZIN / CUTI ──────────────────────────────────────────── --}}
        <div class="tab-pane fade {{ request('tab','leave') === 'leave' ? 'show active' : '' }}"
             id="pane-leave" role="tabpanel">

          {{-- Form Pengajuan --}}
          <div class="card card-outline card-primary mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-plus-circle mr-1"></i>Ajukan Izin / Cuti / Sakit</h5></div>
            <div class="card-body">
              <form action="{{ url('my-pengajuan/leave') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                  <div class="form-group col-md-3">
                    <label class="font-weight-bold">Jenis <span class="text-danger">*</span></label>
                    <select name="type" class="form-control @error('type') is-invalid @enderror">
                      <option value="">-- Pilih --</option>
                      <option value="cuti"         {{ old('type')=='cuti'         ? 'selected':'' }}>Cuti</option>
                      <option value="sakit"        {{ old('type')=='sakit'        ? 'selected':'' }}>Sakit</option>
                      <option value="izin_lainnya" {{ old('type')=='izin_lainnya' ? 'selected':'' }}>Izin Lainnya</option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-3">
                    <label class="font-weight-bold">Tanggal Mulai <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                           value="{{ old('start_date', date('Y-m-d')) }}">
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-3">
                    <label class="font-weight-bold">Tanggal Selesai <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                           value="{{ old('end_date', date('Y-m-d')) }}">
                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-3">
                    <label class="font-weight-bold">Lampiran</label>
                    <input type="file" name="attachment" class="form-control-file @error('attachment') is-invalid @enderror"
                           accept=".jpg,.jpeg,.png,.pdf">
                    <small class="text-muted">JPG/PNG/PDF maks 5MB</small>
                    @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                  </div>
                </div>
                <div class="form-group">
                  <label class="font-weight-bold">Alasan / Keterangan <span class="text-danger">*</span></label>
                  <textarea name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror"
                            placeholder="Jelaskan alasan pengajuan...">{{ old('reason') }}</textarea>
                  @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-paper-plane mr-1"></i>Kirim Pengajuan
                </button>
              </form>
            </div>
          </div>

          {{-- Riwayat --}}
          <div class="card card-outline card-secondary">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-history mr-1"></i>Riwayat Pengajuan Izin/Cuti</h5></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th>#</th><th>Jenis</th><th>Tanggal</th><th>Hari</th>
                      <th>Alasan</th><th>Lampiran</th><th>Status</th><th>Catatan</th><th>Dibuat</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($leaves as $i => $lv)
                    <tr>
                      <td>{{ $leaves->firstItem() + $i }}</td>
                      <td>
                        @if($lv->type==='cuti')         <span class="badge badge-info">Cuti</span>
                        @elseif($lv->type==='sakit')    <span class="badge badge-secondary">Sakit</span>
                        @else                           <span class="badge badge-dark">Izin</span>
                        @endif
                      </td>
                      <td class="text-nowrap">
                        {{ \Carbon\Carbon::parse($lv->start_date)->format('d/m/Y') }}
                        @if($lv->start_date != $lv->end_date)
                          – {{ \Carbon\Carbon::parse($lv->end_date)->format('d/m/Y') }}
                        @endif
                      </td>
                      <td class="text-center">{{ $lv->days }}</td>
                      <td style="max-width:200px">{{ Str::limit($lv->reason,60) }}</td>
                      <td class="text-center">
                        @if($lv->attachment)
                          <a href="{{ asset('storage/KC/'.$lv->attachment) }}" target="_blank" class="btn btn-xs btn-outline-secondary">
                            <i class="fas fa-paperclip"></i>
                          </a>
                        @else <span class="text-muted">-</span> @endif
                      </td>
                      <td class="text-center">
                        @if($lv->status==='pending')  <span class="badge badge-warning">Menunggu</span>
                        @elseif($lv->status==='approved') <span class="badge badge-success">Disetujui</span>
                        @else <span class="badge badge-danger">Ditolak</span>
                        @endif
                      </td>
                      <td><small>{{ $lv->approval_notes ?? '-' }}</small></td>
                      <td class="text-nowrap"><small>{{ $lv->created_at->format('d/m/Y H:i') }}</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-3 text-muted">Belum ada pengajuan.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
            @if($leaves->hasPages())
            <div class="card-footer">{{ $leaves->appends(['tab'=>'leave','opage'=>request('opage')])->links() }}</div>
            @endif
          </div>
        </div>{{-- end pane-leave --}}

        {{-- ──── TAB LEMBUR ────────────────────────────────────────────────── --}}
        <div class="tab-pane fade {{ request('tab') === 'overtime' ? 'show active' : '' }}"
             id="pane-overtime" role="tabpanel">

          {{-- Form Lembur --}}
          <div class="card card-outline card-primary mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-plus-circle mr-1"></i>Ajukan Lembur</h5></div>
            <div class="card-body">
              <form action="{{ url('my-pengajuan/overtime') }}" method="POST">
                @csrf
                <div class="row">
                  <div class="form-group col-md-3">
                    <label class="font-weight-bold">Tanggal Lembur <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                           value="{{ old('date', date('Y-m-d')) }}">
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-3">
                    <label class="font-weight-bold">Jam Mulai <span class="text-danger">*</span></label>
                    <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror"
                           value="{{ old('start_time') }}">
                    @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-3">
                    <label class="font-weight-bold">Jam Selesai <span class="text-danger">*</span></label>
                    <input type="time" name="end_time" id="end_time" class="form-control @error('end_time') is-invalid @enderror"
                           value="{{ old('end_time') }}">
                    @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-3">
                    <label class="font-weight-bold">Durasi</label>
                    <input type="text" id="duration_display" class="form-control" readonly placeholder="Otomatis dihitung">
                  </div>
                </div>
                <div class="form-group">
                  <label class="font-weight-bold">Alasan / Keterangan <span class="text-danger">*</span></label>
                  <textarea name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror"
                            placeholder="Jelaskan keperluan lembur...">{{ old('reason') }}</textarea>
                  @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-paper-plane mr-1"></i>Kirim Pengajuan
                </button>
              </form>
            </div>
          </div>

          {{-- Riwayat --}}
          <div class="card card-outline card-secondary">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-history mr-1"></i>Riwayat Pengajuan Lembur</h5></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th>#</th><th>Tanggal</th><th>Jam Mulai</th><th>Jam Selesai</th>
                      <th>Durasi</th><th>Alasan</th><th>Status</th><th>Catatan</th><th>Dibuat</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($overtimes as $i => $ot)
                    <tr>
                      <td>{{ $overtimes->firstItem() + $i }}</td>
                      <td class="text-nowrap">{{ \Carbon\Carbon::parse($ot->date)->format('d/m/Y') }}</td>
                      <td>{{ $ot->start_time }}</td>
                      <td>{{ $ot->end_time }}</td>
                      <td class="text-center"><span class="badge badge-info">{{ $ot->duration_hours }} jam</span></td>
                      <td style="max-width:200px">{{ Str::limit($ot->reason,60) }}</td>
                      <td class="text-center">
                        @if($ot->status==='pending')   <span class="badge badge-warning">Menunggu</span>
                        @elseif($ot->status==='approved') <span class="badge badge-success">Disetujui</span>
                        @else <span class="badge badge-danger">Ditolak</span>
                        @endif
                      </td>
                      <td><small>{{ $ot->approval_notes ?? '-' }}</small></td>
                      <td class="text-nowrap"><small>{{ $ot->created_at->format('d/m/Y H:i') }}</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-3 text-muted">Belum ada pengajuan lembur.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
            @if($overtimes->hasPages())
            <div class="card-footer">{{ $overtimes->appends(['tab'=>'overtime','lpage'=>request('lpage')])->links() }}</div>
            @endif
          </div>
        </div>{{-- end pane-overtime --}}

      </div>
    </div>
  </div>

</div></section>

<script>
// Hitung durasi lembur otomatis
function calcDuration() {
  const s = document.querySelector('[name=start_time]')?.value;
  const e = document.querySelector('[name=end_time]')?.value;
  const disp = document.getElementById('duration_display');
  if (!s || !e || !disp) return;
  let [sh,sm] = s.split(':').map(Number);
  let [eh,em] = e.split(':').map(Number);
  let mins = (eh*60+em) - (sh*60+sm);
  if (mins <= 0) mins += 24*60;
  const h = Math.floor(mins/60), m = mins%60;
  disp.value = h + ' jam ' + (m ? m + ' menit' : '');
}
document.querySelector('[name=start_time]')?.addEventListener('change', calcDuration);
document.querySelector('[name=end_time]')?.addEventListener('change', calcDuration);
</script>
@endsection
