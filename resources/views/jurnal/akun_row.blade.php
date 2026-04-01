@php
$indent = str_repeat('&nbsp;', $level * 4); // Indentasi berdasarkan level
@endphp
<tr>
  <td>{!! $indent !!}{{ $akun->name }}</td>
  <td>{{ number_format($akun->saldo, 2) }}</td>
</tr>

@if ($akun->children->isNotEmpty())
@foreach ($akun->children as $child)
@include('jurnal.akun_row', ['akun' => $child, 'level' => $level + 1])
@endforeach
@endif