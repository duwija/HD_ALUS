@extends('layout.main')
@section('title','Add onu  in OLT')

@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Register ONU to OLT </h3>
    </div>
    <form role="form" method="post" action="/olt/onuregistercst">
      @csrf


      <div class="card-body row">
        <div class="form-group col-md-3">

          <input type="hidden"  name="olt" id="olt"   value="{{$olt->id}}">

        </div>
      </div>


      <div class="card-body row">


        <div class="form-group col-md-3">
          <label for="onu_name">ONU Name </label>
          <input type="text" class="form-control " name="onu_name" id="onu_name" >
        </div>

      </div>
      <div class="card-body row">
       <div class="form-group col-md-3">
        <label for="onu_sn">ONU SN</label>
        <div class="input-group mb-3">
          <select name="onu_sn" id="onu_sn" class="form-control">
            <option value="" selected disabled>Choose ONU SN</option> <!-- Opsi default -->
            @foreach ($onu as $onu)
            <option value="{{$onu['oid']}}:{{$onu['value']}}:{{$onu['ponid']}}">{{$onu['oltName'] . ' - ' . $onu['identifier'] . ' (' . $onu['value'] . ')'}}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="form-group col-md-3">
        <label for="onu_id">ONU Id</label>
        <div class="input-group mb-3">
          <select name="onu_id" id="onu_id" class="form-control">
            <option value="">Select ONU ID</option>
          </select>
        </div>
      </div>


      <div class="form-group col-md-3">
        <label for="onu_type">  ONU Type </label>
        <div class="input-group mb-3">
          <select name="onu_type" id="onu_type" class="form-control select2">
            <!-- <option value="0">none</option> -->
            @foreach ($onutype as $id => $name)
            <option value="{{ $name }}">{{ $name }}</option>
            @endforeach
          </select>
        </div>

      </div>
    </div>
    <div class="card-body row">



      <div class="form-group col-md-3">
        <label for="tcon_profile">  Tcon Profile </label>
        <div class="input-group mb-3">
          <select name="tcon_profile" id="tcon_profile" class="form-control">

            @foreach ($oidOltTconProfile as $oidOltTconProfile)

            {

              <option value="{{$oidOltTconProfile}}">{{$oidOltTconProfile}}</option>
            }


            @endforeach

          </select>
        </div>

      </div>


      <div class="form-group col-md-3">
        <label for="gemport_profile">  Gemport Profile </label>
        <div class="input-group mb-3">
          <select name="gemport_profile" id="gemport_profile" class="form-control">

            @foreach ($oidOltGmportProfile as $oidOltGmportProfile)

            {

              <option value="{{$oidOltGmportProfile}}">{{$oidOltGmportProfile}}</option>
            }


            @endforeach

          </select>
        </div>

      </div>



      <div class="form-group col-md-3 ">
        <label for="onu_profile"> Vlan </label>
        <div class="input-group mb-3">
          <select name="onu_profile" id="onu_profile" class="form-control select2">
            @foreach ($onuprofile as $profile)
            <option value="{{ $profile }}:{{ $profile }}">
              {{ $profile}} (VLAN: {{ $profile}})
            </option>
            @endforeach
          </select>
        </div>

      </div>






    </div>
    <!-- /.card-body -->

    <div class="card-footer">
      <button type="submit"  id="submitBtn" class="btn btn-primary" disabled>Submit</button>
      <a href="{{url('olt/addonu/' . $olt->id)}}" class="btn btn-default float-right">Cancel</a>
    </div>
  </form>
</div>
<!-- /.card -->

<!-- Form Element sizes -->



</section>

@endsection
@section('footer-scripts')
@include('script.getonuid')
@endsection 
