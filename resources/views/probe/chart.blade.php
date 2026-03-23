@extends('layout.main')
@section('title','Grafik Latency')

@section('content')
<div class="container py-3">
  <h4 class="fw-bold mb-3">📈 Grafik Latency per Host</h4>
  <canvas id="latencyChart" height="100"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  async function loadData() {
    const res = await fetch('/api/probe/status');
    const data = await res.json();
    const hosts = data.map(d => d.host);
    const latencies = data.map(d => d.rtt_avg_ms ?? 0);

    const ctx = document.getElementById('latencyChart');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: hosts,
        datasets: [{
          label: 'RTT (ms)',
          data: latencies,
          backgroundColor: latencies.map(v => v > 100 ? 'rgba(255,99,132,0.7)' : 'rgba(54,162,235,0.7)')
        }]
      },
      options: {
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }
  loadData();
</script>
@endsection
