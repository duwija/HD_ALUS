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
            <label for="nama">Customer Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Customer Name" value="{{$customer->name}}">
            @error('name')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group col-md-1">
            <label for="site location">  Status </label>
            <div class="input-group mb-3">
              <select name="id_status" id="id_status" class="form-control">

                @foreach ($status as $id => $name)
                @if ($id == $customer->id_status){
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
        <label for="nama">Contact Name</label>
        <div class="input-group mb-3">
          <input type="text" class="form-control @error('contact_name') is-invalid @enderror " name="contact_name" id="contact_name"  placeholder="Customer contact_name" value="{{$customer->contact_name}}">
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
      <label for="nama">Phone No</label>
      <input type="text" class="form-control @error('phone') is-invalid @enderror " name="phone" id="phone"  placeholder="Customer phone" value="{{$customer->phone}}">
      @error('phone')
      <div class="error invalid-feedback">{{ $message }}</div>
      @enderror
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
     <input type="text" class="form-control @error('email') is-invalid @enderror" name="email"  id="email" placeholder="email" value="{{$customer->email}}">
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
  <label for="ip"> Customer Address</label>
  <input type="text" class="form-control" name="address" id="address"  placeholder="Enter Address" value="{{$customer->address}}">

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
    </select>
  </div>
</div>

<div class="form-group col-sm-4">
 <label for="coordinate"> Coordinate </label>
 <div class="input-group mb-3">

  <input type="text" class="form-control @error('coordinate') is-invalid @enderror" name="coordinate"  id="coordinate" placeholder="Coordinate" value="{{$customer->coordinate}}">
  @error('coordinate')
  <div class="error invalid-feedback">{{ $message }}</div>
  @enderror
  <div class="input-group-append">
   <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-maps">Get From Maps </button>
 </div>
</div>
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

<div class="form-group col-md-2">
  <label for="site location"> Plan </label>
  <div class="input-group mb-3">
    <select name="id_plan" id="id_plan" class="form-control select2">

      @foreach ($plan as $plan)
      @if ($plan->id == $customer->id_plan){
       <option selected value="{{ $plan->id }}">{{ $plan->name  }} ( Rp. {{number_format($plan->price, 0, ',', '.')}} )</option>
     }
     @else
     {

      <option value="{{ $plan->id }}">{{ $plan->name  }} ( Rp. {{number_format($plan->price, 0, ',', '.')}} )</option>
    }
    @endif
    @endforeach
  </select>
</div>

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
  <label for="site location">  Isolir Date </label>
  <div class="input-group mb-3">
    <select name="isolir_date" id="isolir_date" class="form-control select2">
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
      <a href="/olt/addonu/{{$customer->id}}/{{$customer->id_olt}}" class="btn btn-primary">Onu </a>
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
</script>

@endsection