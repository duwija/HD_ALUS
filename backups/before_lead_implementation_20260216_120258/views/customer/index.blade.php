
@extends('layout.main')
@section('title','Customer List')
@section('content')
@inject('suminvoice', 'App\Suminvoice')
<section class="content-header p-0 m-0 p-md-3 m-md-3">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Customers List  </h3>

    </div>

    <div class="form-row mb-4 p-3" >
      <div class="form-group col-md-2">
        <label for="filter">Filter By</label>
        <select name="filter" id="filter" class="form-control">
          <option value="name">Name</option>
          <option value="customer_id">Customer ID</option>
          <option value="address">Address</option>
          <option value="phone">Phone</option>
          <option value="id_card">ID Card</option>
          <option value="billing_start">Billing Start</option>
          <option value="isolir_date">Isolir Date</option>
        </select>
      </div>

      <div class="form-group col-md-2">
        <label for="parameter">Parameter</label>
        <input
        type="text"
        id="parameter"
        name="parameter"
        class="form-control"
        placeholder="Leave blank for all"
        >
      </div>

      <div class="form-group col-md-2">
        <label for="id_merchant">Merchant</label>
        <select name="id_merchant" id="id_merchant" class="form-control">
          <option value="">All</option>
          @foreach ($merchant as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group col-md-2">
        <label for="id_status">Status</label>
        <select name="id_status" id="id_status" class="form-control">
          <option value="">All</option>
          @foreach ($status as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group col-md-2">
        <label for="id_plan">Plan</label>
        <select name="id_plan" id="id_plan" class="form-control">
          <option value="">All</option>
          @foreach ($plan as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group col-md-2 d-flex align-items-end">
        <button
        type="button"
        id="customer_filter"
        class="btn btn-warning btn-block"
        >
        Filter
      </button>
    </div>
  </div>

  <!-- /.card-header -->
  <div class="card-body">
    <div class="row">
      <!-- Plan Group Table -->
      <div class="col-md-4 d-flex align-items-stretch">
        <div class="card card-primary card-outline mb-3 flex-fill">
          <div class="card-header">
            <h3 class="card-title">Plan Group</h3>
          </div>
          <div class="card-body p-2 table-responsive">
            <table id="table-plan-group" class="table table-bordered table-striped mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Plan Name</th>
                  <th>Customer Count</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>

      <!-- Customer Status Chart -->
      <div class="col-md-4 d-flex align-items-stretch">
        <div class="card card-primary card-outline mb-3 flex-fill">
          <div class="card-header">
            <h3 class="card-title">Customer Status</h3>
          </div>
          <div class="card-body p-2">
            <canvas id="customerStatusChart" height="400"></canvas>
          </div>
        </div>
      </div>

      <!-- New Customers Per Day Chart -->
      <div class="col-md-4 d-flex align-items-stretch">
        <div class="card card-primary card-outline mb-3 flex-fill">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">New Customers This Month</h3>
            <span class="badge badge-info badge-pill">
              Total: {{ $totalNewCustomers }}
            </span>
          </div>
          <div class="card-body p-2">
            <canvas id="dailyNewCustomersChart" height="200"></canvas>
          </div>
        </div>
      </div>
    </div>


    <div class="table-responsive">
      <form role="form" method="post" action="/customer/update/status">
       @method('patch')
       @csrf
       <table id="table-customer" class="table table-bordered table-striped ">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Customer Id</th>
            <th scope="col">Name</th>
            <th scope="col">Address</th>
            <th scope="col">Merchant</th>
            <th scope="col">Plan</th>
            <th scope="col">Billing Start</th>
            <th scope="col">Isolir Date</th>
            <th scope="col">Status</th>

            <th scope="col">Invoice</th>
            <th scope="col">Notif</th>
            <th scope="col">Action</th>
          </tr>
        </thead>





      </table>

    </form>
  </div>
</div>
</div>

</section>

@endsection
@section('footer-scripts')
@include('script.customer')
@endsection 

