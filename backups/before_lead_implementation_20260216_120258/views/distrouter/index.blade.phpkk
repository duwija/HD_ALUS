@extends('layout.main')
@section('title','Distrouter List')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">distrouter List  </h3>
      <a href="{{url ('distrouter/create')}}" class=" float-right btn  bg-gradient-primary btn-sm">Add New Distrouter</a>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      <table id="example1" class="table table-bordered table-striped">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>
            <th scope="col">IP</th>
            <th scope="col">API Port</th>
            <th scope="col">Description</th>
            <!-- <th scope="col">Action</th> -->
          </tr>
        </thead>
        <tbody>
         @foreach( $distrouter as $distrouter)
         <tr>
          <th scope="row">{{ $loop->iteration }}</th>
          <td class="text-center"> <a href="/distrouter/{{ $distrouter->id }}" class="btn btn-primary btn-sm "> {{ $distrouter->name }} </a></td>
          <td class="text-center"> <a href="{{ 'http://' . $distrouter->ip . ':' . $distrouter->web }}" class="badge badge-info"> {{ $distrouter->ip }} </a>
            <td>{{ $distrouter->port }}</td>
            <td>{{ $distrouter->note }}</td>
<!--           <td >
            <div class="float-right " >
              <a href="https://www.google.com/maps/place/{{ $distrouter->coordinate }}" target="_blank" class="btn btn-primary btn-sm "> <i title="show map" class="fa fa-map"> </i> </a>
              <a href="/distrouter/{{ $distrouter->id }}/edit" class="btn btn-primary btn-sm "> <i class="fa fa-edit"> </i> </a>


              <form  action="/distrouter/{{ $distrouter->id }}" method="POST" class="d-inline distrouter-delete" >
                @method('delete')
                @csrf

                <button type="submit"  class="btn btn-danger btn-sm"> <i class="fa fa-times"> </i> </button>
              </form>

            </div>
          </td> -->

        </tr>
        @endforeach

      </tbody>
    </table>
  </div>
</div>

</section>

@endsection
