  <li class="nav-item">
    <a href="{{ url ('distrouter')}}" class="nav-link">
      <i class="fa fa-sitemap nav-icon "></i>
      <p>
        Distribution Router
        {{--  <span class="right badge badge-danger">New</span> --}}
      </p>
    </a>
  </li>
  <li class="nav-item">
    <a href="{{ url('mikrotik-sync') }}" class="nav-link {{ request()->is('mikrotik-sync*') ? 'active' : '' }}">
      <i class="fas fa-exclamation-triangle nav-icon text-danger"></i>
      <p>
        MikroTik Sync
        @php $syncFailCount = \App\MikrotikSyncFailure::pending()->count() @endphp
        @if($syncFailCount > 0)
          <span class="right badge badge-danger">{{ $syncFailCount }}</span>
        @endif
      </p>
    </a>
  </li>
  </li>