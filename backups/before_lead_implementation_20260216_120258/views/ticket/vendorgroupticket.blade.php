@extends('layout.main')
@section('title','Vendor Gruop Ticket List')
@section('content')
<section class="content-header">




  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Vendor  Gruop Ticket List  </h3>

      {{--  <a href="{{url ('distpoint/create')}}" class=" float-right btn  bg-gradient-primary btn-sm">Add New Ticket</a> --}}
      <br>
     <!--  @if (empty($date_from))
      <form role="form" method="post" action="/ticket/search">
          @csrf -->
          <div class="row pt-2 pl-2">



            <div class="form-group col-md-2">
              <label for="site location">  Start Date </label>
              <div class="input-group mb-3">
                <div class="input-group   date" id="reservationdate" data-target-input="nearest">
                    <input type="text" name="date_from" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-01')}}" />
                    <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group col-md-2">
            <label for="site location">  End Date </label>
            <div class="input-group mb-3">
                <div class="input-group  date" id="reservationdate" data-target-input="nearest">
                    <input type="text" name="date_end" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-d')}}" />
                    <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div hidden class="form-group col-md-2">
            <label for="site location">  Category </label>
            <div class="input-group mb-3 ">
                <select name="id_categori" id="id_categori" class="form-control">
                 <option value="">All</option> 
                 @foreach ($ticketcategorie as $id => $name)
                 <option value="{{ $id }}">{{ $name }}</option>
                 @endforeach
             </select>
         </div>
     </div>

     <div class="form-group col-md-1">
        <label for="site location">  Assign to </label>
        <div class="input-group ">
          <select name="assign_to" id="assign_to" class="form-control">
             <option value="">All</option> 
             @foreach ($user as $id => $name)
             <option value="{{ $id }}">{{ $name }}</option>
             @endforeach
         </select>
     </div>
 </div>
 <div class="form-group col-md-1">
    <label for="site location">  Status </label>
    <div class="input-group ">
      <select name="id_status" id="id_status" class="form-control">
         <option value="">All</option> 
         <option value="Open">Open</option>
         <option value="Inprogress">Inprogress</option>
         <option value="Pending">Pending</option>
         <option value="Solve">Solve</option>
         <option value="Close">Close</option>

     </select>
 </div>
</div>

<div class="form-group col-md-2">
    <label for="site location">   </label>

    <div class="input-group ">

        <button type="submit" class="btn m-3   bg-gradient-primary  btn-primary"  id="vendorgroupticket_filter" name="vendorgroupticket_filter">Filter
        </button>
    </div> 
</div> 
<!-- <div class="form-group col-md-1">
    <div class="input-group  col-md-3">
     <button type="submit" class="btn btn-warning">show</button>
 </div> 
</div>
-->

</div>
</form>


<!-- @else

<form role="form" method="post" action="/ticket/search">
  @csrf
  <div class="row float-right  pt-2 col-md-12 m-auto pl-4">
    <a class=" pt-2"> Show From :</a>
    <div class="input-group p-1 col-md-2   date" id="reservationdate" data-target-input="nearest">
        <input type="text" name="date_from" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{$date_from}}" />
        <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
        </div>
    </div>
    <a class=" pt-2"> To </a>

    <div class="input-group p-1 col-md-2 date" id="reservationdate" data-target-input="nearest">
        <input type="text" name="date_end" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{$date_end}}" />
        <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
        </div>
    </div>

    <div class="input-group p-1 col-md-3">
     <button type="submit" class="btn btn-warning">show</button>
 </div> 
</div>
</form>


@endif -->
</div>








<!-- /.card-header -->
<div class="card-body">
    <div class="table-responsive">
        <table id="table-vendorgroupticket-list" class="table table-bordered table-striped w-100 ">

            <thead >
               <tr>

                <th colspan="8"class="text-right border-0" >
                    <div class="row float-right">

                        <div class="bg-navy p-2 rounded-sm m-1  "><h5> <span name='total' id='total'>0 </span></h5>
                            <p>Total Ticket</p>
                        </div>
                        <div class="bg-success p-2 rounded-sm m-1  "><h5> <span name='open' id='open'>0 </span></h5>
                            <p>Open Ticket</p>
                        </div>


                        <div class="bg-secondary p-2 rounded-sm m-1 " ><h5> <span name='close' id='close'>0 </span></h5>
                            <div class="text-xs"id="fee_counter"> </div>
                            <p>Closed Ticket</p>
                        </div>

                        <div class="bg-warning p-2 rounded-sm m-1" ><h5> <span name='inprogress' id='inprogress'>0 </span></h5>
                            <p>Inprogress Ticket</p>
                        </div>
                        <div class="bg-primary p-2 rounded-sm m-1" ><h5> <span name='Pending' id='pending'>0 </span></h5>
                            <p>Pending Ticket</p>
                        </div>

                    </div>
                </th>




            </tr>
            <tr>
                <th scope="col">#</th>

                <th scope="col">Ticket ID</th>
                <th scope="col">Customer Name</th>
                <th scope="col">Status</th>
                <th scope="col">Category</th>

                <th scope="col">Title</th>
                <th scope="col">Assign to</th>
                <th scope="col">Schedule</th>
                {{--  <th scope="col">Action</th> --}}
            </tr>
        </thead>

    </table>
</div>
</div>
</div>

</section>

@endsection
@section('footer-scripts')
@include('script.vendorgroupticket_list')
@endsection 

