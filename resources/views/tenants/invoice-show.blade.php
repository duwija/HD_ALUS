@extends('admin.layouts.app')

@section('content')
@php
    $taxfee = $suminvoice->tax == null ? 0 : $suminvoice->tax / 100;
    $subtotal = 0;
    $pphTotal = 0;
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 text-muted">
                            <i class="fas fa-building"></i> Tenant: {{ $tenant->app_name }}
                        </h4>
                        <h3 class="mb-0">
                            <i class="fas fa-file-invoice"></i> Invoice #{{ $suminvoice->number }}
                            @if ($suminvoice->payment_status == 1)
                                <span class="badge badge-success">Lunas</span>
                            @elseif ($suminvoice->payment_status == 2)
                                <span class="badge badge-secondary">Dibatalkan</span>
                            @else
                                <span class="badge badge-warning">Belum Lunas</span>
                            @endif
                        </h3>
                    </div>
                    <div>
                        @if ($suminvoice->tempcode)
                            <a href="{{ 'https://' . $tenant->domain . '/suminvoice/' . $suminvoice->tempcode . '/viewinvoice' }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                                <i class="fas fa-print"></i> Print
                            </a>
                        @endif
                        <a href="{{ route('admin.tenants.customers.show', ['id' => $tenant->id, 'customerId' => $customer->id]) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-user"></i> Customer</h5>
                            <table class="table table-sm table-borderless mb-4">
                                <tr><th width="180">Customer ID</th><td>{{ $customer->customer_id }}</td></tr>
                                <tr><th>Nama</th><td>{{ $customer->name }}</td></tr>
                                <tr><th>Alamat</th><td>{{ $customer->address ?? '-' }}</td></tr>
                                <tr><th>Telepon</th><td>{{ $customer->phone ?? '-' }}</td></tr>
                                <tr><th>Merchant</th><td>{{ $merchant->name ?? '-' }}</td></tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5><i class="fas fa-info-circle"></i> Info Invoice</h5>
                            <table class="table table-sm table-borderless mb-4">
                                <tr><th width="180">Nomor</th><td>{{ $suminvoice->number }}</td></tr>
                                <tr><th>Tanggal</th><td>{{ $suminvoice->date }}</td></tr>
                                <tr><th>Jatuh Tempo</th><td>{{ $suminvoice->due_date ?? '-' }}</td></tr>
                                <tr>
                                    <th>Tanggal Bayar</th>
                                    <td>{{ $suminvoice->payment_status == 1 ? ($suminvoice->payment_date ?? '-') : '-' }}</td>
                                </tr>
                                <tr><th>Metode/Gateway</th><td>{{ $suminvoice->payment_gateway ?? '-' }}</td></tr>
                                <tr><th>Catatan</th><td>{{ $suminvoice->note ?? '-' }}</td></tr>
                            </table>
                        </div>
                    </div>

                    <h5><i class="fas fa-list"></i> Item Invoice</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th width="30">#</th>
                                    <th>Deskripsi</th>
                                    <th class="text-right">Harga</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Sub Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                @php
                                    $totalwutax = $item->qty * $item->amount;
                                    $totaltax = $totalwutax * $taxfee;
                                    $pph = $totalwutax * ($suminvoice->pph ?? 0) / 100;
                                    $taxitem = $item->amount * $taxfee;
                                    $itotal = $totalwutax + $totaltax;
                                    $subtotal += ($totalwutax + $totaltax) - $pph;
                                    $pphTotal += $pph;

                                    $periodLabel = '-';
                                    if (!empty($item->periode) && strlen($item->periode) >= 6) {
                                        $strmonth = substr($item->periode, -6, 2);
                                        $stryear = substr($item->periode, -4, 4);
                                        if (is_numeric($strmonth) && (int) $strmonth >= 1 && (int) $strmonth <= 12) {
                                            $periodLabel = date('F', mktime(0, 0, 0, (int) $strmonth, 10)) . ' ' . $stryear;
                                        }
                                    }
                                    $description = $item->description;
                                    if ((int) $item->monthly_fee === 1 && $periodLabel !== '-') {
                                        $description .= ' - ' . $periodLabel;
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $description }}</td>
                                    <td class="text-right">Rp {{ number_format($item->amount + $taxitem, 0, ',', '.') }}</td>
                                    <td class="text-center">{{ $item->qty }}</td>
                                    <td class="text-right">Rp {{ number_format($itotal, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Tidak ada item.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                @if ($pphTotal != 0)
                                <tr>
                                    <th colspan="4" class="text-right">PPh 23</th>
                                    <th class="text-right">- Rp {{ number_format($pphTotal, 0, ',', '.') }}</th>
                                </tr>
                                @endif
                                <tr>
                                    <th colspan="4" class="text-right">Total Tagihan</th>
                                    <th class="text-right">Rp {{ number_format($subtotal, 0, ',', '.') }}</th>
                                </tr>
                                @if ($suminvoice->payment_status == 1 && $suminvoice->merchant_fee > 0)
                                <tr>
                                    <th colspan="4" class="text-right">Biaya Admin</th>
                                    <th class="text-right">Rp {{ number_format($suminvoice->merchant_fee, 0, ',', '.') }}</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-right">Total Dibayar</th>
                                    <th class="text-right">Rp {{ number_format($subtotal + $suminvoice->merchant_fee, 0, ',', '.') }}</th>
                                </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
