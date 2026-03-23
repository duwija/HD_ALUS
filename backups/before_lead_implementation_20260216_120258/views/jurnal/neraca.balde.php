@extends('layout.main')
@section('title', 'Laporan Neraca')

@section('content')
<div class="container mt-5">
    <h1 class="text-center">Laporan Neraca</h1>
    <div class="row">
        <!-- Aset -->
        <div class="col-md-4">
            <h3>Aset</h3>
            <ul class="list-group">
                @foreach ($aset as $item)
                <li class="list-group-item d-flex justify-content-between">
                    {{ $item->name }}
                    <span>{{ number_format($item->balance, 2) }}</span>
                </li>
                @endforeach
            </ul>
            <h5 class="text-right mt-2">Total Aset: {{ number_format($totalAset, 2) }}</h5>
        </div>

        <!-- Kewajiban -->
        <div class="col-md-4">
            <h3>Kewajiban</h3>
            <ul class="list-group">
                @foreach ($kewajiban as $item)
                <li class="list-group-item d-flex justify-content-between">
                    {{ $item->name }}
                    <span>{{ number_format($item->balance, 2) }}</span>
                </li>
                @endforeach
            </ul>
            <h5 class="text-right mt-2">Total Kewajiban: {{ number_format($totalKewajiban, 2) }}</h5>
        </div>

        <!-- Ekuitas -->
        <div class="col-md-4">
            <h3>Ekuitas</h3>
            <ul class="list-group">
                @foreach ($ekuitas as $item)
                <li class="list-group-item d-flex justify-content-between">
                    {{ $item->name }}
                    <span>{{ number_format($item->balance, 2) }}</span>
                </li>
                @endforeach
            </ul>
            <h5 class="text-right mt-2">Total Ekuitas: {{ number_format($totalEkuitas, 2) }}</h5>
        </div>
    </div>

    <!-- Persamaan Akuntansi -->
    <div class="mt-4">
        <h4 class="text-center">
            Total Aset: {{ number_format($totalAset, 2) }} = Total Kewajiban + Ekuitas: {{ number_format($totalKewajiban + $totalEkuitas, 2) }}
        </h4>
    </div>
</div>
@endsection
