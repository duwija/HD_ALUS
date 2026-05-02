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

      {{-- Col 1: Link Pembayaran --}}
      <div class="col-md-4 mb-3">
        <div class="card border-left-success shadow-sm h-100">
          <div class="card-header bg-gradient-success text-white">
            <h6 class="font-weight-bold mb-0">
              <i class="fas fa-qrcode"></i> Link pembayaran
            </h6>
          </div>
          <div class="card-body d-flex flex-column align-items-center text-center">
            <div class="mb-2">
              <a href="/invoice/cst/{{$encryptedurl}}" target="_blank" class="btn btn-sm btn-outline-info">
                <i class="fas fa-link"></i> Encrypted URL
              </a>
            </div>
            <img
              id="paymentQrImage"
              src="data:image/png;base64,{{ $qrcodePayment }}"
              alt="QR Link Pembayaran"
              style="width:160px;height:160px;border:1px solid #dee2e6;border-radius:6px;background:#fff;padding:4px"
            >
            <div class="mt-2 small text-muted">Silahkan scan QRcode ini untuk melihat daftar tagihan anda.</div>
            <div class="mt-3 d-flex flex-wrap justify-content-center" style="gap:6px">
              <button
                type="button"
                id="btnDownloadPaymentQr"
                data-customer-id="{{ $customer->customer_id }}"
                data-customer-name="{{ $customer->name }}"
                class="btn btn-sm btn-success"
              >
                <i class="fas fa-download"></i> Download QR
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

      {{-- Col 2: Customer Information --}}
      <div class="col-md-4 mb-3">
        <div class="card border-left-primary shadow-sm h-100">
          <div class="card-header bg-gradient-primary text-white">
            <h6 class="font-weight-bold mb-0">
              <i class="fas fa-user-circle"></i> Customer Information
            </h6>
          </div>
          <div class="card-body">
            <table class="table table-sm table-borderless mb-0">
              <tbody>
                <tr>
                  <td class="text-muted" style="width:35%">CID / Name</td>
                  <td>
                    <a href="/customer/{{ $customer->id}}" class="font-weight-bold text-decoration-none">
                      <i class="fas fa-external-link-alt"></i> {{$customer->customer_id}} - {{$customer->name}}
                    </a>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted">Phone</td>
                  <td>
                    <a href="https://wa.me/{{$customer->phone}}" target="_blank" class="badge badge-success px-3 py-2">
                      <i class="fab fa-whatsapp"></i> {{$customer->phone}}
                    </a>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted">Address</td>
                  <td>
                    <a href="https://www.google.com/maps/place/{{ $customer->coordinate }}" target="_blank" class="text-info">
                      <i class="fas fa-map-marked-alt"></i> {{$customer->address}}
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- Col 3: Account Details --}}
      <div class="col-md-4 mb-3">
        <div class="card border-left-info shadow-sm h-100">
          <div class="card-header bg-gradient-info text-white">
            <h6 class="font-weight-bold mb-0">
              <i class="fas fa-info-circle"></i> Account Details
            </h6>
          </div>
          <div class="card-body">
            <table class="table table-sm table-borderless mb-0">
              <tbody>
                <tr>
                  <td class="text-muted" style="width:35%">Status</td>
                  <td>
                    <span class="badge badge-primary px-3 py-2">
                      {{$customer->status_name}}
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted">Plan</td>
                  <td class="font-weight-bold">{{$customer->plan_name}}</td>
                </tr>
                <tr>
                  <td class="text-muted">NPWP</td>
                  <td class="font-weight-bold">{{strtoupper($customer->npwp)}}</td>
                </tr>
              </tbody>
            </table>
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
  var qrImg = document.getElementById('paymentQrImage');

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

  if (btn && qrImg) {
    btn.addEventListener('click', function () {
      var customerId = btn.getAttribute('data-customer-id') || '-';
      var customerName = btn.getAttribute('data-customer-name') || '-';

      var canvas = document.createElement('canvas');
      var ctx = canvas.getContext('2d');

      canvas.width = 700;
      canvas.height = 860;

      // Background
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      // Header
      ctx.fillStyle = '#111111';
      ctx.font = 'bold 42px Arial';
      ctx.fillText('Link pembayaran', 56, 82);

      // QR
      var qrSize = 520;
      var qrX = Math.floor((canvas.width - qrSize) / 2);
      var qrY = 120;
      ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize);

      // Detail
      ctx.fillStyle = '#111111';
      ctx.font = 'bold 32px Arial';
      ctx.fillText('Customer ID: ' + customerId, 56, 700);
      ctx.fillText('Nama: ' + customerName, 56, 752);

      ctx.fillStyle = '#6c757d';
      ctx.font = '24px Arial';
      ctx.fillText('Silahkan scan QRcode ini untuk melihat daftar tagihan anda.', 56, 808);

      var safeName = (customerId || 'customer').replace(/[^a-zA-Z0-9_-]/g, '_');
      var downloadLink = document.createElement('a');
      downloadLink.href = canvas.toDataURL('image/png');
      downloadLink.download = 'link-pembayaran-' + safeName + '.png';
      downloadLink.click();
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