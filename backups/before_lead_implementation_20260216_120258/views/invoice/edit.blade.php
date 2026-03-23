@extends('layout.main')
@section('title','Edit Invoice')
@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title"><strong>INVOICE - Edit Invoice</strong></h3>
    </div>
    <div class="card-body row">
      <form role="form" method="post" action="/invoice/{{ $suminvoice->id }}/update">
        @csrf
        @method('PUT')
        
        <table class="table table-borderless col-md-6 table-sm">
          <tbody>
            <tr>
              <th class="text-right">CID / Name :</th>
              <td><a href="/customer/{{ $suminvoice->id }}"><strong>{{ $suminvoice->customer->customer_id }} ({{ $suminvoice->customer->name }})</strong></a></td>
            </tr>
            <tr>
              <th class="text-right">Phone :</th>
              <td><a href="https://wa.me/{{ $suminvoice->customer->phone }}">{{ $suminvoice->customer->phone }}</a></td>
            </tr>
            <tr>
              <th class="text-right">Address :</th>
              <td><a href="https://www.google.com/maps/place/{{ $suminvoice->customer->coordinate }}" target="_blank">{{ $suminvoice->customer->address }}</a></td>
            </tr>
          </tbody>
        </table>
        
        <table class="table table-borderless col-md-6 table-sm">
          <tbody>
            <tr>
              <th class="text-right">Status :</th>
              <td><strong>{{ $suminvoice->customer->status_name->name }}</strong></td>
            </tr>
            <tr>
              <th class="text-right">Plan :</th>
              <td><strong>{{ $suminvoice->customer->plan_name->name }}</strong></td>
            </tr>
            <tr>
              <th class="text-right">NPWP :</th>
              <td><strong>{{ strtoupper($suminvoice->customer->npwp) }}</strong></td>
            </tr>
          </tbody>
        </table>
        
        <div class="form-group col-md-2">
          <label>Due Date</label>
          <input type="date" name="due_date" class="form-control" value="{{ $suminvoice->due_date }}" />
        </div>
        
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Description</th>
              <th>Price</th>
              <th>Qty</th>
              <th>Total</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @php $subtotal = 0; @endphp
            @foreach ($invoice as $item)
            @php $subtotal += $item->amount * $item->qty; @endphp
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td><input type="text" name="items[{{ $item->id }}][description]" class="form-control" value="{{ $item->description }}" /></td>
              <td><input type="number" name="items[{{ $item->id }}][price]" class="form-control" value="{{ $item->amount }}" /></td>
              <td><input type="number" name="items[{{ $item->id }}][qty]" class="form-control" value="{{ $item->qty }}" /></td>
              <td>{{ number_format($item->amount * $item->qty, 0, ',', '.') }}</td>
              <td><a href="/invoice/item/{{ $item->id }}/delete" class="btn btn-danger btn-sm">Delete</a></td> 
            </tr>
            @endforeach
            <tr>
              <td colspan="4"><strong>Subtotal</strong></td>
              <td colspan="2">{{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
              <td colspan="4"><strong>Tax (%)</strong></td>
              <td colspan="2"><input type="number" name="tax" class="form-control" value="{{ $suminvoice->tax }}" /></td>
            </tr>
            <tr>
              <td colspan="4"><strong>Total</strong></td>
              <td colspan="2">{{ number_format($subtotal + ($subtotal * $suminvoice->tax / 100), 0, ',', '.') }}</td>
            </tr>
          </tbody>
        </table>
        
        <button type="submit" class="btn btn-primary">Update Invoice</button>
      </form>
    </div>
  </div>
</section>
@endsection
