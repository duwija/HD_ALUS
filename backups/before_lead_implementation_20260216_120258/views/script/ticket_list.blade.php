
<script>

  $('#ticket_filter').click(function() 
  {
   $('#table-ticket-list').DataTable().ajax.reload();
 });

  // Reset button handler
  $('#filter-form button[type="reset"]').click(function() {
    // Clear all select2
    $('.select2').val(null).trigger('change');
    
    // Reload table with empty filters
    setTimeout(function() {
      $('#table-ticket-list').DataTable().ajax.reload();
    }, 100);
  });




  var table = $('#table-ticket-list').DataTable({
    "responsive": true,
    "autoWidth": false,
    "searching": false,
    "language": {
      "processing": "<span class='fa-stack fa-lg'>\n\
      <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
      </span>&emsp;Processing ..."
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
        "ticket_type": $(document.querySelector('[name="ticket_type"]')).val(),

      } );
     },


     dataSrc: function(json) {
                    // Mengupdate nilai total amount di view
    // console.log(json); // Log data JSON untuk debugging
      $('#total').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.total)),
      $('#open').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.open));
      $('#close').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.close));
      $('#inprogress').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.inprogress));
      $('#solve').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.solve));
      $('#pending').text(json.pending);
      
      // Update MTTR values
      $('#mttr').text(json.mttr || 0);
      $('#mttr_count').text(json.mttr_count || 0);
      
      // Update MTTR badge based on value
      var mttrValue = parseFloat(json.mttr) || 0;
      var badgeHtml = '';
      if (json.mttr_count == 0 || mttrValue == 0) {
        badgeHtml = '<i class="fas fa-info-circle mr-2"></i>No Data';
      } else if (mttrValue < 24) {
        badgeHtml = '<i class="fas fa-thumbs-up mr-2"></i>Excellent (< 24h)';
      } else if (mttrValue < 48) {
        badgeHtml = '<i class="fas fa-check mr-2"></i>Good (< 48h)';
      } else {
        badgeHtml = '<i class="fas fa-exclamation-triangle mr-2"></i>Needs Improvement';
      }
      $('#mttr_badge').html(badgeHtml);
      
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
    {
      "targets": 14, // MTTR column
      "className": "text-center",

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
    {data: 'workflow_progress', name: 'workflow_progress', orderable: false, searchable: false},
    {data: 'mttr', name: 'mttr', orderable: false, searchable: false},



    ],

});

</script>

