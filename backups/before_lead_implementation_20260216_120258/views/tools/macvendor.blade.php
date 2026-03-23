@extends('layout.main')
@section('title', 'MAC Vendor Lookup')

@section('content')
<section class="content-header">
  <div class="d-flex justify-content-center">
    <div class="card card-primary card-outline">
      <div class="card-header">
        <h5>Mac lookup</h5>
      </div>
      <!-- /.card-header -->
      <div class="card-body">
<iframe src="http://103.251.8.156" width="680" height="480" allowfullscreen></iframe>

        <div class="container d-flex justify-content-center align-items-center mb-5" style="min-height: 10vh;">
          <div class="col-md-6">
            <h2 class="text-center mb-4">MAC Address Vendor Lookup</h2>

            <form method="POST" action="/tool/macvendor" class="text-center">
              @csrf
              <div class="form-group mb-4">
                <label for="macAddress" class="form-label">Enter MAC Address</label>
                <input type="text" name="mac_address" id="macAddress" class="form-control text-center" placeholder="e.g., FC:FB:FB:01:FA:21" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">Lookup Vendor</button>
            </form>

            @if(isset($vendor))
            <div class="mt-3 text-center">
              <h4>Vendor: {{ $vendor }}</h4>
            </div>
            @elseif(isset($vendor) && $vendor === false)
            <div class="mt-3 text-center text-danger">
              <h4>Vendor not found.</h4>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection