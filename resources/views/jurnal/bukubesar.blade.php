@extends('layout.main')
@section('title','BUKU BESAR')

@section('content')
<style>
/* Card Header */
.card-header-custom {
  background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
  color: white;
  padding: 20px;
}
.card-header-custom h3 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}
.card-subtitle {
  font-size: 0.875rem;
  opacity: 0.9;
  margin-top: 5px;
}

/* Filter Section */
.filter-section {
  background-color: #f8f9fa;
  padding: 1.5rem;
  border-radius: 0.25rem;
  margin-bottom: 1.5rem;
}
.filter-label {
  font-weight: 600;
  color: #495057;
}

/* Table Styling */
#bukubesar-table thead th {
  background: #4a90e2;
  color: white;
  font-weight: 600;
  padding: 12px;
  text-align: center;
}
#bukubesar-table tbody tr.bg-light {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%) !important;
  font-weight: 600;
  border-top: 2px solid #dee2e6;
}
#bukubesar-table tbody tr.bg-secondary {
  background: #343a40 !important;
  color: white !important;
  font-weight: 700;
  border-top: 2px solid #dee2e6;
  border-bottom: 2px solid #dee2e6;
}
#bukubesar-table tbody tr.bg-secondary strong {
  color: white !important;
}
#bukubesar-table tbody tr:hover:not(.bg-light):not(.bg-secondary) {
  background-color: #f8f9fa;
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
tr.bg-secondary .amount-debet {
  color: #5dff8f !important;
}
tr.bg-secondary .amount-kredit {
  color: #5dcdff !important;
}

/* Badge Styling */
.badge-info {
  background-color: #17a2b8;
  cursor: pointer;
  transition: all 0.2s;
  color: white;
}
.badge-info:hover {
  background-color: #138496;
  transform: scale(1.05);
}
.badge-primary {
  background-color: #4a90e2;
  cursor: pointer;
  transition: all 0.2s;
}
.badge-primary:hover {
  background-color: #357abd;
  transform: scale(1.05);
}

/* DataTables Custom Styling */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter {
  margin-bottom: 15px;
}
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
  margin-top: 15px;
}

/* Button Styling */
.btn-primary {
  background: #4a90e2;
  border: none;
  transition: all 0.3s;
}
.btn-primary:hover {
  background: #357abd;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(74, 144, 226, 0.3);
}

/* Total Cards */
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
</style>

<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header-custom">
      <h3 class="card-title-custom">
        <i class="fas fa-book-open"></i>
        BUKU BESAR (GENERAL LEDGER)
      </h3>
      <div class="card-subtitle">Ringkasan transaksi per akun dengan saldo berjalan</div>
    </div>

    <div class="card-body">
      <!-- Filter Section -->
      <div class="row pt-3 pb-2">
        <div class="form-group col-md-3">
          <label class="filter-label">
            <i class="fas fa-calendar-alt"></i> Transaction Date Start
          </label>
          <div class="input-group">
            <input type="text" id="bb_date_from_display" class="form-control" autocomplete="off" readonly
                   value="{{ date('d/m/Y', strtotime(date('Y-m-01'))) }}" />
            <input type="hidden" name="date_from" id="bb_date_from_hidden" value="{{date('Y-m-01')}}" />
            <div class="input-group-append">
              <div class="input-group-text"><i class="fa fa-calendar"></i></div>
            </div>
          </div>
        </div>
        
        <div class="form-group col-md-3">
          <label class="filter-label">
            <i class="fas fa-calendar-alt"></i> Transaction Date End
          </label>
          <div class="input-group">
            <input type="text" id="bb_date_end_display" class="form-control" autocomplete="off" readonly
                   value="{{ date('d/m/Y') }}" />
            <input type="hidden" name="date_end" id="bb_date_end_hidden" value="{{date('Y-m-d')}}" />
            <div class="input-group-append">
              <div class="input-group-text"><i class="fa fa-calendar"></i></div>
            </div>
          </div>
        </div>
        
        <div class="form-group col-md-4">
          <label class="filter-label">
            <i class="fas fa-list"></i> Kode Akun
          </label>
          <select name="akun_filter" id="akun_filter" class="form-control select2">
            <option value="">All Akun</option>
            @foreach ($akun as $akun_code => $name)
              @php
                $isParent = strpos($name, '(Header)') !== false;
                $displayName = $isParent ? str_replace(' (Header)', '', $name) : $name;
              @endphp
              <option value="{{ $akun_code }}" 
                      data-is-parent="{{ $isParent ? 'true' : 'false' }}"
                      {{ $isParent ? 'disabled' : '' }}>
                {{ $displayName }}
              </option>
            @endforeach
          </select>
        </div>
        
        <div class="form-group col-md-2">
          <label class="filter-label">&nbsp;</label>
          <button type="button" class="btn btn-primary btn-block" id="bukubesar" style="padding: 10px;">
            <i class="fas fa-filter"></i> Filter
          </button>
        </div>
      </div>

      <hr style="border-top: 2px solid #e9ecef;">

      <!-- Total Cards -->
      <div class="totals-container">
        <div class="total-card debet">
          <div class="total-card-label">
            <i class="fas fa-plus-circle"></i> Total Debet
          </div>
          <div class="total-card-value">
            Rp <span id="total-debet-card">0</span>
          </div>
        </div>
        
        <div class="total-card kredit">
          <div class="total-card-label">
            <i class="fas fa-minus-circle"></i> Total Kredit
          </div>
          <div class="total-card-value">
            Rp <span id="total-kredit-card">0</span>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="table-responsive">
        <table id="bukubesar-table" class="table table-bordered table-striped table-hover">
          <thead>
            <tr>
              <th style="width: 5%;">#</th>
              <th style="width: 10%;">Date</th>
              <th style="width: 10%;">Code</th>
              <th style="width: 25%;">Akun</th>
              <th style="width: 15%;">Debet</th>
              <th style="width: 15%;">Kredit</th>
              <th style="width: 15%;">Saldo</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>

      <!-- Modal View Jurnal -->
      <div class="modal fade" id="modalViewJurnal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content" style="overflow:hidden;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius:calc(.3rem - 1px);border-top-right-radius:calc(.3rem - 1px);">
              <h5 class="modal-title">Detail Jurnal</h5>
              <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body" id="modal-jurnal-content">
              <div class="text-center p-4">
                <i class="fa fa-spinner fa-spin fa-2x"></i><br>
                Memuat data...
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
@endsection

@section('footer-scripts')

<script>
$(document).ready(function() {
 var index = 0;
 
 // Initialize Select2 with custom template
 $('#akun_filter').select2({
   placeholder: 'Pilih Kode Akun',
   allowClear: true,
   templateResult: function(data) {
     if (!data.id) {
       return data.text;
     }
     
     var $option = $(data.element);
     var isParent = $option.attr('data-is-parent') === 'true';
     
     var $result = $('<span></span>');
     $result.text(data.text);
     
     if (isParent) {
       // Parent: RATA KIRI, bold, gray background
       $result.css({
         'font-weight': 'bold',
         'color': '#212529',
         'background-color': '#e9ecef',
         'padding-left': '0px'
       });
       $result.attr('data-is-parent', 'true');
     } else {
       // Child/Standalone: MENJOROK KANAN
       $result.css({
         'padding-left': '15px'
       });
       $result.attr('data-is-parent', 'false');
     }
     
     return $result;
   },
   templateSelection: function(data) {
     if (!data.id) {
       return data.text;
     }
     return data.text;
   }
 });
 
 // Initialize date pickers
 var dpOpts = { format: 'dd/mm/yyyy', todayHighlight: true, autoclose: true };
 $('#bb_date_from_display').datepicker(dpOpts).on('changeDate', function(e) {
   var d = e.date;
   $('#bb_date_from_hidden').val(d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'));
 });
 $('#bb_date_end_display').datepicker(dpOpts).on('changeDate', function(e) {
   var d = e.date;
   $('#bb_date_end_hidden').val(d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'));
 });
 
 $('#bukubesar').click(function() 
 {
  var index=0;
  $('#bukubesar-table').DataTable().ajax.reload();
 });

 var table = $('#bukubesar-table').DataTable({
   "responsive": true,
   "autoWidth": false,
       // "searching": true,
   "language": {
    "processing": "<span class='fa-stack fa-lg'>\n\
    <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
    </span>&emsp;Processing ..."
  },
  dom: 'Bfrtip',
  buttons: [
    'pageLength','copy', 'excel', 'pdf', 'csv', 'print'
    ],
  "lengthMenu": [[10,50, 100, 200, 500, 1000], [10,50, 100, 200, 1000]],
  processing: true,
  serverSide: true,
  ajax: {

    url: '/jurnal/getbukubesardata',
    type: 'POST',
    data: function ( d ) {
     return $.extend( {}, d, {
      "date_from": $(document.querySelector('[name="date_from"]')).val(),
      "date_end": $(document.querySelector('[name="date_end"]')).val(),
      "akun_filter": $(document.querySelector('[name="akun_filter"]')).val(),

    } );
   },
   dataSrc: function (json) {
    // Update totals in the cards
    $('#total-debet-card').text(json.totals.debet);
    $('#total-kredit-card').text(json.totals.kredit);
    table.start = json.start;
    return json.data; 

  },
},
columns: [
  { data: null, name: null, orderable: false, searchable: false, className: 'dt-center' },
  { data: 'date', name: 'date', className: 'dt-left', orderable: false, searchable: false },
  { 
    data: 'code', 
    name: 'code', 
    className: 'dt-left', 
    orderable: false, 
    searchable: false,
    render: function (data, type, row) {
      if (!data) return '-';
      return `<span class="badge badge-info cursor-pointer view-jurnal" data-code="${data}" style="font-size: 12px; padding: 5px 10px;">
      ${data}
      </span>`;
    }
  },
  { data: 'akun_name', name: 'akun_name', className: 'dt-left', orderable: false, searchable: false },
  { 
    data: 'debet', 
    name: 'debet', 
    className: 'dt-right',
    render: function (data, type, row) {
      if (!data || data === '-' || data === '0' || data === '0,00') return '-';
      return `<span class="amount-debet">${data}</span>`;
    }
  },
  { 
    data: 'kredit', 
    name: 'kredit', 
    className: 'dt-right',
    render: function (data, type, row) {
      if (!data || data === '-' || data === '0' || data === '0,00') return '-';
      return `<span class="amount-kredit">${data}</span>`;
    }
  },
  { 
    data: 'saldo', 
    name: 'saldo', 
    className: 'dt-right',
    render: function (data, type, row) {
      if (!data || data === '-') return '-';
      // Determine if saldo is positive (debet) or negative (kredit) based on formatting
      if (data.includes('(') || data.startsWith('-')) {
        return `<span class="amount-kredit">${data}</span>`;
      }
      return `<span class="amount-debet">${data}</span>`;
    }
  }
],
rowCallback: function (row, data) {
  if (data.is_group) {
    index++;
    $(row).addClass('bg-light text-bold');
    $('td', row).eq(0).html(data.index); // Nomor dan description
    $('td', row).eq(1).html(`<strong>${data.description}</strong>`);
    $('td', row).eq(2).html('');
    $('td', row).eq(3).html(`Saldo Awal`);
    // Apply amount styling to Saldo Awal
    if (data.debet && data.debet !== '-' && data.debet !== '0') {
      $('td', row).eq(4).html(`<span class="amount-debet">${data.debet}</span>`);
    }
    if (data.kredit && data.kredit !== '-' && data.kredit !== '0') {
      $('td', row).eq(5).html(`<span class="amount-kredit">${data.kredit}</span>`);
    }
    if (data.saldo && data.saldo !== '-') {
      var saldoClass = (data.saldo.includes('(') || data.saldo.startsWith('-')) ? 'amount-kredit' : 'amount-debet';
      $('td', row).eq(6).html(`<span class="${saldoClass}">${data.saldo}</span>`);
    }
  } else if (data.akun_name === 'Saldo Akhir') {
    $(row).addClass('bg-secondary');
    $('td', row).eq(0).html('');
    $('td', row).eq(2).html('');
    $('td', row).eq(3).html('<strong>Saldo Akhir</strong>');
    $('td', row).eq(4).html(`<strong class="amount-debet">${data.debet}</strong>`);
    $('td', row).eq(5).html(`<strong class="amount-kredit">${data.kredit}</strong>`);
    // Determine saldo color
    var saldoClass = (data.saldo.includes('(') || data.saldo.startsWith('-')) ? 'amount-kredit' : 'amount-debet';
    $('td', row).eq(6).html(`<strong class="${saldoClass}">${data.saldo}</strong>`);
  } else {
    $('td', row).eq(0).html(''); // Kosongkan nomor untuk baris biasa
  }
}
      // drawCallback: function (settings) {
        // Nomor hanya untuk baris grup
        // var api = this.api();
        // var rows = api.rows({ page: 'current' }).nodes();
         // var startIndex = settings._iDisplayStart; // Offset data dari DataTables
    // var index = startIndex + 1; // Hitungan nomor dimulai dari offset


         // api.column(0, { page: 'current' }).data().each(function (data, i) {
         //  if (data.is_group) {
            // Set nomor dan tambahkan description
        // $(rows).eq(i).find('td:first').html(`<strong>${index++}</strong>`);
        //   }
        // });
       // },
      });


 // Event klik badge
 $('#bukubesar-table').on('click', '.view-jurnal', function () {
  const code = $(this).data('code');
  $('#modalViewJurnal').modal('show');

  // Loading state
  $('#modal-jurnal-content').html(`
    <div class="text-center p-4">
    <i class="fa fa-spinner fa-spin fa-2x"></i><br>
    Memuat data...
    </div>
    `);

  // Ambil konten via AJAX
  $.ajax({
    url: '/jurnal/show/' + code,
    type: 'GET',
    success: function (html) {
      $('#modal-jurnal-content').html(html);
    },
    error: function () {
      $('#modal-jurnal-content').html(`
        <div class="alert alert-danger">
        Gagal memuat data jurnal. Silakan coba lagi.
        </div>
        `);
    }
  });
});

}); // end document.ready

</script>

@endsection