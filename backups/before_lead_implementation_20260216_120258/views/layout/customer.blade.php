      <li class="nav-item has-treeview">
        <a href="#" class="nav-link">
          <i class="nav-icon fas fa-user-tag"></i>
          <p>
            Customer
            <i class="right fas fa-angle-left"></i>
          </p>
        </a>
        {{--  <ul class="nav nav-treeview">
          <li class="nav-item">

           <form action="/customer/search" method="GET" class="">
            <div class="input-group   ">
              <input  class="form-control " name='search' type="search" placeholder="Search Customer" aria-label="Search">

            </div>
          </form>
        </li>
      </ul> --}}
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="{{ url ('customer/create')}}" class="nav-link">
            <i class="far fa-circle nav-icon ml-3"></i>
            <p>Add New Customer </p>
          </a>
        </li>
      </ul>
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="{{ url ('customer')}}" class="nav-link">
            <i class="far fa-circle nav-icon ml-3"></i>
            <p>Customer List</p>
          </a>
        </li>
      </ul>
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="{{ url ('customer/trash')}}" class="nav-link">
            <i class="far fa-circle nav-icon ml-3"></i>
            <p>Trash</p>
          </a>
        </li>
      </ul>

    </li>
