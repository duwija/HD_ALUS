@extends('layout.main')
@section('title','JURNAL UMUM')
@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Transaksi - Kirim Uang</h3>
    </div>

    <form role="form" action="" method="POST">
        @csrf
        <div class="card-body row">
            <div class="form-group col-md-3">
                <label for="pay_from">Bayar Dari</label>
                <select id="pay_from" name="pay_from" class="form-control" required>
                    <option value="">Pilih Akun</option>
                    <option value="1">Kas (Cash & Bank)</option>
                </select>
            </div>

            <div class="form-group col-md-3">
                <label for="contact">Penerima</label>
                <select id="contact" name="contact" class="form-control" required>
                    <option value="">Pilih kontak</option>
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

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Pembayaran Untuk</th>
                        <th>Deskripsi</th>
                        <th>Pajak</th>
                        <th>Jumlah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="account-rows">
                    <tr>
                        <td>
                            <select name="accounts[]" class="form-control">
                                <option value="">Pilih Akun</option>
                                <option value="1">Akun 1</option>
                                <option value="2">Akun 2</option>
                            </select>
                        </td>
                        <td><input type="text" name="descriptions[]" class="form-control"></td>
                        <td>
                            <select name="taxes[]" class="form-control">
                                <option value="0">Tanpa Pajak</option>
                                <option value="10">PPN 10%</option>
                            </select>
                        </td>
                        <td><input type="number" name="amounts[]" class="form-control" value="0" step="0.01"></td>
                        <td><button type="button" class="btn btn-danger remove-row">Hapus</button></td>
                    </tr>
                </tbody>
            </table> 

            <div class="form-group col-md-2">
                <button type="button" id="add-row" class="btn btn-primary form-control mt-3 mb-3">+ Tambah Data</button>
            </div>

            <div class="table">
                <div class="tfoot">
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-3 text-right">SubTotal</div>
                        <div class="col-md-3"><span id="subtotal">Rp 0,00</span></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-3 text-right">PPN</div>
                        <div class="col-md-3"><span id="tax-total">Rp 0,00</span></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-3 text-right">
                            <button type="button" id="add-discount-btn" class="badge badge-success">Masukkan Potongan</button>
                        </div>
                        <div class="col-md-3"></div>
                    </div>

                    <div class="row d-none" id="discount-row">
                        <div class="col-md-5 text-right"></div>
                        <div class="col-md-2 text-right"></div>
                        <div class="col-md-2 text-right form-group">
                            <div class=" text-right">Potongan</div>
                            <input class="form-control text-right" type="number" id="discount" name="discount" value="0" step="0.01">
                        </div>
                        <div class="col-md-3 "> 
                            <span id="discount-display">Rp 0,00</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-3 text-right">Total</div>
                        <div class="col-md-3"><span id="total">Rp 0,00</span></div>
                    </div>

                    <button type="submit" class="btn btn-success">Buat Pengiriman</button>
                </div>
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
            const taxRate = parseFloat(row.querySelector('select[name="taxes[]"]').value) || 0;

            subtotal += amount;
            taxTotal += (amount * taxRate) / 100;
        });

        const discount = parseFloat(document.getElementById('discount').value) || 0;
        const total = subtotal + taxTotal - discount;

        document.getElementById('subtotal').innerText = formatCurrency(subtotal);
        document.getElementById('tax-total').innerText = formatCurrency(taxTotal);
        document.getElementById('discount-display').innerText = formatCurrency(discount);
        document.getElementById('total').innerText = formatCurrency(total);
    }


    document.addEventListener('DOMContentLoaded', function () {
        const addDiscountBtn = document.getElementById('add-discount-btn');
        const discountRow = document.getElementById('discount-row');

    // Klik tombol untuk menampilkan div potongan
        addDiscountBtn.addEventListener('click', function () {
        discountRow.classList.remove('d-none'); // Show the discount div
        addDiscountBtn.style.display = 'none'; // Hide the button
    });

    // Event listener untuk menghitung ulang total saat potongan diubah
        document.getElementById('discount').addEventListener('input', function () {
        calculateTotals(); // Memanggil fungsi kalkulasi jika ada
    });
    });


    document.addEventListener('input', function (e) {
        if (e.target.matches('input[name="amounts[]"], select[name="taxes[]"], #discount')) {
            calculateTotals();
        }
    });

    document.getElementById('add-row').addEventListener('click', function () {
        const row = document.querySelector('#account-rows tr').cloneNode(true);
        row.querySelectorAll('input, select').forEach(function (field) {
            field.value = '';
        });
        document.getElementById('account-rows').appendChild(row);
        calculateTotals();
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
            calculateTotals();
        }
    });

    calculateTotals();
</script>
@endsection
