
<li class="nav-item has-treeview {{ request()->is('attendance*') || request()->is('leave*') || request()->is('overtime*') ? 'menu-open' : '' }}">
  <a href="#" class="nav-link {{ request()->is('attendance*') || request()->is('leave*') || request()->is('overtime*') ? 'active' : '' }}">
    <i class="nav-icon fas fa-user-clock"></i>
    <p>
      Absensi
      <i class="right fas fa-angle-left"></i>
    </p>
  </a>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url('attendance/employees') }}" class="nav-link {{ request()->is('attendance/employees*') ? 'active' : '' }}">
        <i class="fas fa-users nav-icon"></i>
        <p>Karyawan</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ url('attendance/locations') }}" class="nav-link {{ request()->is('attendance/locations*') ? 'active' : '' }}">
        <i class="fas fa-map-marker-alt nav-icon"></i>
        <p>Lokasi Absen</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ url('attendance/shifts') }}" class="nav-link {{ request()->is('attendance/shifts*') ? 'active' : '' }}">
        <i class="fas fa-clock nav-icon"></i>
        <p>Shift</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ url('attendance/schedule') }}" class="nav-link {{ request()->is('attendance/schedule*') ? 'active' : '' }}">
        <i class="fas fa-calendar-alt nav-icon"></i>
        <p>Jadwal Shift</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ url('attendance/daily') }}" class="nav-link {{ request()->is('attendance/daily*') ? 'active' : '' }}">
        <i class="fas fa-calendar-day nav-icon"></i>
        <p>Absen Harian</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ url('attendance/report') }}" class="nav-link {{ request()->is('attendance/report*') ? 'active' : '' }}">
        <i class="fas fa-chart-bar nav-icon"></i>
        <p>Rekap Bulanan</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ url('leave') }}" class="nav-link {{ request()->is('leave*') ? 'active' : '' }}">
        <i class="fas fa-umbrella-beach nav-icon"></i>
        <p>Izin / Cuti / Sakit
          @php $leavePending = \App\LeaveRequest::where('status','pending')->count() @endphp
          @if($leavePending > 0) <span class="right badge badge-warning">{{ $leavePending }}</span> @endif
        </p>
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ url('overtime') }}" class="nav-link {{ request()->is('overtime*') ? 'active' : '' }}">
        <i class="fas fa-business-time nav-icon"></i>
        <p>Lembur
          @php $overtimePending = \App\OvertimeRequest::where('status','pending')->count() @endphp
          @if($overtimePending > 0) <span class="right badge badge-warning">{{ $overtimePending }}</span> @endif
        </p>
      </a>
    </li>
  </ul>
</li>
