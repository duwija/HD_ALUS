@extends('layout.main')
@section('title','Add New Employee')

@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Add New User </h3>
    </div>
    <form role="form" method="post" action="/user" enctype="multipart/form-data">
      @csrf
      <div class="card-body">
        <div class="row">
          <div class="form-group col-sm-3" >
            <label for="nama">Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Employee Name" value="{{old('name')}}">
            @error('name')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group col-sm-3" >
            <label for="nama">full Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror " name="full_name" id="full_name"  placeholder="Employee Full Name" value="{{old('full_name')}}">
            @error('name')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group col-sm-3">
            <label for="date_of_birth">Date of Birth</label>
            <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror " name="date_of_birth" id="date_of_birth" value="{{old('date_of_birth')}}">
            @error('date_of_birth')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="row">

         <div class="form-group col-sm-3">
           <label for="email"> Email  </label>
           <input type="text" class="form-control @error('email') is-invalid @enderror" name="email"  id="email" placeholder="email" value="{{old('email')}}">
           @error('email')
           <div class="error invalid-feedback">{{ $message }}</div>
           @enderror
         </div>
         <div class="form-group col-sm-3">
           <label for="password"> Password </label>
           <input type="password" class="form-control @error('password') is-invalid @enderror" name="password"  id="password" placeholder="password" value="{{old('password')}}">
           @error('password')
           <div class="error invalid-feedback">{{ $message }}</div>
           @enderror
         </div>
         <div class="form-group col-sm-3">
           <label for="job_title"> Job Title </label>
           <select name="job_title" id="job_title" class="form-control">
            <option value="Network Engineer">Network Engineer</option>
            <option value="NOC">NOC</option>
            <option value="Inventoryr">Inventory</option>
            <option value="Accounting">Accounting</option>
            <option value="Marketing">Marketing</option>
            <option value="HRD">HRD</option>
            <option value="GA">GA</option>
            <option value="GA">Vedor</option>
            <option value="Management">Management</option>

          </select>
        </div>
        <div class="form-group col-sm-3">
          <label for="privilege">
            <i class="fas fa-user-shield"></i> Privilege Level
          </label>
          <select name="privilege" id="privilege" class="form-control @error('privilege') is-invalid @enderror">
            <option value="user" {{ old('privilege') == 'user' ? 'selected' : '' }}>User</option>
            <option value="admin" {{ old('privilege') == 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="accounting" {{ old('privilege') == 'accounting' ? 'selected' : '' }}>Accounting</option>
            <option value="marketing" {{ old('privilege') == 'marketing' ? 'selected' : '' }}>Marketing</option>
            <option value="payment" {{ old('privilege') == 'payment' ? 'selected' : '' }}>Payment</option>
            <option value="noc" {{ old('privilege') == 'noc' ? 'selected' : '' }}>Noc</option>
            <option value="merchant" {{ old('privilege') == 'merchant' ? 'selected' : '' }}>Merchant</option>
            <option value="vendor" {{ old('privilege') == 'vendor' ? 'selected' : '' }}>Vendor</option>
          </select>
          @error('privilege')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
          <small class="text-muted">Default: User</small>
        </div>
      </div>
      {{--  <div class="row">


      </div> --}}
      <div class="row">
        <div class="form-group col-sm-2">
          <label for="employee_type"> Employee Type </label>
          <div class="input-group mb-3">

            <select name="employee_type" id="employee_type" class="form-control">
              <option value="Full Time">Full Time</option>
              <option value="Part Time">Part Time</option>
              <option value="Fixed-Term Contract">Fixed-Term Contract</option>
              <option value="Accounting">Probation</option>
            </select>


          </div>
        </div>
        <div class="form-group col-sm-1">
         <label for="join_date"> Join Date </label>
         <input type="date" class="form-control @error('join_date') is-invalid @enderror" name="join_date"  id="join_date" value="{{old('join_date')}}">
         @error('join_date')
         <div class="error invalid-feedback">{{ $message }}</div>
         @enderror
       </div>
       <div class="form-group col-sm-3">
        <label for="groups">Assign Tiket Groups</label>
        <select name="groups[]" id="groups" class="form-control select2" multiple>
         @foreach ($groups as $group)
         <option value="{{ $group->id }}">
          {{ $group->name }}
        </option>
        @endforeach
      </select>
      @error('groups')
      <div class="invalid-feedback d-block">{{ $message }}</div>
      @enderror
    </div>
    <div class="form-group col-sm-3">
      <label for="groups">Assign Bank Akun</label>
      <select name="akuns[]" id="akuns" class="form-control select2" multiple>
        @foreach ($akuns as $akun)
        <option value="{{ $akun->akun_code }}">
          {{ $akun->name }}
        </option>
        @endforeach
      </select>
      @error('akuns')
      <div class="invalid-feedback d-block">{{ $message }}</div>
      @enderror
    </div>


  </div>

  <div class="row">
    <div class="form-group col-sm-2">
     <label for="phone"> Phone </label>
     <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" id="phone" placeholder="phone" value="{{ old('phone') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
     @error('phone')
     <div class="error invalid-feedback">{{ $message }}</div>
     @enderror
   </div>
   <div class="form-group col-sm-3">
     <label for="address"> Address </label>
     <input type="text" class="form-control @error('address') is-invalid @enderror" name="address"  id="address" placeholder="address" value="{{old('address')}}">
     @error('address')
     <div class="error invalid-feedback">{{ $message }}</div>
     @enderror
   </div>
   <div class="form-group col-sm-4">
    <label for="description">Note  </label>
    <input type="text" class="form-control @error('description') is-invalid @enderror" name="description" id="description" placeholder="Note " value="{{old('description')}}">
    @error('description')
    <div class="error invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>
<div class="form-group">
  <label>Upload Photo (Square Format)</label>
  <input type="file" class="form-control-file m-3" name="photo" id="photo" accept="image/*">
  <input type="hidden" name="cropped_photo" id="cropped_photo">
  <small class="text-muted d-block mb-2">Photo will be cropped to square format (1:1)</small>
  
  <!-- Preview Area -->
  <div id="preview-container" style="display: none;" class="mt-3">
    <div class="row">
      <div class="col-md-6">
        <h6>Original Image</h6>
        <div style="max-width: 100%; overflow: hidden;">
          <img id="image-preview" style="max-width: 100%;">
        </div>
      </div>
      <div class="col-md-6">
        <h6>Cropped Preview</h6>
        <div id="cropped-preview" style="width: 200px; height: 200px; border: 2px solid #ddd; overflow: hidden; background: #f5f5f5;"></div>
        <button type="button" class="btn btn-success btn-sm mt-2" id="crop-button">
          <i class="fas fa-crop"></i> Apply Crop
        </button>
      </div>
    </div>
  </div>
</div>









<div class="form-group">
  <input type="hidden" name="create_at" value="{{now()}}" >
</div>



</div>
<!-- /.card-body -->
<div class="card-footer">
  <button type="submit" class="btn btn-primary">Submit</button>
  <a href="{{url('user')}}" class="btn btn-default float-right">Cancel</a>
</div>

</form>






<!-- /.card -->
</div>
<!-- Form Element sizes -->


</div>

</section>

@endsection

@section('footer-scripts')
<!-- Cropper.js CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<!-- Cropper.js JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const photoInput = document.getElementById('photo');
  const imagePreview = document.getElementById('image-preview');
  const previewContainer = document.getElementById('preview-container');
  const croppedPreview = document.getElementById('cropped-preview');
  const cropButton = document.getElementById('crop-button');
  const croppedPhotoInput = document.getElementById('cropped_photo');
  const form = photoInput.closest('form');
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
          crop: function(event) {
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
    const canvas = cropper.getCroppedCanvas({
      width: 200,
      height: 200
    });
    croppedPreview.innerHTML = '';
    if (canvas) {
      croppedPreview.appendChild(canvas);
    }
  }

  cropButton.addEventListener('click', function() {
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({
      width: 500,
      height: 500
    });
    
    if (canvas) {
      canvas.toBlob(function(blob) {
        const reader = new FileReader();
        reader.onloadend = function() {
          croppedPhotoInput.value = reader.result;
          isCropped = true;
          console.log('Cropped photo set:', croppedPhotoInput.value.substring(0, 50));
          
          Swal.fire({
            icon: 'success',
            title: 'Photo Cropped!',
            text: 'Your photo has been cropped and ready to upload.',
            timer: 2000,
            showConfirmButton: false
          });
        };
        reader.readAsDataURL(blob);
      }, 'image/jpeg', 0.9);
    }
  });

  form.addEventListener('submit', function(e) {
    if (photoInput.files.length > 0 && !isCropped && !croppedPhotoInput.value) {
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'Please Crop Your Photo',
        text: 'You need to click "Apply Crop" button before submitting.',
        confirmButtonText: 'OK'
      });
      return false;
    }
  });
});
</script>
@endsection