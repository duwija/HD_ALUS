@extends('layout.main')
@section('title','RUGI LABA')
@section('content')
<section class="content-header">








  <div class="card card-primary card-outline">
              <div class="card-header">
                <h3 class="card-title font-weight-bold">LAPORAN RUGI LABA </h3>
  

       
              </div>
          
              <div class="card-body">

@if (empty($date_from))
   <form role="form" method="post" action="/jurnal/rugilaba">
      @csrf
<div class="row pt-2 pl-2">
                <a class=" pt-2"> Show From :</a>
                    <div class="input-group p-1 col-md-2   date" id="reservationdate" data-target-input="nearest">
                        <input type="text" name="date_from" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-1')}}" />
                        <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                    </div>
               <a class=" pt-2"> To </a>
                 
                    <div class="input-group p-1 col-md-2 date" id="reservationdate" data-target-input="nearest">
                        <input type="text" name="date_end" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-d')}}" />
                        <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                    </div>
                    
         {{--  <select   name="akun" id="akun" class="input-group m-1 col-md-2" >
            <option value="0">All</option>
            @foreach ($akun as $id =>$name)
            <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
          </select> --}}
       

                    <div class="input-group p-1 col-md-3">
                       <button type="submit" class="btn btn-primary">show</button>
                    </div> 
                </div>
              </form>


@else
@endif
 <div class="text-center bg-primary p-2 ">
                {{$date_msg}}
              </div>
              <br/>
<table id="example1" class="table table-bordered table-striped">
  
  
<thead >
    <tr>
      <th scope="col">#</th>
     
      <th scope="col">Akun</th>
      
     {{--  <th scope="col">Debet</th>
       <th scope="col">Kredit</th> --}}
       <th scope="col">Saldo Debet</th>
       <th scope="col">Saldo Kredit</th>
      
        
    </tr>
  </thead>
@php
$number=0;
$sumdebet =0;
$sumkredit=0;
@endphp
@foreach( $nrugilaba as $nrugilaba)


 

@if ($nrugilaba->debet-$nrugilaba->kredit > 0)
 @php
 $sumdebet=$sumdebet+$nrugilaba->debet-$nrugilaba->kredit;
 @endphp
 <tr>
 <td scope="row">{{ $number=$number+1}}</td>

 <td colspan="">{{ $nrugilaba->name }}</td>
{{--  <td><strong>{{ number_format($nrugilaba->debet,0,',',',') }}</strong></td>
 <td><strong>{{ number_format($nrugilaba->kredit,0,',',',') }}</strong></td> --}}
 <td><strong>{{ number_format($nrugilaba->debet-$nrugilaba->kredit,0,',',',') }}</strong></td>
 <td><strong>0</strong></td>
</tr>
 @elseif ($nrugilaba->debet-$nrugilaba->kredit < 0)
 @php
 $sumkredit=$sumkredit+$nrugilaba->debet-$nrugilaba->kredit;
 @endphp
 <tr>
 <td scope="row">{{ $number=$number+1}}</td>
 <td colspan="">{{ $nrugilaba->name }}</td>
 <td><strong>0</strong></td>
 <td><strong>{{ number_format(abs($nrugilaba->debet-$nrugilaba->kredit),0,',',',') }}</strong></td>
 
{{--  <td><strong>{{ number_format($nrugilaba->debet,0,',',',') }}</strong></td>
 <td><strong>{{ number_format($nrugilaba->kredit,0,',',',') }}</strong></td> --}}
</tr> 
@endif




 {{--  <td><strong>{{ number_format($sum->debet-$sum->kredit,0,',',',') }}</strong></td> --}}

@endforeach
<tr class="bg-primary">
   <td colspan="" >{{ $number=$number+1}}</td>
     <td colspan="" >Total</td>
  <td><strong>{{ number_format(abs($sumdebet),0,',',',')}}</strong></td>
 <td><strong>{{ number_format(abs($sumkredit),0,',',',')}}</strong></td>

  </tr>

  @php
  $rl=abs($sumkredit)-abs($sumdebet);
  @endphp
  @if ($rl<=0)
  <tr  class="bg-primary">
    <td colspan="" >{{ $number=$number+1}}</td>
  <td colspan="" >Laba Rugi</td>
  <td><strong>0</strong></td>
 <td><strong>{{ number_format($rl,0,',',',')}}</strong></td>
</tr>
 @else
 <tr>
  <td colspan="" >9{{ $number=$number+1}}</td>
<td colspan="" >Laba Rugi</td>
 <td><strong>{{ number_format($rl,0,',',',')}}</strong></td>
 <td><strong>0</strong></td>
 
 </tr>
@endif
</table>
</div>
</div>













</section>

@endsection

    