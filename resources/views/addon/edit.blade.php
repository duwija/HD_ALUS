@extends('layout.main')
@section('title','Edit Add-on')
@section('content')

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1><i class="fas fa-puzzle-piece mr-2"></i>Edit Add-on</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/home">Home</a></li>
          <li class="breadcrumb-item"><a href="{{ route('addon.index') }}">Add-on</a></li>
          <li class="breadcrumb-item active">Edit</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="card card-warning card-outline shadow-sm" style="max-width:600px;">
      <div class="card-header"><h3 class="card-title">Edit: {{ $addon->name }}</h3></div>
      <form action="{{ route('addon.update', $addon->id) }}" method="POST">
        @csrf @method('PATCH')
        <div class="card-body">
          <div class="form-group">
            <label>Nama Add-on <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
              value="{{ old('name', $addon->name) }}">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Harga (Rp) <span class="text-danger">*</span></label>
            <input type="number" name="price" class="form-control @error('price') is-invalid @enderror"
              value="{{ old('price', $addon->price) }}" min="0">
            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
              value="{{ old('description', $addon->description) }}">
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
          <a href="{{ route('addon.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i>Kembali
          </a>
          <button type="submit" class="btn btn-warning btn-sm">
            <i class="fas fa-save mr-1"></i>Update
          </button>
        </div>
      </form>
    </div>
  </div>
</section>
@endsection
