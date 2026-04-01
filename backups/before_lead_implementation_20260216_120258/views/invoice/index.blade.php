

@extends('layout.main')

@section('title','Create Monthly Invoice ')
@section('content')
@inject('suminvoice', 'App\Suminvoice')

<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Monthly Invoice  </h3>

      
    </div>

    <div class="row pt-2 pl-4">

      <input type="hidden" id="search_var" name="search_var" value={{ $search_var }}>
      <div class="form-group col-md-2">
        <label for="site location">  Filter By </label>
        <div class="input-group mb-3">
          <select name="filter" id="filter" class="form-control">

            <option value="name">Name</option>
            <option value="customer_id">Customer ID</option>
            <option value="address">Address</option>
            <option value="phone">Phone</option>
            <option value="id_card">Id Card</option>
            <option value="infra">Infrastructure</option>
            <option value="link_type">Link Type</option>
            <option value="snote">Tag</option>

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
      <label for="site location">  Plan </label>
      <div class="input-group mb-3">
        <select name="id_plan" id="id_plan" class="form-control">
         <option value="">All</option> 
         @foreach ($plan as $id => $name)
         <option value="{{ $id }}">{{ $name }}</option>
         @endforeach
       </select>
     </div>
   </div>
   <div class="form-group col-md-2">
    <label for="has_invoice"> Invoice Status </label>
    <div class="input-group mb-3">
      <select name="has_invoice" id="has_invoice" class="form-control">
        <option value="">All</option>
        <option value="yes">Has Invoice</option>
        <option value="no">No Invoice</option>
      </select>
    </div>
  </div>
  <div class="form-group col-md-2">
    <label for="site location">   </label>

    <div class="input-group p-1 col-md-3">
     <button type="button" id="invoice_filter" name="invoice_filter" class="btn btn-warning">Filter</button>
   </div> 
 </div>
</div>

<div class="card-body">
  <div class="table-responsive">
    <table id="table-invoice" class="table table-bordered table-striped">
      <div class="float-right pr-lg-4 badge badge-info">Status Customer yang di buatkan invoice adalah yang berstatus Active dan Blocked</div>
      <thead >
        <tr>
          <th scope="col">#</th>
          <th scope="col">Customer Id</th>
          <th scope="col">Name</th>
          <th scope="col">Address</th>
          <th scope="col">Plan</th>
          <th scope="col">Start Billing</th>
          <th scope="col">Status</th>
          <th scope="col">Invoice</th>
        </tr>
      </thead>



    </select>

  </table>
</div>


</div>

</div>

</section>

@endsection
@section('footer-scripts')
@include('script.invoice')
@endsection