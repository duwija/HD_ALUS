@extends('layout.main')
@section('title','BUKU BESAR')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold">BUKU BESAR  </h3>

      <br>
      <hr>

      <div class="row pt-2 pl-4">
        <div class="form-group col-md-3">
          <label for="site location">  Transaction Date Start </label>
          <div class="input-group mb-3">
            <div class="input-group p-1  date" id="reservationdate" data-target-input="nearest">
              <input type="text" name="date_from" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-01')}}" />
              <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
        </div>
        <div class="form-group col-md-3">
          <label for="site location">  Transaction Date End </label>
          <div class="input-group mb-3">
           <div class="input-group p-1 date" id="reservationdate" data-target-input="nearest">
            <input type="text" name="date_end" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-d')}}" />
            <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
              <div class="input-group-text"><i class="fa fa-calendar"></i></div>
            </div>
          </div>
        </div>
      </div>
      <div class="form-group col-md-3">
        <label for="site location">  Kode Akun </label>
        <div class="input-group mb-3">
          <div class="input-group p-1 date" id="reservationdate" data-target-input="nearest">
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
        </div>
      </div>
      <div class="form-group col-md-2">
        <label for="site location">   </label>

        <div class="input-group p-1 col-md-3">

          <button type="button" class="btn mt-2   bg-gradient-primary  btn-primary"  id="bukubesar">Filter
          </button>
        </div> 
      </div>
    </div>

    <hr>

    <div class="card-body">
      <div class="table-responsive">
        <div id="totals" style="margin-bottom: 10px;">

        </div>
        <table id="bukubesar-table" class="table table-bordered table-striped">
          <thead>
           <tr>

            <th colspan="12"class="text-right border-0" >
              <div class="row float-right">



              </div>
            </th>




          </tr>

          <tr>
            <th scope="col">#</th>
            <th scope="col">Date</th>
            <th scope="col">Code</th>
            <th scope="col">Akun</th>


            <th scope="col">Total Rp. <span name='total-debet' id='total-debet'>0 </span></br> Debet   </th>
            <th scope="col">Total Rp. <span name='total-kredit' id='total-kredit'>0 </span></br> Kredit</th>
            <th scope="col"> Saldo</th>
          </tr>
        </thead>
      </table>
      <!-- Modal View Jurnal -->
      <div class="modal fade" id="modalViewJurnal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content">
            <div class="modal-header bg-primary text-white">
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
</div>
</section>
@endsection

@section('footer-scripts')

<style>
/* Styling untuk parent accounts (header) di select2 - RATA KIRI */
.select2-results__option[data-is-parent="true"] {
  font-weight: bold !important;
  background-color: #e9ecef !important;
  color: #212529 !important;
  cursor: not-allowed !important;
  padding-left: 8px !important;
  font-size: 14px !important;
  border-bottom: 1px solid #dee2e6 !important;
}

/* Child dan Standalone accounts - MENJOROK KE KANAN */
.select2-results__option[data-is-parent="false"] {
  padding-left: 35px !important;
  font-size: 13px !important;
}

/* Hover effect untuk child/standalone accounts */
.select2-results__option[data-is-parent="false"]:hover {
  background-color: #007bff !important;
  color: white !important;
}

/* Selected item styling */
.select2-selection__rendered {
  padding-left: 12px !important;
}
</style>

<script>
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
  { 
    data: 'code', 
    name: 'code', 
    className: 'dt-left', 
    orderable: false, 
    searchable: false,
    render: function (data, type, row) {
      if (!data) return '-';
      return `<span class="badge badge-primary cursor-pointer view-jurnal" data-code="${data}">
      ${data}
      </span>`;
    }
  },
  { data: 'akun_name', name: 'akun_name', className: 'dt-left', orderable: false, searchable: false },
  { data: 'debet', name: 'debet', className: 'dt-right' },
  { data: 'kredit', name: 'kredit', className: 'dt-right' },
        { data: 'saldo', name: 'saldo', className: 'dt-right' } // Kolom Saldo
        ],
rowCallback: function (row, data) {
  if (data.is_group) {
    index++;
    $(row).addClass('bg-light text-bold');
            $('td', row).eq(0).html(data.index); // Nomor dan description
            // $('td:gt(1)', row).remove(); // Hapus kolom lain di baris grup
            $('td', row).eq(1).html(`
              <strong>${data.description}</strong> 
              `);
            $('td', row).eq(2).html('');
            $('td', row).eq(3).html(`
              Saldo Awal
              `);
          } else if (data.akun_name === 'Saldo Akhir') {
            $(row).addClass('bg-secondary');
            $('td', row).eq(0).html('');
            $('td', row).eq(2).html('');
            $('td', row).eq(3).html('<strong>Saldo Akhir</strong>'); // Kolom akun_name
            $('td', row).eq(4).html(`<strong>${data.debet}</strong>`); // Kolom debet
            $('td', row).eq(5).html(`<strong>${data.kredit}</strong>`); // Kolom kredit
            $('td', row).eq(6).html(`<strong>${data.saldo}</strong>`); // Kolom saldo
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


</script>


@endsection