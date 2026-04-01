@extends('layout.main')
@section('title','Akun List')
@section('content')
<section class="content-header">

{{-- ═══ OUTER CARD ══════════════════════════════════════════════════════════ --}}
<div class="card" style="border:1px solid var(--border)">

  {{-- ─── Header ──────────────────────────────────────────────────────────── --}}
  <div class="card-header d-flex align-items-center"
       style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
    <h5 class="mb-0 font-weight-bold" style="color:var(--text-primary)">
      <i class="fas fa-sitemap mr-2" style="color:var(--brand)"></i>Chart of Accounts (Akun)
    </h5>
    <button type="button" class="btn btn-primary btn-sm ml-auto" data-toggle="modal" data-target="#modal-akun">
      <i class="fas fa-plus mr-1"></i>Add New Akun
    </button>
  </div>

  <div class="card-body" style="background:var(--bg-surface)">

    {{-- ─── Legend ──────────────────────────────────────────────────────────── --}}
    <div class="d-flex flex-wrap align-items-center mb-3" style="gap:.5rem;font-size:.8rem">
      <span class="text-muted font-weight-bold mr-1">Legenda:</span>
      <span class="badge" style="background:#343a40;color:#fff;padding:.3em .6em">
        <i class="fas fa-folder mr-1"></i>Parent Akun
      </span>
      <span class="badge" style="background:#6c757d;color:#fff;padding:.3em .6em">
        <i class="fas fa-file mr-1"></i>Child Akun (level 1)
      </span>
      <span class="badge" style="background:#adb5bd;color:#343a40;padding:.3em .6em">
        <i class="fas fa-file-alt mr-1"></i>Sub-child (level 2+)
      </span>
      <span class="badge badge-success ml-2">Is Used (jurnal/child)</span>
      <span class="badge badge-danger ml-1">Hapus (jika tidak dipakai)</span>
    </div>

    {{-- ─── Table ────────────────────────────────────────────────────────────── --}}
    <div class="table-responsive">
      <table id="akun-table" class="table table-bordered table-sm mb-0"
             style="border-color:var(--border)">
        <thead class="thead-dark">
          <tr>
            <th style="width:180px">Kode Akun</th>
            <th>Nama Akun</th>
            <th style="width:120px">Grup</th>
            <th style="width:160px">Kategori</th>
            <th style="width:90px" class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($rootAkuns as $akun)
            @include('akun.akun-row', ['akun' => $akun, 'level' => 0])
          @endforeach
        </tbody>
      </table>
    </div>

  </div>{{-- /card-body --}}
</div>{{-- /card --}}

{{-- ═══ MODAL TAMBAH AKUN ══════════════════════════════════════════════════ --}}
<div class="modal fade" id="modal-akun">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--bg-surface-2);border-bottom:1px solid var(--border)">
        <h5 class="modal-title font-weight-bold" style="color:var(--text-primary)">
          <i class="fas fa-plus-circle mr-2" style="color:var(--brand)"></i>Tambah Akun Baru
        </h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form role="form" method="POST" action="/akun">
          @csrf

          <div class="form-group">
            <label class="font-weight-bold">Nama Akun</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror"
              name="name" id="name" placeholder="Nama Akun" value="{{ old('name') }}">
            @error('name')<div class="error invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Category</label>
            <select name="category" id="category" class="form-control"
              onchange="filterParentAccounts(); updateHiddenInput();">
              <option>==Choose Category==</option>
              <optgroup label="aktiva">
                <option value="kas & bank"                    data-kode="1-10000">Kas & Bank</option>
                <option value="akun piutang"                  data-kode="1-10100">Akun Piutang</option>
                <option value="persediaan"                    data-kode="1-10200">Persediaan</option>
                <option value="aktiva lancar lainnya"         data-kode="1-10300">Aktiva Lancar Lainnya</option>
                <option value="aktiva tetap"                  data-kode="1-10700">Aktiva Tetap</option>
                <option value="depresiasi dan amortisasi"     data-kode="1-10750">Depresiasi & Amortisasi</option>
                <option value="aktiva lainnya"                data-kode="1-10780">Aktiva Lainnya</option>
              </optgroup>
              <optgroup label="kewajiban">
                <option value="akun hutang"                   data-kode="2-20100">Akun Hutang</option>
                <option value="kewajiban lancar lainnya"      data-kode="2-20200">Kewajiban Lancar Lainnya</option>
                <option value="kewajiban jangka panjang"      data-kode="2-20700">Kewajiban Jangka Panjang</option>
              </optgroup>
              <optgroup label="ekuitas">
                <option value="ekuitas"                       data-kode="3-30000">Ekuitas</option>
              </optgroup>
              <optgroup label="pendapatan">
                <option value="pendapatan"                    data-kode="4-40000">Pendapatan</option>
                <option value="pendapatan lainnya"            data-kode="7-70000">Pendapatan Lainnya</option>
              </optgroup>
              <optgroup label="beban">
                <option value="harga pokok penjualan"         data-kode="5-50000">Harga Pokok Penjualan</option>
                <option value="beban"                         data-kode="6-60000">Beban</option>
                <option value="beban lainnya"                 data-kode="8-80000">Beban Lainnya</option>
              </optgroup>
            </select>
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Kode Akun</label>
            <input type="text" class="form-control @error('akun_code') is-invalid @enderror"
              name="akun_code" id="akun_code" placeholder="Kode Akun" value="{{ old('akun_code') }}">
            @error('akun_code')<div class="error invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Group</label>
            <input class="form-control @error('group') is-invalid @enderror"
              type="text" readonly name="group" id="group" value="">
            @error('group')<div class="error invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Parent Akun</label>
            <select name="parent" id="parent" class="form-control">
              <option value="">None (akun baru adalah parent)</option>
            </select>
          </div>

          <div class="row">
            <div class="form-group col-md-2">
              <label class="font-weight-bold">Tax Akun?</label>
              <select disabled name="tax" id="tax" class="form-control" onchange="toggleTaxValue()">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label class="font-weight-bold">Tax Amount</label>
              <input class="form-control @error('tax_value') is-invalid @enderror"
                type="number" name="tax_value" id="tax_value" value="0" disabled>
              @error('tax_value')<div class="error invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <input type="hidden" name="created_at" value="{{ now() }}">

          <div class="modal-footer justify-content-between px-0">
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="fas fa-save mr-1"></i>Simpan Akun
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

</section>
@endsection
@section('footer-scripts')
<script>
  function toggleTaxValue() {
    const taxDropdown = document.getElementById("tax");
    const taxValueInput = document.getElementById("tax_value");
    if (taxDropdown.value === "1") {
      taxValueInput.disabled = false;
      taxValueInput.value = "";
    } else {
      taxValueInput.disabled = true;
      taxValueInput.value = "0";
    }
  }

  function updateHiddenInput() {
    const dropdown = document.getElementById("category");
    const selectedOption = dropdown.options[dropdown.selectedIndex];
    const groupLabel = selectedOption.parentNode.label;
    const kodeAkun = selectedOption.getAttribute("data-kode");
    document.getElementById("group").value = groupLabel;
    document.getElementById("akun_code").value = kodeAkun;
    const taxinput = document.getElementById("tax");
    const taxValueInput = document.getElementById("tax_value");
    if (groupLabel === "kewajiban") {
      taxinput.disabled = false;
    } else {
      taxinput.disabled = true;
      taxValueInput.disabled = true;
    }
  }

  function filterParentAccounts() {
    const selectedCategory = document.getElementById('category').value;
    const parentDropdown = document.getElementById('parent');
    if (selectedCategory) {
      fetch(`/akun/filter-parents/${selectedCategory}`)
        .then(response => response.json())
        .then(data => {
          parentDropdown.innerHTML = '<option value="">None (akun baru adalah parent)</option>';
          data.forEach(account => {
            const option = document.createElement('option');
            option.value = account.akun_code;
            option.textContent = `${account.name} (${account.akun_code})`;
            parentDropdown.appendChild(option);
          });
        })
        .catch(error => console.error('Error fetching parent accounts:', error));
    } else {
      parentDropdown.innerHTML = '<option value="">None (akun baru adalah parent)</option>';
    }
  }
</script>
@endsection
