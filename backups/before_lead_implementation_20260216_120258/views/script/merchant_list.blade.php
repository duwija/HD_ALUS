
<script>



  var table = $('#table-merchant-list').DataTable({
    "responsive": true,
    "autoWidth": false,
    "searching": true,
    "language": {
      "processing": "<span class='fa-stack fa-lg'>\n\
      <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
      </span>&emsp;Processing ..."
    },
    dom: 'Bfrtip',
    buttons: [
     'pageLength','copy', 'excel', 'pdf', 'csv', 'print'
     ],
    "lengthMenu": [[50, 100, 200, 500, 1000], [50, 100, 200, 500, 1000]],
    processing: true,
    serverSide: true,
    ajax: {
      url: '/merchant/table_merchant_list',
      method: 'POST',

      
    },

    'columnDefs': [

    {
      "targets": 1, // your case first column
      "className": "text-left",

    },
    {
      "targets": 2, // your case first column
      "className": "text-center",

    },
    {
      "targets": 3, // your case first columnzZxZ
      "className": "text-center",

    },
    // {
    //   "targets": 4, // your case first columnzZxZ
    //   "className": "text-center",

    // },
    // {
    //   "targets": 5, // your case first column
    //   "className": "text-center",

    // },
    // {
    //   "targets": 6, // your case first column
    //   "className": "text-center",

   // },
    
    // {
    //   "targets": 7, // your case first columnzZxZ
    //   "className": "text-center font-weight-bold",

    // },
    ],
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      {data: 'name', name: 'name'},
      {data: 'contact_name', name: 'contact_name'},
      {data: 'phone', name: 'phone'},
      {data: 'address', name: 'address'},
      

      ],

  });






</script>