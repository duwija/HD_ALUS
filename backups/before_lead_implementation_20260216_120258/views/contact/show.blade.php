@extends('layout.main')
@section('title', 'merchant')

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold">Show Detail Merchant</h3>
    </div>

    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div class="card mb-3">
            <div class="card-header">
              <h5 class="card-title">Merchant Details</h5>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="form-group col-md-7">

                  <p><strong>Name : </strong> {{ $merchant->name }}
                    <br><strong>Contact Name : </strong> {{ $merchant->contact_name }}
                    <br><strong>Phone : </strong> {{ $merchant->phone }}
                    <br><strong>Payment Point : </strong> {{ $merchant->payment_point == 1 ? 'Yes' : 'No' }}
                    <br><strong>Address: </strong> {{ $merchant->address }}
                    <br><strong>Description: </strong> {{ $merchant->description }}
                  </div>
                  <div id="akun_bank" class="form-group col-md-5">

                    <div class="info-box bg-success row">
                      <div class="form-group col-md-12">
                       @if($merchant->akun_name?->name && $merchant->akun_name?->akun_code)
                       {{ $merchant->akun_name->akun_code }} | {{ $merchant->akun_name->name }}
                       @else
                       None
                       @endif
                     </div>
                     
                     <div class="info-box-content text-lg form-group col-md-12" id="sum_akun">


                     </div>
                   </div>

                 </div>
               </div>

               <div class="card-footer">

                <a href="/merchant/{{ $merchant->id }}/edit" class="btn btn-primary btn-sm ">  Edit  </a>


                <form  action="/merchant/{{ $merchant->id }}" method="POST" class="d-inline item-delete" >
                  @method('delete')
                  @csrf

                  <button type="submit"  class="btn btn-danger btn-sm float-right">  Delete  </button>
                </form>

              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card mb-3">
            <div class="card-header">
              <h5 class="card-title">Customer Information</h5>
            </div>
            <div class="card-body">
              <div id="merchant-info">
                <div id="spinner" style="display:none; text-align: center;">
                  <p>Loading...</p>
                  <span class='fa-stack fa-lg'>
                    <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>



        <div class="col-md-12">
          <div class="card mb-3">
            <div class="card-header">
              <h5 class="card-title">Customer List</h5>
            </div>
            <div class="card-body">



              <div class="row pt-2 pl-6">


               <div class="form-group col-md-2">
                <label for="site location">  Filter By </label>
                <div class="input-group mb-3">
                  <select name="filter" id="filter" class="form-control">

                    <option value="name">Name</option>
                    <option value="customer_id">Customer ID</option>
                    <option value="address">Address</option>
                    <option value="phone">Phone</option>
                    <option value="id_card">Id Card</option>
                    <option value="billing_start">Billing Start</option>
                    <option value="isolir_date">Isolir Date</option>

                  </select>
                </div>
              </div>
              <input type="hidden" name="id_merchant" id="id_merchant" value="{{ $merchant->id }}">
              <div class="form-group col-md-2">
                <label for="site location">  Parameter </label>
                <div class="input-group mb-3">
                  <input class="form-control" type="text" id="parameter" name="parameter" placeholder="Leave blank for all">
                </div>
              </div>

              <div class="form-group col-md-2">
                <label for="site location">  Status </label>
                <div class="input-group mb-3">
                  <select name="id_status" id="id_status" class="form-control">
                   <option value="">All</option> 
                   @foreach ($status as $id => $name)
                   <option value="{{ $id }}">{{ $name }}</option>
                   @endforeach
                 </select>
               </div>
             </div>
             <div class="form-group col-md-2">
              <label for="site location">  Plan </label>
              <div class="input-group mb-3">
                <select name="id_plan" id="id_plan" class="form-control">
                 <option value="">All</option> 
                 @foreach ($plan as $id => $name)
                 <option value="{{ $id }}">{{ $name }}</option>
                 @endforeach
               </select>
             </div>
           </div>
           <div class="form-group col-md-2">
            <label for="site location">   </label>

            <div class="input-group p-1 col-md-3">
             <button type="button" id="customer_filter" name="customer_filter" class="btn btn-warning">Filter</button>
           </div> 
         </div>
       </div>

       <!--  <div class="col-md-4">
          <div class="card mb-3">
            <div class="card-header">
              <h5 class="card-title">Customer Grup by Plan</h5>
            </div>
            <div class="card-body"> -->
              <table id="table-plan-group" class="table table-bordered table-striped">
               <thead >
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">PLan Name</th>
                  <th scope="col">Customer Count</th>
                  
                  <!-- <th scope="col">Action</th> -->
                </tr>
              </thead>
            </table>
            <!-- </div>
          </div>
        </div> -->




        <table id="table-customer" class="table table-bordered table-striped">

          <thead >
            <tr>
              <th scope="col">#</th>
              <th scope="col">Customer Id</th>
              <th scope="col">Name</th>
              <th scope="col">Address</th>
              <th scope="col">Merchant</th>
              <th scope="col">Plan</th>
              <th scope="col">Billing Start</th>
              <th scope="col">Isolir Date</th>
              <th scope="col">Status</th>
              <!-- <th scope="col">Select</th> -->
              <th scope="col">Invoice</th>
              <!-- <th scope="col">Action</th> -->
            </tr>
          </thead>



        </select>

      </table>

    </div>
  </div>

       <!--    <div id="merchant-onu-info">

       </div> -->

     </div>
   </div>
 </div>
</div>
</div>
</div>
</section>

<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

</script> -->

@endsection
@section('footer-scripts')
@include('script.merchant')

@endsection 
