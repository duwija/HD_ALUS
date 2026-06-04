@extends('layout.main')
@section('title','PPPoE Monitor')
@section('content')
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
  .metric-filter-wrap {
    display:inline-flex;
    align-items:center;
    flex-wrap:wrap;
    gap:8px;
    padding:6px 10px;
    border:1px solid var(--border);
    border-radius:10px;
    background:var(--bg-surface);
  }
  .metric-filter-item {
    display:inline-flex;
    align-items:center;
    gap:5px;
    margin:0;
    font-size:12px;
    color:var(--text-secondary);
    cursor:pointer;
    user-select:none;
  }
  .metric-dot {
    width:8px;
    height:8px;
    border-radius:50%;
    display:inline-block;
  }
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
      <div class="metric-filter-wrap" title="Tampilkan/sembunyikan seri chart">
        <span style="font-size:11px;color:var(--text-muted);font-weight:700">Chart:</span>
        <label class="metric-filter-item">
          <input type="checkbox" class="metricFilter" value="total" checked>
          <span class="metric-dot" style="background:#4a76bd"></span>Total
        </label>
        <label class="metric-filter-item">
          <input type="checkbox" class="metricFilter" value="active" checked>
          <span class="metric-dot" style="background:#10b981"></span>Aktif
        </label>
        <label class="metric-filter-item">
          <input type="checkbox" class="metricFilter" value="offline" checked>
          <span class="metric-dot" style="background:#ef4444"></span>Offline
        </label>
        <label class="metric-filter-item">
          <input type="checkbox" class="metricFilter" value="disabled" checked>
          <span class="metric-dot" style="background:#9ca3af"></span>Disable
        </label>
      </div>
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

    {{-- Charts --}}
    <div class="col-12" style="padding:0 8px">
      <div id="monitorGrid" class="row">
        <div class="col-12 text-center py-5" style="color:var(--text-muted)">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <div class="mt-2">Memuat data...</div>
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
<script>
(function(){
  var charts = {};
  var detailChart = null;
  var detailRouterId = null;
  var detailHours = 24;
  var monitorDataCache = [];
  var AUTO = 180; // 3 menit
  var countdown = AUTO;
  var metricFilterStorageKey = 'pppoe_monitor_metric_filters_' + window.location.host;

  var COLORS = {
    total:    { border:'#4a76bd', bg:'rgba(74,118,189,.15)' },
    active:   { border:'#10b981', bg:'rgba(16,185,129,.15)' },
    offline:  { border:'#ef4444', bg:'rgba(239,68,68,.15)'  },
    disabled: { border:'#9ca3af', bg:'rgba(156,163,175,.15)'},
  };

  function buildDataset(label, key, rows){
    var numericRows = Array.isArray(rows)
      ? rows.map(function(v) { return Number(v) || 0; })
      : [];
    return {
      label: label,
      metricKey: key,
      data: numericRows,
      borderColor: COLORS[key].border,
      backgroundColor: COLORS[key].bg,
      borderWidth: 2,
      pointRadius: 1.5,
      pointHoverRadius: 4,
      fill: false,
      tension: 0.2,
    };
  }

  function getLastSeriesValue(series){
    var normalized = toSeriesArray(series);
    if (!normalized.length) return 0;
    return Number(normalized[normalized.length - 1]) || 0;
  }

  function toSeriesArray(series){
    if (Array.isArray(series)) return series;
    if (series && typeof series === 'object') {
      return Object.keys(series)
        .sort(function(a, b){ return Number(a) - Number(b); })
        .map(function(k){ return series[k]; });
    }
    return [];
  }

  function normalizeSeriesByLabels(labels, series){
    var safeLabels = Array.isArray(labels) ? labels : [];
    var safeSeries = toSeriesArray(series);
    var normalized = [];

    for (var i = 0; i < safeLabels.length; i++) {
      normalized.push(Number(safeSeries[i]) || 0);
    }

    return normalized;
  }

  function prepareRouterSeries(r){
    var labels = Array.isArray(r.labels) ? r.labels : [];
    r.__series = {
      total: normalizeSeriesByLabels(labels, r.total),
      active: normalizeSeriesByLabels(labels, r.active),
      offline: normalizeSeriesByLabels(labels, r.offline),
      disabled: normalizeSeriesByLabels(labels, r.disabled),
    };
    return r;
  }

  function getSelectedMetricMap() {
    var selected = {};
    document.querySelectorAll('.metricFilter:checked').forEach(function(el) {
      selected[el.value] = true;
    });
    return selected;
  }

  function computeYAxisConfig(datasets) {
    var maxVal = 0;
    (datasets || []).forEach(function(ds) {
      (Array.isArray(ds.data) ? ds.data : []).forEach(function(v) {
        var n = Number(v) || 0;
        if (n > maxVal) maxVal = n;
      });
    });
    var pad = maxVal > 0 ? Math.max(1, Math.ceil(maxVal * 0.15)) : 1;
    var ymax = Math.max(1, maxVal + pad);
    var step = Math.max(1, Math.ceil(ymax / 6));
    return { min: 0, max: ymax, stepSize: step };
  }

  function buildSelectedDatasets(r) {
    var s = r.__series || { total: [], active: [], offline: [], disabled: [] };
    var selected = getSelectedMetricMap();
    var datasets = [];

    if (selected.total) datasets.push(buildDataset('Total', 'total', s.total));
    if (selected.active) datasets.push(buildDataset('Aktif', 'active', s.active));
    if (selected.offline) datasets.push(buildDataset('Offline', 'offline', s.offline));
    if (selected.disabled) datasets.push(buildDataset('Disabled', 'disabled', s.disabled));

    return datasets;
  }

  function saveMetricFilterSelection() {
    try {
      var selected = [];
      document.querySelectorAll('.metricFilter:checked').forEach(function(el) {
        selected.push(el.value);
      });
      localStorage.setItem(metricFilterStorageKey, JSON.stringify(selected));
    } catch (e) {
      // Ignore storage errors
    }
  }

  function restoreMetricFilterSelection() {
    try {
      var raw = localStorage.getItem(metricFilterStorageKey);
      if (!raw) return;
      var selected = JSON.parse(raw);
      if (!Array.isArray(selected)) return;

      var selectedMap = {};
      selected.forEach(function(key) { selectedMap[String(key)] = true; });
      document.querySelectorAll('.metricFilter').forEach(function(el) {
        el.checked = !!selectedMap[String(el.value)];
      });
    } catch (e) {
      // Ignore malformed storage data
    }
  }

  function rerenderAllCharts() {
    monitorDataCache.forEach(function(r) { drawChart(r); });
    if (detailRouterId !== null) {
      loadDetailChart(detailRouterId, detailHours);
    }
  }

  function renderCard(r){
    var l = r.latest || {};
    var s = r.__series || { total: [], active: [], offline: [], disabled: [] };
    var lastTotal = getLastSeriesValue(s.total);
    var lastActive = getLastSeriesValue(s.active);
    var lastOffline = getLastSeriesValue(s.offline);
    var lastDisabled = getLastSeriesValue(s.disabled);
    var noData = !r.labels || r.labels.length === 0;

    var rid = r.id;
    var rname = r.name;
    var badgeHtml = '';
    if(!noData){
      badgeHtml  = '<span class="mon-badge mb-total" title="Total PPPoE users"><i class="fas fa-circle" style="font-size:7px"></i>Total: '+lastTotal+'</span>';
      badgeHtml += '<span class="mon-badge mb-active show-users" data-router-id="'+rid+'" data-router-name="'+rname+'" data-status="active" title="Klik untuk lihat user aktif"><i class="fas fa-circle" style="font-size:7px"></i>Aktif: '+lastActive+'</span>';
      badgeHtml += '<span class="mon-badge mb-offline show-users" data-router-id="'+rid+'" data-router-name="'+rname+'" data-status="offline" title="Klik untuk lihat user offline"><i class="fas fa-circle" style="font-size:7px"></i>Offline: '+lastOffline+'</span>';
      badgeHtml += '<span class="mon-badge mb-disabled show-users" data-router-id="'+rid+'" data-router-name="'+rname+'" data-status="disabled" title="Klik untuk lihat user disabled"><i class="fas fa-circle" style="font-size:7px"></i>Disabled: '+lastDisabled+'</span>';
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
    var datasets = buildSelectedDatasets(r);
    var ycfg = computeYAxisConfig(datasets);
    var ctx = document.getElementById('chart-'+r.id);
    if(!ctx) return;
    if(charts[r.id]) { charts[r.id].destroy(); delete charts[r.id]; }
    charts[r.id] = new Chart(ctx, {
      type: 'line',
      data: {
        labels: r.labels,
        datasets: datasets
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
          y: {
            beginAtZero:true,
            min: ycfg.min,
            max: ycfg.max,
            ticks:{ color:'#888', font:{size:10}, stepSize: ycfg.stepSize },
            grid:{color:'rgba(128,128,128,.1)'}
          },
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
      data.forEach(function(r){
        prepareRouterSeries(r);
        html += renderCard(r);
      });
      $('#monitorGrid').html(html);
      monitorDataCache = data;
      data.forEach(function(r){ drawChart(r); });
    }).fail(function(){
      $('#monitorGrid').html('<div class="col-12"><div class="alert alert-danger">Gagal memuat data.</div></div>');
    });
  }

  $(document).ready(function(){
    // Restore checkbox state first to avoid first-render race with async data fetch.
    restoreMetricFilterSelection();
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

    function loadDetailChart(rid, hours){
      detailRouterId = rid;
      detailHours = Number(hours) || 24;
      $.getJSON('/pppoe-monitor/data?hours='+hours, function(data){
        var r = null;
        for(var i=0;i<data.length;i++){ if(data[i].id == rid){ r=data[i]; break; } }
        if(!r || !r.labels || !r.labels.length){
          $('#detailChartWrap').html('<div class="text-center py-5" style="color:var(--text-muted)">Tidak ada data untuk rentang ini.</div>');
          return;
        }
        prepareRouterSeries(r);
        var datasets = buildSelectedDatasets(r);
        var ycfg = computeYAxisConfig(datasets);
        var s = r.__series || { total: [], active: [], offline: [], disabled: [] };
        $('#detailChartWrap').html('<canvas id="detailCanvas" style="width:100%;height:320px"></canvas>');
        if(detailChart){ detailChart.destroy(); detailChart=null; }
        var ctx = document.getElementById('detailCanvas');
        detailChart = new Chart(ctx, {
          type:'line',
          data:{
            labels: r.labels,
            datasets: datasets
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
              y:{
                beginAtZero:true,
                min: ycfg.min,
                max: ycfg.max,
                ticks:{color:'#888',font:{size:10},stepSize: ycfg.stepSize},
                grid:{color:'rgba(128,128,128,.1)'}
              },
            }
          }
        });
        // Show latest stats in modal footer
        var l = r.latest||{};
        var lastTotal = getLastSeriesValue(s.total);
        var lastActive = getLastSeriesValue(s.active);
        var lastOffline = getLastSeriesValue(s.offline);
        var lastDisabled = getLastSeriesValue(s.disabled);
        $('#detailStats').html(
          '<span style="margin-right:8px;font-size:12px">Total: <b>'+lastTotal+'</b></span>'
          +'<span style="color:#10b981;margin-right:8px;font-size:12px">Aktif: <b>'+lastActive+'</b></span>'
          +'<span style="color:#ef4444;margin-right:8px;font-size:12px">Offline: <b>'+lastOffline+'</b></span>'
          +'<span style="color:#6b7280;font-size:12px">Disabled: <b>'+lastDisabled+'</b></span>'
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

    // Ensure restored filters are reflected after first render.
    setTimeout(function(){ rerenderAllCharts(); }, 0);

    $(document).on('change', '.metricFilter', function(){
      saveMetricFilterSelection();
      rerenderAllCharts();
    });

    setInterval(function(){
      countdown--;
      $('#countdown').text(countdown + 's');
      if(countdown <= 0){
        loadData();
        countdown = AUTO;
      }
    }, 1000);
  });
})();
</script>
@endsection
