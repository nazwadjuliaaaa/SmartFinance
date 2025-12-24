<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SmartFinance</title>
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body class="login-page">
    <div class="auth-container">
        <div class="auth-header" style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
            <img src="{{ asset('images/logo.jpg') }}" alt="Logo" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid #d4af37; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
            SmartFinance
        </div>
        
        <div class="auth-card">
            <div class="auth-title">LOGIN</div>
            
            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                @if($errors->any())
                    <div style="color: red; margin-bottom: 10px; text-align: center;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <button type="submit" class="btn-primary">Login</button>
            </form>
            <div style="text-align: center; margin-top: 20px;">
                <a href="{{ route('register') }}" style="color: var(--primary-color);">Belum punya akun? Register</a>
            </div>
        </div>
    </div>
</body>
</html>
