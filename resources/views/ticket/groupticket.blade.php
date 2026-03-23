@extends('layout.main')
@section('title','Group Ticket Management')
@section('content')

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-12">
        <h1><i class="fas fa-layer-group mr-2 text-primary"></i>Group Ticket Management</h1>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    {{-- ===== STATS ROW ===== --}}
    <div class="row mb-3">

      {{-- Left: 6 stat tiles in 2-row x 3-col grid --}}
      <div class="col-lg-8 col-md-12 mb-2">
        <div class="row no-gutters">

          <div class="col-4 pr-1 pb-1">
            <div class="p-3 rounded text-white" style="background:#1a1a2e;min-height:70px">
              <div style="font-size:1.8rem;font-weight:700;line-height:1" id="total">0</div>
              <div style="font-size:.78rem;margin-top:4px">Total Groups</div>
            </div>
          </div>

          <div class="col-4 pr-1 pb-1">
            <div class="p-3 rounded text-white bg-success" style="min-height:70px">
              <div style="font-size:1.8rem;font-weight:700;line-height:1" id="open">0</div>
              <div style="font-size:.78rem;margin-top:4px">Open</div>
            </div>
          </div>

          <div class="col-4 pb-1">
            <div class="p-3 rounded text-white bg-warning" style="min-height:70px">
              <div style="font-size:1.8rem;font-weight:700;line-height:1" id="inprogress">0</div>
              <div style="font-size:.78rem;margin-top:4px">Inprogress</div>
            </div>
          </div>

          <div class="col-4 pr-1">
            <div class="p-3 rounded text-white bg-primary" style="min-height:70px">
              <div style="font-size:1.8rem;font-weight:700;line-height:1" id="pending">0</div>
              <div style="font-size:.78rem;margin-top:4px">Pending</div>
            </div>
          </div>

          <div class="col-4 pr-1">
            <div class="p-3 rounded text-white" style="background:#17a589;min-height:70px">
              <div style="font-size:1.8rem;font-weight:700;line-height:1" id="solve">0</div>
              <div style="font-size:.78rem;margin-top:4px">Solved</div>
            </div>
          </div>

          <div class="col-4">
            <div class="p-3 rounded text-white bg-secondary" style="min-height:70px">
              <div style="font-size:1.8rem;font-weight:700;line-height:1" id="close">0</div>
              <div style="font-size:.78rem;margin-top:4px">Closed</div>
            </div>
          </div>

        </div>
      </div>

      {{-- Right: MTTR card (purple gradient) --}}
      <div class="col-lg-4 col-md-12 mb-2">
        <div class="rounded text-white h-100 d-flex flex-column justify-content-between p-3"
             style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:140px;position:relative;overflow:hidden">
          <i class="fas fa-tachometer-alt"
             style="position:absolute;right:-10px;bottom:-10px;font-size:6rem;opacity:.15"></i>
          <div class="row text-center">
            <div class="col-6" style="border-right:1px solid rgba(255,255,255,.3)">
              <div style="font-size:2rem;font-weight:700;line-height:1" id="mttr_hours">0</div>
              <div style="font-size:.72rem;letter-spacing:1px;margin-top:4px">hours</div>
              <div style="font-size:.85rem;font-weight:600;letter-spacing:2px;margin-top:2px">MTTR</div>
            </div>
            <div class="col-6">
              <div style="font-size:2rem;font-weight:700;line-height:1" id="mttr_count">0</div>
              <div style="font-size:.72rem;letter-spacing:1px;margin-top:4px">tickets</div>
              <div style="font-size:.85rem;font-weight:600;letter-spacing:2px;margin-top:2px">COMPLETED</div>
            </div>
          </div>
          <div class="text-center mt-2">
            <span class="badge badge-light" id="mttr_badge" style="font-size:.8rem;padding:5px 14px">
              <i class="fas fa-circle mr-1 text-secondary"></i> No Data
            </span>
          </div>
        </div>
      </div>

    </div>

    {{-- ===== ADVANCED FILTERS ===== --}}
    <div class="card card-outline card-primary mb-3">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Advanced Filters</h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      <div class="card-body pt-3">
        <div class="row">

          <div class="col-lg-3 col-md-6">
            <div class="form-group">
              <label><i class="far fa-calendar-alt text-primary mr-1"></i> <strong>Start Date</strong></label>
              <div class="input-group date" id="gt_date_from_picker" data-target-input="nearest">
                <div class="input-group-prepend" data-target="#gt_date_from_picker" data-toggle="datetimepicker">
                  <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                </div>
                <input type="text" name="date_from" id="gt_date_from"
                       class="form-control datetimepicker-input"
                       data-target="#gt_date_from_picker"
                       value="{{ date('Y-m-01') }}" />
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="form-group">
              <label><i class="far fa-calendar-alt text-primary mr-1"></i> <strong>End Date</strong></label>
              <div class="input-group date" id="gt_date_end_picker" data-target-input="nearest">
                <div class="input-group-prepend" data-target="#gt_date_end_picker" data-toggle="datetimepicker">
                  <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                </div>
                <input type="text" name="date_end" id="gt_date_end"
                       class="form-control datetimepicker-input"
                       data-target="#gt_date_end_picker"
                       value="{{ date('Y-m-d') }}" />
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="form-group">
              <label><i class="fas fa-folder-open text-warning mr-1"></i> <strong>Category</strong></label>
              <select name="id_categori" id="id_categori" class="form-control">
                <option value="">All Categories</option>
                @foreach ($ticketcategorie as $id => $name)
                  <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="form-group">
              <label><i class="fas fa-user-check text-success mr-1"></i> <strong>Assigned To</strong></label>
              <select name="assign_to" id="assign_to" class="form-control">
                <option value="">All Users</option>
                @foreach ($user as $id => $name)
                  <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="form-group">
              <label><i class="fas fa-flag text-danger mr-1"></i> <strong>Status</strong></label>
              <select name="id_status" id="id_status" class="form-control">
                <option value="">All Status</option>
                <option value="Open">Open</option>
                <option value="Inprogress">Inprogress</option>
                <option value="Pending">Pending</option>
                <option value="Solve">Solve</option>
                <option value="Close">Close</option>
              </select>
            </div>
          </div>

        </div>

        <div class="row mt-1">
          <div class="col-12 text-right">
            <button type="button" class="btn btn-primary" id="groupticket_filter">
              <i class="fas fa-search mr-1"></i> Apply Filters
            </button>
            <button type="button" class="btn btn-default ml-1" id="groupticket_reset">
              <i class="fas fa-redo mr-1"></i> Reset
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- ===== TABLE ===== --}}
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-th mr-1"></i> Group Ticket List</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table id="table-groupticket-list" class="table table-bordered table-striped table-sm">
            <thead>
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
                <th>Assign to</th>
                <th>Created</th>
                <th>Closed</th>
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
{{-- Datepicker dependencies (Tempus Dominus Bootstrap 4) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js"></script>
@include('script.groupticket_list')
@endsection
