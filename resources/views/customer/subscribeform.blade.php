@extends('layout.main')
@section('title', 'Formulir Pendaftaran Internet')
@section('content')

<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold">Formulir Pendaftaran Internet ALUSNet</h3>
    </div>

    <form method="POST" action="/pendaftaran">
      @csrf
      <div class="card-body">

        <div class="row">
          {{-- Kolom Kiri --}}
          <div class="col-md-6">
            <h5 class="mb-3 font-weight-bold">Data Pelanggan</h5>
            <div class="mb-2"><strong>CID:</strong><br>{{ $customer->customer_id }}</div>
            <div class="mb-2"><strong>Nama Lengkap:</strong><br>{{ $customer->name }}</div>
            <div class="mb-2"><strong>Alamat:</strong><br>{{ $customer->address }}</div>
            <div class="mb-2"><strong>No. KTP:</strong><br>{{ $customer->id_card }}</div>
            <div class="mb-2"><strong>No. HP (WhatsApp):</strong><br>{{ $customer->phone }}</div>
            <div class="mb-2"><strong>Email:</strong><br>{{ $customer->email }}</div>
          </div>

          {{-- Kolom Kanan --}}
          <div class="col-md-6">
            <h5 class="mb-3 font-weight-bold">Informasi Layanan</h5>

            <div class="mb-2">
              <strong>Paket:</strong><br>
              {{ $customer->plan_name->name }} - Rp.{{ number_format($customer->plan_name->price, 0, ',', '.') }}
            </div>
            <input type="hidden" name="id" value="{{ $customer->id }}">
            <div class="mb-2">
              <strong>Biaya Registrasi:</strong><br>
              <input type="text" name="biaya_registrasi" class="form-control col-md-2" value="0" required>
            </div>

            <div class="mb-2">
              <label for="tanggal_pendaftaran"><strong>Tanggal Pendaftaran:</strong></label><br>
              <input class="form-control col-md-2" type="date" name="tanggal_pendaftaran" id="tanggal_pendaftaran" 
              value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
            </div>



          </div>
        </div>

        <hr>
        <h5 class="mb-3 font-weight-bold">Daftar Perangkat</h5>
        <div class="table-responsive">
          <table class="table table-bordered" id="device-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Keterangan</th>
                <th>SN</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th><button type="button" class="btn btn-sm btn-success" onclick="addRow()">+</button></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>1</td>
                <td><input type="text" name="devices[0][keterangan]" class="form-control"></td>
                <td><input type="text" name="devices[0][sn]" class="form-control"></td>
                <td><input type="number" name="devices[0][jumlah]" class="form-control"></td>
                <td><input type="text" name="devices[0][status]" class="form-control"></td>
                <td></td>
              </tr>
            </tbody>
          </table>
        </div>

        <hr>



        <div class="form-group">
          <label>Tanggal</label><br>
          {{ \Carbon\Carbon::now()->format('d-m-Y') }}
        </div>



        <div class="form-group">
          <label>Catatan Tambahan</label>
          <textarea name="keterangan_tambahan" class="form-control" rows="4"></textarea>
        </div>
        <div class="form-group col-md-4">
          <label>Petugas</label>
          <input type="text" name="ttd_nama" class="form-control" value="{{ Auth::user()->name }}" required>
        </div>
      </div>

      <div class="card-footer">
        <button type="submit" class="btn btn-primary">Submit</button>
        <a href="{{ url('customers') }}" class="btn btn-secondary float-right">Cancel</a>
      </div>
    </form>
  </div>
</section>

<!-- Tambahkan Script JavaScript -->
<script>
  let rowCount = 1;
  function addRow() {
    const table = document.querySelector('#device-table tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
    <td>${rowCount + 1}</td>
    <td><input type="text" name="devices[${rowCount}][keterangan]" class="form-control"></td>
    <td><input type="text" name="devices[${rowCount}][sn]" class="form-control"></td>
    <td><input type="number" name="devices[${rowCount}][jumlah]" class="form-control"></td>
    <td><input type="text" name="devices[${rowCount}][status]" class="form-control"></td>
    <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">-</button></td>
    `;
    table.appendChild(newRow);
    rowCount++;
  }
</script>

@endsection
