@extends('layouts.app')

@section('content')
<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .login-container {
        min-height: calc(100vh - 76px);
        display: flex;
        align-items: center;
        padding: 40px 0;
    }
    
    .login-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: none;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        max-width: 480px;
        margin: 0 auto;
    }
    
    .login-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 40px 30px;
        text-align: center;
        position: relative;
    }
    
    .login-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        pointer-events: none;
    }
    
    .login-header h4 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 600;
        position: relative;
        z-index: 2;
    }
    
    .login-header p {
        margin: 10px 0 0 0;
        opacity: 0.9;
        font-size: 0.95rem;
        position: relative;
        z-index: 2;
    }
    
    .login-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        position: relative;
        z-index: 2;
    }
    
    .login-body {
        padding: 40px 30px;
    }
    
    .form-group {
        margin-bottom: 25px;
        position: relative;
    }
    
    .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        font-size: 0.9rem;
    }
    
    .form-label i {
        margin-right: 8px;
        color: #667eea;
    }
    
    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 15px 20px;
        font-size: 0.95rem;
        background: #f8f9fa;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        background: white;
        transform: translateY(-1px);
    }
    
    .form-control.is-invalid {
        border-color: #dc3545;
    }
    
    .invalid-feedback {
        display: block;
        font-size: 0.85rem;
        margin-top: 5px;
        color: #dc3545;
    }
    
    .login-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 12px;
        padding: 15px 30px;
        font-size: 1rem;
        font-weight: 600;
        color: white;
        width: 100%;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        overflow: hidden;
    }
    
    .login-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }
    
    .login-btn:hover::before {
        left: 100%;
    }
    
    .login-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }
    
    .login-btn:active {
        transform: translateY(0);
    }
    
    .login-footer {
        padding: 20px 30px;
        background: #f8f9fa;
        text-align: center;
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .welcome-text {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    /* Input Icons */
    .input-group {
        position: relative;
    }
    
    .input-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
        z-index: 5;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .login-container {
            min-height: 100vh;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            margin: 0;
            max-width: 100%;
            width: 100%;
        }
        
        .login-header {
            padding: 25px 20px;
        }
        
        .login-body {
            padding: 25px 20px;
        }
        
        .login-header h4 {
            font-size: 1.4rem;
        }
        
        .login-header p {
            font-size: 0.9rem;
        }
        
        .login-icon {
            width: 50px;
            height: 50px;
            margin-bottom: 15px;
        }
        
        .form-control {
            padding: 12px 16px;
            font-size: 16px; /* Prevent zoom on iOS */
        }
        
        .login-btn {
            padding: 12px 25px;
            font-size: 0.95rem;
        }
        
        .welcome-text h5 {
            font-size: 1.1rem;
        }
        
        .welcome-text p {
            font-size: 0.85rem;
        }
    }
    
    @media (max-width: 480px) {
        .login-container {
            padding: 10px;
        }
        
        .login-header {
            padding: 20px 15px;
        }
        
        .login-body {
            padding: 20px 15px;
        }
        
        .login-footer {
            padding: 15px;
        }
        
        .login-header h4 {
            font-size: 1.25rem;
        }
        
        .form-control {
            padding: 10px 14px;
        }
        
        .login-btn {
            padding: 10px 20px;
            font-size: 0.9rem;
        }
    }
    
    /* Landscape mobile */
    @media (max-width: 768px) and (orientation: landscape) {
        .login-container {
            min-height: 100vh;
            padding: 10px;
        }
        
        .login-header {
            padding: 15px 20px;
        }
        
        .login-body {
            padding: 20px;
        }
        
        .login-icon {
            width: 40px;
            height: 40px;
            margin-bottom: 10px;
        }
        
        .login-header h4 {
            font-size: 1.2rem;
        }
        
        .welcome-text {
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
    }
    
    /* Error Alert Styling */
    .alert {
        border: none;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        color: white;
    }
</style>

<div class="login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 col-sm-9">
                <div class="card login-card">
                    <!-- Header Section -->
                    <div class="login-header">
                        <div class="login-icon">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                        <h4>SPK Lembur System</h4>
                        <p>Sistem Pengelolaan Surat Perintah Kerja Lembur</p>
                    </div>
                    
                    <!-- Body Section -->
                    <div class="login-body">
                        <div class="welcome-text text-center">
                            <h5>Selamat Datang!</h5>
                            <p class="text-muted">Silakan masuk untuk melanjutkan</p>
                        </div>
                        
                        <!-- Display Validation Errors -->
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                @foreach ($errors->all() as $error)
                                    {{ $error }}
                                @endforeach
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            
                            <!-- Username Field -->
                                <div class="form-group">
                                        <label for="username" class="form-label">
                                            <i class="fas fa-user"></i>
                                            Username
                                        </label>
                                        <div class="input-group">
                                            <input type="text" 
                                                class="form-control @error('username') is-invalid @enderror" 
                                                id="username" 
                                                name="username" 
                                                value="{{ old('username') }}" 
                                                placeholder="Masukkan username Anda"
                                                required>
                                            <i class="fas fa-user input-icon"></i>
                                        </div>
                                        @error('username')
                                            <div class="invalid-feedback">
                                                <i class="fas fa-times-circle me-1"></i>
                                                {{ $message }}
                                            </div>
                                        @enderror
                            </div>
                            
                            <!-- Password Field -->
                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i>
                                    Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Masukkan password Anda"
                                           required>
                                    <i class="fas fa-key input-icon"></i>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-times-circle me-1"></i>
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            
                            <!-- Remember Me -->
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                    <label class="form-check-label" for="remember">
                                        Ingat saya
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Login Button -->
                            <div class="form-group">
                                <button type="submit" class="login-btn">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Masuk
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Footer Section -->
                    <div class="login-footer">
                        <div class="d-flex justify-content-center align-items-center">
                            <i class="fas fa-shield-alt me-2 text-success"></i>
                            <span>Sistem keamanan terjamin</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection