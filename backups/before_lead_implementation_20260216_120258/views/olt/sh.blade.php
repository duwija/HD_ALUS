@extends('layout.main')
@section('title','OLT')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title"><strong>OLT List </strong> </h3>
      <a href="{{url ('olt/create')}}" class=" float-right btn  bg-gradient-primary btn-sm">Add New Olt</a>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      <table id="onu-table" class="table table-bordered table-striped">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>
            <th scope="col">status</th>
          </tr>
        </thead>

      </table>
    </div>
  </div>

</section>


@endsection
@section('footer-scripts')
@include('script.onu_list')
@endsection 

<script>

  $(document).ready(function() {
    var table = $('#onu-table').DataTable({
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
        url: '/olt/getoltonu',
        method: 'GET',


      },

    //console.log(data),
      'columnDefs': [

      {
      "targets": 1, // your case first column
      "className": "text-center",
      "render": function (data, type, row) {
                return data.replace(/\"/g, ''); // Remove double quotes
              }

            },
    //         {
    //   "targets": 2, // your case first column
    //   "className": "text-center",

    // },

            ],
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        {data: 'name', name: 'name'},
        // {data: 'status', name: 'status'},


        ],


    });
  });






</script>