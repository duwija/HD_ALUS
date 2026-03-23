
@extends('layout.main')
@section('title','Transaction List')
@section('content')
<section class="content-header">




  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title"><strong>Transaction List </strong>  </h3>
    </div>

    <!-- Content Wrapper. Contains page content -->

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          {{-- sebelah kiri: infobox --}}
          <div class="col-lg-6 p-3">
            <div class="row">
              <div class="col-md-6 col-sm-6 col-12">
                <div class="small-box bg-navy">
                  <div class="inner">
                    <h4>Rp. {{ number_format($totalReceivable,0,',','.') }}</h4>
                    <p>Total Receivable</p>
                  </div>
                  <div class="icon"><i class="fas fa-wallet"></i></div>
                </div>
              </div>
              <div class="col-md-6 col-sm-6 col-12">
                <div class="small-box bg-green">
                  <div class="inner">
                    <h4>Rp. {{ number_format($totalTransactionThisMonth,0,',','.') }}</h4>
                    <p>This Month</p>
                  </div>
                  <div class="icon"><i class="fas fa-university"></i></div>
                </div>
              </div>
              <div class="col-md-6 col-sm-6 col-12">
                <div class="small-box bg-lightblue">
                  <div class="inner">
                    <h4>Rp. {{ number_format($totalTransactionThisWeek,0,',','.') }}</h4>
                    <p>This Week</p>
                  </div>
                  <div class="icon"><i class="fas fa-chart-line"></i></div>
                </div>
              </div>
              <div class="col-md-6 col-sm-6 col-12">
                <div class="small-box bg-pink">
                  <div class="inner">
                    <h4>Rp. {{ number_format($totalPaymentToday,0,',','.') }}</h4>
                    <p>Today</p>
                  </div>
                  <div class="icon"><i class="fas fa-chart-bar"></i></div>
                </div>
              </div>
            </div>
          </div>

          {{-- sebelah kanan: chart --}}
          <div class="col-lg-6 p-3">
            <div class="card">
              <div class="card-body">
                <canvas id="dailyTransactionChart" style="width:100%; height:300px;"></canvas>
              </div>
            </div>
          </div>
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->

    <!-- /.content-wrapper -->

    <!-- ./wrapper -->






    <!-- group by receive -->



    <!-- <div class="container "> -->

      <div class="col-lg-12 pb-3 ">
       <!--  <div class="card-header ">
          <h3 class="card-title"><strong>Transaction Filter </strong></h3>
        </div> -->
        

        <div class="d-flex flex-wrap pt-2 pl-2">
          <div class="input-group col-md-2">
            <label>Transaction Start:</label>
            <div class="input-group date" id="reservationdate" data-target-input="nearest">
              <input type="text" name="dateStart" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-d')}}" />
              <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>

          <div class="input-group col-md-2">
            <label>Transaction End:</label>
            <div class="input-group date" id="reservationdate" data-target-input="nearest">
              <input type="text" name="dateEnd" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-d')}}" />
              <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>

          <div class="input-group col-md-2">
            <label>Merchant:</label>
            <div class="input-group">
              <select name="id_merchant" id="id_merchant" class="form-control select2">
                <option value="">All</option>
                @foreach ($merchant as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
              </select>
            </div>
          </div>



          <div class="input-group col-md-2">
            <label>Parameter:</label>
            <div class="input-group">
              <input placeholder="Number INV | CID | Name" type="text" name="parameter" id="parameter" class="form-control" />
            </div>
          </div>

          <div class="input-group col-md-1">
            <label>Receive By:</label>
            <div class="input-group">
              <select name="updatedBy" id="updatedBy" class="form-control select2">
                <option value="">ALL</option>
                @foreach($user as $transaction)
                @if(is_numeric($transaction->updated_by))
                <option value="{{$transaction->updated_by}}">{{ $transaction->user->name }}</option>
                @else
                <option value="{{$transaction->updated_by}}">{{ $transaction->updated_by }}</option>
                @endif
                @endforeach
              </select>
            </div>
          </div>

          <div class="input-group col-md-2">
            <label>Kas Bank:</label>
            <div class="input-group">
              <select name="kasbank" id="kasbank" class="form-control select2">
                <option value="">All</option>
                @foreach ($kasbank as $akun)
                <option value="{{ $akun->akun_code }}">{{ $akun->name }}</option>
                @endforeach
              </select>
            </div>
          </div>


          <div class="input-group col-md-1">
            <label>&nbsp;</label>
            <div class="input-group">
              <button type="button" class="float-right btn bg-gradient-primary btn-primary" id="transaction_filter">Filter</button>
            </div>
          </div>
        </div>
      </div>



      <div class="container col-12 pl-4 row">


        <div class="col-lg-4 col-md-4 p-2 ">
          <div class="card card-primary card-outline  table-responsive"  style="max-height: 400px; overflow-y: auto;">
            <div class="card-header ">
              <h3 class="card-title"><strong>Payments are grouped by recipient </strong> </h3>
            </div>

            <table class="table table-bordered table-striped rounded-sm  ">
              <thead>
          <!--   <tr class="card-header bg-lightblue">
              <th colspan="5" class="text-center">Search results based on the selected date range</th>
            </tr> -->

            <tr>
              <th>No</th>
              <th>Received By</th>
              <th>Total Amount | *exclude fee</th>
              <th>Total Fee</th>
              <th>Total Receive Payment</th>
            </tr>
          </thead>
          <tbody name='groupedTransactionsUser' id='groupedTransactionsUser'>

          </tbody>
          <tfoot>
            <tr>
              <th colspan="2">Total</th>
              <th name ="totalAmount" id="totalAmount" class="text-right">0</th>
              <th name="totalFee" id="totalFee" class="text-right">0</th>
              <th name="totalPayment" id="totalPayment" class="text-right">0</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="col-lg-4 col-md-4 p-2">
      <div class="card card-warning card-outline  table-responsive"  style="max-height: 400px; overflow-y: auto;">
        <div class="card-header ">
          <h3 class="card-title"><strong>Payments are grouped by Kasbank </strong> </h3>
        </div>

        <table class="table table-bordered table-striped rounded-sm  ">
          <thead>


            <tr>
              <th>No</th>
              <th>Kas Bank</th>
              <!-- <th>Total Amount</th> -->
              <th>Total Receive Payment</th>
            </tr>
          </thead>
          <tbody name='groupedTransactionsKasbank' id='groupedTransactionsKasbank'>

          </tbody>
          <tfoot>
            <tr>
              <th colspan="2">Total</th>
              <!-- <th name ="totalAmount" id="totalAmountMerchant" class="text-right">0</th> -->
              <th name="totalPaymentKasbank" id="totalPaymentKasbank" class="text-right">0</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="col-lg-4 col-md-4 p-2">
      <div class="card card-success card-outline  table-responsive" style="max-height: 400px; overflow-y: auto;">
        <div class="card-header ">
          <h3 class="card-title"><strong>Payments are grouped by merchant </strong> </h3>
        </div>

        <table id="groupedTransactionsMerchanTable" name="groupedTransactionsMerchanTable" class="table table-bordered table-striped rounded-sm  " >
          <thead>
          <!--   <tr class="card-header bg-lightblue">
              <th colspan="5" class="text-center">Search results based on the selected date range</th>
            </tr> -->

            <tr>
              <th>No</th>
              <th>Merchant</th>
              <!-- <th>Total Amount</th> -->
              <th>Total Receive Payment</th>
            </tr>
          </thead>
          <tbody name='groupedTransactionsMerchant' id='groupedTransactionsMerchant'>

          </tbody>
          <tfoot>
            <tr>
              <th colspan="2">Total</th>
              <!-- <th name ="totalAmount" id="totalAmountMerchant" class="text-right">0</th> -->
              <th name="totalPaymentMerchant" id="totalPaymentMerchant" class="text-right">0</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- ./col -->




    <!-- ./col -->

    <!-- ./col -->

    <!-- ./col -->

    <!-- /.row -->
  </div><!-- /.container-fluid -->



  <!-- end group by receive -->















  <!-- /.card-header -->
  <div class="card-body">
   <div class="table-responsive">
    <table id="table-transaction-list" class="table table-bordered table-striped text-xs ">
    <!-- @php
    $total=0;
    $no=0;
    @endphp -->
    <thead >
     <tr>

      <th colspan="15"class="text-right border-0" >
        <div class="row float-right">
          <div class="bg-green p-2 rounded-sm m-1  "><h5>Rp. <span name='total_paid' id='total_paid'>0 </span></h5>
            <p>Total Amount</p>
          </div>


          <div class="bg-primary p-2 rounded-sm m-1 " ><h5>Rp. <span name='fee_counter' id='fee_counter'>0 </span></h5>
            <p>Total Payment Point Fee</p>
          </div>

          <div class="bg-navy p-2 rounded-sm m-1" ><h5>Rp. <span name='total_payment' id='total_payment'>0 </span></h5>
            <p>Total Payment</p>
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
     <th scope="col">Note</th>
     <th scope="col">Periode</th>
     <th scope="col">Total Amount</th>
     <th scope="col">Payment Point Fee</th>
     <th scope="col">Status</th>
     <th scope="col">Kasbank</th>
     <th scope="col">Recieve By</th>
     <th scope="col">Transaction Date</th>

     {{--  <th scope="col">Action</th> --}}
   </tr>
 </thead>

</table>
</div>  

</div>
</div>

</section>

@endsection
@section('footer-scripts')
@include('script.transaction_list')
@endsection 

