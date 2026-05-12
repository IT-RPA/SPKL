<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SPKL System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            overflow: hidden;
            background: #0a0e27;
        }

        .login-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* ==================== LEFT PANEL ==================== */
        .login-left {
            flex: 1.15;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: linear-gradient(160deg, #0f1435 0%, #1a1f4e 35%, #252b6a 70%, #1a1f4e 100%);
        }

        /* Animated mesh gradient orbs */
        .login-left::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, transparent 70%);
            top: -200px;
            right: -150px;
            animation: floatOrb 8s ease-in-out infinite;
        }

        .login-left::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.2) 0%, transparent 70%);
            bottom: -180px;
            left: -100px;
            animation: floatOrb 10s ease-in-out infinite reverse;
        }

        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -20px) scale(1.05); }
            66% { transform: translate(-20px, 15px) scale(0.95); }
        }

        /* Subtle grid pattern */
        .grid-overlay {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            z-index: 1;
        }

        /* Floating particles */
        .particles {
            position: absolute;
            inset: 0;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(139, 92, 246, 0.5);
            border-radius: 50%;
            animation: particleFloat linear infinite;
        }

        .particle:nth-child(1) { left: 10%; top: 20%; animation-duration: 12s; animation-delay: 0s; width: 4px; height: 4px; background: rgba(99,102,241,0.6); }
        .particle:nth-child(2) { left: 80%; top: 70%; animation-duration: 15s; animation-delay: 2s; }
        .particle:nth-child(3) { left: 30%; top: 80%; animation-duration: 10s; animation-delay: 4s; width: 5px; height: 5px; background: rgba(167,139,250,0.4); }
        .particle:nth-child(4) { left: 60%; top: 30%; animation-duration: 18s; animation-delay: 1s; }
        .particle:nth-child(5) { left: 50%; top: 50%; animation-duration: 14s; animation-delay: 3s; width: 4px; height: 4px; }
        .particle:nth-child(6) { left: 20%; top: 60%; animation-duration: 11s; animation-delay: 5s; background: rgba(99,102,241,0.4); }
        .particle:nth-child(7) { left: 70%; top: 15%; animation-duration: 16s; animation-delay: 0s; width: 3px; height: 3px; }
        .particle:nth-child(8) { left: 90%; top: 45%; animation-duration: 13s; animation-delay: 2s; background: rgba(167,139,250,0.5); }

        @keyframes particleFloat {
            0% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) translateX(30px); opacity: 0; }
        }

        .left-content {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 40px;
            max-width: 520px;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 22px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 50px;
            margin-bottom: 50px;
            backdrop-filter: blur(10px);
        }

        .brand-badge i {
            color: #a78bfa;
            font-size: 1.1rem;
        }

        .brand-badge span {
            color: #fff;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .left-illustration {
            width: 100%;
            max-width: 420px;
            margin: 0 auto 45px;
            position: relative;
        }

        .left-illustration img {
            width: 100%;
            height: auto;
            filter: drop-shadow(0 20px 40px rgba(0,0,0,0.3));
            border-radius: 16px;
        }

        .left-heading {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 14px;
            letter-spacing: -0.3px;
        }

        .left-heading span {
            background: linear-gradient(135deg, #a78bfa, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .left-desc {
            color: rgba(255,255,255,0.5);
            font-size: 0.9rem;
            line-height: 1.7;
            max-width: 380px;
            margin: 0 auto;
        }

        /* ==================== RIGHT PANEL ==================== */
        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            padding: 60px 40px;
            position: relative;
        }

        /* Subtle decoration on right panel */
        .login-right::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #6366f1, #a78bfa, #6366f1);
        }

        .login-form-container {
            width: 100%;
            max-width: 400px;
        }

        /* Avatar icon */
        .avatar-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 28px;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }

        .avatar-icon i {
            color: #fff;
            font-size: 1.6rem;
        }

        .login-title {
            font-size: 1.85rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            color: #9ca3af;
            font-size: 0.92rem;
            margin-bottom: 36px;
            font-weight: 400;
        }

        /* Form fields */
        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.85rem;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .input-icon-left {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.9rem;
            transition: color 0.2s;
            z-index: 2;
        }

        .input-wrapper .input-icon-right {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.9rem;
            cursor: pointer;
            transition: color 0.2s;
            z-index: 2;
        }

        .input-wrapper .input-icon-right:hover {
            color: #6366f1;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px 14px 46px;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.92rem;
            font-family: 'Inter', sans-serif;
            color: #111827;
            background: #f9fafb;
            transition: all 0.25s ease;
            outline: none;
        }

        .form-input:focus {
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.08);
        }

        .form-input:focus ~ .input-icon-left {
            color: #6366f1;
        }

        .form-input::placeholder {
            color: #c0c4cc;
            font-weight: 400;
        }

        .form-input.is-invalid {
            border-color: #ef4444;
        }

        .invalid-feedback {
            display: block;
            color: #ef4444;
            font-size: 0.78rem;
            margin-top: 6px;
            font-weight: 500;
        }

        /* Login options row */
        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            margin-top: -2px;
        }

        .custom-check {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .custom-check input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #6366f1;
            cursor: pointer;
        }

        .custom-check label {
            font-size: 0.82rem;
            color: #6b7280;
            cursor: pointer;
            user-select: none;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: #fff;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.6s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.35);
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Divider */
        .login-divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 24px 0;
        }

        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .login-divider span {
            font-size: 0.78rem;
            color: #9ca3af;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Footer info */
        .login-footer-info {
            text-align: center;
            margin-top: 24px;
        }

        .login-footer-info p {
            font-size: 0.8rem;
            color: #9ca3af;
        }

        .login-footer-info .secure-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 50px;
            font-size: 0.78rem;
            color: #16a34a;
            font-weight: 500;
        }

        /* Error alert */
        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
        }

        .alert-error i {
            color: #ef4444;
            margin-top: 2px;
            font-size: 0.9rem;
        }

        .alert-error .alert-text {
            font-size: 0.85rem;
            color: #991b1b;
            line-height: 1.5;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 992px) {
            .login-left {
                flex: 0.8;
            }
            .left-illustration {
                max-width: 320px;
            }
            .left-heading {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
            }

            .login-left {
                display: none;
            }

            .login-right {
                min-height: 100vh;
                padding: 30px 24px;
            }

            .login-right::before {
                display: none;
            }

            .login-form-container {
                max-width: 100%;
            }

            .login-title {
                font-size: 1.5rem;
            }

            .form-input {
                padding: 12px 14px 12px 42px;
                font-size: 16px; /* Prevent iOS zoom */
            }
        }

        /* Entrance animation */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-form-container {
            animation: slideUp 0.6s ease-out;
        }

        .left-content {
            animation: slideUp 0.8s ease-out;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- ==================== LEFT PANEL ==================== -->
    <div class="login-left">
        <div class="grid-overlay"></div>
        <div class="particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>

        <div class="left-content">
            <div class="brand-badge">
                <i class="fas fa-bolt"></i>
                <span>SPKL System</span>
            </div>

            <div class="left-illustration">
                <img src="{{ asset('images/buatlogin.png') }}" alt="SPKL Illustration">
            </div>

            <h2 class="left-heading">Manage Overtime <span>Smarter & Faster</span></h2>
            <p class="left-desc">Sistem Pengelolaan Surat Perintah Kerja Lembur yang terintegrasi. Kelola pengajuan, approval, dan laporan dalam satu platform.</p>
        </div>
    </div>

    <!-- ==================== RIGHT PANEL ==================== -->
    <div class="login-right">
        <div class="login-form-container">
            <!-- Avatar -->
            <div class="avatar-icon">
                <i class="fas fa-fingerprint"></i>
            </div>

            <h1 class="login-title">Welcome back</h1>

            <!-- Error Messages -->
            @if ($errors->any())
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="alert-text">
                        @foreach ($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Username -->
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-wrapper">
                        <input type="text"
                               class="form-input @error('username') is-invalid @enderror"
                               id="username"
                               name="username"
                               value="{{ old('username') }}"
                               placeholder="Enter your username"
                               required
                               autocomplete="username">
                        <i class="fas fa-user input-icon-left"></i>
                    </div>
                    @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input type="password"
                               class="form-input @error('password') is-invalid @enderror"
                               id="password"
                               name="password"
                               placeholder="Enter your password"
                               required
                               autocomplete="current-password">
                        <i class="fas fa-lock input-icon-left"></i>
                        <i class="fas fa-eye input-icon-right" id="togglePassword"></i>
                    </div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Options -->
                <div class="login-options">
                    <div class="custom-check">
                        <input type="checkbox" name="remember" id="remember">
                        <label for="remember">Remember me</label>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login">
                    <i class="fas fa-arrow-right-to-bracket" style="margin-right: 8px;"></i>
                    Sign In
                </button>
            </form>


            <div class="login-footer-info">
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        if(togglePassword) {
            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Auto focus username
        const username = document.querySelector('#username');
        if(username && !username.value) {
            username.focus();
        }
    });
</script>

</body>
</html>