@extends('layout.main')
@section('title','Customer Notification and jobs process')
@section('content')
<section class="content-header">







  <div class="card card-primary card-outline">
    <div class="card-header">
      <h5 class="card-title m-0"><strong>Notification</strong></h5>
    </div>
    <div class="card-body">




      <div class="row d-flex">
        <div class="col-lg-3 col-6 d-flex">
          <div class="small-box card card-info card-outline flex-fill d-flex flex-column">
            <div class="card-header text-center">
              <h5 class="card-title m-0">
                <strong>Number of Tasks Queued in Jobs</strong>
              </h5>
            </div>

            <div class="card-body text-center">
              <h3>{{ $queue }} Queue</h3>
              <i class="fas fa-envelope fa-3x text-info my-2"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6 d-flex">
          <div class="small-box card card-warning card-outline flex-fill d-flex flex-column">
            <div class="card-header text-center">
              <h5 class="card-title m-0">
                <strong>Send Notifications to Consumers Who Have Invoices</strong>
              </h5>
            </div>

            <div class="card-body text-center">
              <h3><div name="customercountunpaid" id="customercountunpaid"> {{ $custactiveinv }} Customer</div></h3>
              <p>Ready to sent Notification</p>
              <i class="fas fa-bullhorn fa-3x text-warning my-2"></i>
            </div>

            <form action="/jobs/notifinv" method="POST" class="mt-auto p-3 d-inline notifblocked-send1">
              @method('post')
              @csrf

              <div class="form-group">
                <label for="id_merchant_unpaid" class="fw-bold">Merchant</label>
                <select name="id_merchant_unpaid" id="id_merchant_unpaid" class="form-control select2" onchange="getSelectedunpaidnotif()">
                  <option value="">All</option> <!-- Tambahkan opsi All -->
                  @foreach ($merchant as $id => $name)
                  <option value="{{ $id }}">{{ $name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="text-center mt-3">
                <button type="submit" class="btn btn-warning btn-sm px-4">
                  SEND <i class="fas fa-arrow-circle-right"></i>
                </button>
              </div>
            </form>
          </div>
        </div>

<!-- <div class="col-lg-3 col-6">

<div class="small-box bg-success">
<div class="inner">
<h3>53<sup style="font-size: 20px">%</sup></h3>
<p>Bounce Rate</p>
</div>
<div class="icon">
<i class="ion ion-stats-bars"></i>
</div>
<a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
</div>
</div> -->
<div class="col-lg-3 col-6 d-flex">
  <div class="small-box card card-danger card-outline flex-fill d-flex flex-column">
    <div class="card-header text-center">
      <h5 class="card-title m-0">
        <strong>Send Notifications to Consumers with Blocked Status</strong>
      </h5>
    </div>
    
    <div class="card-body text-center">
      <h3><div name="customercountblock" id="customercountblock">  {{ $custblocked }} Customer</div></h3>
      <i class="fas fa-bullhorn fa-3x text-danger my-2"></i>
    </div>

    <form action="/jobs/customerblockednotifjob" method="POST" class="mt-auto p-3 d-inline notifblocked-send1">
      @method('get')
      @csrf
      
      <div class="form-group">
        <label for="id_merchant_block" class="fw-bold">Merchant</label>
        <select name="id_merchant_block" id="id_merchant_block" class="form-control select2" onchange="getSelectedblocknotif()">
          <option value="">All</option> <!-- Tambahkan opsi All -->
          @foreach ($merchant as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>

      <div class="text-center mt-3">
        <button type="submit" class="btn btn-danger btn-sm px-4">
          SEND <i class="fas fa-arrow-circle-right"></i>
        </button>
      </div>
    </form>
  </div>
</div>


<div class="col-lg-3 col-6">
  <div class="small-box card card-success card-outline flex-fill d-flex flex-column">
    <div class="card-header text-center">
      <h5 class="card-title m-0">
        <strong>Create Monthly Invoice</strong></h5>
      </div>

      <div class="card-body text-center">
        <h3><div  id="customerinvcount" nama="customerinvcount">{{ $customerinv }} Customer</div></h3>
        <p><h4> Customers</h4></p>

        <div  id="month" nama="month">{{ $customerinv }} </div>

        <p>Ready to Create Invoice</p>
        <i class="fas fa-file-invoice fa-3x text-success my-2"></i>
        <p>
          <a href="/invoice/createinv" class="btn btn-success">
            <i class="fas fa-file-invoice"></i> Show Invoice Page
          </a></p>
        </div>

        <form action="/jobs/customerinvjob" method="POST" class="mt-auto p-3 d-inline createmonthlyinv-send1">
          @method('post')
          @csrf

          <div class="form-group">
            <label for="date" class="fw-bold">Invoice Date</label>
            <input class="form-control" id="inv_date" onchange="getSelectedcustomermerchant()" name="inv_date" type="date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">

          </div>

          <div class="form-group">
            <label for="id_merchant" class="fw-bold">Merchant</label>
            <select name="id_merchant" id="id_merchant" onchange="getSelectedcustomermerchant()" class="form-control select2">
              <option value="">All</option> <!-- Tambahkan opsi All -->
              @foreach ($merchant as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
              @endforeach
            </select>
          </div>

          <div class="text-center mt-3">
            <button type="submit" class="btn btn-success btn-sm px-4">
              CREATE <i class="fas fa-arrow-circle-right"></i>
            </button>
          </div>
        </form>
      </div>
    </div>


    <div class="col-lg-3 col-6">

      <div class="small-box card card-danger card-outline flex-fill d-flex flex-column">
       <div class="card-header">
        <h5 class="card-title m-0"><strong>Customer Isolir</strong></h5>
      </div>
      <div class="card-body text-center">
        <h3> <div id="customercount" name="customercount"></div>
        </h3>

        <div id="result" name="result"></div>
        <i class="ion ion-locked fa-3x text-danger my-2"></i>
      </div>
      <form  action="/jobs/customerisolirjob" method="POST" class="mt-auto p-3 d-inline blocked-customer1" >
       @method('post')
       @csrf
       <div class="form-group">
         <label for="id_merchant" class="fw-bold">Select Isolir Date</label>

         <select name="isolir_date" id="isolir_date" onchange="getSelectedisolirdate()" class="form-control select2">
          @php
          $numbers = [];
          for ($i = 1; $i <= 29; $i++) {
            $numbers[] = sprintf('%02d', $i);

          }
          @endphp
          @foreach ($numbers as $numbers)
          @if ($numbers == date('d'))
          <option selected value="{{$numbers}}">{{ $numbers }}</option>


          @else


          <option value="{{ $numbers}}">{{ $numbers }}</option>

          @endif

          @endforeach
          @php
          echo '<script type="text/javascript">';
          echo   'getSelectedisolirdate();';
        echo '</script>';
        @endphp
      </select>
    </div>
<!-- 
<div class="icon">
  <i class="ion ion-locked"></i>
</div>
-->

<div class="text-center mt-3">
  <button  type="submit"  class="btn  bg-danger btn-sm bg-danger  btn-sm px-4"> BLOCK | ISOLIR <i class="fas fa-arrow-circle-right"></i> </button>
</div>
</form>
</div>


</div>





</div>
</div>
</div>




</section>

@endsection

<script type="text/javascript">

  function getSelectedisolirdate()
  {
    var isolirdate = $('#isolir_date').val();
    $.ajax({
      url: '/jobs/isolirdata',
      type: 'GET',
      data: { isolirdate: isolirdate },
      success: function(response) {
        $('#result').html(response.message);
        $('#customercount').html(response.customercount);
      }
    });
  }
</script>
<script type="text/javascript">

  function getSelectedcustomermerchant()
  {

    var id_merchant = $('#id_merchant').val();
    var inv_date = $('#inv_date').val();

    $.ajax({
      url: '/jobs/getSelectedcustomermerchant',
  type: 'POST', // Ubah menjadi POST
  data: { 
    id_merchant: $('#id_merchant').val(), // Bisa kosong untuk semua data
    inv_date: $('#inv_date').val() // Kirim tanggal dalam format YYYY-MM-DD
  },
  dataType: 'json',
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Laravel CSRF token
  },
  success: function(response) {

    $('#customerinvcount').text(response.customercount);
    $('#month').text(response.month);

  },
  error: function(xhr, status, error) {
    console.error('AJAX Error:', error);
    alert('Terjadi kesalahan saat mengambil data.');
  }
});

  }
</script>

<script type="text/javascript">

  function getSelectedblocknotif()
  {
    var id_merchant_block = $('#id_merchant_block').val();
    $.ajax({
      url: '/jobs/getSelectedblocknotif',
      type: 'GET',
      data: { id_merchant_block: id_merchant_block },
      success: function(response) {

        $('#customercountblock').html(response.customercount);
      }
    });
  }
</script>
<script type="text/javascript">

  function getSelectedunpaidnotif()
  {
    var id_merchant_unpaid = $('#id_merchant_unpaid').val();
    $.ajax({
      url: '/jobs/getSelectedunpaidnotif',
      type: 'GET',
      data: { id_merchant_unpaid: id_merchant_unpaid },
      success: function(response) {
       // alert(response.customercount);
        $('#customercountunpaid').html(response.customercount);
      }
    });
  }
</script>
<script>
   // Panggil fungsi saat halaman dimuat
 document.addEventListener("DOMContentLoaded", function() {
  getSelectedcustomermerchant();
  getSelectedblocknotif();
  getSelectedunpaidnotif();
});
</script>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    // Pilih semua form yang membutuhkan konfirmasi
    document.querySelectorAll('.notifblocked-send1, .createmonthlyinv-send1, .blocked-customer1').forEach(function (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault(); // Mencegah submit langsung

        Swal.fire({
          title: "Are you sure?",
          text: "This action will proceed with the job!",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Yes, proceed!",
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit(); // Kirim form setelah konfirmasi
          }
        });
      });
    });
  });
</script>
