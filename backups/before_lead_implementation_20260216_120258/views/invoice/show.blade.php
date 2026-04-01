@extends('layout.main')
@section('title','Invoice List')
@section('content')
@inject('invoicecalc', 'App\Invoice')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-3">
      <div class="col-sm-6">
        <h1 class="m-0">
          <i class="fas fa-file-invoice text-primary"></i> Invoice List
        </h1>
      </div>
      <div class="col-sm-6">
        <a href="/invoice/{{$customer->id}}/create" class="btn btn-primary btn-sm shadow-sm float-right">
          <i class="fas fa-plus-circle"></i> Create New Invoice
        </a>
      </div>
    </div>

    <!-- Customer Information Cards -->
    <div class="row">
      <!-- Left Column - Customer Info -->
      <div class="col-md-6 mb-3">
        <div class="card border-left-primary shadow-sm h-100">
          <div class="card-header bg-gradient-primary text-white">
            <h6 class="font-weight-bold mb-0">
              <i class="fas fa-user-circle"></i> Customer Information
            </h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <a href="/invoice/cst/{{$encryptedurl}}" target="_blank" class="btn btn-sm btn-outline-info">
                <i class="fas fa-link"></i> Encrypted URL
              </a>
            </div>
            <table class="table table-sm table-borderless mb-0">
              <tbody>
                <tr>
                  <td class="text-muted text-right" style="width: 20%">
                    CID / Name :
                  </td>
                  <td>
                    <a href="/customer/{{ $customer->id}}" class="font-weight-bold text-decoration-none">
                      <i class="fas fa-external-link-alt"></i> {{$customer->customer_id}} - {{$customer->name}}
                    </a>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted text-right">
                    Phone :
                  </td>
                  <td>
                    <a href="https://wa.me/{{$customer->phone}}" target="_blank" class="badge badge-success px-3 py-2">
                      <i class="fab fa-whatsapp"></i> {{$customer->phone}}
                    </a>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted text-right">
                    Address :
                  </td>
                  <td>
                    <a href="https://www.google.com/maps/place/{{ $customer->coordinate }}" target="_blank" class="text-info">
                      <i class="fas fa-map-marked-alt"></i> {{$customer->address}}
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right Column - Account Info -->
      <div class="col-md-6 mb-3">
        <div class="card border-left-info shadow-sm h-100">
          <div class="card-header bg-gradient-info text-white">
            <h6 class="font-weight-bold mb-0">
              <i class="fas fa-info-circle"></i> Account Details
            </h6>
          </div>
          <div class="card-body">
            <table class="table table-sm table-borderless mb-0">
              <tbody>
                <tr>
                  <td class="text-muted text-right" style="width: 20%">
                    Status :
                  </td>
                  <td>
                    <span class="badge badge-primary px-3 py-2">
                      {{$customer->status_name}}
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted text-right">
                    Plan :
                  </td>
                  <td class="font-weight-bold">{{$customer->plan_name}}</td>
                </tr>
                <tr>
                  <td class="text-muted text-right">
                    NPWP :
                  </td>
                  <td class="font-weight-bold">{{strtoupper($customer->npwp)}}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- Invoice List Table -->
    <div class="row">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header bg-gradient-success text-white">
            <h6 class="font-weight-bold mb-0">
              <i class="fas fa-list-alt"></i> Invoice Summary
            </h6>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="example1" class="table table-hover table-striped">
                <thead class="thead-light">
                  <tr>
                    <th scope="col" class="text-center">#</th>
                    <th scope="col"><i class="fas fa-hashtag"></i> INV Number</th>
                    <th scope="col"><i class="far fa-calendar-alt"></i> INV Date</th>
                    <th scope="col"><i class="fas fa-percentage"></i> Tax</th>
                    <th scope="col"><i class="fas fa-calendar-check"></i> Due Date</th>
                    <th scope="col"><i class="fas fa-money-bill-wave"></i> Total</th>
                    <th scope="col"><i class="fas fa-credit-card"></i> Payment Status</th>
                    <th scope="col"><i class="fas fa-clock"></i> Updated</th>
                    <th scope="col"><i class="fas fa-user"></i> Received By</th>
                    <th scope="col" class="text-center"><i class="fas fa-cog"></i> Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach( $suminvoice as $suminvoice)
                  <tr>
                    <th scope="row" class="text-center">{{ $loop->iteration }}</th>
                    <td><strong>{{ $suminvoice->number }}</strong></td>
                    <td>
                      <i class="far fa-calendar"></i> {{ $suminvoice->date }}
                    </td>
                    <td>{{ $suminvoice->tax }}%</td>
                    <td>
                      <i class="far fa-calendar-check"></i> {{ $suminvoice->due_date }}
                    </td>
                    <td><strong>Rp {{number_format($suminvoice->total_amount, 2, ',', '.')}}</strong></td>
                    <td>
                      @if($suminvoice->payment_status == 0)
                      <span class="badge badge-danger px-3 py-2">
                        <i class="fas fa-exclamation-circle"></i> Unpaid
                      </span>
                      @elseif($suminvoice->payment_status == 1)
                      <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-check-circle"></i> Paid
                      </span>
                      <br><small class="text-muted"><i class="far fa-calendar-check"></i> {{ $suminvoice->payment_date }}</small>
                      @elseif($suminvoice->payment_status == 2)
                      <span class="badge badge-secondary px-3 py-2">
                        <i class="fas fa-ban"></i> Cancel
                      </span>
                      @endif
                    </td>
                    <td>
                      <small class="text-muted">
                        <i class="far fa-clock"></i> {{ $suminvoice->updated_at }}
                      </small>
                    </td>
                    <td>
                      @if(is_numeric($suminvoice->updated_by))
                      <i class="fas fa-user"></i> {{ $suminvoice->user->name }}
                      @else
                      <i class="fas fa-user"></i> {{ $suminvoice->updated_by }}
                      @endif
                    </td>
                    <td class="text-center">
                      <a href="/suminvoice/{{ $suminvoice->tempcode }}" class="btn btn-info btn-sm shadow-sm" title="View Details">
                        <i class="fa fa-eye"></i> Show
                      </a>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</section>

@endsection