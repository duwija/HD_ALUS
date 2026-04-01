@extends('layout.main')
@section('title','Distribution Point List')
@section('content')
<style>
  .dp-stat {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 14px 18px;
    box-shadow: var(--shadow-sm);
    position: relative; overflow: hidden;
  }
  .dp-stat-icon {
    position: absolute; right: 14px; top: 50%;
    transform: translateY(-50%);
    font-size: 30px; opacity: .08; color: var(--brand);
  }
  .dp-stat-num {
    font-size: 28px; font-weight: 800;
    color: var(--text-primary); line-height: 1;
  }
  .dp-stat-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.5px; color: var(--text-muted); margin-top: 3px;
  }
  .dp-stat-sub { font-size: 12px; color: var(--text-secondary); margin-top: 3px; }
  .dp-stat-al     { border-left: 4px solid var(--brand); }
  .dp-stat-green  { border-left: 4px solid #10b981; }
  .dp-stat-blue   { border-left: 4px solid #4a76bd; }
  .dp-stat-yellow { border-left: 4px solid #f59e0b; }
  .dp-stat-purple { border-left: 4px solid #8b5cf6; }

  .util-bar-track {
    background: var(--border); border-radius: 4px; height: 6px; margin-top: 6px;
  }
  .util-bar-fill {
    height: 6px; border-radius: 4px;
    background: linear-gradient(90deg, #10b981, #f59e0b, #ef4444);
    transition: width .5s ease;
  }
  .grp-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--bg-surface-2); border: 1px solid var(--border);
    border-radius: 8px; padding: 6px 12px; font-size: 12px;
    color: var(--text-primary); text-decoration: none !important;
  }
  .grp-badge:hover { box-shadow: var(--shadow-sm); }
  .grp-badge strong { color: var(--brand); }
</style>

@php
  $totalDp       = \App\Distpoint::whereNotIn('id',[1])->count();
  $totalGroups   = \App\Distpointgroup::count();
  $totalCapacity = \App\Distpointgroup::sum('capacity');
  $totalPort     = \App\Distpoint::whereNotIn('id',[1])->sum('ip');
  $totalCust     = \App\Customer::whereNotNull('id_distpoint')->where('id_distpoint','!=',0)->count();
  $utilPct       = $totalCapacity > 0 ? round($totalCust / $totalCapacity * 100, 1) : 0;
  $groupStats    = \App\Distpointgroup::withCount('customers')->orderByDesc('customers_count')->take(8)->get();
@endphp

<div class="container-fluid">

  {{-- ===== STAT CARDS ===== --}}
  <div class="row mb-3">
    <div class="col-6 col-md-3 col-xl mb-2">
      <div class="dp-stat dp-stat-al">
        <i class="fas fa-network-wired dp-stat-icon"></i>
        <div class="dp-stat-num">{{ $totalDp }}</div>
        <div class="dp-stat-label">Distribution Point</div>
         <div class="dp-stat-sub">count</div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl mb-2">
      <div class="dp-stat dp-stat-blue">
        <i class="fas fa-layer-group dp-stat-icon"></i>
        <div class="dp-stat-num">{{ $totalGroups }}</div>
        <div class="dp-stat-label">Distribution Group</div>
         <div class="dp-stat-sub">Count</div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl mb-2">
      <div class="dp-stat dp-stat-purple">
        <i class="fas fa-plug dp-stat-icon"></i>
        <div class="dp-stat-num">{{ $totalCapacity }}</div>
        <div class="dp-stat-label">Total Kapasitas</div>
        <div class="dp-stat-sub">Port tersedia (group)</div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl mb-2">
      <div class="dp-stat" style="border-left:4px solid #06b6d4">
        <i class="fas fa-ethernet dp-stat-icon"></i>
        <div class="dp-stat-num">{{ number_format($totalPort) }}</div>
        <div class="dp-stat-label">Total Port</div>
        <div class="dp-stat-sub">Sum kolom IP distpoint</div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl mb-2">
      <div class="dp-stat dp-stat-green">
        <i class="fas fa-users dp-stat-icon"></i>
        <div class="dp-stat-num">{{ $totalCust }}</div>
        <div class="dp-stat-label">Customer Terpasang</div>
        <div class="dp-stat-sub">di semua distpoint</div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl mb-2">
      <div class="dp-stat dp-stat-yellow">
        <i class="fas fa-tachometer-alt dp-stat-icon"></i>
        <div class="dp-stat-num">{{ $utilPct }}<span style="font-size:14px;font-weight:400">%</span></div>
        <div class="dp-stat-label">Utilisasi Kapasitas</div>
        <div class="util-bar-track">
          <div class="util-bar-fill" style="width:{{ min($utilPct,100) }}%"></div>
        </div>
        <div class="dp-stat-sub" style="margin-top:4px">{{ $totalCust }} / {{ $totalCapacity }} port terpakai</div>
      </div>
    </div>
  </div>

  {{-- ===== TOP GROUPS ===== --}}
  @if($groupStats->count())
  <div class="mb-3" style="background:var(--bg-surface);border:1px solid var(--border);border-radius:12px;padding:14px 18px;box-shadow:var(--shadow-sm)">
    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);margin-bottom:10px;">
      <i class="fas fa-layer-group mr-1" style="color:var(--brand)"></i>Utilisasi per Group
    </div>
    <div class="d-flex flex-wrap" style="gap:8px">
      @foreach($groupStats as $grp)
      @php
        $gcap = $grp->capacity ?: 1;
        $gcnt = $grp->customers_count;
        $gpct = round($gcnt / $gcap * 100);
        $gcol = $gpct >= 90 ? '#ef4444' : ($gpct >= 60 ? '#f59e0b' : '#10b981');
      @endphp
      <a href="/distpointgroup/{{ $grp->id }}" class="grp-badge">
        <i class="fas fa-circle" style="color:{{ $gcol }};font-size:8px"></i>
        {{ $grp->name }}
        <strong>{{ $gcnt }}/{{ $grp->capacity }}</strong>
        <span style="font-size:11px;color:var(--text-muted)">({{ $gpct }}%)</span>
      </a>
      @endforeach
    </div>
  </div>
  @endif

  {{-- ===== CARD TABLE ===== --}}
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Distribution Point List</h3>
      <a href="{{url('distpoint/create')}}" class="float-right btn bg-gradient-primary btn-sm m-2">
        <i class="fas fa-plus mr-1"></i>New Distribution Point
      </a>
      <a href="{{url('distpointgroup')}}" class="float-right btn bg-gradient-primary btn-sm m-2">
        <i class="fas fa-layer-group mr-1"></i>Dist Group List
      </a>
    </div>

    <div class="card-body">
      <div class="row mb-2 p-2">
        <div class="col-md-2 mb-2">
          <select id="filter-site" class="form-control">
            <option value="">All Sites</option>
            @foreach(\App\Site::orderBy('name')->get() as $site)
            <option value="{{ $site->name }}">{{ $site->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <select id="filter-group" class="form-control">
            <option value="">All Groups</option>
            @foreach(\App\Distpointgroup::orderBy('name')->get() as $group)
            <option value="{{ $group->name }}">{{ $group->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <input type="text" id="filter-name" class="form-control" placeholder="Search Distribution Point Name">
        </div>
        <div class="col-md-1 mb-2">
          <button class="btn btn-primary btn-block" id="apply-filters">Filter</button>
        </div>
      </div>

      <div class="table-responsive">
        <table id="table-distpoint-list" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Port Capacity</th>
              <th>Port Used</th>
              <th>Optic Power</th>
              <th>Site</th>
              <th>Parrent</th>
              <th>Group</th>
              <th>Description</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@section('footer-scripts')
@include('script.distpoint_list')
@endsection
