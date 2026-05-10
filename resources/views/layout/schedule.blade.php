          <li class="nav-item has-treeview {{ request()->is('schedule') || request()->is('schedule/list') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ request()->is('schedule') || request()->is('schedule/list') ? 'active' : '' }}">
             <i class="nav-icon fas fa-tasks"></i>
             <p>
              Job Schedules
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ url('schedule') }}" target="_blank" class="nav-link {{ request()->is('schedule') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>TV Wall</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ url('schedule/list') }}" class="nav-link {{ request()->is('schedule/list') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>List</p>
              </a>
            </li>
          </ul>
        </li>