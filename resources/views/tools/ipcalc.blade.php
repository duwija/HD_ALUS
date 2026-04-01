@extends('layout.main')
@section('title', 'IP Address Network Calculator')

@section('content')
<section class="content-header">
  <div class="d-flex justify-content-center">
    <div class="card card-primary card-outline col-12 col-md-6 col-lg-4">
      <div class="card-header text-center">
        <h5>IP Address Network Calculator</h5>
      </div>

      <!-- Card Body -->
      <div class="card-body">
        <form method="POST" action="/tool/ipcalc">
          @csrf
          <div class="form-group mb-3">
            <label for="ipAddress" class="form-label">IP Address</label>
            <input type="text" name="ip_address" id="ipAddress" class="form-control" placeholder="e.g., 192.168.1.1" required>
          </div>
          <div class="form-group mb-4">
            <label for="subnetMask" class="form-label">Subnet Mask (CIDR Notation)</label>
            <input type="text" name="subnet_mask" id="subnetMask" class="form-control" placeholder="e.g., /24" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Calculate</button>
        </form>

        <!-- Calculation Results -->
        @if(isset($network))
        <div class="mt-5">
          <h5>Calculation Results:</h5>
          <ul class="list-group">
            <li class="list-group-item"><strong>IP Address:</strong> {{ $ip }}</li>
            <li class="list-group-item"><strong>Subnet Mask:</strong> {{ $mask }} (/{{ $cidr }})</li>
            <li class="list-group-item"><strong>Network Address:</strong> {{ $network }}</li>
            <li class="list-group-item"><strong>Broadcast Address:</strong> {{ $broadcast }}</li>
            <li class="list-group-item"><strong>Wildcard Mask:</strong> {{ $wildcardMask }}</li>
            <li class="list-group-item"><strong>Number of Usable Hosts:</strong> {{ $numHosts }}</li>
            <li class="list-group-item"><strong>First Usable IP:</strong> {{ $firstIp }}</li>
            <li class="list-group-item"><strong>Last Usable IP:</strong> {{ $lastIp }}</li>
          </ul>
        </div>
        @endif
      </div>
      <!-- End Card Body -->
    </div>
  </div>
</section>
@endsection
