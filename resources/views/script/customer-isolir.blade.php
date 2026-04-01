  <script>
    $('#customer_isolir').click(function() 
    {
      $('#table-isolir-customer').DataTable().ajax.reload()
  });

    var table = $('#table-isolir-customer').DataTable({
        "responsive": true,
        "autoWidth": false,
        "searching": true,
        "language": {
            "processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
        },
        dom: 'lBfrtip',
        buttons: [
            'copy', 'excel', 'pdf', 'csv', 'print'
            ],
        "lengthMenu": [[25, 50, 100, 200, 500], [25, 50, 100, 200, 500]],
        processing: true,
        serverSide: true,
        pageLength: 50,
        ajax: {
            url: '/customer/table_isolir_customer',
            method: 'POST',
        // },
            data: function ( d ) {
               return $.extend( {}, d, {
                 "filter": $("#filter").val(),
                 "parameter": $("#parameter").val(),
                 "id_status": $("#id_status").val(),
                 "id_plan": $("#id_plan").val(), 
                 // "deleted_at": $("#deleted_at").val(),            
             } );
           }
       },
       'columnDefs': [
       {
      "targets": 5, // your case first column
      "className": "text-center",
      
  },
  {
      "targets": 6, // your case first column
      "className": "text-center",
      
  },
  {
      "targets": 7, // your case first columnzZxZ
      "className": "text-center",
      
  }
  ],
       columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        {data: 'customer_id', name: 'customer_id'},
        {data: 'name', name: 'name'},
        {data: 'address', name: 'address'},
        {data: 'plan', name: 'plan'},
        {data: 'tax', name: 'tax'},
        {data: 'billing_start', name: 'billing_start'},
        {data: 'status_cust', name: 'status_cust'},
        {data: 'select', name: 'select'},
        {data: 'invoice', name: 'invoice'},
        {data: 'Total Inv', name: 'Total Inv'}
        
        
        ],

   });
</script>