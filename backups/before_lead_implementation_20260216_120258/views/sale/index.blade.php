@extends('layout.main')
@section('title','Sales List')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Sales List  </h3>
      <a href="{{url ('sale/create')}}" class=" float-right btn  bg-gradient-primary btn-sm">Add New Sales</a>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped">

          <thead >
            <tr>
              <th scope="col">#</th>
              <th scope="col">Name</th>
              <th scope="col">Email</th>
              <th scope="col">Sales Type</th>
              <th scope="col">Phone</th>
              <th scope="col">Address</th>
              <!-- <th scope="col">Customer</th> -->
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
           @foreach( $sale as $sale)
           <tr>
            <th scope="row">{{ $loop->iteration }}</th>
            <td class="text-center"><a href="/sale/{{ $sale->id }}" class="badge bg-primary m-1">{{ $sale->name }}  </a></td>
            <td> {{ $sale->email }}</td>
            <td>{{ $sale->sale_type }}</td>
            <td>{{ $sale->phone }}</td>
            <td>{{ $sale->address }}</td>
            <!-- <td>{{ $sale->count }}</td> -->
            <td >
              <!-- <div class="float-right " > -->
                <button type="button" class="btn btn-primary btn-sm m-1" data-toggle="modal" data-target="#modal-sale-detail{{ $sale->id }}">Detail </button>


                <a href="/sale/{{ $sale->id }}/edit" class="btn btn-primary btn-sm m-1"> <i class="fa fa-edit"> </i> </a>


                <form  action="/sale/{{ $sale->id }}" method="POST" class="d-inline item-delete " >
                  @method('delete')
                  @csrf

                  <button type="submit"  class="btn btn-danger btn-sm m-1"> <i class="fa fa-times"> </i> Del </button>
                </form>
              </td>

              <!-- </div> -->
            </td>

          </tr>


          <div class="modal fade" id="modal-sale-detail{{ $sale->id }}">
            <div class="modal-dialog modal-lg ">
              <div class="modal-content">
                <div class="card card-primary card-outline">
                  <div class="card-body box-profile bg-light">
                    <div class="text-center">
                      <img style="width: 128px; height: 128px" class="profile-sale-img img-fluid img-circle"
                      src="/storage/sales/{{$sale->photo}}"
                      alt="sale profile picture" onerror="this.onerror=null;this.src='storage/sales/default_profile.png';" />
                    </div>

                    <h3 class="profile-salename text-center">{{$sale->name}}</h3>
                    <p class="text-muted text-center">~ {{$sale->privilege}} ~</p>

                    <p class="text-muted text-center">{{$sale->job_title}}</p>
                    <div class="row">
                      <ul class="list-group list-group-unbordered col-md-6 p-md-2">
                       <li class="list-group-item p-2">
                        <b>Full Name</b> <a class="float-right">{{$sale->full_name}}</a>
                      </li>
                      <li class="list-group-item p-2">
                        <b>E mail</b> <a class="float-right">{{$sale->email}}</a>
                      </li>
                      <li class="list-group-item p-2 ">
                        <b>Employee Type</b> <a class="float-right">{{$sale->sale_type}}</a>
                      </li>
                      <li class="list-group-item p-2 ">
                        <b>Join Date</b> <a class="float-right">{{$sale->join_date}}</a>
                      </li>
                    </ul>
                    <ul class="list-group list-group-unbordered col-md-6 p-md-2">
                     <li class="list-group-item p-2">
                      <b>Date of birth</b> <a class="float-right">{{$sale->date_of_birth}}</a>
                    </li>
                    <li class="list-group-item p-2 ">
                      <b>Address</b> <a class="float-right">{{$sale->address}}</a>
                    </li>
                    <li class="list-group-item p-2 ">
                      <b>Phone</b> <a class="float-right">{{$sale->phone}}</a>
                    </li>
                    {{--  <li class="list-group-item p-2 ">
                      <b>note</b> <a class="float-right">{{$sale->date_of_birth}}</a>
                    </li> --}}
                  </ul>

                  <ul class="list-group list-group-unbordered col-md-12 pr-md-2 pl-md-2">
                    <li class="list-group-item p-2 ">
                      <b>note</b> <a class="float-right">{{$sale->description}}</a>
                    </li>
                  </ul>
                </div>

                <div class="modal-footer justify-content-between float-right">
                  <button type="button" class="btn btn-primary float-right " data-dismiss="modal">Close</button>

                {{--  </div> --}}
              </div>
            </div>
            <!-- /.card-body -->
          </div>
          <!-- /.card-body -->
        </div>



        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->

    </div>

    @endforeach

  </tbody>
</table>
</div>
</div>
</div>

</section>

@endsection
