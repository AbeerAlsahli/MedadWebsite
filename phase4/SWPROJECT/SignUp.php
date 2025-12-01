<?php
require_once 'config.php';

// بداية الجلسة بشكل آمن
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => !empty($_SERVER['HTTPS']),
    ]);
}

/*
 * ===========================
 * 1) مود تشيك الإيميل (AJAX)
 * ===========================
 */
if (isset($_GET['check_email']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['exists' => false, 'valid' => false]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT userID FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $exists = (bool)$stmt->fetch();

        echo json_encode([
            'exists' => $exists,
            'valid'  => true,
        ]);
    } catch (PDOException $e) {
        error_log("check_email inline error: " . $e->getMessage());
        echo json_encode(['exists' => false, 'valid' => false]);
    }
    exit;
}

/*
 * ===========================
 * 2) مود الصفحة العادي (عرض + تسجيل)
 * ===========================
 */

$error_message   = '';
$success_message = '';

$username        = $_POST['username']  ?? '';
$email           = $_POST['email']     ?? '';
$birthdate       = $_POST['birthdate'] ?? '';
$interests       = $_POST['interests'] ?? '';

$hasMinLength    = false;
$hasUppercase    = false;
$hasSpecialChar  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تنظيف داخلي
    $username  = trim($username);
    $email     = trim($email);
    $password  = $_POST['password'] ?? '';
    $birthdate = trim($birthdate);
    $interests = trim($interests);

    $cleanUsername = $username;

    // شروط الباسورد
    $hasMinLength   = strlen($password) >= 8;
    $hasUppercase   = (bool)preg_match('/[A-Z]/', $password);
    $hasSpecialChar = (bool)preg_match('/[!@#$%^&*]/', $password);

    // التحقق من اكتمال الحقول
    $form_valid = $username !== '' && $email !== '' && $password !== '' && $birthdate !== '' && $interests !== '';

    if (!$form_valid) {
        $error_message = 'جميع الحقول مطلوبة';
    }
    // شروط اسم المستخدم
    elseif (mb_strlen($cleanUsername) < 2) {
        $error_message = 'اسم المستخدم يجب أن يحتوي على حرفين على الأقل.';
    } elseif (mb_strlen($cleanUsername) > 40) {
        $error_message = 'اسم المستخدم طويل جدًا، الحد الأقصى 40 حرف.';
    } elseif (!preg_match('/[a-zA-Z0-9ء-ي]/u', $cleanUsername)) {
        $error_message = 'اسم المستخدم يجب أن يحتوي على حرف أو رقم واحد على الأقل.';
    }
    // تحقق من الإيميل
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'صيغة البريد الإلكتروني غير صحيحة';
    }
    // تحقق من طول كلمة المرور
    elseif (!$hasMinLength) {
        $error_message = 'كلمة المرور يجب أن تكون 8 خانات على الأقل';
    }
    // تحقق من شروط كلمة المرور
    elseif (!$hasUppercase || !$hasSpecialChar) {
        $error_message = 'كلمة المرور يجب أن تحتوي على حرف كبير واحد ورمز خاص واحد على الأقل (!@#$%^&*)';
    } else {
        try {
            // تحقق من العمر (16 سنة أو أكثر)
            $birthdate_obj = new DateTime($birthdate);
            $today         = new DateTime();
            $age           = $today->diff($birthdate_obj)->y;

            if ($age < 16) {
                $error_message = 'يجب أن يكون عمرك 16 سنة على الأقل';
            } else {
                // تحقق من عدم تكرار البريد الإلكتروني
                $stmt = $pdo->prepare("SELECT userID FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);

                if ($stmt->fetch()) {
                    $error_message = 'البريد الإلكتروني مسجل مسبقاً';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
               INSERT INTO users (name, email, password, role, birthdate, interests, notificationPreferences) 
                VALUES (?, ?, ?, 'user', ?, ?, 'email')
                  ");

                $stmt->execute([$username, $email, $hashedPassword, $birthdate, $interests]);

                    $userId = $pdo->lastInsertId();

                    $_SESSION['user_id'] = $userId;
                    $_SESSION['name']    = $username;
                    $_SESSION['email']   = $email;
                    $_SESSION['role']    = 'user';

                    $success_message = "مرحباً {$username}! تم تسجيلك بنجاح. سيتم توجيهك تلقائياً...";
                }
            }
        } catch (PDOException $e) {
            error_log("Signup Error - IP: {$_SERVER['REMOTE_ADDR']} - Email: $email - Error: " . $e->getMessage());
            $error_message = 'حدث خطأ غير متوقع. الرجاء المحاولة لاحقاً.';
        } catch (Exception $e) {
            error_log("Date Error - IP: {$_SERVER['REMOTE_ADDR']} - Error: " . $e->getMessage());
            $error_message = 'تاريخ الميلاد غير صحيح';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مِـداد | Medad</title>
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

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            padding: 50px 40px;
            width: 100%;
            max-width: 500px;
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

        .login-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(123, 30, 58, 0.03) 0%, 
                transparent 30%, 
                transparent 70%, 
                rgba(232, 209, 167, 0.03) 100%);
            pointer-events: none;
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
            margin-bottom: 40px;
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
            font-size: 2.8rem;
            color: var(--burgundy);
            margin-bottom: 8px;
            font-weight: bold;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .subtitle {
            color: var(--text-light);
            font-size: 1.2rem;
            font-weight: 300;
        }

        .form-group {
            margin-bottom: 30px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1rem;
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid rgba(123, 30, 58, 0.1);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--burgundy);
            background: white;
            box-shadow: 0 0 0 4px rgba(123, 30, 58, 0.1);
            transform: translateY(-2px);
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
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .password-requirements {
            margin-top: 8px;
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }

        .requirement.valid {
            color: var(--success);
        }

        .requirement.invalid {
            color: var(--error);
        }

        .requirement-icon {
            margin-left: 5px;
            font-size: 0.7rem;
        }

        .interests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .interest-card {
            background: rgba(255, 255, 255, 0.6);
            border: 2px solid rgba(123, 30, 58, 0.1);
            border-radius: 12px;
            padding: 14px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .interest-card:hover {
            border-color: var(--burgundy);
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(123, 30, 58, 0.15);
        }

        .interest-card.selected {
            background: var(--burgundy);
            color: white;
            border-color: var(--burgundy);
            box-shadow: 
                0 6px 20px rgba(123, 30, 58, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .interest-card.selected::after {
            content: '✓';
            position: absolute;
            top: 6px;
            left: 10px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            right: 0;
            width: 45%;
            height: 1px;
            background: rgba(123, 30, 58, 0.2);
        }

        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            width: 45%;
            height: 1px;
            background: rgba(123, 30, 58, 0.2);
        }

        .date-input {
            direction: ltr;
            text-align: right;
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--burgundy), #5a152c);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 14px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Doran', 'Arial', sans-serif;
            margin-top: 15px;
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 8px 25px rgba(123, 30, 58, 0.3),
                0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .login-link {
            text-align: center;
            margin-top: 30px;
            color: var(--text-light);
        }

        .login-link a {
            color: var(--burgundy);
            text-decoration: none;
            font-weight: 600;
            position: relative;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--burgundy);
            transition: width 0.3s ease;
        }

        .login-link a:hover::after {
            width: 100%;
        }

     
    .main-footer {
      background-color: #ebe2db;
      width: 100%;
      padding: 30px 16px 16px;
      margin-top: 60px;
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
      margin-bottom: 12px;
      font-size: 1.1rem;
    }

    .footer-icons {
      display: flex;
      gap: 12px;
      justify-content: center;
    }

    .icon-box {
      width: 40px; 
      height: 40px;
      color:var(--burgundy);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .icon-box:hover {
      background-color: var(--burgundy);
      color: white;
      transform: translateY(-3px);
      box-shadow: 0 4px 12px rgba(123, 30, 58, 0.2);
    }

    .footer-center img.footer-logo {
      width: 55px;
      height: 55px;
      object-fit: contain;
      margin-bottom: 8px;
    }
    .footer-center p {
      font-weight: 600;
      color: var(--burgundy);
      font-size: 1rem;
    }

    .footer-right {
      flex-direction: row;
      align-items: center;
      gap: 8px;
      color: var(--burgundy);
      font-weight: 600;
      font-size: 1rem;
    }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .loading-spinner {
            width: 80px;
            height: 80px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--burgundy);
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 20px;
        }

        .loading-text {
            color: white;
            font-size: 1.2rem;
            text-align: center;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            animation: fadeIn 0.3s ease;
        }

        .success-message {
            background: #efe;
            border: 1px solid #cfc;
            color: #080;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">جاري إنشاء حسابك...</div>
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
                <p class="subtitle">سجّل وانضم إلى مجتمعنا الأدبي</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                    <p style="margin: 10px 0 0 0; font-size: 0.9em;">سيتم توجيهك تلقائياً...</p>
                </div>
            <?php endif; ?>

            <!-- رسالة خطأ من الفرونت فقط -->
            <div id="clientError" class="error-message" style="display:none;"></div>

            <form id="loginForm" method="POST" action="" novalidate>
                <div class="form-group">
                    <label for="username" class="form-label">اسم المستخدم</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        placeholder="اختر اسمًا مميزًا لك"
                        value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <div id="usernameFeedback" class="field-feedback"></div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="example@email.com"
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
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
                            placeholder="أدخل كلمة مرور قوية"
                        >
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">إظهار</button>
                    </div>
                    <div class="password-requirements">
                        <div class="requirement <?php echo $hasMinLength ? 'valid' : 'invalid'; ?>" id="lengthReq">
                            <span class="requirement-icon">●</span>
                            <span>8 خانات على الأقل</span>
                        </div>
                        <div class="requirement <?php echo $hasUppercase ? 'valid' : 'invalid'; ?>" id="uppercaseReq">
                            <span class="requirement-icon">●</span>
                            <span>حرف كبير واحد على الأقل</span>
                        </div>
                        <div class="requirement <?php echo $hasSpecialChar ? 'valid' : 'invalid'; ?>" id="specialReq">
                            <span class="requirement-icon">●</span>
                            <span>رمز خاص واحد على الأقل (!@#$%^&*)</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="birthdate" class="form-label">تاريخ الميلاد</label>
                    <input
                        type="date"
                        id="birthdate"
                        name="birthdate"
                        class="form-input date-input"
                        value="<?php echo htmlspecialchars($birthdate, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">اهتماماتك الأدبية (اختر ما يناسبك)</label>
                    <div class="interests-grid">
                        <?php
                        $interests_list     = ['شعر', 'رواية', 'قصة قصيرة', 'مسرح', 'نقد أدبي', 'تاريخ أدبي', 'سيرة ذاتية', 'مقال أدبي'];
                        $selected_interests = $interests !== '' ? explode(',', $interests) : [];

                        foreach ($interests_list as $interest):
                            $is_selected = in_array($interest, $selected_interests, true);
                        ?>
                            <div
                                class="interest-card <?php echo $is_selected ? 'selected' : ''; ?>"
                                data-interest="<?php echo htmlspecialchars($interest, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <?php echo htmlspecialchars($interest, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input
                        type="hidden"
                        id="selected-interests"
                        name="interests"
                        value="<?php echo htmlspecialchars($interests, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <div id="interestsFeedback" class="field-feedback"></div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">إنشاء حساب</button>
            </form>

            <div class="divider">أو</div>

            <div class="login-link">
                لديك حساب بالفعل؟ <a href="LogIn.php">سجّل الدخول</a>
            </div>
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
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleButton  = document.querySelector('.toggle-password');
            const type          = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            toggleButton.textContent = type === 'password' ? 'إظهار' : 'إخفاء';
        }

        function updatePasswordRequirements(password) {
            const lengthReq    = document.getElementById('lengthReq');
            const uppercaseReq = document.getElementById('uppercaseReq');
            const specialReq   = document.getElementById('specialReq');

            const hasMinLength   = password.length >= 8;
            const hasUppercase   = /[A-Z]/.test(password);
            const hasSpecialChar = /[!@#$%^&*]/.test(password);

            lengthReq.classList.toggle('valid', hasMinLength);
            lengthReq.classList.toggle('invalid', !hasMinLength);

            uppercaseReq.classList.toggle('valid', hasUppercase);
            uppercaseReq.classList.toggle('invalid', !hasUppercase);

            specialReq.classList.toggle('valid', hasSpecialChar);
            specialReq.classList.toggle('invalid', !hasSpecialChar);

            return hasMinLength && hasUppercase && hasSpecialChar;
        }

        function validateUsernameLive() {
            const usernameInput    = document.getElementById('username');
            const usernameFeedback = document.getElementById('usernameFeedback');
            const raw              = usernameInput.value;
            const username         = raw.trim();

            usernameFeedback.textContent = '';
            usernameFeedback.className   = 'field-feedback';

            if (username === '') {
                return false;
            }

            const length = [...username].length;

            if (length < 2) {
                usernameFeedback.textContent = 'اسم المستخدم يجب أن يحتوي على حرفين على الأقل.';
                usernameFeedback.classList.add('error');
                return false;
            }

            if (length > 40) {
                usernameFeedback.textContent = 'اسم المستخدم طويل جدًا، الحد الأقصى 40 حرف.';
                usernameFeedback.classList.add('error');
                return false;
            }

            const pattern = /[a-zA-Z0-9ء-ي]/u;
            if (!pattern.test(username)) {
                usernameFeedback.textContent = 'اسم المستخدم يجب أن يحتوي على حرف أو رقم واحد على الأقل.';
                usernameFeedback.classList.add('error');
                return false;
            }

            usernameFeedback.textContent = 'اسم مستخدم صالح.';
            usernameFeedback.classList.add('success');
            return true;
        }

        function updateSelectedInterests() {
            const interestsFeedback = document.getElementById('interestsFeedback');

            const selected = Array.from(document.querySelectorAll('.interest-card.selected'))
                .map(card => card.getAttribute('data-interest'));

            document.getElementById('selected-interests').value = selected.join(',');

            interestsFeedback.textContent = '';
            interestsFeedback.className   = 'field-feedback';

            if (selected.length === 0) {
                interestsFeedback.textContent = 'اختر اهتمامًا واحدًا على الأقل.';
                interestsFeedback.classList.add('error');
                return false;
            } else {
                interestsFeedback.textContent = 'تم اختيار اهتماماتك.';
                interestsFeedback.classList.add('success');
                return true;
            }
        }

        let emailCheckTimeout = null;

        function checkEmailLive() {
            const emailInput    = document.getElementById('email');
            const emailFeedback = document.getElementById('emailFeedback');
            const email         = emailInput.value.trim();

            emailFeedback.textContent = '';
            emailFeedback.className   = 'field-feedback';

            if (email === '') return;

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                emailFeedback.textContent = 'صيغة البريد الإلكتروني غير صحيحة';
                emailFeedback.classList.add('error');
                return;
            }

            const formData = new FormData();
            formData.append('email', email);

            fetch('SignUp.php?check_email=1', {
                method: 'POST',
                body: formData
            })
            .then(resp => resp.json())
            .then(data => {
                if (!data.valid) {
                    return;
                }
                if (data.exists) {
                    emailFeedback.textContent = 'البريد الإلكتروني مسجل مسبقاً';
                    emailFeedback.classList.add('error');
                } else {
                    emailFeedback.textContent = 'البريد الإلكتروني متاح للتسجيل';
                    emailFeedback.classList.add('success');
                }
            })
            .catch(() => {});
        }

        document.addEventListener('DOMContentLoaded', function() {
            const today   = new Date();
            const minDate = new Date(today.getFullYear() - 16, today.getMonth(), today.getDate());
            document.getElementById('birthdate').max = minDate.toISOString().split('T')[0];

            const savedInterests = document.getElementById('selected-interests').value;
            if (savedInterests) {
                const interestsArray = savedInterests.split(',');
                document.querySelectorAll('.interest-card').forEach(card => {
                    if (interestsArray.includes(card.getAttribute('data-interest'))) {
                        card.classList.add('selected');
                    }
                });
            }
            // إظهار رسالة الاهتمامات من أول ما تفتح الصفحة
            updateSelectedInterests();

            document.querySelectorAll('.interest-card').forEach(card => {
                card.addEventListener('click', function() {
                    this.classList.toggle('selected');
                    updateSelectedInterests();
                });
            });

            const passwordInput = document.getElementById('password');
            passwordInput.addEventListener('input', function () {
                updatePasswordRequirements(this.value);
            });

            const emailInput = document.getElementById('email');
            emailInput.addEventListener('input', function () {
                clearTimeout(emailCheckTimeout);
                emailCheckTimeout = setTimeout(checkEmailLive, 500);
            });
            emailInput.addEventListener('blur', checkEmailLive);

            const usernameInput = document.getElementById('username');
            usernameInput.addEventListener('input', validateUsernameLive);
            usernameInput.addEventListener('blur', validateUsernameLive);

            const form        = document.getElementById('loginForm');
            const clientError = document.getElementById('clientError');

            form.addEventListener('submit', function (e) {
                clientError.style.display = 'none';
                clientError.textContent   = '';

                const usernameVal  = usernameInput.value.trim();
                const emailVal     = emailInput.value.trim();
                const pwdVal       = passwordInput.value;
                const birthdateVal = document.getElementById('birthdate').value.trim();

                const emailFeedback = document.getElementById('emailFeedback');
                const passwordOk    = updatePasswordRequirements(pwdVal);
                const usernameOk    = validateUsernameLive();
                const interestsOk   = updateSelectedInterests();
                const interestsVal  = document.getElementById('selected-interests').value.trim();

                // التشييك العام على الحقول الفارغة
                if (!usernameVal || !emailVal || !pwdVal || !birthdateVal || !interestsVal) {
                    e.preventDefault();
                    clientError.textContent = 'جميع الحقول مطلوبة';
                    clientError.style.display = 'block';
                    return;
                }

                if (!usernameOk) {
                    e.preventDefault();
                    return;
                }

                if (emailFeedback.classList.contains('error')) {
                    e.preventDefault();
                    return;
                }

                if (!passwordOk) {
                    e.preventDefault();
                    return;
                }

                if (!interestsOk) {
                    e.preventPreventDefault();
                    return;
                }

                document.getElementById('loadingOverlay').classList.add('active');
            });
        });
    </script>

    <?php if (!empty($success_message)): ?>
        <script>
            setTimeout(function () {
                window.location.href = 'homep.php';
            }, 2000);
        </script>
    <?php endif; ?>
</body>
</html>
