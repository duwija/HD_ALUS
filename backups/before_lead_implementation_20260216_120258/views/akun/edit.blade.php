@extends('layout.main')
@section('title',' Site')

<script type="text/javascript">

  function updateDatabase(newLat, newLng)
  {
    document.getElementById("coordinate").value = newLat+','+newLng;

  }
</script>
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Edit Site </h3>
    </div>
    <form role="form" method="post" action="akun/update">
      @csrf
      @method('post') 
      <div class="card-body">

        <!-- Input Nama -->
        <div class="form-group">
          <label for="name">Name</label>
          <input type="text" 
          class="form-control @error('name') is-invalid @enderror" 
          name="name" 
          id="name" 
          placeholder="Akun Name" 
          value="{{ old('name', $akun->name) }}">
          @error('name')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <!-- Kode Akun -->
        <div class="form-group">
          <label for="akun_code">Kode Akun</label>
          <input type="text" readonly 
          class="form-control @error('akun_code') is-invalid @enderror" 
          name="akun_code" 
          id="akun_code" 
          placeholder="Kode Akun" 
          value="{{ old('akun_code', $akun->akun_code) }}">
          @error('akun_code')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <!-- Category -->
        <div class="form-group">
          <label for="category">Category</label>
          <select name="category" id="category" class="form-control">
            <option value="">==Choose Category==</option>
            <optgroup label="Aktiva">
              <option value="kas & bank" data-kode="1-10000" 
              {{ $akun->category == 'kas & bank' ? 'selected' : '' }}>Kas & Bank</option>
              <option value="akun piutang" data-kode="1-10100" 
              {{ $akun->category == 'akun piutang' ? 'selected' : '' }}>Akun Piutang</option>
              <option value="persediaan" data-kode="1-10200" 
              {{ $akun->category == 'persediaan' ? 'selected' : '' }}>Persediaan</option>
              <option value="aktiva lancar lainnya" data-kode="1-10300" 
              {{ $akun->category == 'aktiva lancar lainnya' ? 'selected' : '' }}>Aktiva Lancar Lainnya</option>
              <option value="aktiva tetap" data-kode="1-10700" 
              {{ $akun->category == 'aktiva tetap' ? 'selected' : '' }}>Aktiva Tetap</option>
              <option value="depresiasi dan amortisasi" data-kode="1-10750" 
              {{ $akun->category == 'depresiasi dan amortisasi' ? 'selected' : '' }}>Depresiasi & Amortisasi</option>
              <option value="aktiva lainnya" data-kode="1-10780" 
              {{ $akun->category == 'aktiva lainnya' ? 'selected' : '' }}>Aktiva Lainnya</option>
            </optgroup>
            <optgroup label="Kewajiban">
              <option value="akun hutang" data-kode="2-20100" 
              {{ $akun->category == 'akun hutang' ? 'selected' : '' }}>Akun Hutang</option>
              <option value="kewajiban lancar lainnya" data-kode="2-20200" 
              {{ $akun->category == 'kewajiban lancar lainnya' ? 'selected' : '' }}>Kewajiban Lancar Lainnya</option>
              <option value="kewajiban jangka panjang" data-kode="2-20700" 
              {{ $akun->category == 'kewajiban jangka panjang' ? 'selected' : '' }}>Kewajiban Jangka Panjang</option>
            </optgroup>
            <optgroup label="Ekuitas">
              <option value="ekuitas" data-kode="3-30000" 
              {{ $akun->category == 'ekuitas' ? 'selected' : '' }}>Ekuitas</option>
            </optgroup>
            <optgroup label="Pendapatan">
              <option value="pendapatan" data-kode="4-40000" 
              {{ $akun->category == 'pendapatan' ? 'selected' : '' }}>Pendapatan</option>
              <option value="pendapatan lainnya" data-kode="7-70000" 
              {{ $akun->category == 'pendapatan lainnya' ? 'selected' : '' }}>Pendapatan Lainnya</option>
            </optgroup>
            <optgroup label="Beban">
              <option value="harga pokok penjualan" data-kode="5-50000" 
              {{ $akun->category == 'harga pokok penjualan' ? 'selected' : '' }}>Harga Pokok Penjualan</option>
              <option value="beban" data-kode="6-60000" 
              {{ $akun->category == 'beban' ? 'selected' : '' }}>Beban</option>
              <option value="beban lainnya" data-kode="8-80000" 
              {{ $akun->category == 'beban lainnya' ? 'selected' : '' }}>Beban Lainnya</option>
            </optgroup>
          </select>

        </div>

        <!-- Kode Akun -->
        <div class="form-group">
          <label for="akun_code">Group</label>
          <input type="text" readonly 
          class="form-control @error('group') is-invalid @enderror" 
          name="group" 
          id="group" 
          placeholder="Kode Akun" 
          value="{{ old('group', $akun->group) }}">
          @error('group')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <!-- Tax -->
        <div class="form-group">
          <label for="tax">Tax Akun</label>
          <select name="tax" id="tax" class="form-control" onchange="toggleTaxValue()">
            <option value="0" {{ old('tax', $akun->tax) == '0' ? 'selected' : '' }}>No</option>
            <option value="1" {{ old('tax', $akun->tax) == '1' ? 'selected' : '' }}>Yes</option>
          </select>
        </div>

        <!-- Tax Amount -->
        <div class="form-group">
          <label for="tax_value">Tax Amount</label>
          <input class="form-control" 
          type="text" 
          name="tax_value" 
          id="tax_value" 
          value="{{ old('tax_value', $akun->tax_value) }}" 
          readonly>
        </div>

        <!-- Description -->
        <div class="form-group">
          <label for="description">Description</label>
          <input type="text" 
          class="form-control @error('description') is-invalid @enderror" 
          name="description" 
          id="description" 
          placeholder="Description" 
          value="{{ old('description', $akun->description) }}">
          @error('description')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

      </div>

      <!-- Submit Button -->
      <div class="card-footer">
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>

    <a href="{{url('site')}}" class="btn btn-secondary  float-right">Cancel</a>
  </div>

</div>
<!-- /.card -->

<!-- Form Element sizes -->


</div>

</section>

@endsection
@section('footer-scripts')
<script>
  function toggleTaxValue() {
    const taxDropdown = document.getElementById("tax");
    const taxValueInput = document.getElementById("tax_value");

        // Periksa apakah opsi Yes dipilih
    if (taxDropdown.value === "1") {
            taxValueInput.disabled = false; // Aktifkan input
            taxValueInput.value = ""; // Kosongkan nilai untuk input
          } else {
            taxValueInput.disabled = true; // Nonaktifkan input
            taxValueInput.value = "0"; // Set nilai default
          }
        }
      </script>
      <script>
       function updateHiddenInput() {
        const dropdown = document.getElementById("category");
        const selectedOption = dropdown.options[dropdown.selectedIndex];
      const groupLabel = selectedOption.parentNode.label; // Ambil label dari optgroup
      const kodeAkun = selectedOption.getAttribute("data-kode"); 

      // Gabungkan group dan option
      const group = groupLabel;

      // Set value ke hidden input
      document.getElementById("group").value = group;
      document.getElementById("akun_code").value = kodeAkun;
      const taxinput = document.getElementById("tax");
      if (dropdown === "kewajiban lancar lainnnya") {
            taxinput.disabled = false; // Aktifkan input

          } else {
            taxinput.disabled = true; // Nonaktifkan input

          }
        }
      </script>