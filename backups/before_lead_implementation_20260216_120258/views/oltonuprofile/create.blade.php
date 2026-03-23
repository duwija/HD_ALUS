@extends('layout.main')
@section('title','Add New Profile')


@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Add Onu Profile {{$olt}} </h3>
    </div>
    <form role="form" method="post" action="/oltonuprofile">
      @csrf
      <div class="card-body">
        <div class="form-group">
          <label for="nama">Name</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Enter Site Name" value="{{old('name')}}">
          @error('name')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>


        <div class="form-group">
          <input type="hidden" name="create_at" value="{{now()}}" >
        </div>

        <div class="form-group">
          <input type="hidden" name="olt" value="{{$olt}}" >
        </div>

        <div class="form-group">
          <label for="description">Vlan Id  </label>
          <input type="text" class="form-control @error('vlan') is-invalid @enderror" name="vlan" id="vlan" placeholder="Vlan Id " value="{{old('vlan')}}">
          @error('vlan')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

      </div>
      <!-- /.card-body -->

      <div class="card-footer">
        <button type="submit" class="btn btn-primary">Submit</button>
        <a href="{{url('oltonuprofile/$olt')}}" class="btn btn-default float-right">Cancel</a>
      </div>
    </form>
  </div>
  <!-- /.card -->

  <!-- Form Element sizes -->


</div>

<!-- /.modal -->
</section>

@endsection