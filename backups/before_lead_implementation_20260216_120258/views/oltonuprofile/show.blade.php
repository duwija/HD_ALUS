@extends('layout.main')
@section('title',' Distribution Point')
@section('maps')
@inject('olt', 'App\Olt')
{!! $map['js'] !!}
@endsection
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>



@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Show Detail Distribution Point </h3>
    </div>
    
    <div class="card-body">

      <div class=" col-md-12 card">
        <div class="">
          <div class="form-group col-md-4">
            <label class="font-weight-bold text-right">Name:</label>
            <span class="ml-2">{{$site->name}}</span>
          </div>
          <div class="form-group col-md-8">
            <label class="font-weight-bold">Location:</label>
            <span class="ml-2">{{$site->name}}</span>
          </div>
        <!-- </div>

          <div class="row mb-2"> -->
            <div class="flex">
              <div class="form-group col-md-4">

                <a href="https://www.google.com/maps/place/{{ $site->coordinate }}" target="_blank" class="btn btn-info btn-sm "><i  class="fa fa-map"> </i> Show in Google Maps </a>

              </div>
              <div class=" form-groupfloat-right m-2 " >

                <a href="/site/{{ $site->id}}/edit" class="btn btn-primary btn-sm "> Edit  </a>


                <form  action="/site/{{ $site->id }}" method="POST" class="d-inline site-delete" >
                  @method('delete')
                  @csrf

                  <button type="submit"  class="btn btn-danger btn-sm">  Delete  </button>
                </form>

              </div></div>
           <!--  <div class="form-group col-md-8">
              <label class="font-weight-bold">Security:</label>
              <span class="ml-2">{{$site->security}}</span>
            </div> -->
        <!-- </div>

          <div class="row mb-2"> -->

          </div>
        </div>











        <div class=" col-md-12 card card-primary card-outline">
          <div class="form-group">
            <label for="maps">Maps   </label>

            @if ($distpoint->coordinate == null)

            <br><a class="p-md-2">No Map set !!</a> 

            @else
            <div>
              {!! $map['html'] !!}
            </div>
           <!--  <div class="float-right " >
              <a href="https://www.google.com/maps/place/{{ $distpoint->coordinate }}" target="_blank" class="btn btn-info btn-sm "><i  class="fa fa-map"> </i> Show in Google Maps </a>
            </div> -->
            @endif

          </div>
        </div>






        <div>

          {{--   @foreach( $distpoint_chart as $distpoint_chart)

          @endforeach
        </div>
        --}}







      </div>
      <!-- /.card-body -->








    </div>
    <!-- /.card -->

    <!-- Form Element sizes -->


















  </div>

</section>

@endsection


@section('footer-scripts')
<script type="text/javascript">
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  $(document).ready(function() {
  // Loop untuk setiap baris dengan class 'ont-status'
    $('.ont-status').each(function() {
    // Ambil data dari elemen tersebut
      var id_onu = $(this).data('id-onu');
      var id_olt = $(this).data('id-olt');
      var ont_status_id = $(this).find('span').attr('id');

    // Pastikan id_onu, id_olt, dan ont_status_id tidak undefined
      if (id_onu && id_olt && ont_status_id) {
      // Panggil AJAX
        $.ajax({
          url: '/olt/ont_status',
          method: 'post',
          data: {
            id_onu: id_onu,
            id_olt: id_olt
          },
          success: function(data) {
          $('#' + ont_status_id).html(data); // Update HTML dengan data yang diterima
        },
        error: function(xhr, status, error) {
          console.log('Error: ' + error); // Handle error jika ada
        }
      });
      } else {
        console.log('Data attributes are missing or invalid');
      }
    });
  });

</script>
@endsection



