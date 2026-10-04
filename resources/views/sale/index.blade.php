@extends('layout.main')
@section('title','Sales List')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Sales List  </h3>
      <a href="{{url ('sale/create')}}" class=" float-right btn  bg-gradient-primary btn-sm">Add New Sales</a>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      <form method="GET" action="{{ url('/sale') }}" class="row mb-3">
        <div class="form-group col-md-3">
          <label for="keyword">Cari</label>
          <input type="text" class="form-control" id="keyword" name="keyword" value="{{ request('keyword') }}" placeholder="Nama / email / phone / alamat">
        </div>
        <div class="form-group col-md-2">
          <label for="sale_type">Sales Type</label>
          <select class="form-control" id="sale_type" name="sale_type">
            <option value="">All</option>
            @foreach ($saleTypes as $type)
            <option value="{{ $type }}" {{ request('sale_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-2">
          <label for="date_from">Dari Tanggal</label>
          <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $from }}">
        </div>
        <div class="form-group col-md-2">
          <label for="date_end">Sampai Tanggal</label>
          <input type="date" class="form-control" id="date_end" name="date_end" value="{{ $to }}">
        </div>
        <div class="form-group col-md-3">
          <label>&nbsp;</label>
          <div>
            <button type="submit" class="btn btn-warning"><i class="fas fa-filter"></i> Filter</button>
            <a href="{{ url('/sale') }}" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
          </div>
        </div>
      </form>
      <p class="text-muted small mb-2">
        Total / Active = jumlah customer saat ini. New = billing start, Lost = customer terhapus (berhenti / tidak jadi berlangganan) pada periode
        {{ \Carbon\Carbon::parse($from)->format('d M Y') }} - {{ \Carbon\Carbon::parse($to)->format('d M Y') }}.
      </p>
      <div class="table-responsive">
        <table class="table table-bordered table-striped">

          <thead >
            <tr>
              <th scope="col">#</th>
              <th scope="col">Name</th>
              <th scope="col">Email</th>
              <th scope="col">Sales Type</th>
              <th scope="col">Phone</th>
              <th scope="col">Address</th>
              <th scope="col">Total / Active</th>
              <th scope="col">New</th>
              <th scope="col">Lost</th>
              <th scope="col">Lost Revenue</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
           @foreach( $sale as $sale)
           <tr>
            <th scope="row">{{ $loop->iteration }}</th>
            <td class="text-center"><a href="/sale/{{ $sale->id }}" class="badge bg-primary m-1">{{ $sale->name }}  </a></td>
            <td> {{ $sale->email }}</td>
            <td>{{ $sale->sale_type }}</td>
            <td>{{ $sale->phone }}</td>
            <td>{{ $sale->address }}</td>
            <td class="text-center">{{ $sale->total_customers }} / <span class="text-success">{{ $sale->active_customers }}</span></td>
            <td class="text-center"><span class="badge badge-success">{{ $sale->new_customers }}</span></td>
            <td class="text-center"><span class="badge {{ $sale->lost_customers ? 'badge-danger' : 'badge-light' }}">{{ $sale->lost_customers }}</span></td>
            <td class="text-right">{{ number_format((int) $sale->lost_revenue, 0, ',', '.') }}</td>
            <td >
              <!-- <div class="float-right " > -->
                <button type="button" class="btn btn-primary btn-sm m-1" data-toggle="modal" data-target="#modal-sale-detail{{ $sale->id }}">Detail </button>


                <a href="/sale/{{ $sale->id }}/edit" class="btn btn-primary btn-sm m-1"> <i class="fa fa-edit"> </i> </a>


                <form  action="/sale/{{ $sale->id }}" method="POST" class="d-inline item-delete " >
                  @method('delete')
                  @csrf

                  <button type="submit"  class="btn btn-danger btn-sm m-1"> <i class="fa fa-times"> </i> Del </button>
                </form>
              </td>

              <!-- </div> -->
            </td>

          </tr>


          <div class="modal fade" id="modal-sale-detail{{ $sale->id }}">
            <div class="modal-dialog modal-lg ">
              <div class="modal-content">
                <div class="card card-primary card-outline">
                  <div class="card-body box-profile bg-light">
                    <div class="text-center">
                      <img style="width: 128px; height: 128px" class="profile-sale-img img-fluid img-circle"
                      src="/storage/sales/{{$sale->photo}}"
                      alt="sale profile picture" onerror="this.onerror=null;this.src='storage/sales/default_profile.png';" />
                    </div>

                    <h3 class="profile-salename text-center">{{$sale->name}}</h3>
                    <p class="text-muted text-center">~ {{$sale->privilege}} ~</p>

                    <p class="text-muted text-center">{{$sale->job_title}}</p>
                    <div class="row">
                      <ul class="list-group list-group-unbordered col-md-6 p-md-2">
                       <li class="list-group-item p-2">
                        <b>Full Name</b> <a class="float-right">{{$sale->full_name}}</a>
                      </li>
                      <li class="list-group-item p-2">
                        <b>E mail</b> <a class="float-right">{{$sale->email}}</a>
                      </li>
                      <li class="list-group-item p-2 ">
                        <b>Employee Type</b> <a class="float-right">{{$sale->sale_type}}</a>
                      </li>
                      <li class="list-group-item p-2 ">
                        <b>Join Date</b> <a class="float-right">{{$sale->join_date}}</a>
                      </li>
                    </ul>
                    <ul class="list-group list-group-unbordered col-md-6 p-md-2">
                     <li class="list-group-item p-2">
                      <b>Date of birth</b> <a class="float-right">{{$sale->date_of_birth}}</a>
                    </li>
                    <li class="list-group-item p-2 ">
                      <b>Address</b> <a class="float-right">{{$sale->address}}</a>
                    </li>
                    <li class="list-group-item p-2 ">
                      <b>Phone</b> <a class="float-right">{{$sale->phone}}</a>
                    </li>
                    {{--  <li class="list-group-item p-2 ">
                      <b>note</b> <a class="float-right">{{$sale->date_of_birth}}</a>
                    </li> --}}
                  </ul>

                  <ul class="list-group list-group-unbordered col-md-12 pr-md-2 pl-md-2">
                    <li class="list-group-item p-2 ">
                      <b>note</b> <a class="float-right">{{$sale->description}}</a>
                    </li>
                  </ul>
                </div>

                <div class="modal-footer justify-content-between float-right">
                  <button type="button" class="btn btn-primary float-right " data-dismiss="modal">Close</button>

                {{--  </div> --}}
              </div>
            </div>
            <!-- /.card-body -->
          </div>
          <!-- /.card-body -->
        </div>



        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->

    </div>

    @endforeach

  </tbody>
</table>
</div>
</div>
</div>

</section>

@endsection
