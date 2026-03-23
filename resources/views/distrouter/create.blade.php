@extends('layout.main')
@section('title','Tambah Router')
@section('content')
<style>
  .dr-card { background:var(--bg-surface); border:1px solid var(--border); border-radius:14px; box-shadow:var(--shadow-sm); overflow:hidden; }
  .dr-card-header {
    background:var(--bg-surface-2); border-bottom:1px solid var(--border);
    padding:14px 20px; display:flex; align-items:center; justify-content:space-between; gap:12px;
  }
  .dr-card-header h6 { font-size:14px; font-weight:700; color:var(--text-primary); margin:0; }
  .dr-card-body { padding:24px 20px; }
  .dr-card-footer {
    background:var(--bg-surface-2); border-top:1px solid var(--border);
    padding:12px 20px; display:flex; align-items:center; gap:8px;
  }
  .dr-form-group { margin-bottom:18px; }
  .dr-form-group label { font-size:12px; font-weight:700; color:var(--text-secondary); margin-bottom:5px; display:block; text-transform:uppercase; letter-spacing:.4px; }
  .dr-form-group .form-control {
    background:var(--input-bg) !important; border-color:var(--input-border) !important;
    color:var(--text-primary) !important; border-radius:8px; font-size:13px;
  }
  .dr-form-group .form-control:focus { border-color:var(--brand) !important; box-shadow:0 0 0 3px rgba(163,48,28,.12) !important; }
  .dr-desc { font-size:11px; color:var(--text-muted); margin-top:4px; }
  .dr-divider { height:1px; background:var(--border); margin:20px 0; }
</style>

<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-xl-6 col-lg-7 col-12">

      <div class="dr-card">
        <div class="dr-card-header">
          <h6><i class="fas fa-plus mr-2" style="color:var(--brand)"></i>Tambah Distribution Router</h6>
          <a href="{{ url('distrouter') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:12px">
            <i class="fas fa-arrow-left mr-1"></i>Kembali
          </a>
        </div>

        <form action="{{ url('distrouter') }}" method="POST">
          @csrf
          <div class="dr-card-body">

            <div class="dr-form-group">
              <label>Nama Router <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control"
                     placeholder="Contoh: Router-A01"
                     value="{{ old('name') }}" required>
              <div class="dr-desc">Nama identifikasi router</div>
            </div>

            <div class="dr-divider"></div>

            <div class="row">
              <div class="col-md-6">
                <div class="dr-form-group">
                  <label>IP Address <span class="text-danger">*</span></label>
                  <input type="text" name="ip" class="form-control"
                         placeholder="192.168.1.1"
                         value="{{ old('ip') }}" required>
                  <div class="dr-desc">Alamat IP router</div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="dr-form-group">
                  <label>API Port <span class="text-danger">*</span></label>
                  <input type="number" name="port" class="form-control"
                         placeholder="8728"
                         value="{{ old('port', '8728') }}" required>
                  <div class="dr-desc">Mikrotik API port</div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="dr-form-group">
                  <label>Web Port</label>
                  <input type="number" name="web" class="form-control"
                         placeholder="80"
                         value="{{ old('web', '80') }}">
                  <div class="dr-desc">HTTP port</div>
                </div>
              </div>
            </div>

            <div class="dr-divider"></div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:14px">Kredensial Login</div>

            <div class="row">
              <div class="col-md-6">
                <div class="dr-form-group">
                  <label>Username <span class="text-danger">*</span></label>
                  <input type="text" name="user" class="form-control"
                         placeholder="admin"
                         value="{{ old('user') }}" required autocomplete="username">
                </div>
              </div>
              <div class="col-md-6">
                <div class="dr-form-group">
                  <label>Password</label>
                  <input type="password" name="password" class="form-control"
                         placeholder="••••••••"
                         autocomplete="new-password">
                </div>
              </div>
            </div>

            <div class="dr-form-group">
              <label>Catatan</label>
              <textarea name="note" class="form-control" rows="3"
                        placeholder="Keterangan tambahan...">{{ old('note') }}</textarea>
            </div>

          </div>
          <div class="dr-card-footer">
            <button type="submit" class="btn btn-sm btn-primary" style="border-radius:8px;font-weight:600">
              <i class="fas fa-save mr-1"></i>Simpan Router
            </button>
            <a href="{{ url('distrouter') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
              Batal
            </a>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>
@endsection
