<?php
require_once 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['userID'])) {
    header('Location: LogIn.php');
    exit;
}

// Use either session variable
$user_id = $_SESSION['user_id'] ?? $_SESSION['userID'] ?? null;

if (!$user_id) {
    header('Location: LogIn.php');
    exit;
}

// ===== رفع صورة جديدة =====
if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {
    $file = $_FILES['profileImage'];
    $allowed = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed) && $file['size'] <= 2*1024*1024) {
        $newFileName = 'user_' . $user_id . '.' . $ext;
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        move_uploaded_file($file['tmp_name'], $uploadDir.$newFileName);

        // تحديث قاعدة البيانات
        $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE userID = ?");
        $stmt->execute([$uploadDir.$newFileName, $user_id]);
        
        echo json_encode(['success' => 'تم تحديث الصورة بنجاح']);
        exit;
    } else {
        echo json_encode(['error' => 'نوع الملف غير مدعوم أو الحجم كبير جدًا']);
        exit;
    }
}

// ===== حفظ التعديلات =====
if (isset($_POST['action']) && $_POST['action'] == 'updateProfile') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    // Handle empty bio with default value
    if (empty(trim($bio))) {
        $bio = 'لم يتم إضافة نبذة عن المستخدم بعد.';
    }
    
    $notifications = isset($_POST['notification']) ? implode(',', $_POST['notification']) : '';
    $interests = isset($_POST['interest']) ? implode(',', $_POST['interest']) : '';

    $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, birthdate=?, bio=?, notificationPreferences=?, interests=? WHERE userID=?");
    $stmt->execute([$name, $email, $birthdate, $bio, $notifications, $interests, $user_id]);

    echo json_encode(['success' => 'تم تحديث الملف الشخصي بنجاح']);
    exit;
}

// ===== جلب بيانات المستخدم =====
$stmt = $pdo->prepare("SELECT * FROM users WHERE userID=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: LogIn.php');
    exit;
}

// تجهيز البيانات للعرض مع القيم الافتراضية
$name = $user['name'];
$email = $user['email'];
$birthdate = $user['birthdate'] ?? '';
$bio = $user['bio'] ?? 'لم يتم إضافة نبذة عن المستخدم بعد.';
$notificationPreferences = !empty($user['notificationPreferences']) ? explode(',', $user['notificationPreferences']) : [];
$interests = !empty($user['interests']) ? explode(',', $user['interests']) : [];
$profileImage = !empty($user['profile_image']) ? $user['profile_image'] : 'image/user.jpg';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مِـداد | Medad</title>
<link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
<style>
    /* نفس CSS السابق بالكامل - لا تغيير */
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
        --burgundy-shimmer: rgba(123, 30, 58, 0.1);
        --text-dark: #2D2D2D;
        --text-light: #555555;
        --background: #FFFFFF;
        --off-white: #F9F9F9;
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-hover: 0 6px 18px rgba(123, 30, 58, 0.15);
        --brown: #8B4513;
        --success: #28a745;
        --error: #dc3545;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Almarai', 'Arial', sans-serif;
    }

    body {
        background: linear-gradient(rgba(0, 0, 0, 0.03), rgba(0, 0, 0, 0.05)), 
                    url('image/BACKGROUND.png') center/cover no-repeat fixed;
        color: #333;
        line-height: 1.6;
        overflow-x: hidden;
        min-height: 100vh;
        position: relative;
    }

    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(248, 248, 248, 0.85);
        z-index: -1;
    }

    .main-header{
      background: transparent;
      padding: 12px 0;
      margin-top: 10px;
      margin-bottom: 10px;
      border-bottom: 1px solid transparent;
    }

    .header-shell{
      width: min(1100px, calc(100% - 24px));
      margin: 0 auto;
      display: grid;
      grid-template-columns: auto 1fr auto;
      align-items: center;
      gap: 10px;
      background: #fff;
      border-radius: 999px;
      padding: 8px 12px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.08);
      position: relative;
      z-index: 100;
    }

    .hamburger {
        font-size: 28px;
        cursor: pointer;
        color: var(--burgundy);
        background: none;
        border: none;
        justify-self: end;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
    }

    .hamburger:hover {
        background: var(--burgundy-light);
        transform: scale(1.1);
    }

    .header-center {
      justify-self: center;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .header-start {
      justify-self: start;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .brand-image {
      width: 40px;
      height: 40px;
      object-fit: contain;
      background: transparent;
      display: inline-block;
    }

    @media (max-width: 600px){
      .header-shell{ 
        grid-template-columns: auto 1fr auto; 
        padding: 6px 10px;
      }
      .header-center{ 
        justify-self: center;
      }
      .brand-image {
        width: 35px;
        height: 35px;
      }
      .hamburger {
        width: 40px;
        height: 40px;
        font-size: 24px;
      }
    }

    .nav-menu {
      display: none;
      position: fixed;
      top: 0;
      right: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(15px);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      text-align: center;
      transition: all 0.3s ease;
    }

    .nav-menu.show {
      display: flex;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .menu-content {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 20px;
      padding: 40px 30px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
      border: 1px solid rgba(123, 30, 58, 0.1);
      min-width: 300px;
    }

    .menu-content a {
      display: block;
      font-size: 22px;
      color: var(--burgundy);
      text-decoration: none;
      margin: 20px 0;
      font-weight: 600;
      font-family: 'Doran', 'Arial', sans-serif;
      transition: all 0.3s ease;
      padding: 12px 25px;
      border-radius: 12px;
      position: relative;
      overflow: hidden;
    }

    .menu-content a::before {
      content: '';
      position: absolute;
      top: 0;
      right: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, var(--burgundy-light), transparent);
      transition: right 0.5s ease;
    }

    .menu-content a:hover::before {
      right: 100%;
    }

    .menu-content a:hover {
      color: white;
      background: var(--burgundy);
      transform: translateX(-10px);
      box-shadow: 0 5px 15px rgba(123, 30, 58, 0.3);
    }

    .close-btn {
      position: absolute;
      top: 25px;
      left: 25px;
      font-size: 32px;
      background: rgba(255, 255, 255, 0.9);
      border: none;
      color: var(--burgundy);
      cursor: pointer;
      transition: all 0.3s ease;
      padding: 10px;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .close-btn:hover {
      background: var(--burgundy);
      color: white;
      transform: scale(1.1);
      box-shadow: 0 6px 18px rgba(123, 30, 58, 0.2);
    }

    main {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      min-height: calc(100vh - 200px);
      padding: 40px 20px;
    }

    .profile-container {
      max-width: 700px;
      width: 100%;
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(10px);
      border-radius: 24px;
      box-shadow: var(--shadow);
      padding: 40px;
      text-align: center;
      transition: all 0.3s ease;
      border: 1px solid rgba(123, 30, 58, 0.1);
      position: relative;
      overflow: hidden;
    }

    .profile-container::before {
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
      0%, 100% { background-position: -200% 0; }
      50% { background-position: 200% 0; }
    }

    .profile-container:hover {
      box-shadow: var(--shadow-hover);
      transform: translateY(-5px);
    }

    .profile-image-container {
      position: relative;
      display: inline-block;
      margin-bottom: 20px;
    }

    .profile-container img {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid var(--burgundy-light);
      box-shadow: 0 8px 20px rgba(123, 30, 58, 0.15);
      transition: all 0.3s ease;
    }

    .profile-container img:hover {
      transform: scale(1.05);
      border-color: var(--burgundy);
    }

    .profile-container h2 {
      color: var(--burgundy);
      font-family: 'Doran', 'Arial', sans-serif;
      font-size: 2.2rem;
      margin-bottom: 15px;
      font-weight: bold;
    }

    .profile-container p {
      color: var(--text-light);
      font-size: 1.1rem;
      line-height: 1.7;
      margin-bottom: 25px;
    }

    .profile-info {
      text-align: right;
      margin: 25px 0;
    }

    .info-item {
      margin-bottom: 20px;
      padding: 15px;
      background: var(--burgundy-light);
      border-radius: 12px;
      border-right: 4px solid var(--burgundy);
    }

    .info-label {
      font-weight: 700;
      color: var(--burgundy);
      margin-bottom: 8px;
      font-size: 1.1rem;
    }

    .info-value {
      color: var(--text-dark);
      font-size: 1rem;
      font-family: 'Almarai', 'Arial', sans-serif;
    }

    .edit-form {
      text-align: right;
      margin-top: 30px;
      display: none;
    }

    .edit-form.active {
      display: block;
      animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .form-group {
      margin-bottom: 25px;
      text-align: right;
    }

    .form-label {
      display: block;
      margin-bottom: 10px;
      font-weight: 600;
      color: var(--text-dark);
      font-size: 1.1rem;
    }

    .form-input {
      width: 100%;
      padding: 14px 18px;
      border: 2px solid rgba(123, 30, 58, 0.1);
      border-radius: 12px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: white;
      font-family: 'Almarai', 'Arial', sans-serif;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--burgundy);
      box-shadow: 0 0 0 4px rgba(123, 30, 58, 0.1);
    }

    .date-input {
      direction: ltr;
      text-align: right;
    }

    .form-actions {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-top: 30px;
    }

    .save-btn {
      background: linear-gradient(135deg, var(--burgundy), #5a152c);
      color: white;
      border: none;
      border-radius: 25px;
      padding: 14px 35px;
      font-size: 1.1rem;
      cursor: pointer;
      font-family: 'Doran', 'Arial', sans-serif;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(123, 30, 58, 0.2);
    }

    .save-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(123, 30, 58, 0.3);
    }

    .cancel-btn {
      background: transparent;
      color: var(--text-light);
      border: 2px solid var(--text-light);
      border-radius: 25px;
      padding: 14px 35px;
      font-size: 1.1rem;
      cursor: pointer;
      font-family: 'Doran', 'Arial', sans-serif;
      transition: all 0.3s ease;
    }

    .cancel-btn:hover {
      background: var(--text-light);
      color: white;
      transform: translateY(-3px);
    }

    .edit-btn {
      background: linear-gradient(135deg, var(--burgundy), #5a152c);
      color: white;
      border: none;
      border-radius: 25px;
      padding: 14px 35px;
      font-size: 1.1rem;
      margin-top: 10px;
      cursor: pointer;
      font-family: 'Doran', 'Arial', sans-serif;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(123, 30, 58, 0.2);
      display: block;
      margin-right: auto;
      margin-left: auto;
    }

    .edit-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s;
    }

    .edit-btn:hover::before {
      left: 100%;
    }

    .edit-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(123, 30, 58, 0.3);
    }

    .edit-btn:active {
      transform: translateY(-1px);
    }

    .preferences {
      text-align: center;
      margin-top: 35px;
      padding: 25px;
      background: var(--burgundy-light);
      border-radius: 16px;
      border: 1px solid rgba(123, 30, 58, 0.1);
    }

    .preferences h3 {
      color: var(--burgundy);
      margin-bottom: 20px;
      font-family: 'Doran', 'Arial', sans-serif;
      font-size: 1.4rem;
    }

    .notification-options {
      display: flex;
      justify-content: center;
      gap: 30px;
      flex-wrap: wrap;
    }

    .notification-options label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 1rem;
      cursor: pointer;
      padding: 10px 15px;
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    .notification-options label:hover {
      background: rgba(123, 30, 58, 0.05);
    }

    .notification-options input[type="checkbox"] {
      accent-color: var(--burgundy);
      transform: scale(1.2);
    }

    .notification-display {
      text-align: center;
      margin-top: 20px;
    }

    .notification-display .notification-item {
      display: inline-block;
      background: var(--burgundy-light);
      padding: 8px 16px;
      margin: 5px;
      border-radius: 20px;
      color: var(--burgundy);
      font-weight: 600;
      border: 1px solid rgba(123, 30, 58, 0.2);
    }

    .interests {
      margin-top: 35px;
      text-align: center;
      padding: 25px;
      background: var(--burgundy-light);
      border-radius: 16px;
      border: 1px solid rgba(123, 30, 58, 0.1);
    }

    .interests h3 {
      color: var(--burgundy);
      margin-bottom: 20px;
      font-family: 'Doran', 'Arial', sans-serif;
      font-size: 1.4rem;
    }

    .interests-display {
      text-align: center;
      margin-top: 20px;
    }

    .interests-display .interest-item {
      display: inline-block;
      background: var(--burgundy-light);
      padding: 8px 16px;
      margin: 5px;
      border-radius: 20px;
      color: var(--burgundy);
      font-weight: 600;
      border: 1px solid rgba(123, 30, 58, 0.2);
    }

    .interest-options {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 20px;
    }

    .interest-options label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 1rem;
      cursor: pointer;
      padding: 10px 15px;
      border-radius: 10px;
      transition: all 0.3s ease;
      background: white;
      border: 1px solid rgba(123, 30, 58, 0.2);
    }

    .interest-options label:hover {
      background: rgba(123, 30, 58, 0.05);
    }

    .interest-options input[type="checkbox"] {
      accent-color: var(--burgundy);
      transform: scale(1.2);
    }

    /*  Footer */
    .main-footer {
      background-color: #ebe2db;
      width: 100%;
      padding: 30px 16px 16px;
      color: var(--burgundy);
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
     
      color: var(--burgundy);
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
      color:var(--burgundy);
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

    .footer-right svg {
      width: 18px; 
      height: 18px;
      stroke: var(--burgundy);
    }

    .message {
      padding: 12px 20px;
      border-radius: 8px;
      margin: 15px 0;
      font-weight: 600;
      text-align: center;
      display: none;
    }

    .message.success {
      background-color: rgba(40, 167, 69, 0.1);
      color: var(--success);
      border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .message.error {
      background-color: rgba(220, 53, 69, 0.1);
      color: var(--error);
      border: 1px solid rgba(220, 53, 69, 0.3);
    }

    .image-upload-container {
      position: relative;
      display: inline-block;
      margin-bottom: 20px;
    }

    .image-upload-btn {
      position: absolute;
      bottom: 5px;
      right: 5px;
      background: var(--burgundy);
      color: white;
      border: none;
      border-radius: 50%;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
      display: none; 
    }

    .image-upload-btn.active {
      display: flex; 
    }

    .image-upload-input {
      display: none;
    }

    @media (max-width: 768px) {
      .profile-container {
        margin: 20px;
        padding: 30px 20px;
      }
      
      .profile-container h2 {
        font-size: 1.8rem;
      }
      
      .notification-options, .interest-options {
        flex-direction: column;
        gap: 15px;
      }
      
      .menu-content {
        min-width: 280px;
        padding: 30px 20px;
      }
      
      .menu-content a {
        font-size: 20px;
        padding: 10px 20px;
      }
      
      .form-actions {
        flex-direction: column;
      }
      
      .save-btn, .cancel-btn {
        width: 100%;
      }
      
      .info-item {
        padding: 12px;
      }
    }

    @media (max-width: 480px) {
      .profile-container {
        margin: 15px;
        padding: 25px 15px;
      }
      
      .profile-container img {
        width: 120px;
        height: 120px;
      }
      
      .preferences, .interests {
        padding: 20px 15px;
      }
      
      .footer-container {
        flex-direction: column;
        gap: 25px;
      }
      
      .close-btn {
        top: 20px;
        left: 20px;
        width: 45px;
        height: 45px;
        font-size: 28px;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .profile-container, .edit-btn, .hamburger, .close-btn, .icon-box, .menu-content a {
        transition: none;
        transform: none;
        animation: none;
      }
      
      .profile-container::before, .edit-btn::before, .menu-content a::before {
        animation: none;
      }
    }

    
    .logout-modal {
        display: none;
        position: fixed;
        top: 0;
        right: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(15px);
        z-index: 2000;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        text-align: center;
    }

    .logout-modal.show {
        display: flex;
        animation: fadeIn 0.3s ease;
    }

    .logout-content {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 40px 30px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(123, 30, 58, 0.2);
        min-width: 350px;
        max-width: 90%;
        position: relative;
        overflow: hidden;
    }

    .logout-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border: 2px solid transparent;
        border-radius: 20px;
        background: linear-gradient(45deg, var(--burgundy), transparent, var(--burgundy), transparent, var(--burgundy));
        background-size: 400% 400%;
        animation: borderAnimation 3s linear infinite;
        z-index: 1;
        pointer-events: none;
    }

    @keyframes borderAnimation {
        0% {
            background-position: 0% 50%;
        }
        50% {
            background-position: 100% 50%;
        }
        100% {
            background-position: 0% 50%;
        }
    }

    .logout-content::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        right: 2px;
        bottom: 2px;
        background: white;
        border-radius: 18px;
        z-index: 1;
        pointer-events: none;
    }

    .logout-content h3 {
        color: var(--burgundy);
        margin-bottom: 20px;
        font-family: 'Doran', 'Arial', sans-serif;
        font-size: 1.5rem;
        font-weight: bold;
        position: relative;
        z-index: 2;
    }

    .logout-content p {
        color: var(--text-light);
        margin-bottom: 30px;
        font-size: 1.1rem;
        line-height: 1.6;
        position: relative;
        z-index: 2;
    }

    .logout-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
        position: relative;
        z-index: 2;
    }

    .confirm-logout-btn {
        background: linear-gradient(135deg, var(--burgundy), #5a152c);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 12px 30px;
        font-size: 1.1rem;
        cursor: pointer;
        font-family: 'Doran', 'Arial', sans-serif;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(123, 30, 58, 0.2);
        min-width: 120px;
    }

    .confirm-logout-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(123, 30, 58, 0.3);
    }

    .cancel-logout-btn {
        background: transparent;
        color: var(--text-light);
        border: 2px solid var(--text-light);
        border-radius: 25px;
        padding: 12px 30px;
        font-size: 1.1rem;
        cursor: pointer;
        font-family: 'Doran', 'Arial', sans-serif;
        transition: all 0.3s ease;
        min-width: 120px;
    }

    .cancel-logout-btn:hover {
        background: var(--text-light);
        color: white;
        transform: translateY(-3px);
    }

    @media (max-width: 768px) {
        .logout-content {
            min-width: 280px;
            padding: 30px 20px;
        }
        
        .logout-actions {
            flex-direction: column;
        }
        
        .confirm-logout-btn,
        .cancel-logout-btn {
            width: 100%;
        }
    }
</style>
</head>
<body>

<header class="main-header">
  <div class="header-shell">   
    <div class="header-start">
      <img class="brand-image" src="image/LOGO SW.png" alt="Medad Logo">
    </div>
    
    <div class="header-center"></div>
    
    <button class="hamburger" id="hamburger">☰</button>
  </div>
</header>

<nav class="nav-menu" id="navMenu">
  <button class="close-btn" id="closeBtn">&times;</button>
  <div class="menu-content">
    <a href="homep.php">الملف الشخصي</a>
    <a href="events.php">صفحة الفعاليات والأندية</a>
    <a href="myevents.php">حجوزاتي</a>
    <a href="myclubs.php">أنديتي</a>
    <a href="favs.php">تفضيلاتي</a>
    <a id="logoutLink">تسجيل خروج</a>
  </div>
</nav>

<main>
  <section class="profile-container">
    <div id="successMessage" class="message success"></div>
    <div id="errorMessage" class="message error"></div>
    
    <div class="profile-image-container">
      <img id="profileImage" src="<?= $profileImage ?>" alt="صورة المستخدم">
      <button class="image-upload-btn" id="imageUploadBtn" title="تغيير الصورة">+</button>
      <input type="file" id="imageUploadInput" class="image-upload-input" accept="image/jpeg,image/png,image/jpg">
    </div>
    
    <h2 id="userName"><?= htmlspecialchars($name) ?></h2>
    <p id="userBio"><?= htmlspecialchars($bio) ?></p>
    <div class="profile-info" id="profileInfo">
      <div class="info-item">
        <div class="info-label">البريد الإلكتروني</div>
        <div class="info-value" id="emailValue"><?= htmlspecialchars($email) ?></div>
      </div>
      
      <?php if (!empty($birthdate)): ?>
      <div class="info-item">
        <div class="info-label">تاريخ الميلاد</div>
        <div class="info-value" id="birthdateValue"><?= htmlspecialchars($birthdate) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($notificationPreferences)): ?>
      <div class="notification-display" id="notificationDisplay">
        <div class="info-label">نوع الإشعارات المفضلة</div>
        <div id="notificationValues">
          <?php foreach($notificationPreferences as $n): ?>
          <span class="notification-item"><?= htmlspecialchars($n) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($interests)): ?>
      <div class="interests-display" id="interestsDisplay">
        <div class="info-label">اهتماماتي</div>
        <div id="interestsValues">
          <?php foreach($interests as $i): ?>
          <span class="interest-item"><?= htmlspecialchars($i) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <form id="editForm" class="edit-form" enctype="multipart/form-data">
      <input type="hidden" name="action" value="updateProfile">
      
      <div class="form-group">
        <label for="editName" class="form-label">الاسم الكامل</label>
        <input type="text" id="editName" name="name" class="form-input" value="<?= htmlspecialchars($name) ?>" required>
      </div>

      <div class="form-group">
        <label for="editEmail" class="form-label">البريد الإلكتروني</label>
        <input type="email" id="editEmail" name="email" class="form-input" value="<?= htmlspecialchars($email) ?>" required>
      </div>

      <div class="form-group">
        <label for="editBirthdate" class="form-label">تاريخ الميلاد</label>
        <input type="date" id="editBirthdate" name="birthdate" class="form-input date-input" value="<?= $birthdate ?>">
      </div>

      <div class="form-group">
        <label for="editBio" class="form-label">نبذة عني</label>
        <textarea id="editBio" name="bio" class="form-input" rows="4" placeholder="اكتب نبذة عنك هنا..."><?= htmlspecialchars($bio === 'لم يتم إضافة نبذة عن المستخدم بعد.' ? '' : $bio) ?></textarea>
      </div>
      <div class="preferences">
        <h3>نوع الإشعارات</h3>
        <div class="notification-options">
          <?php
          $allNotifications = ["إشعارات داخل المنصة", "رسائل قصيرة (SMS)", "البريد الإلكتروني"];
          foreach($allNotifications as $n):
          ?>
          <label>
            <input type="checkbox" name="notification[]" value="<?= $n ?>" <?= in_array($n, $notificationPreferences) ? 'checked' : '' ?>>
            <?= $n ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="interests">
        <h3>اهتماماتي</h3>
        <div class="interest-options">
          <?php
          $allInterests = ["الشعر","المقال الأدبي","التاريخ الأدبي","المسرح","سيرة ذاتية","النقد الأدبي","القصة القصيرة","الرواية"];
          foreach($allInterests as $i):
          ?>
          <label>
            <input type="checkbox" name="interest[]" value="<?= $i ?>" <?= in_array($i, $interests) ? 'checked' : '' ?>>
            <?= $i ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="save-btn">حفظ التغييرات</button>
        <button type="button" class="cancel-btn" id="cancelEdit">إلغاء</button>
      </div>
    </form>

    <button class="edit-btn" id="editProfileBtn">تعديل الملف الشخصي</button>
  </section>
</main>

<div class="logout-modal" id="logoutModal">
  <div class="logout-content">
    <h3>تأكيد تسجيل الخروج</h3>
    <p>هل أنت متأكد من رغبتك في تسجيل الخروج من حسابك؟</p>
    <div class="logout-actions">
      <button class="confirm-logout-btn" id="confirmLogout">تأكيد</button>
      <button class="cancel-logout-btn" id="cancelLogout">إلغاء</button>
    </div>
  </div>
</div>

<footer class="main-footer">
  <div class="footer-container">
    <div class="footer-left">
      <h3>تواصل معنا</h3>
      <div class="footer-icons">
        <a href="https://x.com/MedadKsu" class="icon-box" title="X">
          <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M18.244 2H21L14.126 10.088 22 22h-5.905l-4.67-6.726L6.08 22H3l7.31-8.433L2 2h6.08l4.132 5.897L18.244 2zM7.118 4l9.764 13.997h1.999L9.12 4H7.118z"/>
          </svg>
        </a>
        <a href="mailto:MedadKsu@gmail.com" class="icon-box" title="Email">
          <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M4 4h16v16H4V4zm8 8l8-5H4l8 5zm0 2l-8-5v10h16V9l-8 5z"/>
          </svg>
        </a>
      </div>
    </div>

    <div class="footer-center">
      <img src="image/LOGO SW.png" alt="شعار مداد" class="footer-logo">
      <p>© جميع الحقوق محفوظة 2025</p>
    </div>

    <div class="footer-right">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 2C8.13401 2 5 5.13401 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13401 15.866 2 12 2Z"/>
        <circle cx="12" cy="9" r="2.5"/>
      </svg>
      <span>المملكة العربية السعودية</span>
    </div>
  </div>
</footer>

<script>
// JavaScript for profile functionality
const hamburger = document.getElementById('hamburger');
const navMenu = document.getElementById('navMenu');
const closeBtn = document.getElementById('closeBtn');
const logoutLink = document.getElementById('logoutLink');
const logoutModal = document.getElementById('logoutModal');
const confirmLogout = document.getElementById('confirmLogout');
const cancelLogout = document.getElementById('cancelLogout');
const editProfileBtn = document.getElementById('editProfileBtn');
const editForm = document.getElementById('editForm');
const cancelEdit = document.getElementById('cancelEdit');
const profileInfo = document.getElementById('profileInfo');
const profileImage = document.getElementById('profileImage');
const imageUploadBtn = document.getElementById('imageUploadBtn');
const imageUploadInput = document.getElementById('imageUploadInput');
const successMessage = document.getElementById('successMessage');
const errorMessage = document.getElementById('errorMessage');

// Menu functionality
hamburger.addEventListener('click', () => {
  navMenu.classList.add('show');
});

closeBtn.addEventListener('click', () => {
  navMenu.classList.remove('show');
});

navMenu.addEventListener('click', (e) => {
  if (e.target === navMenu) {
    navMenu.classList.remove('show');
  }
});

// Logout functionality
logoutLink.addEventListener('click', (e) => {
  e.preventDefault();
  navMenu.classList.remove('show');
  logoutModal.classList.add('show');
});

confirmLogout.addEventListener('click', () => {
  window.location.href = 'index.html';
});

cancelLogout.addEventListener('click', () => {
  logoutModal.classList.remove('show');
});

logoutModal.addEventListener('click', (e) => {
  if (e.target === logoutModal) {
    logoutModal.classList.remove('show');
  }
});

// Edit profile functionality
editProfileBtn.addEventListener('click', () => {
  profileInfo.style.display = 'none';
  editForm.classList.add('active');
  editProfileBtn.style.display = 'none';
  imageUploadBtn.classList.add('active');
});

cancelEdit.addEventListener('click', () => {
  editForm.classList.remove('active');
  profileInfo.style.display = 'block';
  editProfileBtn.style.display = 'block';
  imageUploadBtn.classList.remove('active');
  hideMessages();
});

// Image upload
imageUploadBtn.addEventListener('click', () => {
  imageUploadInput.click();
});

imageUploadInput.addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (file) {
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!validTypes.includes(file.type)) {
      showErrorMessage('نوع الملف غير مدعوم. يرجى اختيار صورة بصيغة JPEG أو PNG.');
      return;
    }
    
    const maxSize = 2 * 1024 * 1024;
    if (file.size > maxSize) {
      showErrorMessage('حجم الملف كبير جداً. يرجى اختيار صورة بحجم أقل من 2 ميجابايت.');
      return;
    }
    
    const formData = new FormData();
    formData.append('profileImage', file);

    fetch('<?= $_SERVER['PHP_SELF'] ?>', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        profileImage.src = URL.createObjectURL(file);
        showSuccessMessage('تم تحديث صورة الملف الشخصي بنجاح');
      } else {
        showErrorMessage(data.error);
      }
    })
    .catch(err => {
      console.error(err);
      showErrorMessage('حدث خطأ أثناء رفع الصورة');
    });
  }
});

// Form submission
editForm.addEventListener('submit', (e) => {
  e.preventDefault();
  const formData = new FormData(editForm);

  fetch('<?= $_SERVER['PHP_SELF'] ?>', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      showSuccessMessage(data.success);
      setTimeout(() => {
        location.reload();
      }, 1500);
    } else {
      showErrorMessage(data.error);
    }
  })
  .catch(err => {
    console.error(err);
    showErrorMessage('حدث خطأ أثناء حفظ البيانات');
  });
});

// Message functions
function showSuccessMessage(message) {
  successMessage.textContent = message;
  successMessage.style.display = 'block';
  setTimeout(() => {
    successMessage.style.display = 'none';
  }, 5000);
}

function showErrorMessage(message) {
  errorMessage.textContent = message;
  errorMessage.style.display = 'block';
  setTimeout(() => {
    errorMessage.style.display = 'none';
  }, 5000);
}

function hideMessages() {
  successMessage.style.display = 'none';
  errorMessage.style.display = 'none';
}

// Close menu on escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    if (navMenu.classList.contains('show')) {
      navMenu.classList.remove('show');
    }
    if (logoutModal.classList.contains('show')) {
      logoutModal.classList.remove('show');
    }
  }
});
</script>
</body>
</html>
