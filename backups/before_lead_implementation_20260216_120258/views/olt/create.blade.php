@extends('layout.main')
@section('title','Add New OLT')

@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Add New OLT </h3>
    </div>
    <form role="form" method="post" action="/olt">
      @csrf
      <div class="card-body row">
        <div class="form-group col-md-3">
          <label for="nama">Name</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Enter Distribution Point Name" value="{{old('name')}}">
          @error('name')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="form-group col-md-3">
          <label for="vendor">Vendor <span class="text-danger">*</span></label>
          <div class="input-group mb-3">
            <select name="vendor" id="vendor" class="form-control @error('vendor') is-invalid @enderror" required>
              <option value="">-- Select Vendor --</option>
              <option value="zte">ZTE</option>
              <option value="cdata">CDATA</option>
              <option value="hsgq">HSGQ</option>
              <option value="huawei">Huawei</option>
              <option value="fiberhome">Fiberhome</option>
              <option value="vsol">VSOL</option>
              <option value="other">Other</option>
            </select>
            @error('vendor')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="form-group col-md-3">
          <label for="type">OLT Model/Type <span class="text-danger">*</span></label>
          <div class="input-group mb-3">
            <input type="text" class="form-control @error('type') is-invalid @enderror" name="type" id="type" placeholder="e.g., c620, c300, fdd-lt" value="{{old('type')}}" required>
            <small class="form-text text-muted">
              ZTE: c300, c320, c600, c620, c650<br>
              CDATA: fdd-lt, fdd-olt<br>
              Others: ma5, an5, etc.
            </small>
            @error('type')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="form-group col-md-3">
          <label for="ip">IP Address</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" name="ip" id="ip"  placeholder="Enter IP Address" value="{{old('ip')}}">

        </div>
        <div class="form-group col-md-3">
          <label for="port">Telnet Port</label>
          <input type="text" class="form-control @error('port') is-invalid @enderror" name="port" id="port"  placeholder="Enter Telnet Port default: 23" value="23">
        </div>
        <div class="form-group col-md-3">
          <label for="user">Telnet user</label>
          <input type="text" class="form-control @error('user') is-invalid @enderror" name="user" id="user"  placeholder="Enter Telnet user" value="{{old('user')}}">
        </div>
        <div class="form-group col-md-3">
          <label for="password">Password</label>
          <input type="text" class="form-control @error('password') is-invalid @enderror" name="password" id="password"  placeholder="Enter Telnet password" value="{{old('password')}}">
        </div>
        <div class="form-group col-md-3">
          <label for="community_ro">Read Community</label>
          <input type="text" class="form-control @error('community_ro') is-invalid @enderror" name="community_ro" id="community_ro"  placeholder="Enter Read Community" value="{{old('community_ro')}}">
        </div>
        <div class="form-group col-md-3">
          <label for="community_rw">Write Community</label>
          <input type="text" class="form-control @error('community_rw') is-invalid @enderror" name="community_rw" id="community_rw"  placeholder="Enter Write Community" value="{{old('community_rw')}}">
        </div>
        <div class="form-group col-md-3">
          <label for="snmp_port">Snmp Port</label>
          <input type="text" class="form-control @error('snmp_port') is-invalid @enderror" name="snmp_port" id="snmp_port"  placeholder="Enter Telnet snmp port default :161" value="161">
        </div>

        



      </div>
      <!-- /.card-body -->

      <div class="card-footer">
        <button type="submit" class="btn btn-primary">Submit</button>
        <a href="{{url('olt')}}" class="btn btn-default float-right">Cancel</a>
      </div>
    </form>
  </div>
  <!-- /.card -->

  <!-- Form Element sizes -->



</section>

@endsection