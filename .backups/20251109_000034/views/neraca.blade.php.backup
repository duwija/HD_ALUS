{{-- resources/views/jurnal/neraca.blade.php --}}
@extends('layout.main')

@section('content')
<div class="container">
  <h1>Neraca</h1>
  <p>Periode: {{ $tanggalAwal }} s/d {{ $tanggalAkhir }}</p>

  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Nama Akun</th>
        <th class="text-end">Saldo (Rp)</th>
      </tr>
    </thead>
    <tbody>

      {{-- Aktiva --}}
      <tr class="table-primary">
        <td colspan="2"><strong>Aktiva</strong></td>
      </tr>
      @foreach ($data['aktiva'] ?? [] as $parentData)
      <tr>
        <td><strong>{{ $parentData[0]->name }}</strong></td>
        <td class="text-end"><strong>{{ number_format($parentData['total_saldo'], 2, ',', '.') }}</strong></td>
      </tr>
      @foreach ($parentData['children'] as $child)
      <tr>
        <td>&nbsp;&nbsp;&nbsp;{{ $child['akun']->nama }}</td>
        <td class="text-end">{{ number_format($child['saldo'], 2, ',', '.') }}</td>
      </tr>
      @endforeach
      @endforeach

      {{-- Kewajiban --}}
      <tr class="table-primary">
        <td colspan="2"><strong>Kewajiban</strong></td>
      </tr>
      @foreach ($data['kewajiban'] ?? [] as $parentData)
      <tr>
        <td><strong>{{ $parentData['parent']->nama }}</strong></td>
        <td class="text-end"><strong>{{ number_format($parentData['total_saldo'], 2, ',', '.') }}</strong></td>
      </tr>
      @foreach ($parentData['children'] as $child)
      <tr>
        <td>&nbsp;&nbsp;&nbsp;{{ $child['akun']->nama }}</td>
        <td class="text-end">{{ number_format($child['saldo'], 2, ',', '.') }}</td>
      </tr>
      @endforeach
      @endforeach

      {{-- Ekuitas --}}
      <tr class="table-primary">
        <td colspan="2"><strong>Ekuitas</strong></td>
      </tr>
      @foreach ($data['ekuitas'] ?? [] as $parentData)
      <tr>
        <td><strong>{{ $parentData['parent']->nama }}</strong></td>
        <td class="text-end"><strong>{{ number_format($parentData['total_saldo'], 2, ',', '.') }}</strong></td>
      </tr>
      @foreach ($parentData['children'] as $child)
      <tr>
        <td>&nbsp;&nbsp;&nbsp;{{ $child['akun']->nama }}</td>
        <td class="text-end">{{ number_format($child['saldo'], 2, ',', '.') }}</td>
      </tr>
      @endforeach
      @endforeach

      {{-- Total --}}
      <tr class="table-success">
        <th>Total Keseluruhan</th>
        <th class="text-end">{{ number_format($totalAktiva + $totalKewajiban + $totalEkuitas, 2, ',', '.') }}</th>
      </tr>
    </tbody>
  </table>
</div>
@endsection
