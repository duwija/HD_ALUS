@extends('layout.main')
@section('title','Invoice created Report')
@section('content')
@inject('invoicecalc', 'App\Invoice')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Invoice Report </h3>
     
    </div>
    <div class="card-body row">

   
{!! nl2br(e($logs)) !!}



</div>






</div>

</section>



@endsection
