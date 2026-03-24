@extends('layout.main')
@section('title','Add New Customer')
  @section('content')
  <section class="content-header">

    <div class="card card-primary ">
      <div class="card-header bg-primary">
        <h3 class="card-title font-weight-bold"> Add New Customer </h3>
      </div>
      <form role="form" method="post" action="/customer">
        @csrf
        <div class="card-body row">
          <div class="form-group col-md-4">
            <label for="nama">Customer Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Customer Name" value="{{ request('name') ?? old('name') }}">
            @error('name')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
            @if(request('source') == 'pppoe_import')
            <small class="text-info"><i class="fas fa-info-circle"></i> Imported from PPPoE: {{ request('profile_name') ?? 'N/A' }}</small>
            @endif
          </div>

          <div class="form-group col-md-2">
            <label for="site location">  Status </label>
            <div class="input-group mb-3">
              <select name="id_status" id="id_status" class="form-control">
                {{--   <option value="1">none</option> --}}
                @foreach ($status as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
              </select>
            </div>
          </div>


          <div class="form-group col-md-2">
            <label for="customer_id"> Customer Id (CID) </label>
            @php
            
            $rescode         = config("app.rescode");
            $pppoepassword   = tenant_config('pppoe_password');
            // cid_use_rescode: 1 = pakai rescode (default), 0 = tanpa rescode
            $cidUseRescode   = tenant_config('cid_use_rescode', '1');
            $useRescode      = ($cidUseRescode !== '0');
            $year =date('Y', time())-2000;
            $md =date('md', time());
            $ran =substr(str_shuffle("0123456789"), 0, 3);
            $cidDefault      = $useRescode ? ($rescode.$year.$md.$ran) : ($year.$md.$ran);

            @endphp
            <div class="input-group mb-3">

              <input type="text"  class="form-control @error('customer_id') is-invalid @enderror" name="customer_id"  id="customer_id" placeholder="Customer ID" value="{{ $cidDefault }}">
              @error('customer_id')
              <div class="error invalid-feedback">{{ $message }}</div>
              @enderror
              <div class="input-group-append">
               <button type="button" class="btn btn-primary"  onclick="toggle_custid()" title="Lock/Unlock"><i class="fa fa-unlock" aria-hidden="true"></i></button>
             </div>
           </div>
           <div class="custom-control custom-switch mt-1">
             <input type="checkbox" class="custom-control-input" id="toggleRescode" onchange="toggleRescodePrefix()" {{ $useRescode ? '' : 'checked' }}>
             <label class="custom-control-label text-muted small" for="toggleRescode">Tanpa Rescode</label>
           </div>
         </div>
         <div class="form-group col-md-2">
          <label for="pppoe">PPPOE User</label>
          <input type="text" class="form-control @error('pppoe') is-invalid @enderror" name="pppoe" id="pppoe" placeholder="User PPPOE" value="{{ request('pppoe') ?? $cidDefault }}">
          @error('pppoe')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="form-group col-md-2">
          <label for="password">PPPOE Password</label>
          <input type="text" class="form-control @error('password') is-invalid @enderror" name="password" id="password" placeholder="Password" value="{{ $pppoepassword ?? $cidDefault }}">
          @error('password')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        
        <div class="form-group col-md-4">
          <label for="contact_name">Contact Name</label>
          <div class="input-group">
            <input type="text" class="form-control @error('contact_name') is-invalid @enderror" name="contact_name" id="contact_name" placeholder="Contact Name" value="{{old('contact_name')}}">
            <div class="input-group-append">
             <button type="button" class="btn btn-primary" onclick="copy_name()" title="Copy from Customer Name"><i class="fa fa-clone"></i></button>
           </div>
         </div>
         @error('contact_name')
         <div class="error invalid-feedback">{{ $message }}</div>
         @enderror
       </div>
       
       <div class="form-group col-md-2">
        <label for="id_card">ID Card</label>
        <input type="text" class="form-control @error('id_card') is-invalid @enderror" name="id_card" id="id_card" placeholder="No KTP" value="{{old('id_card')}}">
        @error('id_card')
        <div class="error invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
      
      <div class="form-group col-md-2">
        <label for="phone">Phone No</label>
        <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" id="phone"
          placeholder="Contoh: 08123456789"
          value="{{old('phone')}}"
          oninput="this.value=this.value.replace(/[^0-9]/g,'')"
          pattern="[0-9]{6,15}"
          title="Nomor telepon: angka saja, tanpa tanda + atau spasi">
        @error('phone')
        <div class="error invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted"><i class="fas fa-info-circle"></i> Angka saja, tanpa tanda +</small>
      </div>
      <div class="form-group col-md-2">
        <label for="date_of_birth"> Date of Birth </label>
        <div class="input-group date" id="reservationdate" data-target-input="nearest">
          <input type="text" name="date_of_birth" id="date_of_birth" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('1990-01-01')}}" />
          <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
          </div>
        </div>
      </div>
      
      <div class="form-group col-md-2">
       <label for="email"> Email </label>
       <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" placeholder="contoh@email.com" value="{{old('email')}}">
       @error('email')
       <div class="error invalid-feedback">{{ $message }}</div>
       @enderror
       @error('email')
       <div class="error invalid-feedback">{{ $message }}</div>
       @enderror
     </div>

     <div class="form-group col-md-2">
      <label for="id_sale"> Sales </label>
      <select name="id_sale" id="id_sale" class="form-control select2">
        @foreach ($sale as $id => $name)
        <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group col-md-4">
      <label for="ip"> Customer Address <span class="text-danger">*</span></label>
      <input type="text" class="form-control @error('address') is-invalid @enderror" name="address" id="address" placeholder="Customer Address" value="{{old('address')}}" required>
      @error('address')
      <div class="error invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group col-md-2">
      <label for="id_merchant"> Merchant </label>
      <select name="id_merchant" id="id_merchant" class="form-control select2">
        @foreach ($merchant as $id => $name)
        <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
      </select>
    </div>
    
    <div class="form-group col-md-2">
      <label for="npwp">NPWP</label>
      <input type="text" class="form-control @error('npwp') is-invalid @enderror" name="npwp" id="npwp" placeholder="NPWP" value="{{old('npwp')}}">
      @error('npwp')
      <div class="error invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group col-md-2">
      <label for="notification">Sent Notif</label>
      <select name="notification" id="notification" class="form-control select2">
        <option value="0" {{ old('notification','0') == '0' ? 'selected' : '' }}>None</option>
        <option value="1" {{ old('notification') == '1' ? 'selected' : '' }}>Whatsapp</option>
        <option value="2" {{ old('notification') == '2' ? 'selected' : '' }}>Email</option>
        <option value="3" {{ old('notification') == '3' ? 'selected' : '' }}>Mobile App</option>
      </select>
      <div id="fcm-info-box" class="mt-1" style="display:{{ old('notification') == '3' ? 'block' : 'none' }}">
        <div class="alert alert-info alert-sm py-1 px-2" style="font-size:0.75rem;">
          <i class="fas fa-mobile-alt mr-1"></i>FCM token akan terdaftar saat pelanggan login ke mobile app.
        </div>
      </div>
    </div>

    <div class="form-group col-md-4">
      <label for="coordinate"> Coordinate </label>
      <div class="input-group">
        <input type="text" class="form-control @error('coordinate') is-invalid @enderror" name="coordinate" id="coordinate" placeholder="Coordinate" value="{{old('coordinate')}}">
        <div class="input-group-append">
         <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-maps"><i class="fas fa-map-marker-alt"></i> Get From Maps</button>
       </div>
      </div>
      @error('coordinate')
      <div class="error invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group col-md-3">
      <label for="id_plan"> Plan </label>
      <select name="id_plan" id="id_plan" class="form-control select2">
        @foreach ($plan as $plan )
        <option value="{{ $plan->id }}" {{ request('id_plan') == $plan->id ? 'selected' : '' }}>{{ $plan->name }} (Rp. {{number_format($plan->price, 0, ',', '.')}})</option>
        @endforeach
      </select>
    </div>

    {{-- ====== ADD-ON SELECTOR ====== --}}
    <div class="form-group col-md-3">
      <label class="font-weight-bold"><i class="fas fa-puzzle-piece mr-1 text-primary"></i>Add-on <small class="text-muted font-weight-normal">(opsional — bisa lebih dari satu)</small>
        <span id="addon-total-badge" class="badge badge-pill badge-success ml-2" style="display:none;">Total: Rp 0</span>
      </label>
      <select name="addons[]" id="addons" class="form-control select2-addons" multiple style="width:100%">
        @foreach($addons ?? [] as $addon)
        <option value="{{ $addon->id }}"
          data-price="{{ $addon->price }}"
          data-desc="{{ $addon->description }}"
          {{ in_array($addon->id, old('addons', [])) ? 'selected' : '' }}>
          {{ $addon->name }} (+Rp {{ number_format($addon->price, 0, ',', '.') }})
        </option>
        @endforeach
      </select>
      <small class="text-muted">Ketik untuk mencari, klik untuk memilih. Pilihan akan tampil sebagai tag.</small>
    </div>

  <!-- Optional Fields Toggle (hanya untuk status Potensial) -->
  <div class="form-group col-md-12" id="optional-fields-toggle-wrapper" style="display: none;">
    <div class="custom-control custom-checkbox">
      <input type="checkbox" class="custom-control-input" id="show-optional-fields">
      <label class="custom-control-label" for="show-optional-fields">
        <i class="fas fa-cog"></i> Tampilkan Field Opsional (PPN, Billing Start, OLT, Distribution Point, Router)
      </label>
    </div>
  </div>

  <!-- Optional Fields Container -->
  <div id="optional-fields" class="col-md-12" style="display: none;">
    <div class="row">
      <div class="form-group col-md-2">
        <label for="tax">Ppn (%) <span class="text-danger" id="tax-required" style="display: none;">*</span></label>
        <input type="text" class="form-control @error('tax') is-invalid @enderror" name="tax" id="tax" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="0" value="0">
        @error('tax')
        <div class="error invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="form-group col-md-2">
        <label for="billing_start">Billing Start <span class="text-danger" id="billing-required" style="display: none;">*</span></label>
        <div class="input-group date" id="reservationdate" data-target-input="nearest">
          <input type="text" name="billing_start" id="billing_start" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-d')}}" />
          <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
          </div>
        </div>
      </div>

      <div class="form-group col-md-3">
        <label for="id_olt">OLT <span class="text-danger" id="olt-required" style="display: none;">*</span></label>
        <select name="id_olt" id="id_olt" class="form-control select2" style="width: 100%;">
          @foreach ($olt as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group col-md-3">
        <label for="id_distpoint">Distribution Point <span class="text-danger" id="distpoint-required" style="display: none;">*</span></label>
        <select name="id_distpoint" id="id_distpoint" class="form-control select2" style="width: 100%;">
          @foreach ($distpoint as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group col-md-2">
        <label for="id_distrouter">Distribution Router <span class="text-danger" id="distrouter-required" style="display: none;">*</span></label>
        <select name="id_distrouter" id="id_distrouter" class="form-control select2" style="width: 100%;">
          @foreach ($distrouter as $id => $name)
          <option value="{{ $id }}" {{ request('id_distrouter') == $id ? 'selected' : '' }}>{{ $name }}</option>
          @endforeach
        </select>
      </div>
    </div>
  </div>



  <input type="hidden" name="create_at" value="{{now()}}" >
  <input type="hidden" name="created_by" value="{{ Auth::user()->name }}" >

  <!-- Lead Management Fields (only for Potensial status) - PALING BAWAH -->
  <div id="lead-fields" class="col-md-12" style="display: none;">
    <hr>
    <h5 class="text-primary"><i class="fas fa-user-tie"></i> Lead Information</h5>
    <div class="row">
      <div class="form-group col-md-3">
        <label for="lead_source">Lead Source</label>
        <select name="lead_source" id="lead_source" class="form-control">
          <option value="">-- Select Source --</option>
          <option value="WA">WhatsApp</option>
          <option value="Phone">Phone Call</option>
          <option value="Email">Email</option>
          <option value="Walk-in">Walk-in</option>
          <option value="Referral">Referral</option>
          <option value="Social Media">Social Media</option>
          <option value="Website">Website</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <div class="form-group col-md-3">
        <label for="expected_close_date">Expected Close Date</label>
        <input type="date" class="form-control" name="expected_close_date" id="expected_close_date">
      </div>

      <div class="form-group col-md-3">
        <label for="conversion_probability">Conversion Probability (%)</label>
        <input type="number" class="form-control" name="conversion_probability" id="conversion_probability" min="0" max="100" placeholder="0-100">
      </div>

      <div class="form-group col-md-12">
        <label for="lead_notes">Lead Notes</label>
        <textarea class="form-control" name="lead_notes" id="lead_notes" rows="3" placeholder="Sales follow-up notes, customer interests, etc."></textarea>
      </div>
    </div>
    <hr>
  </div>

  <div class="form-group col-md-12">
    <label for="note">Note </label>
    <textarea class="form-control @error('note') is-invalid @enderror" name="note" id="note" rows="3" placeholder="Customer Description">{{old('note')}}</textarea>
    @error('note')
    <div class="error invalid-feedback">{{ $message }}</div>
    @enderror
  </div>


</div>
<!-- /.card-body -->

<div class="card-footer">
  <button type="submit" class="btn btn-primary">Submit</button>
  <a href="{{url('customer')}}" class="btn btn-default float-right">Cancel</a>
</div>
</form>
</div>
<!-- /.card -->

<!-- Form Element sizes -->




<!-- Modal -->
<div class="modal fade" id="modal-maps" tabindex="-1" role="dialog" aria-labelledby="modal-mapsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Select Location from Map</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      
      <div class="modal-body">
        <div id="map" style="height: 400px;"></div>
      </div>

      <div class="modal-footer justify-content-end">
       <button type="button" class="btn btn-secondary" id="btn-current-location">
        <i class="fas fa-location-arrow"></i> Current Location
      </button>
      <button type="button" class="btn btn-primary" data-dismiss="modal">Set</button>
    </div>

  </div>
</div>
</div>
<!-- /.modal -->
</section>
@endsection
@section('footer-scripts')
<script>
  document.getElementById('notification').addEventListener('change', function () {
    document.getElementById('fcm-info-box').style.display = this.value == '3' ? 'block' : 'none';
  });
</script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- Leaflet Geocoder -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<script type="text/javascript">
  $(document).ready(function() {
    $('.js-example-basic-single').select2();
    $('.select2').select2({
      width: '100%'
    });

    // Add-on total price updater
    function updateAddonTotal() {
      var total = 0;
      $('#addons option:selected').each(function () {
        total += parseInt($(this).data('price')) || 0;
      });
      var badge = $('#addon-total-badge');
      if (total > 0) {
        badge.text('Total: Rp ' + total.toLocaleString('id-ID')).show();
      } else {
        badge.hide();
      }
    }
    $('#addons').on('change', updateAddonTotal);
    updateAddonTotal();

    // Add-on tag-style select2
    $('#addons').select2({
      width: '100%',
      placeholder: 'Pilih add-on layanan...',
      allowClear: true,
      closeOnSelect: false,
      templateResult: function(option) {
        if (!option.id) return option.text;
        var price = $(option.element).data('price');
        var desc  = $(option.element).data('desc');
        var html  = '<div class="d-flex justify-content-between align-items-center">' +
                    '<span>' + option.text.split(' (')[0] + '</span>' +
                    '<span class="badge badge-success ml-2">+Rp ' + parseInt(price).toLocaleString('id-ID') + '</span>' +
                    '</div>';
        if (desc) html += '<div><small class="text-muted">' + desc + '</small></div>';
        return $(html);
      },
      templateSelection: function(option) {
        if (!option.id) return option.text;
        return option.text.split(' (')[0];
      }
    });
    
    // Function to toggle required attribute on optional fields
    function toggleOptionalFieldsRequired(isRequired) {
      if (isRequired) {
        // Make fields required
        $('#tax').attr('required', true);
        $('#billing_start').attr('required', true);
        $('#id_olt').attr('required', true);
        $('#id_distpoint').attr('required', true);
        $('#id_distrouter').attr('required', true);
        // Show asterisk
        $('#tax-required, #billing-required, #olt-required, #distpoint-required, #distrouter-required').show();
      } else {
        // Make fields optional
        $('#tax').removeAttr('required');
        $('#billing_start').removeAttr('required');
        $('#id_olt').removeAttr('required');
        $('#id_distpoint').removeAttr('required');
        $('#id_distrouter').removeAttr('required');
        // Hide asterisk
        $('#tax-required, #billing-required, #olt-required, #distpoint-required, #distrouter-required').hide();
      }
    }
    
    // Toggle lead fields and optional fields based on status selection
    $('#id_status').on('change', function() {
      if ($(this).val() == '1') { // Potensial
        // Show lead fields
        $('#lead-fields').slideDown(300);
        // Show checkbox toggle, hide optional fields (user can toggle manually)
        $('#optional-fields-toggle-wrapper').slideDown(300);
        $('#optional-fields').slideUp(300);
        $('#show-optional-fields').prop('checked', false);
        // Make optional fields NOT required
        toggleOptionalFieldsRequired(false);
      } else { // Active, Inactive, Block, dll
        // Hide lead fields
        $('#lead-fields').slideUp(300);
        // Hide checkbox toggle, ALWAYS show optional fields
        $('#optional-fields-toggle-wrapper').slideUp(300);
        $('#optional-fields').slideDown(300);
        // Make optional fields REQUIRED
        toggleOptionalFieldsRequired(true);
      }
    });

    // Toggle optional fields (hanya berfungsi saat status Potensial)
    $('#show-optional-fields').on('change', function() {
      if ($(this).is(':checked')) {
        $('#optional-fields').slideDown(300);
      } else {
        $('#optional-fields').slideUp(300);
      }
    });

    // Trigger on page load based on current status
    if ($('#id_status').val() == '1') { // Potensial
      $('#lead-fields').show();
      $('#optional-fields-toggle-wrapper').show();
      $('#optional-fields').hide();
      toggleOptionalFieldsRequired(false);
    } else { // Status lain
      $('#lead-fields').hide();
      $('#optional-fields-toggle-wrapper').hide();
      $('#optional-fields').show();
      toggleOptionalFieldsRequired(true);
    }
  });

  function copy_name() {
    document.getElementById("contact_name").value = document.getElementById("name").value;
  }

  function updateDatabase(newLat, newLng) {
    document.getElementById("coordinate").value = newLat + ',' + newLng;
  }

  const RESCODE = '{{ config("app.rescode") }}';

  function toggle_custid() {
    const el = document.getElementById("customer_id");
    el.disabled = !el.disabled;
  }

  function toggleRescodePrefix() {
    const useRescode = !document.getElementById('toggleRescode').checked;
    const cidEl   = document.getElementById('customer_id');
    const pppoeEl = document.getElementById('pppoe');
    const passEl  = document.getElementById('password');

    // Generate fresh base value (without rescode)
    const now  = new Date();
    const yy   = String(now.getFullYear()).slice(-2);
    const mm   = String(now.getMonth() + 1).padStart(2, '0');
    const dd   = String(now.getDate()).padStart(2, '0');
    const ran  = String(Math.floor(Math.random() * 1000)).padStart(3, '0');
    const base = yy + mm + dd + ran;

    const newVal = useRescode ? RESCODE + base : base;

    cidEl.value   = newVal;
    pppoeEl.value = newVal;
    if (passEl) passEl.value = newVal;
  }
</script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const pppoeInput = document.getElementById("pppoe");
    const passwordInput = document.getElementById("password");

        // Sinkronisasi nilai dari pppoe ke password
    pppoeInput.addEventListener("input", function () {
      passwordInput.value = this.value;
    });

        // Opsional: Jika ingin sinkronisasi dua arah (password ke pppoe)
      // passwordInput.addEventListener("input", function () {
      //   pppoeInput.value = this.value;
      // });
  });
</script>
<script>
  let map;
  let marker;
  let isMapInitialized = false;

  $('#modal-maps').on('shown.bs.modal', function () {
    if (!isMapInitialized) {
      const defaultLatLng = "{{ tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER', '-6.200000,106.816666')) }}".split(',');
      const lat = parseFloat(defaultLatLng[0]);
      const lng = parseFloat(defaultLatLng[1]);

      map = L.map('map').setView([lat, lng], 13);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      // 📌 Marker draggable
      marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      marker.on('dragend', function (e) {
        const latlng = e.target.getLatLng();
        document.getElementById('coordinate').value = `${latlng.lat.toFixed(6)},${latlng.lng.toFixed(6)}`;
      });

      // 🔍 Search bar
      L.Control.geocoder({
        defaultMarkGeocode: false
      })
      .on('markgeocode', function(e) {
        const latlng = e.geocode.center;
        map.setView(latlng, 16);
        marker.setLatLng(latlng);
        document.getElementById('coordinate').value = `${latlng.lat.toFixed(6)},${latlng.lng.toFixed(6)}`;
      })
      .addTo(map);

      // Tandai bahwa peta sudah di-inisialisasi
      isMapInitialized = true;
    }

    setTimeout(() => {
      map.invalidateSize();
    }, 300);
  });

  // 🌍 Gunakan Lokasi Saya
  document.getElementById('btn-current-location').addEventListener('click', function () {
    if (!map) return;

    map.locate({ setView: true, maxZoom: 18 });

    map.once('locationfound', function (e) {
      const { lat, lng } = e.latlng;
      marker.setLatLng(e.latlng);
      document.getElementById('coordinate').value = `${lat.toFixed(6)},${lng.toFixed(6)}`;
    });

    map.once('locationerror', function () {
      alert('Tidak dapat menemukan lokasi Anda. Pastikan izin lokasi aktif di browser.');
    });
  });
</script>

@endsection