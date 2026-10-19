@extends('layout.main')
@section('title',' My Profile')
@section('content')
@php
  // Versi gambar ikut berubah saat foto/data profil berubah, supaya pratinjau tidak memakai cache lama
  $tagUrl = '/attendance/employees/' . $user->id . '/name-tag';
  $tagVer = optional($user->updated_at)->timestamp . '-' . md5((string) $user->photo);
@endphp
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> My Profile </h3>
    </div>

    <div class="card-body bg-light">
      @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
      @endif
      @error('photo')
      <div class="alert alert-danger">{{ $message }}</div>
      @enderror

      <div class="row">
        {{-- Name tag (gambar yang sama dengan Download / Print) --}}
        <div class="col-lg-4 col-md-5 text-center mb-4">
          <div style="position:relative;display:inline-block;width:100%;max-width:280px;aspect-ratio:54/85.6;border-radius:14px;background:#eef2f7;box-shadow:0 8px 24px rgba(0,0,0,.18);overflow:hidden">
            <div id="nametag-loading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#6b7280">
              <i class="fas fa-spinner fa-spin mr-1"></i> Memuat name tag...
            </div>
            <img id="nametag-img" src="{{ $tagUrl }}?inline=1&v={{ $tagVer }}" alt="Name tag {{ $user->name }}"
                 style="width:100%;height:100%;display:block;opacity:0;transition:opacity .2s"
                 onload="this.style.opacity=1;document.getElementById('nametag-loading').style.display='none'"
                 onerror="document.getElementById('nametag-loading').innerHTML='<i class=&quot;fas fa-exclamation-triangle mr-1&quot;></i> Gagal memuat name tag'">
          </div>
          <div class="mt-3 d-flex flex-wrap justify-content-center" style="gap:6px">
            <a href="{{ $tagUrl }}" class="btn btn-sm btn-success"><i class="fas fa-download mr-1"></i> Download</a>
            <button type="button" id="nametag-print" class="btn btn-sm btn-info" data-url="{{ $tagUrl }}?inline=1&v={{ $tagVer }}">
              <i class="fas fa-print mr-1"></i> Print
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('photo').click()">
              <i class="fas fa-camera mr-1"></i> Change Photo
            </button>
          </div>
        </div>

        <div class="col-lg-8 col-md-7">
          <form id="photo-form" method="POST" action="{{ route('user.myprofile.photo', $user->id) }}" enctype="multipart/form-data" class="text-center">
            @csrf
            <input type="file" class="d-none" name="photo" id="photo" accept="image/*">
            <input type="hidden" name="cropped_photo" id="cropped_photo">

            <div id="preview-container" style="display: none;" class="mb-3 text-left card card-body">
              <div class="row">
                <div class="col-md-6">
                  <h6>Original Image</h6>
                  <div style="max-width: 100%; overflow: hidden;">
                    <img id="image-preview" style="max-width: 100%;">
                  </div>
                </div>
                <div class="col-md-6">
                  <h6>Cropped Preview</h6>
                  <div id="cropped-preview" style="width: 150px; height: 150px; border: 2px solid #ddd; overflow: hidden; background: #f5f5f5; margin: 0 auto;"></div>
                  <button type="button" class="btn btn-success btn-sm mt-2" id="crop-button">
                    <i class="fas fa-crop"></i> Apply Crop
                  </button>
                </div>
              </div>
              <div class="text-center mt-3">
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save"></i> Save Photo
                </button>
                <button type="button" class="btn btn-secondary" id="cancel-photo">Cancel</button>
              </div>
            </div>
          </form>

          <h3 class="mb-0">{{ $user->full_name ?: $user->name }}</h3>
          <div class="text-muted mb-1">{{ $user->name }}@if($user->job_title) · {{ $user->job_title }}@endif</div>
          <span class="badge badge-primary mb-3">{{ $user->privilege }}</span>

          <div class="row">
            <ul class="list-group list-group-unbordered col-md-6 p-md-2">
              <li class="list-group-item p-2"><b>Full Name</b> <span class="float-right">{{ $user->full_name ?: '-' }}</span></li>
              <li class="list-group-item p-2"><b>E-mail</b> <span class="float-right">{{ $user->email ?: '-' }}</span></li>
              <li class="list-group-item p-2"><b>Employee Type</b> <span class="float-right">{{ $user->employee_type ?: '-' }}</span></li>
              <li class="list-group-item p-2"><b>Join Date</b> <span class="float-right">{{ $user->join_date ?: '-' }}</span></li>
            </ul>
            <ul class="list-group list-group-unbordered col-md-6 p-md-2">
              <li class="list-group-item p-2"><b>Date of birth</b> <span class="float-right">{{ $user->date_of_birth ?: '-' }}</span></li>
              <li class="list-group-item p-2"><b>Address</b> <span class="float-right">{{ $user->address ?: '-' }}</span></li>
              <li class="list-group-item p-2"><b>Phone</b> <span class="float-right">{{ $user->phone ?: '-' }}</span></li>
              <li class="list-group-item p-2"><b>Note</b> <span class="float-right">{{ $user->description ?: '-' }}</span></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  // Cetak name tag ukuran asli kartu ID (54 x 85,6 mm) di kertas A4, dengan garis potong
  document.getElementById('nametag-print').addEventListener('click', function () {
    var win = window.open('', '_blank');
    if (!win) { alert('Izinkan pop-up untuk halaman ini agar bisa mencetak name tag.'); return; }
    win.document.write(
      '<!doctype html><html><head><title>Print Name Tag</title>' +
      '<style>@page{size:A4 portrait;margin:15mm}body{margin:0;font-family:sans-serif;color:#666}' +
      '.cut{border:1px dashed #bbb;display:none}img{display:block;width:54mm;height:85.6mm}</style></head>' +
      '<body><p id="msg">Menyiapkan name tag...</p><div class="cut" id="cut"><img id="tag" alt=""></div></body></html>'
    );
    win.document.close();
    var img = win.document.getElementById('tag');
    img.onload = function () {
      win.document.getElementById('msg').remove();
      win.document.getElementById('cut').style.display = 'inline-block';
      win.focus();
      win.print();
    };
    img.onerror = function () { win.document.getElementById('msg').textContent = 'Gagal memuat name tag.'; };
    img.src = window.location.origin + this.getAttribute('data-url');
  });
</script>
@endsection

@section('footer-scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const photoInput = document.getElementById('photo');
  const imagePreview = document.getElementById('image-preview');
  const previewContainer = document.getElementById('preview-container');
  const croppedPreview = document.getElementById('cropped-preview');
  const cropButton = document.getElementById('crop-button');
  const cancelButton = document.getElementById('cancel-photo');
  const croppedPhotoInput = document.getElementById('cropped_photo');
  const form = document.getElementById('photo-form');
  let cropper = null;
  let isCropped = false;

  photoInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      isCropped = false;
      croppedPhotoInput.value = '';

      const reader = new FileReader();
      reader.onload = function(event) {
        imagePreview.src = event.target.result;
        previewContainer.style.display = 'block';

        if (cropper) {
          cropper.destroy();
        }

        cropper = new Cropper(imagePreview, {
          aspectRatio: 1,
          viewMode: 1,
          autoCropArea: 1,
          responsive: true,
          guides: true,
          center: true,
          highlight: true,
          cropBoxResizable: true,
          cropBoxMovable: true,
          crop: function() {
            updateCroppedPreview();
          },
          ready: function() {
            updateCroppedPreview();
          }
        });
      };
      reader.readAsDataURL(file);
    }
  });

  function updateCroppedPreview() {
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({ width: 150, height: 150 });
    croppedPreview.innerHTML = '';
    if (canvas) {
      croppedPreview.appendChild(canvas);
    }
  }

  cropButton.addEventListener('click', function() {
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({ width: 500, height: 500 });

    if (canvas) {
      canvas.toBlob(function(blob) {
        const reader = new FileReader();
        reader.onloadend = function() {
          croppedPhotoInput.value = reader.result;
          isCropped = true;
        };
        reader.readAsDataURL(blob);
      }, 'image/jpeg', 0.9);
    }
  });

  cancelButton.addEventListener('click', function() {
    photoInput.value = '';
    croppedPhotoInput.value = '';
    previewContainer.style.display = 'none';
    isCropped = false;
    if (cropper) {
      cropper.destroy();
      cropper = null;
    }
  });

  form.addEventListener('submit', function(e) {
    if (photoInput.files.length > 0 && !isCropped && !croppedPhotoInput.value) {
      e.preventDefault();
      alert('Please click "Apply Crop" before saving.');
    }
  });
});
</script>
@endsection