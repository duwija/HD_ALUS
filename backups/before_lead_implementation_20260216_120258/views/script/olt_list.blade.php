
<script>

 

  var table = $('#table-olt-list').DataTable({
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
      url: '/olt/table_olt_list',
      method: 'POST',

      
    },

    'columnDefs': [

    {
      "targets": 1, // your case first column
      "className": "text-center",

    },
    {
      "targets": 2, // vendor column
      "className": "text-center",

    },
    {
      "targets": 3, // type column
      "className": "text-center",

    },
    {
      "targets": 4, // ip column
      "className": "text-center",

    },
    {
      "targets": 5, // port column
      "className": "text-center",

    },
    {
      "targets": 8, // snmp_port column
      "className": "text-center",

    },
    
    ],
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      {data: 'name', name: 'name'},
      {data: 'vendor', name: 'vendor'},
      {data: 'type', name: 'type'},
      {data: 'ip', name: 'ip'},
      {data: 'port', name: 'port'},
      {data: 'community_ro', name: 'community_ro'},
      {data: 'community_rw', name: 'community_rw'},
      {data: 'snmp_port', name: 'snmp_port'},

      ],

  });






</script>