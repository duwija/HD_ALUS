@extends('layout.main')
@section('title','Merchant List')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Merchant List  </h3>
      <a href="{{url ('merchant/create')}}" class=" float-right btn  bg-gradient-primary btn-sm">Add New Merchant</a>
    </div>

    <!-- /.card-header -->
    <div class="card-body table-responsive">
      <table id="table-merchant-list" class="table table-bordered table-striped ">

        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>
            <th scope="col">Contact name</th>
            <th scope="col">Phone</th>
            <th scope="col">Address</th>
            
          </tr>
        </thead>

      </table>
    </div>
  </div>

</section>

@endsection
@section('footer-scripts')
@include('script.merchant_list')
@endsection 


