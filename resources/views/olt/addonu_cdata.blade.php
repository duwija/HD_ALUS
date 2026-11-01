@extends('layout.main')
@section('title','Register ONT CDATA')

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold">Register ONT ke {{ $olt->name }} (CDATA GPON)</h3>
    </div>

    <form role="form" method="post" action="/olt/onuregister-cdata" id="cdataForm">
      @csrf
      <input type="hidden" name="olt" value="{{ $olt->id }}">
      <input type="hidden" name="id_customer" value="{{ $customer->id }}">

      <div class="card-body">
        @if ($snmpError)
          <div class="alert alert-warning">{{ $snmpError }} ONU ID kosong tidak bisa dihitung — pastikan ID yang dipilih belum terpakai.</div>
        @endif
        @if (isset($errors) && $errors->any())
          <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        @if ($autofindError)
          <div class="alert alert-warning">Daftar ONU autofind tidak bisa dibaca dari OLT: {{ $autofindError }}. SN tetap bisa diketik manual.</div>
        @endif

        <div class="row">
          <div class="form-group col-md-6">
            <label for="autofind">ONU belum terdaftar (autofind)</label>
            <select id="autofind" class="form-control">
              <option value="">{{ count($autofind) ? '-- Pilih ONU (' . count($autofind) . ' ditemukan) --' : '-- Tidak ada ONU autofind, ketik SN manual --' }}</option>
              @foreach ($autofind as $af)
                <option value="{{ $af['sn'] }}" data-pon="{{ $af['port'] }}">PON {{ $af['port'] }} - {{ $af['sn'] }} ({{ $af['equipment'] }}) - {{ $af['time'] }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row">
          <div class="form-group col-md-3">
            <label>Customer</label>
            <input type="text" class="form-control" value="{{ $customer->customer_id }} - {{ $customer->name }}" readonly>
          </div>
          <div class="form-group col-md-3">
            <label for="sn">ONU SN</label>
            <input type="text" class="form-control text-uppercase" name="sn" id="sn" value="{{ old('sn') }}" placeholder="contoh ZTEGCFEB4AEE" maxlength="12" required autocomplete="off">
            <small id="sn_hint" class="text-danger"></small>
          </div>
          <div class="form-group col-md-2">
            <label for="pon">PON</label>
            <select name="pon" id="pon" class="form-control" required>
              @for ($p = 1; $p <= $ponCount; $p++)
                <option value="{{ $p }}" {{ (int) old('pon', 1) === $p ? 'selected' : '' }}>PON {{ $p }} ({{ 128 - count($freeIds[$p]) }} terpakai)</option>
              @endfor
            </select>
          </div>
          <div class="form-group col-md-2">
            <label for="onu_id">ONU ID</label>
            <select name="onu_id" id="onu_id" class="form-control" required></select>
          </div>
        </div>

        <div class="row">
          <div class="form-group col-md-4">
            <label for="description">Deskripsi ONT</label>
            <input type="text" class="form-control" name="description" id="description" value="{{ old('description', $defaultDesc) }}" maxlength="64" required>
            <small class="text-muted">Tanpa spasi, contoh: TegalLinggahPondok_IWayanAdiDarmita</small>
          </div>
          <div class="form-group col-md-2">
            <label for="vlan">VLAN</label>
            <input type="number" class="form-control" name="vlan" id="vlan" value="{{ old('vlan') }}" min="1" max="4094" list="vlan_list" required>
            <datalist id="vlan_list">
              @foreach ($onuprofile as $profile)
                <option value="{{ $profile->vlan }}">{{ $profile->name }}</option>
              @endforeach
            </datalist>
          </div>
          <div class="form-group col-md-3">
            <label for="pppoe">PPPoE Username</label>
            <input type="text" class="form-control" name="pppoe" id="pppoe" value="{{ old('pppoe', $customer->pppoe) }}" required>
          </div>
          <div class="form-group col-md-3">
            <label for="password">PPPoE Password</label>
            <input type="text" class="form-control" name="password" id="password" value="{{ old('password', $customer->password) }}" required>
          </div>
        </div>
      </div>

      <div class="card-footer">
        <button type="submit" id="submitBtn" class="btn btn-primary">Register</button>
        <a href="{{ url('customer') }}/{{ $customer->id }}/edit" class="btn btn-default float-right">Cancel</a>
      </div>
    </form>
  </div>
</section>
@endsection

@section('footer-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var freeIds = @json($freeIds);
    var usedSn = @json($usedSn);
    var oldOnu = @json(old('onu_id'));
    var pon = document.getElementById('pon');
    var onu = document.getElementById('onu_id');
    var sn = document.getElementById('sn');
    var snHint = document.getElementById('sn_hint');
    var btn = document.getElementById('submitBtn');

    function fillOnu() {
      var ids = freeIds[pon.value] || [];
      onu.innerHTML = '';
      ids.forEach(function (id) {
        var o = document.createElement('option');
        o.value = id;
        o.textContent = id;
        if (oldOnu && String(oldOnu) === String(id)) o.selected = true;
        onu.appendChild(o);
      });
      oldOnu = null;
    }

    function checkSn() {
      sn.value = sn.value.toUpperCase().replace(/\s/g, '');
      var hit = usedSn[sn.value];
      snHint.textContent = hit ? 'SN ini sudah terdaftar di ONT ' + hit : '';
      btn.disabled = !!hit;
    }

    document.getElementById('autofind').addEventListener('change', function () {
      var opt = this.options[this.selectedIndex];
      if (!opt.value) return;
      sn.value = opt.value;
      pon.value = opt.getAttribute('data-pon');
      fillOnu();
      checkSn();
    });
    pon.addEventListener('change', fillOnu);
    sn.addEventListener('input', checkSn);
    document.getElementById('cdataForm').addEventListener('submit', function () {
      btn.disabled = true;
      btn.textContent = 'Memproses... (bisa ~1 menit)';
    });
    fillOnu();
    checkSn();
  });
</script>
@endsection
