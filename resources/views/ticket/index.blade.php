@extends('layout.main')
@section('title','Ticket Management')
@section('content')

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Ticket Management</h1></div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    <div class="card" style="border:1px solid var(--border,#dee2e6)">
      <div class="card-header d-flex align-items-center" style="background:var(--bg-surface-2,#f4f6f8);border-bottom:1px solid var(--border,#dee2e6)">
        <i class="fas fa-ticket-alt mr-2 text-primary"></i>
        <h3 class="card-title mb-0 font-weight-bold">Ticket Management</h3>
      </div>

      <div class="card-body" style="background:var(--bg-surface,#fff)">

        {{-- ===== STATS ===== --}}
        <div class="row mb-3">

          {{-- Left: 6 status cards --}}
          <div class="col-lg-8 col-md-12 mb-2">
            <div class="row">
              <div class="col-4 mb-2">
                <div class="p-2 rounded text-white text-center" style="background:#17a2b8">
                  <div style="font-size:1.7rem;font-weight:700;line-height:1" id="total">0</div>
                  <div style="font-size:.75rem;margin-top:3px;opacity:.9">Total</div>
                </div>
              </div>
              <div class="col-4 mb-2">
                <div class="p-2 rounded text-white text-center bg-danger">
                  <div style="font-size:1.7rem;font-weight:700;line-height:1" id="open">0</div>
                  <div style="font-size:.75rem;margin-top:3px;opacity:.9">Open</div>
                </div>
              </div>
              <div class="col-4 mb-2">
                <div class="p-2 rounded text-white text-center bg-primary">
                  <div style="font-size:1.7rem;font-weight:700;line-height:1" id="inprogress">0</div>
                  <div style="font-size:.75rem;margin-top:3px;opacity:.9">In Progress</div>
                </div>
              </div>
              <div class="col-4 mb-2">
                <div class="p-2 rounded text-white text-center bg-info">
                  <div style="font-size:1.7rem;font-weight:700;line-height:1" id="solve">0</div>
                  <div style="font-size:.75rem;margin-top:3px;opacity:.9">Solve</div>
                </div>
              </div>
              <div class="col-4 mb-2">
                <div class="p-2 rounded text-white text-center bg-secondary">
                  <div style="font-size:1.7rem;font-weight:700;line-height:1" id="close">0</div>
                  <div style="font-size:.75rem;margin-top:3px;opacity:.9">Closed</div>
                </div>
              </div>
              <div class="col-4 mb-2">
                <div class="p-2 rounded text-white text-center bg-warning">
                  <div style="font-size:1.7rem;font-weight:700;line-height:1" id="pending">0</div>
                  <div style="font-size:.75rem;margin-top:3px;opacity:.9">Pending</div>
                </div>
              </div>
            </div>
          </div>

          {{-- Right: MTTR card --}}
          <div class="col-lg-4 col-md-12 mb-2">
            <div class="rounded text-white h-100 d-flex flex-column justify-content-between p-3"
                 style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:130px;position:relative;overflow:hidden">
              <i class="fas fa-tachometer-alt"
                 style="position:absolute;right:-10px;bottom:-10px;font-size:6rem;opacity:.15"></i>
              <div class="row text-center">
                <div class="col-6" style="border-right:1px solid rgba(255,255,255,.3)">
                  <div style="font-size:2rem;font-weight:700;line-height:1" id="mttr">0</div>
                  <div style="font-size:.72rem;letter-spacing:1px;margin-top:4px">hours</div>
                  <div style="font-size:.82rem;font-weight:600;letter-spacing:2px;margin-top:2px">MTTR</div>
                </div>
                <div class="col-6">
                  <div style="font-size:2rem;font-weight:700;line-height:1" id="mttr_count">0</div>
                  <div style="font-size:.72rem;letter-spacing:1px;margin-top:4px">tickets</div>
                  <div style="font-size:.82rem;font-weight:600;letter-spacing:2px;margin-top:2px">RESOLVED</div>
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
        {{-- ===== END STATS ===== --}}

        {{-- ===== FILTERS ===== --}}
        <div class="card card-outline card-primary mb-3">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Advanced Filters</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body pt-2">
            <div class="row">

              <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="form-group">
                  <label class="small font-weight-bold"><i class="far fa-calendar-alt text-primary mr-1"></i>Schedule From</label>
                  <div class="input-group input-group-sm date" id="reservationdate" data-target-input="nearest">
                    <div class="input-group-prepend" data-target="#reservationdate" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                    <input type="text" name="date_from" class="form-control datetimepicker-input"
                           data-target="#reservationdate" value="{{ date('Y-m-01') }}" />
                  </div>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="form-group">
                  <label class="small font-weight-bold"><i class="far fa-calendar-alt text-primary mr-1"></i>Schedule To</label>
                  <div class="input-group input-group-sm date" id="reservationdate2" data-target-input="nearest">
                    <div class="input-group-prepend" data-target="#reservationdate2" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                    <input type="text" name="date_end" class="form-control datetimepicker-input"
                           data-target="#reservationdate2" value="{{ date('Y-m-d') }}" />
                  </div>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="form-group">
                  <label class="small font-weight-bold"><i class="far fa-calendar-check text-info mr-1"></i>Created From</label>
                  <div class="input-group input-group-sm date" id="created_from_picker" data-target-input="nearest">
                    <div class="input-group-prepend" data-target="#created_from_picker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                    <input type="text" name="created_from" class="form-control datetimepicker-input"
                           data-target="#created_from_picker" placeholder="YYYY-MM-DD" />
                  </div>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="form-group">
                  <label class="small font-weight-bold"><i class="far fa-calendar-check text-info mr-1"></i>Created To</label>
                  <div class="input-group input-group-sm date" id="created_end_picker" data-target-input="nearest">
                    <div class="input-group-prepend" data-target="#created_end_picker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                    <input type="text" name="created_end" class="form-control datetimepicker-input"
                           data-target="#created_end_picker" placeholder="YYYY-MM-DD" />
                  </div>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="form-group">
                  <label class="small font-weight-bold"><i class="fas fa-folder-open text-warning mr-1"></i>Category</label>
                  <select name="id_categori" id="id_categori" class="form-control form-control-sm">
                    <option value="">All Categories</option>
                    @foreach ($ticketcategorie as $id => $name)
                      <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="form-group">
                  <label class="small font-weight-bold"><i class="fas fa-flag text-danger mr-1"></i>Status</label>
                  <select name="id_status" id="id_status" class="form-control form-control-sm">
                    <option value="">All Status</option>
                    <option value="Open">Open</option>
                    <option value="Inprogress">Inprogress</option>
                    <option value="Pending">Pending</option>
                    <option value="Solve">Solve</option>
                    <option value="Close">Close</option>
                  </select>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="form-group">
                  <label class="small font-weight-bold"><i class="fas fa-user-check text-success mr-1"></i>Assigned To</label>
                  <select name="assign_to" id="assign_to" class="form-control form-control-sm">
                    <option value="">All Users</option>
                    @foreach ($user as $id => $name)
                      <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="form-group">
                  <label class="small font-weight-bold"><i class="fas fa-user text-secondary mr-1"></i>Created By</label>
                  <select name="create_by" id="create_by" class="form-control form-control-sm">
                    <option value="">All</option>
                    @foreach ($user as $id => $name)
                      <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="form-group">
                  <label class="small font-weight-bold"><i class="fas fa-hashtag text-secondary mr-1"></i>Ticket ID</label>
                  <input type="text" name="ticketid" class="form-control form-control-sm" placeholder="e.g. 123">
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="form-group">
                  <label class="small font-weight-bold"><i class="fas fa-heading text-secondary mr-1"></i>Title</label>
                  <input type="text" name="title" class="form-control form-control-sm" placeholder="Search title...">
                </div>
              </div>

              <div class="col-lg-4 col-md-6 col-sm-12">
                <div class="form-group">
                  <label class="small font-weight-bold"><i class="fas fa-tags text-info mr-1"></i>Tags</label>
                  <select name="tags[]" id="tags" class="form-control form-control-sm select2" multiple>
                    @foreach($tags as $id => $name)
                      <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

            </div>
            <div class="row mt-1">
              <div class="col-12 text-right">
                <button type="button" class="btn btn-primary btn-sm" id="ticket_filter">
                  <i class="fas fa-search mr-1"></i> Apply Filters
                </button>
                <button type="button" class="btn btn-default btn-sm ml-1" id="ticket_reset">
                  <i class="fas fa-redo mr-1"></i> Reset
                </button>
              </div>
            </div>
          </div>
        </div>
        {{-- ===== END FILTERS ===== --}}

        {{-- ===== TABLE ===== --}}
        <div class="table-responsive">
          <table id="table-ticket-list" class="table table-bordered table-striped table-sm">
            <thead class="thead-dark">
              <tr>
                <th>#</th>
                <th>Schedule</th>
                <th>Ticket ID</th>
                <th>Customer</th>
                <th>Address</th>
                <th>Merchant</th>
                <th>Status</th>
                <th>Category</th>
                <th>Title</th>
                <th>Tags</th>
                <th>Created By</th>
                <th>Assign To</th>
                <th>Created At</th>
                <th>Closed At</th>
                <th>Progress</th>
                <th>MTTR</th>
              </tr>
            </thead>
          </table>
        </div>
        {{-- ===== END TABLE ===== --}}

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
@include('script.ticket_list')
@endsection
