@extends('layout.main')
@section('title','Ticket List')
@section('content')
<section class="content-header">




  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">{{$title}} </h3>


      <br>

      <div class="row pt-2 pl-2">



        <div class="form-group col-md-2">
          <label for="site location">  Start Date </label>
          <div class="input-group mb-3">
            <div class="input-group p-1  date" id="reservationdate" data-target-input="nearest">
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
            <div class="input-group p-1 date" id="reservationdate" data-target-input="nearest">
              <input type="text" name="date_end" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-d')}}" />
              <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
        </div>


        <div class="form-group col-md-1">
          <label for="site location">  Status </label>
          <div class="input-group p-1">
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

          <button type="submit" class="btn m-3   bg-gradient-primary  btn-primary"  id="vendorticket_filter" name="vendorticket_filter">Filter
          </button>
        </div> 
      </div> 


    </div>
  </form>



</div>








<!-- /.card-header -->
<div class="card-body table-responsive">
  <table id="table-vendorticket-list" class="table table-bordered table-striped">

    <thead >
     <tr>

      <th colspan="12"class="text-right border-0" >
        <div class="row float-right">

          <div class="bg-navy p-2 rounded-sm  sm-2 m-1  "> <a name='total' id='total'>0 </a>
            Total
          </div>
          <div class="bg-success p-2 rounded-sm sm-2 m-1  "> <a name='open' id='open'>0 </a>
            Open
          </div>


          <div class="bg-secondary p-2 rounded-sm sm-2 m-1 " > <a name='close' id='close'>0 </a>
            Closed
          </div>

          <div class="bg-warning p-2 rounded-sm sm-2 m-1 " > <a name='inprogress' id='inprogress'>0 </a>
            Inprogress
          </div>
          <div class="bg-primary p-2 rounded-sm sm-2 m-1" > <a name='Pending' id='pending'>0 </a>
            Pending
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
    </tr>
  </thead>

</table>
</div>
</div>

</section>

@endsection
@section('footer-scripts')
@include('script.vendorticket_list') 
@endsection 

