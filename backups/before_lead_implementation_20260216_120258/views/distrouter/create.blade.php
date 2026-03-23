@extends('layout.main')
@section('title','Add New Distrouter')


@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Add New Router </h3>
    </div>
    <form role="form" method="post" action="/distrouter">
      @csrf
      <div class="card-body">
        <div class="form-group">
          <label for="nama">Name</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Enter Router Name" value="{{old('name')}}">
          @error('name')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="form-group">
         <label for="ip"> IP </label>
         <input type="text" class="form-control @error('ip') is-invalid @enderror" name="ip"  id="ip" placeholder="IP address" value="{{old('ip')}}">
         @error('ip')
         <div class="error invalid-feedback">{{ $message }}</div>
         @enderror
       </div>
       <div class="form-group">
        <label for="port">Api Port </label>
        <div class="input-group mb-3">

          <input type="text" class="form-control @error('port') is-invalid @enderror" name="port"  id="port" placeholder="Api port" value="{{old('port')}}">
          @error('coordinate')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
          
        </div>
      </div>
      <div class="form-group">
        <label for="web">Web Port </label>
        <div class="input-group mb-3">

          <input type="text" class="form-control @error('web') is-invalid @enderror" name="web"  id="web" placeholder="Web Prt" value="{{old('web')}}">
          @error('coordinate')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
          
        </div>
      </div>


      <div class="form-group">
        <label for="user">User</label>
        <input type="text" class="form-control @error('user') is-invalid @enderror " name="user" id="user"  placeholder="Enter Router username" value="{{old('user')}}">
        @error('user')
        <div class="error invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror " name="password" id="password"  placeholder="Enter Router password" value="{{old('password')}}">
        @error('password')
        <div class="error invalid-feedback">{{ $message }}</div>
        @enderror
      </div>


      <div class="form-group">
        <label for="description">Description  </label>
        <input type="text" class="form-control @error('note') is-invalid @enderror" name="note" id="note" placeholder="Router Descrition " value="{{old('note')}}">
        @error('note')
        <div class="error invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

    </div>
    <!-- /.card-body -->

    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Submit</button>
      <a href="{{url('disrouter')}}" class="btn btn-default float-right">Cancel</a>
    </div>
  </form>
</div>
<!-- /.card -->

<!-- Form Element sizes -->


</div>
       <!--    <div class="modal fade" id="modal-maps">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <!-- <div class="modal-header">
             <h5 class="modal-title">drap Marker to Right Posision</h5> 
              
              
           </div>-->
       <!--     <div class="modal-body">

       </div> -->
<!--        <div class="modal-footer justify-content-between float-right">
        <button type="button" class="btn btn-primary float-right " data-dismiss="modal">Apply</button>

      </div> -->
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div> -->
<!-- /.modal -->
</section>

@endsection