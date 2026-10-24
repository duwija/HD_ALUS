@extends('admin.layouts.app')

@section('content')
@php
    $editing = $agreement->exists;
    $val = fn($field) => old($field, $agreement->$field instanceof \Carbon\Carbon ? $agreement->$field->format('Y-m-d') : $agreement->$field);
@endphp
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">
                <i class="fas fa-file-signature mr-2"></i>
                @if ($editing) Ubah PKS {{ $agreement->nomor_pks }}
                @elseif ($previous) Perpanjangan / PKS Baru menggantikan {{ $previous->nomor_pks }}
                @else Tambah PKS
                @endif
            </h3>
            <a href="{{ $editing ? route('admin.pks.show', $agreement->id) : route('admin.pks.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            @include('admin.pks._flash')

            @if ($previous && !$editing)
                <div class="alert alert-info small">
                    <i class="fas fa-info-circle"></i> PKS ini akan dicatat sebagai <strong>perpanjangan</strong> dari
                    <strong>{{ $previous->nomor_pks }}</strong> (berlaku {{ $previous->berlaku_mulai->translatedFormat('d M Y') }} &ndash;
                    {{ $previous->effective_end ? $previous->effective_end->translatedFormat('d M Y') : 'tanpa batas' }}).
                    PKS lama akan berstatus "Diperpanjang / diganti".
                </div>
            @endif

            <form method="POST" enctype="multipart/form-data"
                  action="{{ $editing ? route('admin.pks.update', $agreement->id) : route('admin.pks.store') }}">
                @csrf
                @if ($editing) @method('PUT') @endif
                <input type="hidden" name="previous_id" value="{{ $val('previous_id') }}">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Tenant <span class="text-danger">*</span></label>
                        <select name="tenant_id" class="form-control" {{ $editing || $previous ? 'disabled' : 'required' }}>
                            <option value="">&mdash; pilih tenant &mdash;</option>
                            @foreach ($tenants as $t)
                                <option value="{{ $t->id }}" {{ (string) $val('tenant_id') === (string) $t->id ? 'selected' : '' }}>{{ $t->app_name }}</option>
                            @endforeach
                        </select>
                        @if ($previous && !$editing)
                            <input type="hidden" name="tenant_id" value="{{ $previous->tenant_id }}">
                        @endif
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">NIB</label>
                        <input type="text" name="nib" class="form-control" maxlength="50" value="{{ $val('nib') }}" placeholder="Nomor Induk Berusaha">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Nomor PKS <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_pks" class="form-control" maxlength="150" required value="{{ $val('nomor_pks') }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Tanggal PKS (ditandatangani)</label>
                        <input type="date" name="tanggal_pks" class="form-control" value="{{ $val('tanggal_pks') }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Berlaku mulai <span class="text-danger">*</span></label>
                        <input type="date" name="berlaku_mulai" class="form-control" required value="{{ $val('berlaku_mulai') }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Berlaku sampai</label>
                        <input type="date" name="berlaku_sampai" class="form-control" value="{{ $val('berlaku_sampai') }}">
                        <small class="text-muted">Kosongkan bila tanpa batas waktu. Perubahan masa berlaku lewat adendum dicatat di halaman detail.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Dokumen PKS {!! $editing ? '' : '<span class="text-danger">*</span>' !!}</label>
                    <input type="file" name="file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" {{ $editing ? '' : 'required' }}>
                    <small class="text-muted">PDF/JPG/PNG/DOC/DOCX, maksimal 20 MB.
                        @if ($editing && $agreement->file_name) File saat ini: <strong>{{ $agreement->file_name }}</strong> &mdash; unggah file baru untuk mengganti. @endif
                    </small>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3" maxlength="5000">{{ $val('keterangan') }}</textarea>
                </div>

                @if ($previous && !$editing && $previous->servicePoints->isNotEmpty())
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="copy_points" name="copy_points" value="1" {{ (old() ? old('copy_points') : true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="copy_points">
                            Salin {{ $previous->servicePoints->count() }} titik layanan/POP dari PKS lama
                            ({{ number_format($previous->total_capacity, 0, ',', '.') }} Mbps) &mdash; bisa diubah setelah disimpan.
                        </label>
                    </div>
                @endif

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </form>
        </div>
    </div>
</div>
@endsection
