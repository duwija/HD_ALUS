@extends('layout.main')
@section('title','Network Alerts')

@section('content')
<div class="container py-3">
  <h4 class="fw-bold mb-3">🚨 Daftar Alert Status</h4>

  <!-- ✅ Tambahkan table-responsive di sini -->
  <div class="table-responsive">
    <table class="table table-bordered table-hover table-sm" id="alertTable">
      <thead class="table-light">
        <tr>
          <th>Time</th>
          <th>Probe</th>
          <th>Host</th>
          <th>Name</th>
          <th>Status</th>
          <th>Pesan</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td colspan="6" class="text-center">Loading...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  async function loadAlerts(){
    const res = await fetch('/api/probe/alerts');
    const data = await res.json();
    const tbody = document.querySelector('#alertTable tbody');
    
    if (!Array.isArray(data) || data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center">Belum ada alert</td></tr>`;
      return;
    }

    tbody.innerHTML = data.map(a => {
      const color = a.status === 'down' ? 'text-danger fw-bold' : 'text-success fw-bold';
      const t = new Date(a.created_at).toLocaleString();
      return `
      <tr>
      <td>${t}</td>
      <td>${a.probe?.probe_id ?? '-'}</td>
      <td>${a.host}</td>
      <td>${a.host_name}</td>
      <td class="${color}">${a.status.toUpperCase()}</td>
      <td>${a.message}</td>
      </tr>`;
    }).join('');
  }

  loadAlerts();
  setInterval(loadAlerts, 15000); // refresh tiap 15 detik
</script>
@endsection
