@extends('layout.main')
@section('title', 'Edit Distpoint Group')

@section('content')
<section class="content-header">
  <div class="d-flex justify-content-center">
    <div class="col-md-6 mb-4">
      <div class="card card-primary card-outline">
        <div class="card-header">
          <h3 class="card-title font-weight-bold">Edit Distpoint Group</h3>
        </div>

        <form role="form" method="POST" action="{{ url('distpointgroup/' . $distpointgroup->id) }}">
          @csrf
          @method('PATCH')
          <div class="card-body">
            <div class="form-group">
              <label for="name">Name</label>
              <input type="text"
              class="form-control @error('name') is-invalid @enderror"
              name="name"
              id="name"
              value="{{ $distpointgroup->name }}">
              @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label for="capacity">Capacity</label>
              <input type="number"
              class="form-control @error('capacity') is-invalid @enderror"
              name="capacity"
              id="capacity"
              value="{{ old('capacity', $distpointgroup->capacity) }}"
              placeholder="Enter capacity">
              @error('capacity')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label for="description">Description</label>
              <input type="text"
              class="form-control @error('description') is-invalid @enderror"
              name="description"
              id="description"
              placeholder="Enter description"
              value="{{ old('description', $distpointgroup->description) }}">
              @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="card-footer">
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ url('distpointgroup') }}" class="btn btn-secondary float-right">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
