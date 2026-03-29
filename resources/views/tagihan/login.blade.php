<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pelanggan - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        /* ── Layout ── */
        .page-wrap {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }
        .two-col {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }
        .two-col.no-promo {
            justify-content: center;
        }
        .col-promo  { flex: 1; min-width: 0; }
        .col-login  { width: 360px; flex-shrink: 0; }
        .two-col.no-promo .col-login { width: 400px; }
        @media (max-width: 767px) {
            .two-col    { flex-direction: column-reverse; }
            .col-login  { width: 100%; }
            .two-col.no-promo .col-login { width: 100%; }
        }

        /* ── Login card ── */
        .login-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,.22);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 32px 28px;
            text-align: center;
        }
        .login-header h3 { margin: 0; font-weight: 700; font-size: 1.25rem; }
        .login-header p  { margin: 8px 0 0; opacity: .9; font-size: .92rem; }
        .login-body { padding: 30px 28px; }
        .form-group label { font-weight: 500; color: #333; margin-bottom: 6px; }
        .form-control {
            border-radius: 8px;
            padding: 11px 14px;
            border: 2px solid #e0e0e0;
            font-size: 14.5px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 .2rem rgba(102,126,234,.12);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff; border: none;
            padding: 12px; border-radius: 8px;
            font-weight: 600; font-size: 15px;
            width: 100%; transition: all .3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 18px rgba(102,126,234,.4);
            color: #fff;
        }
        .alert { border-radius: 8px; border: none; }
        .custom-checkbox .custom-control-label { font-size: 13.5px; color: #666; }
        .activate-link { text-align: center; margin-top: 18px; font-size: 13.5px; }
        .activate-link a { color: #667eea; font-weight: 500; text-decoration: none; }
        .activate-link a:hover { text-decoration: underline; }

        /* ── Promo column ── */
        .promo-title {
            color: #fff;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            opacity: .85;
            margin-bottom: 10px;
        }
        .promo-card {
            background: rgba(255,255,255,.15);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 14px;
            transition: transform .2s;
        }
        .promo-card:hover { transform: translateY(-3px); }
        .promo-img {
            width: 100%; height: 140px;
            object-fit: cover;
        }
        .promo-body { padding: 14px 16px; }
        .promo-badge {
            display: inline-block;
            background: rgba(255,255,255,.28);
            color: #fff;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            padding: 2px 9px;
            border-radius: 20px;
            margin-bottom: 6px;
        }
        .promo-name {
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            margin: 0 0 6px;
            line-height: 1.3;
        }
        .promo-desc {
            color: rgba(255,255,255,.85);
            font-size: .82rem;
            margin: 0;
            line-height: 1.5;
        }
        .promo-desc img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin-top: 6px;
        }
        .promo-desc ul, .promo-desc ol {
            padding-left: 18px;
            margin: 6px 0 0;
        }
        .promo-date {
            color: rgba(255,255,255,.65);
            font-size: .75rem;
            margin-top: 8px;
        }
        .no-promo {
            color: rgba(255,255,255,.7);
            font-size: .88rem;
            text-align: center;
            padding: 30px 0;
        }

        /* ── Carousel dots ── */
        #promoSlider .carousel-indicators li {
            background-color: rgba(255,255,255,.6);
            width: 8px; height: 8px; border-radius: 50%;
        }
        #promoSlider .carousel-indicators .active { background-color: #fff; }
    </style>
</head>
<body>
@php
    $companyName = tenant_config('APP_NAME', config('app.name', 'INTERNET SERVICE PROVIDER'));
    $companyAddress1 = tenant_config('COMPANY_ADDRESS1', tenant_config('company_address1', ''));
    $companyAddress2 = tenant_config('COMPANY_ADDRESS2', tenant_config('company_address2', ''));
@endphp
<div class="container">
<div class="page-wrap">

    <div class="two-col{{ $promos->isEmpty() ? ' no-promo' : '' }}">

        {{-- ── KOLOM PROMO ── --}}
        @if($promos->isNotEmpty())
        <div class="col-promo">
            @if($promos->isNotEmpty())
                <!-- <p class="promo-title"><i class="fas fa-bullhorn mr-1"></i> Promo & Pengumuman</p> -->

                @if($promos->count() === 1)
                    @php $p = $promos->first(); @endphp
                    <div class="promo-card">
                        @if($p->image_url)
                            <img src="{{ $p->image_url }}" alt="{{ $p->title }}" class="promo-img">
                        @endif
                        <div class="promo-body">
                            @if($p->badge)
                                <span class="promo-badge">{{ $p->badge }}</span>
                            @endif
                            <p class="promo-name">{{ $p->title }}</p>
                            <div class="promo-desc">{!! $p->content !!}</div>
                            @if($p->end_date)
                                <p class="promo-date"><i class="fas fa-clock"></i> Berlaku s/d {{ $p->end_date->format('d M Y') }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    {{-- Carousel untuk > 1 promo --}}
                    <div id="promoSlider" class="carousel slide" data-ride="carousel" data-interval="4000">
                        <ol class="carousel-indicators">
                            @foreach($promos as $i => $p)
                                <li data-target="#promoSlider" data-slide-to="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}"></li>
                            @endforeach
                        </ol>
                        <div class="carousel-inner">
                            @foreach($promos as $i => $p)
                                <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                                    <div class="promo-card" style="margin-bottom:30px">
                                        @if($p->image_url)
                                            <img src="{{ $p->image_url }}" alt="{{ $p->title }}" class="promo-img">
                                        @endif
                                        <div class="promo-body">
                                            @if($p->badge)
                                                <span class="promo-badge">{{ $p->badge }}</span>
                                            @endif
                                            <p class="promo-name">{{ $p->title }}</p>
                                            <div class="promo-desc">{!! $p->content !!}</div>
                                            @if($p->end_date)
                                                <p class="promo-date"><i class="fas fa-clock"></i> s/d {{ $p->end_date->format('d M Y') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            <!-- <div class="text-center mt-3">
                <p class="text-white small mb-0">
                    <i class="fas fa-info-circle"></i> Jika mengalami kesulitan, hubungi customer service
                </p>
            </div> -->
        </div>
        @endif

        {{-- ── KOLOM LOGIN ── --}}
        <div class="col-login">
            <div class="login-card">
                <div class="login-header">
                    <i class="fas fa-user-circle fa-3x mb-2"></i>
                    <h3>Login Pelanggan</h3>
                    <p>Akses tagihan dan informasi akun Anda</p>
                </div>
                <div class="login-body">
                    @if(session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                        @endforeach
                    </div>
                    @endif

                    <form method="POST" action="{{ url('/tagihan/login') }}">
                        @csrf
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email') }}"
                                   placeholder="nama@email.com" required autofocus>
                        </div>

                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password"
                                   placeholder="Masukkan password" required>
                        </div>

                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                            <label class="custom-control-label" for="remember">Ingat saya</label>
                        </div>

                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt"></i> Masuk
                        </button>
                    </form>

                    <div class="activate-link">
                        <p class="mb-0">Belum punya akses?</p>
                        <a href="{{ url('/tagihan/activate') }}">
                            <i class="fas fa-user-plus"></i> Aktivasi Akun
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /two-col --}}

    <div class="text-center mt-3">
        <p class="mb-1 text-white" style="font-size: 0.85rem; opacity: 0.95;">
            <strong>{{ $companyName }}</strong>
        </p>
        @if(!empty($companyAddress1) || !empty($companyAddress2))
            <p class="mb-0 text-white" style="font-size: 0.8rem; opacity: 0.85; line-height: 1.4;">
                {{ trim($companyAddress1 . ' ' . $companyAddress2) }}
            </p>
        @endif
    </div>

</div>{{-- /page-wrap --}}
</div>{{-- /container --}}

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
