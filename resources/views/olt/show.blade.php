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

        <div class="col-md-12">
          <div class="card mb-3">
            <div class="card-header">

              <h5 class="card-title">ONU List</h5>
              <!-- <a data-toggle="modal" href="#unconfigonu" class="float-right badge badge-primary">Unconfig Onu </a> -->
            </div>
            <div class="card-body">
              <div class="row m-1 mb-3 ">
                <input hidden type="text" id="olt_id" name="olt_id" value="{{ $olt->id }}">

                <select id="oltPonComboBox" name="oltPonComboBox" class="form-control col-md-4 float-right m-1">
                  <option value="">Pilih OLT PON</option>
                </select> 
                <button id="getOnu" class="btn btn-primary ml-2 col-md-1 m-1">Show</button>
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
