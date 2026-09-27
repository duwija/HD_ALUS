@extends('admin.layouts.app')

@section('content')
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
                            <i class="fas fa-user"></i> {{ $customer->name }}
                            <span class="badge badge-primary">{{ $customer->customer_id }}</span>
                            @if ($status)
                                <span class="badge" style="background-color: {{ $status->color ?? '#6c757d' }}; color: #fff;">{{ $status->name }}</span>
                            @endif
                        </h3>
                    </div>
                    <a href="{{ route('admin.tenants.customers', $tenant->id) }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Informasi Utama -->
                        <div class="col-md-6">
                            <h5><i class="fas fa-id-card"></i> Informasi Utama</h5>
                            <table class="table table-sm table-borderless mb-4">
                                <tr><th width="180">Customer ID</th><td>{{ $customer->customer_id }}</td></tr>
                                <tr><th>Nama</th><td>{{ $customer->name }}</td></tr>
                                <tr><th>No. KTP</th><td>{{ $customer->id_card ?? '-' }}</td></tr>
                                <tr><th>Nama Kontak</th><td>{{ $customer->contact_name ?? '-' }}</td></tr>
                                <tr><th>Tanggal Lahir</th><td>{{ $customer->date_of_birth ?? '-' }}</td></tr>
                                <tr><th>Telepon</th><td>{{ $customer->phone ?? '-' }}</td></tr>
                                <tr><th>Email</th><td>{{ $customer->email ?? '-' }}</td></tr>
                                <tr><th>Alamat</th><td>{{ $customer->address ?? '-' }}</td></tr>
                                <tr><th>NPWP</th><td>{{ $customer->npwp ?? '-' }}</td></tr>
                                <tr><th>Catatan</th><td>{{ $customer->note ?? '-' }}</td></tr>
                            </table>
                        </div>

                        <!-- Paket & Billing -->
                        <div class="col-md-6">
                            <h5><i class="fas fa-box"></i> Paket &amp; Billing</h5>
                            <table class="table table-sm table-borderless mb-4">
                                <tr>
                                    <th width="180">Status</th>
                                    <td>{{ $status->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Paket</th>
                                    <td>
                                        @if ($plan)
                                            {{ $plan->name }} ({{ $plan->speed ?? '-' }}) &mdash; Rp {{ number_format($plan->price, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr><th>Merchant</th><td>{{ $merchant->name ?? '-' }}</td></tr>
                                <tr><th>Billing Start</th><td>{{ $customer->billing_start ?? '-' }}</td></tr>
                                <tr><th>Isolir Date</th><td>{{ $customer->isolir_date ?? '-' }}</td></tr>
                                <tr><th>Tax</th><td>{{ $customer->tax ?? '-' }}</td></tr>
                                <tr>
                                    <th>Notifikasi</th>
                                    <td>
                                        @switch($customer->notification)
                                            @case(1) <i class="fab fa-whatsapp text-success"></i> WhatsApp @break
                                            @case(2) <i class="fas fa-envelope text-primary"></i> Email @break
                                            @default <i class="fas fa-ban text-muted"></i> Tidak ada @break
                                        @endswitch
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Jaringan -->
                        <div class="col-md-6">
                            <h5><i class="fas fa-network-wired"></i> Jaringan</h5>
                            <table class="table table-sm table-borderless mb-4">
                                <tr>
                                    <th width="180">OLT</th>
                                    <td>
                                        @if ($olt)
                                            {{ $olt->name }} ({{ $olt->vendor }}) &mdash; <code>{{ $olt->ip }}</code>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr><th>ONU/ONT ID</th><td>{{ $customer->id_onu ?? '-' }}</td></tr>
                                <tr><th>PPPoE</th><td>{{ $customer->pppoe ?? '-' }}</td></tr>
                                <tr><th>IP</th><td>{{ $customer->ip ?? '-' }}</td></tr>
                                <tr><th>Distribution Point</th><td>{{ $distpoint->name ?? '-' }}</td></tr>
                                <tr><th>Distribution Router</th><td>{{ $distrouter->name ?? '-' }}</td></tr>
                                <tr>
                                    <th>Koordinat</th>
                                    <td>
                                        @if ($customer->coordinate)
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($customer->coordinate) }}" target="_blank" rel="noopener">
                                                {{ $customer->coordinate }} <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Invoice Terakhir -->
                        <div class="col-md-6">
                            <h5><i class="fas fa-file-invoice"></i> Invoice Terakhir</h5>
                            @if ($invoices->isEmpty())
                                <div class="alert alert-info mb-0">Belum ada invoice.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Number</th>
                                                <th>Tanggal</th>
                                                <th>Jatuh Tempo</th>
                                                <th class="text-right">Total</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($invoices as $inv)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('admin.tenants.customers.invoices.show', ['id' => $tenant->id, 'customerId' => $customer->id, 'invoiceId' => $inv->id]) }}">
                                                        {{ $inv->number }}
                                                    </a>
                                                </td>
                                                <td>{{ $inv->date }}</td>
                                                <td>{{ $inv->due_date }}</td>
                                                <td class="text-right">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                                                <td>
                                                    @if ($inv->payment_status == 1)
                                                        <span class="badge badge-success">Lunas</span>
                                                    @elseif ($inv->payment_status == 2)
                                                        <span class="badge badge-secondary">Dibatalkan</span>
                                                    @else
                                                        <span class="badge badge-warning">Belum Lunas</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
