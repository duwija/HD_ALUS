@extends('layout.main')

@section('title', 'Laporan Rugi Laba')

@section('content')
<div class="container">
    <h1 class="mt-4 mb-4">Laporan Rugi Laba</h1>

    <!-- Filter Tanggal -->
    <form method="GET" action="/jurnal/rugilaba">
        <div class="row mb-4">
            <div class="col-md-3">
                <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                <input type="date" id="tanggal_awal" name="tanggal_awal" class="form-control" value="{{ request('tanggal_awal', now()->startOfMonth()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="form-control" value="{{ request('tanggal_akhir', now()->endOfMonth()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    <!-- Tabel Laporan Rugi Laba -->
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Kategori</th>
                    <th>Nama Akun</th>
                    <th class="text-end">Saldo Awal</th>
                    <th class="text-end">Pergerakan (Periode)</th>
                    <th class="text-end">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody>
                <!-- Pendapatan -->
                <tr>
                    <td colspan="5"><strong>Pendapatan</strong></td>
                </tr>
                @foreach ($pendapatan as $item)
                <tr>
                    <td>{{ $item->akun_code }}</td>
                    <td>{{ $item->name }}</td>
                    <td class="text-end">{{ number_format($item->saldo_awal, 2) }}</td>
                    <td class="text-end">{{ number_format($item->transactions->sum('kredit') - $item->transactions->sum('debet'), 2) }}</td>
                    <td class="text-end">{{ number_format($item->saldo_awal + ($item->transactions->sum('kredit') - $item->transactions->sum('debet')), 2) }}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="4" class="text-end"><strong>Total Pendapatan</strong></td>
                    <td class="text-end"><strong>{{ number_format($totalPendapatan, 2) }}</strong></td>
                </tr>

                <!-- Beban -->
                <tr>
                    <td colspan="5"><strong>Beban</strong></td>
                </tr>
                @foreach ($beban as $item)
                <tr>
                    <td>{{ $item->akun_code }}</td>
                    <td>{{ $item->name }}</td>
                    <td class="text-end">{{ number_format($item->saldo_awal, 2) }}</td>
                    <td class="text-end">{{ number_format($item->transactions->sum('debet') - $item->transactions->sum('kredit'), 2) }}</td>
                    <td class="text-end">{{ number_format($item->saldo_awal + ($item->transactions->sum('debet') - $item->transactions->sum('kredit')), 2) }}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="4" class="text-end"><strong>Total Beban</strong></td>
                    <td class="text-end"><strong>{{ number_format($totalBeban, 2) }}</strong></td>
                </tr>

                <!-- Laba/Rugi Bersih -->
                <tr>
                    <td colspan="4" class="text-end"><strong>Laba/Rugi Bersih</strong></td>
                    <td class="text-end"><strong>{{ number_format($labaRugi, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
