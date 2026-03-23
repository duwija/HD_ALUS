@extends('layout.main')
@section('title','JURNAL UMUM')

@section('content')
<style>
/* Modern Card Styling */
.jurnal-card {
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.jurnal-header {
  background: #4a90e2;
  color: white;
  border-radius: 10px 10px 0 0;
  padding: 20px;
}

.jurnal-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
}

.jurnal-title i {
  margin-right: 10px;
  font-size: 28px;
}

/* Total Display Cards */
.totals-container {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
}

.total-card {
  flex: 1;
  padding: 20px;
  border-radius: 8px;
  color: white;
  display: flex;
  flex-direction: column;
  transition: transform 0.3s;
}

.total-card:hover {
  transform: translateY(-3px);
}

.total-card.debet {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.total-card.kredit {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.total-card-label {
  font-size: 14px;
  opacity: 0.9;
  margin-bottom: 5px;
}

.total-card-value {
  font-size: 24px;
  font-weight: 700;
}

/* Table Styling */
#jurnal-table thead th {
  background: #4a90e2;
  color: white;
  font-weight: 600;
  border: none;
  padding: 12px;
}

#jurnal-table tbody tr.bg-light {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  font-weight: 600;
}

#jurnal-table tbody tr.bg-secondary {
  background: #343a40 !important;
  color: white !important;
  font-weight: 700;
}

#jurnal-table tbody tr.bg-secondary strong {
  color: white !important;
}

#jurnal-table tbody tr:hover:not(.bg-light):not(.bg-secondary) {
  background-color: #f8f9fa;
  transition: background-color 0.3s;
}

/* Amount Styling */
.amount-debet {
  color: #28a745;
  font-weight: 600;
}

.amount-kredit {
  color: #007bff;
  font-weight: 600;
}

/* Subtotal specific colors */
tr.bg-secondary .amount-debet {
  color: #5dff8f !important;
}

tr.bg-secondary .amount-kredit {
  color: #5dcdff !important;
}
</style>

<section class="content-header">
  <div class="card jurnal-card">
    <div class="jurnal-header">
      <h3 class="jurnal-title">
        <i class="fas fa-book"></i>
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
            <div class="input-group p-1 date" id="reservationdate" data-target-input="nearest">
              <input type="text" name="date_from" id="date" class="form-control datetimepicker-input" 
                     data-target="#reservationdate" value="{{ now()->toDateString() }}" />
              <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="form-group col-md-3">
          <label><i class="fas fa-calendar-alt"></i> Transaction Date End</label>
          <div class="input-group mb-3">
            <div class="input-group p-1 date" id="reservationdate2" data-target-input="nearest">
              <input type="text" name="date_end" id="date2" class="form-control datetimepicker-input" 
                     data-target="#reservationdate2" value="{{date('Y-m-d')}}" />
              <div class="input-group-append" data-target="#reservationdate2" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
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
      <div class="totals-container">
        <div class="total-card debet">
          <div class="total-card-label">
            <i class="fas fa-plus-circle"></i> Total Debet
          </div>
          <div class="total-card-value">
            Rp <span id="total-debet">0</span>
          </div>
        </div>
        
        <div class="total-card kredit">
          <div class="total-card-label">
            <i class="fas fa-minus-circle"></i> Total Kredit
          </div>
          <div class="total-card-value">
            Rp <span id="total-kredit">0</span>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="table-responsive">
        <table id="jurnal-table" class="table table-bordered table-striped">
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
@endsection

@section('footer-scripts')
<script>
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
      // Set nomor dan description untuk baris grup
      $(row).addClass('bg-light text-bold');
      $('td', row).eq(0).html(data.index); // Nomor dan description
      $('td:gt(1)', row).remove(); // Hapus kolom lain di baris grup
      $('td', row).eq(1).attr('colspan', 4).html(`
        <strong>${data.description}</strong>
      `);
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
</script>
@endsection