@extends('layout.main')
@section('title', 'Laporan Neraca')
@section('content')
<div class="container">
    <h2 class="mt-3">Laporan Neraca</h2>
    <form method="get" class="form-inline mb-4">
        <label for="tanggal_awal">Dari:</label>
        <input type="date" id="tanggal_awal" name="tanggal_awal" value="{{ $tanggalAwal }}" class="form-control mx-2">
        <label for="tanggal_akhir">Sampai:</label>
        <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="{{ $tanggalAkhir }}" class="form-control mx-2">
        <button class="btn btn-primary">Tampilkan</button>
    </form>

    <div class="row">
        <div class="col-md-6">
            <h4>Aktiva</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama Akun</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['aktiva'] as $row)
                    <tr>
                        <td>{{ $row['akun']->name }}</td>
                        <td class="text-end">Rp{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total Aktiva</th>
                        <th class="text-end">Rp{{ number_format($total['aktiva'], 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="col-md-6">
            <h4>Kewajiban</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama Akun</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['kewajiban'] as $row)
                    <tr>
                        <td>{{ $row['akun']->name }}</td>
                        <td class="text-end">Rp{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total Kewajiban</th>
                        <th class="text-end">Rp{{ number_format($total['kewajiban'], 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
            <h4 class="mt-4">Ekuitas</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama Akun</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['ekuitas'] as $row)
                    <tr>
                        <td>{{ $row['akun']->name }}</td>
                        <td class="text-end">Rp{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total Ekuitas</th>
                        <th class="text-end">Rp{{ number_format($total['ekuitas'], 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
            <div class="alert alert-info mt-2">
                <strong>Total Kewajiban + Ekuitas:</strong>
                Rp{{ number_format($total_kewajiban_ekuitas, 0, ',', '.') }}
            </div>
        </div>
    </div>
    <div class="mt-3">
        <span class="badge bg-success" style="font-size:1.1em;">
            @if($total['aktiva'] == $total_kewajiban_ekuitas)
            Neraca SEIMBANG ✅
            @else
            Neraca TIDAK SEIMBANG ⚠️
            @endif
        </span>
    </div>
</div>
@endsection
