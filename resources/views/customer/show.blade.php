@extends('layout.main')
@section('title',' Customer Detail')

@inject('distrouter', 'App\Distrouter')

@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header bg-primary">
      <h3 class="card-title font-weight-bold">Show Detail Customer</h3>
    </div>
    
    <div class="card-body">
      <div class="row">
        <table class="table table-borderless col-md-6 table-sm">
          <tbody>


            <tr>
              <th style="width: 30%" class="text-right">Customer ID :</th>
              @php

              if ($customer->status_name->name == 'Active')
              $btn_sts = "btn-success";
              elseif ($customer->status_name->name == 'Inactive')
              $btn_sts = "btn-secondary";
              elseif ($customer->status_name->name == 'Block')
              $btn_sts = "btn-danger";
              elseif ($customer->status_name->name == 'Company_Properti')
              $btn_sts = "btn-primary";
              else
              $btn_sts = "btn-warning";

              @endphp
              <input type="hidden" name="cid_copy" id="cid_copy" value="{{$customer->customer_id}}">
              <td>
                <div class="{{$btn_sts}} badge btn-sm p-2 mr-1">{{$customer->customer_id}}
                  <strong> | {{$customer->status_name->name}}</strong>
                </div>
                <i class="fa border-secondary fa-copy btn btn-sm" title="Copy Customer Id" onclick="copy_text()"></i>
              </td>
              </tr>
              <tr>
                <th style="width: 25%; " class="text-right">User PPPOE :</th>
                @if ($countpppoe > 1)
                <td>
                  <a class="badge badge-danger">
                    {{ $customer->pppoe }} | pppoe-conflict
                  </a>
                </td>
                @else
                <td>{{ $customer->pppoe }}</td>
                @endif
              </tr> 
              <tr>
                <th style="width: 25%; " class="text-right">Password :</th>
                <td>{{$customer->password}}</td>
              </tr>
              <tr>
                <th style="width: 31%" class="text-right">Customer Name :</th>
                <td>{{$customer->name}}</td>

              </tr>

              <tr>
                <th style="width: 31%" class="text-right">Contact Name : </th>
                <td colspan="">{{$customer->contact_name}}</td>

              </tr>
              <tr>
                <th style="width: 30%" class="text-right">Merchant : </th>
                <td colspan="">
                  @if(!empty($customer->merchant_name) && !empty($customer->merchant_name->name))
                  <a href="/merchant/{{$customer->merchant_name->id}}" class="bg-info badge">{{ $customer->merchant_name->name }}</a>
                  @else
                  <span>No Merchant</span> <!-- You can change this to whatever default text you want -->
                  @endif
                </td>

              </tr>
              <tr>
                <th style="width: 30%" class="text-right">Date of Birth : </th>
                <td colspan="">{{$customer->date_of_birth}}</td>

              </tr>
              <tr>
                <th style="width: 30%" class="text-right">Phone : </th>
                <td colspan=""><a href="https://wa.me/{{$customer->phone}}"> {{$customer->phone}}</a></td>

              </tr>
              <tr>
                <th style="width: 30%" class="text-right">Email : </th>
                <td colspan="">
                  @if($customer->email)
                    <a href="mailto:{{$customer->email}}">{{$customer->email}}</a>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              </tr>
              <tr>
                <th style="width: 30%" class="text-right">Address : </th>
                <td colspan="">{{$customer->address}}</td>

              </tr>
              <tr>
                <th style="width: 25%" class="text-right">Sales :</th>

                @php

                if ($customer->id_sale == 0)
                $sale_name = "none";

                else
                $sale_name = $customer->sale_name->name;

                @endphp

                <td colspan="2">{{$sale_name}}</td>

              </tr>
              <tr>
                <th style="width: 25%" class="text-right">Note :</th>
                <td colspan="2">{{$customer->note}}</td>

              </tr>
              <tr>
                <th style="width: 25%" class="text-right">Tags :</th>
                <td colspan="2">
                  @forelse($customerTags as $tagId => $tagName)
                    <span class="badge badge-info mr-1">{{ $tagName }}</span>
                  @empty
                    <span class="text-muted">-</span>
                  @endforelse
                  <button type="button" class="btn btn-xs btn-outline-secondary ml-2 py-0 px-2"
                    data-toggle="modal" data-target="#modal-customer-tags" title="Edit Tags">
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                </td>
              </tr>
            </tr>



          </tbody>
        </table>

        <table class="table table-borderless col-md-6 table-sm">

          <tbody>

            <tr class="col-md-6">

              <th style="width: 30%" class="text-right">Notif by :</th>
              <td>
                @php
                $icon = '';
                $label = '';

                switch ($customer->notification) {
                  case 0:
                  $icon = '<i class="fas fa-ban text-muted"></i>';
                  $label = 'None';
                  break;
                  case 1:
                  $icon = '<i class="fab fa-whatsapp text-success"></i>';
                  $label = 'WhatsApp';
                  break;
                  case 2:
                  $icon = '<i class="fas fa-envelope text-primary"></i>';
                  $label = 'Email';
                  break;
                  case 3:
                  $icon = '<i class="fas fa-mobile-alt text-warning"></i>';
                  $label = 'Mobile App (FCM)';
                  break;
                  default:
                  $icon = '<i class="fas fa-ban text-muted"></i>';
                  $label = 'None';
                  break;
                }
                @endphp
                {!! $icon !!} {{ $label }}
              </td>


              <tr>
               <th style="width: 30%" class="text-right">Id Card :</th>
               <td>{{$customer->id_card}}</td>

             </tr>

             <tr>
               <th style="width: 25%" class="text-right">Plan :</th>
               <td>{{$customer->plan_name->name}} ( Rp. {{number_format($customer->plan_name->price, 0, ',', '.')}} )</td>
             </tr>

             @if($customer->addons && $customer->addons->count() > 0)
             <tr>
               <th style="width: 25%" class="text-right">Add-on :</th>
               <td>
                 @php $addonsTotal = 0; @endphp
                 @foreach($customer->addons as $addon)
                   @php $addonsTotal += $addon->price; @endphp
                   <span class="badge badge-primary mr-1" style="font-size:0.82em;">
                     <i class="fas fa-puzzle-piece mr-1"></i>{{ $addon->name }}
                     <span class="ml-1 badge badge-light text-dark">+Rp {{ number_format($addon->price, 0, ',', '.') }}</span>
                   </span>
                 @endforeach
                 <br><small class="text-muted mt-1 d-inline-block">Total add-on: <strong class="text-success">Rp {{ number_format($addonsTotal, 0, ',', '.') }}</strong></small>
               </td>
             </tr>
             @endif
             <tr>
               <th style="width: 25%" class="text-right">Billing Start :</th>
               <td><a class="bg-info badge"> {{$customer->billing_start}} </a>  <a class="bg-info badge"> Isolir Date : {{$customer->isolir_date}} </a> </td>



             </tr>
             <tr>

              <th style="width: 25%" class="text-right">On Router Status :</th>

              <td>
               <div id="mikrotik-status" style="min-height:32px;display:flex;align-items:center;flex-wrap:wrap;gap:4px;"><div class="d-flex align-items-center">
                <i class="fas fa-spinner fa-spin text-primary mr-2"></i> 
                <span>Loading router status...</span>
              </div></div>


            </td>

          </tr>
          <tr>
           <th style="width: 25%" class="text-right">Distribution Router :</th>
           <td colspan="2">
            @if ( empty($customer->distrouter->name))

            {{'-'}}
            @else

            <a href="/distrouter/{{ $customer->distrouter->id}}"  class="btn btn-primary btn-sm " target="_blank">{{ $customer->distrouter->name }} | {{ $customer->distrouter->ip }}</a>



            @endif





          </td>

        </tr>
        <tr>

          <th style="width: 25%" class="text-right">OLT | ODP :</th>
          <td colspan="2">
           @if ( empty($customer->distpoint_name->name))

           {{'-'}}
           @else
           <a class="btn btn-sm bg-primary" href="/olt/{{ $customer->olt_name->id ?? '-' }}" >
             {{ $customer->olt_name->name ?? '-' }}
           </a>
           @endif

           @if ( empty($customer->distpoint_name->name))

           {{'-'}}
           @else
           <a class="btn btn-sm bg-primary" href="/distpoint/{{ $customer->distpoint_name->id }}" >
            {{ $customer->distpoint_name->name ?? 'none' }}
          </a>
          @endif


        </td>

      </tr>




      <tr>
        <th style="width: 25%" class="text-right">Ticket :</th>
        <td colspan="2"><a href="/ticket/{{ $customer->id }}/create" title="device" class="btn mt-1 btn-success btn-sm  mr-2"> <i class="fas fa-ticket-alt"></i> Create Ticket </a><a href="/ticket/view/{{ $customer->id }}" title="device" class="btn btn-primary btn-sm mt-1 "> <i class="fas fa-ticket-alt"></i> View Ticket @php $openTickets = \App\Ticket::where('id_customer', $customer->id)->where('status', '!=', 'Close')->count(); @endphp @if($openTickets > 0) <span class="badge badge-danger ml-1">{{ $openTickets }}</span> @endif </a></td>

      </tr>
      <tr>
        <th style="width: 25%" class="text-right">Invoice :</th>
        <td colspan="2"><a href="/invoice/{{ $customer->id }}/create" title="device" class="btn btn-success btn-sm  mr-1 mt-1"> <i class="fas fa-plus-circle"></i> Create Invoice </a><a href="/invoice/{{ $customer->id }}" title="device" class="btn mt-1 btn-primary btn-sm  mr-1 mt-1"> <i class="fas fa-file-invoice"></i> View Invoice @php $unpaidInv = \App\Suminvoice::where('id_customer', $customer->id)->where('payment_status', 0)->count(); @endphp @if($unpaidInv > 0) <span class="badge badge-warning ml-1">{{ $unpaidInv }}</span> @endif </a><a href="/customer/{{ $customer->id }}/jurnal" title="device" class="btn mt-1 btn-primary btn-sm mr-1 mt-1"> <i class="fas fa-book"></i> jurnal </a></td>

      </tr>
      <tr>
        <th style="width: 25%" class="text-right">Monitor Tools :</th>
        <input type="hidden" name="ip"  id="ip" value="{{ $customer->distrouter ? $customer->distrouter->ip : '' }}">
        <input type="hidden" name="user"  id="user" value="{{ $customer->distrouter ? $customer->distrouter->user : '' }}">
        <input type="hidden" name="password"  id="password" value="{{ $customer->distrouter ? $customer->distrouter->password : '' }}">
        <input type="hidden" name="port"  id="port" value="{{ $customer->distrouter ? $customer->distrouter->port : '' }}">
        <input type="hidden" name="interface"  id="interface" value="<pppoe-{{$customer->pppoe}}>">
          <td colspan="2">
            {{-- Default tombol dalam keadaan disabled --}}
            <button type="button" id="btn-traffic"
            class="btn mb-1 btn-secondary btn-sm pb-1" 
            data-toggle="modal" data-target="#modal-monitor"
            disabled>
            <i class="fas fa-chart-line"></i> Traffic
          </button>

          <button type="button" id="createTunnelBtn"
          class="btn mb-1 btn-secondary btn-sm pb-1"
          title="Create Tunnel" disabled>
          <i class="fas fa-plug"></i> web
        </button>

        @if (!empty($customer->id_onu))
        <button type="button" name="btn_onu_detail" id="btn_onu_detail"
        class="btn mb-1 bg-info btn-sm pb-1" data-toggle="modal" data-target="#modal_onu_detail">
        <i class="fas fa-sun"></i> {{ $customer->id_onu }}
      </button>

      <button type="button" name="btn_onu_reboot" id="btn_onu_reboot"
      class="btn mb-1 bg-warning btn-sm pb-1" data-toggle="modal" data-target="#modal_reboot">
      <i class="fas fa-sync-alt"></i> reboot
    </button>
    @endif
  </td>


</tr>


<tr>
  <th>
  </th>
  <td>
   <input type="hidden" name="id_olt"  id="id_olt" value="{{$customer->id_olt}}">
   <input type="hidden" name="id_onu"  id="id_onu" value="{{$customer->id_onu}}">
   <div id="ont_status" style="min-height:28px;display:flex;align-items:center;flex-wrap:wrap;gap:4px;"></div>
   <a id="ont_detail"></a>

 </td>
</tr>
</tbody>
</table>
<div class="card-footer col-md-12 mt-5 mb-5">
 <a href="/customer/{{ $customer->id }}/edit" title="edit" class="btn btn-primary btn-sm "> <i class="fa fa-edit">  </i> Edit </a>
 <a href="/customer/log/{{ $customer->id }}" title="log" class="btn btn-info btn-sm "> <i class="fa fa-history">  </i> log </a>
 <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modal-reset-password" title="Reset Password Portal"> <i class="fas fa-key"></i> Reset Password </button>
 <button type="button" class="{{-- float-right  --}}btn bg-success btn-sm" data-toggle="modal" data-target="#modal-wa"> <i class="fab fa-whatsapp">  </i> WA</button>
 @if($customer->fcm_token || $customer->app_token)
 <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-app-logout" title="Logout Aplikasi Mobile">
   <i class="fas fa-mobile-alt mr-1"></i><i class="fas fa-sign-out-alt mr-1"></i> Logout App
 </button>
 @else
 <button type="button" class="btn btn-secondary btn-sm" disabled title="Customer tidak login di aplikasi">
   <i class="fas fa-mobile-alt mr-1"></i> Tidak Login App
 </button>
 @endif

 @if (in_array($customer->status_name->name, ['Inactive', 'Potensial']))
 <button type="button" class="btn btn-danger btn-sm float-right"
   onclick="confirmDeleteCustomer({{ $customer->id }})">
   <i class="fa fa-times"></i> Delete
 </button>
@else
<button title="Delete" type="button" disabled class="btn btn-danger btn-sm float-right"> <i class="fa fa-times"> </i> Delete </button>
@endif

<!-- Modal Konfirmasi Delete Customer -->
<div class="modal fade" id="modal-confirm-delete" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title text-white"><i class="fas fa-exclamation-triangle mr-2"></i>Konfirmasi Hapus Customer</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="delete-modal-body">
        <p>Memuat...</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <form id="form-delete-customer" action="/customer/{{ $customer->id }}" method="POST" class="d-inline">
          @method('delete')
          @csrf
          <button type="submit" id="btn-confirm-delete" class="btn btn-danger">
            <i class="fa fa-times mr-1"></i>Ya, Hapus
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Reset Password Portal Customer -->
<div class="modal fade" id="modal-reset-password" tabindex="-1" aria-labelledby="resetPasswordLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title text-dark" id="resetPasswordLabel"><i class="fas fa-key mr-2"></i>Reset Password Portal Customer</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form action="/customer/{{ $customer->id }}/reset-password" method="POST">
        @csrf
        <div class="modal-body">
          <p class="text-muted mb-3">Reset password login portal untuk customer <strong>{{ $customer->name }}</strong> ({{ $customer->customer_id }}).</p>
          <div class="form-group">
            <label for="new_password">Password Baru <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="text" id="new_password" name="new_password" class="form-control"
                     placeholder="Masukkan password baru..." minlength="6" maxlength="64" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary" id="btn-generate-password" title="Generate password acak">
                  <i class="fas fa-random"></i>
                </button>
              </div>
            </div>
            <small class="text-muted">Minimal 6 karakter. Klik <i class="fas fa-random"></i> untuk generate password acak.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">
            <i class="fas fa-key mr-1"></i>Reset Password
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Logout Aplikasi Mobile -->
<div class="modal fade" id="modal-app-logout" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title text-white">
          <i class="fas fa-mobile-alt mr-2"></i><i class="fas fa-sign-out-alt mr-1"></i>Logout Aplikasi Mobile
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p>Anda akan me-logout <strong>{{ $customer->name }}</strong> ({{ $customer->customer_id }}) dari aplikasi mobile.</p>
        <p class="text-muted mb-0"><small>App token &amp; FCM token akan dihapus dari <strong>semua akun</strong> dengan email yang sama. Customer harus login ulang di aplikasi.</small></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <form action="/customer/{{ $customer->id }}/app-logout" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-sign-out-alt mr-1"></i>Ya, Logout App
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

</div>
</div>

<!-- Lead Information Card (only for Potensial status) -->
@if($customer->id_status == 1)
@php
  $isLost        = !empty($customer->lost_at);
  $totalSteps    = $customerSteps->count();
  $currentStepId = $customer->current_step_id ?? null;
  $currentIndex  = $customerSteps->search(fn($s) => $s->id == $currentStepId);
  $stepsPassed   = ($currentIndex !== false) ? $currentIndex : 0;
  $displayPct    = ($totalSteps > 0) ? round($stepsPassed / $totalSteps * 100) : 0;
  $progressPct   = ($currentIndex !== false && $totalSteps > 1)
                   ? round(($currentIndex / ($totalSteps - 1)) * 100) : 0;
  if ($currentIndex !== false && $currentIndex === $totalSteps - 1) { $displayPct = 100; $progressPct = 100; }
  $pctColor      = $displayPct >= 100 ? '#28a745' : ($displayPct >= 50 ? 'var(--brand)' : '#ffc107');
  $currentStep   = $customerSteps->firstWhere('id', $currentStepId);
@endphp
<div class="col-md-12 mt-3">
<div class="li-card" style="
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
">
  {{-- ── Top accent bar ── --}}
  <div style="height:4px; background: {{ $isLost ? '#dc3545' : ($displayPct >= 100 ? '#28a745' : 'var(--brand)') }};"></div>

  {{-- ── Header ── --}}
  <div class="d-flex align-items-center flex-wrap px-3 pt-3 pb-2" style="gap:8px; border-bottom:1px solid var(--border);">
    <div class="d-flex align-items-center flex-fill" style="gap:8px; min-width:0;">
      <div style="width:34px;height:34px;border-radius:50%;background:{{ $isLost ? 'rgba(220,53,69,.15)' : 'rgba(163,48,28,.12)' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="fas fa-user-tie" style="color:{{ $isLost ? '#dc3545' : 'var(--brand)' }};font-size:14px;"></i>
      </div>
      <div>
        <div class="font-weight-bold" style="color:var(--text-primary);font-size:0.95rem;">Lead Information</div>
        @if($isLost)
          <span style="font-size:0.75rem;color:#dc3545;font-weight:600;"><i class="fas fa-times-circle mr-1"></i>GAGAL / LOST</span>
        @elseif($totalSteps > 0 && $currentStep)
          <span style="font-size:0.75rem;color:var(--text-muted);">Step aktif: <strong style="color:var(--text-primary);">{{ $currentStep->name }}</strong></span>
        @else
          <span style="font-size:0.75rem;color:var(--text-muted);">Status Potensial</span>
        @endif
      </div>
      @if(!$isLost && $totalSteps > 0)
      <div class="ml-2" style="flex-shrink:0;">
        <span style="font-size:0.72rem;font-weight:700;color:{{ $pctColor }};background:{{ $displayPct >= 100 ? 'rgba(40,167,69,.12)' : ($displayPct >= 50 ? 'rgba(163,48,28,.1)' : 'rgba(255,193,7,.15)') }};border-radius:20px;padding:2px 10px;">{{ $displayPct }}%</span>
      </div>
      @endif
    </div>
    <div class="d-flex flex-wrap" style="gap:6px;flex-shrink:0;">
      @if($isLost)
        <form method="POST" action="/customer/{{ $customer->id }}/reopen-lead" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Buka kembali lead ini?')">
            <i class="fas fa-redo"></i> Reopen Lead
          </button>
        </form>
      @else
        <button type="button" class="btn btn-sm" style="background:rgba(23,162,184,.12);color:#17a2b8;border:1px solid rgba(23,162,184,.3);border-radius:6px;" data-toggle="modal" data-target="#modal-update-lead">
          <i class="fas fa-edit"></i> Update Progress
        </button>
        <button type="button" class="btn btn-sm" style="background:rgba(220,53,69,.1);color:#dc3545;border:1px solid rgba(220,53,69,.3);border-radius:6px;" data-toggle="modal" data-target="#modal-mark-lost">
          <i class="fas fa-times-circle"></i> Tandai Gagal
        </button>
        <button type="button" class="btn btn-sm" style="background:rgba(40,167,69,.1);color:#28a745;border:1px solid rgba(40,167,69,.3);border-radius:6px;" data-toggle="modal" data-target="#modal-convert">
          <i class="fas fa-check-circle"></i> Convert to Active
        </button>
      @endif
    </div>
  </div>

  <div class="px-3 pt-3 pb-2">

    {{-- ── Lost banner ── --}}
    @if($isLost)
    <div style="background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.25);border-radius:8px;padding:10px 14px;margin-bottom:14px;">
      <div class="d-flex align-items-center" style="gap:8px;">
        <i class="fas fa-ban" style="color:#dc3545;font-size:16px;"></i>
        <div>
          <div style="font-weight:700;color:#dc3545;font-size:0.87rem;">Lead ini ditandai GAGAL</div>
          <div style="font-size:0.8rem;color:var(--text-secondary);">
            Alasan: <strong>{{ $customer->lost_reason }}</strong>
            &nbsp;·&nbsp;{{ \Carbon\Carbon::parse($customer->lost_at)->format('d M Y H:i') }}
          </div>
          @if($customer->lost_notes)
            <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;">{{ $customer->lost_notes }}</div>
          @endif
        </div>
      </div>
    </div>
    @endif

    {{-- ── Workflow stepper ── --}}
    @if($totalSteps > 0)
    <div class="mb-3">
      <div class="d-flex align-items-center mb-2" style="gap:6px;">
        <i class="fas fa-stream" style="color:var(--brand);font-size:12px;"></i>
        <span style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;">Workflow Progress</span>
        <button type="button" class="btn btn-xs ml-auto" style="font-size:0.72rem;padding:2px 8px;border-radius:12px;border:1px solid var(--border);color:var(--text-muted);background:var(--bg-surface-2);" data-toggle="modal" data-target="#modal-customer-workflow">
          <i class="fas fa-edit"></i> Edit
        </button>
      </div>
      {{-- Progress bar --}}
      <div style="height:6px;background:var(--bg-surface-2);border-radius:3px;margin-bottom:10px;overflow:hidden;">
        <div style="height:100%;width:{{ $progressPct }}%;background:{{ $progressPct >= 100 ? '#28a745' : 'var(--brand)' }};border-radius:3px;transition:width .4s ease;"></div>
      </div>
      {{-- Step dots --}}
      <div class="d-flex" style="overflow-x:auto;gap:0;padding-bottom:2px;">
        @foreach($customerSteps as $idx => $step)
          @php
            $dotState = ($currentStepId == $step->id) ? 'active'
              : ($currentIndex !== false && $idx < $currentIndex ? 'done' : 'pending');
            $dotBg    = $dotState === 'active' ? 'var(--brand)'
              : ($dotState === 'done' ? '#28a745' : 'var(--bg-surface-2)');
            $dotColor = $dotState === 'pending' ? 'var(--text-muted)' : '#fff';
            $dotBorder = $dotState === 'active' ? 'var(--brand)' : ($dotState === 'done' ? '#28a745' : 'var(--border)');
          @endphp
          <div class="text-center" style="flex:1;min-width:0;position:relative;{{ $idx < $totalSteps-1 ? 'margin-right:0;' : '' }}">
            {{-- connector --}}
            @if($idx < $totalSteps - 1)
            <div style="position:absolute;top:12px;left:50%;right:-50%;height:2px;background:{{ $idx < $currentIndex ? '#28a745' : 'var(--border)' }};z-index:0;"></div>
            @endif
            {{-- dot --}}
            <div style="width:24px;height:24px;border-radius:50%;background:{{ $dotBg }};border:2px solid {{ $dotBorder }};display:inline-flex;align-items:center;justify-content:center;position:relative;z-index:1;">
              @if($dotState === 'done')
                <i class="fas fa-check" style="font-size:9px;color:#fff;"></i>
              @elseif($dotState === 'active')
                <div style="width:8px;height:8px;border-radius:50%;background:#fff;"></div>
              @else
                <div style="width:6px;height:6px;border-radius:50%;background:var(--border);"></div>
              @endif
            </div>
            {{-- label --}}
            <div style="font-size:0.62rem;color:{{ $dotState === 'active' ? 'var(--brand)' : 'var(--text-muted)' }};font-weight:{{ $dotState === 'active' ? '700' : '400' }};margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;padding:0 2px;">{{ $step->name }}</div>
            @if($step->selected_at)
              <div style="font-size:0.56rem;color:var(--text-muted);line-height:1.2;">{{ $step->selected_at->format('d/m H:i') }}</div>
            @endif
          </div>
        @endforeach
      </div>
      {{-- Step summary --}}
      <div class="d-flex justify-content-between align-items-center mt-2">
        <span style="font-size:0.75rem;color:var(--text-muted);">
          @if($currentIndex !== false)
            <i class="fas fa-check-double" style="color:#28a745;margin-right:3px;"></i>{{ $stepsPassed }} dari {{ $totalSteps }} langkah selesai
          @elseif($totalSteps > 0)
            <i class="fas fa-clock" style="color:var(--text-muted);margin-right:3px;"></i>Belum ada step dipilih
          @endif
        </span>
        @if($currentStep && $currentStep->selected_at)
          <span style="font-size:0.72rem;color:var(--text-muted);"><i class="fas fa-history mr-1"></i>Diubah {{ $currentStep->selected_at->diffForHumans() }}</span>
        @endif
      </div>
    </div>
    @endif

    {{-- ── Info grid ── --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:12px;">
      {{-- Lead Source --}}
      <div style="background:var(--bg-surface-2);border-radius:8px;padding:10px 12px;min-width:0;">
        <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;"><i class="fas fa-tags mr-1"></i>Lead Source</div>
        @if($customer->lead_source)
          <div style="font-size:0.85rem;font-weight:600;color:var(--text-primary);">{{ $customer->lead_source }}</div>
        @else
          <div style="font-size:0.85rem;color:var(--text-muted);">-</div>
        @endif
      </div>
      {{-- Expected Close --}}
      @php
        $closeDate    = $customer->expected_close_date ? \Carbon\Carbon::parse($customer->expected_close_date) : null;
        $isOverdue    = $closeDate && !$isLost && $closeDate->isPast();
      @endphp
      <div style="background:var(--bg-surface-2);border-radius:8px;padding:10px 12px;min-width:0;">
        <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;"><i class="fas fa-calendar-alt mr-1"></i>Expected Close</div>
        @if($closeDate)
          <div style="font-size:0.85rem;font-weight:600;color:{{ $isOverdue ? '#dc3545' : 'var(--text-primary)' }};">
            {{ $closeDate->format('d M Y') }}
            @if($isOverdue) <span style="font-size:0.7rem;">(overdue)</span> @endif
          </div>
        @else
          <div style="font-size:0.85rem;color:var(--text-muted);">-</div>
        @endif
      </div>
      {{-- Assigned Sales --}}
      <div style="background:var(--bg-surface-2);border-radius:8px;padding:10px 12px;min-width:0;">
        <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;"><i class="fas fa-user-circle mr-1"></i>Assigned Sales</div>
        <div style="font-size:0.85rem;font-weight:600;color:var(--text-primary);">{{ $customer->id_sale ? $customer->sale_name->name : '-' }}</div>
      </div>
    </div>

    {{-- ── Lead Notes ── --}}
    @if($customer->lead_notes)
    <div style="background:rgba(163,48,28,.05);border:1px solid rgba(163,48,28,.15);border-left:3px solid var(--brand);border-radius:0 8px 8px 0;padding:10px 14px;">
      <div style="font-size:0.7rem;color:var(--brand);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;"><i class="fas fa-sticky-note mr-1"></i>Lead Notes</div>
      <div style="font-size:0.83rem;color:var(--text-secondary);max-height:70px;overflow-y:auto;line-height:1.5;">{{ $customer->lead_notes }}</div>
    </div>
    @endif

  </div>
</div>
@if($customer->status_name->name == 'Potensial')
<!-- Lead Update History Timeline (AJAX loaded) -->
<div id="lead-history-timeline"></div>
@endif
@endif

<div class="col-md-12 mt-4">
  <div class="card card-primary card-outline">
    <div class="card-header d-flex align-items-center">
      <h3 class="card-title mb-0">File List</h3>

      <div class="ml-auto">
        <button type="button" class="btn bg-gradient-primary btn-sm mr-2" data-toggle="modal" data-target="#modal-customerfile">
          Upload File
        </button>
        <a href="/subscribe/{{ $customer->id }}" class="btn btn-primary btn-sm">
          Form Berlangganan
        </a>
      </div>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      <table id="example4" class="table table-bordered table-striped">

      <thead >
        <tr>
          <th scope="col">#</th>
          <th scope="col">Name</th>

          <th scope="col">Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach( $customer->file as $file)
        <tr>
          <th scope="row">{{ $loop->iteration }}</th>
          <td>{{ $file->name }}</td>

          <td >
           <a href="{{url ($file->path) }}"  target="_blank" title="Download" class="btn btn-primary btn-sm "> <i class="" aria-hidden="true"></i> Download </a>
           <form  action="/file/customer/{{ $file->id }}" method="POST" class="d-inline distpoint-delete" >
            @method('delete')
            @csrf

            <button title="Delete" type="submit"  class="btn btn-danger btn-sm"> Delete </button>
          </form>

        </td>


        <!-- /.modal -->



      </tr>




      @endforeach

    </tbody>
    </table>
    </div>
  </div>
</div>

<!-- Topology & Map Section -->
<div class="col-md-12 mt-2">
  <div class="row">
    <div class="col-md-6 p-1">
      <div class="card card-primary card-outline">
        <div class="card-header p-2 bg-info">
          <a href="/device/{{ $customer->id }}" title="device" class="btn btn-info btn-sm"><i class="fas fa-network-wired"></i> Manage Topology</a>
        </div>
        <div class="card-body p-0">
          <div style="width: 100%; height: 400px; overflow: auto;" id="chart_div_topology"></div>
        </div>
      </div>
    </div>

    <div class="col-md-6 p-1">
      <div class="card card-primary card-outline">
        <div class="card-header p-2 bg-info">
          <a href="https://www.google.com/maps/place/{{ $customer->coordinate }}" target="_blank" class="btn btn-info btn-sm"><i class="fa fa-map"></i> Show in Google Maps</a>
        </div>
        <div class="card-body p-0">
          <div style="width: 100%; height: 400px;" id="map">
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#888;font-size:14px;">
              <i class="fas fa-map-marker-alt mr-2"></i> Map loading...
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

</div>
<!-- /.row (end main row) -->

</div>
<!-- /.card-body -->

<!-- </div> -->
<!-- /.card -->

</section>
<!-- /.content-header -->

@endsection

<!-- Modals Section -->
<div class="modal fade" id="modal-wa">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
     <div class="card-header text-center">
      <h3 class="card-title font-weight-bold"> Message </h3>
    </div>
    <form role="form" method="post" action="/customer/wa">

      @csrf
      <div class="card-body">
       {{--    <div class="form-group">
        <label for="nama">FROM</label>
        <input type="text" class="form-control @error('key') is-invalid @enderror " name="key" id="key"  placeholder="Enter Plan key" value="{{env('WAPISENDER_KEY')}}">
        @error('key')
        <div class="error invalid-feedback">{{ $message }}</div>
        @enderror
      </div> --}}
      <div class="form-group">
        <label for="device">FROM</label>


        <select name="device" id="device" class="form-control">
          <option value="{{env('WAPISENDER_PAYMENT')}}">WA PAYMENT</option>
          <option value="{{env('WAPISENDER_TICKET')}}">WA NOC</option>

        </select>

      </div>
      <div class="form-group">
       <input type='hidden' name='id_customer' value="{{ $customer->id }}" class="form-control">
       <label for="phone">To  </label>
       <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone"  id="phone" placeholder="Phone" value="{{$customer->phone}}">
       @error('phone')
       <div class="error invalid-feedback">{{ $message }}</div>
       @enderror
     </div>

     <div class="form-group">
      <label for="description">Description  </label>
      @php
      if ($customer->status_name->name == 'Active'){                      {}
      $message = "Yth. ".$customer->name." ";
      $message .= "\nAccount Anda dengan CID ".$customer->customer_id." Saat ini telah *ACTIVE*";
      $message .= "\nSilahkan Menikmati layanan kami dengan aman dan nyaman  ";
      $message .= "\n*".config('app.signature')."*";
    }
    elseif ($customer->status_name->name == 'Inactive')
    {
     $message = "Yth. ".$customer->name." ";
     $message .= "\nAccount Anda dengan CID ".$customer->customer_id." Saat ini dalam masa *INACTIVE*";
     $message .= "\nSilahkan menghubungi bagian Payment untuk informasi lebih lanjut";
     $message .= "\n*".config('app.signature')."*";
   }
   elseif ($customer->status_name->name == 'Block')
   {
    $message = "Yth. ".$customer->name." ";
    $message .= "\nAccount Anda dengan CID ".$customer->customer_id." Saat ini telah *TERISOLIR*";
    $message .= "\nSilahkan menghubungi bagian Payment untuk informasi lebih lanjut";
    $message .= "\n*".config('app.signature')."*";
  }
  else
  $message = "";
  @endphp

  <textarea style="height: 110px;" class="form-control" name="message" id="message" placeholder="Message" value={{$message}} >{{$message}} </textarea>
</div>

</div>
<!-- /.card-body -->

<div class="card-footer">
  <button type="submit" class="btn btn-primary">Submit</button>
  <button type="button" class="btn btn-default float-right " data-dismiss="modal">Cancel</button>

</div>
</form>

</div>
<!-- /.modal-content -->
</div>
<!-- /.modal-dialog -->
</div>

<!-- Modal -->


<div class="modal fade" id="modal_reboot" tabindex="-1" aria-labelledby="modalRebootLabel" aria-hidden="true">
  <div class="modal-dialog ">
    <div class="modal-content shadow-lg rounded">

      <!-- Modal Header -->
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title" id="modalRebootLabel">
          <i class="fas fa-exclamation-triangle"></i> Confirmation
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body text-center">
        <p class="fs-5"><h5>Are you sure</h5> </p>
        <p class="fs-5">reboot this ONU?</p>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer d-flex justify-content-between">
        @php
        $portId = null;
        $value = null;

        if (isset($customer->id_onu) && strpos($customer->id_onu, ':') !== false) {
          list($key, $value) = explode(":", $customer->id_onu, 2);
          $portId = config('zteframeslotportid')[$key] ?? null;
        }
        @endphp

        @if($portId !== null && $value !== null)
        <form onsubmit="confirmSubmit(event, 'Reboot This ONU!')" action="{{ url('/olt/reboot/' . $customer->id_olt . '/' . $portId . '/' . $value) }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-warning px-4" title="Reboot">
            <i class="fas fa-sync-alt"></i> Reboot
          </button>
        </form>
        @endif

        <button type="button" class="btn btn-default float-right " data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>



<div class="modal fade" id="modal_onu_detail" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">ONU Detail - {{ $customer->id_onu }}</h5>
      </div>
      <div class="modal-body modal-dialog-scrollable" id="modal-body-content">
        <div id="onu_detail" name="onu_detail">
          <div class="fa-3x">
            <i class="fas fa-cog fa-spin"></i>
          </div>
          <a>Getting data from OLT.....</a>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default float-right " data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Edit Tags Customer -->
<div class="modal fade" id="modal-customer-tags">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info">
        <h5 class="modal-title text-white"><i class="fas fa-tags mr-2"></i>Edit Tags Customer</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('customer.tags.update', $customer->id) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="form-group mb-2">
            <label class="font-weight-bold">Tags</label>
            @php $selectedCustomerTags = array_keys($customerTags); @endphp
            <select name="tags[]" id="customer-tags-select" class="form-control select2" multiple data-placeholder="Pilih atau tambah tag...">
              @foreach($alltags as $tagId => $tagName)
                <option value="{{ $tagId }}" {{ in_array($tagId, $selectedCustomerTags) ? 'selected' : '' }}>{{ $tagName }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold">Tambah Tag Baru</label>
            <div class="input-group input-group-sm">
              <input type="text" id="new_customer_tag" class="form-control" placeholder="Nama tag baru...">
              <div class="input-group-append">
                <button type="button" class="btn btn-success" id="btn-add-customer-tag">
                  <i class="fas fa-plus"></i> Tambah
                </button>
              </div>
            </div>
            <small class="text-muted">Tag baru akan otomatis tersedia untuk semua ticket dan customer.</small>
          </div>
        </div>
        <div class="modal-footer">
          <a href="/tag" target="_blank" class="btn btn-outline-secondary mr-auto">
            <i class="fas fa-cog mr-1"></i>Kelola Tag
          </a>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-info">
            <i class="fas fa-save mr-1"></i>Simpan Tags
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<div class="modal fade" id="modal-customerfile">
  <div class="modal-dialog modal-lg">
    <div class="modal-content ">
            <!-- <div class="modal-header">
             <h5 class="modal-title">drap Marker to Right Posision</h5> 
              
              
           </div>-->
           {{-- <div class="modal-body"> --}}
             {{--   <div class="content-header"> --}}

              <div class="card card-primary card-outline p-5">
                <div class="card-header">
                  <h3 class="card-title font-weight-bold"> Upload File </h3>
                </div>


                <!-- Alert message (start) -->
                @if(Session::has('message'))
                <div class="alert {{ Session::get('alert-class') }}">
                  {{ Session::get('message') }}
                </div>
                @endif 
                <!-- Alert message (end) -->

                <form action="/file"  enctype='multipart/form-data' method="post" >
                 {{csrf_field()}}
                 <input type='hidden' name='id_customer' value="{{ $customer->id }}" class="form-control">

                 <div class="form-group">
                   <label class="control-label col-md-3 col-sm-3 col-xs-12" for="file_name">Nama File</label>
                   <div class="col-md-6 col-sm-6 col-xs-12">
                    <input type='text' name='file_name' id='file_name' class="form-control" placeholder="Nama file (opsional, default: nama asli)">
                  </div>
                </div>

                 <div class="form-group">
                   <label class="control-label col-md-3 col-sm-3 col-xs-12" for="name">File <span class="required">*</span></label>
                   <div class="col-md-6 col-sm-6 col-xs-12">
                    <input type='file' name='file' class="form-control">

                    @if ($errors->has('file'))
                    <span class="errormsg text-danger">{{ $errors->first('file') }}</span>
                    @endif
                  </div>
                </div>

                <div class="form-group">
                 <div class="col-md-6">
                   <input type="submit" name="submit" value='Submit' class='btn btn-success'>
                 </div>
               </div>

             </form>
           </div>

         {{--  </div> --}}

       {{-- </div> --}}
       <!-- /.modal-content -->
     </div>
     <!-- /.modal-dialog -->
   </div>
   <!-- /.modal -->


 </div>

 <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logModalLabel">Customer Log</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered" id="logTable">
          <thead>
            <tr>
              <th>Tanggal & Waktu</th>
              <th>Customer</th>
              <th>Diubah Oleh</th>
              <th>Perubahan</th>
            </tr>
          </thead>
          <tbody>
            <!-- Log entries will be populated here -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>



<div class="modal fade" id="modal-monitor">
  <div class="modal-dialog modal-lg">
    <div class="modal-content ">

      <div class="card card-primary card-outline ">
        {{-- <div class="card-header">
          <h3 class="card-title font-weight-bold"> Monitoring </h3>
        </div> --}}


        <div class="row">
          <div class="col-md-12 mt-1">
            <div class="card">
              <div id="graph"></div>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered">
                <tr>
                  <th>Interace</th>
                  <th>TX</th>
                  <th>RX</th>
                </tr>
                <tr>
                  <td><a>pppoe-{{$customer->customer_id}}</a></td>
                  <td><div id="tabletx"></div></td>
                  <td><div id="tablerx"></div></td>
                </tr>
              </table>
            </div>

          </div>
        </div>


      </div>

    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
<!-- /.modal-monitor -->

@section('footer-scripts')

<script>
// ── Customer Tag: Add new tag via AJAX ──────────────────────────
$('#btn-add-customer-tag').on('click', function() {
  var tagName = $('#new_customer_tag').val().trim();
  if (!tagName) return;

  $.ajax({
    url: '/tag/store',
    method: 'POST',
    data: { new_tag: tagName, _token: '{{ csrf_token() }}' },
    success: function(res) {
      var select = $('#customer-tags-select');
      // Tambahkan ke select2 dan langsung pilih
      var option = new Option(res.name, res.id, true, true);
      select.append(option).trigger('change');
      $('#new_customer_tag').val('');
    },
    error: function(xhr) {
      alert('Gagal menambahkan tag: ' + (xhr.responseJSON?.message ?? 'Unknown error'));
    }
  });
});

$(document).ready(function() {

  // ── Summernote for update_notes in Update Progress modal ──────────
  if ($('#lead-note-editor').length) {
    $('#lead-note-editor').summernote({
      height: 220,
      dialogsInBody: true,
      placeholder: 'Tulis catatan follow-up, hasil diskusi, atau perkembangan lainnya...',
      toolbar: [
        ['style',  ['bold', 'underline', 'strikethrough', 'clear']],
        ['font',   ['fontname', 'color']],
        ['para',   ['ul', 'ol', 'paragraph']],
        ['table',  ['table']],
        ['insert', ['link', 'picture']],
        ['view',   ['codeview', 'help']],
      ],
      callbacks: {
        onInit: function () {
          $('body > .note-popover').hide();
        }
      }
    });
    // Reset content every time modal opens
    $('#modal-update-lead').on('shown.bs.modal', function () {
      $('#lead-note-editor').summernote('reset');
    });
    // Sync Summernote HTML to textarea before form submit
    $('#modal-update-lead form').on('submit', function () {
      $('#lead-note-editor').val($('#lead-note-editor').summernote('code'));
    });
  }

  // Load Lead Update History Timeline via AJAX hanya jika status Potensial
  var customerStatus = "{{ $customer->status_name->name }}";
  if (customerStatus === 'Potensial') {
    var customerId = {{ $customer->id }};
    $("#lead-history-timeline").html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Loading update history...</div>');
    $.get('/customer/' + customerId + '/lead-history', function(data) {
      $("#lead-history-timeline").html(data);
    }).fail(function() {
      $("#lead-history-timeline").html('<div class="alert alert-danger">Gagal memuat update history.</div>');
    });
  }
});
</script>

<!-- Google Charts — lazy load hanya saat chart_div_topology masuk viewport -->
<script>
  // Copy Customer ID to Clipboard
  function copy_text() {
    navigator.clipboard.writeText('{{$customer->customer_id}}');
  }

  (function () {
    var chartLoaded = false;

    function drawTopologyChart() {
      if (typeof google === 'undefined' || !google.visualization) return;
      var data = new google.visualization.DataTable();
      data.addColumn('string', 'Name');
      data.addColumn('string', 'Manager');
      data.addColumn('string', 'ToolTip');
      data.addRows([
        @foreach ($customer->device as $topology)
        [{'v':'{{$topology->id}}', 'f':'{{$topology->name}}<div style="color:blue;">{{$topology->ip}} <br>{{$topology->type}}</div>'},
         '{{$topology->parrent}}', 'owner: {{$topology->owner}} | Position :{{$topology->position}} | Note :{{$topology->note}}'],
        @endforeach
      ]);
      @foreach ($customer->device as $topology)
      data.setRowProperty({{ $loop->iteration-1 }}, 'style', ' border: 0px; ');
      @endforeach
      var el = document.getElementById('chart_div_topology');
      if (el) {
        var chart = new google.visualization.OrgChart(el);
        chart.draw(data, {'allowHtml': true});
      }
    }

    function loadGoogleCharts() {
      if (chartLoaded) return;
      chartLoaded = true;
      var el = document.getElementById('chart_div_topology');
      if (el) el.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#888;font-size:13px;"><i class="fas fa-spinner fa-spin mr-2"></i> Loading topology...</div>';
      var s = document.createElement('script');
      s.src = 'https://www.gstatic.com/charts/loader.js';
      s.async = true;
      s.onload = function () {
        google.charts.load('current', { packages: ['orgchart'] });
        google.charts.setOnLoadCallback(drawTopologyChart);
      };
      s.onerror = function () {
        var el = document.getElementById('chart_div_topology');
        if (el) el.innerHTML = '<div style="padding:20px;color:#888;font-size:13px;"><i class="fas fa-exclamation-triangle mr-2"></i>Topology chart tidak tersedia (CDN gagal dimuat).</div>';
      };
      document.head.appendChild(s);
    }

    // Gunakan IntersectionObserver agar hanya load saat div topology terlihat
    var topologyEl = document.getElementById('chart_div_topology');
    if (topologyEl) {
      if ('IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function (entries, observer) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              observer.disconnect();
              loadGoogleCharts();
            }
          });
        }, { rootMargin: '0px' });
        obs.observe(topologyEl);
      } else {
        // Fallback browser lama — load langsung
        loadGoogleCharts();
      }
    }
  })();
</script>

<script>
  // Wait for the document to be fully loaded
  // $(document).ready(function() {
  //   // Get ONT status on page load
  //   getOntStatus();

  //   // Get ONT details on page load
  //         //   getOntDetail();
  // });

  // Function to get ONT status
  function getOntStatus() {
    // tampilkan spinner loading di awal
    // $('#ont_status').html(`
    //   <div class="d-flex align-items-center">
    //   <i class="fas fa-spinner fa-spin text-info mr-2"></i> 
    //   <span>Loading ONT status...</span>
    //   </div>
    //   `);

    // validasi id_onu & id_olt sebelum kirim request
    let idOnu = $('#id_onu').val();
    let idOlt = $('#id_olt').val();

    if (!idOnu || !idOlt) {
      $('#ont_status').html(
        `<span class="badge badge-secondary">No OLT/ONU data available</span>`
        );
        return; // hentikan eksekusi
      }

      $.ajax({
        url: '/olt/ont_status',
        method: 'POST',
        data: {
          id_onu: idOnu,
          id_olt: idOlt
        },
        success: function (data) {
          $('#ont_status').html(data);
        },
        error: function (xhr) {
          console.warn('Error fetching ONT status:', xhr.status, xhr.responseText);
          $('#ont_status').html(
            `<span class="badge badge-danger">
            Failed to fetch ONT status (${xhr.status})
            </span>`
            );
        }
      });
    }

  // Function to get ONT details
    function getOntDetail() {

     $.ajax({
      url: '/olt/onu_detail',
      method: 'POST',
      data: {
        id_onu: $('#id_onu').val(),  // Using jQuery to get the value
        id_olt: $('#id_olt').val()   // Using jQuery to get the value

      },
      success: function(data) {
        $('#onu_detail').html(data);  // Update HTML with the received data
      },
      error: function(xhr, status, error) {
        console.log('Error fetching ONT details: ' + error);  // Error handling
      }
    });
   }

  // Optional: Trigger functions on a specific event (if required)
  // Example:
   $('#btn_onu_detail').on('click', function() {
    getOntDetail();
  });

  // Lazy-init Leaflet Map — hanya diinisialisasi saat map div masuk viewport
  (function() {
    var mapInitialized = false;

    function doInitMap() {
      var center = @json($center);
      var locations = @json($locations);

      // Ubah koordinat pusat menjadi array [lat, lng] dengan pengecekan null
      var coordinates = [];
      if (center && center.coordinate && typeof center.coordinate === 'string') {
        coordinates = center.coordinate.split(',').map(Number);
      }
      if (!coordinates.length) { coordinates = [0, 0]; }

      var mapEl = document.getElementById('map');
      if (!mapEl) return;
      mapEl.innerHTML = '';

      var map = L.map('map').setView(coordinates, center.zoom);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);

      locations.forEach(function(location) {
        var coords = [];
        if (location && location.customer && typeof location.customer === 'string') {
          coords = location.customer.split(',').map(Number);
        }
        if (coords.length === 2) {
          L.marker(coords).addTo(map).bindPopup(location.name);
        }
      });
    }

    function initMap() {
      if (mapInitialized) return;
      mapInitialized = true;

      if (typeof L !== 'undefined') {
        doInitMap();
      } else {
        // Leaflet belum selesai load (async) — tunggu onload-nya
        var leafScript = document.querySelector('script[src*="leaflet"]');
        if (leafScript) {
          leafScript.addEventListener('load', function() { doInitMap(); });
        } else {
          // Fallback polling
          var poll = setInterval(function() {
            if (typeof L !== 'undefined') { clearInterval(poll); doInitMap(); }
          }, 100);
        }
      }
    }

    // Gunakan IntersectionObserver — hanya init saat map benar-benar terlihat (rootMargin 0)
    // Ini mencegah tile Leaflet menjadi LCP element sebelum user scroll
    var mapEl = document.getElementById('map');
    if (mapEl) {
      if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries, obs) {
          entries.forEach(function(entry) {
            if (entry.isIntersecting) {
              obs.disconnect();
              // Tunda hingga browser idle agar tidak mengganggu LCP
              if ('requestIdleCallback' in window) {
                requestIdleCallback(function() { initMap(); }, { timeout: 2000 });
              } else {
                setTimeout(initMap, 300);
              }
            }
          });
        }, { rootMargin: '0px' }); // tile TIDAK diunduh sebelum terlihat
        observer.observe(mapEl);
      } else {
        setTimeout(initMap, 1000); // fallback browser lama — delay 1s
      }
    }
  })();
</script>


    <script>
      let remoteIp = null; // Default kosong
      let IdCustomer = {{ $customer->id }};

// Ambil status router saat halaman load


// Event tombol create tunnel
      document.getElementById('createTunnelBtn').addEventListener('click', function () {
        if (!remoteIp) {
          Swal.fire({
            icon: 'error',
            title: 'Invalid IP Address',
            text: 'Remote IP address is missing.',
          });
          return;
        }

        Swal.fire({
          title: 'Creating Tunnel...',
          text: 'Please wait while the tunnel is being created.',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        fetch('/customer/createtunnel', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ IdCustomer: IdCustomer, remoteIp: remoteIp })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Tunnel Created!',
              text: 'Opening port...',
              timer: 2000,
              showConfirmButton: false
            });
            setTimeout(() => {
              window.open(`http://${data.host}:${data.port}`, '_blank');
            }, 2000);
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Failed to Create Tunnel',
              text: data.message || 'Unknown error.',
            });
          }
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: error.message,
          });
        });
      });




      function loadRouterStatus(customerId) {

       $.get(`/customer/${customerId}/router-status`, function (res) {
        if (!res.success) {
          $("#mikrotik-status").html(`<span class="badge badge-danger">${res.message}</span>`);
          return;
        }
        remoteIp = res.ip;
        let html = `
        <div class="btn ${res.btn_status} btn-sm mr-2">${res.status_user}</div>
        <a href="http://${res.ip}" class="btn ${res.btn_online} btn-sm" target="_blank">
        ${res.online} | ${res.ip} | ${res.uptime}
        </a>
        `;
        if (parseInt(res.ip_count) > 1) {
          html += `<span class="badge badge-danger ml-2">IP Conflict</span>`;
        }
        $("#mikrotik-status").html(html);
        $("#btn-traffic")
        .prop("disabled", false)
        .removeAttr("disabled")
        .removeClass("btn-secondary")
        .addClass(res.btn_status || "btn-warning")
        .attr("title", "Show Traffic");

            // Aktifkan tombol Web Tunnel
        $("#createTunnelBtn")
        .prop("disabled", false)
        .removeAttr("disabled")
        .removeClass("btn-secondary")
        .addClass(res.btn_status || "btn-warning");
      });
     }
     $(document).ready(function () {
      const customerId = {{ $customer->id }};
      loadRouterStatus(customerId);
      getOntStatus();

      setInterval(() => {
        loadRouterStatus(customerId);
        getOntStatus();
      }, 30000); 

    });
  </script>

<!-- Modal Workflow Steps per Customer -->
@if($customer->id_status == 1)
<div class="modal fade" id="modal-customer-workflow" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-stream"></i> Workflow Steps — {{ $customer->name }}</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info py-2 px-3" style="font-size: 0.82rem;">
          <i class="fas fa-info-circle"></i> Drag <i class="fas fa-grip-lines"></i> untuk mengubah urutan. Klik <strong>Pilih</strong> untuk set step aktif saat ini.
          <a href="{{ route('lead-workflow.index') }}" class="float-right text-info" target="_blank" title="Edit template default"><i class="fas fa-cog"></i> Template</a>
        </div>
        <!-- Form tambah step -->
        <form id="addCustomerStepForm" class="form-inline mb-3">
          @csrf
          <input type="hidden" id="customerIdForStep" value="{{ $customer->id }}">
          <input type="text" id="customerStepName" class="form-control mr-2 flex-fill" placeholder="Nama step baru" required>
          <button class="btn btn-success" type="submit"><i class="fas fa-plus"></i> Tambah</button>
        </form>
        <!-- List drag & drop -->
        <ul id="customer-workflow-steps" class="list-group">
          @foreach($customerSteps as $step)
          @php $isCurrent = $customer->current_step_id == $step->id; @endphp
          <li class="list-group-item d-flex align-items-center {{ $isCurrent ? 'bg-primary text-white' : '' }}"
              style="cursor: grab;" data-step="{{ $step->id }}">
            <i class="fas fa-grip-lines mr-2 text-muted" style="cursor:grab;"></i>
            <button type="button" class="btn btn-outline-danger btn-sm btn-cs-delete mr-2"
                    data-step="{{ $step->id }}"><i class="fas fa-trash"></i></button>
            <div class="flex-fill" style="min-width:0;">
              <div style="font-size:0.87rem;font-weight:{{ $isCurrent ? '700' : '400' }};">{{ $step->name }}</div>
              @if($step->selected_at)
                <div style="font-size:0.68rem;opacity:.7;margin-top:1px;"><i class="fas fa-clock mr-1"></i>{{ \Carbon\Carbon::parse($step->selected_at)->format('d M Y, H:i') }}</div>
              @endif
            </div>
            <button type="button" class="btn btn-sm {{ $isCurrent ? 'btn-light' : 'btn-outline-primary' }} btn-cs-choose"
                    data-step="{{ $step->id }}" {{ $isCurrent ? 'disabled' : '' }}>
              {!! $isCurrent ? '<i class="fas fa-check"></i> Aktif' : 'Pilih' !!}
            </button>
          </li>
          @endforeach
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endif

<!-- Modal Update Lead Progress -->
@if($customer->id_status == 1)
<div class="modal fade" id="modal-update-lead" tabindex="-1" role="dialog" aria-labelledby="modalUpdateLeadLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info">
        <h5 class="modal-title font-weight-bold" id="modalUpdateLeadLabel">
          <i class="fas fa-edit"></i> Update Lead Progress
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" action="/customer/{{ $customer->id }}/update-lead">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>Tip:</strong> Update lead progress setiap kali ada follow-up atau perubahan status untuk tracking yang lebih baik.
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="update_lead_source">Lead Source</label>
                <select name="lead_source" id="update_lead_source" class="form-control">
                  <option value="">-- Select Source --</option>
                  <option value="WA" {{ $customer->lead_source == 'WA' ? 'selected' : '' }}>WhatsApp</option>
                  <option value="Phone" {{ $customer->lead_source == 'Phone' ? 'selected' : '' }}>Phone Call</option>
                  <option value="Email" {{ $customer->lead_source == 'Email' ? 'selected' : '' }}>Email</option>
                  <option value="Walk-in" {{ $customer->lead_source == 'Walk-in' ? 'selected' : '' }}>Walk-in</option>
                  <option value="Referral" {{ $customer->lead_source == 'Referral' ? 'selected' : '' }}>Referral</option>
                  <option value="Social Media" {{ $customer->lead_source == 'Social Media' ? 'selected' : '' }}>Social Media</option>
                  <option value="Website" {{ $customer->lead_source == 'Website' ? 'selected' : '' }}>Website</option>
                  <option value="Other" {{ $customer->lead_source == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label for="update_expected_close_date">Expected Close Date</label>
                <input type="date" class="form-control" name="expected_close_date" id="update_expected_close_date" 
                       value="{{ $customer->expected_close_date }}">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="update_lead_notes">Lead Notes / Follow-up Progress</label>
            <textarea class="form-control" name="lead_notes" id="update_lead_notes" rows="5" 
                      placeholder="Catat progress follow-up, feedback customer, dll...">{{ $customer->lead_notes }}</textarea>
            <small class="form-text text-muted">
              <i class="fas fa-lightbulb"></i> Tips: Catat tanggal, aktivitas, dan hasil setiap follow-up
            </small>
          </div>

          <div class="form-group">
            <label style="font-size:0.82rem;font-weight:600;color:var(--text-secondary);"><i class="fas fa-comment-alt mr-1" style="color:var(--brand);"></i> Catatan Update <small class="font-weight-normal text-muted">— tersimpan di history timeline</small></label>
            <textarea name="update_notes" id="lead-note-editor"></textarea>
          </div>

          @if($customer->updated_at)
          <div class="alert alert-secondary">
            <i class="fas fa-clock"></i> Last updated: {{ $customer->updated_at->format('d M Y H:i') }}
          </div>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="submit" class="btn btn-info">
            <i class="fas fa-save"></i> Update Lead Progress
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

<!-- Conversion Modal -->
@if($customer->id_status == 1)
<div class="modal fade" id="modal-convert" tabindex="-1" role="dialog" aria-labelledby="modalConvertLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-success">
        <h5 class="modal-title font-weight-bold" id="modalConvertLabel">
          <i class="fas fa-check-circle"></i> Convert Lead to Active Customer
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" action="/customer/{{ $customer->id }}/convert-to-active">
        @csrf
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>Important:</strong> Converting this lead will:
            <ul class="mb-0 mt-2">
              <li>Change status from <strong>Potensial</strong> to <strong>Active</strong></li>
              <li>Configure MikroTik PPPoE Secret</li>
              <li>Record conversion timestamp and user</li>
            </ul>
          </div>

          <h5 class="text-primary mb-3"><i class="fas fa-user"></i> Customer Information</h5>
          <div class="row">
            <div class="col-md-6">
              <table class="table table-sm table-borderless">
                <tr>
                  <th style="width: 40%">Name:</th>
                  <td>{{ $customer->name }}</td>
                </tr>
                <tr>
                  <th>Phone:</th>
                  <td>{{ $customer->phone }}</td>
                </tr>
                <tr>
                  <th>Email:</th>
                  <td>{{ $customer->email ?: '-' }}</td>
                </tr>
                <tr>
                  <th>Address:</th>
                  <td>{{ $customer->address }}</td>
                </tr>
              </table>
            </div>
            <div class="col-md-6">
              <table class="table table-sm table-borderless">
                <tr>
                  <th style="width: 40%">PPPoE User:</th>
                  <td>{{ $customer->pppoe }}</td>
                </tr>
                <tr>
                  <th>Customer ID:</th>
                  <td>{{ $customer->customer_id }}</td>
                </tr>
              </table>
            </div>
          </div>

          <hr>
          <h5 class="text-primary mb-3"><i class="fas fa-network-wired"></i> Technical Configuration</h5>
          
          <div class="form-group">
            <label for="convert_id_plan">Plan <span class="text-danger">*</span></label>
            <select name="id_plan" id="convert_id_plan" class="form-control" required>
              <option value="">-- Select Plan --</option>
              @foreach($customer->plan_list ?? [] as $plan)
                <option value="{{ $plan->id }}" {{ $customer->id_plan == $plan->id ? 'selected' : '' }}>
                  {{ $plan->name }} (Rp. {{ number_format($plan->price, 0, ',', '.') }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="form-group">
            <label for="convert_id_distrouter">Distribution Router <span class="text-danger">*</span></label>
            <select name="id_distrouter" id="convert_id_distrouter" class="form-control" required>
              <option value="">-- Select Router --</option>
              @foreach($customer->router_list ?? [] as $id => $name)
                <option value="{{ $id }}" {{ $customer->id_distrouter == $id ? 'selected' : '' }}>{{ $name }}</option>
              @endforeach
            </select>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="convert_id_olt">OLT</label>
                <select name="id_olt" id="convert_id_olt" class="form-control">
                  <option value="">-- Select OLT (Optional) --</option>
                  @foreach($customer->olt_list ?? [] as $id => $name)
                    <option value="{{ $id }}" {{ $customer->id_olt == $id ? 'selected' : '' }}>{{ $name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="convert_id_distpoint">Distribution Point</label>
                <select name="id_distpoint" id="convert_id_distpoint" class="form-control">
                  <option value="">-- Select DP (Optional) --</option>
                  @foreach($customer->distpoint_list ?? [] as $id => $name)
                    <option value="{{ $id }}" {{ $customer->id_distpoint == $id ? 'selected' : '' }}>{{ $name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check-circle"></i> Convert to Active
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

{{-- Modal: Tandai Lead Gagal --}}
@if($customer->id_status == 1)
<div class="modal fade" id="modal-mark-lost" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title font-weight-bold text-white">
          <i class="fas fa-times-circle"></i> Tandai Lead Gagal / Lost
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" action="/customer/{{ $customer->id }}/mark-as-lost">
        @csrf
        <div class="modal-body">
          <div class="alert alert-warning py-2">
            <i class="fas fa-exclamation-triangle"></i> Lead <strong>{{ $customer->name }}</strong> akan ditandai sebagai <strong>Gagal</strong>. Data tetap tersimpan dan bisa dibuka kembali kapan saja.
          </div>
          <div class="form-group">
            <label>Alasan Gagal <span class="text-danger">*</span></label>
            <select name="lost_reason" class="form-control" required>
              <option value="">-- Pilih Alasan --</option>
              <option value="Tidak Tertarik">Tidak Tertarik</option>
              <option value="Harga Tidak Cocok">Harga Tidak Cocok</option>
              <option value="Lokasi Tidak Terjangkau">Lokasi Tidak Terjangkau</option>
              <option value="Sudah Pakai Provider Lain">Sudah Pakai Provider Lain</option>
              <option value="Tidak Ada Respon">Tidak Ada Respon / Tidak Bisa Dihubungi</option>
              <option value="Tunda / Belum Butuh">Tunda / Belum Butuh</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
          <div class="form-group">
            <label>Catatan Tambahan</label>
            <textarea name="lost_notes" class="form-control" rows="3" placeholder="Opsional - detail alasan gagal..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> Batal
          </button>
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-times-circle"></i> Tandai Gagal
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

@if($customer->id_status == 1)
<script>
document.addEventListener('DOMContentLoaded', function () {
  var customerId = "{{ $customer->id }}";

  // ── Drag & drop reorder ───────────────────────────────────────
  var el = document.getElementById('customer-workflow-steps');
  if (el) {
    Sortable.create(el, {
      animation: 150,
      handle: '.fa-grip-lines',
      filter: '.btn-cs-choose, .btn-cs-delete',
      preventOnFilter: true,
      onEnd: function () {
        var order = [];
        document.querySelectorAll('#customer-workflow-steps li[data-step]').forEach(function (item, idx) {
          order.push({ id: item.getAttribute('data-step'), position: idx + 1 });
        });
        fetch('/customer/' + customerId + '/steps/reorder', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
          body: JSON.stringify({ order: order })
        }).then(r => r.json()).then(function (d) {
          if (d.success) location.reload();
        });
      }
    });
  }

  // ── Pilih step aktif ─────────────────────────────────────────
  document.querySelectorAll('.btn-cs-choose').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var stepId = this.getAttribute('data-step');
      Swal.fire({ title: 'Set sebagai step aktif?', icon: 'question',
        showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal' })
      .then(function (res) {
        if (!res.isConfirmed) return;
        Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch('/customer/' + customerId + '/steps/move', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
          body: JSON.stringify({ step_id: stepId })
        }).then(r => r.json()).then(function (d) {
          if (d.success) Swal.fire('Berhasil!', 'Step aktif: ' + d.step, 'success').then(() => location.reload());
        });
      });
    });
  });

  // ── Hapus step ───────────────────────────────────────────────
  document.querySelectorAll('.btn-cs-delete').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var stepId = this.getAttribute('data-step');
      Swal.fire({ title: 'Hapus step ini?', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal' })
      .then(function (res) {
        if (!res.isConfirmed) return;
        fetch('/customer/' + customerId + '/steps/delete', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
          body: JSON.stringify({ step_id: stepId })
        }).then(r => r.json()).then(function (d) {
          if (d.success) location.reload();
        });
      });
    });
  });

  // ── Tambah step baru ─────────────────────────────────────────
  var addForm = document.getElementById('addCustomerStepForm');
  if (addForm) {
    addForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var name = document.getElementById('customerStepName').value;
      Swal.fire({ title: 'Menambah...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
      fetch('/customer/' + customerId + '/steps/add', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name })
      }).then(r => r.json()).then(function (d) {
        if (d.success) Swal.fire('Berhasil!', 'Step ditambahkan.', 'success').then(() => location.reload());
      });
    });
  }
});
</script>
@endif

<script>
function confirmDeleteCustomer(customerId) {
  var unpaidCount = {{ $unpaidInvoiceCount ?? 0 }};
  var modalBody = document.getElementById('delete-modal-body');

  if (unpaidCount > 0) {
    modalBody.innerHTML =
      '<div class="alert alert-danger mb-2">'+
      '<i class="fas fa-exclamation-circle mr-2"></i>'+
      '<strong>Tidak dapat menghapus customer!</strong>'+
      '</div>'+
      '<p>Terdapat <strong>' + unpaidCount + ' invoice yang belum dibayar</strong>. '+
      'Harap <strong>lunasi</strong> atau <strong>batalkan</strong> invoice tersebut terlebih dahulu sebelum menghapus customer ini.</p>';
    document.getElementById('btn-confirm-delete').disabled = true;
    document.getElementById('btn-confirm-delete').classList.add('disabled');
  } else {
    modalBody.innerHTML =
      '<p>Apakah Anda yakin ingin menghapus customer <strong>{{ addslashes($customer->name) }}</strong>?</p>'+
      '<p class="text-muted mb-0"><small>Tindakan ini tidak dapat dibatalkan.</small></p>';
    document.getElementById('btn-confirm-delete').disabled = false;
    document.getElementById('btn-confirm-delete').classList.remove('disabled');
  }

  $('#modal-confirm-delete').modal('show');
}
</script>

  @endsection

@push('summernote-script')
<script src="{{ url('dashboard/plugins/summernote/summernote-bs4.min.js') }}"></script>
@endpush

@push('highcharts-scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
  $('#modal-monitor').on('hidden.bs.modal', function () {
    window.location.reload();
  });
  $('#modal-monitor').on('show.bs.modal', function () {

    var chart;

    function requestDatta() {
      $.ajax({
        url: '/distrouter/client_monitor',
        method: 'post',
        data: {
          interface: document.getElementById("interface").value,
          ip:        document.getElementById("ip").value,
          user:      document.getElementById("user").value,
          password:  document.getElementById("password").value,
          port:      document.getElementById("port").value
        },
        success: function(data) {
          var midata = JSON.parse(data);
          if (midata.length > 0) {
            var TX = parseInt(midata[0].data);
            var RX = parseInt(midata[1].data);
            var x  = (new Date()).getTime();
            var shift = chart.series[0].data.length > 19;
            chart.series[0].addPoint([x, TX], true, shift);
            chart.series[1].addPoint([x, RX], true, shift);
            document.getElementById("tabletx").innerHTML = convert(TX);
            document.getElementById("tablerx").innerHTML = convert(RX);
          } else {
            document.getElementById("tabletx").innerHTML = "0";
            document.getElementById("tablerx").innerHTML = "0";
          }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
          console.error("Status: " + textStatus + " request: " + XMLHttpRequest);
          console.error("Error: " + errorThrown);
        }
      });
    }

    $(document).ready(function() {
      Highcharts.setOptions({ global: { useUTC: false } });

      chart = new Highcharts.Chart({
        chart: {
          renderTo: 'graph',
          animation: Highcharts.svg,
          type: 'area',
          events: {
            load: function () {
              setInterval(function () { requestDatta(); }, 1000);
            }
          }
        },
        title: { text: 'Traffic Monitoring' },
        xAxis: { type: 'datetime', tickPixelInterval: 150, maxZoom: 20 * 1000 },
        yAxis: {
          minPadding: 0.2,
          maxPadding: 0.2,
          title: { text: 'Traffic' },
          labels: {
            formatter: function () {
              return convert(this.value);
            }
          }
        },
        series: [{ name: 'TX', data: [] }, { name: 'RX', data: [] }],
        tooltip: {
          headerFormat: '<b>{series.name}</b><br/>',
          pointFormat: '{point.x:%Y-%m-%d %H:%M:%S}<br/>{point.y}'
        }
      });
    });

    function convert(bytes) {
      var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
      if (bytes == 0) return '0 bps';
      var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
      return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
    }
  });
</script>

<script>
// Reset Password - generate random password
document.getElementById('btn-generate-password').addEventListener('click', function () {
  const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$!';
  let pwd = '';
  for (let i = 0; i < 10; i++) {
    pwd += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  document.getElementById('new_password').value = pwd;
});
</script>
@endpush
