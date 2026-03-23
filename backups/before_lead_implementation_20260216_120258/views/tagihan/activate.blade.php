<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun Pelanggan - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .activate-container {
            max-width: 500px;
            margin: 0 auto;
        }
        .activate-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .activate-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .activate-header h3 {
            margin: 0;
            font-weight: 600;
        }
        .activate-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .activate-body {
            padding: 40px 30px;
        }
        .form-group label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            font-size: 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.1);
        }
        .btn-activate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-activate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        .info-box i {
            color: #2196F3;
            margin-right: 10px;
        }
        .info-box p {
            margin: 0;
            color: #333;
            font-size: 14px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .login-link a {
            color: #667eea;
            font-weight: 500;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .password-requirements ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .password-requirements li {
            margin: 3px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="activate-container">
            <div class="activate-card">
                <div class="activate-header">
                    <i class="fas fa-user-plus fa-3x mb-3"></i>
                    <h3>Aktivasi Akun Pelanggan</h3>
                    <p>Buat password untuk akses portal pelanggan</p>
                </div>
                <div class="activate-body">
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <p>Masukkan email dan nomor telepon yang terdaftar di sistem. Setelah verifikasi, Anda dapat membuat password untuk akses portal tagihan.</p>
                    </div>

                    @if($errors->any())
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                        @endforeach
                    </div>
                    @endif

                    <form method="POST" action="{{ url('/tagihan/activate') }}">
                        @csrf
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email Terdaftar
                            </label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" 
                                   placeholder="nama@email.com" required autofocus>
                            <small class="text-muted">Email yang terdaftar di sistem</small>
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Nomor Telepon
                            </label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone') }}" 
                                   placeholder="08123456789" required>
                            <small class="text-muted">Nomor telepon yang terdaftar di sistem</small>
                        </div>

                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> Password Baru
                            </label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" 
                                   placeholder="Buat password" required>
                            <div class="password-requirements">
                                <strong>Persyaratan password:</strong>
                                <ul>
                                    <li>Minimal 8 karakter</li>
                                    <li>Kombinasi huruf dan angka disarankan</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">
                                <i class="fas fa-lock"></i> Konfirmasi Password
                            </label>
                            <input type="password" class="form-control" 
                                   id="password_confirmation" name="password_confirmation" 
                                   placeholder="Ketik ulang password" required>
                        </div>

                        <button type="submit" class="btn btn-activate">
                            <i class="fas fa-check-circle"></i> Aktivasi Akun
                        </button>
                    </form>

                    <div class="login-link">
                        <p class="mb-0">Sudah punya akun?</p>
                        <a href="{{ url('/tagihan/login') }}">
                            <i class="fas fa-sign-in-alt"></i> Login di sini
                        </a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <p class="text-white small">
                    <i class="fas fa-shield-alt"></i> Data Anda aman dan terenkripsi
                </p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password match validation
        $('#password_confirmation').on('keyup', function() {
            var password = $('#password').val();
            var confirm = $(this).val();
            
            if (password !== confirm) {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
    </script>
</body>
</html>
