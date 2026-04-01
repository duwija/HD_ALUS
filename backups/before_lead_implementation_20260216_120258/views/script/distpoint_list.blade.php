
<script>

 $('#apply-filters').on('click', function() {
  $('#table-distpoint-list').DataTable().ajax.reload();
});



 var table = $('#table-distpoint-list').DataTable({
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
    url: '/distpoint/table_distpoint_list',
    method: 'POST',
    data: function(d) {
      d.site = $('#filter-site').val();
      d.group = $('#filter-group').val();
      d.name = $('#filter-name').val();
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
      "className": "text-center",

    },
    {
      "targets": 4, // your case first columnzZxZ
      "className": "text-center",

    },
    {
      "targets": 5, // your case first column
      "className": "text-center",

    },
    {
      "targets": 6, // your case first column
      "className": "text-center",

    },
    
    // {
    //   "targets": 7, // your case first columnzZxZ
    //   "className": "text-center font-weight-bold",

    // },
    ],
  columns: [
    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
    {data: 'name', name: 'name'},

    {data: 'ip', name: 'ip'},
    {data: 'customer_count', name: 'customer_count'},
    {data: 'security', name: 'security'},
    {data: 'site', name: 'site'},
    {data: 'parrent', name: 'parrent'},
    {data: 'group', name: 'group'},
    {data: 'description', name: 'description'},



    ],

});





</script>