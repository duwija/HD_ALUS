@extends('layout.main')
@section('title','Log WA')
@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold">Log WhatsApp</h3>
    </div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="form-group col-md-2">
          <label>Dari Tanggal</label>
          <input type="date" id="logwa_date_from" class="form-control" value="{{ date('Y-m-01') }}">
        </div>
        <div class="form-group col-md-2">
          <label>Sampai Tanggal</label>
          <input type="date" id="logwa_date_end" class="form-control" value="{{ date('Y-m-d') }}">
        </div>
        <div class="form-group col-md-2">
          <label>Nomor</label>
          <input type="text" id="logwa_number" class="form-control">
        </div>
        <div class="form-group col-md-2">
          <label>Session</label>
          <select id="logwa_session" class="form-control">
            <option value="">Semua</option>
            @foreach($sessions as $s)
            <option value="{{ $s }}">{{ $s }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-2">
          <label>Status</label>
          <select id="logwa_status" class="form-control">
            <option value="">Semua</option>
            <option value="sent">Terkirim</option>
            <option value="failed">Gagal</option>
            <option value="error">Error</option>
          </select>
        </div>
        <div class="form-group col-md-2 align-self-end">
          <button class="btn btn-primary" id="logwa_filter">Filter</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered" id="logwa_table">
          <thead>
            <tr>
              <th>#</th>
              <th>Tanggal</th>
              <th>Nomor</th>
              <th>Session</th>
              <th>Pesan</th>
              <th>Status</th>
              <th>Error</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</section>
@endsection

@section('footer-scripts')
<script>
  $('#logwa_filter').click(function() {
    $('#logwa_table').DataTable().ajax.reload();
  });

  $('#logwa_table').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: '/wa/logs/table',
      method: 'POST',
      data: function(d) {
        return Object.assign(d, {
          date_from: $('#logwa_date_from').val(),
          date_end: $('#logwa_date_end').val(),
          number: $('#logwa_number').val(),
          session: $('#logwa_session').val(),
          status: $('#logwa_status').val(),
          _token: '{{ csrf_token() }}'
        });
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'created_at', name: 'created_at' },
      { data: 'number', name: 'number' },
      { data: 'session', name: 'session' },
      { data: 'message', name: 'message' },
      { data: 'status', name: 'status' },
      { data: 'error', name: 'error' },
      ],
    order: [[1, 'desc']]
  });
</script>
@endsection
