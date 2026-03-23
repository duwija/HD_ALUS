@extends('layout.main')

@section('content')
<style>
  /* Efek modern */
  .modern-input {
    border-radius: 10px;
  }

  .modern-input:focus {
    box-shadow: 0 0 8px rgba(25, 135, 84, 0.5); /* Hijau Bootstrap */
    border-color: #198754;
  }

  .modern-btn {
    border-radius: 10px;
    transition: all 0.3s ease-in-out;
  }

  .modern-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(25, 135, 84, 0.4);
  }
</style>

<div class="container">
  <div class="row justify-content-center pt-5">
    <div class="col-lg-7 col-md-8 col-sm-10 col-12">
      <h2 class="text-center mb-4">Cari Data Pelanggan</h2>

      <form role="form" method="post" action="/payment/show" enctype="multipart/form-data">
        @csrf

        <div class="row g-2">
          <!-- Select Filter -->
          <div class="col-md-4 col-12 mb-2 mb-md-0">
            <select name="filter" id="filter" class="form-control form-control-lg modern-input" required>
              <option value="customer_id">CID / Kode Pelanggan</option>
              <option value="name">Nama sesuai KTP</option>
              <option value="phone">No Tlp</option>
            </select>
          </div>

          <!-- Input Parameter -->
          <div class="col-md-6 col-12 mb-2 mb-md-0">
            <input name="parameter" id="parameter" type="search"
            class="form-control form-control-lg modern-input" placeholder="Masukkan kata kunci" required>
          </div>

          <!-- Button -->
          <div class="col-md-2 col-12 d-grid">
            <button type="submit" class="btn btn-success btn-lg modern-btn">
              <i class="fa fa-search"></i>
            </button>
          </div>
        </div>

      </form>
    </div>
  </div>
</div>
@endsection
