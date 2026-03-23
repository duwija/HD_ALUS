@extends('layout.main')
@section('title','Customer List')
@section('content')
@inject('suminvoice', 'App\Suminvoice')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      {{"Ditemukan beberapa data yang sesuai, silahkan pilih salah satu untuk menampilkan Tagihan !"}}
    </div>

    
    @if ($customer== null)
      {{'data tidak ditemukan'}}
      @else
    <div class="card-body">
      <table id="" class="table table-bordered table-striped">

        <thead >
          <tr>
           
            <th scope="col">No</th>
            <th scope="col">CID / Kode Pelanggan</th>
            <th scope="col">Nama</th>
            <th scope="col">Alamat</th>
           <th scope="col">Status</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
         @foreach( $customer as $customer)
         <tr>
          <th scope="row">{{ $loop->iteration }} </th>

          <td><a class="badge badge-primary text-white">{{ $customer->customer_id }}</a></td>
          <td>{{ $customer->name }} </td>
          <td> <a style="font-size: 13px"> {{ $customer->address }}</a></td>

      
        


@php

             if ($customer->status_name->name == 'Active')
        $badge_sts = "badge-success";
      elseif ($customer->status_name->name == 'Inactive')
         $badge_sts = "badge-secondary";
       elseif ($customer->status_name->name == 'Block')
         $badge_sts = "badge-danger";
       elseif ($customer->status_name->name == 'Company_Properti')
         $badge_sts = "badge-primary";
       else
         $badge_sts = "badge-warning";

      @endphp
     



          <td class="text-center"><a class="badge text-white {{$badge_sts}}">{{ $customer->status_name->name }}</a></td>

       
        
          <td >
            <div class="float-right " >
            


              <form  action="/payment/show" method="POST">
                @method('post')
                @csrf
                <input type="hidden" name="filter" value="customer_id">
                <input type="hidden" name="parameter" value="{{$customer->customer_id}}">
                <button title="Tampilkan" type="submit"  class="btn btn-success btn-sm"> Tampilkan </button>
              </form>

          
            </div>
          </td>

        </tr>
        @endforeach
  

      </tbody>
    </table>
  </div>
  @endif
</div>

</section>

@endsection
