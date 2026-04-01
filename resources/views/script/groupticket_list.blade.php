
<script>

  // Init datepickers
  $(function () {
    if ($.fn.datetimepicker) {
      $('#gt_date_from_picker').datetimepicker({ format: 'YYYY-MM-DD' });
      $('#gt_date_end_picker').datetimepicker({ format: 'YYYY-MM-DD', useCurrent: false });
    }
  });

  // Apply Filters
  $('#groupticket_filter').on('click', function() {
    $('#table-groupticket-list').DataTable().ajax.reload();
  });

  // Reset Filters
  $('#groupticket_reset').on('click', function() {
    $('#gt_date_from').val('{{ date("Y-m-01") }}');
    $('#gt_date_end').val('{{ date("Y-m-d") }}');
    $('#id_categori').val('');
    $('#assign_to').val('');
    $('#id_status').val('');
    $('#table-groupticket-list').DataTable().ajax.reload();
  });




  var table = $('#table-groupticket-list').DataTable({
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
      url: '/ticket/table_groupticket_list',
      method: 'POST',
      data: function ( d ) {
       return $.extend( {}, d, {
        "date_from": $('#gt_date_from').val(),
        "date_end":   $('#gt_date_end').val(),
        "id_categori": $('#id_categori').val(),
        "assign_to":   $('#assign_to').val(),
        "id_status":   $('#id_status').val(),
      } );
     },


     dataSrc: function(json) {
      var fmt = function(n){ return new Intl.NumberFormat('id-ID').format(n||0); };
      $('#total').text(fmt(json.total));
      $('#open').text(fmt(json.open));
      $('#close').text(fmt(json.close));
      $('#inprogress').text(fmt(json.inprogress));
      $('#solve').text(fmt(json.solve));
      $('#pending').text(fmt(json.pending));
      // MTTR card
      var hrs   = json.mttr   || 0;
      var cnt   = json.mttr_count || 0;
      $('#mttr_hours').text(hrs);
      $('#mttr_count').text(cnt);
      if (cnt > 0) {
        var cls = hrs < 4 ? 'badge-success' : (hrs < 8 ? 'badge-warning' : 'badge-danger');
        $('#mttr_badge').attr('class','badge '+cls).html('<i class="fas fa-check-circle mr-1"></i> Avg '+hrs+' h');
      } else {
        $('#mttr_badge').attr('class','badge badge-light').html('<i class="fas fa-circle mr-1 text-secondary"></i> No Data');
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
      "className": "text-left",

    },
    {
      "targets": 3, // your case first columnzZxZ
      "className": "text-center",

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
    {data: 'tags', name: 'tags', orderable: false, searchable: false},
    {data: 'assign_to', name: 'assign_to'},
    {data: 'created_at', name: 'created_at'},
    {data: 'solved_at', name: 'solved_at'},
    {data: 'progress', name: 'progress', orderable: false, searchable: false},
    {data: 'mttr', name: 'mttr', orderable: false, searchable: false},


    ],

});






</script>