@extends('layout.main')
@section('title','Invoice List')
@section('content')
@inject('invoicecalc', 'App\Invoice')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-3">
      <div class="col-sm-6">
        <h1 class="m-0">
          <i class="fas fa-file-invoice text-primary"></i> Invoice List
        </h1>
      </div>
      <div class="col-sm-6">
        <a href="/invoice/{{$customer->id}}/create" class="btn btn-primary btn-sm shadow-sm float-right">
          <i class="fas fa-plus-circle"></i> Create New Invoice
        </a>
      </div>
    </div>

    <!-- 3-column Cards Row -->
    <div class="row">

      {{-- Col 1: Link Pembayaran (desain sama dengan kartu PNG dari PaymentQrCardService) --}}
      <div class="col-md-4 mb-3">
        <div class="pay-qr-card shadow-sm h-100">
          <div class="pay-qr-head">
            <div class="pay-qr-kicker">LINK PEMBAYARAN</div>
            <div class="pay-qr-tenant">{{ tenant('app_name') ?: config('app.name') }}</div>
            <div class="pay-qr-sub">Scan untuk melihat &amp; membayar tagihan</div>
          </div>
          <div class="pay-qr-body">
            <div class="pay-qr-frame">
              <span class="c tl"></span><span class="c tr"></span><span class="c bl"></span><span class="c br"></span>
              <div class="pay-qr-code">{!! $qrcodePayment !!}</div>
            </div>
            <div class="pay-qr-divider"></div>
            <div class="pay-qr-name" title="{{ $customer->name }}">{{ $customer->name }}</div>
            <div class="pay-qr-id">ID Pelanggan: {{ $customer->customer_id }}</div>

            <div class="pay-qr-actions">
              <a href="/invoice/cst/{{$encryptedurl}}" target="_blank" class="btn btn-sm btn-outline-info" title="Buka halaman tagihan pelanggan">
                <i class="fas fa-external-link-alt"></i> Buka
              </a>
              <button
                type="button"
                id="btnDownloadPaymentQr"
                data-card-url="/invoice/{{ $customer->id }}/payment-qr-card"
                class="btn btn-sm btn-success"
              >
                <i class="fas fa-download"></i> Download QR
              </button>
              <button
                type="button"
                id="btnPrintPaymentQr"
                data-card-url="/invoice/{{ $customer->id }}/payment-qr-card?inline=1"
                class="btn btn-sm btn-secondary"
              >
                <i class="fas fa-print"></i> Print
              </button>
              <button
                type="button"
                id="btnSendPaymentWa"
                data-internal-customer-id="{{ $customer->id }}"
                data-customer-id="{{ $customer->customer_id }}"
                data-customer-name="{{ $customer->name }}"
                data-customer-phone="{{ $customer->phone }}"
                data-payment-url="{{ $paymentUrl }}"
                class="btn btn-sm btn-primary"
              >
                <i class="fab fa-whatsapp"></i> Kirim via WA
              </button>
            </div>
          </div>
        </div>
      </div>

<style>
  .pay-qr-card { border-radius: 14px; overflow: hidden; background: #fff; display: flex; flex-direction: column; }
  .pay-qr-head {
    position: relative; overflow: hidden; text-align: center; color: #fff; padding: 16px 14px 46px;
    background: linear-gradient(160deg, #0d6efd 0%, #20c997 100%);
  }
  .pay-qr-head::after {
    content: ""; position: absolute; right: -40px; top: -50px; width: 150px; height: 150px;
    border-radius: 50%; background: rgba(255,255,255,.12);
  }
  .pay-qr-kicker { font-size: 11px; font-weight: 700; letter-spacing: 3px; opacity: .85; }
  .pay-qr-tenant { font-size: 24px; font-weight: 800; line-height: 1.2; letter-spacing: .5px; word-break: break-word; }
  .pay-qr-sub { font-size: 12px; opacity: .9; }
  .pay-qr-body {
    position: relative; margin: -32px 14px 14px; padding: 18px 14px 14px; background: #fff;
    border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,.08); text-align: center; flex: 1;
  }
  .pay-qr-frame { position: relative; display: inline-block; padding: 12px; }
  .pay-qr-frame .c { position: absolute; width: 22px; height: 22px; border: 0 solid #0d6efd; }
  .pay-qr-frame .tl { top: 0; left: 0; border-top-width: 4px; border-left-width: 4px; border-top-left-radius: 4px; }
  .pay-qr-frame .tr { top: 0; right: 0; border-top-width: 4px; border-right-width: 4px; border-top-right-radius: 4px; }
  .pay-qr-frame .bl { bottom: 0; left: 0; border-bottom-width: 4px; border-left-width: 4px; border-bottom-left-radius: 4px; }
  .pay-qr-frame .br { bottom: 0; right: 0; border-bottom-width: 4px; border-right-width: 4px; border-bottom-right-radius: 4px; }
  .pay-qr-code svg { display: block; width: 180px; height: 180px; }
  .pay-qr-divider { border-top: 2px dashed #e5e7eb; margin: 12px 6px; }
  .pay-qr-name { font-size: 17px; font-weight: 700; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .pay-qr-id {
    display: inline-block; margin-top: 6px; padding: 4px 14px; border-radius: 999px;
    background: #e8f0fe; color: #0d6efd; font-weight: 700; font-size: 13px;
  }
  .pay-qr-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px; margin-top: 14px; }
  /* Mode gelap: kartu luar ikut gelap, kotak QR tetap putih agar mudah di-scan */
  .dark-mode .pay-qr-card { background: #1f2937; }
  .dark-mode .pay-qr-body { background: #fff; }
  .dark-mode .pay-qr-name { color: #111827; }
</style>

      {{-- Col 2: Informasi Pelanggan (gabungan Customer Information + Account Details) --}}
      @php
        $statusColor = [
          'Active' => 'success', 'Block' => 'danger', 'Inactive' => 'secondary',
          'Potensial' => 'warning', 'Company_Properti' => 'info',
        ][$customer->status_name] ?? 'primary';
      @endphp
      <div class="col-md-4 mb-3">
        <div class="card border-left-primary shadow-sm h-100">
          <div class="card-header bg-gradient-primary text-white d-flex align-items-center">
            <h6 class="font-weight-bold mb-0 mr-auto">
              <i class="fas fa-user-circle"></i> Informasi Pelanggan
            </h6>
            <span class="badge badge-{{ $statusColor }} px-2 py-1">{{ $customer->status_name }}</span>
          </div>
          <div class="card-body">
            <a href="/customer/{{ $customer->id }}" class="d-block font-weight-bold text-decoration-none mb-1" style="font-size:16px">
              <i class="fas fa-external-link-alt"></i> {{ $customer->name }}
            </a>
            <div class="text-muted small mb-3">{{ $customer->customer_id }}</div>

            <table class="table table-sm table-borderless mb-0">
              <tbody>
                <tr>
                  <td class="text-muted" style="width:38%"><i class="fas fa-phone fa-fw"></i> Phone</td>
                  <td>
                    @if($customer->phone)
                    <a href="https://wa.me/{{ $customer->phone }}" target="_blank" class="badge badge-success px-2 py-1">
                      <i class="fab fa-whatsapp"></i> {{ $customer->phone }}
                    </a>
                    @else - @endif
                  </td>
                </tr>
                <tr>
                  <td class="text-muted"><i class="fas fa-map-marker-alt fa-fw"></i> Address</td>
                  <td>
                    <a href="https://www.google.com/maps/place/{{ $customer->coordinate }}" target="_blank" class="text-info">
                      {{ $customer->address ?: '-' }}
                    </a>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted"><i class="fas fa-box fa-fw"></i> Plan</td>
                  <td class="font-weight-bold">
                    {{ $customer->plan_name }}
                    <span class="text-muted font-weight-normal small">Rp {{ number_format((float) $customer->plan_price, 0, ',', '.') }}</span>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted"><i class="far fa-calendar-alt fa-fw"></i> Billing Start</td>
                  <td>{{ $customer->billing_start ? \Carbon\Carbon::parse($customer->billing_start)->format('d M Y') : '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted"><i class="fas fa-user-lock fa-fw"></i> Tgl Isolir</td>
                  <td>{{ $customer->isolir_date ? 'Setiap tanggal ' . $customer->isolir_date : '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted"><i class="fas fa-file-alt fa-fw"></i> NPWP</td>
                  <td class="font-weight-bold">{{ $customer->npwp ? strtoupper($customer->npwp) : '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- Col 3: Ringkasan Invoice (chart) --}}
      @php
        $invStats = [
          ['label' => 'Lunas',       'status' => 1, 'color' => '#28a745'],
          ['label' => 'Belum Bayar', 'status' => 0, 'color' => '#ffc107'],
          ['label' => 'Batal',       'status' => 2, 'color' => '#dc3545'],
        ];
        foreach ($invStats as &$st) {
          $rows = $suminvoice->where('payment_status', $st['status']);
          $st['count'] = $rows->count();
          $st['total'] = (float) $rows->sum('total_amount');
        }
        unset($st);
        $invTotalCount = array_sum(array_column($invStats, 'count'));
        $unpaidTotal = $invStats[1]['total'];
      @endphp
      <div class="col-md-4 mb-3">
        <div class="card border-left-info shadow-sm h-100">
          <div class="card-header bg-gradient-info text-white">
            <h6 class="font-weight-bold mb-0">
              <i class="fas fa-chart-pie"></i> Ringkasan Invoice
            </h6>
          </div>
          <div class="card-body d-flex flex-column">
            @if($invTotalCount === 0)
              <div class="text-center text-muted my-auto">
                <i class="far fa-folder-open fa-2x mb-2"></i><br>Belum ada invoice.
              </div>
            @else
              <div class="d-flex align-items-center">
                <div style="position:relative;width:150px;height:150px;flex-shrink:0">
                  <canvas id="invoiceStatusChart" width="150" height="150"></canvas>
                  <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none">
                    <div style="font-size:26px;font-weight:800;line-height:1">{{ $invTotalCount }}</div>
                    <div class="text-muted small">invoice</div>
                  </div>
                </div>
                <div class="ml-3 flex-grow-1">
                  @foreach($invStats as $st)
                    <div class="d-flex align-items-start mb-2">
                      <span style="width:12px;height:12px;border-radius:3px;background:{{ $st['color'] }};margin-top:4px;flex-shrink:0"></span>
                      <div class="ml-2" style="line-height:1.25">
                        <div class="font-weight-bold">{{ $st['label'] }} <span class="text-muted font-weight-normal">({{ $st['count'] }})</span></div>
                        <div class="small text-muted">Rp {{ number_format($st['total'], 0, ',', '.') }}</div>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>

              <div class="mt-auto pt-3">
                <div class="rounded px-3 py-2" style="{{ $unpaidTotal > 0 ? 'background:#fff3cd;color:#856404' : 'background:#d4edda;color:#155724' }}">
                  @if($unpaidTotal > 0)
                    <i class="fas fa-exclamation-circle"></i> Tagihan belum dibayar:
                    <strong>Rp {{ number_format($unpaidTotal, 0, ',', '.') }}</strong>
                  @else
                    <i class="fas fa-check-circle"></i> Tidak ada tagihan tertunggak
                  @endif
                </div>
              </div>

              <script>
                document.addEventListener('DOMContentLoaded', function () {
                  var el = document.getElementById('invoiceStatusChart');
                  if (!el || typeof Chart === 'undefined') return;
                  new Chart(el.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                      labels: @json(array_column($invStats, 'label')),
                      datasets: [{
                        data: @json(array_column($invStats, 'count')),
                        backgroundColor: @json(array_column($invStats, 'color')),
                        borderWidth: 2
                      }]
                    },
                    options: {
                      responsive: false,
                      cutoutPercentage: 68,
                      legend: { display: false },
                      tooltips: {
                        callbacks: {
                          label: function (item, data) {
                            return ' ' + data.labels[item.index] + ': ' + data.datasets[0].data[item.index] + ' invoice';
                          }
                        }
                      }
                    }
                  });
                });
              </script>
            @endif
          </div>
        </div>
      </div>

    </div>
    <!-- Invoice List Table -->
    <div class="row">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header bg-gradient-success text-white">
            <h6 class="font-weight-bold mb-0">
              <i class="fas fa-list-alt"></i> Invoice Summary
            </h6>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="example1" class="table table-hover table-striped">
                <thead class="thead-light">
                  <tr>
                    <th scope="col" class="text-center">#</th>
                    <th scope="col"><i class="fas fa-hashtag"></i> INV Number</th>
                    <th scope="col"><i class="far fa-calendar-alt"></i> INV Date</th>
                    <th scope="col"><i class="fas fa-percentage"></i> Tax</th>
                    <th scope="col"><i class="fas fa-calendar-check"></i> Due Date</th>
                    <th scope="col"><i class="fas fa-money-bill-wave"></i> Total</th>
                    <th scope="col"><i class="fas fa-credit-card"></i> Payment Status</th>
                    <th scope="col"><i class="fas fa-clock"></i> Updated</th>
                    <th scope="col"><i class="fas fa-user"></i> Received By</th>
                    <th scope="col" class="text-center"><i class="fas fa-cog"></i> Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach( $suminvoice as $suminvoice)
                  <tr>
                    <th scope="row" class="text-center">{{ $loop->iteration }}</th>
                    <td><strong>{{ $suminvoice->number }}</strong></td>
                    <td>
                      <i class="far fa-calendar"></i> {{ $suminvoice->date }}
                    </td>
                    <td>{{ $suminvoice->tax }}%</td>
                    <td>
                      <i class="far fa-calendar-check"></i> {{ $suminvoice->due_date }}
                    </td>
                    <td><strong>Rp {{number_format($suminvoice->total_amount, 2, ',', '.')}}</strong></td>
                    <td>
                      @if($suminvoice->payment_status == 0)
                      <span class="badge badge-danger px-3 py-2">
                        <i class="fas fa-exclamation-circle"></i> Unpaid
                      </span>
                      @elseif($suminvoice->payment_status == 1)
                      <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-check-circle"></i> Paid
                      </span>
                      <br><small class="text-muted"><i class="far fa-calendar-check"></i> {{ $suminvoice->payment_date }}</small>
                      @elseif($suminvoice->payment_status == 2)
                      <span class="badge badge-secondary px-3 py-2">
                        <i class="fas fa-ban"></i> Cancel
                      </span>
                      @endif
                    </td>
                    <td>
                      <small class="text-muted">
                        <i class="far fa-clock"></i> {{ $suminvoice->updated_at }}
                      </small>
                    </td>
                    <td>
                      @if(is_numeric($suminvoice->updated_by))
                      <i class="fas fa-user"></i> {{ $suminvoice->user->name }}
                      @else
                      <i class="fas fa-user"></i> {{ $suminvoice->updated_by }}
                      @endif
                    </td>
                    <td class="text-center">
                      <a href="/suminvoice/{{ $suminvoice->tempcode }}" class="btn btn-info btn-sm shadow-sm" title="View Details">
                        <i class="fa fa-eye"></i> Show
                      </a>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('btnDownloadPaymentQr');
  var btnWa = document.getElementById('btnSendPaymentWa');

  function showSwal(icon, title, text) {
    if (typeof Swal !== 'undefined' && Swal.fire) {
      return Swal.fire({
        icon: icon,
        title: title,
        text: text,
        confirmButtonText: 'OK'
      });
    }
    alert(text || title);
    return Promise.resolve();
  }

  if (btn) {
    // Kartu QR dibuat di server (PaymentQrCardService) — sama persis dengan gambar yang dikirim via WA.
    btn.addEventListener('click', function () {
      var original = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan...';
      window.location.href = btn.getAttribute('data-card-url');
      setTimeout(function () {
        btn.disabled = false;
        btn.innerHTML = original;
      }, 3000);
    });
  }

  var btnPrint = document.getElementById('btnPrintPaymentQr');
  if (btnPrint) {
    // Buka kartu QR di jendela baru lalu tampilkan dialog print (kartu disesuaikan ke 1 halaman).
    btnPrint.addEventListener('click', function () {
      var win = window.open('', '_blank');
      if (!win) {
        showSwal('warning', 'Pop-up diblokir', 'Izinkan pop-up untuk halaman ini agar bisa mencetak QR.');
        return;
      }
      var src = window.location.origin + btnPrint.getAttribute('data-card-url');
      win.document.write(
        '<!doctype html><html><head><title>Print QR Link Pembayaran</title>' +
        '<style>@page{size:A4 portrait;margin:12mm}html,body{margin:0;height:100%}' +
        'body{display:flex;align-items:center;justify-content:center;font-family:sans-serif;color:#666}' +
        'img{max-width:100%;max-height:100%;width:130mm;height:auto}</style></head>' +
        '<body><p id="msg">Menyiapkan QR...</p><img id="card" alt="" style="display:none"></body></html>'
      );
      win.document.close();
      var img = win.document.getElementById('card');
      img.onload = function () {
        win.document.getElementById('msg').remove();
        img.style.display = 'block';
        win.focus();
        win.print();
      };
      img.onerror = function () { win.document.getElementById('msg').textContent = 'Gagal memuat QR.'; };
      img.src = src;
    });
  }

  if (btnWa) {
    btnWa.addEventListener('click', function () {
      var internalCustomerId = btnWa.getAttribute('data-internal-customer-id');
      if (!internalCustomerId) {
        showSwal('error', 'Data Tidak Lengkap', 'Customer ID internal tidak ditemukan.');
        return;
      }

      var proceedPromise;
      if (typeof Swal !== 'undefined' && Swal.fire) {
        proceedPromise = Swal.fire({
          icon: 'question',
          title: 'Kirim via WA gateway?',
          text: 'Gambar QR pembayaran akan dikirim ke nomor WhatsApp customer.',
          showCancelButton: true,
          confirmButtonText: 'Ya, kirim',
          cancelButtonText: 'Batal'
        }).then(function (result) {
          return result.isConfirmed;
        });
      } else {
        proceedPromise = Promise.resolve(confirm('Kirim gambar QR pembayaran via WA gateway?'));
      }

      proceedPromise.then(function (ok) {
        if (!ok) {
          return;
        }

        btnWa.disabled = true;
        btnWa.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

        fetch('/invoice/' + internalCustomerId + '/send-wa-gateway', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          },
          body: JSON.stringify({})
        })
        .then(function (res) {
          return res.json();
        })
        .then(function (json) {
          if (json && json.success) {
            showSwal('success', 'Berhasil', 'Berhasil kirim via WA gateway.');
          } else {
            showSwal('error', 'Gagal', (json && json.message) ? json.message : 'Gagal kirim WA gateway.');
          }
        })
        .catch(function (err) {
          showSwal('error', 'Error', 'Gagal kirim WA gateway: ' + (err && err.message ? err.message : 'Unknown error'));
        })
        .finally(function () {
          btnWa.disabled = false;
          btnWa.innerHTML = '<i class="fab fa-whatsapp"></i> Kirim via WA';
        });
      });
    });
  }
});
</script>

@endsection