@extends('layout.main')
@section('title', 'Site')

@section('maps')
@inject('olt', 'App\Olt')

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
@endsection

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold">Show Site</h3>
    </div>

    <div class="card-body">
      <form action="{{ url('/create-winpay-va') }}" method="POST">
        @csrf

        <label for="number">Nomor Invoice:</label>
        <input type="text" name="number" id="number" value="INV-0001" required><br>

        <label for="name">Nama Pelanggan:</label>
        <input type="text" name="name" id="name" value="Duwija Putra" required><br>

        <label for="phone">No HP Pelanggan:</label>
        <input type="text" name="phone" id="phone" value="081805360534" required><br>

        <label for="amount">Jumlah (Rp):</label>
        <input type="number" name="amount" id="amount" value="10000" required><br>

        <label for="method">Metode Bank (channel):</label>
        <select name="method" id="method">
          <option value="BSI">BSI</option>
          <option value="BNI">BNI</option>
          <option value="BCA">BCA</option>
          <option value="BRI">BRI</option>
        </select><br>

        <button type="submit">Buat Virtual Account</button>
      </form>

    </div>
    <!-- /.card -->
  </section>
  @endsection
