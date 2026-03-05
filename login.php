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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
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

        .logo i {
            font-size: 60px;
            color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo h2 {
            margin-top: 10px;
            color: #333;
            font-size: 24px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 12px;
            color: #999;
            font-size: 16px;
        }

        input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            outline: none;
        }

        input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        input::placeholder {
            color: #aaa;
            font-size: 14px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            color: #999;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #667eea;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            cursor: pointer;
        }

        .remember input[type="checkbox"] {
            width: auto;
            margin-right: 5px;
        }

        .forgot-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .forgot-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        button {
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }

        button:active {
            transform: translateY(0);
        }

        button.loading {
            opacity: 0.7;
            cursor: not-allowed;
        }

        button.loading .btn-text {
            visibility: hidden;
        }

        button.loading .spinner {
            display: inline-block;
        }

        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            position: absolute;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .error-message {
            background: #fee;
            color: #e74c3c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
            align-items: center;
            gap: 8px;
            border-left: 4px solid #e74c3c;
        }

        .error-message i {
            font-size: 16px;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
            align-items: center;
            gap: 8px;
            border-left: 4px solid #2e7d32;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
            border: 1px dashed #ddd;
        }

        .info-box i {
            color: #667eea;
            margin-right: 5px;
        }

        .info-box p {
            margin: 5px 0;
        }

        .demo-credentials {
            background: white;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 12px;
            border: 1px solid #e0e0e0;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            color: #999;
            font-size: 12px;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .test-credentials {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            font-size: 12px;
        }

        .test-credentials button {
            padding: 5px 10px;
            background: #f0f0f0;
            color: #333;
            font-size: 11px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .test-credentials button:hover {
            background: #e0e0e0;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-shield-alt"></i>
            <h2>Admin Login</h2>
            <p>Lost & Found Management System</p>
        </div>
        <div class="error-message" id="errorMessage">
            <i class="fas fa-exclamation-circle"></i>
            <span id="errorText"></span>
        </div>
        <div class="success-message" id="successMessage">
            <i class="fas fa-check-circle"></i>
            <span id="successText"></span>
        </div>
        <form id="loginForm" onsubmit="handleLogin(event)">
            <div class="form-group">
                <label for="student_id">
                    <i class="fas fa-id-card"></i> Student ID / Email
                </label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input 
                        type="text" 
                        id="student_id" 
                        name="student_id" 
                        placeholder="Enter your student ID" 
                        required
                        autocomplete="off"
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <div class="input-group">
                    <i class="fas fa-key input-icon"></i>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password" 
                        required
                    >
                    <i class="fas fa-eye toggle-password" onclick="togglePassword()" id="togglePassword"></i>
                </div>
            </div>

            <div class="remember-forgot">
                <label class="remember">
                    <input type="checkbox" id="remember" checked>
                    <i class="fas fa-check-square"></i> Remember me
                </label>
                <a href="#" class="forgot-link" onclick="showForgotPassword()">
                    <i class="fas fa-question-circle"></i> Forgot password?
                </a>
            </div>

            <button type="submit" id="loginBtn">
                <span class="spinner"></span>
                <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Login to Dashboard</span>
            </button>
        </form>
    </div>

    <script>
        const API_URL = 'https://astufindit.x10.mx/index/api.php';

        const savedAdmin = localStorage.getItem('adminData') || sessionStorage.getItem('adminData');
        if (savedAdmin) {
            window.location.href = 'admin.php';
        }

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePassword');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            errorText.textContent = message;
            errorDiv.style.display = 'flex';
            
            document.getElementById('successMessage').style.display = 'none';
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            const successText = document.getElementById('successText');
            successText.textContent = message;
            successDiv.style.display = 'flex';
            
            document.getElementById('errorMessage').style.display = 'none';
        }
        function setLoading(isLoading) {
            const loginBtn = document.getElementById('loginBtn');
            
            if (isLoading) {
                loginBtn.classList.add('loading');
                loginBtn.disabled = true;
            } else {
                loginBtn.classList.remove('loading');
                loginBtn.disabled = false;
            }
        }
        async function handleLogin(event) {
            event.preventDefault();
            
            const student_id = document.getElementById('student_id').value.trim();
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;
            
            if (!student_id || !password) {
                showError('Please enter both Student ID and Password');
                return;
            }
            
            setLoading(true);
            
            try {
                console.log('Attempting login with:', { student_id });
                
                const response = await fetch(`${API_URL}?action=admin-login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        student_id: student_id,
                        password: password
                    })
                });

                const data = await response.json();
                console.log('Login response:', data);
                
                if (data.success && data.user) {
                    const userData = {
                        id: data.user.id,
                        user_string_id: data.user.user_string_id,
                        student_id: data.user.student_id,
                        full_name: data.user.full_name,
                        phone: data.user.phone || '',
                        email: data.user.email || data.user.student_id,
                        role: data.user.role
                    };
                    
                    if (remember) {
                        localStorage.setItem('adminData', JSON.stringify(userData));
                    } else {
                        sessionStorage.setItem('adminData', JSON.stringify(userData));
                    }
                    
                    showSuccess('Login successful! Redirecting...');
                    
                    setTimeout(() => {
                        window.location.href = 'admin.php';
                    }, 1000);
                } else {
                    showError(data.message || 'Login failed - Invalid credentials');
                    setLoading(false);
                }
            } catch (error) {
                showError('Network error. Please check your connection.');
                setLoading(false);
            }
        }

        function showForgotPassword() {
            document.getElementById('forgotModal').style.display = 'flex';
        }

        function closeForgotModal() {
            document.getElementById('forgotModal').style.display = 'none';
        }

        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginBtn').click();
            }
        });
        document.getElementById('student_id').addEventListener('input', function() {
            document.getElementById('errorMessage').style.display = 'none';
        });
        
        document.getElementById('password').addEventListener('input', function() {
            document.getElementById('errorMessage').style.display = 'none';
        });
    </script>
</body>
</html>