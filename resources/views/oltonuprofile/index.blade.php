@extends('layout.main')
@section('title','Olt Onu Profile')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Olt Onu Profile <a href="/olt/{{ $olt->id }}" class="btn  bg-gradient-primary btn-sm"> <strong>{{ $olt->name }} </strong> </a> </h3>
      <button type="button" class="float-right btn  bg-gradient-primary btn-sm" data-toggle="modal" data-target="#modal-ontonuprofile">Add Onu Vlan Profile</button>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      <table id="example1" class="table table-bordered table-striped">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Olt Name</th>
            <th scope="col">Profile Name</th>
            <th scope="col">Vlan id</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
         @foreach( $oltonuprofile as $oltonuprofile)
         <tr>
          <th scope="row">{{ $loop->iteration }}</th>
          <td>{{ $oltonuprofile->olt->name }}</td>
          <td>{{ $oltonuprofile->name }}</td>
          <td>{{ $oltonuprofile->vlan }}</td>
          <td>   <form  action="/oltonuprofile/{{ $oltonuprofile->id }}/{{ $olt->id }}" method="POST" class="d-inline site-delete" >
            @method('delete')
            @csrf

            <button type="submit"  class="btn btn-danger btn-sm">  Delete  </button>
          </form></td>


        </tr>
        @endforeach

      </tbody>
    </table>
  </div>

</div>


</div>



<div class="modal fade" id="modal-ontonuprofile">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
            <!-- <div class="modal-header">
             <h5 class="modal-title">drap Marker to Right Posision</h5> 
              
              
           </div>-->
           <div {{-- class="modal-body" --}}>
             <div {{-- class="content-header" --}}>

              <div class="card card-primary card-outline">
                <div class="card-header">
                  <h3 class="card-title font-weight-bold"> Add Onu Vlan Profile </h3>
                </div>

                <form role="form" method="post" action="/oltonuprofile">
                  @csrf
                  <div class="card-body">

                   <div class="form-group">
                    <input type="hidden" name="created_at" value="{{now()}}" >
                  </div>

                  <div class="form-group">
                    <input type="hidden" name="id_olt" value="{{$olt->id}}" >
                  </div>
                  <div class="form-group">
                    <label for="nama">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Enter Vlan Name" value="{{old('name')}}">
                    @error('name')
                    <div class="error invalid-feedback">{{ $message }}</div>
                    @enderror
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
                  
                  <span class="btn btn-default float-right" data-dismiss="modal" aria-label="Close">cancel</span>
                </div>
              </form>

            </div>

          </div>

        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->


  </div>

</section>




@endsection
