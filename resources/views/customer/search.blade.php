
@extends('layout.main')
@section('title','Customer List')
@section('content')
@inject('suminvoice', 'App\Suminvoice')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Customers List  </h3>

      <a href="{{url ('customer/create')}}" class=" float-right btn  bg-gradient-primary btn-sm">Add New Customer</a>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
     <form role="form" method="post" action="/customer/update/status">
       @method('patch')
       @csrf
       <table id="example2" class="table table-bordered table-striped">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Customer Id</th>
            <th scope="col">Name</th>
            <th scope="col">Address</th>
            <th scope="col">Plan</th>
            <th scope="col">Status</th>
            <!-- <th scope="col">Select</th> -->
            <th scope="col">Invoice</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
         @foreach( $customer as $customer)
         <tr>
          <th scope="row">{{ $loop->iteration }} </th>

          <td><a class="btn btn-primary btn-sm" href="/customer/{{ $customer->id }}" >{{ $customer->customer_id }}</a></td>
          <td>{{ $customer->name }} </td>

          <td> <a style="font-size: 13px"> {{ $customer->address }}</a></td>

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
     <!--      @if (($customer->status_name->name == 'Active')Or ($customer->status_name->name == 'Block'))
          

           <td class="text-center"><input   type="checkbox" id="id_cust" name="id[]" value="{{ $customer->id }}"></td>
          
          @else
          <td></td>
          @endif -->
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


    </tbody>


  </select>

</table>
<!--       <div class="input-group mb-3 ">
        <span class=" p-2"><strong>ACTION</strong> </span><br>
    <select class="" name="status" id="status" class="input-group  ">
  <option value="0">Block</option>
  <option value="1">Active</option>
</select>
 <button type="submit" class="btn btn-primary input-group-append">Submit</button>
</div> -->

</form>
</div>
</div>

</section>

@endsection
