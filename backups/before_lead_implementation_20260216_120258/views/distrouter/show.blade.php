@extends('layout.main')
@section('title', 'distrouter')

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold">Show Detail Router</h3>
    </div>


    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div class="card mb-3">
            <div class="card-header bg-success">
              <h5 class="card-title">Router Details</h5>
            </div>
            <div class="card-body">

              <p><strong>Name:</strong> {{ $distrouter->name }}</p>
              <p><strong>IP Address:</strong> {{ $distrouter->ip }}</p>
              <p><strong>Api Port:</strong> {{ $distrouter->port }}</p>
              <p><strong>Web Port:</strong> {{ $distrouter->web }}</p>
              <p><strong>Description:</strong> {{ $distrouter->note }}</p>
              <p><strong>Registered User:</strong> {{ $count_user }}</p>
              <div class="card-footer">

                <a href="/distrouter/{{ $distrouter->id }}/edit" class="btn btn-primary btn-sm ">  Edit  </a>
                <a href="/distrouter/backupconfig/{{ $distrouter->id }}" class="btn btn-primary btn-sm ">  Add backup schedule  </a>
                <a href="/distrouter/import-ppp-profiles/{{ $distrouter->id }}" class="btn btn-success btn-sm" onclick="return confirm('Import PPPoE profiles from this Mikrotik to Plans table?')">
                  <i class="fas fa-download"></i> Import PPP Profiles
                </a>


                <form  action="/distrouter/{{ $distrouter->id }}" method="POST" class="d-inline item-delete" >
                  @method('delete')
                  @csrf

                  <button type="submit"  class="btn btn-danger btn-sm float-right">  Delete  </button>
                </form>

              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card mb-3">
            <div class="card-header bg-primary">
              <h5 class="card-title">Retrieved distrouter Information</h5>
            </div>
            <div class="card-body">
              <div id="distrouter-info">
                <div id="spinner" style="display:none; text-align: center;">
                  <p>Loading...</p>
                  <span class='fa-stack fa-lg'>
                    <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>







        <div class="col-md-12">
          <div class="card">

            <div class="">
              <nav class="navbar navbar-expand-lg navbar-light bg-success">
                <a class="navbar-brand" href="#">MikroTik Commands</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                  <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                  <ul class="navbar-nav" id="categoryMenu">
                    <!-- Menu items will be dynamically loaded via JavaScript -->
                    <li class="nav-item dropdown">
                      <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span><strong>IP</strong></span>
                      </a>
                      <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                       <a class="dropdown-item" href="#" data-command="/ip/address/print" data-id="{{ $distrouter->id }}">Addresses</a>
                       <a class="dropdown-item" href="#" data-command="/ip/dns/print" data-id="{{ $distrouter->id }}">DNS</a>
                       <a class="dropdown-item" href="#" data-command="/ip/route/print" data-id="{{ $distrouter->id }}">Route</a>
                       <a class="dropdown-item" href="#" data-command="/ip/firewall/nat/print" data-id="{{ $distrouter->id }}">Firewall-NAT</a>
                       <a class="dropdown-item" href="#" data-command="/ip/firewall/filter/print" data-id="{{ $distrouter->id }}">Firewall-Filter</a>
                       <a class="dropdown-item" href="#" data-command="/ip/firewall/mangle/print" data-id="{{ $distrouter->id }}">Firewall-Mangle</a>
                       <a class="dropdown-item" href="#" data-command="/ip/firewall/address-list/print" data-id="{{ $distrouter->id }}">Firewall-Address-List</a>
                       <a class="dropdown-item" href="#" data-command="/ip/firewall/raw/print" data-id="{{ $distrouter->id }}">Firewall-Raw</a>
                       <a class="dropdown-item" href="#" data-command="/ip/service/print" data-id="{{ $distrouter->id }}">Services</a>

                     </div>
                   </li>

                   <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                     <span><strong>Interface</strong></span> 
                   </a>
                   <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="#" data-command="/interface/print" data-id="{{ $distrouter->id }}">Interface</a>
                    <a class="dropdown-item" href="#" data-command="/interface/ethernet/print" data-id="{{ $distrouter->id }}">Ethernet</a>
                    <a class="dropdown-item" href="#" data-command="/interface/vlan/print" data-id="{{ $distrouter->id }}">Vlan</a>
                    <a class="dropdown-item" href="#" data-command="/interface/bridge/print" data-id="{{ $distrouter->id }}">Bridge</a>
                    <a class="dropdown-item" href="#" data-command="/interface/bridge/port/print" data-id="{{ $distrouter->id }}">Bridge-Port</a>

                  </div>
                </li>


                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span><strong> Routing</strong></span>
                  </a>
                  <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="#" data-command="/routing/bgp/peer/print" data-id="{{ $distrouter->id }}">BGP-Peer (ROS_6)</a>
                    <a class="dropdown-item" href="#" data-command="/routing/bgp/session/print" data-id="{{ $distrouter->id }}">BGP-Peer (ROS_7)</a>
                    <a class="dropdown-item" href="#" data-command="/routing/filter/print" data-id="{{ $distrouter->id }}">Filter (ROS_6)</a>
                    <a class="dropdown-item" href="#" data-command="/routing/filter/rule/print" data-id="{{ $distrouter->id }}">Filter (ROS_7)</a>
                  </div>
                </li>

                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                   <span><strong>PPP</strong></span>
                 </a>
                 <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                  <a class="dropdown-item" href="#" data-command="/ppp/secret/print" data-id="{{ $distrouter->id }}">Secret</a>
                  <a class="dropdown-item" href="#" data-command="/ppp/profile/print" data-id="{{ $distrouter->id }}">Profile</a>
                  <a class="dropdown-item" href="#" data-command="/ppp/active/print" data-id="{{ $distrouter->id }}">Active</a>
                </div>
              </li>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                 <span><strong>Queue</strong></span>
               </a>
               <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                <a class="dropdown-item" href="#" data-command="/queue/simple/print" data-id="{{ $distrouter->id }}">Simple Queue</a>
                <a class="dropdown-item" href="#" data-command="/queue/tree/print" data-id="{{ $distrouter->id }}">Queue Tree</a>
                <a class="dropdown-item" href="#" data-command="/queue/type/print" data-id="{{ $distrouter->id }}">Queue Type</a>
              </div>
            </li>
          </ul>
        </div>
      </nav>

    </div>
  </div>
</div>
























<div class="col-md-12">
  <div id="spinnermk" style="display:none; text-align: center;">
    <p>Loading...</p>
    <span class='fa-stack fa-lg'>
      <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>
    </span>
  </div>
  
  <!-- Command Output Section -->
  <div class="table-responsive card p-2 mb-3" id="commandOutput">
    <!-- Command results will be displayed here -->
  </div>
  
  <!-- PPP User Status Section -->
  <div class="card">
    <div class="card-header">
      <h5 class="card-title">PPP User Status</h5>
    </div>
    <div class="card-body">
      <div class="mb-3">
        <label for="statusFilter">Filter by Status</label>
        <select id="statusFilter" class="form-control col-md-2">
          <option value="all">All</option>
          <option value="online">Online</option>
          <option value="offline">Offline</option>
          <option value="disabled">Disabled</option>
        </select>
      </div>
      
      <div id="spinnerpppoe" style="display:none; text-align: center; padding: 20px;">
        <p>Loading PPPoE data...</p>
        <span class='fa-stack fa-lg'>
          <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>
        </span>
      </div>

      <div class="table-responsive">
        <table id="pppoeTable" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Name</th>
              <th>Description</th>
              <th>Profile</th>
              <th>Last logout</th>
              <th>Address</th>
              <th>Uptime</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="col-md-12 row">

  <div class="col-md-6">
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="card-title">Interfaces</h5>
      </div>
      <div class="card-body">

        <div style=" min-height: 300px; max-height: max-height: 400px; overflow-y: auto;">
          <table id="interface-table" name="interface-table" class="table table-bordered table-striped mt-4 ">

            <thead >
              <tr>

                <th scope="col">Interfaces</th>
                <th scope="col">Comment</th>
                <th scope="col">Running</th>
                <th scope="col">Data rate</th>
                <th style="width: 150px;" scope="col">RX </th>
                <th style="width: 150px;" scope="col">TX </th>

              </tr>
            </thead>
            <tbody>

            </tbody>

          </table>
        </div>

      </div>
    </div>

  </div>

  <div class="col-md-6">

    <div class="card mb-3">
      <div class="card-header">
        <h5 class="card-title">System Logs</h5>
      </div>
      <div class="card-body table-responsive">
        <table id="logTable" class="table table-bordered table-striped mt-4 ">
          <thead>
            <tr>
              <th>Time</th>
              <th>Topics</th>
              <th>Message</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="3">Loading logs...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</div>
</div>
</div>
</div>

</section>


@endsection
@section('footer-scripts')
@include('script.distrouter')
@endsection 
