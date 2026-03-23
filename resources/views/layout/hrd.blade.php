<li class="nav-item">
  <a href="{{ url('attendance/dashboard') }}" class="nav-link">
    <i class="nav-icon fas fa-tachometer-alt"></i>
    <p>Dashboard HRD</p>
  </a>
</li>

<li class="nav-item has-treeview">
  <a href="#" class="nav-link">
    <i class="nav-icon fas fa-user-clock"></i>
    <p>
      Absensi
      <i class="right fas fa-angle-left"></i>
    </p>
  </a>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url('attendance/employees') }}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Karyawan</p>
      </a>
    </li>
  </ul>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url('attendance/locations') }}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Lokasi Absen</p>
      </a>
    </li>
  </ul>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url('attendance/shifts') }}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Shift</p>
      </a>
    </li>
  </ul>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url('attendance/schedule') }}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Jadwal Shift</p>
      </a>
    </li>
  </ul>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url('attendance/daily') }}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Absen Harian</p>
      </a>
    </li>
  </ul>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url('attendance/report') }}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Rekap Bulanan</p>
      </a>
    </li>
  </ul>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url('leave') }}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Izin / Cuti / Sakit</p>
      </a>
    </li>
  </ul>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url('overtime') }}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Lembur</p>
      </a>
    </li>
  </ul>
</li>
