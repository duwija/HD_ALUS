@extends('layout.main')
@section('title', 'Application Logs')

@section('content')
<style>
.log-panel { display: flex; gap: 0; height: calc(100vh - 120px); min-height: 500px; }

/* File list sidebar */
.log-sidebar {
    width: 260px;
    min-width: 220px;
    flex-shrink: 0;
    background: #2d2d2d;
    border-radius: 6px 0 0 6px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}
.log-sidebar-section { padding: 8px 0; }
.log-sidebar-title {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    color: #888;
    padding: 6px 14px 4px;
    text-transform: uppercase;
}
.log-file-item {
    display: flex;
    align-items: baseline;
    padding: 6px 14px;
    cursor: pointer;
    color: #ccc;
    font-size: 12px;
    font-family: 'Courier New', monospace;
    border-left: 3px solid transparent;
    transition: background .15s;
    word-break: break-all;
    gap: 6px;
}
.log-file-item:hover { background: #3a3a3a; color: #fff; }
.log-file-item.active { background: #1e3a5f; color: #9cdcfe; border-left-color: #007bff; }
.log-file-name { flex: 1; }
.log-file-size { color: #666; font-size: 10px; white-space: nowrap; }

/* Viewer panel */
.log-viewer {
    flex: 1;
    background: #1e1e1e;
    border-radius: 0 6px 6px 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.log-viewer-header {
    background: #252526;
    padding: 8px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #333;
    flex-shrink: 0;
}
.log-viewer-title { color: #9cdcfe; font-family: monospace; font-size: 12px; }
.log-viewer-meta  { color: #888; font-size: 11px; }
.log-viewer-body  { flex: 1; overflow-y: auto; padding: 12px 16px; }
pre.log-content {
    color: #d4d4d4;
    font-family: 'Courier New', Courier, monospace;
    font-size: 11px;
    line-height: 1.55;
    margin: 0;
    white-space: pre-wrap;
    word-break: break-all;
}
.le { color: #f48771; }
.lw { color: #dcdcaa; }
.li { color: #9cdcfe; }
.ld { color: #6a9955; }

.log-empty { color: #555; font-family: monospace; font-size: 12px; text-align: center; padding: 60px 20px; }

.log-toolbar { display: flex; align-items: center; gap: 8px; }
.log-toolbar select { background: #333; color: #ccc; border: 1px solid #444; border-radius: 3px; padding: 2px 6px; font-size: 11px; }
.log-toolbar .btn-refresh { background: #333; color: #9cdcfe; border: 1px solid #444; border-radius: 3px; padding: 2px 8px; font-size: 11px; cursor: pointer; }
.log-toolbar .btn-refresh:hover { background: #3a3a3a; }

.badge-invoice { background:#1e3a2a; color:#6dbf8a; }
.badge-notif   { background:#1a2e4a; color:#7fb3e0; }
.badge-payment { background:#3a2e10; color:#d4a84b; }
.badge-olt     { background:#1a3240; color:#6bbdd4; }
.badge-jobs    { background:#2a2a2a; color:#aaa; }
.badge-auth    { background:#3a1a1a; color:#e07a7a; }
.badge-laravel { background:#4a1e33; color:#e0a0c0; }

@media (max-width: 768px) {
    .log-panel { flex-direction: column; height: auto; }
    .log-sidebar { width: 100%; border-radius: 6px 6px 0 0; max-height: 220px; }
    .log-viewer { border-radius: 0 0 6px 6px; min-height: 400px; }
}
</style>

<div class="container-fluid px-2 pt-2">
    <div class="d-flex align-items-center mb-2">
        <h5 class="mb-0 mr-3"><i class="fa fa-scroll mr-1 text-secondary"></i> Application Logs</h5>
        <span class="badge badge-secondary">{{ strtoupper($tenantKey) }}</span>
    </div>

    <div class="log-panel">

        {{-- ===== SIDEBAR ===== --}}
        <div class="log-sidebar">

            {{-- Tenant laravel.log --}}
            <div class="log-sidebar-section">
                <div class="log-sidebar-title">Laravel Log</div>
                @if($tenantLogInfo)
                <div class="log-file-item"
                     data-file="{{ $tenantLogInfo['name'] }}"
                     onclick="loadLog(this)"
                     title="{{ $tenantLogInfo['name'] }}">
                    <span class="badge badge-laravel mr-1" style="font-size:9px;padding:2px 5px">L</span>
                    <span class="log-file-name">laravel.log</span>
                    <span class="log-file-size">{{ $tenantLogInfo['size'] > 1048576 ? number_format($tenantLogInfo['size']/1048576,1).'MB' : number_format($tenantLogInfo['size']/1024,0).'KB' }}</span>
                </div>
                @else
                <div class="log-file-item" style="color:#555;cursor:default">
                    <span class="log-file-name">laravel.log</span>
                    <span class="log-file-size">—</span>
                </div>
                @endif
            </div>

            <hr style="border-color:#444;margin:4px 0">

            {{-- App log files --}}
            @php
                $typeMap = [
                    'invoice' => ['badge'=>'badge-invoice','letter'=>'I'],
                    'notif'   => ['badge'=>'badge-notif',  'letter'=>'N'],
                    'payment' => ['badge'=>'badge-payment','letter'=>'P'],
                    'olt_log' => ['badge'=>'badge-olt',    'letter'=>'O'],
                    'auth'    => ['badge'=>'badge-auth',   'letter'=>'A'],
                    'jobspro' => ['badge'=>'badge-jobs',   'letter'=>'J'],
                ];
                $currentGroup = '';
            @endphp

            @foreach($appFiles as $f)
            @php
                $type = 'other';
                foreach($typeMap as $prefix => $_) {
                    if(str_starts_with($f['name'], $prefix)) { $type = $prefix; break; }
                }
                $badge = $typeMap[$type] ?? ['badge'=>'badge-jobs','letter'=>'?'];
                $groupLabel = match($type) {
                    'invoice' => 'Invoice',
                    'notif'   => 'Notifikasi',
                    'payment' => 'Payment',
                    'olt_log' => 'OLT / Device',
                    'auth'    => 'Auth',
                    'jobspro' => 'Jobs',
                    default   => 'Lainnya',
                };
            @endphp
            @if($groupLabel !== $currentGroup)
                @php $currentGroup = $groupLabel; @endphp
                <div class="log-sidebar-title" style="margin-top:6px">{{ $groupLabel }}</div>
            @endif
            <div class="log-file-item"
                 data-file="{{ $f['name'] }}"
                 onclick="loadLog(this)"
                 title="{{ $f['name'] }} • {{ date('d M Y H:i', $f['modified']) }}">
                <span class="badge {{ $badge['badge'] }} mr-1" style="font-size:9px;padding:2px 5px">{{ $badge['letter'] }}</span>
                <span class="log-file-name">{{ $f['name'] }}</span>
                <span class="log-file-size">{{ $f['size'] > 1048576 ? number_format($f['size']/1048576,1).'MB' : number_format($f['size']/1024,0).'KB' }}</span>
            </div>
            @endforeach

            @if(empty($appFiles) && !$tenantLogInfo)
            <div class="px-3 py-3" style="color:#555;font-size:11px">Tidak ada log file</div>
            @endif
        </div>

        {{-- ===== VIEWER ===== --}}
        <div class="log-viewer">
            <div class="log-viewer-header">
                <span class="log-viewer-title" id="viewerTitle">— Pilih file log di sebelah kiri —</span>
                <div class="log-toolbar">
                    <span class="log-viewer-meta" id="viewerMeta"></span>
                    <select id="linesSelect" onchange="refreshLog()">
                        <option value="100">100 baris</option>
                        <option value="200" selected>200 baris</option>
                        <option value="500">500 baris</option>
                        <option value="1000">1000 baris</option>
                    </select>
                    <button class="btn-refresh" onclick="refreshLog()" title="Refresh"><i class="fa fa-sync-alt"></i></button>
                </div>
            </div>
            <div class="log-viewer-body" id="logViewerBody">
                <div class="log-empty">
                    <i class="fa fa-scroll fa-2x mb-3 d-block" style="opacity:.3"></i>
                    Pilih file log dari daftar di sebelah kiri
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('footer-scripts')
<script>
var currentFile = null;

function loadLog(el) {
    document.querySelectorAll('.log-file-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');
    currentFile = el.dataset.file;
    fetchLog(currentFile);
}

function refreshLog() {
    if (currentFile) fetchLog(currentFile);
}

function fetchLog(file) {
    var lines = document.getElementById('linesSelect').value;
    document.getElementById('logViewerBody').innerHTML =
        '<div class="log-empty"><i class="fa fa-spinner fa-spin fa-2x mb-3 d-block" style="opacity:.5"></i>Memuat log...</div>';

    fetch('/user/log/read?file=' + encodeURIComponent(file) + '&lines=' + lines, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            document.getElementById('logViewerBody').innerHTML =
                '<div class="log-empty" style="color:#f48771">' + data.error + '</div>';
            return;
        }
        document.getElementById('viewerTitle').textContent = file;
        document.getElementById('viewerMeta').textContent  =
            (data.size > 1048576 ? (data.size/1048576).toFixed(1)+' MB' : Math.round(data.size/1024)+' KB')
            + ' \u2022 ' + data.modified;

        var lines = data.content.split('\n');
        var html  = '<pre class="log-content">';
        lines.forEach(function(line) {
            var esc = line.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            var cls = line.match(/\.ERROR|ERROR:/) ? 'le'
                    : line.match(/\.WARNING|WARNING:/) ? 'lw'
                    : line.match(/\.INFO/) ? 'li'
                    : line.match(/\.DEBUG/) ? 'ld' : '';
            html += cls ? '<span class="'+cls+'">'+esc+'</span>\n' : esc+'\n';
        });
        html += '</pre>';
        document.getElementById('logViewerBody').innerHTML = html;
        var body = document.getElementById('logViewerBody');
        body.scrollTop = body.scrollHeight;
    })
    .catch(function(e) {
        document.getElementById('logViewerBody').innerHTML =
            '<div class="log-empty" style="color:#f48771">Gagal memuat: ' + e.message + '</div>';
    });
}

window.addEventListener('DOMContentLoaded', function() {
    var first = document.querySelector('.log-file-item[data-file]');
    if (first) loadLog(first);
});
</script>
