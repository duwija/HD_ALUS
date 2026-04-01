<tr>
  <!-- <th scope="row">{{ $counter++}}</th> -->
  <td style="padding-left: {{ 10+( $level * 20 )}}px;">
    @if (empty($akun->parent))
    {{ $akun->akun_code }}
    @else
    {{ $akun->parent }} | {{ $akun->akun_code }}
    @endif
  </td>
  <td>
    @if ($akun->tax == 0)
    {{ $akun->name }}
    @else
    {{ $akun->name }} | tax ({{ $akun->tax_value }}%)
    @endif
  </td>
  <td>{{ $akun->group }}</td>
  <td>{{ $akun->category }}</td>
  <td>
    <div class="float-right">
      {{-- Tampilkan tombol hapus hanya jika akun tidak memiliki child dan tidak digunakan di jurnal --}}
      @if ($akun->children->isEmpty() && !$akun->isUsedInJournals())
      <form action="/akun/{{ $akun->akun_code }}" method="post" class="d-inline site-delete">
        @method('delete')
        @csrf
        <button type="submit" class="btn btn-danger btn-xs">
          <i class="fa fa-times"></i> Hapus
        </button>
      </form>
      @else
      <a class="bg-info badge">
        {{ $akun->children->isNotEmpty() ? 'is Used' : 'Is Used' }}
      </a>
      @endif
    </div>
  </td>
</tr>


@if ($level < 5) {{-- Batasi hingga 5 level --}}
@foreach ($akun->children as $child)

@include('akun.akun-row', ['akun' => $child, 'level' => $level + 1])
<!-- @php $counter++ @endphp -->
@endforeach

@endif
