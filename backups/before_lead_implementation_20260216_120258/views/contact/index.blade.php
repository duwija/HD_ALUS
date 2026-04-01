@extends('layout.main')
@section('title','Contact List')

@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Contact List</h3>
      <a href="{{ url('Contact/create') }}" class="float-right btn bg-gradient-primary btn-sm">Add New Contact</a>
    </div>

    <!-- /.card-header -->
    <div class="card-body table-responsive">
      <table id="table-contact-list" class="table table-bordered table-striped">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Contact Id</th>
            <th scope="col">Name</th>
            <th scope="col">Type</th>
            <th scope="col">Phone</th>
            <th scope="col">Email</th>
            <th scope="col">Address</th>
            <th scope="col">Note</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

</section>
@endsection

@section('footer-scripts')
<script>
  var table = $('#table-contact-list').DataTable({
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
      url: '/contact/table_contact_list',
      method: 'POST',
    },
    'columnDefs': [
      { "targets": [1,  3,], "className": "text-center" },
      ],
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'contact_id', name: 'contact_id' },
      { data: 'name', name: 'name' },
      { data: 'category', name: 'category' },
      { data: 'phone', name: 'phone' },
      { data: 'email', name: 'email' },
      { data: 'address', name: 'address' },
      { data: 'note', name: 'note' },
      { data: 'action', name: 'action', orderable: false, searchable: false },
      ],
  });
</script>

<script>
  function confirmDelete(event) {
        event.preventDefault(); // Mencegah form langsung submit

        Swal.fire({
          title: 'Are you sure?',
          text: "You want to delete this contact!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
                event.target.submit(); // Submit form jika user konfirmasi
              }
              else {
                location.reload(); // Refresh halaman jika user menekan Cancel
              }
            });
      }
    </script>
    @endsection
