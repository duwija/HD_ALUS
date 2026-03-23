<li class="nav-item has-treeview">
  <a href="#" class="nav-link">
    <i class="nav-icon fas fa-ticket-alt"></i>
    <p>
      Vendor Tickets
      <i class="right fas fa-angle-left"></i>
    </p>
  </a>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url ('vendorticket')}}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>My Ticket</p>
      </a>
    </li>
  </ul>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url ('ticket/vendorgroupticket')}}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Group Ticket</p>
      </a>
    </li>
  </ul>

</li>
