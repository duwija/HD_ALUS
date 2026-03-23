@extends('layout.main')
@section('title', 'Transfer Uang')
@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Transaksi - Transfer Uang</h3>
    </div>
    <form role="form" action="" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="card-body">
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
            <input type="number" id="amount" name="amount" class="form-control" step="0.01" placeholder="Rp 0,00" required>
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
            <label for="transaction_date">Tgl Transaksi</label>
            <input type="date" id="transaction_date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
          </div>
        </div>

        <div class="form-group mt-3">
          <button type="reset" class="btn btn-danger">Batal</button>
          <button type="submit" class="btn btn-success">Buat Transferan</button>
        </div>
      </div>
    </form>
  </div>
</section>
@endsection
