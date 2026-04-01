@extends('layout.main')
@section('title','invoice List')
@section('content')
<section class="content-header">




  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">invoice List  </h3>

      <br>
  </div>
  <div class="row pt-2 pl-4">


     <div class="form-group col-md-2">
        <label for="site location">  invoice Date Start </label>
        <div class="input-group mb-3">
            <div class="input-group p-1  date" id="reservationdate" data-target-input="nearest">
                <input type="text" name="dateStart" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-1')}}" />
                <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group col-md-2">
        <label for="site location">  invoice Date Start </label>
        <div class="input-group mb-3">
           <div class="input-group p-1 date" id="reservationdate" data-target-input="nearest">
            <input type="text" name="dateEnd" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-d')}}" />
            <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
            </div>
        </div>
    </div>
</div>
<div class="form-group col-md-2">
    <label for="site location">  Merchant </label>
    <div class="input-group mb-3">
        <select name="id_merchant" id="id_merchant" class="form-control m-1 p-1">
            <option value="">ALL</option> 
            @foreach ($merchant as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="form-group col-md-1">
    <label for="site location">  Invoice Status </label>
    <select name="paymentStatus" id="paymentStatus" class="form-control m-1 p-1">
        <option value="">ALL</option>
        <option value="0">UNPAID</option>
        <option value="1">PAID</option>
        <option value="2">CANCEL</option>

    </select>



</div>
<div class="form-group col-md-1">
    <label for="site location">  Invoice Type </label>
    <select name="invoicetype" id="invoicetype" class="form-control m-1 p-1">
        <option value="0">ALL</option>
        <option value="1">Monthly Fee</option>


    </select>



</div>


<div class="form-group col-md-2">
  <label for="site location">  Parameter </label>
  
  <div class="input-group p-1 " id="parameter" data-target-input="nearest">
    <input placeholder="Number INV | CID | Name" type="text" name="parameter" id="parameter" class="form-control" />

</div>
</div>

<div class="form-group col-md-2">
    <label for="site location">   </label>

    <div class="input-group p-1 col-md-3">

        <button type="button" class="btn mt-2   bg-gradient-primary  btn-primary"  id="invoice_filter">Filter
        </button>
    </div> 
</div>
</div>
<div class="row pt-2 pl-4" id="updatedByLabel">
    <div class="form-group col-md-2">
        <label for="site location">  Payment Date Start </label>
        <div class="input-group mb-3">
           <div class="input-group p-1   date" id="reservationdate" data-target-input="nearest">
            <input type="text" name="paymentDateStart" id="date" class="form-control datetimepicker-input" data-target="#reservationdate"    />
            <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
            </div>
        </div>
    </div>
</div>
<div class="form-group col-md-2">
    <label for="site location">  Payment Date End </label>
    <div class="input-group mb-3">
        <div class="input-group p-1  date" id="reservationdate" data-target-input="nearest">
            <input type="text" name="paymentDateEnd" id="date" class="form-control datetimepicker-input" data-target="#reservationdate"  />
            <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
            </div>
        </div>
    </div>
</div>
<div class="form-group col-md-2">
    <label for="site location">  Recieved By </label>
    <div class="input-group mb-3">
        <select name="updatedBy" id="updatedBy" class="form-control ml-1">
            <option value="">ALL</option>
            @foreach($groupedTransactions as $transaction)
            @if(is_numeric($transaction->updated_by))
            <option value="{{ $transaction->updated_by }}">{{ $transaction->user->name }}</option>
            @else
            <option value="{{ $transaction->updated_by }}">{{ $transaction->updated_by }}</option>
            @endif
            @endforeach
        </select>
    </div>
</div>
</div>
<hr>
<!-- =============================================== -->



<!-- /.card-header -->
<div class="card-body">



    <!-- <form role="form" method="post" action="/invoice/reload/status"> -->
 <!--   @method('patch')
     @csrf -->
     <div class="table-responsive">
        <table id="table-invoice-list" name=table-invoice-list class="table table-bordered table-striped text-xs">

            <thead >
             <tr>

                <th colspan="14"class="text-right border-0" >
                    <div class="row float-right">

                        <div class="bg-secondary p-2 rounded-sm m-1  "><h5>Rp. <span name='cancel_payment' id='cancel_payment'>0 </span></h5>
                            <p>Total Invoice Cancel</p>
                        </div>
                        <div class="bg-danger p-2 rounded-sm m-1  "><h5>Rp. <span name='unpaid_payment' id='unpaid_payment'>0 </span></h5>
                            <p>Total Invoice Upaid</p>
                        </div>


                        <div class="bg-green p-2 rounded-sm m-1 " ><h5>Rp. <span name='total_paid' id='total_paid'>0 </span></h5>
                            <div class="text-xs"id="fee_counter"> </div>
                            <p>Total Invoice Paid</p>
                        </div>

                        <div class="bg-navy p-2 rounded-sm m-1" ><h5>Rp. <span name='total' id='total'>0 </span></h5>
                            <p>Total Invoice Amount</p>
                        </div>

                    </div>
                </th>




            </tr>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Invoice Date</th>
                <th scope="col">Invoice NO</th>
                <th scope="col">CID</th>
                <th scope="col">Name</th>
                <th scope="col">Merchant</th>
                <th scope="col">Address</th>
                <th scope="col">Period</th>
                <th scope="col">Due Date</th>
                <th scope="col">Tax</th>
                <th scope="col">Total Amount</th>
                <th scope="col">Status</th>
                <th scope="col">Recieve By</th>
                <th scope="col">Transaction Date</th>
            </tr>
        </thead>

    </table>
</div>

<!-- </form> -->
</div>
</div>

</section>


@endsection
@section('footer-scripts')
@include('script.invoice_list')
@endsection 



