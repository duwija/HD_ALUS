@extends('layout.main')
@section('title','Customer List')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Customers List</h3>
    </div>

    @if ($merchantScopeEmpty)
      <div class="alert alert-warning m-3 mb-0">
        <i class="fas fa-exclamation-triangle"></i>
        Belum ada merchant yang di-set untuk akun ini. Hubungi Super Admin untuk mengatur cakupan merchant di halaman Edit Tenant (admin pusat).
      </div>
    @endif

    <div class="row pt-2 pl-4">
      <div class="form-group col-md-2">
        <label>Filter By</label>
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
        <label>Parameter</label>
        <div class="input-group mb-3">
          <input class="form-control" type="text" id="parameter" name="parameter" placeholder="Leave blank for all">
        </div>
      </div>
      <div class="form-group col-md-2">
        <label>Status</label>
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
        <label>&nbsp;</label>
        <div class="input-group p-1 col-md-3">
          <button type="button" id="customer_filter" class="btn btn-warning">Filter</button>
        </div>
      </div>
    </div>

    <div class="card-body">
      <div class="table-responsive w-100">
        <table id="table-customer" class="table table-bordered table-striped w-100">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Customer Id</th>
              <th scope="col">Name</th>
              <th scope="col">Address</th>
              <th scope="col">Merchant</th>
              <th scope="col">Status</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

</section>
@endsection
@section('footer-scripts')
<script>
  var input = document.getElementById("parameter");
  input.addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
      event.preventDefault();
      document.getElementById("customer_filter").click();
    }
  });

  $('#customer_filter').click(function() {
    $('#table-customer').DataTable().ajax.reload();
  });

  var table = $('#table-customer').DataTable({
    "responsive": false,
    "scrollX": true,
    "autoWidth": false,
    "searching": false,
    "language": {
      "processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
    },
    dom: 'Bfrtip',
    buttons: ['pageLength', 'copy', 'excel', 'pdf', 'csv', 'print'],
    "lengthMenu": [[25, 50, 100, 200, 500], [25, 50, 100, 200, 500]],
    processing: true,
    serverSide: true,
    pageLength: 50,
    "order": [],
    ajax: {
      url: '{{ route("adminbilling.customers.data") }}',
      method: 'POST',
      data: function (d) {
        return $.extend({}, d, {
          "filter": $("#filter").val(),
          "parameter": $("#parameter").val(),
          "id_status": $("#id_status").val(),
        });
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'customer_id', name: 'customer_id' },
      { data: 'name', name: 'name' },
      { data: 'address', name: 'address' },
      { data: 'id_merchant', name: 'id_merchant' },
      { data: 'status_cust', name: 'status_cust' },
    ],
  });
</script>
@endsection
