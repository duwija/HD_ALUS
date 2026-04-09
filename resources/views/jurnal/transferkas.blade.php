@extends('layout.main')
@section('title', 'Transfer Uang')
@section('content')

<div class="container-fluid">
  <div class="card shadow-sm">
    <div class="card-header-custom" style="background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%); padding: 18px 24px;">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h4 class="mb-0 font-weight-bold text-white" style="letter-spacing: 1px;">
            <i class="fas fa-exchange-alt mr-2"></i>TRANSFER KAS
          </h4>
          <small class="text-white" style="opacity: 0.85;">Pencatatan transaksi transfer antar kas</small>
        </div>
        <div class="dropdown">
          <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="transactionDropdown" data-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-exchange-alt mr-1"></i> Transaksi Lainnya
          </button>
          <ul class="dropdown-menu dropdown-menu-right shadow" aria-labelledby="transactionDropdown">
            <li><a class="dropdown-item" href="/jurnal/kasmasuk"><i class="fas fa-hand-holding-usd text-success mr-2"></i> Kas Masuk</a></li>
            <li><a class="dropdown-item" href="/jurnal/kaskeluar"><i class="fas fa-money-bill-wave text-danger mr-2"></i> Kas Keluar</a></li>
            <li><a class="dropdown-item" href="/jurnal/transferkas"><i class="fas fa-exchange-alt text-primary mr-2"></i> Transfer Kas</a></li>
            <li><a class="dropdown-item" href="/jurnal/general"><i class="fas fa-file-invoice text-secondary mr-2"></i> Transaksi General</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="card-body">
    <form role="form" action="" method="POST" enctype="multipart/form-data">
      @csrf
        <div class="row mb-3 bg-light p-3">
          <div class="form-group col-md-3">
            <label for="transfer_from">Transfer Dari</label>
            <select id="transfer_from" name="transfer_from" class="form-control" required>
              <option value="">Pilih Akun</option>
              <option value="1">(1-10001) - Kas (Cash & Bank)</option>
              <option value="2">(1-10002) - Rekening Bank (Cash & Bank)</option>
            </select>
          </div>

          <div class="form-group col-md-3">
            <label for="transfer_to">Setor Ke</label>
            <select id="transfer_to" name="transfer_to" class="form-control" required>
              <option value="">Pilih Akun</option>
              <option value="1">(1-10001) - Kas (Cash & Bank)</option>
              <option value="2">(1-10002) - Rekening Bank (Cash & Bank)</option>
            </select>
          </div>

          <div class="form-group col-md-3">
            <label for="amount">Jumlah</label>
            <input type="text" id="amount" name="amount" class="form-control" inputmode="numeric" autocomplete="off" placeholder="0" required>
          </div>
        </div>

        <div class="row mb-3">
          <div class="form-group col-md-6">
            <label for="memo">Memo</label>
            <textarea id="memo" name="memo" class="form-control" rows="3"></textarea>
          </div>

          <div class="form-group col-md-3">
            <label for="tag">Tag</label>
            <input type="text" id="tag" name="tag" class="form-control">
          </div>

          <div class="form-group col-md-3">
            <label for="transaction_no">No Transaksi</label>
            <input type="text" id="transaction_no" name="transaction_no" class="form-control" value="[Auto]" disabled>
          </div>
        </div>

        <div class="row mb-3">
          <div class="form-group col-md-6">
            <label for="attachment">Lampiran</label>
            <input type="file" id="attachment" name="attachment" class="form-control-file">
            <small class="form-text text-muted">Ukuran maksimal 10 MB/file</small>
          </div>

          <div class="form-group col-md-3">
            <label for="transaction_date_display">Tgl Transaksi</label>
            <input type="text" class="form-control" id="transaction_date_display" value="{{ date('d/m/Y') }}" autocomplete="off" required placeholder="dd/mm/yyyy">
            <input type="hidden" name="transaction_date" id="transaction_date_hidden" value="{{ date('Y-m-d') }}">
          </div>
        </div>

        <div class="form-group mt-3">
          <button type="reset" class="btn btn-danger">Batal</button>
          <button type="submit" class="btn btn-success">Buat Transferan</button>
        </div>
    </form>
    </div>
  </div>
</div>

@section('footer-scripts')
<script>
$(document).ready(function() {
  $('#transaction_date_display').datepicker({
    format: 'dd/mm/yyyy',
    todayHighlight: true,
    autoclose: true,
  }).on('changeDate', function(e) {
    var d = e.date;
    var yyyy = d.getFullYear();
    var mm = String(d.getMonth() + 1).padStart(2, '0');
    var dd = String(d.getDate()).padStart(2, '0');
    $('#transaction_date_hidden').val(yyyy + '-' + mm + '-' + dd);
  });

  // Izinkan ketik tanggal manual (format: dd/mm/yyyy)
  $('#transaction_date_display').on('input', function() {
    var val = $(this).val().replace(/[^0-9\/]/g, '');
    if (val.length === 2 && val.indexOf('/') === -1) val += '/';
    else if (val.length === 5 && val.split('/').length - 1 === 1) val += '/';
    $(this).val(val);
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(val)) {
      var parts = val.split('/');
      var dNum = parseInt(parts[0], 10);
      var mNum = parseInt(parts[1], 10) - 1;
      var yNum = parseInt(parts[2], 10);
      var dateObj = new Date(yNum, mNum, dNum);
      if (dateObj.getFullYear() === yNum && dateObj.getMonth() === mNum && dateObj.getDate() === dNum) {
        $('#transaction_date_display').datepicker('update', val);
        $('#transaction_date_hidden').val(yNum + '-' + String(mNum + 1).padStart(2, '0') + '-' + String(dNum).padStart(2, '0'));
      }
    }
  });

  // Format ribuan untuk input amount
  $('#amount').on('input', function() {
    var raw = $(this).val().replace(/[^0-9,]/g, '');
    if (!raw.endsWith(',')) {
      var num = parseFloat(raw.replace(/\./g, '').replace(',', '.')) || 0;
      $(this).val(raw === '' ? '' : (num > 0 ? num.toLocaleString('id-ID') : '0'));
    } else {
      $(this).val(raw);
    }
  });

  $('#amount').on('blur', function() {
    if ($(this).val().trim() === '') $(this).val('0');
  });

  // Strip ribuan sebelum submit
  $('form').on('submit', function() {
    var raw = String($('#amount').val()).replace(/\./g, '').replace(',', '.');
    $('#amount').val(parseFloat(raw) || 0);
  });

});
</script>
@endsection
@endsection
