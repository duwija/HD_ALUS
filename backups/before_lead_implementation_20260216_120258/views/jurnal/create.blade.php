<!-- View for jurnal/create -->
@extends('layout.main')

@section('content')
<div class="container">
  <h2>Create Transaction</h2>
  <form method="POST" action="/jurnal/store">
    @csrf

    <!-- Transaction Type -->
    <div class="form-group">
      <label for="akuntransaction">Transaction Type</label>
      <select name="akuntransaction" id="akuntransaction" class="form-control">
        @foreach($akuntransaction as $id => $name)
        <option value="{{ $id }}" {{ old('akuntransaction') == $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
      </select>
    </div>

    <!-- Accounts -->
    <div class="form-group">
      <label for="akundebet">Debit Account</label>
      <select name="akundebet" id="akundebet" class="form-control">
        @foreach($akundebet as $akun)
        <option value="{{ $akun->id }}" {{ old('akundebet') == $akun->id ? 'selected' : '' }}>{{ $akun->name }} ({{ $akun->type }})</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label for="akunkredit">Credit Account</label>
      <select name="akunkredit" id="akunkredit" class="form-control">
        @foreach($akunkredit as $akun)
        <option value="{{ $akun->id }}" {{ old('akunkredit') == $akun->id ? 'selected' : '' }}>{{ $akun->name }} ({{ $akun->type }})</option>
        @endforeach
      </select>
    </div>

    <!-- Amount -->
    <div class="form-group">
      <label for="amount">Amount</label>
      <input type="number" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" required>
    </div>

    <!-- Description -->
    <div class="form-group">
      <label for="description">Description</label>
      <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
    </div>

    <!-- Utang (Optional) -->
    @if(!empty($utang))
    <div class="form-group">
      <label for="utang">Outstanding Transactions</label>
      <select name="utang" id="utang" class="form-control">
        <option value="">Select</option>
        @foreach($utang as $u)
        <option value="{{ $u->id_akun }}">{{ $u->reff }} - {{ $u->description }} ({{ $u->debet - $u->kredit }})</option>
        @endforeach
      </select>
    </div>
    @endif

    <!-- Submit Button -->
    <button type="submit" class="btn btn-primary">Submit</button>
  </form>
</div>
@endsection
