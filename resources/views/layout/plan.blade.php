    <li class="nav-item">
      <a href="{{ url ('plan')}}" class="nav-link">
        <i class="nav-icon fas fa-money-check-alt"></i>
        <p>
          Plans
          {{--  <span class="right badge badge-danger">New</span> --}}
        </p>
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ url ('addon')}}" class="nav-link {{ request()->is('addon*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-puzzle-piece"></i>
        <p>Add-on Services</p>
      </a>
    </li>