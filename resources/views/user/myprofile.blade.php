@extends('layout.main')
@section('title',' My Profile')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
              <div class="card-header">
                <h3 class="card-title font-weight-bold"> My Profile </h3>
              </div>

              <div class="card-body box-profile bg-light">
                @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @error('photo')
                <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <div class="text-center">
                  <img id="current-photo" style="width: 128px; height: 128px; cursor: pointer;" class="profile-user-img img-fluid img-circle"
                       src="/storage/users/{{$user->photo}}"
                       alt="User profile picture" title="Click to change your profile picture"
                       onerror="this.onerror=null;this.src='storage/users/default_profile.png';" />
                  <div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="document.getElementById('photo').click()">
                      <i class="fas fa-camera"></i> Change Photo
                    </button>
                  </div>
                </div>

                <form id="photo-form" method="POST" action="{{ route('user.myprofile.photo', $user->id) }}" enctype="multipart/form-data" class="text-center">
                  @csrf
                  <input type="file" class="d-none" name="photo" id="photo" accept="image/*">
                  <input type="hidden" name="cropped_photo" id="cropped_photo">

                  <div id="preview-container" style="display: none;" class="mt-3 text-left">
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

                <h3 class="profile-username text-center">{{$user->name}}</h3>
                <p class="text-muted text-center">~ {{$user->privilege}} ~</p>

                <p class="text-muted text-center">{{$user->job_title}}</p>
<div class="row">
                <ul class="list-group list-group-unbordered col-md-6 p-md-2">
                   <li class="list-group-item p-2">
                    <b>Full Name</b> <a class="float-right">{{$user->full_name}}</a>
                  </li>
                  <li class="list-group-item p-2">
                    <b>E mail</b> <a class="float-right">{{$user->email}}</a>
                  </li>
                  <li class="list-group-item p-2 ">
                    <b>Employee Type</b> <a class="float-right">{{$user->employee_type}}</a>
                  </li>
                  <li class="list-group-item p-2 ">
                    <b>Join Date</b> <a class="float-right">{{$user->join_date}}7</a>
                  </li>
                </ul>
                <ul class="list-group list-group-unbordered col-md-6 p-md-2">
                   <li class="list-group-item p-2">
                    <b>Date of birth</b> <a class="float-right">{{$user->date_of_birth}}</a>
                  </li>
                  <li class="list-group-item p-2 ">
                    <b>Address</b> <a class="float-right">{{$user->address}}</a>
                  </li>
                  <li class="list-group-item p-2 ">
                    <b>Phone</b> <a class="float-right">{{$user->phone}}</a>
                  </li>
                  <li class="list-group-item p-2 ">
                    <b>note</b> <a class="float-right">{{$user->description}}</a>
                  </li>
                </ul>

</div>

                 <div class="card-footer">
               {{--    <a href="/user/{{$user->id}}/edit">  <button type="button" class="btn btn-primary float-left "> Edit </button></a> --}}
              {{-- <button type="button" class="btn btn-primary float-right " data-dismiss="modal">Close</button> --}}
              
           {{--  </div> --}}
          </div>
         
           
        
          <!-- /.modal-content -->
        
        <!-- /.modal-dialog -->
      
    </div>
                </div>
              
            </div>
            <!-- /.card -->

            <!-- Form Element sizes -->
   

          </div>
          
          </section>

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