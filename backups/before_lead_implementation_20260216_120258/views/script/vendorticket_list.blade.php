
<script>


  $('#vendorticket_filter').click(function() 
  {
   // document.getElementById("search_var").value = "";
        //$('#invoice-customer').DataTable().ajax.reload();
    $('#table-vendorticket-list').DataTable().ajax.reload();

  });




  var table = $('#table-vendorticket-list').DataTable({
    "responsive": true,
    "autoWidth": false,
    "searching": false,
    "language": {
      "processing": "<span class='fa-stack fa-lg'>\n\
      <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
      </span>&emsp;Processing ..."
    },
    dom: 'lBfrtip',
    buttons: [
      'copy', 'excel', 'pdf', 'csv', 'print'
      ],
    "lengthMenu": [[200, 500, 1000], [200, 500, 1000]],
    processing: true,
    serverSide: true,
    ajax: {
      url: '/ticket/table_vendorticket_list',
      method: 'POST',
      data: function ( d ) {
       return $.extend( {}, d, {
        "date_from": $(document.querySelector('[name="date_from"]')).val(),
        "date_end": $(document.querySelector('[name="date_end"]')).val(),    
        "id_status": $(document.querySelector('[name="id_status"]')).val(),

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
    {data: 'id', name: 'id'},
    {data: 'id_customer', name: 'id_customer'},
    {data: 'status', name: 'status'},
    {data: 'id_categori', name: 'id_categori'},
    {data: 'tittle', name: 'tittle'},
    {data: 'assign_to', name: 'assign_to'},
    {data: 'date', name: 'date'},


    ],

});






</script>