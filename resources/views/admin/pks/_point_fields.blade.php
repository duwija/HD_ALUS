@php $prefix = $prefix ?? ''; $useOld = $prefix === ''; @endphp
<div class="form-row">
    <div class="form-group col-md-3">
        <label class="small font-weight-bold">Jenis <span class="text-danger">*</span></label>
        <select name="jenis" class="form-control form-control-sm" required>
            @foreach (\App\PksServicePoint::JENIS as $key => $label)
                <option value="{{ $key }}" {{ $useOld && old('jenis') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-5">
        <label class="small font-weight-bold">Nama titik layanan / POP <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control form-control-sm" maxlength="150" required value="{{ $useOld ? old('nama') : '' }}">
    </div>
    <div class="form-group col-md-4">
        <label class="small font-weight-bold">Kapasitas (Mbps) <span class="text-danger">*</span></label>
        <input type="number" name="kapasitas_mbps" class="form-control form-control-sm" min="0" step="1" required value="{{ $useOld ? old('kapasitas_mbps') : '' }}">
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label class="small font-weight-bold">Lokasi / alamat</label>
        <input type="text" name="lokasi" class="form-control form-control-sm" maxlength="255" value="{{ $useOld ? old('lokasi') : '' }}">
    </div>
    <div class="form-group col-md-3">
        <label class="small font-weight-bold">Koordinat</label>
        <input type="text" name="koordinat" class="form-control form-control-sm point-koordinat" maxlength="60"
               placeholder="-8.4095,115.1889" value="{{ $useOld ? old('koordinat') : '' }}">
        <small class="text-muted">lat,lng &mdash; atau klik lokasi di peta</small>
    </div>
    <div class="form-group col-md-5">
        <label class="small font-weight-bold">Keterangan</label>
        <textarea name="keterangan" class="form-control form-control-sm" rows="1" maxlength="2000">{{ $useOld ? old('keterangan') : '' }}</textarea>
    </div>
</div>
