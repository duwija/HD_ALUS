 <li class="nav-item has-treeview">
  <a href="#" class="nav-link">
    <i class="nav-icon fas fa-money-check-alt"></i>
    <p>
      Accounting
      <i class="right fas fa-angle-left"></i>
    </p>
  </a>

  

  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url ('merchant')}}" class="nav-link">
        <i class="fas fa-store nav-icon  ml-3"></i>
        <p>Merchant</p>
      </a>
    </li>
  </ul>

  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url ('customer/unpaid')}}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Customer Bill</p>
      </a>
    </li>
  </ul>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url ('invoice')}}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3" ml-3></i>
        <p>Invoice</p>
      </a>
    </li>
  </ul>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url ('suminvoice/transaction')}}" class="nav-link">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Payment</p>
      </a>
    </li>
  </ul>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url ('invoice/createinv')}}" class="nav-link ">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Mounthly Invoice</p>
      </a>
    </li>
  </ul>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="{{ url ('suminvoice/notification')}}" class="nav-link ">
        <i class="far fa-circle nav-icon ml-3"></i>
        <p>Jobs</p>
      </a>
    </li>
  </ul>

</li>