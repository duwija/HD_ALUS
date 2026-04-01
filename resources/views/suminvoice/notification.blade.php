@extends('layout.main')
@section('title','Notification & Job Center')
@section('content')

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><i class="fas fa-bell mr-2"></i>Notification &amp; Job Center</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/home">Home</a></li>
          <li class="breadcrumb-item active">Notification</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    {{-- ====== STAT BOXES ====== --}}
    <div class="row align-items-stretch">

      <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-3 d-flex">
        <div class="info-box shadow-sm w-100 d-flex" style="align-items:stretch;">
          <span class="info-box-icon bg-info elevation-1" style="display:flex;align-items:center;justify-content:center;"><i class="fas fa-tasks"></i></span>
          <div class="info-box-content d-flex flex-column" style="flex:1;">
            <span class="info-box-text d-flex justify-content-between align-items-center">
              Jobs in Queue
              <button id="btn-refresh-queue" type="button" class="btn btn-xs btn-outline-info ml-2" title="Refresh">
                <i class="fas fa-sync-alt" id="refresh-icon"></i>
              </button>
            </span>
            <span class="info-box-number font-weight-bold" id="queuecount">{{ $queue }}</span>
            <div class="progress mt-1" style="height:6px;border-radius:3px;">
              <div class="progress-bar bg-info" id="queuebar" style="width:{{ $queue > 0 ? '0' : '100' }}%; transition:width .5s;"></div>
            </div>
            <span class="progress-description small" id="queuestatus" style="font-size:11px;">{{ $queue > 0 ? 'Memuat...' : 'No pending tasks' }}</span>
            <span class="small text-muted" id="queueeta" style="font-size:11px;"></span>
            <div class="mt-auto pt-2">
              <button id="btn-cancel-jobs" class="btn btn-xs btn-outline-danger" title="Hapus semua job dari antrian">
                <i class="fas fa-trash-alt mr-1"></i>Cancel All Jobs
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-3 d-flex">
        <div class="info-box shadow-sm w-100 d-flex" style="align-items:stretch;">
          <span class="info-box-icon bg-warning elevation-1" style="display:flex;align-items:center;justify-content:center;"><i class="fas fa-file-invoice-dollar"></i></span>
          <div class="info-box-content d-flex flex-column" style="flex:1;">
            <span class="info-box-text">Tagihan Belum Lunas</span>
            <span class="info-box-number font-weight-bold" id="customercountunpaid">{{ $custactiveinv }}</span>
            <div class="progress"><div class="progress-bar bg-warning" style="width:100%"></div></div>
            <span class="progress-description">Siap kirim notifikasi</span>
            <div class="mt-auto pt-2" style="visibility:hidden;">
              <button class="btn btn-xs">&nbsp;</button>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-3 d-flex">
        <div class="info-box shadow-sm w-100 d-flex" style="align-items:stretch;">
          <span class="info-box-icon bg-danger elevation-1" style="display:flex;align-items:center;justify-content:center;"><i class="fas fa-ban"></i></span>
          <div class="info-box-content d-flex flex-column" style="flex:1;">
            <span class="info-box-text">Pelanggan Diblokir</span>
            <span class="info-box-number font-weight-bold" id="customercountblock">{{ $custblocked }}</span>
            <div class="progress"><div class="progress-bar bg-danger" style="width:100%"></div></div>
            <span class="progress-description">Status blocked</span>
            <div class="mt-auto pt-2" style="visibility:hidden;">
              <button class="btn btn-xs">&nbsp;</button>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-3 d-flex">
        <div class="info-box shadow-sm w-100 d-flex" style="align-items:stretch;">
          <span class="info-box-icon bg-success elevation-1" style="display:flex;align-items:center;justify-content:center;"><i class="fas fa-users"></i></span>
          <div class="info-box-content d-flex flex-column" style="flex:1;">
            <span class="info-box-text">Siap Buat Invoice</span>
            <span class="info-box-number font-weight-bold" id="customerinvcount">{{ $customerinv }}</span>
            <div class="progress"><div class="progress-bar bg-success" style="width:100%"></div></div>
            <span class="progress-description" id="month">–</span>
            <div class="mt-auto pt-2" style="visibility:hidden;">
              <button class="btn btn-xs">&nbsp;</button>
            </div>
          </div>
        </div>
      </div>

    </div>

    {{-- ====== ACTION CARDS ====== --}}
    <div class="row align-items-stretch">

      {{-- Card 1: Notifikasi Tagihan --}}
      <div class="col-lg-3 col-md-6 col-12 mb-4 d-flex">
        <div class="card card-warning card-outline w-100 shadow-sm">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-bullhorn mr-2 text-warning"></i>Notifikasi Tagihan</h3>
          </div>
          <div class="card-body d-flex flex-column">
            <p class="text-muted small">Kirim notifikasi WhatsApp ke pelanggan yang memiliki tagihan belum dibayar.</p>
            <form action="/jobs/notifinv" method="POST" class="notifblocked-send1 mt-auto">
              @method('post')
              @csrf
              <div class="form-group">
                <label class="font-weight-bold small">Merchant</label>
                <select name="id_merchant_unpaid" id="id_merchant_unpaid" class="form-control form-control-sm select2" onchange="getSelectedunpaidnotif()">
                  <option value="">Semua Merchant</option>
                  @foreach ($merchant as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="text-right">
                <button type="submit" class="btn btn-warning btn-sm">
                  <i class="fas fa-paper-plane mr-1"></i>Kirim
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {{-- Card 2: Notifikasi Diblokir --}}
      <div class="col-lg-3 col-md-6 col-12 mb-4 d-flex">
        <div class="card card-danger card-outline w-100 shadow-sm">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-bell-slash mr-2 text-danger"></i>Notifikasi Diblokir</h3>
          </div>
          <div class="card-body d-flex flex-column">
            <p class="text-muted small">Kirim notifikasi WhatsApp ke pelanggan dengan status blocked / isolir.</p>
            <form action="/jobs/customerblockednotifjob" method="POST" class="notifblocked-send1 mt-auto">
              @method('get')
              @csrf
              <div class="form-group">
                <label class="font-weight-bold small">Merchant</label>
                <select name="id_merchant_block" id="id_merchant_block" class="form-control form-control-sm select2" onchange="getSelectedblocknotif()">
                  <option value="">Semua Merchant</option>
                  @foreach ($merchant as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="text-right">
                <button type="submit" class="btn btn-danger btn-sm">
                  <i class="fas fa-paper-plane mr-1"></i>Kirim
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {{-- Card 3: Buat Invoice Bulanan --}}
      <div class="col-lg-3 col-md-6 col-12 mb-4 d-flex">
        <div class="card card-success card-outline w-100 shadow-sm">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-invoice mr-2 text-success"></i>Invoice Bulanan</h3>
          </div>
          <div class="card-body d-flex flex-column">
            <p class="text-muted small">
              Generate invoice bulanan untuk pelanggan aktif.
              <a href="/invoice/createinv" class="text-success ml-1"><i class="fas fa-external-link-alt"></i> Halaman Invoice</a>
            </p>
            <form action="/jobs/customerinvjob" method="POST" class="createmonthlyinv-send1 mt-auto">
              @method('post')
              @csrf
              <div class="form-group">
                <label class="font-weight-bold small">Tanggal Invoice</label>
                <input class="form-control form-control-sm" id="inv_date" name="inv_date" type="date"
                  value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" onchange="getSelectedcustomermerchant()">
              </div>
              <div class="form-group">
                <label class="font-weight-bold small">Merchant</label>
                <select name="id_merchant" id="id_merchant" class="form-control form-control-sm select2" onchange="getSelectedcustomermerchant()">
                  <option value="">Semua Merchant</option>
                  @foreach ($merchant as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="text-right">
                <button type="submit" class="btn btn-success btn-sm">
                  <i class="fas fa-plus-circle mr-1"></i>Buat Invoice
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {{-- Card 4: Isolir Pelanggan --}}
      <div class="col-lg-3 col-md-6 col-12 mb-4 d-flex">
        <div class="card card-secondary card-outline w-100 shadow-sm">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-lock mr-2"></i>Isolir Pelanggan</h3>
          </div>
          <div class="card-body d-flex flex-column">
            <p class="text-muted small">Blokir / isolir pelanggan berdasarkan tanggal jatuh tempo pembayaran.</p>
            <div class="text-center mb-2">
              <span class="text-muted small">Jatuh tempo hari ini:</span><br>
              <strong class="text-danger" style="font-size:1.4rem;" id="customercount">–</strong>
              <p class="text-muted small mb-0" id="result"></p>
            </div>
            <form action="/jobs/customerisolirjob" method="POST" class="blocked-customer1 mt-auto">
              @method('post')
              @csrf
              <div class="form-group">
                <label class="font-weight-bold small">Tanggal Isolir</label>
                <select name="isolir_date" id="isolir_date" class="form-control form-control-sm select2" onchange="getSelectedisolirdate()">
                  @php
                    for ($i = 1; $i <= 29; $i++) {
                      $d = sprintf('%02d', $i);
                      $sel = ($d == date('d')) ? 'selected' : '';
                      echo "<option value=\"$d\" $sel>$d</option>";
                    }
                  @endphp
                </select>
              </div>
              <div class="text-right">
                <button type="submit" class="btn btn-secondary btn-sm">
                  <i class="fas fa-lock mr-1"></i>Jalankan Isolir
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>{{-- /row action cards --}}

  </div>
</section>

@endsection

@section('footer-scripts')
<script>
  // ── Cancel all jobs ────────────────────────────────────────────────────────
  document.getElementById('btn-cancel-jobs').addEventListener('click', function () {
    var count = parseInt($('#queuecount').text()) || 0;
    if (count === 0) {
      Swal.fire({ icon: 'info', title: 'Antrian kosong', text: 'Tidak ada job yang perlu dihapus.', timer: 2000, showConfirmButton: false });
      return;
    }
    Swal.fire({
      title: 'Hapus semua job?',
      html: 'Terdapat <strong>' + count + '</strong> job di antrian.<br>Semua job akan dihapus permanen.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, hapus semua!',
      cancelButtonText: 'Batal'
    }).then(function (result) {
      if (result.isConfirmed) {
        $.ajax({
          url: '/jobs/canceljobs',
          type: 'POST',
          data: { _token: $('meta[name="csrf-token"]').attr('content') },
          success: function (response) {
            Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 2500, showConfirmButton: false });
            refreshQueueCount(true);
          },
          error: function () {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan saat menghapus job.' });
          }
        });
      }
    });
  });

  // ── Live queue counter (polls every 5 seconds) ─────────────────────────────
  function refreshQueueCount(spin) {
    if (spin) {
      $('#refresh-icon').addClass('fa-spin');
    }
    $.ajax({
      url: '/jobs/queuecount',
      type: 'GET',
      success: function(r) {
        var remaining  = parseInt(r.count)     || 0;
        var total      = parseInt(r.total)     || 0;
        var processed  = parseInt(r.processed) || 0;
        var percent    = parseInt(r.percent)   || 0;
        var eta        = r.eta || null;

        $('#queuecount').text(remaining);

        if (remaining === 0) {
          // Semua selesai
          $('#queuebar').css('width', '100%').removeClass('bg-info').addClass('bg-success');
          $('#queuestatus').text(total > 0 ? '✅ Semua selesai (' + total + ' job)' : 'No pending tasks');
          $('#queueeta').text('');
          $('#queuecount').removeClass('text-info');
        } else {
          $('#queuebar').css('width', percent + '%').removeClass('bg-success').addClass('bg-info');
          var statusText = total > 0
            ? (processed + ' / ' + total + ' selesai (' + percent + '%)')
            : (remaining + ' tasks running...');
          $('#queuestatus').text(statusText);
          $('#queueeta').text(eta ? '⏱ ETA: ' + eta : '');
          $('#queuecount').addClass('text-info');
        }
      },
      complete: function() {
        $('#refresh-icon').removeClass('fa-spin');
      }
    });
  }

  function getSelectedisolirdate() {
    $.ajax({
      url: '/jobs/isolirdata',
      type: 'GET',
      data: { isolirdate: $('#isolir_date').val() },
      success: function(response) {
        $('#result').html(response.message);
        $('#customercount').html(response.customercount);
      }
    });
  }

  function getSelectedcustomermerchant() {
    $.ajax({
      url: '/jobs/getSelectedcustomermerchant',
      type: 'POST',
      data: { id_merchant: $('#id_merchant').val(), inv_date: $('#inv_date').val() },
      dataType: 'json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        $('#customerinvcount').text(response.customercount);
        $('#month').text(response.month);
      }
    });
  }

  function getSelectedblocknotif() {
    $.ajax({
      url: '/jobs/getSelectedblocknotif',
      type: 'GET',
      data: { id_merchant_block: $('#id_merchant_block').val() },
      success: function(response) {
        $('#customercountblock').html(response.customercount);
      }
    });
  }

  function getSelectedunpaidnotif() {
    $.ajax({
      url: '/jobs/getSelectedunpaidnotif',
      type: 'GET',
      data: { id_merchant_unpaid: $('#id_merchant_unpaid').val() },
      success: function(response) {
        $('#customercountunpaid').html(response.customercount);
      }
    });
  }

  // Gunakan jQuery ready — DOMContentLoaded sudah lewat saat footer-scripts dieksekusi
  $(function () {
    // Manual refresh button
    $('#btn-refresh-queue').on('click', function() {
      refreshQueueCount(true);
    });

    getSelectedisolirdate();
    getSelectedblocknotif();
    getSelectedunpaidnotif();
    getSelectedcustomermerchant();
    refreshQueueCount(true);
    setInterval(function() { refreshQueueCount(false); }, 5000);

    document.querySelectorAll('.notifblocked-send1, .createmonthlyinv-send1, .blocked-customer1').forEach(function (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        Swal.fire({
          title: "Konfirmasi",
          text: "Proses ini akan dijalankan. Lanjutkan?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Ya, lanjutkan!",
          cancelButtonText: "Batal"
        }).then((result) => {
          if (result.isConfirmed) form.submit();
        });
      });
    });
  });
</script>
@endsection
