@extends('layout.main')
@section('title','Add onu  in OLT')

@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Register ONU to OLT </h3>
    </div>










    <div class="card-body">
      <div class="nav-tabs-custom ">
        <ul class="nav nav-tabs card-tabs">
          <li class="active nav-item card-primary  card-outline"><a class="nav-link " href="#tab_1" data-toggle="tab"><strong>ONU  Support OMCI </strong></a></li>
          <li class=" nav-item card-warning card-outline "><a class="nav-link " href="#tab_2" data-toggle="tab"><strong>ONU not Support OMCI </strong></a></li>
        </ul>
        <div class="tab-content ">
          <div class="tab-pane active card-primary card-outline" id="tab_1">
            <!-- Form content for Tab 1 -->
            <form role="form" method="post" action="/olt/onuregister">
              @csrf


              <div class="card-body row">
                <div class="form-group col-md-3">

                  <input type="hidden"  name="olt" id="olt"   value="{{$olt->id}}">
                  <input type="hidden"  name="id_customer" id="id_customer"   value="{{$customer->id}}">

                </div>
              </div>


              <div class="card-body row">
                <div class="form-group col-md-3">
                  <label for="customer_id">Customer ID </label>
                  <input type="text" class="form-control " name="customer_id" id="customer_id"   value="{{$customer->customer_id}}">
                </div>
                <div class="form-group col-md-3">
                  <label for="customer_name">Customer Name </label>
                  <input type="text" class="form-control " name="customer_name" id="customer_name"   value="{{$customer->name}}">
                </div>

                <div class="form-group col-md-3">
                  <label for="password">CID Psssword </label>
                  <input type="text" class="form-control " name="password" id="password"   value="{{$customer->password}}">
                </div>

              </div>
              <div class="card-body row">
                <div class="form-group col-md-3">
                  <label for="onu_sn"> ONU SN </label>
                  <div class="input-group mb-3">

                   <select name="onu_sn" id="onu_sn" class="form-control">
                     <option value="" selected disabled>Choose ONU SN</option> <!-- Opsi default -->
                     @foreach ($onu as $onus)

                     {

                      <option value="{{$onus['oid']}}:{{$onus['value']}}:{{$onus['ponid']}}">{{$onus['oltName'] . ' - ' . $onus['identifier'] . ' (' . $onus['value'] . ')'}}</option>
                    }


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

                    @foreach ($oidOltTconProfile as $oidOltTconProfiles)

                    {

                      <option value="{{$oidOltTconProfiles}}">{{$oidOltTconProfiles}}</option>
                    }


                    @endforeach

                  </select>
                </div>

              </div>


              <div class="form-group col-md-3">
                <label for="gemport_profile">  Gemport Profile </label>
                <div class="input-group mb-3">
                  <select name="gemport_profile" id="gemport_profile" class="form-control">

                    @foreach ($oidOltGmportProfile as $oidOltGmportProfiles)

                    {

                      <option value="{{$oidOltGmportProfiles}}">{{$oidOltGmportProfiles}}</option>
                    }


                    @endforeach

                  </select>
                </div>

              </div>



              <div class="form-group col-md-3 ">
                <label for="onu_profile">  ONU Profile </label>
                <div class="input-group mb-3">
                  <select name="onu_profile" id="onu_profile" class="form-control select2">
                    <!-- <option value="0">none</option> -->
                    @foreach ($onuprofile as $profile)
                    <option value="{{ $profile->name }}:{{ $profile->vlan }}">
                      {{ $profile->name }} (VLAN: {{ $profile->vlan }})
                    </option>
                    @endforeach
                  </select>
                </div>

              </div>






            </div>
            <!-- /.card-body -->

            <div class="card-footer">
              <button disabled id="submitBtn" type="submit" class="btn btn-primary">Submit</button>
              <a href="{{url('customer')}}/{{$customer->id}}" class="btn btn-default float-right">Cancel</a>
            </div>
          </form>
        </div>
        <div class="tab-pane card-warning card-outline" id="tab_2">
          <!-- Form content for Tab 2 -->
          <form role="form" method="post" action="/olt/onuregistercst">
            @csrf


            <div class="card-body row">
              <div class="form-group col-md-3">

                <input type="hidden"  name="olt" id="olt"   value="{{$olt->id}}">
                <input type="hidden"  name="id_customer" id="id_customer"   value="{{$customer->id}}">
                <!--    <input type="text"  name="onu_name" id="onu_name"   value="{{$customer->customer_id}} {{$customer->name}}">  -->

              </div>
            </div>


            <div class="card-body row">
             <!--  <div class="form-group col-md-3">
                <label for="customer_id">Customer ID </label>
                <input type="text" class="form-control " name="customer_id" id="customer_id"   value="{{$customer->customer_id}}">
              </div> -->
              <div class="form-group col-md-3">
                <label for="customer_name">Onu Name </label>
                <input type="text" class="form-control " name="onu_name" id="onu_name"   value="{{$customer->customer_id}} {{$customer->name}}">
              </div>

           <!--    <div class="form-group col-md-3">
                <label for="password">CID Psssword </label>
                <input type="text" class="form-control " name="password" id="password"   value="{{$customer->password}}">
              </div> -->

            </div>
            <div class="card-body row">
              <div class="form-group col-md-3">
                <label for="onu_sn"> ONU SN </label>
                <div class="input-group mb-3">

                  <select name="onu_sn" id="onu_snx" class="form-control">
                   <option value="" selected disabled>Choose ONU SN</option> <!-- Opsi default -->
                   @foreach ($onu as $onux)

                   {

                    <option value="{{$onux['oid']}}:{{$onux['value']}}:{{$onux['ponid']}}">{{$onux['oltName'] . ' - ' . $onux['identifier'] . ' (' . $onux['value'] . ')'}}</option>
                  }


                  @endforeach

                </select>
              </div>

            </div>


            <div class="form-group col-md-3">
              <label for="onu_idx">ONU Id</label>
              <div class="input-group mb-3">
                <select name="onu_id" id="onu_idx" class="form-control">
                  <option value="">Choose ONU ID</option>
                </select>
              </div>
            </div>



            <div class="form-group col-md-4">
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

                  @foreach ($oidOltTconProfile as $oidOltTconProfilex)

                  {

                    <option value="{{$oidOltTconProfilex}}">{{$oidOltTconProfilex}}</option>
                  }


                  @endforeach

                </select>
              </div>

            </div>


            <div class="form-group col-md-3">
              <label for="gemport_profile">  Gemport Profile </label>
              <div class="input-group mb-3">
                <select name="gemport_profile" id="gemport_profile" class="form-control">

                  @foreach ($oidOltGmportProfile as $oidOltGmportProfilex)

                  {

                    <option value="{{$oidOltGmportProfilex}}">{{$oidOltGmportProfilex}}</option>
                  }


                  @endforeach

                </select>
              </div>

            </div>



            <div class="form-group col-md-3  ">
              <label for="onu_profile">  Vlan </label>
              <div class="input-group mb-3">
                <select name="onu_profile" id="onu_profile" class="form-control  select2">
                  @foreach ($vlanList as $vlanlist)
                  <option value="{{ $vlanlist }}:{{ $vlanlist }}">
                    {{ $vlanlist}} (VLAN: {{ $vlanlist}})
                  </option>
                  @endforeach
                </select>
              </div>

            </div> 






          </div>
          <!-- /.card-body -->

          <div class="card-footer">
            <button disabled id="submitBtnx" type="submit" class="btn btn-primary">Submit</button>
            <a href="{{url('customer')}}/{{$customer->id}}" class="btn btn-default float-right">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>














































</div>
<!-- /.card -->

<!-- Form Element sizes -->



</section>

@endsection
@section('footer-scripts')
@include('script.getonuid')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const onuSnSelect = document.getElementById('onu_sn');
    const submitBtn = document.getElementById('submitBtn');
    const onuSnSelectx = document.getElementById('onu_snx');
    const submitBtnx = document.getElementById('submitBtnx');

    onuSnSelect.addEventListener('change', function() {
      submitBtn.disabled = !this.value; // Enable button if a value is selected
    });
    onuSnSelectx.addEventListener('change', function() {
      submitBtnx.disabled = !this.value; // Enable button if a value is selected
    });
  });
</script>
@endsection 
