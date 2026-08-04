@extends('layout.main')
@section('title','Edit Customer')

<script type="text/javascript">
  function copy_name()
  {

    document.getElementById("contact_name").value= document.getElementById("name").value;
  }

  function updateDatabase(newLat, newLng)
  {
    document.getElementById("coordinate").value = newLat+','+newLng;
    validateCoordinate(document.getElementById("coordinate"));
  }

  function validateCoordinate(el) {
    const pattern = /^-?\d{1,3}\.\d+\s*,\s*-?\d{1,3}\.\d+$/;
    const errEl   = document.getElementById('coordinate-error');
    const val     = el.value.trim();

    if (val === '') {
      el.classList.remove('is-invalid', 'is-valid');
      errEl.style.display = 'none';
      return;
    }

    // Auto-hapus spasi di antara lat & lng
    if (pattern.test(val) && val !== val.replace(/\s/g, '')) {
      el.value = val.replace(/\s/g, '');
    }

    if (pattern.test(el.value.trim())) {
      el.classList.remove('is-invalid');
      el.classList.add('is-valid');
      errEl.style.display = 'none';
    } else {
      el.classList.remove('is-valid');
      el.classList.add('is-invalid');
      errEl.textContent = 'Format tidak valid. Gunakan: -6.200000,106.816666 (titik=desimal, koma=pemisah)';
      errEl.style.display = 'block';
    }
  }
  function toggle_custid(){
    if(document.getElementById("customer_id").disabled==true)
    {
      document.getElementById("customer_id").disabled=false;
    }
    else
      document.getElementById("customer_id").disabled=true;}
  </script>

  @section('content')
  <section class="content-header">

    <div class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title font-weight-bold"> Edit Customer </h3>
      </div>
      <form role="form" method="post" action="/customer/{{ $customer->id }}" enctype="multipart/form-data">
        @method('patch')
        @csrf
        <div class="card-body row">
          <div class="form-group col-md-4">
            <label for="nama">Customer Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Customer Name" value="{{$customer->name}}" required>
            @error('name')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group col-md-1">
            <label for="site location">  Status </label>
            @if ($customer->id_status == 1)
              {{-- Lead Potensial: status tidak bisa diubah via form edit --}}
              <input type="hidden" name="id_status" value="{{ $customer->id_status }}">
              <div class="form-control-plaintext">
                <span class="badge badge-warning" style="font-size:0.9rem;padding:6px 10px;">
                  <i class="fas fa-user-clock mr-1"></i> Potensial
                </span>
              </div>
              <small class="text-info mt-1 d-block"><i class="fas fa-lock mr-1"></i>
                Status hanya bisa diubah melalui tombol <strong>"Convert to Active"</strong> di halaman detail.
              </small>
            @else
              <div class="input-group mb-3">
                <select name="id_status" id="id_status" class="form-control">
                  @foreach ($status as $id => $name)
                    @if ($id == $customer->id_status)
                      <option selected value="{{ $id }}">{{ $name }}</option>
                    @else
                      <option value="{{ $id }}">{{ $name }}</option>
                    @endif
                  @endforeach
                </select>
              </div>
            @endif
          </div>

        <div class="form-group col-md-1">
          <label for="customer_id"> Customer Id (CID) </label>

          <div class="input-group mb-2">

            <input type="text" readonly  class="form-control @error('customer_id') is-invalid @enderror" name="customer_idx"  id="customer_idx" placeholder="Customer ID" value="{{$customer->customer_id}}">
            @error('customer_id')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="input-group-append">
             <button type="button" class="btn btn-primary"  onclick="toggle_custid()" ><i class="fa fa-unlock" aria-hidden="true"></i></button>
           </div>
         </div>
       </div>
       <div class="form-group col-md-2">
        <label for="nama">PPPOE User</label>
        <input type="text" class="form-control @error('pppoe') is-invalid @enderror " name="pppoe" id="pppoe"  placeholder="CID pppoe" value="{{$customer->pppoe}}">
        @error('pppoe')
        <div class="error invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
      <div class="form-group col-md-2">
        <label for="nama">PPPOE Password</label>
        <input type="text" class="form-control @error('password') is-invalid @enderror " name="password" id="password"  placeholder="CID Password" value="{{$customer->password}}">
        @error('password')
        <div class="error invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
      <div class="form-group col-md-2">
        <label for="ip">IP Address</label>
        <input 
        type="text" 
        class="form-control @error('ip') is-invalid @enderror" 
        name="ip" 
        id="ip" 
        placeholder="Leave blank for dynamic IP" 
        value="{{ old('ip', $customer->ip ?? '') }}" 
        pattern="^(\d{1,3}\.){3}\d{1,3}$" 
        title="Please enter a valid IPv4 address (e.g., 192.168.1.10)">

        @error('ip')
        <div class="error invalid-feedback">{{ $message }}</div>
        @enderror
      </div>


      <div class="form-group col-md-4">
        <label for="nama">Contact Name <span class="text-danger">*</span></label>
        <div class="input-group mb-3">
          <input type="text" class="form-control @error('contact_name') is-invalid @enderror " name="contact_name" id="contact_name"  placeholder="Customer contact_name" value="{{$customer->contact_name}}" required>
          @error('contact_name')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
          <div class="input-group-append">
           <button type="button" class="btn btn-primary"  onclick="copy_name()" ><i class="fa fa-clone" aria-hidden="true"></i></button>
         </div>
       </div>
     </div>
     <div class="form-group col-md-2">
      <label for="nama">Id Card</label>
      <input type="text" class="form-control @error('id_card') is-invalid @enderror " name="id_card" id="id_card"  placeholder="No KTP" value="{{$customer->id_card}}">
      @error('id_card')
      <div class="error invalid-feedback">{{ $message }}</div>
      @enderror
    </div>
    <div class="form-group col-md-2">
      <label for="nama">Phone No <span class="text-danger">*</span></label>
      <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" id="phone"
        placeholder="Contoh: 08123456789"
        value="{{$customer->phone}}"
        oninput="this.value=this.value.replace(/[^0-9]/g,'')"
        pattern="[0-9]{6,15}"
        title="Nomor telepon: angka saja, tanpa tanda + atau spasi"
        required>
      @error('phone')
      <div class="error invalid-feedback">{{ $message }}</div>
      @enderror
      <small class="text-muted"><i class="fas fa-info-circle"></i> Angka saja, tanpa tanda +</small>
    </div>
    <div class="form-group col-md-2">
      <label for="site location">  Date of Birth </label>

      <div class="input-group date" id="reservationdate" data-target-input="nearest">
        <input type="text" name="date_of_birth" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{$customer->date_of_birth}}" />
        <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
          <div class="input-group-text"><i class="fa fa-calendar"></i></div>
        </div>
      </div>
      

    </div>
    <div class="form-group col-sm-2">
     <label for="email"> Email  </label>
     <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" placeholder="contoh@email.com" value="{{$customer->email}}">
     @error('email')
     <div class="error invalid-feedback">{{ $message }}</div>
     @enderror

     
   </div>

   <div class="form-group col-md-2">
    <label for="site location">  Sales </label>
    <div class="input-group mb-3">
      <select name="id_sale" id="id_sale" class="form-control select2">
       @foreach ($sale as $id => $name)
       @if ($id == $customer->id_sale){
         <option selected value="{{ $id }}">{{ $name }}</option>
       }
       @else
       {

        <option value="{{ $id }}">{{ $name }}</option>
      }
      @endif
      @endforeach
    </select>
  </div>

</div>

<div class="form-group col-md-6">
  <label for="ip"> Customer Address <span class="text-danger">*</span></label>
  <input type="text" class="form-control" name="address" id="address"  placeholder="Enter Address" value="{{$customer->address}}" required>

</div>

<div class="form-group col-md-2">
  <label for="site location">  Merchant </label>
  <div class="input-group mb-3">
    <select name="id_merchant" id="id_merchant" class="form-control select2">
     @foreach ($merchant as $id => $name)
     @if ($id == $customer->id_merchant){
       <option selected value="{{ $id }}">{{ $name }}</option>
     }
     @else
     {

      <option value="{{ $id }}">{{ $name }}</option>
    }
    @endif
    @endforeach
  </select>
</div>

</div>

<div class="form-group col-md-2">
  <label for="notification">Sent Notif</label>
  <div class="input-group mb-3">
    <select name="notification" id="notification" class="form-control select2">

      <option value="0" {{ $customer->notification == 0 ? 'selected' : '' }}>None</option>
      <option value="1" {{ $customer->notification == 1 ? 'selected' : '' }}>Whatsapp</option>
      <option value="2" {{ $customer->notification == 2 ? 'selected' : '' }}>Email</option>
      <option value="3" {{ $customer->notification == 3 ? 'selected' : '' }}>Mobile App</option>
    </select>
  </div>
  <div id="fcm-token-box" class="mt-2" style="display:{{ $customer->notification == 3 ? 'block' : 'none' }}">
    @if($customer->fcm_token)
      <div class="alert alert-success alert-sm py-1 px-2 mb-1" style="font-size:0.75rem;">
        <i class="fas fa-mobile-alt mr-1"></i><strong>FCM Token terdaftar</strong>
      </div>
      <div class="input-group input-group-sm">
        <input type="text" class="form-control form-control-sm" value="{{ $customer->fcm_token }}" readonly style="font-size:0.7rem;">
        <div class="input-group-append">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyFcmToken(this)" title="Copy token">
            <i class="fas fa-copy"></i>
          </button>
        </div>
      </div>
    @else
      <div class="alert alert-warning alert-sm py-1 px-2" style="font-size:0.75rem;">
        <i class="fas fa-exclamation-triangle mr-1"></i>Belum ada FCM token. Pelanggan perlu login ke mobile app.
      </div>
    @endif
  </div>
</div>

<div class="form-group col-sm-4">
  <label for="coordinate"> Coordinate </label>
  <div class="input-group mb-3">
    <input type="text"
      class="form-control @error('coordinate') is-invalid @enderror"
      name="coordinate" id="coordinate"
      placeholder="Contoh: -6.200000,106.816666"
      value="{{$customer->coordinate}}"
      pattern="-?\d{1,3}\.\d+\s*,\s*-?\d{1,3}\.\d+"
      title="Format: lat,lng — contoh: -6.200000,106.816666 (titik sebagai desimal, koma sebagai pemisah lat & lng)"
      oninput="validateCoordinate(this)"
      onchange="validateCoordinate(this)">
    <div class="input-group-append">
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-maps"><i class="fas fa-map-marker-alt"></i> Get From Maps</button>
    </div>
  </div>
  <small class="text-muted"><i class="fas fa-info-circle"></i> Format: <code>-6.200000,106.816666</code> (titik = desimal, koma = pemisah)</small>
  <div id="coordinate-error" class="text-danger small" style="display:none;"></div>
  @error('coordinate')
  <div class="error invalid-feedback">{{ $message }}</div>
  @enderror
</div>
<div class="form-group col-md-2">
  <label for="nama">NPWP</label>
  <div class="input-group mb-3">
    <input type="text" class="form-control @error('npwp') is-invalid @enderror " name="npwp" id="npwp"  placeholder="Npwp" value="{{$customer->npwp}}">
    @error('npwp')
    <div class="error invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="input-group-append">

    </div>
  </div>
</div>

<div class="form-group col-md-3">
  <label for="site location"> Plan </label>
  <div class="input-group mb-3">
    <select name="id_plan" id="id_plan" class="form-control select2">
      @foreach ($plan as $plan)
      @if ($plan->id == $customer->id_plan)
       <option selected value="{{ $plan->id }}">{{ $plan->name }}{{ $plan->is_active ? '' : ' (inactive)' }} ( Rp. {{number_format($plan->price, 0, ',', '.')}} )</option>
     @else
      <option value="{{ $plan->id }}">{{ $plan->name }}{{ $plan->is_active ? '' : ' (inactive)' }} ( Rp. {{number_format($plan->price, 0, ',', '.')}} )</option>
    @endif
    @endforeach
    </select>
  </div>
</div>

{{-- ====== ADD-ON SELECTOR ====== --}}
<div class="form-group col-md-3">
  <label class="font-weight-bold">
    <i class="fas fa-puzzle-piece mr-1 text-primary"></i>Add-on
    <small class="text-muted font-weight-normal">(opsional &mdash; bisa lebih dari satu)</small>
    <span id="addon-total-badge" class="badge badge-pill badge-success ml-2" style="display:none;">Total: Rp 0</span>
  </label>
  <select name="addons[]" id="addons" class="form-control select2-addons" multiple style="width:100%">
    @foreach($addons as $addon)
    <option value="{{ $addon->id }}"
      data-price="{{ $addon->price }}"
      data-desc="{{ $addon->description }}"
      {{ in_array($addon->id, $customerAddons) ? 'selected' : '' }}>
      {{ $addon->name }}{{ $addon->is_active ? '' : ' (inactive)' }} (+Rp {{ number_format($addon->price, 0, ',', '.') }})
    </option>
    @endforeach
  </select>
  <small class="text-muted">Ketik untuk mencari, klik untuk memilih. Pilihan akan tampil sebagai tag.</small>
</div>

<div class="form-group col-md-3">
  <label class="font-weight-bold">
    <i class="fas fa-tags mr-1 text-info"></i>Customer Tags
    <small class="text-muted font-weight-normal">(add / delete tag customer)</small>
  </label>
  <select name="tags[]" id="customer-tags-select" class="form-control select2" multiple style="width:100%" data-placeholder="Pilih tag customer...">
    @foreach($allCustomerTags as $tagId => $tagName)
      <option value="{{ $tagId }}" {{ in_array($tagId, $selectedCustomerTags) ? 'selected' : '' }}>{{ $tagName }}</option>
    @endforeach
  </select>
  <div class="input-group input-group-sm mt-2">
    <input type="text" id="new_customer_tag" class="form-control" placeholder="Tambah tag customer baru...">
    <div class="input-group-append">
      <button type="button" class="btn btn-success" id="btn-add-customer-tag">
        <i class="fas fa-plus"></i> Tambah Tag
      </button>
    </div>
  </div>
  <small class="text-muted">Tag di sini khusus customer, terpisah dari tag tiket.</small>
</div>
<div class="form-group col-md-1">
  <label for="site location"> Ppn (%)</label>

  <div class="input-group mb-3">
    <input type="text" class="form-control @error('tax') is-invalid @enderror " name="tax" id="tax"  placeholder="Customer tax" value="{{$customer->tax}}">
    @error('tax')
    <div class="error invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

</div>
<div class="form-group col-md-2">
  <label for="site location">  Billing Start </label>

  <div class="input-group date" id="reservationdate" data-target-input="nearest">
    <input type="text" name="billing_start" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{$customer->billing_start}}" />
    <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
    </div>
  </div>


</div>
<div class="form-group col-md-1">
  <label for="site location">  Isolir Date <span class="text-danger">*</span></label>
  <div class="input-group mb-3">
    <select name="isolir_date" id="isolir_date" class="form-control select2" required>
      <?php
      $numbers = [];
      for ($i = 0; $i < 30; $i++) {
        $numbers[] = sprintf('%02d', $i);
      }
      ?>
      @foreach ($numbers as $numbers)
      @if ($numbers == $customer->isolir_date){
       <option selected value="{{$customer->isolir_date }}">{{ $customer->isolir_date }}</option>
     }
     @else
     {

      <option value="{{ $numbers }}">{{ $numbers }}</option>
    }
    @endif
    @endforeach
  </select>
</div>

</div>
<div class="form-group col-md-2">
  <label for="site location">  Distribution Point </label>
  <div class="input-group mb-3">
    <select name="id_distpoint" id="id_distpoint" class="form-control select2">
     {{--  <option value="1">none</option> --}}
     @foreach ($distpoint as $id => $name)
     @if ($id == $customer->id_distpoint){
       <option selected value="{{ $id }}">{{ $name }}</option>
     }
     @else
     {

      <option value="{{ $id }}">{{ $name }}</option>
    }
    @endif
    @endforeach
  </select>
</div>

</div>



{{-- distrouter --}}
<div class="form-group col-md-2">
  <label for="site location">  Distribution Router </label>
  <div class="input-group mb-3">
    <select name="id_distrouter" id="id_distrouter" class="form-control select2">

      @foreach ($distrouter as $id => $name)
      @if ($id == $customer->id_distrouter){
       <option selected value="{{ $id }}">{{ $name }}</option>
     }
     @else
     {

      <option value="{{ $id }}">{{ $name }}</option>
    }
    @endif
    @endforeach
  </select>
</div>

</div>

<div class="form-group col-md-2">
  <label for="site location">  Olt </label>
  <div class="input-group mb-3">
    <select name="id_olt" id="id_olt" class="form-control select2">
     {{--  <option value="1">none</option> --}}
     @foreach ($olt as $id => $name)
     @if ($id == $customer->id_olt){
       <option selected value="{{ $id }}">{{ $name }}</option>
     }
     @else
     {

      <option value="{{ $id }}">{{ $name }}</option>
    }
    @endif
    @endforeach
  </select>
</div>

</div>

<div class="form-group col-md-2">
  <label for="site location"> Onu Id</label>

  <div class="input-group mb-3">
    <input type="text" class="form-control @error('id_onu') is-invalid @enderror " name="id_onu" id="id_onu"  placeholder="x/x/x:xx" value="{{$customer->id_onu}}">
    @error('onu_id')
    <div class="error invalid-feedback">{{ $message }}</div>
    @enderror 
    <div class="input-group-append">
      <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modal-search-onu" title="Search ONU by Name/SN">
        <i class="fas fa-search"></i>
      </button>
      <a href="/olt/addonu/{{$customer->id}}/{{$customer->id_olt}}" class="btn btn-primary">Onu </a>
    </div>
  </div>
  <small class="text-muted" id="onu_format_hint">Format: x/x/x:xx</small>
  <div class="invalid-feedback" id="onu_format_error" style="display:none;"></div>

</div>

<!-- Modal: Search ONU by Name/SN -->
<div class="modal fade" id="modal-search-onu" tabindex="-1" role="dialog" aria-labelledby="modal-search-onuLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-search-onuLabel">Search ONU by Name / SN</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex flex-wrap align-items-center mb-3">
          <input type="text" id="onuSearchModalInput" class="form-control mr-2 mb-1"
            placeholder="Search by Name / SN (min 2 char)…" style="max-width:360px;">
          <button id="btnSearchOnuModal" class="btn btn-info mr-2 mb-1" type="button">
            <i class="fas fa-search"></i> Search
          </button>
        </div>
        <div id="onuSearchModalMeta" class="small text-muted mb-2"></div>
        <div class="table-responsive">
          <table id="onu-search-modal-table" class="table table-bordered table-striped table-sm mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>PON</th>
                <th>ONU ID</th>
                <th>SN</th>
                <th>Name</th>
                <th>Status</th>
                <th>Customer</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="8" class="text-center text-muted">Ketik nama customer atau Serial Number ONU lalu klik Search.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>




<div class="form-group">
  <input type="hidden" name="updated_at" value="{{now()}}" >
</div>

<div class="form-group">
  <input type="hidden" name="updated_by" value="{{ Auth::user()->name }}" >
</div>


<div class="form-group col-md-9">
  <label for="note">Note  </label>
  <textarea style="height: 100px;" class="form-control @error('note') is-invalid @enderror" name="note" id="note" placeholder="Site Descrition " > {{$customer->note}}</textarea>
  @error('note')
  <div class="error invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="form-group col-md-3">

</div>
{{--    <div class="form-group col-md-3">
  <label for="topology"> Topology </label>
  <div class="input-group mb-3">


   <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-topology">Topology </button>
 </div>
</div> --}}
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
</section>

@endsection

@section('footer-scripts')
<script>
  // Notification type toggle
  document.getElementById('notification').addEventListener('change', function () {
    document.getElementById('fcm-token-box').style.display = this.value == '3' ? 'block' : 'none';
  });
  function copyFcmToken(btn) {
    var val = btn.closest('.input-group').querySelector('input').value;
    navigator.clipboard.writeText(val).then(function () {
      btn.innerHTML = '<i class="fas fa-check"></i>';
      setTimeout(function () { btn.innerHTML = '<i class="fas fa-copy"></i>'; }, 1500);
    });
  }
</script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- Leaflet Geocoder -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<script>
  let map;
  let marker;
  let isMapInitialized = false;

  $('#modal-maps').on('shown.bs.modal', function () {
    if (!isMapInitialized) {
      const defaultLatLng = "{{ env('COORDINATE_CENTER', '-6.200000,106.816666') }}".split(',');
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

  // Add-on tag-style select2
  $(document).ready(function () {
    $('.select2').select2({ width: '100%' });

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

    $('#addons').select2({
      width: '100%',
      placeholder: 'Pilih add-on layanan...',
      allowClear: true,
      closeOnSelect: false,
      templateResult: function (option) {
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
      templateSelection: function (option) {
        if (!option.id) return option.text;
        return option.text.split(' (')[0];
      }
    });

    $('#addons').on('change', updateAddonTotal);
    updateAddonTotal();

    // Customer tag: add new dedicated customer tag (AJAX)
    $('#btn-add-customer-tag').on('click', function () {
      var tagName = $('#new_customer_tag').val().trim();
      if (!tagName) return;

      $.ajax({
        url: '{{ route('customer.tags.store') }}',
        method: 'POST',
        data: { new_tag: tagName, _token: '{{ csrf_token() }}' },
        success: function(res) {
          var select = $('#customer-tags-select');
          var exists = select.find('option[value="' + res.id + '"]').length > 0;
          if (!exists) {
            var option = new Option(res.name, res.id, true, true);
            select.append(option).trigger('change');
          } else {
            var current = select.val() || [];
            if (current.indexOf(String(res.id)) === -1) {
              current.push(String(res.id));
              select.val(current).trigger('change');
            }
          }
          $('#new_customer_tag').val('');
        },
        error: function(xhr) {
          var msg = 'Unknown error';
          if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
          }
          alert('Gagal menambahkan tag customer: ' + msg);
        }
      });
    });

    // === ONU ID Auto-Format ===
    var oltVendors = @json($oltVendors ?? []);

    function getOltType(oltId) {
      var olt = oltVendors[oltId];
      if (!olt) return 'zte';
      var v = ((olt.vendor || '') + ' ' + (olt.type || '') + ' ' + (olt.name || '')).toLowerCase();
      if (v.indexOf('hsgq') !== -1) return 'hsgq';
      if (v.indexOf('cdata') !== -1 || v.indexOf('fdd') !== -1) return 'cdata';
      if (v.indexOf('vsol') !== -1) return 'vsol';
      if (v.indexOf('hioso') !== -1) return 'hioso';
      return 'zte';
    }

    function updateOnuPlaceholder() {
      var oltId = $('#id_olt').val();
      var type = getOltType(oltId);
      var input = $('#id_onu');
      var hint = $('#onu_format_hint');
      if (type === 'hsgq') {
        input.attr('placeholder', 'PON:ONU contoh 2:1');
        hint.text('Format HSGQ: PON:ONU (contoh: 2:1)');
      } else if (type === 'cdata') {
        input.attr('placeholder', 'PON:ONU contoh 1:5');
        hint.text('Format CDATA: PON:ONU (contoh: 1:5)');
      } else if (type === 'vsol') {
        input.attr('placeholder', 'PON:ONU contoh 1:5');
        hint.text('Format VSOL: PON:ONU (contoh: 1:5)');
      } else if (type === 'hioso') {
        input.attr('placeholder', 'PON:ONU contoh 1:5');
        hint.text('Format HIOSO: PON:ONU (contoh: 1:5)');
      } else {
        input.attr('placeholder', 'x/x/x:xx contoh 0/2/1:3');
        hint.text('Format ZTE: frame/slot/port:onu (contoh: 0/2/1:3)');
      }
      validateOnuId();
    }

    function formatOnuId(value, type) {
      // Remove semua karakter selain angka, titik dua, dan slash
      var clean = value.replace(/[^0-9:\/]/g, '');
      if (type === 'hsgq' || type === 'cdata' || type === 'vsol' || type === 'hioso') {
        // HSGQ & CDATA: hanya angka dan satu titik dua -> x:x
        clean = clean.replace(/\//g, ''); // hapus slash
        var parts = clean.split(':');
        if (parts.length > 2) {
          clean = parts[0] + ':' + parts.slice(1).join('');
        }
        // Auto-insert : setelah angka pertama jika belum ada
        if (clean.length >= 2 && clean.indexOf(':') === -1) {
          clean = clean.charAt(0) + ':' + clean.substring(1);
        }
      } else {
        // ZTE: x/x/x:xx
        // Auto-insert / dan : pada posisi yang tepat
        var digits = clean.replace(/[\/\:]/g, '');
        if (digits.length > 0 && clean.indexOf('/') === -1 && clean.indexOf(':') === -1) {
          var result = '';
          var slashCount = 0;
          for (var i = 0; i < digits.length; i++) {
            result += digits[i];
            if (slashCount < 2 && i < digits.length - 1 && (i === 0 || (slashCount === 1 && i >= 1))) {
              // Don't auto-insert if user is still typing first digit
              if (digits.length > 3) {
                result += '/';
                slashCount++;
              }
            }
          }
          // Only auto-format if enough digits
          if (digits.length >= 4 && result.indexOf(':') === -1) {
            // Try to parse as frame/slot/port:onu
            clean = digits[0] + '/' + digits[1] + '/' + digits[2] + ':' + digits.substring(3);
          }
        }
      }
      return clean;
    }

    function validateOnuId() {
      var oltId = $('#id_olt').val();
      var type = getOltType(oltId);
      var value = $('#id_onu').val().trim();
      var errorEl = $('#onu_format_error');
      var input = $('#id_onu');

      if (!value) {
        errorEl.hide();
        input.removeClass('is-invalid');
        return true;
      }

      var valid = false;
      var msg = '';
      if (type === 'hsgq') {
        // Format: PON:ONU (misal 2:1)
        valid = /^\d{1,2}:\d{1,3}$/.test(value);
        msg = 'Format HSGQ harus PON:ONU (contoh: 2:1, 8:12)';
      } else if (type === 'cdata') {
        // Format: PON:ONU (misal 1:5) — sama seperti HSGQ, PON 1-8/1-16 tergantung model
        valid = /^\d{1,2}:\d{1,3}$/.test(value);
        msg = 'Format CDATA harus PON:ONU (contoh: 1:5, 8:12)';
      } else if (type === 'vsol') {
        // Format: PON:ONU (misal 1:5) — VSOL-G004 punya 4 PON port, ONU ID 1-127
        valid = /^\d{1,2}:\d{1,3}$/.test(value);
        msg = 'Format VSOL harus PON:ONU (contoh: 1:5, 4:17)';
      } else if (type === 'hioso') {
        // Format: PON:ONU (misal 1:5)
        valid = /^\d{1,2}:\d{1,3}$/.test(value);
        msg = 'Format HIOSO harus PON:ONU (contoh: 1:5, 4:12)';
      } else {
        // Format: x/x/x:xx (misal 0/2/1:3)
        valid = /^\d{1,2}\/\d{1,2}\/\d{1,2}:\d{1,3}$/.test(value);
        msg = 'Format ZTE harus frame/slot/port:onu (contoh: 0/2/1:3)';
      }

      if (!valid) {
        errorEl.text(msg).show();
        input.addClass('is-invalid');
      } else {
        errorEl.hide();
        input.removeClass('is-invalid');
      }
      return valid;
    }

    // Event: OLT berubah -> update placeholder & validasi
    $('#id_olt').on('change', function() {
      updateOnuPlaceholder();
      // Update link Onu button
      var oltId = $(this).val();
      var customerId = '{{ $customer->id }}';
      $(this).closest('.row, .form-row').find('a[href*="addonu"]').attr('href', '/olt/addonu/' + customerId + '/' + oltId);
    });

    // Event: input ONU ID -> auto-format
    $('#id_onu').on('input', function() {
      var oltId = $('#id_olt').val();
      var type = getOltType(oltId);
      var cursorPos = this.selectionStart;
      var oldLen = this.value.length;
      var formatted = formatOnuId(this.value, type);
      if (formatted !== this.value) {
        this.value = formatted;
        var newPos = cursorPos + (formatted.length - oldLen);
        this.setSelectionRange(newPos, newPos);
      }
    });

    // Event: blur -> validasi final
    $('#id_onu').on('blur', validateOnuId);

    // Init on page load
    updateOnuPlaceholder();

    // === Search ONU by Name/SN (same backend as OLT show page's Search ONU tab) ===
    function escapeHtml(str) {
      return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function runOnuSearchModal() {
      var q = ($('#onuSearchModalInput').val() || '').trim();
      var oltId = $('#id_olt').val();
      var $btn  = $('#btnSearchOnuModal');
      var $meta = $('#onuSearchModalMeta');
      var $body = $('#onu-search-modal-table tbody');

      if (!oltId) {
        Swal.fire({ icon: 'warning', title: 'Pilih OLT terlebih dahulu', timer: 1800, showConfirmButton: false });
        return;
      }
      if (q.length < 2) {
        Swal.fire({ icon: 'warning', title: 'Keyword minimal 2 karakter', timer: 1800, showConfirmButton: false });
        return;
      }

      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Searching…');
      $meta.text('Walking SNMP…');
      $body.html('<tr><td colspan="8" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Loading…</td></tr>');

      $.ajax({
        url: '/olt/onu-search',
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data: { olt_id: oltId, q: q },
        dataType: 'json'
      })
      .done(function(res) {
        if (!res.success) {
          $meta.text('');
          $body.html('<tr><td colspan="8" class="text-center text-danger">' + escapeHtml(res.message || 'Search gagal') + '</td></tr>');
          return;
        }
        var rows = res.data || [];
        $meta.text(res.message || (rows.length + ' result'));
        if (rows.length === 0) {
          $body.html('<tr><td colspan="8" class="text-center text-muted">Tidak ada ONU yang cocok.</td></tr>');
          return;
        }
        var html = '';
        rows.forEach(function(r, i) {
          var custCell = r.customer ? escapeHtml(r.customer) : '<span class="text-muted">—</span>';
          var statusLower = (r.status || '').toLowerCase();
          var isWorking = statusLower.indexOf('working') !== -1 || statusLower.indexOf('online') !== -1;
          var statusBadge = isWorking
            ? '<span class="badge badge-success">' + escapeHtml(r.status) + '</span>'
            : '<span class="badge badge-secondary">' + escapeHtml(r.status) + '</span>';

          html += '<tr>'
                +   '<td>' + (i + 1) + '</td>'
                +   '<td><code>' + escapeHtml(r.pon) + '</code></td>'
                +   '<td>' + escapeHtml(r.onu_id) + '</td>'
                +   '<td><code>' + escapeHtml(r.sn) + '</code></td>'
                +   '<td>' + escapeHtml(r.name) + '</td>'
                +   '<td>' + statusBadge + '</td>'
                +   '<td>' + custCell + '</td>'
                +   '<td>'
                +     '<button type="button" class="btn btn-xs btn-success onu-add-id" '
                +       'data-pon="' + escapeHtml(r.pon) + '" data-onuid="' + escapeHtml(r.onu_id) + '" title="Gunakan ONU ID ini">'
                +       '<i class="fas fa-plus"></i> Add ID'
                +     '</button>'
                +   '</td>'
                + '</tr>';
        });
        $body.html(html);
      })
      .fail(function(xhr) {
        var msg = 'HTTP ' + xhr.status;
        try { msg = (JSON.parse(xhr.responseText).message) || msg; } catch (e) {}
        $meta.text('');
        $body.html('<tr><td colspan="8" class="text-center text-danger">' + escapeHtml(msg) + '</td></tr>');
      })
      .always(function() {
        $btn.prop('disabled', false).html('<i class="fas fa-search"></i> Search');
      });
    }

    $(document).on('click', '#btnSearchOnuModal', runOnuSearchModal);
    $(document).on('keypress', '#onuSearchModalInput', function(e) {
      if (e.which === 13) { e.preventDefault(); runOnuSearchModal(); }
    });

    // Klik "Add ID" pada hasil pencarian -> paste "pon:onu_id" ke field ONU ID
    $(document).on('click', '.onu-add-id', function() {
      var pon = $(this).data('pon');
      var onuId = $(this).data('onuid');
      $('#id_onu').val(pon + ':' + onuId).trigger('input');
      validateOnuId();
      $('#modal-search-onu').modal('hide');
    });

    // Reset modal state saat dibuka
    $('#modal-search-onu').on('shown.bs.modal', function () {
      $('#onuSearchModalInput').val('').trigger('focus');
      $('#onuSearchModalMeta').text('');
      $('#onu-search-modal-table tbody').html('<tr><td colspan="8" class="text-center text-muted">Ketik nama customer atau Serial Number ONU lalu klik Search.</td></tr>');
    });
  });
</script>

@endsection