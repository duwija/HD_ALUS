@extends('layout.main')
@section('title','Edit contact')

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Edit contact </h3>
    </div>
    <form role="form" method="post" action="/contact/{{$contact->id}}">
      @csrf
      @method('patch')
      <div class="card-body row">

        <div class="form-group col-md-3">
          <label for="category">Category</label>
          <select name="category" id="category" class="form-control @error('category') is-invalid @enderror">
            <option value="">-- Pilih Kategori --</option>
            <option value="supplier" {{ $supplier->category == 'supplier' ? 'selected' : '' }}>supplier</option>
            <option value="vendor" {{ $contact->category == 'vendor' ? 'selected' : '' }}>Vendor</option>
            <option value="other" {{ $contact->category == 'other' ? 'selected' : '' }}>Other</option>
          </select>
          @error('category')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group col-md-3">
          <label for="name">Name</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" 
          name="name" id="name" placeholder="Enter contact Name" 
          value="{{ old('name', $contact->name) }}">
          @error('name')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group col-md-3">
          <label for="phone">Phone No</label>
          <input type="text" class="form-control @error('phone') is-invalid @enderror" 
          name="phone" id="phone" placeholder="contact Phone" 
          oninput="this.value = this.value.replace(/[^0-9]/g, '')"
          value="{{ old('phone', $contact->phone) }}">
          @error('phone')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group col-md-3">
          <label for="email">Email</label>
          <input type="email" class="form-control @error('email') is-invalid @enderror" 
          name="email" id="email" placeholder="contact Email" 
          value="{{ old('email', $contact->email) }}">
          @error('email')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group col-md-8">
          <label for="address">Address</label>
          <input type="text" class="form-control @error('address') is-invalid @enderror" 
          name="address" id="address" placeholder="contact Address" 
          value="{{ old('address', $contact->address) }}">
          @error('address')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group col-md-8">
          <label for="note">Note</label>
          <textarea class="form-control @error('note') is-invalid @enderror" 
          name="note" id="note" placeholder="Additional Notes">{{ old('note', $contact->note) }}</textarea>
          @error('note')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="card-footer">
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="/contacts/index" class="btn btn-default float-right">Cancel</a>
      </div>
    </form>
  </div>
</section>
@endsection
