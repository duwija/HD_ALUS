@extends('layout.main')
@section('title',' Edit Merchant')
@section('maps')
{!!$map['js']!!}
@endsection
<script type="text/javascript">

  function updateDatabase(newLat, newLng)
  {
    document.getElementById("coordinate").value = newLat+','+newLng;

  }
</script>
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Edit Merchant </h3>
    </div>
    <form role="form" action="{{url ('merchant')}}/{{ $merchant->id }}" method="POST">
      @method('patch')
      @csrf
      <div class="card-body row">
        <div class="form-group col-md-2">
          <label for="nama">Name</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Enter Merchant Name" value="{{$merchant->name}}">
          @error('name')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group col-md-3">
          <label for="contact_name">Contact Name</label>
          <input type="text" class="form-control" name="contact_name" id="contact_name"  placeholder="Contact Name" value="{{$merchant->contact_name}}">

        </div>
        <div class="form-group col-md-2">
          <label for="nama">Phone No</label>
          <input type="text" class="form-control @error('phone') is-invalid @enderror " name="phone" id="phone" oninput="this.value = this.value.replace(/[^0-9]/g, '')"   placeholder="Customer phone" value="{{$merchant->phone}}">
          @error('phone')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="form-group col-md-2">
          <label for="address">Akun Kas</label>
          <select name="akun_code" id="akun_code" class="form-control select2" required>
            <option value="">none</option>
            @foreach($akuns as $akun)
            <option value="{{ $akun->akun_code }}" 
              {{ $merchant->akun_code == $akun->akun_code ? 'selected' : '' }}>
              {{ $akun->akun_code }} - {{ $akun->name }}
            </option>
            @endforeach
          </select>
        </div>


        <div class="form-group col-md-2">
          <label for="address">Payment Point?</label>
          <select name="payment_point" id="payment_point" class="form-control">
            <option value="1" {{ $merchant->payment_point == 1 ? 'selected' : '' }}>Yes</option>
            <option value="0" {{ $merchant->payment_point == 0 ? 'selected' : '' }}>No</option>
          </select>
        </div>
        <div class="form-group col-md-3">
          <label for="address">Address</label>
          <input type="text" class="form-control" name="address" id="address"  placeholder="address" value="{{$merchant->address}}">
        </div>


        <div class="form-group col-md-3">
          <label for="coordinate"> Coordinate </label>
          <div class="input-group mb-3">

            <input type="text" class="form-control @error('coordinate') is-invalid @enderror" name="coordinate"  id="coordinate" placeholder="Coordinate" value="{{$merchant->coordinate}}">
            @error('coordinate')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="input-group-append">
             <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-maps">Get From Maps </button>
           </div>
         </div>
       </div>
    <!--    <div class="form-group">
        <input type="hidden" name="create_at" value="{{now()}}" >
      </div>
    -->
    <div class="form-group col-md-5">
      <label for="description">Description  </label>
      <input type="text" class="form-control @error('description') is-invalid @enderror" name="description" id="description" placeholder="Site Descrition " value="{{$merchant->description}}">
      @error('description')
      <div class="error invalid-feedback">{{ $message }}</div>
      @enderror
    </div>


  </div>


  <!-- /.card-body -->

  <div class="card-footer">
    <button type="submit" class="btn btn-primary">Update</button>
  </form>
  <a href="{{url('distpoint')}}" class="btn btn-secondary  float-right">Cancel</a>
</div>

</div>
<!-- /.card -->

<!-- Form Element sizes -->


</div>
<div class="modal fade" id="modal-maps-edit">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
            <!-- <div class="modal-header">
             <h5 class="modal-title">drap Marker to Right Posision</h5> 
              
              
           </div>-->
           <div class="modal-body">
            {!! $map['html'] !!}
          </div>
          <div class="modal-footer justify-content-between float-right">
            <button type="button" class="btn btn-primary float-right " data-dismiss="modal">Apply</button>

          </div>
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>
  </section>

  @endsection