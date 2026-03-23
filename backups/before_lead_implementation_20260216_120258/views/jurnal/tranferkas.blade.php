@extends('layout.main')

@section('title', 'Transfer Kas')

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header row mb-12 card-title">


      <div class="mb-4">
        <strong>Transaksi - Transfer Kas</strong>
      </div> 
      <div class="ml-auto ">
        <div class="nav-item dropdown ">
          <button class="btn btn-primary dropdown-toggle" type="button" id="transactionDropdown" data-toggle="dropdown" aria-expanded="false">
           Transaksi
         </button>
         <ul class="dropdown-menu" aria-labelledby="transactionDropdown">
          <li><a class="dropdown-item" href="/jurnal/kasmasuk"><i class="fas fa-hand-holding-usd"></i> Kas Masuk</a></li>
          <li><a class="dropdown-item" href="/jurnal/kaskeluar"><i class="fas fa-money-bill-wave"></i> Kas Keluar</a></li>
          <li><a class="dropdown-item" href="/jurnal/transferkas"><i class="fas fa-random"></i> Transfer Kas</a></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="card-body">
    <form id="transaksiForm" method="POST" action="/jurnal/transferkastransaction" class="d-inline ">
      @csrf
      <input type="hidden" name="type" class="form-control" value="transferkas">
      <div class="row mb-3">
        <div class="col-md-3">
          <label for="kas" class="form-label">Transfer Dari</label>
          <select name="akunkredit" id="akunkredit" class="form-control select2" required>
            <option value="">-- Pilih Akun --</option>
            @foreach($akunkredit as $akunk)
            <option value="{{ $akunk->akun_code }}">{{ $akunk->akun_code }} - {{ $akunk->name }}</option>
            @endforeach
          </select>
        </div>


        <div class="col-md-3">
          <label for="date" class="form-label">Tgl Transaksi</label>
          <input type="date" name="date" class="form-control" id="date" required>
        </div>
        <div class="col-md-3">
          <label for="noTransaksi" class="form-label">No Transaksi</label>
          <input type="text" class="form-control" id="noTransaksi" placeholder="[Auto]" disabled>
        </div>
      </div>

      <div class="row mb-6">

        <div class="col-md-3">
          <label for="kas" class="form-label">Setor Ke</label>
          <select name="akundebet" id="akundebet" class="form-control select2" required>
            <option value="">-- Pilih Akun --</option>
            @foreach($akundebet as $akund)
            <option value="{{ $akund->akun_code }}">{{ $akund->akun_code }} - {{ $akund->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label for="kas" class="form-label">Jumlah</label>
          <input type="number"  name="amount" id="amount" class="form-control jumlah" placeholder="Rp. 0,00" min="0" step="0.01" required></h5>
        </div>


        
      </div>






      <div class="row my-3">
        <div class="col-md-12">
          <label for="memo" class="form-label">Memo</label>
          <textarea class="form-control" name="memo" id="memo" rows="3"></textarea>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button type="reset" class="btn btn-danger">Batal</button>
        <button type="submit" class="btn btn-success jurnal-transaction">Buat Transferan</button>
      </div>
    </form>

  </div>
</div>
</section>
@endsection

@section('footer-scripts')





@endsection

