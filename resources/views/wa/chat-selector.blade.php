@extends('layout.main')
@section('title', 'Pilih WhatsApp Session')
@section('content')

<section class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h4 class="m-0">💬 Pilih Session WhatsApp</h4>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-body text-center">

        @if(empty($sessions))
        <div class="p-5 text-muted">
          <i class="fas fa-unlink fa-3x mb-3"></i>
          <p>Gateway offline atau tidak ada session terdaftar.</p>
        </div>
        @else
        <h5 class="mb-3">Pilih session yang ingin kamu buka:</h5>
        <div class="row justify-content-center">
          @foreach($sessions as $session)
          <div class="col-md-3 col-sm-6 mb-3">
            <button class="btn btn-lg btn-success w-100" onclick="enterSession('{{ $session }}')">
              <i class="fab fa-whatsapp"></i> {{ $session }}
            </button>
          </div>
          @endforeach
        </div>
        @endif

      </div>
    </div>
  </div>
</section>

<script>
  function enterSession(session) {
  // Simpan session terakhir ke localStorage
    localStorage.setItem('wa_last_session', session);
    window.location.href = `/wa/chat/${session}`;
  }

// Jika ada session tersimpan, langsung arahkan otomatis
  document.addEventListener('DOMContentLoaded', () => {
    const savedSession = localStorage.getItem('wa_last_session');
    if (savedSession) {
      window.location.href = `/wa/chat/${savedSession}`;
    }
  });
</script>
@endsection
