@extends('layout.main')
@section('title','Site List')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Site List  </h3>
      <a href="{{url ('site/create')}}" class=" float-right btn  bg-gradient-primary btn-sm">Add New Site</a>
    </div>

    <div class="card-body">
      <table id="example1" class="table table-bordered table-striped">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>
            <th scope="col">Location</th>
            <th scope="col">Description</th>
          </tr>
        </thead>
        <tbody>
         @foreach( $site as $site)
         <tr>
          <th scope="row">{{ $loop->iteration }}</th>
          <td><a href="/site/{{ $site->id }}/show" type="button" class="btn btn-primary btn-sm" >{{ $site->name }} </a></td>
          <td>{{ $site->location }}</td>
          <td>{{ $site->description }}</td>
          

        </tr>
        @endforeach

      </tbody>
    </table>
  </div>
</div>

</section>

@endsection
