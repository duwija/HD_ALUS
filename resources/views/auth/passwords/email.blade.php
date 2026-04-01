@extends('layouts.app')

@section('content')
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    .auth-wrapper {
        min-height: 100vh;
        background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 50%, #faf5ff 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        position: relative;
        overflow: hidden;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    .orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.15;
        animation: float 20s ease-in-out infinite;
    }
    .orb-1 { width: 400px; height: 400px; background: linear-gradient(45deg, #667eea, #764ba2); top: -100px; left: -100px; animation-delay: 0s; }
    .orb-2 { width: 350px; height: 350px; background: linear-gradient(45deg, #f093fb, #f5576c); bottom: -80px; right: -80px; animation-delay: 5s; }
    .orb-3 { width: 300px; height: 300px; background: linear-gradient(45deg, #4facfe, #00f2fe); top: 50%; left: 50%; transform: translate(-50%, -50%); animation-delay: 10s; }

    @keyframes float {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -30px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }

    .glass-card {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 900px;
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        animation: slideIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(40px) scale(0.95); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .card-left {
        flex: 1;
        background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
        padding: 60px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    .card-left::before {
        content: '';
        position: absolute;
        top: -50%; left: -50%;
        width: 200%; height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
        background-size: 30px 30px;
        animation: movePattern 30s linear infinite;
    }
    @keyframes movePattern {
        0%   { transform: translate(0, 0); }
        100% { transform: translate(30px, 30px); }
    }

    .branding-content {
        position: relative;
        z-index: 1;
        text-align: center;
        max-width: 320px;
    }
    .desktop-logo { margin-bottom: 35px; }
    .desktop-logo img {
        max-width: 180px;
        height: auto;
        filter: brightness(0) invert(1) drop-shadow(0 4px 12px rgba(0,0,0,0.2));
    }
    .branding-title { color: #fff; font-size: 28px; font-weight: 700; margin: 0 0 15px; line-height: 1.2; text-shadow: 0 2px 10px rgba(0,0,0,0.2); }
    .branding-description { color: rgba(255,255,255,0.9); font-size: 15px; line-height: 1.6; margin: 0; }
    .feature-list { margin-top: 30px; text-align: left; }
    .feature-item { display: flex; align-items: center; color: rgba(255,255,255,0.95); font-size: 14px; margin-bottom: 12px; }
    .feature-item::before {
        content: '🔒';
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        margin-right: 12px;
        font-size: 13px;
    }

    .card-right {
        flex: 1;
        padding: 60px 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .mobile-logo { display: none; text-align: center; margin-bottom: 25px; }
    .mobile-logo img { max-width: 140px; height: auto; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.1)); }

    .card-header-custom {
        padding: 0 0 30px;
        text-align: left;
        background: none;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 35px;
    }
    .header-title { color: #1e293b; font-size: 26px; font-weight: 700; margin: 0 0 8px; }
    .header-subtitle { color: #64748b; font-size: 14px; margin: 0; line-height: 1.5; }

    .input-group-custom { margin-bottom: 24px; }
    .input-label {
        display: block;
        color: #334155;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .input-field {
        width: 100%;
        padding: 14px 18px;
        background: #f8f9fa;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        color: #1e293b;
        font-size: 15px;
        transition: all 0.3s ease;
    }
    .input-field::placeholder { color: #94a3b8; }
    .input-field:focus {
        outline: none;
        background: #fff;
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(96,165,250,0.1);
        transform: translateY(-1px);
    }
    .input-field.is-invalid { border-color: #ef4444; background: #fef2f2; }
    .error-message { color: #ef4444; font-size: 13px; margin-top: 6px; display: block; }

    .success-alert {
        background: #f0fdf4;
        border: 1.5px solid #86efac;
        color: #166534;
        border-radius: 12px;
        padding: 14px 18px;
        font-size: 14px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .submit-button {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
        border: none;
        border-radius: 12px;
        color: #fff;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 6px 20px rgba(96,165,250,0.3);
    }
    .submit-button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(96,165,250,0.4); }

    .back-link {
        display: block;
        text-align: center;
        margin-top: 20px;
        color: #60a5fa;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
    }
    .back-link:hover { color: #3b82f6; text-decoration: underline; }
    .decorative-line { height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); margin: 25px 0; }

    @media (max-width: 768px) {
        .glass-card { flex-direction: column; }
        .card-left { display: none; }
        .card-right { padding: 40px 30px; }
        .mobile-logo { display: block; }
        .card-header-custom { text-align: center; }
    }
</style>

<div class="auth-wrapper">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="glass-card">
        <div class="card-left">
            <div class="branding-content">
                <div class="desktop-logo">
                    <img src="{{ tenant_img('trikamedia.png', 'img/trikamedia.png') }}" alt="Logo">
                </div>
                <h1 class="branding-title">Lupa Password?</h1>
                <p class="branding-description">Masukkan email Anda dan kami akan mengirimkan link untuk reset password.</p>
                <div class="feature-list">
                    <div class="feature-item">Link reset dikirim via email</div>
                    <div class="feature-item">Link berlaku selama 60 menit</div>
                    <div class="feature-item">Password lama tetap aman</div>
                </div>
            </div>
        </div>

        <div class="card-right">
            <div class="mobile-logo">
                <img src="{{ tenant_img('trikamedia.png', 'img/trikamedia.png') }}" alt="Logo">
            </div>

            <div class="card-header-custom">
                <h2 class="header-title">Reset Password</h2>
                <p class="header-subtitle">Masukkan alamat email akun Anda untuk menerima link reset password.</p>
            </div>

            @if (session('status'))
                <div class="success-alert">
                    <span>✅</span> {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="input-group-custom">
                    <label for="email" class="input-label">E-Mail Address</label>
                    <input
                        id="email"
                        type="email"
                        class="input-field @error('email') is-invalid @enderror"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        autofocus
                        placeholder="your@email.com"
                    >
                    @error('email')
                        <span class="error-message"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <button type="submit" class="submit-button">Kirim Link Reset</button>

                <div class="decorative-line"></div>
                <a class="back-link" href="{{ route('login') }}">← Kembali ke Halaman Login</a>
            </form>
        </div>
    </div>
</div>
@endsection
