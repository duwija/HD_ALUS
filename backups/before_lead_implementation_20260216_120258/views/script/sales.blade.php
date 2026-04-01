

<script>

  var input = document.getElementById("sale_parameter");

// Execute a function when the user presses a key on the keyboard
  input.addEventListener("keypress", function(event) {
  // If the user presses the "Enter" key on the keyboard
    if (event.key === "Enter") {
    // Cancel the default action, if needed
      event.preventDefault();
    // Trigger the button element with a click
      
      
      document.getElementById("sale_filter").click();
    }
  });




  $('#sale_filter').click(function() 
  {
   document.getElementById("search_var").value = "";
   $('#table-sale').DataTable().ajax.reload();

 });

  var table = $('#table-sale').DataTable({
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
    ajax: {
      url: '/sale/table_sale',
      method: 'POST',
        // },
       //  data: function ( d ) {
       //   return $.extend( {}, d, {
       //    // "search_var" : $("#search_var").val(),
       //    //  "filter": $("#sale_filter").val(),
       //    //  "parameter": $("#sale_parameter").val(),
       //      } );
      
       // }
    },

    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false }
    // {data: 'name', name: 'name'},
    // {data: 'full_name', name: 'full_name'},
    // {data: 'phone', name: 'phone'},
    // {data: 'address', name: 'address'},
    // {data: 'email', name: 'email'},
    // {data: 'note', name: 'note'},

      ],

  });
</script>