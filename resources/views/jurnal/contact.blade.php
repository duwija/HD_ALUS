@extends('layout.main')
@section('title', "Jurnal contact - {$contact->name}")
@section('content')

<section class="content-header">
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user"></i> Jurnal contact:
                <span class="text-primary">
                    <a href="{{ url('/contact/'.$contact->contact_id) }}">
                        {{ $contact->contact_id }} - {{ $contact->name }}
                    </a>
                </span>
            </h3>
        </div>

        <div class="card-body">
            @if($jurnals->isEmpty())
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-circle"></i> Tidak ada jurnal untuk contact ini.
            </div>
            @else
            @foreach($jurnals as $code => $group)
            <h5 class="mt-3">
                <span class="badge badge-primary">{{ $code }}</span>
            </h5>
            <table class="table table-sm table-bordered table-striped mb-4">
             <colgroup>
                <col style="width:3%">  {{-- # --}}
                <col style="width:6%"> {{-- Tanggal --}}
                <col style="width:30%"> {{-- Deskripsi --}}
                <col style="width:30%"> {{-- Catatan --}}
                <col style="width:15%"> {{-- Akun --}}
                <col style="width:8%">  {{-- Debet --}}
                <col style="width:8%">  {{-- Kredit --}}
            </colgroup>
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Catatan</th>
                    <th>Akun</th>
                    <th class="text-right">Debet</th>
                    <th class="text-right">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group as $index => $jurnal)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($jurnal->date)->format('Y-m-d') }}</td>
                    <td>{{ $jurnal->description }}</td>
                    <td>{{ $jurnal->note }}</td>
                    <td>
                        <span >
                            {{ $jurnal->id_akun }}
                            @if($jurnal->id_akun)
                            | {{ $jurnal->akun->name }}
                            @endif
                        </span>
                    </td>
                    <td class="text-right">
                        {{ $jurnal->debet ? number_format($jurnal->debet, 2, ',', '.') : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $jurnal->kredit ? number_format($jurnal->kredit, 2, ',', '.') : '-' }}
                    </td>
                </tr>
                @endforeach
                <tr class="bg-light font-weight-bold">
                    <td colspan="5" class="text-right">Subtotal</td>
                    <td class="text-right">{{ number_format($group->sum('debet'), 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($group->sum('kredit'), 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        @endforeach

        <table class="table table-sm table-bordered table-striped table-jurnal">
            <colgroup>
                <col style="width:3%">  {{-- Kosong --}}
                <col style="width:6%"> 
                <col style="width:30%"> 
                <col style="width:30%"> 
                <col style="width:15%"> 
                <col style="width:8%">  
                <col style="width:8%">  
            </colgroup>
            <tfoot>
                <tr class="bg-secondary text-white font-weight-bold">
                    <td colspan="5" class="text-right">TOTAL KESELURUHAN</td>
                    <td class="text-right">{{ number_format($totalDebet, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalKredit, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        @endif
    </div>
</div>
</section>

@endsection
