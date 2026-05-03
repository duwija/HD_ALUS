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
        <table class="table table-bordered table-sm" id="logwa_table" style="width:100%">
          <thead>
            <tr>
              <th>#</th>
              <th>Tanggal</th>
              <th>Nomor</th>
              <th>Session</th>
              <th>Pesan</th>
              <th>Status</th>
              <th>Error</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</section>

{{-- Modal lihat pesan lengkap --}}
<div class="modal fade" id="modal-wa-message" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white"><i class="fab fa-whatsapp mr-1"></i> Pesan WhatsApp</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="row mb-2">
          <div class="col-sm-4"><strong>Nomor:</strong> <span id="modal-wa-number"></span></div>
          <div class="col-sm-4"><strong>Session:</strong> <span id="modal-wa-session"></span></div>
          <div class="col-sm-4"><strong>Waktu:</strong> <span id="modal-wa-time"></span></div>
        </div>
        <div class="row mb-2">
          <div class="col-sm-4"><strong>Status:</strong> <span id="modal-wa-status"></span></div>
          <div class="col-sm-8"><strong>Error:</strong> <span id="modal-wa-error" class="text-danger"></span></div>
        </div>
        <hr>
        <label><strong>Isi Pesan:</strong></label>
        <pre id="modal-wa-msg" style="white-space:pre-wrap;word-break:break-word;background:#f8f9fa;padding:12px;border-radius:6px;max-height:400px;overflow-y:auto;font-family:inherit;font-size:13px;"></pre>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('footer-scripts')
<script>
  $('#logwa_filter').click(function() {
    waLogsTable.ajax.reload();
  });

  var waLogsTable = $('#logwa_table').DataTable({
    processing: true,
    serverSide: true,
    responsive: false,
    autoWidth: false,
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
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
      { data: 'created_at', name: 'created_at', width: '140px', render: function(data) {
          if (!data) return '-';
          var d = new Date(data);
          var pad = n => String(n).padStart(2,'0');
          return pad(d.getDate()) + '/' + pad(d.getMonth()+1) + '/' + d.getFullYear()
               + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
        }
      },
      { data: 'number', name: 'number', width: '130px' },
      { data: 'session', name: 'session', width: '110px' },
      { data: 'message', name: 'message', render: function(data) {
          if (!data) return '-';
          var short = data.length > 60 ? data.substring(0, 60) + '…' : data;
          return '<span class="text-muted small">' + $('<div>').text(short).html() + '</span>';
        }
      },
      { data: 'status', name: 'status', width: '80px', render: function(data) {
          if (!data) return '-';
          var cls = data === 'sent' ? 'badge-success' : (data === 'failed' ? 'badge-warning' : 'badge-danger');
          return '<span class="badge ' + cls + '">' + data + '</span>';
        }
      },
      { data: 'error', name: 'error', render: function(data) {
          if (!data) return '-';
          var short = data.length > 50 ? data.substring(0, 50) + '…' : data;
          return '<span class="text-danger small">' + $('<div>').text(short).html() + '</span>';
        }
      },
      { data: null, name: 'aksi', orderable: false, searchable: false, width: '60px',
        render: function(data, type, row) {
          return '<button class="btn btn-xs btn-info btn-lihat-pesan" data-row=\'' + JSON.stringify(row).replace(/'/g,"&#39;") + '\'><i class="fas fa-eye"></i> Lihat</button>';
        }
      },
    ],
    order: [[1, 'desc']]
  });

  $(document).on('click', '.btn-lihat-pesan', function() {
    var row = $(this).data('row');
    // Format tanggal
    var tgl = '-';
    if (row.created_at) {
      var d = new Date(row.created_at);
      var pad = n => String(n).padStart(2,'0');
      tgl = pad(d.getDate()) + '/' + pad(d.getMonth()+1) + '/' + d.getFullYear()
          + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    }
    var statusCls = row.status === 'sent' ? 'badge-success' : (row.status === 'failed' ? 'badge-warning' : 'badge-danger');
    $('#modal-wa-number').text(row.number || '-');
    $('#modal-wa-session').text(row.session || '-');
    $('#modal-wa-time').text(tgl);
    $('#modal-wa-status').html('<span class="badge ' + statusCls + '">' + (row.status || '-') + '</span>');
    $('#modal-wa-error').text(row.error || '-');
    $('#modal-wa-msg').text(row.message || '-');
    $('#modal-wa-message').modal('show');
  });
</script>
@endsection
