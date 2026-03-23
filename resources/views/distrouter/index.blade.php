@extends('layout.main')
@section('title','Distribution Router')
@section('content')
<style>
  .dr-stat {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 14px 18px;
    box-shadow: var(--shadow-sm);
    position: relative; overflow: hidden;
  }
  .dr-stat-icon {
    position: absolute; right: 14px; top: 50%;
    transform: translateY(-50%);
    font-size: 30px; opacity: .08; color: var(--brand);
  }
  .dr-stat-num { font-size: 28px; font-weight: 800; color: var(--text-primary); line-height: 1; }
  .dr-stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); margin-top: 3px; }
  .dr-stat-sub  { font-size: 12px; color: var(--text-secondary); margin-top: 3px; }

  /* router cards */
  .router-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 18px;
    box-shadow: var(--shadow-sm);
    transition: box-shadow .2s;
    height: 100%;
  }
  .router-card:hover { box-shadow: var(--shadow-md); }
  .router-card-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px; gap: 8px;
  }
  .router-name {
    font-size: 15px; font-weight: 700;
    color: var(--text-primary); text-decoration: none !important;
    display: flex; align-items: center; gap: 6px;
  }
  .router-name:hover { color: var(--brand) !important; }
  .router-ip {
    font-size: 12px; color: var(--text-muted);
    display: flex; align-items: center; gap: 4px; margin-bottom: 10px;
  }
  .router-ip a { color: var(--text-secondary); text-decoration: none; }
  .router-ip a:hover { color: var(--brand); text-decoration: underline; }

  .ppp-badges { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px; }
  .ppp-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
  }
  .ppp-total    { background: rgba(74,118,189,.12);  color: #4a76bd; border: 1px solid rgba(74,118,189,.25); }
  .ppp-active   { background: rgba(16,185,129,.12);  color: #10b981; border: 1px solid rgba(16,185,129,.25); }
  .ppp-offline  { background: rgba(239,68,68,.12);   color: #ef4444; border: 1px solid rgba(239,68,68,.25); }
  .ppp-disabled { background: rgba(107,114,128,.12); color: #6b7280; border: 1px solid rgba(107,114,128,.25); }

  .router-actions { display: flex; gap: 6px; margin-top: 10px; }

  /* modal dark mode */
  #routerDetailModal .modal-content {
    background: var(--bg-surface) !important;
    color: var(--text-primary) !important;
    border: 1px solid var(--border);
  }
  #routerDetailModal .modal-header { border-color: var(--border) !important; }
  #routerDetailModal .modal-footer { border-color: var(--border) !important; }
  #routerDetailModal h6 { color: var(--text-secondary); font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }

  /* search bar */
  #routerSearch {
    background: var(--input-bg) !important;
    border-color: var(--input-border) !important;
    color: var(--text-primary) !important;
  }
</style>

@php
  $totalRouters = count($distrouter);
@endphp

<div class="container-fluid">

  {{-- HEADER --}}
  <div class="d-flex align-items-center justify-content-between mb-3" style="gap:12px">
    <div>
      <h5 style="font-size:16px;font-weight:700;color:var(--text-primary);margin:0">
        <i class="fas fa-server mr-2" style="color:var(--brand)"></i>Distribution Router
      </h5>
      <div style="font-size:12px;color:var(--text-muted)">{{ $totalRouters }} router terdaftar</div>
    </div>
    <div class="d-flex" style="gap:8px">
      <div class="input-group input-group-sm" style="width:240px">
        <input id="routerSearch" type="search" class="form-control" placeholder="Cari nama / IP...">
        <div class="input-group-append">
          <button id="clearSearch" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      <button id="refreshAll" class="btn btn-sm btn-outline-primary" style="border-radius:8px">
        <i class="fas fa-sync-alt mr-1"></i>Refresh All
        <span id="auto-countdown" class="badge badge-light ml-1" style="font-size:10px;background:rgba(0,0,0,.08)">60s</span>
      </button>
      <a href="{{ url('pppoe-monitor') }}" class="btn btn-sm btn-outline-success" style="border-radius:8px">
        <i class="fas fa-chart-line mr-1"></i>Monitor
      </a>
      <a href="{{ url('distrouter/create') }}" class="btn btn-sm btn-primary" style="border-radius:8px">
        <i class="fas fa-plus mr-1"></i>Tambah Router
      </a>
    </div>
  </div>

  {{-- ROUTER CARDS GRID --}}
  <div class="row" id="routerGrid">
    @foreach($distrouter as $router)
    <div class="col-xl-3 col-lg-4 col-md-6 mb-3 router-row"
         data-name="{{ strtolower($router->name) }}"
         data-ip="{{ $router->ip }}">
      <div class="router-card">
        <div class="router-card-header">
          <a href="/distrouter/{{ $router->id }}" class="router-name">
            <i class="fas fa-server" style="color:var(--brand);font-size:13px"></i>
            {{ $router->name }}
          </a>
          <div class="d-flex" style="gap:4px">
            <button class="btn btn-sm refresh-router"
                    data-id="{{ $router->id }}"
                    title="Refresh"
                    style="background:var(--bg-surface-2);border:1px solid var(--border);border-radius:6px;padding:3px 7px;color:var(--text-secondary)">
              <i class="fas fa-sync-alt" style="font-size:11px"></i>
            </button>
            <a href="{{ url('distrouter/'.$router->id.'/edit') }}"
               title="Edit"
               style="background:var(--bg-surface-2);border:1px solid var(--border);border-radius:6px;padding:3px 7px;color:var(--text-secondary);display:inline-flex;align-items:center">
              <i class="fas fa-edit" style="font-size:11px"></i>
            </a>
          </div>
        </div>

        <div class="router-ip">
          <i class="fas fa-network-wired" style="font-size:10px"></i>
          <a href="{{ 'http://'.$router->ip.':'.$router->web }}" target="_blank" rel="noopener">
            {{ $router->ip }}
          </a>
          <span style="color:var(--border)">|</span>
          <span>Port: {{ $router->port }}</span>
        </div>

        <div id="pppoe-{{ $router->id }}" class="ppp-badges">
          <span class="ppp-badge ppp-total"><i class="fas fa-circle" style="font-size:7px"></i>Loading...</span>
        </div>

        @if($router->note)
        <div style="font-size:11px;color:var(--text-muted);border-top:1px solid var(--border);padding-top:8px;margin-top:4px">
          <i class="fas fa-sticky-note mr-1"></i>{{ $router->note }}
        </div>
        @endif

        <div class="router-actions">
          <button class="btn btn-sm show-detail flex-fill"
                  data-id="{{ $router->id }}"
                  style="background:var(--brand-light);color:var(--brand);border:1px solid rgba(163,48,28,.2);border-radius:8px;font-size:12px;font-weight:600">
            <i class="fas fa-list mr-1"></i>Detail Users
          </button>
        </div>
      </div>
    </div>
    @endforeach
  </div>

  <div id="noResult" class="text-center py-4" style="display:none;color:var(--text-muted)">
    <i class="fas fa-search" style="font-size:24px;opacity:.3;display:block;margin-bottom:8px"></i>
    Tidak ditemukan router
  </div>

</div>

{{-- DETAIL MODAL --}}
<div class="modal fade" id="routerDetailModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="routerDetailModalLabel"><i class="fas fa-server mr-2" style="color:var(--brand)"></i>Router Detail</h5>
        <button type="button" class="close" data-dismiss="modal" style="color:var(--text-primary)">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="routerDetailContent">
          <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--brand)"></i></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal" style="border-radius:8px">Tutup</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('footer-scripts')
<script>
(function(){
  function renderBadges(container, data){
    var a = data.pppActiveCount   || 0;
    var t = data.pppUserCount     || 0;
    var o = data.pppOfflineCount  || 0;
    var d = data.pppDisabledCount || 0;
    var html = '';
    html += '<span class="ppp-badge ppp-total"><i class="fas fa-circle" style="font-size:7px"></i>Total: '+t+'</span>';
    html += '<span class="ppp-badge ppp-active"><i class="fas fa-circle" style="font-size:7px"></i>Aktif: '+a+'</span>';
    html += '<span class="ppp-badge ppp-offline"><i class="fas fa-circle" style="font-size:7px"></i>Offline: '+o+'</span>';
    html += '<span class="ppp-badge ppp-disabled"><i class="fas fa-circle" style="font-size:7px"></i>Disabled: '+d+'</span>';
    $(container).html(html);
  }

  function fetchRouterInfo(id, cb){
    var $el = $('#pppoe-'+id);
    $el.html('<span style="color:var(--text-muted);font-size:12px"><i class="fas fa-spinner fa-spin mr-1"></i>Loading...</span>');
    $.ajax({ url: '/distrouter/getrouterinfo/'+id, method: 'GET', dataType: 'json' })
      .done(function(resp){
        if(resp && resp.success){ renderBadges($el, resp); if(cb) cb(resp); }
        else $el.html('<span class="ppp-badge ppp-disabled">No data</span>');
      })
      .fail(function(){ $el.html('<span class="ppp-badge ppp-offline"><i class="fas fa-exclamation-triangle mr-1"></i>Error</span>'); });
  }

  function debounce(fn, d){ var t; return function(){ clearTimeout(t); var a=arguments; t=setTimeout(function(){ fn.apply(null,a); },d); }; }

  $(document).ready(function(){
    // Auto-fetch all
    @foreach($distrouter as $router)
      fetchRouterInfo({{ $router->id }});
    @endforeach

    // Refresh individual
    $(document).on('click', '.refresh-router', function(){
      fetchRouterInfo($(this).data('id'));
    });

    // Refresh all
    function doRefreshAll(showSpin) {
      if (showSpin) $('#refreshAll i').addClass('fa-spin');
      var count = 0, total = {{ count($distrouter) }};
      @foreach($distrouter as $router)
        fetchRouterInfo({{ $router->id }}, function(){ if(++count >= total) $('#refreshAll i').removeClass('fa-spin'); });
      @endforeach
    }

    $('#refreshAll').on('click', function(){
      doRefreshAll(true);
      resetCountdown();
    });

    // Auto-reload countdown (30 detik)
    var AUTO_INTERVAL = 60;
    var countdown = AUTO_INTERVAL;
    function resetCountdown() { countdown = AUTO_INTERVAL; }
    setInterval(function(){
      countdown--;
      $('#auto-countdown').text(countdown + 's');
      if (countdown <= 0) {
        doRefreshAll(true);
        resetCountdown();
      }
    }, 1000);

    // Show detail modal
    $(document).on('click', '.show-detail', function(){
      var id = $(this).data('id');
      var name = $(this).closest('.router-card').find('.router-name').text().trim();
      $('#routerDetailModalLabel').html('<i class="fas fa-server mr-2" style="color:var(--brand)"></i>'+name);
      $('#routerDetailContent').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--brand)"></i></div>');
      $('#routerDetailModal').modal('show');
      fetchRouterInfo(id, function(resp){
        var html = '<div class="row mb-3">';
        html += '<div class="col-6 col-md-3 text-center"><div style="font-size:22px;font-weight:800;color:#4a76bd">'+(resp.pppUserCount||0)+'</div><div style="font-size:11px;color:var(--text-muted)">Total</div></div>';
        html += '<div class="col-6 col-md-3 text-center"><div style="font-size:22px;font-weight:800;color:#10b981">'+(resp.pppActiveCount||0)+'</div><div style="font-size:11px;color:var(--text-muted)">Aktif</div></div>';
        html += '<div class="col-6 col-md-3 text-center"><div style="font-size:22px;font-weight:800;color:#ef4444">'+(resp.pppOfflineCount||0)+'</div><div style="font-size:11px;color:var(--text-muted)">Offline</div></div>';
        html += '<div class="col-6 col-md-3 text-center"><div style="font-size:22px;font-weight:800;color:#6b7280">'+(resp.pppDisabledCount||0)+'</div><div style="font-size:11px;color:var(--text-muted)">Disabled</div></div>';
        html += '</div>';
        html += '<hr style="border-color:var(--border)">';
        html += '<div class="row">';
        html += '<div class="col-md-4"><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);margin-bottom:6px"><i class="fas fa-circle" style="color:#10b981;font-size:8px;margin-right:4px"></i>Online</div><ul class="list-unstyled" style="font-size:12px;max-height:200px;overflow-y:auto">';
        (resp.onlineUsers||[]).forEach(function(u){ html += '<li style="padding:2px 0;border-bottom:1px solid var(--border)">'+u+'</li>'; });
        if(!(resp.onlineUsers||[]).length) html += '<li style="color:var(--text-muted)">—</li>';
        html += '</ul></div>';
        html += '<div class="col-md-4"><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);margin-bottom:6px"><i class="fas fa-circle" style="color:#ef4444;font-size:8px;margin-right:4px"></i>Offline</div><ul class="list-unstyled" style="font-size:12px;max-height:200px;overflow-y:auto">';
        (resp.offlineUsers||[]).forEach(function(u){ html += '<li style="padding:2px 0;border-bottom:1px solid var(--border)">'+u+'</li>'; });
        if(!(resp.offlineUsers||[]).length) html += '<li style="color:var(--text-muted)">—</li>';
        html += '</ul></div>';
        html += '<div class="col-md-4"><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);margin-bottom:6px"><i class="fas fa-circle" style="color:#6b7280;font-size:8px;margin-right:4px"></i>Disabled</div><ul class="list-unstyled" style="font-size:12px;max-height:200px;overflow-y:auto">';
        (resp.disabledUsers||[]).forEach(function(u){ html += '<li style="padding:2px 0;border-bottom:1px solid var(--border)">'+u+'</li>'; });
        if(!(resp.disabledUsers||[]).length) html += '<li style="color:var(--text-muted)">—</li>';
        html += '</ul></div>';
        html += '</div>';
        $('#routerDetailContent').html(html);
      });
    });

    // Search
    function debounce(fn, d){ var t; return function(){ clearTimeout(t); var a=arguments; t=setTimeout(function(){ fn.apply(null,a); },d); }; }
    $('#routerSearch').on('input', debounce(function(){
      var q = $(this).val().toLowerCase().trim();
      var vis = 0;
      $('.router-row').each(function(){
        var n = $(this).data('name')||'', ip = $(this).data('ip')||'';
        if(!q || n.indexOf(q)!==-1 || ip.indexOf(q)!==-1){ $(this).show(); vis++; }
        else $(this).hide();
      });
      $('#noResult').toggle(vis === 0);
    }, 200));
    $('#clearSearch').on('click', function(){ $('#routerSearch').val('').trigger('input'); });
  });
})();
</script>
@endsection
