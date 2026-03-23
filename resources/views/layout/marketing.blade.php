  <li class="nav-item has-treeview">
    <a href="#" class="nav-link">
      <i class="nav-icon fas fa-chart-line"></i>
      <p>
        Marketing
        <i class="right fas fa-angle-left"></i>
      </p>
    </a>
    <ul class="nav nav-treeview">
      <li class="nav-item">
        <a href="{{ url('sale') }}" class="nav-link">
          <i class="far fa-circle nav-icon"></i>
          <p>Sales</p>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('marketing.lead-summary') }}" class="nav-link">
          <i class="far fa-circle nav-icon text-success"></i>
          <p>Lead Summary</p>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('lead-workflow.index') }}" class="nav-link {{ request()->is('settings/lead-workflow*') ? 'active' : '' }}">
          <i class="far fa-circle nav-icon text-warning"></i>
          <p>Template Workflow Lead</p>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('marketing.promos.index') }}" class="nav-link {{ request()->is('marketing/promos*') ? 'active' : '' }}">
          <i class="far fa-circle nav-icon text-info"></i>
          <p>Promo App</p>
        </a>
      </li>
    </ul>


  </li>