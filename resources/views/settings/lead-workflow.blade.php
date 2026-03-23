@extends('layout.main')
@section('title', 'Template Workflow Lead')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><i class="fas fa-stream text-warning"></i> Template Workflow Lead</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/">Home</a></li>
          <li class="breadcrumb-item"><a href="#">Marketing</a></li>
          <li class="breadcrumb-item active">Template Workflow Lead</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
    @endif

    <div class="row">

      {{-- Kolom kiri: Daftar Step --}}
      <div class="col-md-8">
        <div class="card card-warning card-outline">
          <div class="card-header d-flex align-items-center">
            <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-list-ol mr-1"></i> Urutan Step Default</h3>
            <span class="badge badge-secondary ml-2">{{ $stages->count() }} step</span>
          </div>
          <div class="card-body p-0">

            <div class="px-3 pt-2 pb-1">
              <div class="alert alert-info mb-2" style="font-size: 0.82rem; padding: 0.4rem 0.75rem;">
                <i class="fas fa-info-circle"></i>
                Drag <i class="fas fa-grip-vertical"></i> untuk mengubah urutan. Template ini disalin otomatis ke setiap customer baru yang berstatus <strong>Potensial</strong>.
              </div>
            </div>

            <ul class="list-group list-group-flush" id="sortable-stages">
              @forelse($stages as $stage)
              <li class="list-group-item d-flex align-items-center py-2" data-id="{{ $stage->id }}">
                <span class="drag-handle text-muted mr-3" style="cursor: grab; font-size: 1.1rem;">
                  <i class="fas fa-grip-vertical"></i>
                </span>
                <span class="badge badge-secondary mr-2 order-badge" style="min-width: 24px;">{{ $stage->order }}</span>
                <div class="flex-grow-1">
                  <strong class="stage-name-display">{{ $stage->name }}</strong>
                  @if($stage->description)
                    <small class="text-muted d-block">{{ $stage->description }}</small>
                  @endif
                </div>
                <div class="ml-2 d-flex" style="gap: 4px;">
                  <button class="btn btn-xs btn-outline-secondary btn-edit-stage"
                    data-id="{{ $stage->id }}"
                    data-name="{{ $stage->name }}"
                    data-description="{{ $stage->description ?? '' }}"
                    title="Edit">
                    <i class="fas fa-edit"></i>
                  </button>
                  <form action="{{ route('lead-workflow.destroy', $stage->id) }}" method="POST" class="form-delete-stage mb-0">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Hapus">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </li>
              @empty
              <li class="list-group-item text-center text-muted py-3">Belum ada step. Tambahkan di form sebelah kanan.</li>
              @endforelse
            </ul>

          </div>
          <div class="card-footer text-muted" style="font-size: 0.8rem;">
            <i class="fas fa-lightbulb text-warning"></i>
            Urutan disimpan otomatis saat drag. Perubahan hanya berlaku untuk customer baru &mdash; customer yang sudah punya workflow tidak terpengaruh.
          </div>
        </div>
      </div>

      {{-- Kolom kanan: Form tambah + Preview --}}
      <div class="col-md-4">
        <div class="card card-success card-outline">
          <div class="card-header">
            <h3 class="card-title font-weight-bold"><i class="fas fa-plus-circle mr-1"></i> Tambah Step</h3>
          </div>
          <div class="card-body">
            <form action="{{ route('lead-workflow.store') }}" method="POST">
              @csrf
              <div class="form-group">
                <label>Nama Step <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                  placeholder="cth: Survei Lokasi" value="{{ old('name') }}" required maxlength="100">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label>Keterangan <small class="text-muted">(opsional)</small></label>
                <input type="text" name="description" class="form-control"
                  placeholder="cth: Tim survei datang ke lokasi" value="{{ old('description') }}" maxlength="255">
              </div>
              <button type="submit" class="btn btn-success btn-block">
                <i class="fas fa-plus"></i> Tambah Step
              </button>
            </form>
          </div>
        </div>

        <div class="card card-default card-outline">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-eye mr-1"></i> Preview Stepper</h3>
          </div>
          <div class="card-body">
            <small class="text-muted d-block mb-2">Tampilan di halaman customer:</small>
            <div class="workflow-wrapper position-relative" style="padding: 16px 0 20px;">
              <div class="base-line position-absolute w-100"></div>
              <div class="d-flex justify-content-start">
                @foreach($stages as $i => $s)
                <div class="text-center flex-fill" style="min-width: 0;">
                  <div class="step-dot {{ $i === 0 ? 'active' : 'pending' }}" style="width:16px;height:16px;font-size:8px;">
                    <i class="fas fa-circle-notch"></i>
                  </div>
                  <span style="font-size: 0.6rem; display:block; margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:52px; margin-left:auto; margin-right:auto;">{{ $s->name }}</span>
                </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

{{-- Modal Edit Step --}}
<div class="modal fade" id="modal-edit-stage" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <form id="form-edit-stage" method="POST">
      @csrf @method('PUT')
      <div class="modal-content">
        <div class="modal-header bg-warning">
          <h5 class="modal-title"><i class="fas fa-edit mr-1"></i> Edit Step</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Step <span class="text-danger">*</span></label>
            <input type="text" name="name" id="edit-name" class="form-control" required maxlength="100">
          </div>
          <div class="form-group mb-0">
            <label>Keterangan</label>
            <input type="text" name="description" id="edit-description" class="form-control" maxlength="255">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>

@endsection

@section('footer-scripts')
<script>
$(document).ready(function() {

  // Edit modal
  $(document).on('click', '.btn-edit-stage', function() {
    var btn = $(this);
    $('#edit-name').val(btn.data('name'));
    $('#edit-description').val(btn.data('description'));
    $('#form-edit-stage').attr('action', '/settings/lead-workflow/' + btn.data('id'));
    $('#modal-edit-stage').modal('show');
  });

  // Delete confirm
  $(document).on('submit', '.form-delete-stage', function(e) {
    e.preventDefault();
    var form = this;
    var name = $(this).closest('.list-group-item').find('.stage-name-display').text().trim();
    if (confirm('Hapus step "' + name + '"? Tidak bisa dibatalkan.')) {
      form.submit();
    }
  });

  // Drag & drop reorder
  Sortable.create(document.getElementById('sortable-stages'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function() {
      var ids = [];
      $('#sortable-stages li[data-id]').each(function(i) {
        ids.push($(this).data('id'));
        $(this).find('.order-badge').text(i + 1);
      });
      $.ajax({
        url: '{{ route("lead-workflow.reorder") }}',
        method: 'POST',
        data: { _token: '{{ csrf_token() }}', order: ids },
        error: function() {
          alert('Gagal menyimpan urutan. Silakan refresh halaman.');
        }
      });
    }
  });

});
</script>
@endsection
