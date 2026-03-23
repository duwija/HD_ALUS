  <li class="nav-item has-treeview">
    <a href="#" class="nav-link">
      <i class="nav-icon fa fa-gear"></i>
      <p>
        Admin
        <i class="right fas fa-angle-left"></i>
      </p>
    </a>
    <ul class="nav nav-treeview">
      <li class="nav-item">
        <a href="{{ url ('user')}}" class="nav-link">
          <i class="fas fa-user nav-icon"></i>
          <p>User Management</p>
        </a>
      </li>
    </ul>

    <ul class="nav nav-treeview">
      <li class="nav-item">
        <a href="{{ url ('file/backup')}}" class="nav-link">
          <i class="far fa-file nav-icon"></i>
          <p>Backup Files</p>
        </a>
      </li>
    </ul>
    <ul class="nav nav-treeview">
      <li class="nav-item">
        <a href="{{ url ('wa/dashboard')}}" class="nav-link">
          <i class="fab fa-whatsapp nav-icon"></i>
          <p>Wa Gateway</p>
        </a>
      </li>
    </ul>
    <ul class="nav nav-treeview">
      <li class="nav-item">
        <a href="{{ url ('user/log')}}" class="nav-link ">
          <i class="far fa-circle nav-icon"></i>
          <p>Logs</p>
        </a>
      </li>
    </ul>

  </li>