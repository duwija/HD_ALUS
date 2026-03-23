    <li class="nav-item has-treeview">
      <a href="#" class="nav-link">
        <i class="nav-icon fas fa-ticket-alt"></i>
        <p>
          Tickets
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="{{ url ('ticket')}}" class="nav-link">
            <i class="far fa-circle nav-icon ml-3"></i>
            <p>Ticket List </p>
          </a>
        </li>
      </ul>
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="{{ url ('ticket/groupticket')}}" class="nav-link">
            <i class="far fa-circle nav-icon ml-3"></i>
            <p>My Group Ticket </p>
          </a>
        </li>
      </ul>
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="{{ url ('ticket/report')}}" class="nav-link">
            <i class="far fa-circle nav-icon ml-3"></i>
            <p>Tiket Report</p>
          </a>
        </li>
      </ul>
        <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="{{ url ('ticketcategories')}}" class="nav-link">
            <i class="far fa-circle nav-icon ml-3"></i>
            <p>Tiket Categori</p>
          </a>
        </li>
      </ul>

    </li>