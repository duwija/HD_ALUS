@extends('layout.main')
@section('title','Router — ' . $distrouter->name)
@section('content')
<style>
  .dr-card { background:var(--bg-surface); border:1px solid var(--border); border-radius:12px; box-shadow:var(--shadow-sm); overflow:hidden; margin-bottom:18px; }
  .dr-card-header {
    background:var(--bg-surface-2); border-bottom:1px solid var(--border);
    padding:12px 18px; display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;
  }
  .dr-card-header h6 { font-size:13px; font-weight:700; color:var(--text-primary); margin:0; }
  .dr-card-body { padding:18px; }

  /* info grid */
  .dr-info-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:0; }
  .dr-info-item { padding:10px 14px; border-bottom:1px solid var(--border); display:flex; gap:8px; align-items:flex-start; }
  .dr-info-item:nth-child(odd) { border-right:1px solid var(--border); }
  .dr-info-item:last-child, .dr-info-item:nth-last-child(2) { border-bottom:0; }
  .dr-info-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:var(--text-muted); min-width:90px; padding-top:1px; }
  .dr-info-value { font-size:13px; color:var(--text-primary); word-break:break-all; }
  .dr-info-value a { color:var(--brand); text-decoration:none; }
  .dr-info-value a:hover { text-decoration:underline; }

  /* command navbar */
  .cmd-nav {
    background:var(--bg-surface-2); border:1px solid var(--border); border-radius:10px;
    padding:8px 12px; display:flex; flex-wrap:wrap; gap:4px; margin-bottom:14px;
  }
  .cmd-nav .dropdown-toggle {
    background:var(--bg-surface); border:1px solid var(--border); border-radius:8px;
    color:var(--text-primary); font-size:12px; font-weight:600; padding:5px 12px;
  }
  .cmd-nav .dropdown-toggle:hover { background:var(--brand-light); color:var(--brand); border-color:rgba(163,48,28,.3); }
  .cmd-nav .dropdown-menu { background:var(--bg-surface); border:1px solid var(--border); border-radius:10px; box-shadow:var(--shadow-md); padding:6px; min-width:180px; }
  .cmd-nav .dropdown-item { border-radius:6px; font-size:12px; color:var(--text-primary); padding:6px 10px; }
  .cmd-nav .dropdown-item:hover { background:var(--brand-light); color:var(--brand); }

  /* command search, status & output */
  .cmd-search { width:260px; max-width:100%; border-radius:8px; background:var(--input-bg); border-color:var(--input-border); color:var(--text-primary); }
  .cmd-note { font-size:10px; color:var(--text-muted); margin-left:4px; }
  .cmd-search-results { display:flex; flex-wrap:wrap; gap:6px; margin:-6px 0 12px; }
  .cmd-search-results .cmd-item { display:inline-block; width:auto; border:1px solid var(--border); border-radius:20px; padding:3px 10px; font-size:12px; color:var(--text-primary); background:var(--bg-surface); }
  .cmd-search-results .cmd-item:hover { background:var(--brand-light); color:var(--brand); text-decoration:none; }
  .cmd-item.active { background:var(--brand-light) !important; color:var(--brand) !important; font-weight:600; }
  .cmd-status { display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; padding:8px 12px; margin-bottom:10px; border:1px solid var(--border); border-radius:8px; background:var(--bg-surface-2); font-size:12px; }
  .cmd-status code { font-size:11px; color:var(--brand); background:transparent; }
  .cmd-output { font-size:12px; color:var(--text-primary); }
  .cmd-empty { color:var(--text-muted); padding:18px; text-align:center; border:1px dashed var(--border); border-radius:10px; }
  .cmd-error { color:#b91c1c; background:rgba(239,68,68,.08); border:1px solid rgba(239,68,68,.25); border-radius:10px; padding:12px 14px; }
  #outputTable { font-size:12px; width:100% !important; }
  #outputTable thead th { background:var(--bg-surface-2); color:var(--text-secondary); font-size:11px; text-transform:uppercase; letter-spacing:.3px; white-space:nowrap; border-color:var(--border); }
  #outputTable td { border-color:var(--border); vertical-align:top; max-width:340px; word-break:break-word; }
  #outputTable tr.row-disabled td { color:var(--text-muted); text-decoration:line-through; }
  #outputTable tr.row-dynamic td { font-style:italic; }
  .flag-true { color:#10b981; font-weight:700; }
  .flag-false { color:var(--text-muted); }
  .mk-secret { cursor:pointer; color:var(--text-muted); letter-spacing:2px; }
  .row-flags .badge { font-size:10px; font-weight:600; margin-right:2px; }

  /* tables */
  #pppoeTable thead th,
  #interface-table thead th,
  #logTable thead th { background:var(--bg-surface-2); color:var(--text-secondary); font-size:11px; text-transform:uppercase; letter-spacing:.4px; border-color:var(--border); }
  #pppoeTable td, #interface-table td, #logTable td { border-color:var(--border); color:var(--text-primary); font-size:12px; }

  /* status badges */
  .badge-online   { background:rgba(16,185,129,.12); color:#10b981; border:1px solid rgba(16,185,129,.25); border-radius:20px; padding:2px 8px; font-size:11px; font-weight:600; }
  .badge-offline  { background:rgba(239,68,68,.12);  color:#ef4444; border:1px solid rgba(239,68,68,.25);  border-radius:20px; padding:2px 8px; font-size:11px; font-weight:600; }
  .badge-disabled { background:rgba(107,114,128,.12);color:#6b7280; border:1px solid rgba(107,114,128,.25);border-radius:20px; padding:2px 8px; font-size:11px; font-weight:600; }

  /* spinner */
  .dr-spinner { text-align:center; padding:20px; color:var(--text-muted); }
</style>

<div class="container-fluid">

  {{-- ACTION BAR --}}
  <div class="d-flex align-items-center mb-3" style="gap:8px;flex-wrap:wrap">
    <a href="{{ url('distrouter') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
      <i class="fas fa-arrow-left mr-1"></i>Kembali
    </a>
    <div class="ml-auto d-flex" style="gap:6px;flex-wrap:wrap">
      <a href="/distrouter/{{ $distrouter->id }}/edit" class="btn btn-sm btn-primary" style="border-radius:8px">
        <i class="fas fa-edit mr-1"></i>Edit Router
      </a>
      <a href="/distrouter/backupconfig/{{ $distrouter->id }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px">
        <i class="fas fa-calendar-plus mr-1"></i>Backup Schedule
      </a>
      <a href="/distrouter/import-ppp-profiles/{{ $distrouter->id }}"
         class="btn btn-sm btn-outline-success" style="border-radius:8px"
         onclick="return confirm('Import PPPoE profiles dari Mikrotik ini ke tabel Plans?')">
        <i class="fas fa-download mr-1"></i>Import PPP Profiles
      </a>
      <form action="/distrouter/{{ $distrouter->id }}" method="POST" class="d-inline item-delete">
        @method('delete') @csrf
        <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:8px">
          <i class="fas fa-trash mr-1"></i>Hapus
        </button>
      </form>
    </div>
  </div>

  {{-- TOP ROW: Info + Live Stats --}}
  <div class="row">
    <div class="col-lg-5 col-md-6">
      <div class="dr-card">
        <div class="dr-card-header">
          <h6><i class="fas fa-server mr-2" style="color:var(--brand)"></i>{{ $distrouter->name }}</h6>
          <span style="font-size:11px;color:var(--text-muted)">ID: {{ $distrouter->id }}</span>
        </div>
        <div class="dr-info-grid">
          <div class="dr-info-item">
            <span class="dr-info-label">IP</span>
            <span class="dr-info-value">
              <a href="http://{{ $distrouter->ip }}:{{ $distrouter->web }}" target="_blank" rel="noopener">
                {{ $distrouter->ip }}
              </a>
            </span>
          </div>
          <div class="dr-info-item">
            <span class="dr-info-label">API Port</span>
            <span class="dr-info-value">{{ $distrouter->port }}</span>
          </div>
          <div class="dr-info-item">
            <span class="dr-info-label">Web Port</span>
            <span class="dr-info-value">{{ $distrouter->web }}</span>
          </div>
          <div class="dr-info-item">
            <span class="dr-info-label">Username</span>
            <span class="dr-info-value">{{ $distrouter->user }}</span>
          </div>
          <div class="dr-info-item">
            <span class="dr-info-label">User Terdaftar</span>
            <span class="dr-info-value" style="font-weight:700;color:var(--brand)">{{ $count_user }}</span>
          </div>
          @if($distrouter->note)
          <div class="dr-info-item">
            <span class="dr-info-label">Catatan</span>
            <span class="dr-info-value">{{ $distrouter->note }}</span>
          </div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-7 col-md-6">
      <div class="dr-card">
        <div class="dr-card-header">
          <h6><i class="fas fa-tachometer-alt mr-2" style="color:var(--brand)"></i>Live Router Info</h6>
          <button id="btnRefreshInfo" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:11px">
            <i class="fas fa-sync-alt mr-1"></i>Refresh
          </button>
        </div>
        <div class="dr-card-body">
          <div id="distrouter-info">
            <div class="dr-spinner"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--brand)"></i></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- MIKROTIK COMMANDS (hanya perintah baca, lihat App\Services\MikrotikCommandCatalog) --}}
  <div class="dr-card" id="mkCommands">
    <div class="dr-card-header">
      <h6><i class="fas fa-terminal mr-2" style="color:var(--brand)"></i>MikroTik Commands <small class="text-muted font-weight-normal">&middot; read-only</small></h6>
      <input type="search" id="cmdSearch" class="form-control form-control-sm cmd-search" placeholder="Cari perintah… (mis. arp, lease, queue)" autocomplete="off">
    </div>
    <div class="dr-card-body" style="padding-top:12px">
      <div class="cmd-nav">
        @foreach (\App\Services\MikrotikCommandCatalog::GROUPS as $group => $items)
        <div class="dropdown">
          <button class="dropdown-toggle" data-toggle="dropdown"><strong>{{ $group }}</strong></button>
          <div class="dropdown-menu">
            @foreach ($items as $key => $item)
              <a class="dropdown-item cmd-item" href="#" data-key="{{ $key }}" data-label="{{ $group }} › {{ $item[0] }}" title="{{ $item[1] }}">
                {{ $item[0] }}
                @isset($item[3]) <span class="cmd-note">{{ $item[3] }}</span> @endisset
              </a>
            @endforeach
          </div>
        </div>
        @endforeach
      </div>
      <div id="cmdSearchResults" class="cmd-search-results" style="display:none"></div>

      <div class="cmd-status" id="cmdStatus" style="display:none">
        <div>
          <strong id="cmdLabel"></strong>
          <code id="cmdPath" class="ml-1"></code>
          <span id="cmdMeta" class="text-muted ml-2"></span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="cmdRefresh" title="Jalankan ulang"><i class="fas fa-sync-alt"></i> Refresh</button>
      </div>

      <div id="spinnermk" class="dr-spinner" style="display:none">
        <i class="fas fa-spinner fa-spin fa-2x" style="color:var(--brand)"></i>
        <div style="font-size:12px;margin-top:6px">Mengambil data dari router…</div>
      </div>
      <div id="commandOutput" class="cmd-output">
        <div class="cmd-empty"><i class="fas fa-mouse-pointer mr-1"></i> Pilih perintah dari menu di atas atau cari perintah. Semua perintah hanya membaca data (print).</div>
      </div>
    </div>
  </div>

  {{-- PPP USER STATUS --}}
  <div class="dr-card">
    <div class="dr-card-header">
      <h6><i class="fas fa-users mr-2" style="color:var(--brand)"></i>PPP User Status</h6>
      <select id="statusFilter" class="form-control form-control-sm" style="width:auto;border-radius:8px;background:var(--input-bg);border-color:var(--input-border);color:var(--text-primary)">
        <option value="all">Semua Status</option>
        <option value="online">Online</option>
        <option value="offline">Offline</option>
        <option value="disabled">Disabled</option>
      </select>
    </div>
    <div class="dr-card-body">
      <div id="spinnerpppoe" class="dr-spinner" style="display:none">
        <i class="fas fa-spinner fa-spin fa-2x" style="color:var(--brand)"></i>
        <div style="font-size:12px;margin-top:6px">Loading PPPoE data...</div>
      </div>
      <div class="table-responsive">
        <table id="pppoeTable" class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>Name</th>
              <th>Description</th>
              <th>Profile</th>
              <th>Last Logout</th>
              <th>Address</th>
              <th>Uptime</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- INTERFACES + LOGS --}}
  <div class="row">
    <div class="col-lg-6">
      <div class="dr-card">
        <div class="dr-card-header">
          <h6><i class="fas fa-ethernet mr-2" style="color:var(--brand)"></i>Interfaces</h6>
        </div>
        <div class="dr-card-body">
          <div class="table-responsive" style="max-height:360px;overflow-y:auto">
            <table id="interface-table" class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th>Interface</th>
                  <th>Comment</th>
                  <th>Running</th>
                  <th>Data Rate</th>
                  <th>RX</th>
                  <th>TX</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="dr-card">
        <div class="dr-card-header">
          <h6><i class="fas fa-list-alt mr-2" style="color:var(--brand)"></i>System Logs</h6>
        </div>
        <div class="dr-card-body">
          <div class="table-responsive" style="max-height:360px;overflow-y:auto">
            <table id="logTable" class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th>Time</th>
                  <th>Topics</th>
                  <th>Message</th>
                </tr>
              </thead>
              <tbody>
                <tr><td colspan="3" style="color:var(--text-muted);font-size:12px">Loading logs...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
@section('footer-scripts')
@include('script.distrouter')
@endsection
