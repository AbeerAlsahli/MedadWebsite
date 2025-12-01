<?php
// Start session and include database configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Temporary REMOVE AFTER ABEER PAGE
if (!isset($_SESSION['userID'])) {
    $_SESSION['userID'] = 1;
    $_SESSION['username'] = 'Test User';
}

require_once 'config.php';

//  sanitization 
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Initialize variables
$events = [];
$clubs = [];
$search_term = '';
$category = 'all';
$event_comments = [];

// Sanitize search input
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = clean_input(trim($_GET['search']));
    // Limit length
    if (strlen($search_term) > 100) {
        $search_term = substr($search_term, 0, 100);
    }
}

// Validate category
if (isset($_GET['category'])) {
    $allowed_categories = ['all', 'events', 'clubs'];
    $category = in_array($_GET['category'], $allowed_categories) ? $_GET['category'] : 'all';
}

// Get user's bookmarks 
$bookmarked_events = [];
$bookmarked_clubs = [];

if (isset($_SESSION['userID'])) {
    try {
        $bookmark_sql = "SELECT itemType, itemID FROM bookmark WHERE userID = ?";
        $stmt = $pdo->prepare($bookmark_sql);
        $stmt->execute([$_SESSION['userID']]);
        $user_bookmarks = $stmt->fetchAll();
        
        foreach ($user_bookmarks as $bookmark) {
            if ($bookmark['itemType'] === 'event') {
                $bookmarked_events[] = (int)$bookmark['itemID'];
            } else if ($bookmark['itemType'] === 'club') {
                $bookmarked_clubs[] = (int)$bookmark['itemID'];
            }
        }
    } catch (PDOException $e) {
        
    }
}




// Get user interests for recommendations
$user_interests = '';
if (isset($_SESSION['userID'])) {
    try {
        $user_sql = "SELECT interests FROM users WHERE userID = ?";
        $stmt = $pdo->prepare($user_sql);
        $stmt->execute([$_SESSION['userID']]);
        $user_data = $stmt->fetch();
        
        if ($user_data && !empty($user_data['interests'])) {
            $user_interests = $user_data['interests'];
        }
    } catch (PDOException $e) {
    }
}

// Handle search
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = trim($_GET['search']);
}

// Handle filter
if (isset($_GET['category'])) {
    $category = $_GET['category'];
}

try {
    // Events query 
    $events_sql = "SELECT eventID, title, date, location, description, capacity, status,image_path 
                   FROM event 
                   WHERE status = 'open'";
    
    if (!empty($search_term)) {
        $events_sql .= " AND (title LIKE :search OR description LIKE :search OR location LIKE :search)";
    }
    
    $events_sql .= " ORDER BY date ASC";
    
    $stmt = $pdo->prepare($events_sql);
    
    if (!empty($search_term)) {
        $search_param = "%$search_term%";
        $stmt->bindParam(':search', $search_param);
    }
    
    $stmt->execute();
    $events = $stmt->fetchAll();
    
    // Clubs query 
    $clubs_sql = "SELECT clubID, name, description, location,image_path FROM club WHERE 1=1";
    
    if (!empty($search_term)) {
        $clubs_sql .= " AND (name LIKE :club_search OR description LIKE :club_search OR location LIKE :club_search)";
    }
    
    $stmt_clubs = $pdo->prepare($clubs_sql);
    
    if (!empty($search_term)) {
        $stmt_clubs->bindParam(':club_search', $search_param);
    }
    
    $stmt_clubs->execute();
    $clubs = $stmt_clubs->fetchAll();
    
    // Get comments for events
   $comments_sql = "SELECT c.*, u.name as user_name 
                 FROM comment c 
                 JOIN users u ON c.userID = u.userID 
                 ORDER BY c.timestamp DESC";
$stmt_comments = $pdo->prepare($comments_sql);
$stmt_comments->execute();
$all_comments = $stmt_comments->fetchAll();
    
    // Organize comments by eventID
    foreach ($all_comments as $comment) {
        $event_comments[$comment['eventID']][] = $comment;
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "عذراً، حدث خطأ في تحميل البيانات. الرجاء المحاولة لاحقاً.";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مِـداد | الفعاليات والأندية</title>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    <style>
        /* Your existing CSS styles */
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Almarai', 'Arial', sans-serif;
        }

        body {
            background-color: #f8f8f8;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .main-header {
            background: transparent;
            padding: 12px 0;
            margin-top: 10px;
            margin-bottom: 10px;
            border-bottom: 1px solid transparent;
        }

        .header-shell {
            width: min(1100px, calc(100% - 24px));
            margin: 0 auto;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 15px;
            background: #fff;
            border-radius: 999px;
            padding: 8px 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            position: relative;
            z-index: 100;
        }

        .header-start {
            justify-self: start;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-center {
            justify-self: center;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-container {
            display: none;
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

        @media (max-width: 768px) {
            .header-shell {
                grid-template-columns: auto 1fr auto;
                gap: 10px;
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
            from { 
                opacity: 0; 
            }
            to { 
                opacity: 1; 
            }
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

       
        .page-header-section {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 16px;
            text-align: right;
            position: relative;
        }

        .page-header {
            margin-bottom: 24px;
            position: relative;
            display: inline-block;
        }

        .page-title {
            font-family: 'Doran', 'Arial', sans-serif;
            font-size: 2rem;
            color: var(--burgundy);
            margin-bottom: 8px;
            position: relative;
        }

        .animated-line {
            height: 3px;
            width: 0;
            background: var(--burgundy);
            position: absolute;
            bottom: -5px;
            right: 0;
            animation: expandLine 2s ease-in-out infinite;
        }

        @keyframes expandLine {
            from {
                width: 0;
            }
            to {
                width: 100%;
            }
        }

        .animated-line {
            animation: expandLine 2s ease-in-out infinite alternate;
        }

        .page-content {
            font-family: 'Almarai', 'Arial', sans-serif;
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-light);
            text-align: justify;
            max-width: 800px;
            margin-top: 16px;
            font-weight: 400;
        }

        .recommended-btn {
            font-family: 'Doran', 'Arial', sans-serif;
            background-color: var(--burgundy);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: bold;
            box-shadow: var(--shadow);
            position: absolute;
            left: 16px;
            top: 0;
        }

        .recommended-btn:hover {
            background-color: #5a152c;
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .recommended-btn.back-btn {
            background-color: var(--text-light);
        }

        .recommended-btn.back-btn:hover {
            background-color: #444444;
        }

        .category-section {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            position: relative;
        }

        .category-tabs {
            display: flex;
            justify-content: flex-start;
            flex: 1;
        }

        .category-tab {
            background: none;
            border: none;
            padding: 12px 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Almarai', 'Arial', sans-serif;
            font-size: 1.1rem;
            color: var(--text-light);
            font-weight: 600;
            position: relative;
        }

        .category-tab::after {
            content: '';
            position: absolute;
            bottom: -2px;
            right: 0;
            width: 0;
            height: 3px;
            background: var(--burgundy);
            transition: width 0.3s ease;
        }

        .category-tab:hover {
            color: var(--burgundy);
        }

        .category-tab:hover::after {
            width: 100%;
        }

        .category-tab.active {
            color: var(--burgundy);
        }

        .category-tab.active::after {
            width: 100%;
        }

        .search-container-new {
            position: relative;
            width: 250px;
            margin-right: 20px;
        }

        .search-input-new {
            width: 100%;
            padding: 10px 45px 10px 15px;
            border: 2px solid var(--burgundy-light);
            border-radius: 25px;
            font-size: 0.9rem;
            background: var(--off-white);
            transition: all 0.3s ease;
            font-family: 'Almarai', 'Arial', sans-serif;
        }

        .search-input-new:focus {
            outline: none;
            border-color: var(--burgundy);
            box-shadow: 0 0 0 3px var(--burgundy-light);
        }

        .search-input-new::placeholder {
            color: var(--text-light);
            opacity: 0.7;
        }

        .search-btn-new {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--burgundy);
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .search-btn-new:hover {
            color: #5a152c;
            transform: translateY(-50%) scale(1.1);
        }

        /* المحتوى الرئيسي */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 16px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            align-items: start;
        }

        @media (max-width: 900px) {
            .container {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 600px) {
            .container {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background-color: #fff;
            margin-bottom: 0;
            border-radius: 14px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 400px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
            background-color: #f0f0f0;
        }

        .card-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card h3 {
            font-family: 'Doran', 'Arial', sans-serif;
            color: var(--burgundy);
            margin-bottom: 10px;
            font-size: 1.3rem;
            line-height: 1.3;
        }

        .card p {
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--text-light);
            margin-bottom: 15px;
            flex: 1;
        }

        .book {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2;
        }

        .book button {
            background-color: var(--burgundy);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.85rem;
            cursor: pointer;
            font-family: 'Almarai', 'Arial', sans-serif;
            transition: all 0.3s ease;
        }

        .book button:hover {
            background-color: #5a152c;
            transform: translateY(-2px);
        }

        .details-btn {
            background-color: var(--off-white);
            border: 1px solid rgba(123, 30, 58, 0.1);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.85rem;
            cursor: pointer;
            width: 100%;
            text-align: center;
            color: var(--burgundy);
            margin-top: auto;
            transition: all 0.3s ease;
            font-family: 'Almarai', 'Arial', sans-serif;
        }

        .details-btn:hover {
            background-color: var(--burgundy-light);
        }

        .details-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background-color: var(--off-white);
            border-radius: 8px;
            margin-top: 10px;
            padding: 0 12px;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .countdown {
            background: var(--burgundy);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
            font-family: 'Doran', 'Arial', sans-serif;
        }

        .event-ended {
            background: #dc3545;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
            font-family: 'Doran', 'Arial', sans-serif;
        }

        .title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .title-row h3 {
            margin: 0;
            flex: 1;
            word-wrap: break-word;
        }

        .bookmark-btn {
            background: none;
            border: none;
            color: #ccc;
            font-size: 1.5rem;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            padding: 2px 4px;
            line-height: 1;
            flex-shrink: 0;
        }

        .bookmark-btn:hover {
            background-color: rgba(123, 30, 58, 0.1);
            transform: scale(1.08);
        }

        .bookmark-btn.active {
            color: var(--burgundy);
            transform: scale(1.1);
        }

        .hidden {
            display: none !important;
        }

        .feedback-section {
            margin-top: 15px;
            border-top: 1px solid rgba(123, 30, 58, 0.1);
            padding-top: 15px;
        }

        .feedback-title {
            font-weight: bold;
            color: var(--burgundy);
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .feedback-item {
            background: white;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-right: 3px solid var(--burgundy-light);
        }

        .feedback-text {
            font-size: 0.85rem;
            line-height: 1.5;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

     
.feedback-item {
    position: relative; 
   
}


.feedback-user {
    
    display: block; 
    
    font-size: 0.75rem;
    color: var(--text-light);
    padding-top: 5px; 
    text-align: right; 
}


.comment-timestamp {
    color: var(--burgundy);
    font-size: 0.8rem; 
    font-weight: 600;
    direction: ltr;
    display: inline-block;
    padding: 2px 6px;
    background: var(--burgundy-light);
    border-radius: 4px;
    display: inline-block !important; 
    visibility: visible !important; 

    position: absolute; 
    left: 10px;        
    top: 50%;         
    transform: translateY(-50%); 
}
        .no-feedback {
            text-align: center;
            color: var(--text-light);
            font-size: 0.85rem;
            padding: 10px;
            font-style: italic;
        }

        .error-message {
            background: #ffe6e6;
            color: #d63031;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            border: 1px solid #ff7675;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
            font-size: 1.1rem;
            grid-column: 1 / -1;
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
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


        /* Responsive styles */
        @media (max-width: 768px) {
            .page-title {
                font-size: 1.7rem;
            }
            .page-content {
                font-size: 1rem;
            }
            .container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
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
            .category-section {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            .category-tabs {
                justify-content: center;
                border-bottom: none;
            }
            .category-tab {
                width: 200px;
                text-align: center;
                border-bottom: 2px solid #eee;
                padding: 15px;
            }
            .category-tab::after {
                bottom: -2px;
            }
            .search-container-new {
                width: 100%;
                margin-right: 0;
            }
            .recommended-btn {
                position: relative;
                left: auto;
                top: auto;
                margin-bottom: 20px;
                margin-right: 0;
            }
            .page-header-section {
                text-align: center;
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

    <section class="page-header-section">
        <button class="recommended-btn" id="recommendedBtn">اقتراحات مِداد</button>
        <div class="page-header">
            <h2 class="page-title">الفعاليات والأندية</h2>
            <div class="animated-line"></div>
        </div>
        <div class="page-content">
            <p>
                استكشف مجموعة متنوعة من الفعاليات والأندية الأدبية في المملكة العربية السعودية. 
                سواء كنت تبحث عن أمسيات شعرية، ورش كتابة، ندوات ثقافية، أو نوادي أدبية، 
                ستجد هنا كل ما يلهمك ويثري شغفك بالأدب.
            </p>
        </div>
    </section>

    <div class="category-section">
        <div class="category-tabs">
            <button class="category-tab <?php echo $category === 'all' ? 'active' : ''; ?>" 
                    onclick="filterCategory('all')">الكل</button>
            <button class="category-tab <?php echo $category === 'events' ? 'active' : ''; ?>" 
                    onclick="filterCategory('events')">الفعاليات</button>
            <button class="category-tab <?php echo $category === 'clubs' ? 'active' : ''; ?>" 
                    onclick="filterCategory('clubs')">الأندية</button>
        </div>
        <form method="GET" class="search-container-new">
            <input type="text" class="search-input-new" name="search" id="searchInputNew" 
                   placeholder="ابحث عن فعالية أو نادي..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="search-btn-new">🔍</button>
        </form>
    </div>

    <div class="container" id="mainContainer">
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php 
        $hasResults = false;
        
        // Display events based on category filter
        if (($category === 'all' || $category === 'events') && !empty($events)): 
            $hasResults = true;
            foreach ($events as $event): 
        ?>
            <div class="card event-card" data-category="event">
                <div class="book">
                    <button onclick="bookEvent(<?php echo $event['eventID']; ?>)">احجز الآن!</button>
                </div>
                <img src="<?php echo htmlspecialchars($event['image_path'] ?? 'image/event4.jpg'); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
           <div class="card-content">
            <div class="title-row">
                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                <button type="button" class="bookmark-btn <?php echo in_array($event['eventID'], $bookmarked_events) ? 'active' : ''; ?>" 
                        onclick="toggleBookmark(<?php echo $event['eventID']; ?>, 'event', this)">
                    <?php echo in_array($event['eventID'], $bookmarked_events) ? '★' : '☆'; ?>
                </button>
            </div>
            <p><?php echo htmlspecialchars($event['description']); ?></p>
            
            
        
            
            <button class="details-btn">التفاصيل ▼</button>
            <div class="details-content">
                <p><strong>الموقع:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                <p><strong>الوقت:</strong> <?php echo date('Y-m-d', strtotime($event['date'])); ?></p>
                <p><strong>السعة:</strong> <?php echo $event['capacity']; ?> شخص</p>
                <div class="countdown" id="countdown-<?php echo $event['eventID']; ?>" 
                    data-event-date="<?php echo $event['date']; ?>">
                </div>
                <p><?php echo htmlspecialchars($event['description']); ?></p>
    <div class="feedback-section">
                <div class="feedback-title">آراء المشاركين:</div>
                <?php 
                $currentEventId = $event['eventID'];
                $hasComments = isset($event_comments[$currentEventId]) && !empty($event_comments[$currentEventId]);
                
                if ($hasComments): 
                    foreach ($event_comments[$currentEventId] as $comment): 
                ?>
                    <div class="feedback-item">
                        
                        <span class="comment-timestamp">
                            <?php echo !empty($comment['timestamp']) ? date('Y-m-d', strtotime($comment['timestamp'])) : '2025-11-28'; ?>
                        </span>
                        
                        <div class="feedback-text"><?php echo htmlspecialchars($comment['content']); ?></div>
                        
                        <div class="feedback-user">
                            - <?php echo htmlspecialchars($comment['user_name']); ?>
                        </div>
                    </div>
                <?php 
                    endforeach; 
                else: 
                ?>
                    <div class="no-feedback">لا توجد آراء حتى الآن</div>
                <?php endif; ?>
            </div>
            </div>
        </div>
            </div>
        <?php endforeach; endif; ?>

        <?php 
        // Display clubs based on category filter
        if (($category === 'all' || $category === 'clubs') && !empty($clubs)): 
            $hasResults = true;
            foreach ($clubs as $club): 
        ?>
            <div class="card club-card" data-category="club">
                <div class="book">
                    <button onclick="joinClub(<?php echo $club['clubID']; ?>)">انضم الآن!</button>
                </div>
                <img src="<?php echo htmlspecialchars($club['image_path'] ?? 'image/event3.jpg'); ?>" alt="<?php echo htmlspecialchars($club['name']); ?>">
                <div class="card-content">
                    <div class="title-row">
                        <h3><?php echo htmlspecialchars($club['name']); ?></h3>
                        <button type="button" class="bookmark-btn <?php echo in_array($club['clubID'], $bookmarked_clubs) ? 'active' : ''; ?>" 
                                onclick="toggleBookmark(<?php echo $club['clubID']; ?>, 'club', this)">
                            <?php echo in_array($club['clubID'], $bookmarked_clubs) ? '★' : '☆'; ?>
                        </button>
                    </div>
                    <p><?php echo htmlspecialchars($club['description']); ?></p>
                    <button class="details-btn">التفاصيل ▼</button>
                    <div class="details-content">
                        <p><strong>الموقع:</strong> <?php echo htmlspecialchars($club['location']); ?></p>
                        <p><strong>النشاط:</strong> نادي أدبي تفاعلي</p>
                        <p>مجتمع يهدف إلى دعم المواهب الأدبية السعودية وتطويرها.</p>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>

        <?php if (!$hasResults): ?>
            <div class="no-results">
                <p> لم يتم العثور على نتائج <?php echo !empty($search_term) ? 'لـ "' . htmlspecialchars($search_term) . '"' : ''; ?></p>
                <?php if (!empty($search_term)): ?>
                    <p>حاول البحث بكلمات أخرى أو <a href="events.php">عرض جميع الفعاليات والأندية</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
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
        // Navigation
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('navMenu');
        const closeBtn = document.getElementById('closeBtn');

        hamburger.addEventListener('click', () => {
            navMenu.classList.add('show');
        });

        closeBtn.addEventListener('click', () => {
            navMenu.classList.remove('show');
        });

        document.addEventListener('click', (e) => {
            if (navMenu.classList.contains('show') &&
                !navMenu.contains(e.target) &&
                e.target !== hamburger) {
                navMenu.classList.remove('show');
            }
        });

        // filtering here
        function filterCategory(category) {
            const url = new URL(window.location.href);
            url.searchParams.set('category', category);
            window.location.href = url.toString();
        }

       // Book event function
        function bookEvent(eventId) {
            const formData = new FormData();
            formData.append('eventID', eventId);

            fetch('booking_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.text();
            })
            .then(data => {
                if (data === 'success') {
                    alert('تم الحجز بنجاح! تذكرتك موجودة بصفحة حجوزاتي!');
                } else if (data === 'already') {
                    alert('لديك حجز مسبق في هذه الفعالية, تذكرتك موجودة في صفحة حجوزاتي!');
                } else if (data === 'expired') {
                    alert('عذراً، هذه الفعالية منتهية ولا يمكن الحجز فيها');
                } else if (data === 'full') {
                    alert('عذراً، هذه الفعالية ممتلئة ولا يوجد أماكن متاحة');
                } else if (data === 'not_found') {
                    alert('الفعالية غير موجودة');
                } else {
                    alert('حدث خطأ في الحجز');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال');
            });
        }

        // Join club 
        function joinClub(clubId) {
            const formData = new FormData();
            formData.append('clubID', clubId);

            fetch('club_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.text();
            })
            .then(data => {
                if (data === 'success') {
                    alert('تم الانضمام بنجاح!');
                } else if (data === 'already') {
                    alert('أنت عضو بالفعل');
                } else {
                    alert('حدث خطأ في الانضمام');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال');
            });
        }

        // Bookmark 
       
function toggleBookmark(itemID, itemType, button) {
    const formData = new FormData();
    formData.append('itemID', itemID);
    formData.append('itemType', itemType);

    fetch('bookmark_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.text();
    })
    .then(data => {
        if (data === 'added') {
            button.classList.add('active');
            button.innerHTML = '★';
            alert('تمت الإضافة إلى المفضلة بنجاح!');
        } else if (data === 'removed') {
            button.classList.remove('active');
            button.innerHTML = '☆';
            alert('تمت الإزالة من المفضلة بنجاح!');
        } else if (data === 'error') {
            alert('حدث خطأ، الرجاء المحاولة مرة أخرى');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في الاتصال');
    });
}

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
        // Details button 
        document.querySelectorAll('.details-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const content = btn.nextElementSibling;
                if (content.style.maxHeight && content.style.maxHeight !== "0px") {
                    content.style.maxHeight = "0";
                    btn.innerText = "التفاصيل ▼";
                } else {
                    content.style.maxHeight = content.scrollHeight + "px";
                    btn.innerText = "التفاصيل ▲";
                }
            });
        });

        // countdwns
        function updateCountdowns() {
            document.querySelectorAll('.countdown').forEach(element => {
                const eventDate = new Date(element.getAttribute('data-event-date'));
                const now = new Date();
                const timeDiff = eventDate - now;
                
                if (timeDiff > 0) {
                    const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    
                    if (days > 0) {
                        element.innerHTML = `${days} يوم و ${hours} ساعة متبقية`;
                    } else {
                        element.innerHTML = `اليوم! ${hours} ساعة متبقية`;
                    }
                } else {
                    element.innerHTML = "انتهت الفعالية";
                }
            });
        }
        
        // gets all 
        const recommendedBtn = document.getElementById('recommendedBtn');
        const pageTitle = document.querySelector('.page-title');
        const pageContent = document.querySelector('.page-content');
        const categoryTabs = document.querySelector('.category-tabs');
        const searchContainer = document.querySelector('.search-container-new');
        let isRecommendedView = false;

        recommendedBtn.addEventListener('click', function() {
            if (!isRecommendedView) {
                // Switch to recommendations view
                this.textContent = 'العودة للكل';
                this.classList.add('back-btn');
                pageTitle.textContent = 'اقتراحات مِداد';
                pageContent.innerHTML = '<p>هذه الفعاليات والأندية مختارة خصيصاً لك بناءً على اهتماماتك الأدبية. استمتع باكتشاف محتوى يناسب ذوقك!</p>';

                categoryTabs.style.display = 'none';
                searchContainer.style.display = 'none';

                // no show first
                const allCards = document.querySelectorAll('.card');
                allCards.forEach(card => {
                    card.style.display = 'none';
                });

                // get from db intrests
                const userInterests = "<?php echo $user_interests ?? ''; ?>";
                
                if (!userInterests) {
                    // same as events and clubs
                    const eventCards = document.querySelectorAll('.event-card');
                    const randomEvents = Array.from(eventCards).sort(() => 0.5 - Math.random()).slice(0, 4);
                    randomEvents.forEach(card => card.style.display = 'block');
                } else {
                    // alo filter on itrest
                    const interestsArray = userInterests.split(',');
                    
                    allCards.forEach(card => {
                        const title = card.querySelector('h3')?.textContent || '';
                        const description = card.querySelector('p')?.textContent || '';
                        const cardText = (title + ' ' + description).toLowerCase();
                        
                        // matches the intrests
                        const hasMatchingInterest = interestsArray.some(interest => {
                            const cleanInterest = interest.trim().toLowerCase();
                            return cardText.includes(cleanInterest);
                        });
                        
                        if (hasMatchingInterest) {
                            card.style.display = 'block';
                        }
                    });

                    // if no match dounf
                    const visibleCards = document.querySelectorAll('.card[style="display: block"]');
                    if (visibleCards.length === 0) {
                        const eventCards = document.querySelectorAll('.event-card');
                        const randomEvents = Array.from(eventCards).sort(() => 0.5 - Math.random()).slice(0, 4);
                        randomEvents.forEach(card => card.style.display = 'block');
                    }
                }

                isRecommendedView = true;
            } else {
                // Return to normal view
                this.textContent = 'اقتراحات مِداد';
                this.classList.remove('back-btn');
                pageTitle.textContent = 'الفعاليات والأندية';
                pageContent.innerHTML = '<p>استكشف مجموعة متنوعة من الفعاليات والأندية الأدبية في المملكة العربية السعودية. سواء كنت تبحث عن أمسيات شعرية، ورش كتابة، ندوات ثقافية، أو نوادي أدبية، ستجد هنا كل ما يلهمك ويثري شغفك بالأدب.</p>';

                categoryTabs.style.display = 'flex';
                searchContainer.style.display = 'block';

                // Show all cards
                const allCards = document.querySelectorAll('.card');
                allCards.forEach(card => {
                    card.style.display = 'block';
                });
                document.querySelectorAll('.category-tab').forEach(t => {
                    t.classList.remove('active');
                });
                document.querySelector('[data-category="all"]').classList.add('active');

                isRecommendedView = false;
            }
        });
        // for the countdown
        setInterval(updateCountdowns, 60000);

        
        document.addEventListener('DOMContentLoaded', function() {
            updateCountdowns();
        });
    </script>
</body>
</html>