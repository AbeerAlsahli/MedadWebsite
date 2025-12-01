<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

//sanitization 
function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
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

// Temporary REMOVE AFTER ABEER PAGE
if (!isset($_SESSION['userID'])) {
    $_SESSION['userID'] = 1; // Use user ID 1 for testing
    $_SESSION['username'] = 'Test User';
}

require_once 'config.php';

$userID = $_SESSION['userID'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_booking'])) {
        $bookingID = $_POST['booking_id'];
        cancelBooking($bookingID, $pdo);
    } elseif (isset($_POST['submit_comment'])) {
        $eventID = $_POST['event_id'];
        $comment = $_POST['comment'];
        addComment($userID, $eventID, $comment, $pdo);
    } elseif (isset($_POST['toggle_bookmark'])) {
        $itemID = $_POST['item_id'];
        $itemType = $_POST['item_type'];
        toggleBookmark($userID, $itemID, $itemType, $pdo);
    }
    
    // Redirect to prevent resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Function to cancel a booking
function cancelBooking($bookingID, $pdo) {
    $sql = "DELETE FROM booking WHERE bookingID = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$bookingID])) {
        $_SESSION['success_message'] = 'تم إلغاء الحجز بنجاح';
    } else {
        $_SESSION['error_message'] = 'حدث خطأ أثناء إلغاء الحجز';
    }
}

// Function to add a comment
function addComment($userID, $eventID, $comment, $pdo) {
    // Validate comment is not empty
    if (empty(trim($comment))) {
        $_SESSION['error_message'] = 'يرجى كتابة تعليق قبل الإرسال';
        return;
    }
    
    $sql = "INSERT INTO comment (userID, eventID, content, timestamp) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$userID, $eventID, trim($comment)])) {
        $_SESSION['success_message'] = 'شكراً لك! تم إرسال تعليقك بنجاح.';
    } else {
        $_SESSION['error_message'] = 'حدث خطأ أثناء إرسال التعليق';
    }
}

// Function to toggle bookmark
function toggleBookmark($userID, $itemID, $itemType, $pdo) {
    // Check if bookmark exists
    $check_sql = "SELECT * FROM bookmark WHERE userID = ? AND itemID = ? AND itemType = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$userID, $itemID, $itemType]);
    
    if ($check_stmt->fetch()) {
        // Remove bookmark
        $delete_sql = "DELETE FROM bookmark WHERE userID = ? AND itemID = ? AND itemType = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$userID, $itemID, $itemType]);
    } else {
        // Add bookmark
        $insert_sql = "INSERT INTO bookmark (userID, itemType, itemID, dateAdded) VALUES (?, ?, ?, CURDATE())";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([$userID, $itemType, $itemID]);
    }
}

// Get current bookings for the user
$current_bookings_sql = "
    SELECT b.bookingID, e.eventID, e.title, e.date, e.location, e.description, b.status 
    FROM booking b 
    JOIN event e ON b.eventID = e.eventID 
    WHERE b.userID = ? AND e.date >= CURDATE() AND b.status != 'cancelled'
    ORDER BY e.date ASC";
$current_stmt = $pdo->prepare($current_bookings_sql);
$current_stmt->execute([$userID]);
$current_bookings = $current_stmt->fetchAll();

// Get past bookings for the user
$past_bookings_sql = "
    SELECT b.bookingID, e.eventID, e.title, e.date, e.location, e.description, b.status 
    FROM booking b 
    JOIN event e ON b.eventID = e.eventID 
    WHERE b.userID = ? AND (e.date < CURDATE() OR b.status = 'cancelled')
    ORDER BY e.date DESC";
$past_stmt = $pdo->prepare($past_bookings_sql);
$past_stmt->execute([$userID]);
$past_bookings = $past_stmt->fetchAll();

// Get bookmarks for the user
$bookmarks_sql = "SELECT itemID, itemType FROM bookmark WHERE userID = ?";
$bookmarks_stmt = $pdo->prepare($bookmarks_sql);
$bookmarks_stmt->execute([$userID]);
$bookmarks_result = $bookmarks_stmt->fetchAll();
$bookmarks = [];
foreach ($bookmarks_result as $row) {
    $bookmarks[$row['itemType'] . '_' . $row['itemID']] = true;
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
        /* All the CSS styles from your original code */
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
            --barcode-blue: #2C5AA0;
            --barcode-light: #4A76B8;
            --success: #27ae60;
            --success-light: rgba(39, 174, 96, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Almarai', 'Arial', sans-serif;
        }

        body {
            background-color: #f8f8f8;
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
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
            z-index: 1500;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            text-align: center;
        }

        .nav-menu.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
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
            background: rgba(255, 255, 255, 0.95);
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

        /*  المحتوى الرئيسي  */
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
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
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
            font-size: 1.5rem;
            cursor: pointer;
            color: #ddd;
            transition: color 0.3s;
        }

        .bookmark-btn.active {
            color: gold;
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

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 1rem;
        }

        .cancel-btn {
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

        .cancel-btn:hover {
            background-color: #5a152c;
        }

        .barcode-btn {
            background: linear-gradient(135deg, var(--barcode-blue), var(--barcode-light));
            color: white;
            border: none;
            padding: 0.7rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            position: relative;
            overflow: hidden;
        }

        .barcode-btn:hover {
            background: linear-gradient(135deg, var(--barcode-light), var(--barcode-blue));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 90, 160, 0.3);
        }

        .barcode-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .barcode-btn:hover::before {
            left: 100%;
        }

        .barcode-icon {
            width: 24px;
            height: 24px;
            filter: brightness(0) invert(1);
        }

        .barcode-text {
            display: none;
            font-size: 0.8rem;
            margin-right: 5px;
        }

        .feedback-section {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            display: none;
        }

        .feedback-section.active {
            display: block;
        }

        .feedback-title {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .comment-section {
            margin-bottom: 1rem;
        }

        .comment-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            font-weight: 500;
        }

        .comment-textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }

        .comment-textarea:focus {
            outline: none;
            border-color: var(--burgundy);
        }

        .submit-feedback {
            background-color: var(--success);
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            width: 100%;
        }

        .submit-feedback:hover {
            background-color: #219653;
        }

        .feedback-success {
            background-color: var(--success-light);
            color: var(--success);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin-top: 1rem;
            display: none;
        }

        .feedback-success.active {
            display: block;
        }

        .add-feedback-btn {
            background-color: var(--accent);
            color: var(--text-dark);
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            width: 100%;
            margin-top: 1rem;
        }

        .add-feedback-btn:hover {
            background-color: #d4b98c;
            transform: translateY(-2px);
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-top: 0.5rem;
        }

        .status-active {
            background-color: rgba(46, 204, 113, 0.1);
            color: #27ae60;
        }

        .status-cancelled {
            background-color: rgba(231, 76, 60, 0.1);
            color: #c0392b;
        }

        .status-completed {
            background-color: rgba(52, 152, 219, 0.1);
            color: #2980b9;
        }

        /*  نافذة الباركود  */
        .barcode-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .barcode-modal.active {
            display: flex;
        }

        .barcode-content {
            background: white;
            border-radius: 14px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .barcode-close {
            position: absolute;
            top: 15px;
            left: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
        }

        .barcode-title {
            font-family: 'Doran', 'Arial', sans-serif;
            color: var(--burgundy);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .barcode-image {
            width: 100%;
            max-width: 300px;
            height: auto;
            margin: 1rem 0;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 10px;
            background: white;
        }

        .barcode-info {
            margin: 1rem 0;
            text-align: right;
        }

        .barcode-info p {
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }

        .print-btn {
            background: var(--burgundy);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 1rem;
            transition: all 0.3s;
            width: 100%;
        }

        .print-btn:hover {
            background: #5a152c;
        }

        .delete-modal {
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

        .delete-modal.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .delete-content {
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

        .delete-content::before {
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

        .delete-content::after {
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

        .delete-content h3 {
            color: var(--burgundy);
            margin-bottom: 20px;
            font-family: 'Doran', 'Arial', sans-serif;
            font-size: 1.5rem;
            font-weight: bold;
            position: relative;
            z-index: 2;
        }

        .delete-content p {
            color: var(--text-light);
            margin-bottom: 30px;
            font-size: 1.1rem;
            line-height: 1.6;
            position: relative;
            z-index: 2;
        }

        .delete-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }

        .confirm-delete-btn {
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

        .confirm-delete-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(123, 30, 58, 0.3);
        }

        .cancel-delete-btn {
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

        .cancel-delete-btn:hover {
            background: var(--text-light);
            color: white;
            transform: translateY(-3px);
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
            z-index: 2100;
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
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }

        .empty-state h3 {
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-light);
            max-width: 400px;
            margin: 0 auto;
        }

        /*  الفوتر  */
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
           
            color: var(--burgundy);
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
            
            .barcode-btn {
                width: 100%;
                margin-top: 10px;
                justify-content: flex-start;
                padding: 0.7rem 1rem;
            }
            
            .barcode-text {
                display: inline;
            }
            
            .delete-content,
            .logout-content {
                min-width: 280px;
                padding: 30px 20px;
            }
            
            .delete-actions,
            .logout-actions {
                flex-direction: column;
            }
            
            .confirm-delete-btn, 
            .cancel-delete-btn,
            .confirm-logout-btn,
            .cancel-logout-btn {
                width: 100%;
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
            
            .barcode-content {
                padding: 1.5rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .card, .cancel-btn, .hamburger {
                transition: none;
            }
            
            .card:hover {
                transform: none;
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

    <!--  المحتوى الرئيسي  -->
    <div class="container">
        <h1 class="page-title">حجوزاتي</h1>
        
        <div class="tabs">
            <button class="tab active" data-tab="current">حجوزاتي الحالية</button>
            <button class="tab" data-tab="history">سجل الحجوزات</button>
        </div>
        
        <div class="tab-content active" id="current">
            <div class="cards-container" id="current-bookings">
               <?php if (count($current_bookings) > 0): ?>
    <?php foreach($current_bookings as $booking): ?>
                        <div class="card">
                            <div class="card-header">
                                <img src="image/event<?php echo $booking['eventID']; ?>.jpg" alt="<?php echo $booking['title']; ?>">
                                <div class="card-badge">فعالية</div>
                            </div>
                            <div class="card-content">
                                <div class="title-row">
                                    <h3><?php echo $booking['title']; ?></h3>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="toggle_bookmark" value="1">
                                        <input type="hidden" name="item_id" value="<?php echo $booking['eventID']; ?>">
                                        <input type="hidden" name="item_type" value="event">
                                        
                                    </form>
                                </div>
                                <p><?php echo $booking['description']; ?></p>
                                <button class="details-btn">التفاصيل ▼</button>
                                <div class="details-content">
                                    <p><strong>الموقع:</strong> <?php echo $booking['location']; ?></p>
                                    <p><strong>الوقت:</strong> <?php echo $booking['date']; ?></p>
                                    <p><strong>التاريخ:</strong> <?php echo $booking['date']; ?></p>
                                    <p><?php echo $booking['description']; ?></p>
                                    <div class="status-badge status-active">نشط</div>
                                </div>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline; width: 100%;">
                                        <input type="hidden" name="cancel_booking" value="1">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['bookingID']; ?>">
                                        <button type="submit" class="cancel-btn" onclick="return confirm('هل أنت متأكد أنك تريد إلغاء هذا الحجز؟')">إلغاء الحجز</button>
                                    </form>
                                    <button class="barcode-btn" data-booking-id="BKG-<?php echo $booking['bookingID']; ?>">
                                        <span class="barcode-text">باركود التذكرة</span>
                                        <svg class="barcode-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 5h2M7 5h1M11 5h2M15 5h1M19 5h2M3 10h1M7 10h2M11 10h1M15 10h2M19 10h1M3 15h2M7 15h1M11 15h2M15 15h1M19 15h2M3 20h1M7 20h2M11 20h1M15 20h2M19 20h1"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" id="empty-current">
                        <i>📭</i>
                        <h3>لا توجد حجوزات حالية</h3>
                        <p>يمكنك حجز فعالية من صفحة الفعاليات أو الأندية</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="tab-content" id="history">
            <div class="cards-container" id="past-bookings">
                <?php if (count($past_bookings) > 0): ?>
    <?php foreach($past_bookings as $booking): ?>
                        <div class="card">
                            <div class="card-header">
                                <img src="image/event<?php echo $booking['eventID']; ?>.jpg" alt="<?php echo $booking['title']; ?>">
                                <div class="card-badge">فعالية</div>
                            </div>
                            <div class="card-content">
                                <div class="title-row">
                                    <h3><?php echo $booking['title']; ?></h3>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="toggle_bookmark" value="1">
                                        <input type="hidden" name="item_id" value="<?php echo $booking['eventID']; ?>">
                                        <input type="hidden" name="item_type" value="event">
                                       
                                    </form>
                                </div>
                                <p><?php echo $booking['description']; ?></p>
                                <button class="details-btn">التفاصيل ▼</button>
                                <div class="details-content">
                                    <p><strong>الموقع:</strong> <?php echo $booking['location']; ?></p>
                                    <p><strong>الوقت:</strong> <?php echo $booking['date']; ?></p>
                                    <p><strong>التاريخ:</strong> <?php echo $booking['date']; ?></p>
                                    <p><?php echo $booking['description']; ?></p>
                                    <div class="status-badge <?php echo $booking['status'] == 'cancelled' ? 'status-cancelled' : 'status-completed'; ?>">
                                        <?php echo $booking['status'] == 'cancelled' ? 'ملغاة' : 'منتهية'; ?>
                                    </div>
                                </div>
                                
                                <?php if ($booking['status'] != 'cancelled'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="event_id" value="<?php echo $booking['eventID']; ?>">
                                        <button type="button" class="add-feedback-btn" data-event-id="event-<?php echo $booking['eventID']; ?>">أضف تعليقك عن الفعالية</button>
                                        
                                        <div class="feedback-section" id="feedback-event-<?php echo $booking['eventID']; ?>">
                                            <h4 class="feedback-title">شاركنا رأيك عن الفعالية</h4>
                                            <div class="comment-section">
                                                <label class="comment-label">تعليقك:</label>
                                                <textarea class="comment-textarea" name="comment" placeholder="اكتب تعليقك عن الفعالية هنا..." required></textarea>
                                            </div>
                                            <button type="submit" name="submit_comment" class="submit-feedback">إرسال التعليق</button>
                                            <div class="feedback-success" id="success-event-<?php echo $booking['eventID']; ?>">
                                                شكراً لك! تم إرسال تعليقك بنجاح.
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" id="empty-history">
                        <i>📖</i>
                        <h3>لا توجد حجوزات سابقة</h3>
                        <p>سيظهر هنا سجل حجوزاتك الملغاة أو المنتهية</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!--  نافذة الباركود  -->
    <div class="barcode-modal" id="barcodeModal">
        <div class="barcode-content">
            <button class="barcode-close" id="barcodeClose">×</button>
            <h3 class="barcode-title">باركود التذكرة</h3>
            <div class="barcode-info">
                <p><strong>الفعالية:</strong> <span id="barcodeEventName">معرض الأدب السعودي</span></p>
                <p><strong>التاريخ:</strong> <span id="barcodeEventDate">15 نوفمبر 2025</span></p>
                <p><strong>رقم الحجز:</strong> <span id="barcodeBookingId">BKG-001</span></p>
            </div>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=MEDAD-BKG-001" alt="باركود التذكرة" class="barcode-image" id="barcodeImage">
            <button class="print-btn" id="printBarcode">طباعة الباركود</button>
        </div>
    </div>

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

    <!--  الفوتر  -->
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
        const hamburger     = document.getElementById('hamburger');
        const navMenu       = document.getElementById('navMenu');
        const closeBtn      = document.getElementById('closeBtn');
        const logoutLink    = document.getElementById('logoutLink');
        const logoutModal   = document.getElementById('logoutModal');
        const confirmLogout = document.getElementById('confirmLogout');
        const cancelLogout  = document.getElementById('cancelLogout');

        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });
        
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('details-btn')) {
                const detailsContent = e.target.nextElementSibling;
                detailsContent.classList.toggle('active');
                e.target.textContent = detailsContent.classList.contains('active') ? 
                    'التفاصيل ▲' : 'التفاصيل ▼';
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('barcode-btn') || e.target.closest('.barcode-btn')) {
                const button    = e.target.classList.contains('barcode-btn') ? e.target : e.target.closest('.barcode-btn');
                const bookingId = button.getAttribute('data-booking-id');
                const card      = button.closest('.card');
                const eventName = card.querySelector('h3').textContent;
                const eventDate = card.querySelector('.details-content p:nth-child(2)').textContent.replace('الوقت: ', '');
                
                document.getElementById('barcodeEventName').textContent = eventName;
                document.getElementById('barcodeEventDate').textContent = eventDate;
                document.getElementById('barcodeBookingId').textContent = bookingId;
                document.getElementById('barcodeImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=MEDAD-${bookingId}`;
                
                document.getElementById('barcodeModal').classList.add('active');
            }
        });
        
        document.getElementById('barcodeClose').addEventListener('click', function() {
            document.getElementById('barcodeModal').classList.remove('active');
        });
        
        document.getElementById('barcodeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
        
        document.getElementById('printBarcode').addEventListener('click', function() {
            window.print();
        });
        
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-feedback-btn')) {
                const eventId         = e.target.getAttribute('data-event-id');
                const feedbackSection = document.getElementById(`feedback-${eventId}`);
                
                document.querySelectorAll('.feedback-section').forEach(section => {
                    section.classList.remove('active');
                });
                
                document.querySelectorAll('.feedback-success').forEach(success => {
                    success.classList.remove('active');
                });
                
                feedbackSection.classList.add('active');
                e.target.style.display = 'none';
            }
        });

        // Add form validation for empty comments
        document.addEventListener('submit', function(e) {
            if (e.target && e.target.querySelector('textarea[name="comment"]')) {
                const commentTextarea = e.target.querySelector('textarea[name="comment"]');
                const comment = commentTextarea.value.trim();
                
                if (comment === '') {
                    e.preventDefault();
                    alert('يرجى كتابة تعليق قبل الإرسال');
                    commentTextarea.focus();
                    return false;
                }
            }
        });

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
    </script>
</body>
</html>

