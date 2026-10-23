@extends('layout.main')
@section('title', 'Telnet OLT')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.css">
<style>
  #olt-terminal-wrap { background: #000; padding: 6px; border-radius: 4px; }
  #olt-terminal { height: calc(100vh - 300px); min-height: 420px; }
  #olt-telnet-card.is-fullscreen { position: fixed; inset: 0; z-index: 2000; margin: 0; border-radius: 0; }
  #olt-telnet-card.is-fullscreen #olt-terminal { height: calc(100vh - 110px); }
</style>

<section class="content-header">
  <div class="card card-primary card-outline" id="olt-telnet-card">
    <div class="card-header d-flex flex-wrap align-items-center">
      <h3 class="card-title font-weight-bold mr-3 mb-1">
        <i class="fas fa-terminal"></i> Telnet: {{ $olt->name }}
        <small class="text-muted">({{ $olt->ip }}:{{ $olt->port ?: 23 }})</small>
      </h3>
      <span id="telnet-status" class="badge badge-secondary mr-auto mb-1">Belum terhubung</span>

      <div class="mb-1">
        <button type="button" id="btn-connect" class="btn btn-success btn-sm"><i class="fas fa-plug"></i> Connect</button>
        <button type="button" id="btn-disconnect" class="btn btn-danger btn-sm" disabled><i class="fas fa-times"></i> Disconnect</button>
        <button type="button" id="btn-clear" class="btn btn-secondary btn-sm"><i class="fas fa-eraser"></i> Clear</button>
        <button type="button" id="btn-fullscreen" class="btn btn-secondary btn-sm"><i class="fas fa-expand"></i></button>
        <a href="/olt/{{ $olt->id }}" class="btn btn-primary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
      </div>
    </div>

    <div class="card-body p-2">
      <div id="olt-terminal-wrap"><div id="olt-terminal"></div></div>
      <small class="text-muted d-block mt-2">
        Login otomatis memakai user/password OLT yang tersimpan. Semua sesi dan perintah yang diketik dicatat di audit log.
        Sesi otomatis ditutup setelah 15 menit tanpa aktivitas (maksimum 2 jam).
      </small>
    </div>
  </div>
</section>
@endsection

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.js"></script>
<script>
(function () {
  var tokenUrl = '/olt/{{ $olt->id }}/telnet/token';
  var csrf = '{{ csrf_token() }}';

  var term = new Terminal({
    cursorBlink: true,
    fontSize: 14,
    fontFamily: 'Menlo, Consolas, "DejaVu Sans Mono", monospace',
    scrollback: 5000,
    convertEol: false,
    theme: { background: '#000000' }
  });
  var fit = new FitAddon.FitAddon();
  term.loadAddon(fit);
  term.open(document.getElementById('olt-terminal'));
  fit.fit();

  var ws = null;
  var $status = document.getElementById('telnet-status');
  var btnConnect = document.getElementById('btn-connect');
  var btnDisconnect = document.getElementById('btn-disconnect');

  function setStatus(state, text) {
    var cls = { connecting: 'badge-warning', connected: 'badge-success', closed: 'badge-secondary', error: 'badge-danger' }[state] || 'badge-secondary';
    $status.className = 'badge ' + cls + ' mr-auto mb-1';
    $status.textContent = text;
    var open = state === 'connecting' || state === 'connected';
    btnConnect.disabled = open;
    btnDisconnect.disabled = !open;
  }

  function sendResize() {
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify({ t: 'resize', cols: term.cols, rows: term.rows }));
    }
  }

  function connect() {
    setStatus('connecting', 'Meminta akses...');
    fetch(tokenUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    }).then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    }).then(function (data) {
      ws = new WebSocket(data.ws_url);
      ws.binaryType = 'arraybuffer';

      ws.onopen = function () { sendResize(); term.focus(); };
      ws.onmessage = function (ev) {
        if (typeof ev.data === 'string') {
          var msg = JSON.parse(ev.data);
          if (msg.t === 'status') {
            setStatus(msg.state, msg.message);
            if (msg.state !== 'connected') term.write('\r\n\x1b[33m*** ' + msg.message + ' ***\x1b[0m\r\n');
          }
          return;
        }
        term.write(new Uint8Array(ev.data));
      };
      ws.onclose = function () {
        if (!btnConnect.disabled) return;
        setStatus('closed', 'Terputus');
      };
      ws.onerror = function () { setStatus('error', 'Gagal membuka WebSocket'); };
    }).catch(function (err) {
      setStatus('error', 'Gagal meminta akses: ' + err.message);
    });
  }

  term.onData(function (d) {
    if (ws && ws.readyState === WebSocket.OPEN) ws.send(JSON.stringify({ t: 'in', d: d }));
  });
  term.onResize(sendResize);

  btnConnect.addEventListener('click', connect);
  btnDisconnect.addEventListener('click', function () { if (ws) ws.close(); setStatus('closed', 'Terputus'); });
  document.getElementById('btn-clear').addEventListener('click', function () { term.clear(); term.focus(); });
  document.getElementById('btn-fullscreen').addEventListener('click', function () {
    document.getElementById('olt-telnet-card').classList.toggle('is-fullscreen');
    setTimeout(function () { fit.fit(); term.focus(); }, 50);
  });
  window.addEventListener('resize', function () { fit.fit(); });
  window.addEventListener('beforeunload', function () { if (ws) ws.close(); });

  connect();
})();
</script>
@endsection
