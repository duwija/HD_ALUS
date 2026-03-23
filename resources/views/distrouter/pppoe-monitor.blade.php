@extends('layout.main')
@section('title','PPPoE Monitor')
@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
  .mon-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;
  }
  .mon-card-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px; flex-wrap: wrap; gap: 8px;
  }
  .mon-router-name {
    font-size: 15px; font-weight: 700; color: var(--text-primary);
    display: flex; align-items: center; gap: 6px;
  }
  .mon-badges { display: flex; flex-wrap: wrap; gap: 6px; }
  .mon-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
    cursor: pointer; text-decoration: none; transition: filter .15s;
  }
  .mon-badge:hover { filter: brightness(1.15); }
  .mb-total    { background:rgba(74,118,189,.12);   color:#4a76bd; border:1px solid rgba(74,118,189,.25); }
  .mb-active   { background:rgba(16,185,129,.12);   color:#10b981; border:1px solid rgba(16,185,129,.25); }
  .mb-offline  { background:rgba(239,68,68,.12);    color:#ef4444; border:1px solid rgba(239,68,68,.25); }
  .mb-disabled { background:rgba(107,114,128,.12);  color:#6b7280; border:1px solid rgba(107,114,128,.25); }
  .chart-wrap { position: relative; height: 160px; }
  .no-data-msg { text-align:center; color:var(--text-muted); padding:40px 0; font-size:13px; }
  .mon-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:16px; }
  .countdown-badge {
    font-size:11px; background:rgba(74,118,189,.12); color:#4a76bd;
    border:1px solid rgba(74,118,189,.25); border-radius:20px;
    padding:3px 10px; font-weight:600;
  }
  /* Map panel */
  #mapPanel {
    position: sticky;
    top: 10px;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 14px;
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-direction: column;
    height: calc(100vh - 130px);
    min-height: 420px;
  }
  #pppoe-inline-map {
    flex: 1;
    border-radius: 8px;
    overflow: hidden;
    min-height: 300px;
  }
  .map-offline-badge {
    display:inline-flex;align-items:center;gap:4px;
    padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;
    background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.25);
  }
  /* Leaflet popup dark theme */
  .leaflet-popup-content-wrapper {
    background: var(--bg-surface) !important;
    color: var(--text-primary) !important;
    border: 1px solid var(--border) !important;
    box-shadow: 0 4px 16px rgba(0,0,0,.3) !important;
  }
  .leaflet-popup-tip { background: var(--bg-surface) !important; }
  .mpopup-title { font-weight:700;font-size:12px;margin-bottom:4px; }
  .mpopup-row   { font-size:11px;color:var(--text-secondary);margin:2px 0; }
  .mpopup-badge { display:inline-block;padding:2px 7px;border-radius:20px;font-size:10px;font-weight:600;background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);margin-top:3px; }

  /* ODP marker */
  .pppoe-odp-dot {
    width: 14px; height: 14px;
    background: #f59e0b;
    border: 2px solid #fff;
    border-radius: 3px;
    box-shadow: 0 0 0 2px rgba(245,158,11,.4);
  }
  /* Map legend */
  .map-legend {
    display:flex;align-items:center;gap:12px;flex-wrap:wrap;
    font-size:11px;color:var(--text-secondary);margin-bottom:6px;
  }
  .legend-dot { width:12px;height:12px;border-radius:50%;border:2px solid #fff;display:inline-block;vertical-align:middle;margin-right:3px; }
  .legend-sq  { width:12px;height:12px;border-radius:3px;border:2px solid #fff;display:inline-block;vertical-align:middle;margin-right:3px; }
  .legend-line { width:24px;height:0;border-top:2px dashed;display:inline-block;vertical-align:middle;margin-right:3px; }

  /* Pulsing offline marker */
  @keyframes pppoe-pulse {
    0%   { box-shadow: 0 0 0 0 rgba(239,68,68,.7), 0 0 0 0 rgba(239,68,68,.4); }
    50%  { box-shadow: 0 0 0 6px rgba(239,68,68,.0), 0 0 0 10px rgba(239,68,68,.0); }
    100% { box-shadow: 0 0 0 0 rgba(239,68,68,.7), 0 0 0 0 rgba(239,68,68,.4); }
  }
  @keyframes pppoe-blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: .35; }
  }
  .pppoe-marker-dot {
    width: 14px; height: 14px;
    background: #ef4444;
    border: 2px solid #fff;
    border-radius: 50%;
    animation: pppoe-pulse 1.4s ease-out infinite, pppoe-blink 1.4s ease-in-out infinite;
  }
  /* Animated flow: parent → child */
  @keyframes dashFlow {
    from { stroke-dashoffset: 20; }
    to   { stroke-dashoffset: 0; }
  }
  path.odp-flow-line {
    animation: dashFlow 1.2s linear infinite;
    stroke-linecap: round;
  }
  @keyframes legendFlow {
    from { stroke-dashoffset: 10; }
    to   { stroke-dashoffset: 0; }
  }
  .legend-flow-line { animation: legendFlow 1.2s linear infinite; }
</style>

<div class="container-fluid">

  <div class="mon-header">
    <div>
      <h5 style="margin:0;font-size:16px;font-weight:700;color:var(--text-primary)">
        <i class="fas fa-chart-line mr-2" style="color:var(--brand)"></i>PPPoE Monitor
      </h5>
      <div style="font-size:12px;color:var(--text-muted)">Update otomatis setiap 3 menit &bull; Tampil: <span id="rangeLabel">2 jam terakhir</span></div>
    </div>
    <div class="d-flex align-items-center" style="gap:10px">
      <select id="rangeSelect" class="form-control form-control-sm" style="width:auto;background:var(--input-bg);color:var(--text-primary);border-color:var(--input-border)">
        <option value="1">1 Jam</option>
        <option value="2" selected>2 Jam</option>
        <option value="3">3 Jam</option>
        <option value="6">6 Jam</option>
        <option value="24">24 Jam</option>
        <option value="48">48 Jam</option>
        <option value="168">7 Hari</option>
      </select>
      <button id="btnRefresh" class="btn btn-sm btn-outline-primary" style="border-radius:8px">
        <i class="fas fa-sync-alt mr-1"></i>Refresh
        <span id="countdown" class="countdown-badge ml-1">180s</span>
      </button>
      <a href="/distrouter" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
        <i class="fas fa-server mr-1"></i>Router List
      </a>
    </div>
  </div>

  <div class="row" style="margin:0 -8px">

    {{-- LEFT: Charts --}}
    <div class="col-xl-7 col-lg-7 col-12" style="padding:0 8px">
      <div id="monitorGrid" class="row">
        <div class="col-12 text-center py-5" style="color:var(--text-muted)">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <div class="mt-2">Memuat data...</div>
        </div>
      </div>
    </div>

    {{-- RIGHT: Offline Map --}}
    <div class="col-xl-5 col-lg-5 col-12" style="padding:0 8px">
      <div id="mapPanel">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px">
          <span style="font-size:14px;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:6px">
            <i class="fas fa-map-marked-alt" style="color:#ef4444;font-size:13px"></i>PPPoE Offline Map
          </span>
          <div style="display:flex;align-items:center;gap:6px">
            <select id="mapRouterFilter" class="form-control form-control-sm" style="width:auto;background:var(--input-bg);color:var(--text-primary);border-color:var(--input-border)">
              <option value="">Semua Router</option>
            </select>
            <button id="btnRefreshMap" class="btn btn-sm btn-outline-danger" style="border-radius:8px" title="Refresh map">
              <i class="fas fa-sync-alt"></i>
            </button>
          </div>
        </div>
        <div style="margin-bottom:8px;min-height:18px">
          <span id="mapStatBadge" class="map-offline-badge" style="display:none">
            <i class="fas fa-circle" style="font-size:7px"></i><span id="mapCount">0</span> titik offline
          </span>
          <span id="mapLoadingText" style="font-size:11px;color:var(--text-muted);display:none">
            <i class="fas fa-spinner fa-spin mr-1"></i>Menghubungi router...
          </span>
        </div>
        <div class="map-legend">
          <span><span class="legend-dot" style="background:#ef4444"></span>Pelanggan Offline</span>
          <span><span class="legend-sq" style="background:#f59e0b"></span>ODP / Dispoint</span>
          <span><span class="legend-line" style="border-color:#f59e0b"></span>Pelanggan → ODP</span>
          <span>
            <svg width="28" height="10" style="vertical-align:middle;margin-right:3px;overflow:visible">
              <path class="legend-flow-line" d="M0,5 L28,5"
                stroke="#60a5fa" stroke-width="2.5"
                stroke-dasharray="6,4" stroke-linecap="round" fill="none"/>
              <polygon points="24,2 28,5 24,8" fill="#60a5fa"/>
            </svg>Aliran Parent &rarr; Child ODP
          </span>
        </div>
        <div style="position:relative;flex:1;display:flex;flex-direction:column">
          <div id="pppoe-inline-map"></div>
        </div>
      </div>
    </div>

  </div>

</div>

{{-- Detail Chart Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content" style="background:var(--bg-surface);color:var(--text-primary);border:1px solid var(--border)">
      <div class="modal-header" style="border-color:var(--border)">
        <h5 class="modal-title" id="detailModalTitle"><i class="fas fa-chart-bar mr-2"></i>Detail</h5>
        <div class="ml-auto mr-3">
          <select id="detailHoursSelect" class="form-control form-control-sm" style="width:auto;background:var(--input-bg);color:var(--text-primary);border-color:var(--input-border)">
            <option value="3">3 Jam</option>
            <option value="6">6 Jam</option>
            <option value="24" selected>24 Jam</option>
            <option value="48">48 Jam</option>
            <option value="168">7 Hari</option>
          </select>
        </div>
        <button type="button" class="close" data-dismiss="modal" style="color:var(--text-primary)">&times;</button>
      </div>
      <div class="modal-body" id="detailChartWrap" style="min-height:360px">
        <div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--brand)"></i></div>
      </div>
      <div class="modal-footer" style="border-color:var(--border)">
        <span id="detailStats" class="mr-auto"></span>
        <input type="hidden" id="detailHours" value="24">
        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

{{-- User List Modal --}}
<div class="modal fade" id="userListModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content" style="background:var(--bg-surface);color:var(--text-primary);border:1px solid var(--border)">
      <div class="modal-header" style="border-color:var(--border)">
        <h5 class="modal-title" id="userListTitle"><i class="fas fa-users mr-2"></i>User List</h5>
        <button type="button" class="close" data-dismiss="modal" style="color:var(--text-primary)">&times;</button>
      </div>
      <div class="modal-body" id="userListBody" style="max-height:65vh;overflow-y:auto">
        <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--brand)"></i></div>
      </div>
      <div class="modal-footer" style="border-color:var(--border)">
        <span id="userListCount" class="mr-auto" style="font-size:12px;color:var(--text-muted)"></span>
        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
  var charts = {};
  var AUTO = 180; // 3 menit
  var countdown = AUTO;

  var COLORS = {
    total:    { border:'#4a76bd', bg:'rgba(74,118,189,.15)' },
    active:   { border:'#10b981', bg:'rgba(16,185,129,.15)' },
    offline:  { border:'#ef4444', bg:'rgba(239,68,68,.15)'  },
    disabled: { border:'#9ca3af', bg:'rgba(156,163,175,.15)'},
  };

  function buildDataset(label, key, rows){
    return {
      label: label,
      data: rows,
      borderColor: COLORS[key].border,
      backgroundColor: COLORS[key].bg,
      borderWidth: 2,
      pointRadius: 0,
      pointHoverRadius: 4,
      fill: true,
      tension: 0.4,
    };
  }

  function renderCard(r){
    var l = r.latest || {};
    var noData = !r.labels || r.labels.length === 0;

    var rid = r.id;
    var rname = r.name;
    var badgeHtml = '';
    if(!noData){
      badgeHtml  = '<span class="mon-badge mb-total" title="Total PPPoE users"><i class="fas fa-circle" style="font-size:7px"></i>Total: '+(l.total||0)+'</span>';
      badgeHtml += '<span class="mon-badge mb-active show-users" data-router-id="'+rid+'" data-router-name="'+rname+'" data-status="active" title="Klik untuk lihat user aktif"><i class="fas fa-circle" style="font-size:7px"></i>Aktif: '+(l.active||0)+'</span>';
      badgeHtml += '<span class="mon-badge mb-offline show-users" data-router-id="'+rid+'" data-router-name="'+rname+'" data-status="offline" title="Klik untuk lihat user offline"><i class="fas fa-circle" style="font-size:7px"></i>Offline: '+(l.offline||0)+'</span>';
      badgeHtml += '<span class="mon-badge mb-disabled show-users" data-router-id="'+rid+'" data-router-name="'+rname+'" data-status="disabled" title="Klik untuk lihat user disabled"><i class="fas fa-circle" style="font-size:7px"></i>Disabled: '+(l.disabled||0)+'</span>';
    }

    var chartContent = noData
      ? '<div class="no-data-msg"><i class="fas fa-database" style="font-size:24px;opacity:.3;display:block;margin-bottom:8px"></i>Belum ada data terkumpul</div>'
      : '<div class="chart-wrap"><canvas id="chart-'+r.id+'"></canvas></div>';

    var lastAt = l.at ? '<span style="font-size:11px;color:var(--text-muted)">Update: '+l.at+'</span>' : '';
    var detailBtn = !noData
      ? '<button class="btn btn-xs btn-outline-primary show-detail" data-router-id="'+rid+'" data-router-name="'+rname+'" style="border-radius:6px;font-size:11px;padding:2px 9px;margin-left:6px" title="Lihat 24 jam terakhir"><i class="fas fa-chart-bar mr-1"></i>Detail</button>'
      : '';

    return '<div class="col-xl-6 col-12">'
      + '<div class="mon-card">'
      + '<div class="mon-card-header">'
      +   '<span class="mon-router-name"><i class="fas fa-server" style="color:var(--brand);font-size:13px"></i>'+r.name+detailBtn+'</span>'
      +   lastAt
      + '</div>'
      + '<div class="mon-badges mb-2">'+badgeHtml+'</div>'
      + chartContent
      + '</div></div>';
  }

  function drawChart(r){
    if(!r.labels || r.labels.length === 0) return;
    var ctx = document.getElementById('chart-'+r.id);
    if(!ctx) return;
    if(charts[r.id]) { charts[r.id].destroy(); delete charts[r.id]; }
    charts[r.id] = new Chart(ctx, {
      type: 'line',
      data: {
        labels: r.labels,
        datasets: [
          buildDataset('Total',    'total',    r.total),
          buildDataset('Aktif',    'active',   r.active),
          buildDataset('Offline',  'offline',  r.offline),
          buildDataset('Disabled', 'disabled', r.disabled),
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode:'index', intersect:false },
        plugins: {
          legend: { position:'top', labels:{ boxWidth:10, font:{size:11}, color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary') || '#888' }},
          tooltip: { mode:'index', intersect:false },
        },
        scales: {
          x: { ticks:{ maxTicksLimit:12, color:'#888', font:{size:10} }, grid:{color:'rgba(128,128,128,.1)'} },
          y: { ticks:{ color:'#888', font:{size:10} }, grid:{color:'rgba(128,128,128,.1)'}, beginAtZero:true },
        }
      }
    });
  }

  function loadData(){
    var hours = $('#rangeSelect').val();
    $.getJSON('/pppoe-monitor/data?hours='+hours, function(data){
      if(!data || !data.length){
        $('#monitorGrid').html(
          '<div class="col-12 text-center py-5" style="color:var(--text-muted)">'
          +'<i class="fas fa-database" style="font-size:32px;opacity:.3;display:block;margin-bottom:10px"></i>'
          +'Belum ada data. Pastikan scheduler Laravel berjalan dan router dapat dijangkau.'
          +'</div>'
        );
        return;
      }
      var html = '';
      data.forEach(function(r){ html += renderCard(r); });
      $('#monitorGrid').html(html);
      data.forEach(function(r){ drawChart(r); });
      // Populate map router filter
      if(window.populateMapRouters) window.populateMapRouters(data.map(function(r){ return {id:r.id, name:r.name}; }));
    }).fail(function(){
      $('#monitorGrid').html('<div class="col-12"><div class="alert alert-danger">Gagal memuat data.</div></div>');
    });
  }

  $(document).ready(function(){
    loadData();

    $('#btnRefresh').on('click', function(){
      loadData();
      countdown = AUTO;
    });

    $('#rangeSelect').on('change', function(){
      // update subtitle label
      var txt = $('#rangeSelect option:selected').text();
      $('#rangeLabel').text(txt.toLowerCase()+' terakhir');
      loadData();
    });

    // Detail modal: 24h chart per router
    $(document).on('click', '.show-detail', function(){
      var rid   = $(this).data('router-id');
      var rname = $(this).data('router-name');
      $('#detailModalTitle').text(rname + ' — 24 Jam Terakhir');
      $('#detailChartWrap').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--brand)"></i></div>');
      $('#detailHours').val(24);
      $('#detailModal').modal('show');
      loadDetailChart(rid, 24);
      $('#detailHoursSelect').val(24);
      $('#detailHoursSelect').off('change').on('change', function(){
        loadDetailChart(rid, $(this).val());
      });
    });

    var detailChart = null;
    function loadDetailChart(rid, hours){
      $.getJSON('/pppoe-monitor/data?hours='+hours, function(data){
        var r = null;
        for(var i=0;i<data.length;i++){ if(data[i].id == rid){ r=data[i]; break; } }
        if(!r || !r.labels || !r.labels.length){
          $('#detailChartWrap').html('<div class="text-center py-5" style="color:var(--text-muted)">Tidak ada data untuk rentang ini.</div>');
          return;
        }
        $('#detailChartWrap').html('<canvas id="detailCanvas" style="width:100%;height:320px"></canvas>');
        if(detailChart){ detailChart.destroy(); detailChart=null; }
        var ctx = document.getElementById('detailCanvas');
        detailChart = new Chart(ctx, {
          type:'line',
          data:{
            labels: r.labels,
            datasets:[
              buildDataset('Total','total',r.total),
              buildDataset('Aktif','active',r.active),
              buildDataset('Offline','offline',r.offline),
              buildDataset('Disabled','disabled',r.disabled),
            ]
          },
          options:{
            responsive:true, maintainAspectRatio:false,
            interaction:{mode:'index',intersect:false},
            plugins:{
              legend:{position:'top',labels:{boxWidth:10,font:{size:11},color:getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')||'#888'}},
              tooltip:{mode:'index',intersect:false},
            },
            scales:{
              x:{ticks:{maxTicksLimit:18,color:'#888',font:{size:10}},grid:{color:'rgba(128,128,128,.1)'}},
              y:{ticks:{color:'#888',font:{size:10}},grid:{color:'rgba(128,128,128,.1)'},beginAtZero:true},
            }
          }
        });
        // Show latest stats in modal footer
        var l = r.latest||{};
        $('#detailStats').html(
          '<span style="margin-right:8px;font-size:12px">Total: <b>'+(l.total||0)+'</b></span>'
          +'<span style="color:#10b981;margin-right:8px;font-size:12px">Aktif: <b>'+(l.active||0)+'</b></span>'
          +'<span style="color:#ef4444;margin-right:8px;font-size:12px">Offline: <b>'+(l.offline||0)+'</b></span>'
          +'<span style="color:#6b7280;font-size:12px">Disabled: <b>'+(l.disabled||0)+'</b></span>'
        );
      }).fail(function(){
        $('#detailChartWrap').html('<div class="alert alert-danger">Gagal memuat data.</div>');
      });
    }

    // User list modal
    var STATUS_LABELS = { active:'Aktif (Online)', offline:'Offline', disabled:'Disabled' };
    var STATUS_COLORS = { active:'#10b981', offline:'#ef4444', disabled:'#6b7280' };

    $(document).on('click', '.show-users', function(){
      var routerId   = $(this).data('router-id');
      var routerName = $(this).data('router-name');
      var status     = $(this).data('status');
      var label      = STATUS_LABELS[status] || status;
      var color      = STATUS_COLORS[status] || '#888';

      $('#userListTitle').html('<i class="fas fa-circle mr-2" style="color:'+color+';font-size:10px"></i>'+routerName+' — '+label);
      $('#userListCount').text('');
      $('#userListBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--brand)"></i></div>');
      $('#userListModal').modal('show');

      $.getJSON('/distrouter/getrouterinfo/'+routerId, function(data){
        var list = [];
        if(status === 'active')   list = data.onlineUsers   || [];
        if(status === 'offline')  list = data.offlineUsers  || [];
        if(status === 'disabled') list = data.disabledUsers || [];

        $('#userListCount').text(list.length + ' user');

        if(!list.length){
          $('#userListBody').html('<div class="text-center text-muted py-4">Tidak ada user.</div>');
          return;
        }

        // Search box + list
        var html = '<input type="search" id="userSearchBox" class="form-control form-control-sm mb-3" placeholder="Cari username..." style="background:var(--input-bg);color:var(--text-primary);border-color:var(--input-border)">';
        html += '<div id="userListItems">';
        list.forEach(function(u){
          var parts = u.split(' - ');
          var uname = parts[0] || u;
          var desc  = parts[1] ? '<small class="text-muted ml-1">— '+parts[1]+'</small>' : '';
          html += '<div class="user-item d-flex align-items-center py-1" style="border-bottom:1px solid var(--border);font-size:13px">';
          html += '<i class="fas fa-user mr-2" style="color:'+color+';font-size:10px"></i>';
          html += '<span class="font-weight-600">'+uname+'</span>'+desc;
          html += '</div>';
        });
        html += '</div>';
        $('#userListBody').html(html);

        // Live search
        $('#userSearchBox').on('input', function(){
          var q = $(this).val().toLowerCase();
          $('#userListItems .user-item').each(function(){
            $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
          });
        });
      }).fail(function(){
        $('#userListBody').html('<div class="alert alert-danger">Gagal mengambil data dari router.</div>');
      });
    });

    // Countdown + auto refresh
    setInterval(function(){
      countdown--;
      $('#countdown').text(countdown + 's');
      if(countdown <= 0){
        loadData();
        loadMap();
        countdown = AUTO;
      }
    }, 1000);
  });
})();

// ── Inline PPPoE Offline Map ────────────────────────────────
(function(){
  var map = L.map('pppoe-inline-map', { zoomControl: true }).setView([-2.5, 118], 5);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OSM contributors', maxZoom: 19
  }).addTo(map);

  var redIcon = L.divIcon({
    className: '',
    html: '<div class="pppoe-marker-dot"></div>',
    iconSize:[14,14], iconAnchor:[7,7], popupAnchor:[0,-10]
  });

  var odpIcon = L.divIcon({
    className: '',
    html: '<div class="pppoe-odp-dot"></div>',
    iconSize:[14,14], iconAnchor:[7,7], popupAnchor:[0,-10]
  });

  var markerLayer = L.layerGroup().addTo(map);

  window.loadMap = function(){
    var rid = document.getElementById('mapRouterFilter').value;
    var url = '/pppoe-map/data' + (rid ? '?router_id='+rid : '');

    map.invalidateSize(); // ensure correct tile/marker rendering
    document.getElementById('mapLoadingText').style.display = 'inline-flex';
    document.getElementById('mapStatBadge').style.display   = 'none';
    markerLayer.clearLayers();

    $.getJSON(url, function(data){
      document.getElementById('mapLoadingText').style.display = 'none';
      var pts = data.markers || [];
      document.getElementById('mapCount').textContent = pts.length;
      document.getElementById('mapStatBadge').style.display = pts.length ? 'inline-flex' : 'none';

      var bounds = [];
      pts.forEach(function(m){
        var ll = [m.lat, m.lng];
        bounds.push(ll);
        var popup = '<div class="mpopup-title"><i class="fas fa-user" style="margin-right:4px"></i>'+m.name+'</div>'
          +'<div class="mpopup-row"><i class="fas fa-wifi" style="width:12px;margin-right:3px"></i>'+m.pppoe+'</div>'
          +(m.phone   ? '<div class="mpopup-row"><i class="fas fa-phone" style="width:12px;margin-right:3px"></i>'+m.phone+'</div>'   : '')
          +(m.address ? '<div class="mpopup-row"><i class="fas fa-map-pin" style="width:12px;margin-right:3px"></i>'+m.address+'</div>' : '')
          +'<div class="mpopup-row"><i class="fas fa-server" style="width:12px;margin-right:3px"></i>'+m.router+'</div>'
          +(m.last_offline ? '<div class="mpopup-row" style="color:#f59e0b"><i class="fas fa-clock" style="width:12px;margin-right:3px"></i>Offline sejak: <b>'+m.last_offline+'</b></div>' : '')
          +'<span class="mpopup-badge"><i class="fas fa-exclamation-circle" style="margin-right:3px"></i>Offline</span>';
        L.marker(ll, { icon: redIcon }).bindPopup(popup, { maxWidth:220 }).addTo(markerLayer);

        // ODP marker + dashed line
        if(m.odp_lat != null && m.odp_lng != null){
          var odpll = [m.odp_lat, m.odp_lng];
          var odpPopup = '<div class="mpopup-title"><i class="fas fa-map-marker-alt" style="margin-right:4px;color:#f59e0b"></i>'+(m.odp_name||'ODP')+'</div>'
            +'<div class="mpopup-row" style="font-size:10px">Dispoint / ODP</div>';
          L.marker(odpll, { icon: odpIcon }).bindPopup(odpPopup, { maxWidth:180 }).addTo(markerLayer);
          L.polyline([ll, odpll], {
            color: '#f59e0b',
            weight: 2,
            dashArray: '6, 5',
            opacity: 0.8
          }).addTo(markerLayer);
          bounds.push(odpll);
        }
      });

      if(bounds.length === 1)       map.setView(bounds[0], 15);
      else if(bounds.length > 1)    map.fitBounds(bounds, { padding:[30,30], maxZoom:15 });

      // Draw ODP parent-child links (only ODPs connected to offline users on this map)
      var odpLinks = data.odp_links || [];
      // Build set of ODP names that appear in current offline markers
      var visibleOdpNames = {};
      pts.forEach(function(m){ if(m.odp_name) visibleOdpNames[m.odp_name] = true; });

      odpLinks.forEach(function(link){
        // Only draw if child ODP has at least one offline user visible
        if(!visibleOdpNames[link.child_name]) return;
        var childll  = [link.child_lat,  link.child_lng];
        var parentll = [link.parent_lat, link.parent_lng];

        // Parent ODP marker (blue-ish to distinguish from child ODP)
        var parentIcon = L.divIcon({
          className: '',
          html: '<div style="width:16px;height:16px;background:#3b82f6;border:2px solid #fff;border-radius:3px;box-shadow:0 0 0 2px rgba(59,130,246,.4)"></div>',
          iconSize:[16,16], iconAnchor:[8,8], popupAnchor:[0,-10]
        });
        var parentPopup = '<div class="mpopup-title"><i class="fas fa-sitemap" style="margin-right:4px;color:#3b82f6"></i>'+link.parent_name+'</div>'
          +'<div class="mpopup-row" style="font-size:10px">Parent ODP</div>';
        var childPopup = '<div class="mpopup-title"><i class="fas fa-map-marker-alt" style="margin-right:4px;color:#f59e0b"></i>'+link.child_name+'</div>'
          +'<div class="mpopup-row" style="font-size:10px">Child ODP &rarr; <b>'+link.parent_name+'</b></div>';

        // Only add parent marker once (avoid duplicates by checking layerGroup)
        var alreadyAdded = false;
        markerLayer.eachLayer(function(l){
          if(l._popup && l._popup._content && l._popup._content.indexOf(link.parent_name) !== -1
            && l._popup._content.indexOf('Parent ODP') !== -1) alreadyAdded = true;
        });
        if(!alreadyAdded) L.marker(parentll, { icon: parentIcon }).bindPopup(parentPopup, { maxWidth:180 }).addTo(markerLayer);

        // Animated flow line parent → child
        L.polyline([parentll, childll], {
          color: '#60a5fa',
          weight: 2.5,
          dashArray: '12, 8',
          opacity: 0.9,
          className: 'odp-flow-line'
        }).addTo(markerLayer);
      });
    }).fail(function(){
      document.getElementById('mapLoadingText').style.display = 'none';
    });
  };

  // Populate router dropdown from chart data when chart loads
  window.populateMapRouters = function(routers){
    var sel = document.getElementById('mapRouterFilter');
    // keep only first option
    while(sel.options.length > 1) sel.remove(1);
    routers.forEach(function(r){
      var opt = document.createElement('option');
      opt.value = r.id; opt.textContent = r.name;
      sel.appendChild(opt);
    });
  };

  document.getElementById('btnRefreshMap').addEventListener('click', window.loadMap);
  document.getElementById('mapRouterFilter').addEventListener('change', window.loadMap);

  // Initial load after layout settles
  $(document).ready(function(){
    setTimeout(function(){
      map.invalidateSize();
      window.loadMap();
    }, 800);
  });

  // Fix map size after layout settles
  setTimeout(function(){ map.invalidateSize(); }, 300);
})();
</script>
@endsection
