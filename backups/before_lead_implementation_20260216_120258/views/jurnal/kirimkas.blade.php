@extends('layout.main')
@section('title', 'Terima Uang')
@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Transaksi - Terima Uang</h3>
    </div>

    <form role="form" action="" method="POST">
      @csrf
      <div class="card-body">
        <div class="row mb-3 bg-light p-3">
          <div class="form-group col-md-3">
            <label for="pay_to">Setor Ke</label>
            <select id="pay_to" name="pay_to" class="form-control" required>
              <option value="">Pilih Akun</option>
              <option value="1">(1-10001) Kas (Cash & Bank)</option>
            </select>
          </div>

          <div class="form-group col-md-3">
            <label for="payer">Yang Membayar</label>
            <select id="payer" name="payer" class="form-control" required>
              <option value="">Pilih kontak</option>
              <option value="supplier_a">Supplier A</option>
            </select>
          </div>

          <div class="form-group col-md-3">
            <label for="transaction_date">Tgl Transaksi</label>
            <input type="date" id="transaction_date" name="transaction_date" class="form-control" required>
          </div>

          <div class="form-group col-md-3">
            <label for="transaction_no">No Transaksi</label>
            <input type="text" id="transaction_no" name="transaction_no" class="form-control" value="[Auto]" disabled>
          </div>
        </div>

        <table class="table table-bordered">
          <thead class="bg-light">
            <tr>
              <th>Terima Dari</th>
              <th>Deskripsi</th>
              <th>Pajak (%)</th>
              <th>Jumlah</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody id="account-rows">
            <tr>
              <td>
                <select name="accounts[]" class="form-control">
                  <option value="">Pilih Akun</option>
                  <option value="1">(1-10002) Rekening Bank (Cash & Bank)</option>
                  <option value="2">(1-10200) Persediaan Barang (Inventory)</option>
                </select>
              </td>
              <td><input type="text" name="descriptions[]" class="form-control"></td>
              <td><input type="number" name="taxes[]" class="form-control" value="0" step="0.01"></td>
              <td><input type="number" name="amounts[]" class="form-control" value="0" step="0.01"></td>
              <td><button type="button" class="btn btn-danger remove-row">-</button></td>
            </tr>
          </tbody>
        </table>

        <button type="button" id="add-row" class="btn btn-primary mt-2">+ Tambah Data</button>

        <div class="form-group mt-3">
          <label for="memo">Memo</label>
          <textarea id="memo" name="memo" class="form-control"></textarea>
        </div>

        <div class="row mt-3">
          <div class="col-md-8"></div>
          <div class="col-md-4">
            <div class="row">
              <div class="col-md-6 text-right">SubTotal</div>
              <div class="col-md-6"><span id="subtotal">Rp 0,00</span></div>
            </div>
            <div class="row">
              <div class="col-md-6 text-right">PPN</div>
              <div class="col-md-6"><span id="tax-total">Rp 0,00</span></div>
            </div>
            <div class="row">
              <div class="col-md-6 text-right">Total</div>
              <div class="col-md-6"><span id="total">Rp 0,00</span></div>
            </div>
          </div>
        </div>

        <div class="form-group mt-3">
          <button type="submit" class="btn btn-success">Buat Penerimaan</button>
          <button type="reset" class="btn btn-danger">Batal</button>
        </div>
      </div>
    </form>
  </div>
</section>
@endsection

@section('footer-scripts')
<script>
  function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount);
  }

  function calculateTotals() {
    let subtotal = 0;
    let taxTotal = 0;

    document.querySelectorAll('#account-rows tr').forEach(function (row) {
      const amount = parseFloat(row.querySelector('input[name="amounts[]"]').value) || 0;
      const taxRate = parseFloat(row.querySelector('input[name="taxes[]"]').value) || 0;

      subtotal += amount;
      taxTotal += (amount * taxRate) / 100;
    });

    const total = subtotal + taxTotal;

    document.getElementById('subtotal').innerText = formatCurrency(subtotal);
    document.getElementById('tax-total').innerText = formatCurrency(taxTotal);
    document.getElementById('total').innerText = formatCurrency(total);
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('add-row').addEventListener('click', function () {
      const row = document.querySelector('#account-rows tr').cloneNode(true);
      row.querySelectorAll('input').forEach(function (field) {
        field.value = '';
      });
      document.getElementById('account-rows').appendChild(row);
    });

    document.addEventListener('click', function (e) {
      if (e.target.classList.contains('remove-row')) {
        e.target.closest('tr').remove();
        calculateTotals();
      }
    });

    document.addEventListener('input', function (e) {
      if (e.target.matches('input[name="amounts[]"], input[name="taxes[]"]')) {
        calculateTotals();
      }
    });

    calculateTotals();
  });
</script>
@endsection
