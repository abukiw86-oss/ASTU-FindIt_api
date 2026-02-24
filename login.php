<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Lost & Found Mediator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 400px;
            padding: 40px;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input.error {
            border-color: #e74c3c;
        }

        .error-message {
            color: #e74c3c;
            font-size: 13px;
            margin-top: 5px;
            min-height: 20px;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .login-btn.loading {
            position: relative;
            color: transparent;
        }

        .login-btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 3px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
            border-left: 3px solid #667eea;
        }

        .info-box strong {
            color: #333;
            display: block;
            margin-bottom: 5px;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
            z-index: 2000;
        }

        .toast.success { border-left: 4px solid #27ae60; }
        .toast.error { border-left: 4px solid #e74c3c; }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-shield-alt" style="font-size: 50px; color: #667eea; margin-bottom: 15px;"></i>
            <h1>Admin Login</h1>
            <p>Lost & Found Mediator System</p>
        </div>

        <div class="form-group">
            <label><i class="far fa-envelope" style="margin-right: 5px;"></i> Email</label>
            <input type="email" id="email" placeholder="Enter your email" value="abukiw86@gmail.com">
            <div class="error-message" id="emailError"></div>
        </div>

        <div class="form-group">
            <label><i class="fas fa-lock" style="margin-right: 5px;"></i> Password</label>
            <input type="password" id="password" placeholder="Enter your password">
            <div class="error-message" id="passwordError"></div>
        </div>

        <button class="login-btn" id="loginBtn" onclick="handleLogin()">Login to Dashboard</button>

        <div class="info-box">
            <strong><i class="fas fa-info-circle"></i> Admin Access Only</strong>
            Only users with admin role can access the dashboard. 
            Please use your registered email and password.
        </div>
    </div>

    <div class="toast" id="toast">
        <i class="fas" id="toastIcon"></i>
        <span id="toastMessage"></span>
    </div>

    <script>
        const API_URL = 'https://astufindit.x10.mx/index/api.php';

        const savedAdmin = localStorage.getItem('adminData');
        if (savedAdmin) {
            window.location.href = 'admin.php';
        }

        async function handleLogin() {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');

            // Reset errors
            document.getElementById('emailError').textContent = '';
            document.getElementById('passwordError').textContent = '';
            document.getElementById('email').classList.remove('error');
            document.getElementById('password').classList.remove('error');

            // Validate
            if (!email) {
                document.getElementById('emailError').textContent = 'Email is required';
                document.getElementById('email').classList.add('error');
                return;
            }

            if (!password) {
                document.getElementById('passwordError').textContent = 'Password is required';
                document.getElementById('password').classList.add('error');
                return;
            }

            loginBtn.classList.add('loading');
            loginBtn.disabled = true;

            try {
                const response = await fetch(`${API_URL}?action=login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (data.success && data.user) {
                    if (data.user.role === 'admin') {
                        localStorage.setItem('adminData', JSON.stringify(data.user));
                        window.location.href = 'admin.php';
                    } else {
                        showToast('Access denied. Admin role required.', 'error');
                        document.getElementById('email').classList.add('error');
                        document.getElementById('password').classList.add('error');
                    }
                } else {
                    showToast(data.message || 'Invalid credentials', 'error');
                    document.getElementById('email').classList.add('error');
                    document.getElementById('password').classList.add('error');
                }
            } catch (error) {
                console.error('Login error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                loginBtn.classList.remove('loading');
                loginBtn.disabled = false;
            }
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toastIcon');
            const msg = document.getElementById('toastMessage');
            
            icon.className = `fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}`;
            msg.textContent = message;
            toast.className = `toast ${type}`;
            toast.style.display = 'flex';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleLogin();
            }
        });
    </script>
</body>
</html>