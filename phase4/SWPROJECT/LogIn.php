<?php
require_once 'config.php';

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => !empty($_SERVER['HTTPS']),
    ]);
}

$error_message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error_message = 'جميع الحقول مطلوبة';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'صيغة البريد الإلكتروني غير صحيحة';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT userID, name, email, password, role FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Clear any existing session data
                $_SESSION = [];
                
                // Set session variables
                $_SESSION['user_id'] = $user['userID'];
                $_SESSION['userID']  = $user['userID'];   // للتوافق مع صفحات ثانية
                $_SESSION['name']    = $user['name'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: adhome.php');
                    exit;
                } else {
                    header('Location: homep.php');
                    exit;
                }
            } else {
                // إيميل أو باسورد غلط
                $error_message = 'البريد الالكتروني  أو كلمة المرور غير صحيحة';
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $error_message = 'حدث خطأ غير متوقع. الرجاء المحاولة لاحقاً.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | مِـداد</title>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Doran';
            src: url('font/Doran-Regular.woff2') format('woff2'),
                 url('font/Doran-Regular.woff') format('woff'),
                 url('font/Doran-Regular.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Doran';
            src: url('font/Doran-Bold.woff2') format('woff2'),
                 url('font/Doran-Bold.woff') format('woff'),
                 url('font/Doran-Bold.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --accent: #E8D1A7;
            --burgundy: #7B1E3A;
            --burgundy-light: rgba(123, 30, 58, 0.08);
            --text-dark: #2D2D2D;
            --text-light: #555555;
            --off-white: #F9F9F9;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 6px 18px rgba(123, 30, 58, 0.15);
            --error: #e74c3c;
            --success: #2ecc71;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Almarai', 'Arial', sans-serif;
        }

        body {
            background: #f8f5f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-dark);
            position: relative;
            overflow-x: hidden;
        }

        .background-art {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.4;
        }

        .background-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 80%, var(--burgundy-light) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, var(--accent) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(123, 30, 58, 0.05) 0%, transparent 50%);
            animation: floatBackground 20s ease-in-out infinite;
        }

        .background-image {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: url('image/BACKGROUND.png');
            background-size: 60%;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.15;
            filter: grayscale(100%) sepia(30%) hue-rotate(300deg);
            mix-blend-mode: multiply;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            background: var(--burgundy);
            opacity: 0.1;
            border-radius: 50%;
            animation: floatShape 15s ease-in-out infinite;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -150px;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            bottom: -100px;
            left: 10%;
            animation-delay: -5s;
            background: var(--accent);
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            top: 30%;
            left: -75px;
            animation-delay: -10s;
        }

        @keyframes floatBackground {
            0%, 100% {
                transform: scale(1) rotate(0deg);
            }
            50% {
                transform: scale(1.1) rotate(180deg);
            }
        }

        @keyframes floatShape {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            33% {
                transform: translateY(-30px) rotate(120deg);
            }
            66% {
                transform: translateY(20px) rotate(240deg);
            }
        }

        /* الهيدر */
        .main-header {
            background: transparent;
            padding: 12px 0;
            margin: 10px 0;
        }

        .header-shell {
            width: min(1100px, calc(100% - 24px));
            margin: 0 auto;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 999px;
            padding: 8px 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            direction: ltr;
            border: 1px solid rgba(123, 30, 58, 0.1);
        }

        .header-start {
            justify-self: start;
            display: flex;
            align-items: center;
        }

        .header-center {
            justify-self: center;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-end {
            justify-self: end;
            display: flex;
            align-items: center;
        }

        .brand-image {
            width: 40px;
            height: 40px;
            object-fit: contain;
            display: inline-block;
        }

        .back-button {
            background: var(--burgundy);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .back-button:hover {
            background: #5a152c;
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        @media (max-width: 600px) {
            .header-shell {
                grid-template-columns: auto 1fr auto;
                padding: 6px 10px;
            }
            
            .brand-image {
                width: 35px;
                height: 35px;
            }
            
            .back-button {
                padding: 6px 15px;
                font-size: 0.85rem;
            }
        }

        /* المحتوى الرئيسي */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            padding: 40px 35px;
            width: 100%;
            max-width: 480px;
            box-shadow: 
                0 8px 32px rgba(123, 30, 58, 0.1),
                0 2px 8px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(123, 30, 58, 0.15);
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--burgundy), var(--accent), var(--burgundy));
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% {
                background-position: -200% 0;
            }
            50% {
                background-position: 200% 0;
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .logo-section::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 25%;
            width: 50%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--burgundy), transparent);
        }

        .title {
            font-family: 'Doran', 'Arial', sans-serif;
            font-size: 2.4rem;
            color: var(--burgundy);
            margin-bottom: 8px;
            font-weight: bold;
        }

        .subtitle {
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 300;
        }

        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .form-input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid rgba(123, 30, 58, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--burgundy);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(123, 30, 58, 0.1);
            transform: translateY(-1px);
        }

        .field-feedback {
            margin-top: 6px;
            font-size: 0.8rem;
        }

        .field-feedback.error {
            color: var(--error);
        }

        .field-feedback.success {
            color: var(--success);
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--burgundy), #5a152c);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Doran', 'Arial', sans-serif;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
            transition: left 0.4s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(123,30,58,0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
            text-align: center;
            font-size: 0.9rem;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .login-link a {
            color: var(--burgundy);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Footer styles */
        .main-footer {
            background-color: #ebe2db;
            width: 100%;
            padding: 30px 16px 16px;
            color: var(--burgundy);
            margin-top: 50px;
        }

        .footer-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .footer-left, .footer-center, .footer-right {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .footer-left h3 {
            color: var(--burgundy);
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .footer-icons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .icon-box {
            width: 35px;
            height: 35px;
            color: #743014;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .icon-box:hover {
            background-color: #E8D1A7;
            color: #743014;
        }

        .footer-center img.footer-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-bottom: 6px;
        }

        .footer-center p {
            font-weight: 600;
            color: var(--burgundy);
            font-size: 0.9rem;
        }

        .footer-right {
            flex-direction: row;
            align-items: center;
            gap: 5px;
            color: var(--burgundy);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .footer-right svg {
            width: 16px;
            height: 16px;
            stroke: var(--burgundy);
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 32px 24px;
            }
        }

        @media (max-width: 700px) {
            .footer-container {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="background-art">
        <div class="background-pattern"></div>
        <div class="background-image"></div>
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </div>

    <header class="main-header">
        <div class="header-shell">
            <div class="header-start">
                <a href="index.html" class="back-button">← الرجوع للرئيسية</a>
            </div>
            <div class="header-center"></div>
            <div class="header-end">
                <img class="brand-image" src="image/LOGO SW.png" alt="Medad Logo">
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="login-container">
            <div class="logo-section">
                <h1 class="title">مِـداد</h1>
                <p class="subtitle">مرحباً بعودتك إلى مجتمعنا الأدبي</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- رسالة خطأ من الفرونت فقط -->
            <div id="clientError" class="error-message" style="display:none;"></div>

            <form method="POST" action="" id="loginForm" novalidate>
                <div class="form-group">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <input
                        type="text"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="example@email.com"
                        value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <div id="emailFeedback" class="field-feedback"></div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <div class="password-container">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="أدخل كلمة المرور"
                        >
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">إظهار</button>
                    </div>
                </div>

                <button type="submit" class="submit-btn">تسجيل الدخول</button>

                <div class="login-link">
                    ليس لديك حساب؟ <a href="SignUp.php">إنشاء حساب جديد</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="main-footer">
      <div class="footer-container">
        <div class="footer-left">
          <h3>تواصل معنا</h3>
          <div class="footer-icons">
            <a href="https://x.com/MedadKsu" class="icon-box" title="X">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path d="M18.244 2H21L14.126 10.088 22 22h-5.905l-4.67-6.726L6.08 22H3l7.31-8.433L2 2h6.08l4.132 5.897L18.244 2zM7.118 4l9.764 13.997h1.999L9.12 4H7.118z"/>
              </svg>
            </a>
            <a href="mailto:MedadKsu@gmail.com" class="icon-box" title="Email">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
              </svg>
            </a>
          </div>
        </div>

        <div class="footer-center">
          <img src="image/LOGO SW.png" alt="شعار مداد" class="footer-logo">
          <p>© جميع الحقوق محفوظة 2025</p>
        </div>

        <div class="footer-right">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
          </svg>
          <span>المملكة العربية السعودية</span>
        </div>
      </div>
    </footer>

    <script>
        // إظهار/إخفاء كلمة المرور
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const btn = document.querySelector('.toggle-password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            btn.textContent = type === 'password' ? 'إظهار' : 'إخفاء';
        }

        // فحص صيغة الإيميل في الفرونت فقط (بدون تشيك وجوده)
        function validateEmailFormat() {
            const emailInput    = document.getElementById('email');
            const emailFeedback = document.getElementById('emailFeedback');
            const email         = emailInput.value.trim();

            emailFeedback.textContent = '';
            emailFeedback.className   = 'field-feedback';

            // لو فاضي، ما نعطي رسالة هنا (تُستخدم الرسالة العامة فوق)
            if (email === '') return true;

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(email)) {
                emailFeedback.textContent = 'صيغة البريد الإلكتروني غير صحيحة';
                emailFeedback.classList.add('error');
                return false;
            }

            emailFeedback.textContent = 'صيغة البريد الإلكتروني صحيحة';
            emailFeedback.classList.add('success');
            return true;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const emailInput    = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const form          = document.getElementById('loginForm');
            const clientError   = document.getElementById('clientError');

            emailInput.addEventListener('input', validateEmailFormat);
            emailInput.addEventListener('blur', validateEmailFormat);

            form.addEventListener('submit', function (e) {
                clientError.style.display = 'none';
                clientError.textContent   = '';

                const emailVal = emailInput.value.trim();
                const pwdVal   = passwordInput.value;

                // لو أي حقل فاضي → رسالة عامة فوق
                if (!emailVal || !pwdVal) {
                    e.preventDefault();
                    clientError.textContent = 'جميع الحقول مطلوبة';
                    clientError.style.display = 'block';
                    return;
                }

                const emailOk = validateEmailFormat();

                if (!emailOk) {
                    e.preventDefault();
                    return;
                }
            });
        });
    </script>
</body>
</html>
