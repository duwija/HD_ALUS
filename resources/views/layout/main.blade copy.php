<!DOCTYPE html>
<html>
<head>

  <!-- === Dark Mode Preload Fix === -->
  <script>
    (function() {
      if (localStorage.getItem("darkMode") === "enabled") {
        document.documentElement.classList.add("dark-mode-preload");
      }
    })();
  </script>
  <link rel="icon" type="image/png" href="{{ tenant_img('favicon.png', 'favicon.png') }}">
  @inject('ticket', 'App\Ticket')
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  {{--   <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
  <title>| {{ config('app.name') }} Helpdesk System | @yield('title')</title>

  @yield('maps')
  <!-- Tell the browser to be responsive to screen width -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- DataTables -->
  <link rel="stylesheet" href="{{url('dashboard/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="{{url('dashboard/plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
  <!-- Select2 -->
  <link rel="stylesheet" href="{{url('dashboard/plugins/select2/css/select2.min.css')}}">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{url('dashboard/plugins/fontawesome-free/css/all.min.css')}}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="{{url('dashboard/plugins/summernote/summernote-bs4.css')}}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>
  <!-- Ionicons: async non-blocking -->
  <link rel="preconnect" href="https://code.ionicframework.com">
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" media="print" onload="this.media='all'">
  <noscript><link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css"></noscript>
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
  <link rel="stylesheet" href="{{url('dashboard/dist/css/adminlte.min.css')}}">
  <!-- Google Font: async non-blocking -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
  <noscript><link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet"></noscript>
  <link rel="stylesheet" href="{{url('dashboard/dist/css/workflow.css')}}">
  <!-- <link rel="stylesheet" href="{{url('dashboard/dist/css/tvwall.css')}}"> -->
  <!-- Financial Reports & Accounting Stylesheet -->
  <!-- <link rel="stylesheet" href="{{url('css/financial-reports.css')}}?v={{ time() }}"> -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <!-- <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" /> -->
  <!-- <link rel="stylesheet" href="https://unpkg.com/leaflet-search/dist/leaflet-search.css" /> -->

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet-search/3.0.0/leaflet-search.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">

  <!-- jQuery + DataTables + Bootstrap di head (lokal) agar inline scripts bisa pakai $ dan DataTable -->
  <script src="{{url('dashboard/plugins/jquery/jquery.min.js')}}"></script>
  <script src="{{url('dashboard/plugins/datatables/jquery.dataTables.min.js')}}"></script>
  <script src="{{url('dashboard/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
  <script src="{{url('dashboard/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
  <script src="{{url('dashboard/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
  <script src="{{url('dashboard/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.5.6/css/buttons.dataTables.min.css">
  <!-- DataTables Buttons JS di head agar button types (print, excel, pdf, csv) tersedia sebelum DataTable init -->
  <script src="https://cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.flash.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.print.min.js"></script>
  
  <style>

    @keyframes glowing {
      0% {
        background-color: #ffd700;
        box-shadow: 0 0 1px #2ba805;
      }
      50% {
        background-color: #ffd966;
        box-shadow: 0 0 2px #49e819;
      }
      100% {
        background-color: #2ba805;
        box-shadow: 0 0 1px #2ba805;
      }
    }
    .btnblink {
      animation: glowing 1300ms infinite;
    }

    /*.tiketview img {
      width: 100% !important;
      height: auto !important;
    }*/

    /* Optional: Use media queries to apply styles specifically for mobile devices */
    @media (max-width: 768px) {
      .tiketview_padding {
        padding: 2px !important;
      }
      .tiketview img {
        width: 100% !important;
        height: auto !important;
      }
    }

    .badge-soft-danger    { background-color: #ffdddd !important; color: #a94442 !important; }
    .badge-soft-warning   { background-color: #fff8e1 !important; color: #a68c31 !important; }
    .badge-soft-info      { background-color: #e3f1fc !important; color: #2c5463 !important; }
    .badge-soft-success   { background-color: #d4f8e8 !important; color: #35694c !important; }
    .badge-soft-secondary { background-color: #eeeef2 !important; color: #55576b !important; }
    .badge-soft-inprogress { background-color: #dcecfc !important; /* soft blue premium */
      color: #1d4e89 !important;           /* kontras elegan tapi tetap soft */
    }

    /* === Grey Minimal Dark Mode Theme === */
    .dark-mode-preload body,
    body.dark-mode {
      background-color: #3A3D42 !important;
      color: #F4F5F7 !important;
    }

    body.dark-mode .main-header,
    body.dark-mode .main-footer,
    body.dark-mode .main-sidebar {
      background-color: #44474D !important;
      color: #F4F5F7 !important;
    }

    body.dark-mode .content-wrapper {
      background-color: #3A3D42 !important;
    }

    body.dark-mode .card,
    body.dark-mode .table,
    body.dark-mode .form-control {
      background-color: #44474D !important;
      color: #F4F5F7 !important;
      border-color: #575A60 !important;
    }

    body.dark-mode .btn-success {
      background-color: #68C6B8 !important;
      border-color: #68C6B8 !important;
      color: #FFF !important;
    }

    body.dark-mode .btn-primary {
      background-color: #5A9CAF !important;
      border-color: #5A9CAF !important;
      color: #FFF !important;
    }

    body.dark-mode a {
      color: #68C6B8 !important;
    }

    body.dark-mode .nav-link i {
      color: #F4F5F7 !important;
    }

    /* Smooth transition */
    body, .main-header, .main-sidebar, .card, .content-wrapper {
      transition: background-color 0.3s, color 0.3s;
    }

    .btn-choose-step {
      position: relative;
      z-index: 9999;
      cursor: pointer;
      padding: 8px 12px;
    }

    /* --- Card Modern untuk Timeline Item --- */
    .timeline-item {
      background: #ffffff;
      border-radius: 12px;
      border: 1px solid #e5e7eb;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      transition: box-shadow .2s ease, transform .2s ease;
    }

    .timeline-item:hover {
      box-shadow: 0 4px 14px rgba(0,0,0,0.12);
      transform: translateY(-2px);
    }

/* --- Header time + user --- */
.time {
  font-size: 0.85rem;
  color: #6b7280;
}

.timeline-header strong {
  font-size: 0.9rem;
  color: #374151;
}

/* --- Badge status modern --- */
.badge-modern {
  padding: 4px 7px;
  font-size: 0.7rem;
  border-radius: 10px;
  font-weight: 600;
}

/* Soft pastel colors */
.badge-Open { background: #fde2e2; color: #b91c1c; }
.badge-Pending { background: #fef3c7; color: #b45309; }
.badge-Solve { background: #d1fae5; color: #047857; }
.badge-Close { background: #e5e7eb; color: #374151; }
.badge-Inprogress { background: #dcecfc; color: #1d4e89; }


/* --- Ticket Title --- */
.timeline-body strong {
  font-size: 0.92rem;
  color: #111827;
}

/* --- Ticket metadata --- */
.ticket-meta {
  font-size: 0.8rem;
  color: #6b7280;
  line-height: 1.3;
}

/* Button size refinement */


</style>

</head>
<body class="hold-transition sidebar-mini sidebar-collapse">




  <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-center p-4">
        <i class="fa fa-spinner fa-spin" style="font-size:40px"></i>
        <a>Processing, please wait...</a>
      </div>
    </div>
  </div>

  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Navbar -->
    <nav style="background-color:#a3301c" class="main-header navbar navbar-expand navbar-white ">
      <!-- Left navbar links -->
      <ul class="navbar-nav  m-2">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>

      </ul>

      <!-- SEARCH FORM -->
      @php
      $privilege = Auth::user()?->privilege ?? 'merchant';
      @endphp

      @switch ($privilege)

      @case ("admin") 
      <form action="/customer/search" method="GET" class="form-inline  ml-6">
        <div class="input-group input-group-sm m-1  ">
          <input  class="form-control form-control-navbar  navbar-light badge-light @error('search') is-invalid @enderror" name='search' id="search" type="search" placeholder="Search Customer|min:4" aria-label="Search">
          
          <div class="input-group-append">
            <button class="btn btn-success" type="submit">
              <i class="fas fa-search"></i>
            </button>
          </div>
        </div>

      </form>
      @break
      @case ("noc") 
      <form action="/customer/search" method="GET" class="form-inline  ml-6">
        <div class="input-group input-group-sm m-1  ">
          <input  class="form-control form-control-navbar  navbar-light badge-light @error('search') is-invalid @enderror" name='search' id="search" type="search" placeholder="Search Customer|min:4" aria-label="Search">
          
          <div class="input-group-append">
            <button class="btn btn-success" type="submit">
              <i class="fas fa-search"></i>
            </button>
          </div>
        </div>

      </form>
      @break
      @case ("user") 
      <form action="/customer/search" method="GET" class="form-inline  ml-6">
        <div class="input-group input-group-sm m-1  ">
          <input  class="form-control form-control-navbar  navbar-light badge-light @error('search') is-invalid @enderror" name='search' id="search" type="search" placeholder="Search Customer|min:4" aria-label="Search">
          
          <div class="input-group-append">
            <button class="btn btn-success" type="submit">
              <i class="fas fa-search"></i>
            </button>
          </div>
        </div>

      </form>
      @break
      @case ("payment") 
      <form action="/customer/search" method="GET" class="form-inline  ml-6">
        <div class="input-group input-group-sm m-1  ">
          <input  class="form-control form-control-navbar  navbar-light badge-light @error('search') is-invalid @enderror" name='search' id="search" type="search" placeholder="Search Customer|min:4" aria-label="Search">
          
          <div class="input-group-append">
            <button class="btn btn-success" type="submit">
              <i class="fas fa-search"></i>
            </button>
          </div>
        </div>

      </form>
      @break
      @case ("accounting") 
      <form action="/customer/search" method="GET" class="form-inline  ml-6">
        <div class="input-group input-group-sm m-1  ">
          <input  class="form-control form-control-navbar  navbar-light badge-light @error('search') is-invalid @enderror" name='search' id="search" type="search" placeholder="Search Customer|min:4" aria-label="Search">
          
          <div class="input-group-append">
            <button class="btn btn-success" type="submit">
              <i class="fas fa-search"></i>
            </button>
          </div>
        </div>

      </form>
      @break
      @case ("marketing") 
      <form action="/customer/search" method="GET" class="form-inline  ml-6">
        <div class="input-group input-group-sm m-1  ">
          <input  class="form-control form-control-navbar  navbar-light badge-light @error('search') is-invalid @enderror" name='search' id="search" type="search" placeholder="Search Customer|min:4" aria-label="Search">
          
          <div class="input-group-append">
            <button class="btn btn-success" type="submit">
              <i class="fas fa-search"></i>
            </button>
          </div>
        </div>

      </form>
      @break
      @default



      @endswitch



      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">
        <!-- Messages Dropdown Menu -->

        <li class="nav-item">
          <a href="#" id="toggleDarkMode" class="nav-link" title="Toggle Dark Mode">
            <i class="fas fa-moon"></i>
          </a>
        </li>


        <li class="nav-item dropdown">


          <a class="nav-link" href="/uncloseticket">
            <i class="nav-icon fas fa-ticket-alt"></i>
            <span class="badge badge-danger navbar-badge" data-toggle="tooltip" data-placement="top" title="My Ticket"> {{ $ticket->my_ticket() }}</span>
          </a>

          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">

          </div>
        </li>

        <li class="nav-item dropdown">


          <a id="navbarDropdown"  href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
            <img src="/storage/users/{{Auth::user()->photo}}" alt="User Avatar" class="img-size-50 mr-3 img-circle">
          </a>


          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
           <a class="dropdown-item font-weight-bold" >
            {{ Auth::user()->name }} 
          </a>
          <hr>




          @switch ($privilege)

          @case ("admin") 

          <a class="dropdown-item" href="/myticket">
            {{ " My Ticket"}}
          </a>
          <a class="dropdown-item" href="/suminvoice/mytransaction">
            {{ " My Transaction"}}
          </a>
          @break
          @case ("accounting") 

          <a class="dropdown-item" href="/myticket">
            {{ " My Ticket"}}
          </a>
          <a class="dropdown-item" href="/suminvoice/mytransaction">
            {{ " My Transaction"}}
          </a>
          @break
          @case ("merchant") 

          <a class="dropdown-item" href="/payment/mytransaction">
            {{ "Transaction"}}
          </a>
          @break

          @default
          <a class="dropdown-item" href="/myticket">
            {{ " My Ticket"}}
          </a>


          @endswitch



          <hr>
          <a class="dropdown-item" href="{{'/user/'.(Auth::user()->id.'/myprofile') }}">
            {{ " My Profile"}}
          </a>

        <!--   <a class="dropdown-item" href="/suminvoice/mytransaction">
            {{ " My Transaction"}}
          </a> -->
          <a class="dropdown-item" href="{{ route('logout') }}"
          onclick="event.preventDefault();
          document.getElementById('logout-form').submit();">
          {{ __('Logout') }}
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
          @csrf
        </form>


      </div>

    </li>


  </ul>
</nav>
<!-- /.navbar -->

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <!-- Brand Logo -->
  <a href="../../" class="brand-link">
    <img src="{{ tenant_img('favicon.png', 'favicon.png') }}"
    alt="AdminLTE Logo"
    class="brand-image img-circle elevation-3"
    style="opacity: .8">
    <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
  </a>

  <!-- Sidebar -->
  <div class="sidebar">
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
           with font-awesome or any other icon font library -->
           <li class="nav-item">

            <a href="{{ url ('/')}}" class="nav-link">
              <i class="nav-icon  fas fa-tachometer-alt"></i>
              <p>
                Dashboard
              </p>
            </a>

          </li>
          @switch ($privilege)

          @case ("admin") 
          @include('layout/customer')
          @include('layout/schedule')
          @include('layout/ticket')
          @include('layout/plan')
          @include('layout/site')
          @include('layout/distpoint')
          @include('layout/olt')
          @include('layout/distrouter')
          @include('layout/map')
          <hr>
          @include('layout/payment')
          @include('layout/marketing')
          @include('layout/accounting')
          @include('layout/transaction')
          <hr>
          @include('layout/tool')
          @include('layout/admin')
          @break
          @case ("noc") 



          @include('layout/customer')
          @include('layout/schedule')
          @include('layout/ticket')
          @include('layout/plan')
          @include('layout/site')
          @include('layout/distpoint')
          @include('layout/olt')
          @include('layout/distrouter')
          @include('layout/map')

          <hr>
          @include('layout/tool')
          @break

          @case ("accounting")


          @include('layout/customer')
          @include('layout/schedule')
          @include('layout/ticket')
          @include('layout/plan')
        <!--   @include('layout/site')
          @include('layout/distpoint')
          @include('layout/olt')
          @include('layout/distrouter') -->
          @include('layout/map')
          <hr>
          @include('layout/payment')
          @include('layout/marketing')
          @include('layout/accounting')
          @include('layout/transaction')
          @break



          @case ("marketing")


          @include('layout/customer')
          @include('layout/schedule')
          @include('layout/ticket')
          @include('layout/plan')
          <!-- @include('layout/site') -->
          @include('layout/distpoint')
          @include('layout/map')
         <!--  @include('layout/olt')
          @include('layout/distrouter') -->
          <hr>
          <!-- @include('layout/payment') -->
          @include('layout/marketing')
          <!-- @include('layout/accounting') -->
          @break

          @case ("payment")


          @include('layout/customer')
          @include('layout/schedule')
          @include('layout/ticket')
          @include('layout/plan')

          <hr>
          @include('layout/payment')
          @include('layout/marketing')

          @break

          @case ("user")
          @include('layout/customerlite')
          @include('layout/schedule')
          @include('layout/ticket')
          <!-- @include('layout/plan') -->
          @include('layout/site')
          @include('layout/distpoint')
          @include('layout/map')
          <!-- @include('layout/olt') -->
          <!-- @include('layout/distrouter') -->

          <hr>
          @include('layout/tool')
          @break

          @case ("vendor")

          @include('layout/vendor')

          @break

          @case ("merchant")
          @include('layout/customermerchant')
          

          @break

          @default



          @endswitch







        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <div class="row">

      <div class="col-12 p-1 float-sm-right">
        @include('layout/flash-message')
      </div>
    </div>
    @yield('content')
  </div>
  @yield('footer-scripts')
  <!-- /.content-wrapper -->

  <footer style="background-color:#a3301c" class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> 2.0.1
    </div>
    <strong>Copyright &copy; 2024 <a href="http://duwija.io">lubax</a>.</strong> All rights
    reserved.
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- ============================================================ -->
<!-- JS CDN Libraries (dipindah dari <head> agar tidak block render) -->
<!-- ============================================================ -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
  onload="(function(){var s=document.createElement('script');s.src='https://cdnjs.cloudflare.com/ajax/libs/leaflet-search/3.0.0/leaflet-search.min.js';document.head.appendChild(s);})()"
  onerror="console.warn('Leaflet CDN failed to load, map features disabled.')"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
<script src="https://cdn.datatables.net/plug-ins/1.11.5/api/sum().js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js"></script>

<!-- jQuery Local Plugins -->
{{-- jQuery, DataTables, Bootstrap sudah dimuat di <head> --}}
<!-- Sweetalert -->

<script src="{{url('dashboard/plugins/sweetalert2/sweetalert2.all.js')}}"></script>
<!-- Select2-->

<script src="{{url('dashboard/plugins/select2/js/select2.min.js')}}"></script>
<!-- Itik -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>

<script src="{{url('dashboard/dist/js/itik.js')}}"></script>
<!-- AdminLTE App -->
<script src="{{url('dashboard/dist/js/adminlte.min.js')}}"></script>
<!-- AdminLTE fr demo purposes -->
<script src="{{url('dashboard/dist/js/demo.js')}}"></script>
<script src="{{url('dashboard/plugins/summernote/summernote-bs4.min.js')}}"></script>


<script>
  $(document).ready(function() {
    $('form').submit(function(e) {
      // Mencegah submit ganda
      $(this).find(':button[type=submit]')
      .addClass("disabled");
      // .html('Processing.. <i class="fa fa-spinner fa-spin"></i>');
      
      // Tampilkan modal loading
      $('#loadingModal').modal({
        backdrop: 'static', // Modal tidak bisa ditutup dengan klik di luar
        keyboard: false     // Modal tidak bisa ditutup dengan tombol escape
      });
    });
  });
</script>
<script>
  function myFunction(a) {
    var productObj = {};

    productObj.id = a;
    productObj._token = '{{csrf_token()}}';


    $.ajax({
      url: '/invoice/mounthlyfee',
      method: 'post',
      data: productObj,
      success: function(data){
               // alert("Mounthly Invoice was Created !!");
        document.getElementById("inv"+productObj.id).innerHTML = '<a class="badge text-white text-center  badge-secondary"> Created</a>';
      },
      error: function(){
        alert("ERROR To Processed !!");
      }
    });
  }
</script>

<script>


  $(document).ready(function(){
        var date_input=$('input[id="date"]'); //our date input has the name "date"
        var container=$('.bootstrap-iso form').length>0 ? $('.bootstrap-iso form').parent() : "body";
        date_input.datepicker({
          format: 'yyyy-mm-dd',
          container: container,
          todayHighlight: true,
          autoclose: true,
        })
      });

    </script>
    


    <script>
      $('#sale_customer_filter').click(function() 
      {
        $('#table-sale-customer').DataTable().ajax.reload()
      });

      var table = $('#table-sale-customer').DataTable({
        "responsive": true,
        "autoWidth": true,
        "searching": true,
        "language": {
          "processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
        },
        dom: 'Bfrtip',
        buttons: [
          'pageLength','copy', 'excel', 'pdf', 'csv', 'print'
          ],
        serverSide: true,
        ajax: {
          url: '/sale/table_sale_customer',
          method: 'POST',
          
          data: function ( d ) {
           return $.extend( {}, d, {
            "id_sale":$("#id_sale").val(),
            "filter": $("#filter").val(),
            "parameter": $("#parameter").val(),
            "id_status": $("#id_status").val(),
            "id_plan": $("#id_plan").val(),             
          } );
         }
       },
       'columnDefs': [
       {
      "targets": 5, // your case first column
      "className": "text-center",

    },
    {
      "targets": 6, // your case first column
      "className": "text-center",

    },
    {
      "targets": 7, // your case first columnzZxZ
      "className": "text-center",

    }
    ],
       columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        {data: 'customer_id', name: 'customer_id'},
        {data: 'name', name: 'name'},
        {data: 'address', name: 'address'},
        {data: 'plan', name: 'plan'},
        {data: 'price', name: 'price'},
        {data: 'billing_start', name: 'billing_start'},
        {data: 'status_cust', name: 'status_cust'},
                     // {data: 'select', name: 'select'},
        {data: 'invoice', name: 'invoice'},
                     // {data: 'action', name: 'action'}


        ],

     });
   </script>



   <script>
    $('#sales_filter').click(function() 
    {
      $('#table_sales').DataTable().ajax.reload()
    });

    var table = $('#table_sales').DataTable({

      "responsive": true,
      "autoWidth": false,
      "searching": false,
      "language": {
        "processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
      },
      dom: 'lBfrtip',
      buttons: [
        'copy', 'excel', 'pdf', 'csv', 'print'
        ],
      "lengthMenu": [[25, 50, 100, 200, 500], [25, 50, 100, 200, 500]],
      processing: true,
      serverSide: true,
      ajax: {
        url: '/sale/table_sales',
        method: 'POST',
        // },
        data: function ( d ) {
         return $.extend( {}, d, {

           "id_user": $("#id_user").val(),
           "date_from": $(document.querySelector('[name="date_from"]')).val(),
           "date_end": $(document.querySelector('[name="date_end"]')).val(),             
         } );
       }
     },
 //                 'columnDefs': [
 // //  {
 // //      "targets": , // your case first column
 // //      "className": "text-center",

 // // },
 // {
 //      "targets": 2, // your case first column
 //      "className": "text-center",

 // }
 // ],

     "footerCallback": function ( row, data, start, end, display ) {
      var api = this.api(), data;

            // Remove the formatting to get integer data for summation
      var intVal = function ( i ) {
        return typeof i === 'string' ?
        i.replace(/[\$,]/g, '')*1 :
        typeof i === 'number' ?
        i : 0;
      };

            // Total over all pages
      total_input = api
      .column( 5 )
      .data()
      .reduce( function (a, b) {
        return intVal(a) + intVal(b);
      }, 0 );

            // Total over this page
      pageTotal_input = api
      .column( 5, { page: 'current'} )
      .data()
      .reduce( function (a, b) {
        return intVal(a) + intVal(b);
      }, 0 );

            // Update footer


      total_output = api
      .column( 6 )
      .data()
      .reduce( function (a, b) {
        return intVal(a) + intVal(b);
      }, 0 );

            // Total over this page
      pageTotal_output = api
      .column( 6, { page: 'current'} )
      .data()
      .reduce( function (a, b) {
        return intVal(a) + intVal(b);
      }, 0 );

      $( api.column( 5 ).footer() ).html(
        ' (Rp.'+pageTotal_input.toLocaleString("id-ID")+') <br/> Rp.'+total_input.toLocaleString("id-ID")
        );
            // Update footer
      $( api.column( 6 ).footer() ).html(
       ' (Rp.'+pageTotal_output.toLocaleString("id-ID")+') <br/> Rp.'+total_output.toLocaleString("id-ID")
       );
      $( api.column( 7 ).footer() ).html(
        'Margin per Page : Rp.'+ (pageTotal_input - pageTotal_output).toLocaleString("id-ID")+'<br/>'+
        'Margin Total : Rp.'+ (total_input - total_output).toLocaleString("id-ID")
        );


    },

    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      {data: 'date', name: 'date'},
      {data: 'sales', name: 'sales', orderable: false},
      {data: 'customer', name: 'customer', orderable: false},
      {data: 'customer_name', name: 'customer_name'},
      {data: 'input', name: 'input'},
      {data: 'output', name: 'output'},
      {data: 'suminvoice', name: 'suminvoice'},
                     // {data: 'invoice', name: 'invoice'},
                     // {data: 'action', name: 'action'}


      ],



  });
</script>
<script>
  $(function () {
    $("#example1").DataTable({

      "lengthMenu": [[25, 50, 100, 200, -1], [25, 50, 100, 200, "All"]],



      "responsive": true,
      "autoWidth": false,
      dom: 'Bfrtip',
      buttons: [
        'pageLength',
        'copyHtml5',
        'print',

        'excelHtml5',
        'csvHtml5',
        'pdfHtml5'
        ]
    });
//yajra
 // $('#table-customer thead th').each( function (i) {
 //        var title = $('#table-customer thead th').eq( $(this).index() ).text();
 //        $(this).html( '<input type="text" placeholder="'+title+'" data-index="'+i+'" />' );
 //    } );



 // Filter event handler
    // $( table.table().container() ).on( 'keyup', 'thead input', function () {
    //     table
    //         .column( $(this).data('index') )
    //         .search( this.value )
    //         .draw();
    // } );

    $("#datatablerugilaba").DataTable({

      //  "lengthMenu": [[10, 25, 50, 100, 200, -1], [10, 25, 50, 100, 200, "All"]],



      "responsive": true,
      "autoWidth": false,
      dom: 'Bfrtip',
      buttons: [
        'pageLength',
        'copyHtml5',
        'excelHtml5',
        'csvHtml5',
        'pdfHtml5'
        ]
    });
    $("#datatableneraca").DataTable({

      "lengthMenu": [[10, 25, 50, 100, 200, -1], [10, 25, 50, 100, 200, "All"]],



      "responsive": true,
      "autoWidth": false,
      dom: 'Bfrtip',
      buttons: [
        'pageLength',
        'copyHtml5',
        'excelHtml5',
        'csvHtml5',
        'pdfHtml5'
        ]
    });

    $('.select2').select2();
      // $('#time').timepicker({ timeFormat: 'HH:mm', startTime: '08:00',dynamic: false,
      //   dropdown: true,});

    $('#time_update').timepicker({ timeFormat: 'hh:mm', startTime: '08:00',dynamic: false,
      dropdown: true,});

    $('.textarea').summernote({

      height: 300,
      dialogsInBody: true,
      callbacks:{
        onInit:function(){
          $('body > .note-popover').hide();
        }
      },

    });
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
      dom: 'Bfrtip',
      buttons: [
        'copyHtml5',
        'excelHtml5',
        'csvHtml5',
        'pdfHtml5'
        ]
    });
    $('#example3').DataTable({
      "paging": false,
      "lengthChange": false,
      "searching": true,
      "ordering": true,
      "info": false,
      "autoWidth": false,
      "responsive": true,
      dom: 'Bfrtip',
      buttons: [
        'copyHtml5',
        'excelHtml5',
        'csvHtml5',
        'pdfHtml5'
        ]
    });
  });

</script>




<script type="text/javascript" src="https://code.highcharts.com/highcharts.js"></script>

<script> 
  $('#modal-monitor').on('hidden.bs.modal', function () {
   window.location.reload();
 });
  $('#modal-monitor').on('show.bs.modal', function () {


    var chart;

    function requestDatta() {
      $.ajax({
       url: '/distrouter/client_monitor',
       method: 'post',
      // datatype: "json",
       data:{interface:document.getElementById("interface").value,
       ip:document.getElementById("ip").value,
       user:document.getElementById("user").value,
       password:document.getElementById("password").value,
       port:document.getElementById("port").value

     },
     success: function(data) {
       var midata = JSON.parse(data);
        // console.log(midata);
       if( midata.length > 0 ) {
        var TX=parseInt(midata[0].data);
        var RX=parseInt(midata[1].data);
        var x = (new Date()).getTime(); 
        shift=chart.series[0].data.length > 19;
        chart.series[0].addPoint([x, TX], true, shift);
        chart.series[1].addPoint([x, RX], true, shift);
        document.getElementById("tabletx").innerHTML=convert(TX);
        document.getElementById("tablerx").innerHTML=convert(RX);
      }else{
        document.getElementById("tabletx").innerHTML="0";
        document.getElementById("tablerx").innerHTML="0";
      }
    },
    error: function(XMLHttpRequest, textStatus, errorThrown) { 
      console.error("Status: " + textStatus + " request: " + XMLHttpRequest); console.error("Error: " + errorThrown); 
    }       
  });
    } 

    $(document).ready(function() {

      Highcharts.setOptions({
        global: {
          useUTC: false
        }
      });


      chart = new Highcharts.Chart({
       chart: {
        renderTo: 'graph',
        animation: Highcharts.svg,
        type: 'area',
        events: {
          load: function () {
            setInterval(function () {
              requestDatta();
            }, 1000);
          }       
        }
      },
      title: {
        text: 'Traffic Monitoring'
      },
      xAxis: {
        type: 'datetime',
        tickPixelInterval: 150,
        maxZoom: 20 * 1000
      },

      yAxis: {
        minPadding: 0.2,
        maxPadding: 0.2,
        title: {
          text: 'Traffic'
        },
        labels: {
          formatter: function () {      
            var bytes = this.value;                          
            var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
            if (bytes == 0) return '0 bps';
            var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
            return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];                    
          },
        },       
      },
      series: [{
        name: 'TX',
        data: []
      }, {
        name: 'RX',
        data: []
      }],
      tooltip: {
        headerFormat: '<b>{series.name}</b><br/>',
        pointFormat: '{point.x:%Y-%m-%d %H:%M:%S}<br/>{point.y}'
      },


    });
    });
    function convert( bytes) {      

      var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
      if (bytes == 0) return '0 bps';
      var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
      return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];                    
    }
  })
</script>


<script>
  document.addEventListener("DOMContentLoaded", function() {
    const body = document.body;
    const html = document.documentElement;
    const toggleButton = document.getElementById("toggleDarkMode");

      // Apply preload fix
    if (html.classList.contains("dark-mode-preload")) {
      html.classList.remove("dark-mode-preload");
      body.classList.add("dark-mode");
    }

      // Set correct icon on load
    if (localStorage.getItem("darkMode") === "enabled") {
      toggleButton.innerHTML = '<i class="fas fa-sun"></i>';
    } else {
      toggleButton.innerHTML = '<i class="fas fa-moon"></i>';
    }

      // Toggle Dark Mode
    toggleButton.addEventListener("click", function(e) {
      e.preventDefault();
      body.classList.toggle("dark-mode");
      if (body.classList.contains("dark-mode")) {
        localStorage.setItem("darkMode", "enabled");
        toggleButton.innerHTML = '<i class="fas fa-sun"></i>';
      } else {
        localStorage.setItem("darkMode", "disabled");
        toggleButton.innerHTML = '<i class="fas fa-moon"></i>';
      }
    });
  });
</script>

</body>
</html>
