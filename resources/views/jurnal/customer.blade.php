@extends('layout.main')
@section('title', "Jurnal Customer - {$customer->name}")
@section('content')

<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header-custom" style="background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%); padding: 18px 24px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0 font-weight-bold text-white" style="letter-spacing: 1px;">
                        <i class="fas fa-user mr-2"></i>Jurnal Customer
                    </h4>
                    <small class="text-white" style="opacity: 0.85;">
                        <a href="{{ url('/customer/'.$customer->id) }}" class="text-white">
                            {{ $customer->customer_id }} - {{ $customer->name }}
                        </a>
                    </small>
                </div>
            </div>
        </div>

        <div class="card-body">
            {{-- Export toolbar --}}
            <div class="d-flex justify-content-end mb-3">
                <div class="btn-group">
                    <button class="btn btn-secondary btn-sm" onclick="window.print()">
                        <i class="fas fa-print mr-1"></i> Cetak
                    </button>
                    <a href="{{ url('/jurnal/export-excel/'.$customer->id) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </a>
                    <a href="{{ url('/jurnal/export-pdf/'.$customer->id) }}" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </a>
                </div>
            </div>

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
                            <th style="width:10%">Tanggal</th>
                            <th style="width:10%">Tipe</th>
                            <th style="width:38%">Deskripsi</th>
                            <th style="width:17%">Akun</th>
                            <th class="text-right" style="width:11%">Debet</th>
                            <th class="text-right" style="width:11%">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jurnals as $code => $group)
                        {{-- Group header row --}}
                        <tr class="table-light">
                            <td colspan="7" class="py-1">
                                <small class="text-muted">
                                    <i class="fas fa-hashtag mr-1"></i><strong>{{ $code }}</strong>
                                    @if(optional($group->first())->memo)
                                        &mdash; <i class="fas fa-sticky-note mr-1"></i>{{ optional($group->first())->memo }}
                                    @elseif(optional($group->first())->note)
                                        &mdash; {{ optional($group->first())->note }}
                                    @endif
                                </small>
                            </td>
                        </tr>

                        @foreach($group as $index => $jurnal)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($jurnal->date)->format('d-m-Y') }}</td>
                            <td>
                                @php
                                    $typeLabels = [
                                        'jumum'    => ['label' => 'Jurnal', 'class' => 'badge-primary'],
                                        'general'  => ['label' => 'General', 'class' => 'badge-info'],
                                        'kasmasuk' => ['label' => 'Kas Masuk', 'class' => 'badge-success'],
                                        'kaskeluar'=> ['label' => 'Kas Keluar', 'class' => 'badge-danger'],
                                        'kasbank'  => ['label' => 'Kas Bank', 'class' => 'badge-warning'],
                                        'reversal' => ['label' => 'Reversal', 'class' => 'badge-secondary'],
                                        'writeoff' => ['label' => 'Write-off', 'class' => 'badge-dark'],
                                    ];
                                    $tl = $typeLabels[$jurnal->type] ?? ['label' => $jurnal->type, 'class' => 'badge-secondary'];
                                @endphp
                                <span class="badge {{ $tl['class'] }}">{{ $tl['label'] }}</span>
                            </td>
                            <td>{{ $jurnal->description }}</td>
                            <td>
                                <span class="text-primary font-weight-bold">
                                    {{ $jurnal->id_akun }}
                                    @if($jurnal->akun)
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
                            <td colspan="5" class="text-right text-uppercase">Subtotal</td>
                            <td class="text-right text-success">{{ number_format($group->sum('debet'), 2, ',', '.') }}</td>
                            <td class="text-right text-danger">{{ number_format($group->sum('kredit'), 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-secondary text-white font-weight-bold">
                            <td colspan="5" class="text-right text-uppercase">TOTAL KESELURUHAN</td>
                            <td class="text-right">{{ number_format($totalDebet, 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($totalKredit, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection
