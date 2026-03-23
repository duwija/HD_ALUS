@php
$counts = [
'Open'       => $tickets->where('status', 'Open')->count(),
'Inprogress' => $tickets->where('status', 'Inprogress')->count(),
'Pending'    => $tickets->where('status', 'Pending')->count(),
'Solve'      => $tickets->where('status', 'Solve')->count(),
'Close'      => $tickets->where('status', 'Close')->count(),
];
$total = $tickets->count();
$colors = [
'Open' => 'bg-open',
'Inprogress' => 'bg-inprogress',
'Pending' => 'bg-pending',
'Solve' => 'bg-solve',
'Close' => 'bg-close',
];
@endphp

@foreach($counts as $status => $count)
<div class="summary-card {{ $colors[$status] ?? 'bg-secondary' }}">
	<div class="fw-bold">{{ $status }}</div>
	<div style="font-size:1.6rem;font-weight:700;">{{ $count }}</div>
	<div class="small">Tickets</div>
</div>
@endforeach
<div class="summary-card bg-total">
	<div class="fw-bold text-info">Total</div>
	<div style="font-size:1.6rem;font-weight:700;">{{ $total }}</div>
	<div class="small text-info">All</div>
</div>
