@extends('layout.main')
@section('title', 'MikroTik Sync Failures')
@section('content')

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-8">
        <h1>
          <i class="fas fa-exclamation-triangle text-danger mr-2"></i>
          MikroTik Sync Failures
          @if($pendingCount > 0)
            <span class="badge badge-danger ml-2">{{ $pendingCount }} Pending</span>
          @endif
        </h1>
      </div>
      <div class="col-sm-4">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/home">Home</a></li>
          <li class="breadcrumb-item active">MikroTik Sync</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    {{-- Flash messages ditangani oleh layout/flash-message --}}

    {{-- Stat boxes --}}
    <div class="row mb-3">
      <div class="col-md-3">
        <div class="info-box shadow-sm">
          <span class="info-box-icon bg-danger"><i class="fas fa-times-circle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Pending</span>
            <span class="info-box-number">{{ \App\MikrotikSyncFailure::whereIn('status',['pending','retrying'])->count() }}</span>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="info-box shadow-sm">
          <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Resolved</span>
            <span class="info-box-number">{{ \App\MikrotikSyncFailure::where('status','resolved')->count() }}</span>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="info-box shadow-sm">
          <span class="info-box-icon bg-info"><i class="fas fa-list"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Total</span>
            <span class="info-box-number">{{ \App\MikrotikSyncFailure::count() }}</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Filter tabs --}}
    <div class="card shadow-sm">
      <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
          <li class="nav-item">
            <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}" href="?status=pending">
              <i class="fas fa-clock mr-1"></i>Pending
              @php $pc = \App\MikrotikSyncFailure::pending()->count() @endphp
              @if($pc > 0)<span class="badge badge-danger ml-1">{{ $pc }}</span>@endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ $status === 'resolved' ? 'active' : '' }}" href="?status=resolved">
              <i class="fas fa-check mr-1"></i>Resolved
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ $status === 'all' ? 'active' : '' }}" href="?status=all">
              <i class="fas fa-list mr-1"></i>Semua
            </a>
          </li>
        </ul>
      </div>

      <div class="card-body p-0">

        {{-- Bulk actions (hanya tampil saat pending) --}}
        @if($status === 'pending' && $failures->total() > 0)
        <div class="px-3 py-2 border-bottom bg-light d-flex align-items-center justify-content-between">
          <span class="text-muted small">{{ $failures->total() }} record belum ditangani</span>
          <form id="form-resolve-all" action="/mikrotik-sync/resolve-all" method="POST" class="m-0">
            @csrf
            <button type="button" id="btn-resolve-all" class="btn btn-sm btn-outline-success">
              <i class="fas fa-check-double mr-1"></i>Resolve Semua
            </button>
          </form>
        </div>
        @endif

        <div class="table-responsive">
          <table class="table table-hover table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Customer</th>
                <th>PPPoE</th>
                <th>Router IP</th>
                <th>Action</th>
                <th>Error</th>
                <th>Attempts</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($failures as $f)
              <tr>
                <td class="text-muted small">{{ $f->id }}</td>

                {{-- Customer --}}
                <td>
                  <strong>{{ $f->customer_name ?? '-' }}</strong>
                  @if($f->customer_cid)
                    <br><span class="text-muted small">{{ $f->customer_cid }}</span>
                  @endif
                  @if($f->customer_id)
                    <br><a href="/customer/{{ $f->customer_id }}" target="_blank" class="small text-info">
                      <i class="fas fa-external-link-alt"></i> Detail
                    </a>
                  @endif
                </td>

                {{-- PPPoE --}}
                <td><code>{{ $f->pppoe ?? '-' }}</code></td>

                {{-- Router IP --}}
                <td>
                  <span class="badge badge-light border">{{ $f->distrouter_ip ?? '-' }}</span>
                </td>

                {{-- Action --}}
                <td>
                  @php
                    $actionBadge = match($f->action) {
                      'disable' => 'badge-danger',
                      'enable'  => 'badge-success',
                      'remove'  => 'badge-dark',
                      default   => 'badge-secondary',
                    };
                  @endphp
                  <span class="badge {{ $actionBadge }}">{{ strtoupper($f->action) }}</span>
                </td>

                {{-- Error --}}
                <td style="max-width:280px;">
                  <span class="text-danger small" style="word-break:break-word;"
                    title="{{ $f->error_message }}"
                    data-toggle="tooltip" data-placement="top">
                    {{ \Illuminate\Support\Str::limit($f->error_message, 80) }}
                  </span>
                  @if($f->notes)
                    <br><span class="text-muted small"><i class="fas fa-sticky-note mr-1"></i>{{ $f->notes }}</span>
                  @endif
                </td>

                {{-- Attempts --}}
                <td class="text-center">
                  <span class="badge badge-warning">{{ $f->attempts }}x</span>
                </td>

                {{-- Status --}}
                <td>{!! $f->statusBadge() !!}</td>

                {{-- Tanggal --}}
                <td class="small text-muted">
                  {{ $f->created_at->format('d/m/Y H:i') }}
                  @if($f->resolved_at)
                    <br><span class="text-success">✔ {{ $f->resolved_at->format('d/m/Y H:i') }}</span>
                    <br><span class="text-muted">by {{ $f->resolved_by }}</span>
                  @endif
                </td>

                {{-- Aksi --}}
                <td>
                  @if($f->status !== 'resolved')
                    {{-- Retry --}}
                    <form class="form-retry d-inline"
                          action="/mikrotik-sync/{{ $f->id }}/retry"
                          method="POST"
                          data-name="{{ addslashes($f->customer_name) }}"
                          data-action="{{ strtoupper($f->action) }}"
                          data-pppoe="{{ $f->pppoe }}"
                          data-cust-status="{{ optional($f->customer)->id_status ?? '' }}">
                      @csrf
                      <button type="button" class="btn btn-xs btn-warning mb-1 btn-retry-trigger" title="Retry langsung sesuai status customer saat ini">
                        <i class="fas fa-redo mr-1"></i>Retry
                      </button>
                    </form>

                    {{-- Resolve manual --}}
                    <button type="button" class="btn btn-xs btn-success mb-1 btn-resolve-trigger"
                            data-id="{{ $f->id }}"
                            data-name="{{ addslashes($f->customer_name) }}"
                            data-action="{{ strtoupper($f->action) }}"
                            title="Tandai sudah diselesaikan manual di MikroTik">
                      <i class="fas fa-check mr-1"></i>Resolve
                    </button>
                  @else
                    <span class="text-muted small">—</span>
                  @endif
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="10" class="text-center text-muted py-4">
                  <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
                  Tidak ada data
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- Pagination --}}
        @if($failures->hasPages())
        <div class="card-footer">
          {{ $failures->links() }}
        </div>
        @endif

      </div>
    </div>

  </div>
</section>

{{-- Hidden form for resolve single --}}
<form id="form-resolve-single" method="POST" style="display:none">
  @csrf
  <input type="hidden" name="notes" id="resolve-notes-input">
</form>

@endsection

@section('footer-scripts')
<script>
  $(function () {
    $('[data-toggle="tooltip"]').tooltip();

    // ── RETRY ────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-retry-trigger', function () {
      var form       = $(this).closest('.form-retry');
      var name       = form.data('name');
      var pppoe      = form.data('pppoe');
      var custStatus = parseInt(form.data('cust-status'));

      // Tentukan action sesuai status customer sekarang (sama dengan logika di controller)
      var effectiveAction = (custStatus === 2) ? 'ENABLE' : 'DISABLE';
      var actionColor     = (custStatus === 2) ? '#28a745' : '#dc3545';
      var statusLabel     = (custStatus === 2) ? 'Active' : (custStatus === 4 ? 'Blocked' : (custStatus === 3 ? 'Inactive' : 'Inprogress/Other'));

      Swal.fire({
        title: 'Retry Job?',
        html: 'Action yang akan dijalankan: <strong style="color:' + actionColor + '">' + effectiveAction + '</strong> secret<br>' +
              'Customer: <strong>' + name + '</strong>' +
              (pppoe ? '<br>PPPoE: <code>' + pppoe + '</code>' : '') +
              '<br><small class="text-muted">Status saat ini: ' + statusLabel + '</small>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f0ad4e',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-redo mr-1"></i>Ya, Jalankan',
        cancelButtonText: 'Batal',
        reverseButtons: true,
      }).then(function (result) {
        if (result.isConfirmed) {
          form[0].submit();
        }
      });
    });

    // ── RESOLVE SINGLE ──────────────────────────────────────────────────
    $(document).on('click', '.btn-resolve-trigger', function () {
      var id     = $(this).data('id');
      var name   = $(this).data('name');
      var action = $(this).data('action');

      Swal.fire({
        title: 'Resolve Manual?',
        html: 'Konfirmasi bahwa aksi <strong>' + action + '</strong> untuk ' +
              '<strong>' + name + '</strong> sudah diselesaikan manual di MikroTik.<br><br>' +
              '<textarea id="swal-notes" class="swal2-textarea" placeholder="Catatan (opsional)&#10;Contoh: Disabled manual via Winbox"></textarea>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check mr-1"></i>Tandai Resolved',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        didOpen: function () {
          document.getElementById('swal-notes').focus();
        },
        preConfirm: function () {
          return document.getElementById('swal-notes').value;
        }
      }).then(function (result) {
        if (result.isConfirmed) {
          var form = document.getElementById('form-resolve-single');
          form.action = '/mikrotik-sync/' + id + '/resolve';
          document.getElementById('resolve-notes-input').value = result.value || 'Diselesaikan manual oleh admin';
          form.submit();
        }
      });
    });

    // ── RESOLVE ALL ─────────────────────────────────────────────────────
    $('#btn-resolve-all').on('click', function () {
      Swal.fire({
        title: 'Resolve Semua?',
        html: 'Semua record <strong>pending</strong> akan ditandai sebagai resolved.<br>' +
              'Pastikan semua PPPoE sudah ditangani secara manual.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check-double mr-1"></i>Ya, Resolve Semua',
        cancelButtonText: 'Batal',
        reverseButtons: true,
      }).then(function (result) {
        if (result.isConfirmed) {
          document.getElementById('form-resolve-all').submit();
        }
      });
    });
  });
</script>
@endsection
