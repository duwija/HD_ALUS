@extends('layout.main')
@section('title','Distribution Point List')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Distibution Point List  </h3>
      <a href="{{url ('distpoint/create')}}" class=" float-right btn  bg-gradient-primary btn-sm m-2">New Distribution Point</a>
      <a href="{{url ('distpointgroup')}}" class=" float-right btn  bg-gradient-primary btn-sm m-2">Show Dist Group List</a>
    </div>


    <!-- /.card-header -->
    <div class="card-body ">
      <div>

        <div class="row mb-2 p-2">
          <div class="col-md-2 mb-2">
            <select id="filter-site" class="form-control">
              <option value="">All Sites</option>
              @foreach(\App\Site::orderBy('name')->get() as $site)
              <option value="{{ $site->name }}">{{ $site->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2 mb-2">
            <select id="filter-group" class="form-control">
              <option value="">All Groups</option>
              @foreach(\App\Distpointgroup::orderBy('name')->get() as $group)
              <option value="{{ $group->name }}">{{ $group->name }}</option>
              @endforeach
            </select>
          </div>
          <div class=" col-md-2 mb-2">
            <input type="text" id="filter-name" class="form-control" placeholder="Search Distribution Point Name">
          </div>
          <div class=" col-md-1 mb-2">
            <button class="btn btn-primary btn-block" id="apply-filters">
             Filter
           </button>
         </div>
       </div>

     </div>
     <div class="table-responsive ">
       <table id="table-distpoint-list" class="table table-bordered table-striped ">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>

            <th scope="col">Port Capacity</th>
            <th scope="col">Port Used</th>
            <th scope="col">Optic Power</th>
            <th scope="col">Site</th>
            <th scope="col">Parrent</th>

            <th scope="col">Group</th>
            <th scope="col">Description</th>

          </tr>
        </thead>

      </table>
    </div>
  </div>
</div>

</section>

@endsection
@section('footer-scripts')
@include('script.distpoint_list')
@endsection 


