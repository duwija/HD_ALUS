@extends('layout.main')
@section('title', $shift->id ? 'Edit Shift' : 'Tambah Shift')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <h1><i class="fas fa-clock text-primary mr-2"></i>{{ $shift->id ? 'Edit Shift' : 'Tambah Shift' }}</h1>
  </div>
</section>

<section class="content"><div class="container-fluid">
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <form method="POST" action="{{ $shift->id ? '/attendance/shifts/'.$shift->id : '/attendance/shifts' }}">
          @csrf
          @if($shift->id) @method('PATCH') @endif
          @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul></div>
          @endif

          <div class="form-group">
            <label>Nama Shift <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $shift->name) }}" placeholder="Shift Pagi" required>
            @error('name')<div class="invalid-feedback">{{$message}}</div>@enderror
          </div>

          <div class="row">
            <div class="col">
              <div class="form-group">
                <label>Jam Masuk <span class="text-danger">*</span></label>
                <input type="time" name="start_time" class="form-control"
                       value="{{ old('start_time', $shift->start_time ?? '08:00') }}" required>
              </div>
            </div>
            <div class="col">
              <div class="form-group">
                <label>Jam Keluar <span class="text-danger">*</span></label>
                <input type="time" name="end_time" class="form-control"
                       value="{{ old('end_time', $shift->end_time ?? '17:00') }}" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Toleransi Terlambat (menit)</label>
            <input type="number" name="late_tolerance" class="form-control"
                   value="{{ old('late_tolerance', $shift->late_tolerance ?? 15) }}" min="0" max="120">
            <small class="text-muted">Karyawan dianggap terlambat jika clock-in melebihi jam masuk + toleransi ini.</small>
          </div>

          <div class="form-group">
            <label>Warna Shift</label>
            <input type="color" name="color" class="form-control" style="height:40px"
                   value="{{ old('color', $shift->color ?? '#3498db') }}">
          </div>

          <div class="form-group">
            <label>Catatan</label>
            <textarea name="note" class="form-control" rows="2">{{ old('note', $shift->note) }}</textarea>
          </div>

          <div class="form-check mb-3">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" name="is_active" value="1" id="chk-active"
              {{ old('is_active', $shift->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="chk-active">Shift Aktif</label>
          </div>

          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button>
          <a href="/attendance/shifts" class="btn btn-secondary ml-1">Batal</a>
        </form>
      </div>
    </div>
  </div>
</div>
</div></section>
@endsection
