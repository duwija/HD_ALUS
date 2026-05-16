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

        {{-- ============================================================
             OLT Tools — Tabbed: Search ONU | Top RX | Distance Map
             ============================================================ --}}
        <div class="col-md-12">
          <style>
            /* Distance Map — light mode (default) */
            .dm-wrap { background:#f8f9fa; color:#212529; border:1px solid #dee2e6;
                       border-radius:8px; padding:18px; }
            .dm-wrap h5 { color:#212529; margin:0; }
            .dm-wrap .dm-subtitle { color:#6c757d; font-size:13px; margin-top:4px; }
            .dm-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));
                        gap:10px; margin:16px 0; }
            .dm-stat { background:#ffffff; border:1px solid #dee2e6; border-radius:8px;
                       padding:10px; text-align:center; }
            .dm-stat-val { font-size:22px; font-weight:700; color:#0d6efd; line-height:1.1; }
            .dm-stat-lbl { font-size:11px; color:#6c757d; text-transform:uppercase; margin-top:4px; }
            .dm-card { background:#ffffff; border:1px solid #dee2e6; border-radius:8px; padding:16px; }
            .dm-legend { display:flex; gap:18px; flex-wrap:wrap; font-size:13px; margin-top:12px;
                         color:#212529; }
            .dm-legend-item { display:flex; align-items:center; gap:6px; }
            .dm-dot { width:12px; height:12px; border-radius:50%; display:inline-block; }
            .dm-insights { background:#fff8e6; border-left:4px solid #ffa94d; padding:12px 16px;
                           border-radius:4px; margin-top:16px; font-size:13px; color:#212529; }
            .dm-insights h4 { margin:0 0 8px; color:#d97706; font-size:14px; }
            .dm-insights ul { margin:4px 0 0 18px; padding:0; }
            .dm-insights li { margin:4px 0; line-height:1.5; }
            .dm-anomaly { color:#dc3545; font-weight:600; }
            .dm-ok { color:#198754; }
            .dm-btn { background:transparent; color:#0d6efd; border:1px solid #0d6efd;
                      padding:4px 12px; border-radius:4px; font-size:12px; cursor:pointer; }
            .dm-btn:hover { background:#0d6efd; color:#ffffff; }
            .dm-btn:disabled { opacity:0.5; cursor:not-allowed; }
            .dm-meta { color:#6c757d; }

            /* ONU info popup */
            .dm-info { display:none; margin-top:14px; padding:14px 16px; border-radius:8px;
                       border:1px solid #dee2e6; background:#ffffff; position:relative; }
            .dm-info-close { position:absolute; top:8px; right:12px; cursor:pointer;
                             background:transparent; border:0; font-size:20px; line-height:1;
                             color:#6c757d; }
            .dm-info-close:hover { color:#212529; }
            .dm-info-title { font-size:15px; font-weight:600; margin-bottom:10px; }
            .dm-info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
                            gap:10px 18px; font-size:13px; }
            .dm-info-item .dm-info-lbl { font-size:11px; color:#6c757d; text-transform:uppercase;
                                          letter-spacing:0.5px; }
            .dm-info-item .dm-info-val { font-size:14px; font-weight:500; word-break:break-all; }
            .dm-info-actions { margin-top:12px; display:flex; gap:8px; flex-wrap:wrap; }

            /* Distance Map — dark mode overrides */
            body.dark-mode .dm-wrap { background:#1a1d21; color:#e4e6eb; border-color:#3a3b3c; }
            body.dark-mode .dm-wrap h5 { color:#e4e6eb; }
            body.dark-mode .dm-wrap .dm-subtitle { color:#8a8d91; }
            body.dark-mode .dm-stat { background:#242526; border-color:#3a3b3c; }
            body.dark-mode .dm-stat-val { color:#4dabf7; }
            body.dark-mode .dm-stat-lbl { color:#8a8d91; }
            body.dark-mode .dm-card { background:#242526; border-color:#3a3b3c; }
            body.dark-mode .dm-legend { color:#e4e6eb; }
            body.dark-mode .dm-insights { background:#2a2d31; color:#e4e6eb; }
            body.dark-mode .dm-insights h4 { color:#ffa94d; }
            body.dark-mode .dm-anomaly { color:#ff6b6b; }
            body.dark-mode .dm-ok { color:#51cf66; }
            body.dark-mode .dm-btn { color:#4dabf7; border-color:#4dabf7; }
            body.dark-mode .dm-btn:hover { background:#4dabf7; color:#1a1d21; }
            body.dark-mode .dm-meta { color:#8a8d91; }
            body.dark-mode .dm-info { background:#242526; border-color:#3a3b3c; color:#e4e6eb; }
            body.dark-mode .dm-info-close { color:#8a8d91; }
            body.dark-mode .dm-info-close:hover { color:#e4e6eb; }
            body.dark-mode .dm-info-item .dm-info-lbl { color:#8a8d91; }
          </style>

          <div class="card mb-3">
            <div class="card-header p-0">
              <ul class="nav nav-tabs card-header-tabs m-0" id="oltToolsTabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="tab-search-tab" data-toggle="tab" href="#tab-search" role="tab">
                    <i class="fas fa-search"></i> Search ONU
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="tab-toprx-tab" data-toggle="tab" href="#tab-toprx" role="tab">
                    <i class="fas fa-heartbeat text-danger"></i> Top 10 RX Terburuk
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="tab-distmap-tab" data-toggle="tab" href="#tab-distmap" role="tab">
                    <i class="fas fa-map-marked-alt text-primary"></i> Distance Map
                  </a>
                </li>
              </ul>
            </div>
            <div class="card-body">
              <div class="tab-content" id="oltToolsTabsContent">

                {{-- Tab 1: Search ONU --}}
                <div class="tab-pane fade show active" id="tab-search" role="tabpanel">
                  <div class="d-flex flex-wrap align-items-center mb-3">
                    <input type="text" id="onuSearchInput" class="form-control mr-2 mb-1"
                      placeholder="Search by Name / SN (min 2 char)…" style="max-width:360px;">
                    <button id="btnSearchOnu" class="btn btn-info mr-2 mb-1" type="button">
                      <i class="fas fa-search"></i> Search
                    </button>
                    <button id="btnClearSearchOnu" class="btn btn-secondary mb-1" type="button" style="display:none;">
                      Clear
                    </button>
                  </div>
                  <div id="onuSearchResult" style="display:none;">
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
                  <div id="onuSearchEmpty" class="text-muted text-center p-3">
                    Ketik nama customer atau Serial Number ONU lalu klik <strong>Search</strong>.
                  </div>
                </div>

                {{-- Tab 2: Top 10 RX --}}
                <div class="tab-pane fade" id="tab-toprx" role="tabpanel">
                  <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <div>
                      <strong><i class="fas fa-heartbeat text-danger"></i> Top 10 ONU dengan RX Power terburuk</strong>
                      <small id="rxHealthMeta" class="text-muted ml-2"></small>
                    </div>
                    <button id="btnRefreshRxHealth" type="button" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                  </div>
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

                {{-- Tab 3: Distance Map --}}
                <div class="tab-pane fade" id="tab-distmap" role="tabpanel">
                  <div class="dm-wrap">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                      <div>
                        <h5>
                          <i class="fas fa-map-marked-alt" style="color:#4dabf7;"></i>
                          ONU Distance Map &mdash; {{ $olt->name ?? 'OLT' }}
                        </h5>
                        <div class="dm-subtitle">
                          Visualisasi sebaran ONU berdasarkan jarak fiber vs RX power.
                          Hover titik untuk detail · Klik titik untuk lihat info ONU.
                        </div>
                      </div>
                      <div class="text-right">
                        <small id="distMapMeta" class="dm-meta" style="display:block; margin-bottom:4px;"></small>
                        <button id="btnRefreshDistMap" type="button" class="dm-btn">
                          <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                      </div>
                    </div>

                    <div id="distMapSpinner" class="text-center p-4" style="display:none;">
                      <i class="fas fa-spinner fa-spin fa-2x" style="color:#4dabf7;"></i>
                      <div class="small mt-2 dm-meta">Walking SNMP distance + RX power…</div>
                    </div>

                    <div id="distMapStats" class="dm-stats" style="display:none;">
                      <div class="dm-stat"><div class="dm-stat-val" id="dmsTotal">0</div><div class="dm-stat-lbl">Total ONU</div></div>
                      <div class="dm-stat"><div class="dm-stat-val" id="dmsHealthy" style="color:#51cf66;">0</div><div class="dm-stat-lbl">Sehat</div></div>
                      <div class="dm-stat"><div class="dm-stat-val" id="dmsWarn"    style="color:#ffd43b;">0</div><div class="dm-stat-lbl">Warning</div></div>
                      <div class="dm-stat"><div class="dm-stat-val" id="dmsCrit"    style="color:#ff6b6b;">0</div><div class="dm-stat-lbl">Kritis</div></div>
                      <div class="dm-stat"><div class="dm-stat-val" id="dmsAvgRx">—</div><div class="dm-stat-lbl">Avg RX (dBm)</div></div>
                      <div class="dm-stat"><div class="dm-stat-val" id="dmsAvgDist">—</div><div class="dm-stat-lbl">Avg Distance</div></div>
                    </div>

                    <div id="distMapEmpty" class="dm-card text-center dm-meta">
                      Klik <strong>Refresh</strong> untuk memuat data dari OLT via SNMP.
                    </div>

                    <div id="distMapChartBox" class="dm-card" style="display:none;">
                      <div style="position:relative; height:500px;">
                        <canvas id="distanceMapChart"></canvas>
                      </div>
                      <div class="dm-legend">
                        <div class="dm-legend-item"><span class="dm-dot" style="background:#51cf66;"></span> Sehat (RX &gt; -25 dBm)</div>
                        <div class="dm-legend-item"><span class="dm-dot" style="background:#ffd43b;"></span> Warning (-25 s/d -27 dBm)</div>
                        <div class="dm-legend-item"><span class="dm-dot" style="background:#ff6b6b;"></span> Kritis (≤ -27 dBm)</div>
                        <div class="dm-legend-item"><span style="display:inline-block;width:24px;height:2px;background:#4dabf7;"></span> Trend regresi linear (expected RX)</div>
                      </div>
                    </div>

                    {{-- ONU detail popup on click --}}
                    <div id="distMapOnuInfo" class="dm-info">
                      <button type="button" class="dm-info-close" id="dmInfoClose" aria-label="Close">&times;</button>
                      <div class="dm-info-title" id="dmInfoTitle">ONU Info</div>
                      <div class="dm-info-grid">
                        <div class="dm-info-item">
                          <div class="dm-info-lbl">Name</div>
                          <div class="dm-info-val" id="dmInfoName">-</div>
                        </div>
                        <div class="dm-info-item">
                          <div class="dm-info-lbl">Serial Number</div>
                          <div class="dm-info-val" id="dmInfoSn">-</div>
                        </div>
                        <div class="dm-info-item">
                          <div class="dm-info-lbl">PON / ONU ID</div>
                          <div class="dm-info-val" id="dmInfoPon">-</div>
                        </div>
                        <div class="dm-info-item">
                          <div class="dm-info-lbl">RX Power</div>
                          <div class="dm-info-val" id="dmInfoRx">-</div>
                        </div>
                        <div class="dm-info-item">
                          <div class="dm-info-lbl">Distance</div>
                          <div class="dm-info-val" id="dmInfoDist">-</div>
                        </div>
                        <div class="dm-info-item">
                          <div class="dm-info-lbl">Customer</div>
                          <div class="dm-info-val" id="dmInfoCustomer">-</div>
                        </div>
                      </div>
                      <div class="dm-info-actions">
                        <button type="button" class="dm-btn" id="dmInfoJump">
                          <i class="fas fa-external-link-alt"></i> Jump ke PON
                        </button>
                      </div>
                    </div>

                    <div id="distMapInsights" class="dm-insights" style="display:none;">
                      <h4>🔍 Auto-Insights</h4>
                      <ul id="distMapInsightsList"></ul>
                    </div>
                  </div>
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
              <div class="row m-1 mb-3 align-items-center">
                <input hidden type="text" id="olt_id" name="olt_id" value="{{ $olt->id }}">

                <div class="col-md-12 d-flex flex-wrap align-items-center p-0">
                  <select id="oltPonComboBox" name="oltPonComboBox" class="form-control col-md-4 m-1">
                    <option value="">Pilih OLT PON</option>
                  </select>
                  <button id="getOnu" class="btn btn-primary m-1">Show</button>
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
