<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Temporary REMOVE AFTER ABEER PAGE
if (!isset($_SESSION['userID'])) {
    $_SESSION['userID'] = 1; // Use user ID 1 for testing
    $_SESSION['username'] = 'Test User';
}

require_once 'config.php';

$userID = $_SESSION['userID'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_bookmark'])) {
        $bookmarkID = $_POST['bookmark_id'];
        $sql = "DELETE FROM bookmark WHERE bookmarkID = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$bookmarkID])) {
            $_SESSION['success_message'] = 'تم إزالة العنصر من المفضلة بنجاح';
        } else {
            $_SESSION['error_message'] = 'حدث خطأ أثناء إزالة العنصر من المفضلة';
        }
    } 
    elseif (isset($_POST['join_club'])) {
        $clubID = $_POST['club_id'];
        
        // for join club
        $check_sql = "SELECT * FROM club_memberships WHERE userID = ? AND clubID = ?";
        $stmt = $pdo->prepare($check_sql);
        $stmt->execute([$userID, $clubID]);
        
        if ($stmt->fetch()) {
            $_SESSION['error_message'] = 'أنت بالفعل عضو في هذا النادي';
        } else {
            $insert_sql = "INSERT INTO club_memberships (userID, clubID) VALUES (?, ?)";
            $stmt = $pdo->prepare($insert_sql);
            if ($stmt->execute([$userID, $clubID])) {
                $_SESSION['success_message'] = 'تم إرسال طلب انضمامك إلى النادي بنجاح';
            } else {
                $_SESSION['error_message'] = 'حدث خطأ أثناء الانضمام إلى النادي';
            }
        }
    } 
    elseif (isset($_POST['book_event'])) {
        $eventID = $_POST['event_id'];
        
        // for booking
        $check_sql = "SELECT * FROM booking WHERE userID = ? AND eventID = ?";
        $stmt = $pdo->prepare($check_sql);
        $stmt->execute([$userID, $eventID]);
        
        if ($stmt->fetch()) {
            $_SESSION['error_message'] = 'لديك حجز مسبق في هذه الفعالية';
        } else {
            $insert_sql = "INSERT INTO booking (userID, eventID, bookingDate, status) VALUES (?, ?, CURDATE(), 'booked')";
            $stmt = $pdo->prepare($insert_sql);
            if ($stmt->execute([$userID, $eventID])) {
                $_SESSION['success_message'] = 'تم حجز الفعالية بنجاح';
            } else {
                $_SESSION['error_message'] = 'حدث خطأ أثناء حجز الفعالية';
            }
        }
    }
    
    // Redirect to same page 
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Function to remove bookmark 
function removeBookmark($bookmarkID, $pdo) {
    $sql = "DELETE FROM bookmark WHERE bookmarkID = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$bookmarkID])) {
        $_SESSION['success_message'] = 'تم إزالة العنصر من المفضلة بنجاح';
    } else {
        $_SESSION['error_message'] = 'حدث خطأ أثناء إزالة العنصر من المفضلة';
    }
}

// Get user's bookmarks with event and club details
$bookmarks_sql = "
    SELECT b.bookmarkID, b.itemType, b.itemID, b.dateAdded,
           e.title as eventTitle, e.date as eventDate, e.location as eventLocation, e.description as eventDescription, e.image_path as eventImage,
           c.name as clubName, c.location as clubLocation, c.description as clubDescription, c.image_path as clubImage
    FROM bookmark b
    LEFT JOIN event e ON b.itemType = 'event' AND b.itemID = e.eventID
    LEFT JOIN club c ON b.itemType = 'club' AND b.itemID = c.clubID
    WHERE b.userID = ?
    ORDER BY b.dateAdded DESC";
$bookmarks_stmt = $pdo->prepare($bookmarks_sql);
$bookmarks_stmt->execute([$userID]);
$bookmarks = $bookmarks_stmt->fetchAll();

// Count bookmarks by type
$event_bookmarks_count = 0;
$club_bookmarks_count = 0;

foreach ($bookmarks as $bookmark) {
    if ($bookmark['itemType'] == 'event') {
        $event_bookmarks_count++;
    } else {
        $club_bookmarks_count++;
    }
}

// Display messages 
if (isset($_SESSION['success_message'])) {
    echo "<script>alert('" . $_SESSION['success_message'] . "');</script>";
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo "<script>alert('" . $_SESSION['error_message'] . "');</script>";
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مفضلاتي | مداد</title>
    
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
            --burgundy-shimmer: rgba(123, 30, 58, 0.1);
            --text-dark: #2D2D2D;
            --text-light: #555555;
            --background: #FFFFFF;
            --off-white: #F9F9F9;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 6px 18px rgba(123, 30, 58, 0.15);
            --brown: #8B4513;
            --gold: #FFD700;
            --gold-light: rgba(255, 215, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Almarai', 'Arial', sans-serif;
        }

        body {
            
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

        /* ===== الهيدر (نفس homep) ===== */
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

        .header-end {
            justify-self: end;
        }

        .brand-image {
            width: 40px;
            height: 40px;
            object-fit: contain;
            background: transparent;
            display: inline-block;
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

        /* منيو الهيدر (Overlay) */
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

        /* مودال تسجيل الخروج (نفس homep) */
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

        /* ===== المحتوى الرئيسي ===== */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .page-title {
            font-family: 'Doran', 'Arial', sans-serif;
            font-size: 2.5rem;
            color: var(--burgundy);
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            right: 50%;
            transform: translateX(50%);
            width: 100px;
            height: 3px;
            background: var(--burgundy);
            border-radius: 2px;
        }

        .page-subtitle {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        /* ===== التبويبات ===== */
        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid #ddd;
            background: white;
            border-radius: 12px;
            padding: 8px;
            box-shadow: var(--shadow);
        }

        .tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 1rem;
            color: #666;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 600;
        }

        .tab.active {
            color: var(--burgundy);
            background: var(--burgundy-light);
            box-shadow: 0 2px 8px rgba(123, 30, 58, 0.1);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* ===== بطاقات المفضلات ===== */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            border: 2px solid transparent;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
            border-color: var(--gold);
        }

        .card-header {
            position: relative;
        }

        .card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .card-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background-color: var(--burgundy);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 10;
        }

        .card-content {
            padding: 1.5rem;
        }

        .title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .card h3 {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            flex: 1;
            font-size: 1.3rem;
        }

        .bookmark-btn {
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--gold);
            transition: all 0.3s;
            padding: 0.2rem;
        }

        .bookmark-btn:hover {
            transform: scale(1.2);
        }

        .card p {
            color: var(--text-light);
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .details-btn {
            background: none;
            border: none;
            color: var(--burgundy);
            cursor: pointer;
            margin-top: 0.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .details-btn:hover {
            color: #5a152c;
        }

        .details-content {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            display: none;
        }

        .details-content.active {
            display: block;
        }

        .details-content p {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .details-content strong {
            color: var(--text-dark);
        }

        /* ===== أزرار الإجراءات ===== */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 1rem;
        }

        .reserve-btn {
            background-color: var(--burgundy);
            color: white;
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            flex: 1;
            font-size: 1rem;
        }

        .reserve-btn:hover {
            background-color: #5a152c;
        }

        .remove-btn {
            background-color: #f8f8f8;
            color: var(--text-light);
            border: 1px solid #ddd;
            padding: 0.7rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remove-btn:hover {
            background-color: #ffebee;
            color: #c62828;
            border-color: #ffcdd2;
        }

        .remove-icon {
            width: 18px;
            height: 18px;
            margin-left: 5px;
        }

        /* ===== حالة عدم وجود مفضلات ===== */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
            background: white;
            border-radius: 14px;
            box-shadow: var(--shadow);
            margin: 2rem 0;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--gold);
        }

        .empty-state h3 {
            color: var(--text-light);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .empty-state p {
            color: var(--text-light);
            max-width: 400px;
            margin: 0 auto;
            font-size: 1.1rem;
        }

        /* ===== الفوتر ===== */
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

        @media (max-width: 700px) {
            .footer-container {
                flex-direction: column;
                gap: 20px;
            }
            
            .footer-icons {
                gap: 6px;
            }
            
            .icon-box {
                width: 32px;
                height: 32px;
            }
        }

        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-direction: column;
                gap: 5px;
            }
            
            .tab {
                padding: 0.6rem 1rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .remove-btn {
                width: 100%;
                margin-top: 10px;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.8rem;
            }
            
            .card-content {
                padding: 1rem;
            }
            
            .empty-state {
                padding: 2rem 1rem;
            }
            
            .empty-state i {
                font-size: 3rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .card, .reserve-btn, .hamburger {
                transition: none;
            }
            
            .card:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <!-- ===== الهيدر (نفس homep) ===== -->
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
            <a href="#" id="logoutLink">تسجيل خروج</a>
        </div>
    </nav>

    <!-- مودال تأكيد تسجيل الخروج -->
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

    <!-- ===== المحتوى الرئيسي ===== -->
    <div class="container">
        <h1 class="page-title">مفضلاتي</h1>
        <p class="page-subtitle">الأندية والفعاليات التي أضفتها إلى قائمة مفضلاتك</p>
        
        <!-- ===== التبويبات ===== -->
        <div class="tabs">
            <button class="tab active" data-tab="all">جميع المفضلات</button>
            <button class="tab" data-tab="clubs">الأندية</button>
            <button class="tab" data-tab="events">الفعاليات</button>
        </div>
        
        <!-- ===== محتوى التبويب: جميع المفضلات ===== -->
        <div class="tab-content active" id="all">
            <div class="cards-container" id="all-favorites">
                <?php if (count($bookmarks) > 0): ?>
                    <?php foreach($bookmarks as $bookmark): ?>
                        <div class="card" data-type="<?php echo $bookmark['itemType']; ?>">
                            <div class="card-header">
                                <img src="<?php echo $bookmark['itemType'] == 'event' ? ($bookmark['eventImage'] ?? 'images/event1.jpg') : ($bookmark['clubImage'] ?? 'images/club1.jpg'); ?>" alt="<?php echo $bookmark['itemType'] == 'event' ? $bookmark['eventTitle'] : $bookmark['clubName']; ?>">
                                <div class="card-badge"><?php echo $bookmark['itemType'] == 'event' ? 'فعالية' : 'نادي'; ?></div>
                            </div>
                            <div class="card-content">
                                <div class="title-row">
                                    <h3><?php echo $bookmark['itemType'] == 'event' ? $bookmark['eventTitle'] : $bookmark['clubName']; ?></h3>
                                    <button class="bookmark-btn active">★</button>
                                </div>
                                <p><?php echo $bookmark['itemType'] == 'event' ? $bookmark['eventDescription'] : $bookmark['clubDescription']; ?></p>
                                <button class="details-btn">التفاصيل ▼</button>
                                <div class="details-content">
                                    <p><strong>الموقع:</strong> <?php echo $bookmark['itemType'] == 'event' ? $bookmark['eventLocation'] : $bookmark['clubLocation']; ?></p>
                                    <?php if ($bookmark['itemType'] == 'event'): ?>
                                        <p><strong>الوقت:</strong> <?php echo $bookmark['eventDate']; ?></p>
                                        <p><strong>التاريخ:</strong> <?php echo $bookmark['eventDate']; ?></p>
                                    <?php else: ?>
                                        <p><strong>الاجتماعات:</strong> 
                                            <?php 
                                            $meeting_times = [
                                                1 => "كل يوم ثلاثاء، 6:00 مساءً",
                                                2 => "كل يوم أحد، 5:00 مساءً",
                                                3 => "كل يوم سبت، 4:00 مساءً"
                                            ];
                                            echo isset($meeting_times[$bookmark['itemID']]) ? $meeting_times[$bookmark['itemID']] : "سيتم الإعلان لاحقاً";
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                    <p><strong>تاريخ الإضافة:</strong> <?php echo $bookmark['dateAdded']; ?></p>
                                    <p><?php echo $bookmark['itemType'] == 'event' ? $bookmark['eventDescription'] : $bookmark['clubDescription']; ?></p>
                                </div>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline; flex: 1;">
                                        <?php if ($bookmark['itemType'] == 'event'): ?>
                                            <input type="hidden" name="book_event" value="1">
                                            <input type="hidden" name="event_id" value="<?php echo $bookmark['itemID']; ?>">
                                            <button type="button" class="reserve-btn" onclick="bookEvent(<?php echo $bookmark['itemID']; ?>)">احجز الآن</button>
                                        <?php else: ?>
                                            <input type="hidden" name="join_club" value="1">
                                            <input type="hidden" name="club_id" value="<?php echo $bookmark['itemID']; ?>">
                                            <button type="button" class="reserve-btn" onclick="joinClub(<?php echo $bookmark['itemID']; ?>)">انضم الآن</button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="remove_bookmark" value="1">
                                        <input type="hidden" name="bookmark_id" value="<?php echo $bookmark['bookmarkID']; ?>">
                                        <button type="submit" class="remove-btn" onclick="return confirm('هل تريد إزالة هذا العنصر من المفضلة؟')">
                                            إزالة
                                            <svg class="remove-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" id="empty-all">
                        <i>★</i>
                        <h3>لا توجد مفضلات حالياً</h3>
                        <p>يمكنك إضافة الأندية والفعاليات إلى المفضلة بالنقر على النجمة في أي نادي أو فعالية</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ===== محتوى التبويب: الأندية ===== -->
        <div class="tab-content" id="clubs">
            <div class="cards-container" id="club-favorites">
                <?php 
                $has_clubs = false;
                foreach($bookmarks as $bookmark): 
                    if ($bookmark['itemType'] == 'club'): 
                        $has_clubs = true;
                ?>
                    <div class="card" data-type="club">
                        <div class="card-header">
                            <img src="<?php echo $bookmark['clubImage'] ?? 'images/club1.jpg'; ?>" alt="<?php echo $bookmark['clubName']; ?>">
                            <div class="card-badge">نادي</div>
                        </div>
                        <div class="card-content">
                            <div class="title-row">
                                <h3><?php echo $bookmark['clubName']; ?></h3>
                                <button class="bookmark-btn active">★</button>
                            </div>
                            <p><?php echo $bookmark['clubDescription']; ?></p>
                            <button class="details-btn">التفاصيل ▼</button>
                            <div class="details-content">
                                <p><strong>الموقع:</strong> <?php echo $bookmark['clubLocation']; ?></p>
                                <p><strong>الاجتماعات:</strong> 
                                    <?php 
                                    $meeting_times = [
                                        1 => "كل يوم ثلاثاء، 6:00 مساءً",
                                        2 => "كل يوم أحد، 5:00 مساءً",
                                        3 => "كل يوم سبت، 4:00 مساءً"
                                    ];
                                    echo isset($meeting_times[$bookmark['itemID']]) ? $meeting_times[$bookmark['itemID']] : "سيتم الإعلان لاحقاً";
                                    ?>
                                </p>
                                <p><strong>تاريخ الإضافة:</strong> <?php echo $bookmark['dateAdded']; ?></p>
                                <p><?php echo $bookmark['clubDescription']; ?></p>
                            </div>
                            <div class="action-buttons">
                                <form method="POST" style="display: inline; flex: 1;">
                                    <input type="hidden" name="join_club" value="1">
                                    <input type="hidden" name="club_id" value="<?php echo $bookmark['itemID']; ?>">
                                    <button type="submit" class="reserve-btn">انضم الآن</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="remove_bookmark" value="1">
                                    <input type="hidden" name="bookmark_id" value="<?php echo $bookmark['bookmarkID']; ?>">
                                    <button type="submit" class="remove-btn" onclick="return confirm('هل تريد إزالة هذا العنصر من المفضلة؟')">
                                        إزالة
                                        <svg class="remove-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php 
                    endif;
                endforeach; 
                
                if (!$has_clubs): 
                ?>
                    <div class="empty-state" id="empty-clubs">
                        <i>★</i>
                        <h3>لا توجد أندية في المفضلة</h3>
                        <p>يمكنك إضافة الأندية إلى المفضلة بالنقر على النجمة في أي نادي</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ===== محتوى التبويب: الفعاليات ===== -->
        <div class="tab-content" id="events">
            <div class="cards-container" id="event-favorites">
                <?php 
                $has_events = false;
                foreach($bookmarks as $bookmark): 
                    if ($bookmark['itemType'] == 'event'): 
                        $has_events = true;
                ?>
                    <div class="card" data-type="event">
                        <div class="card-header">
                            <img src="<?php echo $bookmark['eventImage'] ?? 'images/event1.jpg'; ?>" alt="<?php echo $bookmark['eventTitle']; ?>">
                            <div class="card-badge">فعالية</div>
                        </div>
                        <div class="card-content">
                            <div class="title-row">
                                <h3><?php echo $bookmark['eventTitle']; ?></h3>
                                <button class="bookmark-btn active">★</button>
                            </div>
                            <p><?php echo $bookmark['eventDescription']; ?></p>
                            <button class="details-btn">التفاصيل ▼</button>
                            <div class="details-content">
                                <p><strong>الموقع:</strong> <?php echo $bookmark['eventLocation']; ?></p>
                                <p><strong>الوقت:</strong> <?php echo $bookmark['eventDate']; ?></p>
                                <p><strong>التاريخ:</strong> <?php echo $bookmark['eventDate']; ?></p>
                                <p><strong>تاريخ الإضافة:</strong> <?php echo $bookmark['dateAdded']; ?></p>
                                <p><?php echo $bookmark['eventDescription']; ?></p>
                            </div>
                            <div class="action-buttons">
                                <form method="POST" style="display: inline; flex: 1;">
                                    <input type="hidden" name="book_event" value="1">
                                    <input type="hidden" name="event_id" value="<?php echo $bookmark['itemID']; ?>">
                                    <button type="submit" class="reserve-btn">احجز الآن</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="remove_bookmark" value="1">
                                    <input type="hidden" name="bookmark_id" value="<?php echo $bookmark['bookmarkID']; ?>">
                                    <button type="submit" class="remove-btn" onclick="return confirm('هل تريد إزالة هذا العنصر من المفضلة؟')">
                                        إزالة
                                        <svg class="remove-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php 
                    endif;
                endforeach; 
                
                if (!$has_events): 
                ?>
                    <div class="empty-state" id="empty-events">
                        <i>★</i>
                        <h3>لا توجد فعاليات في المفضلة</h3>
                        <p>يمكنك إضافة الفعاليات إلى المفضلة بالنقر على النجمة في أي فعالية</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== الفوتر ===== -->
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
        // منيو الهيدر + مودال الخروج (نفس منطق homep)
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('navMenu');
        const closeBtn = document.getElementById('closeBtn');
        const logoutLink = document.getElementById('logoutLink');
        const logoutModal = document.getElementById('logoutModal');
        const confirmLogout = document.getElementById('confirmLogout');
        const cancelLogout = document.getElementById('cancelLogout');

        if (hamburger && navMenu) {
            hamburger.addEventListener('click', () => {
                navMenu.classList.add('show');
            });
        }

        if (closeBtn && navMenu) {
            closeBtn.addEventListener('click', () => {
                navMenu.classList.remove('show');
            });
        }

        if (navMenu) {
            navMenu.addEventListener('click', (e) => {
                if (e.target === navMenu) {
                    navMenu.classList.remove('show');
                }
            });
        }

        if (logoutLink && logoutModal) {
            logoutLink.addEventListener('click', (e) => {
                e.preventDefault();
                navMenu.classList.remove('show');
                logoutModal.classList.add('show');
            });
        }

        if (confirmLogout) {
            confirmLogout.addEventListener('click', () => {
                window.location.href = 'index.html'; // نفس صفحة الخروج في homep
            });
        }

        if (cancelLogout && logoutModal) {
            cancelLogout.addEventListener('click', () => {
                logoutModal.classList.remove('show');
            });

            logoutModal.addEventListener('click', (e) => {
                if (e.target === logoutModal) {
                    logoutModal.classList.remove('show');
                }
            });
        }

        // Esc يغلق المنيو أو المودال (نفس homep)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (navMenu && navMenu.classList.contains('show')) {
                    navMenu.classList.remove('show');
                }
                if (logoutModal && logoutModal.classList.contains('show')) {
                    logoutModal.classList.remove('show');
                }
            }
        });

        // إدارة التبويبات
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
                
                filterFavorites();
            });
        });
        
        // تفعيل أزرار التفاصيل
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('details-btn')) {
                const detailsContent = e.target.nextElementSibling;
                detailsContent.classList.toggle('active');
                e.target.textContent = detailsContent.classList.contains('active') ? 
                    'التفاصيل ▲' : 'التفاصيل ▼';
            }
        });
        
        // تصفية المفضلات حسب التبويب
        function filterFavorites() {
            const activeTab = document.querySelector('.tab.active').dataset.tab;
            const allCards = document.querySelectorAll('.card');
            
            allCards.forEach(card => {
                const cardType = card.getAttribute('data-type');
                
                if (activeTab === 'all') {
                    card.style.display = 'block';
                } else if (activeTab === 'clubs' && cardType === 'club') {
                    card.style.display = 'block';
                } else if (activeTab === 'events' && cardType === 'event') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function bookEvent(eventID) {
            fetch('booking_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'eventID=' + eventID
            })
            .then(response => response.text())
            .then(result => {
                if (result === 'success') {
                    alert('تم حجز الفعالية بنجاح');
                } else if (result === 'already') {
                    alert('لديك حجز مسبق في هذه الفعالية');
                } else if (result === 'full') {
                    alert('عفواً، لا توجد أماكن متاحة في هذه الفعالية');
                } else if (result === 'expired') {
                    alert('هذه الفعالية قد انتهت');
                } else {
                    alert('حدث خطأ أثناء الحجز');
                }
            });
        }

        function joinClub(clubID) {
            fetch('club_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'clubID=' + clubID
            })
            .then(response => response.text())
            .then(result => {
                if (result === 'success') {
                    alert('تم إرسال طلب انضمامك إلى النادي بنجاح');
                } else if (result === 'already') {
                    alert('أنت بالفعل عضو في هذا النادي');
                } else {
                    alert('حدث خطأ أثناء الانضمام إلى النادي');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            filterFavorites();
        });
    </script>
</body>
</html>
