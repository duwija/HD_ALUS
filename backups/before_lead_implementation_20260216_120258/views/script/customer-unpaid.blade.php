  <script>
    $('#customer_unpaid_filter').click(function() 
    {
      $('#table-unpaid-customer').DataTable().ajax.reload()
    });

    $('#customer_isolir').click(function() 
    {
      $('#table-unpaid-customer').DataTable().ajax.reload()
    });


    var table = $('#table-unpaid-customer').DataTable({
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
      "lengthMenu": [[25, 50, 100, 200, 500], [25, 50, 100, 200, 500]],
      processing: true,
      serverSide: true,
      pageLength: 50,
      ajax: {
        url: '/customer/table_unpaid_customer',
        method: 'POST',
        // },
        data: function ( d ) {
         return $.extend( {}, d, {
           "filter": $("#filter").val(),
           "parameter": $("#parameter").val(),
           "id_status": $("#id_status").val(),
           "id_plan": $("#id_plan").val(), 
           "countinv": $("#countinv").val(), 
           "deleted_at": $("#deleted_at").val(),
           "id_merchant": $("#id_merchant").val(),                
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
    ,
    {
      "targets": 8, // your case first columnzZxZ
      "className": "text-center",

    }
    ,
    {
      "targets": 9, // your case first columnzZxZ
      "className": "text-center",

    }
    ,
    {
      "targets": 11, // your case first columnzZxZ
      "className": "text-center",

    },
    {
      "targets": 10, // your case first columnzZxZ
      "className": "text-right",

    }
    ],
     columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      {data: 'customer_id', name: 'customer_id'},
      {data: 'name', name: 'name'},

      {data: 'address', name: 'address'}, 
      {data: 'id_merchant', name: 'id_merchant'},
      {data: 'plan', name: 'plan'},
      {data: 'tax', name: 'tax'},
      {data: 'billing_start', name: 'billing_start'},
      {data: 'isolir_date', name: 'isolir_date'},
      {data: 'status_cust', name: 'status_cust'},
      // {data: 'select', name: 'select'},
      {data: 'invoice', name: 'invoice'},
      {data: 'Total Inv', name: 'Total Inv'}


      ],

   });
 </script>