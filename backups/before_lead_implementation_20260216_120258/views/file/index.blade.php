@extends('layout.main')
@section('title','Backup File')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Backup File  </h3>
      
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      <table id="example1" class="table table-bordered table-striped">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>
            <th scope="col">File Date</th>
            <th scope="col">Action</th>
            <!-- <th scope="col">Action</th> -->
          </tr>
        </thead>
        <tbody>
         @foreach($files as $file)
         <tr>
           <th scope="row">{{ $loop->iteration }}</th>
           <td>   {{ $file->getFilename() }}</td>
           <td> {{ date('Y-m-d H:i:s', $file->getMTime()) }} </td>
           <td> 
            <div class="row " >
              <a class="btn btn-primary m-2" href="{{ route('file.download', $file->getFilename()) }}"> Download </a>
              <form id="delete-form-{{ $loop->iteration }}" action="{{ route('file.delete', $file->getFilename()) }}" method="post">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger m-2" type="button" onclick="confirmDelete({{ $loop->iteration }}, '{{ $file->getFilename() }}')">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach

      </tbody>
    </table>
  </div>
</div>

</section>

@endsection

@section('footer-scripts')
<script>
function confirmDelete(id, filename) {
    Swal.fire({
        title: 'Hapus File Backup?',
        text: "File '" + filename + "' akan dihapus permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + id).submit();
        }
    });
}
</script>
@endsection
