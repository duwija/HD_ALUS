@php
  // Bawa filter tanggal & sales saat pindah tab
  $tabParams = array_filter(request()->only(['start_date', 'end_date', 'id_sale']));
@endphp
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link {{ $activeTab === 'pipeline' ? 'active' : '' }}" href="{{ route('marketing.lead-summary', $tabParams) }}">
      <i class="fas fa-stream"></i> Pipeline Lead
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link {{ $activeTab === 'conversion' ? 'active' : '' }}" href="{{ route('marketing.lead-conversion', $tabParams) }}">
      <i class="fas fa-handshake"></i> Hasil Konversi
    </a>
  </li>
</ul>
