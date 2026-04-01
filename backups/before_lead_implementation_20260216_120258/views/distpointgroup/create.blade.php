@extends('layout.main')
@section('title', 'Add New Distpoint Group')

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold">Add Distribution Group</h3>
    </div>

    <form role="form" method="POST" action="/distpointgroup">
      @csrf
      <div class="card-body">
        <div class="form-group">
          <label for="name">Name</label>
          <input type="text" 
                 class="form-control @error('name') is-invalid @enderror" 
                 name="name" 
                 id="name" 
                 placeholder="Enter Group Name" 
                 value="{{ old('name') }}">
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
                 placeholder="Enter Capacity" 
                 value="{{ old('capacity') }}">
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
                 placeholder="Enter Description" 
                 value="{{ old('description') }}">
          @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="card-footer">
        <button type="submit" class="btn btn-primary">Submit</button>
        <a href="{{ url('dispointgroup') }}" class="btn btn-secondary float-right">Cancel</a>
      </div>
    </form>
  </div>
</section>
@endsection
