@extends('layout.main')
@section('title',' Site')


@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Edit Site </h3>
    </div>
    <form role="form" action="{{url ('distrouter')}}/{{ $distrouter->id }}" method="POST">
      @method('patch')
      @csrf
      <div class="card-body">
        <div class="form-group">
          <label for="nama">Name</label>
          <input type="text" disabled="" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Enter distrouter Name" value="{{ $distrouter->name }}">
          @error('name')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="form-group">
          <label for="Ip">IP </label>
          <input type="text" class="form-control @error('Ip') is-invalid @enderror" name="ip" id="Ip"  placeholder="distrouter Ip" value="{{ $distrouter->ip }}">
          @error('location')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label for="Api Port"> Api Port </label>
          <div class="input-group mb-3">

            <input type="text" class="form-control @error('Api Port') is-invalid @enderror" name="port"  id="Api Port" placeholder="Api Port" value="{{ $distrouter->port }}">
            @error('Api Port')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror

          </div>
        </div>

        <div class="form-group">
          <label for="web">Web Port </label>
          <input type="text" class="form-control @error('web') is-invalid @enderror" name="web" id="web" placeholder="distrouter Descrition" value="{{ $distrouter->web }}">
          @error('web')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label for="user">User</label>
          <input type="text" class="form-control @error('user') is-invalid @enderror " name="user" id="user"  placeholder="Enter Router username" value="{{ $distrouter->user }}">
          @error('user')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" class="form-control @error('password') is-invalid @enderror " name="password" id="password"  placeholder="Enter Router password" value="{{ $distrouter->password }}">
          @error('password')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>


        <div class="form-group">
          <label for="description">Description  </label>
          <input type="text" class="form-control @error('note') is-invalid @enderror" name="note" id="note" placeholder="Router Descrition " value="{{ $distrouter->note }}">
          @error('note')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

      </div>
      <!-- /.card-body -->

      <div class="card-footer">
        <button type="submit" class="btn btn-primary">Update</button>
      </form>
      <a href="{{url('distrouter')}}" class="btn btn-secondary  float-right">Cancel</a>
    </div>

  </div>
  <!-- /.card -->

  <!-- Form Element sizes -->


</div>

</section>

@endsection