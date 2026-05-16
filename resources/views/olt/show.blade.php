@extends('layout.main')
@section('title', 'OLT')

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold">Show Detail Olt</h3>
    </div>

    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div class="card mb-3">
            <div class="card-header">


              <div id="loading-spinner" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999;">
                <div class="spinner-border text-primary" role="status">
                  <span class="sr-only">Loading...</span>
                </div>
                <p>Please wait, processing...</p>
              </div>


              <h5 class="card-title">OLT Details</h5>
            </div>


            <div class="card-body">

              <p><strong>Name:</strong> {{ $olt->name }}</p>
              <p><strong>Vendor:</strong> 
                @php
                  $vendorBadges = [
                    'zte' => 'badge-info',
                    'cdata' => 'badge-success',
                    'hsgq' => 'badge-warning',
                    'huawei' => 'badge-danger',
                    'fiberhome' => 'badge-primary',
                    'vsol' => 'badge-secondary',
                    'other' => 'badge-dark',
                  ];
                  $badge = $vendorBadges[$olt->vendor ?? 'other'] ?? 'badge-secondary';
                  $detectedVendor = get_olt_vendor($olt);
                @endphp
                <span class="badge {{ $badge }}">{{ strtoupper($olt->vendor ?? 'N/A') }}</span>
                <small class="text-muted">(Detected: {{ $detectedVendor }})</small>
              </p>
              <p><strong>IP Address:</strong> {{ $olt->ip }}</p>
              <p><strong>Type:</strong> {{ $olt->type }}</p>
              <p><strong>User:</strong> {{ $olt->user }}</p>
              <p><strong>SNMP Port:</strong> {{ $olt->snmp_port }}</p>
              <div class=" form-groupfloat-right m-2 " >
                <a href="/oltonutype/olt/{{$olt->id}}" class="btn btn-success btn-sm "> Onu Type  </a>
                <a href="/oltonuprofile/olt/{{$olt->id}}" class="btn btn-success btn-sm "> Onu Profile  </a>

                <a href="/olt/{{$olt->id}}/edit" class="btn btn-primary btn-sm "> Edit  </a>


                <form  action="/olr/{{ $olt->id }}" method="POST" class="d-inline site-delete" >
                  @method('delete')
                  @csrf

                  <button type="submit"  class="btn btn-danger btn-sm">  Delete  </button>
                </form>

              </div>
            </div>
          </div>


        </div>

        <div class="col-md-6">
          <div class="card mb-3">
            <div class="card-header">
              <h5 class="card-title">Retrieved OLT Information</h5>
            </div>
            <div class="card-body">



              <div id="olt-info">
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

        {{-- Health Dashboard: Top 10 ONU dengan RX Power terburuk --}}
        <div class="col-md-12">
          <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="fas fa-heartbeat text-danger"></i>
                Health Dashboard — Top 10 ONU RX Terburuk
              </h5>
              <div>
                <small id="rxHealthMeta" class="text-muted mr-2"></small>
                <button id="btnRefreshRxHealth" type="button" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-sync-alt"></i> Refresh
                </button>
              </div>
            </div>
            <div class="card-body p-2">
              <div id="rxHealthSpinner" class="text-center p-3" style="display:none;">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <div class="small text-muted mt-2">Scanning RX power semua ONU…</div>
              </div>
              <div class="table-responsive">
                <table id="rx-health-table" class="table table-sm table-bordered table-striped mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th style="width:40px;">#</th>
                      <th>RX (dBm)</th>
                      <th>PON</th>
                      <th>ONU ID</th>
                      <th>SN</th>
                      <th>Name</th>
                      <th>Customer</th>
                      <th style="width:90px;">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr><td colspan="8" class="text-center text-muted">
                      Klik <strong>Refresh</strong> untuk memuat data.
                    </td></tr>
                  </tbody>
                </table>
              </div>
              <div class="small text-muted mt-2">
                <span class="badge badge-warning">≤ -25 dBm</span> Perlu perhatian &nbsp;·&nbsp;
                <span class="badge badge-danger">≤ -27 dBm</span> Kritis (mendekati LOS)
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-12">
          <div class="card mb-3">
            <div class="card-header">

              <h5 class="card-title">ONU List</h5>
              <!-- <a data-toggle="modal" href="#unconfigonu" class="float-right badge badge-primary">Unconfig Onu </a> -->
            </div>
            <div class="card-body">
              <div class="row m-1 mb-3 align-items-center">
                <input hidden type="text" id="olt_id" name="olt_id" value="{{ $olt->id }}">

                <div class="col-md-6 d-flex flex-wrap align-items-center p-0">
                  <select id="oltPonComboBox" name="oltPonComboBox" class="form-control col-md-8 m-1">
                    <option value="">Pilih OLT PON</option>
                  </select>
                  <button id="getOnu" class="btn btn-primary m-1">Show</button>
                </div>

                <div class="col-md-6 d-flex flex-wrap align-items-center justify-content-md-end p-0">
                  <input type="text" id="onuSearchInput" class="form-control col-md-7 m-1"
                    placeholder="Search by Name / SN (min 2 char)…">
                  <button id="btnSearchOnu" class="btn btn-info m-1" type="button">
                    <i class="fas fa-search"></i> Search
                  </button>
                  <button id="btnClearSearchOnu" class="btn btn-secondary m-1" type="button" style="display:none;">
                    Clear
                  </button>
                </div>
              </div>

              <div id="onuSearchResult" class="m-1" style="display:none;">
                <div class="card border-info">
                  <div class="card-header bg-info text-white py-2">
                    <strong>Hasil Search</strong>
                    <span id="onuSearchResultMeta" class="float-right small"></span>
                  </div>
                  <div class="card-body p-2">
                    <div class="table-responsive">
                      <table id="onu-search-table" class="table table-bordered table-striped table-sm mb-0">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>PON</th>
                            <th>ONU ID</th>
                            <th>SN</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Customer</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              <div class="table-responsive">
                <table id="onu-table" class="table table-bordered table-striped mt-4 ">

                  <thead >
                    <tr>
                      <th scope="col">#</th>
                      <th scope="col">Ont Id</th>
                      <th scope="col">SN</th>
                      <th scope="col">Model</th>
                      <th scope="col">Name</th>
                      <th scope="col">Status</th>
                      <th scope="col">Distance</th>
                      <th scope="col">Last offline</th>
                      <th scope="col">Last Online</th>
                      <th scope="col">Ont Uptime</th>
                      <th scope="col">Action</th>
                    </tr>
                  </thead>

                </table>
              </div>

            </div>
          </div>

          <div id="olt-onu-info">

          </div>

        </div>

        <div class="modal  fade" id="unconfigonu">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h4 class="modal-title">Unconfigure ONU</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                
              </div>
              <div class="modal-body">
                <input type="hidden" id="olt_id_uncfg" name="olt_id_uncfg" value="{{ $olt->id }}">
                <input type="hidden" id="olt" name="olt" value="{{ $olt->ip }}">
                <input type="hidden" id="community" name="community" value="{{ $olt->community_ro }}">


                <div class="table-responsive">
                  <table id="table-onu-unconfig" class="table table-bordered table-striped mt-4 ">

                    <thead >
                      <tr>
                        <th scope="col">#</th>
                        <th scope="col">OLT</th>
                        <th scope="col">Slot</th>
                        <th scope="col">SN</th>
                        <th scope="col">Model</th>
                        <!-- <th scope="col">Action</th> -->

                      </tr>
                    </thead>

                  </table>
                </div>

                <div class=" form-groupfloat-right m-2 " >

                  <a href="/olt/addonu/{{ $olt->id}}" class="btn btn-primary btn-sm "> Configure  </a>




                </div>

              </div>
            </div>
          </div>
        </div>


        <div class="modal  fade" id="dyinggasp">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h4 class="modal-title">Dyinggasp ONU</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                
              </div>
              <div class="modal-body">




                <div id="dyinggasp_list" >





                </div>

              </div>
            </div>
          </div>
        </div>
        <div class="modal  fade" id="offline">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h4 class="modal-title">Offline ONU</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                
              </div>
              <div class="modal-body">




                <div id="offline_list" >





                </div>

              </div>
            </div>
          </div>
        </div>
        <div class="modal  fade" id="loslist">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h4 class="modal-title">Los ONU</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                
              </div>
              <div class="modal-body">




                <div id="los_list" >





                </div>

              </div>
            </div>
          </div>
        </div>


      </div>
    </div>
  </div>
</div>
</div>
</section>

<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

</script> -->

@endsection
@section('footer-scripts')
@include('script.onu_list')
@endsection 
