<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — Pendataan Keluarga Sehat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-header">
                <div class="mark">SIPANDAI</div>
                <div class="app-name">Pendataan Keluarga Sehat</div>
            </div>

            <div class="auth-body">
                <h2>Masuk</h2>
                <p class="auth-hint">Masuk untuk mencatat, memantau, dan merekap data keluarga sehat di wilayah Anda.</p>

                @if ($errors->any())
                    <div class="auth-error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                    </div>
                    <div class="field">
                        <label for="password">Kata Sandi</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <div class="field" style="margin-bottom:20px;">
                        <label for="remember" style="display:flex;align-items:center;gap:6px;font-weight:500;">
                            <input type="checkbox" id="remember" name="remember" style="width:auto;">
                            Ingat saya
                        </label>
                    </div>
                    <button type="submit" class="btn auth-btn">Masuk</button>
                </form>

                <div class="auth-footer">
                    <span>Butuh bantuan? Hubungi <strong>admin wilayah</strong> Anda.</span>
                    <span>v2</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>