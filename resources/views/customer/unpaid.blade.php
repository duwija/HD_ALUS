
@extends('layout.main')
@section('title','Customer List Unpaid Bill')
@section('content')
@inject('suminvoice', 'App\Suminvoice')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Customers UnPaid List  </h3>
    </div>

    {{-- Info banner --}}
    <div class="px-3 pt-3">
      <div class="alert alert-warning alert-dismissible mb-0" style="border-left:4px solid #e65100">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h6 class="font-weight-bold mb-1">
          <i class="fas fa-exclamation-triangle mr-1"></i> Daftar Customer Dengan Tunggakan
        </h6>
        <p class="mb-0 small">
          Halaman ini menampilkan customer yang masih memiliki <strong>invoice belum dibayar</strong> (payment status = belum lunas).
          Gunakan filter <em>Total Invoice</em> untuk menyaring berdasarkan jumlah tunggakan, atau filter <em>Terminated Cust</em>
          untuk melihat customer yang sudah dihapus namun masih mempunyai tagihan.
        </p>
      </div>
    </div>



    <div class="row pt-2 pl-4">


     <div class="form-group col-md-2">
      <label for="Filter by">  Filter By </label>
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
    <div class="form-group col-md-1">
      <label for="site location">  Parameter </label>
      <div class="input-group mb-3">
        <input class="form-control" type="text" id="parameter" name="parameter" placeholder="Leave blank for all">
      </div>
    </div>
    <div class="form-group col-md-2">
      <label for="site location">  Merchant </label>
      <div class="input-group mb-3">
        <select name="id_merchant" id="id_merchant" class="form-control">
         <option value="">All</option> 
         @foreach ($merchant as $id => $name)
         <option value="{{ $id }}">{{ $name }}</option>
         @endforeach
       </select>
     </div>
   </div>
   <div class="form-group col-md-1">
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


 <div class="form-group col-md-1">
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
<div class="form-group col-md-1">
  <label for="countinv">Total Invoice</label>
  <div class="input-group mb-3">
    <select name="countinv" id="countinv" class="form-control">
      <option value="">All</option>
      <?php for ($i = 1; $i <= 15; $i++): ?>
        <option value="<?= $i ?>"><?= $i ?></option>
      <?php endfor; ?>
    </select>
  </div>
</div>
<div class="form-group col-md-1">
  <label for="site location">  Terminated Cust ? </label>
  <div class="input-group mb-3">
    <select name="deleted_at" id="deleted_at" class="form-control">
      <option value="no">NO</option>
      <option value="yes">YES</option>

    </select>
  </div>
</div>
<div class="form-group col-md-2">
  <label for="site location">   </label>

  <div class="input-group p-1 col-md-3">
   <button type="button" id="customer_unpaid_filter" name="customer_unpaid_filter" class="btn btn-warning">Filter</button>
 </div> 
</div>
</div>
{{-- </form>
--}}
<!-- /.card-header -->
<div class="card-body">
  <div class="table-responsive">
   <form role="form" method="post" action="/customer/update/status_2">
     @method('patch')
     @csrf
     <table id="table-unpaid-customer" class="table table-bordered table-striped">

      <thead >
        <tr>
          <th scope="col">#</th>
          <th scope="col">Customer Id</th>
          <th scope="col">Name</th>

          <th scope="col">Address</th>
          <th scope="col">Merchant</th>
          <th scope="col">Plan</th>
          <th scope="col">Tax</th>
          <th scope="col">Billing Start</th>
          <th scope="col">Isolir Date</th>
          <th scope="col">Status</th>

          <th scope="col">Invoice</th>
          <th scope="col">Total</th>
        </tr>
      </thead>
      {{--         <tbody>
       @foreach( $customer as $customer)
       <tr>
        <th scope="row">{{ $loop->iteration }} </th>

        <td><a class="btn btn-primary btn-sm" href="/customer/{{ $customer->id }}" >{{ $customer->customer_id }}</a></td>
        <td>{{ $customer->name }} </td>

        <td> <a style="font-size: 13px"> {{ $customer->address }}</a></td>
        <td> <a style="font-size: 13px"> {{ $customer->billing_start }}</a></td>

        @if( $customer->id_plan == null)


        <td> none</td>


        @else
        <td>{{ $customer->plan_name->name }} ( {{ number_format($customer->plan_name->price)}})</td>

        @endif



        @if( $customer->id_status == null)


        <td> none</td>


        @else


        @php

        if ($customer->status_name->name == 'Active')
        $badge_sts = "badge-success";
        elseif ($customer->status_name->name == 'Inactive')
        $badge_sts = "badge-secondary";
        elseif ($customer->status_name->name == 'Block')
        $badge_sts = "badge-danger";
        elseif ($customer->status_name->name == 'Company_Properti')
        $badge_sts = "badge-primary";
        else
        $badge_sts = "badge-warning";

        @endphp




        <td class="text-center"><a class="badge text-white {{$badge_sts}}">{{ $customer->status_name->name }}</a></td>

        @endif
        @if (($customer->status_name->name == 'Active')Or ($customer->status_name->name == 'Block'))


        <td class="text-center"><input   type="checkbox" id="id_cust" name="id[]" value="{{ $customer->id }}"></td>

        @else
        <td></td>
        @endif
        <td class="text-center">  @if ($suminvoice->countinv($customer->id) >= 1)

         <a href="/invoice/{{ $customer->id }}" title="Invoice" class="btn btn-warning btn-sm   "> {{$suminvoice->countinv($customer->id)}} </a>
         @else



       @endif</td>
       <td >
        <div class="float-right " >



          <a href="/ticket/{{ $customer->id }}/create" title="ticket" class="btn btn-success btn-sm "> <i class="fas fa-ticket-alt" aria-hidden="true"></i> Create Ticket </a>


        </div>
      </td>

    </tr>
    @endforeach


  </tbody> --}}


</select>

</table>


</form>
</div>
</div>
</div>

</section>

@endsection
@section('footer-scripts')
@include('script.customer-unpaid')
@endsection


