@php
  $isRoot     = ($level === 0);
  $isChild    = ($level === 1);
  $isDeep     = ($level >= 2);
  $hasChildren = $akun->children->isNotEmpty();

  // Row background per level
  if ($isRoot)       { $rowBg = 'background:#f1f3f5'; $codeBg = '#343a40'; $codeColor = '#fff'; }
  elseif ($isChild)  { $rowBg = 'background:#f8f9fa'; $codeBg = '#6c757d'; $codeColor = '#fff'; }
  else               { $rowBg = 'background:#fff';     $codeBg = '#adb5bd'; $codeColor = '#343a40'; }

  // Indentation + tree connector
  $indent = $level * 22;
@endphp

<tr style="{{ $rowBg }};border-bottom:1px solid #dee2e6">

  {{-- ─── Kode Akun ─────────────────────────────────────────────────────── --}}
  <td style="padding-left:{{ 8 + $indent }}px;white-space:nowrap;vertical-align:middle">
    {{-- Tree connector for child rows --}}
    @if ($level > 0)
      <span style="color:#adb5bd;font-size:.85rem;margin-right:3px;letter-spacing:-1px">
        @for($i = 1; $i < $level; $i++) &nbsp;&nbsp;&nbsp; @endfor
        └─
      </span>
    @endif

    {{-- Folder/file icon --}}
    @if ($isRoot)
      <i class="fas fa-folder text-secondary mr-1" style="font-size:.8rem"></i>
    @elseif ($hasChildren)
      <i class="fas fa-folder-open mr-1" style="color:#adb5bd;font-size:.8rem"></i>
    @else
      <i class="fas fa-file-alt mr-1" style="color:#adb5bd;font-size:.75rem"></i>
    @endif

    {{-- Code badge --}}
    <span class="badge" style="background:{{ $codeBg }};color:{{ $codeColor }};font-size:.8rem;letter-spacing:.3px;font-family:monospace">
      {{ $akun->akun_code }}
    </span>

    {{-- Show parent code if this is a child --}}
    @if (!empty($akun->parent) && $isChild)
      <span class="text-muted" style="font-size:.7rem;margin-left:4px">
        ← {{ $akun->parent }}
      </span>
    @endif
  </td>

  {{-- ─── Nama ─────────────────────────────────────────────────────────── --}}
  <td style="vertical-align:middle">
    @if ($isRoot)
      <strong>{{ $akun->name }}</strong>
      @if ($hasChildren)
        <span class="badge badge-secondary ml-1" style="font-size:.65rem">
          {{ $akun->children->count() }} child
        </span>
      @endif
    @else
      <span style="color:var(--text-primary)">{{ $akun->name }}</span>
    @endif

    @if ($akun->tax == 1)
      <span class="badge badge-warning ml-1" style="font-size:.65rem">
        tax {{ $akun->tax_value }}%
      </span>
    @endif
  </td>

  {{-- ─── Grup ────────────────────────────────────────────────────────── --}}
  <td style="vertical-align:middle;font-size:.82rem">{{ $akun->group }}</td>

  {{-- ─── Kategori ────────────────────────────────────────────────────── --}}
  <td style="vertical-align:middle;font-size:.82rem">{{ $akun->category }}</td>

  {{-- ─── Action ──────────────────────────────────────────────────────── --}}
  <td class="text-center" style="vertical-align:middle">
    @if ($akun->children->isEmpty() && !$akun->isUsedInJournals())
      <form action="/akun/{{ $akun->akun_code }}" method="post" class="d-inline site-delete">
        @method('delete')
        @csrf
        <button type="submit" class="btn btn-danger btn-xs py-0 px-2" title="Hapus Akun">
          <i class="fa fa-times"></i>
        </button>
      </form>
    @else
      <span class="badge badge-success" style="font-size:.7rem">
        <i class="fas fa-lock mr-1"></i>In Use
      </span>
    @endif
  </td>
</tr>

{{-- Recursive children --}}
@if ($level < 5)
  @foreach ($akun->children->sortBy('akun_code') as $child)
    @include('akun.akun-row', ['akun' => $child, 'level' => $level + 1])
  @endforeach
@endif
