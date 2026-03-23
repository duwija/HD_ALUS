
<script>

$(document).ready(function () {

  $('#ticket_filter').on('click', function() {
    $('#table-ticket-list').DataTable().ajax.reload();
  });

  $('#ticket_reset').on('click', function() {
    $('#create_by').val('').trigger('change');
    $('input[name="created_from"]').val('');
    $('input[name="created_end"]').val('');
    $('input[name="date_from"]').val('{{ date("Y-m-01") }}');
    $('input[name="date_end"]').val('{{ date("Y-m-d") }}');
    $('input[name="ticketid"]').val('');
    $('input[name="title"]').val('');
    $('#id_categori').val('').trigger('change');
    $('#id_status').val('');
    $('#assign_to').val('').trigger('change');
    $('#tags').val([]).trigger('change');
    $('#table-ticket-list').DataTable().ajax.reload();
  });

  var table = $('#table-ticket-list').DataTable({
    "responsive": true,
    "autoWidth": false,
    "searching": false,
    "language": {
      "processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
    },
    dom: 'Bfrtip',
    buttons: [
      'pageLength','copy', 'excel', 'pdf', 'csv', 'print'
      ],
    "lengthMenu": [[100, 200, 500, 1000], [100, 200, 500, 1000]],
    processing: true,
    serverSide: true,
    ajax: {
      url: '/ticket/table_ticket_list',
      method: 'POST',
      data: function ( d ) {
       return $.extend( {}, d, {
        "_token": '{{ csrf_token() }}',
        "date_from": $(document.querySelector('[name="date_from"]')).val(),
        "date_end": $(document.querySelector('[name="date_end"]')).val(),
        "id_categori": $(document.querySelector('[name="id_categori"]')).val(),
        "assign_to": $(document.querySelector('[name="assign_to"]')).val(),
        "id_status": $(document.querySelector('[name="id_status"]')).val(),
        "ticketid": $(document.querySelector('[name="ticketid"]')).val(),
        "title": $(document.querySelector('[name="title"]')).val(),
        "tags": $('#tags').val() || [],
        "create_by": $(document.querySelector('[name="create_by"]')).val(),
        "created_from": $(document.querySelector('[name="created_from"]')).val(),
        "created_end": $(document.querySelector('[name="created_end"]')).val(),

      } );
     },


     dataSrc: function(json) {
      var fmt = function(n) {
        return new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(n || 0);
      };
      $('#total').text(fmt(json.total));
      $('#open').text(fmt(json.open));
      $('#close').text(fmt(json.close));
      $('#inprogress').text(fmt(json.inprogress));
      $('#solve').text(fmt(json.solve));
      $('#pending').text(json.pending || 0);
      if (json.mttr !== undefined) {
        var mttrVal = parseFloat(json.mttr || 0);
        $('#mttr').text(mttrVal.toFixed(1));
        $('#mttr_count').text(json.mttr_count || 0);
        var $badge = $('#mttr_badge');
        if (mttrVal === 0) {
          $badge.attr('class','badge badge-light').html('<i class="fas fa-circle mr-1 text-secondary"></i> No Data');
        } else if (mttrVal < 4) {
          $badge.attr('class','badge badge-success').html('<i class="fas fa-check-circle mr-1"></i> Avg '+mttrVal.toFixed(1)+' h');
        } else if (mttrVal < 8) {
          $badge.attr('class','badge badge-warning').html('<i class="fas fa-check-circle mr-1"></i> Avg '+mttrVal.toFixed(1)+' h');
        } else {
          $badge.attr('class','badge badge-danger').html('<i class="fas fa-exclamation-circle mr-1"></i> Avg '+mttrVal.toFixed(1)+' h');
        }
      }
      return json.data;
    }

  },

  'columnDefs': [

  {
      "targets": 1, // your case first column
      "className": "text-center",

    },
    {
      "targets": 2, // your case first column
      "className": "text-center",

    },
    {
      "targets": 3, // your case first columnzZxZ
      "className": "text-left",

    },
    {
      "targets": 4, // your case first columnzZxZ
      "className": "text-left",

    },
    // {
    //   "targets": 7, // your case first column
    //   "className": "text-center",

    // },
    
    // // {
    // //   "targets": 7, // your case first columnzZxZ
    // //   "className": "text-center font-weight-bold",

    // },
    ],
  columns: [
    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
    {data: 'date', name: 'date'},
    {data: 'id', name: 'id'},

    {data: 'id_customer', name: 'id_customer'},
    {data: 'address', name: 'address'},
    {data: 'merchant', name: 'merchant'},
    {data: 'status', name: 'status'},
    {data: 'id_categori', name: 'id_categori'},
    {data: 'tittle', name: 'tittle'},
    {data: 'tags', name: 'tags'},
    {data: 'create_by', name: 'create_by'},
    {data: 'assign_to', name: 'assign_to'},
    {data: 'created_at', name: 'created_at'},
    {data: 'solved_at', name: 'solved_at', orderable: false, searchable: false},
    {data: 'progress', name: 'progress', orderable: false, searchable: false},
    {data: 'mttr', name: 'mttr', orderable: false, searchable: false, defaultContent: '-'},



    ],

});


  // Init datepickers (Tempus Dominus)
  if ($.fn.datetimepicker) {
    $('#reservationdate').datetimepicker({ format: 'YYYY-MM-DD' });
    $('#reservationdate2').datetimepicker({ format: 'YYYY-MM-DD', useCurrent: false });
    $('#created_from_picker').datetimepicker({ format: 'YYYY-MM-DD' });
    $('#created_end_picker').datetimepicker({ format: 'YYYY-MM-DD', useCurrent: false });
  }

}); // end document.ready
</script>
