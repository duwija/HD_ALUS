@extends('layout.main')
@section('title','Ticket List')

@section('header-styles')
<style>
  /* Modern Card Styling */
  .card {
    border-radius: 10px;
    border: none;
  }
  
  .card-header {
    border-radius: 10px 10px 0 0 !important;
    font-weight: 600;
  }
  
  .shadow-sm {
    box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.075) !important;
  }
  
  /* Small Box Enhancements */
  .small-box {
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }
  
  .small-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  }
  
  .small-box h3 {
    font-size: 2.2rem;
    font-weight: bold;
  }
  
  .small-box p {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .small-box .icon {
    font-size: 18px;
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.08;
  }
  
  /* MTTR Card special icon */
  .small-box.bg-gradient-purple .icon {
    font-size: 22px;
    opacity: 0.12;
  }
  
  .small-box .inner {
    position: relative;
    z-index: 10;
  }
  
  /* Form Styling */
  .form-control, .select2-container--default .select2-selection--single,
  .select2-container--default .select2-selection--multiple {
    border-radius: 6px;
    border: 1px solid #d2d6de;
  }
  
  .form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  }
  
  label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
  }
  
  label i {
    margin-right: 5px;
  }
  
  /* Button Styling */
  .btn-lg {
    padding: 0.6rem 1.5rem;
    font-size: 1rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  
  .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
  }
  
  .btn-primary:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }
  
  .btn-secondary {
    background: linear-gradient(135deg, #868e96 0%, #6c757d 100%);
    border: none;
  }
  
  .btn-secondary:hover {
    background: linear-gradient(135deg, #6c757d 0%, #868e96 100%);
    transform: translateY(-2px);
  }
  
  /* Table Styling */
  .table-hover tbody tr:hover {
    background-color: #f8f9fa;
    cursor: pointer;
  }
  
  thead.thead-light th {
    background-color: #e9ecef;
    color: #495057;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #dee2e6;
  }
  
  /* Input Group Icons */
  .input-group-text {
    background-color: #f8f9fa;
    border: 1px solid #d2d6de;
    color: #6c757d;
  }
  
  /* Collapsible Card Animation */
  .card[data-card-widget="collapse"] {
    transition: all 0.3s ease;
  }
  
  /* Content Header */
  .content-header h1 {
    font-weight: 700;
    color: #2c3e50;
  }
  
  /* Responsive adjustments */
  @media (max-width: 768px) {
    .small-box h3 {
      font-size: 1.5rem;
    }
    
    .small-box .icon {
      font-size: 40px;
    }
  }
</style>
@endsection

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0"><i class="fas fa-ticket-alt text-primary"></i> Ticket Management</h1>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    
    <!-- Statistics Cards - Compact 2 Column Layout -->
    <div class="row mb-3">
      <!-- Left Column: Status Cards -->
      <div class="col-lg-8">
        <div class="row">
          <div class="col-lg-4 col-6">
            <div class="small-box bg-gradient-navy" style="margin-bottom: 10px;">
              <div class="inner" style="padding: 10px 10px 5px 10px;">
                <h3 id="total" style="font-size: 1.8rem; margin-bottom: 2px;">0</h3>
                <p style="font-size: 0.75rem; margin-bottom: 0;">Total Tickets</p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-4 col-6">
            <div class="small-box bg-gradient-success" style="margin-bottom: 10px;">
              <div class="inner" style="padding: 10px 10px 5px 10px;">
                <h3 id="open" style="font-size: 1.8rem; margin-bottom: 2px;">0</h3>
                <p style="font-size: 0.75rem; margin-bottom: 0;">Open</p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-4 col-6">
            <div class="small-box bg-gradient-warning" style="margin-bottom: 10px;">
              <div class="inner" style="padding: 10px 10px 5px 10px;">
                <h3 id="inprogress" style="font-size: 1.8rem; margin-bottom: 2px;">0</h3>
                <p style="font-size: 0.75rem; margin-bottom: 0;">In Progress</p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-4 col-6">
            <div class="small-box bg-gradient-primary" style="margin-bottom: 10px;">
              <div class="inner" style="padding: 10px 10px 5px 10px;">
                <h3 id="pending" style="font-size: 1.8rem; margin-bottom: 2px;">0</h3>
                <p style="font-size: 0.75rem; margin-bottom: 0;">Pending</p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-4 col-6">
            <div class="small-box bg-gradient-info" style="margin-bottom: 10px;">
              <div class="inner" style="padding: 10px 10px 5px 10px;">
                <h3 id="solve" style="font-size: 1.8rem; margin-bottom: 2px;">0</h3>
                <p style="font-size: 0.75rem; margin-bottom: 0;">Solved</p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-4 col-6">
            <div class="small-box bg-gradient-secondary" style="margin-bottom: 10px;">
              <div class="inner" style="padding: 10px 10px 5px 10px;">
                <h3 id="close" style="font-size: 1.8rem; margin-bottom: 2px;">0</h3>
                <p style="font-size: 0.75rem; margin-bottom: 0;">Closed</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: MTTR Card -->
      <div class="col-lg-4">
        <div class="small-box bg-gradient-purple" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; margin-bottom: 10px; min-height: 145px;">
          <div class="inner" style="padding: 15px;">
            <div class="row">
              <div class="col-6 text-center" style="border-right: 1px solid rgba(255,255,255,0.3);">
                <h3 id="mttr" style="font-size: 2.2rem; margin-bottom: 0;">0</h3>
                <span style="font-size: 0.9rem; color: rgba(255,255,255,0.8);">hours</span>
                <p style="font-size: 0.7rem; margin-top: 5px; font-weight: 600;">MTTR</p>
              </div>
              <div class="col-6 text-center">
                <h3 id="mttr_count" style="font-size: 2.2rem; margin-bottom: 0;">0</h3>
                <span style="font-size: 0.9rem; color: rgba(255,255,255,0.8);">tickets</span>
                <p style="font-size: 0.7rem; margin-top: 5px; font-weight: 600;">COMPLETED</p>
              </div>
            </div>
            <div class="text-center mt-2">
              <span id="mttr_badge" class="badge badge-light" style="padding: 5px 12px; font-size: 0.75rem;">
                <i class="fas fa-info-circle mr-1"></i>No Data
              </span>
            </div>
          </div>
          <div class="icon">
            <i class="fas fa-stopwatch"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="card card-primary card-outline shadow-sm">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Advanced Filters</h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      
      <div class="card-body">
        <form id="filter-form">
          <div class="row">
            
            <!-- Schedule Date Range -->
            <div class="col-md-3">
              <div class="form-group">
                <label><i class="fas fa-calendar-check text-primary"></i> Schedule From</label>
                <input type="date" name="date_from" class="form-control" value="{{date('Y-m-01')}}" />
              </div>
            </div>
            
            <div class="col-md-3">
              <div class="form-group">
                <label><i class="fas fa-calendar-check text-primary"></i> Schedule To</label>
                <input type="date" name="date_end" class="form-control" value="{{date('Y-m-d')}}" />
              </div>
            </div>
            
            <!-- Created Date Range -->
            <div class="col-md-3">
              <div class="form-group">
                <label><i class="fas fa-clock text-info"></i> Created From</label>
                <input type="date" name="created_from" class="form-control" />
              </div>
            </div>
            
            <div class="col-md-3">
              <div class="form-group">
                <label><i class="fas fa-clock text-info"></i> Created To</label>
                <input type="date" name="created_end" class="form-control" />
              </div>
            </div>
            
            <!-- Ticket ID & Title -->
            <div class="col-md-2">
              <div class="form-group">
                <label><i class="fas fa-hashtag text-success"></i> Ticket ID</label>
                <input type="text" name="ticketid" class="form-control" placeholder="Search by ID">
              </div>
            </div>
            
            <div class="col-md-4">
              <div class="form-group">
                <label><i class="fas fa-heading text-success"></i> Title</label>
                <input type="text" name="title" class="form-control" placeholder="Search by title">
              </div>
            </div>
            
            <!-- Category & Tags -->
            <div class="col-md-3">
              <div class="form-group">
                <label><i class="fas fa-folder text-warning"></i> Category</label>
                <select name="id_categori" id="id_categori" class="form-control select2">
                  <option value="">All Categories</option>
                  @foreach ($ticketcategorie as $id => $name)
                  <option value="{{ $id }}">{{ $name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            
            <div class="col-md-3">
              <div class="form-group">
                <label><i class="fas fa-tags text-warning"></i> Tags</label>
                <select name="tags[]" id="tags" class="form-control select2" multiple>
                  @foreach($tags as $id => $name)
                  <option value="{{ $id }}">{{ $name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            
            <!-- Created By & Assigned To -->
            <div class="col-md-3">
              <div class="form-group">
                <label><i class="fas fa-user-plus text-secondary"></i> Created By</label>
                <select name="create_by" id="create_by" class="form-control select2">
                  <option value="">All Users</option>
                  @foreach ($user as $id => $name)
                  <option value="{{ $name }}">{{ $name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            
            <div class="col-md-3">
              <div class="form-group">
                <label><i class="fas fa-user-check text-secondary"></i> Assigned To</label>
                <select name="assign_to" id="assign_to" class="form-control select2">
                  <option value="">All Users</option>
                  @foreach ($user as $id => $name)
                  <option value="{{ $id }}">{{ $name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            
            <!-- Status & Ticket Type -->
            <div class="col-md-3">
              <div class="form-group">
                <label><i class="fas fa-flag text-danger"></i> Status</label>
                <select name="id_status" id="id_status" class="form-control">
                  <option value="">All Status</option>
                  <option value="Open">Open</option>
                  <option value="Inprogress">In Progress</option>
                  <option value="Pending">Pending</option>
                  <option value="Solve">Solved</option>
                  <option value="Close">Closed</option>
                </select>
              </div>
            </div>
            
            <div class="col-md-3">
              <div class="form-group">
                <label><i class="fas fa-sitemap text-purple"></i> Ticket Type</label>
                <select name="ticket_type" id="ticket_type" class="form-control">
                  <option value="">All Types</option>
                  <option value="parent">Parent Only</option>
                  <option value="child">Child Only</option>
                  <option value="standalone">Standalone</option>
                </select>
              </div>
            </div>
            
            <!-- Filter Button -->
            <div class="col-md-12 text-right">
              <button type="button" id="ticket_filter" class="btn btn-primary btn-sm">
                <i class="fas fa-search"></i> Apply Filters
              </button>
              <button type="reset" class="btn btn-secondary btn-sm">
                <i class="fas fa-redo"></i> Reset
              </button>
            </div>
            
          </div>
        </form>
      </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
      <div class="card-header card card-primary card-outline  shadow-sm  shadow-sm">
        <h3 class="card-title"><i class="fas fa-table"></i> Ticket List</h3>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="table-ticket-list" class="table table-bordered table-striped table-hover">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Schedule</th>
                <th>Ticket ID</th>
                <th>Customer Name</th>
                <th>Address</th>
                <th>Merchant</th>
                <th>Status</th>
                <th>Category</th>
                <th>Title</th>
                <th>Tags</th>
                <th>Create By</th>
                <th>Assign to</th>
                <th>Created</th>
                <th>Progress</th>
                <th>MTTR</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
    
  </div>
</section>

@endsection
@section('footer-scripts')
@include('script.ticket_list')
@endsection 

