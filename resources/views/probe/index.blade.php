<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Network Monitoring Realtime</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

  <style>
    .sparkline { height: 60px !important; }
    .offline-glow { box-shadow: 0 0 12px 2px rgba(250, 204, 21, 0.7); } /* kuning lembut */
    .down-glow { box-shadow: 0 0 10px 2px rgba(239, 68, 68, 0.7); } /* merah */
    .fullscreen { position: fixed; inset: 0; background: #111827; z-index: 50; overflow: auto; }

    .probe-container {
      max-height: calc(100vh - 180px);
      overflow-y: auto;
      padding-right: 4px;
      scrollbar-width: thin;
      scroll-behavior: smooth;
    }
    .probe-container::-webkit-scrollbar {
      width: 6px;
    }
    .probe-container::-webkit-scrollbar-thumb {
      background-color: rgba(255,255,255,0.2);
      border-radius: 4px;
    }

    @keyframes softPulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.6; }
    }
    .animate-softPulse {
      animation: softPulse 1.5s ease-in-out infinite;
    }
  </style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans">

  <div id="main" class="p-4">
   <div class="flex justify-between items-center mb-3">
    <!-- Judul di kiri -->
    <h1 class="text-2xl font-bold animate__animated animate__fadeInDown bg-gradient-to-r from-green-400 via-blue-500 to-purple-600 bg-clip-text text-transparent">
      🌐 Network Monitoring Dashboard
    </h1>

    <!-- Tombol di kanan -->
    <div class="flex gap-2">
      <button id="btnSetup" class="bg-purple-700 hover:bg-purple-600 text-xs px-2 py-1 rounded">📡 MikroTik Setup</button>
      <button id="btnFull" class="bg-gray-700 hover:bg-gray-600 text-xs px-2 py-1 rounded">⛶ Fullscreen</button>
      <button id="btnSound" class="bg-green-700 hover:bg-green-600 text-xs px-2 py-1 rounded">🔊 Sound ON</button>
      <button id="btnLog" class="bg-blue-700 hover:bg-blue-600 text-xs px-2 py-1 rounded">📜 Logs</button>
    </div>
  </div>

  <!-- Modal MikroTik Setup -->
  <div id="modalSetup" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-gray-800 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-gray-800 border-b border-gray-700 p-4 flex justify-between items-center">
        <h2 class="text-xl font-bold text-purple-400">📡 Setup MikroTik Scheduler untuk Probe Monitoring</h2>
        <button id="closeSetup" class="text-gray-400 hover:text-white text-2xl">&times;</button>
      </div>
      
      <div class="p-6 space-y-6">
        
        <!-- Step 1 -->
        <div class="bg-gray-900 rounded-lg p-4 border border-purple-500">
          <h3 class="text-lg font-semibold text-purple-400 mb-3">📋 Step 1: Salin Script MikroTik Ros 7</h3>
          <p class="text-sm text-gray-300 mb-3">Script ini sudah dikonfigurasi otomatis dengan domain dan probe_key tenant Anda:</p>
          
          <div class="bg-gray-950 rounded p-3 relative">
            <button id="copyScript" class="absolute top-2 right-2 bg-purple-600 hover:bg-purple-500 text-xs px-3 py-1 rounded">
              📋 Copy
            </button>
            <pre class="text-xs text-green-400 overflow-x-auto pr-20" id="mikrotikScript">/system scheduler add interval=1m name=ProbeMonitoring on-event="# === ProbeSend v3.5 ===\r\
    \n:local domain \"https://{{ tenant_config('probe_domain', request()->getHost()) }}\"\r\
    \n:local key \"{{ tenant_config('probe_key', config('app.probe_key', 'default-key')) }}\"\r\
    \n\r\
    \n# 📍 Daftar host & nama manual (format: IP|Nama)\r\
    \n# Format: ip|nama,ip|nama,ip|nama\r\
    \n:local hosts \"8.8.8.8|Google DNS,1.1.1.1|Cloudflare DNS,google.com|Google,facebook.com|Facebook,youtube.com|YouTube,9.9.9.9|Quad9\"\r\
    \n\r\
    \n:local probeId [/system identity get name]\r\
    \n\r\
    \n# Pisahkan tiap pasangan host|nama\r\
    \n:foreach pair in=[:toarray \$hosts] do={\r\
    \n\r\
    \n    :local ip [:pick \$pair 0 [:find \$pair \"|\"]]\r\
    \n    :local hostName [:pick \$pair ([:find \$pair \"|\"] + 1) [:len \$pair]]\r\
    \n\r\
    \n    :local avgRtt 0\r\
    \n    :local status \"down\"\r\
    \n    :local result [/ping address=\$ip count=1 interval=200ms as-value]\r\
    \n    :local time (\$result->\"time\")\r\
    \n    :local pingStatus (\$result->\"status\")\r\
    \n\r\
    \n    # Cek apakah ping berhasil\r\
    \n    :if (([:typeof \$time] != \"nil\") && ([:len \$time] > 0)) do={\r\
    \n\r\
    \n        # Ambil milidetik dari hasil time\r\
    \n        :local dotPos [:find \$time \".\"]\r\
    \n        :if (\$dotPos != nil) do={\r\
    \n            :local msStr [:pick \$time (\$dotPos+1) (\$dotPos+4)]\r\
    \n            :if ([:len \$msStr] = 0) do={ :set msStr \"0\" }\r\
    \n            :set avgRtt \$msStr\r\
    \n        } else={\r\
    \n            :set avgRtt 0\r\
    \n        }\r\
    \n\r\
    \n        :set status \"up\"\r\
    \n    } else={\r\
    \n        :set status \"down\"\r\
    \n        :set avgRtt 0\r\
    \n    }\r\
    \n\r\
    \n    # Kirim hasil ke server (dengan host_name manual)\r\
    \n    :local url (\$domain . \"/api/probe/push\?\" . \\\r\
    \n        \"probe_id=\" . \$probeId . \\\r\
    \n        \"&host=\" . \$ip . \\\r\
    \n        \"&host_name=\" . \$hostName . \\\r\
    \n        \"&status=\" . \$status . \\\r\
    \n        \"&rtt=\" . \$avgRtt . \\\r\
    \n        \"&key=\" . \$key)\r\
    \n\r\
    \n    /tool fetch url=\$url mode=https keep-result=no\r\
    \n\r\
    \n}\r\
    \n" policy=ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon start-time=startup</pre>
          </div>
        </div>

        <!-- Step 2 -->
        <div class="bg-gray-900 rounded-lg p-4 border border-blue-500">
          <h3 class="text-lg font-semibold text-blue-400 mb-3">🔧 Step 2: Masuk ke MikroTik Terminal</h3>
          <ol class="list-decimal list-inside space-y-2 text-sm text-gray-300">
            <li>Buka Winbox atau WebFig MikroTik Anda</li>
            <li>Klik menu <code class="bg-gray-950 px-2 py-1 rounded">New Terminal</code></li>
            <li>Paste script yang sudah di-copy di Step 1</li>
            <li>Tekan Enter untuk menjalankan</li>
          </ol>
        </div>

        <!-- Step 3 -->
        <div class="bg-gray-900 rounded-lg p-4 border border-green-500">
          <h3 class="text-lg font-semibold text-green-400 mb-3">✅ Step 3: Verifikasi Scheduler</h3>
          <p class="text-sm text-gray-300 mb-3">Pastikan scheduler sudah terbuat dengan benar:</p>
          <div class="bg-gray-950 rounded p-3">
            <pre class="text-xs text-yellow-400">/system scheduler print</pre>
          </div>
          <p class="text-xs text-gray-400 mt-2">Output yang benar akan menampilkan scheduler bernama <strong>ProbeMonitoring</strong> dengan interval <strong>1m</strong></p>
        </div>

        <!-- Step 4 -->
        <div class="bg-gray-900 rounded-lg p-4 border border-yellow-500">
          <h3 class="text-lg font-semibold text-yellow-400 mb-3">⚙️ Step 4: Konfigurasi Host yang Dimonitor</h3>
          <p class="text-sm text-gray-300 mb-3">Untuk mengubah host yang dimonitor, edit bagian ini di script:</p>
          <div class="bg-gray-950 rounded p-3">
            <pre class="text-xs text-cyan-400">:local hosts "8.8.8.8|Google DNS,1.1.1.1|Cloudflare DNS,192.168.1.1|Gateway"</pre>
          </div>
          <div class="mt-3 text-sm space-y-1">
            <p class="text-gray-300">Format: <code class="bg-gray-950 px-2 py-1 rounded text-green-400">IP|Nama,IP|Nama,IP|Nama</code></p>
            <p class="text-gray-400 text-xs">• Gunakan koma (,) untuk memisahkan host</p>
            <p class="text-gray-400 text-xs">• Gunakan pipe (|) untuk memisahkan IP dan Nama</p>
            <p class="text-gray-400 text-xs">• Bisa monitor IP, domain, atau hostname</p>
          </div>
        </div>

        <!-- Info Tambahan -->
        <div class="bg-blue-900 bg-opacity-30 rounded-lg p-4 border border-blue-400">
          <h3 class="text-lg font-semibold text-blue-300 mb-3">ℹ️ Informasi Penting</h3>
          <ul class="space-y-2 text-sm text-gray-300">
            <li class="flex items-start gap-2">
              <span class="text-blue-400">•</span>
              <span><strong>Domain:</strong> https://{{ tenant_config('probe_domain', request()->getHost()) }}</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-blue-400">•</span>
              <span><strong>Probe Key:</strong> <code class="bg-gray-950 px-2 py-1 rounded text-green-400">{{ tenant_config('probe_key', config('app.probe_key', 'not-configured')) }}</code></span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-blue-400">•</span>
              <span><strong>Interval:</strong> Script berjalan setiap 1 menit</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-blue-400">•</span>
              <span><strong>Auto Start:</strong> Script otomatis jalan saat MikroTik restart</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-red-400">•</span>
              <span><strong>Keamanan:</strong> Jangan share probe_key Anda ke orang lain!</span>
            </li>
          </ul>
        </div>

        <!-- Troubleshooting -->
        <div class="bg-gray-900 rounded-lg p-4 border border-red-500">
          <h3 class="text-lg font-semibold text-red-400 mb-3">🔍 Troubleshooting</h3>
          <div class="space-y-3 text-sm">
            <div>
              <p class="text-yellow-400 font-semibold">❌ Data tidak muncul di dashboard?</p>
              <ul class="ml-4 mt-1 space-y-1 text-gray-300">
                <li>• Cek koneksi internet MikroTik</li>
                <li>• Pastikan domain dan probe_key benar</li>
                <li>• Cek log scheduler: <code class="bg-gray-950 px-2 py-1 rounded">/log print where topics~"system"</code></li>
              </ul>
            </div>
            <div>
              <p class="text-yellow-400 font-semibold">❌ Error "unauthorized"?</p>
              <ul class="ml-4 mt-1 space-y-1 text-gray-300">
                <li>• Probe key salah, hubungi administrator</li>
                <li>• Konfigurasi probe_key di tenant settings</li>
              </ul>
            </div>
          </div>
        </div>

      </div>

      <div class="sticky bottom-0 bg-gray-800 border-t border-gray-700 p-4 flex justify-end">
        <button id="closeSetupBottom" class="bg-gray-600 hover:bg-gray-500 px-4 py-2 rounded">Close</button>
      </div>
    </div>
  </div>

  <audio id="alertSound" src="/sounds/alert.mp3" preload="auto"></audio>

  <div class="flex justify-end mb-4 text-sm">
    <div class="flex flex-wrap gap-2">
      <button class="filter-btn bg-blue-600 hover:bg-blue-500 px-2 py-1 rounded" data-filter="all">All</button>
      <button class="filter-btn bg-yellow-500 hover:bg-yellow-400 px-2 py-1 rounded text-gray-900 font-semibold" data-filter="offline">Offline</button>
      <button class="filter-btn bg-red-600 hover:bg-red-500 px-2 py-1 rounded" data-filter="down">Down</button>
      <button class="filter-btn bg-green-600 hover:bg-green-500 px-2 py-1 rounded" data-filter="online">Online</button>
    </div>
  </div>

  <div id="stats" class="text-sm mb-3"></div>

  <div class="probe-container">
    <div id="probeTable" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3"></div>
  </div>
</div>

<script>
  const historyData = {};
  let currentFilter = 'all';
  let soundEnabled = true;



let previousStatus = {}; // simpan status terakhir setiap host
const alertSound = document.getElementById('alertSound');
let alertLoop = null; // untuk menyimpan interval suara
let isAnyDown = false; // status apakah ada host down

function startAlertLoop() {
  if (!alertLoop && soundEnabled) {
    alertLoop = setInterval(() => {
      alertSound.currentTime = 0;
      alertSound.play().catch(() => {});
    }, 3000); // bunyi setiap 3 detik
  }
}

function stopAlertLoop() {
  if (alertLoop) {
    clearInterval(alertLoop);
    alertLoop = null;
  }
}

// Tombol toggle suara
document.getElementById('btnSound').addEventListener('click', () => {
  soundEnabled = !soundEnabled;
  const btn = document.getElementById('btnSound');
  btn.textContent = soundEnabled ? '🔊 Sound ON' : '🔈 Sound OFF';
  btn.classList.toggle('bg-green-700', soundEnabled);
  btn.classList.toggle('bg-gray-600', !soundEnabled);

  if (!soundEnabled) stopAlertLoop();
  else if (isAnyDown) startAlertLoop();
});

// Modal Setup MikroTik
const modalSetup = document.getElementById('modalSetup');
const btnSetup = document.getElementById('btnSetup');
const closeSetup = document.getElementById('closeSetup');
const closeSetupBottom = document.getElementById('closeSetupBottom');
const copyScript = document.getElementById('copyScript');

btnSetup.addEventListener('click', () => {
  modalSetup.classList.remove('hidden');
});

closeSetup.addEventListener('click', () => {
  modalSetup.classList.add('hidden');
});

closeSetupBottom.addEventListener('click', () => {
  modalSetup.classList.add('hidden');
});

// Close modal ketika klik di luar
modalSetup.addEventListener('click', (e) => {
  if (e.target === modalSetup) {
    modalSetup.classList.add('hidden');
  }
});

// Copy script ke clipboard
copyScript.addEventListener('click', async () => {
  const scriptText = document.getElementById('mikrotikScript').textContent;
  try {
    await navigator.clipboard.writeText(scriptText);
    copyScript.textContent = '✅ Copied!';
    copyScript.classList.add('bg-green-600');
    copyScript.classList.remove('bg-purple-600');
    
    setTimeout(() => {
      copyScript.textContent = '📋 Copy';
      copyScript.classList.remove('bg-green-600');
      copyScript.classList.add('bg-purple-600');
    }, 2000);
  } catch (err) {
    alert('Gagal copy! Silakan copy manual dengan Ctrl+C');
  }
});


async function loadProbeData() {
  try {
    const res = await fetch('/probe/data');
    const data = await res.json();

        // Sort & filter
    data.sort((a, b) => {
      if (a.probe_status === 'offline' && b.probe_status !== 'offline') return -1;
      if (b.probe_status === 'offline' && a.probe_status !== 'offline') return 1;
      if (!a.is_up && b.is_up) return -1;
      if (!b.is_up && a.is_up) return 1;
      return 0;
    });

    const filtered = data.filter(p => {
      if (currentFilter === 'offline') return p.probe_status === 'offline';
      if (currentFilter === 'down') return p.probe_status !== 'offline' && !p.is_up;
      if (currentFilter === 'online') return p.probe_status !== 'offline' && p.is_up;
      return true;
    });

        // Statistik
    const total = data.length;
    const offline = data.filter(p => p.probe_status === 'offline').length;
    const down = data.filter(p => !p.is_up && p.probe_status !== 'offline').length;
    document.getElementById('stats').innerHTML = `
    <span class="text-green-400">Online: ${total - down - offline}</span> |
    <span class="text-red-400">Down: ${down}</span> |
    <span class="text-yellow-400">Offline: ${offline}</span> |
    <span class="text-gray-400">Total: ${total}</span>
    `;

    const container = document.getElementById('probeTable');
    container.innerHTML = '';

    filtered.forEach(p => {
      const key = `${p.probe_id}_${p.host}`;
      if (!historyData[key]) historyData[key] = [];
      historyData[key] = p.history;
      if (historyData[key].length > 30) historyData[key] = historyData[key].slice(-30);

      const probeStatus = p.probe_status === 'offline'
      ? 'Offline'
      : (p.is_up ? 'Online' : 'Down');

      let bgClass = 'bg-gray-800';
      let statusColor = 'gray';
      let glowClass = '';
      let animClass = '';

      if (p.probe_status === 'offline') {
        bgClass = 'bg-yellow-600';
        statusColor = 'yellow';
        glowClass = 'offline-glow';
      } else if (!p.is_up) {
        bgClass = 'bg-red-700';
        statusColor = 'red';
        glowClass = 'down-glow';
        animClass = 'animate-softPulse';
      } else {
        bgClass = 'bg-gray-800';
        statusColor = 'green';
      }

// 🔊 Deteksi status berubah dari UP ke DOWN
      const currentState = p.is_up ? 'up' : (p.probe_status === 'offline' ? 'offline' : 'down');
      const previousState = previousStatus[key];

      if (soundEnabled && previousState && previousState !== 'down' && currentState === 'down') {
        alertSound.currentTime = 0;
  alertSound.play().catch(() => {}); // aman walau browser blokir autoplay
}

previousStatus[key] = currentState;




const card = document.createElement('div');
card.className = `p-2 rounded-lg shadow border border-gray-700 transition transform hover:scale-105 cursor-pointer ${bgClass} ${glowClass} ${animClass}`;

          // 🆕 Tambahkan host_name di tampilan card
card.innerHTML = `
<div class="flex justify-between items-center text-xs mb-1">
<span class="font-semibold">${p.probe_name}</span>
<span class="px-1 rounded bg-${statusColor}-700 text-white">${probeStatus}</span>
</div>

<div class="text-[11px] font-semibold text-gray-100 truncate">${p.host_name ?? p.host}</div>
<div class="text-[9px] text-gray-400 mb-1 truncate">${p.host}</div>

<div class="w-full bg-gray-700 h-1 rounded mb-1">
<div class="h-1 bg-${statusColor}-400 rounded" style="width:${Math.min(p.rtt_avg_ms || 0, 200)/2}%"></div>
</div>

<div class="text-[10px] text-gray-200">RTT: ${p.rtt_avg_ms ?? 0} ms</div>
<canvas id="chart_${key.replace(/[.:]/g,'_')}" class="sparkline mt-1"></canvas>

<div class="flex justify-between items-center mt-1 text-[9px] text-gray-300">
<span>Last: ${new Date(p.polled_at).toLocaleTimeString()}</span>
<button class="delete-btn hover:bg-red-600 px-2 py-0.5 rounded" 
data-host="${p.host}" data-probe-id="${p.probe_id}" data-probe-name="${p.probe_name}">🗑</button>
</div>
`;
container.appendChild(card);

const ctx = card.querySelector('canvas').getContext('2d');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: p.time_labels,
    datasets: [{
      data: historyData[key],
      borderColor: p.is_up ? '#10B981' : '#EF4444',
      tension: 0.35,
      fill: false,
      pointRadius: 0,
      borderWidth: 2
    }]
  },
  options: { scales: { x: {display: false}, y: {display: false} }, plugins: {legend: {display: false}} }
});

card.addEventListener('click', () => showProbeDetail(p));
});
    // Setelah loop selesai - cek apakah ada host yang down
    isAnyDown = data.some(p => !p.is_up && p.probe_status !== 'offline');

// Jika ada yang down dan sound aktif → mulai loop suara
    if (isAnyDown && soundEnabled) {
      startAlertLoop();
    } else {
      stopAlertLoop();
    }

  } catch (err) {
    console.error('Error loading data:', err);
  }
}

    // Popup detail dengan zona merah
function showProbeDetail(p) {
  const labels = p.history_detail?.map(h => h.time) || [];
  const values = p.history_detail?.map(h => h.rtt) || [];
  const status = p.history_detail?.map(h => h.is_up) || [];

  const downArea = values.map((v, i) => status[i] ? null : (v ?? 0));

  Swal.fire({
    title: `${p.probe_name} (${p.host_name ?? p.host})`,
    html: `
    <div class="text-sm text-gray-200 mb-2">
    <b>Status:</b> ${p.is_up ? 'Online' : (p.probe_status === 'offline' ? 'Offline' : 'Down')}<br>
    <b>Last Update:</b> ${new Date(p.polled_at).toLocaleString()}<br>
    <b>RTT:</b> ${p.rtt_avg_ms ?? 0} ms
    </div>
    <canvas id="popupChart" height="250"></canvas>
    `,
    background: '#1f2937',
    color: '#fff',
    width: 750,
    showConfirmButton: true,
    confirmButtonColor: '#3B82F6',
    didOpen: () => {
      const ctx = document.getElementById('popupChart').getContext('2d');

      const pointColors = status.map(s => s ? '#10B981' : '#EF4444');
      const pointSize = status.map(s => s ? 2 : 6);

      new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
          {
            label: 'RTT (ms)',
            data: values,
            borderColor: '#3B82F6',
            borderWidth: 2,
            tension: 0.3,
            fill: false,
            pointBackgroundColor: pointColors,
            pointRadius: pointSize,
            pointHoverRadius: 6
          },
          {
            label: 'Down Period',
            data: downArea,
            backgroundColor: 'rgba(239,68,68,0.2)',
            borderWidth: 0,
            fill: true,
            tension: 0.3,
            pointRadius: 0
          }
          ]
        },
        options: {
          scales: {
            x: { ticks: { color: '#9CA3AF', maxRotation: 45, minRotation: 45 }},
            y: { ticks: { color: '#9CA3AF' }, beginAtZero: true }
          },
          plugins: {
            legend: { labels: { color: '#E5E7EB' } },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const i = context.dataIndex;
                  const s = status[i] ? 'UP' : 'DOWN';
                  return `RTT: ${context.parsed.y ?? 0} ms (${s})`;
                }
              }
            }
          }
        }
      });
    }
  });
}

    // Event handlers
loadProbeData();
setInterval(loadProbeData, 30000);

document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', e => {
    currentFilter = btn.dataset.filter;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('ring-2', 'ring-white'));
    btn.classList.add('ring-2', 'ring-white');
    loadProbeData();
  });
});

document.getElementById('btnFull').addEventListener('click', () => {
  const el = document.documentElement;
  if (!document.fullscreenElement) el.requestFullscreen();
  else document.exitFullscreen();
});

document.addEventListener('click', async e => {
  if (e.target.classList.contains('delete-btn')) {
    e.stopPropagation();
    const probeId = e.target.dataset.probeId;
    const probeName = e.target.dataset.probeName;
    const host = e.target.dataset.host;

    Swal.fire({
      title: 'Hapus Host?',
      html: `<b>${host}</b> dari <b>${probeName}</b> akan dihapus.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal',
      background: '#1f2937',
      color: '#fff',
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
    }).then(async (result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        formData.append('probe_id', probeId);
        formData.append('host', host);

        const del = await fetch('/probe/delete', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        });

        const res = await del.json();
        if (res.success) {
          Swal.fire({
            title: 'Berhasil!',
            text: res.message,
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            background: '#1f2937',
            color: '#fff'
          });
          loadProbeData();
        } else {
          Swal.fire('Gagal', res.message || 'Tidak bisa menghapus data.', 'error');
        }
      }
    });
  }
});



// Modal Log dengan Pagination & Filter
document.getElementById('btnLog').addEventListener('click', async () => {
  try {
    const res = await fetch('/api/probe/alerts');
    let logs = await res.json();

    if (!Array.isArray(logs) || logs.length === 0) {
      Swal.fire({
        title: '📜 Log Alert',
        html: '<p class="text-gray-300">Belum ada log alert.</p>',
        background: '#1f2937',
        color: '#fff',
        confirmButtonColor: '#3B82F6',
      });
      return;
    }

    // Sort dari terbaru
    logs = logs.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

    let currentPage = 1;
    const perPage = 10;
    let currentFilter = 'all';
    let currentSearch = '';

    const renderLogs = () => {
      let filteredLogs = logs;

  // Filter status
      if (currentFilter === 'down') {
        filteredLogs = filteredLogs.filter(l => l.status === 'down');
      } else if (currentFilter === 'up') {
        filteredLogs = filteredLogs.filter(l => l.status !== 'down');
      }

  // Filter host (jika ada pencarian)
      if (currentSearch.trim() !== '') {
        const keyword = currentSearch.toLowerCase();
        filteredLogs = filteredLogs.filter(l =>
          (l.host_name ?? l.host).toLowerCase().includes(keyword)
          );
      }

      const totalPages = Math.ceil(filteredLogs.length / perPage);
      if (currentPage > totalPages) currentPage = totalPages || 1;

      const start = (currentPage - 1) * perPage;
      const pageData = filteredLogs.slice(start, start + perPage);

      let tableHtml = `
      <div class="flex justify-between mb-2 gap-2">
      <input id="logSearch" type="text" placeholder="Cari host..." 
      class="bg-gray-700 text-white text-xs px-2 py-1 rounded w-full" value="${currentSearch}">
      <select id="logFilter" class="bg-gray-700 text-white text-xs px-2 py-1 rounded w-32">
      <option value="all">All</option>
      <option value="down">Down</option>
      <option value="up">Up</option>
      </select>
      </div>
      <div class="overflow-y-auto" style="max-height:300px;">
      <table class="w-full text-xs text-left">
      <thead class="bg-gray-700 text-gray-200">
      <tr>
      <th class="px-2 py-1">Time</th>
      <th class="px-2 py-1">Probe</th>
      <th class="px-2 py-1">Host</th>
      <th class="px-2 py-1">Status</th>
      <th class="px-2 py-1">Message</th>
      </tr>
      </thead>
      <tbody>
      `;

      pageData.forEach(a => {
        const statusIcon = a.status === 'down' ? '🔴' : '🟢';
        tableHtml += `
        <tr class="border-b border-gray-700">
        <td class="px-2 py-1">${new Date(a.created_at).toLocaleString()}</td>
        <td class="px-2 py-1">${a.probe?.probe_id ?? '-'}</td>
        <td class="px-2 py-1">${a.host_name ?? a.host}</td>
        <td class="px-2 py-1">${statusIcon} ${a.status.toUpperCase()}</td>
        <td class="px-2 py-1">${a.message ?? '-'}</td>
        </tr>
        `;
      });

      tableHtml += `
      </tbody>
      </table>
      </div>
      <div class="flex justify-between items-center mt-2">
      <button id="prevPage" class="px-2 py-1 bg-gray-600 hover:bg-gray-500 rounded text-xs">⬅ Prev</button>
      <span class="text-gray-200 text-xs">Page ${currentPage} / ${totalPages || 1}</span>
      <button id="nextPage" class="px-2 py-1 bg-gray-600 hover:bg-gray-500 rounded text-xs">Next ➡</button>
      </div>
      `;

      Swal.update({ html: tableHtml });

      document.getElementById('logFilter').value = currentFilter;

  // Event filter status
      document.getElementById('logFilter').addEventListener('change', e => {
        currentFilter = e.target.value;
        currentPage = 1;
        renderLogs();
      });

  // Event pencarian host
      document.getElementById('logSearch').addEventListener('input', e => {
        currentSearch = e.target.value;
        currentPage = 1;
        renderLogs();
      });

  // Event pagination
      document.getElementById('prevPage').addEventListener('click', () => {
        if (currentPage > 1) {
          currentPage--;
          renderLogs();
        }
      });

      document.getElementById('nextPage').addEventListener('click', () => {
        if (currentPage < totalPages) {
          currentPage++;
          renderLogs();
        }
      });
    };

    Swal.fire({
      title: '📜 Log Alert',
      html: '<p class="text-gray-300">Loading...</p>',
      background: '#1f2937',
      color: '#fff',
      width: 800,
      showConfirmButton: true,
      confirmButtonColor: '#3B82F6',
      didOpen: renderLogs
    });

  } catch (err) {
    console.error('Gagal load log:', err);
    Swal.fire('Error', 'Tidak dapat memuat log alert.', 'error');
  }
});

</script>
</body>
</html>
