@extends('layout.main')
@section('title','site List')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Distpoint Group List  </h3>
      <a href="{{url ('distpointgroup/create')}}" class=" float-right btn  bg-gradient-primary btn-sm">Add New Dispoint Group</a>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      <table id="table-distpointgroup-list" class="table table-bordered table-striped">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>
            <th scope="col">Capacity</th>
            <th scope="col">Description</th>
            <!-- <th scope="col">Action</th> -->
          </tr>
        </thead>
        <tbody>
       
          
      </tbody>
    </table>
  </div>
</div>

</section>

@endsection
@section('footer-scripts')

<script>

 $('#apply-filters').on('click', function() {
  $('#table-distpointgroup-list').DataTable().ajax.reload();
});



 var table = $('#table-distpointgroup-list').DataTable({
  "responsive": true,
  "autoWidth": false,
  "searching": true,
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
    url: '/distpointgroup/table_distpointgroup_list',
    method: 'POST',
    data: function(d) {
      // d.site = $('#filter-site').val();
      // d.group = $('#filter-group').val();
      // d.name = $('#filter-name').val();
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
 
    

    ],
  columns: [
    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
    {data: 'name', name: 'name'},
    {data: 'capacity', name: 'capacity'},
    {data: 'description', name: 'description'},
 



    ],

});





</script>
@endsection 
