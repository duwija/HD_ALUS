@extends('layout.main')

@section('title', 'WhatsApp Gateway')
@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Status WhatsApp Gateway</h3>
    </div>
    <div class="card-body text-center">

      {{-- Flash Message --}}
      @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
      @endif
      @if(!empty($error))
      <div class="alert alert-danger">{{ $error }}</div>
      @endif

      {{-- Status Logic --}}
      @switch($status)
      @case('authenticated')
      <img src="{{ asset('img/connected.png') }}" width="200" alt="Connected">
      <h4 class="mt-3 text-success">CONNECTED</h4>

      {{-- Tombol --}}
      <div class="mt-3">
        <form action="/whatsapp/logout" method="POST" class="d-inline-block">
          @csrf
          <button class="btn btn-danger">Disconnect</button>
        </form>
        <form action="/whatsapp/restart" method="POST" class="d-inline-block ms-2">
          @csrf
          <button class="btn btn-warning">Restart</button>
        </form>
      </div>


      @break

      @case('not_authenticated')
      <h5 class="text-secondary">Scan QR Code untuk koneksi.</h5>
      <div id="qr-container">
        @if($qrUrl)
        <img id="qr-img" src="{{ $qrUrl }}" alt="QR Code" width="250">
        @else
        <div class="alert alert-warning">QR belum tersedia. Coba refresh beberapa detik lagi.</div>
        @endif
      </div>
      @break

      @case('initializing')
      <div class="alert alert-info">Sedang menyiapkan koneksi WhatsApp...</div>
      @break

      @default
      <div class="alert alert-danger">Status tidak diketahui.</div>
      @endswitch

      {{-- Device Info --}}
      @if($status === 'authenticated' && !empty($device))
      <hr>
      <div class="row">
        <div class="col-md-6">
          <h5 class="mb-3">Device Info</h5>
          <table class="table table-bordered mx-auto" style="max-width: 600px;">
            <tr><th>ID</th>             <td>{{ $device['id'] ?? '-' }}</td></tr>
            <tr><th>Phone Number</th>   <td>{{ $device['phoneNumber'] ?? '-' }}</td></tr>
            <tr><th>Name</th>           <td>{{ $device['name'] ?? '-' }}</td></tr>
            <tr><th>Platform</th>       <td>{{ $device['platform'] ?? '-' }}</td></tr>
            <tr><th>Battery Level</th>  <td>{{ $device['batteryLevel'] ?? 'N/A' }}%</td></tr>
          </table>
        </div>
        <div class="col-md-6">
         {{-- Grup WhatsApp --}}
         <h5 class="mb-3">Grup WhatsApp</h5>
         <div id="group-loader" class="text-muted">🔄 Memuat daftar grup...</div>
         <ul id="group-list" class="list-group mt-2 mx-auto" style="max-width: 600px; display: none;"></ul>
       </div>



     </div>
     @endif

   </div>
 </div>
</section>
@endsection

@section('footer-scripts')
@if($status === 'not_authenticated')
<script>
  let lastQrSrc = document.querySelector('#qr-img')?.src ?? null;

  setInterval(() => {
    fetch("/wa/qrcode", {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');

      const newQrImg = doc.querySelector('#qr-img');
      const qrContainer = document.querySelector('#qr-container');

      if (newQrImg && qrContainer) {
        const newSrc = newQrImg.src;
        if (newSrc !== lastQrSrc) {
          lastQrSrc = newSrc;
          newQrImg.style.opacity = 0;
          qrContainer.innerHTML = '';
          qrContainer.appendChild(newQrImg);
          setTimeout(() => {
            newQrImg.style.transition = 'opacity 0.3s ease-in-out';
            newQrImg.style.opacity = 1;
          }, 100);
        }
      }

      const isAuthenticated = doc.querySelector('.text-success');
      if (isAuthenticated) {
        location.reload();
      }
    })
    .catch(error => {
      console.error('QR refresh failed:', error);
    });
  }, 10000);
</script>
@endif

@if($status === 'initializing')
<script>
  const pollInterval = setInterval(() => {
    fetch('/whatsapp/qrcode', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const statusSection = doc.querySelector('.card-body');

      if (statusSection) {
        document.querySelector('.card-body').innerHTML = statusSection.innerHTML;
      }

      const connected = doc.querySelector('.text-success');
      const qrImg = doc.querySelector('#qr-img');
      if (connected || qrImg) {
        clearInterval(pollInterval);
      }
    })
    .catch(err => {
      console.error('Polling error:', err);
    });
  }, 5000);
</script>
@endif

@if($status === 'authenticated')
<script>
  document.addEventListener("DOMContentLoaded", function () {
    fetch("/wa/groups")
    .then(res => res.json())
    .then(groups => {
      const loader = document.getElementById('group-loader');
      const list = document.getElementById('group-list');

      loader.style.display = 'none';
      list.style.display = 'block';

      if (!Array.isArray(groups) || groups.length === 0) {
        list.innerHTML = '<li class="list-group-item text-muted">Tidak ada grup ditemukan</li>';
        return;
      }

      groups.forEach(group => {
        const col = document.createElement('div');
        col.className = 'col-md-12';

        const card = document.createElement('div');
        card.className = 'border rounded p-2 h-100';

        card.innerHTML = `
        <div class="d-flex justify-content-between">
        <strong>${group.name}</strong>
        <small class="text-muted">${group.id}</small>
        </div>
        `;

        col.appendChild(card);
        document.getElementById('group-list').appendChild(col);
      });


    })
    .catch(err => {
      console.error('Gagal memuat grup:', err);
      document.getElementById('group-loader').innerHTML = '❌ Gagal memuat grup.';
    });
  });
</script>
@endif
@endsection
