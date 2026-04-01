@extends('layout.main')
@section('title','Distribution Router')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Router LIst  </h3>
      <a href="{{url ('distrouter/create')}}" class=" float-right btn  bg-gradient-primary btn-sm">Add New Router</a>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      <!-- Toolbar -->
      <div class="d-flex mb-3 align-items-center">
        <div class="input-group" style="max-width:420px;">
          <input id="routerSearch" type="search" class="form-control form-control-sm" placeholder="Search name or IP...">
          <div class="input-group-append">
            <button id="clearSearch" class="btn btn-sm btn-outline-secondary">Clear</button>
          </div>
        </div>
        <div class="ml-3">
          <button id="refreshAll" class="btn btn-sm btn-outline-primary">Refresh All</button>
        </div>
        <div class="ml-auto text-muted small">Showing <span id="visibleCount">{{ count($distrouter) }}</span> of {{ count($distrouter) }}</div>
      </div>

      <div class="table-responsive">
      <table id="example1" class="table table-hover table-striped">
        <thead class="thead-light">
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Resume</th>
            <th>IP</th>
            <th>API Port</th>
            <th>Description</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
         @foreach( $distrouter as $router)
         <tr data-name="{{ strtolower($router->name) }}" data-ip="{{ $router->ip }}">
          <td>{{ $loop->iteration }}</td>
          <td>
            <a href="/distrouter/{{ $router->id }}" class="font-weight-bold">{{ $router->name }}</a>
            <div class="text-muted small">ID: {{ $router->id }}</div>
          </td>
          <td class="text-center"><div id="pppoe-{{ $router->id }}" class="d-inline-block">Loading...</div></td>
          <td>
            <a href="{{ 'http://' . $router->ip . ':' . $router->web }}" target="_blank" rel="noopener" class="text-decoration-none">{{ $router->ip }}</a>
          </td>
          <td>{{ $router->port }}</td>
          <td>{{ $router->note }}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-info show-detail" data-id="{{ $router->id }}">Detail</button>
            <button class="btn btn-sm btn-outline-secondary refresh-router" data-id="{{ $router->id }}">Refresh</button>
          </td>
         </tr>
         @endforeach
        </tbody>
      </table>
      </div>
    </div>
  </div>

  <!-- Detail modal -->
  <div class="modal fade" id="routerDetailModal" tabindex="-1" role="dialog" aria-labelledby="routerDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="routerDetailModalLabel">Router Detail</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="routerDetailContent">Loading...</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function(){
      // Small CSS tweaks injected for better visuals
      var style = '<style>'+
        'table#example1 tbody tr:hover{background:#f8f9fa;}'+
        '.btn-outline-info.show-detail{padding:4px 8px;}'+
        '.btn-outline-secondary.refresh-router{padding:4px 8px;}'+
        '.spinner-border-sm{width:1rem;height:1rem;border-width:.15em;}'+
      '</style>';
      $('head').append(style);

      // Debounce helper
      function debounce(fn, delay){
        var t;
        return function(){
          var args = arguments;
          clearTimeout(t);
          t = setTimeout(function(){ fn.apply(null, args); }, delay);
        };
      }
      function renderSummary(container, data){
        var active = data.pppActiveCount || 0;
        var total = data.pppUserCount || 0;
        var offline = data.pppOfflineCount || 0;
        var disabled = data.pppDisabledCount || 0;

  var html = '<div class="d-flex flex-column align-items-start">';
  html += '<span class="badge badge-info mb-1">Total: ' + total + '</span>';
  html += '<span class="badge badge-success mb-1">Active: ' + active + '</span>';
  html += '<span class="badge badge-danger mb-1">Offline: ' + offline + '</span>';
  html += '<span class="badge badge-secondary mb-1">Disabled: ' + disabled + '</span>';
  html += '</div>';

  $(container).html(html);
      }

      function fetchRouterInfo(id, detailCallback){
        var target = '#pppoe-' + id;
        $(target).html('<span class="spinner-border spinner-border-sm text-muted" role="status"></span>');

        $.ajax({
          url: '/distrouter/getrouterinfo/' + id,
          method: 'GET',
          dataType: 'json'
        }).done(function(resp){
          if(resp && resp.success){
            renderSummary(target, resp);
            if(typeof detailCallback === 'function') detailCallback(resp);
          } else {
            $(target).html('<span class="badge badge-warning">No data</span>');
          }
        }).fail(function(){
          $(target).html('<span class="badge badge-danger">Error</span>');
        });
      }

      $(document).ready(function(){
        @foreach($distrouter as $router)
          fetchRouterInfo({{ $router->id }});
        @endforeach

        $(document).on('click', '.refresh-router', function(){
          var id = $(this).data('id');
          fetchRouterInfo(id);
        });

        $(document).on('click', '.show-detail', function(){
          var id = $(this).data('id');
          $('#routerDetailModalLabel').text('Router Detail — ID: ' + id);
          $('#routerDetailContent').html('Loading...');
          fetchRouterInfo(id, function(resp){
            var html = '';
            html += '<h6>Summary</h6>';
            html += '<div class="mb-3">';
            html += '<strong>Active:</strong> ' + (resp.pppActiveCount || 0) + ' &nbsp;';
            html += '<strong>Total:</strong> ' + (resp.pppUserCount || 0) + ' &nbsp;';
            html += '<strong>Offline:</strong> ' + (resp.pppOfflineCount || 0) + ' &nbsp;';
            html += '<strong>Disabled:</strong> ' + (resp.pppDisabledCount || 0) + '</div>';

            html += '<div class="row">';
            html += '<div class="col-md-4"><h6>Online</h6><ul class="list-unstyled small">';
            (resp.onlineUsers || []).forEach(function(u){ html += '<li>' + u + '</li>'; });
            html += '</ul></div>';

            html += '<div class="col-md-4"><h6>Offline</h6><ul class="list-unstyled small">';
            (resp.offlineUsers || []).forEach(function(u){ html += '<li>' + u + '</li>'; });
            html += '</ul></div>';

            html += '<div class="col-md-4"><h6>Disabled</h6><ul class="list-unstyled small">';
            (resp.disabledUsers || []).forEach(function(u){ html += '<li>' + u + '</li>'; });
            html += '</ul></div>';
            html += '</div>';

            $('#routerDetailContent').html(html);
            $('#routerDetailModal').modal('show');
          });
        });

        $('#refreshAll').on('click', function(){
          @foreach($distrouter as $router)
            fetchRouterInfo({{ $router->id }});
          @endforeach
        });

        $('#routerSearch').on('input', debounce(function(){
          var q = $(this).val().toLowerCase().trim();
          var visible = 0;
          $('table#example1 tbody tr').each(function(){
            var name = $(this).data('name') || '';
            var ip = $(this).data('ip') || '';
            if(q === '' || name.indexOf(q) !== -1 || ip.indexOf(q) !== -1){
              $(this).show(); visible++; 
            } else { $(this).hide(); }
          });
          $('#visibleCount').text(visible);
        }, 200));

        $('#clearSearch').on('click', function(){ $('#routerSearch').val('').trigger('input'); });
      });
    })();
  </script>
</section>

@endsection
