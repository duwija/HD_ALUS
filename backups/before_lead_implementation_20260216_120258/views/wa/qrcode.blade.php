@extends('layout.main')
@section('title','Whatsapp')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Whatsapp Status </h3>
      
    </div>

    <!-- /.card-header -->
    <div class="card-body">

      <div class="container">
        <div>
          <?php

          if ($result=="<br><br><center>AUTHENTICATED</center>")
          {
            echo "<center><img style='width:200px' src='" . asset('img/connected.png') . "'></br> CONNECTED </center>";
          }
          else
          {
           echo $result;
         }
         ?>
         
       </div>

       <table class="table table-bordered">
        <tr>
          <th>Balance</th>
          <td>Rp.{{ number_format($infoData['balance'], 0, ',', '.') }}</td>
        </tr>
        <tr>
          <th>ID</th>
          <td>{{ $deviceData['id'] }}</td>
        </tr>
        <tr>
          <th>Phone Number</th>
          <td>{{ $deviceData['phoneNumber'] }}</td>
        </tr>
        <tr>
          <th>Name</th>
          <td>{{ $deviceData['name'] }}</td>
        </tr>
        <tr>
          <th>OS Version</th>
          <td>{{ $deviceData['os_version'] ?? 'N/A' }}</td>
        </tr>
        <tr>
          <th>Manufacturer</th>
          <td>{{ $deviceData['manufacturer'] ?? 'N/A' }}</td>
        </tr>
        <tr>
          <th>Model</th>
          <td>{{ $deviceData['model'] ?? 'N/A' }}</td>
        </tr>
        <tr>
          <th>Battery Level</th>
          <td>{{ $deviceData['batteryLevel'] ?? 'N/A' }}</td>
        </tr>
        <tr>
          <th>Device ID</th>
          <td>{{ $deviceData['id_device'] }}</td>
        </tr>
        <tr>
          <th>Token</th>
          <td>{{ $deviceData['token'] }}</td>
        </tr>
        <tr>
          <th>Token Expiry</th>
          <td>{{ $deviceData['expired'] }}</td>
        </tr>
        <tr>
          <th>Package</th>
          <td>{{ $deviceData['paket'] }}</td>
        </tr>
        <tr>
          <th>Message</th>
          <td>{{ $deviceData['message'] }}</td>
        </tr>
      </table>
    </div>
  </div>
</div>

</section>

@endsection
