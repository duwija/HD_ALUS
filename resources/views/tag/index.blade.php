@extends('layout.main')
@section('title','Manajemen Tag')
@section('content')

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Manajemen Tag</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/home">Home</a></li>
          <li class="breadcrumb-item active">Tag</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    @endif

    <div class="row">

      {{-- ── Tambah Tag ── --}}
      <div class="col-md-4">
        <div class="card card-primary card-outline">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-plus mr-1"></i>Tambah Tag Baru</h3>
          </div>
          <form action="{{ url('/tag/store') }}" method="POST" id="form-add-tag">
            @csrf
            <div class="card-body">
              <div class="form-group mb-0">
                <label>Nama Tag</label>
                <input type="text" name="new_tag" id="new_tag_name" class="form-control"
                  placeholder="Masukkan nama tag..." required maxlength="255">
              </div>
            </div>
            <div class="card-footer">
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-save mr-1"></i>Simpan
              </button>
            </div>
          </form>
        </div>
      </div>

      {{-- ── Daftar Tag Aktif ── --}}
      <div class="col-md-8">
        <div class="card card-outline card-info">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-tags mr-1"></i>Daftar Tag Aktif
              <span class="badge badge-info ml-2">{{ $tags->count() }}</span>
            </h3>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:40px">#</th>
                  <th>Nama Tag</th>
                  <th class="text-center" style="width:90px">Ticket</th>
                  <th class="text-center" style="width:100px">Customer</th>
                  <th style="width:160px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($tags as $i => $tag)
                <tr id="row-tag-{{ $tag->id }}">
                  <td class="text-muted">{{ $i+1 }}</td>
                  <td>
                    {{-- Inline edit --}}
                    <span class="tag-label-{{ $tag->id }}">
                      <span class="badge badge-info mr-1" style="font-size:.85em;">{{ $tag->name }}</span>
                    </span>
                    <form class="form-edit-tag d-none" id="form-edit-{{ $tag->id }}"
                      action="{{ route('tag.update', $tag->id) }}" method="POST"
                      style="display:inline-flex;gap:4px;align-items:center;">
                      @csrf
                      <input type="text" name="name" class="form-control form-control-sm"
                        value="{{ $tag->name }}" style="width:160px;" required>
                      <button type="submit" class="btn btn-success btn-sm px-2">
                        <i class="fas fa-check"></i>
                      </button>
                      <button type="button" class="btn btn-secondary btn-sm px-2 btn-cancel-edit"
                        data-id="{{ $tag->id }}"><i class="fas fa-times"></i></button>
                    </form>
                  </td>
                  <td class="text-center">
                    <span class="badge badge-secondary">{{ $tag->tickets_count }}</span>
                  </td>
                  <td class="text-center">
                    <span class="badge badge-secondary">{{ $tag->customers_count }}</span>
                  </td>
                  <td>
                    <button type="button" class="btn btn-xs btn-warning btn-edit-tag"
                      data-id="{{ $tag->id }}" title="Edit">
                      <i class="fas fa-pencil-alt"></i>
                    </button>
                    <form action="{{ route('tag.destroy', $tag->id) }}" method="POST"
                      class="d-inline form-delete-tag">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-xs btn-danger" title="Hapus (soft delete)"
                        onclick="return confirm('Hapus tag \'{{ addslashes($tag->name) }}\'?\nTag akan dinonaktifkan dan bisa dipulihkan.')">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada tag.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>{{-- end row --}}

    {{-- ── Tag Terhapus ── --}}
    @if($trashedTags->count() > 0)
    <div class="card card-outline card-warning mt-1">
      <div class="card-header">
        <h3 class="card-title text-warning">
          <i class="fas fa-trash-restore mr-1"></i>Tag Terhapus (Soft Delete)
          <span class="badge badge-warning ml-2">{{ $trashedTags->count() }}</span>
        </h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th style="width:40px">#</th>
              <th>Nama Tag</th>
              <th>Dihapus Pada</th>
              <th style="width:170px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($trashedTags as $i => $tag)
            <tr class="table-warning">
              <td class="text-muted">{{ $i+1 }}</td>
              <td>
                <span class="badge badge-secondary mr-1" style="font-size:.85em;">{{ $tag->name }}</span>
                <small class="text-muted">(nonaktif)</small>
              </td>
              <td class="text-muted" style="font-size:.85em;">
                {{ $tag->deleted_at ? $tag->deleted_at->format('d M Y H:i') : '-' }}
              </td>
              <td>
                {{-- Restore --}}
                <form action="{{ route('tag.restore', $tag->id) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-xs btn-success" title="Pulihkan">
                    <i class="fas fa-undo"></i> Pulihkan
                  </button>
                </form>
                {{-- Hard delete --}}
                <form action="{{ route('tag.force-destroy', $tag->id) }}" method="POST" class="d-inline">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-xs btn-danger" title="Hapus Permanen"
                    onclick="return confirm('Hapus PERMANEN tag \'{{ addslashes($tag->name) }}\'?\nTindakan ini tidak bisa dibatalkan!')">
                    <i class="fas fa-times"></i> Hapus Permanen
                  </button>
                </form>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

  </div>
</section>

@section('footer-scripts')
<script>
$(document).ready(function() {

  // Handle submit form tambah via AJAX (agar tidak reload jika mau, bisa hapus ini untuk full redirect)
  // Simpan dengan redirect biasa sudah cukup

  // Tombol Edit inline
  $(document).on('click', '.btn-edit-tag', function() {
    var id = $(this).data('id');
    $('.tag-label-' + id).addClass('d-none');
    $('#form-edit-' + id).removeClass('d-none').show();
  });

  // Tombol Cancel edit
  $(document).on('click', '.btn-cancel-edit', function() {
    var id = $(this).data('id');
    $('.tag-label-' + id).removeClass('d-none');
    $('#form-edit-' + id).addClass('d-none').hide();
  });

});
</script>
@endsection

@endsection
