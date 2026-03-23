@extends('layout.main')
@section('title','OLT')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title"><strong>OLT List </strong> </h3>
      <a href="{{url ('olt/create')}}" class=" float-right btn  bg-gradient-primary btn-sm">Add New Olt</a>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      <div class="table-responsive">
        <table id="table-olt-list" class="table table-bordered table-striped">

          <thead >
            <tr>
              <th scope="col">#</th>
              <th scope="col">Name</th>
              <th scope="col">Vendor</th>
              <th scope="col">Type</th>
              <th scope="col">IP Address</th>
              <th scope="col">Telnet Port</th>
              <th scope="col">Ro Community</th>
              <th scope="col">Rw Community</th>
              <th scope="col">Snmp Port</th>
            </tr>
          </thead>

        </table>
      </div>
    </div>
  </div>

</section>


@endsection
@section('footer-scripts')
@include('script.olt_list')
@endsection 

