@extends('layout.main')
@section('title','My Ticket')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">My Ticket</h1></div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    <div class="card" style="border:1px solid var(--border,#dee2e6)">
      <div class="card-header d-flex align-items-center" style="background:var(--bg-surface-2,#f4f6f8);border-bottom:1px solid var(--border,#dee2e6)">
        <i class="fas fa-ticket-alt mr-2 text-primary"></i>
        <h3 class="card-title mb-0 font-weight-bold">{{ $title }}</h3>
      </div>

      <div class="card-body" style="background:var(--bg-surface,#fff)">

        {{-- ===== STATS ===== --}}
        <div class="row mb-3">

          {{-- Left: 6 status cards in 2 columns --}}
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

        {{-- ===== FILTER BAR ===== --}}
        <div class="card card-outline card-primary mb-3">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filters</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body pt-2">
            <div class="row align-items-end">
              <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="form-group mb-2">
                  <label class="mb-1 small font-weight-bold"><i class="far fa-calendar-alt text-primary mr-1"></i>Start Date</label>
                  <div class="input-group input-group-sm date" id="dp-myStart" data-target-input="nearest">
                    <div class="input-group-prepend" data-target="#dp-myStart" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                    <input type="text" name="date_from"
                           class="form-control datetimepicker-input"
                           data-target="#dp-myStart"
                           value="{{ date('Y-m-01') }}" />
                  </div>
                </div>
              </div>
              <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="form-group mb-2">
                  <label class="mb-1 small font-weight-bold"><i class="far fa-calendar-alt text-primary mr-1"></i>End Date</label>
                  <div class="input-group input-group-sm date" id="dp-myEnd" data-target-input="nearest">
                    <div class="input-group-prepend" data-target="#dp-myEnd" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                    <input type="text" name="date_end"
                           class="form-control datetimepicker-input"
                           data-target="#dp-myEnd"
                           value="{{ date('Y-m-d') }}" />
                  </div>
                </div>
              </div>
              <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="form-group mb-2">
                  <label class="mb-1 small font-weight-bold"><i class="fas fa-flag text-danger mr-1"></i>Status</label>
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
                <div class="form-group mb-2">
                  <label class="mb-1 small font-weight-bold">&nbsp;</label>
                  <div>
                    <button type="button" class="btn btn-primary btn-sm" id="myticket_filter">
                      <i class="fas fa-search mr-1"></i> Apply Filters
                    </button>
                    <button type="button" class="btn btn-default btn-sm ml-1" id="myticket_reset">
                      <i class="fas fa-redo mr-1"></i> Reset
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        {{-- ===== END FILTER BAR ===== --}}

        {{-- ===== DATATABLE ===== --}}
        <div class="table-responsive">
          <table id="table-myticket-list" class="table table-bordered table-striped table-sm">
            <thead class="thead-dark">
              <tr>
                <th>#</th>
                <th>Ticket ID</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Category</th>
                <th>Title</th>
                <th>Assign To</th>
                <th>Schedule</th>
                <th>MTTR</th>
              </tr>
            </thead>
          </table>
        </div>
        {{-- ===== END DATATABLE ===== --}}

      </div>
    </div>

  </div>
</section>

@endsection
@section('footer-scripts')
@include('script.myticket_list')
@endsection


