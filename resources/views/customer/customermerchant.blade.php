
@extends('layout.main')
@section('title','Customer List')
@section('content')
@inject('suminvoice', 'App\Suminvoice')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Customers List  </h3>

      <!-- <a href="{{url ('customer/create')}}" class=" float-right btn  bg-gradient-primary btn-sm">Add New Customer</a> -->
    </div>
    {{-- <form role="form" method="post" action="/customer/filter">
      @csrf --}}
      <div class="row pt-2 pl-4">


       <div class="form-group col-md-2">
        <label for="site location">  Filter By </label>
        <div class="input-group mb-3">
          <select name="filter" id="filter" class="form-control">

            <option value="name">Name</option>
            <option value="customer_id">Customer ID</option>
            <option value="address">Address</option>
            <option value="phone">Phone</option>
            <option value="id_card">Id Card</option>
            <option value="billing_start">Billing Start</option>
            <option value="isolir_date">Isolir Date</option>

          </select>
        </div>
      </div>
      <div class="form-group col-md-2">
        <label for="site location">  Parameter </label>
        <div class="input-group mb-3">
          <input class="form-control" type="text" id="parameter" name="parameter" placeholder="Leave blank for all">
        </div>
      </div>

      <div class="form-group col-md-2">
        <label for="site location">  Status </label>
        <div class="input-group mb-3">
          <select name="id_status" id="id_status" class="form-control">
           <option value="">All</option> 
           @foreach ($status as $id => $name)
           <option value="{{ $id }}">{{ $name }}</option>
           @endforeach
         </select>
       </div>
     </div>

     <div class="form-group col-md-2">
      <label for="site location">   </label>

      <div class="input-group p-1 col-md-3">
       <button type="button" id="customer_filter" name="customer_filter" class="btn btn-warning">Filter</button>
     </div> 
   </div>
 </div>
{{-- </form>
--}}
<!-- /.card-header -->
<div class="card-body">





  <form role="form" method="post" action="/customer/update/status">
   @method('patch')
   @csrf
    <div class="table-responsive w-100">
    <table id="table-customer" class="table table-bordered table-striped w-100">

    <thead >
      <tr>
        <th scope="col">#</th>
        <th scope="col">Customer Id</th>
        <th scope="col">Name</th>
        <th scope="col">Address</th>
        <th scope="col">Merchant</th>
      <!--   <th scope="col">Plan</th>
        <th scope="col">Billing Start</th>
        <th scope="col">Isolir Date</th> -->
        <th scope="col">Status</th>
       <!--  <th scope="col">Select</th>
        <th scope="col">Invoice</th>
        <th scope="col">Action</th> -->
      </tr>
    </thead>



  </select>

</table>
</div>


</form>
</div>
</div>

</section>

@endsection
@section('footer-scripts')
@include('script.customermerchant')
@endsection 

