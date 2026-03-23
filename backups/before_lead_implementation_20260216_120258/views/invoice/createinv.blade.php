
@extends('layout.main')
@section('title','Create Mounthly Invoice')
@section('content')
@inject('suminvoice', 'App\Suminvoice')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Create Monthly Invoice </h3>

<br/>
    <!-- /.card-header -->
    <div class="card-body">
  {{--      <form role="form" method="post" action="/customer/update/status">
         @method('patch')
      @csrf --}}

      <table id="example112" class="table table-bordered table-striped">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Customer Id</th>
            <th scope="col">Name</th>
             <th scope="col">Address</th>
            <th scope="col">Plan</th>
            <th scope="col">Create Invoice</th>
         
          </tr>
        </thead>
        <tbody>
          @php
          $month = now()->format('mY');
          $no=0;
          $i = 0;
          @endphp
         @foreach( $customer as $customer)

         @php

         $check_invoice = \App\Invoice::where('id_customer', $customer->id)->Where('periode', $month)->Where('monthly_fee','1')->first();
	
         @endphp
         @if (!$check_invoice)
            
                @php $no =$no +1;
                @endphp
<tr>
          <th scope="row">{{ $no }} </th>

          <td><a class="btn btn-primary btn-sm" href="/customer/{{ $customer->id }}" >{{ $customer->customer_id }}</a></td>
          <td>{{ $customer->name }} </td>

            <td> <a style="font-size: 13px"> {{ $customer->address }}</a></td>

          @if( $customer->id_plan == null)


          <td> none</td>


          @else
          <td>{{ $customer->plan_name->name }} ( {{ number_format($customer->plan_name->price)}})</td>

          @endif

<td>
   <div class="custom-control custom-switch">
  <input class="custom-control-input" data-toggle="toggle" data-onstyle="primary" id="switch-primary-{{ $customer->id }}" value="{{ $customer->id }}" name="toggle" type="checkbox" {{ $customer->status === 1 ? 'checked' : '' }}>

  <label for="switch-primary-{{ $customer->id }}" class="custom-control-label" ></label>
  </div>
 


            </td>
           {{--  <td>
            <form  action="/invoice/mounthlyfee" method="POST" class="d-inline " >
                @method('post')
                @csrf
                <input type="text" name="id" value="{{ $customer->id }}">
                <button title="test" type="submit"  class="btn btn-danger btn-sm"> <i class="fa fa-times"> </i> </button>
              </form>
            </td> --}}
         
        </tr>
                @else


                @endif
               @if ($no == 150)
                   @break
               @endif
        @endforeach
  

      </tbody>
          
 
</select>

    </table>
 <br/>
      <a href="{{url ('invoice/createinv')}}" class=" float-right btn  bg-gradient-primary btn-sm">Next Page</a>
    </div>
{{--  </form> --}}
  </div>
</div>

</section>

@endsection

 
