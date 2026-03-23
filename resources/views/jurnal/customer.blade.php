@extends('layout.main')
@section('title', "Jurnal Customer - {$customer->name}")
@section('content')

<section class="content-header">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                <i class="fas fa-user mr-2"></i> Jurnal Customer:
                <a href="{{ url('/customer/'.$customer->id) }}" class="text-white text-decoration-underline">
                    {{ $customer->customer_id }} - {{ $customer->name }}
                </a>
            </h3>
            <div class="btn-group">
                <button class="btn btn-light btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
                <a href="{{ url('/jurnal/export-excel/'.$customer->id) }}" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Excel</a>
                <a href="{{ url('/jurnal/export-pdf/'.$customer->id) }}" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
            </div>
        </div>

        <div class="card-body bg-light">
            @if($jurnals->isEmpty())
            <div class="alert alert-warning text-center shadow-sm">
                <i class="fas fa-exclamation-circle mr-1"></i> 
                Tidak ada jurnal untuk customer ini.
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm shadow-sm bg-white">
                    <thead class="bg-secondary text-white font-weight-bold">
                        <tr>
                            <th style="width:3%">#</th>
                            <th style="width:12%">Tanggal</th>
                            <th style="width:45%">Deskripsi</th>
                            <th style="width:20%">Akun</th>
                            <th class="text-right" style="width:10%">Debet</th>
                            <th class="text-right" style="width:10%">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jurnals as $code => $group)
                        {{-- tampilkan note sebagai baris pembatas --}}
                        @if(optional($group->first())->note)
                        <tr>
                            <td colspan="6">
                                <i class="fas fa-sticky-note mr-1"></i> 
                                <strong>{{ optional($group->first())->note }}</strong>
                            </td>
                        </tr>
                        @endif

                        @foreach($group as $index => $jurnal)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($jurnal->date)->format('d-m-Y') }}</td>
                            <td>{{ $jurnal->description }}</td>
                            <td>
                                <span class="text-primary font-weight-bold">
                                    {{ $jurnal->id_akun }}
                                    @if($jurnal->id_akun)
                                    | {{ $jurnal->akun->name }}
                                    @endif
                                </span>
                            </td>
                            <td class="text-right text-success">
                                {{ $jurnal->debet ? number_format($jurnal->debet, 2, ',', '.') : '-' }}
                            </td>
                            <td class="text-right text-danger">
                                {{ $jurnal->kredit ? number_format($jurnal->kredit, 2, ',', '.') : '-' }}
                            </td>
                        </tr>
                        @endforeach

                        {{-- subtotal per group --}}
                        <tr class="bg-light font-weight-bold">
                            <td colspan="4" class="text-right text-uppercase">Subtotal</td>
                            <td class="text-right text-success">{{ number_format($group->sum('debet'), 2, ',', '.') }}</td>
                            <td class="text-right text-danger">{{ number_format($group->sum('kredit'), 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-secondary text-white font-weight-bold">
                            <td colspan="4" class="text-right text-uppercase">TOTAL KESELURUHAN</td>
                            <td class="text-right">{{ number_format($totalDebet, 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($totalKredit, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>
    </div>
</section>

@endsection
