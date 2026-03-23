@extends('layout.main')

@section('title', $promo ? 'Edit Promo' : 'Tambah Promo')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="d-flex align-items-center mb-3">
                <a href="{{ route('marketing.promos.index') }}" class="btn btn-sm btn-outline-secondary mr-2">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h4 class="mb-0">{{ $promo ? 'Edit Promo' : 'Tambah Promo Baru' }}</h4>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ $promo ? route('marketing.promos.update', $promo) : route('marketing.promos.store') }}"
                          method="POST">
                        @csrf
                        @if($promo) @method('PUT') @endif

                        {{-- Judul --}}
                        <div class="form-group">
                            <label class="font-weight-bold">Judul <span class="text-danger">*</span></label>
                            <input type="text" name="title"
                                   class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title', $promo?->title) }}"
                                   placeholder="Contoh: Promo Akhir Tahun 50%"
                                   required>
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Konten --}}
                        <div class="form-group">
                            <label class="font-weight-bold">Isi Pengumuman <span class="text-danger">*</span></label>
                            <textarea name="content" id="content-editor"
                                      class="form-control @error('content') is-invalid @enderror"
                                      placeholder="Tulis isi promo / pengumuman...">{{ old('content', $promo?->content) }}</textarea>
                            @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row">
                            {{-- Badge --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Badge Label</label>
                                    <input type="text" name="badge"
                                           class="form-control"
                                           value="{{ old('badge', $promo?->badge) }}"
                                           placeholder="PROMO / INFO / BARU">
                                    <small class="text-muted">Maks 20 karakter</small>
                                </div>
                            </div>
                            {{-- URL Gambar --}}
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="font-weight-bold">URL Gambar</label>
                                    <input type="url" name="image_url"
                                           class="form-control @error('image_url') is-invalid @enderror"
                                           value="{{ old('image_url', $promo?->image_url) }}"
                                           placeholder="https://...">
                                    @error('image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Tanggal Mulai</label>
                                    <input type="date" name="start_date"
                                           class="form-control"
                                           value="{{ old('start_date', $promo?->start_date?->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Tanggal Akhir</label>
                                    <input type="date" name="end_date"
                                           class="form-control @error('end_date') is-invalid @enderror"
                                           value="{{ old('end_date', $promo?->end_date?->format('Y-m-d')) }}">
                                    @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <small class="text-muted">Kosongkan = permanen</small>
                                </div>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input"
                                       id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $promo ? ($promo->is_active ? '1' : '') : '1') ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold" for="is_active">
                                    Aktif (tampil di app)
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i>
                                {{ $promo ? 'Simpan Perubahan' : 'Tambah Promo' }}
                            </button>
                            <a href="{{ route('marketing.promos.index') }}" class="btn btn-outline-secondary ml-2">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('summernote-script')
<script src="{{ url('dashboard/plugins/summernote/summernote-bs4.min.js') }}"></script>
<script>
$(document).ready(function () {

    // Upload helper
    function uploadToServer(file, onSuccess) {
        var fd = new FormData();
        fd.append('file', file);
        fd.append('_token', '{{ csrf_token() }}');
        $.ajax({
            url: '{{ route("marketing.promos.upload-media") }}',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) { onSuccess(res); },
            error:   function ()    { alert('Gagal mengunggah file.'); }
        });
    }

    // Custom: tombol upload video file
    var btnVideoUpload = function (context) {
        var ui = $.summernote.ui;
        var btn = ui.button({
            contents: '<i class="fas fa-film"></i> Video',
            tooltip: 'Upload video dari perangkat',
            click: function () {
                var inp = $('<input type="file" accept="video/*">').hide().appendTo('body');
                inp.trigger('click');
                inp.on('change', function () {
                    var file = this.files[0];
                    if (!file) return;
                    uploadToServer(file, function (res) {
                        var tag = '<video controls style="max-width:100%" src="' + res.url + '"></video>';
                        context.invoke('editor.pasteHTML', tag);
                    });
                    inp.remove();
                });
            }
        });
        return btn.render();
    };

    $('#content-editor').summernote({
        height: 320,
        dialogsInBody: true,
        toolbar: [
            ['style',  ['style']],
            ['font',   ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['fontsize', ['fontsize']],
            ['color',  ['color']],
            ['para',   ['ul', 'ol', 'paragraph']],
            ['table',  ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['custom', ['videoUpload']],
            ['view',   ['fullscreen', 'codeview']],
        ],
        buttons: {
            videoUpload: btnVideoUpload,
        },
        callbacks: {
            onInit: function () {
                $('body > .note-popover').hide();
            },
            onImageUpload: function (files) {
                for (var i = 0; i < files.length; i++) {
                    (function (file) {
                        uploadToServer(file, function (res) {
                            $('#content-editor').summernote('insertImage', res.url, function ($img) {
                                $img.css('max-width', '100%');
                            });
                        });
                    })(files[i]);
                }
            }
        }
    });

    // Pastikan value textarea terisi sebelum submit
    $('form').on('submit', function () {
        var html = $('#content-editor').summernote('code');
        if (!html || html === '<p><br></p>') {
            alert('Isi Pengumuman tidak boleh kosong.');
            return false;
        }
    });

});
</script>
@endpush