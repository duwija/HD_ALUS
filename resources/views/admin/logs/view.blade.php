@extends('admin.layouts.app')

@section('styles')
<style>
pre.log-content {
    background: #1e1e1e;
    color: #d4d4d4;
    font-family: 'Courier New', monospace;
    font-size: 11px;
    line-height: 1.5;
    padding: 16px;
    border-radius: 4px;
    overflow-x: auto;
    max-height: 75vh;
    overflow-y: auto;
    white-space: pre-wrap;
    word-break: break-all;
}
.log-line-error   { color: #f48771; }
.log-line-warning { color: #dcdcaa; }
.log-line-info    { color: #9cdcfe; }
.log-line-debug   { color: #6a9955; }
</style>
@endsection

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h5 mb-0"><i class="fas fa-scroll mr-2 text-secondary"></i><code>{{ $filename }}</code></h2>
            <small class="text-muted">
                Ukuran: {{ $size > 1048576 ? number_format($size/1048576,2).' MB' : number_format($size/1024,1).' KB' }}
                &bull; Diubah: {{ date('d M Y H:i', $modified) }}
            </small>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('admin.logs.view') }}" class="d-inline-flex align-items-center mr-2">
                <input type="hidden" name="file" value="{{ $filename }}">
                <label class="mb-0 mr-1 text-muted small">Baris terakhir:</label>
                <select name="lines" class="form-control form-control-sm" style="width:80px" onchange="this.form.submit()">
                    @foreach([100, 200, 500, 1000] as $n)
                    <option value="{{ $n }}" {{ $lines == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('admin.logs.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </div>

    {{-- Log Content --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <pre class="log-content" id="logPre">@foreach(explode("\n", $content) as $line)@php
    $cls = '';
    if (str_contains($line, '.ERROR') || str_contains($line, 'ERROR:'))        $cls = 'log-line-error';
    elseif (str_contains($line, '.WARNING') || str_contains($line, 'WARNING:')) $cls = 'log-line-warning';
    elseif (str_contains($line, '.INFO'))                                        $cls = 'log-line-info';
    elseif (str_contains($line, '.DEBUG'))                                       $cls = 'log-line-debug';
@endphp<span class="{{ $cls }}">{{ $line }}</span>
@endforeach</pre>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    // Scroll log to bottom
    const pre = document.getElementById('logPre');
    if (pre) pre.scrollTop = pre.scrollHeight;
</script>
@endsection
