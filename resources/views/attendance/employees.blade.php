@extends('layout.main')
@section('title','Manajemen Karyawan')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <h1><i class="fas fa-users mr-2 text-success"></i>Data Karyawan & Supervisor</h1>
  </div>
</section>

<section class="content"><div class="container-fluid">

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0" id="table-employees">
          <thead class="bg-success text-white">
            <tr>
              <th>#</th><th>Nama</th><th>Jabatan</th><th>NIK/Employee ID</th>
              <th>Supervisor</th><th>Join Date</th><th>Status</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($employees as $i => $emp)
            <tr>
              <td>{{ $i+1 }}</td>
              <td class="d-flex align-items-center" style="gap:10px">
                <img src="{{ $emp->photo ? asset('storage/users/'.$emp->photo) : asset('storage/users/user.png') }}"
                     alt="{{ $emp->name }}"
                     style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #28a745;">
                <div>
                  <strong>{{ $emp->name }}</strong>
                  @if($emp->full_name && $emp->full_name !== $emp->name)
                    <br><small class="text-muted">{{ $emp->full_name }}</small>
                  @endif
                </div>
              </td>
              <td>{{ $emp->job_title ?? '-' }}</td>
              <td>{{ $emp->employee_id ?? '-' }}</td>
              <td>{!! optional($emp->supervisor)->name ?? '<span class="text-muted">-</span>' !!}</td>
              <td>{{ $emp->join_date ?? '-' }}</td>
              <td>
                @if($emp->is_active_employee)
                  <span class="badge badge-success">Aktif</span>
                @else
                  <span class="badge badge-secondary">Nonaktif</span>
                @endif
              </td>
              <td>
                <button class="btn btn-xs btn-warning btn-edit-emp"
                        data-id="{{ $emp->id }}"
                        data-name="{{ $emp->name }}"
                        data-supervisor="{{ $emp->supervisor_id }}"
                        data-employee-id="{{ $emp->employee_id }}"
                        data-active="{{ $emp->is_active_employee ? 1 : 0 }}">
                  <i class="fas fa-edit"></i>
                </button>
              </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div></section>

{{-- Modal Edit --}}
<div class="modal fade" id="modal-edit-emp" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="fas fa-edit mr-1"></i>Edit Data Karyawan: <span id="modal-emp-name"></span></h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form id="form-edit-emp" method="POST">
        @csrf @method('PATCH')
        <div class="modal-body">
          <div class="form-group">
            <label>NIK / Employee ID</label>
            <input type="text" name="employee_id" id="inp-employee-id" class="form-control" placeholder="EMP-001">
          </div>
          <div class="form-group">
            <label>Supervisor</label>
            <select name="supervisor_id" id="inp-supervisor" class="form-control">
              <option value="">-- Tidak ada supervisor --</option>
              @foreach($supervisors as $s)
                <option value="{{ $s->id }}">{{ $s->name }} {{ $s->job_title ? '('.$s->job_title.')' : '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-check">
            <input type="hidden" name="is_active_employee" value="0">
            <input type="checkbox" class="form-check-input" name="is_active_employee" value="1" id="inp-active">
            <label class="form-check-label" for="inp-active">Karyawan Aktif</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('footer-scripts')
<script>
$(function(){
  $(document).on('click', '.btn-edit-emp', function(){
    var id         = $(this).data('id');
    var name       = $(this).data('name');
    var supId      = $(this).data('supervisor');
    var empId      = $(this).data('employee-id');
    var isActive   = $(this).data('active');

    $('#modal-emp-name').text(name);
    $('#form-edit-emp').attr('action', '/attendance/employees/' + id);
    $('#inp-employee-id').val(empId || '');
    $('#inp-supervisor').val(supId || '');
    $('#inp-active').prop('checked', isActive == 1);
    $('#modal-edit-emp').modal('show');
  });
});
</script>
@endsection
