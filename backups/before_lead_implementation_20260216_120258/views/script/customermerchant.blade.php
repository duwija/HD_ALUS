
<script>

  var input = document.getElementById("parameter");

// Execute a function when the user presses a key on the keyboard
  input.addEventListener("keypress", function(event) {
  // If the user presses the "Enter" key on the keyboard
    if (event.key === "Enter") {
    // Cancel the default action, if needed
      event.preventDefault();
    // Trigger the button element with a click


      document.getElementById("customer_filter").click();
    }
  });


  $('#customer_filter').click(function() 
  {
    $('#table-customer').DataTable().ajax.reload()
    $('#table-plan-group').DataTable().ajax.reload()
  });

  var table = $('#table-customer').DataTable({
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
      url: '/customer/table_customermerchant',
      method: 'POST',
        // },
      data: function ( d ) {
       return $.extend( {}, d, {
         "filter": $("#filter").val(),
         "parameter": $("#parameter").val(),
         "id_status": $("#id_status").val(),
         
       } );
     }
   },
   'columnDefs': [
   {
      "targets": 5, // your case first column
      "className": "text-center",

    },
    // {
    //   "targets": 6, // your case first column
    //   "className": "text-center",

    // },
    // {
    //   "targets": 7, // your case first columnzZxZ
    //   "className": "text-center",

    // }
    // ,
    // {
    //   "targets": 8, // your case first columnzZxZ
    //   "className": "text-center",

    // }
    ],
   columns: [
    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
    {data: 'customer_id', name: 'customer_id'},
    {data: 'name', name: 'name'},
    {data: 'address', name: 'address'},
    {data: 'id_merchant', name: 'id_merchant'},
    // {data: 'plan', name: 'plan'},
    // {data: 'billing_start', name: 'billing_start'},
    // {data: 'isolir_date', name: 'isolir_date'},
    {data: 'status_cust', name: 'status_cust'},
    


    ],

 });





</script>