<ul>
  @foreach ($akuns as $akun)
  <li>
    {{ $akun->akun_code }} | {{ $akun->name }}
    @if ($akun->children->isNotEmpty())
    @include('akun.sub-akun', ['akuns' => $akun->children]) {{-- Rekursi untuk anak-anak --}}
    @endif
  </li>
  @endforeach
</ul>
