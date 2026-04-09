@extends('layout.main')
@section('title','JURNAL UMUM')

@section('content')
<style>
.card-header-custom {
  background: #4a90e2;
  color: white;
  padding: 20px;
}
.card-header-custom h3 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}
.summary-card {
  border: none;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transition: transform 0.2s;
}
.summary-card:hover {
  transform: translateY(-2px);
}
.summary-card.debit-card {
  border-left: 4px solid #28a745;
}
.summary-card.debit-card h3 {
  color: #28a745;
}
.summary-card.kredit-card {
  border-left: 4px solid #dc3545;
}
.summary-card.kredit-card h3 {
  color: #dc3545;
}
.summary-card h6 {
  font-size: 0.875rem;
  color: #6c757d;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
}
.financial-table thead th {
  background: #4a90e2;
  color: white;
  font-weight: 600;
  padding: 12px;
}
.financial-table tbody tr.bg-secondary {
  background: #343a40 !important;
  color: white !important;
  font-weight: 700;
}
</style>

<section class="content-header">
  <div class="card shadow-sm">
    <div class="card-header-custom">
      <h3>
        <i class="fas fa-book mr-2"></i>
        JURNAL UMUM (GENERAL JOURNAL)
      </h3>
      <small style="opacity: 0.9;">Semua transaksi jurnal dalam periode tertentu</small>
    </div>

    <div class="card-body">
      <!-- Filter Section -->
      <div class="row pt-2">
        <div class="form-group col-md-3">
          <label><i class="fas fa-calendar-alt"></i> Transaction Date Start</label>
          <div class="input-group mb-3">
            <input type="text" id="jum_date_from_display" class="form-control" autocomplete="off" readonly
                   value="{{ now()->format('d/m/Y') }}" />
            <input type="hidden" name="date_from" id="jum_date_from_hidden" value="{{ now()->toDateString() }}" />
            <div class="input-group-append">
              <div class="input-group-text"><i class="fa fa-calendar"></i></div>
            </div>
          </div>
        </div>
        
        <div class="form-group col-md-3">
          <label><i class="fas fa-calendar-alt"></i> Transaction Date End</label>
          <div class="input-group mb-3">
            <input type="text" id="jum_date_to_display" class="form-control" autocomplete="off" readonly
                   value="{{ date('d/m/Y') }}" />
            <input type="hidden" name="date_end" id="jum_date_to_hidden" value="{{ date('Y-m-d') }}" />
            <div class="input-group-append">
              <div class="input-group-text"><i class="fa fa-calendar"></i></div>
            </div>
          </div>
        </div>
        
        <div class="form-group col-md-2">
          <label>&nbsp;</label>
          <div class="input-group mb-3">
            <button type="button" class="btn bg-gradient-primary btn-primary btn-block" id="jurnal" style="padding: 10px;">
              <i class="fas fa-filter"></i> Filter
            </button>
          </div>
        </div>
      </div>

      <hr>

      <!-- Totals Display -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="summary-card debit-card card">
            <div class="card-body">
              <h6><i class="fas fa-plus-circle mr-1"></i> Total Debet</h6>
              <h3>Rp <span id="total-debet">0</span></h3>
            </div>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="summary-card kredit-card card">
            <div class="card-body">
              <h6><i class="fas fa-minus-circle mr-1"></i> Total Kredit</h6>
              <h3>Rp <span id="total-kredit">0</span></h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="table-responsive">
        <table id="jurnal-table" class="table table-bordered table-striped financial-table">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Date</th>
              <th scope="col">Akun</th>
              <th scope="col">Debet</th>
              <th scope="col">Kredit</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</section>

<!-- Modal View/Edit Jurnal -->
<div class="modal fade" id="modalViewJurnalUmum" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-book mr-2"></i>Detail Jurnal</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="modal-jurnal-umum-content">
        <div class="text-center p-4">
          <i class="fa fa-spinner fa-spin fa-2x"></i><br>Memuat data...
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('footer-scripts')
<script>
$(document).ready(function() {
  var dpOpts = { format: 'dd/mm/yyyy', todayHighlight: true, autoclose: true };
  $('#jum_date_from_display').datepicker(dpOpts).on('changeDate', function(e) {
    var d = e.date;
    $('#jum_date_from_hidden').val(d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'));
  });
  $('#jum_date_to_display').datepicker(dpOpts).on('changeDate', function(e) {
    var d = e.date;
    $('#jum_date_to_hidden').val(d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'));
  });
});

var index = 0;

$('#jurnal').click(function() {
  var index = 0;
  $('#jurnal-table').DataTable().ajax.reload();
});

var table = $('#jurnal-table').DataTable({
  "responsive": true,
  "autoWidth": false,
  "language": {
    "processing": "<span class='fa-stack fa-lg'>\n\
    <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
    </span>&emsp;Processing ..."
  },
  dom: 'Bfrtip',
  buttons: [
    'pageLength', 'copy', 'excel', 'pdf', 'csv', 'print'
  ],
  "lengthMenu": [[20, 50, 100, 200, 500, 1000], [20, 50, 100, 200, 1000]],
  processing: true,
  serverSide: true,
  ajax: {
    url: '/jurnal/getjurnaldata',
    type: 'POST',
    data: function(d) {
      return $.extend({}, d, {
        "date_from": $(document.querySelector('[name="date_from"]')).val(),
        "date_end": $(document.querySelector('[name="date_end"]')).val(),
      });
    },
    dataSrc: function(json) {
      // Update totals in the div
      $('#total-debet').text(json.totals.debet.toLocaleString());
      $('#total-kredit').text(json.totals.kredit.toLocaleString());
      table.start = json.start;
      return json.data;
    },
  },
  columns: [
    { data: null, name: null, orderable: false, searchable: false, className: 'dt-center' },
    { data: 'date', name: 'date', className: 'dt-left', orderable: false, searchable: false },
    { data: 'akun_name', name: 'akun_name', className: 'dt-left', orderable: false, searchable: false },
    { data: 'debet', name: 'debet', className: 'dt-right' },
    { data: 'kredit', name: 'kredit', className: 'dt-right' },
  ],
  rowCallback: function(row, data) {
    if (data.is_group) {
      index++;
      $(row).addClass('bg-light text-bold');
      $('td', row).eq(0).html(data.index);
      $('td:gt(1)', row).remove();
      $('td', row).eq(1).attr('colspan', 4).html(
        '<div class="d-flex align-items-center justify-content-between">'
        + '<span>' + data.description + '</span>'
        + (data.code ? '<span class="badge badge-warning view-jurnal-umum ml-2" data-code="' + data.code + '" style="cursor:pointer;font-size:12px;padding:5px 10px;"><i class="fas fa-edit mr-1"></i>Edit</span>' : '')
        + '</div>'
      );
    } else if (data.akun_name === 'Subtotal') {
      // Baris Subtotal
      $(row).addClass('bg-secondary');
      $('td', row).eq(0).html('');
      $('td', row).eq(2).html('<strong>Subtotal</strong>'); // Kolom akun_name
      $('td', row).eq(3).html(`<strong class="amount-debet">${data.debet}</strong>`); // Kolom debet
      $('td', row).eq(4).html(`<strong class="amount-kredit">${data.kredit}</strong>`); // Kolom kredit
    } else {
      // Detail rows - add color to amounts
      $('td', row).eq(0).html(''); // Kosongkan nomor untuk baris biasa
      
      // Add color classes to amounts
      if (data.debet && data.debet !== '0,00') {
        $('td', row).eq(3).html(`<span class="amount-debet">${data.debet}</span>`);
      }
      if (data.kredit && data.kredit !== '0,00') {
        $('td', row).eq(4).html(`<span class="amount-kredit">${data.kredit}</span>`);
      }
    }
  },
});

// Klik badge Edit
$('#jurnal-table').on('click', '.view-jurnal-umum', function() {
  var code = $(this).data('code');
  $('#modalViewJurnalUmum').modal('show');
  $('#modal-jurnal-umum-content').html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Memuat data...</div>');
  $.ajax({
    url: '/jurnal/show/' + code,
    type: 'GET',
    success: function(html) {
      $('#modal-jurnal-umum-content').html(html);
    },
    error: function() {
      $('#modal-jurnal-umum-content').html('<div class="alert alert-danger">Gagal memuat data jurnal.</div>');
    }
  });
});
</script>
@endsection