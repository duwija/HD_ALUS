@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-plus mr-2"></i> Tambah License Plan</h3>
                    <a href="{{ route('admin.license-plans.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.license-plans.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="name">Nama Plan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}"
                                   placeholder="Contoh: Starter, Basic, Professional">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="max_customers">Maksimum Pelanggan Aktif <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('max_customers') is-invalid @enderror"
                                   id="max_customers" name="max_customers" value="{{ old('max_customers', 50) }}"
                                   min="-1">
                            @error('max_customers')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Masukkan <strong>-1</strong> untuk unlimited (tidak terbatas).
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="price_monthly">Harga per Bulan (Rp)</label>
                            <input type="number" class="form-control @error('price_monthly') is-invalid @enderror"
                                   id="price_monthly" name="price_monthly" value="{{ old('price_monthly', 0) }}"
                                   min="0" step="1000">
                            @error('price_monthly')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3"
                                      placeholder="Deskripsi singkat plan ini">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="sort_order">Urutan Tampil</label>
                                <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                       id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6 d-flex align-items-end pb-1">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_active"
                                           name="is_active" value="1"
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_active">Plan Aktif</label>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.license-plans.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
