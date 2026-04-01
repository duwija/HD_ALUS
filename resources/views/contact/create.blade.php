@extends('layout.main')
@section('title','Add New Supplier')

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline ">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Add New Supplier </h3>
    </div>
    <form role="form" method="post" action="/contact">
      @csrf
      <div class="card-body row ">
        <div class="form-group col-md-3">
          <label for="contact_id">Supplier ID</label>
          <input type="text" class="form-control @error('contact_id') is-invalid @enderror" 
          name="contact_id" id="contact_id" placeholder="Enter Supplier ID" 
          value="{{ old('contact_id') }}">
          @error('contact_id')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group col-md-3">
          <label for="type">Category</label>
          <select name="category" id="category" class="form-control @error('category') is-invalid @enderror">
            <option value="">-- Pilih Kategori --</option>

            <option value="supplier">Supplier</option>
            <option value="vendor">Vendor</option>
            <option value="other">Other</option>
          </select>
          @error('type')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="form-group col-md-3">
          <label for="name">Name</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" 
          name="name" id="name" placeholder="Enter Supplier Name" 
          value="{{ old('name') }}">
          @error('name')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group col-md-3">
          <label for="phone">Phone No</label>
          <input type="text" class="form-control @error('phone') is-invalid @enderror" 
          name="phone" id="phone" placeholder="Supplier Phone" 
          oninput="this.value = this.value.replace(/[^0-9]/g, '')"
          value="{{ old('phone') }}">
          @error('phone')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group col-md-3">
          <label for="email">Email</label>
          <input type="email" class="form-control @error('email') is-invalid @enderror" 
          name="email" id="email" placeholder="Supplier Email" 
          value="{{ old('email') }}">
          @error('email')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group col-md-6">
          <label for="address">Address</label>
          <input type="text" class="form-control @error('address') is-invalid @enderror" 
          name="address" id="address" placeholder="Supplier Address" 
          value="{{ old('address') }}">
          @error('address')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>


        <div class="form-group col-md-9">
          <label for="note">Note</label>
          <textarea class="form-control @error('note') is-invalid @enderror" 
          name="note" id="note" placeholder="Additional Notes">{{ old('note') }}</textarea>
          @error('note')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="card-footer">
        <button type="submit" class="btn btn-primary">Submit</button>
        <a href="'suppliers/index'" class="btn btn-default float-right">Cancel</a>
      </div>
    </form>
  </div>
</section>
@endsection



@section('footer-scripts')

<script>

  function generateSupplierID() {
    let now = new Date();
    let year = now.getFullYear();
    let month = String(now.getMonth() + 1).padStart(2, '0'); 
    let day = String(now.getDate()).padStart(2, '0');
    let random = Math.floor(1 + Math.random() * 99); // 3 digit random

    let supplierID = `C${year}${month}${day}${random}`;
    document.getElementById("contact_id").value = supplierID;
  }

// Panggil fungsi saat halaman dimuat
  window.onload = generateSupplierID;

</script>
@endsection